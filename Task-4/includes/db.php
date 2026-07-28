<?php
/**
 * Database Connection File (PDO)
 * Prepared statements used throughout the app protect against SQL Injection.
 */

$host = "localhost";
$dbname = "blog";
$db_username = "root";
$db_password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() .
        "<br>Make sure MySQL is running in XAMPP and that you've imported database/database.sql.");
}
