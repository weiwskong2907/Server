<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'controllers/logs_controller.php';

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
$logsController = new LogsController($pdo);

// Set page title
$page_title = 'Activity Logs';

// Handle clear logs action
if (isset($_POST['clear_logs']) && isset($_POST['days'])) {
    $days = (int)$_POST['days'];
    if ($days > 0) {
        $result = $logsController->clearOldLogs($days);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    } else {
        $error = 'Invalid number of days';
    }
}

// Get filter parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$action_type = isset($_GET['action_type']) ? trim($_GET['action_type']) : '';
$entity_type = isset($_GET['entity_type']) ? trim($_GET['entity_type']) : '';

// Get logs with pagination
$result = $logsController->getAllLogs($page, 20, $search, $action_type, $entity_type);
$logs = $result['logs'];
$pagination = $result['pagination'];

// Get filter options
$action_types = $logsController->getActionTypes();
$entity_types = $logsController->getEntityTypes();

// Include header
include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Activity Logs</h1>
    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#clearLogsModal">
        <i class="fas fa-trash-alt me-1"></i> Clear Old Logs
    </button>
</div>

<?php if (isset($success)): ?>
<div class="alert alert-success">
    <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-danger">
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search logs...">
            </div>
            <div class="col-md-3">
                <label for="action_type" class="form-label">Action Type</label>
                <select class="form-select" id="action_type" name="action_type">
                    <option value="">All Actions</option>
                    <?php foreach ($action_types as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $action_type === $type ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst($type)); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="entity_type" class="form-label">Entity Type</label>
                <select class="form-select" id="entity_type" name="entity_type">
                    <option value="">All Entities</option>
                    <?php foreach ($entity_types as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $entity_type === $type ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst($type)); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Logs Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Entity ID</th>
                        <th>Description</th>
                        <th>Date/Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No activity logs found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($log['id']); ?></td>
                        <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                        <td>
                            <span class="badge bg-<?php echo getActionBadgeClass($log['action_type']); ?>">
                                <?php echo htmlspecialchars(ucfirst($log['action_type'])); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars(ucfirst($log['entity_type'])); ?></td>
                        <td><?php echo htmlspecialchars($log['entity_id']); ?></td>
                        <td><?php echo htmlspecialchars($log['description']); ?></td>
                        <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mt-4">
                <?php if ($pagination['current_page'] > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $pagination['current_page'] - 1; ?>&search=<?php echo urlencode($search); ?>&action_type=<?php echo urlencode($action_type); ?>&entity_type=<?php echo urlencode($entity_type); ?>">
                        Previous
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                <li class="page-item <?php echo $i === $pagination['current_page'] ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&action_type=<?php echo urlencode($action_type); ?>&entity_type=<?php echo urlencode($entity_type); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $pagination['current_page'] + 1; ?>&search=<?php echo urlencode($search); ?>&action_type=<?php echo urlencode($action_type); ?>&entity_type=<?php echo urlencode($entity_type); ?>">
                        Next
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Clear Logs Modal -->
<div class="modal fade" id="clearLogsModal" tabindex="-1" aria-labelledby="clearLogsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clearLogsModalLabel">Clear Old Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <p>This will permanently delete old activity logs. Please specify how many days of logs to keep:</p>
                    <div class="mb-3">
                        <label for="days" class="form-label">Keep logs from the last</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="days" name="days" value="30" min="1" required>
                            <span class="input-group-text">days</span>
                        </div>
                        <div class="form-text">Logs older than this will be permanently deleted.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="clear_logs" class="btn btn-danger">Clear Logs</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Helper function for badge colors
function getActionBadgeClass($action_type) {
    switch ($action_type) {
        case 'create':
            return 'success';
        case 'update':
            return 'primary';
        case 'delete':
            return 'danger';
        case 'login':
            return 'info';
        case 'logout':
            return 'secondary';
        default:
            return 'warning';
    }
}

// Include footer
include 'footer.php';
?>