<?php
require_once './includes/config.php';
require_once './includes/db.php';
require_once './includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get current user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    // Start transaction for multiple updates
    $pdo->beginTransaction();
    
    try {
        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_picture']['name'];
            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($file_ext, $allowed)) {
                throw new Exception("Only JPG, JPEG, PNG and GIF files are allowed");
            }
            
            if ($_FILES['profile_picture']['size'] > 5242880) { // 5MB max
                throw new Exception("File size must be less than 5MB");
            }
            
            // Create unique filename
            $new_filename = uniqid('profile_') . '.' . $file_ext;
            $upload_dir = 'uploads/profile_pictures/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $destination)) {
                // Delete old profile picture if exists
                if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])) {
                    unlink($user['profile_picture']);
                }
                
                // Update profile picture in database
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->execute([$destination, $_SESSION['user_id']]);
            } else {
                throw new Exception("Failed to upload profile picture");
            }
        }
        
        // Update name
        $name = trim($_POST['name']);
        if (!empty($name) && $name !== $user['name']) {
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->execute([$name, $_SESSION['user_id']]);
        }
        
        // Update email
        $email = trim($_POST['email']);
        if (!empty($email) && $email !== $user['email']) {
            $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
        }
        
        // Update additional details
        $bio = trim($_POST['bio']);
        $location = trim($_POST['location']);
        $website = trim($_POST['website']);
        
        $stmt = $pdo->prepare("UPDATE users SET bio = ?, location = ?, website = ? WHERE id = ?");
        $stmt->execute([$bio, $location, $website, $_SESSION['user_id']]);
        
        // Handle password change
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (!empty($current_password)) {
            // User wants to change password
            if (!password_verify($current_password, $user['password'])) {
                throw new Exception("Current password is incorrect");
            } elseif (empty($new_password)) {
                throw new Exception("New password is required");
            } elseif ($new_password !== $confirm_password) {
                throw new Exception("New passwords do not match");
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
            }
        }
        
        // Commit all changes
        $pdo->commit();
        $success = "Profile updated successfully";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Get updated user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Count user posts
$stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$post_count = $stmt->fetchColumn();

// Count user comments
$stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$comment_count = $stmt->fetchColumn();

include 'layouts/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <!-- User Profile Card -->
            <div class="card profile-card mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-user-circle me-2"></i>Profile</h3>
                </div>
                <div class="card-body text-center">
                    <div class="profile-avatar mb-3">
                        <?php if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="rounded-circle profile-img">
                        <?php else: ?>
                            <i class="fas fa-user-circle fa-6x text-primary"></i>
                        <?php endif; ?>
                    </div>
                    <h4 class="mb-0"><?php echo htmlspecialchars($user['name'] ?? $user['username']); ?></h4>
                    <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                    
                    <?php if (!empty($user['bio'])): ?>
                    <div class="bio-section mt-3 mb-3">
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="user-details mt-3">
                        <p class="mb-1"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?></p>
                        
                        <?php if (!empty($user['location'])): ?>
                        <p class="mb-1"><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($user['location']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($user['website'])): ?>
                        <p class="mb-1">
                            <i class="fas fa-globe me-2"></i>
                            <a href="<?php echo htmlspecialchars($user['website']); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo htmlspecialchars($user['website']); ?>
                            </a>
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-6">
                            <div class="stat-box p-2 border rounded">
                                <h5 class="mb-0"><?php echo $post_count; ?></h5>
                                <small class="text-muted">Posts</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box p-2 border rounded">
                                <h5 class="mb-0"><?php echo $comment_count; ?></h5>
                                <small class="text-muted">Comments</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <!-- Profile Settings Card -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-cog me-2"></i>Profile Settings</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <h4><i class="fas fa-image me-2"></i>Profile Picture</h4>
                            <div class="mb-3">
                                <label class="form-label">Upload New Picture</label>
                                <input type="file" name="profile_picture" class="form-control" accept="image/*">
                                <small class="text-muted">Max file size: 5MB. Allowed formats: JPG, PNG, GIF</small>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h4><i class="fas fa-id-card me-2"></i>Basic Information</h4>
                            <div class="form-group mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                <small class="text-muted">Username cannot be changed</small>
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label">Display Name</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" placeholder="Your full name">
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h4><i class="fas fa-info-circle me-2"></i>Additional Details</h4>
                            <div class="form-group mb-3">
                                <label class="form-label">Bio</label>
                                <textarea name="bio" class="form-control" rows="3" placeholder="Tell us about yourself"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">Location</label>
                                        <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" placeholder="City, Country">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">Website</label>
                                        <input type="url" name="website" class="form-control" value="<?php echo htmlspecialchars($user['website'] ?? ''); ?>" placeholder="https://example.com">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h4><i class="fas fa-lock me-2"></i>Change Password</h4>
                            <div class="form-group mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control">
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">New Password</label>
                                        <input type="password" name="new_password" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" name="confirm_password" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Profile
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- User's Posts Section -->
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-file-alt me-2"></i>My Posts</h3>
                    <a href="post.php?action=new" class="btn btn-light btn-sm">
                        <i class="fas fa-plus-circle me-1"></i>New Post
                    </a>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
                    $stmt->execute([$_SESSION['user_id']]);
                    $posts = $stmt->fetchAll();
                    
                    if ($posts): ?>
                        <div class="list-group">
                            <?php foreach($posts as $post): ?>
                                <a href="post.php?id=<?php echo $post['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-1"><?php echo htmlspecialchars($post['title']); ?></h5>
                                            <p class="mb-1 text-muted small">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                                            </p>
                                        </div>
                                        <span class="badge bg-primary rounded-pill">
                                            <i class="fas fa-eye me-1"></i>
                                            <?php 
                                            // Get view count if available
                                            echo isset($post['views']) ? $post['views'] : '0'; 
                                            ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                            <p>You haven't created any posts yet.</p>
                            <a href="post.php?action=new" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-1"></i>Create Your First Post
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add custom CSS for profile page -->
<style>
.profile-card .profile-avatar {
    margin-top: 10px;
    margin-bottom: 20px;
}

.profile-img {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border: 4px solid #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.bio-section {
    padding: 10px;
    background-color: rgba(0,0,0,0.02);
    border-radius: 5px;
}

.stat-box {
    transition: all 0.3s ease;
}

.stat-box:hover {
    background-color: var(--light-color);
    transform: translateY(-3px);
}

.card-header {
    font-weight: 500;
}

.list-group-item {
    transition: all 0.2s ease;
}

.list-group-item:hover {
    transform: translateX(5px);
    background-color: rgba(52, 152, 219, 0.05);
}

.user-details {
    text-align: left;
    padding: 0 20px;
}

.user-details i {
    width: 20px;
    text-align: center;
    color: var(--primary-color);
}
</style>

<?php include 'layouts/footer.php'; ?>