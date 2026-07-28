<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    $_SESSION['flash_error'] = "Post not found.";
    header("Location: dashboard.php");
    exit;
}

// ===== Ownership check =====
$isAdmin = $_SESSION['role'] === 'admin';
if (!$isAdmin && $post['user_id'] != $_SESSION['user_id']) {
    $_SESSION['flash_error'] = "You do not have permission to edit this post.";
    header("Location: dashboard.php");
    exit;
}

$error = "";
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid form submission. Please try again.";
    } else {
        $title = sanitizeInput($_POST['title'] ?? '');
        $content = sanitizeInput($_POST['content'] ?? '');
        $removeImage = isset($_POST['remove_image']);
        $imageName = $post['image']; // Default to keeping existing image

        if ($title === "" || $content === "") {
            $error = "Please fill in both title and content.";
        } elseif (strlen($title) > 255) {
            $error = "Title is too long (max 255 characters).";
        } else {
            // Check if removing existing image
            if ($removeImage && $imageName) {
                if (file_exists('assets/images/' . $imageName)) {
                    unlink('assets/images/' . $imageName);
                }
                $imageName = null;
            }

            // Handle optional new image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $fileInfo = pathinfo($_FILES['image']['name']);
                $ext = strtolower($fileInfo['extension']);
                
                if (!in_array($ext, $allowedExtensions)) {
                    $error = "Invalid image format. Allowed: JPG, PNG, GIF, WEBP.";
                } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) { // 2MB max
                    $error = "Image must be less than 2MB.";
                } else {
                    $newImageName = uniqid('post_') . '.' . $ext;
                    $targetPath = 'assets/images/' . $newImageName;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                        // Delete old image if it exists
                        if ($imageName && file_exists('assets/images/' . $imageName)) {
                            unlink('assets/images/' . $imageName);
                        }
                        $imageName = $newImageName;
                    } else {
                        $error = "Failed to save the uploaded image.";
                    }
                }
            }

            if (!$error) {
                $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, image = ? WHERE id = ?");
                $stmt->execute([$title, $content, $imageName, $id]);

                $_SESSION['flash_success'] = "Entry updated successfully!";
                header("Location: dashboard.php");
                exit;
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center py-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 bg-glass">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex align-items-center mb-4">
                    <div class="icon-box bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-3">
                        <i class="bi bi-pencil-square fs-4"></i>
                    </div>
                    <div>
                        <h3 class="card-title fw-bold mb-1">Edit Entry</h3>
                        <p class="text-muted mb-0">Update the details of your logbook entry.</p>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="edit_post.php?id=<?php echo $post['id']; ?>" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Title</label>
                        <input type="text" name="title" class="form-control form-control-lg" maxlength="255"
                               value="<?php echo htmlspecialchars($post['title']); ?>" required autofocus>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Cover Image</label>
                        <?php if (!empty($post['image'])): ?>
                            <div class="mb-3">
                                <img src="assets/images/<?php echo htmlspecialchars($post['image']); ?>" class="img-thumbnail" style="max-height: 150px; display: block;">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="remove_image" id="removeImage">
                                    <label class="form-check-label text-danger" for="removeImage">Remove this image</label>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <input class="form-control" type="file" name="image" accept="image/jpeg, image/png, image/gif, image/webp">
                        <div class="form-text">Max 2MB. JPG, PNG, GIF, or WEBP. Uploading a new image will replace the old one.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Content</label>
                        <textarea name="content" class="form-control" rows="10" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                    </div>
                    
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-bold"><i class="bi bi-check2-circle me-2"></i> Update Entry</button>
                        <a href="dashboard.php" class="btn btn-outline-secondary px-4 py-2 fw-bold">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
