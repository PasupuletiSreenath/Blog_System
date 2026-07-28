<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$error = "";
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid form submission. Please try again.";
    } else {
        $title = sanitizeInput($_POST['title'] ?? '');
        $content = sanitizeInput($_POST['content'] ?? '');
        $imageName = null;

        if ($title === "" || $content === "") {
            $error = "Please fill in both title and content.";
        } elseif (strlen($title) > 255) {
            $error = "Title is too long (max 255 characters).";
        } else {
            // Handle optional image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $fileInfo = pathinfo($_FILES['image']['name']);
                $ext = strtolower($fileInfo['extension']);
                
                if (!in_array($ext, $allowedExtensions)) {
                    $error = "Invalid image format. Allowed: JPG, PNG, GIF, WEBP.";
                } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) { // 2MB max
                    $error = "Image must be less than 2MB.";
                } else {
                    $imageName = uniqid('post_') . '.' . $ext;
                    $targetPath = 'assets/images/' . $imageName;
                    
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                        $error = "Failed to save the uploaded image.";
                        $imageName = null;
                    }
                }
            }
            
            if (!$error) {
                $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, image) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $title, $content, $imageName]);

                $_SESSION['flash_success'] = "Entry published successfully!";
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
                        <h3 class="card-title fw-bold mb-1">New Entry</h3>
                        <p class="text-muted mb-0">Write it down while it's fresh.</p>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="create_post.php" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Title</label>
                        <input type="text" name="title" class="form-control form-control-lg" maxlength="255"
                               placeholder="e.g. My amazing day"
                               value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" required autofocus>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Cover Image (Optional)</label>
                        <input class="form-control" type="file" name="image" accept="image/jpeg, image/png, image/gif, image/webp">
                        <div class="form-text">Max 2MB. JPG, PNG, GIF, or WEBP.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Content</label>
                        <textarea name="content" class="form-control" rows="10" placeholder="Start writing here..." required><?php echo isset($content) ? htmlspecialchars($content) : ''; ?></textarea>
                    </div>
                    
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-bold"><i class="bi bi-send-fill me-2"></i> Publish Entry</button>
                        <a href="dashboard.php" class="btn btn-outline-secondary px-4 py-2 fw-bold">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
