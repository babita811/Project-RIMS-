-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 25, 2026 at 01:59 AM
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
(12, 'Marigold', '2026-02-23 02:11:57', '🌻'),
(14, 'Baby\'s Breath', '2026-02-28 06:13:09', '🤍'),
(15, 'Lotus', '2026-02-28 06:13:09', '🪷'),
(18, 'Sheep', '2026-03-23 02:00:04', '❤️'),
(25, 'Dancing Flower', '2026-03-24 10:22:03', '🌱'),
(26, 'hi', '2026-03-25 00:24:20', '🪻');

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
