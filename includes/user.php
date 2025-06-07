<?php
/**
 * User helper functions
 */

/**
 * Get user with details
 */
function get_user($user_id) {
    $user = get_row(
        "SELECT id, username, email, role, status, created_at 
         FROM users 
         WHERE id = ?",
        "i",
        [$user_id]
    );
    
    if (!$user) {
        return null;
    }
    
    // Get post count
    $user['post_count'] = get_row(
        "SELECT COUNT(*) as count FROM posts WHERE user_id = ?",
        "i",
        [$user_id]
    )['count'];
    
    // Get comment count
    $user['comment_count'] = get_row(
        "SELECT COUNT(*) as count FROM comments WHERE user_id = ?",
        "i",
        [$user_id]
    )['count'];
    
    return $user;
}

/**
 * Get user profile
 */
function get_user_profile($user_id) {
    $user = get_row(
        "SELECT u.*, 
                COUNT(DISTINCT p.id) as post_count,
                COUNT(DISTINCT c.id) as comment_count
         FROM users u
         LEFT JOIN posts p ON u.id = p.user_id
         LEFT JOIN comments c ON u.id = c.user_id
         WHERE u.id = ?
         GROUP BY u.id",
        "i",
        [$user_id]
    );
    
    if (!$user) {
        return null;
    }
    
    // Get recent posts
    $user['recent_posts'] = get_rows(
        "SELECT id, title, created_at 
         FROM posts 
         WHERE user_id = ? 
         ORDER BY created_at DESC 
         LIMIT 5",
        "i",
        [$user_id]
    );
    
    // Get recent comments
    $user['recent_comments'] = get_rows(
        "SELECT c.*, p.title as post_title 
         FROM comments c
         JOIN posts p ON c.post_id = p.id
         WHERE c.user_id = ? 
         ORDER BY c.created_at DESC 
         LIMIT 5",
        "i",
        [$user_id]
    );
    
    return $user;
}

/**
 * Get users with pagination
 */
function get_users($page = 1, $per_page = 20) {
    $offset = ($page - 1) * $per_page;
    
    // Get total count
    $total = get_row(
        "SELECT COUNT(*) as total FROM users",
        "i",
        []
    )['total'];
    
    // Get users
    $users = get_rows(
        "SELECT u.*, 
                COUNT(DISTINCT p.id) as post_count,
                COUNT(DISTINCT c.id) as comment_count
         FROM users u
         LEFT JOIN posts p ON u.id = p.user_id
         LEFT JOIN comments c ON u.id = c.user_id
         GROUP BY u.id
         ORDER BY u.created_at DESC
         LIMIT ? OFFSET ?",
        "ii",
        [$per_page, $offset]
    );
    
    return [
        'users' => $users,
        'total' => $total,
        'pages' => ceil($total / $per_page),
        'current_page' => $page
    ];
}

/**
 * Search users
 */
function search_users($query, $limit = 10) {
    return get_rows(
        "SELECT u.*, 
                COUNT(DISTINCT p.id) as post_count,
                COUNT(DISTINCT c.id) as comment_count
         FROM users u
         LEFT JOIN posts p ON u.id = p.user_id
         LEFT JOIN comments c ON u.id = c.user_id
         WHERE u.username LIKE ? OR u.email LIKE ?
         GROUP BY u.id
         ORDER BY u.created_at DESC
         LIMIT ?",
        "ssi",
        ["%$query%", "%$query%", $limit]
    );
}

/**
 * Update user role
 */
function update_user_role($user_id, $role) {
    if (!is_admin()) {
        return ['success' => false, 'message' => 'Permission denied'];
    }
    
    // Validate role
    $valid_roles = ['user', 'moderator', 'admin'];
    if (!in_array($role, $valid_roles)) {
        return ['success' => false, 'message' => 'Invalid role'];
    }
    
    // Check if user exists
    $user = get_row(
        "SELECT id FROM users WHERE id = ?",
        "i",
        [$user_id]
    );
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    // Update role
    $updated = update_data(
        'users',
        ['role' => $role],
        'id = ?',
        'i',
        [$user_id]
    );
    
    if (!$updated) {
        return ['success' => false, 'message' => 'Failed to update user role'];
    }
    
    // Log activity
    log_activity($_SESSION['user_id'], 'role_update', 'user', $user_id, "Updated user role to $role");
    
    return ['success' => true, 'message' => 'User role updated successfully'];
}

/**
 * Update user status
 */
function update_user_status($user_id, $status) {
    if (!is_admin()) {
        return ['success' => false, 'message' => 'Permission denied'];
    }
    
    // Validate status
    $valid_statuses = ['pending', 'active', 'suspended'];
    if (!in_array($status, $valid_statuses)) {
        return ['success' => false, 'message' => 'Invalid status'];
    }
    
    // Check if user exists
    $user = get_row(
        "SELECT id FROM users WHERE id = ?",
        "i",
        [$user_id]
    );
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    // Update status
    $updated = update_data(
        'users',
        ['status' => $status],
        'id = ?',
        'i',
        [$user_id]
    );
    
    if (!$updated) {
        return ['success' => false, 'message' => 'Failed to update user status'];
    }
    
    // Log activity
    log_activity($_SESSION['user_id'], 'status_update', 'user', $user_id, "Updated user status to $status");
    
    return ['success' => true, 'message' => 'User status updated successfully'];
}

/**
 * Get user activity
 */
function get_user_activity($user_id, $page = 1, $per_page = 20) {
    $offset = ($page - 1) * $per_page;
    
    // Get total count
    $total = get_row(
        "SELECT COUNT(*) as total FROM activity_logs WHERE user_id = ?",
        "i",
        [$user_id]
    )['total'];
    
    // Get activity logs
    $logs = get_rows(
        "SELECT * FROM activity_logs 
         WHERE user_id = ? 
         ORDER BY created_at DESC 
         LIMIT ? OFFSET ?",
        "iii",
        [$user_id, $per_page, $offset]
    );
    
    return [
        'logs' => $logs,
        'total' => $total,
        'pages' => ceil($total / $per_page),
        'current_page' => $page
    ];
}

/**
 * Get user statistics
 */
function get_user_statistics($user_id) {
    $stats = [
        'posts' => 0,
        'comments' => 0,
        'likes' => 0,
        'views' => 0
    ];
    
    // Get post count
    $stats['posts'] = get_row(
        "SELECT COUNT(*) as count FROM posts WHERE user_id = ?",
        "i",
        [$user_id]
    )['count'];
    
    // Get comment count
    $stats['comments'] = get_row(
        "SELECT COUNT(*) as count FROM comments WHERE user_id = ?",
        "i",
        [$user_id]
    )['count'];
    
    // Get total views on user's posts
    $stats['views'] = get_row(
        "SELECT SUM(views) as total FROM posts WHERE user_id = ?",
        "i",
        [$user_id]
    )['total'] ?? 0;
    
    return $stats;
} 