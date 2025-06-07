<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'controllers/users_controller.php';
require_once 'layout.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
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
$usersController = new UsersController($pdo);

// Handle actions
$message = '';
$message_type = '';

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $result = $usersController->deleteUser($_GET['id']);
    if ($result['success']) {
        $message = $result['message'];
        $message_type = 'success';
    } else {
        $message = $result['message'];
        $message_type = 'danger';
    }
}

// Handle search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;

// Get users with pagination
$result = $usersController->getAllUsers($page, $records_per_page, $search);
$users = $result['users'];
$pagination = $result['pagination'];

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$per_page = 20;

// Get users data
$users_data = get_user_management_data($page, $per_page, $filter, $search);

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action'];
    
    switch ($action) {
        case 'approve':
            if (update_user_status($user_id, 'active')) {
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'User approved successfully.'];
            }
            break;
            
        case 'suspend':
            if (update_user_status($user_id, 'suspended')) {
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'User suspended successfully.'];
            }
            break;
            
        case 'delete':
            if (delete_user($user_id)) {
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'User deleted successfully.'];
            }
            break;
            
        case 'change_role':
            $role = $_POST['role'];
            if (update_user_role($user_id, $role)) {
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'User role updated successfully.'];
            }
            break;
    }
    
    // Redirect to prevent form resubmission
    header("Location: users.php?filter=$filter&search=$search&page=$page");
    exit;
}

// Get page header
echo get_admin_header('User Management', [
    'Dashboard' => 'index.php',
    'Users' => null
]);

// Display alerts
if (isset($_SESSION['alert'])) {
    echo get_admin_alert($_SESSION['alert']['type'], $_SESSION['alert']['message']);
    unset($_SESSION['alert']);
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">User Management</h1>
        <a href="user_add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New User
        </a>
    </div>
    
    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="GET" class="row g-3">
                <div class="col-md-10">
                    <input type="text" class="form-control" name="search" placeholder="Search by username, email or role" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Filter Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>" 
               href="?filter=all<?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                All Users
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter === 'pending' ? 'active' : ''; ?>" 
               href="?filter=pending<?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                Pending
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter === 'active' ? 'active' : ''; ?>" 
               href="?filter=active<?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                Active
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter === 'suspended' ? 'active' : ''; ?>" 
               href="?filter=suspended<?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                Suspended
            </a>
        </li>
    </ul>
    
    <!-- Users Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo isset($user['is_admin']) && $user['is_admin'] == 1 ? 'danger' : 'secondary'; ?>">
                                        <?php echo ucfirst(htmlspecialchars(isset($user['is_admin']) && $user['is_admin'] == 1 ? 'admin' : 'user')); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo isset($user['status']) && $user['status'] === 'active' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst(htmlspecialchars($user['status'] ?? 'active')); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="user_edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user? This will also delete all their posts and comments.')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center mt-4">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                            Previous
                        </a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $page >= $pagination['total_pages'] ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                            Next
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Get page footer
echo get_admin_footer();
?>