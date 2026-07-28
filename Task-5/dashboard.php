<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$isAdmin = $_SESSION['role'] === 'admin';
$userId = $_SESSION['user_id'];

// --- Dashboard Analytics ---
$totalUsers = 0;
$totalPostsCount = 0;
$totalAdmins = 0;
$totalEditors = 0;
$todayPostsCount = 0;
$recentPosts = [];

if ($isAdmin) {
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalPostsCount = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    $totalAdmins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    $totalEditors = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'editor'")->fetchColumn();
    $todayPostsCount = $pdo->query("SELECT COUNT(*) FROM posts WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    
    $stmt = $pdo->query("SELECT posts.*, users.username, categories.name AS category_name FROM posts JOIN users ON posts.user_id = users.id LEFT JOIN categories ON posts.category_id = categories.id ORDER BY posts.created_at DESC LIMIT 5");
    $recentPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); // Might be visible to all
    $totalPostsCount = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    $totalPostsCount->execute([$userId]);
    $totalPostsCount = $totalPostsCount->fetchColumn();
    
    $todayPostsCount = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE DATE(created_at) = CURDATE() AND user_id = ?");
    $todayPostsCount->execute([$userId]);
    $todayPostsCount = $todayPostsCount->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT posts.*, users.username, categories.name AS category_name FROM posts JOIN users ON posts.user_id = users.id LEFT JOIN categories ON posts.category_id = categories.id WHERE posts.user_id = ? ORDER BY posts.created_at DESC LIMIT 5");
    $stmt->execute([$userId]);
    $recentPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0 text-primary">Dashboard Overview</h3>
    <a href="create_post.php" class="btn btn-primary shadow-sm rounded-pill"><i class="bi bi-plus-lg me-1"></i> New Post</a>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-5">
    <?php if ($isAdmin): ?>
    <div class="col-md-4 col-lg-2">
        <div class="glass-card analytics-card h-100 p-3 text-center">
            <div class="text-primary analytics-icon mb-2"><i class="bi bi-people-fill"></i></div>
            <h3 class="fw-bold mb-0"><?php echo number_format($totalUsers); ?></h3>
            <p class="text-muted mb-0 fw-semibold small">Total Users</p>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="glass-card analytics-card h-100 p-3 text-center">
            <div class="text-danger analytics-icon mb-2"><i class="bi bi-person-badge-fill"></i></div>
            <h3 class="fw-bold mb-0"><?php echo number_format($totalAdmins); ?></h3>
            <p class="text-muted mb-0 fw-semibold small">Total Admins</p>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="glass-card analytics-card h-100 p-3 text-center">
            <div class="text-success analytics-icon mb-2"><i class="bi bi-person-workspace"></i></div>
            <h3 class="fw-bold mb-0"><?php echo number_format($totalEditors); ?></h3>
            <p class="text-muted mb-0 fw-semibold small">Total Editors</p>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="col-md-4 <?php echo $isAdmin ? 'col-lg-3' : 'col-lg-6'; ?>">
        <div class="glass-card analytics-card h-100 p-3 text-center">
            <div class="text-info analytics-icon mb-2"><i class="bi bi-file-earmark-text-fill"></i></div>
            <h3 class="fw-bold mb-0"><?php echo number_format($totalPostsCount); ?></h3>
            <p class="text-muted mb-0 fw-semibold small"><?php echo $isAdmin ? 'Total Posts' : 'My Total Posts'; ?></p>
        </div>
    </div>
    <div class="col-md-4 <?php echo $isAdmin ? 'col-lg-3' : 'col-lg-6'; ?>">
        <div class="glass-card analytics-card h-100 p-3 text-center">
            <div class="text-warning analytics-icon mb-2"><i class="bi bi-calendar2-check-fill"></i></div>
            <h3 class="fw-bold mb-0"><?php echo number_format($todayPostsCount); ?></h3>
            <p class="text-muted mb-0 fw-semibold small">Posts Created Today</p>
        </div>
    </div>
</div>

<div class="glass-card p-4">
    <h4 class="fw-bold mb-4">Recent Posts</h4>
    
    <?php if (count($recentPosts) === 0): ?>
        <div class="text-center py-5">
            <i class="bi bi-journal-x text-muted fs-1 mb-3 d-block"></i>
            <h5 class="fw-bold text-muted">No posts found</h5>
            <p class="text-muted">You haven't written anything recently.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle bg-transparent">
                <thead class="text-muted">
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentPosts as $post): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($post['image'])): ?>
                                        <img src="assets/uploads/images/<?php echo htmlspecialchars($post['image']); ?>" class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-secondary rounded me-3 d-flex justify-content-center align-items-center text-white" style="width: 50px; height: 50px;">
                                            <i class="bi bi-image"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="fw-semibold text-dark"><?php echo htmlspecialchars($post['title']); ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($post['username']); ?></td>
                            <td>
                                <?php if ($post['category_name']): ?>
                                    <span class="badge badge-category rounded-pill"><?php echo htmlspecialchars($post['category_name']); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary rounded-pill">Uncategorized</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small"><?php echo date("M d, Y", strtotime($post['created_at'])); ?></td>
                            <td>
                                <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bi bi-pencil"></i> Edit</a>
                                <form method="POST" action="delete_post.php" class="d-inline" onsubmit="return confirm('Delete this post?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                                    <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
