<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : "";
$authorFilter = isset($_GET['author']) ? (int)$_GET['author'] : 0;
$dateFilter = isset($_GET['date']) ? sanitizeInput($_GET['date']) : "";
$sort = isset($_GET['sort']) && $_GET['sort'] === 'oldest' ? 'ASC' : 'DESC';

$postsPerPage = 6;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $postsPerPage;

// Build query
$conditions = [];
$params = [];

if ($search !== "") {
    $conditions[] = "(posts.title LIKE :search OR posts.content LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($authorFilter > 0) {
    $conditions[] = "posts.user_id = :author";
    $params[':author'] = $authorFilter;
}
if ($dateFilter !== "") {
    $conditions[] = "DATE(posts.created_at) = :date";
    $params[':date'] = $dateFilter;
}

$whereSql = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

// Total count for pagination
$countQuery = "SELECT COUNT(posts.id) FROM posts $whereSql";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalPosts = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalPosts / $postsPerPage));

// Fetch posts with category and comment count
$query = "
    SELECT posts.*, users.username, users.profile_pic, categories.name AS category_name,
    (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count
    FROM posts
    JOIN users ON posts.user_id = users.id
    LEFT JOIN categories ON posts.category_id = categories.id
    $whereSql
    ORDER BY posts.created_at $sort
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $postsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch authors for filter
$authors = $pdo->query("SELECT id, username FROM users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="row mb-5">
    <div class="col-12 text-center">
        <h1 class="fw-bold text-primary display-4 mb-3">Welcome to The Logbook</h1>
        <p class="lead text-muted">A modern blog built with glassmorphism and advanced features.</p>
        <a href="export.php?format=csv" class="btn btn-outline-primary rounded-pill me-2"><i class="bi bi-filetype-csv"></i> Export CSV</a>
        <a href="export.php?format=pdf" class="btn btn-outline-danger rounded-pill"><i class="bi bi-filetype-pdf"></i> Export PDF</a>
    </div>
</div>

<div class="glass-card p-4 mb-5">
    <form method="GET" action="index.php" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label fw-semibold">Search</label>
            <input type="text" name="search" class="form-control" placeholder="Search title or content..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Author</label>
            <select name="author" class="form-select">
                <option value="0">All Authors</option>
                <?php foreach ($authors as $a): ?>
                    <option value="<?php echo $a['id']; ?>" <?php echo $authorFilter == $a['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($a['username']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold">Date</label>
            <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($dateFilter); ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold">Sort By</label>
            <select name="sort" class="form-select">
                <option value="newest" <?php echo $sort === 'DESC' ? 'selected' : ''; ?>>Newest First</option>
                <option value="oldest" <?php echo $sort === 'ASC' ? 'selected' : ''; ?>>Oldest First</option>
            </select>
        </div>
        <div class="col-md-1 text-end">
            <button type="submit" class="btn btn-primary w-100 rounded-pill"><i class="bi bi-search"></i></button>
        </div>
    </form>
</div>

<?php if (count($posts) === 0): ?>
    <div class="text-center py-5">
        <i class="bi bi-journal-x text-muted" style="font-size: 4rem;"></i>
        <h4 class="fw-bold mt-3">No posts found</h4>
        <p class="text-muted">Try adjusting your filters or search term.</p>
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-5">
        <?php foreach ($posts as $post): ?>
            <div class="col">
                <div class="card glass-card h-100 border-0 overflow-hidden">
                    <?php if (!empty($post['image'])): ?>
                        <img src="assets/uploads/images/<?php echo htmlspecialchars($post['image']); ?>" class="post-card-img w-100" alt="Post Image">
                    <?php else: ?>
                        <div class="post-card-img bg-secondary d-flex align-items-center justify-content-center text-white w-100 fs-1">
                            <i class="bi bi-image"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <?php if ($post['category_name']): ?>
                                <span class="badge badge-category rounded-pill mb-2"><?php echo htmlspecialchars($post['category_name']); ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary rounded-pill mb-2">Uncategorized</span>
                            <?php endif; ?>
                            <span class="text-muted small"><i class="bi bi-chat-dots"></i> <?php echo $post['comment_count']; ?></span>
                        </div>
                        
                        <h5 class="card-title fw-bold mb-3">
                            <a href="post.php?id=<?php echo $post['id']; ?>" class="text-decoration-none text-dark"><?php echo highlightKeyword($post['title'], $search); ?></a>
                        </h5>
                        
                        <p class="card-text text-muted mb-4" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                            <?php echo strip_tags($post['content']); ?>
                        </p>
                        
                        <?php
                        // Fetch Tags
                        $tagStmt = $pdo->prepare("SELECT t.name FROM tags t JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ?");
                        $tagStmt->execute([$post['id']]);
                        $tags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);
                        if (count($tags) > 0):
                        ?>
                            <div class="mb-3">
                                <?php foreach ($tags as $tag): ?>
                                    <span class="badge badge-tag rounded-pill me-1"><i class="bi bi-hash"></i><?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-footer bg-transparent border-top p-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <?php
                            $pic = !empty($post['profile_pic']) ? 'assets/uploads/profiles/' . $post['profile_pic'] : 'https://ui-avatars.com/api/?name=' . urlencode($post['username']) . '&background=random';
                            ?>
                            <img src="<?php echo htmlspecialchars($pic); ?>" class="rounded-circle me-2" style="width: 30px; height: 30px; object-fit: cover;">
                            <span class="small fw-semibold"><?php echo htmlspecialchars($post['username']); ?></span>
                        </div>
                        <span class="text-muted small"><?php echo date("M d, Y", strtotime($post['created_at'])); ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link shadow-sm" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&author=<?php echo $authorFilter; ?>&date=<?php echo urlencode($dateFilter); ?>&sort=<?php echo $sort === 'ASC' ? 'oldest' : 'newest'; ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link shadow-sm" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&author=<?php echo $authorFilter; ?>&date=<?php echo urlencode($dateFilter); ?>&sort=<?php echo $sort === 'ASC' ? 'oldest' : 'newest'; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link shadow-sm" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&author=<?php echo $authorFilter; ?>&date=<?php echo urlencode($dateFilter); ?>&sort=<?php echo $sort === 'ASC' ? 'oldest' : 'newest'; ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
