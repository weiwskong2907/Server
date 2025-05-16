<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

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

include 'header.php';
?>

<div class="container mt-4">
    <!-- Add after the header section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search Posts</label>
                            <input type="text" class="form-control" id="search" name="search" placeholder="Post title" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">All Categories</option>
                                <?php
                                $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
                                foreach ($categories as $category) {
                                    $selected = isset($_GET['category']) && $_GET['category'] == $category['id'] ? 'selected' : '';
                                    echo "<option value=\"{$category['id']}\" {$selected}>{$category['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="sort" class="form-label">Sort By</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="newest" <?php echo (!isset($_GET['sort']) || $_GET['sort'] == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                                <option value="oldest" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                <option value="title" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'title' ? 'selected' : ''; ?>>Title</option>
                                <option value="comments" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'comments' ? 'selected' : ''; ?>>Most Comments</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add this after the search form and before the posts table -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form id="bulkActionForm" method="POST">
                    <div class="row g-2 align-items-center">
                        <div class="col-auto">
                            <select class="form-select" name="bulk_action" id="bulkAction">
                                <option value="">Bulk Actions</option>
                                <option value="feature">Feature Selected</option>
                                <option value="unfeature">Unfeature Selected</option>
                                <option value="delete">Delete Selected</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary" id="applyBulkAction" disabled>Apply</button>
                        </div>
                        <div class="col-auto ms-auto">
                            <span class="text-muted"><span id="selectedCount">0</span> items selected</span>
                        </div>
                    </div>
                    <input type="hidden" name="action" value="bulk">
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add this to the table header row, as the first column -->
<th width="40">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="selectAll">
    </div>
</th>

<!-- Add this to each post row, as the first column -->
<td>
    <div class="form-check">
        <input class="form-check-input post-checkbox" type="checkbox" name="post_ids[]" value="<?php echo $post['id']; ?>" form="bulkActionForm">
    </div>
</td>

<!-- Add this JavaScript before the closing </body> tag -->
<script>
$(document).ready(function() {
    // Select all checkbox functionality
    $('#selectAll').change(function() {
        $('.post-checkbox').prop('checked', $(this).prop('checked'));
        updateSelectedCount();
    });
    
    // Update selected count when individual checkboxes change
    $('.post-checkbox').change(function() {
        updateSelectedCount();
    });
    
    // Update the selected count and enable/disable the apply button
    function updateSelectedCount() {
        const count = $('.post-checkbox:checked').length;
        $('#selectedCount').text(count);
        $('#applyBulkAction').prop('disabled', count === 0 || $('#bulkAction').val() === '');
    }
    
    // Enable/disable apply button based on selection
    $('#bulkAction').change(function() {
        $('#applyBulkAction').prop('disabled', $(this).val() === '' || $('.post-checkbox:checked').length === 0);
    });
    
    // Confirm before submitting delete action
    $('#bulkActionForm').submit(function(e) {
        if ($('#bulkAction').val() === 'delete') {
            if (!confirm('Are you sure you want to delete the selected posts? This action cannot be undone.')) {
                e.preventDefault();
            }
        }
    });
});
</script>
<?php include 'footer.php';?>