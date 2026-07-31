<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Authentication Guard: Admin access required
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Date Range Filtering Parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date   = $_GET['end_date'] ?? date('Y-m-t');   // Last day of current month

// 1. Fetch Summary Analytics for Date Range
$stmt_summary = $pdo->prepare("
    SELECT 
        COUNT(id) AS total_orders,
        COALESCE(SUM(total_price), 0) AS total_revenue,
        COALESCE(AVG(total_price), 0) AS avg_order_value
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$stmt_summary->execute([$start_date, $end_date]);
$summary = $stmt_summary->fetch();

// 2. Fetch Top Selling Products in Date Range
$stmt_top = $pdo->prepare("
    SELECT m.name, SUM(oi.quantity) AS total_qty, SUM(oi.quantity * oi.price) AS total_sales
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN menu_items m ON oi.item_id = m.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY m.id, m.name
    ORDER BY total_qty DESC
    LIMIT 5
");
$stmt_top->execute([$start_date, $end_date]);
$top_items = $stmt_top->fetchAll();

// 3. Fetch Itemized Orders List
$stmt_orders = $pdo->prepare("
    SELECT o.*, COUNT(oi.id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$stmt_orders->execute([$start_date, $end_date]);
$orders = $stmt_orders->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales & Revenue Reports - Admin Dashboard</title>
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
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg-color);
            color: var(--text-main);
            margin: 0;
            padding: 20px;
            max-width: 1150px;
            margin: 0 auto;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 16px 24px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        header h1 {
            margin: 0;
            font-size: 22px;
            color: #0f172a;
        }
        .nav-links {
            display: flex;
            gap: 10px;
        }
        .btn-nav {
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            background: #334155;
            color: white;
            transition: background 0.2s;
        }
        .btn-nav:hover { background: #1e293b; }

        /* Filter Controls Form */
        .filter-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .filter-form {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .filter-group {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
        }
        input[type="date"] {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
        }
        .btn-filter {
            background: var(--primary);
            color: white;
            border: none;
            padding: 9px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
        }
        .btn-filter:hover { background: var(--primary-hover); }

        .export-group {
            display: flex;
            gap: 10px;
        }
        .btn-export-pdf {
            background: #dc2626;
            color: white;
            padding: 9px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }
        .btn-export-pdf:hover { background: #b91c1c; }
        .btn-export-csv {
            background: #16a34a;
            color: white;
            padding: 9px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }
        .btn-export-csv:hover { background: #15803d; }

        /* KPI Stat Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);
        }
        .stat-card .label { font-size: 13px; color: var(--text-muted); font-weight: 600; }
        .stat-card .value { font-size: 24px; font-weight: 800; margin: 8px 0 4px 0; }
        .stat-card .subtext { font-size: 12px; color: #94a3b8; }

        /* Table Styling */
        .section-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);
            margin-bottom: 25px;
        }
        .section-card h3 {
            margin: 0 0 16px 0;
            font-size: 18px;
            color: #0f172a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }
        th { background: #f8fafc; color: #475569; font-weight: 600; }
    </style>
</head>
<body>

    <header>
        <h1>📊 System Sales & Revenue Report Generator</h1>
        <div class="nav-links">
            <a href="../auth/admin.php" class="btn-nav">🛠️ Admin Dashboard</a>
            <a href="../user/index.php" target="_blank" class="btn-nav">🌐 Customer Storefront</a>
        </div>
    </header>

    <!-- Filter Form & Export Buttons -->
    <div class="filter-card">
        <form method="GET" action="index.php" class="filter-form">
            <div class="filter-group">
                <label>From:</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
            </div>
            <div class="filter-group">
                <label>To:</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
            </div>
            <button type="submit" class="btn-filter">🔍 Filter Report</button>
        </form>

        <div class="export-group">
            <a href="generate_pdf.php?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" target="_blank" class="btn-export-pdf">
                📄 Export PDF Form
            </a>
            <a href="export_csv.php?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="btn-export-csv">
                📊 Export CSV
            </a>
        </div>
    </div>

    <!-- KPI Summary Metrics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="label">Total Revenue</div>
            <div class="value" style="color: var(--success);">₱<?= number_format($summary['total_revenue'], 2) ?></div>
            <div class="subtext">Period total sales</div>
        </div>
        <div class="stat-card">
            <div class="label">Total Orders Placed</div>
            <div class="value" style="color: var(--primary);"><?= number_format($summary['total_orders']) ?></div>
            <div class="subtext">Completed order count</div>
        </div>
        <div class="stat-card">
            <div class="label">Average Order Value</div>
            <div class="value" style="color: #0284c7;">₱<?= number_format($summary['avg_order_value'], 2) ?></div>
            <div class="subtext">Per order average</div>
        </div>
    </div>

    <!-- Top Selling Items -->
    <div class="section-card">
        <h3>🔥 Top Selling Menu Items</h3>
        <?php if (empty($top_items)): ?>
            <p style="color: var(--text-muted); font-size: 14px;">No items sold in the selected date range.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Units Sold</th>
                        <th>Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_items as $item): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
                            <td><?= number_format($item['total_qty']) ?> pcs</td>
                            <td style="font-weight: 700; color: var(--success);">₱<?= number_format($item['total_sales'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Detailed Orders Table -->
    <div class="section-card">
        <h3>📋 Detailed Transaction Log (<?= count($orders) ?> Orders)</h3>
        <?php if (empty($orders)): ?>
            <p style="color: var(--text-muted); font-size: 14px;">No transactions recorded for this period.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer Name</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Date & Time</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?= $order['id'] ?></td>
                            <td><strong><?= htmlspecialchars($order['customer_name']) ?></strong></td>
                            <td><?= htmlspecialchars($order['phone']) ?></td>
                            <td><?= htmlspecialchars($order['address']) ?></td>
                            <td><?= htmlspecialchars($order['created_at']) ?></td>
                            <td style="font-weight: 700; color: var(--success);">₱<?= number_format($order['total_price'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</body>
</html>
