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


// Inside the post creation handling section
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
        } // Inside the catch block
        catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Error creating post: " . $e->getMessage();
            error_log("Database error: " . $e->getMessage());
        }
    }
}


// Handle viewing single post
if (isset($_GET['id'])) {
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
}

include 'layouts/header.php';
?>

<div class="container mt-4">
    <?php if (isset($_GET['action']) && $_GET['action'] == 'new'): ?>
        <h2>Create New Post</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Inside the post creation form -->
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group mb-3">
                <label>Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="form-group mb-3">
                <label>Content</label>
                <textarea name="content" id="editor" class="form-control" rows="10"></textarea>
            </div>
            <!-- Add this inside the post creation form -->
            <div class="form-group mb-3">
                <label>Category</label>
                <select name="category_id" class="form-control" required>
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
                <label>Tags (comma separated)</label>
                <input type="text" name="tags" class="form-control" placeholder="news, technology, tutorial">
            </div>
            <div class="form-group mb-3">
                <label>Attachments</label>
                <input type="file" name="attachments[]" class="form-control" multiple>
                <small class="text-muted">Allowed files: JPG, PNG, GIF, PDF, DOC, DOCX (Max 5MB each)</small>
            </div>
            <button type="submit" class="btn btn-primary">Create Post</button>
        </form>
        
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
        
    <?php elseif (isset($post)): ?>
        <h1><?php echo htmlspecialchars($post['title']); ?></h1>
        <div class="text-muted mb-4">
            Posted by <?php echo htmlspecialchars($post['username']); ?> on 
            <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
        </div>
        
        <!-- Inside the post viewing section -->
        <div class="post-content mb-4">
            <?php if (!empty($post['category_name'])): ?>
                <div class="category mb-2">
                    <span class="badge bg-secondary"><?php echo htmlspecialchars($post['category_name']); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($tags)): ?>
                <div class="tags mb-2">
                    <?php foreach ($tags as $tag): ?>
                        <span class="badge bg-info"><?php echo htmlspecialchars($tag); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php echo $post['content']; ?>
            
            <?php
            // Display attachments
            $stmt = $pdo->prepare("SELECT * FROM attachments WHERE post_id = ?");
            $stmt->execute([$post['id']]);
            $attachments = $stmt->fetchAll();
            
            if ($attachments): ?>
                <div class="attachments mt-4">
                    <h4>Attachments</h4>
                    <div class="list-group">
                        <?php foreach($attachments as $attachment): ?>
                            <?php
                            $is_image = strpos($attachment['file_type'], 'image/') === 0;
                            $file_url = 'uploads/' . $attachment['filename']; // Remove the leading slash
                            ?>
                            <?php if ($is_image): ?>
                                <div class="mb-3">
                                    <img src="<?php echo $file_url; ?>" class="img-fluid" alt="<?php echo htmlspecialchars($attachment['original_filename']); ?>">
                                </div>
                            <?php else: ?>
                                <a href="<?php echo $file_url; ?>" class="list-group-item list-group-item-action" target="_blank">
                                    <i class="fas fa-file"></i> <?php echo htmlspecialchars($attachment['original_filename']); ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
            <div class="mb-4">
                <a href="post.php?action=edit&id=<?php echo $post['id']; ?>" class="btn btn-primary">Edit Post</a>
                <a href="post.php?action=delete&id=<?php echo $post['id']; ?>" class="btn btn-danger">Delete Post</a>
            </div>
        <?php endif; ?>
        
        <!-- Comments section -->
        <!-- Inside the comments-section div -->
        <div class="comments-section mt-5">
            <h3>Comments</h3>
            
            <!-- Add comment form -->
            <form method="POST" action="add_comment.php" class="mb-4">
                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                <div class="form-group mb-3">
                    <textarea name="content" class="form-control" rows="3" required placeholder="Write your comment..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Add Comment</button>
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
                <?php foreach($comments as $comment): ?>
                    <div class="comment">
                        <div class="comment-meta">
                            <strong><?php echo htmlspecialchars($comment['username']); ?></strong> • 
                            <span><?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?></span>
                        </div>
                        <div class="comment-content">
                            <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No comments yet. Be the first to comment!</p>
            <?php endif; ?>
        </div>
    // Add this after the existing POST handling section and before the view post section
    // Handle edit post action
    if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
        // Get post data for editing
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
        $edit_post = $stmt->fetch();
        
        if (!$edit_post) {
            // Either post doesn't exist or doesn't belong to current user
            header("Location: index.php");
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
}
</div>

<?php include 'layouts/footer.php'; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php elseif (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($edit_post)): ?>
    <h2>Edit Post</h2>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
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
                    <option value="<?php echo $category['id']; ?>" <?php echo ($edit_post['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group mb-3">
            <label>Tags (comma separated)</label>
            <input type="text" name="tags" class="form-control" placeholder="news, technology, tutorial" value="<?php echo htmlspecialchars($edit_tags_string); ?>">
        </div>
        
        <div class="form-group mb-3">
            <label>Current Attachments</label>
            <?php
            $stmt = $pdo->prepare("SELECT * FROM attachments WHERE post_id = ?");
            $stmt->execute([$edit_post['id']]);
            $current_attachments = $stmt->fetchAll();
            
            if ($current_attachments): ?>
                <div class="list-group mb-3">
                    <?php foreach($current_attachments as $attachment): ?>
                        <div class="list-group-item">
                            <?php echo htmlspecialchars($attachment['original_filename']); ?>
                            <!-- You can add delete attachment functionality here -->
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No attachments</p>
            <?php endif; ?>
        </div>
        
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
            },
            required: true
        });
    </script>
<?php endif; ?>