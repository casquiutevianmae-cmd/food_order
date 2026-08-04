<?php
$host = 'localhost';
$db   = 'food_db';
$user = 'root'; // Default XAMPP MySQL username
$pass = '';     // Default XAMPP MySQL password (empty)

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Create database if it does not exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db`");

    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('customer', 'admin') DEFAULT 'customer',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Create categories table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Create menu_items table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS menu_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            name VARCHAR(150) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            description TEXT,
            image_url VARCHAR(255) DEFAULT NULL,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Add image_url column if table already exists without it
    try {
        $pdo->exec("ALTER TABLE menu_items ADD COLUMN image_url VARCHAR(255) DEFAULT NULL");
    } catch (Exception $e) {
        // Column already exists
    }

    // Create orders table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_name VARCHAR(100) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            address TEXT NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            user_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN user_id INT DEFAULT NULL");
    } catch (Exception $e) {
        // Column already exists
    }

    // Create order_items table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            item_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Ensure initial admin user exists
    $checkAdmin = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR role = 'admin'");
    $checkAdmin->execute(['admin']);
    if ($checkAdmin->fetchColumn() == 0) {
        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@foodorder.com', $adminPass, 'admin']);
    }

    // Ensure sample categories exist if categories table is empty
    $checkCat = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($checkCat == 0) {
        $pdo->exec("
            INSERT INTO categories (id, name) VALUES 
            (1, 'Burgers'),
            (2, 'Pizzas'),
            (3, 'Drinks'),
            (4, 'Sides'),
            (5, 'Desserts')
        ");
    }

    // Default sample menu items definition - seed only if menu_items table is empty
    $checkItems = $pdo->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
    if ($checkItems == 0) {
        $defaultMenuItems = [
            ['id' => 1, 'category_id' => 1, 'name' => 'Classic Cheeseburger', 'price' => 89.99, 'description' => 'Juicy beef patty with cheddar cheese, lettuce, tomato, and secret sauce.', 'image_url' => 'uploads/cheeseburger.png'],
            ['id' => 2, 'category_id' => 1, 'name' => 'Bacon BBQ Burger', 'price' => 89.99, 'description' => 'Crispy bacon, BBQ sauce, onion rings, and smoked cheddar.', 'image_url' => 'uploads/bacon_bbq_burger.png'],
            ['id' => 3, 'category_id' => 2, 'name' => 'Margherita Pizza', 'price' => 89.99, 'description' => 'Fresh mozzarella, tomatoes, and basil on crispy crust.', 'image_url' => 'uploads/margherita_pizza.png'],
            ['id' => 4, 'category_id' => 2, 'name' => 'Pepperoni Feast Pizza', 'price' => 89.99, 'description' => 'Loaded with spicy pepperoni and extra mozzarella.', 'image_url' => 'uploads/pepperoni_pizza.png'],
            ['id' => 5, 'category_id' => 3, 'name' => 'Iced Lemon Tea', 'price' => 89.99, 'description' => 'Refreshing chilled lemon tea with fresh mint.', 'image_url' => 'uploads/iced_lemon_tea.png'],
            ['id' => 6, 'category_id' => 3, 'name' => 'Chocolate Milkshake', 'price' => 89.99, 'description' => 'Rich and creamy chocolate milkshake topped with whip.', 'image_url' => 'uploads/chocolate_milkshake.png'],
            ['id' => 7, 'category_id' => 4, 'name' => 'French Fries', 'price' => 49.99, 'description' => 'Crispy golden french fries salted to perfection, served hot with dipping ketchup.', 'image_url' => 'uploads/french_fries.png'],
            ['id' => 8, 'category_id' => 4, 'name' => 'Chicken Nuggets', 'price' => 69.99, 'description' => 'Tender and juicy chicken nuggets served with signature honey mustard sauce.', 'image_url' => 'uploads/chicken_nuggets.png'],
            ['id' => 9, 'category_id' => 3, 'name' => 'Soda', 'price' => 39.99, 'description' => 'Cold, ice-filled bubbly soda for maximum refreshment.', 'image_url' => 'uploads/soda.png'],
            ['id' => 10, 'category_id' => 5, 'name' => 'Ice Cream', 'price' => 59.99, 'description' => 'Rich vanilla ice cream sundae topped with chocolate drizzle and cherry.', 'image_url' => 'uploads/ice_cream.png']
        ];

        $insertStmt = $pdo->prepare("INSERT INTO menu_items (id, category_id, name, price, description, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($defaultMenuItems as $item) {
            try {
                $insertStmt->execute([$item['id'], $item['category_id'], $item['name'], $item['price'], $item['description'], $item['image_url']]);
            } catch (Exception $e) {
                $insertNoId = $pdo->prepare("INSERT INTO menu_items (category_id, name, price, description, image_url) VALUES (?, ?, ?, ?, ?)");
                $insertNoId->execute([$item['category_id'], $item['name'], $item['price'], $item['description'], $item['image_url']]);
            }
        }
    }

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "<br><br><strong>Note:</strong> Please make sure MySQL is started in your XAMPP Control Panel.");
}
?>
