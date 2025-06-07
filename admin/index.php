<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Verify admin status directly from database
$stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['is_admin'] != 1) {
    header('Location: ../login.php');
    exit;
}

// Get statistics for dashboard
$db = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
    DB_USER,
    DB_PASS,
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

// Count total users
$stmt = $db->prepare("SELECT COUNT(*) as total_users FROM users");
$stmt->execute();
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

// Count total posts
$stmt = $db->prepare("SELECT COUNT(*) as total_posts FROM posts");
$stmt->execute();
$total_posts = $stmt->fetch(PDO::FETCH_ASSOC)['total_posts'];

// Count total comments
$stmt = $db->prepare("SELECT COUNT(*) as total_comments FROM comments");
$stmt->execute();
$total_comments = $stmt->fetch(PDO::FETCH_ASSOC)['total_comments'];

// Get recent users
$stmt = $db->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent posts
$stmt = $db->prepare("SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 5");
$stmt->execute();
$recent_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get activity data for chart (last 7 days)
$activity_data = [];
$labels = [];

// Get date for the last 7 days
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('M d', strtotime($date));
    
    // Count posts created on this date
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM posts WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $posts_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count comments created on this date
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM comments WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $comments_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $activity_data['posts'][] = $posts_count;
    $activity_data['comments'][] = $comments_count;
}

// Include header
include 'header.php';
?>

<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Admin Dashboard</h1>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card dashboard-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Users</h6>
                            <h2 class="mb-0"><?php echo $total_users; ?></h2>
                        </div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="users.php" class="text-white text-decoration-none">View Details</a>
                    <i class="fas fa-arrow-circle-right"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card dashboard-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Posts</h6>
                            <h2 class="mb-0"><?php echo $total_posts; ?></h2>
                        </div>
                        <i class="fas fa-file-alt fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="posts.php" class="text-white text-decoration-none">View Details</a>
                    <i class="fas fa-arrow-circle-right"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card dashboard-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Comments</h6>
                            <h2 class="mb-0"><?php echo $total_comments; ?></h2>
                        </div>
                        <i class="fas fa-comments fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="comments.php" class="text-white text-decoration-none">View Details</a>
                    <i class="fas fa-arrow-circle-right"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Activity Chart -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Activity Overview (Last 7 Days)</h5>
        </div>
        <div class="card-body">
            <canvas id="activityChart" height="100"></canvas>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Users -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Users</h5>
                    <a href="users.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Joined Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recent_users) > 0): ?>
                                    <?php foreach ($recent_users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                        <span class="badge bg-<?php echo isset($user['is_admin']) && $user['is_admin'] == 1 ? 'danger' : 'secondary'; ?>">
                                        <?php echo ucfirst(htmlspecialchars(isset($user['is_admin']) && $user['is_admin'] == 1 ? 'admin' : 'user')); ?>
                                    </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No users found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Posts -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Posts</h5>
                    <a href="posts.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recent_posts) > 0): ?>
                                    <?php foreach ($recent_posts as $post): ?>
                                    <tr>
                                        <td>
                                            <a href="../post.php?id=<?php echo $post['id']; ?>" target="_blank">
                                                <?php echo htmlspecialchars(substr($post['title'], 0, 30)) . (strlen($post['title']) > 30 ? '...' : ''); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($post['username']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No posts found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart Initialization Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Activity Chart
        var ctx = document.getElementById('activityChart').getContext('2d');
        var activityChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Posts',
                    data: <?php echo json_encode($activity_data['posts']); ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 2,
                    tension: 0.3
                },
                {
                    label: 'Comments',
                    data: <?php echo json_encode($activity_data['comments']); ?>,
                    backgroundColor: 'rgba(23, 162, 184, 0.2)',
                    borderColor: 'rgba(23, 162, 184, 1)',
                    borderWidth: 2,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    });
</script>

<?php include 'footer.php'; ?>