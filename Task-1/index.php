<?php

//Date and time
date_default_timezone_set("Asia/Kolkata");


$currentDateTime = date("l, F j, Y - h:i:s A");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ApexPlanet Internship - Task 1</title>

    <!-- Linking our external CSS file for styling -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="container">

        

        <h1>Welcome to ApexPlanet Internship</h1>

        <h2>Task 1: Development Environment Setup Completed</h2>

        <div class="info-box">
            <p>This page was generated using <strong>PHP</strong>, confirming that:</p>
            <ul>
                <li>XAMPP is installed and running correctly</li>
                <li>Apache server is serving PHP files</li>
                <li>PHP is executing dynamic code successfully</li>
            </ul>
        </div>

        <div class="datetime-box">
            <p>Current Date &amp; Time:</p>
            <h3><?php echo $currentDateTime; ?></h3>
        </div>

        <footer>
            <p>&copy; <?php echo date("Y"); ?> ApexPlanet Internship | Task 1</p>
        </footer>

    </div>

</body>
</html>
