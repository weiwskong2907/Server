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

// Ensure all POST request handling (like user creation, deletion) is done before this.

// Get all users with post and comment counts, applying filters
$base_sql = "SELECT users.*,
            (SELECT COUNT(*) FROM posts WHERE user_id = users.id) as post_count,
            (SELECT COUNT(*) FROM comments WHERE user_id = users.id) as comment_count
            FROM users";
$where_conditions = [];
$query_params = []; // Use an associative array for named placeholders

// Search filter
if (!empty($_GET['search'])) {
    $search_term = '%' . trim($_GET['search']) . '%';
    // Use OR for multiple fields in search
    $where_conditions[] = "(users.username LIKE :search_username OR users.email LIKE :search_email)";
    $query_params[':search_username'] = $search_term;
    $query_params[':search_email'] = $search_term;
}

// Role filter (using admin_level)
if (!empty($_GET['role'])) {
    $role = $_GET['role'];
    if ($role == 'admin') {
        $where_conditions[] = "users.admin_level = :role_admin_level";
        $query_params[':role_admin_level'] = 2;
    } elseif ($role == 'subadmin') {
        $where_conditions[] = "users.admin_level = :role_admin_level";
        $query_params[':role_admin_level'] = 1;
    } elseif ($role == 'user') {
        $where_conditions[] = "users.admin_level = :role_admin_level";
        $query_params[':role_admin_level'] = 0;
    }
    // If 'All Roles' is selected, $_GET['role'] is empty, so no role-specific WHERE clause is added.
}

$sql_query = $base_sql;
if (!empty($where_conditions)) {
    $sql_query .= " WHERE " . implode(" AND ", $where_conditions);
}

// Sort order
$sort_by = $_GET['sort_by'] ?? 'newest'; // Default to 'newest'
$order_by_clause = " ORDER BY ";
switch ($sort_by) {
    case 'oldest':
        $order_by_clause .= "users.created_at ASC";
        break;
    case 'username_asc':
        $order_by_clause .= "users.username ASC";
        break;
    case 'username_desc':
        $order_by_clause .= "users.username DESC";
        break;
    case 'newest':
    default:
        $order_by_clause .= "users.created_at DESC";
        break;
}
$sql_query .= $order_by_clause;

$stmt = $pdo->prepare($sql_query);
$stmt->execute($query_params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC); // Using FETCH_ASSOC is common

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
        
        // After validation and $errors check:
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // Determine admin_level based on the is_admin checkbox
            // is_admin comes from: $is_admin = isset($_POST['is_admin']) ? 1 : 0;
            $admin_level_value = $is_admin ? 2 : 0; // If 'is_admin' is checked, set level to 2 (Admin), else 0 (User)
        
            // Ensure your INSERT statement includes admin_level
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_admin, admin_level) VALUES (:username, :email, :password, :is_admin, :admin_level)");
            
            if ($stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $hashed_password,
                ':is_admin' => $is_admin,
                ':admin_level' => $admin_level_value
            ])) {
                $message = ["success" => "User created successfully"];
                // Consider redirecting here to prevent form resubmission (Post/Redirect/Get pattern)
                // header("Location: admin_users.php?success=user_created"); exit();
                // If not redirecting, the $users variable will be re-fetched by the logic above if this POST handling is at the top of the script.
            } else {
                $errors[] = "Error creating user";
                // Add detailed error logging here if needed
                // error_log("PDO Error: " . print_r($stmt->errorInfo(), true));
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