<?php
require_once 'config.php';

function send_email($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . SITE_NAME . ' <noreply@' . $_SERVER['HTTP_HOST'] . '>' . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

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