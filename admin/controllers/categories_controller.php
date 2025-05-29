<?php
require_once './includes/config.php';
require_once './includes/db.php';
require_once './includes/functions.php';

class CategoriesController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get all categories
     */
    public function getAllCategories() {
        $stmt = $this->pdo->prepare(
            "SELECT c.*, COUNT(p.id) as post_count 
             FROM categories c 
             LEFT JOIN posts p ON c.id = p.category_id 
             GROUP BY c.id 
             ORDER BY c.name ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get a single category by ID
     */
    public function getCategoryById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create a new category
     */
    public function createCategory($name, $description = '') {
        // Validate required fields
        if (empty($name)) {
            return ['success' => false, 'message' => 'Category name is required'];
        }
        
        // Check if category name already exists
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM categories WHERE name = ?");
        $stmt->execute([$name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            return ['success' => false, 'message' => 'Category name already exists'];
        }
        
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO categories (name, description, created_at) 
                 VALUES (?, ?, NOW())"
            );
            $stmt->execute([$name, $description]);
            
            $category_id = $this->pdo->lastInsertId();
            
            // Log activity
            if (function_exists('log_activity')) {
                log_activity('create', 'category', $category_id, 'Category created by admin');
            }
            
            return ['success' => true, 'message' => 'Category created successfully', 'category_id' => $category_id];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update an existing category
     */
    public function updateCategory($id, $name, $description = '') {
        // Validate required fields
        if (empty($name)) {
            return ['success' => false, 'message' => 'Category name is required'];
        }
        
        // Get current category data
        $current_category = $this->getCategoryById($id);
        
        if (!$current_category) {
            return ['success' => false, 'message' => 'Category not found'];
        }
        
        // Check if category name already exists for another category
        if ($name !== $current_category['name']) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM categories WHERE name = ? AND id != ?");
            $stmt->execute([$name, $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                return ['success' => false, 'message' => 'Category name already exists'];
            }
        }
        
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE categories 
                 SET name = ?, description = ? 
                 WHERE id = ?"
            );
            $stmt->execute([$name, $description, $id]);
            
            // Log activity
            if (function_exists('log_activity')) {
                log_activity('update', 'category', $id, 'Category updated by admin');
            }
            
            return ['success' => true, 'message' => 'Category updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete a category
     */
    public function deleteCategory($id) {
        // Check if category exists
        $category = $this->getCategoryById($id);
        
        if (!$category) {
            return ['success' => false, 'message' => 'Category not found'];
        }
        
        // Check if category has posts
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM posts WHERE category_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            return ['success' => false, 'message' => 'Cannot delete category with associated posts'];
        }
        
        try {
            $stmt = $this->pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            
            // Log activity
            if (function_exists('log_activity')) {
                log_activity('delete', 'category', $id, 'Category deleted by admin');
            }
            
            return ['success' => true, 'message' => 'Category deleted successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}