<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Authentication Guard: Admin access required
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$success_msg = '';
$error_msg = '';

// --- INVENTORY ACTION HANDLERS ---

// 1. Quick Adjust Stock (Add/Subtract/Set)
if (isset($_POST['action']) && $_POST['action'] === 'adjust_stock') {
    $item_id = (int)($_POST['item_id'] ?? 0);
    $adjustment_type = $_POST['adj_type'] ?? 'set'; // 'add', 'subtract', 'set'
    $amount = (int)($_POST['amount'] ?? 0);

    if ($item_id > 0) {
        if ($adjustment_type === 'add') {
            $stmt = $pdo->prepare("UPDATE menu_items SET stock_quantity = stock_quantity + ? WHERE id = ?");
            $stmt->execute([max(1, $amount), $item_id]);
            $success_msg = "Stock increased successfully!";
        } elseif ($adjustment_type === 'subtract') {
            $stmt = $pdo->prepare("UPDATE menu_items SET stock_quantity = GREATEST(0, stock_quantity - ?) WHERE id = ?");
            $stmt->execute([max(1, $amount), $item_id]);
            $success_msg = "Stock reduced successfully!";
        } else {
            $new_stock = max(0, $amount);
            $stmt = $pdo->prepare("UPDATE menu_items SET stock_quantity = ? WHERE id = ?");
            $stmt->execute([$new_stock, $item_id]);
            $success_msg = "Stock level updated to {$new_stock}!";
        }
    }
}

// 2. Bulk Restock Low Stock Items
if (isset($_POST['action']) && $_POST['action'] === 'restock_low') {
    $restock_qty = (int)($_POST['restock_amount'] ?? 50);
    $stmt = $pdo->prepare("UPDATE menu_items SET stock_quantity = stock_quantity + ? WHERE stock_quantity < 10");
    $stmt->execute([$restock_qty]);
    $count = $stmt->rowCount();
    $success_msg = "Successfully restocked {$count} low-stock products by +{$restock_qty} units each!";
}

// 3. Add New Product with Stock
if (isset($_POST['action']) && $_POST['action'] === 'add_product') {
    $category_id = (int)$_POST['category_id'];
    $name        = trim($_POST['item_name'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $image_url   = trim($_POST['image_url'] ?? '');
    $stock       = max(0, (int)($_POST['stock_quantity'] ?? 50));

    if (!empty($name) && $category_id > 0 && $price > 0) {
        $stmt = $pdo->prepare("INSERT INTO menu_items (category_id, name, price, description, image_url, stock_quantity) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$category_id, $name, $price, $description, $image_url, $stock]);
        $success_msg = "Product '{$name}' with {$stock} initial units added successfully!";
    } else {
        $error_msg = "Please fill in valid Product Name, Category, and Price.";
    }
}

// 4. Edit Product Details & Stock
if (isset($_POST['action']) && $_POST['action'] === 'edit_product') {
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
        $success_msg = "Product '{$name}' inventory updated successfully!";
    } else {
        $error_msg = "Failed to update product details. Check your inputs.";
    }
}

// 5. Delete Product
if (isset($_POST['action']) && $_POST['action'] === 'delete_product') {
    $item_id = (int)$_POST['item_id'];
    $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
    $stmt->execute([$item_id]);
    $success_msg = "Product removed from inventory.";
}

// --- FETCH METRICS & DATA ---
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// KPI Metrics
$total_products = (int)$pdo->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
$total_units    = (int)$pdo->query("SELECT COALESCE(SUM(stock_quantity), 0) FROM menu_items")->fetchColumn();
$low_stock_count = (int)$pdo->query("SELECT COUNT(*) FROM menu_items WHERE stock_quantity > 0 AND stock_quantity < 10")->fetchColumn();
$out_of_stock_count = (int)$pdo->query("SELECT COUNT(*) FROM menu_items WHERE stock_quantity <= 0")->fetchColumn();

// Filter & Search Parameters
$search_query  = trim($_GET['search'] ?? '');
$filter_status = $_GET['status'] ?? 'all'; // 'all', 'in_stock', 'low_stock', 'out_of_stock'
$filter_cat    = (int)($_GET['category_id'] ?? 0);

$where = ["1=1"];
$params = [];

if (!empty($search_query)) {
    $where[] = "m.name LIKE ?";
    $params[] = "%" . $search_query . "%";
}

if ($filter_cat > 0) {
    $where[] = "m.category_id = ?";
    $params[] = $filter_cat;
}

if ($filter_status === 'in_stock') {
    $where[] = "m.stock_quantity >= 10";
} elseif ($filter_status === 'low_stock') {
    $where[] = "m.stock_quantity > 0 AND m.stock_quantity < 10";
} elseif ($filter_status === 'out_of_stock') {
    $where[] = "m.stock_quantity <= 0";
}

$sql = "
    SELECT m.*, c.name AS category_name 
    FROM menu_items m 
    JOIN categories c ON m.category_id = c.id 
    WHERE " . implode(" AND ", $where) . " 
    ORDER BY m.stock_quantity ASC, m.name ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$inventory_items = $stmt->fetchAll();

$page_title = "Product Inventory Management - Yum's berchg";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4">
    <!-- Header Title & XAMPP DB Status Badge -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold text-dark mb-0">📦 Product Inventory Management</h2>
        </div>
        <div class="d-flex gap-2">
            <form method="POST" class="d-inline" onsubmit="return confirm('Restock all low-stock products by +50 units?');">
                <input type="hidden" name="action" value="restock_low">
                <input type="hidden" name="restock_amount" value="50">
                <button type="submit" class="btn btn-outline-warning rounded-pill fw-bold btn-sm px-3" <?= $low_stock_count === 0 && $out_of_stock_count === 0 ? 'disabled' : '' ?>>
                    <i class="bi bi-arrow-repeat me-1"></i> Quick Restock All Low Items (+50)
                </button>
            </form>
            <button class="btn btn-success rounded-pill fw-bold btn-sm px-3" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="bi bi-plus-circle me-1"></i> Add New Product
            </button>
        </div>
    </div>

    <!-- Alert Notifications -->
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

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Total Products</span>
                        <h3 class="fw-bold mb-0 text-dark"><?= number_format($total_products) ?></h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle fs-4">
                        <i class="bi bi-box-seam"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Units in Warehouse</span>
                        <h3 class="fw-bold mb-0 text-success"><?= number_format($total_units) ?></h3>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle fs-4">
                        <i class="bi bi-stack"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Low Stock Alert (&lt;10)</span>
                        <h3 class="fw-bold mb-0 text-warning"><?= number_format($low_stock_count) ?></h3>
                    </div>
                    <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle fs-4">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Out of Stock (0)</span>
                        <h3 class="fw-bold mb-0 text-danger"><?= number_format($out_of_stock_count) ?></h3>
                    </div>
                    <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle fs-4">
                        <i class="bi bi-x-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
        <form method="GET" action="index.php" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search product name..." value="<?= htmlspecialchars($search_query) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="category_id" class="form-select">
                    <option value="0">-- All Categories --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $filter_cat === $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All Stock Statuses</option>
                    <option value="in_stock" <?= $filter_status === 'in_stock' ? 'selected' : '' ?>>In Stock (&ge;10)</option>
                    <option value="low_stock" <?= $filter_status === 'low_stock' ? 'selected' : '' ?>>Low Stock (&lt;10)</option>
                    <option value="out_of_stock" <?= $filter_status === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock (0)</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100 fw-bold">Filter</button>
                <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Inventory Items Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0">Product Stock List (<?= count($inventory_items) ?> items)</h5>
            <span class="text-muted small">Real-time sync with XAMPP MySQL database</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Product Details</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock Level</th>
                        <th>Status</th>
                        <th class="text-center">Manual Stock Adjust</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventory_items)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                No product inventory items found matching your filters.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inventory_items as $item): ?>
                            <?php 
                                $img_src = !empty($item['image_url']) ? '../' . $item['image_url'] : '../uploads/default_food.png';
                                $qty = (int)$item['stock_quantity'];
                                if ($qty <= 0) {
                                    $badge_class = 'bg-danger';
                                    $status_label = 'Out of Stock';
                                } elseif ($qty < 10) {
                                    $badge_class = 'bg-warning text-dark';
                                    $status_label = 'Low Stock Alert';
                                } else {
                                    $badge_class = 'bg-success';
                                    $status_label = 'In Stock';
                                }
                            ?>
                            <tr>
                                <td class="fw-semibold text-muted">#<?= $item['id'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= htmlspecialchars($img_src) ?>" alt="" class="rounded-3 object-fit-cover" style="width: 48px; height: 48px;">
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($item['name']) ?></div>
                                            <div class="text-muted small text-truncate" style="max-width: 250px;"><?= htmlspecialchars($item['description'] ?? 'No description') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($item['category_name']) ?></span></td>
                                <td class="fw-bold text-success">₱<?= number_format($item['price'], 2) ?></td>
                                <td>
                                    <div class="fw-extrabold fs-5 <?= $qty <= 0 ? 'text-danger' : ($qty < 10 ? 'text-warning' : 'text-dark') ?>">
                                        <?= $qty ?> <span class="fs-6 text-muted fw-normal">units</span>
                                    </div>
                                </td>
                                <td><span class="badge <?= $badge_class ?> rounded-pill px-3 py-2"><?= $status_label ?></span></td>
                                <td class="text-center">
                                    <!-- Manual Quantity Adjustment Form -->
                                    <form method="POST" class="d-inline-flex align-items-center justify-content-center gap-1" style="max-width: 230px;">
                                        <input type="hidden" name="action" value="adjust_stock">
                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                        <div class="input-group input-group-sm">
                                            <button type="submit" name="adj_type" value="subtract" class="btn btn-outline-danger px-2" title="Subtract quantity from stock">-</button>
                                            <input type="number" name="amount" value="1" min="1" max="9999" class="form-control text-center fw-bold px-1" style="width: 55px;" required>
                                            <button type="submit" name="adj_type" value="add" class="btn btn-outline-success px-2" title="Add quantity to stock">+</button>
                                        </div>
                                        <button type="submit" name="adj_type" value="set" class="btn btn-sm btn-primary rounded-3 px-2 text-nowrap" title="Set total stock to exact quantity">Set</button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-outline-primary btn-sm rounded-pill px-3 me-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal<?= $item['id'] ?>">
                                        <i class="bi bi-pencil me-1"></i> Edit Stock
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <input type="hidden" name="action" value="delete_product">
                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle" title="Delete Product">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>

                                    <!-- Edit Product & Stock Modal -->
                                    <div class="modal fade text-start" id="editModal<?= $item['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <div class="modal-header border-bottom">
                                                    <h5 class="modal-title fw-bold">Edit Product Inventory - <?= htmlspecialchars($item['name']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body p-4">
                                                        <input type="hidden" name="action" value="edit_product">
                                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">

                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold small text-muted">Product Name</label>
                                                            <input type="text" name="item_name" class="form-control" value="<?= htmlspecialchars($item['name']) ?>" required>
                                                        </div>

                                                        <div class="row g-3 mb-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold small text-muted">Category</label>
                                                                <select name="category_id" class="form-select" required>
                                                                    <?php foreach ($categories as $cat): ?>
                                                                        <option value="<?= $cat['id'] ?>" <?= $item['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($cat['name']) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold small text-muted">Price (₱)</label>
                                                                <input type="number" step="0.01" name="price" class="form-control" value="<?= $item['price'] ?>" required>
                                                            </div>
                                                        </div>

                                                        <div class="mb-3 bg-light p-3 rounded-3 border">
                                                            <label class="form-label fw-bold small text-dark d-flex justify-content-between">
                                                                <span>Stock Quantity (Warehouse Inventory)</span>
                                                                <span class="text-primary">Current: <?= $qty ?></span>
                                                            </label>
                                                            <div class="input-group">
                                                                <span class="input-group-text bg-white"><i class="bi bi-boxes"></i></span>
                                                                <input type="number" name="stock_quantity" class="form-control fw-bold" value="<?= $qty ?>" min="0" required>
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold small text-muted">Image Path / URL</label>
                                                            <input type="text" name="image_url" class="form-control" value="<?= htmlspecialchars($item['image_url'] ?? '') ?>" placeholder="e.g. uploads/burger.jpg">
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold small text-muted">Description</label>
                                                            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top bg-light">
                                                        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add New Product with Stock -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2 text-success"></i>Add New Inventory Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="add_product">

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Product Name</label>
                        <input type="text" name="item_name" class="form-control" placeholder="e.g. Double Cheese Bacon Burger" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Category</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Price (₱)</label>
                            <input type="number" step="0.01" name="price" class="form-control" placeholder="89.99" required>
                        </div>
                    </div>

                    <div class="mb-3 bg-light p-3 rounded-3 border">
                        <label class="form-label fw-bold small text-dark">Initial Stock Quantity</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-boxes"></i></span>
                            <input type="number" name="stock_quantity" class="form-control fw-bold" value="50" min="0" required>
                        </div>
                        <div class="form-text">Set the starting inventory amount for this food product.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Image Path / URL (Optional)</label>
                        <input type="text" name="image_url" class="form-control" placeholder="uploads/cheeseburger.png">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Brief description of the product..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Add to Inventory</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
