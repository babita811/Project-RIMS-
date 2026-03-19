-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 19, 2026 at 09:42 AM
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
-- Database: `rims_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `icon` varchar(10) DEFAULT '?'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `created_at`, `icon`) VALUES
(1, 'Rose', '2026-02-23 01:37:32', '🌹'),
(2, 'Lily', '2026-02-23 01:37:32', '🌼'),
(3, 'Tulip', '2026-02-23 01:37:32', '🌺'),
(4, 'Orchid', '2026-02-23 01:37:32', '🌾'),
(5, 'Gerbera', '2026-02-23 01:37:32', '🌿'),
(6, 'Sunflower', '2026-02-23 01:37:32', '🌻'),
(7, 'Lavender', '2026-02-23 01:37:32', '🌷'),
(8, 'Carnation', '2026-02-23 01:37:32', '🪻'),
(10, 'Dancing Flower', '2026-02-23 02:07:25', '🌿'),
(12, 'Marigold', '2026-02-23 02:11:57', '🌻'),
(14, 'Baby\'s Breath', '2026-02-28 06:13:09', '🤍'),
(15, 'Lotus', '2026-02-28 06:13:09', '🪷'),
(16, 'Love', '2026-03-19 07:35:51', '🌱');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `phone`, `message`, `is_read`, `sent_at`, `user_id`) VALUES
(1, '9842789450', 'Your roducts are extremely good.', 1, '2026-02-23 02:05:27', NULL),
(2, '9842789450', 'Excellent flowers!!!!', 1, '2026-02-23 02:24:47', NULL),
(3, '9842789450', 'hello!!!', 1, '2026-03-08 09:30:25', 4),
(4, '9842789450', 'NICE PRODUCTS', 1, '2026-03-08 09:35:18', 4),
(5, '9842789450', 'Hello', 1, '2026-03-12 01:00:13', 4),
(6, '986044667', 'hi', 1, '2026-03-14 09:59:02', 4),
(7, '986044667', 'Hello', 1, '2026-03-14 12:41:08', 4),
(8, '', 'hi', 1, '2026-03-15 07:11:46', 4),
(9, '', 'awesome', 1, '2026-03-15 07:26:48', 4),
(10, '9842789450', 'HELLO', 1, '2026-03-17 00:37:07', 4),
(11, '', 'hi', 1, '2026-03-19 08:18:03', NULL),
(12, '', 'hii', 1, '2026-03-19 08:23:12', NULL),
(13, '', 'hiiiiiiiii', 1, '2026-03-19 08:32:56', 5);

-- --------------------------------------------------------

--
-- Table structure for table `message_replies`
--

CREATE TABLE `message_replies` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `reply_text` text NOT NULL,
  `replied_at` datetime NOT NULL DEFAULT current_timestamp(),
  `sender` enum('admin','user') NOT NULL DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `message_replies`
--

INSERT INTO `message_replies` (`id`, `message_id`, `reply_text`, `replied_at`, `sender`) VALUES
(1, 7, 'hello maam', '2026-03-14 19:06:31', 'admin'),
(2, 6, 'hello', '2026-03-14 19:18:39', 'admin'),
(3, 9, 'Thanks', '2026-03-15 13:25:16', 'admin'),
(4, 10, 'hi', '2026-03-19 07:29:50', 'admin'),
(5, 13, '?', '2026-03-19 14:18:06', 'user'),
(6, 13, '??', '2026-03-19 14:25:47', 'user'),
(7, 10, 'hey', '2026-03-19 14:26:17', 'user'),
(8, 13, 'yes', '2026-03-19 14:27:08', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `quantity`, `image`, `category_id`) VALUES
(13, 'Lotus', 500.00, 9, 'lotus.jpg', 15),
(14, 'Sunflower', 600.00, 0, 'sunflower.jpg', 6),
(15, 'Gerbera', 400.00, 6, 'gerbera.jpg', 5),
(16, 'Lavender', 580.00, 6, 'Lavnder.jpg', 7),
(17, 'Carnation', 490.00, 10, 'carnation.jpg', 8),
(18, 'Baby\'s Breath', 480.00, 10, 'Babys Breath.jpg', 14),
(19, 'Rose', 600.00, 0, 'rose.jpg', 1),
(20, 'Lily', 660.00, 37, 'lily.jpg', 2),
(21, 'Tulip', 540.00, 15, 'tulip.jpg', 3),
(22, 'Orchid', 550.00, 10, 'orchid.jpg', 4);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `sale_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `product_id`, `user_id`, `order_id`, `quantity`, `subtotal`, `sale_date`) VALUES
(1, 13, NULL, NULL, 1, 500.00, '2026-02-16 11:19:12'),
(2, 13, NULL, NULL, 1, 500.00, '2026-02-16 11:19:20'),
(3, 14, NULL, NULL, 1, 520.00, '2026-02-16 11:22:10'),
(4, 13, NULL, NULL, 1, 500.00, '2026-02-16 11:25:16'),
(5, 13, NULL, NULL, 1, 500.00, '2026-02-16 11:26:41'),
(6, 16, NULL, NULL, 1, 580.00, '2026-02-17 08:05:26'),
(7, 15, NULL, NULL, 12, 4800.00, '2026-02-17 08:57:26'),
(8, 13, NULL, NULL, 11, 5500.00, '2026-02-17 16:08:37'),
(9, 13, NULL, NULL, 1, 500.00, '2026-02-17 16:35:34'),
(10, 20, NULL, NULL, 1, 660.00, '2026-02-22 14:17:00'),
(11, 20, NULL, NULL, 1, 660.00, '2026-02-22 14:17:00'),
(12, 20, NULL, NULL, 1, 660.00, '2026-02-22 14:17:00'),
(13, 20, NULL, NULL, 1, 660.00, '2026-02-22 14:17:00'),
(14, 20, NULL, NULL, 1, 660.00, '2026-02-22 14:17:00'),
(15, 16, NULL, NULL, 1, 580.00, '2026-02-22 14:17:00'),
(16, 17, NULL, NULL, 1, 490.00, '2026-02-22 14:17:00'),
(17, 18, NULL, NULL, 1, 480.00, '2026-02-22 14:17:00'),
(18, 19, NULL, NULL, 1, 600.00, '2026-02-22 14:17:00'),
(19, 20, NULL, NULL, 1, 660.00, '2026-02-22 14:17:00'),
(20, 21, NULL, NULL, 1, 540.00, '2026-02-22 14:17:00'),
(21, 22, NULL, NULL, 1, 550.00, '2026-02-22 14:17:00'),
(22, 15, NULL, NULL, 1, 400.00, '2026-02-22 14:17:00'),
(23, 14, NULL, NULL, 1, 600.00, '2026-02-22 14:17:00'),
(24, 14, NULL, NULL, 1, 600.00, '2026-02-22 14:17:00'),
(25, 14, NULL, NULL, 1, 600.00, '2026-02-22 15:12:45'),
(26, 15, NULL, NULL, 1, 400.00, '2026-02-22 15:13:14'),
(27, 19, NULL, NULL, 1, 600.00, '2026-02-22 15:15:43'),
(28, 18, NULL, NULL, 1, 480.00, '2026-02-22 15:16:17'),
(29, 19, NULL, NULL, 7, 4200.00, '2026-02-22 19:37:08'),
(30, 18, 4, NULL, 5, 2400.00, '2026-02-22 19:41:28'),
(31, 18, NULL, NULL, 1, 480.00, '2026-02-22 20:08:35'),
(32, 14, 5, NULL, 3, 1800.00, '2026-02-22 20:37:31'),
(33, 16, 5, NULL, 1, 580.00, '2026-02-22 20:37:31'),
(34, 19, 5, NULL, 6, 3600.00, '2026-02-22 20:37:31'),
(35, 19, 4, NULL, 1, 600.00, '2026-02-22 21:10:51'),
(36, 13, 5, NULL, 4, 2000.00, '2026-02-23 06:57:01'),
(37, 22, 4, NULL, 2, 1100.00, '2026-02-23 07:07:54'),
(38, 19, 4, NULL, 1, 600.00, '2026-02-23 08:09:09'),
(39, 22, 4, NULL, 3, 1650.00, '2026-02-23 08:09:09'),
(40, 15, 4, 3, 5, 2000.00, '2026-02-23 08:56:50'),
(41, 19, 4, 4, 1, 600.00, '2026-02-23 10:27:18'),
(42, 14, 4, 3, 5, 3000.00, '2026-02-24 13:44:01'),
(43, 17, 4, 4, 19, 9310.00, '2026-02-24 13:49:34'),
(44, 17, 4, 6, 5, 2450.00, '2026-02-27 07:18:43'),
(45, 22, 4, 7, 9, 4950.00, '2026-02-27 16:52:22'),
(46, 13, 4, 8, 6, 3000.00, '2026-03-01 17:30:28'),
(47, 14, 4, 9, 10, 6000.00, '2026-03-08 15:10:12'),
(48, 15, 4, 10, 10, 4000.00, '2026-03-08 15:12:36'),
(49, 16, 4, 11, 10, 5800.00, '2026-03-08 15:19:55'),
(50, 13, NULL, NULL, 2, 1000.00, '2026-03-08 15:31:45'),
(51, 13, 4, 12, 12, 6000.00, '2026-03-12 06:44:39'),
(52, 14, 4, 13, 10, 6000.00, '2026-03-14 15:43:36'),
(53, 20, NULL, NULL, 10, 6600.00, '2026-03-15 13:25:54'),
(54, 19, NULL, NULL, 12, 7200.00, '2026-03-19 13:22:13'),
(55, 13, NULL, NULL, 1, 500.00, '2026-03-19 13:22:21'),
(56, 14, 4, 14, 15, 9000.00, '2026-03-19 14:26:45');

-- --------------------------------------------------------

--
-- Table structure for table `sales_details`
--

CREATE TABLE `sales_details` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `note` text DEFAULT NULL,
  `payment_method` varchar(20) DEFAULT 'COD',
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(30) DEFAULT 'Pending',
  `ordered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_details`
--

INSERT INTO `sales_details` (`id`, `user_id`, `address`, `city`, `note`, `payment_method`, `total_amount`, `status`, `ordered_at`) VALUES
(1, 4, 'Chhauni -09', 'Kathmandu', '', 'COD', 3000.00, 'Pending', '2026-02-24 07:59:01'),
(2, 4, 'Chhauni -09', 'Kathmandu', '', 'COD', 9310.00, 'Pending', '2026-02-24 08:04:34'),
(3, 4, 'Chhauni -09', 'Kathmandu', 'Fresh flowers only!!!', 'COD', 2000.00, 'Pending', '2026-02-23 03:11:50'),
(4, 4, 'Chhauni -09', 'Kathmandu', 'handle with care for fresh flower.', 'COD', 600.00, 'Pending', '2026-02-23 04:42:18'),
(6, 4, 'Chhauni -09', 'Kathmandu', '', 'COD', 2450.00, 'Pending', '2026-02-27 01:33:43'),
(7, 4, 'Chhauni -09', 'Kathmandu', '', 'COD', 4950.00, 'Pending', '2026-02-27 11:07:22'),
(8, 4, 'Chhauni -09', 'Kathmandu', '', 'COD', 3000.00, 'Pending', '2026-03-01 11:45:28'),
(9, 4, 'Chhauni -09', 'Kathmandu', '', 'COD', 6000.00, 'Pending', '2026-03-08 09:25:12'),
(10, 4, 'Chhauni -09', 'Kathmandu', '', 'COD', 4000.00, 'Pending', '2026-03-08 09:27:36'),
(11, 4, 'Chhauni -09', 'Kathmandu', '', 'COD', 5800.00, 'Pending', '2026-03-08 09:34:55'),
(12, 4, 'Chhauni -09', 'Kathmandu', '', 'COD', 6000.00, 'Pending', '2026-03-12 00:59:39'),
(13, 4, 'Chhauni -09', 'Kathmandu', '', 'COD', 6000.00, 'Pending', '2026-03-14 09:58:36'),
(14, 4, 'Chhauni -09', 'Kathmandu', '', 'COD', 9000.00, 'Pending', '2026-03-19 08:41:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','client') NOT NULL DEFAULT 'client',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `username`, `password`, `role`, `created_at`, `phone`, `address`, `city`) VALUES
(1, 'Admin', 'admin@rims.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2026-02-22 03:07:06', NULL, NULL, NULL),
(2, 'user one', 'user@example.com', 'user1', '$2y$10$yEb1NLhoeUcTuOalk8HIfuo7b3P8s4JNUQjloso1cFKrP0A/PeJV.', 'client', '2026-02-22 03:13:48', NULL, NULL, NULL),
(4, 'Babita Tamang', 'babita@gmail.com', 'babita', '$2y$10$NNTP94ML4Q9Wb3dsyfQgqO7BfFW/s07QCxfr4T.EypbNN1WWf1em.', 'client', '2026-02-22 13:51:32', '980789004', 'Chhauni -09', 'Kathmandu'),
(5, 'Shristi Maharjan', 'shristi@1gmail.com', 'Shristi', '$2y$10$158wNzIDsVkGdGxY345A8emS/qWWGxrlVbqzX4JcVwpJ7vEX5I65K', 'client', '2026-02-22 14:51:47', NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_message_user` (`user_id`);

--
-- Indexes for table `message_replies`
--
ALTER TABLE `message_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reply_message` (`message_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_product_category` (`category_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sales_user2` (`user_id`),
  ADD KEY `fk_sales_product` (`product_id`),
  ADD KEY `fk_sales_order2` (`order_id`);

--
-- Indexes for table `sales_details`
--
ALTER TABLE `sales_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `message_replies`
--
ALTER TABLE `message_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `sales_details`
--
ALTER TABLE `sales_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_message_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `message_replies`
--
ALTER TABLE `message_replies`
  ADD CONSTRAINT `fk_reply_message` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_order` FOREIGN KEY (`order_id`) REFERENCES `sales_details` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sales_order2` FOREIGN KEY (`order_id`) REFERENCES `sales_details` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sales_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sales_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sales_user2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_details`
--
ALTER TABLE `sales_details`
  ADD CONSTRAINT `sales_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
