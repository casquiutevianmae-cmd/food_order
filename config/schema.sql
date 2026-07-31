-- =========================================================
-- Food Order Management System Database Schema & Initial Data
-- Database Name: food_db
-- Compatibility: MySQL / MariaDB (XAMPP)
-- =========================================================

CREATE DATABASE IF NOT EXISTS `food_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `food_db`;

-- ---------------------------------------------------------
-- 1. Table structure for `users`
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('customer', 'admin') DEFAULT 'customer',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 2. Table structure for `categories`
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 3. Table structure for `menu_items`
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `menu_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `image_url` VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 4. Table structure for `orders`
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(50) NOT NULL,
    `address` TEXT NOT NULL,
    `total_price` DECIMAL(10,2) NOT NULL,
    `user_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 5. Table structure for `order_items`
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `item_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- INITIAL SEED DATA
-- =========================================================

-- Seed Default Admin Account (Username: admin | Password: admin123)
INSERT INTO `users` (`username`, `email`, `password`, `role`)
SELECT 'admin', 'admin@foodorder.com', '$2y$10$wO7v8zJ3M6K7X6h8a7M1oO4G9vJ0Q5U3a2Z1y8x7w6v5u4t3s2r1q', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username` = 'admin');

-- Seed Sample Categories
INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Burgers'),
(2, 'Pizzas'),
(3, 'Drinks'),
(4, 'Sides'),
(5, 'Desserts')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Seed Sample Menu Items
INSERT INTO `menu_items` (`id`, `category_id`, `name`, `price`, `description`, `image_url`) VALUES
(1, 1, 'Classic Cheeseburger', 89.99, 'Juicy beef patty with cheddar cheese, lettuce, tomato, and secret sauce.', 'uploads/cheeseburger.png'),
(2, 1, 'Bacon BBQ Burger', 89.99, 'Crispy bacon, BBQ sauce, onion rings, and smoked cheddar.', 'uploads/bacon_bbq_burger.png'),
(3, 2, 'Margherita Pizza', 89.99, 'Fresh mozzarella, tomatoes, and basil on crispy crust.', 'uploads/margherita_pizza.png'),
(4, 2, 'Pepperoni Feast Pizza', 89.99, 'Loaded with spicy pepperoni and extra mozzarella.', 'uploads/pepperoni_pizza.png'),
(5, 3, 'Iced Lemon Tea', 89.99, 'Refreshing chilled lemon tea with fresh mint.', 'uploads/iced_lemon_tea.png'),
(6, 3, 'Chocolate Milkshake', 89.99, 'Rich and creamy chocolate milkshake topped with whip.', 'uploads/chocolate_milkshake.png'),
(7, 4, 'French Fries', 49.99, 'Crispy golden french fries salted to perfection, served hot with dipping ketchup.', 'uploads/french_fries.png'),
(8, 4, 'Chicken Nuggets', 69.99, 'Tender and juicy chicken nuggets served with signature honey mustard sauce.', 'uploads/chicken_nuggets.png'),
(9, 3, 'Soda', 39.99, 'Cold, ice-filled bubbly soda for maximum refreshment.', 'uploads/soda.png'),
(10, 5, 'Ice Cream', 59.99, 'Rich vanilla ice cream sundae topped with chocolate drizzle and cherry.', 'uploads/ice_cream.png')
ON DUPLICATE KEY UPDATE 
    `category_id` = VALUES(`category_id`),
    `name` = VALUES(`name`),
    `price` = VALUES(`price`),
    `description` = VALUES(`description`),
    `image_url` = VALUES(`image_url`);
