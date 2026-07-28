# Task 1 - Development Environment Setup

**ApexPlanet Internship - PHP & MySQL Track**

## 📋 About This Task

This is **Task 1** of my PHP & MySQL internship at ApexPlanet. The goal of this task
was to set up a local development environment (XAMPP) and confirm that PHP is
installed and working correctly by building a simple dynamic web page.

## ✅ What This Project Does

The project displays a single web page (`index.php`) that shows:

- A welcome message: **"Welcome to ApexPlanet Internship"**
- Confirmation that Task 1 is complete
- The **current date and time**, generated dynamically using PHP's `date()` function
- Simple, clean styling using an external CSS file

## 🛠️ Technologies Used

- **PHP** – for dynamic content (no frameworks used)
- **HTML** – for page structure
- **CSS** – for styling
- **XAMPP** – as the local development/server environment

## 📁 Project Structure

```
Task-1/
│
├── index.php          # Main PHP file - the page that gets displayed
├── css/
│   └── style.css       # Stylesheet for the page
├── images/
│   └── logo.png         # (optional) add your own logo/image here
└── README.md            # Project documentation (this file)
```

## 🚀 How to Run This Project (XAMPP)

1. Download and install [XAMPP](https://www.apachefriends.org/) if you haven't already.
2. Start the **Apache** module from the XAMPP Control Panel.
3. Copy the `Task-1` folder into your XAMPP `htdocs` directory:
   - Windows: `C:\xampp\htdocs\Task-1`
   - macOS/Linux: `/Applications/XAMPP/htdocs/Task-1` or `/opt/lampp/htdocs/Task-1`
4. Open your browser and go to:

   ```
   http://localhost/Task-1/
   ```

5. You should see the welcome page along with the current date and time.

## 📝 Notes for Beginners

- The `index.php` file contains PHP code inside `<?php ... ?>` tags, mixed with
  regular HTML — this is normal and common when learning PHP.
- Comments (`//` and `/* */`) are added throughout the code to explain what each
  part does.
- The `date_default_timezone_set()` function is used to make sure the date/time
  shown matches the correct timezone (currently set to `Asia/Kolkata` — feel free
  to change it to your own).
- No external libraries or frameworks were used — this is plain PHP, HTML, and CSS,
  as required for this task.

## 👤 Author

Submitted as part of the **ApexPlanet Internship Program** – Task 1.
