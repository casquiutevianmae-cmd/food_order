<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = $page_title ?? "Yum's berchg - Food Ordering System";
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;

// Determine base URL path depth relative to current script
$is_subfolder = (strpos($_SERVER['SCRIPT_NAME'], '/auth/') !== false || strpos($_SERVER['SCRIPT_NAME'], '/user/') !== false || strpos($_SERVER['SCRIPT_NAME'], '/reports/') !== false);
$base_path = $is_subfolder ? '../' : './';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    
    <!-- Bootstrap 5.3 CDN CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="<?= $base_path ?>css/style.css">
</head>
<body class="<?= $body_class ?? '' ?>">

<?php if (!isset($hide_navbar) || !$hide_navbar): ?>
<!-- Responsive Bootstrap 5 Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm py-2">
    <div class="container">
        <!-- Brand Logo -->
        <a class="navbar-brand navbar-brand-logo d-flex align-items-center gap-2" href="<?= $base_path ?>index.php">
            <span class="fs-3">🍔</span>
            <span class="fw-bold text-white">Yum's berchg</span>
        </a>

        <!-- Responsive Mobile Navbar Toggler -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Menu Collapse -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link px-3" href="<?= $base_path ?>user/index.php">
                        <i class="bi bi-shop me-1"></i> Menu Storefront
                    </a>
                </li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-outline-warning btn-sm me-2" href="<?= $base_path ?>auth/admin.php">
                            <i class="bi bi-speedometer2 me-1"></i> Admin Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-info btn-sm" href="<?= $base_path ?>reports/index.php">
                            <i class="bi bi-graph-up-arrow me-1"></i> Sales Reports
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- Right Actions & User Badge -->
            <div class="d-flex align-items-center gap-3">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Shopping Cart Button with Dynamic Badge -->
                    <a href="<?= $base_path ?>user/cart.php" class="btn btn-primary position-relative px-3 rounded-pill shadow-sm">
                        <i class="bi bi-cart3 me-1"></i> Cart
                        <?php if ($cart_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light">
                                <?= $cart_count ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <!-- User Account Badge -->
                    <span class="navbar-text text-light small d-none d-md-inline">
                        <i class="bi bi-person-circle me-1 text-info"></i>
                        <strong><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></strong>
                    </span>

                    <!-- Logout Button -->
                    <a href="<?= $base_path ?>auth/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                        <i class="bi bi-box-arrow-right me-1"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="<?= $base_path ?>auth/login.php" class="btn btn-outline-light rounded-pill px-4">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </a>
                    <a href="<?= $base_path ?>auth/register.php" class="btn btn-success rounded-pill px-4">
                        <i class="bi bi-person-plus me-1"></i> Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<?php endif; ?>

<main class="main-content py-4">
