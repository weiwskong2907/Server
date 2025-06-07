<?php
/**
 * Admin helper functions
 */

/**
 * Get dashboard statistics
 */
function get_dashboard_stats() {
    $stats = [
        'users' => 0,
        'posts' => 0,
        'comments' => 0,
        'categories' => 0,
        'tags' => 0,
        'recent_activities' => [],
        'pending_users' => 0,
        'reported_content' => 0
    ];
    
    // Get user count
    $stats['users'] = get_row(
        "SELECT COUNT(*) as count FROM users",
        "i",
        []
    )['count'];
    
    // Get post count
    $stats['posts'] = get_row(
        "SELECT COUNT(*) as count FROM posts",
        "i",
        []
    )['count'];
    
    // Get comment count
    $stats['comments'] = get_row(
        "SELECT COUNT(*) as count FROM comments",
        "i",
        []
    )['count'];
    
    // Get category count
    $stats['categories'] = get_row(
        "SELECT COUNT(*) as count FROM categories",
        "i",
        []
    )['count'];
    
    // Get tag count
    $stats['tags'] = get_row(
        "SELECT COUNT(*) as count FROM tags",
        "i",
        []
    )['count'];
    
    // Get pending users count
    $stats['pending_users'] = get_row(
        "SELECT COUNT(*) as count FROM users WHERE status = 'pending'",
        "i",
        []
    )['count'];
    
    // Get reported content count
    $stats['reported_content'] = get_row(
        "SELECT COUNT(*) as count FROM reports WHERE status = 'pending'",
        "i",
        []
    )['count'];
    
    // Get recent activities
    $stats['recent_activities'] = get_rows(
        "SELECT al.*, u.username 
         FROM activity_logs al
         JOIN users u ON al.user_id = u.id
         ORDER BY al.created_at DESC 
         LIMIT 10",
        "i",
        []
    );
    
    return $stats;
}

/**
 * Get system settings
 */
function get_system_settings() {
    return get_rows(
        "SELECT * FROM settings",
        "i",
        []
    );
}

/**
 * Update system settings
 */
function update_system_settings($settings) {
    if (!is_admin()) {
        return ['success' => false, 'message' => 'Permission denied'];
    }
    
    $success = true;
    foreach ($settings as $key => $value) {
        $updated = update_data(
            'settings',
            ['value' => $value],
            'setting_key = ?',
            's',
            [$key]
        );
        
        if (!$updated) {
            $success = false;
        }
    }
    
    if ($success) {
        log_activity($_SESSION['user_id'], 'settings_update', 'system', 0, 'Updated system settings');
        return ['success' => true, 'message' => 'Settings updated successfully'];
    }
    
    return ['success' => false, 'message' => 'Failed to update settings'];
}

/**
 * Get reported content
 */
function get_reported_content($page = 1, $per_page = 20) {
    $offset = ($page - 1) * $per_page;
    
    // Get total count
    $total = get_row(
        "SELECT COUNT(*) as total FROM reports",
        "i",
        []
    )['total'];
    
    // Get reports
    $reports = get_rows(
        "SELECT r.*, 
                u.username as reporter_username,
                p.title as post_title,
                c.content as comment_content
         FROM reports r
         JOIN users u ON r.reporter_id = u.id
         LEFT JOIN posts p ON r.content_type = 'post' AND r.content_id = p.id
         LEFT JOIN comments c ON r.content_type = 'comment' AND r.content_id = c.id
         ORDER BY r.created_at DESC
         LIMIT ? OFFSET ?",
        "ii",
        [$per_page, $offset]
    );
    
    return [
        'reports' => $reports,
        'total' => $total,
        'pages' => ceil($total / $per_page),
        'current_page' => $page
    ];
}

/**
 * Handle report
 */
function handle_report($report_id, $action, $notes = '') {
    if (!is_admin()) {
        return ['success' => false, 'message' => 'Permission denied'];
    }
    
    // Get report
    $report = get_row(
        "SELECT * FROM reports WHERE id = ?",
        "i",
        [$report_id]
    );
    
    if (!$report) {
        return ['success' => false, 'message' => 'Report not found'];
    }
    
    // Update report status
    $updated = update_data(
        'reports',
        [
            'status' => 'resolved',
            'resolution' => $action,
            'admin_notes' => $notes,
            'resolved_at' => date('Y-m-d H:i:s'),
            'resolved_by' => $_SESSION['user_id']
        ],
        'id = ?',
        'i',
        [$report_id]
    );
    
    if (!$updated) {
        return ['success' => false, 'message' => 'Failed to update report'];
    }
    
    // Handle content based on action
    if ($action === 'delete') {
        if ($report['content_type'] === 'post') {
            delete_post($report['content_id']);
        } elseif ($report['content_type'] === 'comment') {
            delete_comment($report['content_id']);
        }
    } elseif ($action === 'warn') {
        // Send warning to content owner
        $content_owner = get_row(
            "SELECT user_id FROM {$report['content_type']}s WHERE id = ?",
            "i",
            [$report['content_id']]
        );
        
        if ($content_owner) {
            send_warning_email($content_owner['user_id'], $report['content_type'], $report['content_id']);
        }
    }
    
    // Log activity
    log_activity(
        $_SESSION['user_id'],
        'report_handled',
        $report['content_type'],
        $report['content_id'],
        "Handled report with action: $action"
    );
    
    return ['success' => true, 'message' => 'Report handled successfully'];
}

/**
 * Get user management data
 */
function get_user_management_data($page = 1, $per_page = 20) {
    $offset = ($page - 1) * $per_page;
    
    // Get total count
    $total = get_row(
        "SELECT COUNT(*) as total FROM users",
        "i",
        []
    )['total'];
    
    // Get users with detailed stats
    $users = get_rows(
        "SELECT u.*,
                COUNT(DISTINCT p.id) as post_count,
                COUNT(DISTINCT c.id) as comment_count,
                COUNT(DISTINCT r.id) as report_count
         FROM users u
         LEFT JOIN posts p ON u.id = p.user_id
         LEFT JOIN comments c ON u.id = c.user_id
         LEFT JOIN reports r ON (r.content_type = 'post' AND p.id = r.content_id) 
                            OR (r.content_type = 'comment' AND c.id = r.content_id)
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
 * Get content management data
 */
function get_content_management_data($type, $page = 1, $per_page = 20) {
    $offset = ($page - 1) * $per_page;
    
    // Get total count
    $total = get_row(
        "SELECT COUNT(*) as total FROM {$type}s",
        "i",
        []
    )['total'];
    
    // Get content with detailed stats
    $content = get_rows(
        "SELECT c.*,
                u.username,
                COUNT(DISTINCT cm.id) as comment_count,
                COUNT(DISTINCT r.id) as report_count
         FROM {$type}s c
         JOIN users u ON c.user_id = u.id
         LEFT JOIN comments cm ON c.id = cm.{$type}_id
         LEFT JOIN reports r ON r.content_type = ? AND r.content_id = c.id
         GROUP BY c.id
         ORDER BY c.created_at DESC
         LIMIT ? OFFSET ?",
        "sii",
        [$type, $per_page, $offset]
    );
    
    return [
        'content' => $content,
        'total' => $total,
        'pages' => ceil($total / $per_page),
        'current_page' => $page
    ];
}

/**
 * Get system logs
 */
function get_system_logs($page = 1, $per_page = 20) {
    $offset = ($page - 1) * $per_page;
    
    // Get total count
    $total = get_row(
        "SELECT COUNT(*) as total FROM activity_logs",
        "i",
        []
    )['total'];
    
    // Get logs
    $logs = get_rows(
        "SELECT al.*, u.username 
         FROM activity_logs al
         JOIN users u ON al.user_id = u.id
         ORDER BY al.created_at DESC
         LIMIT ? OFFSET ?",
        "ii",
        [$per_page, $offset]
    );
    
    return [
        'logs' => $logs,
        'total' => $total,
        'pages' => ceil($total / $per_page),
        'current_page' => $page
    ];
}

/**
 * Get backup data
 */
function get_backup_data() {
    $backups = [
        'users' => get_rows("SELECT * FROM users", "i", []),
        'posts' => get_rows("SELECT * FROM posts", "i", []),
        'comments' => get_rows("SELECT * FROM comments", "i", []),
        'categories' => get_rows("SELECT * FROM categories", "i", []),
        'tags' => get_rows("SELECT * FROM tags", "i", []),
        'settings' => get_rows("SELECT * FROM settings", "i", [])
    ];
    
    return $backups;
}

/**
 * Restore from backup
 */
function restore_from_backup($backup_data) {
    if (!is_admin()) {
        return ['success' => false, 'message' => 'Permission denied'];
    }
    
    $success = true;
    
    // Restore each table
    foreach ($backup_data as $table => $data) {
        // Clear existing data
        $cleared = execute_query(
            "TRUNCATE TABLE $table",
            "i",
            []
        );
        
        if (!$cleared) {
            $success = false;
            continue;
        }
        
        // Insert backup data
        foreach ($data as $row) {
            $columns = implode(', ', array_keys($row));
            $values = implode(', ', array_fill(0, count($row), '?'));
            
            $inserted = execute_query(
                "INSERT INTO $table ($columns) VALUES ($values)",
                str_repeat('s', count($row)),
                array_values($row)
            );
            
            if (!$inserted) {
                $success = false;
            }
        }
    }
    
    if ($success) {
        log_activity($_SESSION['user_id'], 'backup_restore', 'system', 0, 'Restored system from backup');
        return ['success' => true, 'message' => 'Backup restored successfully'];
    }
    
    return ['success' => false, 'message' => 'Failed to restore backup'];
} 