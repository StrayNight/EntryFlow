-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 05, 2026 at 08:24 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `entryflow_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('owner','staff') NOT NULL DEFAULT 'owner',
  `business_name` varchar(200) DEFAULT NULL,
  `business_type` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `currency` varchar(5) NOT NULL DEFAULT 'PHP',
  `avatar_color` varchar(20) NOT NULL DEFAULT '#9B1D1D',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `role`, `business_name`, `business_type`, `phone`, `address`, `currency`, `avatar_color`, `is_active`, `created_at`, `updated_at`) VALUES
(6, 'Rick Warren Nicasio', '2401107842@student.buksu.edu.ph', '$2y$10$Xz4xvkTOFYoS5m0vLRaK4O1GaCaRA.8EF9jk/ogqPjinmcyyBk6IK', 'owner', 'Monkey Business', 'Goofing Around', '09622431801', 'Malaybalay City', 'PHP', '#9B1D1D', 1, '2026-04-30 11:45:48', '2026-05-04 19:00:11');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL = global default',
  `name` varchar(100) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `user_id`, `name`, `type`, `is_default`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Sales', 'income', 1, '2026-05-05 13:53:43', '2026-05-05 13:53:43'),
(2, NULL, 'Service Revenue', 'income', 1, '2026-05-05 13:53:43', '2026-05-05 13:53:43'),
(3, NULL, 'Refunds', 'income', 1, '2026-05-05 13:53:43', '2026-05-05 13:53:43'),
(4, NULL, 'Other Income', 'income', 1, '2026-05-05 13:53:43', '2026-05-05 13:53:43'),
(5, NULL, 'Cloud Hosting', 'expense', 1, '2026-05-05 13:53:43', '2026-05-05 13:53:43'),
(6, NULL, 'SMS Services', 'expense', 1, '2026-05-05 13:53:43', '2026-05-05 13:53:43'),
(7, NULL, 'Marketing', 'expense', 1, '2026-05-05 13:53:43', '2026-05-05 13:53:43'),
(8, NULL, 'Salaries', 'expense', 1, '2026-05-05 13:53:43', '2026-05-05 13:53:43'),
(9, NULL, 'Inventory', 'expense', 1, '2026-05-05 13:53:43', '2026-05-05 13:53:43'),
(10, NULL, 'Rent', 'expense', 1, '2026-05-05 13:53:43', '2026-05-05 13:53:43'),
(11, NULL, 'Utilities', 'expense', 1, '2026-05-05 13:53:43', '2026-05-05 13:53:43'),
(12, NULL, 'Operations', 'expense', 1, '2026-05-05 13:53:43', '2026-05-05 13:53:43');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `user_id`, `name`, `email`, `phone`, `address`, `notes`, `created_at`, `updated_at`) VALUES
(11, 6, 'Emmge Ramos', 'Emmge@email.com', '0969 696 6969', 'Malaybalay City', 'Fatass man', '2026-05-04 18:00:00', '2026-05-05 13:53:43'),
(12, 6, 'Jay Kyle Tanedo', 'Jay@email.com', '0967 694 2021', 'Manila City', 'Goofy ass man', '2026-05-04 18:00:21', '2026-05-05 13:53:43'),
(13, 6, 'Khem Shwartz Cabutad', 'ShwartzIzBlack@yahoo.com', '0991 191 6969', 'Tomato Town', 'Black ass man', '2026-05-04 18:00:47', '2026-05-05 13:53:43'),
(14, 6, 'Tax Man', 'EyeHeartMoney@email.com', '0987 654 3210', 'Epstein Island', NULL, '2026-05-04 18:07:39', '2026-05-05 13:53:43'),
(15, 6, 'Ian Augustine Balarias', 'Bayout@gmail.com', '0900 000 0001', 'Landing City', NULL, '2026-05-05 13:56:54', '2026-05-05 13:56:54');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED DEFAULT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `invoice_no` varchar(50) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `status` enum('paid','pending','overdue') NOT NULL DEFAULT 'pending',
  `transaction_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `client_id`, `category_id`, `invoice_no`, `description`, `amount`, `type`, `status`, `transaction_date`, `notes`, `created_at`, `updated_at`) VALUES
(9, 6, 13, 1, '1234', 'Extra large Illocos impanada', 599.00, 'income', 'paid', '2026-05-04', NULL, '2026-05-04 18:02:04', '2026-05-04 18:02:04'),
(10, 6, 11, 1, '666', 'Dubai Chewy Choco', 199.00, 'income', 'paid', '2026-05-04', NULL, '2026-05-04 18:02:58', '2026-05-04 18:02:58'),
(11, 6, 12, 1, '420', 'San Marino Corned Beef', 99.00, 'income', 'paid', '2026-05-04', NULL, '2026-05-04 18:05:53', '2026-05-04 18:05:53'),
(13, 6, 14, 10, '420', 'Monthly Rent', 5000.00, 'expense', 'overdue', '2026-05-04', NULL, '2026-05-04 18:20:36', '2026-05-05 14:03:18'),
(14, 6, 12, 1, '1893', '(Bulk) Dubai Chewy Choco', 2500.00, 'income', 'paid', '2026-05-04', NULL, '2026-05-04 18:21:40', '2026-05-04 18:21:40'),
(15, 6, 11, 1, '3242', 'Extra large Illocos impanada', 599.00, 'income', 'paid', '2026-05-04', NULL, '2026-05-04 18:22:11', '2026-05-04 18:22:11'),
(16, 6, 14, 10, NULL, 'Monthly Rent', 5000.00, 'expense', 'paid', '2026-05-04', NULL, '2026-05-04 18:41:21', '2026-05-04 18:41:21'),
(17, 6, 15, 1, '555', '(Bulk) King Sized Illocos Empanada', 4999.00, 'income', 'paid', '2026-05-05', NULL, '2026-05-05 13:58:05', '2026-05-05 13:58:05'),
(18, 6, 15, 1, '321', 'San Marino Corned Beef', 99.00, 'income', 'paid', '2026-05-05', NULL, '2026-05-05 13:58:58', '2026-05-05 13:58:58'),
(19, 6, 15, 1, '909', '(Bulk) Dubai Chewy Choco', 1499.00, 'income', 'paid', '2026-05-05', NULL, '2026-05-05 13:59:39', '2026-05-05 13:59:39'),
(20, 6, 12, 2, '69', 'Pahilot', 150.00, 'income', 'paid', '2026-05-05', NULL, '2026-05-05 14:03:51', '2026-05-05 14:03:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_categories_user` (`user_id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_clients_user` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tx_client` (`client_id`),
  ADD KEY `fk_tx_category` (`category_id`),
  ADD KEY `idx_user_date` (`user_id`,`transaction_date`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_categories_user` FOREIGN KEY (`user_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `fk_clients_user` FOREIGN KEY (`user_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_tx_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tx_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tx_user` FOREIGN KEY (`user_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
