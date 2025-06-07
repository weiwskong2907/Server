<?php
require_once '../includes/config.php';
require_once 'layout.php';

// Handle backup actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'create':
            $backup_data = get_backup_data();
            $backup_file = 'backups/backup_' . date('Y-m-d_H-i-s') . '.json';
            
            if (file_put_contents($backup_file, json_encode($backup_data, JSON_PRETTY_PRINT))) {
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Backup created successfully.'];
            } else {
                $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Failed to create backup.'];
            }
            break;
            
        case 'restore':
            if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
                $backup_data = json_decode(file_get_contents($_FILES['backup_file']['tmp_name']), true);
                
                if ($backup_data && restore_from_backup($backup_data)) {
                    $_SESSION['alert'] = ['type' => 'success', 'message' => 'Backup restored successfully.'];
                } else {
                    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Failed to restore backup.'];
                }
            } else {
                $_SESSION['alert'] = ['type' => 'danger', 'message' => 'No backup file uploaded.'];
            }
            break;
            
        case 'delete':
            $backup_file = $_POST['backup_file'];
            
            if (file_exists($backup_file) && unlink($backup_file)) {
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Backup deleted successfully.'];
            } else {
                $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Failed to delete backup.'];
            }
            break;
    }
    
    // Redirect to prevent form resubmission
    header('Location: backup.php');
    exit;
}

// Get list of backup files
$backup_files = glob('backups/backup_*.json');
usort($backup_files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

// Get page header
echo get_admin_header('System Backup', [
    'Dashboard' => 'index.php',
    'Backup' => null
]);

// Display alerts
if (isset($_SESSION['alert'])) {
    echo get_admin_alert($_SESSION['alert']['type'], $_SESSION['alert']['message']);
    unset($_SESSION['alert']);
}
?>

<div class="row g-4">
    <!-- Backup Actions -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Backup Actions</h5>
            </div>
            
            <div class="card-body">
                <div class="d-grid gap-3">
                    <!-- Create Backup -->
                    <form method="POST" class="d-grid">
                        <input type="hidden" name="action" value="create">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-download me-2"></i>Create Backup
                        </button>
                    </form>
                    
                    <!-- Restore Backup -->
                    <form method="POST" enctype="multipart/form-data" class="d-grid">
                        <input type="hidden" name="action" value="restore">
                        <div class="mb-3">
                            <label class="form-label">Restore from Backup File</label>
                            <input type="file" name="backup_file" class="form-control" 
                                   accept=".json" required>
                        </div>
                        <button type="submit" class="btn btn-warning" 
                                onclick="return confirm('Are you sure you want to restore from this backup? This will overwrite current data.')">
                            <i class="fas fa-upload me-2"></i>Restore Backup
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Backup Information -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Backup Information</h5>
            </div>
            
            <div class="card-body">
                <div class="mb-3">
                    <h6>What's Included</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i>User Accounts</li>
                        <li><i class="fas fa-check text-success me-2"></i>Posts & Comments</li>
                        <li><i class="fas fa-check text-success me-2"></i>Categories & Tags</li>
                        <li><i class="fas fa-check text-success me-2"></i>System Settings</li>
                        <li><i class="fas fa-check text-success me-2"></i>Activity Logs</li>
                    </ul>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Backups are stored in JSON format and can be used to restore the system to a previous state.
                </div>
            </div>
        </div>
    </div>
    
    <!-- Backup List -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Available Backups</h5>
            </div>
            
            <div class="card-body">
                <?php if (empty($backup_files)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No backups available. Create your first backup using the button on the left.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Backup File</th>
                                    <th>Created</th>
                                    <th>Size</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backup_files as $file): ?>
                                    <tr>
                                        <td><?php echo basename($file); ?></td>
                                        <td><?php echo date('M d, Y H:i:s', filemtime($file)); ?></td>
                                        <td><?php echo format_bytes(filesize($file)); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="<?php echo $file; ?>" class="btn btn-sm btn-primary" 
                                                   download>
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="backup_file" value="<?php echo $file; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                            onclick="return confirm('Are you sure you want to delete this backup?')">
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
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Helper function to format file size
function format_bytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Get page footer
echo get_admin_footer();
?> 