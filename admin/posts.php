<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'controllers/posts_controller.php';
require_once 'controllers/categories_controller.php';

// Check if user is logged in and is an admin
session_start();
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

// Initialize controllers
$postsController = new PostsController($pdo);
$categoriesController = new CategoriesController($pdo);

// Set page title
$page_title = 'Manage Posts';

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $result = $postsController->deletePost($_GET['id']);
    
    if ($result['success']) {
        header('Location: posts.php?success=post_deleted');
        exit;
    } else {
        $error = $result['message'];
    }
}

// Handle status update action
if (isset($_GET['action']) && $_GET['action'] == 'status' && isset($_GET['id']) && isset($_GET['status'])) {
    $result = $postsController->updatePostStatus($_GET['id'], $_GET['status']);
    
    if ($result['success']) {
        header('Location: posts.php?success=status_updated');
        exit;
    } else {
        $error = $result['message'];
    }
}

// Get all categories for filter
$categories = $categoriesController->getAllCategories();

// Handle search and filtering
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;

// Get posts with pagination
$result = $postsController->getAllPosts($page, $limit, $search, $category_id);
$posts = $result['posts'];
$pagination = $result['pagination'];

// Include header
include 'header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manage Posts</h1>
    </div>
    
    <?php if (isset($_GET['success']) && $_GET['success'] == 'post_deleted'): ?>
        <div class="alert alert-success" role="alert">
            Post deleted successfully.
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success']) && $_GET['success'] == 'status_updated'): ?>
        <div class="alert alert-success" role="alert">
            Post status updated successfully.
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Posts List</h6>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-8">
                    <form action="" method="get" class="form-inline">
                        <div class="input-group mr-2 mb-2">
                            <input type="text" class="form-control" placeholder="Search posts..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                        
                        <select name="category_id" class="form-control mr-2 mb-2">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <button type="submit" class="btn btn-primary mb-2">Filter</button>
                        <?php if (!empty($search) || !empty($category_id)): ?>
                            <a href="posts.php" class="btn btn-secondary mb-2 ml-2">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Comments</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($posts) > 0): ?>
                            <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td><?php echo $post['id']; ?></td>
                                    <td>
                                        <a href="../post.php?id=<?php echo $post['id']; ?>" target="_blank">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($post['username']); ?></td>
                                    <td><?php echo htmlspecialchars($post['category_name']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $post['status'] == 'published' ? 'success' : ($post['status'] == 'draft' ? 'warning' : 'secondary'); ?>">
                                            <?php echo ucfirst($post['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $post['comment_count']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="post_edit.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($post['status'] == 'published'): ?>
                                                <a href="posts.php?action=status&id=<?php echo $post['id']; ?>&status=draft" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to unpublish this post?')">
                                                    <i class="fas fa-eye-slash"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="posts.php?action=status&id=<?php echo $post['id']; ?>&status=published" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to publish this post?')">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="posts.php?action=delete&id=<?php echo $post['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this post? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No posts found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo urlencode($category_id); ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo urlencode($category_id); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo urlencode($category_id); ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>