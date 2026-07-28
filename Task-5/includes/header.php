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
    <title>Advanced Blog Management System</title>

    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- CKEditor 5 CDN -->
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <style>
        .ck-editor__editable_inline {
            min-height: 300px;
        }
    </style>
    <script>
      document.addEventListener("DOMContentLoaded", function() {
          if (document.querySelector('#content')) {
              ClassicEditor
                  .create(document.querySelector('#content'))
                  .catch(error => {
                      console.error(error);
                  });
          }
      });
    </script>
</head>
<body>

<?php if ($isLoggedIn): ?>
<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <div class="sidebar glass-sidebar flex-shrink-0 p-3" id="sidebar-wrapper" style="width: 250px;">
        <div class="sidebar-heading fs-4 fw-bold text-primary mb-4 text-center">
            <i class="bi bi-journal-album"></i> The Logbook
        </div>
        
        <!-- Profile Picture Snippet in Sidebar -->
        <div class="text-center mb-4">
            <?php
            $profilePic = isset($_SESSION['profile_pic']) && !empty($_SESSION['profile_pic']) 
                ? 'assets/uploads/profiles/' . $_SESSION['profile_pic'] 
                : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username']) . '&background=random';
            ?>
            <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile Picture" class="rounded-circle shadow-sm" style="width: 80px; height: 80px; object-fit: cover; border: 3px solid #fff;">
            <div class="mt-2 fw-bold"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
            <span class="badge <?php echo $_SESSION['role'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
        </div>

        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="create_post.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'create_post.php' ? 'active' : ''; ?>">
                    <i class="bi bi-pencil-square"></i> New Post
                </a>
            </li>
            <li>
                <a href="profile.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    <i class="bi bi-person-circle"></i> Profile
                </a>
            </li>
            
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <li class="mt-3 mb-1 text-muted small fw-bold px-3">ADMIN</li>
            <li>
                <a href="users.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                    <i class="bi bi-people-fill"></i> Users
                </a>
            </li>
            <li>
                <a href="categories.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                    <i class="bi bi-tags-fill"></i> Categories
                </a>
            </li>
            <li>
                <a href="comments.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'comments.php' ? 'active' : ''; ?>">
                    <i class="bi bi-chat-dots-fill"></i> Comments
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
    
    <!-- Page Content -->
    <div id="page-content-wrapper" class="w-100 d-flex flex-column" style="min-height: 100vh;">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg glass-navbar px-4 py-2">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-primary d-md-none me-3" id="menu-toggle"><i class="bi bi-list"></i></button>
                <a href="index.php" class="btn btn-sm btn-outline-secondary me-2"><i class="bi bi-globe"></i> View Blog</a>
            </div>
            
            <ul class="navbar-nav ms-auto mt-2 mt-lg-0 align-items-center">
                <li class="nav-item">
                    <a class="btn btn-danger btn-sm rounded-pill px-4 fw-bold shadow-sm" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
                </li>
            </ul>
        </nav>
        
        <div class="main-content flex-grow-1">
<?php else: ?>
    <!-- Top Navbar for Auth & Public Pages -->
    <nav class="navbar navbar-expand-lg glass-navbar px-4 py-3 sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fs-3 fw-bold text-primary" href="index.php">
                <i class="bi bi-journal-album"></i> The Logbook
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto align-items-center gap-3">
                    <?php if (!$isLoggedIn): ?>
                        <li class="nav-item"><a class="nav-link fw-semibold" href="login.php">Login</a></li>
                        <li class="nav-item"><a class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" href="register.php">Register</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" href="dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container my-5">
<?php endif; ?>

    <!-- Flash Notifications -->
    <div class="container-fluid mt-3">
        <?php if (isset($_SESSION['flash_success'])): ?>
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
    </div>
