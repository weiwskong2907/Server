<?php
// Define secure access constant
define('SECURE_ACCESS', true);

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include config file
require_once __DIR__ . '/includes/config.php';

// Test database connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    echo "<h2>Database Connection Test</h2>";
    echo "<pre>";
    echo "DSN: " . $dsn . "\n";
    echo "Username: " . DB_USER . "\n";
    echo "Password: " . (DB_PASS ? '****' : 'empty') . "\n";
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "\nConnection successful!\n";
    echo "Server version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
    echo "Client version: " . $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION) . "\n";
    
    // Test database operations
    echo "\nTesting database operations:\n";
    
    // Test SELECT
    $stmt = $pdo->query("SELECT 1");
    echo "SELECT test: " . ($stmt->fetchColumn() ? "OK" : "Failed") . "\n";
    
    // Test database privileges
    $stmt = $pdo->query("SHOW GRANTS");
    echo "\nDatabase privileges:\n";
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        if (isset($row[0])) {
            echo $row[0] . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "\nConnection failed!\n";
    echo "Error code: " . $e->getCode() . "\n";
    echo "Error message: " . $e->getMessage() . "\n";
    
    // Additional diagnostic information
    echo "\nDiagnostic Information:\n";
    echo "PHP PDO drivers: " . implode(", ", PDO::getAvailableDrivers()) . "\n";
    echo "MySQL socket: " . (file_exists('/var/run/mysqld/mysqld.sock') ? "Exists" : "Not found") . "\n";
    echo "MySQL port 3306: " . (@fsockopen('localhost', 3306) ? "Open" : "Closed") . "\n";
    
    // Try alternative connection methods
    echo "\nTrying alternative connection methods:\n";
    
    // Try TCP/IP connection
    try {
        $dsn = "mysql:host=127.0.0.1;port=3306;dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        echo "TCP/IP connection successful!\n";
    } catch (PDOException $e) {
        echo "TCP/IP connection failed: " . $e->getMessage() . "\n";
    }
    
    // Try Unix socket connection
    try {
        $dsn = "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        echo "Unix socket connection successful!\n";
    } catch (PDOException $e) {
        echo "Unix socket connection failed: " . $e->getMessage() . "\n";
    }
    
    // Check MySQL service status
    echo "\nMySQL Service Status:\n";
    $output = [];
    exec("systemctl status mysql 2>&1", $output);
    echo implode("\n", $output) . "\n";
    
    // Check MySQL process
    echo "\nMySQL Process:\n";
    $output = [];
    exec("ps aux | grep mysql 2>&1", $output);
    echo implode("\n", $output) . "\n";
}
echo "</pre>"; 