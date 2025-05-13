<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo defined('SITE_NAME') ? SITE_NAME : 'My Site'; ?></title>
    <!-- Make sure Bootstrap CSS is loaded first -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Move Bootstrap JS to the end of body for better performance -->
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--dark-color, #2c3e50);">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="fas fa-comments me-2"></i>
                <span><?php echo defined('SITE_NAME') ? SITE_NAME : 'My Site'; ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="post.php?action=new"><i class="fas fa-plus-circle me-1"></i> New Post</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-id-card me-2"></i> Profile</a></li>
                                <?php 
                                try {
                                    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $current_user = $stmt->fetch();
                                } catch (Exception $e) {
                                    error_log("Error in header: " . $e->getMessage());
                                    $current_user = false;
                                }
                                if ($current_user && $current_user['is_admin']): 
                                ?>
                                <li><a class="dropdown-item" href="admin/"><i class="fas fa-cog me-2"></i> Admin Panel</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php"><i class="fas fa-user-plus me-1"></i> Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Add Bootstrap JS at the end of body -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    <!-- Add this right before the closing </body> tag -->
    <script>
        // Initialize all dropdowns
        document.addEventListener('DOMContentLoaded', function() {
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            dropdownElementList.forEach(function(dropdownToggleEl) {
                dropdownToggleEl.addEventListener('click', function(e) {
                    e.preventDefault();
                    var dropdown = this.nextElementSibling;
                    if (dropdown.classList.contains('show')) {
                        dropdown.classList.remove('show');
                    } else {
                        dropdown.classList.add('show');
                    }
                });
            });
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.matches('.dropdown-toggle')) {
                    var dropdowns = document.getElementsByClassName('dropdown-menu');
                    for (var i = 0; i < dropdowns.length; i++) {
                        if (dropdowns[i].classList.contains('show')) {
                            dropdowns[i].classList.remove('show');
                        }
                    }
                }
            });
        });
    </script>
</body>