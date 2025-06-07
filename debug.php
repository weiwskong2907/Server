<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to check if a file exists and is readable
function check_file($file) {
    if (file_exists($file)) {
        return is_readable($file) ? "Exists and readable" : "Exists but not readable";
    }
    return "Does not exist";
}

// Function to check directory permissions
function check_dir($dir) {
    if (is_dir($dir)) {
        return is_writable($dir) ? "Exists and writable" : "Exists but not writable";
    }
    return "Does not exist";
}

// Check required files
$required_files = [
    'includes/config.php',
    'includes/database.php',
    'includes/layout.php',
    'includes/auth.php',
    'includes/functions.php'
];

// Check required directories
$required_dirs = [
    'logs',
    'uploads',
    'uploads/avatars',
    'uploads/posts'
];

// Check PHP configuration
$php_config = [
    'PHP Version' => PHP_VERSION,
    'display_errors' => ini_get('display_errors'),
    'error_reporting' => ini_get('error_reporting'),
    'log_errors' => ini_get('log_errors'),
    'error_log' => ini_get('error_log'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size')
];

// Check loaded extensions
$required_extensions = [
    'mysqli',
    'json',
    'session',
    'mbstring',
    'gd',
    'fileinfo'
];

// Try to connect to database
$db_connection = false;
$db_error = '';
try {
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    $db_connection = true;
} catch (Exception $e) {
    $db_error = $e->getMessage();
}

// Output results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Information</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="mb-4">Debug Information</h1>

        <div class="card mb-4">
            <div class="card-header">
                <h2 class="h5 mb-0">PHP Configuration</h2>
            </div>
            <div class="card-body">
                <table class="table">
                    <?php foreach ($php_config as $key => $value): ?>
                    <tr>
                        <th><?php echo htmlspecialchars($key); ?></th>
                        <td><?php echo htmlspecialchars($value); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h2 class="h5 mb-0">Required Files</h2>
            </div>
            <div class="card-body">
                <table class="table">
                    <?php foreach ($required_files as $file): ?>
                    <tr>
                        <th><?php echo htmlspecialchars($file); ?></th>
                        <td><?php echo check_file($file); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h2 class="h5 mb-0">Required Directories</h2>
            </div>
            <div class="card-body">
                <table class="table">
                    <?php foreach ($required_dirs as $dir): ?>
                    <tr>
                        <th><?php echo htmlspecialchars($dir); ?></th>
                        <td><?php echo check_dir($dir); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h2 class="h5 mb-0">PHP Extensions</h2>
            </div>
            <div class="card-body">
                <table class="table">
                    <?php foreach ($required_extensions as $ext): ?>
                    <tr>
                        <th><?php echo htmlspecialchars($ext); ?></th>
                        <td><?php echo extension_loaded($ext) ? "Loaded" : "Not loaded"; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h2 class="h5 mb-0">Database Connection</h2>
            </div>
            <div class="card-body">
                <?php if ($db_connection): ?>
                    <div class="alert alert-success">Database connection successful</div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        Database connection failed: <?php echo htmlspecialchars($db_error); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (file_exists('logs/error.log')): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="h5 mb-0">Recent Error Log</h2>
            </div>
            <div class="card-body">
                <pre class="bg-dark text-light p-3 rounded"><?php
                    $log = file_get_contents('logs/error.log');
                    echo htmlspecialchars(substr($log, -5000)); // Show last 5000 characters
                ?></pre>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html> 