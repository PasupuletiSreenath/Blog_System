-- =====================================================
-- Task 4 - Secure Blog Database (with Roles, Emails & Images)
-- =====================================================

CREATE DATABASE IF NOT EXISTS blog;
USE blog;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------
-- Table: users
-- -----------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'editor') NOT NULL DEFAULT 'editor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------
-- Table: posts
-- -----------------------------------------------------
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- Sample admin account (username: admin / email: admin@example.com / password: admin)
-- You should probably change this or register normally.
-- -----------------------------------------------------
INSERT INTO users (username, email, password, role)
VALUES ('admin', 'admin@example.com', '$2y$10$7zB.M/T4Wq/FkXb52eZ66Om.wQvJ3Uj.0B7Uj8J.0B7Uj8J.0B7Uj', 'admin');
