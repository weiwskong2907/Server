<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'controllers/comments_controller.php';

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

// Initialize controller
$commentsController = new CommentsController($pdo);

// Set page title
$page_title = 'Manage Comments';

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $result = $commentsController->deleteComment($_GET['id']);
    
    if ($result['success']) {
        header('Location: comments.php?success=comment_deleted');
        exit;
    } else {
        $error = $result['message'];
    }
}

// Handle status update action
if (isset($_GET['action']) && $_GET['action'] == 'status' && isset($_GET['id']) && isset($_GET['status'])) {
    $result = $commentsController->updateCommentStatus($_GET['id'], $_GET['status'], $_SESSION['user_id']);
    
    if ($result['success']) {
        header('Location: comments.php?success=status_updated');
        exit;
    } else {
        $error = $result['message'];
    }
}

// Handle search and filtering
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;

// Get comments with pagination
$result = $commentsController->getAllComments($page, $limit, $search, $status);
$comments = $result['comments'];
$pagination = $result['pagination'];

// Include header
include 'header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manage Comments</h1>
    </div>
    
    <?php if (isset($_GET['success']) && $_GET['success'] == 'comment_deleted'): ?>
        <div class="alert alert-success" role="alert">
            Comment deleted successfully.
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success']) && $_GET['success'] == 'status_updated'): ?>
        <div class="alert alert-success" role="alert">
            Comment status updated successfully.
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Comments List</h6>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-8">
                    <form action="" method="get" class="form-inline">
                        <div class="input-group mr-2 mb-2">
                            <input type="text" class="form-control" placeholder="Search comments..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                        
                        <select name="status" class="form-control mr-2 mb-2">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $status == 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="spam" <?php echo $status == 'spam' ? 'selected' : ''; ?>>Spam</option>
                        </select>
                        
                        <button type="submit" class="btn btn-primary mb-2">Filter</button>
                        <?php if (!empty($search) || !empty($status)): ?>
                            <a href="comments.php" class="btn btn-secondary mb-2 ml-2">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Content</th>
                            <th>Author</th>
                            <th>Post</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($comments) > 0): ?>
                            <?php foreach ($comments as $comment): ?>
                                <tr>
                                    <td><?php echo $comment['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars(substr($comment['content'], 0, 100)) . (strlen($comment['content']) > 100 ? '...' : ''); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($comment['username']); ?></td>
                                    <td>
                                        <a href="../post.php?id=<?php echo $comment['post_id']; ?>" target="_blank">
                                            <?php echo htmlspecialchars(substr($comment['post_title'], 0, 30)) . (strlen($comment['post_title']) > 30 ? '...' : ''); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $comment['status'] == 'approved' ? 'success' : ($comment['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($comment['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($comment['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <?php if ($comment['status'] != 'approved'): ?>
                                                <a href="comments.php?action=status&id=<?php echo $comment['id']; ?>&status=approved" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to approve this comment?')">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($comment['status'] != 'pending'): ?>
                                                <a href="comments.php?action=status&id=<?php echo $comment['id']; ?>&status=pending" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to mark this comment as pending?')">
                                                    <i class="fas fa-clock"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($comment['status'] != 'spam'): ?>
                                                <a href="comments.php?action=status&id=<?php echo $comment['id']; ?>&status=spam" class="btn btn-sm btn-secondary" onclick="return confirm('Are you sure you want to mark this comment as spam?')">
                                                    <i class="fas fa-ban"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="comments.php?action=delete&id=<?php echo $comment['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this comment? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No comments found.</td>
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
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