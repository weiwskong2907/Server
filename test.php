<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test basic PHP functionality
echo "<h1>PHP Test</h1>";

// Test PHP version
echo "<h2>PHP Version</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";

// Test file operations
echo "<h2>File Operations</h2>";
echo "Current directory: " . __DIR__ . "<br>";
echo "File exists: " . (file_exists(__FILE__) ? "Yes" : "No") . "<br>";
echo "File readable: " . (is_readable(__FILE__) ? "Yes" : "No") . "<br>";
echo "File permissions: " . substr(sprintf('%o', fileperms(__FILE__)), -4) . "<br>";

// Test directory operations
echo "<h2>Directory Operations</h2>";
echo "Directory exists: " . (is_dir(__DIR__) ? "Yes" : "No") . "<br>";
echo "Directory readable: " . (is_readable(__DIR__) ? "Yes" : "No") . "<br>";
echo "Directory permissions: " . substr(sprintf('%o', fileperms(__DIR__)), -4) . "<br>";

// Test session
echo "<h2>Session Test</h2>";
session_start();
echo "Session started: " . (session_status() === PHP_SESSION_ACTIVE ? "Yes" : "No") . "<br>";

// Test database connection
echo "<h2>Database Connection Test</h2>";
try {
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    echo "Database connection successful<br>";
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "<br>";
}

// Test function availability
echo "<h2>Function Availability</h2>";
$functions = [
    'is_logged_in',
    'get_posts_paginated',
    'get_categories',
    'get_popular_tags'
];

foreach ($functions as $function) {
    echo "$function(): " . (function_exists($function) ? "Exists" : "Not found") . "<br>";
}

// Test file includes
echo "<h2>File Includes Test</h2>";
$files = [
    'includes/config.php',
    'includes/database.php',
    'includes/auth.php',
    'includes/functions.php',
    'includes/layout.php'
];

foreach ($files as $file) {
    echo "$file: " . (file_exists($file) ? "Exists" : "Not found") . "<br>";
    if (file_exists($file)) {
        echo "Permissions: " . substr(sprintf('%o', fileperms($file)), -4) . "<br>";
    }
}
?> 