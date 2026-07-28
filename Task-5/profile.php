<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$csrfToken = generateCsrfToken();

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = "Invalid form submission.";
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_profile') {
            $username = sanitizeInput($_POST['username'] ?? '');
            $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
            
            if ($username === '' || $email === '') {
                $_SESSION['flash_error'] = "Username and email are required.";
            } elseif (!isValidUsername($username)) {
                $_SESSION['flash_error'] = "Invalid username format.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['flash_error'] = "Invalid email address.";
            } else {
                // Check if username/email already taken by someone else
                $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                $stmt->execute([$username, $email, $userId]);
                if ($stmt->rowCount() > 0) {
                    $_SESSION['flash_error'] = "Username or email is already taken.";
                } else {
                    // Handle Profile Picture Upload
                    $profilePic = $user['profile_pic'];
                    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
                        $uploaded = uploadImage($_FILES['profile_pic'], 'assets/uploads/profiles/');
                        if ($uploaded) {
                            $profilePic = $uploaded;
                            // Optionally delete old profile picture here
                        }
                    }
                    
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, profile_pic = ? WHERE id = ?");
                    if ($stmt->execute([$username, $email, $profilePic, $userId])) {
                        $_SESSION['username'] = $username;
                        $_SESSION['profile_pic'] = $profilePic;
                        $_SESSION['flash_success'] = "Profile updated successfully!";
                        
                        // Refresh user data
                        $user['username'] = $username;
                        $user['email'] = $email;
                        $user['profile_pic'] = $profilePic;
                    } else {
                        $_SESSION['flash_error'] = "Failed to update profile.";
                    }
                }
            }
        } elseif ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $_SESSION['flash_error'] = "All password fields are required.";
            } elseif ($newPassword !== $confirmPassword) {
                $_SESSION['flash_error'] = "New passwords do not match.";
            } elseif (strlen($newPassword) < 6) {
                $_SESSION['flash_error'] = "New password must be at least 6 characters.";
            } elseif (!password_verify($currentPassword, $user['password'])) {
                $_SESSION['flash_error'] = "Current password is incorrect.";
            } else {
                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashed, $userId])) {
                    $_SESSION['flash_success'] = "Password changed successfully!";
                    $user['password'] = $hashed; // update local copy
                } else {
                    $_SESSION['flash_error'] = "Failed to change password.";
                }
            }
        }
        
        // Redirect to clear POST data
        header("Location: profile.php");
        exit;
    }
}

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="glass-card p-4 text-center h-100">
            <?php
            $pic = !empty($user['profile_pic']) ? 'assets/uploads/profiles/' . $user['profile_pic'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&background=random&size=150';
            ?>
            <img src="<?php echo htmlspecialchars($pic); ?>" class="profile-img-preview mb-3" alt="Profile Picture">
            <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($user['username']); ?></h3>
            <p class="text-muted mb-2"><?php echo htmlspecialchars($user['email']); ?></p>
            <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?> fs-6 rounded-pill px-3 py-2"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
            
            <hr class="my-4">
            <div class="text-start">
                <p class="text-muted small mb-1">Member Since</p>
                <p class="fw-semibold"><i class="bi bi-calendar3 me-2 text-primary"></i><?php echo date("F d, Y", strtotime($user['created_at'])); ?></p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="glass-card p-4 mb-4">
            <h4 class="fw-bold mb-4 border-bottom pb-2">Update Profile</h4>
            <form method="POST" action="profile.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Username</label>
                        <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-semibold">Profile Picture</label>
                    <input type="file" name="profile_pic" class="form-control" accept="image/png, image/jpeg, image/jpg">
                    <div class="form-text">Max size 2MB (JPG, JPEG, PNG). Leave blank to keep current picture.</div>
                </div>
                
                <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="bi bi-save me-1"></i> Save Changes</button>
            </form>
        </div>
        
        <div class="glass-card p-4">
            <h4 class="fw-bold mb-4 border-bottom pb-2">Change Password</h4>
            <form method="POST" action="profile.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="change_password">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold"><i class="bi bi-shield-lock me-1"></i> Update Password</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
