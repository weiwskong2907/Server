<?php
require_once 'includes/config.php';
require_once 'includes/layout.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: /');
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        // Attempt registration
        $result = register_user($username, $email, $password);
        
        if ($result['success']) {
            $success = 'Registration successful! Please check your email to verify your account.';
        } else {
            $error = $result['message'];
        }
    }
}

// Get page header
echo get_page_header('Register', ['Register' => null]);

// Display messages
if ($error) {
    echo get_alert('danger', $error);
}
if ($success) {
    echo get_alert('success', $success);
}
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-body">
                <h1 class="h3 mb-4 text-center">Create Account</h1>
                
                <form method="POST" action="/register.php">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        <div class="form-text">Choose a unique username (3-20 characters)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        <div class="form-text">We'll send a verification email to this address</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">Must be at least 8 characters long</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="/terms.php" target="_blank">Terms of Service</a> and 
                            <a href="/privacy.php" target="_blank">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Create Account</button>
                    </div>
                </form>
                
                <hr>
                
                <div class="text-center">
                    <p class="mb-0">Already have an account? <a href="/login.php" class="text-decoration-none">Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Get page footer
echo get_page_footer();
?>