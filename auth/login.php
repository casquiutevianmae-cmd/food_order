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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unified Login - Yum's berchg</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .login-card {
            background: #ffffff;
            padding: 40px 32px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 420px;
        }
        .brand-header {
            text-align: center;
            margin-bottom: 28px;
        }
        .brand-header .icon {
            font-size: 42px;
            margin-bottom: 8px;
            display: inline-block;
        }
        .brand-header h2 {
            margin: 0 0 6px 0;
            color: #0f172a;
            font-size: 26px;
            font-weight: 700;
        }
        .brand-header p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 7px;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        .btn-submit {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.1s ease, box-shadow 0.2s ease;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
        }
        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: center;
        }
        .success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #047857;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: center;
        }
        .divider {
            text-align: center;
            margin: 24px 0 18px 0;
            color: #94a3b8;
            font-size: 12px;
            position: relative;
        }
        .divider::before, .divider::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 42%;
            height: 1px;
            background: #e2e8f0;
        }
        .divider::before { left: 0; }
        .divider::after { right: 0; }
        .btn-register {
            width: 100%;
            padding: 11px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: block;
            text-align: center;
            transition: background 0.2s;
        }
        .btn-register:hover {
            background: #059669;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand-header">
            <span class="icon">🍔</span>
            <h2>Welcome</h2>
            <p>Sign in to access your Food Ordering Portal</p>
        </div>

        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label>Username or Email Address</label>
                <input type="text" name="login_input" required placeholder="Enter your username or email" value="<?= htmlspecialchars($_POST['login_input'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Enter your password">
            </div>
            <button type="submit" class="btn-submit">Sign In</button>
        </form>

        <div class="divider">NEW CUSTOMER?</div>
        <a href="register.php" class="btn-register">📝 Create New Account</a>
    </div>
</body>
</html>
