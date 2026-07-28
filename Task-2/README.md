# Task 2 - PHP Blog CRUD Application

A beginner-friendly Blog application built with **PHP, MySQL, HTML and CSS** (no frameworks).
Users can register, log in, and create/read/update/delete blog posts.

## Features
- User Registration (passwords hashed with `password_hash()`)
- User Login / Logout with PHP Sessions
- Create, view, edit, and delete blog posts
- Clean, responsive UI

## Folder Structure
```
Task-2/
├── css/
│   └── style.css
├── database/
│   └── database.sql
├── includes/
│   ├── db.php        (database connection)
│   ├── header.php     (navbar + page head)
│   └── footer.php
├── index.php
├── register.php
├── login.php
├── logout.php
├── dashboard.php
├── create_post.php
├── edit_post.php
├── delete_post.php
└── README.md
```

## Installation (XAMPP)

1. **Install XAMPP** from https://www.apachefriends.org if you haven't already.
2. Copy the entire `Task-2` folder into your XAMPP `htdocs` directory:
   ```
   C:\xampp\htdocs\Task-2
   ```
3. Start **Apache** and **MySQL** from the XAMPP Control Panel.
4. Open **phpMyAdmin**: http://localhost/phpmyadmin
5. Click **Import**, choose `database/database.sql`, and click **Go**.
   This creates the `blog` database with `users` and `posts` tables.
6. Visit the app in your browser:
   ```
   http://localhost/Task-2/
   ```
7. Register a new account, log in, and start creating posts!

## Database Credentials
The app uses the default XAMPP MySQL credentials (`root` / no password),
configured in `includes/db.php`. Update these if your setup is different.

## Notes
- Built using plain PHP with PDO (no frameworks).
- All passwords are securely hashed - never stored in plain text.
- SQL Injection is prevented via prepared statements.
