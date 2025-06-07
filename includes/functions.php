<?php
// File upload handling functions
function get_allowed_file_types() {
    return [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
    ];
}

function validate_file_upload($file) {
    $allowed_types = get_allowed_file_types();
    $max_size = 5 * 1024 * 1024; // 5MB
    $errors = [];
    
    if ($file['size'] > $max_size) {
        $errors[] = "File size must be less than 5MB";
    }
    
    if (!isset($allowed_types[$file['type']])) {
        $errors[] = "File type not allowed. Allowed types: JPG, PNG, GIF, PDF, DOC, DOCX";
    }
    
    return $errors;
}

function save_uploaded_file($file) {
    $allowed_types = get_allowed_file_types();
    $extension = $allowed_types[$file['type']];
    $filename = uniqid() . '.' . $extension;
    $upload_path = __DIR__ . '/../uploads/' . $filename;
    error_log("Attempting to save file to: " . $upload_path);
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return $filename;
    }
    
    return false;
}

function is_admin($user_id) {
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn() === 'admin';
    } catch (Exception $e) {
        error_log("Error checking admin status: " . $e->getMessage());
        return false;
    }
}

/**
 * Get username by user ID
 */
function getUsernameById($user_id) {
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Error getting username: " . $e->getMessage());
        return null;
    }
}

/**
 * Get paginated posts
 * @param int $page
 * @param int $per_page
 * @return array
 */
function get_posts_paginated($page = 1, $per_page = 10) {
    try {
        $pdo = get_db_connection();
        $page = max(1, (int)$page);
        $per_page = max(1, (int)$per_page);
        $offset = ($page - 1) * $per_page;
        
        $query = "SELECT p.*, u.username, c.name as category_name,
                  (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                  FROM posts p
                  LEFT JOIN users u ON p.user_id = u.id
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.status = 'published'
                  ORDER BY p.created_at DESC
                  LIMIT :offset, :per_page";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
        $stmt->execute();
        $posts = $stmt->fetchAll();
        
        // Get total count for pagination
        $stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'");
        $total = $stmt->fetchColumn();
        
        return [
            'posts' => $posts,
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'current_page' => $page
        ];
    } catch (Exception $e) {
        error_log("Error getting paginated posts: " . $e->getMessage());
        return [
            'posts' => [],
            'total' => 0,
            'pages' => 0,
            'current_page' => $page
        ];
    }
}

/**
 * Get categories
 * @return array
 */
function get_categories() {
    try {
        $pdo = get_db_connection();
        $query = "SELECT c.*, COUNT(p.id) as post_count
                  FROM categories c
                  LEFT JOIN posts p ON c.id = p.category_id AND p.status = 'published'
                  GROUP BY c.id
                  ORDER BY c.name";
        
        $stmt = $pdo->query($query);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting categories: " . $e->getMessage());
        return [];
    }
}

/**
 * Get popular tags
 * @param int $limit
 * @return array
 */
function get_popular_tags($limit = 10) {
    try {
        $pdo = get_db_connection();
        $query = "SELECT t.name, COUNT(pt.post_id) as count
                  FROM tags t
                  JOIN post_tags pt ON t.id = pt.tag_id
                  JOIN posts p ON pt.post_id = p.id AND p.status = 'published'
                  GROUP BY t.id
                  ORDER BY count DESC
                  LIMIT :limit";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting popular tags: " . $e->getMessage());
        return [];
    }
}

/**
 * Get post by ID
 * @param int $post_id
 * @return array|null
 */
function get_post($post_id) {
    try {
        $pdo = get_db_connection();
        $query = "SELECT p.*, u.username, c.name as category_name
                  FROM posts p
                  LEFT JOIN users u ON p.user_id = u.id
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.id = :post_id AND p.status = 'published'";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->execute();
        $post = $stmt->fetch();
        
        if ($post) {
            // Get tags
            $query = "SELECT t.name
                     FROM tags t
                     JOIN post_tags pt ON t.id = pt.tag_id
                     WHERE pt.post_id = :post_id";
            
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
            $stmt->execute();
            $post['tags'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        return $post;
    } catch (Exception $e) {
        error_log("Error getting post: " . $e->getMessage());
        return null;
    }
}

/**
 * Get related posts
 * @param int $post_id
 * @param int $limit
 * @return array
 */
function get_related_posts($post_id, $limit = 3) {
    try {
        $pdo = get_db_connection();
        $query = "SELECT p.*, u.username
                  FROM posts p
                  LEFT JOIN users u ON p.user_id = u.id
                  WHERE p.id != :post_id 
                  AND p.status = 'published'
                  AND p.category_id = (SELECT category_id FROM posts WHERE id = :post_id)
                  ORDER BY p.created_at DESC
                  LIMIT :limit";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting related posts: " . $e->getMessage());
        return [];
    }
}

/**
 * Get paginated comments
 * @param int $post_id
 * @param int $page
 * @param int $per_page
 * @return array
 */
function get_comments_paginated($post_id, $page = 1, $per_page = 10) {
    try {
        $pdo = get_db_connection();
        $page = max(1, (int)$page);
        $per_page = max(1, (int)$per_page);
        $offset = ($page - 1) * $per_page;
        
        $query = "SELECT c.*, u.username
                  FROM comments c
                  LEFT JOIN users u ON c.user_id = u.id
                  WHERE c.post_id = :post_id
                  ORDER BY c.created_at DESC
                  LIMIT :offset, :per_page";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
        $stmt->execute();
        $comments = $stmt->fetchAll();
        
        // Get total count for pagination
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = :post_id");
        $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->execute();
        $total = $stmt->fetchColumn();
        
        return [
            'comments' => $comments,
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'current_page' => $page
        ];
    } catch (Exception $e) {
        error_log("Error getting paginated comments: " . $e->getMessage());
        return [
            'comments' => [],
            'total' => 0,
            'pages' => 0,
            'current_page' => $page
        ];
    }
}

/**
 * Increment post views
 * @param int $post_id
 */
function increment_post_views($post_id) {
    try {
        $pdo = get_db_connection();
        $query = "UPDATE posts SET views = views + 1 WHERE id = :post_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Error incrementing post views: " . $e->getMessage());
    }
}

/**
 * Format date
 * @param string $date
 * @return string
 */
function format_date($date) {
    return date('F j, Y', strtotime($date));
}

/**
 * Truncate text
 * @param string $text
 * @param int $length
 * @return string
 */
function truncate_text($text, $length = 200) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

/**
 * Generate slug
 * @param string $text
 * @return string
 */
function generate_slug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text ?: 'n-a';
}

/**
 * Sanitize output
 * @param string $text
 * @return string
 */
function sanitize_output($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
?>