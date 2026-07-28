<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$error = "";
$csrfToken = generateCsrfToken();

// Fetch categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid form submission.";
    } else {
        $title = sanitizeInput($_POST['title'] ?? '');
        $content = $_POST['content'] ?? ''; // TinyMCE handles HTML, so we don't strictly strip tags, but we should purify it in a real app. We will allow it for now.
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $tagsInput = sanitizeInput($_POST['tags'] ?? '');
        $imageName = null;

        if ($title === "" || trim(strip_tags($content)) === "") {
            $error = "Title and content are required.";
        } else {
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploaded = uploadImage($_FILES['image'], 'assets/uploads/images/');
                if ($uploaded) {
                    $imageName = $uploaded;
                } else {
                    $error = isset($_SESSION['flash_error']) ? $_SESSION['flash_error'] : "Image upload failed.";
                    unset($_SESSION['flash_error']);
                }
            }
            
            if (!$error) {
                try {
                    $pdo->beginTransaction();
                    
                    // Insert post
                    $stmt = $pdo->prepare("INSERT INTO posts (user_id, category_id, title, content, image) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $categoryId, $title, $content, $imageName]);
                    $postId = $pdo->lastInsertId();
                    
                    // Process Tags
                    if (!empty($tagsInput)) {
                        $tags = array_unique(array_map('trim', explode(',', $tagsInput)));
                        foreach ($tags as $tagName) {
                            if (empty($tagName)) continue;
                            
                            // Check if tag exists
                            $tagStmt = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
                            $tagStmt->execute([$tagName]);
                            $tagId = $tagStmt->fetchColumn();
                            
                            if (!$tagId) {
                                $pdo->prepare("INSERT INTO tags (name) VALUES (?)")->execute([$tagName]);
                                $tagId = $pdo->lastInsertId();
                            }
                            
                            // Link tag to post
                            $pdo->prepare("INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)")->execute([$postId, $tagId]);
                        }
                    }
                    
                    $pdo->commit();
                    $_SESSION['flash_success'] = "Post created successfully!";
                    header("Location: dashboard.php");
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "An error occurred while saving the post.";
                }
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="glass-card p-4 p-md-5">
            <h3 class="fw-bold mb-4 text-primary"><i class="bi bi-pencil-square me-2"></i> Create New Post</h3>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="create_post.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Title</label>
                        <input type="text" name="title" class="form-control" placeholder="Post Title" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Uncategorized --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Featured Image</label>
                        <input type="file" name="image" class="form-control" accept="image/jpeg, image/png, image/jpg">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tags (comma separated)</label>
                        <input type="text" name="tags" class="form-control" placeholder="e.g. php, web, tutorial">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-semibold">Content</label>
                    <textarea id="content" name="content" class="form-control" rows="15"></textarea>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold"><i class="bi bi-send-fill me-1"></i> Publish Post</button>
                    <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
