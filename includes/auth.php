<?php
/**
 * Authentication helper functions
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user ID
 * @return int|null
 */
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 * @return string|null
 */
function get_current_user_role() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Check if user is admin
 * @return bool
 */
function is_admin() {
    return get_current_user_role() === 'admin';
}

/**
 * Register new user
 */
function register_user($username, $email, $password) {
    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'All fields are required'];
    }
    
    if (!validate_email($email)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    
    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters long'];
    }
    
    // Check if username or email already exists
    $existing_user = get_row(
        "SELECT id FROM users WHERE username = ? OR email = ?",
        "ss",
        [$username, $email]
    );
    
    if ($existing_user) {
        return ['success' => false, 'message' => 'Username or email already exists'];
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate verification token
    $verification_token = bin2hex(random_bytes(32));
    
    // Insert user
    $user_data = [
        'username' => $username,
        'email' => $email,
        'password' => $hashed_password,
        'verification_token' => $verification_token,
        'status' => 'pending'
    ];
    
    $user_id = insert_data('users', $user_data);
    
    if (!$user_id) {
        return ['success' => false, 'message' => 'Failed to create user'];
    }
    
    // Send verification email
    $verification_link = SITE_URL . '/verify.php?token=' . $verification_token;
    $email_sent = send_verification_email($email, $username, $verification_link);
    
    if (!$email_sent) {
        // Log error but don't fail registration
        error_log("Failed to send verification email to: $email");
    }
    
    return [
        'success' => true,
        'message' => 'Registration successful. Please check your email for verification.',
        'user_id' => $user_id
    ];
}

/**
 * Login user
 */
function login_user($email, $password, $remember = false) {
    global $conn;
    
    $email = mysqli_real_escape_string($conn, $email);
    $query = "SELECT id, username, password, role, status FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        if ($user['status'] !== 'active') {
            return false;
        }
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                $query = "INSERT INTO remember_tokens (user_id, token, expires_at) 
                         VALUES ({$user['id']}, '$token', '$expires')";
                mysqli_query($conn, $query);
                
                setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
            }
            
            log_activity($user['id'], 'login', 'User logged in');
            return true;
        }
    }
    
    return false;
}

/**
 * Logout user
 */
function logout_user() {
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        $query = "DELETE FROM remember_tokens WHERE token = '$token'";
        mysqli_query($conn, $query);
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
    
    session_destroy();
}

/**
 * Verify user email
 */
function verify_user($token) {
    $user = get_row(
        "SELECT id, username, email FROM users WHERE verification_token = ? AND status = 'pending'",
        "s",
        [$token]
    );
    
    if (!$user) {
        return ['success' => false, 'message' => 'Invalid or expired verification token'];
    }
    
    $updated = update_data(
        'users',
        ['status' => 'active', 'verification_token' => null],
        'id = ?',
        'i',
        [$user['id']]
    );
    
    if (!$updated) {
        return ['success' => false, 'message' => 'Failed to verify account'];
    }
    
    // Log activity
    log_activity($user['id'], 'verification', 'user', $user['id'], 'Email verified');
    
    return ['success' => true, 'message' => 'Email verified successfully'];
}

/**
 * Request password reset
 */
function request_password_reset($email) {
    $user = get_row(
        "SELECT id, username FROM users WHERE email = ? AND status = 'active'",
        "s",
        [$email]
    );
    
    if (!$user) {
        return ['success' => false, 'message' => 'No active account found with this email'];
    }
    
    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Store reset token
    $reset_data = [
        'user_id' => $user['id'],
        'token' => $token,
        'expires_at' => $expires
    ];
    
    $reset_id = insert_data('password_resets', $reset_data);
    
    if (!$reset_id) {
        return ['success' => false, 'message' => 'Failed to generate reset token'];
    }
    
    // Send reset email
    $reset_link = SITE_URL . '/reset_password.php?token=' . $token;
    $email_sent = send_password_reset_email($email, $user['username'], $reset_link);
    
    if (!$email_sent) {
        // Log error but don't fail request
        error_log("Failed to send password reset email to: $email");
    }
    
    return ['success' => true, 'message' => 'Password reset instructions sent to your email'];
}

/**
 * Reset password
 */
function reset_password($token, $new_password) {
    if (strlen($new_password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters long'];
    }
    
    // Get reset request
    $reset = get_row(
        "SELECT pr.*, u.id as user_id, u.username 
         FROM password_resets pr 
         JOIN users u ON pr.user_id = u.id 
         WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()",
        "s",
        [$token]
    );
    
    if (!$reset) {
        return ['success' => false, 'message' => 'Invalid or expired reset token'];
    }
    
    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password
    $updated = update_data(
        'users',
        ['password' => $hashed_password],
        'id = ?',
        'i',
        [$reset['user_id']]
    );
    
    if (!$updated) {
        return ['success' => false, 'message' => 'Failed to reset password'];
    }
    
    // Mark reset token as used
    update_data(
        'password_resets',
        ['used' => 1],
        'id = ?',
        'i',
        [$reset['id']]
    );
    
    // Log activity
    log_activity($reset['user_id'], 'password_reset', 'user', $reset['user_id'], 'Password reset');
    
    return ['success' => true, 'message' => 'Password reset successful'];
}

/**
 * Update user profile
 */
function update_profile($user_id, $data) {
    $allowed_fields = ['username', 'email', 'bio', 'avatar'];
    $update_data = array_intersect_key($data, array_flip($allowed_fields));
    
    if (empty($update_data)) {
        return ['success' => false, 'message' => 'No valid fields to update'];
    }
    
    // Check if username or email already exists
    if (isset($update_data['username']) || isset($update_data['email'])) {
        $existing_user = get_row(
            "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?",
            "ssi",
            [
                $update_data['username'] ?? '',
                $update_data['email'] ?? '',
                $user_id
            ]
        );
        
        if ($existing_user) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
    }
    
    // Update profile
    $updated = update_data(
        'users',
        $update_data,
        'id = ?',
        'i',
        [$user_id]
    );
    
    if (!$updated) {
        return ['success' => false, 'message' => 'Failed to update profile'];
    }
    
    // Log activity
    log_activity($user_id, 'profile_update', 'user', $user_id, 'Profile updated');
    
    return ['success' => true, 'message' => 'Profile updated successfully'];
}

/**
 * Change password
 */
function change_password($user_id, $current_password, $new_password) {
    if (strlen($new_password) < 8) {
        return ['success' => false, 'message' => 'New password must be at least 8 characters long'];
    }
    
    // Get user
    $user = get_row(
        "SELECT password FROM users WHERE id = ?",
        "i",
        [$user_id]
    );
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    if (!password_verify($current_password, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }
    
    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password
    $updated = update_data(
        'users',
        ['password' => $hashed_password],
        'id = ?',
        'i',
        [$user_id]
    );
    
    if (!$updated) {
        return ['success' => false, 'message' => 'Failed to change password'];
    }
    
    // Log activity
    log_activity($user_id, 'password_change', 'user', $user_id, 'Password changed');
    
    return ['success' => true, 'message' => 'Password changed successfully'];
}

/**
 * Check if email exists
 * @param string $email
 * @return bool
 */
function email_exists($email) {
    global $conn;
    
    $email = mysqli_real_escape_string($conn, $email);
    $query = "SELECT id FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    
    return mysqli_num_rows($result) > 0;
}

/**
 * Check if username exists
 * @param string $username
 * @return bool
 */
function username_exists($username) {
    global $conn;
    
    $username = mysqli_real_escape_string($conn, $username);
    $query = "SELECT id FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);
    
    return mysqli_num_rows($result) > 0;
}

/**
 * Log user activity
 * @param int $user_id
 * @param string $action
 * @param string $description
 */
function log_activity($user_id, $action, $description) {
    global $conn;
    
    $user_id = (int)$user_id;
    $action = mysqli_real_escape_string($conn, $action);
    $description = mysqli_real_escape_string($conn, $description);
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $query = "INSERT INTO activity_logs (user_id, action, description, ip_address) 
              VALUES ($user_id, '$action', '$description', '$ip')";
    mysqli_query($conn, $query);
} 