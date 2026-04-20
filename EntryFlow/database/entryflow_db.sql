-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 20, 2026 at 10:19 AM
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
-- Database: `entryflow_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `businesses`
--

CREATE TABLE `businesses` (
  `business_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `business_name` varchar(150) NOT NULL,
  `industry_type` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `businesses`
--

INSERT INTO `businesses` (`business_id`, `user_id`, `template_id`, `business_name`, `industry_type`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'Juan Mini Store', 'Retail', 'active', '2026-04-20 08:18:40', NULL),
(2, 2, NULL, 'Maria Bakeshop', 'Food', 'active', '2026-04-20 08:18:40', NULL),
(3, 3, NULL, 'Pedro Repair Shop', 'Services', 'active', '2026-04-20 08:18:40', NULL),
(4, 4, NULL, 'Ana Online Boutique', 'E-commerce', 'active', '2026-04-20 08:18:40', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('income','expense') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `business_id`, `name`, `type`) VALUES
(1, 1, 'Product Sales', 'income'),
(2, 1, 'Supplies', 'expense'),
(3, 1, 'Rent', 'expense'),
(4, 2, 'Cake Sales', 'income'),
(5, 2, 'Ingredients', 'expense'),
(6, 2, 'Utilities', 'expense'),
(7, 3, 'Repair Service', 'income'),
(8, 3, 'Parts', 'expense'),
(9, 4, 'Online Sales', 'income'),
(10, 4, 'Shipping Cost', 'expense');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `business_id`, `name`, `phone`, `email`) VALUES
(1, 1, 'Carlos Buyer', '09111111111', 'carlosb@example.com'),
(2, 2, 'Wedding Client', '09222222222', 'wedding@example.com'),
(3, 3, 'Motorcycle Owner', '09333333333', 'moto@example.com'),
(4, 4, 'Online Shopper', '09444444444', 'shopper@example.com');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `report_type` enum('monthly','yearly','custom') DEFAULT 'monthly',
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `templates`
--

CREATE TABLE `templates` (
  `template_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL CHECK (`amount` >= 0),
  `description` text DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `business_id`, `category_id`, `customer_id`, `amount`, `description`, `transaction_date`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 300.00, 'Morning sales', '2026-04-01', '2026-04-20 08:18:40', NULL),
(2, 1, 1, 1, 450.00, 'Afternoon sales', '2026-04-01', '2026-04-20 08:18:40', NULL),
(3, 1, 2, NULL, 200.00, 'Restocked snacks', '2026-04-02', '2026-04-20 08:18:40', NULL),
(4, 1, 3, NULL, 1500.00, 'Monthly rent', '2026-04-03', '2026-04-20 08:18:40', NULL),
(5, 1, 1, 1, 500.00, 'Weekend sales spike', '2026-04-05', '2026-04-20 08:18:40', NULL),
(6, 2, 4, 2, 5000.00, 'Wedding cake order', '2026-04-02', '2026-04-20 08:18:40', NULL),
(7, 2, 5, NULL, 1200.00, 'Bought ingredients', '2026-04-01', '2026-04-20 08:18:40', NULL),
(8, 2, 6, NULL, 800.00, 'Electricity bill', '2026-04-03', '2026-04-20 08:18:40', NULL),
(9, 2, 4, NULL, 1500.00, 'Walk-in cake sales', '2026-04-04', '2026-04-20 08:18:40', NULL),
(10, 3, 7, 3, 700.00, 'Motorcycle repair', '2026-04-01', '2026-04-20 08:18:40', NULL),
(11, 3, 8, NULL, 300.00, 'Purchased spare parts', '2026-04-01', '2026-04-20 08:18:40', NULL),
(12, 3, 7, 3, 1200.00, 'Engine repair job', '2026-04-03', '2026-04-20 08:18:40', NULL),
(13, 4, 9, 4, 900.00, 'Online order #001', '2026-04-02', '2026-04-20 08:18:40', NULL),
(14, 4, 10, NULL, 150.00, 'Shipping expense', '2026-04-02', '2026-04-20 08:18:40', NULL),
(15, 4, 9, 4, 1300.00, 'Online order #002', '2026-04-03', '2026-04-20 08:18:40', NULL),
(16, 4, 10, NULL, 200.00, 'Shipping expense', '2026-04-03', '2026-04-20 08:18:40', NULL),
(17, 4, 9, 4, 2000.00, 'Bulk order sale', '2026-04-05', '2026-04-20 08:18:40', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `plan_type` enum('free','premium') DEFAULT 'free',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password_hash`, `plan_type`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Juan Dela Cruz', 'juan@example.com', '$2y$10$dummyhash', 'free', 'active', '2026-04-20 08:18:40', NULL),
(2, 'Maria Santos', 'maria@example.com', '$2y$10$dummyhash', 'premium', 'active', '2026-04-20 08:18:40', NULL),
(3, 'Pedro Reyes', 'pedro@example.com', '$2y$10$dummyhash', 'free', 'active', '2026-04-20 08:18:40', NULL),
(4, 'Ana Lopez', 'ana@example.com', '$2y$10$dummyhash', 'premium', 'active', '2026-04-20 08:18:40', NULL),
(5, 'Carlos Mendoza', 'carlos@example.com', '$2y$10$dummyhash', 'free', 'active', '2026-04-20 08:18:40', NULL),
(6, 'Liza Ramos', 'liza@example.com', '$2y$10$dummyhash', 'free', 'active', '2026-04-20 08:18:40', NULL),
(7, 'Mark Villanueva', 'mark@example.com', '$2y$10$dummyhash', 'premium', 'active', '2026-04-20 08:18:40', NULL),
(8, 'Ella Cruz', 'ella@example.com', '$2y$10$dummyhash', 'free', 'active', '2026-04-20 08:18:40', NULL),
(9, 'Ryan Torres', 'ryan@example.com', '$2y$10$dummyhash', 'free', 'active', '2026-04-20 08:18:40', NULL),
(10, 'Sophia Lim', 'sophia@example.com', '$2y$10$dummyhash', 'premium', 'active', '2026-04-20 08:18:40', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `businesses`
--
ALTER TABLE `businesses`
  ADD PRIMARY KEY (`business_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `template_id` (`template_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `business_id` (`business_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD KEY `business_id` (`business_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `business_id` (`business_id`);

--
-- Indexes for table `templates`
--
ALTER TABLE `templates`
  ADD PRIMARY KEY (`template_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `business_id` (`business_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `businesses`
--
ALTER TABLE `businesses`
  MODIFY `business_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `templates`
--
ALTER TABLE `templates`
  MODIFY `template_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `businesses`
--
ALTER TABLE `businesses`
  ADD CONSTRAINT `fk_business_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`template_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_business_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_category_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`business_id`) ON DELETE CASCADE;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `fk_customer_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`business_id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_report_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`business_id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_transaction_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`business_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_transaction_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_transaction_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
