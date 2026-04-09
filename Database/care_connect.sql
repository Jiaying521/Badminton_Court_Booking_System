-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 08, 2026 at 05:49 PM
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
-- Database: `care_connect`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'Admin',
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `role`, `reset_token`, `token_expiry`) VALUES
(0, 'heybi', 'chinzx1814@gmail.com', '$2y$10$x5NsQGVwkkp5f4oivtMd..D9tsrJLMICxeSDnSe0peEwVN77QeFGu', 'Superadmin', '42692f8398994ed579c6f07ae531e8f954dc210f30d9cd99e037f8ab856ec721', '2026-04-07 15:41:20'),
(1, 'admin', '-', '$2y$10$lKJR3lV5IxZSt0Wasb3dUuCFGooQPJd4EBDFSc0xb30V.N7quWA/e', 'Admin', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `doctor_name` varchar(100) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `user_id`, `doctor_name`, `appointment_date`, `appointment_time`, `status`, `created_at`) VALUES
(1, 1, 'Dr. Adam', '2026-01-05', '09:00:00', 'Completed', '2026-04-08 15:48:53'),
(2, 2, 'Dr. Sarah', '2026-01-05', '10:00:00', 'Completed', '2026-04-08 15:48:53'),
(3, 3, 'Dr. Adam', '2026-01-05', '11:00:00', 'Completed', '2026-04-08 15:48:53'),
(4, 4, 'Dr. Wong', '2026-01-06', '09:00:00', 'Cancelled', '2026-04-08 15:48:53'),
(5, 5, 'Dr. Adam', '2026-01-08', '10:00:00', 'Completed', '2026-04-08 15:48:53'),
(6, 6, 'Dr. Sarah', '2026-01-08', '11:00:00', 'Rescheduled', '2026-04-08 15:48:53'),
(7, 7, 'Dr. Wong', '2026-01-10', '14:00:00', 'Completed', '2026-04-08 15:48:53'),
(8, 8, 'Dr. Adam', '2026-01-12', '09:00:00', 'Completed', '2026-04-08 15:48:53'),
(9, 9, 'Dr. Sarah', '2026-01-12', '10:00:00', 'Completed', '2026-04-08 15:48:53'),
(10, 10, 'Dr. Wong', '2026-01-15', '11:00:00', 'Completed', '2026-04-08 15:48:53'),
(11, 11, 'Dr. Adam', '2026-01-15', '14:00:00', 'Cancelled', '2026-04-08 15:48:53'),
(12, 12, 'Dr. Sarah', '2026-01-18', '09:00:00', 'Completed', '2026-04-08 15:48:53'),
(13, 13, 'Dr. Wong', '2026-01-20', '10:00:00', 'Completed', '2026-04-08 15:48:53'),
(14, 14, 'Dr. Adam', '2026-01-20', '11:00:00', 'Completed', '2026-04-08 15:48:53'),
(15, 15, 'Dr. Sarah', '2026-01-22', '09:00:00', 'Rescheduled', '2026-04-08 15:48:53'),
(16, 16, 'Dr. Wong', '2026-01-25', '14:00:00', 'Completed', '2026-04-08 15:48:53'),
(17, 17, 'Dr. Adam', '2026-01-25', '15:00:00', 'Completed', '2026-04-08 15:48:53'),
(18, 18, 'Dr. Sarah', '2026-01-28', '09:00:00', 'Completed', '2026-04-08 15:48:53'),
(19, 19, 'Dr. Wong', '2026-01-30', '10:00:00', 'Completed', '2026-04-08 15:48:53'),
(20, 20, 'Dr. Adam', '2026-01-30', '11:00:00', 'Completed', '2026-04-08 15:48:53'),
(21, 1, 'Dr. Sarah', '2026-02-02', '09:00:00', 'Completed', '2026-04-08 15:48:53'),
(22, 2, 'Dr. Adam', '2026-02-02', '10:00:00', 'Completed', '2026-04-08 15:48:53'),
(23, 3, 'Dr. Sarah', '2026-02-05', '11:00:00', 'Cancelled', '2026-04-08 15:48:53'),
(24, 4, 'Dr. Wong', '2026-02-05', '14:00:00', 'Completed', '2026-04-08 15:48:53'),
(25, 5, 'Dr. Adam', '2026-02-10', '09:00:00', 'Completed', '2026-04-08 15:48:53'),
(26, 6, 'Dr. Sarah', '2026-02-10', '10:00:00', 'Completed', '2026-04-08 15:48:53'),
(27, 7, 'Dr. Wong', '2026-02-12', '11:00:00', 'Rescheduled', '2026-04-08 15:48:53'),
(28, 8, 'Dr. Adam', '2026-02-15', '09:00:00', 'Completed', '2026-04-08 15:48:53'),
(29, 9, 'Dr. Sarah', '2026-02-15', '10:00:00', 'Completed', '2026-04-08 15:48:53'),
(30, 10, 'Dr. Wong', '2026-02-18', '11:00:00', 'Completed', '2026-04-08 15:48:53'),
(31, 11, 'Dr. Adam', '2026-02-20', '14:00:00', 'Completed', '2026-04-08 15:48:53'),
(32, 12, 'Dr. Sarah', '2026-02-20', '15:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(33, 13, 'Dr. Wong', '2026-02-25', '09:00:00', 'Completed', '2026-04-08 15:48:53'),
(34, 14, 'Dr. Adam', '2026-02-25', '10:00:00', 'Completed', '2026-04-08 15:48:53'),
(35, 15, 'Dr. Sarah', '2026-02-28', '11:00:00', 'Completed', '2026-04-08 15:48:53'),
(36, 1, 'Dr. Wong', '2026-03-01', '09:00:00', 'Completed', '2026-04-08 15:48:53'),
(37, 2, 'Dr. Sarah', '2026-03-01', '10:00:00', 'Completed', '2026-04-08 15:48:53'),
(38, 3, 'Dr. Adam', '2026-03-05', '11:00:00', 'Cancelled', '2026-04-08 15:48:53'),
(39, 4, 'Dr. Sarah', '2026-03-05', '14:00:00', 'Completed', '2026-04-08 15:48:53'),
(40, 5, 'Dr. Wong', '2026-03-08', '09:00:00', 'Completed', '2026-04-08 15:48:53'),
(41, 6, 'Dr. Adam', '2026-03-10', '10:00:00', 'Rescheduled', '2026-04-08 15:48:53'),
(42, 7, 'Dr. Sarah', '2026-03-10', '11:00:00', 'Completed', '2026-04-08 15:48:53'),
(43, 8, 'Dr. Wong', '2026-03-15', '14:00:00', 'Completed', '2026-04-08 15:48:53'),
(44, 9, 'Dr. Adam', '2026-03-15', '15:00:00', 'Completed', '2026-04-08 15:48:53'),
(45, 10, 'Dr. Sarah', '2026-03-20', '09:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(46, 11, 'Dr. Wong', '2026-03-20', '10:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(47, 12, 'Dr. Adam', '2026-03-25', '11:00:00', 'Completed', '2026-04-08 15:48:53'),
(48, 13, 'Dr. Sarah', '2026-03-28', '14:00:00', 'Completed', '2026-04-08 15:48:53'),
(49, 14, 'Dr. Wong', '2026-03-30', '15:00:00', 'Completed', '2026-04-08 15:48:53'),
(50, 1, 'Dr. Adam', '2026-04-01', '09:00:00', 'Completed', '2026-04-08 15:48:53'),
(51, 2, 'Dr. Sarah', '2026-04-02', '10:00:00', 'Completed', '2026-04-08 15:48:53'),
(52, 3, 'Dr. Wong', '2026-04-03', '11:00:00', 'Cancelled', '2026-04-08 15:48:53'),
(53, 4, 'Dr. Adam', '2026-04-05', '14:00:00', 'Completed', '2026-04-08 15:48:53'),
(54, 5, 'Dr. Sarah', '2026-04-07', '09:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(55, 6, 'Dr. Wong', '2026-04-08', '10:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(56, 7, 'Dr. Adam', '2026-04-08', '11:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(57, 8, 'Dr. Sarah', '2026-04-09', '14:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(58, 9, 'Dr. Wong', '2026-04-10', '15:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(59, 10, 'Dr. Adam', '2026-04-12', '09:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(60, 11, 'Dr. Sarah', '2026-04-15', '10:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(61, 12, 'Dr. Wong', '2026-04-20', '11:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(62, 1, 'Dr. Adam', '2026-05-10', '09:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(63, 2, 'Dr. Sarah', '2026-05-15', '10:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(64, 3, 'Dr. Wong', '2026-05-20', '11:00:00', 'Rescheduled', '2026-04-08 15:48:53'),
(65, 1, 'Dr. Adam', '2026-06-10', '09:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(66, 2, 'Dr. Sarah', '2026-06-15', '10:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(67, 3, 'Dr. Wong', '2026-06-20', '11:00:00', 'Cancelled', '2026-04-08 15:48:53'),
(68, 1, 'Dr. Adam', '2026-07-10', '09:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(69, 2, 'Dr. Sarah', '2026-07-15', '10:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(70, 3, 'Dr. Wong', '2026-07-20', '11:00:00', 'Rescheduled', '2026-04-08 15:48:53'),
(71, 1, 'Dr. Adam', '2026-08-10', '09:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(72, 2, 'Dr. Sarah', '2026-08-15', '10:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(73, 3, 'Dr. Wong', '2026-08-20', '11:00:00', 'Completed', '2026-04-08 15:48:53'),
(74, 1, 'Dr. Adam', '2026-09-10', '09:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(75, 2, 'Dr. Sarah', '2026-09-15', '10:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(76, 3, 'Dr. Wong', '2026-09-20', '11:00:00', 'Completed', '2026-04-08 15:48:53'),
(77, 1, 'Dr. Adam', '2026-10-10', '09:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(78, 2, 'Dr. Sarah', '2026-10-15', '10:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(79, 3, 'Dr. Wong', '2026-10-20', '11:00:00', 'Cancelled', '2026-04-08 15:48:53'),
(80, 1, 'Dr. Adam', '2026-11-10', '09:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(81, 2, 'Dr. Sarah', '2026-11-15', '10:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(82, 3, 'Dr. Wong', '2026-11-20', '11:00:00', 'Rescheduled', '2026-04-08 15:48:53'),
(83, 1, 'Dr. Adam', '2026-12-10', '09:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(84, 2, 'Dr. Sarah', '2026-12-15', '10:00:00', 'Ongoing', '2026-04-08 15:48:53'),
(85, 3, 'Dr. Wong', '2026-12-20', '11:00:00', 'Completed', '2026-04-08 15:48:53');

-- --------------------------------------------------------

--
-- Table structure for table `otp_codes`
--

CREATE TABLE `otp_codes` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `code` varchar(6) NOT NULL,
  `type` enum('register','login') NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `otp_codes`
--

INSERT INTO `otp_codes` (`id`, `email`, `code`, `type`, `expires_at`, `created_at`) VALUES
(1, 'xyyjiaying@gmail.com', '123456', 'register', '2026-04-02 21:53:11', '2026-04-02 13:43:11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nric` varchar(50) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`,`type`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
