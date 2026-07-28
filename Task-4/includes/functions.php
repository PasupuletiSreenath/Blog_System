<?php
/**
 * Wraps every occurrence of $keyword inside $text with a <mark> tag
 * so it appears highlighted in the browser.
 * The text is escaped first (XSS-safe), then highlighting is applied.
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

/* =========================================================
   SECURITY HELPERS
   ========================================================= */

/**
 * Generate a CSRF token and store it in the session.
 * Call this once per form render, then embed the token
 * in a hidden input field.
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
 * Uses hash_equals() to prevent timing attacks.
 */
function verifyCsrfToken($token)
{
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!is_string($token)) {
        return false;
    }

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
    $timeout_duration = 1800; // 30 * 60 seconds
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
            // User was deleted from DB but session remained
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
 * Basic input sanitizer for text fields - trims whitespace and
 * strips raw HTML tags. We ALSO use htmlspecialchars() at output
 * time as a second layer of defense ("defense in depth").
 */
function sanitizeInput($value)
{
    return trim(strip_tags($value));
}
