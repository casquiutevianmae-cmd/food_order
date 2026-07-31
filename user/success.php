<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$order_id = (int)($_GET['order_id'] ?? 0);
$order = null;
$order_items = [];

if ($order_id > 0) {
    // Fetch order from XAMPP MySQL database
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if ($order) {
        // Fetch order items from XAMPP MySQL database
        $stmt_items = $pdo->prepare("
            SELECT oi.*, m.name AS item_name 
            FROM order_items oi 
            LEFT JOIN menu_items m ON oi.item_id = m.id 
            WHERE oi.order_id = ?
        ");
        $stmt_items->execute([$order_id]);
        $order_items = $stmt_items->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Receipt #<?= htmlspecialchars($order_id) ?> - Yum's berchg</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        :root {
            --primary: #2563eb;
            --success: #16a34a;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg-color);
            color: var(--text-main);
            padding: 40px 20px;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .receipt-card {
            background: var(--card-bg);
            max-width: 520px;
            width: 100%;
            padding: 35px 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            border: 1px solid var(--border-color);
        }

        .success-icon {
            text-align: center;
            font-size: 48px;
            margin-bottom: 10px;
        }
        h1 {
            text-align: center;
            margin: 0 0 5px 0;
            color: var(--success);
            font-size: 24px;
        }
        .subtitle {
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 25px;
        }

        .receipt-details {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        .receipt-details div {
            margin-bottom: 6px;
        }
        .receipt-details div:last-child {
            margin-bottom: 0;
        }

        .items-list {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .items-list th, .items-list td {
            padding: 10px;
            border-bottom: 1px solid var(--border-color);
            text-align: left;
        }
        .items-list th {
            background: #f1f5f9;
            color: #475569;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: 800;
            padding: 12px 0;
            border-top: 2px solid var(--border-color);
            color: var(--success);
            margin-bottom: 25px;
        }

        .btn-home {
            display: block;
            width: 100%;
            text-align: center;
            background: var(--primary);
            color: white;
            padding: 12px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: background 0.2s;
        }
        .btn-home:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>

    <div class="receipt-card">
        <div class="success-icon">🎉</div>
        <h1>Order Confirmed!</h1>
        <p class="subtitle">Your order has been received and is being prepared.</p>

        <?php if ($order): ?>
            <div class="receipt-details">
                <div><strong>Order #:</strong> #<?= htmlspecialchars($order['id']) ?></div>
                <div><strong>Customer Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></div>
                <div><strong>Contact Number:</strong> <?= htmlspecialchars($order['phone']) ?></div>
                <div><strong>Delivery Address:</strong> <?= htmlspecialchars($order['address']) ?></div>
                <div><strong>Order Date:</strong> <?= htmlspecialchars($order['created_at']) ?></div>
            </div>

            <table class="items-list">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($item['item_name'] ?? 'Item') ?></strong></td>
                            <td><?= $item['quantity'] ?></td>
                            <td style="font-weight: 600;">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="total-row">
                <span>Grand Total Paid:</span>
                <span>₱<?= number_format($order['total_price'], 2) ?></span>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: var(--text-muted);">Order information not found.</p>
        <?php endif; ?>

        <a href="index.php" class="btn-home">🍔 Back to Yum's berchg Menu</a>
    </div>

</body>
</html>
