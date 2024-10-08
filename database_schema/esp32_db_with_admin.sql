-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 08, 2024 at 01:30 PM
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
-- Database: `esp32_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `last_login` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `password_hash`, `email`, `last_login`, `created_at`) VALUES
(2, 'admin', 'dc3565645d8002becb5fd7977aeef3e1', 'admin@gmail.com', '2024-09-20 22:57:37', '2024-09-20 22:57:37');

-- --------------------------------------------------------

--
-- Table structure for table `sensor_readings`
--

CREATE TABLE `sensor_readings` (
  `id` int(11) NOT NULL,
  `temperature` float DEFAULT NULL,
  `humidity` float DEFAULT NULL,
  `heat_index` float DEFAULT NULL,
  `alert` varchar(50) DEFAULT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `alert_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sensor_readings`
--

INSERT INTO `sensor_readings` (`id`, `temperature`, `humidity`, `heat_index`, `alert`, `latitude`, `longitude`, `alert_time`) VALUES
(2, 32.6, 55, 36.92, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:43:17'),
(3, 32.5, 55, 36.7, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:43:30'),
(4, 32.5, 55, 36.7, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:43:43'),
(5, 32.5, 55, 36.7, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:43:57'),
(6, 32.4, 55, 36.49, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:44:09'),
(7, 32.4, 55, 36.49, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:44:22'),
(8, 32.4, 55, 36.49, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:44:36'),
(9, 32.4, 56, 36.78, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:44:48'),
(10, 32.5, 57, 37.31, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:45:02'),
(11, 32.5, 57, 37.31, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:45:12'),
(12, 32.6, 57, 37.54, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:45:24'),
(13, 32.9, 57, 38.24, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:45:36'),
(14, 33.4, 56, 39.09, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:45:48'),
(15, 33.9, 53, 39.21, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:45:59'),
(16, 34.3, 51, 39.42, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:46:12'),
(17, 34.5, 49, 39.15, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:46:24'),
(18, 34.5, 48, 38.8, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:46:36'),
(19, 34.3, 48, 38.37, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:46:48'),
(20, 34, 49, 38.06, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:47:00'),
(21, 33.8, 49, 37.63, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:47:12'),
(22, 33.5, 50, 37.32, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:47:24'),
(23, 33.4, 51, 37.42, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:47:36'),
(24, 33.3, 51, 37.21, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:47:47'),
(25, 33.2, 52, 37.3, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:47:57'),
(26, 33.1, 52, 37.09, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:48:09'),
(27, 33, 53, 37.18, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:48:21'),
(28, 32.8, 53, 36.75, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:48:34'),
(29, 32.7, 53, 36.54, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:48:47'),
(30, 32.7, 54, 36.84, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:48:59'),
(31, 32.6, 54, 36.62, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:49:10'),
(32, 32.5, 55, 36.7, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:49:22'),
(33, 32.5, 55, 36.7, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:49:35'),
(34, 32.5, 55, 36.7, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:49:47'),
(35, 32.4, 55, 36.49, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:49:59'),
(36, 32.4, 55, 36.49, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:50:12'),
(37, 32.4, 55, 36.49, 'Extreme Caution', '7.947062', '123.587546', '2024-10-08 05:50:23'),
(38, 28.9, 62, 31.17, 'Caution', '7.947062', '123.587546', '2024-10-08 10:02:36'),
(39, 28.5, 63, 30.62, 'Caution', '7.947062', '123.587546', '2024-10-08 10:06:16'),
(40, 28.5, 63, 30.62, 'Caution', '7.947062', '123.587546', '2024-10-08 10:06:35'),
(41, 28.5, 63, 30.62, 'Caution', '7.947062', '123.587546', '2024-10-08 10:06:44'),
(42, 28.5, 63, 30.62, 'Caution', '7.947062', '123.587546', '2024-10-08 10:06:57'),
(43, 28.5, 63, 30.62, 'Caution', '7.947062', '123.587546', '2024-10-08 10:07:08'),
(44, 28.5, 64, 30.76, 'Caution', '7.947062', '123.587546', '2024-10-08 10:07:20'),
(45, 28.4, 64, 30.59, 'Caution', '7.947062', '123.587546', '2024-10-08 10:07:32'),
(46, 28.4, 64, 30.59, 'Caution', '7.947062', '123.587546', '2024-10-08 10:07:42'),
(47, 28.4, 64, 30.59, 'Caution', '7.947062', '123.587546', '2024-10-08 10:07:52'),
(48, 28.4, 64, 30.59, 'Caution', '7.947062', '123.587546', '2024-10-08 10:08:04'),
(49, 28.4, 64, 30.59, 'Caution', '7.947062', '123.587546', '2024-10-08 10:08:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `sensor_readings`
--
ALTER TABLE `sensor_readings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `sensor_readings`
--
ALTER TABLE `sensor_readings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
