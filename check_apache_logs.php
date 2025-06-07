<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to safely read file
function safe_read_file($file) {
    if (file_exists($file) && is_readable($file)) {
        return file_get_contents($file);
    }
    return "File not found or not readable: $file";
}

// Function to check file permissions
function check_permissions($file) {
    if (!file_exists($file)) {
        return "File does not exist";
    }
    
    $perms = fileperms($file);
    $perms = substr(sprintf('%o', $perms), -4);
    
    $owner = posix_getpwuid(fileowner($file));
    $group = posix_getgrgid(filegroup($file));
    
    return [
        'permissions' => $perms,
        'owner' => $owner['name'] ?? 'unknown',
        'group' => $group['name'] ?? 'unknown'
    ];
}

// Check various log files
$log_files = [
    '/var/log/apache2/error.log',
    '/var/log/httpd/error.log',
    '/var/log/apache2/error.log.1',
    '/var/log/httpd/error.log.1',
    __DIR__ . '/logs/error.log',
    __DIR__ . '/error.log'
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

// Check file permissions
$files_to_check = [
    __DIR__,
    __DIR__ . '/includes',
    __DIR__ . '/includes/config.php',
    __DIR__ . '/includes/database.php',
    __DIR__ . '/includes/auth.php',
    __DIR__ . '/includes/functions.php',
    __DIR__ . '/includes/layout.php'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apache Logs Checker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="mb-4">Apache Logs Checker</h1>
        
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
                <h2 class="h5 mb-0">File Permissions</h2>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Permissions</th>
                            <th>Owner</th>
                            <th>Group</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files_to_check as $file): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($file); ?></td>
                            <?php
                            $perms = check_permissions($file);
                            if (is_array($perms)):
                            ?>
                            <td><?php echo $perms['permissions']; ?></td>
                            <td><?php echo $perms['owner']; ?></td>
                            <td><?php echo $perms['group']; ?></td>
                            <?php else: ?>
                            <td colspan="3"><?php echo $perms; ?></td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="h5 mb-0">Apache Error Logs</h2>
            </div>
            <div class="card-body">
                <?php foreach ($log_files as $log_file): ?>
                <h3 class="h6"><?php echo htmlspecialchars($log_file); ?></h3>
                <pre class="bg-dark text-light p-3 rounded"><?php
                    $log = safe_read_file($log_file);
                    echo htmlspecialchars(substr($log, -5000)); // Show last 5000 characters
                ?></pre>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="h5 mb-0">Server Information</h2>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th>Server Software</th>
                        <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                    </tr>
                    <tr>
                        <th>Server Name</th>
                        <td><?php echo $_SERVER['SERVER_NAME']; ?></td>
                    </tr>
                    <tr>
                        <th>Server Protocol</th>
                        <td><?php echo $_SERVER['SERVER_PROTOCOL']; ?></td>
                    </tr>
                    <tr>
                        <th>Document Root</th>
                        <td><?php echo $_SERVER['DOCUMENT_ROOT']; ?></td>
                    </tr>
                    <tr>
                        <th>Current Directory</th>
                        <td><?php echo __DIR__; ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>
</html> 