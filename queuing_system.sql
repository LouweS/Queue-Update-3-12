-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2025 at 03:47 PM
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
-- Database: `queuing_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `counters`
--

CREATE TABLE `counters` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `service_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`service_types`)),
  `is_online` tinyint(1) DEFAULT 1,
  `current_customer_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `counters`
--

INSERT INTO `counters` (`id`, `name`, `service_types`, `is_online`, `current_customer_id`) VALUES
(1, 'CASHIER 1', '[\"bills\", \"customer_service\"]', 1, NULL),
(2, 'CASHIER 2', '[\"complaints\"]', 1, NULL),
(3, 'CUSTOMER SERVICE 3', '[\"bills\"]', 1, NULL),
(4, 'CUSTOMER SERVICE 4', '["customer_service", "complaints"]', 1, NULL),
(5, 'Counter 5', '["bills", "customer_service", "complaints"]', 1, NULL),
(6, 'Counter 6', '["bills", "customer_service", "complaints"]', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `queue_number` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `service_type` varchar(50) NOT NULL,
  `is_priority` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('waiting','serving','completed','cancelled') DEFAULT 'waiting',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `called_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `display_settings`
--

CREATE TABLE `display_settings` (
  `id` int(11) NOT NULL,
  `company_name` varchar(100) DEFAULT 'Customer Service',
  `welcome_message` text DEFAULT NULL,
  `refresh_interval` int(11) DEFAULT 10,
  `ad_video_url` text DEFAULT NULL,
  `announcement_text` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `display_settings`
--

INSERT INTO `display_settings` (`id`, `company_name`, `welcome_message`, `refresh_interval`, `ad_video_url`, `announcement_text`) VALUES
(1, 'Customer Service Center', 'Welcome to our Service Center', 10, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4', 'Please prepare your queue number and wait for your turn.');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `counters`
--
ALTER TABLE `counters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `current_customer_id` (`current_customer_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `queue_number` (`queue_number`);

--
-- Indexes for table `display_settings`
--
ALTER TABLE `display_settings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `counters`
--
ALTER TABLE `counters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `display_settings`
--
ALTER TABLE `display_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `counters`
--
ALTER TABLE `counters`
  ADD CONSTRAINT `counters_ibfk_1` FOREIGN KEY (`current_customer_id`) REFERENCES `customers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
