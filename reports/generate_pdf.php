<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Authentication Guard: Admin access required
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Date Range Filtering Parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date'] ?? date('Y-m-t');

// 1. Fetch Financial Summary
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

// 2. Fetch Top Selling Products
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

// 3. Fetch Full Orders Breakdown with Itemized details
$stmt_orders = $pdo->prepare("
    SELECT o.* 
    FROM orders o
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    ORDER BY o.created_at ASC
");
$stmt_orders->execute([$start_date, $end_date]);
$orders = $stmt_orders->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales_Report_<?= htmlspecialchars($start_date) ?>_to_<?= htmlspecialchars($end_date) ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm;
        }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #1e293b;
            background: #f1f5f9;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Floating Top Action Control Bar (Hidden when printing) */
        .no-print-bar {
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            background: #0f172a;
            color: white;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
        }

        .btn-print {
            background: #2563eb;
            color: white;
            border: none;
            padding: 9px 18px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }
        .btn-print:hover { background: #1d4ed8; }

        .btn-close {
            background: #475569;
            color: white;
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
        }
        .btn-close:hover { background: #334155; }

        /* Printable PDF Sheet */
        .pdf-sheet {
            background: white;
            max-width: 800px;
            margin: 25px auto;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #cbd5e1;
        }

        /* Document Header */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            border-bottom: 3px solid #0f172a;
            padding-bottom: 15px;
        }
        .header-brand h1 {
            margin: 0;
            font-size: 26px;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .header-brand p {
            margin: 4px 0 0 0;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-meta {
            text-align: right;
            font-size: 12px;
            color: #475569;
        }
        .header-meta strong {
            color: #0f172a;
        }

        .period-badge {
            background: #e0f2fe;
            border: 1px solid #bae6fd;
            color: #0369a1;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 25px;
            display: inline-block;
        }

        /* Financial Metric Cards */
        .kpi-grid {
            display: table;
            width: 100%;
            margin-bottom: 30px;
            table-layout: fixed;
        }
        .kpi-box {
            display: table-cell;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 16px;
            border-radius: 8px;
            text-align: center;
        }
        .kpi-box:not(:last-child) {
            border-right-width: 0;
        }
        .kpi-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .kpi-val {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 6px;
        }

        /* Data Tables */
        .section-title {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin: 25px 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 6px;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 12px;
        }
        .report-table th {
            background: #0f172a;
            color: white;
            padding: 9px 12px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .report-table td {
            padding: 9px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .report-table tr:nth-child(even) {
            background: #f8fafc;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .total-row td {
            font-size: 13px;
            font-weight: 800;
            background: #f1f5f9;
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
        }

        .doc-footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px dashed #cbd5e1;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #94a3b8;
        }

        /* Print Media Styles */
        @media print {
            .no-print-bar { display: none !important; }
            body { background: white !important; }
            .pdf-sheet {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>

    <!-- Non-printable top action toolbar -->
    <div class="no-print-bar">
        <div>
            <strong>📄 PDF Form Report Preview</strong> &nbsp;|&nbsp; 
            <span style="font-size: 13px; color: #cbd5e1;">Period: <?= htmlspecialchars($start_date) ?> to <?= htmlspecialchars($end_date) ?></span>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <button onclick="window.print()" class="btn-print">🖨️ Print / Save as PDF</button>
            <a href="index.php?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="btn-close">Close Preview</a>
        </div>
    </div>

    <!-- Official PDF Report Sheet -->
    <div class="pdf-sheet">
        
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td class="header-brand">
                    <h1>🍔 Yum's berchg</h1>
                    <p>Executive Sales & Revenue Report</p>
                </td>
                <td class="header-meta">
                    <div><strong>Report ID:</strong> RPT-<?= date('Ymd-His') ?></div>
                    <div><strong>Generated On:</strong> <?= date('F j, Y - g:i A') ?></div>
                    <div><strong>Generated By:</strong> <?= htmlspecialchars($_SESSION['username'] ?? 'Administrator') ?></div>
                </td>
            </tr>
        </table>

        <!-- Date Range Filter Metadata -->
        <div class="period-badge">
            📅 <strong>Reporting Period:</strong> <?= date('F j, Y', strtotime($start_date)) ?> &mdash; <?= date('F j, Y', strtotime($end_date)) ?>
        </div>

        <!-- KPI Metrics Summary Grid -->
        <div class="kpi-grid">
            <div class="kpi-box">
                <div class="kpi-label">Gross Revenue</div>
                <div class="kpi-val" style="color: #16a34a;">₱<?= number_format($summary['total_revenue'], 2) ?></div>
            </div>
            <div class="kpi-box">
                <div class="kpi-label">Orders Completed</div>
                <div class="kpi-val" style="color: #2563eb;"><?= number_format($summary['total_orders']) ?></div>
            </div>
            <div class="kpi-box">
                <div class="kpi-label">Average Order Value</div>
                <div class="kpi-val" style="color: #0284c7;">₱<?= number_format($summary['avg_order_value'], 2) ?></div>
            </div>
        </div>

        <!-- Top Performing Items Table -->
        <div class="section-title">1. Top Selling Menu Items</div>
        <?php if (empty($top_items)): ?>
            <p style="font-size: 12px; color: #64748b;">No items recorded during this reporting window.</p>
        <?php else: ?>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Menu Item Name</th>
                        <th class="text-center">Quantity Sold</th>
                        <th class="text-right">Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rank = 1; foreach ($top_items as $item): ?>
                        <tr>
                            <td class="text-center" style="font-weight: 700;">#<?= $rank++ ?></td>
                            <td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
                            <td class="text-center"><?= number_format($item['total_qty']) ?> pcs</td>
                            <td class="text-right" style="font-weight: 700; color: #16a34a;">₱<?= number_format($item['total_sales'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Itemized Sales Transactions Ledger -->
        <div class="section-title">2. Order Transactions Ledger</div>
        <?php if (empty($orders)): ?>
            <p style="font-size: 12px; color: #64748b;">No transaction records found for the selected period.</p>
        <?php else: ?>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date & Time</th>
                        <th>Customer Name</th>
                        <th>Phone</th>
                        <th>Delivery Address</th>
                        <th class="text-right">Order Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $grand_total = 0; foreach ($orders as $order): $grand_total += $order['total_price']; ?>
                        <tr>
                            <td style="font-weight: 700;">#<?= $order['id'] ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
                            <td><strong><?= htmlspecialchars($order['customer_name']) ?></strong></td>
                            <td><?= htmlspecialchars($order['phone']) ?></td>
                            <td><?= htmlspecialchars($order['address']) ?></td>
                            <td class="text-right" style="font-weight: 600;">₱<?= number_format($order['total_price'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="5" class="text-right">GRAND TOTAL SALES:</td>
                        <td class="text-right" style="color: #16a34a;">₱<?= number_format($grand_total, 2) ?></td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Official Document Footer -->
        <div class="doc-footer">
            <div>Yum's berchg System &copy; <?= date('Y') ?> &bull; Confidential Financial Report</div>
            <div>Page 1 of 1</div>
        </div>

    </div>

</body>
</html>
