<?php
// File upload handling functions
function get_allowed_file_types() {
    return [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
    ];
}

function validate_file_upload($file) {
    $allowed_types = get_allowed_file_types();
    $max_size = 5 * 1024 * 1024; // 5MB
    $errors = [];
    
    if ($file['size'] > $max_size) {
        $errors[] = "File size must be less than 5MB";
    }
    
    if (!isset($allowed_types[$file['type']])) {
        $errors[] = "File type not allowed. Allowed types: JPG, PNG, GIF, PDF, DOC, DOCX";
    }
    
    return $errors;
}

function save_uploaded_file($file) {
    $allowed_types = get_allowed_file_types();
    $extension = $allowed_types[$file['type']];
    $filename = uniqid() . '.' . $extension;
    $upload_path = __DIR__ . '/../uploads/' . $filename;
    error_log("Attempting to save file to: " . $upload_path);
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return $filename;
    }
    
    return false;
}

// Add this function to your existing functions.php
function log_activity($action_type, $entity_type, $entity_id, $description = '') {
    global $pdo;
    
    $stmt = $pdo->prepare(
        "INSERT INTO activity_logs (user_id, action_type, entity_type, entity_id, description)
         VALUES (?, ?, ?, ?, ?)"
    );
    
    return $stmt->execute([
        isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
        $action_type,
        $entity_type,
        $entity_id,
        $description
    ]);
}

function is_admin($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return (bool)$stmt->fetchColumn();
}
?>