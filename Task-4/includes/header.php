<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>The Logbook</title>

    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php if ($isLoggedIn): ?>
<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <div class="sidebar bg-glass border-end" id="sidebar-wrapper">
        <div class="sidebar-heading d-flex align-items-center gap-2 px-4 py-4 fs-4 fw-bold text-primary">
            <i class="bi bi-journal-album"></i> The Logbook
        </div>
        <div class="list-group list-group-flush px-3">
            <a href="dashboard.php" class="list-group-item list-group-item-action bg-transparent rounded-3 mb-2 fw-semibold text-muted <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="bi bi-grid-1x2-fill me-2"></i> Dashboard
            </a>
            <a href="create_post.php" class="list-group-item list-group-item-action bg-transparent rounded-3 mb-2 fw-semibold text-muted <?php echo basename($_SERVER['PHP_SELF']) == 'create_post.php' ? 'active' : ''; ?>">
                <i class="bi bi-pencil-square me-2"></i> New Entry
            </a>
            <div class="mt-auto pt-4 border-top text-muted small px-3">
                Logged in as<br>
                <strong class="text-dark fs-6"><?php echo htmlspecialchars($_SESSION['username']); ?></strong><br>
                <span class="role-pill mt-2 <?php echo $_SESSION['role'] === 'admin' ? 'admin' : 'editor'; ?>"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Page Content -->
    <div id="page-content-wrapper" class="w-100 d-flex flex-column" style="min-height: 100vh;">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-gradient py-3 px-4">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-primary d-md-none me-3" id="menu-toggle"><i class="bi bi-list text-white"></i></button>
            </div>
            
            <ul class="navbar-nav ms-auto mt-2 mt-lg-0 align-items-center">
                <li class="nav-item">
                    <a class="btn btn-danger btn-sm rounded-pill px-4 fw-bold" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
                </li>
            </ul>
        </nav>
        
        <div class="container-fluid p-4 flex-grow-1">
<?php else: ?>
    <!-- Top Navbar for Auth Pages -->
    <nav class="navbar navbar-expand-lg navbar-gradient py-3 px-4">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fs-3" href="index.php">
                <i class="bi bi-journal-album"></i> The Logbook
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto align-items-center gap-2">
                    <li class="nav-item"><a class="nav-link fw-semibold" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="btn btn-light rounded-pill px-4 fw-bold text-primary" href="register.php">Register</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container my-4">
<?php endif; ?>

    <?php
    // ===== Flash messages (success / error) stored in session =====
    if (isset($_SESSION['flash_success'])):
    ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
