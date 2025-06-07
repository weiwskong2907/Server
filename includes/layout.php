<?php
/**
 * Layout helper functions
 */

/**
 * Get page title
 */
function get_page_title($title = '') {
    $site_name = get_setting('site_name', 'Family Forum');
    return $title ? "$title - $site_name" : $site_name;
}

/**
 * Get page header
 */
function get_page_header($title = '', $breadcrumbs = []) {
    $user = is_logged_in() ? get_user($_SESSION['user_id']) : null;
    $notifications = is_logged_in() ? get_user_notifications($_SESSION['user_id']) : [];
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars(get_page_title($title)); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link href="/assets/css/style.css" rel="stylesheet">
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="/"><?php echo htmlspecialchars(get_setting('site_name', 'Family Forum')); ?></a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/categories.php">Categories</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/tags.php">Tags</a>
                        </li>
                        <?php if (is_logged_in()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/create-post.php">New Post</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <ul class="navbar-nav">
                        <?php if (is_logged_in()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-bell"></i>
                                    <?php if (count($notifications) > 0): ?>
                                        <span class="badge bg-danger"><?php echo count($notifications); ?></span>
                                    <?php endif; ?>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <?php if (count($notifications) > 0): ?>
                                        <?php foreach ($notifications as $notification): ?>
                                            <a class="dropdown-item" href="<?php echo $notification['link']; ?>">
                                                <?php echo htmlspecialchars($notification['message']); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="dropdown-item">No new notifications</div>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                    <img src="<?php echo get_user_avatar($user['id']); ?>" class="rounded-circle" width="24" height="24">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="/profile.php">Profile</a>
                                    <a class="dropdown-item" href="/settings.php">Settings</a>
                                    <?php if (is_admin()): ?>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="/admin/">Admin Panel</a>
                                    <?php endif; ?>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="/logout.php">Logout</a>
                                </div>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/login.php">Login</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/register.php">Register</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>

        <?php if (!empty($breadcrumbs)): ?>
        <nav aria-label="breadcrumb" class="bg-light py-2">
            <div class="container">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/">Home</a></li>
                    <?php foreach ($breadcrumbs as $label => $url): ?>
                        <?php if ($url): ?>
                            <li class="breadcrumb-item"><a href="<?php echo $url; ?>"><?php echo htmlspecialchars($label); ?></a></li>
                        <?php else: ?>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($label); ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </div>
        </nav>
        <?php endif; ?>

        <main class="container py-4">
    <?php
    return ob_get_clean();
}

/**
 * Get page footer
 */
function get_page_footer() {
    ob_start();
    ?>
        </main>
        <footer class="bg-light py-4 mt-auto">
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(get_setting('site_name', 'Family Forum')); ?>. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <a href="/about.php" class="text-decoration-none me-3">About</a>
                        <a href="/privacy.php" class="text-decoration-none me-3">Privacy Policy</a>
                        <a href="/terms.php" class="text-decoration-none">Terms of Service</a>
                    </div>
                </div>
            </div>
        </footer>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
        <script src="/assets/js/main.js"></script>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

/**
 * Get alert message
 */
function get_alert($type, $message) {
    return sprintf(
        '<div class="alert alert-%s alert-dismissible fade show" role="alert">%s<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>',
        $type,
        htmlspecialchars($message)
    );
}

/**
 * Get pagination
 */
function get_pagination($current_page, $total_pages, $url_pattern) {
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    $html .= sprintf(
        '<li class="page-item %s"><a class="page-link" href="%s">Previous</a></li>',
        $current_page <= 1 ? 'disabled' : '',
        $current_page <= 1 ? '#' : sprintf($url_pattern, $current_page - 1)
    );
    
    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    if ($start > 1) {
        $html .= sprintf(
            '<li class="page-item"><a class="page-link" href="%s">1</a></li>',
            sprintf($url_pattern, 1)
        );
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $html .= sprintf(
            '<li class="page-item %s"><a class="page-link" href="%s">%d</a></li>',
            $i === $current_page ? 'active' : '',
            sprintf($url_pattern, $i),
            $i
        );
    }
    
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= sprintf(
            '<li class="page-item"><a class="page-link" href="%s">%d</a></li>',
            sprintf($url_pattern, $total_pages),
            $total_pages
        );
    }
    
    // Next button
    $html .= sprintf(
        '<li class="page-item %s"><a class="page-link" href="%s">Next</a></li>',
        $current_page >= $total_pages ? 'disabled' : '',
        $current_page >= $total_pages ? '#' : sprintf($url_pattern, $current_page + 1)
    );
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Get user avatar
 */
function get_user_avatar($user_id) {
    $user = get_user($user_id);
    if ($user && $user['avatar']) {
        return $user['avatar'];
    }
    return '/assets/images/default-avatar.png';
}

/**
 * Get user notifications
 */
function get_user_notifications($user_id) {
    return get_rows(
        "SELECT * FROM notifications 
         WHERE user_id = ? AND is_read = 0 
         ORDER BY created_at DESC 
         LIMIT 5",
        "i",
        [$user_id]
    );
}

/**
 * Mark notification as read
 */
function mark_notification_read($notification_id) {
    return update_data(
        'notifications',
        ['is_read' => 1],
        'id = ?',
        'i',
        [$notification_id]
    );
} 