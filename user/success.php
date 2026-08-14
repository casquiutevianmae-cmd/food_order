<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$order_id = (int)($_GET['order_id'] ?? 0);
$order = null;
$order_items = [];

if ($order_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if ($order) {
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

$page_title = "Order Receipt #" . $order_id . " - Yum's berchg";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <!-- Receipt Card -->
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-body p-4 p-sm-5">
                    <div class="text-center mb-4">
                        <span class="display-1 text-success d-block mb-2">🎉</span>
                        <h2 class="fw-bold text-success mb-1">Order Confirmed!</h2>
                        <p class="text-muted small">Thank you! Your order has been placed successfully.</p>
                    </div>

                    <?php if ($order): ?>
                        <!-- Order Metadata Box -->
                        <div class="bg-light rounded-3 p-3 mb-4 text-start small">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Order Number:</span>
                                <strong class="text-dark">#<?= htmlspecialchars($order['id']) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Customer Name:</span>
                                <strong class="text-dark"><?= htmlspecialchars($order['customer_name']) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Phone Number:</span>
                                <strong class="text-dark"><?= htmlspecialchars($order['phone']) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Delivery Address:</span>
                                <strong class="text-dark text-end ms-2"><?= htmlspecialchars($order['address']) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Order Date:</span>
                                <strong class="text-dark"><?= htmlspecialchars($order['created_at']) ?></strong>
                            </div>
                        </div>

                        <!-- Itemized Table -->
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered align-middle small mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($item['item_name'] ?? 'Item') ?></td>
                                            <td class="text-center"><?= $item['quantity'] ?></td>
                                            <td class="text-end fw-semibold">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Grand Total -->
                        <div class="d-flex justify-content-between align-items-center bg-success bg-opacity-10 p-3 rounded-3 mb-4">
                            <span class="fw-bold text-success">Grand Total Paid:</span>
                            <span class="fs-3 fw-extrabold text-success">₱<?= number_format($order['total_price'], 2) ?></span>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning text-center">Order details not found.</div>
                    <?php endif; ?>

                    <a href="index.php" class="btn btn-primary btn-lg w-100 rounded-3 fw-bold">
                        <i class="bi bi-shop me-1"></i> Back to Menu
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
