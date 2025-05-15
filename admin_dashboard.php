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

// Get statistics
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'posts' => $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
    'comments' => $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn(),
];

// Get recent activity
$recent_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recent_posts = $pdo->query("SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recent_comments = $pdo->query("SELECT comments.*, users.username, posts.title as post_title FROM comments JOIN users ON comments.user_id = users.id JOIN posts ON comments.post_id = posts.id ORDER BY comments.created_at DESC LIMIT 5")->fetchAll();

include './layouts/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h2>
            <p class="text-muted">Welcome to the administration panel. Manage your site from here.</p>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Users</h5>
                            <p class="card-text display-4"><?php echo $stats['users']; ?></p>
                        </div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                    <a href="admin_users.php" class="btn btn-outline-light mt-2">Manage Users</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Posts</h5>
                            <p class="card-text display-4"><?php echo $stats['posts']; ?></p>
                        </div>
                        <i class="fas fa-file-alt fa-3x opacity-50"></i>
                    </div>
                    <a href="admin_posts.php" class="btn btn-outline-light mt-2">Manage Posts</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Comments</h5>
                            <p class="card-text display-4"><?php echo $stats['comments']; ?></p>
                        </div>
                        <i class="fas fa-comments fa-3x opacity-50"></i>
                    </div>
                    <a href="admin_comments.php" class="btn btn-outline-light mt-2">Manage Comments</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <h3><i class="fas fa-history me-2"></i>Recent Activity</h3>
        </div>
        
        <!-- Recent Users -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Recent Users</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach($recent_users as $u): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo htmlspecialchars($u['username']); ?></h6>
                                <small><?php echo date('M d', strtotime($u['created_at'])); ?></small>
                            </div>
                            <small class="text-muted"><?php echo htmlspecialchars($u['email']); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-footer">
                    <a href="admin_users.php" class="btn btn-sm btn-outline-secondary">View All Users</a>
                </div>
            </div>
        </div>
        
        <!-- Recent Posts -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Recent Posts</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach($recent_posts as $p): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo htmlspecialchars($p['title']); ?></h6>
                                <small><?php echo date('M d', strtotime($p['created_at'])); ?></small>
                            </div>
                            <small class="text-muted">By <?php echo htmlspecialchars($p['username']); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-footer">
                    <a href="admin_posts.php" class="btn btn-sm btn-outline-secondary">View All Posts</a>
                </div>
            </div>
        </div>
        
        <!-- Recent Comments -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Recent Comments</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach($recent_comments as $c): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo htmlspecialchars(substr($c['content'], 0, 30)); ?>...</h6>
                                <small><?php echo date('M d', strtotime($c['created_at'])); ?></small>
                            </div>
                            <small class="text-muted">By <?php echo htmlspecialchars($c['username']); ?> on "<?php echo htmlspecialchars($c['post_title']); ?>"</small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-footer">
                    <a href="admin_comments.php" class="btn btn-sm btn-outline-secondary">View All Comments</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include './layouts/footer.php'; ?>