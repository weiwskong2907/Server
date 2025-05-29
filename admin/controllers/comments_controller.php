<?php
require_once './includes/config.php';
require_once './includes/db.php';
require_once './includes/functions.php';

class CommentsController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get all comments with pagination
     */
    public function getAllComments($page = 1, $limit = 10, $search = '', $status = null) {
        $offset = ($page - 1) * $limit;
        $params = [];
        $conditions = [];
        
        if (!empty($search)) {
            $conditions[] = "(c.content LIKE ? OR u.username LIKE ?)"; 
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        if (!empty($status)) {
            $conditions[] = "c.status = ?";
            $params[] = $status;
        }
        
        $where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        // Get total count for pagination
        $count_query = "SELECT COUNT(*) as total FROM comments c 
                       LEFT JOIN users u ON c.user_id = u.id 
                       $where_clause";
        $stmt = $this->pdo->prepare($count_query);
        
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $total_pages = ceil($total_records / $limit);
        
        // Get comments with pagination
        $query = "SELECT c.*, u.username, p.title as post_title 
                 FROM comments c 
                 LEFT JOIN users u ON c.user_id = u.id 
                 LEFT JOIN posts p ON c.post_id = p.id 
                 $where_clause 
                 ORDER BY c.created_at DESC LIMIT $offset, $limit";
        $stmt = $this->pdo->prepare($query);
        
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'comments' => $comments,
            'pagination' => [
                'total_records' => $total_records,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'limit' => $limit
            ]
        ];
    }
    
    /**
     * Get a single comment by ID
     */
    public function getCommentById($id) {
        $stmt = $this->pdo->prepare(
            "SELECT c.*, u.username, p.title as post_title 
             FROM comments c 
             LEFT JOIN users u ON c.user_id = u.id 
             LEFT JOIN posts p ON c.post_id = p.id 
             WHERE c.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update comment status
     */
    public function updateCommentStatus($id, $status, $admin_id) {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE comments 
                 SET status = ?, moderated_by = ?, moderated_at = NOW() 
                 WHERE id = ?"
            );
            $stmt->execute([$status, $admin_id, $id]);
            
            // Log activity
            if (function_exists('log_activity')) {
                log_activity('update', 'comment', $id, "Comment status updated to $status by admin");
            }
            
            return ['success' => true, 'message' => 'Comment status updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete a comment
     */
    public function deleteComment($id) {
        // Check if comment exists
        $comment = $this->getCommentById($id);
        
        if (!$comment) {
            return ['success' => false, 'message' => 'Comment not found'];
        }
        
        try {
            $stmt = $this->pdo->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$id]);
            
            // Log activity
            if (function_exists('log_activity')) {
                log_activity('delete', 'comment', $id, 'Comment deleted by admin');
            }
            
            return ['success' => true, 'message' => 'Comment deleted successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get comment statistics
     */
    public function getCommentStats() {
        // Total comments
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM comments");
        $stmt->execute();
        $total_comments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Comments by status
        $stmt = $this->pdo->prepare("SELECT status, COUNT(*) as count FROM comments GROUP BY status");
        $stmt->execute();
        $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'total_comments' => $total_comments,
            'status_counts' => $status_counts
        ];
    }
}