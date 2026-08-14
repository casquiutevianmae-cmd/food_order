<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$error = '';
$username = '';
$email    = '';

// Redirect logged in users
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
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Username or Email is already registered.";
        } else {
            // Hash password and insert customer user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'customer')");
            
            if ($stmt->execute([$username, $email, $hashed_password])) {
                header("Location: login.php?registered=success");
                exit;
            } else {
                $error = "An error occurred while creating your account. Please try again.";
            }
        }
    }
}

$page_title = "Customer Registration - Yum's berchg";
$hide_navbar = true;
$body_class = 'auth-body';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
            <!-- Bootstrap Registration Card -->
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-body p-4 p-sm-5">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <span class="fs-1 d-block mb-1">📝</span>
                        <h2 class="fw-bold text-dark mb-1">Create Account</h2>
                        <p class="text-muted small">Join Yum's berchg to start ordering food online</p>
                    </div>

                    <!-- Alert -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-3 text-center small" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Form -->
                    <form method="POST" action="register.php" novalidate>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                                <input type="text" name="username" class="form-control bg-light border-start-0 py-2" required placeholder="Choose a username" value="<?= htmlspecialchars($username) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control bg-light border-start-0 py-2" required placeholder="name@example.com" value="<?= htmlspecialchars($email) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" class="form-control bg-light border-start-0 py-2" required placeholder="Create a password">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-secondary small">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-shield-lock"></i></span>
                                <input type="password" name="confirm_password" class="form-control bg-light border-start-0 py-2" required placeholder="Repeat password">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100 rounded-3 shadow-sm fw-bold mb-3">
                            Complete Registration <i class="bi bi-check-lg ms-1"></i>
                        </button>
                    </form>

                    <!-- Footer link -->
                    <div class="text-center pt-2">
                        <span class="text-muted small">Already have an account?</span>
                        <a href="login.php" class="fw-bold text-primary text-decoration-none ms-1">Log In Here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
