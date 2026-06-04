-- ສ້າງຖານຂໍ້ມູນ db_gym2026 ຖ້າຍັງບໍ່ມີ
CREATE DATABASE IF NOT EXISTS `db_gym2026` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db_gym2026`;

-- --------------------------------------------------------
-- 1. ຕາຕະລາງຜູ້ໃຊ້ລະບົບ/ພະນັກງານ (users)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` varchar(50) NOT NULL,
  `fname` varchar(50) DEFAULT NULL,
  `lname` varchar(50) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `tel` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL, -- 'ຜູ້ບໍລິຫານ', 'ພະນັກງານ'
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `permissions` text DEFAULT NULL,
  `profile_img` varchar(100) DEFAULT 'default.png',
  `created_at` datetime DEFAULT current_timestamp(),
  `remark` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 2. ຕາຕະລາງສະມາຊິກຍິມ (members)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `members` (
  `member_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_code` varchar(50) NOT NULL,
  `fname` varchar(50) NOT NULL,
  `lname` varchar(50) NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `tel` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_img` varchar(100) DEFAULT 'default.png',
  `status` varchar(20) DEFAULT 'Active', -- 'Active', 'Expired', 'Inactive'
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`member_id`),
  UNIQUE KEY `member_code` (`member_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 3. ຕາຕະລາງແພັກເກດຍິມ (packages)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `packages` (
  `package_id` int(11) NOT NULL AUTO_INCREMENT,
  `package_name` varchar(100) NOT NULL,
  `duration_days` int(11) NOT NULL, -- ຈຳນວນມື້ (ເຊັ່ນ 1, 30, 90, 365)
  `price` decimal(12,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`package_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 4. ຕາຕະລາງລົງທະບຽນແພັກເກດ/ການເປັນສະມາຊິກ (memberships)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `memberships` (
  `membership_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `price_paid` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'ເງິນສົດ', -- 'ເງິນສົດ', 'ໂອນຜ່ານ QR'
  `payment_status` varchar(20) DEFAULT 'Paid', -- 'Paid', 'Unpaid'
  `status` varchar(20) DEFAULT 'Active', -- 'Active', 'Expired'
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`membership_id`),
  KEY `member_id` (`member_id`),
  KEY `package_id` (`package_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 5. ຕາຕະລາງເຊັກອິນເຂົ້າໃຊ້ບໍລິການ (checkins)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `checkins` (
  `checkin_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `checkin_time` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`checkin_id`),
  KEY `member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 6. ຕາຕະລາງເຄື່ອງອອກກຳລັງກາຍ/ອຸປະກອນ (equipment)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `equipment` (
  `equipment_id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_code` varchar(50) NOT NULL,
  `equipment_name` varchar(100) NOT NULL,
  `brand_model` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `status` varchar(20) DEFAULT 'ດີ', -- 'ດີ', 'ເພ'
  `purchase_date` date DEFAULT NULL,
  `price` decimal(12,2) DEFAULT 0.00,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`equipment_id`),
  UNIQUE KEY `equipment_code` (`equipment_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------
-- ເພີ່ມຂໍ້ມູນເລີ່ມຕົ້ນ (Seed Data)
-- --------------------------------------------------------

-- 1. ຜູ້ໃຊ້ລະບົບ (ລະຫັດຜ່ານແມ່ນ '123456')
INSERT INTO `users` (`user_id`, `fname`, `lname`, `gender`, `dob`, `tel`, `address`, `status`, `username`, `password`, `permissions`, `profile_img`, `remark`) VALUES
('U001', 'Admin', 'System', 'ຊາຍ', '2026-06-03', '020 99999999', 'ຍິມ ຟິດເນັດ', 'ຜູ້ບໍລິຫານ', 'admin', '$2y$10$.lTOs3FCe9WbbW7Td7xxsuXK4X1A8G9ti/YGrShkY9EH41fmPdgnS', '[]', 'default.png', 'ແອດມິນລະບົບ'),
('U002', 'Staff', 'Gym', 'ຍິງ', '2026-06-03', '020 55555555', 'ຍິມ ຟິດເນັດ', 'ພະນັກງານ', 'staff', '$2y$10$.lTOs3FCe9WbbW7Td7xxsuXK4X1A8G9ti/YGrShkY9EH41fmPdgnS', '["members","checkin","subscriptions"]', 'default.png', 'ພະນັກງານຕ້ອນຮັບ');

-- 2. ແພັກເກດເລີ່ມຕົ້ນ
INSERT INTO `packages` (`package_name`, `duration_days`, `price`, `description`) VALUES
('1 ເດືອນ (1 Month)', 30, 250000.00, 'ເຂົ້າຫຼິ້ນໄດ້ 1 ເດືອນ ບໍ່ຈຳກັດຄັ້ງ'),
('3 ເດືອນ (3 Months)', 90, 650000.00, 'ເຂົ້າຫຼິ້ນໄດ້ 3 ເດືອນ ປະຢັດກວ່າ'),
('6 ເດືອນ (6 Months)', 180, 1200000.00, 'ເຂົ້າຫຼິ້ນໄດ້ 6 ເດືອນ ຄຸ້ມຄ່າ'),
('1 ປີ (1 Year)', 365, 2200000.00, 'ເຂົ້າຫຼິ້ນໄດ້ 1 ປີ ເໝາະສຳລັບຜູ້ຫຼິ້ນປະຈຳ');

-- 3. ເຄື່ອງອອກກຳລັງກາຍເລີ່ມຕົ້ນ
INSERT INTO `equipment` (`equipment_code`, `equipment_name`, `brand_model`, `quantity`, `status`, `purchase_date`, `price`, `description`) VALUES
('EQ-001', 'ລູ່ວິ່ງໄຟຟ້າ Treadmill', 'PowerRun X9', 5, 'ດີ', '2026-01-15', 12000000.00, 'ລູ່ວິ່ງໄຟຟ້າຄວາມໄວສູງ'),
('EQ-002', 'ດຳເບວ dumbbell set', 'IronGrip 2.5-25kg', 1, 'ດີ', '2026-01-15', 5000000.00, 'ຊຸດດຳເບວ 10 ຄູ່ ພ້ອມຊັ້ນວາງ'),
('EQ-003', 'ລົດຖີບອອກກຳລັງກາຍ Spin Bike', 'FitLife S2', 4, 'ດີ', '2026-02-10', 4500000.00, 'ລົດຖີບປັ່ນລະບົບແມ່ເຫຼັກ'),
('EQ-004', 'ເຄື່ອງກົດເອິກ Chest Press', 'GymMax C1', 2, 'ເພ', '2026-02-10', 8500000.00, 'ສາຍສະລິງຂາດ, ລໍຖ້າການສ້ອມແປງ');
