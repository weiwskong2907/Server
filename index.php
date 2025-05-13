<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


// Get all posts
$stmt = $pdo->query("SELECT posts.*, users.username 
                     FROM posts 
                     JOIN users ON posts.user_id = users.id 
                     ORDER BY created_at DESC");
$posts = $stmt->fetchAll();

include 'layouts/header.php';
?>

<?php if (isset($_GET['success']) && $_GET['success'] == 'post_created'): ?>
    <div class="alert alert-success">Your post was created successfully!</div>
<?php endif; ?>

<div class="container mt-4">
    <h1>Welcome to <?php echo SITE_NAME; ?></h1>
    
    <div class="mb-4">
        <a href="post.php?action=new" class="btn btn-primary">Create New Post</a>
    </div>

    <!-- Add this before the regular posts list -->
    <?php
    try {
        $featured_posts = $pdo->query("SELECT posts.*, users.username 
                                      FROM posts 
                                      JOIN users ON posts.user_id = users.id 
                                      WHERE is_featured = TRUE 
                                      ORDER BY created_at DESC 
                                      LIMIT 3")->fetchAll();
    } catch(PDOException $e) {
        // If the column doesn't exist, just set featured_posts to an empty array
        $featured_posts = [];
    }
    
    if ($featured_posts && count($featured_posts) > 0): ?>
        <div class="featured-posts mb-5">
            <h3><i class="fas fa-star text-warning"></i> Featured Posts</h3>
            <div class="row">
                <?php foreach($featured_posts as $post): ?>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted">
                                    By <?php echo htmlspecialchars($post['username']); ?>
                                </h6>
                                <p class="card-text">
                                    <?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 150))); ?>...
                                </p>
                                <a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-outline-primary btn-sm">Read More</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    <div class="posts">
        <?php foreach($posts as $post): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted">
                        By <?php echo htmlspecialchars($post['username']); ?> on 
                        <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
                    </h6>
                    <p class="card-text">
                        <?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 200))); ?>...
                    </p>
                    <a href="post.php?id=<?php echo $post['id']; ?>" class="card-link">Read More</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>