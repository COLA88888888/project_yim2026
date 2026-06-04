-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 04, 2026 at 07:09 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_gym2026`
--

-- --------------------------------------------------------

--
-- Table structure for table `checkins`
--

CREATE TABLE `checkins` (
  `checkin_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `checkin_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_checkins`
--

CREATE TABLE `daily_checkins` (
  `id` int(11) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `price_paid` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `checkin_date` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `user_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_checkins`
--

INSERT INTO `daily_checkins` (`id`, `gender`, `price_paid`, `payment_method`, `checkin_date`, `created_at`, `user_id`) VALUES
(1, 'ຊາຍ', 20000.00, 'ເງິນສົດ', '2026-06-04', '2026-06-04 08:57:56', 'U001');

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `equipment_id` int(11) NOT NULL,
  `equipment_code` varchar(50) NOT NULL,
  `equipment_name` varchar(100) NOT NULL,
  `brand_model` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `status` varchar(20) DEFAULT '??????',
  `purchase_date` date DEFAULT NULL,
  `price` decimal(12,2) DEFAULT 0.00,
  `description` varchar(255) DEFAULT NULL,
  `equipment_img` varchar(100) DEFAULT 'default_eq.png',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`equipment_id`, `equipment_code`, `equipment_name`, `brand_model`, `quantity`, `status`, `purchase_date`, `price`, `description`, `equipment_img`, `created_at`) VALUES
(1, 'EQ-001', 'Treadmill', 'PowerRun X9', 5, 'ດີ', '0000-00-00', 1200000000.00, '', 'default_eq.png', '2026-06-03 19:42:38'),
(2, 'EQ-002', 'dumbbell set', 'IronGrip 2.5-25kg', 1, 'ດີ', '0000-00-00', 500000000.00, '', 'default_eq.png', '2026-06-03 19:42:38'),
(3, 'EQ-003', 'Spin Bike', 'FitLife S2', 4, 'ດີ', '0000-00-00', 450000000.00, '?????????????????????????????????????????????????????????????????????', 'default_eq.png', '2026-06-03 19:42:38'),
(4, 'EQ-004', 'Chest Press', 'GymMax C1', 2, 'ດີ', NULL, 9999999999.99, '', 'default_eq.png', '2026-06-03 19:42:38');

-- --------------------------------------------------------

--
-- Table structure for table `lockers`
--

CREATE TABLE `lockers` (
  `locker_id` int(11) NOT NULL,
  `locker_code` varchar(50) NOT NULL,
  `locker_floor` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Available',
  `member_id` int(11) DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `member_name` varchar(150) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lockers`
--

INSERT INTO `lockers` (`locker_id`, `locker_code`, `locker_floor`, `status`, `member_id`, `assigned_at`, `member_name`, `created_at`) VALUES
(1, 'L-01', 'ຊັ້ນ 1', 'Occupied', NULL, NULL, NULL, '2026-06-03 21:46:02'),
(2, 'L-02', 'ຊັ້ນ 1', 'Available', NULL, NULL, NULL, '2026-06-03 21:46:02'),
(3, 'L-03', 'ຊັ້ນ 1', 'Occupied', NULL, NULL, NULL, '2026-06-03 21:46:02'),
(4, 'L-04', 'ຊັ້ນ 2', 'Available', NULL, NULL, NULL, '2026-06-03 21:46:02'),
(5, 'L-05', 'ຊັ້ນ 2', 'Available', NULL, NULL, NULL, '2026-06-03 21:46:02');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `member_id` int(11) NOT NULL,
  `member_code` varchar(50) NOT NULL,
  `fname` varchar(50) NOT NULL,
  `lname` varchar(50) NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `tel` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_img` varchar(100) DEFAULT 'default.png',
  `status` varchar(20) DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `memberships`
--

CREATE TABLE `memberships` (
  `membership_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `price_paid` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT '?????????????????????',
  `payment_status` varchar(20) DEFAULT 'Paid',
  `status` varchar(20) DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp(),
  `user_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `package_id` int(11) NOT NULL,
  `package_name` varchar(100) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`package_id`, `package_name`, `duration_days`, `price`, `description`, `created_at`) VALUES
(1, '1 ເດືອນ', 30, 25000000.00, 'ເຂົ້າຫຼິ້ນໄດ້ 1 ເດືອນ ບໍ່ຈຳກັດຄັ້ງ', '2026-06-03 20:28:53'),
(2, '3 ເດືອນ', 90, 65000000.00, 'ເຂົ້າຫຼິ້ນໄດ້ 3 ເດືອນ ປະຢັດກວ່າ', '2026-06-03 20:28:53'),
(3, '6 ເດືອນ', 180, 120000000.00, 'ເຂົ້າຫຼິ້ນໄດ້ 6 ເດືອນ ຄຸ້ມຄ່າ', '2026-06-03 20:28:53'),
(4, '1 ປີ', 365, 220000000.00, 'ເຂົ້າຫຼິ້ນໄດ້ 1 ປີ ເໝາະສຳລັບຜູ້ຫຼິ້ນປະຈຳ', '2026-06-03 20:28:53');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` varchar(50) NOT NULL,
  `fname` varchar(50) DEFAULT NULL,
  `lname` varchar(50) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `tel` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `permissions` text DEFAULT NULL,
  `profile_img` varchar(100) DEFAULT 'default.png',
  `created_at` datetime DEFAULT current_timestamp(),
  `remark` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `fname`, `lname`, `gender`, `dob`, `tel`, `address`, `status`, `username`, `password`, `permissions`, `profile_img`, `created_at`, `remark`) VALUES
('U001', 'Admin', 'System', 'ຊາຍ', '2026-06-03', '020 99999999', 'ໂພນໝີ ວຽງຄຳ ວຽງຈັນ', 'ຜູ້ບໍລິຫານ', 'admin', '$2y$10$7rUZIcB72erLaC8kvenw6ecJDkOcmbZlJXoeuOHWX77tO.YBknDsS', '[]', 'user_1780500680_2319.png', '2026-06-03 19:42:37', ''),
('U002', 'Staff', 'Gym', 'ຊາຍ', '2026-06-03', '020 55555555', 'ໂນນສະຫວ່າງ ວຽງຄຳ ວຽງຈັນ', 'ພະນັກງານ', 'khola', '$2y$10$spmZD4vCqiBONBgFWmO0t.PtJ8BE/oXTVKNwt8JtLWxdiRkCUBjzi', '{}', 'user_1780500603_3287.png', '2026-06-03 19:42:37', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `checkins`
--
ALTER TABLE `checkins`
  ADD PRIMARY KEY (`checkin_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `daily_checkins`
--
ALTER TABLE `daily_checkins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`equipment_id`),
  ADD UNIQUE KEY `equipment_code` (`equipment_code`);

--
-- Indexes for table `lockers`
--
ALTER TABLE `lockers`
  ADD PRIMARY KEY (`locker_id`),
  ADD UNIQUE KEY `locker_code` (`locker_code`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`member_id`),
  ADD UNIQUE KEY `member_code` (`member_code`);

--
-- Indexes for table `memberships`
--
ALTER TABLE `memberships`
  ADD PRIMARY KEY (`membership_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`package_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `checkins`
--
ALTER TABLE `checkins`
  MODIFY `checkin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_checkins`
--
ALTER TABLE `daily_checkins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `equipment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `lockers`
--
ALTER TABLE `lockers`
  MODIFY `locker_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `memberships`
--
ALTER TABLE `memberships`
  MODIFY `membership_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `package_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
