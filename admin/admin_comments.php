<?php
require_once './includes/config.php';
require_once './includes/db.php';
require_once './includes/functions.php';
require_once './includes/email.php';

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

// Handle comment moderation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $comment_id = $_POST['comment_id'];
    
    switch ($action) {
        case 'approve':
        case 'spam':
        case 'pending':
            $stmt = $pdo->prepare(
                "UPDATE comments 
                 SET status = ?, moderated_by = ?, moderated_at = NOW() 
                 WHERE id = ?"
            );
            $stmt->execute([$action, $_SESSION['user_id'], $comment_id]);
            
            // Log activity if function exists
            if (function_exists('log_activity')) {
                log_activity('comment_moderation', 'comment', $comment_id, "Comment marked as {$action}");
            }
            
            // Send notification if approved
            if ($action == 'approve') {
                $stmt = $pdo->prepare("SELECT post_id FROM comments WHERE id = ?");
                $stmt->execute([$comment_id]);
                $post_id = $stmt->fetchColumn();
                if (function_exists('send_comment_notification')) {
                    send_comment_notification($post_id, $comment_id);
                }
            }
            
            $message = ["success" => "Comment marked as " . ucfirst($action)];
            break;
            
        case 'delete':
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$comment_id]);
            if (function_exists('log_activity')) {
                log_activity('comment_deletion', 'comment', $comment_id, 'Comment deleted');
            }
            $message = ["success" => "Comment deleted successfully"];
            break;
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$where_clause = $status_filter != 'all' ? "WHERE comments.status = '{$status_filter}'" : "";

// Get all comments with post and user information
$comments = $pdo->query(
    "SELECT comments.*, posts.title as post_title, users.username,
            moderator.username as moderator_name
     FROM comments
     JOIN posts ON comments.post_id = posts.id
     JOIN users ON comments.user_id = users.id
     LEFT JOIN users moderator ON comments.moderated_by = moderator.id
     {$where_clause}
     ORDER BY comments.created_at DESC"
)->fetchAll();

// Get comment counts by status
$comment_counts = [
    'all' => $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn(),
    'approved' => $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'approved'")->fetchColumn(),
    'spam' => $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'spam'")->fetchColumn(),
];

include './layouts/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-comments me-2"></i>Comment Management</h2>
                <p class="text-muted">Moderate and manage user comments</p>
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
    
    <!-- Filter Tabs -->
    <div class="row mb-4">
        <div class="col-md-12">
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link <?php echo $status_filter == 'all' ? 'active' : ''; ?>" href="?status=all">
                        All <span class="badge bg-secondary"><?php echo $comment_counts['all']; ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $status_filter == 'pending' ? 'active' : ''; ?>" href="?status=pending">
                        Pending <span class="badge bg-warning text-dark"><?php echo $comment_counts['pending']; ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $status_filter == 'approved' ? 'active' : ''; ?>" href="?status=approved">
                        Approved <span class="badge bg-success"><?php echo $comment_counts['approved']; ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $status_filter == 'spam' ? 'active' : ''; ?>" href="?status=spam">
                        Spam <span class="badge bg-danger"><?php echo $comment_counts['spam']; ?></span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    
    <!-- Comments Table -->
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Post</th>
                    <th>Author</th>
                    <th>Comment</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($comments) > 0): ?>
                    <?php foreach($comments as $comment): ?>
                        <tr>
                            <td><?php echo $comment['id']; ?></td>
                            <td>
                                <a href="post.php?id=<?php echo $comment['post_id']; ?>" target="_blank">
                                    <?php echo htmlspecialchars(substr($comment['post_title'], 0, 30)) . (strlen($comment['post_title']) > 30 ? '...' : ''); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($comment['username']); ?></td>
                            <td>
                                <div style="max-height: 100px; overflow-y: auto;">
                                    <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $comment['status'] == 'approved' ? 'success' : 
                                        ($comment['status'] == 'spam' ? 'danger' : 'warning');
                                ?>">
                                    <?php echo ucfirst($comment['status']); ?>
                                </span>
                                <?php if ($comment['moderated_by']): ?>
                                    <small class="d-block text-muted mt-1" data-bs-toggle="tooltip" title="Moderated on <?php echo date('Y-m-d H:i', strtotime($comment['moderated_at'])); ?>">
                                        by <?php echo htmlspecialchars($comment['moderator_name']); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu">
                                        <?php if ($comment['status'] != 'approved'): ?>
                                            <li>
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                    <button type="submit" class="dropdown-item text-success">
                                                        <i class="fas fa-check me-2"></i>Approve
                                                    </button>
                                                </form>
                                            </li>
                                        <?php endif; ?>
                                        <?php if ($comment['status'] != 'pending'): ?>
                                            <li>
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="pending">
                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                    <button type="submit" class="dropdown-item text-warning">
                                                        <i class="fas fa-clock me-2"></i>Mark as Pending
                                                    </button>
                                                </form>
                                            </li>
                                        <?php endif; ?>
                                        <?php if ($comment['status'] != 'spam'): ?>
                                            <li>
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="spam">
                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fas fa-ban me-2"></i>Mark as Spam
                                                    </button>
                                                </form>
                                            </li>
                                        <?php endif; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this comment?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fas fa-trash-alt me-2"></i>Delete
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-comment-slash fa-3x mb-3"></i>
                                <p>No comments found</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
});
</script>

<?php include './admin/footer.php'; ?>