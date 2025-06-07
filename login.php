<?php
require_once 'includes/config.php';
require_once 'includes/layout.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: /');
    exit();
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_username = $_POST['email_or_username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validate input
    if (empty($email_or_username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Attempt login
        $result = login_user($email_or_username, $password);
        
        if ($result['success']) {
            // Set remember me cookie if requested
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = time() + (30 * 24 * 60 * 60); // 30 days
                
                // Store token in database
                $token_data = [
                    'user_id' => $_SESSION['user_id'],
                    'token' => $token,
                    'expires_at' => date('Y-m-d H:i:s', $expires)
                ];
                insert_data('remember_tokens', $token_data);
                
                // Set cookie
                setcookie('remember_token', $token, $expires, '/', '', true, true);
            }
            
            // Redirect to intended page or home
            $redirect = $_SESSION['redirect_after_login'] ?? '/';
            unset($_SESSION['redirect_after_login']);
            header("Location: $redirect");
            exit();
        } else {
            $error = $result['message'];
        }
    }
}

// Get page header
echo get_page_header('Login', ['Login' => null]);

// Display error
if ($error) {
    echo get_alert('danger', $error);
}
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-body">
                <h1 class="h3 mb-4 text-center">Login</h1>
                
                <form method="POST" action="/login.php">
                    <div class="mb-3">
                        <label for="email_or_username" class="form-label">Email or Username</label>
                        <input type="text" class="form-control" id="email_or_username" 
                               name="email_or_username" required 
                               value="<?php echo htmlspecialchars($_POST['email_or_username'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" 
                               name="password" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" 
                               name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
                
                <div class="mt-3 text-center">
                    <a href="/forgot-password.php" class="text-decoration-none">Forgot password?</a>
                </div>
                
                <hr>
                
                <div class="text-center">
                    <p class="mb-0">Don't have an account? 
                        <a href="/register.php" class="text-decoration-none">Register</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Get page footer
echo get_page_footer();
?>