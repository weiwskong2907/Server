<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'family_forum');

// Site settings
define('SITE_NAME', 'Family & Friends Forum');
define('SITE_URL', 'http://localhost/Server');

// SMTP settings
define('SMTP_HOST', 'smtp.gmail.com');  // Replace with your SMTP server
define('SMTP_USER', 'your-email@gmail.com');  // Replace with your email
define('SMTP_PASS', 'your-app-password');  // Replace with your password or app password
define('SMTP_PORT', 587);  // Common SMTP port
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');  // Replace with your email

// Session settings
session_start();
?>