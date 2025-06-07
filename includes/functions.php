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

// Add this function to your existing functions.php
function log_activity($action_type, $entity_type, $entity_id, $description = '') {
    global $pdo;
    
    $stmt = $pdo->prepare(
        "INSERT INTO activity_logs (user_id, action_type, entity_type, entity_id, description)
         VALUES (?, ?, ?, ?, ?)"
    );
    
    return $stmt->execute([
        isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
        $action_type,
        $entity_type,
        $entity_id,
        $description
    ]);
}

function is_admin($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Get username by user ID
 */
function getUsernameById($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

/**
 * Get paginated posts
 * @param int $page
 * @param int $per_page
 * @return array
 */
function get_posts_paginated($page = 1, $per_page = 10) {
    global $conn;
    
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
              LIMIT $offset, $per_page";
    
    $result = mysqli_query($conn, $query);
    $posts = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $posts[] = $row;
    }
    
    // Get total count for pagination
    $query = "SELECT COUNT(*) as total FROM posts WHERE status = 'published'";
    $result = mysqli_query($conn, $query);
    $total = mysqli_fetch_assoc($result)['total'];
    
    return [
        'posts' => $posts,
        'total' => $total,
        'pages' => ceil($total / $per_page),
        'current_page' => $page
    ];
}

/**
 * Get categories
 * @return array
 */
function get_categories() {
    global $conn;
    
    $query = "SELECT c.*, COUNT(p.id) as post_count
              FROM categories c
              LEFT JOIN posts p ON c.id = p.category_id AND p.status = 'published'
              GROUP BY c.id
              ORDER BY c.name";
    
    $result = mysqli_query($conn, $query);
    $categories = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    
    return $categories;
}

/**
 * Get popular tags
 * @param int $limit
 * @return array
 */
function get_popular_tags($limit = 10) {
    global $conn;
    
    $limit = (int)$limit;
    $query = "SELECT t.name, COUNT(pt.post_id) as count
              FROM tags t
              JOIN post_tags pt ON t.id = pt.tag_id
              JOIN posts p ON pt.post_id = p.id AND p.status = 'published'
              GROUP BY t.id
              ORDER BY count DESC
              LIMIT $limit";
    
    $result = mysqli_query($conn, $query);
    $tags = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $tags[] = $row;
    }
    
    return $tags;
}

/**
 * Get post by ID
 * @param int $post_id
 * @return array|null
 */
function get_post($post_id) {
    global $conn;
    
    $post_id = (int)$post_id;
    $query = "SELECT p.*, u.username, c.name as category_name
              FROM posts p
              LEFT JOIN users u ON p.user_id = u.id
              LEFT JOIN categories c ON p.category_id = c.id
              WHERE p.id = $post_id AND p.status = 'published'";
    
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $post = mysqli_fetch_assoc($result);
        
        // Get tags
        $query = "SELECT t.name
                 FROM tags t
                 JOIN post_tags pt ON t.id = pt.tag_id
                 WHERE pt.post_id = $post_id";
        
        $result = mysqli_query($conn, $query);
        $tags = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $tags[] = $row['name'];
        }
        
        $post['tags'] = $tags;
        return $post;
    }
    
    return null;
}

/**
 * Get related posts
 * @param int $post_id
 * @param int $limit
 * @return array
 */
function get_related_posts($post_id, $limit = 3) {
    global $conn;
    
    $post_id = (int)$post_id;
    $limit = (int)$limit;
    
    // Get post's category and tags
    $query = "SELECT p.category_id, GROUP_CONCAT(t.id) as tag_ids
              FROM posts p
              LEFT JOIN post_tags pt ON p.id = pt.post_id
              LEFT JOIN tags t ON pt.tag_id = t.id
              WHERE p.id = $post_id
              GROUP BY p.id";
    
    $result = mysqli_query($conn, $query);
    $post = mysqli_fetch_assoc($result);
    
    if (!$post) {
        return [];
    }
    
    // Get related posts by category and tags
    $query = "SELECT DISTINCT p.*, u.username
              FROM posts p
              LEFT JOIN users u ON p.user_id = u.id
              LEFT JOIN post_tags pt ON p.id = pt.post_id
              WHERE p.id != $post_id
              AND p.status = 'published'
              AND (p.category_id = {$post['category_id']}
                   OR pt.tag_id IN ({$post['tag_ids']}))
              ORDER BY p.created_at DESC
              LIMIT $limit";
    
    $result = mysqli_query($conn, $query);
    $posts = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $posts[] = $row;
    }
    
    return $posts;
}

/**
 * Get paginated comments
 * @param int $post_id
 * @param int $page
 * @param int $per_page
 * @return array
 */
function get_comments_paginated($post_id, $page = 1, $per_page = 10) {
    global $conn;
    
    $post_id = (int)$post_id;
    $page = max(1, (int)$page);
    $per_page = max(1, (int)$per_page);
    $offset = ($page - 1) * $per_page;
    
    $query = "SELECT c.*, u.username
              FROM comments c
              LEFT JOIN users u ON c.user_id = u.id
              WHERE c.post_id = $post_id AND c.status = 'approved'
              ORDER BY c.created_at DESC
              LIMIT $offset, $per_page";
    
    $result = mysqli_query($conn, $query);
    $comments = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $comments[] = $row;
    }
    
    // Get total count for pagination
    $query = "SELECT COUNT(*) as total
              FROM comments
              WHERE post_id = $post_id AND status = 'approved'";
    
    $result = mysqli_query($conn, $query);
    $total = mysqli_fetch_assoc($result)['total'];
    
    return [
        'comments' => $comments,
        'total' => $total,
        'pages' => ceil($total / $per_page),
        'current_page' => $page
    ];
}

/**
 * Increment post views
 * @param int $post_id
 */
function increment_post_views($post_id) {
    global $conn;
    
    $post_id = (int)$post_id;
    $query = "UPDATE posts SET views = views + 1 WHERE id = $post_id";
    mysqli_query($conn, $query);
}

/**
 * Format date
 * @param string $date
 * @return string
 */
function format_date($date) {
    return date('F j, Y g:i a', strtotime($date));
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
    // Convert to lowercase
    $text = strtolower($text);
    
    // Replace non-alphanumeric characters with hyphens
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    
    // Remove leading/trailing hyphens
    $text = trim($text, '-');
    
    return $text;
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