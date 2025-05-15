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

// Handle post actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $post_id = $_POST['post_id'];
    
    switch ($action) {
        case 'delete':
            // Delete post and its attachments
            $pdo->beginTransaction();
            try {
                // Get attachments to delete files
                $stmt = $pdo->prepare("SELECT filename FROM attachments WHERE post_id = ?");
                $stmt->execute([$post_id]);
                $attachments = $stmt->fetchAll();
                
                // Delete files from uploads directory
                foreach ($attachments as $attachment) {
                    $file_path = __DIR__ . '/uploads/' . $attachment['filename'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
                
                // Delete from database
                $stmt = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
                $stmt->execute([$post_id]);
                
                $stmt = $pdo->prepare("DELETE FROM attachments WHERE post_id = ?");
                $stmt->execute([$post_id]);
                
                $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
                $stmt->execute([$post_id]);
                
                $pdo->commit();
                $message = ["success" => "Post deleted successfully"];
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = ["danger" => "Error deleting post: " . $e->getMessage()];
            }
            break;
            
        case 'feature':
            // Add featured flag to posts table if not exists
            try {
                $pdo->query("ALTER TABLE posts ADD COLUMN IF NOT EXISTS is_featured BOOLEAN DEFAULT FALSE");
                $stmt = $pdo->prepare("UPDATE posts SET is_featured = NOT is_featured WHERE id = ?");
                $stmt->execute([$post_id]);
                $message = ["success" => "Post feature status updated"];
            } catch (Exception $e) {
                $message = ["danger" => "Error updating post feature status: " . $e->getMessage()];
            }
            break;
    }
}

// Get all posts with user information
$posts = $pdo->query(
    "SELECT posts.*, users.username, 
            (SELECT COUNT(*) FROM comments WHERE post_id = posts.id) as comment_count,
            (SELECT COUNT(*) FROM attachments WHERE post_id = posts.id) as attachment_count
     FROM posts 
     JOIN users ON posts.user_id = users.id 
     ORDER BY created_at DESC"
)->fetchAll();

include './layouts/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-file-alt me-2"></i>Post Management</h2>
                <p class="text-muted">Manage all posts on your site</p>
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
    
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Created</th>
                            <th>Comments</th>
                            <th>Attachments</th>
                            <th>Featured</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($posts as $post): ?>
                            <tr>
                                <td><?php echo $post['id']; ?></td>
                                <td>
                                    <a href="post.php?id=<?php echo $post['id']; ?>" target="_blank">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($post['username']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($post['created_at'])); ?></td>
                                <td><span class="badge bg-secondary"><?php echo $post['comment_count']; ?></span></td>
                                <td><span class="badge bg-secondary"><?php echo $post['attachment_count']; ?></span></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="feature">
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo isset($post['is_featured']) && $post['is_featured'] ? 'btn-warning' : 'btn-outline-secondary'; ?>">
                                            <i class="fas fa-star"></i>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-info" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this post? This will also delete all comments and attachments.');">                                            
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include './layouts/footer.php'; ?>