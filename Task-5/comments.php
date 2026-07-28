<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireAdmin();

$csrfToken = generateCsrfToken();

// Handle Comment Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = "Invalid form submission.";
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'delete') {
            $targetId = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            if ($stmt->execute([$targetId])) {
                $_SESSION['flash_success'] = "Comment deleted successfully.";
            } else {
                $_SESSION['flash_error'] = "Failed to delete comment.";
            }
        }
    }
    header("Location: comments.php");
    exit;
}

// Fetch comments
$stmt = $pdo->query("SELECT c.*, p.title AS post_title, u.username FROM comments c JOIN posts p ON c.post_id = p.id JOIN users u ON c.user_id = u.id ORDER BY c.created_at DESC");
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0 text-primary">Manage Comments</h3>
</div>

<div class="glass-card p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle bg-transparent">
            <thead class="text-muted">
                <tr>
                    <th style="width: 20%;">Author</th>
                    <th style="width: 35%;">Comment</th>
                    <th style="width: 25%;">Post</th>
                    <th style="width: 10%;">Date</th>
                    <th style="width: 10%;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($comments) === 0): ?>
                    <tr><td colspan="5" class="text-center py-4">No comments found.</td></tr>
                <?php else: ?>
                    <?php foreach ($comments as $c): ?>
                        <tr>
                            <td><span class="fw-semibold text-dark"><i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($c['username']); ?></span></td>
                            <td>
                                <div class="text-secondary text-truncate" style="max-width: 300px;">
                                    <?php echo htmlspecialchars(substr($c['content'], 0, 100)) . (strlen($c['content']) > 100 ? '...' : ''); ?>
                                </div>
                            </td>
                            <td><a href="index.php?search=<?php echo urlencode($c['post_title']); ?>" class="text-decoration-none text-primary" target="_blank"><?php echo htmlspecialchars(substr($c['post_title'], 0, 50)); ?></a></td>
                            <td class="text-muted small"><?php echo date("M d, Y", strtotime($c['created_at'])); ?></td>
                            <td class="text-end">
                                <form method="POST" action="comments.php" class="d-inline m-0" onsubmit="return confirm('Are you sure you want to delete this comment?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3"><i class="bi bi-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
