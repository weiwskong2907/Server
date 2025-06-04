<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';


class UsersController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get all users with pagination
     */
    public function getAllUsers($page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        $params = [];
        $search_condition = '';
        
        if (!empty($search)) {
            // Fix: Replace 'role' with CASE statement for is_admin
            $search_condition = "WHERE username LIKE ? OR email LIKE ? OR 
                               (CASE WHEN is_admin = 1 THEN 'admin' ELSE 'user' END) LIKE ?";
            $search_param = "%$search%";
            $params = [$search_param, $search_param, $search_param];
        }
        
        // Get total count for pagination
        $count_query = "SELECT COUNT(*) as total FROM users $search_condition";
        $stmt = $this->pdo->prepare($count_query);
        
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $total_pages = ceil($total_records / $limit);
        
        // Get users with pagination and add a calculated 'role' field
        $query = "SELECT *, (CASE WHEN is_admin = 1 THEN 'admin' ELSE 'user' END) as role 
                 FROM users $search_condition ORDER BY created_at DESC LIMIT $offset, $limit";
        $stmt = $this->pdo->prepare($query);
        
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'users' => $users,
            'pagination' => [
                'total_records' => $total_records,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'limit' => $limit
            ]
        ];
    }
    
    /**
     * Get a single user by ID
     */
    public function getUserById($id) {
        $stmt = $this->pdo->prepare("SELECT *, 
                                  (CASE WHEN is_admin = 1 THEN 'admin' ELSE 'user' END) as role,
                                  'active' as status 
                                  FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create a new user
     */
    public function createUser($userData) {
        // Validate required fields
        if (empty($userData['username']) || empty($userData['email']) || empty($userData['password'])) {
            return ['success' => false, 'message' => 'Username, email and password are required'];
        }
        
        // Check if username or email already exists
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$userData['username'], $userData['email']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        // Hash password
        $hashed_password = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Convert role to is_admin
        $is_admin = (isset($userData['role']) && $userData['role'] === 'admin') ? 1 : 0;
        $admin_level = (isset($userData['role']) && $userData['role'] === 'admin') ? 1 : 0;
        
        // Set birthday if provided
        $birthday = !empty($userData['birthday']) ? $userData['birthday'] : null;
        
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO users (username, email, password, is_admin, admin_level, birthday, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, NOW())"
            );
            
            $stmt->execute([
                $userData['username'],
                $userData['email'],
                $hashed_password,
                $is_admin,
                $admin_level,
                $birthday
            ]);
            
            $user_id = $this->pdo->lastInsertId();
            
            // Log activity
            if (function_exists('log_activity')) {
                log_activity('create', 'user', $user_id, 'User created by admin');
            }
            
            return ['success' => true, 'message' => 'User created successfully', 'user_id' => $user_id];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update an existing user
     */
    public function updateUser($id, $userData) {
        // Get current user data
        $current_user = $this->getUserById($id);
        
        if (!$current_user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Check if username or email already exists for another user
        if ($userData['username'] !== $current_user['username'] || $userData['email'] !== $current_user['email']) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$userData['username'], $userData['email'], $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
        }
        
        try {
            $fields = [];
            $params = [];
            
            // Basic fields
            if (!empty($userData['username'])) {
                $fields[] = "username = ?";
                $params[] = $userData['username'];
            }
            
            if (!empty($userData['email'])) {
                $fields[] = "email = ?";
                $params[] = $userData['email'];
            }
            
            // Handle role (is_admin)
            if (isset($userData['role'])) {
                $fields[] = "is_admin = ?";
                $params[] = ($userData['role'] === 'admin') ? 1 : 0;
                
                // Only set admin_level if role is admin
                if ($userData['role'] === 'admin' && isset($userData['admin_level'])) {
                    $fields[] = "admin_level = ?";
                    $params[] = intval($userData['admin_level']);
                } else if ($userData['role'] !== 'admin') {
                    // Reset admin_level to 0 if not admin
                    $fields[] = "admin_level = ?";
                    $params[] = 0;
                }
            }
            
            // Profile fields
            $profile_fields = ['birthday', 'name', 'bio', 'location', 'website'];
            foreach ($profile_fields as $field) {
                if (isset($userData[$field])) {
                    $fields[] = "$field = ?";
                    $params[] = $userData[$field];
                }
            }
            
            // Handle password separately
            if (!empty($userData['password'])) {
                $fields[] = "password = ?";
                $params[] = password_hash($userData['password'], PASSWORD_DEFAULT);
            }
            
            // Handle profile picture upload
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/profile_pictures/';
                $file_name = 'profile_' . uniqid() . '.' . pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $upload_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                    $fields[] = "profile_picture = ?";
                    $params[] = 'uploads/profile_pictures/' . $file_name;
                    
                    // Delete old profile picture if exists
                    if (!empty($current_user['profile_picture'])) {
                        $old_file = '../' . $current_user['profile_picture'];
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }
                }
            }
            
            // Add user ID to params
            $params[] = $id;
            
            if (!empty($fields)) {
                $query = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
                $stmt = $this->pdo->prepare($query);
                $stmt->execute($params);
                
                // Log activity
                if (function_exists('log_activity')) {
                    log_activity('update', 'user', $id, 'User updated by admin');
                }
                
                return ['success' => true, 'message' => 'User updated successfully'];
            } else {
                return ['success' => false, 'message' => 'No fields to update'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete a user
     */
    public function deleteUser($id) {
        // Check if user exists
        $user = $this->getUserById($id);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        try {
            $this->pdo->beginTransaction();
            
            // Delete user's posts and related data
            // First get all post IDs
            $stmt = $this->pdo->prepare("SELECT id FROM posts WHERE user_id = ?");
            $stmt->execute([$id]);
            $posts = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($posts as $post_id) {
                // Delete post tags
                $stmt = $this->pdo->prepare("DELETE FROM post_tags WHERE post_id = ?");
                $stmt->execute([$post_id]);
                
                // Delete comments
                $stmt = $this->pdo->prepare("DELETE FROM comments WHERE post_id = ?");
                $stmt->execute([$post_id]);
                
                // Get attachments to delete files
                $stmt = $this->pdo->prepare("SELECT filename FROM attachments WHERE post_id = ?");
                $stmt->execute([$post_id]);
                $attachments = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Delete attachment records
                $stmt = $this->pdo->prepare("DELETE FROM attachments WHERE post_id = ?");
                $stmt->execute([$post_id]);
            }
            
            // Delete all user's posts
            $stmt = $this->pdo->prepare("DELETE FROM posts WHERE user_id = ?");
            $stmt->execute([$id]);
            
            // Delete all user's comments
            $stmt = $this->pdo->prepare("DELETE FROM comments WHERE user_id = ?");
            $stmt->execute([$id]);
            
            // Delete the user
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
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
                log_activity('delete', 'user', $id, 'User deleted by admin');
            }
            
            return ['success' => true, 'message' => 'User deleted successfully'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get user statistics
     */
    public function getUserStats() {
        // Total users
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM users");
        $stmt->execute();
        $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // New users in last 7 days
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stmt->execute();
        $new_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Users by role
        $stmt = $this->pdo->prepare("SELECT role, COUNT(*) as count FROM users GROUP BY role");
        $stmt->execute();
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'total_users' => $total_users,
            'new_users' => $new_users,
            'roles' => $roles
        ];
    }
    /**
     * Check if username or email already exists
     * @param string $username Username to check
     * @param string $email Email to check
     * @param int|null $exclude_user_id User ID to exclude from the check (for updates)
     * @return array|false User data if found, false otherwise
     */
    public function getUserByUsernameOrEmail($username, $email, $exclude_user_id = null) {
        $params = [$username, $email];
        $exclude_condition = '';
        
        if ($exclude_user_id) {
            $exclude_condition = 'AND id != ?';
            $params[] = $exclude_user_id;
        }
        
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? $exclude_condition LIMIT 1");
        $stmt->execute($params);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result : false;
    }
}
