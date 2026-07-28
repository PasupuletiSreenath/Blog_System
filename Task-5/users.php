<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireAdmin();

$csrfToken = generateCsrfToken();

// Handle Delete or Role Change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = "Invalid form submission.";
    } else {
        $action = $_POST['action'] ?? '';
        $targetId = (int)($_POST['id'] ?? 0);

        if ($targetId === $_SESSION['user_id']) {
            $_SESSION['flash_error'] = "You cannot modify or delete your own account.";
        } else {
            if ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                if ($stmt->execute([$targetId])) {
                    $_SESSION['flash_success'] = "User deleted successfully.";
                } else {
                    $_SESSION['flash_error'] = "Failed to delete user.";
                }
            } elseif ($action === 'change_role') {
                $newRole = $_POST['role'] === 'admin' ? 'admin' : 'editor';
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                if ($stmt->execute([$newRole, $targetId])) {
                    $_SESSION['flash_success'] = "User role updated successfully.";
                } else {
                    $_SESSION['flash_error'] = "Failed to update user role.";
                }
            }
        }
    }
    header("Location: users.php");
    exit;
}

// Fetch users
$stmt = $pdo->query("SELECT id, username, email, role, profile_pic, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0 text-primary">Manage Users</h3>
</div>

<div class="glass-card p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle bg-transparent">
            <thead class="text-muted">
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php
                                $pic = !empty($u['profile_pic']) ? 'assets/uploads/profiles/' . $u['profile_pic'] : 'https://ui-avatars.com/api/?name=' . urlencode($u['username']) . '&background=random';
                                ?>
                                <img src="<?php echo htmlspecialchars($pic); ?>" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($u['username']); ?></span>
                                <?php if ($u['id'] === $_SESSION['user_id']): ?>
                                    <span class="badge bg-secondary ms-2 rounded-pill">You</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td>
                            <form method="POST" action="users.php" class="d-flex align-items-center gap-2 m-0">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="action" value="change_role">
                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                <select name="role" class="form-select form-select-sm" style="width: 100px;" <?php echo $u['id'] === $_SESSION['user_id'] ? 'disabled' : ''; ?> onchange="this.form.submit()">
                                    <option value="editor" <?php echo $u['role'] === 'editor' ? 'selected' : ''; ?>>Editor</option>
                                    <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </form>
                        </td>
                        <td class="text-muted small"><?php echo date("M d, Y", strtotime($u['created_at'])); ?></td>
                        <td>
                            <form method="POST" action="users.php" class="d-inline m-0" onsubmit="return confirm('Are you sure you want to delete this user? This will also delete all their posts.');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3" <?php echo $u['id'] === $_SESSION['user_id'] ? 'disabled' : ''; ?>><i class="bi bi-trash"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
