<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'controllers/posts_controller.php';
require_once 'controllers/categories_controller.php';

// Check if user is logged in and is an admin
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
$page_title = 'Edit Post';

// Check if post ID is provided
if (!isset($_GET['id'])) {
    header('Location: posts.php');
    exit;
}

$post_id = $_GET['id'];
$post = $postsController->getPostById($post_id);

if (!$post) {
    header('Location: posts.php?error=post_not_found');
    exit;
}

// Handle form submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category_id = $_POST['category_id'];
    $status = $_POST['status'];
    
    // Validate inputs
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($content)) {
        $errors[] = "Content is required";
    }
    
    if (empty($category_id)) {
        $errors[] = "Category is required";
    }
    
    if (empty($status)) {
        $errors[] = "Status is required";
    }
    
    // If no errors, update the post
    if (empty($errors)) {
        $result = $postsController->updatePost($post_id, [
            'title' => $title,
            'content' => $content,
            'category_id' => $category_id,
            'status' => $status
        ]);
        
        if ($result['success']) {
            header('Location: posts.php?success=post_updated');
            exit;
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Include header
include 'header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Edit Post</h1>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Post Details</h6>
            <a href="posts.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Posts
            </a>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="post_edit.php?id=<?php echo $post_id; ?>">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea class="form-control" id="editor" name="content" rows="10" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select class="form-control" id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php 
                        $categories = $categoriesController->getAllCategories();
                        foreach ($categories as $category): 
                        ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo ($category['id'] == $post['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select class="form-control" id="status" name="status" required>
                        <option value="published" <?php echo ($post['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo ($post['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Author</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($post['username']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>Created At</label>
                    <input type="text" class="form-control" value="<?php echo date('F d, Y h:i A', strtotime($post['created_at'])); ?>" readonly>
                </div>

                // Add to post_edit.php form
<div class="form-group">
    <label for="featured_image">Featured Image</label>
    <input type="file" class="form-control-file" id="featured_image" name="featured_image">
    <?php if (!empty($post['featured_image'])): ?>
        <div class="mt-2">
            <img src="../uploads/<?php echo htmlspecialchars($post['featured_image']); ?>" alt="Featured Image" style="max-width: 200px;" class="img-thumbnail">
        </div>
    <?php endif; ?>
</div>

<div class="form-group">
    <label for="tags">Tags (comma separated)</label>
    <input type="text" class="form-control" id="tags" name="tags" value="<?php echo htmlspecialchars($tags_string); ?>">
</div>

<div class="form-group">
    <label for="meta_title">SEO Title</label>
    <input type="text" class="form-control" id="meta_title" name="meta_title" value="<?php echo htmlspecialchars($post['meta_title'] ?? ''); ?>">
</div>

<div class="form-group">
    <label for="meta_description">SEO Description</label>
    <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($post['meta_description'] ?? ''); ?></textarea>
</div>
                
                <button type="submit" class="btn btn-primary">Update Post</button>
                <a href="posts.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<!-- Initialize TinyMCE editor -->
<script src="https://cdn.tiny.cloud/1/xj1pomo1mrpu7fz9gus1zulblwty6ajfd4c76gtbmsx5fhwn/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#editor',
        plugins: 'link image code',
        toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | link image | code',
        height: 400
    });
</script>

<?php include 'footer.php'; ?>