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

// 4. Fetch Low Stock Inventory Count
$low_stock_items_count = (int)$pdo->query("SELECT COUNT(*) FROM menu_items WHERE stock_quantity < 10")->fetchColumn();

$page_title = "Sales Analytics & Reports - Yum's berchg";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold text-dark mb-1">📊 Sales & Revenue Analytics</h2>
            <p class="text-muted small mb-0">Generate financial reports, view top products, and export data.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="../inventory/index.php" class="btn btn-outline-success rounded-pill btn-sm fw-semibold position-relative">
                <i class="bi bi-boxes me-1"></i> Product Inventory
                <?php if ($low_stock_items_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" title="<?= $low_stock_items_count ?> items low in stock">
                        <span class="visually-hidden">Low stock alerts</span>
                    </span>
                <?php endif; ?>
            </a>
            <a href="../auth/admin.php" class="btn btn-outline-secondary rounded-pill btn-sm fw-semibold">
                <i class="bi bi-speedometer2 me-1"></i> Admin Dashboard
            </a>
        </div>
    </div>

    <!-- Filter Form & Export Card -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-lg-7">
                <form method="GET" action="index.php" class="row align-items-center g-2">
                    <div class="col-auto">
                        <label class="col-form-label fw-bold small text-muted">From:</label>
                    </div>
                    <div class="col-auto">
                        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                    </div>
                    <div class="col-auto">
                        <label class="col-form-label fw-bold small text-muted">To:</label>
                    </div>
                    <div class="col-auto">
                        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary fw-bold">
                            <i class="bi bi-search me-1"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
            <div class="col-lg-5 text-lg-end">
                <div class="d-flex gap-2 justify-content-lg-end">
                    <a href="generate_pdf.php?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" target="_blank" class="btn btn-danger rounded-pill fw-bold">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF Report
                    </a>
                    <a href="export_csv.php?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="btn btn-success rounded-pill fw-bold">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Summary Metrics -->
    <div class="row row-cols-1 row-cols-md-3 g-3 mb-4">
        <div class="col">
            <div class="card kpi-card kpi-success shadow-sm p-4 h-100">
                <span class="text-uppercase text-muted extra-small fw-bold">Period Total Revenue</span>
                <div class="fs-2 fw-extrabold text-success my-1">₱<?= number_format($summary['total_revenue'], 2) ?></div>
                <span class="text-muted small">Gross sales revenue</span>
            </div>
        </div>
        <div class="col">
            <div class="card kpi-card shadow-sm p-4 h-100">
                <span class="text-uppercase text-muted extra-small fw-bold">Completed Orders</span>
                <div class="fs-2 fw-extrabold text-primary my-1"><?= number_format($summary['total_orders']) ?></div>
                <span class="text-muted small">Total completed transactions</span>
            </div>
        </div>
        <div class="col">
            <div class="card kpi-card kpi-info shadow-sm p-4 h-100">
                <span class="text-uppercase text-muted extra-small fw-bold">Average Order Value</span>
                <div class="fs-2 fw-extrabold text-info my-1">₱<?= number_format($summary['avg_order_value'], 2) ?></div>
                <span class="text-muted small">Average revenue per order</span>
            </div>
        </div>
    </div>

    <!-- Top Selling Products Table -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-fire text-danger me-2"></i>Top Selling Menu Items</h5>
        <?php if (empty($top_items)): ?>
            <p class="text-muted mb-0">No items sold during the selected date range.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item Name</th>
                            <th>Units Sold</th>
                            <th>Total Revenue Generated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_items as $item): ?>
                            <tr>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($item['name']) ?></td>
                                <td><?= number_format($item['total_qty']) ?> pcs</td>
                                <td class="text-success fw-bold">₱<?= number_format($item['total_sales'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Detailed Transactions Ledger Table -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-5">
        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-receipt text-primary me-2"></i>Detailed Transaction Ledger (<?= count($orders) ?> Orders)</h5>
        <?php if (empty($orders)): ?>
            <p class="text-muted mb-0">No orders recorded for this period.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
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
                                <td class="fw-bold">#<?= $order['id'] ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($order['customer_name']) ?></td>
                                <td><?= htmlspecialchars($order['phone']) ?></td>
                                <td class="small" style="max-width: 250px;"><?= htmlspecialchars($order['address']) ?></td>
                                <td class="text-muted small"><?= htmlspecialchars($order['created_at']) ?></td>
                                <td class="text-success fw-bold">₱<?= number_format($order['total_price'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
