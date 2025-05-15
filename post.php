<?php
// Add at the top of the file
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

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

include 'layouts/header.php';
?>

<div class="container mt-4">
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['action']) && $_GET['action'] == 'new'): ?>
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Create New Post</h2>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach($errors as $err): ?>
                                <li><?php echo htmlspecialchars($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <!-- Inside the post creation form -->
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group mb-3">
                        <label class="form-label"><i class="fas fa-heading me-1"></i> Title</label>
                        <input type="text" name="title" class="form-control form-control-lg" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label"><i class="fas fa-align-left me-1"></i> Content</label>
                        <textarea name="content" id="editor" class="form-control" rows="10"></textarea>
                    </div>
                    <!-- Add this inside the post creation form -->
                    <div class="form-group mb-3">
                        <label class="form-label"><i class="fas fa-folder me-1"></i> Category</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php
                            $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
                            foreach($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label class="form-label"><i class="fas fa-tags me-1"></i> Tags (comma separated)</label>
                        <input type="text" name="tags" class="form-control" placeholder="news, technology, tutorial">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label"><i class="fas fa-paperclip me-1"></i> Attachments</label>
                        <input type="file" name="attachments[]" class="form-control" multiple>
                        <small class="text-muted">Allowed files: JPG, PNG, GIF, PDF, DOC, DOCX (Max 5MB each)</small>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="index.php" class="btn btn-secondary"><i class="fas fa-times me-1"></i> Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Create Post</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Initialize TinyMCE editor -->
        <script src="https://cdn.tiny.cloud/1/xj1pomo1mrpu7fz9gus1zulblwty6ajfd4c76gtbmsx5fhwn/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
        <script>
            tinymce.init({
                selector: '#editor',
                plugins: 'link image code',
                toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | link image | code',
                height: 400,
                referrer_policy: 'origin',
                setup: function(editor) {
                    editor.on('change', function() {
                        editor.save(); // Save content to textarea
                    });
                },
                required: true
            });
        </script>
        
    <?php elseif (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($edit_post)): ?>
        <h2>Edit Post</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group mb-3">
                <label>Title</label>
                <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($edit_post['title']); ?>">
            </div>
            <div class="form-group mb-3">
                <label>Content</label>
                <textarea name="content" id="editor" class="form-control" rows="10"><?php echo htmlspecialchars($edit_post['content']); ?></textarea>
            </div>
            <div class="form-group mb-3">
                <label>Category</label>
                <select name="category_id" class="form-control" required>
                    <option value="">Select Category</option>
                    <?php
                    $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
                    foreach($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo ($category['id'] == $edit_post['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-3">
                <label>Tags (comma separated)</label>
                <input type="text" name="tags" class="form-control" value="<?php echo htmlspecialchars($edit_tags_string); ?>">
            </div>
            
            <!-- Display existing attachments -->
            <?php
            $stmt = $pdo->prepare("SELECT * FROM attachments WHERE post_id = ?");
            $stmt->execute([$edit_post['id']]);
            $existing_attachments = $stmt->fetchAll();
            
            if ($existing_attachments): ?>
                <div class="form-group mb-3">
                    <label>Existing Attachments</label>
                    <div class="list-group">
                        <?php foreach($existing_attachments as $attachment): ?>
                            <div class="list-group-item">
                                <?php echo htmlspecialchars($attachment['original_filename']); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="form-group mb-3">
                <label>Add New Attachments</label>
                <input type="file" name="attachments[]" class="form-control" multiple>
                <small class="text-muted">Allowed files: JPG, PNG, GIF, PDF, DOC, DOCX (Max 5MB each)</small>
            </div>
            
            <input type="hidden" name="update_post" value="1">
            <button type="submit" class="btn btn-primary">Update Post</button>
            <a href="post.php?id=<?php echo $edit_post['id']; ?>" class="btn btn-secondary">Cancel</a>
        </form>
        
        <!-- Initialize TinyMCE editor for edit form -->
        <script src="https://cdn.tiny.cloud/1/xj1pomo1mrpu7fz9gus1zulblwty6ajfd4c76gtbmsx5fhwn/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
        <script>
            tinymce.init({
                selector: '#editor',
                plugins: 'link image code',
                toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | link image | code',
                height: 400,
                referrer_policy: 'origin',
                setup: function(editor) {
                    editor.on('change', function() {
                        editor.save(); // Save content to textarea
                    });
                }
            });
        </script>
        
    <?php elseif (isset($post)): ?>
        <div class="post-container bg-white p-4 rounded shadow-sm mb-4">
            <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
            <div class="post-meta d-flex align-items-center mb-4">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($post['username']); ?>&background=random" class="rounded-circle me-2" width="32" height="32" alt="User avatar">
                <span>
                    <strong><?php echo htmlspecialchars($post['username']); ?></strong> • 
                    <span class="text-muted"><?php echo date('F j, Y', strtotime($post['created_at'])); ?></span>
                </span>
            </div>
            
            <div class="post-categories-tags mb-4">
                <?php if (!empty($post['category_name'])): ?>
                    <div class="category mb-2">
                        <span class="badge bg-primary"><i class="fas fa-folder me-1"></i> <?php echo htmlspecialchars($post['category_name']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($tags)): ?>
                    <div class="tags">
                        <?php foreach ($tags as $tag): ?>
                            <span class="badge bg-secondary"><i class="fas fa-tag me-1"></i> <?php echo htmlspecialchars($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="post-content mb-4">
                <?php echo $post['content']; ?>
            </div>
            
            <?php
            // Display attachments
            $stmt = $pdo->prepare("SELECT * FROM attachments WHERE post_id = ?");
            $stmt->execute([$post['id']]);
            $attachments = $stmt->fetchAll();
            
            if ($attachments): ?>
                <div class="attachments mt-4 p-3 border rounded bg-light">
                    <h4><i class="fas fa-paperclip me-2"></i>Attachments</h4>
                    <div class="row g-3 mt-2">
                        <?php foreach($attachments as $attachment): ?>
                            <?php
                            $is_image = strpos($attachment['file_type'], 'image/') === 0;
                            $file_url = 'uploads/' . $attachment['filename']; // Remove the leading slash
                            ?>
                            <?php if ($is_image): ?>
                                <div class="col-md-4 col-sm-6">
                                    <div class="card h-100">
                                        <a href="<?php echo $file_url; ?>" target="_blank" class="image-popup">
                                            <img src="<?php echo $file_url; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($attachment['original_filename']); ?>">
                                        </a>
                                        <div class="card-footer text-center">
                                            <small><?php echo htmlspecialchars($attachment['original_filename']); ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="col-md-4 col-sm-6">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-file fa-3x mb-3 text-secondary"></i>
                                            <p class="card-text"><?php echo htmlspecialchars($attachment['original_filename']); ?></p>
                                        </div>
                                        <div class="card-footer text-center">
                                            <a href="<?php echo $file_url; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                <i class="fas fa-download me-1"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
            <div class="post-actions mb-4 d-flex gap-2">
                <a href="post.php?action=edit&id=<?php echo $post['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-edit me-1"></i> Edit Post
                </a>
                <button onclick="confirmDelete(<?php echo $post['id']; ?>)" class="btn btn-danger">
                    <i class="fas fa-trash-alt me-1"></i> Delete Post
                </button>
            </div>
            
            <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Deletion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete this post? This action cannot be undone.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
                function confirmDelete(postId) {
                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                    document.getElementById('confirmDeleteBtn').href = 'post.php?action=delete&id=' + postId;
                    deleteModal.show();
                }
            </script>
        <?php endif; ?>
        
        <!-- Comments section with enhanced styling -->
        <div class="comments-section mt-5 bg-white p-4 rounded shadow-sm">
            <h3><i class="fas fa-comments me-2"></i>Comments</h3>
            
            <!-- Add comment form -->
            <form method="POST" action="add_comment.php" class="mb-4 mt-3">
                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                <div class="form-group mb-3">
                    <textarea name="content" class="form-control" rows="3" required placeholder="Write your comment..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-1"></i> Add Comment
                </button>
            </form>
            
            <!-- Display comments -->
            <?php
            $stmt = $pdo->prepare("SELECT comments.*, users.username 
                                  FROM comments 
                                  JOIN users ON comments.user_id = users.id 
                                  WHERE post_id = ? 
                                  ORDER BY created_at DESC");
            $stmt->execute([$post['id']]);
            $comments = $stmt->fetchAll();
            
            if ($comments): ?>
                <div class="comments-list mt-4">
                    <?php foreach($comments as $comment): ?>
                        <div class="comment mb-3 p-3 border-start border-4 rounded" style="border-color: var(--primary-color) !important;">
                            <div class="comment-meta d-flex align-items-center mb-2">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($comment['username']); ?>&background=random" class="rounded-circle me-2" width="24" height="24" alt="User avatar">
                                <strong><?php echo htmlspecialchars($comment['username']); ?></strong> 
                                <span class="mx-2">•</span>
                                <span class="text-muted"><i class="far fa-clock me-1"></i><?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?></span>
                            </div>
                            <div class="comment-content">
                                <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center p-4 mt-3 bg-light rounded">
                    <i class="far fa-comment-dots fa-3x mb-3 text-muted"></i>
                    <p>No comments yet. Be the first to comment!</p>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">No post selected. <a href="index.php">Return to homepage</a></div>
    <?php endif; ?>
</div>

<?php include 'layouts/footer.php'; ?>