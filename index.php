<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Get current page
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Get posts
$posts_data = get_posts_paginated($page);

// Get categories
$categories = get_categories();

// Get popular tags
$popular_tags = get_popular_tags(10);

// Generate page header
get_page_header('Home');

// Display alerts if any
if (isset($_SESSION['alert'])) {
    echo get_alert($_SESSION['alert']['type'], $_SESSION['alert']['message']);
    unset($_SESSION['alert']);
}
?>

<div class="container py-5">
    <div class="row">
        <!-- Main content -->
        <div class="col-lg-8">
            <?php if (is_logged_in()): ?>
            <div class="mb-4">
                <a href="new-post.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Post
                </a>
            </div>
            <?php endif; ?>
            
            <?php if (empty($posts_data['posts'])): ?>
            <div class="alert alert-info">
                No posts found. Be the first to create one!
            </div>
            <?php else: ?>
            <?php foreach ($posts_data['posts'] as $post): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="card-title h4">
                        <a href="post.php?id=<?php echo $post['id']; ?>" class="text-decoration-none">
                            <?php echo sanitize_output($post['title']); ?>
                        </a>
                    </h2>
                    
                    <div class="text-muted mb-2">
                        <small>
                            By <?php echo sanitize_output($post['username']); ?>
                            in <?php echo sanitize_output($post['category_name']); ?>
                            on <?php echo format_date($post['created_at']); ?>
                        </small>
                    </div>
                    
                    <p class="card-text">
                        <?php echo truncate_text(sanitize_output($post['content'])); ?>
                    </p>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary">
                                Read more
                            </a>
                            
                            <?php if (is_logged_in() && (get_current_user_id() === $post['user_id'] || is_admin())): ?>
                            <a href="edit-post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                Edit
                            </a>
                            <?php endif; ?>
                        </div>
                        
                        <small class="text-muted">
                            <?php echo $post['comment_count']; ?> comments
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Pagination -->
            <?php if ($posts_data['pages'] > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $posts_data['pages']; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $posts_data['pages']): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Categories -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="h5 mb-0">Categories</h3>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($categories as $category): ?>
                        <li class="mb-2">
                            <a href="category.php?id=<?php echo $category['id']; ?>" class="text-decoration-none">
                                <?php echo sanitize_output($category['name']); ?>
                                <span class="badge bg-secondary float-end">
                                    <?php echo $category['post_count']; ?>
                                </span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Popular Tags -->
            <div class="card">
                <div class="card-header">
                    <h3 class="h5 mb-0">Popular Tags</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($popular_tags as $tag): ?>
                        <a href="tag.php?name=<?php echo urlencode($tag['name']); ?>" 
                           class="btn btn-sm btn-outline-secondary">
                            <?php echo sanitize_output($tag['name']); ?>
                            <span class="badge bg-secondary">
                                <?php echo $tag['count']; ?>
                            </span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Generate page footer
get_page_footer();
?>