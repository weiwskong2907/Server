<?php
require_once './includes/config.php';
require_once './includes/db.php';
require_once './includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get current user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!empty($current_password)) {
        // User wants to change password
        if (!password_verify($current_password, $user['password'])) {
            $error = "Current password is incorrect";
        } elseif (empty($new_password)) {
            $error = "New password is required";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $_SESSION['user_id']]);
            $success = "Password updated successfully";
        }
    }
    
    // Update email
    if (!empty($email) && $email !== $user['email']) {
        $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        $success = "Profile updated successfully";
    }
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Count user posts
$stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$post_count = $stmt->fetchColumn();

// Count user comments
$stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$comment_count = $stmt->fetchColumn();

include 'layouts/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <!-- User Profile Card -->
            <div class="card profile-card mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-user-circle me-2"></i>Profile</h3>
                </div>
                <div class="card-body text-center">
                    <div class="profile-avatar mb-3">
                        <i class="fas fa-user-circle fa-6x text-primary"></i>
                    </div>
                    <h4 class="mb-0"><?php echo htmlspecialchars($user['username']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    <div class="row mt-3">
                        <div class="col-6">
                            <div class="stat-box p-2 border rounded">
                                <h5 class="mb-0"><?php echo $post_count; ?></h5>
                                <small class="text-muted">Posts</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box p-2 border rounded">
                                <h5 class="mb-0"><?php echo $comment_count; ?></h5>
                                <small class="text-muted">Comments</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <!-- Profile Settings Card -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-cog me-2"></i>Profile Settings</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group mb-3">
                            <label class="form-label"><i class="fas fa-user me-2"></i>Username</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label"><i class="fas fa-envelope me-2"></i>Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <hr>
                        <h4><i class="fas fa-lock me-2"></i>Change Password</h4>
                        <div class="form-group mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Profile
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- User's Posts Section -->
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-file-alt me-2"></i>My Posts</h3>
                    <a href="post.php?action=new" class="btn btn-light btn-sm">
                        <i class="fas fa-plus-circle me-1"></i>New Post
                    </a>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
                    $stmt->execute([$_SESSION['user_id']]);
                    $posts = $stmt->fetchAll();
                    
                    if ($posts): ?>
                        <div class="list-group">
                            <?php foreach($posts as $post): ?>
                                <a href="post.php?id=<?php echo $post['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-1"><?php echo htmlspecialchars($post['title']); ?></h5>
                                            <p class="mb-1 text-muted small">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                                            </p>
                                        </div>
                                        <span class="badge bg-primary rounded-pill">
                                            <i class="fas fa-eye me-1"></i>
                                            <?php 
                                            // Get view count if available
                                            echo isset($post['views']) ? $post['views'] : '0'; 
                                            ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                            <p>You haven't created any posts yet.</p>
                            <a href="post.php?action=new" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-1"></i>Create Your First Post
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add custom CSS for profile page -->
<style>
.profile-card .profile-avatar {
    margin-top: 10px;
    margin-bottom: 20px;
}

.stat-box {
    transition: all 0.3s ease;
}

.stat-box:hover {
    background-color: var(--light-color);
    transform: translateY(-3px);
}

.card-header {
    font-weight: 500;
}

.list-group-item {
    transition: all 0.2s ease;
}

.list-group-item:hover {
    transform: translateX(5px);
    background-color: rgba(52, 152, 219, 0.05);
}
</style>

<?php include 'layouts/footer.php'; ?>