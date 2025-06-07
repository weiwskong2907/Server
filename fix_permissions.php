<?php
// Define secure access constant
define('SECURE_ACCESS', true);

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Permission Fix Script</h2>";
echo "<pre>";

// Function to safely change permissions
function safe_chmod($path, $mode) {
    if (file_exists($path)) {
        if (chmod($path, $mode)) {
            echo "Changed permissions of $path to " . substr(sprintf('%o', $mode), -4) . "\n";
            return true;
        } else {
            echo "Failed to change permissions of $path\n";
            return false;
        }
    } else {
        echo "Path $path does not exist\n";
        return false;
    }
}

// Function to safely change ownership
function safe_chown($path, $user, $group) {
    if (file_exists($path)) {
        if (chown($path, $user) && chgrp($path, $group)) {
            echo "Changed ownership of $path to $user:$group\n";
            return true;
        } else {
            echo "Failed to change ownership of $path\n";
            return false;
        }
    } else {
        echo "Path $path does not exist\n";
        return false;
    }
}

// Directories to fix
$directories = [
    __DIR__ . '/includes' => 0775,
    __DIR__ . '/uploads' => 0775,
    __DIR__ . '/logs' => 0775
];

// Fix permissions
foreach ($directories as $dir => $perms) {
    safe_chmod($dir, $perms);
    safe_chown($dir, 'www-data', 'www-data');
}

// Create .env file if it doesn't exist
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    $envContent = <<<EOT
DB_HOST=localhost
DB_NAME=blog
DB_USER=root
DB_PASS=
SITE_NAME=My Blog
SITE_URL=http://localhost
ADMIN_EMAIL=admin@example.com
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=
SMTP_PASS=
SMTP_FROM=noreply@example.com
SMTP_FROM_NAME=My Blog
EOT;
    if (file_put_contents($envFile, $envContent)) {
        echo "Created .env file with default settings\n";
        safe_chmod($envFile, 0644);
        safe_chown($envFile, 'www-data', 'www-data');
    } else {
        echo "Failed to create .env file\n";
    }
}

// Set session save path
$sessionPath = __DIR__ . '/sessions';
if (!file_exists($sessionPath)) {
    if (mkdir($sessionPath, 0775, true)) {
        echo "Created sessions directory\n";
        safe_chown($sessionPath, 'www-data', 'www-data');
    } else {
        echo "Failed to create sessions directory\n";
    }
}

// Update PHP configuration
$phpIni = [
    'session.save_path' => $sessionPath,
    'session.gc_maxlifetime' => 3600,
    'session.cookie_httponly' => 1,
    'session.cookie_secure' => 1,
    'session.cookie_samesite' => 'Strict'
];

foreach ($phpIni as $key => $value) {
    if (ini_set($key, $value) !== false) {
        echo "Set $key to $value\n";
    } else {
        echo "Failed to set $key\n";
    }
}

echo "\nDone!\n";
echo "</pre>"; 