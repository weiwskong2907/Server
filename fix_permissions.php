<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Fixing File Permissions</h2>";

// Function to safely change permissions
function change_permissions($path, $permissions) {
    if (file_exists($path)) {
        if (chmod($path, $permissions)) {
            echo "Successfully changed permissions for: $path<br>";
        } else {
            echo "Failed to change permissions for: $path<br>";
        }
    } else {
        echo "File/directory not found: $path<br>";
    }
}

// Function to safely change ownership
function change_ownership($path, $user, $group) {
    if (file_exists($path)) {
        if (chown($path, $user) && chgrp($path, $group)) {
            echo "Successfully changed ownership for: $path to $user:$group<br>";
        } else {
            echo "Failed to change ownership for: $path<br>";
        }
    } else {
        echo "File/directory not found: $path<br>";
    }
}

// Fix includes directory and files
$includes_dir = __DIR__ . '/includes';
change_permissions($includes_dir, 0755);
change_ownership($includes_dir, 'www-data', 'www-data');

// Fix logs directory
$logs_dir = __DIR__ . '/logs';
change_permissions($logs_dir, 0775);
change_ownership($logs_dir, 'www-data', 'www-data');

// Fix uploads directory
$uploads_dir = __DIR__ . '/uploads';
change_permissions($uploads_dir, 0775);
change_ownership($uploads_dir, 'www-data', 'www-data');

// Fix PHP files in root directory
$php_files = glob(__DIR__ . '/*.php');
foreach ($php_files as $file) {
    change_permissions($file, 0644);
    change_ownership($file, 'www-data', 'www-data');
}

// Fix includes files
$includes_files = glob($includes_dir . '/*.php');
foreach ($includes_files as $file) {
    change_permissions($file, 0644);
    change_ownership($file, 'www-data', 'www-data');
}

echo "<h3>Current Permissions:</h3>";
echo "<pre>";
system('ls -la ' . escapeshellarg(__DIR__));
echo "</pre>";

echo "<h3>Includes Directory:</h3>";
echo "<pre>";
system('ls -la ' . escapeshellarg($includes_dir));
echo "</pre>";

echo "<h3>Logs Directory:</h3>";
echo "<pre>";
system('ls -la ' . escapeshellarg($logs_dir));
echo "</pre>";

echo "<h3>Uploads Directory:</h3>";
echo "<pre>";
system('ls -la ' . escapeshellarg($uploads_dir));
echo "</pre>";
?> 