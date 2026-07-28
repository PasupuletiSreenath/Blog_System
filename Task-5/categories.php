<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireAdmin();

$csrfToken = generateCsrfToken();

// Handle Category Creation or Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = "Invalid form submission.";
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create') {
            $name = sanitizeInput($_POST['name'] ?? '');
            if ($name === '') {
                $_SESSION['flash_error'] = "Category name is required.";
            } else {
                $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
                $stmt->execute([$name]);
                if ($stmt->rowCount() > 0) {
                    $_SESSION['flash_error'] = "Category already exists.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                    if ($stmt->execute([$name])) {
                        $_SESSION['flash_success'] = "Category created successfully.";
                    } else {
                        $_SESSION['flash_error'] = "Failed to create category.";
                    }
                }
            }
        } elseif ($action === 'delete') {
            $targetId = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$targetId])) {
                $_SESSION['flash_success'] = "Category deleted successfully.";
            } else {
                $_SESSION['flash_error'] = "Failed to delete category.";
            }
        }
    }
    header("Location: categories.php");
    exit;
}

// Fetch categories
$stmt = $pdo->query("SELECT c.*, COUNT(p.id) as post_count FROM categories c LEFT JOIN posts p ON c.id = p.category_id GROUP BY c.id ORDER BY c.name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0 text-primary">Manage Categories</h3>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="glass-card p-4 h-100">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Add New Category</h5>
            <form method="POST" action="categories.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="create">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Category Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary rounded-pill px-4 w-100"><i class="bi bi-plus-lg me-1"></i> Create Category</button>
            </form>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="glass-card p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle bg-transparent">
                    <thead class="text-muted">
                        <tr>
                            <th>Category Name</th>
                            <th>Posts Count</th>
                            <th>Created On</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($categories) === 0): ?>
                            <tr><td colspan="4" class="text-center py-3">No categories found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><span class="fw-semibold text-dark"><?php echo htmlspecialchars($cat['name']); ?></span></td>
                                    <td><span class="badge bg-secondary rounded-pill"><?php echo $cat['post_count']; ?></span></td>
                                    <td class="text-muted small"><?php echo date("M d, Y", strtotime($cat['created_at'])); ?></td>
                                    <td class="text-end">
                                        <form method="POST" action="categories.php" class="d-inline m-0" onsubmit="return confirm('Are you sure you want to delete this category? Posts in this category will become uncategorized.');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
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
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
