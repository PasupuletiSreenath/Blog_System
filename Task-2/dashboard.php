<?php
require_once 'includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Protect this page - only logged-in users can view it
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch all posts, newest first
$stmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<h2>Dashboard</h2>
<p><a href="create_post.php" class="btn">+ New Post</a></p>

<?php if (count($posts) === 0): ?>
    <p>No posts yet. Create your first post!</p>
<?php else: ?>
    <div class="post-list">
        <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                <small>Posted on <?php echo date("d M Y, h:i A", strtotime($post['created_at'])); ?></small>
                <div class="post-actions">
                    <a href="edit_post.php?id=<?php echo $post['id']; ?>">Edit</a>
                    <a href="delete_post.php?id=<?php echo $post['id']; ?>" onclick="return confirm('Delete this post?');">Delete</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
