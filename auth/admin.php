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
    $stock       = max(0, (int)($_POST['stock_quantity'] ?? 50));

    if (!empty($name) && $category_id > 0 && $price > 0) {
        $stmt = $pdo->prepare("INSERT INTO menu_items (category_id, name, price, description, image_url, stock_quantity) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$category_id, $name, $price, $description, $image_url, $stock]);
        $success_msg = "Menu item '{$name}' with {$stock} initial stock added successfully!";
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
    $stock       = max(0, (int)($_POST['stock_quantity'] ?? 0));

    if ($item_id > 0 && !empty($name) && $category_id > 0 && $price > 0) {
        $stmt = $pdo->prepare("UPDATE menu_items SET category_id = ?, name = ?, price = ?, description = ?, image_url = ?, stock_quantity = ? WHERE id = ?");
        $stmt->execute([$category_id, $name, $price, $description, $image_url, $stock, $item_id]);
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

$page_title = "Admin Dashboard - Yum's berchg";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4">
    <!-- Notification Banners -->
    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($success_msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error_msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Dashboard Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold text-dark mb-1">🛠️ Admin Dashboard</h2>
            <p class="text-muted small mb-0">Manage food categories, menu items, customer orders, and revenue metrics.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="../task.php" class="btn btn-outline-primary rounded-pill shadow-sm">
                <i class="bi bi-calculator me-1"></i> Task Estimator
            </a>
            <a href="../reports/index.php" class="btn btn-primary rounded-pill shadow-sm">
                <i class="bi bi-graph-up-arrow me-1"></i> Sales Reports & Export
            </a>
        </div>
    </div>

    <!-- 1. KEY ANALYTICS KPI CARDS & QUICK TOOLS -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-6 g-3 mb-4">
        <div class="col">
            <div class="card kpi-card kpi-success shadow-sm h-100 p-3">
                <div class="text-uppercase text-muted extra-small fw-bold">Total Revenue</div>
                <div class="fs-3 fw-bold text-success my-1">₱<?= number_format($total_sales, 2) ?></div>
                <div class="text-muted extra-small">Lifetime gross sales</div>
            </div>
        </div>
        <div class="col">
            <div class="card kpi-card shadow-sm h-100 p-3">
                <div class="text-uppercase text-muted extra-small fw-bold">This Month's Sales</div>
                <div class="fs-3 fw-bold text-primary my-1">₱<?= number_format($this_month_sales, 2) ?></div>
                <div class="text-muted extra-small"><?= date('F Y') ?> sales total</div>
            </div>
        </div>
        <div class="col">
            <div class="card kpi-card kpi-info shadow-sm h-100 p-3">
                <div class="text-uppercase text-muted extra-small fw-bold">Total Orders</div>
                <div class="fs-3 fw-bold text-dark my-1"><?= number_format($total_orders) ?></div>
                <div class="text-muted extra-small">Total customer orders</div>
            </div>
        </div>
        <div class="col">
            <div class="card kpi-card kpi-warning shadow-sm h-100 p-3">
                <div class="text-uppercase text-muted extra-small fw-bold">This Month's Orders</div>
                <div class="fs-3 fw-bold text-warning my-1"><?= number_format($this_month_orders) ?></div>
                <div class="text-muted extra-small">Orders placed this month</div>
            </div>
        </div>
        <div class="col">
            <div class="card kpi-card kpi-purple shadow-sm h-100 p-3">
                <div class="text-uppercase text-muted extra-small fw-bold">Avg Order Value</div>
                <div class="fs-3 fw-bold text-purple my-1">₱<?= number_format($avg_order_value, 2) ?></div>
                <div class="text-muted extra-small">Revenue per order</div>
            </div>
        </div>
        <div class="col">
            <a href="../task.php" class="text-decoration-none">
                <div class="card kpi-card shadow-sm h-100 p-3 bg-light hover-shadow" style="border-left-color: #2563eb;">
                    <div class="text-uppercase text-primary extra-small fw-bold"><i class="bi bi-calculator me-1"></i> Task Estimator</div>
                    <div class="fs-4 fw-bold text-dark my-1">Catering Quotes</div>
                    <div class="text-primary extra-small fw-semibold">Calculate Costs &rarr;</div>
                </div>
            </a>
        </div>
    </div>

    <!-- 2. MONTHLY REVENUE CHARTS & BREAKDOWN -->
    <div class="row g-4 mb-4">
        <!-- Revenue Bar Progress Charts -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-bar-chart-line text-primary me-2"></i>Monthly Revenue Trends</h5>
                <?php if (empty($monthly_revenues)): ?>
                    <p class="text-muted text-center py-4">No monthly sales recorded yet.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($monthly_revenues as $rev): ?>
                            <?php $percent = round(($rev['total_revenue'] / $max_monthly_rev) * 100); ?>
                            <div>
                                <div class="d-flex justify-content-between small fw-bold mb-1">
                                    <span><?= htmlspecialchars($rev['month_name']) ?></span>
                                    <span class="text-success">₱<?= number_format($rev['total_revenue'], 2) ?> (<?= $rev['order_count'] ?> orders)</span>
                                </div>
                                <div class="progress rounded-pill" style="height: 14px;">
                                    <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?= max(5, $percent) ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Detailed Monthly Table -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-calendar-check text-info me-2"></i>Monthly Breakdown Table</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Month / Year</th>
                                <th>Orders</th>
                                <th>Total Revenue</th>
                                <th>Avg / Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($monthly_revenues)): ?>
                                <tr><td colspan="4" class="text-center text-muted">No monthly data available.</td></tr>
                            <?php else: ?>
                                <?php foreach ($monthly_revenues as $rev): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($rev['month_name']) ?></td>
                                        <td><span class="badge bg-secondary rounded-pill"><?= $rev['order_count'] ?> orders</span></td>
                                        <td class="text-success fw-bold">₱<?= number_format($rev['total_revenue'], 2) ?></td>
                                        <td>₱<?= number_format($rev['avg_revenue'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. TOP SELLING MENU ITEMS -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-trophy text-warning me-2"></i>Top Revenue-Generating Menu Items</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Food Item</th>
                        <th>Category</th>
                        <th>Units Sold</th>
                        <th>Total Item Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_items)): ?>
                        <tr><td colspan="4" class="text-center text-muted">No sales data available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($top_items as $item): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($item['item_name']) ?></td>
                                <td><span class="badge bg-dark rounded-pill"><?= htmlspecialchars($item['category_name']) ?></span></td>
                                <td><strong><?= number_format($item['total_qty']) ?></strong> pcs</td>
                                <td class="text-success fw-bold">₱<?= number_format($item['total_revenue'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 4. CUSTOMER ORDERS LOG -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-box-seam text-primary me-2"></i>Customer Orders Log</h5>
        <?php if (empty($orders)): ?>
            <p class="text-muted text-center py-3 mb-0">No customer orders placed yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order #</th>
                            <th>Customer Info</th>
                            <th>Address</th>
                            <th>Total Price</th>
                            <th>Date & Time</th>
                            <th>Items Purchased</th>
                            <th class="text-end">Action</th>
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
                                <td class="fw-bold">#<?= $order['id'] ?></td>
                                <td>
                                    <span class="fw-bold text-dark"><?= htmlspecialchars($order['customer_name']) ?></span><br>
                                    <span class="text-muted extra-small"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($order['phone']) ?></span>
                                </td>
                                <td class="small" style="max-width: 200px;"><?= htmlspecialchars($order['address']) ?></td>
                                <td class="text-success fw-bold">₱<?= number_format($order['total_price'], 2) ?></td>
                                <td class="text-muted extra-small"><?= $order['created_at'] ?></td>
                                <td>
                                    <div class="order-items-badge-box">
                                        <?php foreach ($order_items as $oi): ?>
                                            <div>• <strong><?= htmlspecialchars($oi['item_name'] ?? 'Item Deleted') ?></strong> x <?= $oi['quantity'] ?> (₱<?= number_format($oi['price'], 2) ?>)</div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete Order #<?= $order['id'] ?>?');">
                                        <input type="hidden" name="action" value="delete_order">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- 5. CATEGORIES & MENU ITEM MANAGEMENT SECTION -->
    <div class="row g-4 mb-4">
        <!-- Categories Management -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-tags text-primary me-2"></i>Categories</h5>
                
                <form method="POST" class="mb-4">
                    <input type="hidden" name="action" value="add_category">
                    <div class="input-group">
                        <input type="text" name="category_name" class="form-control" placeholder="New Category Name..." required>
                        <button type="submit" class="btn btn-primary fw-bold">Add Category</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Category Name</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td>#<?= $cat['id'] ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($cat['name']) ?></td>
                                    <td class="text-end">
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Deleting category will also delete associated menu items. Continue?');">
                                            <input type="hidden" name="action" value="delete_category">
                                            <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add/Edit Menu Item Form -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-pencil-square text-success me-2"></i>
                        <?= $edit_item ? 'Edit Menu Item' : 'Add New Menu Item' ?>
                    </h5>
                    <?php if ($edit_item): ?>
                        <a href="admin.php" class="btn btn-sm btn-outline-secondary rounded-pill">Cancel Editing</a>
                    <?php endif; ?>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="<?= $edit_item ? 'edit_menu_item' : 'add_menu_item' ?>">
                    <?php if ($edit_item): ?>
                        <input type="hidden" name="item_id" value="<?= $edit_item['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Item Name</label>
                        <input type="text" name="item_name" class="form-control" required placeholder="e.g. Bacon Cheeseburger" value="<?= htmlspecialchars($edit_item['name'] ?? '') ?>">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Category</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($edit_item && $edit_item['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Price (₱)</label>
                            <input type="number" step="0.01" name="price" class="form-control" required placeholder="89.99" value="<?= htmlspecialchars($edit_item['price'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3 bg-light p-3 rounded-3 border">
                        <label class="form-label fw-semibold small text-dark d-flex justify-content-between">
                            <span>Stock Quantity</span>
                            <?php if ($edit_item): ?>
                                <span class="text-muted">Current: <?= (int)$edit_item['stock_quantity'] ?> units</span>
                            <?php endif; ?>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-boxes"></i></span>
                            <input type="number" name="stock_quantity" class="form-control fw-bold" min="0" placeholder="50" value="<?= htmlspecialchars($edit_item['stock_quantity'] ?? '50') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Brief description of food item..."><?= htmlspecialchars($edit_item['description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Image Path</label>
                        <input type="text" name="image_url" class="form-control" placeholder="uploads/cheeseburger.png" value="<?= htmlspecialchars($edit_item['image_url'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-3 fw-bold">
                        <?= $edit_item ? 'Update Menu Item' : 'Save Menu Item' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- 6. MENU ITEMS CATALOG TABLE -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-menu-app text-dark me-2"></i>Menu Items Catalog</h5>
            <a href="../inventory/index.php" class="btn btn-outline-success btn-sm rounded-pill fw-bold">
                <i class="bi bi-boxes me-1"></i> Open Full Inventory Manager
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Image</th>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock Level</th>
                        <th>Description</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($menu_items as $item): ?>
                        <?php 
                            $img_path = !empty($item['image_url']) ? '../' . $item['image_url'] : '../uploads/default_food.png'; 
                            $stk = (int)($item['stock_quantity'] ?? 0);
                            if ($stk <= 0) {
                                $stk_badge = '<span class="badge bg-danger">Out of Stock (0)</span>';
                            } elseif ($stk < 10) {
                                $stk_badge = '<span class="badge bg-warning text-dark">Low Stock (' . $stk . ')</span>';
                            } else {
                                $stk_badge = '<span class="badge bg-success">In Stock (' . $stk . ')</span>';
                            }
                        ?>
                        <tr>
                            <td>
                                <img src="<?= htmlspecialchars($img_path) ?>" alt="Food" class="img-thumbnail rounded-3" style="width: 50px; height: 50px; object-fit: cover;">
                            </td>
                            <td class="fw-bold"><?= htmlspecialchars($item['name']) ?></td>
                            <td><span class="badge bg-dark rounded-pill"><?= htmlspecialchars($item['category_name']) ?></span></td>
                            <td class="text-success fw-bold">₱<?= number_format($item['price'], 2) ?></td>
                            <td><?= $stk_badge ?></td>
                            <td class="text-muted small" style="max-width: 250px;"><?= htmlspecialchars($item['description']) ?></td>
                            <td class="text-end">
                                <a href="admin.php?edit_item_id=<?= $item['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill me-1">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete <?= htmlspecialchars($item['name']) ?>?');">
                                    <input type="hidden" name="action" value="delete_menu_item">
                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
