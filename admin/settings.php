<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'controllers/settings_controller.php';

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
$settingsController = new SettingsController($pdo);

// Set page title
$page_title = 'Site Settings';

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $site_name = trim($_POST['site_name']);
    $site_description = trim($_POST['site_description']);
    $site_email = trim($_POST['site_email']);
    $posts_per_page = (int)$_POST['posts_per_page'];
    $comments_approval = isset($_POST['comments_approval']) ? 1 : 0;
    $user_registration = isset($_POST['user_registration']) ? 1 : 0;
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
    
    // Basic validation
    if (empty($site_name)) {
        $errors[] = 'Site name is required';
    }
    
    if (empty($site_email)) {
        $errors[] = 'Site email is required';
    } elseif (!filter_var($site_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if ($posts_per_page < 1) {
        $errors[] = 'Posts per page must be at least 1';
    }
    
    // If no errors, update settings
    if (empty($errors)) {
        $settings = [
            'site_name' => $site_name,
            'site_description' => $site_description,
            'site_email' => $site_email,
            'posts_per_page' => $posts_per_page,
            'comments_approval' => $comments_approval,
            'user_registration' => $user_registration,
            'maintenance_mode' => $maintenance_mode
        ];
        
        $result = $settingsController->updateSettings($settings);
        
        if ($result['success']) {
            $success = 'Settings updated successfully';
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Get current settings
$settings = $settingsController->getAllSettings();

// Include header
include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Site Settings</h1>
</div>

<?php if (!empty($success)): ?>
<div class="alert alert-success">
    <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h5>General Settings</h5>
                    <div class="mb-3">
                        <label for="site_name" class="form-label">Site Name</label>
                        <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? SITE_NAME); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="site_description" class="form-label">Site Description</label>
                        <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="site_email" class="form-label">Site Email</label>
                        <input type="email" class="form-control" id="site_email" name="site_email" value="<?php echo htmlspecialchars($settings['site_email'] ?? SMTP_FROM_EMAIL); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5>Content Settings</h5>
                    <div class="mb-3">
                        <label for="posts_per_page" class="form-label">Posts Per Page</label>
                        <input type="number" class="form-control" id="posts_per_page" name="posts_per_page" value="<?php echo (int)($settings['posts_per_page'] ?? 10); ?>" min="1" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="comments_approval" name="comments_approval" <?php echo (isset($settings['comments_approval']) && $settings['comments_approval']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="comments_approval">Require Comment Approval</label>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="user_registration" name="user_registration" <?php echo (isset($settings['user_registration']) && $settings['user_registration']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="user_registration">Allow User Registration</label>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="maintenance_mode" name="maintenance_mode" <?php echo (isset($settings['maintenance_mode']) && $settings['maintenance_mode']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
    </div>
</div>

<?php
// Include footer
include 'footer.php';
?>