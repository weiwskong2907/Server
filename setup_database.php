<?php
// Define secure access constant
define('SECURE_ACCESS', true);

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include config file
require_once __DIR__ . '/includes/config.php';

echo "<h2>Database Setup</h2>";
echo "<pre>";

// Diagnostic information
echo "PHP Version: " . PHP_VERSION . "\n";
echo "PDO Drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n";
echo "MySQL Socket: " . (file_exists('/var/run/mysqld/mysqld.sock') ? 'Found' : 'Not found') . "\n";
echo "MySQL Port 3306: " . (fsockopen('127.0.0.1', 3306) ? 'Open' : 'Closed') . "\n\n";

// Try different connection methods
$connection_methods = [
    ['host' => 'localhost', 'desc' => 'Localhost'],
    ['host' => '127.0.0.1', 'desc' => 'TCP/IP'],
    ['host' => 'localhost:/var/run/mysqld/mysqld.sock', 'desc' => 'Unix Socket'],
    ['host' => 'localhost:/tmp/mysql.sock', 'desc' => 'Alternative Socket']
];

$connected = false;
foreach ($connection_methods as $method) {
    echo "Trying {$method['desc']} connection...\n";
    try {
        $dsn = "mysql:host={$method['host']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        echo "Successfully connected using {$method['desc']}\n";
        $connected = true;
        break;
    } catch (PDOException $e) {
        echo "Failed: {$e->getMessage()}\n";
    }
}

if (!$connected) {
    echo "\nAll connection attempts failed. Please check:\n";
    echo "1. MySQL service is running\n";
    echo "2. MySQL credentials are correct\n";
    echo "3. MySQL is configured to accept connections\n";
    echo "4. Firewall settings allow MySQL connections\n";
    die();
}

// Create database if not exists
$pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "Database '" . DB_NAME . "' created or already exists\n";

// Select the database
$pdo->exec("USE " . DB_NAME);
echo "Using database '" . DB_NAME . "'\n";

// Create tables
$tables = [
    // Users table
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('user', 'admin') DEFAULT 'user',
        status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Categories table
    "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        slug VARCHAR(50) NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Posts table
    "CREATE TABLE IF NOT EXISTS posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        category_id INT,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        content TEXT NOT NULL,
        excerpt TEXT,
        featured_image VARCHAR(255),
        status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
        views INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Tags table
    "CREATE TABLE IF NOT EXISTS tags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        slug VARCHAR(50) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Post tags table
    "CREATE TABLE IF NOT EXISTS post_tags (
        post_id INT NOT NULL,
        tag_id INT NOT NULL,
        PRIMARY KEY (post_id, tag_id),
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Comments table
    "CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT,
        parent_id INT,
        content TEXT NOT NULL,
        status ENUM('pending', 'approved', 'spam') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Activity log table
    "CREATE TABLE IF NOT EXISTS activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(50) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

// Execute each table creation query
foreach ($tables as $sql) {
    $pdo->exec($sql);
    echo "Table created successfully\n";
}

// Check if users table exists and has admin user
$stmt = $pdo->query("SHOW TABLES LIKE 'users'");
if ($stmt->rowCount() > 0) {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['admin', 'admin@example.com', $password]);
        echo "Default admin user created\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
        echo "Please change these credentials immediately!\n";
    } else {
        echo "Admin user already exists\n";
    }
}

echo "\nDatabase setup completed successfully!\n";

echo "</pre>"; 