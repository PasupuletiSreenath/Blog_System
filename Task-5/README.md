# Task 4 - Secure PHP Blog CRUD (Roles & Security Hardening)

Builds on Task 3 by adding security best practices and role-based access control.

> **Fixed:** `Unknown column 'posts.user_id'` on the dashboard. This happened
> when an older `posts` table (from Task 3, which had no `user_id`/`role`
> columns) was still in the database — `CREATE TABLE IF NOT EXISTS` silently
> skips tables that already exist, so the old schema never got updated.
> `database/database.sql` now drops and recreates `users`/`posts` with the
> correct columns. **Re-import it** (phpMyAdmin → Import, or
> `mysql -u root -p < database/database.sql`) and the error goes away.
> A commented-out `ALTER TABLE` migration is included in that file if you
> need to keep existing rows instead of dropping the tables.
>
> **UI refresh:** the whole app now uses a small "Logbook" theme (Fraunces
> serif headings, monospace metadata, ink/amber/teal palette) instead of
> stock Bootstrap styling — see `css/style.css`.

## Security Features Implemented
| Feature | How |
|---|---|
| SQL Injection prevention | All queries use **PDO prepared statements** with bound parameters |
| XSS prevention | All output passed through `htmlspecialchars()` before rendering |
| CSRF protection | Every form includes a hidden `csrf_token`, verified with `hash_equals()` on submit |
| Password security | `password_hash()` on register, `password_verify()` on login |
| Session security | `session_regenerate_id(true)` on login/logout to prevent session fixation |
| Input validation | Server-side checks (username pattern, min length) + client-side HTML5 `required`/`pattern`/`minlength` |
| Input sanitization | `sanitizeInput()` trims and strips raw HTML tags before saving |
| Safe delete requests | Deleting a post requires a **POST** request with a valid CSRF token (not a plain GET link) |

## Role-Based Access Control
Two roles:
- **Admin** – can view, edit, and delete **all** posts from every user, and sees a role badge in the navbar.
- **Editor** – can only view, edit, and delete **their own** posts. Trying to edit/delete someone else's post is blocked server-side (ownership is re-checked on every request, not just hidden in the UI).

## Folder Structure
```
Task-4/
├── css/style.css
├── database/database.sql   (users table now has 'role', posts table has 'user_id')
├── includes/
│   ├── db.php
│   ├── header.php           (shows role badge)
│   ├── footer.php
│   └── functions.php        (CSRF helpers, requireLogin, requireAdmin, sanitizeInput, isValidUsername)
├── index.php, register.php, login.php, logout.php
├── dashboard.php            (role-aware post listing + search + pagination)
├── create_post.php / edit_post.php / delete_post.php  (all CSRF + ownership protected)
└── README.md
```

## Installation (XAMPP)

1. Copy the `Task-4` folder into `C:\xampp\htdocs\Task-4`
2. Start **Apache** and **MySQL** in XAMPP Control Panel
3. Open http://localhost/phpmyadmin, import `database/database.sql`
4. Visit: **http://localhost/Task-4/**
5. Register two accounts - one as **Admin**, one as **Editor** - to see the difference in what each can manage.

## Try It Out
- Log in as an Editor, create a post, then log in as a different Editor - you won't be able to edit/delete the first user's post.
- Log in as an Admin - you'll see and be able to manage every post from every user.
