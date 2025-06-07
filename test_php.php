<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Basic PHP test
echo "<h1>PHP Test Page</h1>";

// Test PHP version
echo "<h2>PHP Information</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

// Test file permissions
echo "<h2>File Permissions Test</h2>";
$test_file = __DIR__ . '/test.txt';
if (file_put_contents($test_file, 'Test content')) {
    echo "File write test: Success<br>";
    unlink($test_file);
} else {
    echo "File write test: Failed<br>";
}

// Test includes directory
echo "<h2>Includes Directory Test</h2>";
$includes_dir = __DIR__ . '/includes';
if (is_dir($includes_dir)) {
    echo "Includes directory exists<br>";
    echo "Files in includes directory:<br>";
    echo "<pre>";
    print_r(scandir($includes_dir));
    echo "</pre>";
} else {
    echo "Includes directory not found!<br>";
}

// Test database connection
echo "<h2>Database Connection Test</h2>";
try {
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/includes/database.php';
    $pdo = get_db_connection();
    echo "Database connection: Success<br>";
} catch (Exception $e) {
    echo "Database connection: Failed<br>";
    echo "Error: " . $e->getMessage() . "<br>";
}

// Display PHP configuration
echo "<h2>PHP Configuration</h2>";
echo "<pre>";
echo "display_errors: " . ini_get('display_errors') . "\n";
echo "error_reporting: " . ini_get('error_reporting') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "</pre>";

// Test session
echo "<h2>Session Test</h2>";
session_start();
$_SESSION['test'] = 'working';
echo "Session test: " . (isset($_SESSION['test']) ? "Success" : "Failed") . "<br>";
?> 