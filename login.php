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
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Attempt login
        $result = login_user($email, $password, $remember);
        
        if ($result['success']) {
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
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
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
                    <p class="mb-0">Don't have an account? <a href="/register.php" class="text-decoration-none">Register</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Get page footer
echo get_page_footer();
?>