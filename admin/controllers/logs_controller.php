<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

class LogsController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get all activity logs with pagination
     */
    public function getAllLogs($page = 1, $limit = 20, $search = '', $action_type = null, $entity_type = null) {
        $offset = ($page - 1) * $limit;
        $params = [];
        $conditions = [];
        
        if (!empty($search)) {
            $conditions[] = "(al.description LIKE ? OR u.username LIKE ?)"; 
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        if (!empty($action_type)) {
            $conditions[] = "al.action_type = ?";
            $params[] = $action_type;
        }
        
        if (!empty($entity_type)) {
            $conditions[] = "al.entity_type = ?";
            $params[] = $entity_type;
        }
        
        $where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        // Get total count for pagination
        $count_query = "SELECT COUNT(*) as total FROM activity_logs al 
                       LEFT JOIN users u ON al.user_id = u.id 
                       $where_clause";
        $stmt = $this->pdo->prepare($count_query);
        
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $total_pages = ceil($total_records / $limit);
        
        // Get logs with pagination
        $query = "SELECT al.*, u.username 
                 FROM activity_logs al 
                 LEFT JOIN users u ON al.user_id = u.id 
                 $where_clause 
                 ORDER BY al.created_at DESC LIMIT $offset, $limit";
        $stmt = $this->pdo->prepare($query);
        
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'logs' => $logs,
            'pagination' => [
                'total_records' => $total_records,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'limit' => $limit
            ]
        ];
    }
    
    /**
     * Get distinct action types
     */
    public function getActionTypes() {
        $stmt = $this->pdo->prepare("SELECT DISTINCT action_type FROM activity_logs ORDER BY action_type ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Get distinct entity types
     */
    public function getEntityTypes() {
        $stmt = $this->pdo->prepare("SELECT DISTINCT entity_type FROM activity_logs ORDER BY entity_type ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Clear logs older than specified days
     */
    public function clearOldLogs($days = 30) {
        try {
            $stmt = $this->pdo->prepare(
                "DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
            );
            $stmt->execute([$days]);
            
            $affected_rows = $stmt->rowCount();
            
            return ['success' => true, 'message' => "$affected_rows logs cleared successfully"];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}