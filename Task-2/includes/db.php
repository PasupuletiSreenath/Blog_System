<?php
/**
 * Database Connection File
 * -------------------------
 * Uses PDO (PHP Data Objects) to connect to MySQL.
 * PDO lets us use prepared statements, which protect
 * against SQL Injection attacks.
 */

$host = "localhost";   // Database server (XAMPP default)
$dbname = "blog";      // Database name
$db_username = "root";     // Default XAMPP MySQL username
$db_password = "";         // Default XAMPP MySQL password (empty)

try {
    // Create a new PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);

    // Tell PDO to throw exceptions on errors (easier debugging)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If connection fails, stop the script and show the error
    die("Database connection failed: " . $e->getMessage());
}
