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

// Check if admin_level column exists, if not create it
try {
    $pdo->query("SELECT admin_level FROM users LIMIT 1");
} catch (PDOException $e) {
    // Column doesn't exist, create it
    $pdo->query("ALTER TABLE users ADD COLUMN admin_level TINYINT(1) DEFAULT 0");
    // Update existing admins to have level 2
    $pdo->query("UPDATE users SET admin_level = 2 WHERE is_admin = 1");
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $user_id = $_POST['user_id'];
    
    switch ($action) {
        case 'make_subadmin':
            // Make user a subadmin (level 1)
            $stmt = $pdo->prepare("UPDATE users SET admin_level = 1 WHERE id = ? AND id != ?");
            $stmt->execute([$user_id, $_SESSION['user_id']]);
            $message = ["success" => "User promoted to subadmin successfully"];
            break;
            
        case 'remove_subadmin':
            // Remove subadmin privileges
            $stmt = $pdo->prepare("UPDATE users SET admin_level = 0 WHERE id = ? AND id != ? AND admin_level = 1");
            $stmt->execute([$user_id, $_SESSION['user_id']]);
            $message = ["success" => "Subadmin privileges removed successfully"];
            break;
    }
}

// Get all users with their admin levels
$users = $pdo->query(
    "SELECT users.*, 
            (SELECT COUNT(*) FROM posts WHERE user_id = users.id) as post_count,
            (SELECT COUNT(*) FROM comments WHERE user_id = users.id) as comment_count
     FROM users 
     ORDER BY username ASC"
)->fetchAll();

include './admin/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-user-shield me-2"></i>Subadmin Management</h2>
                <p class="text-muted">Manage subadmin privileges for users</p>
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
        <div class="card-header bg-light">
            <h5 class="mb-0">Subadmin Privileges</h5>
            <p class="text-muted small mb-0">Subadmins can moderate comments and manage posts but cannot manage other users</p>
        </div>
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
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                            <?php 
                            // Determine user role
                            $role = "Regular User";
                            $role_badge_class = "secondary";
                            
                            if (isset($u['admin_level'])) {
                                if ($u['admin_level'] == 2) {
                                    $role = "Admin";
                                    $role_badge_class = "danger";
                                } elseif ($u['admin_level'] == 1) {
                                    $role = "Subadmin";
                                    $role_badge_class = "warning";
                                }
                            } elseif ($u['is_admin']) {
                                $role = "Admin";
                                $role_badge_class = "danger";
                            }
                            ?>
                            <tr>
                                <td><?php echo $u['id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($u['profile_picture'])): ?>
                                            <img src="uploads/profile_pictures/<?php echo $u['profile_picture']; ?>" 
                                                 class="rounded-circle me-2" width="32" height="32" alt="Profile">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-secondary text-white me-2 d-flex align-items-center justify-content-center" 
                                                 style="width: 32px; height: 32px;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($u['username']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                <td><?php echo $u['post_count']; ?></td>
                                <td><?php echo $u['comment_count']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $role_badge_class; ?>"><?php echo $role; ?></span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="profile.php?id=<?php echo $u['id']; ?>" class="btn btn-outline-secondary" title="View Profile">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                            <?php if ((isset($u['admin_level']) && $u['admin_level'] == 0) || (!isset($u['admin_level']) && !$u['is_admin'])): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="make_subadmin">
                                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-warning" title="Make Subadmin" onclick="return confirm('Make this user a subadmin?')">
                                                        <i class="fas fa-user-shield"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ((isset($u['admin_level']) && $u['admin_level'] == 1)): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="remove_subadmin">
                                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger" title="Remove Subadmin" onclick="return confirm('Remove subadmin privileges?')">
                                                        <i class="fas fa-user-slash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
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

<?php include './admin/footer.php'; ?>