<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Authentication Guard: Must log in before accessing index dashboard
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
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
    $_SESSION['cart'][$item_id] = ($_SESSION['cart'][$item_id] ?? 0) + $quantity;
    header("Location: index.php?added=" . $quantity);
    exit;
}

// Fetch items grouped by category
$stmt = $pdo->query("SELECT m.*, c.name AS category FROM menu_items m JOIN categories c ON m.category_id = c.id ORDER BY c.name, m.name");
$menu = $stmt->fetchAll();

$cart_count = array_sum($_SESSION['cart']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yum's berchg</title>
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
            max-width: 1050px;
            margin: auto;
            padding: 20px;
            background: var(--bg-color);
            color: var(--text-main);
        }

        /* Top Navigation Header */
        .nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            margin-bottom: 28px;
            border: 1px solid var(--border-color);
        }
        .nav-bar h2 {
            margin: 0;
            font-size: 24px;
            color: #0f172a;
        }

        /* Upper Right Side Navigation Controls (Cart & Logout side-by-side) */
        .nav-right-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-cart-nav {
            background: #1e293b;
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s, transform 0.1s;
        }
        .btn-cart-nav:hover {
            background: #0f172a;
            transform: translateY(-1px);
        }
        .cart-count {
            background: #2563eb;
            color: #ffffff;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
        }

        .btn-admin-nav {
            background: #334155;
            color: #ffffff;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-admin-nav:hover {
            background: #1e293b;
        }

        .btn-logout-nav {
            background: #ef4444;
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-logout-nav:hover {
            background: #dc2626;
        }

        .section-title {
            font-size: 20px;
            margin-bottom: 20px;
            color: #1e293b;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 8px;
        }

        /* Menu Grid */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 24px;
        }

        .menu-card {
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .menu-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        }

        .image-container {
            height: 190px;
            width: 100%;
            position: relative;
            background: #f1f5f9;
            overflow: hidden;
        }
        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .menu-card:hover .image-container img {
            transform: scale(1.05);
        }

        .category-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(4px);
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .card-body {
            padding: 18px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        .card-header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        .item-name {
            font-size: 17px;
            font-weight: 700;
            margin: 0;
            color: #0f172a;
        }
        .item-price {
            font-size: 18px;
            font-weight: 800;
            color: var(--success);
            white-space: nowrap;
            margin-left: 10px;
        }
        .item-desc {
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.5;
            margin: 0 0 16px 0;
            flex-grow: 1;
        }

        .add-cart-form {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: auto;
        }
        .qty-control {
            display: flex;
            align-items: center;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
            background: #f8fafc;
            height: 38px;
        }
        .btn-qty-step {
            background: #e2e8f0;
            border: none;
            width: 28px;
            height: 100%;
            font-size: 15px;
            font-weight: 700;
            color: #334155;
            cursor: pointer;
            transition: background 0.15s;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
        }
        .btn-qty-step:hover {
            background: #cbd5e1;
        }
        .input-qty {
            width: 36px;
            height: 100%;
            border: none;
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            background: transparent;
            -moz-appearance: textfield;
        }
        .input-qty::-webkit-outer-spin-button,
        .input-qty::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .btn-add-cart {
            flex-grow: 1;
            height: 38px;
            background: #2563eb;
            color: white;
            border: none;
            padding: 0 12px;
            cursor: pointer;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .btn-add-cart:hover {
            background: var(--primary-hover);
        }
    </style>
</head>
<body>

    <!-- Header: Title on Left, Cart & Logout Side-by-Side on Upper Right Side -->
    <div class="nav-bar">
        <h2>🍔 Yum's berchg</h2>

        <!-- Upper Right Side Controls -->
        <div class="nav-right-group">
            <a href="cart.php" class="btn-cart-nav">
                🛒 <span>Cart</span>
                <span class="cart-count"><?= $cart_count ?></span>
            </a>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="../auth/admin.php" class="btn-admin-nav">⚙️ Admin Panel</a>
            <?php endif; ?>

            <a href="../auth/logout.php" class="btn-logout-nav">Logout</a>
        </div>
    </div>

    <?php if (isset($_GET['added'])): ?>
        <?php $qty_added = (int)$_GET['added']; ?>
        <div style="background: #dcfce7; border: 1px solid #bbf7d0; color: #15803d; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; font-size: 14px;">
            ✅ Successfully added <?= $qty_added > 1 ? $qty_added . ' items' : '1 item' ?> to your cart! <a href="cart.php" style="color: #15803d; text-decoration: underline; margin-left: 10px;">Go to Cart & Checkout →</a>
        </div>
    <?php endif; ?>

    <h3 class="section-title">Delicious Menu Items</h3>

    <div class="menu-grid">
        <?php foreach ($menu as $item): ?>
            <?php $img_src = !empty($item['image_url']) ? '../' . $item['image_url'] : '../uploads/default_food.png'; ?>
            <div class="menu-card">
                <div class="image-container">
                    <img src="<?= htmlspecialchars($img_src) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                    <span class="category-badge"><?= htmlspecialchars($item['category']) ?></span>
                </div>
                <div class="card-body">
                    <div class="card-header-row">
                        <h4 class="item-name"><?= htmlspecialchars($item['name']) ?></h4>
                        <span class="item-price">₱<?= number_format($item['price'], 2) ?></span>
                    </div>
                    <p class="item-desc"><?= htmlspecialchars($item['description']) ?></p>
                    <form method="POST" class="add-cart-form">
                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                        <div class="qty-control">
                            <button type="button" class="btn-qty-step" onclick="var i=this.nextElementSibling; if(i.value>1) i.value--;">-</button>
                            <input type="number" name="quantity" value="1" min="1" max="99" class="input-qty" readonly>
                            <button type="button" class="btn-qty-step" onclick="var i=this.previousElementSibling; if(i.value<99) i.value++;">+</button>
                        </div>
                        <button type="submit" name="add_to_cart" class="btn-add-cart">🛒 Add to Cart</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</body>
</html>
