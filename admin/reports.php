<?php
require_once '../includes/config.php';
require_once 'layout.php';

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'pending';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;

// Get reported content
$reports_data = get_reported_content($page, $per_page, $filter);

// Handle report actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $report_id = (int)$_POST['report_id'];
    $action = $_POST['action'];
    $notes = $_POST['notes'] ?? '';
    
    if (handle_report($report_id, $action, $notes)) {
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Report handled successfully.'];
    }
    
    // Redirect to prevent form resubmission
    header("Location: reports.php?filter=$filter&page=$page");
    exit;
}

// Get page header
echo get_admin_header('Report Management', [
    'Dashboard' => 'index.php',
    'Reports' => null
]);

// Display alerts
if (isset($_SESSION['alert'])) {
    echo get_admin_alert($_SESSION['alert']['type'], $_SESSION['alert']['message']);
    unset($_SESSION['alert']);
}
?>

<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col">
                <h5 class="card-title mb-0">Report Management</h5>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <!-- Filter Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo $filter === 'pending' ? 'active' : ''; ?>" 
                   href="?filter=pending">
                    Pending Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $filter === 'resolved' ? 'active' : ''; ?>" 
                   href="?filter=resolved">
                    Resolved Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>" 
                   href="?filter=all">
                    All Reports
                </a>
            </li>
        </ul>
        
        <!-- Reports Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Reported By</th>
                        <th>Content Type</th>
                        <th>Content</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Reported At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports_data['reports'] as $report): ?>
                        <tr>
                            <td>
                                <a href="/profile.php?id=<?php echo $report['reporter_id']; ?>" 
                                   class="text-decoration-none">
                                    <?php echo htmlspecialchars($report['reporter_username']); ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo get_content_type_badge_color($report['content_type']); ?>">
                                    <?php echo ucfirst($report['content_type']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($report['content_type'] === 'post'): ?>
                                    <a href="/post.php?id=<?php echo $report['content_id']; ?>" 
                                       target="_blank" class="text-decoration-none">
                                        <?php echo htmlspecialchars(substr($report['content_title'], 0, 50)) . 
                                            (strlen($report['content_title']) > 50 ? '...' : ''); ?>
                                    </a>
                                <?php elseif ($report['content_type'] === 'comment'): ?>
                                    <div class="small text-muted">
                                        <?php echo htmlspecialchars(substr($report['content_text'], 0, 50)) . 
                                            (strlen($report['content_text']) > 50 ? '...' : ''); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($report['reason']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo get_report_status_badge_color($report['status']); ?>">
                                    <?php echo ucfirst($report['status']); ?>
                                </span>
                            </td>
                            <td><?php echo time_ago($report['created_at']); ?></td>
                            <td>
                                <?php if ($report['status'] === 'pending'): ?>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#handleReportModal<?php echo $report['id']; ?>">
                                            <i class="fas fa-gavel"></i> Handle
                                        </button>
                                    </div>
                                    
                                    <!-- Handle Report Modal -->
                                    <div class="modal fade" id="handleReportModal<?php echo $report['id']; ?>" 
                                         tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                                    <input type="hidden" name="action" value="handle">
                                                    
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Handle Report</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" 
                                                                aria-label="Close"></button>
                                                    </div>
                                                    
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Action</label>
                                                            <select name="action" class="form-select" required>
                                                                <option value="delete">Delete Content</option>
                                                                <option value="warn">Warn User</option>
                                                                <option value="ignore">Ignore Report</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Notes</label>
                                                            <textarea name="notes" class="form-control" rows="3" 
                                                                      placeholder="Add notes about this action..."></textarea>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" 
                                                                data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Submit</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="small text-muted">
                                        <?php echo htmlspecialchars($report['action_taken']); ?>
                                        <br>
                                        By: <?php echo htmlspecialchars($report['handled_by_username']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($reports_data['total_pages'] > 1): ?>
            <div class="mt-4">
                <?php echo get_admin_pagination($page, $reports_data['total_pages'], 
                    "reports.php?filter=$filter&page=%d"); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Get page footer
echo get_admin_footer();
?> 