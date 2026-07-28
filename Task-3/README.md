# Task 3 - Enhanced PHP Blog CRUD (Search, Pagination, Bootstrap 5)

Builds on Task 2 by improving the UI and adding search + pagination.

## New Features (added on top of Task 2)
- 🔍 **Search bar** - search posts by title or content
- 📄 **Pagination** - 5 posts per page
- 🎨 **Bootstrap 5** UI - responsive navbar, cards, footer
- ✅ **Flash messages** - success/error alerts after actions
- 🖍️ **Keyword highlighting** in search results (`<mark>` tag)
- All original CRUD functionality still works

## Folder Structure
```
Task-3/
├── css/style.css
├── database/database.sql
├── includes/
│   ├── db.php
│   ├── header.php
│   ├── footer.php
│   └── functions.php   (search highlight helper)
├── index.php, register.php, login.php, logout.php
├── dashboard.php        (search + pagination + cards)
├── create_post.php / edit_post.php / delete_post.php
└── README.md
```

## Installation (XAMPP)

1. Copy the `Task-3` folder into `C:\xampp\htdocs\Task-3`
2. Start **Apache** and **MySQL** in XAMPP Control Panel
3. Open http://localhost/phpmyadmin, import `database/database.sql`
4. Visit: **http://localhost/Task-3/**
5. Register, log in, and try the search bar + pagination on the dashboard

## Tech Notes
- Bootstrap 5 is loaded via CDN (no local install needed, requires internet).
- Search uses a `LIKE` prepared statement query, safe from SQL Injection.
- Pagination uses `LIMIT` / `OFFSET` in the SQL query.
