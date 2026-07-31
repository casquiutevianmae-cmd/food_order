<?php
session_start();

// Smart router: Redirect based on active session and user role
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: auth/admin.php");
        exit;
    } else {
        header("Location: user/index.php");
        exit;
    }
}

// Default redirect to unified login page
header("Location: auth/login.php");
exit;
?>
