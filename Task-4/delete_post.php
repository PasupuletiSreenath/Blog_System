<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

// Only accept deletes via POST (not a plain GET link) - safer against CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_error'] = "Invalid form submission. Please try again.";
    header("Location: dashboard.php");
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

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
    $_SESSION['flash_error'] = "You do not have permission to delete this post.";
    header("Location: dashboard.php");
    exit;
}

// Delete image file if it exists
if (!empty($post['image']) && file_exists('assets/images/' . $post['image'])) {
    unlink('assets/images/' . $post['image']);
}

$stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
$stmt->execute([$id]);

$_SESSION['flash_success'] = "Post deleted successfully!";
header("Location: dashboard.php");
exit;
