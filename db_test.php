<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

try {
    $conn = connect_db();
    echo "Database connection successful!";
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage();
}
?> 