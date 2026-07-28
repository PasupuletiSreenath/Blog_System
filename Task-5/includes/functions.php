<?php
/**
 * Wraps every occurrence of $keyword inside $text with a <mark> tag
 */
function highlightKeyword($text, $keyword)
{
    $safeText = htmlspecialchars($text);
    if (trim($keyword) === "") {
        return $safeText;
    }
    $pattern = '/' . preg_quote(htmlspecialchars($keyword), '/') . '/i';
    return preg_replace($pattern, '<mark>$0</mark>', $safeText);
}

/**
 * Generate a CSRF token and store it in the session.
 */
function generateCsrfToken()
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a CSRF token submitted from a form matches the one in session.
 */
function verifyCsrfToken($token)
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!is_string($token)) return false;
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Require the visitor to be logged in. Redirects to login.php otherwise.
 */
function requireLogin()
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    // Session Timeout - 30 minutes
    $timeout_duration = 1800;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['flash_error'] = "Your session has expired due to inactivity. Please log in again.";
        header("Location: login.php");
        exit;
    }
    $_SESSION['last_activity'] = time();

    global $pdo;
    if (isset($pdo)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            session_unset();
            session_destroy();
            header("Location: login.php");
            exit;
        }
    }
}

/**
 * Require the visitor to be an admin. Shows an error otherwise.
 */
function requireAdmin()
{
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        die("Access denied: this page is for admins only.");
    }
}

/**
 * Validate a username: letters, numbers, underscores, 3-30 chars.
 */
function isValidUsername($username)
{
    return preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username) === 1;
}

/**
 * Basic input sanitizer for text fields
 */
function sanitizeInput($value)
{
    return trim(strip_tags($value));
}

/**
 * Handle Image Uploads
 * $file: $_FILES['image'] array
 * $destDir: path to upload directory (e.g. 'assets/uploads/images/')
 * Returns filename on success, or false on error. 
 * Note: Error message should be stored in $_SESSION['flash_error'] before calling, or handled by caller.
 */
function uploadImage($file, $destDir, $maxSize = 2097152) { // 2MB default
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    if ($file['size'] > $maxSize) {
        $_SESSION['flash_error'] = "File size exceeds limit (2MB).";
        return false;
    }

    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $fileType = mime_content_type($file['tmp_name']);

    if (!in_array($fileType, $allowedTypes)) {
        $_SESSION['flash_error'] = "Invalid file type. Only JPG, JPEG, and PNG are allowed.";
        return false;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_') . '.' . $ext;
    
    // Ensure dir exists
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    
    $targetPath = $destDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $filename;
    }
    
    $_SESSION['flash_error'] = "Failed to upload image.";
    return false;
}
