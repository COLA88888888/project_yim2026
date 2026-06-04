-- Create expense categories table
CREATE TABLE IF NOT EXISTS `expense_categories` (
  `category_id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_name` VARCHAR(100) NOT NULL UNIQUE,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default categories
INSERT INTO `expense_categories` (`category_name`) VALUES 
('ຄ່ານ້ຳ/ຄ່າໄຟ'),
('ຄ່າເຊົ່າສະຖານທີ່'),
('ເງິນເດືອນພະນັກງານ'),
('ຄ່າບຳລຸງຮັກສາອຸປະກອນ'),
('ອື່ນໆ')
ON DUPLICATE KEY UPDATE `category_name` = `category_name`;
