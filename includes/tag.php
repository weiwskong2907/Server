<?php
/**
 * Tag helper functions
 */

/**
 * Create tag
 */
function create_tag($name) {
    if (!is_admin()) {
        return ['success' => false, 'message' => 'Permission denied'];
    }
    
    // Validate input
    if (empty($name)) {
        return ['success' => false, 'message' => 'Tag name is required'];
    }
    
    // Check if tag exists
    $existing = get_row(
        "SELECT id FROM tags WHERE name = ?",
        "s",
        [$name]
    );
    
    if ($existing) {
        return ['success' => false, 'message' => 'Tag already exists'];
    }
    
    // Insert tag
    $tag_id = insert_data('tags', ['name' => $name]);
    
    if (!$tag_id) {
        return ['success' => false, 'message' => 'Failed to create tag'];
    }
    
    // Log activity
    log_activity($_SESSION['user_id'], 'tag_create', 'tag', $tag_id, 'Created new tag');
    
    return [
        'success' => true,
        'message' => 'Tag created successfully',
        'tag_id' => $tag_id
    ];
}

/**
 * Update tag
 */
function update_tag($tag_id, $name) {
    if (!is_admin()) {
        return ['success' => false, 'message' => 'Permission denied'];
    }
    
    // Validate input
    if (empty($name)) {
        return ['success' => false, 'message' => 'Tag name is required'];
    }
    
    // Check if tag exists
    $tag = get_row(
        "SELECT id FROM tags WHERE id = ?",
        "i",
        [$tag_id]
    );
    
    if (!$tag) {
        return ['success' => false, 'message' => 'Tag not found'];
    }
    
    // Check if new name conflicts with existing tag
    $existing = get_row(
        "SELECT id FROM tags WHERE name = ? AND id != ?",
        "si",
        [$name, $tag_id]
    );
    
    if ($existing) {
        return ['success' => false, 'message' => 'Tag name already exists'];
    }
    
    // Update tag
    $updated = update_data(
        'tags',
        ['name' => $name],
        'id = ?',
        'i',
        [$tag_id]
    );
    
    if (!$updated) {
        return ['success' => false, 'message' => 'Failed to update tag'];
    }
    
    // Log activity
    log_activity($_SESSION['user_id'], 'tag_update', 'tag', $tag_id, 'Updated tag');
    
    return ['success' => true, 'message' => 'Tag updated successfully'];
}

/**
 * Delete tag
 */
function delete_tag($tag_id) {
    if (!is_admin()) {
        return ['success' => false, 'message' => 'Permission denied'];
    }
    
    // Check if tag exists
    $tag = get_row(
        "SELECT id FROM tags WHERE id = ?",
        "i",
        [$tag_id]
    );
    
    if (!$tag) {
        return ['success' => false, 'message' => 'Tag not found'];
    }
    
    // Start transaction
    begin_transaction();
    
    try {
        // Delete tag associations
        delete_data('post_tags', 'tag_id = ?', 'i', [$tag_id]);
        
        // Delete tag
        $deleted = delete_data('tags', 'id = ?', 'i', [$tag_id]);
        
        if (!$deleted) {
            throw new Exception('Failed to delete tag');
        }
        
        // Log activity
        log_activity($_SESSION['user_id'], 'tag_delete', 'tag', $tag_id, 'Deleted tag');
        
        commit_transaction();
        
        return ['success' => true, 'message' => 'Tag deleted successfully'];
    } catch (Exception $e) {
        rollback_transaction();
        error_log("Tag deletion failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete tag'];
    }
}

/**
 * Get tag with details
 */
function get_tag($tag_id) {
    $tag = get_row(
        "SELECT * FROM tags WHERE id = ?",
        "i",
        [$tag_id]
    );
    
    if (!$tag) {
        return null;
    }
    
    // Get post count
    $tag['post_count'] = get_row(
        "SELECT COUNT(*) as count FROM post_tags WHERE tag_id = ?",
        "i",
        [$tag_id]
    )['count'];
    
    return $tag;
}

/**
 * Get all tags
 */
function get_tags() {
    $tags = get_rows(
        "SELECT t.*, COUNT(pt.post_id) as post_count 
         FROM tags t 
         LEFT JOIN post_tags pt ON t.id = pt.tag_id 
         GROUP BY t.id 
         ORDER BY t.name ASC"
    );
    
    return $tags;
}

/**
 * Get tags with pagination
 */
function get_tags_paginated($page = 1, $per_page = 20) {
    $offset = ($page - 1) * $per_page;
    
    // Get total count
    $total = get_row(
        "SELECT COUNT(*) as total FROM tags",
        "i",
        []
    )['total'];
    
    // Get tags
    $tags = get_rows(
        "SELECT t.*, COUNT(pt.post_id) as post_count 
         FROM tags t 
         LEFT JOIN post_tags pt ON t.id = pt.tag_id 
         GROUP BY t.id 
         ORDER BY t.name ASC 
         LIMIT ? OFFSET ?",
        "ii",
        [$per_page, $offset]
    );
    
    return [
        'tags' => $tags,
        'total' => $total,
        'pages' => ceil($total / $per_page),
        'current_page' => $page
    ];
}

/**
 * Get popular tags
 */
function get_popular_tags($limit = 10) {
    return get_rows(
        "SELECT t.*, COUNT(pt.post_id) as post_count 
         FROM tags t 
         JOIN post_tags pt ON t.id = pt.tag_id 
         GROUP BY t.id 
         ORDER BY post_count DESC 
         LIMIT ?",
        "i",
        [$limit]
    );
}

/**
 * Search tags
 */
function search_tags($query, $limit = 10) {
    return get_rows(
        "SELECT t.*, COUNT(pt.post_id) as post_count 
         FROM tags t 
         LEFT JOIN post_tags pt ON t.id = pt.tag_id 
         WHERE t.name LIKE ? 
         GROUP BY t.id 
         ORDER BY post_count DESC 
         LIMIT ?",
        "si",
        ["%$query%", $limit]
    );
} 