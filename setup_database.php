<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

try {
    // Connect to database
    $conn = connect_db();
    
    // Read and execute SQL file
    $sql = file_get_contents('database.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            if (!$conn->query($statement)) {
                throw new Exception("Error executing statement: " . $conn->error);
            }
        }
    }
    
    echo "Database setup completed successfully!";
} catch (Exception $e) {
    echo "Error setting up database: " . $e->getMessage();
}
?> 