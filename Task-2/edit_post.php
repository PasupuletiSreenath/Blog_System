<?php
require_once 'includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get post ID from the URL
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Fetch the post to edit
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    die("Post not found.");
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if ($title === "" || $content === "") {
        $error = "Please fill in both title and content.";
    } else {
        $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ? WHERE id = ?");
        $stmt->execute([$title, $content, $id]);

        header("Location: dashboard.php");
        exit;
    }
}

require_once 'includes/header.php';
?>

<h2>Edit Post</h2>

<?php if ($error): ?>
    <p class="alert alert-error"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<form method="POST" action="edit_post.php?id=<?php echo $post['id']; ?>" class="form-box">
    <label>Title</label>
    <input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>

    <label>Content</label>
    <textarea name="content" rows="6" required><?php echo htmlspecialchars($post['content']); ?></textarea>

    <button type="submit">Update Post</button>
</form>

<?php require_once 'includes/footer.php'; ?>
