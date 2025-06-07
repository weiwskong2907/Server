<?php
// Start session and include configuration
session_start();
require_once __DIR__ . '/includes/config.php';

// Function to log test results
function log_test($test_name, $status, $message = '') {
    $log_file = __DIR__ . '/logs/system_test.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $test_name: $status" . ($message ? " - $message" : "") . "\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
    return $status === 'PASS';
}

// Function to check file permissions
function check_file_permissions($path, $required_perms) {
    if (!file_exists($path)) {
        return false;
    }
    $perms = fileperms($path) & 0777;
    return ($perms & $required_perms) === $required_perms;
}

// Clear previous log
file_put_contents(__DIR__ . '/logs/system_test.log', '');

// Start HTML output
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>System Test Results</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin-bottom: 20px; }
        .test-item { margin: 10px 0; padding: 10px; border: 1px solid #ddd; }
        .pass { background-color: #dff0d8; }
        .fail { background-color: #f2dede; }
        .warning { background-color: #fcf8e3; }
        pre { background-color: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>System Test Results</h1>
    <p>Test run at: " . date('Y-m-d H:i:s') . "</p>";

// 1. PHP Environment Tests
echo "<div class='test-section'>
    <h2>1. PHP Environment</h2>";

$tests = [
    'PHP Version' => [
        'check' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'message' => 'PHP Version: ' . PHP_VERSION
    ],
    'Session Support' => [
        'check' => function_exists('session_start'),
        'message' => 'Session functions available'
    ],
    'PDO Extension' => [
        'check' => extension_loaded('pdo'),
        'message' => 'PDO extension loaded'
    ],
    'PDO MySQL' => [
        'check' => extension_loaded('pdo_mysql'),
        'message' => 'PDO MySQL driver loaded'
    ],
    'File Upload' => [
        'check' => ini_get('file_uploads'),
        'message' => 'File uploads enabled'
    ]
];

foreach ($tests as $name => $test) {
    $status = $test['check'] ? 'PASS' : 'FAIL';
    $class = $status === 'PASS' ? 'pass' : 'fail';
    echo "<div class='test-item $class'>
        <strong>$name:</strong> $status<br>
        <small>{$test['message']}</small>
    </div>";
    log_test($name, $status, $test['message']);
}

// 2. File System Tests
echo "</div><div class='test-section'>
    <h2>2. File System</h2>";

$dirs_to_check = [
    'Root Directory' => [__DIR__, 0755],
    'Includes Directory' => [__DIR__ . '/includes', 0755],
    'Logs Directory' => [__DIR__ . '/logs', 0775],
    'Uploads Directory' => [__DIR__ . '/uploads', 0775]
];

foreach ($dirs_to_check as $name => $info) {
    $exists = file_exists($info[0]);
    $perms_ok = $exists && check_file_permissions($info[0], $info[1]);
    $status = $exists && $perms_ok ? 'PASS' : 'FAIL';
    $class = $status === 'PASS' ? 'pass' : 'fail';
    $message = $exists ? 
        'Permissions: ' . substr(sprintf('%o', fileperms($info[0])), -4) :
        'Directory not found';
    
    echo "<div class='test-item $class'>
        <strong>$name:</strong> $status<br>
        <small>$message</small>
    </div>";
    log_test($name, $status, $message);
}

// 3. Database Tests
echo "</div><div class='test-section'>
    <h2>3. Database Connection</h2>";

try {
    require_once __DIR__ . '/includes/database.php';
    $pdo = get_db_connection();
    $status = 'PASS';
    $message = 'Successfully connected to database';
} catch (Exception $e) {
    $status = 'FAIL';
    $message = $e->getMessage();
}

$class = $status === 'PASS' ? 'pass' : 'fail';
echo "<div class='test-item $class'>
    <strong>Database Connection:</strong> $status<br>
    <small>$message</small>
</div>";
log_test('Database Connection', $status, $message);

// 4. Required Files Test
echo "</div><div class='test-section'>
    <h2>4. Required Files</h2>";

$required_files = [
    'config.php' => __DIR__ . '/includes/config.php',
    'database.php' => __DIR__ . '/includes/database.php',
    'auth.php' => __DIR__ . '/includes/auth.php',
    'functions.php' => __DIR__ . '/includes/functions.php',
    'layout.php' => __DIR__ . '/includes/layout.php'
];

foreach ($required_files as $name => $path) {
    $exists = file_exists($path);
    $readable = $exists && is_readable($path);
    $status = $exists && $readable ? 'PASS' : 'FAIL';
    $class = $status === 'PASS' ? 'pass' : 'fail';
    $message = $exists ? 
        ($readable ? 'File is readable' : 'File exists but not readable') :
        'File not found';
    
    echo "<div class='test-item $class'>
        <strong>$name:</strong> $status<br>
        <small>$message</small>
    </div>";
    log_test($name, $status, $message);
}

// 5. PHP Configuration
echo "</div><div class='test-section'>
    <h2>5. PHP Configuration</h2>
    <pre>";

$configs = [
    'display_errors' => ini_get('display_errors'),
    'error_reporting' => ini_get('error_reporting'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'session.gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
    'session.cookie_httponly' => ini_get('session.cookie_httponly'),
    'session.cookie_secure' => ini_get('session.cookie_secure'),
    'session.cookie_samesite' => ini_get('session.cookie_samesite')
];

foreach ($configs as $key => $value) {
    echo "$key: $value\n";
}

echo "</pre></div>";

// 6. Error Log Check
echo "<div class='test-section'>
    <h2>6. Error Log</h2>";

$error_log = __DIR__ . '/logs/error.log';
if (file_exists($error_log)) {
    $log_content = file_get_contents($error_log);
    if ($log_content) {
        echo "<div class='test-item warning'>
            <strong>Recent Errors:</strong><br>
            <pre>" . htmlspecialchars($log_content) . "</pre>
        </div>";
    } else {
        echo "<div class='test-item pass'>
            <strong>Error Log:</strong> No errors found
        </div>";
    }
} else {
    echo "<div class='test-item fail'>
        <strong>Error Log:</strong> Log file not found
    </div>";
}

// End HTML
echo "</div></body></html>";
?> 