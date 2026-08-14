<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Authentication Guard: Must log in before accessing storefront
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Initialize cart if empty
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Add to Cart action with Quantity
if (isset($_POST['add_to_cart'])) {
    $item_id  = (int)$_POST['item_id'];
    $requested_qty = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
    
    // Check available stock in MySQL database
    $stmt_stock = $pdo->prepare("SELECT stock_quantity FROM menu_items WHERE id = ?");
    $stmt_stock->execute([$item_id]);
    $avail_stock = (int)$stmt_stock->fetchColumn();
    
    $current_in_cart = $_SESSION['cart'][$item_id] ?? 0;
    if ($avail_stock > 0 && ($current_in_cart + $requested_qty) <= $avail_stock) {
        $_SESSION['cart'][$item_id] = $current_in_cart + $requested_qty;
        header("Location: index.php?added=" . $requested_qty);
        exit;
    } else {
        $max_addable = max(0, $avail_stock - $current_in_cart);
        if ($max_addable > 0) {
            $_SESSION['cart'][$item_id] = $current_in_cart + $max_addable;
            header("Location: index.php?added=" . $max_addable . "&stock_limit=1");
        } else {
            header("Location: index.php?out_of_stock=1");
        }
        exit;
    }
}

// Fetch categories for filter tabs
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$selected_cat = (int)($_GET['category_id'] ?? 0);

// Fetch items grouped by category
if ($selected_cat > 0) {
    $stmt = $pdo->prepare("SELECT m.*, c.name AS category FROM menu_items m JOIN categories c ON m.category_id = c.id WHERE m.category_id = ? ORDER BY m.name");
    $stmt->execute([$selected_cat]);
} else {
    $stmt = $pdo->query("SELECT m.*, c.name AS category FROM menu_items m JOIN categories c ON m.category_id = c.id ORDER BY c.name, m.name");
}
$menu = $stmt->fetchAll();

$page_title = "Yum's berchg - Food Storefront";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <!-- Hero Banner -->
    <div class="bg-gradient bg-dark text-white rounded-4 p-4 p-md-5 mb-4 shadow-sm position-relative overflow-hidden">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-warning text-dark fw-bold mb-2">🔥 Fresh & Hot Delivered Fast</span>
                <h1 class="display-5 fw-bold mb-3">Craving Something Delicious?</h1>
                <a href="../task.php" class="btn btn-outline-light rounded-pill px-4 fw-bold">
                    <i class="bi bi-calculator me-1"></i> Catering & Task Estimator
                </a>
            </div>
            <div class="col-lg-4 text-center d-none d-lg-block">
                <span class="display-1">🍔🍕🥤</span>
            </div>
        </div>
    </div>

    <!-- Added to Cart / Stock Limit Alerts -->
    <?php if (isset($_GET['added'])): ?>
        <?php $qty_added = (int)$_GET['added']; ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4 d-flex align-items-center justify-content-between" role="alert">
            <div>
                <i class="bi bi-check-circle-fill fs-5 me-2 align-middle"></i>
                <strong>Added to Cart!</strong> Successfully added <?= $qty_added ?> <?= $qty_added > 1 ? 'items' : 'item' ?>.
                <?php if (isset($_GET['stock_limit'])): ?>
                    <span class="text-dark fw-semibold ms-2">(Quantity adjusted to maximum available stock)</span>
                <?php endif; ?>
            </div>
            <a href="cart.php" class="btn btn-sm btn-success rounded-pill px-3 ms-3">Go to Checkout <i class="bi bi-arrow-right"></i></a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif (isset($_GET['out_of_stock'])): ?>
        <div class="alert alert-warning alert-dismissible fade show rounded-3 shadow-sm mb-4 d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-warning align-middle"></i>
            <div>
                <strong>Stock Limit Reached!</strong> You already have the maximum available inventory quantity of this item in your cart.
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Category Filter Pills -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h3 class="fw-bold mb-0 text-dark">Explore Our Menu</h3>
        <div class="nav nav-pills gap-1">
            <a class="nav-link rounded-pill px-3 <?= $selected_cat === 0 ? 'active' : 'bg-white border text-dark' ?>" href="index.php">All Items</a>
            <?php foreach ($categories as $cat): ?>
                <a class="nav-link rounded-pill px-3 <?= $selected_cat === $cat['id'] ? 'active' : 'bg-white border text-dark' ?>" href="index.php?category_id=<?= $cat['id'] ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Food Menu Grid -->
    <?php if (empty($menu)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-4">
            <div class="fs-1 text-muted mb-2">🍽️</div>
            <h4 class="fw-bold text-secondary">No Food Items Available</h4>
            <p class="text-muted mb-0">Try selecting another category or check back later.</p>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-5">
            <?php foreach ($menu as $item): ?>
                <?php 
                    $img_src = !empty($item['image_url']) ? '../' . $item['image_url'] : '../uploads/default_food.png'; 
                    $stk = (int)($item['stock_quantity'] ?? 0);
                    $in_cart = $_SESSION['cart'][$item['id']] ?? 0;
                    $rem_stock = max(0, $stk - $in_cart);
                ?>
                <div class="col">
                    <!-- Bootstrap Food Card -->
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden hover-shadow">
                        <div class="food-card-img-wrapper position-relative">
                            <img src="<?= htmlspecialchars($img_src) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                            <span class="badge bg-dark bg-opacity-75 backdrop-blur position-absolute top-0 start-0 m-3 px-3 py-2 rounded-pill">
                                <?= htmlspecialchars($item['category']) ?>
                            </span>
                            
                            <!-- Stock Status Overlay Badge -->
                            <?php if ($stk <= 0): ?>
                                <div class="position-absolute top-0 end-0 m-3">
                                    <span class="badge bg-danger shadow-sm px-3 py-2 rounded-pill">
                                        <i class="bi bi-x-circle me-1"></i> Out of Stock
                                    </span>
                                </div>
                            <?php elseif ($stk < 10): ?>
                                <div class="position-absolute top-0 end-0 m-3">
                                    <span class="badge bg-warning text-dark shadow-sm px-3 py-2 rounded-pill">
                                        <i class="bi bi-exclamation-triangle me-1"></i> Only <?= $stk ?> Left!
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body d-flex flex-column p-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title fw-bold text-dark mb-0"><?= htmlspecialchars($item['name']) ?></h5>
                                <span class="fs-5 fw-extrabold text-success">₱<?= number_format($item['price'], 2) ?></span>
                            </div>
                            <p class="card-text text-muted small flex-grow-1 mb-3"><?= htmlspecialchars($item['description']) ?></p>
                            
                            <!-- Add to Cart Form -->
                            <form method="POST" class="mt-auto">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <?php if ($rem_stock <= 0): ?>
                                    <button type="button" class="btn btn-secondary w-100 rounded-3 fw-bold disabled" disabled>
                                        <i class="bi bi-x-circle me-1"></i> Out of Stock
                                    </button>
                                <?php else: ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <!-- Quantity Step Control -->
                                        <div class="input-group qty-input-group">
                                            <button class="btn btn-outline-secondary btn-sm" type="button" onclick="var input=this.nextElementSibling; if(input.value>1) input.value--;">-</button>
                                            <input type="number" name="quantity" value="1" min="1" max="<?= $rem_stock ?>" class="form-control form-control-sm text-center fw-bold bg-white" readonly>
                                            <button class="btn btn-outline-secondary btn-sm" type="button" onclick="var input=this.previousElementSibling; if(parseInt(input.value)<<?= $rem_stock ?>) input.value++;">+</button>
                                        </div>
                                        <button type="submit" name="add_to_cart" class="btn btn-primary rounded-3 flex-grow-1 fw-bold">
                                            <i class="bi bi-cart-plus me-1"></i> Add
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
