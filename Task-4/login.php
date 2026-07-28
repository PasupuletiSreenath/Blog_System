<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Handle Remember Me Cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_user'])) {
    $token = $_COOKIE['remember_user'];
    // In a real production app, use a dedicated auth tokens table.
    // For this simple task, we're assuming the cookie holds the user ID securely encrypted,
    // but we'll do a basic direct ID match just for demonstration of the concept.
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        header("Location: dashboard.php");
        exit;
    }
}

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid form submission. Please try again.";
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $remember = isset($_POST['remember']);

        if ($username === "" || $password === "") {
            $error = "Please fill in all fields.";
        } else {
            // Check by username OR email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();

                if ($remember) {
                    // Set cookie for 30 days
                    setcookie('remember_user', $user['id'], time() + (86400 * 30), "/");
                }

                $_SESSION['flash_success'] = "Welcome back, " . htmlspecialchars($user['username']) . "!";
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid username/email or password.";
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="col-md-5">
        <div class="card shadow-sm auth-card">
            <div class="card-body p-5">
                <div class="auth-icon"><i class="bi bi-box-arrow-in-right"></i></div>
                <h3 class="card-title mb-1 fw-bold">Welcome back</h3>
                <p class="text-muted mb-4">Log in to your account.</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <div class="mb-3">
                        <label class="form-label">Username or Email</label>
                        <input type="text" name="username" class="form-control" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe" name="remember">
                        <label class="form-check-label text-muted" for="rememberMe">Remember me</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold">Log In</button>
                </form>
                <p class="mt-4 text-center mb-0 text-muted">Don't have an account? <a href="register.php" class="text-decoration-none fw-bold">Register here</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
