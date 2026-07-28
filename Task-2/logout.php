<?php
// Destroy the session to log the user out
if (session_status() === PHP_SESSION_NONE) session_start();

session_unset();   // Remove all session variables
session_destroy(); // Destroy the session itself

header("Location: login.php");
exit;
