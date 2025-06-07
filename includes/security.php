<?php
/**
 * Security helper functions
 */

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require login
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit();
    }
}

/**
 * Require admin
 */
function require_admin() {
    if (!is_admin()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit();
    }
}

/**
 * Rate limiting
 */
function check_rate_limit($key, $max_attempts = MAX_LOGIN_ATTEMPTS, $timeout = LOGIN_TIMEOUT) {
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = [
            'attempts' => 0,
            'last_attempt' => time()
        ];
    }

    $rate_limit = &$_SESSION['rate_limit'][$key];

    if (time() - $rate_limit['last_attempt'] > $timeout) {
        $rate_limit['attempts'] = 0;
        $rate_limit['last_attempt'] = time();
    }

    $rate_limit['attempts']++;
    $rate_limit['last_attempt'] = time();

    return $rate_limit['attempts'] <= $max_attempts;
}

/**
 * Secure file upload
 */
function secure_file_upload($file, $allowed_types = ALLOWED_FILE_TYPES, $max_size = MAX_FILE_SIZE) {
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload failed';
        return ['success' => false, 'errors' => $errors];
    }

    if ($file['size'] > $max_size) {
        $errors[] = 'File size exceeds limit';
        return ['success' => false, 'errors' => $errors];
    }

    $file_type = mime_content_type($file['tmp_name']);
    $allowed_types = explode(',', $allowed_types);
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_extension, $allowed_types)) {
        $errors[] = 'File type not allowed';
        return ['success' => false, 'errors' => $errors];
    }

    $new_filename = bin2hex(random_bytes(16)) . '.' . $file_extension;
    $upload_path = __DIR__ . '/../uploads/' . $new_filename;

    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        $errors[] = 'Failed to move uploaded file';
        return ['success' => false, 'errors' => $errors];
    }

    return [
        'success' => true,
        'filename' => $new_filename,
        'original_filename' => $file['name'],
        'file_type' => $file_type
    ];
}

/**
 * Log activity
 */
function log_activity($user_id, $action_type, $entity_type, $entity_id, $description = null) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action_type, entity_type, entity_id, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issis", $user_id, $action_type, $entity_type, $entity_id, $description);
    return $stmt->execute();
} 