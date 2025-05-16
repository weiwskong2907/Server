<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ./login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user['is_admin']) {
    header("Location: ./index.php");
    exit();
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $user_id = $_POST['user_id'];
    
    switch ($action) {
        case 'delete':
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND id != ?");
            $stmt->execute([$user_id, $_SESSION['user_id']]);
            $message = ["success" => "User deleted successfully"];
            break;
            
        case 'toggle_admin':
            $stmt = $pdo->prepare("UPDATE users SET is_admin = NOT is_admin WHERE id = ? AND id != ?");
            $stmt->execute([$user_id, $_SESSION['user_id']]);
            $message = ["success" => "User admin status updated"];
            break;
            
        case 'reset_password':
            // Generate a random password
            $new_password = bin2hex(random_bytes(8));
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            
            $message = ["success" => "Password reset to: " . $new_password];
            break;
    }
}

// Get all users with post and comment counts
$users = $pdo->query(
    "SELECT users.*, 
            (SELECT COUNT(*) FROM posts WHERE user_id = users.id) as post_count,
            (SELECT COUNT(*) FROM comments WHERE user_id = users.id) as comment_count
     FROM users 
     ORDER BY created_at DESC"
)->fetchAll();

include 'header.php';
?>

<div class="container mt-4">
    <!-- Add after the header section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search Users</label>
                            <input type="text" class="form-control" id="search" name="search" placeholder="Username or email" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="">All Roles</option>
                                <option value="admin" <?php echo isset($_GET['role']) && $_GET['role'] == 'admin' ? 'selected' : ''; ?>>Admins</option>
                                <option value="user" <?php echo isset($_GET['role']) && $_GET['role'] == 'user' ? 'selected' : ''; ?>>Regular Users</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="sort" class="form-label">Sort By</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="newest" <?php echo (!isset($_GET['sort']) || $_GET['sort'] == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                                <option value="oldest" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                <option value="username" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'username' ? 'selected' : ''; ?>>Username</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add this PHP code near the top of the file -->
<?php
// Handle new user creation
if (isset($_GET['action']) && $_GET['action'] == 'new') {
    $show_form = true;
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_user'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;
        
        // Validate input
        $errors = [];
        
        if (empty($username)) {
            $errors[] = "Username is required";
        }
        
        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        if (empty($password)) {
            $errors[] = "Password is required";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters";
        }
        
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Username or email already exists";
        }
        
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_admin, created_at) VALUES (?, ?, ?, ?, NOW())");
            if ($stmt->execute([$username, $email, $hashed_password, $is_admin])) {
                $message = ["success" => "User created successfully"];
                $show_form = false;
            } else {
                $errors[] = "Error creating user";
            }
        }
    }
}
?>

<!-- Add this HTML where you want the form to appear -->
<?php if (isset($show_form) && $show_form): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Create New User</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="admin_users.php?action=new">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">Password must be at least 8 characters long.</div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin" <?php echo isset($_POST['is_admin']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_admin">Admin privileges</label>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="admin_users.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" name="create_user" class="btn btn-primary">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; 
include 'footer.php';?>

<!-- Add this before the closing </div> tag at the end of the file -->
<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4" id="userProfileSection">
                    <!-- Profile picture will be inserted here via JavaScript -->
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" id="modalUsername" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="modalEmail" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Joined Date</label>
                        <input type="text" class="form-control" id="modalJoined" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Last Login</label>
                        <input type="text" class="form-control" id="modalLastLogin" readonly>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4 text-center">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h3 class="display-4" id="modalPostCount">0</h3>
                                <p class="text-muted">Posts</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h3 class="display-4" id="modalCommentCount">0</h3>
                                <p class="text-muted">Comments</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h3 class="display-4" id="modalStatus"></h3>
                                <p class="text-muted">Status</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" class="btn btn-primary" id="viewUserPosts">View Posts</a>
            </div>
        </div>
    </div>
</div>

<!-- Add this JavaScript before the closing </body> tag -->
<script>
$(document).ready(function() {
    $('.view-user-details').click(function() {
        const userId = $(this).data('user-id');
        const username = $(this).data('username');
        const email = $(this).data('email');
        const joined = $(this).data('joined');
        const postCount = $(this).data('post-count');
        const commentCount = $(this).data('comment-count');
        const isAdmin = $(this).data('is-admin');
        
        $('#modalUsername').val(username);
        $('#modalEmail').val(email);
        $('#modalJoined').val(joined);
        $('#modalPostCount').text(postCount);
        $('#modalCommentCount').text(commentCount);
        $('#modalStatus').html(isAdmin ? '<span class="text-success">Admin</span>' : '<span class="text-primary">User</span>');
        
        // Set profile picture or default icon
        const profilePic = $(this).data('profile-pic');
        if (profilePic) {
            $('#userProfileSection').html(`<img src="${profilePic}" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">`);
        } else {
            $('#userProfileSection').html(`<div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto" style="width: 150px; height: 150px;"><i class="fas fa-user fa-5x text-white"></i></div>`);
        }
        
        $('#viewUserPosts').attr('href', `admin_posts.php?user_id=${userId}`);
        
        $('#userDetailsModal').modal('show');
    });
});
</script>