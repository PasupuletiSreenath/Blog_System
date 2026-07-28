<?php
require_once 'includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$error = "";
$success = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and trim form data
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Basic validation
    if ($username === "" || $password === "") {
        $error = "Please fill in all fields.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->rowCount() > 0) {
            $error = "Username already taken. Please choose another.";
        } else {
            // Hash the password before saving (NEVER store plain text passwords)
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user using a prepared statement (prevents SQL Injection)
            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $hashedPassword]);

            $success = "Registration successful! You can now log in.";
        }
    }
}

require_once 'includes/header.php';
?>

<h2>Register</h2>

<?php if ($error): ?>
    <p class="alert alert-error"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<?php if ($success): ?>
    <p class="alert alert-success"><?php echo htmlspecialchars($success); ?> <a href="login.php">Login here</a></p>
<?php endif; ?>

<form method="POST" action="register.php" class="form-box">
    <label>Username</label>
    <input type="text" name="username" required>

    <label>Password</label>
    <input type="password" name="password" required minlength="6">

    <button type="submit">Register</button>
</form>

<?php require_once 'includes/footer.php'; ?>
