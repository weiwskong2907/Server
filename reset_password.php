<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$error = '';
$success = '';
$valid_token = false;
$user_id = null;

// Check if token is provided and valid
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    // Check if token exists and is not expired
    $stmt = $pdo->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ? AND used = 0");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();
    
    if ($reset && strtotime($reset['expires_at']) > time()) {
        $valid_token = true;
        $user_id = $reset['user_id'];
    } else {
        $error = "Invalid or expired token. Please request a new password reset link.";
    }
} else {
    $error = "Invalid request. Please request a new password reset link.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password)) {
        $error = "Password is required";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Update password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        
        if ($stmt->execute([$hashed_password, $user_id])) {
            // Mark token as used
            $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);
            
            $success = "Your password has been reset successfully. You can now login with your new password.";
        } else {
            $error = "Failed to update password. Please try again.";
        }
    }
}

include 'layouts/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Reset Password</div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <div class="text-center mt-3">
                            <a href="login.php" class="btn btn-primary">Go to Login</a>
                        </div>
                    <?php elseif ($valid_token): ?>
                        <form method="POST">
                            <div class="form-group mb-3">
                                <label>New Password</label>
                                <input type="password" name="password" class="form-control" required>
                                <small class="text-muted">Password must be at least 6 characters long</small>
                            </div>
                            <div class="form-group mb-3">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Reset Password</button>
                        </form>
                    <?php else: ?>
                        <div class="text-center mt-3">
                            <a href="forgot_password.php" class="btn btn-primary">Request New Reset Link</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>