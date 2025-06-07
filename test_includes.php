<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Testing File Includes</h1>";

// Check current directory
echo "<h2>Current Directory</h2>";
echo "Current directory: " . __DIR__ . "<br>";

// List files in current directory
echo "<h2>Files in Current Directory</h2>";
echo "<pre>";
print_r(scandir(__DIR__));
echo "</pre>";

// List files in includes directory
echo "<h2>Files in Includes Directory</h2>";
if (is_dir(__DIR__ . '/includes')) {
    echo "<pre>";
    print_r(scandir(__DIR__ . '/includes'));
    echo "</pre>";
} else {
    echo "Includes directory not found!<br>";
}

// Try to include files
echo "<h2>Testing File Includes</h2>";

$files_to_test = [
    'includes/config.php',
    'includes/database.php',
    'includes/auth.php',
    'includes/functions.php',
    'includes/layout.php'
];

foreach ($files_to_test as $file) {
    echo "Testing $file: ";
    if (file_exists($file)) {
        echo "File exists<br>";
        try {
            require_once $file;
            echo "Successfully included<br>";
        } catch (Exception $e) {
            echo "Error including file: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "File not found!<br>";
    }
}

// Test database connection
echo "<h2>Testing Database Connection</h2>";
try {
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    echo "Database connection successful<br>";
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "<br>";
}

// Test function availability
echo "<h2>Testing Function Availability</h2>";
$functions_to_test = [
    'is_logged_in',
    'get_posts_paginated',
    'get_categories',
    'get_popular_tags'
];

foreach ($functions_to_test as $function) {
    echo "Testing $function(): ";
    if (function_exists($function)) {
        echo "Function exists<br>";
    } else {
        echo "Function not found!<br>";
    }
} 