-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: db_gym2026
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `checkins`
--

DROP TABLE IF EXISTS `checkins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `checkins` (
  `checkin_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `checkin_time` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`checkin_id`),
  KEY `member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checkins`
--

LOCK TABLES `checkins` WRITE;
/*!40000 ALTER TABLE `checkins` DISABLE KEYS */;
/*!40000 ALTER TABLE `checkins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `daily_checkins`
--

DROP TABLE IF EXISTS `daily_checkins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `daily_checkins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gender` varchar(20) NOT NULL,
  `price_paid` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `checkin_date` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `user_id` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_checkins`
--

LOCK TABLES `daily_checkins` WRITE;
/*!40000 ALTER TABLE `daily_checkins` DISABLE KEYS */;
INSERT INTO `daily_checkins` VALUES (1,'ຊາຍ',20000.00,'ເງິນສົດ','2026-06-04','2026-06-04 08:57:56','U001');
/*!40000 ALTER TABLE `daily_checkins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipment`
--

DROP TABLE IF EXISTS `equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipment` (
  `equipment_id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_code` varchar(50) NOT NULL,
  `equipment_name` varchar(100) NOT NULL,
  `brand_model` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `status` varchar(20) DEFAULT '??????',
  `purchase_date` date DEFAULT NULL,
  `price` decimal(12,2) DEFAULT 0.00,
  `description` varchar(255) DEFAULT NULL,
  `equipment_img` varchar(100) DEFAULT 'default_eq.png',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`equipment_id`),
  UNIQUE KEY `equipment_code` (`equipment_code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment`
--

LOCK TABLES `equipment` WRITE;
/*!40000 ALTER TABLE `equipment` DISABLE KEYS */;
INSERT INTO `equipment` VALUES (1,'EQ-001','Treadmill','PowerRun X9',5,'ດີ','0000-00-00',1200000000.00,'','default_eq.png','2026-06-03 19:42:38'),(2,'EQ-002','dumbbell set','IronGrip 2.5-25kg',1,'ດີ','0000-00-00',500000000.00,'','default_eq.png','2026-06-03 19:42:38'),(3,'EQ-003','Spin Bike','FitLife S2',4,'ດີ','0000-00-00',450000000.00,'?????????????????????????????????????????????????????????????????????','default_eq.png','2026-06-03 19:42:38'),(4,'EQ-004','Chest Press','GymMax C1',2,'ດີ',NULL,9999999999.99,'','default_eq.png','2026-06-03 19:42:38');
/*!40000 ALTER TABLE `equipment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lockers`
--

DROP TABLE IF EXISTS `lockers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lockers` (
  `locker_id` int(11) NOT NULL AUTO_INCREMENT,
  `locker_code` varchar(50) NOT NULL,
  `locker_floor` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Available',
  `member_id` int(11) DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `member_name` varchar(150) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`locker_id`),
  UNIQUE KEY `locker_code` (`locker_code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lockers`
--

LOCK TABLES `lockers` WRITE;
/*!40000 ALTER TABLE `lockers` DISABLE KEYS */;
INSERT INTO `lockers` VALUES (1,'L-01','ຊັ້ນ 1','Occupied',NULL,NULL,NULL,'2026-06-03 21:46:02'),(2,'L-02','ຊັ້ນ 1','Available',NULL,NULL,NULL,'2026-06-03 21:46:02'),(3,'L-03','ຊັ້ນ 1','Occupied',NULL,NULL,NULL,'2026-06-03 21:46:02'),(4,'L-04','ຊັ້ນ 2','Available',NULL,NULL,NULL,'2026-06-03 21:46:02'),(5,'L-05','ຊັ້ນ 2','Available',NULL,NULL,NULL,'2026-06-03 21:46:02');
/*!40000 ALTER TABLE `lockers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `members`
--

DROP TABLE IF EXISTS `members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `members` (
  `member_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_code` varchar(50) NOT NULL,
  `fname` varchar(50) NOT NULL,
  `lname` varchar(50) NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `tel` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_img` varchar(100) DEFAULT 'default.png',
  `status` varchar(20) DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`member_id`),
  UNIQUE KEY `member_code` (`member_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `members`
--

LOCK TABLES `members` WRITE;
/*!40000 ALTER TABLE `members` DISABLE KEYS */;
/*!40000 ALTER TABLE `members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `memberships`
--

DROP TABLE IF EXISTS `memberships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `memberships` (
  `membership_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `price_paid` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT '?????????????????????',
  `payment_status` varchar(20) DEFAULT 'Paid',
  `status` varchar(20) DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp(),
  `user_id` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`membership_id`),
  KEY `member_id` (`member_id`),
  KEY `package_id` (`package_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `memberships`
--

LOCK TABLES `memberships` WRITE;
/*!40000 ALTER TABLE `memberships` DISABLE KEYS */;
/*!40000 ALTER TABLE `memberships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `packages`
--

DROP TABLE IF EXISTS `packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packages` (
  `package_id` int(11) NOT NULL AUTO_INCREMENT,
  `package_name` varchar(100) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`package_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `packages`
--

LOCK TABLES `packages` WRITE;
/*!40000 ALTER TABLE `packages` DISABLE KEYS */;
INSERT INTO `packages` VALUES (1,'1 ເດືອນ',30,25000000.00,'ເຂົ້າຫຼິ້ນໄດ້ 1 ເດືອນ ບໍ່ຈຳກັດຄັ້ງ','2026-06-03 20:28:53'),(2,'3 ເດືອນ',90,65000000.00,'ເຂົ້າຫຼິ້ນໄດ້ 3 ເດືອນ ປະຢັດກວ່າ','2026-06-03 20:28:53'),(3,'6 ເດືອນ',180,120000000.00,'ເຂົ້າຫຼິ້ນໄດ້ 6 ເດືອນ ຄຸ້ມຄ່າ','2026-06-03 20:28:53'),(4,'1 ປີ',365,220000000.00,'ເຂົ້າຫຼິ້ນໄດ້ 1 ປີ ເໝາະສຳລັບຜູ້ຫຼິ້ນປະຈຳ','2026-06-03 20:28:53');
/*!40000 ALTER TABLE `packages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_code` varchar(50) NOT NULL DEFAULT '',
  `category_name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_code` (`category_code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_categories`
--

LOCK TABLES `product_categories` WRITE;
/*!40000 ALTER TABLE `product_categories` DISABLE KEYS */;
INSERT INTO `product_categories` VALUES (1,'CAT001','ເຄື່ອງດື່ມ','2026-06-04 13:22:05'),(2,'CAT002','ອາຫານເສີມ','2026-06-04 13:22:05');
/*!40000 ALTER TABLE `product_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_code` varchar(50) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `category_id` int(11) NOT NULL,
  `cost_price` decimal(12,2) DEFAULT 0.00,
  `sale_price` decimal(12,2) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `unit` varchar(20) DEFAULT '?????????',
  `image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`product_id`),
  UNIQUE KEY `product_code` (`product_code`),
  KEY `fk_products_category` (`category_id`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`category_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'8859313500200','ນ້ຳດື່ມຫົວເສືອ (ຕຸກກາງ)',1,8000.00,12000.00,0,'ຕຸກ','prod_6a211eb920ac70.24757000.png','2026-06-04 13:31:21'),(2,'8859313500201','ນ້ຳດື່ມຫົວເສືອ (ຕຸກໃຫຍ່)',1,12000.00,18000.00,0,'ຕຸກ','prod_6a211ec4345425.98252732.png','2026-06-04 13:37:15'),(3,'8459780457570','CREATINE',2,200000.00,350000.00,0,'ປຸກ','prod_6a21211e2a0be2.30314595.png','2026-06-04 13:54:22'),(4,'95758087498583','WHEY',2,500000.00,800000.00,0,'ປຸກ','prod_6a2121f370c0d6.31215745.png','2026-06-04 13:57:55'),(5,'90458504534840','BAAM',2,450000.00,600000.00,0,'ປຸກ','prod_6a2122398cc703.39050120.png','2026-06-04 13:59:05');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_details`
--

DROP TABLE IF EXISTS `sale_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_details` (
  `detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  PRIMARY KEY (`detail_id`),
  KEY `fk_sale_details_parent` (`sale_id`),
  KEY `fk_sale_details_product` (`product_id`),
  CONSTRAINT `fk_sale_details_parent` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sale_details_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_details`
--

LOCK TABLES `sale_details` WRITE;
/*!40000 ALTER TABLE `sale_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `sale_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_code` varchar(50) NOT NULL,
  `sale_date` datetime DEFAULT current_timestamp(),
  `total_amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT '?????????????????????',
  `user_id` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`sale_id`),
  UNIQUE KEY `sale_code` (`sale_code`),
  KEY `fk_sales_user` (`user_id`),
  CONSTRAINT `fk_sales_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_in`
--

DROP TABLE IF EXISTS `stock_in`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_in` (
  `stock_in_id` int(11) NOT NULL AUTO_INCREMENT,
  `stock_in_date` datetime DEFAULT current_timestamp(),
  `supplier` varchar(100) DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `user_id` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`stock_in_id`),
  KEY `fk_stock_in_user` (`user_id`),
  CONSTRAINT `fk_stock_in_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_in`
--

LOCK TABLES `stock_in` WRITE;
/*!40000 ALTER TABLE `stock_in` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_in` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_in_details`
--

DROP TABLE IF EXISTS `stock_in_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_in_details` (
  `detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `stock_in_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `cost_price` decimal(12,2) NOT NULL,
  PRIMARY KEY (`detail_id`),
  KEY `fk_stock_in_details_parent` (`stock_in_id`),
  KEY `fk_stock_in_details_product` (`product_id`),
  CONSTRAINT `fk_stock_in_details_parent` FOREIGN KEY (`stock_in_id`) REFERENCES `stock_in` (`stock_in_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stock_in_details_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_in_details`
--

LOCK TABLES `stock_in_details` WRITE;
/*!40000 ALTER TABLE `stock_in_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_in_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `remark` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES ('U001','Admin','System','ຊາຍ','2026-06-03','020 99999999','ໂພນໝີ ວຽງຄຳ ວຽງຈັນ','ຜູ້ບໍລິຫານ','admin','$2y$10$7rUZIcB72erLaC8kvenw6ecJDkOcmbZlJXoeuOHWX77tO.YBknDsS','[]','user_1780500680_2319.png','2026-06-03 19:42:37',''),('U002','Staff','Gym','ຊາຍ','2026-06-03','020 55555555','ໂນນສະຫວ່າງ ວຽງຄຳ ວຽງຈັນ','ພະນັກງານ','khola','$2y$10$spmZD4vCqiBONBgFWmO0t.PtJ8BE/oXTVKNwt8JtLWxdiRkCUBjzi','{}','user_1780500603_3287.png','2026-06-03 19:42:37','');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-04 14:01:48
