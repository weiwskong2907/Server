<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Basic test
echo "PHP is working!<br>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Current directory: " . __DIR__ . "<br>";

// Test file operations
$test_file = __DIR__ . '/test.txt';
file_put_contents($test_file, 'Test content');
echo "File write test: " . (file_exists($test_file) ? "Success" : "Failed") . "<br>";
unlink($test_file);

// Test directory
$test_dir = __DIR__ . '/test_dir';
mkdir($test_dir);
echo "Directory creation test: " . (is_dir($test_dir) ? "Success" : "Failed") . "<br>";
rmdir($test_dir);

// Test session
session_start();
$_SESSION['test'] = 'working';
echo "Session test: " . (isset($_SESSION['test']) ? "Success" : "Failed") . "<br>";

// List files in current directory
echo "<h3>Files in current directory:</h3>";
echo "<pre>";
print_r(scandir(__DIR__));
echo "</pre>";

// List files in includes directory
echo "<h3>Files in includes directory:</h3>";
if (is_dir(__DIR__ . '/includes')) {
    echo "<pre>";
    print_r(scandir(__DIR__ . '/includes'));
    echo "</pre>";
} else {
    echo "Includes directory not found!<br>";
}
?> 