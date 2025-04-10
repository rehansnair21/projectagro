-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 02, 2025 at 07:15 PM
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
-- Database: `agrofresh`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `is_guest` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_address` text NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `status` enum('pending','processing','out_for_delivery','delivered') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_id` varchar(255) DEFAULT NULL,
  `razorpay_order_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `shipping_address`, `payment_method`, `status`, `created_at`, `payment_id`, `razorpay_order_id`) VALUES
(1, 8, 21.00, 'hbhtybhynuimio8, pala, Nagaland - 684648', 'cod', '', '2025-03-24 04:56:42', NULL, NULL),
(3, 8, 21.00, 'hbhtybhynuimio8, pala, Nagaland - 684648', 'cod', '', '2025-03-24 05:04:07', NULL, NULL),
(28, 1, 42.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-25 06:35:45', NULL, NULL),
(29, 2, 350.00, 'palai, pala, Goa - 555555', 'cod', '', '2025-03-25 06:51:35', NULL, NULL),
(30, 2, 700.00, 'palai, pala, Goa - 555555', 'cod', '', '2025-03-25 06:52:49', NULL, NULL),
(31, 2, 350.00, 'palai, pala, Goa - 555555', 'cod', '', '2025-03-25 06:53:25', NULL, NULL),
(34, 2, 350.00, 'palai, pala, Goa - 555555', 'cod', '', '2025-03-25 08:07:48', NULL, NULL),
(35, 1, 21.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-25 08:08:16', NULL, NULL),
(36, 1, 63.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-25 08:51:11', NULL, NULL),
(37, 1, 105.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-25 09:10:05', NULL, NULL),
(38, 1, 21.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-25 15:49:45', NULL, NULL),
(40, 1, 42.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-25 17:56:18', NULL, NULL),
(41, 1, 21.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-25 17:57:48', NULL, NULL),
(42, 1, 21.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-25 18:01:37', NULL, NULL),
(43, 10, 700.00, 'ss wsxwxw, wxwxw, xwxsw - 54120', 'razorpay', '', '2025-03-25 18:16:55', NULL, NULL),
(44, 10, 350.00, 'ss wsxwxw, wxwxw, xwxsw - 54120', 'razorpay', '', '2025-03-25 18:18:42', NULL, NULL),
(45, 10, 350.00, 'ss wsxwxw, wxwxw, xwxsw - 54120', 'razorpay', '', '2025-03-25 18:20:27', NULL, NULL),
(46, 1, 21.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-25 18:26:22', NULL, NULL),
(47, 1, 21.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-25 18:26:44', NULL, NULL),
(48, 1, 21.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-25 18:32:08', NULL, NULL),
(49, 1, 0.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-25 18:34:08', NULL, NULL),
(50, 1, 21.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-25 18:36:18', NULL, NULL),
(51, 1, 0.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-25 18:36:43', NULL, NULL),
(52, 1, 21.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-25 18:37:21', NULL, NULL),
(53, 1, 110.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-26 04:06:09', NULL, NULL),
(54, 1, 77.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-26 04:12:03', NULL, NULL),
(55, 1, 55.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-26 04:42:22', NULL, NULL),
(56, 1, 77.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-26 04:45:36', NULL, NULL),
(57, 1, 0.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-26 04:45:45', NULL, NULL),
(58, 1, 0.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-26 04:46:15', NULL, NULL),
(59, 1, 55.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-26 04:46:38', NULL, NULL),
(60, 1, 0.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-26 04:46:46', NULL, NULL),
(61, 2, 350.00, 'palai, pala, Goa - 555555', 'razorpay', '', '2025-03-26 04:50:51', NULL, NULL),
(62, 2, 350.00, 'palai, pala, Goa - 555555', 'razorpay', '', '2025-03-26 04:53:27', NULL, NULL),
(63, 1, 77.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-26 05:33:56', NULL, NULL),
(64, 2, 350.00, 'palai, pala, Goa - 555555', 'razorpay', '', '2025-03-26 05:42:54', NULL, NULL),
(65, 1, 0.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-26 05:49:46', NULL, NULL),
(66, 1, 0.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-26 05:49:57', NULL, NULL),
(67, 2, 350.00, 'palai, pala, Goa - 555555', 'cod', '', '2025-03-26 05:50:36', NULL, NULL),
(68, 2, 0.00, 'palai, pala, Goa - 555555', 'cod', '', '2025-03-26 05:51:43', NULL, NULL),
(69, 2, 350.00, 'palai, pala, Goa - 555555', 'cod', '', '2025-03-26 05:52:02', NULL, NULL),
(70, 2, 55.00, 'palai, pala, Goa - 555555', 'razorpay', '', '2025-03-26 06:00:01', NULL, NULL),
(71, 1, 55.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-26 06:43:44', NULL, NULL),
(72, 1, 77.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-26 06:44:47', NULL, NULL),
(73, 1, 77.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-26 06:47:37', NULL, NULL),
(74, 1, 77.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-26 06:50:46', NULL, NULL),
(75, 1, 77.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-26 06:54:00', NULL, NULL),
(76, 1, 88.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-26 06:56:43', NULL, NULL),
(77, 1, 77.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-26 08:18:35', NULL, NULL),
(78, 1, 0.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-26 08:27:38', NULL, NULL),
(79, 1, 77.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-26 08:28:44', NULL, NULL),
(80, 1, 88.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-26 08:48:48', NULL, NULL),
(81, 1, 77.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-26 08:54:24', NULL, NULL),
(82, 1, 77.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', 'delivered', '2025-03-26 09:09:42', NULL, NULL),
(83, 1, 88.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-26 09:13:22', NULL, NULL),
(84, 1, 88.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-26 09:20:36', NULL, NULL),
(85, 1, 77.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-26 09:23:34', NULL, NULL),
(86, 1, 0.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-26 09:29:51', NULL, NULL),
(87, 1, 88.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-26 09:31:08', NULL, NULL),
(88, 1, 88.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-26 09:36:27', NULL, NULL),
(89, 1, 88.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-26 09:40:09', NULL, NULL),
(90, 1, 88.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-26 09:43:22', NULL, NULL),
(91, 2, 23.00, 'palai, pala, Goa - 555555', 'cod', '', '2025-03-26 09:50:06', NULL, NULL),
(92, 2, 23.00, 'palai, pala, Goa - 555555', 'razorpay', '', '2025-03-26 09:50:50', NULL, NULL),
(93, 2, 23.00, 'palai, pala, Goa - 555555', 'razorpay', 'delivered', '2025-03-26 10:01:14', NULL, NULL),
(94, 2, 23.00, 'palai, pala, Goa - 555555', 'cod', 'delivered', '2025-03-26 10:11:20', NULL, NULL),
(95, 1, 48.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-26 16:19:05', NULL, NULL),
(96, 1, 48.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-26 16:21:38', NULL, NULL),
(97, 1, 48.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-26 16:25:02', NULL, NULL),
(98, 1, 48.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-26 16:27:06', NULL, NULL),
(99, 1, 48.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-26 16:38:30', NULL, NULL),
(100, 1, 48.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-27 05:55:50', NULL, NULL),
(101, 1, 96.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-27 08:39:38', NULL, NULL),
(102, 1, 48.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-27 08:44:07', NULL, NULL),
(103, 1, 144.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-27 08:49:59', NULL, NULL),
(104, 1, 144.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-03-27 08:50:41', NULL, NULL),
(105, 1, 192.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-27 09:44:17', NULL, NULL),
(106, 1, 144.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-03-27 10:05:00', NULL, NULL),
(107, 1, 144.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', 'delivered', '2025-03-27 10:23:16', NULL, NULL),
(108, 1, 48.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', 'delivered', '2025-04-01 04:37:08', NULL, NULL),
(109, 1, 192.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-04-01 05:00:07', NULL, NULL),
(110, 1, 0.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', '', '', '2025-04-01 08:32:58', NULL, NULL),
(111, 1, 0.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-04-01 08:48:51', NULL, NULL),
(112, 1, 144.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-04-01 08:49:15', NULL, NULL),
(113, 1, 0.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'razorpay', '', '2025-04-01 08:50:42', NULL, NULL),
(114, 1, 48.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'cod', '', '2025-04-01 09:35:49', NULL, NULL),
(115, 1, 48.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', '', 'delivered', '2025-04-02 16:13:39', NULL, NULL),
(117, 1, 0.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', '', 'pending', '2025-04-02 16:14:28', NULL, NULL),
(118, 1, 0.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', '', 'pending', '2025-04-02 16:14:29', NULL, NULL),
(120, 1, 0.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', '', 'pending', '2025-04-02 16:15:43', NULL, NULL),
(122, 1, 0.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', '', 'pending', '2025-04-02 16:17:21', NULL, NULL),
(123, 1, 0.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', '', 'pending', '2025-04-02 16:17:22', NULL, NULL),
(124, 1, 0.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', '', 'pending', '2025-04-02 16:17:22', NULL, NULL),
(125, 1, 144.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'online', '', '2025-04-02 16:17:42', 'pay_QEFgpQWdB4K9vK', 'order_QEFgiHkYixDG89'),
(126, 8, 144.00, 'lkjhgvcxcvghjk, pala, Nagaland - 684648', 'online', '', '2025-04-02 16:21:38', 'pay_QEFkyJRVoBsKma', 'order_QEFkrY2bw0JiOd'),
(127, 1, 48.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'online', '', '2025-04-02 16:24:35', 'pay_QEFo4kFfWNO2F9', 'order_QEFnyV1fAs61Yh'),
(128, 1, 48.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'online', '', '2025-04-02 16:29:53', 'pay_QEFth2rvAv7CQi', 'order_QEFtZ4bg6WMDGO'),
(129, 1, 48.00, 'cdhtyjukyukuyk, pala, Jharkhand - 567869', 'online', '', '2025-04-02 16:36:29', 'pay_QEG0ehF5TIsJQe', 'order_QEG0YErUyvuR9Y'),
(130, 3, 23.00, 'bngnhnnm, kan, Kerala - 678986', 'online', '', '2025-04-02 16:41:58', 'pay_QEG6RnjbFUW01n', 'order_QEG6KsSn0qcNj7'),
(131, 8, 23.00, 'lkjhgvcxcvghjk, pala, Nagaland - 684648', 'online', '', '2025-04-02 16:48:00', 'pay_QEGCrXr7lvs3Oa', 'order_QEGCiLvqQNOYDv'),
(132, 3, 887.00, 'bngnhnnm, pala, Kerala - 678986', 'online', '', '2025-04-02 17:02:29', 'pay_QEGS8dvqsTp0Np', 'order_QEGS1DDtRHI0Lw');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(24, 53, 10, 2, 55.00),
(28, 59, 10, 1, 55.00),
(35, 70, 10, 1, 55.00),
(36, 71, 10, 1, 55.00),
(54, 91, 24, 1, 23.00),
(55, 92, 24, 1, 23.00),
(56, 93, 24, 1, 23.00),
(57, 94, 24, 1, 23.00),
(58, 95, 25, 1, 48.00),
(59, 96, 25, 1, 48.00),
(60, 97, 25, 1, 48.00),
(61, 98, 25, 1, 48.00),
(62, 99, 25, 1, 48.00),
(63, 100, 25, 1, 48.00),
(64, 101, 25, 2, 48.00),
(65, 102, 25, 1, 48.00),
(66, 103, 25, 3, 48.00),
(67, 104, 25, 3, 48.00),
(68, 105, 25, 4, 48.00),
(69, 106, 25, 3, 48.00),
(70, 107, 25, 3, 48.00),
(71, 108, 25, 1, 48.00),
(72, 109, 25, 4, 48.00),
(73, 112, 25, 3, 48.00),
(74, 114, 25, 1, 48.00),
(75, 115, 25, 1, 48.00),
(76, 125, 25, 3, 48.00),
(77, 126, 25, 3, 48.00),
(78, 127, 25, 1, 48.00),
(79, 128, 25, 1, 48.00),
(80, 129, 25, 1, 48.00),
(81, 130, 24, 1, 23.00),
(82, 131, 24, 1, 23.00),
(83, 132, 26, 1, 887.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` varchar(50) NOT NULL DEFAULT 'success',
  `transaction_id` varchar(255) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `amount`, `payment_method`, `payment_status`, `transaction_id`, `payment_date`) VALUES
(2, 76, 88.00, 'razorpay', 'pending', NULL, '2025-03-26 06:56:43'),
(3, 77, 77.00, 'razorpay', 'pending', NULL, '2025-03-26 08:18:35'),
(5, 79, 77.00, 'cod', 'pending', NULL, '2025-03-26 08:28:44'),
(6, 80, 88.00, 'razorpay', 'pending', NULL, '2025-03-26 08:48:48'),
(7, 81, 77.00, 'razorpay', 'pending', NULL, '2025-03-26 08:54:24'),
(8, 82, 77.00, 'cod', 'pending', NULL, '2025-03-26 09:09:42'),
(9, 83, 88.00, 'razorpay', 'pending', NULL, '2025-03-26 09:13:22'),
(10, 84, 88.00, 'razorpay', 'pending', NULL, '2025-03-26 09:22:20'),
(11, 85, 77.00, 'razorpay', 'pending', NULL, '2025-03-26 09:23:34'),
(12, 86, 0.00, 'cod', 'pending', NULL, '2025-03-26 09:29:51'),
(13, 87, 88.00, 'razorpay', 'pending', NULL, '2025-03-26 09:31:08'),
(14, 88, 88.00, 'razorpay', 'pending', NULL, '2025-03-26 09:36:27'),
(15, 89, 88.00, 'razorpay', 'pending', NULL, '2025-03-26 09:40:09'),
(16, 90, 88.00, 'razorpay', 'pending', NULL, '2025-03-26 09:43:22'),
(17, 91, 23.00, 'cod', 'pending', NULL, '2025-03-26 09:50:06'),
(18, 92, 23.00, 'razorpay', 'pending', NULL, '2025-03-26 09:50:50'),
(19, 93, 23.00, 'razorpay', 'pending', NULL, '2025-03-26 10:01:14'),
(20, 94, 23.00, 'cod', 'pending', NULL, '2025-03-26 10:11:20'),
(21, 95, 48.00, 'razorpay', 'pending', NULL, '2025-03-26 16:19:05'),
(22, 96, 48.00, 'razorpay', 'pending', NULL, '2025-03-26 16:21:38'),
(23, 97, 48.00, 'razorpay', 'pending', NULL, '2025-03-26 16:25:02'),
(24, 98, 48.00, 'razorpay', 'pending', NULL, '2025-03-26 16:27:07'),
(25, 99, 48.00, 'razorpay', 'pending', NULL, '2025-03-26 16:38:30'),
(26, 100, 48.00, 'cod', 'pending', NULL, '2025-03-27 05:55:50'),
(27, 101, 96.00, 'cod', 'pending', NULL, '2025-03-27 08:39:38'),
(28, 102, 48.00, 'cod', 'pending', NULL, '2025-03-27 08:44:07'),
(29, 103, 144.00, 'razorpay', 'pending', NULL, '2025-03-27 08:49:59'),
(30, 104, 144.00, 'razorpay', 'pending', NULL, '2025-03-27 08:50:42'),
(31, 105, 192.00, 'cod', 'pending', NULL, '2025-03-27 09:44:17'),
(32, 106, 144.00, 'cod', 'pending', NULL, '2025-03-27 10:05:00'),
(33, 107, 144.00, 'cod', 'pending', NULL, '2025-03-27 10:23:16'),
(34, 108, 48.00, 'cod', 'pending', NULL, '2025-04-01 04:37:08'),
(35, 109, 192.00, 'cod', 'pending', NULL, '2025-04-01 05:00:07'),
(36, 110, 0.00, '', 'pending', NULL, '2025-04-01 08:32:58'),
(37, 111, 0.00, 'razorpay', 'pending', NULL, '2025-04-01 08:48:52'),
(38, 112, 144.00, 'razorpay', 'pending', NULL, '2025-04-01 08:49:15'),
(39, 113, 0.00, 'razorpay', 'pending', NULL, '2025-04-01 08:50:44'),
(40, 114, 48.00, 'cod', 'pending', NULL, '2025-04-01 09:35:49'),
(41, 125, 144.00, 'razorpay', 'completed', 'pay_QEFgpQWdB4K9vK', '2025-04-02 16:18:05'),
(42, 126, 144.00, 'razorpay', 'completed', 'pay_QEFkyJRVoBsKma', '2025-04-02 16:22:00'),
(43, 127, 48.00, 'razorpay', 'completed', 'pay_QEFo4kFfWNO2F9', '2025-04-02 16:24:57'),
(44, 128, 48.00, 'razorpay', 'completed', 'pay_QEFth2rvAv7CQi', '2025-04-02 16:30:15'),
(45, 129, 48.00, 'razorpay', 'completed', 'pay_QEG0ehF5TIsJQe', '2025-04-02 16:36:51'),
(46, 130, 23.00, 'razorpay', 'completed', 'pay_QEG6RnjbFUW01n', '2025-04-02 16:42:20'),
(47, 131, 23.00, 'razorpay', 'completed', 'pay_QEGCrXr7lvs3Oa', '2025-04-02 16:48:24'),
(48, 132, 887.00, 'razorpay', 'completed', 'pay_QEGS8dvqsTp0Np', '2025-04-02 17:02:54');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `seller_id`, `name`, `description`, `price`, `image_url`, `category`, `stock`, `created_at`, `updated_at`) VALUES
(10, 4, 'REHAN', 'QWERTYUIOL rfghjk fghjk tyhjk vgbnm', 55.00, 'uploads/1742961718_67e37c36b595e6.78751907.jpg', 'fruits-organic-fruits', 0, '2025-03-26 04:01:58', '2025-03-26 06:43:44'),
(24, 1, 'honey', 'gajbskjhiweugfewiyu', 23.00, 'uploads/1742982531_67e3cd8360b5f2.81109339.jpg', 'grains', 6, '2025-03-26 09:48:51', '2025-04-02 16:48:00'),
(25, 2, 'tomato', 'gggggggggggggggg', 48.00, 'uploads/1742984513_67e3d5410f3789.06446316.jpg', 'vegetables-organic-vegetables', 0, '2025-03-26 10:21:53', '2025-04-02 16:36:29'),
(26, 1, 'sdfsffs', 'sdfsfd sfsfs', 887.00, 'uploads/products/1743003672_images.jpg', 'fruits-organic-fruits', 49, '2025-03-26 15:41:12', '2025-04-02 17:02:29'),
(27, 1, 'tomato', 'xc', 30.00, 'uploads/products/1743053405_tomato.jpg', 'vegetables-organic-vegetables', 4, '2025-03-27 05:30:05', '2025-03-27 05:30:05'),
(28, 1, 'tomato', 'ddddddddddddddddd', 30.00, 'uploads/products/1743055650_tomato.jpg', 'fruits-organic-fruits', 4, '2025-03-27 06:07:30', '2025-03-27 06:07:30'),
(29, 1, 'Organic Apples', NULL, 150.00, NULL, 'fruits-organic-fruits', 88, '2025-04-01 17:18:26', '2025-04-01 17:21:04'),
(30, 1, 'Red Tomatoes', NULL, 30.00, NULL, 'vegetables', 77, '2025-04-01 17:18:26', '2025-04-01 17:21:04'),
(31, 1, 'Fresh Milk', NULL, 60.00, NULL, 'milk', 3, '2025-04-01 17:18:26', '2025-04-01 17:21:04'),
(32, 1, 'Sunflower Seeds', NULL, 120.00, NULL, 'seeds', 33, '2025-04-01 17:18:26', '2025-04-01 17:21:04'),
(33, 1, 'Wheat Flour', NULL, 45.00, NULL, 'grains', 5, '2025-04-01 17:18:26', '2025-04-01 17:21:04'),
(34, 1, 'Carrots', NULL, 40.00, NULL, 'vegetables', 50, '2025-04-01 17:18:26', '2025-04-01 17:21:04'),
(35, 1, 'Bananas', NULL, 80.00, NULL, 'fruits', 265, '2025-04-01 17:18:26', '2025-04-01 17:21:05'),
(36, 1, 'dfgh', 'sdasa adad', 77.00, 'uploads/67ec211796d90_1743528215.jpg', 'dairy', 99, '2025-04-01 17:21:05', '2025-04-01 17:25:31');

-- --------------------------------------------------------

--
-- Table structure for table `seller`
--

CREATE TABLE `seller` (
  `seller_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `seller_name` varchar(255) NOT NULL,
  `location` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sellerdetails`
--

CREATE TABLE `sellerdetails` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `address` text NOT NULL,
  `state` varchar(100) NOT NULL,
  `pincode` varchar(10) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sellerdetails`
--

INSERT INTO `sellerdetails` (`id`, `full_name`, `email`, `mobile`, `address`, `state`, `pincode`, `password`, `created_at`) VALUES
(1, 'rehan', 'rehansnair@gmail.com', '7907243685', 'cdhtyjukyukuyk', 'Jharkhand', '567869', '$2y$10$Udz6eSJrbUhHDKCf3wILh.neKnbcXaV.4i1mIybbY9EcDy5EaTl6S', '2025-03-20 08:07:10'),
(2, 'barroz', 'barrozghost@gmail.com', '9999945123', 'palai', 'Goa', '555555', '$2y$10$oi3xjpamh8P81FAoXpHhIO7Hkg9B0yJ58yX40swSx5PWZ4IPhC6E6', '2025-03-19 08:43:02'),
(3, 'divin babu', 'divin@gmail.com', '7684923732', 'bngnhnnm', 'Kerala', '678986', '$2y$10$o0CrYVIVbNUpWN1OlZ.sxuYkqe69KGSvqUtxgMJSEcnPU5w3aTfDi', '2025-03-19 09:11:46'),
(4, 'AgroFresh Admin', 'agrofresh.admin@gmail.com', '', '', '', '', '$2y$10$oi3xjpamh8P81FAoXpHhIO7Hkg9B0yJ58yX40swSx5PWZ4IPhC6E6', '2025-03-26 03:58:34'),
(10, 'REHAN', 'sxwsx@gmail.com', '74185555555', 'ss wsxwxw', 'xwxsw', '54120', '', '2025-03-25 18:21:37');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `address` text NOT NULL,
  `state` varchar(100) NOT NULL,
  `pincode` varchar(10) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` varchar(10) NOT NULL DEFAULT 'user',
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `city` varchar(100) DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT 'default-avatar.png',
  `admin_email` varchar(255) DEFAULT NULL,
  `admin_password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `mobile`, `address`, `state`, `pincode`, `password`, `created_at`, `role`, `is_admin`, `city`, `photo_url`, `admin_email`, `admin_password`) VALUES
(1, 'REHAN', 'rehansnair@gmail.com', '7907243685', 'cdhtyjukyukuyk', 'Jharkhand', '567869', '$2y$10$Udz6eSJrbUhHDKCf3wILh.neKnbcXaV.4i1mIybbY9EcDy5EaTl6S', '2025-03-19 08:18:52', 'user', 0, 'pala', 'uploads/profile_photos/profile_1_1742406644.png', NULL, NULL),
(2, 'barroz', 'barrozghost@gmail.com', '9999945123', 'palai', 'Goa', '555555', '$2y$10$oi3xjpamh8P81FAoXpHhIO7Hkg9B0yJ58yX40swSx5PWZ4IPhC6E6', '2025-03-19 08:40:36', 'user', 0, 'pala', 'uploads/profile_photos/profile_2_1742460441.jpg', NULL, NULL),
(3, 'divin babu', 'divin@gmail.com', '7684923732', 'bngnhnnm', 'Kerala', '678986', '$2y$10$o0CrYVIVbNUpWN1OlZ.sxuYkqe69KGSvqUtxgMJSEcnPU5w3aTfDi', '2025-03-19 09:10:50', 'user', 0, NULL, 'default-avatar.png', NULL, NULL),
(4, 'AgroFresh Admin', 'agrofresh.admin@gmail.com', '', '', '', '', '$2y$10$oi3xjpamh8P81FAoXpHhIO7Hkg9B0yJ58yX40swSx5PWZ4IPhC6E6', '2025-03-19 18:24:32', 'admin', 1, NULL, 'default-avatar.png', 'agrofresh.admin@gmail.com', '$2y$10$YOZ9qQ9P6RzJ.tWY5hP8vOXGK2AgLG/1PqP1Y2Q5Y6Q6Y6Q6Y6Q6Y'),
(6, 'alwin', 'alwin@gmail.com', '7863428393', 'dvgyhjujmjilol', 'Kerala', '686574', '$2y$10$A5pOWnRVwTaKKRPcBrQH3uoqetHYcssukfbXVi/Pw8z..QVQhHzdC', '2025-03-24 03:52:32', 'user', 0, NULL, 'default-avatar.png', NULL, NULL),
(7, 'rohini', 'rohini@gmail.com', '7889846489', 'fbhni,ol7iokuijtuhty', 'Kerala', '958476', '$2y$10$ZvOCHpSlyQKJxhT6PkK4PuQWMfjdMcojzMt/KYnFy4Elkp0E0VhHi', '2025-03-24 03:53:48', 'user', 0, NULL, 'default-avatar.png', NULL, NULL),
(8, 'pakku', 'pakku@gmail.com', '8676588464', 'lkjhgvcxcvghjk', 'Nagaland', '684648', '$2y$10$2Y3tiE/vnIeSYFz3iIJKSedFzc8x6zw/cNpXGVPyp7kxA8I3d6beW', '2025-03-24 03:58:46', 'delivery', 0, NULL, 'default-avatar.png', NULL, NULL),
(10, 'REHAN', 'sxwsx@gmail.com', '74185555555', 'ss wsxwxw', 'xwxsw', '54120', '', '2025-03-25 18:16:55', 'user', 0, 'wxwxw', 'default-avatar.png', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `seller`
--
ALTER TABLE `seller`
  ADD PRIMARY KEY (`seller_id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `sellerdetails`
--
ALTER TABLE `sellerdetails`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `admin_email` (`admin_email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=162;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `seller`
--
ALTER TABLE `seller`
  MODIFY `seller_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellerdetails` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `seller`
--
ALTER TABLE `seller`
  ADD CONSTRAINT `seller_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sellerdetails`
--
ALTER TABLE `sellerdetails`
  ADD CONSTRAINT `sellerdetails_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
