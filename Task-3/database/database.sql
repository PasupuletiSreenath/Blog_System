-- =====================================================
-- Task 2 - Blog CRUD Database
-- Import this file in phpMyAdmin, or run:
--   mysql -u root -p < database.sql
-- =====================================================

CREATE DATABASE IF NOT EXISTS blog;
USE blog;

-- -----------------------------------------------------
-- Table: users
-- Stores registered users. Passwords are hashed using
-- PHP's password_hash() function - never stored as plain text.
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- -----------------------------------------------------
-- Table: posts
-- Stores blog posts created by logged-in users.
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
