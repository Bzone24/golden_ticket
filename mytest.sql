-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: mysql:3306
-- Generation Time: Aug 24, 2025 at 08:03 AM
-- Server version: 8.0.43
-- PHP Version: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mytest`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@example.com', '$2y$12$dS4RRYADchdajLy2DTX1m.vVktI9eNwhtzUx4F.0qTpiOJiMpCri.', NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cross_abcs`
--

CREATE TABLE `cross_abcs` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `ticket_id` bigint UNSIGNED NOT NULL,
  `draw_details_ids` json DEFAULT NULL,
  `abc` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `option` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `combination` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amt` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ab` json DEFAULT NULL,
  `ac` json DEFAULT NULL,
  `bc` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cross_abcs`
--

INSERT INTO `cross_abcs` (`id`, `user_id`, `ticket_id`, `draw_details_ids`, `abc`, `option`, `number`, `combination`, `amt`, `ab`, `ac`, `bc`, `created_at`, `updated_at`) VALUES
(1, 2, 1, '[1]', NULL, 'ABC', '456', '27', '10', '[44, 45, 46, 54, 55, 56, 64, 65, 66]', '[44, 45, 46, 54, 55, 56, 64, 65, 66]', '[44, 45, 46, 54, 55, 56, 64, 65, 66]', '2025-08-23 19:19:47', '2025-08-23 19:19:47');

-- --------------------------------------------------------

--
-- Table structure for table `cross_abc_details`
--

CREATE TABLE `cross_abc_details` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `ticket_id` bigint UNSIGNED NOT NULL,
  `draw_detail_id` bigint UNSIGNED NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `option` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `combination` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cross_abc_details`
--

INSERT INTO `cross_abc_details` (`id`, `user_id`, `ticket_id`, `draw_detail_id`, `type`, `number`, `amount`, `option`, `combination`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 1, 'AB', '44', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(2, 2, 1, 1, 'AB', '45', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(3, 2, 1, 1, 'AB', '46', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(4, 2, 1, 1, 'AB', '54', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(5, 2, 1, 1, 'AB', '55', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(6, 2, 1, 1, 'AB', '56', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(7, 2, 1, 1, 'AB', '64', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(8, 2, 1, 1, 'AB', '65', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(9, 2, 1, 1, 'AB', '66', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(10, 2, 1, 1, 'AC', '44', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(11, 2, 1, 1, 'AC', '45', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(12, 2, 1, 1, 'AC', '46', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(13, 2, 1, 1, 'AC', '54', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(14, 2, 1, 1, 'AC', '55', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(15, 2, 1, 1, 'AC', '56', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(16, 2, 1, 1, 'AC', '64', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(17, 2, 1, 1, 'AC', '65', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(18, 2, 1, 1, 'AC', '66', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(19, 2, 1, 1, 'BC', '44', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(20, 2, 1, 1, 'BC', '45', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(21, 2, 1, 1, 'BC', '46', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(22, 2, 1, 1, 'BC', '54', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(23, 2, 1, 1, 'BC', '55', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(24, 2, 1, 1, 'BC', '56', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(25, 2, 1, 1, 'BC', '64', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(26, 2, 1, 1, 'BC', '65', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(27, 2, 1, 1, 'BC', '66', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(28, 2, 1, 2, 'AB', '44', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(29, 2, 1, 2, 'AB', '45', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(30, 2, 1, 2, 'AB', '46', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(31, 2, 1, 2, 'AB', '54', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(32, 2, 1, 2, 'AB', '55', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(33, 2, 1, 2, 'AB', '56', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(34, 2, 1, 2, 'AB', '64', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(35, 2, 1, 2, 'AB', '65', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(36, 2, 1, 2, 'AB', '66', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(37, 2, 1, 2, 'AC', '44', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(38, 2, 1, 2, 'AC', '45', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(39, 2, 1, 2, 'AC', '46', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(40, 2, 1, 2, 'AC', '54', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(41, 2, 1, 2, 'AC', '55', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(42, 2, 1, 2, 'AC', '56', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(43, 2, 1, 2, 'AC', '64', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(44, 2, 1, 2, 'AC', '65', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(45, 2, 1, 2, 'AC', '66', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(46, 2, 1, 2, 'BC', '44', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(47, 2, 1, 2, 'BC', '45', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(48, 2, 1, 2, 'BC', '46', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(49, 2, 1, 2, 'BC', '54', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(50, 2, 1, 2, 'BC', '55', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(51, 2, 1, 2, 'BC', '56', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(52, 2, 1, 2, 'BC', '64', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(53, 2, 1, 2, 'BC', '65', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(54, 2, 1, 2, 'BC', '66', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(55, 2, 1, 3, 'AB', '44', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(56, 2, 1, 3, 'AB', '45', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(57, 2, 1, 3, 'AB', '46', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(58, 2, 1, 3, 'AB', '54', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(59, 2, 1, 3, 'AB', '55', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(60, 2, 1, 3, 'AB', '56', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(61, 2, 1, 3, 'AB', '64', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(62, 2, 1, 3, 'AB', '65', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(63, 2, 1, 3, 'AB', '66', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(64, 2, 1, 3, 'AC', '44', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(65, 2, 1, 3, 'AC', '45', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(66, 2, 1, 3, 'AC', '46', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(67, 2, 1, 3, 'AC', '54', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(68, 2, 1, 3, 'AC', '55', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(69, 2, 1, 3, 'AC', '56', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(70, 2, 1, 3, 'AC', '64', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(71, 2, 1, 3, 'AC', '65', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(72, 2, 1, 3, 'AC', '66', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(73, 2, 1, 3, 'BC', '44', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(74, 2, 1, 3, 'BC', '45', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(75, 2, 1, 3, 'BC', '46', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(76, 2, 1, 3, 'BC', '54', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(77, 2, 1, 3, 'BC', '55', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(78, 2, 1, 3, 'BC', '56', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(79, 2, 1, 3, 'BC', '64', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(80, 2, 1, 3, 'BC', '65', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(81, 2, 1, 3, 'BC', '66', '10', 'ABC', '27', '2025-08-23 19:19:47', '2025-08-23 19:19:47');

-- --------------------------------------------------------

--
-- Table structure for table `draws`
--

CREATE TABLE `draws` (
  `id` bigint UNSIGNED NOT NULL,
  `price` decimal(8,2) NOT NULL,
  `start_time` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `end_time` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('PENDING','ACTIVE','RUNNING','INACTIVE') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_collection` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `result` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_rewards` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `draws`
--

INSERT INTO `draws` (`id`, `price`, `start_time`, `end_time`, `status`, `total_collection`, `result`, `total_rewards`, `created_at`, `updated_at`) VALUES
(1, 11.00, '08:30', '08:45', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(2, 11.00, '08:45', '09:00', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(3, 11.00, '09:00', '09:15', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(4, 11.00, '09:15', '09:30', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(5, 11.00, '09:30', '09:45', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(6, 11.00, '09:45', '10:00', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(7, 11.00, '10:00', '10:15', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(8, 11.00, '10:15', '10:30', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(9, 11.00, '10:30', '10:45', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(10, 11.00, '10:45', '11:00', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(11, 11.00, '11:00', '11:15', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(12, 11.00, '11:15', '11:30', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(13, 11.00, '11:30', '11:45', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(14, 11.00, '11:45', '12:00', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(15, 11.00, '12:00', '12:15', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(16, 11.00, '12:15', '12:30', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(17, 11.00, '12:30', '12:45', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(18, 11.00, '12:45', '13:00', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(19, 11.00, '13:00', '13:15', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(20, 11.00, '13:15', '13:30', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(21, 11.00, '13:30', '13:45', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(22, 11.00, '13:45', '14:00', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(23, 11.00, '14:00', '14:15', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(24, 11.00, '14:15', '14:30', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(25, 11.00, '14:30', '14:45', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(26, 11.00, '14:45', '15:00', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(27, 11.00, '15:00', '15:15', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(28, 11.00, '15:15', '15:30', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(29, 11.00, '15:30', '15:45', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(30, 11.00, '15:45', '16:00', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(31, 11.00, '16:00', '16:15', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(32, 11.00, '16:15', '16:30', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(33, 11.00, '16:30', '16:45', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(34, 11.00, '16:45', '17:00', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(35, 11.00, '17:00', '17:15', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(36, 11.00, '17:15', '17:30', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(37, 11.00, '17:30', '17:45', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(38, 11.00, '17:45', '18:00', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(39, 11.00, '18:00', '18:15', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(40, 11.00, '18:15', '18:30', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(41, 11.00, '18:30', '18:45', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(42, 11.00, '18:45', '19:00', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(43, 11.00, '19:00', '19:15', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(44, 11.00, '19:15', '19:30', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(45, 11.00, '19:30', '19:45', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(46, 11.00, '19:45', '20:00', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(47, 11.00, '20:00', '20:15', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(48, 11.00, '20:15', '20:30', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(49, 11.00, '20:30', '20:45', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(50, 11.00, '20:45', '21:00', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(51, 11.00, '21:00', '21:15', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(52, 11.00, '21:15', '21:30', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(53, 11.00, '21:30', '21:45', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32'),
(54, 11.00, '21:45', '22:00', NULL, NULL, NULL, NULL, '2025-08-23 19:15:32', '2025-08-23 19:15:32');

-- --------------------------------------------------------

--
-- Table structure for table `draw_details`
--

CREATE TABLE `draw_details` (
  `id` bigint UNSIGNED NOT NULL,
  `draw_id` bigint UNSIGNED NOT NULL,
  `claim_a` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `claim_b` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `claim_c` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `claim` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ab` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ac` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bc` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `claim_ab` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `claim_ac` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `claim_bc` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_cross_amt` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_qty` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `end_time` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `draw_details`
--

INSERT INTO `draw_details` (`id`, `draw_id`, `claim_a`, `claim_b`, `claim_c`, `claim`, `ab`, `ac`, `bc`, `claim_ab`, `claim_ac`, `claim_bc`, `total_cross_amt`, `total_qty`, `start_time`, `end_time`, `date`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '270', '90', '08:30', '08:45', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:19:47'),
(2, 2, '4', '5', '6', '0', '45', '46', '56', '10', '10', '10', '270', '90', '00:45', '01:00', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:43:55'),
(3, 3, '1', '2', '3', '30', '12', '13', '23', '0', '0', '0', '270', '90', '00:30', '00:50', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:22:44'),
(4, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '00:30', '00:50', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(5, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '09:30', '09:45', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(6, 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '09:45', '10:00', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(7, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '10:00', '10:15', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(8, 8, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '10:15', '10:30', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(9, 9, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '10:30', '10:45', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(10, 10, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '10:45', '11:00', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(11, 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '11:00', '11:15', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(12, 12, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '11:15', '11:30', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(13, 13, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '11:30', '11:45', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(14, 14, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '11:45', '12:00', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(15, 15, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '12:00', '12:15', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(16, 16, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '12:15', '12:30', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(17, 17, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '12:30', '12:45', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(18, 18, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '12:45', '13:00', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(19, 19, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '13:00', '13:15', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(20, 20, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '13:15', '13:30', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(21, 21, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '13:30', '13:45', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(22, 22, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '13:45', '14:00', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(23, 23, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '14:00', '14:15', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(24, 24, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '14:15', '14:30', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(25, 25, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '14:30', '14:45', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(26, 26, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '14:45', '15:00', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(27, 27, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '15:00', '15:15', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(28, 28, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '15:15', '15:30', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(29, 29, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '15:30', '15:45', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(30, 30, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '15:45', '16:00', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(31, 31, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '16:00', '16:15', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(32, 32, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '16:15', '16:30', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(33, 33, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '16:30', '16:45', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(34, 34, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '16:45', '17:00', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(35, 35, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '17:00', '17:15', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(36, 36, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '17:15', '17:30', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(37, 37, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '17:30', '17:45', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(38, 38, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '17:45', '18:00', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(39, 39, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '18:00', '18:15', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(40, 40, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '18:15', '18:30', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(41, 41, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '18:30', '18:45', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(42, 42, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '18:45', '19:00', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(43, 43, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '19:00', '19:15', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(44, 44, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '19:15', '19:30', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(45, 45, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '19:30', '19:45', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(46, 46, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '19:45', '20:00', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(47, 47, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '20:00', '20:15', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(48, 48, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '20:15', '20:30', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(49, 49, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '20:30', '20:45', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(50, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '20:45', '21:00', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(51, 51, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '21:00', '21:15', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(52, 52, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '21:15', '21:30', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(53, 53, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '21:30', '21:45', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40'),
(54, 54, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '21:45', '22:00', '2025-08-24', '2025-08-23 19:15:40', '2025-08-23 19:15:40');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_07_26_042017_create_draws_table', 1),
(5, '2025_07_26_042018_create_draw_details_table', 1),
(6, '2025_07_27_102139_create_tickets_table', 1),
(7, '2025_07_27_103210_create_options_table', 1),
(8, '2025_07_27_145424_create_user_draws_table', 1),
(9, '2025_07_28_002456_create_ticket_options_table', 1),
(10, '2025_08_16_174145_create_cross_abcs_table', 1),
(11, '2025_08_17_122843_create_cross_abc_details_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `options`
--

CREATE TABLE `options` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `draw_details_ids` json NOT NULL,
  `ticket_id` bigint UNSIGNED NOT NULL,
  `number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `option` enum('A','B','C') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('RUNNING','COMPLETED','LOCK') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `options`
--

INSERT INTO `options` (`id`, `user_id`, `draw_details_ids`, `ticket_id`, `number`, `option`, `qty`, `total`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, '[1, 2, 3]', 1, '123', 'C', '10', '330', 'COMPLETED', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(2, 2, '[1, 2, 3]', 1, '123', 'B', '10', '330', 'COMPLETED', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(3, 2, '[1, 2, 3]', 1, '123', 'A', '10', '330', 'COMPLETED', '2025-08-23 19:19:47', '2025-08-23 19:19:47');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('pPjYjv7TTSj3FvzeItnaRWJAq67isCnDaEIS6wQX', 2, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'YTo2OntzOjY6Il90b2tlbiI7czo0MDoiYUpMa1pGUVM3cWhSeVRiN2VGcUg2aDNtRjcxdk5XajZHRGpVeDB4biI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozMToiaHR0cDovL2xvY2FsaG9zdDo4MDAwL2Rhc2hib2FyZCI7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjMxOiJodHRwOi8vbG9jYWxob3N0OjgwMDAvZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MjoibG9naW5fYWRtaW5fNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO3M6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI7fQ==', 1755978680);

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `ticket_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('RUNNING','COMPLETED') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `user_id`, `ticket_number`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'A2-101', 'COMPLETED', '2025-08-23 19:19:47', '2025-08-23 19:19:47');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_options`
--

CREATE TABLE `ticket_options` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `draw_detail_id` bigint UNSIGNED NOT NULL,
  `ticket_id` bigint UNSIGNED NOT NULL,
  `number` int DEFAULT NULL,
  `a_qty` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `b_qty` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `c_qty` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ticket_options`
--

INSERT INTO `ticket_options` (`id`, `user_id`, `draw_detail_id`, `ticket_id`, `number`, `a_qty`, `b_qty`, `c_qty`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 1, 1, '10', '10', '10', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(2, 2, 1, 1, 2, '10', '10', '10', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(3, 2, 1, 1, 3, '10', '10', '10', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(4, 2, 2, 1, 1, '10', '10', '10', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(5, 2, 2, 1, 2, '10', '10', '10', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(6, 2, 2, 1, 3, '10', '10', '10', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(7, 2, 3, 1, 1, '10', '10', '10', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(8, 2, 3, 1, 2, '10', '10', '10', '2025-08-23 19:19:47', '2025-08-23 19:19:47'),
(9, 2, 3, 1, 3, '10', '10', '10', '2025-08-23 19:19:47', '2025-08-23 19:19:47');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `mobile_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile_number_verified_at` timestamp NULL DEFAULT NULL,
  `ticket_series` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_plain` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `email_verified_at`, `mobile_number`, `mobile_number_verified_at`, `ticket_series`, `password`, `password_plain`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Neeaj', 'choudhary', 'neeraj@test.com', NULL, '09509797669', NULL, 'A1-100', '$2y$12$dD6PYCWcB.GDZDyQGChZb.s1oFxSlu461jOHsQHriNGN6ClCyrycm', 'eyJpdiI6IjQwemxiTkdaZ0QybTB2a0c4dWJaWFE9PSIsInZhbHVlIjoid2VIUzR0Q1M0Q284OXFrSWFpMkdsZz09IiwibWFjIjoiNWM0NmY2MzMxMWYzMTBkN2NiYWNjZmEwMTVmMTg5MDcxZDI4Y2ZmY2RkNzYwZTZlNTcwNjQ4YzMyNTZkNzM0ZCIsInRhZyI6IiJ9', NULL, '2025-08-23 19:18:54', '2025-08-23 19:18:54'),
(2, 'Rohit', 'Kumar', 'rohitkumar@mail.com', NULL, '09509797660', NULL, 'A2-100', '$2y$12$1dLH86i70uvEeHRx/KmbXOeTUbsCwD15Oo.bz0u0bgbki5f3p5J/G', 'eyJpdiI6InZQWEFtSWh1QWhwcHJNWHozRVJteEE9PSIsInZhbHVlIjoiemVha3kzLzMxQU9keFVRRHRLTlZFRFZVdjEyYzdnN0JkU2gxaTBBS0RVVT0iLCJtYWMiOiI5MTc5OTE1ZWFhNDFhOTAxODZlY2MxNThmNjMwZDA1ZTcwZDA2NDE5YjU4MDY4NDczZWQ1OTRjYmUzMDZlNGFhIiwidGFnIjoiIn0=', NULL, '2025-08-23 19:19:12', '2025-08-23 19:19:12');

-- --------------------------------------------------------

--
-- Table structure for table `user_draws`
--

CREATE TABLE `user_draws` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `draw_detail_id` bigint UNSIGNED NOT NULL,
  `total_draws` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_draws`
--

INSERT INTO `user_draws` (`id`, `user_id`, `draw_detail_id`, `total_draws`, `created_at`, `updated_at`) VALUES
(1, 2, 1, NULL, NULL, NULL),
(2, 2, 2, NULL, NULL, NULL),
(3, 2, 3, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admins_email_unique` (`email`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cross_abcs`
--
ALTER TABLE `cross_abcs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cross_abcs_user_id_foreign` (`user_id`),
  ADD KEY `cross_abcs_ticket_id_foreign` (`ticket_id`);

--
-- Indexes for table `cross_abc_details`
--
ALTER TABLE `cross_abc_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cross_abc_details_user_id_foreign` (`user_id`),
  ADD KEY `cross_abc_details_ticket_id_foreign` (`ticket_id`),
  ADD KEY `cross_abc_details_draw_detail_id_foreign` (`draw_detail_id`);

--
-- Indexes for table `draws`
--
ALTER TABLE `draws`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `draw_details`
--
ALTER TABLE `draw_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `draw_details_draw_id_date_unique` (`draw_id`,`date`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `options`
--
ALTER TABLE `options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `options_user_id_foreign` (`user_id`),
  ADD KEY `options_ticket_id_foreign` (`ticket_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tickets_user_id_foreign` (`user_id`);

--
-- Indexes for table `ticket_options`
--
ALTER TABLE `ticket_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_options_user_id_foreign` (`user_id`),
  ADD KEY `ticket_options_draw_detail_id_foreign` (`draw_detail_id`),
  ADD KEY `ticket_options_ticket_id_foreign` (`ticket_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `user_draws`
--
ALTER TABLE `user_draws`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_draws_user_id_foreign` (`user_id`),
  ADD KEY `user_draws_draw_detail_id_foreign` (`draw_detail_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cross_abcs`
--
ALTER TABLE `cross_abcs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cross_abc_details`
--
ALTER TABLE `cross_abc_details`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `draws`
--
ALTER TABLE `draws`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `draw_details`
--
ALTER TABLE `draw_details`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `options`
--
ALTER TABLE `options`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ticket_options`
--
ALTER TABLE `ticket_options`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_draws`
--
ALTER TABLE `user_draws`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cross_abcs`
--
ALTER TABLE `cross_abcs`
  ADD CONSTRAINT `cross_abcs_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cross_abcs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cross_abc_details`
--
ALTER TABLE `cross_abc_details`
  ADD CONSTRAINT `cross_abc_details_draw_detail_id_foreign` FOREIGN KEY (`draw_detail_id`) REFERENCES `draw_details` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cross_abc_details_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cross_abc_details_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `draw_details`
--
ALTER TABLE `draw_details`
  ADD CONSTRAINT `draw_details_draw_id_foreign` FOREIGN KEY (`draw_id`) REFERENCES `draws` (`id`);

--
-- Constraints for table `options`
--
ALTER TABLE `options`
  ADD CONSTRAINT `options_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `options_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ticket_options`
--
ALTER TABLE `ticket_options`
  ADD CONSTRAINT `ticket_options_draw_detail_id_foreign` FOREIGN KEY (`draw_detail_id`) REFERENCES `draw_details` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_options_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_options_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_draws`
--
ALTER TABLE `user_draws`
  ADD CONSTRAINT `user_draws_draw_detail_id_foreign` FOREIGN KEY (`draw_detail_id`) REFERENCES `draw_details` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_draws_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
