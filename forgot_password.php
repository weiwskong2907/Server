<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/email.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = "Email address is required";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate a unique token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store the token in the database
            $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            if ($stmt->execute([$user['id'], $token, $expires])) {
                // Send reset email
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;
                $subject = "Password Reset Request";
                $message = "<p>Hello " . htmlspecialchars($user['username']) . ",</p>";
                $message .= "<p>You recently requested to reset your password. Click the link below to reset it:</p>";
                $message .= "<p><a href='" . $reset_link . "'>Reset Password</a></p>";
                $message .= "<p>This link will expire in 1 hour.</p>";
                $message .= "<p>If you did not request a password reset, please ignore this email.</p>";
                
                if (send_email($email, $subject, $message)) {
                    $success = "A password reset link has been sent to your email address.";
                } else {
                    $error = "Failed to send reset email. Please try again later.";
                }
            } else {
                $error = "An error occurred. Please try again later.";
            }
        } else {
            // Don't reveal that the email doesn't exist for security reasons
            $success = "If your email address exists in our database, you will receive a password recovery link at your email address in a few minutes.";
        }
    }
}

include 'layouts/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Forgot Password</div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php else: ?>
                        <p>Enter your email address below and we'll send you a link to reset your password.</p>
                        <form method="POST">
                            <div class="form-group mb-3">
                                <label>Email Address</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" class="btn btn-primary">Send Reset Link</button>
                                <a href="login.php" class="text-decoration-none">Back to Login</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>