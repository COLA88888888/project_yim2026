USE `db_gym2026`;

-- Add category_code with default empty string
ALTER TABLE `product_categories` ADD COLUMN `category_code` VARCHAR(50) NOT NULL DEFAULT '' AFTER `category_id`;

-- Update existing rows with generated codes like CAT001, CAT002
UPDATE `product_categories` SET `category_code` = CONCAT('CAT', LPAD(`category_id`, 3, '0')) WHERE `category_code` = '';

-- Add Unique Constraint
ALTER TABLE `product_categories` ADD UNIQUE KEY `category_code` (`category_code`);
