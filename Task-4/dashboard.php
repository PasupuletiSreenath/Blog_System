<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : "";

$postsPerPage = 5;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $postsPerPage;

$isAdmin = $_SESSION['role'] === 'admin';
$userId = $_SESSION['user_id'];

// --- Top Statistics ---
$statMyPosts = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
$statMyPosts->execute([$userId]);
$myPostsCount = $statMyPosts->fetchColumn();

$totalPostsCount = 0;
$usersCount = 0;
$todayPostsCount = 0;

if ($isAdmin) {
    $totalPostsCount = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    $usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $todayPostsCount = $pdo->query("SELECT COUNT(*) FROM posts WHERE DATE(created_at) = CURDATE()")->fetchColumn();
} else {
    $totalPostsCount = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn(); // Everyone can see total posts metric maybe?
    // Let's restrict it
    $todayPostsCount = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE DATE(created_at) = CURDATE() AND user_id = ?");
    $todayPostsCount->execute([$userId]);
    $todayPostsCount = $todayPostsCount->fetchColumn();
}


// ===== Build WHERE clause depending on role and search term =====
$conditions = [];
$params = [];

if (!$isAdmin) {
    $conditions[] = "posts.user_id = :uid";
    $params[':uid'] = $userId;
}
if ($search !== "") {
    $conditions[] = "(title LIKE :search1 OR content LIKE :search2)";
    $params[':search1'] = "%$search%";
    $params[':search2'] = "%$search%";
}
$whereSql = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

// Count total matching posts (for pagination)
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM posts $whereSql");
$countStmt->execute($params);
$totalPosts = (int) $countStmt->fetchColumn();

// Fetch the actual page of posts
$stmt = $pdo->prepare("SELECT posts.*, users.username FROM posts
                        JOIN users ON posts.user_id = users.id
                        $whereSql
                        ORDER BY posts.created_at DESC LIMIT :limit OFFSET :offset");
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $postsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPages = max(1, ceil($totalPosts / $postsPerPage));

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0 text-primary">Dashboard Overview</h3>
    <a href="create_post.php" class="btn btn-primary shadow-sm"><i class="bi bi-plus-lg me-1"></i> New Entry</a>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="card stat-card h-100 p-3">
            <div class="d-flex align-items-center">
                <div class="icon-box me-3"><i class="bi bi-journal-check"></i></div>
                <div>
                    <p class="text-muted mb-0 fw-semibold">My Posts</p>
                    <h3 class="fw-bold mb-0"><?php echo number_format($myPostsCount); ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card success h-100 p-3">
            <div class="d-flex align-items-center">
                <div class="icon-box me-3"><i class="bi bi-calendar-check"></i></div>
                <div>
                    <p class="text-muted mb-0 fw-semibold">Today's Posts</p>
                    <h3 class="fw-bold mb-0"><?php echo number_format($todayPostsCount); ?></h3>
                </div>
            </div>
        </div>
    </div>
    <?php if ($isAdmin): ?>
    <div class="col-md-3">
        <div class="card stat-card warning h-100 p-3">
            <div class="d-flex align-items-center">
                <div class="icon-box me-3"><i class="bi bi-people"></i></div>
                <div>
                    <p class="text-muted mb-0 fw-semibold">Registered Users</p>
                    <h3 class="fw-bold mb-0"><?php echo number_format($usersCount); ?></h3>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-md-3">
        <div class="card stat-card secondary h-100 p-3">
            <div class="d-flex align-items-center">
                <div class="icon-box me-3"><i class="bi bi-collection"></i></div>
                <div>
                    <p class="text-muted mb-0 fw-semibold"><?php echo $isAdmin ? 'Total Posts' : 'System Posts'; ?></p>
                    <h3 class="fw-bold mb-0"><?php echo number_format($totalPostsCount); ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <h4 class="fw-bold mb-0">Recent Entries</h4>
    <form method="GET" action="dashboard.php" class="d-flex" style="width: 100%; max-width: 400px;">
        <input type="text" name="search" class="form-control rounded-start-pill border-end-0 bg-white" placeholder="Search by title or content..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-primary rounded-end-pill px-4"><i class="bi bi-search"></i></button>
    </form>
</div>

<?php if ($search !== ""): ?>
    <p class="text-muted mb-4"><i class="bi bi-search me-1"></i> Showing results for "<strong><?php echo htmlspecialchars($search); ?></strong>" &mdash; <a href="dashboard.php" class="text-decoration-none fw-semibold">clear</a></p>
<?php endif; ?>

<?php if (count($posts) === 0): ?>
    <div class="card border-dashed p-5 text-center mt-4">
        <div class="card-body">
            <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
            <h4 class="fw-bold mt-3">No entries found</h4>
            <p class="text-muted">
                <?php echo $search !== "" ? "Try a different search term or clear the search." : "You haven't written any entries yet. Start your logbook today!"; ?>
            </p>
            <?php if ($search === ""): ?>
                <a href="create_post.php" class="btn btn-primary mt-2"><i class="bi bi-plus-lg me-1"></i> Create First Entry</a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-lg-2 g-4">
        <?php foreach ($posts as $post): ?>
            <?php
                $canManage = $isAdmin || $post['user_id'] == $_SESSION['user_id'];
                $isMine = $post['user_id'] == $_SESSION['user_id'];
            ?>
            <div class="col">
                <div class="card h-100 <?php echo $isMine ? 'border-primary border-opacity-25' : ''; ?>">
                    <?php if (!empty($post['image'])): ?>
                        <img src="assets/images/<?php echo htmlspecialchars($post['image']); ?>" class="entry-image" alt="Post Image">
                    <?php endif; ?>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title fw-bold mb-0 text-dark"><?php echo highlightKeyword($post['title'], $search); ?></h5>
                            <?php if ($isMine): ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill">Mine</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-muted small mb-3">
                            <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($post['username']); ?> &bull; 
                            <i class="bi bi-clock me-1"></i> <?php echo date("d M Y, h:i A", strtotime($post['created_at'])); ?>
                        </div>
                        
                        <p class="card-text text-secondary"><?php 
                            // Truncate content for dashboard view if too long, or show full
                            $content = highlightKeyword($post['content'], $search);
                            echo nl2br($content); 
                        ?></p>
                    </div>
                    <?php if ($canManage): ?>
                        <div class="card-footer bg-transparent p-3 d-flex justify-content-end gap-2">
                            <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary px-3 rounded-pill">
                                <i class="bi bi-pencil me-1"></i> Edit
                            </a>
                            <form method="POST" action="delete_post.php" onsubmit="return confirm('Are you sure you want to delete this entry?');" class="mb-0">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                                <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger px-3 rounded-pill">
                                    <i class="bi bi-trash me-1"></i> Delete
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <nav class="mt-5">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link shadow-sm" href="dashboard.php?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link shadow-sm" href="dashboard.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link shadow-sm" href="dashboard.php?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
