<?php
/**
 * Database helper functions
 */

$conn = null;

/**
 * Connect to database
 */
function connect_db() {
    global $conn;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            die("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
    }
    
    return $conn;
}

/**
 * Execute query with parameters
 */
function execute_query($query, $types = "", $params = []) {
    global $conn;
    
    if ($conn === null) {
        connect_db();
    }
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Query preparation failed: " . $conn->error);
        return false;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("Query execution failed: " . $stmt->error);
        return false;
    }
    
    return $stmt;
}

/**
 * Get single row
 */
function get_row($query, $types = "", $params = []) {
    $stmt = execute_query($query, $types, $params);
    
    if (!$stmt) {
        return false;
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    return $row;
}

/**
 * Get multiple rows
 */
function get_rows($query, $types = "", $params = []) {
    $stmt = execute_query($query, $types, $params);
    
    if (!$stmt) {
        return false;
    }
    
    $result = $stmt->get_result();
    $rows = [];
    
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    
    $stmt->close();
    return $rows;
}

/**
 * Insert data
 */
function insert_data($table, $data) {
    global $conn;
    
    if ($conn === null) {
        connect_db();
    }
    
    $columns = implode(", ", array_keys($data));
    $values = implode(", ", array_fill(0, count($data), "?"));
    $types = str_repeat("s", count($data));
    
    $query = "INSERT INTO $table ($columns) VALUES ($values)";
    $stmt = execute_query($query, $types, array_values($data));
    
    if (!$stmt) {
        return false;
    }
    
    $insert_id = $conn->insert_id;
    $stmt->close();
    
    return $insert_id;
}

/**
 * Update data
 */
function update_data($table, $data, $where, $where_types = "", $where_params = []) {
    global $conn;
    
    if ($conn === null) {
        connect_db();
    }
    
    $set = implode(" = ?, ", array_keys($data)) . " = ?";
    $types = str_repeat("s", count($data)) . $where_types;
    $params = array_merge(array_values($data), $where_params);
    
    $query = "UPDATE $table SET $set WHERE $where";
    $stmt = execute_query($query, $types, $params);
    
    if (!$stmt) {
        return false;
    }
    
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    
    return $affected_rows;
}

/**
 * Delete data
 */
function delete_data($table, $where, $types = "", $params = []) {
    global $conn;
    
    if ($conn === null) {
        connect_db();
    }
    
    $query = "DELETE FROM $table WHERE $where";
    $stmt = execute_query($query, $types, $params);
    
    if (!$stmt) {
        return false;
    }
    
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    
    return $affected_rows;
}

/**
 * Begin transaction
 */
function begin_transaction() {
    global $conn;
    
    if ($conn === null) {
        connect_db();
    }
    
    return $conn->begin_transaction();
}

/**
 * Commit transaction
 */
function commit_transaction() {
    global $conn;
    
    if ($conn === null) {
        connect_db();
    }
    
    return $conn->commit();
}

/**
 * Rollback transaction
 */
function rollback_transaction() {
    global $conn;
    
    if ($conn === null) {
        connect_db();
    }
    
    return $conn->rollback();
}

/**
 * Close database connection
 */
function close_db() {
    global $conn;
    
    if ($conn !== null) {
        $conn->close();
        $conn = null;
    }
} 