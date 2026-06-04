-- Add category_code column to expense_categories
ALTER TABLE `expense_categories` ADD COLUMN `category_code` VARCHAR(50) NOT NULL AFTER `category_id`;
UPDATE `expense_categories` SET `category_code` = CONCAT('EXP0', `category_id`);
ALTER TABLE `expense_categories` ADD UNIQUE KEY `uq_expense_category_code` (`category_code`);
