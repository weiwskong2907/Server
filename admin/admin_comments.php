<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/email.php';

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

include 'header.php';
?>

<!-- Add this CSS in the header.php or inline -->
<style>
.comment-content {
    max-height: 100px;
    overflow-y: auto;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
    border: 1px solid #dee2e6;
}

.comment-meta {
    font-size: 0.85rem;
    color: #6c757d;
}

.comment-actions {
    opacity: 0.8;
    transition: opacity 0.2s;
}

.comment-actions:hover {
    opacity: 1;
}

.table > :not(caption) > * > * {
    vertical-align: middle;
}

.status-badge {
    min-width: 80px;
    text-align: center;
}

.quick-reply {
    margin-left: 10px;
}
</style>

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
    
    <!-- Add this above the table -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="input-group">
                <input type="text" class="form-control" id="commentSearch" placeholder="Search comments...">
                <button class="btn btn-outline-secondary" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        <div class="col-md-6 text-end">
            <select class="form-select d-inline-block w-auto" id="commentSort">
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
            </select>
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
                            <td width="5%"><?php echo $comment['id']; ?></td>
                            <td width="15%">
                                <a href="../post.php?id=<?php echo $comment['post_id']; ?>" target="_blank" class="text-decoration-none">
                                    <?php echo htmlspecialchars(substr($comment['post_title'], 0, 30)) . (strlen($comment['post_title']) > 30 ? '...' : ''); ?>
                                </a>
                            </td>
                            <td width="15%">
                                <div class="d-flex align-items-center">
                                    <?php if ($comment['profile_picture']): ?>
                                        <img src="<?php echo htmlspecialchars($comment['profile_picture']); ?>" class="rounded-circle me-2" width="30">
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($comment['username']); ?>
                                </div>
                            </td>
                            <td>
                                <div class="comment-content">
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
                                <button type="button" class="btn btn-sm btn-info quick-reply" data-comment-id="<?php echo $comment['id']; ?>" data-post-id="<?php echo $comment['post_id']; ?>" data-username="<?php echo htmlspecialchars($comment['username']); ?>">Quick Reply</button>
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

<!-- Add this modal at the end of the file -->
<div class="modal fade" id="quickReplyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reply to Comment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="../add_comment.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Replying to <span id="replyToUsername"></span></label>
                        <textarea class="form-control" name="content" rows="4" required></textarea>
                    </div>
                    <input type="hidden" name="post_id" id="replyPostId">
                    <input type="hidden" name="parent_id" id="replyCommentId">
                    <input type="hidden" name="redirect" value="admin_comments.php">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Post Reply</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add this JavaScript before the closing </body> tag -->
<script>
$(document).ready(function() {
    $('.quick-reply').click(function() {
        const commentId = $(this).data('comment-id');
        const postId = $(this).data('post-id');
        const username = $(this).data('username');
        
        $('#replyCommentId').val(commentId);
        $('#replyPostId').val(postId);
        $('#replyToUsername').text(username);
        
        $('#quickReplyModal').modal('show');
    });
});
</script>

<script>
// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
});
</script>

<!-- Add this JavaScript before the closing </body> tag -->
<script>
$(document).ready(function() {
    // AJAX form submission for comment moderation
    $('.dropdown-item').click(function(e) {
        const form = $(this).closest('form');
        if (form.length) {
            e.preventDefault();
            
            $.post(form.attr('action'), form.serialize(), function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error processing request');
                }
            }, 'json');
        }
    });

    // Enhance tooltips
    $('[data-bs-toggle="tooltip"]').tooltip({
        html: true,
        placement: 'top'
    });
});
</script>

<?php include 'footer.php'; ?>