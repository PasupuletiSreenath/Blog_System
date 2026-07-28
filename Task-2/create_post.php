<?php
require_once 'includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if ($title === "" || $content === "") {
        $error = "Please fill in both title and content.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO posts (title, content) VALUES (?, ?)");
        $stmt->execute([$title, $content]);

        header("Location: dashboard.php");
        exit;
    }
}

require_once 'includes/header.php';
?>

<h2>Create New Post</h2>

<?php if ($error): ?>
    <p class="alert alert-error"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<form method="POST" action="create_post.php" class="form-box">
    <label>Title</label>
    <input type="text" name="title" required>

    <label>Content</label>
    <textarea name="content" rows="6" required></textarea>

    <button type="submit">Publish Post</button>
</form>

<?php require_once 'includes/footer.php'; ?>
