<?php
if (session_status() === PHP_SESSION_NONE) session_start();

session_unset();
session_destroy();

// Start a fresh session just to show a goodbye message on the login page
session_start();
$_SESSION['flash_success'] = "You have been logged out.";

header("Location: login.php");
exit;
