<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'controllers/settings_controller.php';
require_once 'layout.php';

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

// Initialize controller
$settingsController = new SettingsController($pdo);

// Set page title
$page_title = 'Site Settings';

$errors = [];
$success = '';

// Get current settings
$settings = get_system_settings();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_settings = [
        'site_name' => $_POST['site_name'],
        'site_description' => $_POST['site_description'],
        'site_email' => $_POST['site_email'],
        'registration_enabled' => isset($_POST['registration_enabled']),
        'email_verification' => isset($_POST['email_verification']),
        'moderation_enabled' => isset($_POST['moderation_enabled']),
        'posts_per_page' => (int)$_POST['posts_per_page'],
        'comments_per_page' => (int)$_POST['comments_per_page'],
        'max_upload_size' => (int)$_POST['max_upload_size'],
        'allowed_file_types' => $_POST['allowed_file_types'],
        'maintenance_mode' => isset($_POST['maintenance_mode']),
        'maintenance_message' => $_POST['maintenance_message']
    ];
    
    if (update_system_settings($new_settings)) {
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Settings updated successfully.'];
    }
    
    // Redirect to prevent form resubmission
    header('Location: settings.php');
    exit;
}

// Get page header
echo get_admin_header('System Settings', [
    'Dashboard' => 'index.php',
    'Settings' => null
]);

// Display alerts
if (isset($_SESSION['alert'])) {
    echo get_admin_alert($_SESSION['alert']['type'], $_SESSION['alert']['message']);
    unset($_SESSION['alert']);
}
?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">System Settings</h5>
    </div>
    
    <div class="card-body">
        <form method="POST" class="needs-validation" novalidate>
            <!-- General Settings -->
            <div class="mb-4">
                <h6 class="mb-3">General Settings</h6>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Site Name</label>
                        <input type="text" name="site_name" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Site Email</label>
                        <input type="email" name="site_email" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['site_email']); ?>" required>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Site Description</label>
                        <textarea name="site_description" class="form-control" rows="2"><?php 
                            echo htmlspecialchars($settings['site_description']); 
                        ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- User Settings -->
            <div class="mb-4">
                <h6 class="mb-3">User Settings</h6>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="registration_enabled" class="form-check-input" 
                                   id="registration_enabled" <?php echo $settings['registration_enabled'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="registration_enabled">
                                Enable User Registration
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="email_verification" class="form-check-input" 
                                   id="email_verification" <?php echo $settings['email_verification'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="email_verification">
                                Require Email Verification
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="moderation_enabled" class="form-check-input" 
                                   id="moderation_enabled" <?php echo $settings['moderation_enabled'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="moderation_enabled">
                                Enable Content Moderation
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Content Settings -->
            <div class="mb-4">
                <h6 class="mb-3">Content Settings</h6>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Posts Per Page</label>
                        <input type="number" name="posts_per_page" class="form-control" 
                               value="<?php echo $settings['posts_per_page']; ?>" min="1" max="100" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Comments Per Page</label>
                        <input type="number" name="comments_per_page" class="form-control" 
                               value="<?php echo $settings['comments_per_page']; ?>" min="1" max="100" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Max Upload Size (MB)</label>
                        <input type="number" name="max_upload_size" class="form-control" 
                               value="<?php echo $settings['max_upload_size']; ?>" min="1" max="100" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Allowed File Types</label>
                        <input type="text" name="allowed_file_types" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['allowed_file_types']); ?>" 
                               placeholder="jpg,jpeg,png,gif,pdf" required>
                        <div class="form-text">Comma-separated list of file extensions</div>
                    </div>
                </div>
            </div>
            
            <!-- Maintenance Settings -->
            <div class="mb-4">
                <h6 class="mb-3">Maintenance Settings</h6>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="maintenance_mode" class="form-check-input" 
                                   id="maintenance_mode" <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="maintenance_mode">
                                Enable Maintenance Mode
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Maintenance Message</label>
                        <textarea name="maintenance_message" class="form-control" rows="2"><?php 
                            echo htmlspecialchars($settings['maintenance_message']); 
                        ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict';
    
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php
// Get page footer
echo get_admin_footer();
?>