<?php
/**
 * Post helper functions
 */

/**
 * Create new post
 */
function create_post($user_id, $title, $content, $category_id = null, $tags = [], $attachments = []) {
    // Validate input
    if (empty($title) || empty($content)) {
        return ['success' => false, 'message' => 'Title and content are required'];
    }
    
    // Start transaction
    begin_transaction();
    
    try {
        // Insert post
        $post_data = [
            'user_id' => $user_id,
            'title' => $title,
            'content' => $content,
            'category_id' => $category_id
        ];
        
        $post_id = insert_data('posts', $post_data);
        
        if (!$post_id) {
            throw new Exception('Failed to create post');
        }
        
        // Add tags
        if (!empty($tags)) {
            foreach ($tags as $tag_name) {
                // Get or create tag
                $tag = get_row(
                    "SELECT id FROM tags WHERE name = ?",
                    "s",
                    [$tag_name]
                );
                
                if (!$tag) {
                    $tag_id = insert_data('tags', ['name' => $tag_name]);
                } else {
                    $tag_id = $tag['id'];
                }
                
                // Link tag to post
                insert_data('post_tags', [
                    'post_id' => $post_id,
                    'tag_id' => $tag_id
                ]);
            }
        }
        
        // Handle attachments
        if (!empty($attachments)) {
            foreach ($attachments as $file) {
                $upload_result = secure_file_upload($file);
                
                if (!$upload_result['success']) {
                    throw new Exception('Failed to upload attachment: ' . implode(', ', $upload_result['errors']));
                }
                
                // Save attachment info
                insert_data('attachments', [
                    'post_id' => $post_id,
                    'filename' => $upload_result['filename'],
                    'original_filename' => $upload_result['original_filename'],
                    'file_type' => $upload_result['file_type']
                ]);
            }
        }
        
        // Log activity
        log_activity($user_id, 'post_create', 'post', $post_id, 'Created new post');
        
        commit_transaction();
        
        return [
            'success' => true,
            'message' => 'Post created successfully',
            'post_id' => $post_id
        ];
    } catch (Exception $e) {
        rollback_transaction();
        error_log("Post creation failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to create post'];
    }
}

/**
 * Update post
 */
function update_post($post_id, $user_id, $data) {
    // Check if user owns the post or is admin
    $post = get_row(
        "SELECT user_id FROM posts WHERE id = ?",
        "i",
        [$post_id]
    );
    
    if (!$post) {
        return ['success' => false, 'message' => 'Post not found'];
    }
    
    if ($post['user_id'] != $user_id && !is_admin()) {
        return ['success' => false, 'message' => 'Permission denied'];
    }
    
    // Validate input
    if (empty($data['title']) || empty($data['content'])) {
        return ['success' => false, 'message' => 'Title and content are required'];
    }
    
    // Start transaction
    begin_transaction();
    
    try {
        // Update post
        $update_data = [
            'title' => $data['title'],
            'content' => $data['content'],
            'category_id' => $data['category_id'] ?? null
        ];
        
        $updated = update_data(
            'posts',
            $update_data,
            'id = ?',
            'i',
            [$post_id]
        );
        
        if (!$updated) {
            throw new Exception('Failed to update post');
        }
        
        // Update tags if provided
        if (isset($data['tags'])) {
            // Remove existing tags
            delete_data('post_tags', 'post_id = ?', 'i', [$post_id]);
            
            // Add new tags
            foreach ($data['tags'] as $tag_name) {
                // Get or create tag
                $tag = get_row(
                    "SELECT id FROM tags WHERE name = ?",
                    "s",
                    [$tag_name]
                );
                
                if (!$tag) {
                    $tag_id = insert_data('tags', ['name' => $tag_name]);
                } else {
                    $tag_id = $tag['id'];
                }
                
                // Link tag to post
                insert_data('post_tags', [
                    'post_id' => $post_id,
                    'tag_id' => $tag_id
                ]);
            }
        }
        
        // Handle new attachments if provided
        if (!empty($data['attachments'])) {
            foreach ($data['attachments'] as $file) {
                $upload_result = secure_file_upload($file);
                
                if (!$upload_result['success']) {
                    throw new Exception('Failed to upload attachment: ' . implode(', ', $upload_result['errors']));
                }
                
                // Save attachment info
                insert_data('attachments', [
                    'post_id' => $post_id,
                    'filename' => $upload_result['filename'],
                    'original_filename' => $upload_result['original_filename'],
                    'file_type' => $upload_result['file_type']
                ]);
            }
        }
        
        // Log activity
        log_activity($user_id, 'post_update', 'post', $post_id, 'Updated post');
        
        commit_transaction();
        
        return ['success' => true, 'message' => 'Post updated successfully'];
    } catch (Exception $e) {
        rollback_transaction();
        error_log("Post update failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update post'];
    }
}

/**
 * Delete post
 */
function delete_post($post_id, $user_id) {
    // Check if user owns the post or is admin
    $post = get_row(
        "SELECT user_id FROM posts WHERE id = ?",
        "i",
        [$post_id]
    );
    
    if (!$post) {
        return ['success' => false, 'message' => 'Post not found'];
    }
    
    if ($post['user_id'] != $user_id && !is_admin()) {
        return ['success' => false, 'message' => 'Permission denied'];
    }
    
    // Start transaction
    begin_transaction();
    
    try {
        // Get attachments
        $attachments = get_rows(
            "SELECT filename FROM attachments WHERE post_id = ?",
            "i",
            [$post_id]
        );
        
        // Delete attachments from filesystem
        foreach ($attachments as $attachment) {
            $file_path = __DIR__ . '/../uploads/' . $attachment['filename'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Delete attachments from database
        delete_data('attachments', 'post_id = ?', 'i', [$post_id]);
        
        // Delete post tags
        delete_data('post_tags', 'post_id = ?', 'i', [$post_id]);
        
        // Delete comments
        delete_data('comments', 'post_id = ?', 'i', [$post_id]);
        
        // Delete post
        $deleted = delete_data('posts', 'id = ?', 'i', [$post_id]);
        
        if (!$deleted) {
            throw new Exception('Failed to delete post');
        }
        
        // Log activity
        log_activity($user_id, 'post_delete', 'post', $post_id, 'Deleted post');
        
        commit_transaction();
        
        return ['success' => true, 'message' => 'Post deleted successfully'];
    } catch (Exception $e) {
        rollback_transaction();
        error_log("Post deletion failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete post'];
    }
}

/**
 * Get post with details
 */
function get_post($post_id) {
    $post = get_row(
        "SELECT p.*, u.username, c.name as category_name 
         FROM posts p 
         LEFT JOIN users u ON p.user_id = u.id 
         LEFT JOIN categories c ON p.category_id = c.id 
         WHERE p.id = ?",
        "i",
        [$post_id]
    );
    
    if (!$post) {
        return null;
    }
    
    // Get tags
    $post['tags'] = get_rows(
        "SELECT t.name 
         FROM tags t 
         JOIN post_tags pt ON t.id = pt.tag_id 
         WHERE pt.post_id = ?",
        "i",
        [$post_id]
    );
    
    // Get attachments
    $post['attachments'] = get_rows(
        "SELECT * FROM attachments WHERE post_id = ?",
        "i",
        [$post_id]
    );
    
    // Get comments
    $post['comments'] = get_rows(
        "SELECT c.*, u.username 
         FROM comments c 
         JOIN users u ON c.user_id = u.id 
         WHERE c.post_id = ? AND c.status = 'approved' 
         ORDER BY c.created_at ASC",
        "i",
        [$post_id]
    );
    
    return $post;
}

/**
 * Get posts with pagination
 */
function get_posts($page = 1, $per_page = 10, $category_id = null, $tag = null, $search = null) {
    $offset = ($page - 1) * $per_page;
    $params = [];
    $types = "";
    $where = "1=1";
    
    if ($category_id) {
        $where .= " AND p.category_id = ?";
        $params[] = $category_id;
        $types .= "i";
    }
    
    if ($tag) {
        $where .= " AND t.name = ?";
        $params[] = $tag;
        $types .= "s";
    }
    
    if ($search) {
        $where .= " AND (p.title LIKE ? OR p.content LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "ss";
    }
    
    // Get total count
    $count_query = "
        SELECT COUNT(DISTINCT p.id) as total 
        FROM posts p 
        LEFT JOIN post_tags pt ON p.id = pt.post_id 
        LEFT JOIN tags t ON pt.tag_id = t.id 
        WHERE $where
    ";
    
    $total = get_row($count_query, $types, $params)['total'];
    
    // Get posts
    $query = "
        SELECT DISTINCT p.*, u.username, c.name as category_name 
        FROM posts p 
        LEFT JOIN users u ON p.user_id = u.id 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN post_tags pt ON p.id = pt.post_id 
        LEFT JOIN tags t ON pt.tag_id = t.id 
        WHERE $where 
        ORDER BY p.created_at DESC 
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $per_page;
    $params[] = $offset;
    $types .= "ii";
    
    $posts = get_rows($query, $types, $params);
    
    // Get tags and attachments for each post
    foreach ($posts as &$post) {
        $post['tags'] = get_rows(
            "SELECT t.name 
             FROM tags t 
             JOIN post_tags pt ON t.id = pt.tag_id 
             WHERE pt.post_id = ?",
            "i",
            [$post['id']]
        );
        
        $post['attachments'] = get_rows(
            "SELECT * FROM attachments WHERE post_id = ?",
            "i",
            [$post['id']]
        );
    }
    
    return [
        'posts' => $posts,
        'total' => $total,
        'pages' => ceil($total / $per_page),
        'current_page' => $page
    ];
} 