<?php
require_once './includes/config.php';
require_once './includes/db.php';
require_once './includes/functions.php';

$page_title = "About Us";
include 'layouts/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0"><i class="fas fa-info-circle me-2"></i>About Us</h1>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h2 class="h4 mb-3">Our Mission</h2>
                        <p>
                            Welcome to <?php echo SITE_NAME; ?>! We are dedicated to creating a vibrant community where people can share ideas, 
                            engage in meaningful discussions, and connect with like-minded individuals. Our platform is designed to foster 
                            intellectual growth, creativity, and collaboration among our users.
                        </p>
                    </div>
                    
                    <div class="mb-4">
                        <h2 class="h4 mb-3">Who We Are</h2>
                        <p>
                            Founded in <?php echo date('Y')-1; ?>, <?php echo SITE_NAME; ?> started as a small project with a big vision. 
                            Our team consists of passionate individuals who believe in the power of community and open discussion. 
                            We come from diverse backgrounds but share a common goal: to create a space where ideas can flourish.
                        </p>
                    </div>
                    
                    <div class="mb-4">
                        <h2 class="h4 mb-3">Our Values</h2>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-primary">
                                    <div class="card-body">
                                        <h5><i class="fas fa-comments text-primary me-2"></i>Open Discussion</h5>
                                        <p class="mb-0">We believe in the free exchange of ideas and respectful dialogue.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-primary">
                                    <div class="card-body">
                                        <h5><i class="fas fa-shield-alt text-primary me-2"></i>Safety & Respect</h5>
                                        <p class="mb-0">We are committed to maintaining a safe and respectful environment for all users.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-primary">
                                    <div class="card-body">
                                        <h5><i class="fas fa-lightbulb text-primary me-2"></i>Innovation</h5>
                                        <p class="mb-0">We encourage creative thinking and innovative ideas.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-primary">
                                    <div class="card-body">
                                        <h5><i class="fas fa-users text-primary me-2"></i>Community</h5>
                                        <p class="mb-0">We foster a sense of belonging and connection among our members.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h2 class="h4 mb-3">Join Our Community</h2>
                        <p>
                            We invite you to become part of our growing community. Whether you're here to share your thoughts, 
                            learn from others, or simply explore new ideas, there's a place for you at <?php echo SITE_NAME; ?>.
                        </p>
                        <?php if (!isset($_SESSION['user_id'])): ?>
                        <div class="text-center mt-4">
                            <a href="register.php" class="btn btn-primary"><i class="fas fa-user-plus me-2"></i>Register Now</a>
                            <a href="login.php" class="btn btn-outline-primary ms-2"><i class="fas fa-sign-in-alt me-2"></i>Login</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>