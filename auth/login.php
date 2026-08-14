<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$error = '';
$success = '';

if (isset($_GET['registered']) && $_GET['registered'] === 'success') {
    $success = "Registration successful! You can now log in with your credentials.";
}

// Redirect logged in users based on role
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin.php");
        exit;
    } else {
        header("Location: ../user/index.php");
        exit;
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $login_input = trim($_POST['login_input'] ?? '');
    $password    = $_POST['password'] ?? '';

    if (empty($login_input) || empty($password)) {
        $error = "Please enter your username/email and password.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$login_input, $login_input]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            if ($user['role'] === 'admin') {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username']  = $user['username'];
                header("Location: admin.php");
                exit;
            } else {
                header("Location: ../user/index.php");
                exit;
            }
        } else {
            $error = "Invalid username/email or password.";
        }
    }
}

$page_title = "Sign In - Yum's berchg";
$hide_navbar = true;
$body_class = 'auth-body';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
            <!-- Bootstrap Login Card -->
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-body p-4 p-sm-5">
                    <!-- Brand Header -->
                    <div class="text-center mb-4">
                        <span class="display-3 d-block mb-2">🍔</span>
                        <h2 class="fw-bold text-dark mb-1">Welcome Back</h2>
                        <p class="text-muted small mb-0">Sign in to your Yum's berchg account</p>
                    </div>

                    <!-- Alerts -->
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show rounded-3 text-center small" role="alert">
                            <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-3 text-center small" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Login Form -->
                    <form method="POST" action="login.php" novalidate>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small">Username or Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                                <input type="text" name="login_input" class="form-control bg-light border-start-0 py-2" required placeholder="Enter username or email" value="<?= htmlspecialchars($_POST['login_input'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-secondary small">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" class="form-control bg-light border-start-0 py-2" required placeholder="Enter password">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 rounded-3 shadow-sm fw-bold">
                            Sign In <i class="bi bi-arrow-right-short ms-1"></i>
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="position-relative text-center my-4">
                        <hr class="text-muted opacity-25">
                        <span class="position-absolute top-50 start-50 translate-middle bg-white px-3 text-muted extra-small fw-semibold">NEW CUSTOMER?</span>
                    </div>

                    <!-- Register Link -->
                    <a href="register.php" class="btn btn-outline-success btn-lg w-100 rounded-3 fw-bold fs-6">
                        <i class="bi bi-person-plus me-1"></i> Create New Account
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
