<?php
require_once './includes/config.php';
require_once './includes/db.php';
require_once './includes/functions.php';

class PostsController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get all posts with pagination
     */
    public function getAllPosts($page = 1, $limit = 10, $search = '', $category_id = null) {
        $offset = ($page - 1) * $limit;
        $params = [];
        $conditions = [];
        
        if (!empty($search)) {
            $conditions[] = "(p.title LIKE ? OR p.content LIKE ?)"; 
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        if (!empty($category_id)) {
            $conditions[] = "p.category_id = ?";
            $params[] = $category_id;
        }
        
        $where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        // Get total count for pagination
        $count_query = "SELECT COUNT(*) as total FROM posts p $where_clause";
        $stmt = $this->pdo->prepare($count_query);
        
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $total_pages = ceil($total_records / $limit);
        
        // Get posts with pagination
        $query = "SELECT p.*, u.username, c.name as category_name, 
                 (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count 
                 FROM posts p 
                 LEFT JOIN users u ON p.user_id = u.id 
                 LEFT JOIN categories c ON p.category_id = c.id 
                 $where_clause 
                 ORDER BY p.created_at DESC LIMIT $offset, $limit";
        $stmt = $this->pdo->prepare($query);
        
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'posts' => $posts,
            'pagination' => [
                'total_records' => $total_records,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'limit' => $limit
            ]
        ];
    }
    
    /**
     * Get a single post by ID
     */
    public function getPostById($id) {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, u.username, c.name as category_name 
             FROM posts p 
             LEFT JOIN users u ON p.user_id = u.id 
             LEFT JOIN categories c ON p.category_id = c.id 
             WHERE p.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update post status
     */
    public function updatePostStatus($id, $status) {
        try {
            $stmt = $this->pdo->prepare("UPDATE posts SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            
            // Log activity
            if (function_exists('log_activity')) {
                log_activity('update', 'post', $id, "Post status updated to $status by admin");
            }
            
            return ['success' => true, 'message' => 'Post status updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete a post
     */
    public function deletePost($id) {
        // Check if post exists
        $post = $this->getPostById($id);
        
        if (!$post) {
            return ['success' => false, 'message' => 'Post not found'];
        }
        
        try {
            $this->pdo->beginTransaction();
            
            // Delete post tags
            $stmt = $this->pdo->prepare("DELETE FROM post_tags WHERE post_id = ?");
            $stmt->execute([$id]);
            
            // Delete comments
            $stmt = $this->pdo->prepare("DELETE FROM comments WHERE post_id = ?");
            $stmt->execute([$id]);
            
            // Get attachments to delete files
            $stmt = $this->pdo->prepare("SELECT filename FROM attachments WHERE post_id = ?");
            $stmt->execute([$id]);
            $attachments = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Delete attachment records
            $stmt = $this->pdo->prepare("DELETE FROM attachments WHERE post_id = ?");
            $stmt->execute([$id]);
            
            // Delete the post
            $stmt = $this->pdo->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->pdo->commit();
            
            // Delete attachment files
            foreach ($attachments as $filename) {
                $file_path = __DIR__ . '/../../uploads/' . $filename;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            
            // Log activity
            if (function_exists('log_activity')) {
                log_activity('delete', 'post', $id, 'Post deleted by admin');
            }
            
            return ['success' => true, 'message' => 'Post deleted successfully'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Error deleting post: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get post statistics
     */
    public function getPostStats() {
        // Total posts
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM posts");
        $stmt->execute();
        $total_posts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Posts by status
        $stmt = $this->pdo->prepare("SELECT status, COUNT(*) as count FROM posts GROUP BY status");
        $stmt->execute();
        $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Posts by category
        $stmt = $this->pdo->prepare(
            "SELECT c.name, COUNT(p.id) as count 
             FROM categories c 
             LEFT JOIN posts p ON c.id = p.category_id 
             GROUP BY c.id"
        );
        $stmt->execute();
        $category_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'total_posts' => $total_posts,
            'status_counts' => $status_counts,
            'category_counts' => $category_counts
        ];
    }
}