<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$error = "";
$csrfToken = generateCsrfToken();
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$postId) {
    header("Location: dashboard.php");
    exit;
}

// Fetch post and check ownership
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header("Location: dashboard.php");
    exit;
}

if ($_SESSION['role'] !== 'admin' && $post['user_id'] !== $_SESSION['user_id']) {
    die("Access denied: You don't own this post.");
}

// Fetch categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch current tags
$stmt = $pdo->prepare("SELECT t.name FROM tags t JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ?");
$stmt->execute([$postId]);
$currentTags = implode(', ', $stmt->fetchAll(PDO::FETCH_COLUMN));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid form submission.";
    } else {
        $title = sanitizeInput($_POST['title'] ?? '');
        $content = $_POST['content'] ?? '';
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $tagsInput = sanitizeInput($_POST['tags'] ?? '');
        
        if ($title === "" || trim(strip_tags($content)) === "") {
            $error = "Title and content are required.";
        } else {
            // Handle optional new image upload
            $imageName = $post['image'];
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
                    
                    // Update post
                    $stmt = $pdo->prepare("UPDATE posts SET category_id = ?, title = ?, content = ?, image = ? WHERE id = ?");
                    $stmt->execute([$categoryId, $title, $content, $imageName, $postId]);
                    
                    // Update Tags (Clear existing, add new)
                    $pdo->prepare("DELETE FROM post_tags WHERE post_id = ?")->execute([$postId]);
                    if (!empty($tagsInput)) {
                        $tags = array_unique(array_map('trim', explode(',', $tagsInput)));
                        foreach ($tags as $tagName) {
                            if (empty($tagName)) continue;
                            
                            $tagStmt = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
                            $tagStmt->execute([$tagName]);
                            $tagId = $tagStmt->fetchColumn();
                            
                            if (!$tagId) {
                                $pdo->prepare("INSERT INTO tags (name) VALUES (?)")->execute([$tagName]);
                                $tagId = $pdo->lastInsertId();
                            }
                            
                            $pdo->prepare("INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)")->execute([$postId, $tagId]);
                        }
                    }
                    
                    $pdo->commit();
                    $_SESSION['flash_success'] = "Post updated successfully!";
                    header("Location: dashboard.php");
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "An error occurred while updating the post.";
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
            <h3 class="fw-bold mb-4 text-primary"><i class="bi bi-pencil-square me-2"></i> Edit Post</h3>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="edit_post.php?id=<?php echo $postId; ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Title</label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($title ?? $post['title']); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Uncategorized --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($categoryId ?? $post['category_id']) == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Update Featured Image (Leave blank to keep current)</label>
                        <input type="file" name="image" class="form-control" accept="image/jpeg, image/png, image/jpg">
                        <?php if ($post['image']): ?>
                            <div class="mt-2 text-muted small">Current: <a href="assets/uploads/images/<?php echo htmlspecialchars($post['image']); ?>" target="_blank">View Image</a></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tags (comma separated)</label>
                        <input type="text" name="tags" class="form-control" value="<?php echo htmlspecialchars($tagsInput ?? $currentTags); ?>">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-semibold">Content</label>
                    <textarea id="content" name="content" class="form-control" rows="15"><?php echo htmlspecialchars($content ?? $post['content']); ?></textarea>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold"><i class="bi bi-save-fill me-1"></i> Save Changes</button>
                    <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
