<?php
require_once 'includes/config.php';
require_once 'includes/layout.php';

// Get recent posts
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$posts = get_posts_paginated($page);
$categories = get_categories();

// Get popular tags
$popular_tags = get_popular_tags(10);

// Get page header
echo get_page_header('Home');

// Display alerts
if (isset($_SESSION['alert'])) {
    echo get_alert($_SESSION['alert']['type'], $_SESSION['alert']['message']);
    unset($_SESSION['alert']);
}
?>

<div class="row">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Recent Posts</h1>
            <?php if (is_logged_in()): ?>
                <a href="/create-post.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Post
                </a>
            <?php endif; ?>
        </div>

        <?php if (empty($posts['posts'])): ?>
            <div class="alert alert-info">
                No posts found. Be the first to create a post!
            </div>
        <?php else: ?>
            <?php foreach ($posts['posts'] as $post): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="card-title h5">
                            <a href="/post.php?id=<?php echo $post['id']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </a>
                        </h2>
                        <div class="text-muted small mb-2">
                            Posted by 
                            <a href="/profile.php?id=<?php echo $post['user_id']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($post['username']); ?>
                            </a>
                            in 
                            <a href="/category.php?id=<?php echo $post['category_id']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($post['category_name']); ?>
                            </a>
                            <?php echo time_ago($post['created_at']); ?>
                        </div>
                        <p class="card-text">
                            <?php echo htmlspecialchars(substr($post['content'], 0, 200)) . '...'; ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="btn-group">
                                <a href="/post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    Read More
                                </a>
                                <?php if (is_logged_in() && ($post['user_id'] === $_SESSION['user_id'] || is_admin())): ?>
                                    <a href="/edit-post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                        Edit
                                    </a>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-comments"></i> <?php echo $post['comment_count']; ?> comments
                                <i class="fas fa-eye ms-2"></i> <?php echo $post['views']; ?> views
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php echo get_pagination($page, $posts['pages'], '/?page=%d'); ?>
        <?php endif; ?>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="h5 mb-0">Categories</h2>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php foreach ($categories as $category): ?>
                        <a href="/category.php?id=<?php echo $category['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <?php echo htmlspecialchars($category['name']); ?>
                            <span class="badge bg-primary rounded-pill"><?php echo $category['post_count']; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="h5 mb-0">Popular Tags</h2>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($popular_tags as $tag): ?>
                        <a href="/tag.php?id=<?php echo $tag['id']; ?>" class="btn btn-sm btn-outline-secondary">
                            <?php echo htmlspecialchars($tag['name']); ?>
                            <span class="badge bg-secondary"><?php echo $tag['post_count']; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Get page footer
echo get_page_footer();
?>