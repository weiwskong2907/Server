<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin panel for managing the forum">
    <title><?php echo defined('SITE_NAME') ? SITE_NAME : 'My Site'; ?> - Admin Panel</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <!-- Admin-specific CSS -->
    <style>
        .admin-sidebar {
            min-height: calc(100vh - 56px);
            background-color: #343a40;
        }
        .admin-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.75);
            border-radius: 0;
            padding: 0.75rem 1rem;
        }
        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .admin-sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 0.5rem;
        }
        .admin-content {
            padding: 1.5rem;
        }
        .admin-header {
            background-color: #2c3e50;
            color: white;
        }
        .dashboard-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.03);
        }
        .admin-sidebar .nav-link {
            transition: all 0.2s ease;
        }
        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 50%;
        }
        
        /* Dark Mode Styles */
        .dark-mode { background-color: #222; color: #f8f9fa; }
        .dark-mode .card { background-color: #333; color: #f8f9fa; }
        .dark-mode .table { color: #f8f9fa; }
        .dark-mode .admin-sidebar { background-color: #111; }
        .dark-mode .admin-header { background-color: #111; }
    </style>
    <!-- Favicon -->
    <link rel="shortcut icon" href="../assets/images/favicon.ico" type="image/x-icon">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark admin-header">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="admin_dashboard.php">
                <i class="fas fa-cog me-2"></i>
                <span><?php echo defined('SITE_NAME') ? SITE_NAME : 'My Site'; ?> Admin</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarAdmin">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php" target="_blank"><i class="fas fa-external-link-alt me-1"></i> View Site</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-id-card me-2"></i> Admin Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block admin-sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>" href="admin_dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_users.php' ? 'active' : ''; ?>" href="admin_users.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_posts.php' ? 'active' : ''; ?>" href="admin_posts.php">
                                <i class="fas fa-file-alt"></i> Posts
                            </a>
                        </li>
                        <!-- Add this after the Posts nav item (around line 95) -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_categories.php' ? 'active' : ''; ?>" href="admin_categories.php">
                                <i class="fas fa-folder"></i> Categories
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_comments.php' ? 'active' : ''; ?>" href="admin_comments.php">
                                <i class="fas fa-comments"></i> Comments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                                <i class="fas fa-user-cog"></i> Profile
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 admin-content">
                <!-- Notification container for JavaScript alerts -->
                <div id="notification-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1050"></div>

<script>
$(document).ready(function() {
    // Check for saved theme preference or respect OS preference
    const darkMode = localStorage.getItem('darkMode') === 'enabled' || 
                    (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches && 
                    localStorage.getItem('darkMode') !== 'disabled');
    
    // Set initial theme
    if (darkMode) {
        enableDarkMode();
    }
    
    // Dark mode toggle
    $('#darkModeToggle').click(function() {
        if ($('body').hasClass('dark-mode')) {
            disableDarkMode();
        } else {
            enableDarkMode();
        }
    });
    
    function enableDarkMode() {
        $('body').addClass('dark-mode');
        $('#darkModeToggle i').removeClass('fa-moon').addClass('fa-sun');
        localStorage.setItem('darkMode', 'enabled');
    }
    
    function disableDarkMode() {
        $('body').removeClass('dark-mode');
        $('#darkModeToggle i').removeClass('fa-sun').addClass('fa-moon');
        localStorage.setItem('darkMode', 'disabled');
    }
});
</script>
<!-- Add this to the navbar, before the user dropdown -->
<li class="nav-item">
    <button class="btn nav-link" id="darkModeToggle">
        <i class="fas fa-moon"></i>
    </button>
</li>
</body>