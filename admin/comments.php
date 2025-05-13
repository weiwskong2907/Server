<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/email.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !is_admin($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Handle comment moderation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $comment_id = $_POST['comment_id'];
    
    switch ($action) {
        case 'approve':
        case 'spam':
            $stmt = $pdo->prepare(
                "UPDATE comments 
                 SET status = ?, moderated_by = ?, moderated_at = NOW() 
                 WHERE id = ?"
            );
            $stmt->execute([$action, $_SESSION['user_id'], $comment_id]);
            
            // Log activity
            log_activity('comment_moderation', 'comment', $comment_id, "Comment marked as {$action}");
            
            // Send notification if approved
            if ($action == 'approve') {
                $stmt = $pdo->prepare("SELECT post_id FROM comments WHERE id = ?");
                $stmt->execute([$comment_id]);
                $post_id = $stmt->fetchColumn();
                send_comment_notification($post_id, $comment_id);
            }
            break;
            
        case 'delete':
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$comment_id]);
            log_activity('comment_deletion', 'comment', $comment_id, 'Comment deleted');
            break;
    }
}

// Get all comments with post and user information
$comments = $pdo->query(
    "SELECT comments.*, posts.title as post_title, users.username,
            moderator.username as moderator_name
     FROM comments
     JOIN posts ON comments.post_id = posts.id
     JOIN users ON comments.user_id = users.id
     LEFT JOIN users moderator ON comments.moderated_by = moderator.id
     ORDER BY comments.created_at DESC"
)->fetchAll();

include '../layouts/header.php';
?>

<div class="container mt-4">
    <h2>Comment Moderation</h2>
    
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Post</th>
                    <th>Author</th>
                    <th>Comment</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($comments as $comment): ?>
                    <tr>
                        <td><?php echo $comment['id']; ?></td>
                        <td>
                            <a href="../post.php?id=<?php echo $comment['post_id']; ?>">
                                <?php echo htmlspecialchars($comment['post_title']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($comment['username']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($comment['content'])); ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $comment['status'] == 'approved' ? 'success' : 
                                    ($comment['status'] == 'spam' ? 'danger' : 'warning');
                            ?>">
                                <?php echo ucfirst($comment['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></td>
                        <td>
                            <div class="btn-group">
                                <?php if ($comment['status'] != 'approved'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($comment['status'] != 'spam'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="spam">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-warning">Mark as Spam</button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this comment?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>