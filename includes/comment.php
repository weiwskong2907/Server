<?php
/**
 * Comment helper functions
 */

/**
 * Add comment
 */
function add_comment($post_id, $user_id, $content) {
    // Validate input
    if (empty($content)) {
        return ['success' => false, 'message' => 'Comment content is required'];
    }
    
    // Check if post exists
    $post = get_row(
        "SELECT id FROM posts WHERE id = ?",
        "i",
        [$post_id]
    );
    
    if (!$post) {
        return ['success' => false, 'message' => 'Post not found'];
    }
    
    // Insert comment
    $comment_data = [
        'post_id' => $post_id,
        'user_id' => $user_id,
        'content' => $content,
        'status' => 'pending'
    ];
    
    $comment_id = insert_data('comments', $comment_data);
    
    if (!$comment_id) {
        return ['success' => false, 'message' => 'Failed to add comment'];
    }
    
    // Log activity
    log_activity($user_id, 'comment_create', 'comment', $comment_id, 'Added new comment');
    
    return [
        'success' => true,
        'message' => 'Comment added successfully and awaiting moderation',
        'comment_id' => $comment_id
    ];
}

/**
 * Update comment
 */
function update_comment($comment_id, $user_id, $content) {
    // Validate input
    if (empty($content)) {
        return ['success' => false, 'message' => 'Comment content is required'];
    }
    
    // Check if comment exists and user owns it
    $comment = get_row(
        "SELECT user_id FROM comments WHERE id = ?",
        "i",
        [$comment_id]
    );
    
    if (!$comment) {
        return ['success' => false, 'message' => 'Comment not found'];
    }
    
    if ($comment['user_id'] != $user_id && !is_admin()) {
        return ['success' => false, 'message' => 'Permission denied'];
    }
    
    // Update comment
    $updated = update_data(
        'comments',
        ['content' => $content, 'status' => 'pending'],
        'id = ?',
        'i',
        [$comment_id]
    );
    
    if (!$updated) {
        return ['success' => false, 'message' => 'Failed to update comment'];
    }
    
    // Log activity
    log_activity($user_id, 'comment_update', 'comment', $comment_id, 'Updated comment');
    
    return ['success' => true, 'message' => 'Comment updated successfully and awaiting moderation'];
}

/**
 * Delete comment
 */
function delete_comment($comment_id, $user_id) {
    // Check if comment exists and user owns it or is admin
    $comment = get_row(
        "SELECT user_id FROM comments WHERE id = ?",
        "i",
        [$comment_id]
    );
    
    if (!$comment) {
        return ['success' => false, 'message' => 'Comment not found'];
    }
    
    if ($comment['user_id'] != $user_id && !is_admin()) {
        return ['success' => false, 'message' => 'Permission denied'];
    }
    
    // Delete comment
    $deleted = delete_data('comments', 'id = ?', 'i', [$comment_id]);
    
    if (!$deleted) {
        return ['success' => false, 'message' => 'Failed to delete comment'];
    }
    
    // Log activity
    log_activity($user_id, 'comment_delete', 'comment', $comment_id, 'Deleted comment');
    
    return ['success' => true, 'message' => 'Comment deleted successfully'];
}

/**
 * Moderate comment
 */
function moderate_comment($comment_id, $moderator_id, $action) {
    if (!is_admin()) {
        return ['success' => false, 'message' => 'Permission denied'];
    }
    
    // Check if comment exists
    $comment = get_row(
        "SELECT id, status FROM comments WHERE id = ?",
        "i",
        [$comment_id]
    );
    
    if (!$comment) {
        return ['success' => false, 'message' => 'Comment not found'];
    }
    
    $status = null;
    $description = '';
    
    switch ($action) {
        case 'approve':
            $status = 'approved';
            $description = 'Comment approved';
            break;
        case 'reject':
            $status = 'spam';
            $description = 'Comment marked as spam';
            break;
        default:
            return ['success' => false, 'message' => 'Invalid action'];
    }
    
    // Update comment
    $updated = update_data(
        'comments',
        [
            'status' => $status,
            'moderated_by' => $moderator_id,
            'moderated_at' => date('Y-m-d H:i:s')
        ],
        'id = ?',
        'i',
        [$comment_id]
    );
    
    if (!$updated) {
        return ['success' => false, 'message' => 'Failed to moderate comment'];
    }
    
    // Log activity
    log_activity($moderator_id, 'comment_moderation', 'comment', $comment_id, $description);
    
    return ['success' => true, 'message' => 'Comment moderated successfully'];
}

/**
 * Get comment with details
 */
function get_comment($comment_id) {
    return get_row(
        "SELECT c.*, u.username 
         FROM comments c 
         JOIN users u ON c.user_id = u.id 
         WHERE c.id = ?",
        "i",
        [$comment_id]
    );
}

/**
 * Get comments for post
 */
function get_post_comments($post_id, $page = 1, $per_page = 20) {
    $offset = ($page - 1) * $per_page;
    
    // Get total count
    $total = get_row(
        "SELECT COUNT(*) as total FROM comments WHERE post_id = ? AND status = 'approved'",
        "i",
        [$post_id]
    )['total'];
    
    // Get comments
    $comments = get_rows(
        "SELECT c.*, u.username 
         FROM comments c 
         JOIN users u ON c.user_id = u.id 
         WHERE c.post_id = ? AND c.status = 'approved' 
         ORDER BY c.created_at ASC 
         LIMIT ? OFFSET ?",
        "iii",
        [$post_id, $per_page, $offset]
    );
    
    return [
        'comments' => $comments,
        'total' => $total,
        'pages' => ceil($total / $per_page),
        'current_page' => $page
    ];
}

/**
 * Get pending comments for moderation
 */
function get_pending_comments($page = 1, $per_page = 20) {
    if (!is_admin()) {
        return ['success' => false, 'message' => 'Permission denied'];
    }
    
    $offset = ($page - 1) * $per_page;
    
    // Get total count
    $total = get_row(
        "SELECT COUNT(*) as total FROM comments WHERE status = 'pending'",
        "i",
        []
    )['total'];
    
    // Get comments
    $comments = get_rows(
        "SELECT c.*, u.username, p.title as post_title 
         FROM comments c 
         JOIN users u ON c.user_id = u.id 
         JOIN posts p ON c.post_id = p.id 
         WHERE c.status = 'pending' 
         ORDER BY c.created_at ASC 
         LIMIT ? OFFSET ?",
        "ii",
        [$per_page, $offset]
    );
    
    return [
        'comments' => $comments,
        'total' => $total,
        'pages' => ceil($total / $per_page),
        'current_page' => $page
    ];
} 