<?php
// Include configuration first
require_once __DIR__ . '/includes/config.php';

// Basic PHP test
echo "<h1>Configuration Test</h1>";

// Test PHP version and server info
echo "<h2>Server Information</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

// Test configuration constants
echo "<h2>Configuration Constants</h2>";
echo "<pre>";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "SITE_NAME: " . SITE_NAME . "\n";
echo "SITE_URL: " . SITE_URL . "\n";
echo "SESSION_LIFETIME: " . SESSION_LIFETIME . "\n";
echo "</pre>";

// Test database connection
echo "<h2>Database Connection Test</h2>";
try {
    require_once __DIR__ . '/includes/database.php';
    $pdo = get_db_connection();
    echo "Database connection: Success<br>";
} catch (Exception $e) {
    echo "Database connection: Failed<br>";
    echo "Error: " . $e->getMessage() . "<br>";
}

// Test session
echo "<h2>Session Test</h2>";
$_SESSION['test'] = 'working';
echo "Session test: " . (isset($_SESSION['test']) ? "Success" : "Failed") . "<br>";

// Display PHP configuration
echo "<h2>PHP Configuration</h2>";
echo "<pre>";
echo "display_errors: " . ini_get('display_errors') . "\n";
echo "error_reporting: " . ini_get('error_reporting') . "\n";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "\n";
echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "\n";
echo "session.cookie_samesite: " . ini_get('session.cookie_samesite') . "\n";
echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "\n";
echo "</pre>";
?> 