<?php
// Start session first
session_start();

// Load environment variables from .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'mysql-db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: 'myrootpass');
define('DB_NAME', getenv('DB_NAME') ?: 'family_forum');

// Site settings
define('SITE_NAME', getenv('SITE_NAME') ?: 'Family & Friends Forum');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/Server');

// SMTP settings
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_USER', getenv('SMTP_USER') ?: 'unknowsuser050@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'blts pqzz hpgc hpxt');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'unknowsuser050@gmail.com');

// Security settings
define('CSRF_TOKEN_SECRET', getenv('CSRF_TOKEN_SECRET') ?: bin2hex(random_bytes(32)));
define('SESSION_LIFETIME', getenv('SESSION_LIFETIME') ?: 3600);
define('MAX_LOGIN_ATTEMPTS', getenv('MAX_LOGIN_ATTEMPTS') ?: 5);
define('LOGIN_TIMEOUT', getenv('LOGIN_TIMEOUT') ?: 900); // 15 minutes

// File upload settings
define('MAX_FILE_SIZE', getenv('MAX_FILE_SIZE') ?: 5242880); // 5MB
define('ALLOWED_FILE_TYPES', getenv('ALLOWED_FILE_TYPES') ?: 'jpg,jpeg,png,gif,pdf,doc,docx');

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0777, true);
}
?>