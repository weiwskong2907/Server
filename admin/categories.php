<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
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

// Initialize controller
$categoriesController = new CategoriesController($pdo);

// Set page title
$page_title = 'Manage Categories';

$errors = [];
$success = '';

// Handle form submission for adding/editing category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category_id = isset($_POST['category_id']) ? $_POST['category_id'] : null;
    
    // Validate input
    if (empty($name)) {
        $errors[] = 'Category name is required';
    }
    
    if (empty($errors)) {
        if ($category_id) {
            // Update existing category
            $result = $categoriesController->updateCategory($category_id, $name, $description);
        } else {
            // Create new category
            $result = $categoriesController->createCategory($name, $description);
        }
        
        if ($result['success']) {
            $success = $result['message'];
            // Clear form after successful submission
            $name = '';
            $description = '';
            $category_id = null;
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $result = $categoriesController->deleteCategory($_GET['id']);
    
    if ($result['success']) {
        header('Location: categories.php?success=category_deleted');
        exit;
    } else {
        $errors[] = $result['message'];
    }
}

// Handle edit action
$edit_category = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_category = $categoriesController->getCategoryById($_GET['id']);
    
    if ($edit_category) {
        $category_id = $edit_category['id'];
        $name = $edit_category['name'];
        $description = $edit_category['description'];
    } else {
        header('Location: categories.php?error=category_not_found');
        exit;
    }
}

// Get all categories
$categories = $categoriesController->getAllCategories();

// Include header
include 'header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manage Categories</h1>
    </div>
    
    <?php if (isset($_GET['success']) && $_GET['success'] == 'category_deleted'): ?>
        <div class="alert alert-success" role="alert">
            Category deleted successfully.
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success" role="alert">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?>
                    </h6>
                </div>
                <div class="card-body">
                    <form action="" method="post">
                        <?php if ($edit_category): ?>
                            <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $edit_category ? 'Update Category' : 'Add Category'; ?>
                            </button>
                        </div>
                        
                        <?php if ($edit_category): ?>
                            <div class="d-grid mt-2">
                                <a href="categories.php" class="btn btn-secondary">Cancel Edit</a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">All Categories</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($categories)): ?>
                        <p class="text-center">No categories found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="categoriesTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Posts</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?php echo $category['id']; ?></td>
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td>
                                                <?php 
                                                    echo !empty($category['description']) 
                                                        ? htmlspecialchars(substr($category['description'], 0, 50)) . (strlen($category['description']) > 50 ? '...' : '') 
                                                        : '<em>No description</em>'; 
                                                ?>
                                            </td>
                                            <td><?php echo $category['post_count']; ?></td>
                                            <td><?php echo date('M j, Y', strtotime($category['created_at'])); ?></td>
                                            <td>
                                                <a href="categories.php?action=edit&id=<?php echo $category['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <?php if ($category['post_count'] == 0): ?>
                                                    <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $category['id']; ?>)" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-danger" disabled title="Cannot delete category with posts">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this category? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDelete(categoryId) {
        document.getElementById('confirmDeleteBtn').href = 'categories.php?action=delete&id=' + categoryId;
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }
    
    // Initialize DataTable if available
    if (typeof $.fn.DataTable !== 'undefined') {
        $(document).ready(function() {
            $('#categoriesTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 10
            });
        });
    }
</script>

<?php include 'footer.php'; ?>