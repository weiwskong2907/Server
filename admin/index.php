<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'layout.php';

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

// Get dashboard statistics
$stats = get_dashboard_stats();

// Get page header
echo get_admin_header('Dashboard', ['Dashboard' => null]);

// Display alerts
if (isset($_SESSION['alert'])) {
    echo get_admin_alert($_SESSION['alert']['type'], $_SESSION['alert']['message']);
    unset($_SESSION['alert']);
}
?>

<div class="row g-4">
    <!-- Statistics Cards -->
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Total Users</h6>
                        <h2 class="mt-2 mb-0"><?php echo number_format($stats['users']); ?></h2>
                    </div>
                    <i class="fas fa-users fa-2x opacity-50"></i>
                </div>
                <div class="mt-3">
                    <small>
                        <i class="fas fa-clock me-1"></i>
                        <?php echo $stats['pending_users']; ?> pending
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Total Posts</h6>
                        <h2 class="mt-2 mb-0"><?php echo number_format($stats['posts']); ?></h2>
                    </div>
                    <i class="fas fa-file-alt fa-2x opacity-50"></i>
                </div>
                <div class="mt-3">
                    <small>
                        <i class="fas fa-comments me-1"></i>
                        <?php echo number_format($stats['comments']); ?> comments
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Categories</h6>
                        <h2 class="mt-2 mb-0"><?php echo number_format($stats['categories']); ?></h2>
                    </div>
                    <i class="fas fa-folder fa-2x opacity-50"></i>
                </div>
                <div class="mt-3">
                    <small>
                        <i class="fas fa-tags me-1"></i>
                        <?php echo number_format($stats['tags']); ?> tags
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Reports</h6>
                        <h2 class="mt-2 mb-0"><?php echo number_format($stats['reported_content']); ?></h2>
                    </div>
                    <i class="fas fa-flag fa-2x opacity-50"></i>
                </div>
                <div class="mt-3">
                    <small>
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Need attention
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Activity</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['recent_activities'] as $activity): ?>
                                <tr>
                                    <td>
                                        <a href="/profile.php?id=<?php echo $activity['user_id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($activity['username']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo get_activity_badge_color($activity['action']); ?>">
                                            <?php echo format_activity_action($activity['action']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($activity['details']); ?></td>
                                    <td><?php echo time_ago($activity['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="/admin/users.php?filter=pending" class="btn btn-outline-primary">
                        <i class="fas fa-user-check me-2"></i> Review Pending Users
                    </a>
                    <a href="/admin/reports.php" class="btn btn-outline-warning">
                        <i class="fas fa-flag me-2"></i> Handle Reports
                    </a>
                    <a href="/admin/settings.php" class="btn btn-outline-secondary">
                        <i class="fas fa-cog me-2"></i> System Settings
                    </a>
                    <a href="/admin/backup.php" class="btn btn-outline-info">
                        <i class="fas fa-database me-2"></i> Backup System
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize charts if needed
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

<?php
// Get page footer
echo get_admin_footer();
?>