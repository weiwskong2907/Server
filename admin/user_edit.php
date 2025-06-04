<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'controllers/users_controller.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Initialize database connection
$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
    DB_USER,
    DB_PASS,
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

// Initialize controller
$usersController = new UsersController($pdo);

// Set page title
$page_title = 'Edit User';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: users.php');
    exit;
}

$user_id = $_GET['id'];
$user = $usersController->getUserById($user_id);

if (!$user) {
    header('Location: users.php?error=user_not_found');
    exit;
}

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $birthday = !empty($_POST['birthday']) ? trim($_POST['birthday']) : null;
    $password = !empty($_POST['password']) ? trim($_POST['password']) : '';
    $confirm_password = !empty($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
    
    // Basic validation
    if (empty($username)) {
        $errors[] = 'Username is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    // Check if username or email already exists (excluding current user)
    $existing_user = $usersController->getUserByUsernameOrEmail($username, $email, $user_id);
    if ($existing_user) {
        if ($existing_user['username'] === $username) {
            $errors[] = 'Username already exists';
        }
        if ($existing_user['email'] === $email) {
            $errors[] = 'Email already exists';
        }
    }
    
    // Password validation (only if password is being changed)
    if (!empty($password)) {
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }
    }
    
    // If no errors, update user
    if (empty($errors)) {
        $update_data = [
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'birthday' => $birthday
        ];
        
        // Only include password if it's being changed
        if (!empty($password)) {
            $update_data['password'] = $password;
        }
        
        $result = $usersController->updateUser($user_id, $update_data);
        
        if ($result['success']) {
            $success = $result['message'];
            // Refresh user data
            $user = $usersController->getUserById($user_id);
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Include header
include 'header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Edit User</h1>
        <a href="users.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>
    </div>
    
    <?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form action="user_edit.php?id=<?php echo $user_id; ?>" method="POST">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="user" <?php echo (isset($user['is_admin']) && $user['is_admin'] == 0) ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo (isset($user['is_admin']) && $user['is_admin'] == 1) ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <div class="col-md-6" id="admin-level-container" style="<?php echo (isset($user['is_admin']) && $user['is_admin'] == 1) ? '' : 'display: none;'; ?>">
                        <label for="admin_level" class="form-label">Admin Level</label>
                        <select class="form-select" id="admin_level" name="admin_level">
                            <option value="1" <?php echo (isset($user['admin_level']) && $user['admin_level'] == 1) ? 'selected' : ''; ?>>Level 1</option>
                            <option value="2" <?php echo (isset($user['admin_level']) && $user['admin_level'] == 2) ? 'selected' : ''; ?>>Level 2</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="birthday" class="form-label">Birthday</label>
                        <input type="date" class="form-control" id="birthday" name="birthday" 
                               value="<?php echo isset($user['birthday']) ? htmlspecialchars($user['birthday']) : ''; ?>">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?php echo (isset($user['status']) ? $user['status'] === 'active' : true) ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo (isset($user['status']) && $user['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="suspended" <?php echo (isset($user['status']) && $user['status'] === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <small class="text-muted">Minimum 8 characters</small>
                    </div>
                    <div class="col-md-6">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo isset($user['name']) ? htmlspecialchars($user['name']) : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="location" name="location" 
                               value="<?php echo isset($user['location']) ? htmlspecialchars($user['location']) : ''; ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="website" class="form-label">Website</label>
                        <input type="url" class="form-control" id="website" name="website" 
                               value="<?php echo isset($user['website']) ? htmlspecialchars($user['website']) : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="profile_picture" class="form-label">Profile Picture</label>
                        <input type="file" class="form-control" id="profile_picture" name="profile_picture">
                        <?php if (!empty($user['profile_picture'])): ?>
                            <div class="mt-2">
                                <img src="../<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                     alt="Profile Picture" class="img-thumbnail" style="max-width: 100px;">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12">
                        <label for="bio" class="form-label">Bio</label>
                        <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo isset($user['bio']) ? htmlspecialchars($user['bio']) : ''; ?></textarea>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Created At</label>
                        <p class="form-control-static"><?php echo date('F j, Y, g:i a', strtotime($user['created_at'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Login</label>
                        <p class="form-control-static">
                            <?php echo isset($user['last_login']) ? date('F j, Y, g:i a', strtotime($user['last_login'])) : 'Never'; ?>
                        </p>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="users.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>