<?php
require_once 'config.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_email($to, $subject, $message) {
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();                                      // Use SMTP
        $mail->Host       = SMTP_HOST;                        // SMTP server
        $mail->SMTPAuth   = true;                             // Enable SMTP authentication
        $mail->Username   = SMTP_USER;                        // SMTP username
        $mail->Password   = SMTP_PASS;                        // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // Enable TLS encryption
        $mail->Port       = SMTP_PORT;                        // TCP port to connect to

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SITE_NAME);
        $mail->addAddress($to);                               // Add a recipient

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
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