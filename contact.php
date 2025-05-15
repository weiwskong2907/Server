<?php
require_once './includes/config.php';
require_once './includes/db.php';
require_once './includes/functions.php';
require_once './includes/email.php';

$page_title = "Contact Us";
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validate inputs
    if (empty($name)) {
        $error = "Please enter your name";
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } elseif (empty($subject)) {
        $error = "Please enter a subject";
    } elseif (empty($message)) {
        $error = "Please enter your message";
    } else {
        // All inputs are valid, send email
        $to = "contact@example.com"; // Replace with your contact email
        $email_subject = "Contact Form: " . $subject;
        $email_body = "<h2>Contact Form Submission</h2>"
                    . "<p><strong>Name:</strong> {$name}</p>"
                    . "<p><strong>Email:</strong> {$email}</p>"
                    . "<p><strong>Subject:</strong> {$subject}</p>"
                    . "<p><strong>Message:</strong></p>"
                    . "<p>" . nl2br(htmlspecialchars($message)) . "</p>";
        
        // Try to send email
        if (send_email($to, $email_subject, $email_body)) {
            $success = "Thank you for your message. We'll get back to you soon!";
            // Clear form data after successful submission
            $name = $email = $subject = $message = '';
        } else {
            $error = "Sorry, there was an error sending your message. Please try again later.";
        }
    }
}

include 'layouts/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0"><i class="fas fa-envelope me-2"></i>Contact Us</h1>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-4 mb-md-0">
                            <h2 class="h4 mb-3">Get In Touch</h2>
                            <p>
                                We'd love to hear from you! Whether you have a question about our services, 
                                need help with your account, or just want to say hello, please don't hesitate to reach out.
                            </p>
                            <div class="mt-4">
                                <div class="d-flex mb-3">
                                    <div class="flex-shrink-0 me-3">
                                        <i class="fas fa-envelope fa-2x text-primary"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Email Us</h5>
                                        <p class="mb-0"><a href="mailto:unknowsuser050@gmail.com">unknowsuser050@gmail.com</a></p>
                                    </div>
                                </div>
                                <div class="d-flex mb-3">
                                    <div class="flex-shrink-0 me-3">
                                        <i class="fas fa-clock fa-2x text-primary"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Response Time</h5>
                                        <p class="mb-0">We typically respond within 24-48 hours</p>
                                    </div>
                                </div>                  
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h2 class="h4 mb-3">Contact Form</h2>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Your Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($subject ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message</label>
                                    <textarea class="form-control" id="message" name="message" rows="5" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Send Message
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h2 class="h4 mb-3">Frequently Asked Questions</h2>
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                        How do I create an account?
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        To create an account, click on the "Register" button in the top navigation bar. Fill out the registration form with your username, email address, and password, then click "Register" to create your account.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        How do I create a new post?
                                    </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        After logging in, click on the "New Post" button in the navigation bar. Fill out the post form with a title, content, and category, then click "Submit" to create your post.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                        I forgot my password. What should I do?
                                    </button>
                                </h2>
                                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        If you forgot your password, go to the login page and click on the "Forgot Password" link. Enter your email address, and we'll send you instructions on how to reset your password.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>