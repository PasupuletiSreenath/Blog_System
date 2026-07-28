<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$error = "";
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ===== 1. CSRF check =====
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid form submission. Please try again.";
    } else {
        // ===== 2. Sanitize + validate input =====
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = trim($_POST['password'] ?? '');
        $role = ($_POST['role'] ?? 'editor') === 'admin' ? 'admin' : 'editor'; // whitelist check

        if ($username === "" || $email === "" || $password === "") {
            $error = "Please fill in all fields.";
        } elseif (!isValidUsername($username)) {
            $error = "Username must be 3-30 characters (letters, numbers, underscores only).";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please provide a valid email address.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } else {
            // ===== 3. Check for existing username or email =====
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);

            if ($stmt->rowCount() > 0) {
                $error = "Username or email already taken. Please choose another.";
            } else {
                // ===== 4. Hash password - never store plain text =====
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hashedPassword, $role]);

                $_SESSION['flash_success'] = "Registration successful! You can now log in.";
                header("Location: login.php");
                exit;
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="col-md-5">
        <div class="glass-card">
            <div class="card-body p-5">
                <div class="auth-icon text-center mb-3 fs-1 text-primary"><i class="bi bi-person-plus-fill"></i></div>
                <h3 class="card-title mb-1 fw-bold text-center">Create an account</h3>
                <p class="text-muted mb-4 text-center">Start your own logbook in a minute.</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="register.php" novalidate>
                    <!-- Hidden CSRF token field -->
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control"
                               pattern="[a-zA-Z0-9_]{3,30}"
                               title="3-30 characters: letters, numbers, underscores only"
                               value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control"
                               value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                        <div class="form-text">At least 6 characters.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="editor">Editor — manage my own entries</option>
                            <option value="admin">Admin — manage all entries</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold">Register</button>
                </form>
                <p class="mt-4 text-center mb-0 text-muted">Already have an account? <a href="login.php" class="text-decoration-none fw-bold">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
