<?php
/**
 * General utility functions
 */

// Prevent direct access
if (!defined('SECURE_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * Check if user is admin
 * @param int $userId
 * @return bool
 */
function is_admin($userId) {
    try {
        $sql = "SELECT role FROM users WHERE id = ?";
        $result = db_query_one($sql, [$userId]);
        return $result && $result['role'] === 'admin';
    } catch (Exception $e) {
        error_log("Error checking admin status: " . $e->getMessage());
        return false;
    }
}

/**
 * Get username by ID
 * @param int $userId
 * @return string
 */
function getUsernameById($userId) {
    try {
        $sql = "SELECT username FROM users WHERE id = ?";
        $result = db_query_one($sql, [$userId]);
        return $result ? $result['username'] : 'Unknown User';
    } catch (Exception $e) {
        error_log("Error getting username: " . $e->getMessage());
        return 'Unknown User';
    }
}

/**
 * Get paginated posts
 * @param int $page
 * @param int $perPage
 * @return array
 */
function get_posts_paginated($page = 1, $perPage = 10) {
    try {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.*, u.username, c.name as category_name 
                FROM posts p 
                LEFT JOIN users u ON p.user_id = u.id 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.status = 'published' 
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";
        return db_query($sql, [$perPage, $offset]);
    } catch (Exception $e) {
        error_log("Error getting paginated posts: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all categories
 * @return array
 */
function get_categories() {
    try {
        $sql = "SELECT * FROM categories ORDER BY name ASC";
        return db_query($sql);
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
        $sql = "SELECT t.*, COUNT(pt.post_id) as post_count 
                FROM tags t 
                LEFT JOIN post_tags pt ON t.id = pt.tag_id 
                GROUP BY t.id 
                ORDER BY post_count DESC 
                LIMIT ?";
        return db_query($sql, [$limit]);
    } catch (Exception $e) {
        error_log("Error getting popular tags: " . $e->getMessage());
        return [];
    }
}

/**
 * Get single post with related data
 * @param int $postId
 * @return array|false
 */
function get_post($postId) {
    try {
        $sql = "SELECT p.*, u.username, c.name as category_name 
                FROM posts p 
                LEFT JOIN users u ON p.user_id = u.id 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id = ? AND p.status = 'published'";
        return db_query_one($sql, [$postId]);
    } catch (Exception $e) {
        error_log("Error getting post: " . $e->getMessage());
        return false;
    }
}

/**
 * Get related posts
 * @param int $postId
 * @param int $limit
 * @return array
 */
function get_related_posts($postId, $limit = 3) {
    try {
        $sql = "SELECT p.*, u.username 
                FROM posts p 
                LEFT JOIN users u ON p.user_id = u.id 
                WHERE p.id != ? AND p.status = 'published' 
                ORDER BY RAND() 
                LIMIT ?";
        return db_query($sql, [$postId, $limit]);
    } catch (Exception $e) {
        error_log("Error getting related posts: " . $e->getMessage());
        return [];
    }
}

/**
 * Get paginated comments
 * @param int $postId
 * @param int $page
 * @param int $perPage
 * @return array
 */
function get_comments_paginated($postId, $page = 1, $perPage = 10) {
    try {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT c.*, u.username 
                FROM comments c 
                LEFT JOIN users u ON c.user_id = u.id 
                WHERE c.post_id = ? AND c.status = 'approved' 
                ORDER BY c.created_at DESC 
                LIMIT ? OFFSET ?";
        return db_query($sql, [$postId, $perPage, $offset]);
    } catch (Exception $e) {
        error_log("Error getting paginated comments: " . $e->getMessage());
        return [];
    }
}

/**
 * Increment post views
 * @param int $postId
 * @return bool
 */
function increment_post_views($postId) {
    try {
        $sql = "UPDATE posts SET views = views + 1 WHERE id = ?";
        return db_update('posts', ['views' => 'views + 1'], 'id = ?', [$postId]) > 0;
    } catch (Exception $e) {
        error_log("Error incrementing post views: " . $e->getMessage());
        return false;
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
 * Generate slug from title
 * @param string $title
 * @return string
 */
function generate_slug($title) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    return $slug;
}

/**
 * Handle file upload
 * @param array $file
 * @param string $destination
 * @return string|false
 */
function handle_file_upload($file, $destination) {
    try {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Invalid file parameters');
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('File too large');
            case UPLOAD_ERR_PARTIAL:
                throw new Exception('File upload incomplete');
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('No file uploaded');
            case UPLOAD_ERR_NO_TMP_DIR:
                throw new Exception('Missing temporary folder');
            case UPLOAD_ERR_CANT_WRITE:
                throw new Exception('Failed to write file');
            case UPLOAD_ERR_EXTENSION:
                throw new Exception('File upload stopped by extension');
            default:
                throw new Exception('Unknown upload error');
        }

        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('File too large');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file['tmp_name']);

        if (!in_array($mime_type, ALLOWED_FILE_TYPES)) {
            throw new Exception('Invalid file type');
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $filepath = $destination . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to move uploaded file');
        }

        return $filename;
    } catch (Exception $e) {
        error_log("File upload error: " . $e->getMessage());
        return false;
    }
}