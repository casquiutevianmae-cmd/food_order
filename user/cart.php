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
        if ($change > 0) {
            // Check available stock in database
            $stmt_stk = $pdo->prepare("SELECT stock_quantity FROM menu_items WHERE id = ?");
            $stmt_stk->execute([$item_id]);
            $stk = (int)$stmt_stk->fetchColumn();
            if (($_SESSION['cart'][$item_id] + $change) <= $stk) {
                $_SESSION['cart'][$item_id] += $change;
            } else {
                header("Location: cart.php?stock_exceeded=1");
                exit;
            }
        } else {
            $_SESSION['cart'][$item_id] += $change;
            if ($_SESSION['cart'][$item_id] <= 0) {
                unset($_SESSION['cart'][$item_id]);
            }
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

if (isset($_GET['stock_exceeded'])) {
    $error = "Cannot add more units. You have reached the maximum available inventory stock for that product.";
}

if (!empty($cart)) {
    $placeholders = implode(',', array_fill(0, count($cart), '?'));
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id IN ($placeholders)");
    $stmt->execute(array_keys($cart));
    $cart_items = $stmt->fetchAll();
}

// Pre-fill customer name if logged in
$logged_in_name = $_SESSION['username'] ?? '';

// Handle Place Order action with Inventory Stock Check & Deduct Transaction
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['place_order'])) {
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
            try {
                // Start MySQL Transaction
                $pdo->beginTransaction();

                // 1. Check stock availability for all cart items
                $stock_errors = [];
                foreach ($cart_items as $item) {
                    $requested_qty = $_SESSION['cart'][$item['id']] ?? 0;
                    if ($requested_qty > 0) {
                        $stmt_chk = $pdo->prepare("SELECT stock_quantity FROM menu_items WHERE id = ? FOR UPDATE");
                        $stmt_chk->execute([$item['id']]);
                        $current_stock = (int)$stmt_chk->fetchColumn();

                        if ($requested_qty > $current_stock) {
                            $stock_errors[] = "'{$item['name']}' has only {$current_stock} item(s) left in inventory.";
                        }
                    }
                }

                if (!empty($stock_errors)) {
                    $pdo->rollBack();
                    $error = "Insufficient Inventory Stock: " . implode(" ", $stock_errors);
                } else {
                    // 2. Calculate grand total
                    $grand_total = 0;
                    foreach ($cart_items as $item) {
                        $qty = $_SESSION['cart'][$item['id']] ?? 0;
                        $grand_total += $item['price'] * $qty;
                    }

                    // 3. Insert order record
                    $stmt = $pdo->prepare("INSERT INTO orders (customer_name, phone, address, total_price, user_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $phone, $address, $grand_total, $user_id]);
                    $order_id = $pdo->lastInsertId();

                    // 4. Insert individual order items & DECREMENT STOCK
                    $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)");
                    $stmt_deduct = $pdo->prepare("UPDATE menu_items SET stock_quantity = stock_quantity - ? WHERE id = ?");

                    foreach ($cart_items as $item) {
                        $qty = $_SESSION['cart'][$item['id']] ?? 0;
                        if ($qty > 0) {
                            $stmt_item->execute([$order_id, $item['id'], $qty, $item['price']]);
                            $stmt_deduct->execute([$qty, $item['id']]);
                        }
                    }

                    // Commit transaction
                    $pdo->commit();

                    // Clear cart and redirect
                    $_SESSION['cart'] = [];
                    header("Location: success.php?order_id=" . $order_id);
                    exit;
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = "Failed to process order: " . $e->getMessage();
            }
        }
    }
}

$page_title = "Your Cart & Checkout - Yum's berchg";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">🛒 Shopping Cart</h2>
        <a href="index.php" class="btn btn-outline-primary rounded-pill btn-sm fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to Menu
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($cart_items)): ?>
        <div class="card border-0 shadow-sm rounded-4 text-center p-5 my-4">
            <div class="display-1 text-muted mb-3">🛒</div>
            <h3 class="fw-bold text-dark">Your Cart is Empty</h3>
            <p class="text-muted mb-4">Looks like you haven't added any food items to your cart yet.</p>
            <div>
                <a href="index.php" class="btn btn-primary rounded-pill px-4 py-2 fw-bold">
                    <i class="bi bi-shop me-1"></i> Browse Delicious Menu
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4 mb-5">
            <!-- Left Column: Cart Table -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 border-bottom border-light">
                        <h5 class="fw-bold text-dark mb-0">Order Summary (<?= count($cart_items) ?> items)</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Price</th>
                                    <th class="text-center">Qty</th>
                                    <th>Subtotal</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $grand_total = 0;
                                foreach ($cart_items as $item):
                                    $qty = $cart[$item['id']] ?? 0;
                                    if ($qty <= 0) continue;
                                    $subtotal = $item['price'] * $qty;
                                    $grand_total += $subtotal;
                                    ?>
                                    <?php $avail_stk = (int)($item['stock_quantity'] ?? 0); ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-dark d-block"><?= htmlspecialchars($item['name']) ?></span>
                                            <span class="small text-muted">Available stock: <?= $avail_stk ?></span>
                                        </td>
                                        <td>₱<?= number_format($item['price'], 2) ?></td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                                    <input type="hidden" name="change" value="-1">
                                                    <button type="submit" name="update_qty" class="btn btn-outline-secondary btn-sm rounded-circle px-2 py-0">-</button>
                                                </form>
                                                <span class="fw-bold px-2"><?= $qty ?></span>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                                    <input type="hidden" name="change" value="1">
                                                    <button type="submit" name="update_qty" class="btn btn-outline-secondary btn-sm rounded-circle px-2 py-0" <?= $qty >= $avail_stk ? 'disabled title="Maximum stock limit reached"' : '' ?>>+</button>
                                                </form>
                                            </div>
                                        </td>
                                        <td class="fw-bold text-success">₱<?= number_format($subtotal, 2) ?></td>
                                        <td class="text-end">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                                <button type="submit" name="remove_item" class="btn btn-outline-danger btn-sm rounded-pill">
                                                    <i class="bi bi-trash"></i>
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

            <!-- Right Column: Delivery Form & Checkout Total -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold text-dark mb-3">Total & Delivery Details</h5>
                    
                    <!-- Total Price Box -->
                    <div class="bg-light rounded-3 p-3 mb-4 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold text-secondary">Grand Total:</span>
                        <span class="fs-2 fw-extrabold text-success">₱<?= number_format($grand_total, 2) ?></span>
                    </div>

                    <!-- Delivery Details Form -->
                    <form method="POST" action="cart.php" novalidate>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                                <input type="text" name="name" class="form-control bg-light" required placeholder="Recipient Name" value="<?= htmlspecialchars($logged_in_name) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-telephone"></i></span>
                                <input type="text" name="phone" class="form-control bg-light" required placeholder="e.g. 09171234567">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-secondary small">Delivery Address</label>
                            <textarea name="address" class="form-control bg-light" required rows="3" placeholder="Street, Barangay, City, Landmark"></textarea>
                        </div>

                        <button type="submit" name="place_order" class="btn btn-success btn-lg w-100 rounded-3 shadow-sm fw-bold py-3">
                            <i class="bi bi-check-circle-fill me-1"></i> Place Order Now
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
