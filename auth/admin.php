<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Authentication Guard: Ensure user is logged in as Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$success_msg = '';
$error_msg = '';

// --- ACTION HANDLERS ---

// 1. Add Category
if (isset($_POST['action']) && $_POST['action'] === 'add_category') {
    $name = trim($_POST['category_name'] ?? '');
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$name]);
        $success_msg = "Category '{$name}' added successfully!";
    } else {
        $error_msg = "Category name cannot be empty.";
    }
}

// 2. Delete Category
if (isset($_POST['action']) && $_POST['action'] === 'delete_category') {
    $cat_id = (int)$_POST['category_id'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$cat_id]);
    $success_msg = "Category deleted successfully.";
}

// 3. Add Menu Item
if (isset($_POST['action']) && $_POST['action'] === 'add_menu_item') {
    $category_id = (int)$_POST['category_id'];
    $name        = trim($_POST['item_name'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $image_url   = trim($_POST['image_url'] ?? '');

    if (!empty($name) && $category_id > 0 && $price > 0) {
        $stmt = $pdo->prepare("INSERT INTO menu_items (category_id, name, price, description, image_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$category_id, $name, $price, $description, $image_url]);
        $success_msg = "Menu item '{$name}' added successfully!";
    } else {
        $error_msg = "Please provide valid menu item details (Name, Category, and positive Price).";
    }
}

// 4. Edit Menu Item
if (isset($_POST['action']) && $_POST['action'] === 'edit_menu_item') {
    $item_id     = (int)$_POST['item_id'];
    $category_id = (int)$_POST['category_id'];
    $name        = trim($_POST['item_name'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $image_url   = trim($_POST['image_url'] ?? '');

    if ($item_id > 0 && !empty($name) && $category_id > 0 && $price > 0) {
        $stmt = $pdo->prepare("UPDATE menu_items SET category_id = ?, name = ?, price = ?, description = ?, image_url = ? WHERE id = ?");
        $stmt->execute([$category_id, $name, $price, $description, $image_url, $item_id]);
        $success_msg = "Menu item '{$name}' updated successfully!";
    } else {
        $error_msg = "Failed to update menu item. Please check inputs.";
    }
}

// 5. Delete Menu Item
if (isset($_POST['action']) && $_POST['action'] === 'delete_menu_item') {
    $item_id = (int)$_POST['item_id'];
    $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
    $stmt->execute([$item_id]);
    $success_msg = "Menu item deleted successfully.";
}

// 6. Delete Order
if (isset($_POST['action']) && $_POST['action'] === 'delete_order') {
    $order_id = (int)$_POST['order_id'];
    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $success_msg = "Order #{$order_id} deleted successfully.";
}

// --- FETCH DASHBOARD & REVENUE ANALYTICS DATA ---

// Key Metrics
$total_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_sales  = (float)$pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders")->fetchColumn();

$this_month_orders = (int)$pdo->query("
    SELECT COUNT(*) FROM orders 
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
      AND YEAR(created_at) = YEAR(CURRENT_DATE())
")->fetchColumn();

$this_month_sales = (float)$pdo->query("
    SELECT COALESCE(SUM(total_price), 0) FROM orders 
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
      AND YEAR(created_at) = YEAR(CURRENT_DATE())
")->fetchColumn();

$avg_order_value = $total_orders > 0 ? ($total_sales / $total_orders) : 0;

$total_menu_items = (int)$pdo->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
$total_categories = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// Monthly Revenues Breakdown
$monthly_revenues = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') AS ym,
        DATE_FORMAT(created_at, '%M %Y') AS month_name,
        COUNT(*) AS order_count,
        SUM(total_price) AS total_revenue,
        AVG(total_price) AS avg_revenue
    FROM orders 
    GROUP BY DATE_FORMAT(created_at, '%Y-%m'), DATE_FORMAT(created_at, '%M %Y')
    ORDER BY ym DESC
")->fetchAll();

// Maximum monthly revenue for chart percentage calculation
$max_monthly_rev = 1;
foreach ($monthly_revenues as $mr) {
    if ($mr['total_revenue'] > $max_monthly_rev) {
        $max_monthly_rev = $mr['total_revenue'];
    }
}

// Top Selling Items Breakdown
$top_items = $pdo->query("
    SELECT 
        m.name AS item_name,
        c.name AS category_name,
        SUM(oi.quantity) AS total_qty,
        SUM(oi.quantity * oi.price) AS total_revenue
    FROM order_items oi
    JOIN menu_items m ON oi.item_id = m.id
    JOIN categories c ON m.category_id = c.id
    GROUP BY oi.item_id, m.name, c.name
    ORDER BY total_revenue DESC
    LIMIT 5
")->fetchAll();

// Data Lists
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$menu_items = $pdo->query("
    SELECT m.*, c.name AS category_name 
    FROM menu_items m 
    JOIN categories c ON m.category_id = c.id 
    ORDER BY c.name ASC, m.name ASC
")->fetchAll();

$orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();

// Item edit selection
$edit_item = null;
if (isset($_GET['edit_item_id'])) {
    $edit_id = (int)$_GET['edit_item_id'];
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_item = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordering & Monthly Revenues Dashboard - Yum's berchg</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        :root {
            --bg: #f8fafc;
            --sidebar-bg: #0f172a;
            --card-bg: #ffffff;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --success: #16a34a;
            --success-bg: #dcfce7;
            --danger: #dc2626;
            --purple: #7c3aed;
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text-main);
            margin: 0;
            padding: 0;
        }

        /* Top Navigation Header */
        header {
            background: var(--sidebar-bg);
            color: white;
            padding: 16px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .header-actions a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s;
        }
        .header-actions a:hover {
            color: white;
        }
        .user-badge {
            background: #1e293b;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            color: #e2e8f0;
            border: 1px solid #334155;
        }
        .btn-logout {
            background: #ef4444;
            color: white !important;
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: 600;
        }
        .btn-logout:hover {
            background: #dc2626 !important;
        }

        .container {
            max-width: 1240px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* Alerts */
        .alert {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
        }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        .dashboard-header {
            margin-bottom: 25px;
        }
        .dashboard-header h2 {
            margin: 0 0 5px 0;
            font-size: 24px;
            color: #0f172a;
        }
        .dashboard-header p {
            margin: 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        /* Overview Metric Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }
        .stat-card {
            background: var(--card-bg);
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .stat-card .label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            font-weight: 700;
        }
        .stat-card .value {
            font-size: 26px;
            font-weight: 800;
            margin-top: 10px;
            color: var(--text-main);
        }
        .stat-card .subtext {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }

        /* Section Layouts */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 35px;
        }
        @media (max-width: 900px) {
            .grid-2 { grid-template-columns: 1fr; }
        }

        .section-card {
            background: var(--card-bg);
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 35px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }
        .section-header h3 {
            margin: 0;
            font-size: 18px;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Visual Monthly Revenue Chart Bars */
        .chart-container {
            display: flex;
            flex-direction: column;
            gap: 18px;
            margin-top: 10px;
        }
        .chart-row {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .chart-meta {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            font-weight: 600;
        }
        .chart-bar-bg {
            background: #f1f5f9;
            height: 22px;
            border-radius: 6px;
            overflow: hidden;
            position: relative;
        }
        .chart-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #2563eb 0%, #3b82f6 100%);
            border-radius: 6px;
            transition: width 0.5s ease-in-out;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 600;
        }
        tr:hover td {
            background: #f8fafc;
        }

        /* Forms */
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
        }
        input[type="text"], input[type="number"], select, textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 14px;
            background: #fff;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .btn {
            padding: 9px 16px;
            border-radius: 6px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }

        .badge {
            background: #e2e8f0;
            color: #334155;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .order-details-box {
            background: #f8fafc;
            border-left: 3px solid var(--primary);
            padding: 10px 15px;
            margin-top: 8px;
            font-size: 13px;
        }
    </style>
</head>
<body>

    <header>
        <h1>🛠️ Yum's berchg Admin Dashboard</h1>
        <div class="header-actions">
            <a href="../reports/index.php" style="background: #2563eb; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600;">📊 Sales Reports & PDF Export</a>
            <a href="../user/index.php" target="_blank">🌐 Customer Storefront</a>
            <span class="user-badge">Admin: <strong><?= htmlspecialchars($_SESSION['admin_username'] ?? $_SESSION['username']) ?></strong></span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </header>

    <div class="container">

        <!-- Notification Banners -->
        <?php if ($success_msg): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($success_msg) ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <div class="dashboard-header">
            <h2>📊 Ordering Total & Monthly Revenue Dashboard</h2>
            <p>Real-time order volume, sales breakdown, and performance analytics</p>
        </div>

        <!-- 1. KEY ANALYTICS KPI CARDS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total Revenue</div>
                <div class="value" style="color: var(--success);">₱<?= number_format($total_sales, 2) ?></div>
                <div class="subtext">Lifetime gross sales</div>
            </div>
            <div class="stat-card">
                <div class="label">This Month's Revenue</div>
                <div class="value" style="color: var(--primary);">₱<?= number_format($this_month_sales, 2) ?></div>
                <div class="subtext"><?= date('F Y') ?> sales total</div>
            </div>
            <div class="stat-card">
                <div class="label">Ordering Total</div>
                <div class="value"><?= number_format($total_orders) ?></div>
                <div class="subtext">Total customer orders</div>
            </div>
            <div class="stat-card">
                <div class="label">This Month's Orders</div>
                <div class="value"><?= number_format($this_month_orders) ?></div>
                <div class="subtext">Orders placed this month</div>
            </div>
            <div class="stat-card">
                <div class="label">Avg Order Value (AOV)</div>
                <div class="value" style="color: var(--purple);">₱<?= number_format($avg_order_value, 2) ?></div>
                <div class="subtext">Revenue per order</div>
            </div>
        </div>

        <!-- 2. MONTHLY REVENUES BREAKDOWN & VISUAL CHART -->
        <div class="grid-2">
            <!-- Visual Monthly Bar Chart -->
            <div class="section-card" style="margin-bottom:0;">
                <div class="section-header">
                    <h3>📈 Monthly Revenue Trends</h3>
                </div>
                <?php if (empty($monthly_revenues)): ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 20px;">No revenue data recorded yet.</p>
                <?php else: ?>
                    <div class="chart-container">
                        <?php foreach ($monthly_revenues as $rev): ?>
                            <?php $percent = round(($rev['total_revenue'] / $max_monthly_rev) * 100); ?>
                            <div class="chart-row">
                                <div class="chart-meta">
                                    <span><?= htmlspecialchars($rev['month_name']) ?></span>
                                    <span style="color: var(--success); font-weight:700;">₱<?= number_format($rev['total_revenue'], 2) ?> (<?= $rev['order_count'] ?> orders)</span>
                                </div>
                                <div class="chart-bar-bg">
                                    <div class="chart-bar-fill" style="width: <?= max(5, $percent) ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Detailed Monthly Revenue Table -->
            <div class="section-card" style="margin-bottom:0;">
                <div class="section-header">
                    <h3>🗓️ Monthly Revenues Breakdown</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Month / Year</th>
                            <th>Orders</th>
                            <th>Total Revenue</th>
                            <th>Avg / Order</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($monthly_revenues)): ?>
                            <tr><td colspan="4" style="text-align:center;">No monthly data available.</td></tr>
                        <?php else: ?>
                            <?php foreach ($monthly_revenues as $rev): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($rev['month_name']) ?></strong></td>
                                    <td><span class="badge"><?= $rev['order_count'] ?> orders</span></td>
                                    <td style="color: var(--success); font-weight: 700;">₱<?= number_format($rev['total_revenue'], 2) ?></td>
                                    <td>₱<?= number_format($rev['avg_revenue'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. TOP SELLING FOOD ITEMS REVENUE -->
        <div class="section-card">
            <div class="section-header">
                <h3>🏆 Top Revenue-Generating Menu Items</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Food Item</th>
                        <th>Category</th>
                        <th>Units Sold</th>
                        <th>Total Item Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_items)): ?>
                        <tr><td colspan="4" style="text-align:center;">No sales data available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($top_items as $item): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($item['item_name']) ?></strong></td>
                                <td><span class="badge"><?= htmlspecialchars($item['category_name']) ?></span></td>
                                <td><strong><?= number_format($item['total_qty']) ?></strong> units</td>
                                <td style="color: var(--success); font-weight: 700;">₱<?= number_format($item['total_revenue'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 4. ORDERS MANAGEMENT OVERVIEW -->
        <div class="section-card">
            <div class="section-header">
                <h3>📦 Customer Orders Log</h3>
            </div>
            <?php if (empty($orders)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 20px;">No customer orders placed yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer Info</th>
                            <th>Delivery Address</th>
                            <th>Total Amount</th>
                            <th>Date & Time</th>
                            <th>Items Purchased</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <?php
                            $stmt_items = $pdo->prepare("
                                SELECT oi.*, m.name AS item_name 
                                FROM order_items oi 
                                LEFT JOIN menu_items m ON oi.item_id = m.id 
                                WHERE oi.order_id = ?
                            ");
                            $stmt_items->execute([$order['id']]);
                            $order_items = $stmt_items->fetchAll();
                            ?>
                            <tr>
                                <td><strong>#<?= $order['id'] ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($order['customer_name']) ?></strong><br>
                                    <span style="color: var(--text-muted); font-size: 12px;">📞 <?= htmlspecialchars($order['phone']) ?></span>
                                </td>
                                <td style="max-width: 200px;"><?= htmlspecialchars($order['address']) ?></td>
                                <td style="color: var(--success); font-weight: 700;">₱<?= number_format($order['total_price'], 2) ?></td>
                                <td style="font-size: 12px; color: var(--text-muted);"><?= $order['created_at'] ?></td>
                                <td>
                                    <div class="order-details-box">
                                        <?php foreach ($order_items as $oi): ?>
                                            <div>• <strong><?= htmlspecialchars($oi['item_name'] ?? 'Item Deleted') ?></strong> x <?= $oi['quantity'] ?> (₱<?= number_format($oi['price'], 2) ?>)</div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete Order #<?= $order['id'] ?>?');">
                                        <input type="hidden" name="action" value="delete_order">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- 5. MENU & CATEGORIES MANAGEMENT SECTION -->
        <div class="grid-2">

            <!-- Category Management -->
            <div class="section-card" style="margin-bottom:0;">
                <div class="section-header">
                    <h3>🏷️ Categories</h3>
                </div>
                
                <form method="POST" style="margin-bottom: 20px;">
                    <input type="hidden" name="action" value="add_category">
                    <div style="display: flex; gap: 10px;">
                        <input type="text" name="category_name" placeholder="New Category Name..." required>
                        <button type="submit" class="btn btn-primary" style="white-space: nowrap;">Add Category</button>
                    </div>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr><td colspan="3" style="text-align:center;">No categories found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td>#<?= $cat['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Deleting category will also delete associated menu items. Continue?');">
                                            <input type="hidden" name="action" value="delete_category">
                                            <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Add/Edit Menu Item Form -->
            <div class="section-card" style="margin-bottom:0;">
                <div class="section-header">
                    <h3><?= $edit_item ? '✏️ Edit Menu Item' : '➕ Add Menu Item' ?></h3>
                    <?php if ($edit_item): ?>
                        <a href="index.php" style="font-size: 13px; color: var(--primary); text-decoration: none;">Cancel Editing</a>
                    <?php endif; ?>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="<?= $edit_item ? 'edit_menu_item' : 'add_menu_item' ?>">
                    <?php if ($edit_item): ?>
                        <input type="hidden" name="item_id" value="<?= $edit_item['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Item Name</label>
                        <input type="text" name="item_name" required placeholder="e.g. Bacon Cheeseburger" value="<?= htmlspecialchars($edit_item['name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($edit_item && $edit_item['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Price (₱)</label>
                        <input type="number" step="0.01" name="price" required placeholder="9.99" value="<?= htmlspecialchars($edit_item['price'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="2" placeholder="Brief description of the item..."><?= htmlspecialchars($edit_item['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Image URL</label>
                        <input type="text" name="image_url" placeholder="uploads/cheeseburger.png" value="<?= htmlspecialchars($edit_item['image_url'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <?= $edit_item ? 'Update Menu Item' : 'Save Menu Item' ?>
                    </button>
                </form>
            </div>

        </div>

        <!-- Menu Items List Section -->
        <div class="section-card">
            <div class="section-header">
                <h3>🍔 Menu Items Catalog</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($menu_items)): ?>
                        <tr><td colspan="6" style="text-align:center;">No menu items found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($menu_items as $item): ?>
                            <?php $img_path = !empty($item['image_url']) ? '../' . $item['image_url'] : '../uploads/default_food.png'; ?>
                            <tr>
                                <td>
                                    <img src="<?= htmlspecialchars($img_path) ?>" alt="Food" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
                                </td>
                                <td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
                                <td><span class="badge"><?= htmlspecialchars($item['category_name']) ?></span></td>
                                <td style="color: var(--success); font-weight:700;">₱<?= number_format($item['price'], 2) ?></td>
                                <td style="color: var(--text-muted); font-size:13px; max-width: 250px;"><?= htmlspecialchars($item['description']) ?></td>
                                <td>
                                    <a href="index.php?edit_item_id=<?= $item['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete <?= htmlspecialchars($item['name']) ?>?');">
                                        <input type="hidden" name="action" value="delete_menu_item">
                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>
