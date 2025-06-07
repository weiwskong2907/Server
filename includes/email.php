<?php
/**
 * Email helper functions
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/Exception.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';

/**
 * Send verification email
 */
function send_verification_email($to_email, $username, $verification_link) {
    $subject = 'Verify your email address';
    $body = "
        <h2>Welcome to " . SITE_NAME . "!</h2>
        <p>Hi $username,</p>
        <p>Thank you for registering. Please click the link below to verify your email address:</p>
        <p><a href='$verification_link'>$verification_link</a></p>
        <p>This link will expire in 24 hours.</p>
        <p>If you did not create an account, please ignore this email.</p>
    ";
    
    return send_email($to_email, $subject, $body);
}

/**
 * Send password reset email
 */
function send_password_reset_email($to_email, $username, $reset_link) {
    $subject = 'Reset your password';
    $body = "
        <h2>Password Reset Request</h2>
        <p>Hi $username,</p>
        <p>We received a request to reset your password. Click the link below to reset it:</p>
        <p><a href='$reset_link'>$reset_link</a></p>
        <p>This link will expire in 1 hour.</p>
        <p>If you did not request a password reset, please ignore this email.</p>
    ";
    
    return send_email($to_email, $subject, $body);
}

/**
 * Send notification email
 */
function send_notification_email($to_email, $subject, $message) {
    $body = "
        <h2>$subject</h2>
        <p>$message</p>
    ";
    
    return send_email($to_email, $subject, $body);
}

/**
 * Send email using PHPMailer
 */
function send_email($to_email, $subject, $body) {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SITE_NAME);
        $mail->addAddress($to_email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        // Add plain text version
        $mail->AltBody = strip_tags($body);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Keep the rest of your send_comment_notification function unchanged
function send_comment_notification($post_id, $comment_id) {
    global $pdo;
    
    // Get post and comment details
    $stmt = $pdo->prepare(
        "SELECT posts.title, posts.user_id, users.email, users.username,
                comments.content as comment_content, comment_users.username as commenter_name
         FROM posts 
         JOIN users ON posts.user_id = users.id
         JOIN comments ON comments.id = ?
         JOIN users comment_users ON comments.user_id = comment_users.id
         WHERE posts.id = ?"
    );
    $stmt->execute([$comment_id, $post_id]);
    $data = $stmt->fetch();
    
    if ($data) {
        $subject = "New comment on your post: " . $data['title'];
        
        $message = "<html><body>";
        $message .= "<h2>New Comment on Your Post</h2>";
        $message .= "<p>Hello {$data['username']},</p>";
        $message .= "<p>{$data['commenter_name']} commented on your post: {$data['title']}</p>";
        $message .= "<blockquote>{$data['comment_content']}</blockquote>";
        $message .= "<p><a href='" . SITE_URL . "/post.php?id={$post_id}'>View Post</a></p>";
        $message .= "</body></html>";
        
        return send_email($data['email'], $subject, $message);
    }
    
    return false;
}
?>