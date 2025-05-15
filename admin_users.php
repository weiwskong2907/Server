<?php
require_once './includes/config.php';
require_once './includes/db.php';
require_once './includes/functions.php';

// Check if user is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ./login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user['is_admin']) {
    header("Location: ./index.php");
    exit();
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $user_id = $_POST['user_id'];
    
    switch ($action) {
        case 'delete':
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND id != ?");
            $stmt->execute([$user_id, $_SESSION['user_id']]);
            $message = ["success" => "User deleted successfully"];
            break;
            
        case 'toggle_admin':
            $stmt = $pdo->prepare("UPDATE users SET is_admin = NOT is_admin WHERE id = ? AND id != ?");
            $stmt->execute([$user_id, $_SESSION['user_id']]);
            $message = ["success" => "User admin status updated"];
            break;
            
        case 'reset_password':
            // Generate a random password
            $new_password = bin2hex(random_bytes(8));
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            
            $message = ["success" => "Password reset to: " . $new_password];
            break;
    }
}

// Get all users with post and comment counts
$users = $pdo->query(
    "SELECT users.*, 
            (SELECT COUNT(*) FROM posts WHERE user_id = users.id) as post_count,
            (SELECT COUNT(*) FROM comments WHERE user_id = users.id) as comment_count
     FROM users 
     ORDER BY created_at DESC"
)->fetchAll();

include './layouts/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-users me-2"></i>User Management</h2>
                <p class="text-muted">Manage user accounts and permissions</p>
            </div>
            <a href="admin_dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
        
        <?php if (isset($message)): ?>
            <?php foreach ($message as $type => $text): ?>
                <div class="alert alert-<?php echo $type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $text; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Created</th>
                            <th>Posts</th>
                            <th>Comments</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                            <tr>
                                <td><?php echo $u['id']; ?></td>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($u['created_at'])); ?></td>
                                <td><span class="badge bg-secondary"><?php echo $u['post_count']; ?></span></td>
                                <td><span class="badge bg-secondary"><?php echo $u['comment_count']; ?></span></td>
                                <td>
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_admin">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $u['is_admin'] ? 'btn-success' : 'btn-secondary'; ?>">
                                                <?php echo $u['is_admin'] ? 'Admin' : 'User'; ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Current User</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="profile.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-info" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="reset_password">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to reset this user\'s password?');">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include './layouts/footer.php'; ?>