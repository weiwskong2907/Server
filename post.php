<?php
// Add at the top of the file
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/layout.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';
$errors = [];

// Handle delete post action
// Handle delete post action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    // First, get the post details
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $post = $stmt->fetch();
    
    // Check if post exists
    if ($post) {
        // Check if current user is the owner
        if ($post['user_id'] == $_SESSION['user_id']) {
            // Check if this is the A002 user trying to delete A001's post
            if ($_SESSION['username'] == 'A002' && getUsernameById($pdo, $post['user_id']) == 'A001') {
                $error = "You don't have permission to delete posts created by A001";
            } else {
                $pdo->beginTransaction();
                try {
                    // Delete post tags
                    $stmt = $pdo->prepare("DELETE FROM post_tags WHERE post_id = ?");
                    $stmt->execute([$_GET['id']]);
                    
                    // Delete comments
                    $stmt = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
                    $stmt->execute([$_GET['id']]);
                    
                    // Get attachments to delete files
                    $stmt = $pdo->prepare("SELECT filename FROM attachments WHERE post_id = ?");
                    $stmt->execute([$_GET['id']]);
                    $attachments = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    // Delete attachment records
                    $stmt = $pdo->prepare("DELETE FROM attachments WHERE post_id = ?");
                    $stmt->execute([$_GET['id']]);
                    
                    // Delete the post
                    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
                    $stmt->execute([$_GET['id']]);
                    
                    $pdo->commit();
                    
                    // Delete attachment files
                    foreach ($attachments as $filename) {
                        $file_path = __DIR__ . '/uploads/' . $filename;
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                    }
                    
                    header("Location: index.php?success=post_deleted");
                    exit();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Error deleting post: " . $e->getMessage();
                    error_log("Database error: " . $e->getMessage());
                }
            }
        } else {
            $error = "You don't have permission to delete this post";
        }
    } else {
        header("Location: index.php");
        exit();
    }
}

// Handle edit post action
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    // Get post data for editing
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $edit_post = $stmt->fetch();
    
    if (!$edit_post) {
        // Post doesn't exist
        header("Location: index.php");
        exit();
    }
    
    // Check if current user is the owner
    if ($edit_post['user_id'] != $_SESSION['user_id']) {
        // Not the owner
        header("Location: index.php?error=not_authorized");
        exit();
    }
    
    // Check if this is A002 trying to edit A001's post
    if ($_SESSION['username'] == 'A002' && getUsernameById($pdo, $edit_post['user_id']) == 'A001') {
        header("Location: index.php?error=not_authorized_a002");
        exit();
    }
    
    // Get post tags for editing
    $tag_stmt = $pdo->prepare("SELECT tags.name 
                            FROM tags 
                            JOIN post_tags ON tags.id = post_tags.tag_id 
                            WHERE post_tags.post_id = ?");
    $tag_stmt->execute([$_GET['id']]);
    $edit_tags = $tag_stmt->fetchAll(PDO::FETCH_COLUMN);
    $edit_tags_string = implode(', ', $edit_tags);
    
    // Handle post update submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_post'])) {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $errors = [];
        
        if (empty($title) || empty($content)) {
            $errors[] = "Both title and content are required";
        }
        
        if (empty($_POST['category_id'])) {
            $errors[] = "Category is required";
        }
        
        // Handle file uploads for edit
        $attachments = [];
        if (!empty($_FILES['attachments']['name'][0])) {
            foreach ($_FILES['attachments']['name'] as $key => $name) {
                $file = [
                    'name' => $_FILES['attachments']['name'][$key],
                    'type' => $_FILES['attachments']['type'][$key],
                    'tmp_name' => $_FILES['attachments']['tmp_name'][$key],
                    'error' => $_FILES['attachments']['error'][$key],
                    'size' => $_FILES['attachments']['size'][$key]
                ];
                
                $file_errors = validate_file_upload($file);
                if (empty($file_errors)) {
                    $filename = save_uploaded_file($file);
                    if ($filename) {
                        $attachments[] = [
                            'filename' => $filename,
                            'original_filename' => $file['name'],
                            'file_type' => $file['type']
                        ];
                    } else {
                        $errors[] = "Error uploading file: " . $file['name'];
                    }
                } else {
                    $errors = array_merge($errors, $file_errors);
                }
            }
        }
        
        if (empty($errors)) {
            $pdo->beginTransaction();
            try {
                // Update the post
                $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, category_id = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$title, $content, $_POST['category_id'], $_GET['id'], $_SESSION['user_id']]);
                
                // Handle tags - first delete existing tags
                $stmt = $pdo->prepare("DELETE FROM post_tags WHERE post_id = ?");
                $stmt->execute([$_GET['id']]);
                
                // Then add new tags
                if (!empty($_POST['tags'])) {
                    $tags = array_map('trim', explode(',', $_POST['tags']));
                    $stmt = $pdo->prepare("INSERT INTO tags (name) VALUES (?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
                    $tag_stmt = $pdo->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                    
                    foreach ($tags as $tag) {
                        if (!empty($tag)) {
                            $stmt->execute([$tag]);
                            $tag_id = $pdo->lastInsertId();
                            $tag_stmt->execute([$_GET['id'], $tag_id]);
                        }
                    }
                }
                
                // Save new attachments
                if (!empty($attachments)) {
                    $stmt = $pdo->prepare("INSERT INTO attachments (post_id, filename, original_filename, file_type) VALUES (?, ?, ?, ?)");
                    foreach ($attachments as $attachment) {
                        $result = $stmt->execute([
                            $_GET['id'],
                            $attachment['filename'],
                            $attachment['original_filename'],
                            $attachment['file_type']
                        ]);
                        if (!$result) {
                            throw new Exception("Failed to save attachment: " . $attachment['original_filename']);
                        }
                    }
                }
                
                $pdo->commit();
                header("Location: post.php?id=" . $_GET['id'] . "&success=updated");
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = "Error updating post: " . $e->getMessage();
                error_log("Database error: " . $e->getMessage());
            }
        }
    }
}

// Inside the post creation handling section
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['update_post'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $errors = [];
    
    // Debug the submitted values
    error_log("Submitted title: " . $title);
    error_log("Submitted content: " . $content);
    error_log("Submitted category: " . (isset($_POST['category_id']) ? $_POST['category_id'] : 'not set'));
    
    if (empty($title) || empty($content)) {
        $errors[] = "Both title and content are required";
    }
    
    if (empty($_POST['category_id'])) {
        $errors[] = "Category is required";
    }
    
    // Handle file uploads
    $attachments = [];
    // Inside the file upload section
    if (!empty($_FILES['attachments']['name'][0])) {
        error_log("Starting file upload process");
        foreach ($_FILES['attachments']['name'] as $key => $name) {
            error_log("Processing file: " . $name);
            $file = [
                'name' => $_FILES['attachments']['name'][$key],
                'type' => $_FILES['attachments']['type'][$key],
                'tmp_name' => $_FILES['attachments']['tmp_name'][$key],
                'error' => $_FILES['attachments']['error'][$key],
                'size' => $_FILES['attachments']['size'][$key]
            ];
            
            $file_errors = validate_file_upload($file);
            if (empty($file_errors)) {
                $filename = save_uploaded_file($file);
                if ($filename) {
                    $attachments[] = [
                        'filename' => $filename,
                        'original_filename' => $file['name'],
                        'file_type' => $file['type']
                    ];
                } else {
                    $errors[] = "Error uploading file: " . $file['name'];
                }
            } else {
                $errors = array_merge($errors, $file_errors);
            }
        }
    }
    
    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            // Create the post with category_id
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, category_id) VALUES (?, ?, ?, ?)");
            // Remove the slug generation code
            // Debug the submitted values
            error_log("Inserting post with title: " . $title);
            $stmt->execute([$_SESSION['user_id'], $title, $content, $_POST['category_id']]);
            $post_id = $pdo->lastInsertId();
            
            // Handle tags
            if (!empty($_POST['tags'])) {
                $tags = array_map('trim', explode(',', $_POST['tags']));
                $stmt = $pdo->prepare("INSERT INTO tags (name) VALUES (?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
                $tag_stmt = $pdo->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                
                foreach ($tags as $tag) {
                    if (!empty($tag)) {
                        $stmt->execute([$tag]);
                        $tag_id = $pdo->lastInsertId();
                        $tag_stmt->execute([$post_id, $tag_id]);
                    }
                }
            }
            
            // Save attachments
            if (!empty($attachments)) {
                $stmt = $pdo->prepare("INSERT INTO attachments (post_id, filename, original_filename, file_type) VALUES (?, ?, ?, ?)");
                foreach ($attachments as $attachment) {
                    $result = $stmt->execute([
                        $post_id,
                        $attachment['filename'],
                        $attachment['original_filename'],
                        $attachment['file_type']
                    ]);
                    // Add error checking
                    if (!$result) {
                        throw new Exception("Failed to save attachment: " . $attachment['original_filename']);
                    }
                }
            }
            
            $pdo->commit();
            // Change this line to redirect to homepage with success message
            header("Location: index.php?success=post_created");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Error creating post: " . $e->getMessage();
            error_log("Database error: " . $e->getMessage());
        }
    }
}

// Handle viewing single post
if (isset($_GET['id']) && !isset($_GET['action'])) {
    $stmt = $pdo->prepare("SELECT posts.*, users.username, categories.name as category_name 
                          FROM posts 
                          JOIN users ON posts.user_id = users.id 
                          LEFT JOIN categories ON posts.category_id = categories.id 
                          WHERE posts.id = ?");
    $stmt->execute([$_GET['id']]);
    $post = $stmt->fetch();
    
    if (!$post) {
        header("Location: index.php");
        exit();
    }
    
    // Get post tags
    $tag_stmt = $pdo->prepare("SELECT tags.name 
                              FROM tags 
                              JOIN post_tags ON tags.id = post_tags.tag_id 
                              WHERE post_tags.post_id = ?");
    $tag_stmt->execute([$_GET['id']]);
    $tags = $tag_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Check for success message
    if (isset($_GET['success']) && $_GET['success'] == 'updated') {
        $success = "Post updated successfully!";
    }
}

// Get post ID
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get post
$post = get_post($post_id);
if (!$post) {
    header('Location: /');
    exit();
}

// Increment view count
increment_post_views($post_id);

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
    $content = $_POST['content'] ?? '';
    
    if (empty($content)) {
        $error = 'Please enter a comment';
    } else {
        $result = create_comment($post_id, $content);
        
        if ($result['success']) {
            $success = 'Comment added successfully';
        } else {
            $error = $result['message'];
        }
    }
}

// Get comments
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$comments = get_comments_paginated($post_id, $page);

// Get related posts
$related_posts = get_related_posts($post_id, 3);

// Get page header
echo get_page_header($post['title'], [
    'Posts' => '/',
    $post['category_name'] => "/category.php?id={$post['category_id']}",
    $post['title'] => null
]);

// Display messages
if ($error) {
    echo get_alert('danger', $error);
}
if ($success) {
    echo get_alert('success', $success);
}
?>

<div class="row">
    <div class="col-md-8">
        <article class="card mb-4">
            <div class="card-body">
                <h1 class="card-title h2 mb-3"><?php echo htmlspecialchars($post['title']); ?></h1>
                
                <div class="d-flex align-items-center mb-4">
                    <img src="<?php echo get_user_avatar($post['user_id']); ?>" 
                         class="rounded-circle me-2" width="32" height="32" alt="Avatar">
                    <div>
                        <a href="/profile.php?id=<?php echo $post['user_id']; ?>" class="text-decoration-none">
                            <?php echo htmlspecialchars($post['username']); ?>
                        </a>
                        <div class="text-muted small">
                            Posted in 
                            <a href="/category.php?id=<?php echo $post['category_id']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($post['category_name']); ?>
                            </a>
                            <?php echo time_ago($post['created_at']); ?>
                        </div>
                    </div>
                </div>
                
                <div class="post-content mb-4">
                    <?php echo format_content($post['content']); ?>
                </div>
                
                <?php if (!empty($post['tags'])): ?>
                    <div class="mb-4">
                        <?php foreach ($post['tags'] as $tag): ?>
                            <a href="/tag.php?id=<?php echo $tag['id']; ?>" class="btn btn-sm btn-outline-secondary me-2">
                                <?php echo htmlspecialchars($tag['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-between align-items-center">
                    <div class="btn-group">
                        <?php if (is_logged_in()): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="likePost(<?php echo $post_id; ?>)">
                                <i class="fas fa-heart"></i> Like
                                <span class="badge bg-primary ms-1"><?php echo $post['likes']; ?></span>
                            </button>
                        <?php endif; ?>
                        
                        <?php if (is_logged_in() && ($post['user_id'] === $_SESSION['user_id'] || is_admin())): ?>
                            <a href="/edit-post.php?id=<?php echo $post_id; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deletePost(<?php echo $post_id; ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="text-muted small">
                        <i class="fas fa-eye"></i> <?php echo $post['views']; ?> views
                        <i class="fas fa-comments ms-2"></i> <?php echo $post['comment_count']; ?> comments
                    </div>
                </div>
            </div>
        </article>
        
        <section class="card mb-4">
            <div class="card-header">
                <h2 class="h5 mb-0">Comments (<?php echo $post['comment_count']; ?>)</h2>
            </div>
            
            <div class="card-body">
                <?php if (is_logged_in()): ?>
                    <form method="POST" action="/post.php?id=<?php echo $post_id; ?>" class="mb-4">
                        <div class="mb-3">
                            <label for="content" class="form-label">Add a comment</label>
                            <textarea class="form-control" id="content" name="content" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Post Comment</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">
                        Please <a href="/login.php" class="alert-link">login</a> to post a comment.
                    </div>
                <?php endif; ?>
                
                <?php if (empty($comments['comments'])): ?>
                    <div class="text-center text-muted py-4">
                        No comments yet. Be the first to comment!
                    </div>
                <?php else: ?>
                    <?php foreach ($comments['comments'] as $comment): ?>
                        <div class="comment mb-4">
                            <div class="d-flex">
                                <img src="<?php echo get_user_avatar($comment['user_id']); ?>" 
                                     class="rounded-circle me-2" width="32" height="32" alt="Avatar">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div>
                                            <a href="/profile.php?id=<?php echo $comment['user_id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($comment['username']); ?>
                                            </a>
                                            <span class="text-muted small ms-2">
                                                <?php echo time_ago($comment['created_at']); ?>
                                            </span>
                                        </div>
                                        <?php if (is_logged_in() && ($comment['user_id'] === $_SESSION['user_id'] || is_admin())): ?>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-secondary" 
                                                        onclick="editComment(<?php echo $comment['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" 
                                                        onclick="deleteComment(<?php echo $comment['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="comment-content">
                                        <?php echo format_content($comment['content']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php echo get_pagination($page, $comments['pages'], "/post.php?id=$post_id&page=%d"); ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
    
    <div class="col-md-4">
        <?php if (!empty($related_posts)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">Related Posts</h2>
                </div>
                <div class="card-body">
                    <?php foreach ($related_posts as $related): ?>
                        <div class="mb-3">
                            <h3 class="h6 mb-1">
                                <a href="/post.php?id=<?php echo $related['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($related['title']); ?>
                                </a>
                            </h3>
                            <div class="text-muted small">
                                By <?php echo htmlspecialchars($related['username']); ?>
                                <?php echo time_ago($related['created_at']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function likePost(postId) {
    fetch('/api/like-post.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ post_id: postId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    });
}

function deletePost(postId) {
    if (confirm('Are you sure you want to delete this post?')) {
        fetch('/api/delete-post.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ post_id: postId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '/';
            } else {
                alert(data.message);
            }
        });
    }
}

function editComment(commentId) {
    const content = prompt('Edit your comment:');
    if (content !== null) {
        fetch('/api/edit-comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                comment_id: commentId,
                content: content
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        });
    }
}

function deleteComment(commentId) {
    if (confirm('Are you sure you want to delete this comment?')) {
        fetch('/api/delete-comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ comment_id: commentId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        });
    }
}
</script>

<?php
// Get page footer
echo get_page_footer();
?>