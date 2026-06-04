USE `db_gym2026`;

-- Add image column to products table
ALTER TABLE `products` ADD COLUMN `image` VARCHAR(255) DEFAULT NULL AFTER `unit`;
