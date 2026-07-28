<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ===== Search =====
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// ===== Pagination =====
$postsPerPage = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $postsPerPage;

// ===== Get Posts =====
if ($search !== "") {

    // Count matching posts
    $countStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM posts
        WHERE title LIKE :title
        OR content LIKE :content
    ");

    $countStmt->bindValue(':title', "%$search%", PDO::PARAM_STR);
    $countStmt->bindValue(':content', "%$search%", PDO::PARAM_STR);
    $countStmt->execute();

    $totalPosts = (int)$countStmt->fetchColumn();

    // Fetch matching posts
    $stmt = $pdo->prepare("
        SELECT *
        FROM posts
        WHERE title LIKE :title
        OR content LIKE :content
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset
    ");

    $stmt->bindValue(':title', "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(':content', "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(':limit', $postsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();

} else {

    // Count all posts
    $totalPosts = (int)$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();

    // Fetch all posts
    $stmt = $pdo->prepare("
        SELECT *
        FROM posts
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset
    ");

    $stmt->bindValue(':limit', $postsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
}

$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPages = max(1, ceil($totalPosts / $postsPerPage));

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="mb-0">Dashboard</h2>
    <a href="create_post.php" class="btn btn-success">+ New Post</a>
</div>

<!-- Search Form -->
<form method="GET" action="dashboard.php" class="row g-2 mb-4">
    <div class="col-sm-9">
        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="Search posts by title or content..."
            value="<?php echo htmlspecialchars($search); ?>">
    </div>

    <div class="col-sm-3 d-grid">
        <button type="submit" class="btn btn-primary">
            Search
        </button>
    </div>
</form>

<?php if ($search !== ""): ?>
    <p>
        Showing results for
        <strong><?php echo htmlspecialchars($search); ?></strong>
        —
        <a href="dashboard.php">Clear Search</a>
    </p>
<?php endif; ?>

<?php if (count($posts) == 0): ?>

    <div class="alert alert-info">
        No posts found.
    </div>

<?php else: ?>

<div class="row row-cols-1 row-cols-md-2 g-4">

<?php foreach ($posts as $post): ?>

<div class="col">
    <div class="card h-100 shadow-sm">

        <div class="card-body">

            <h5 class="card-title">
                <?php echo highlightKeyword($post['title'], $search); ?>
            </h5>

            <p class="card-text">
                <?php echo nl2br(highlightKeyword($post['content'], $search)); ?>
            </p>

            <p class="text-muted small mb-0">
                Posted on
                <?php echo date("d M Y, h:i A", strtotime($post['created_at'])); ?>
            </p>

        </div>

        <div class="card-footer bg-white d-flex gap-2">

            <a href="edit_post.php?id=<?php echo $post['id']; ?>"
               class="btn btn-sm btn-outline-secondary">
                Edit
            </a>

            <a href="delete_post.php?id=<?php echo $post['id']; ?>"
               class="btn btn-sm btn-outline-danger"
               onclick="return confirm('Delete this post?');">
                Delete
            </a>

        </div>

    </div>
</div>

<?php endforeach; ?>

</div>

<!-- Pagination -->

<?php if ($totalPages > 1): ?>

<nav class="mt-4">
    <ul class="pagination justify-content-center">

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>

        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">

            <a class="page-link"
               href="dashboard.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                <?php echo $i; ?>
            </a>

        </li>

        <?php endfor; ?>

    </ul>
</nav>

<?php endif; ?>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>