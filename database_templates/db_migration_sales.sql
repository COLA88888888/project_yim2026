-- DDL to create Sales & Inventory tables for db_gym2026
USE `db_gym2026`;

-- 1. Product Categories Table
CREATE TABLE IF NOT EXISTS `product_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_code` varchar(50) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_code` (`category_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Products Table
CREATE TABLE IF NOT EXISTS `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_code` varchar(50) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `category_id` int(11) NOT NULL,
  `cost_price` decimal(12,2) DEFAULT 0.00,
  `sale_price` decimal(12,2) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `unit` varchar(20) DEFAULT 'ອັນ',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`product_id`),
  UNIQUE KEY `product_code` (`product_code`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`category_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Stock In Table
CREATE TABLE IF NOT EXISTS `stock_in` (
  `stock_in_id` int(11) NOT NULL AUTO_INCREMENT,
  `stock_in_date` datetime DEFAULT current_timestamp(),
  `supplier` varchar(100) DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `user_id` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`stock_in_id`),
  CONSTRAINT `fk_stock_in_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Stock In Details Table
CREATE TABLE IF NOT EXISTS `stock_in_details` (
  `detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `stock_in_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `cost_price` decimal(12,2) NOT NULL,
  PRIMARY KEY (`detail_id`),
  CONSTRAINT `fk_stock_in_details_parent` FOREIGN KEY (`stock_in_id`) REFERENCES `stock_in` (`stock_in_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stock_in_details_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Sales Table
CREATE TABLE IF NOT EXISTS `sales` (
  `sale_id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_code` varchar(50) NOT NULL,
  `sale_date` datetime DEFAULT current_timestamp(),
  `total_amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'ເງິນສົດ',
  `user_id` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`sale_id`),
  UNIQUE KEY `sale_code` (`sale_code`),
  CONSTRAINT `fk_sales_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. Sale Details Table
CREATE TABLE IF NOT EXISTS `sale_details` (
  `detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  PRIMARY KEY (`detail_id`),
  CONSTRAINT `fk_sale_details_parent` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sale_details_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed initial test product categories
INSERT INTO `product_categories` (`category_code`, `category_name`) VALUES 
('CAT001', 'ເຄື່ອງດື່ມ (Drinks)'), 
('CAT002', 'ອາຫານເສີມ (Supplements)'), 
('CAT003', 'ອຸປະກອນຍິມ (Gym Gear)');
