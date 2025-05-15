<?php
require_once './includes/config.php';
require_once './includes/db.php';
require_once './includes/functions.php';

$page_title = "Privacy Policy";
include 'layouts/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0"><i class="fas fa-shield-alt me-2"></i>Privacy Policy</h1>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <p class="mb-0"><strong>Last Updated:</strong> <?php echo date('F d, Y'); ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <h2 class="h4 mb-3">Introduction</h2>
                        <p>
                            At <?php echo SITE_NAME; ?>, we respect your privacy and are committed to protecting your personal data. 
                            This Privacy Policy explains how we collect, use, and safeguard your information when you use our website.
                        </p>
                        <p>
                            Please read this Privacy Policy carefully. By accessing or using our website, you acknowledge that you have read, 
                            understood, and agree to be bound by the terms described in this policy.
                        </p>
                    </div>
                    
                    <div class="mb-4">
                        <h2 class="h4 mb-3">Information We Collect</h2>
                        <p>We collect several types of information from and about users of our website, including:</p>
                        <ul>
                            <li><strong>Personal Information:</strong> Name, email address, and username when you register for an account.</li>
                            <li><strong>Profile Information:</strong> Optional information you provide in your user profile, such as biography, location, and website.</li>
                            <li><strong>Content:</strong> Information you post on our website, including posts, comments, and attachments.</li>
                            <li><strong>Usage Data:</strong> Information about how you interact with our website, such as pages visited and actions taken.</li>
                            <li><strong>Technical Data:</strong> IP address, browser type and version, device information, and cookies.</li>
                        </ul>
                    </div>
                    
                    <div class="mb-4">
                        <h2 class="h4 mb-3">How We Use Your Information</h2>
                        <p>We use the information we collect for various purposes, including:</p>
                        <ul>
                            <li>To provide, maintain, and improve our website</li>
                            <li>To create and manage your account</li>
                            <li>To enable user-to-user communications</li>
                            <li>To send notifications about your account or activity</li>
                            <li>To ensure the security of our website</li>
                            <li>To analyze usage patterns and trends</li>
                        </ul>
                    </div>
                    
                    <div class="mb-4">
                        <h2 class="h4 mb-3">Data Security</h2>
                        <p>
                            We implement appropriate security measures to protect your personal information from unauthorized access, 
                            alteration, disclosure, or destruction. These measures include internal reviews of our data collection, storage, 
                            and processing practices and security measures, as well as physical security measures to guard against 
                            unauthorized access to systems where we store personal data.
                        </p>
                    </div>
                    
                    <div class="mb-4">
                        <h2 class="h4 mb-3">Your Rights</h2>
                        <p>Depending on your location, you may have certain rights regarding your personal information, including:</p>
                        <ul>
                            <li>The right to access your personal information</li>
                            <li>The right to correct inaccurate or incomplete information</li>
                            <li>The right to delete your personal information</li>
                            <li>The right to restrict or object to processing of your personal information</li>
                            <li>The right to data portability</li>
                        </ul>
                        <p>
                            To exercise any of these rights, please contact us using the information provided in the "Contact Us" section below.
                        </p>
                    </div>
                    
                    <div class="mb-4">
                        <h2 class="h4 mb-3">Changes to This Privacy Policy</h2>
                        <p>
                            We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy 
                            on this page and updating the "Last Updated" date at the top of this Privacy Policy. You are advised to review this 
                            Privacy Policy periodically for any changes.
                        </p>
                    </div>
                    
                    <div class="mb-4">
                        <h2 class="h4 mb-3">Contact Us</h2>
                        <p>
                            If you have any questions about this Privacy Policy, please contact us at:
                        </p>
                        <div class="card bg-light">
                            <div class="card-body">
                                <p class="mb-1"><strong><?php echo SITE_NAME; ?></strong></p>
                                <p class="mb-1">Email: <a href="mailto:privacy@example.com">privacy@example.com</a></p>
                                <p class="mb-0">Or visit our <a href="contact.php">Contact Us</a> page.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>