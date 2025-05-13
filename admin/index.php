<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user['is_admin']) {
    header("Location: ../index.php");
    exit();
}

// Get statistics
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'posts' => $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
    'comments' => $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn(),
];

include '../layouts/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Admin Dashboard</h2>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Users</h5>
                    <p class="card-text display-4"><?php echo $stats['users']; ?></p>
                    <a href="users.php" class="btn btn-primary">Manage Users</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Posts</h5>
                    <p class="card-text display-4"><?php echo $stats['posts']; ?></p>
                    <a href="posts.php" class="btn btn-primary">Manage Posts</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Comments</h5>
                    <p class="card-text display-4"><?php echo $stats['comments']; ?></p>
                    <a href="comments.php" class="btn btn-primary">Manage Comments</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>