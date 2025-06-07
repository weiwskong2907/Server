<?php
/**
 * Admin layout helper functions
 */

/**
 * Get admin page title
 */
function get_admin_page_title($title = '') {
    $site_name = get_setting('site_name', 'Family Forum');
    return $title ? "$title - Admin Panel - $site_name" : "Admin Panel - $site_name";
}

/**
 * Get admin page header
 */
function get_admin_header($title = '', $breadcrumbs = []) {
    if (!is_admin()) {
        header('Location: /login.php');
        exit();
    }
    
    $user = get_user($_SESSION['user_id']);
    $notifications = get_user_notifications($_SESSION['user_id']);
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars(get_admin_page_title($title)); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link href="/assets/css/admin.css" rel="stylesheet">
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="/admin/">
                    <i class="fas fa-shield-alt me-2"></i>Admin Panel
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="adminNavbar">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/">
                                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/users.php">
                                <i class="fas fa-users me-1"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/posts.php">
                                <i class="fas fa-file-alt me-1"></i> Posts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/comments.php">
                                <i class="fas fa-comments me-1"></i> Comments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/categories.php">
                                <i class="fas fa-folder me-1"></i> Categories
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/tags.php">
                                <i class="fas fa-tags me-1"></i> Tags
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/reports.php">
                                <i class="fas fa-flag me-1"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/settings.php">
                                <i class="fas fa-cog me-1"></i> Settings
                            </a>
                        </li>
                    </ul>
                    <ul class="navbar-nav">
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
                                <a class="dropdown-item" href="/profile.php">
                                    <i class="fas fa-user me-2"></i> Profile
                                </a>
                                <a class="dropdown-item" href="/settings.php">
                                    <i class="fas fa-cog me-2"></i> Settings
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="/">
                                    <i class="fas fa-home me-2"></i> View Site
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <?php if (!empty($breadcrumbs)): ?>
        <nav aria-label="breadcrumb" class="bg-light py-2">
            <div class="container-fluid">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/admin/">Admin</a></li>
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

        <main class="container-fluid py-4">
    <?php
    return ob_get_clean();
}

/**
 * Get admin page footer
 */
function get_admin_footer() {
    ob_start();
    ?>
        </main>
        <footer class="bg-dark text-light py-3 mt-auto">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(get_setting('site_name', 'Family Forum')); ?> Admin Panel</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <a href="/about.php" class="text-light text-decoration-none me-3">About</a>
                        <a href="/privacy.php" class="text-light text-decoration-none me-3">Privacy Policy</a>
                        <a href="/terms.php" class="text-light text-decoration-none">Terms of Service</a>
                    </div>
                </div>
            </div>
        </footer>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="/assets/js/admin.js"></script>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

/**
 * Get admin alert message
 */
function get_admin_alert($type, $message) {
    return sprintf(
        '<div class="alert alert-%s alert-dismissible fade show" role="alert">%s<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>',
        $type,
        htmlspecialchars($message)
    );
}

/**
 * Get admin pagination
 */
function get_admin_pagination($current_page, $total_pages, $url_pattern) {
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