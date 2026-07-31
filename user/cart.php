<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Authentication Guard: Must log in before accessing cart
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Handle quantity changes (+1 / -1)
if (isset($_POST['update_qty'])) {
    $item_id = (int) $_POST['item_id'];
    $change = (int) $_POST['change'];
    if (isset($_SESSION['cart'][$item_id])) {
        $_SESSION['cart'][$item_id] += $change;
        if ($_SESSION['cart'][$item_id] <= 0) {
            unset($_SESSION['cart'][$item_id]);
        }
    }
    header("Location: cart.php");
    exit;
}

// Handle item removal
if (isset($_POST['remove_item'])) {
    $item_id = (int) $_POST['item_id'];
    unset($_SESSION['cart'][$item_id]);
    header("Location: cart.php");
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$cart_items = [];
$error = '';

if (!empty($cart)) {
    $placeholders = implode(',', array_fill(0, count($cart), '?'));
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id IN ($placeholders)");
    $stmt->execute(array_keys($cart));
    $cart_items = $stmt->fetchAll();
}

// Pre-fill customer name if logged in
$logged_in_name = $_SESSION['username'] ?? '';

// Handle Place Order action -> Send order data to XAMPP MySQL Database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (empty($cart_items)) {
        $error = "Your cart is empty. Please add menu items before placing an order.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $user_id = $_SESSION['user_id'] ?? null;

        if (empty($name) || empty($phone) || empty($address)) {
            $error = "Please fill in all delivery details (Name, Phone, and Address).";
        } else {
            // 1. Calculate grand total
            $grand_total = 0;
            foreach ($cart_items as $item) {
                $qty = $_SESSION['cart'][$item['id']] ?? 0;
                $grand_total += $item['price'] * $qty;
            }

            // 2. Insert order record into XAMPP MySQL database orders table
            $stmt = $pdo->prepare("INSERT INTO orders (customer_name, phone, address, total_price, user_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $phone, $address, $grand_total, $user_id]);
            $order_id = $pdo->lastInsertId();

            // 3. Insert individual order items into XAMPP MySQL database order_items table
            $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cart_items as $item) {
                $qty = $_SESSION['cart'][$item['id']] ?? 0;
                if ($qty > 0) {
                    $stmt_item->execute([$order_id, $item['id'], $qty, $item['price']]);
                }
            }

            // 4. Clear active cart session and redirect to success receipt
            $_SESSION['cart'] = [];
            header("Location: success.php?order_id=" . $order_id);
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Order Your Food</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --success: #16a34a;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --danger: #dc2626;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            max-width: 700px;
            margin: 30px auto;
            padding: 0 20px;
            background: var(--bg-color);
            color: var(--text-main);
        }

        .card {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .card-header h2 {
            margin: 0;
            font-size: 22px;
        }

        .back-link {
            text-decoration: none;
            color: var(--primary);
            font-weight: 600;
            font-size: 14px;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .cart-table th,
        .cart-table td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }

        .cart-table th {
            background: #f1f5f9;
            color: #475569;
        }

        .qty-btn {
            background: #e2e8f0;
            color: #0f172a;
            border: none;
            width: 26px;
            height: 26px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
        }

        .qty-btn:hover {
            background: #cbd5e1;
        }

        .btn-remove {
            background: #fee2e2;
            color: var(--danger);
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-remove:hover {
            background: #fecaca;
        }

        .total-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f1f5f9;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .total-box h3 {
            margin: 0;
            font-size: 18px;
        }

        .total-amount {
            font-size: 22px;
            font-weight: 800;
            color: var(--success);
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .btn-checkout {
            width: 100%;
            background: var(--success);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-checkout:hover {
            background: #15803d;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>

<body>

    <div class="card">
        <div class="card-header">
            <h2>🛒 Your Shopping Cart</h2>
            <a href="index.php" class="back-link">← Back to Menu</a>
        </div>

        <?php if ($error): ?>
            <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div style="text-align: center; padding: 40px 20px;">
                <p style="color: var(--text-muted); font-size: 16px; margin-bottom: 20px;">Your cart is currently empty.</p>
                <a href="index.php"
                    style="background: var(--primary); color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600;">Browse
                    Delicious Menu</a>
            </div>
        <?php else: ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $grand_total = 0;
                    foreach ($cart_items as $item):
                        $qty = $cart[$item['id']] ?? 0;
                        if ($qty <= 0)
                            continue;
                        $subtotal = $item['price'] * $qty;
                        $grand_total += $subtotal;
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
                            <td>₱<?= number_format($item['price'], 2) ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                        <input type="hidden" name="change" value="-1">
                                        <button type="submit" name="update_qty" class="qty-btn">-</button>
                                    </form>
                                    <span style="font-weight: 700; width: 20px; text-align: center;"><?= $qty ?></span>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                        <input type="hidden" name="change" value="1">
                                        <button type="submit" name="update_qty" class="qty-btn">+</button>
                                    </form>
                                </div>
                            </td>
                            <td style="font-weight: 700; color: var(--success);">₱<?= number_format($subtotal, 2) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                    <button type="submit" name="remove_item" class="btn-remove">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="total-box">
                <h3>Total Order Price:</h3>
                <div class="total-amount">₱<?= number_format($grand_total, 2) ?></div>
            </div>

            <h3 style="margin-bottom: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">🚚 Delivery
                & Customer Details</h3>
            <form method="POST" action="cart.php">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required placeholder="Enter recipient name"
                        value="<?= htmlspecialchars($logged_in_name) ?>">
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" required placeholder="e.g. 09171234567">
                </div>

                <div class="form-group">
                    <label>Delivery Address</label>
                    <textarea name="address" required rows="3"
                        placeholder="Street address, Village, City, Landmark"></textarea>
                </div>

                <button type="submit" name="place_order" class="btn-checkout">✅ Place Order</button>
            </form>
        <?php endif; ?>
    </div>

</body>

</html>
