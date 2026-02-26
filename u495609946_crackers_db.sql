-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 26, 2026 at 05:38 PM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 7.4.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `crackers_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(5, 'admin', 'admin123'),
(6, 'admin', 'admin123');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_name`, `email`, `phone`, `pincode`, `shipping_address`, `total_amount`, `order_date`) VALUES
(1, 'learn with ai', 'asdf@123', '09876543233', '4354365', 'cbbfdgfdg', '126.00', '2025-12-23 08:44:58'),
(2, 'wef', 'asdf@123', '09876543233', '4354365', 'cbbfdgfdg', '189.00', '2025-12-23 08:49:06'),
(3, 'venkat', 'asdf@123', '09876543233', '4354365', '34r4ref', '880.00', '2025-12-23 08:51:40'),
(4, 'venkat', 'asdf@123', '09876543233', '4354365', 'egerefer', '63.00', '2025-12-24 05:26:34'),
(5, 'venkat', 'asdf@123', '09876543233', '4354365', 'madurai', '64.00', '2025-12-24 06:17:30'),
(6, 'venkat', 'abcd@gmail.com', '1231231231', '112233', 'dsfdsfs', '13200.00', '2025-12-24 08:48:02'),
(7, 'asas', 'asd@gmail.com', '134567890', '112233', 'madurai', '300.00', '2025-12-24 10:08:56'),
(8, 'venkat', 'asdf@gmail.com', '1234567890', '112233', 'madurai', '1200.00', '2025-12-24 10:10:58'),
(9, 'venkat', 'asdf@gmail.com', '1234123412', '222222', 'madurai', '1200.00', '2025-12-24 10:20:33'),
(10, 'Parameswaran s', 'paramesh1991@gmail.com', '09486973691', '627114', 'South Street', '87.00', '2025-12-30 16:28:25'),
(11, 'parameswaran.s', 'paramesh1991@gmail.com', '09486973691', '627114', 'South Street', '178.00', '2026-01-11 16:26:42'),
(12, 'Parameswaran s', 'paramesh1991@gmail.com', '09486973691', '627114', 'South Street', '389.00', '2026-01-22 14:29:54'),
(13, 'Parameswaran s', 'paramesh1991@gmail.com', '09486973691', '627114', 'South Street', '229.00', '2026-02-03 10:41:37');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 0, 2, '63.00'),
(2, 2, 0, 1, '63.00'),
(3, 2, 0, 2, '63.00'),
(4, 3, 0, 4, '220.00'),
(5, 4, 0, 1, '63.00'),
(6, 5, 0, 2, '32.00'),
(7, 6, NULL, 40, '300.00'),
(8, 6, NULL, 1, '300.00'),
(9, 6, NULL, 1, '300.00'),
(10, 6, NULL, 1, '300.00'),
(11, 6, NULL, 1, '300.00'),
(12, NULL, NULL, NULL, NULL),
(13, 9, 8, 1, '300.00'),
(14, 9, 8, 3, '300.00'),
(15, 10, 11, 1, '29.00'),
(16, 10, 11, 2, '29.00'),
(17, 11, 11, 2, '29.00'),
(18, 11, 12, 1, '120.00'),
(19, 12, 11, 1, '29.00'),
(20, 12, 12, 3, '120.00'),
(21, 13, 11, 1, '29.00'),
(22, 13, 12, 1, '120.00'),
(23, 13, 13, 2, '40.00');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name_en` varchar(100) DEFAULT NULL,
  `name_ta` varchar(100) DEFAULT NULL,
  `pack` varchar(50) DEFAULT NULL,
  `old_price` decimal(10,2) DEFAULT NULL,
  `offer` varchar(50) DEFAULT NULL,
  `new_price` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name_en`, `name_ta`, `pack`, `old_price`, `offer`, `new_price`, `image`, `created_at`) VALUES
(11, 'Gold Lakshmi', 'கோல்ட் லட்சுமி', '1 Pkt', '145.00', '80', '29.00', 'uploads/1766572637_magic_pot.jpg', '2025-12-24 10:37:17'),
(12, '4 Inches Deluxe Lakshmi', '4 கே ஜி எஃப்', '1 Pkt', '200.00', '40', '120.00', 'uploads/1766572686_sangu_sakkaram.jpg', '2025-12-24 10:38:06'),
(13, 'Charkhi Crackers', 'சக்கரம் பட்டாசுகள்', '1', '200.00', '80', '40.00', 'uploads/1767112375_charkhi-crackers-071.jpg', '2025-12-30 16:32:55'),
(15, 'GROUND CHAKKARS', 'சங்கு சக்கரம்', '1', '150.00', '45', '82.50', 'uploads/1769952600_SKU-0096_0-1727691147258.webp', '2026-02-01 13:30:00'),
(16, 'sony Crackers', 'சோனி கிராக்கர்ஸ்', '1', '300.00', '20', '240.00', 'uploads/1770115483_9779ca3d-5217-4fa6-b522-4f4aa7c966e5.png', '2026-02-03 10:44:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
