-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 30, 2026 at 06:54 PM
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
-- Database: `badminton_hub`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Superadmin','Admin','Coach') NOT NULL DEFAULT 'Admin',
  `status` enum('Active','Inactive','Suspended') NOT NULL DEFAULT 'Inactive',
  `specialisation` varchar(100) DEFAULT NULL,
  `is_coach` tinyint(1) NOT NULL DEFAULT 0,
  `coach_price_per_hour` decimal(10,2) NOT NULL DEFAULT 20.00,
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `role`, `status`, `specialisation`, `is_coach`, `coach_price_per_hour`, `reset_token`, `token_expiry`, `created_at`) VALUES
(1, 'superadmin', 'chinzx1814@gmail.com', '$2y$10$x5NsQGVwkkp5f4oivtMd..D9tsrJLMICxeSDnSe0peEwVN77QeFGu', 'Superadmin', 'Active', NULL, 0, 0.00, '6f7215969454a243bffe4f847c58375ca1a0e4dc2157e02a1757c75020bfa0c7', '2026-04-30 18:48:36', '2026-04-30 16:17:09'),
(2, 'Coach Lim', 'coach.lim@smasharena.com', '$2y$10$0lBfa23QtHMftiHohzzAjeQQKBt5qNffLSkbubScELAAKyDJO18PK', 'Coach', 'Active', 'Professional Training', 1, 25.00, NULL, NULL, '2026-04-30 16:17:09'),
(3, 'Coach Wong', 'coach.wong@smasharena.com', '$2y$10$0lBfa23QtHMftiHohzzAjeQQKBt5qNffLSkbubScELAAKyDJO18PK', 'Coach', 'Active', 'Technique & Footwork', 1, 20.00, NULL, NULL, '2026-04-30 16:17:09'),
(4, 'Coach Tan', 'coach.tan@smasharena.com', '$2y$10$0lBfa23QtHMftiHohzzAjeQQKBt5qNffLSkbubScELAAKyDJO18PK', 'Coach', 'Active', 'Strategy & Match Play', 1, 30.00, NULL, NULL, '2026-04-30 16:17:09'),
(5, 'admin', '', '$2y$10$0lBfa23QtHMftiHohzzAjeQQKBt5qNffLSkbubScELAAKyDJO18PK', 'Admin', 'Active', NULL, 0, 0.00, NULL, NULL, '2026-04-30 16:37:03');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `court_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `total_hours` int(11) NOT NULL DEFAULT 1 COMMENT 'booking duration in hours',
  `coach_id` int(11) DEFAULT NULL COMMENT 'coach ID (0 or NULL means no coach)',
  `coach_hours` int(11) DEFAULT 0 COMMENT 'coach hours',
  `coach_price_total` decimal(10,2) DEFAULT 0.00 COMMENT 'total coach fee',
  `session_type` enum('Casual Play','Training','Tournament','Friendly Game') DEFAULT 'Casual Play',
  `total_price` decimal(10,2) NOT NULL COMMENT 'total fee (court + coach)',
  `status` enum('Pending','Confirmed','Cancelled','Completed') DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `court_id`, `booking_date`, `start_time`, `end_time`, `total_hours`, `coach_id`, `coach_hours`, `coach_price_total`, `session_type`, `total_price`, `status`, `notes`, `created_at`) VALUES
(6, 2, 1, '2026-05-02', '09:00:00', '11:00:00', 2, NULL, 0, 0.00, 'Training', 40.00, 'Confirmed', 'Alice morning training', '2026-04-30 16:34:47'),
(7, 3, 2, '2026-05-03', '14:00:00', '16:00:00', 2, NULL, 0, 0.00, '', 50.00, 'Pending', 'Michael weekend match', '2026-04-30 16:34:47'),
(8, 4, 1, '2026-05-04', '08:00:00', '10:00:00', 2, NULL, 0, 0.00, 'Training', 40.00, 'Confirmed', 'Siti fitness practice', '2026-04-30 16:34:47'),
(9, 5, 3, '2026-05-04', '18:00:00', '20:00:00', 2, NULL, 0, 0.00, '', 50.00, 'Confirmed', 'Ahmad evening session', '2026-04-30 16:34:47'),
(10, 6, 2, '2026-05-05', '10:00:00', '12:00:00', 2, NULL, 0, 0.00, 'Training', 40.00, 'Cancelled', 'Rachel cancelled booking', '2026-04-30 16:34:47'),
(11, 7, 1, '2026-05-05', '13:00:00', '15:00:00', 2, NULL, 0, 0.00, 'Training', 40.00, 'Confirmed', 'Daniel weekday practice', '2026-04-30 16:34:47'),
(12, 8, 3, '2026-05-06', '16:00:00', '18:00:00', 2, NULL, 0, 0.00, '', 50.00, 'Pending', 'Priya competitive game', '2026-04-30 16:34:47'),
(13, 9, 2, '2026-05-06', '19:00:00', '21:00:00', 2, NULL, 0, 0.00, 'Training', 40.00, 'Confirmed', 'Jason night practice', '2026-04-30 16:34:47'),
(14, 10, 1, '2026-05-07', '07:00:00', '09:00:00', 2, NULL, 0, 0.00, 'Training', 40.00, 'Confirmed', 'Nurul early session', '2026-04-30 16:34:47');

-- --------------------------------------------------------

--
-- Table structure for table `closed_days`
--

CREATE TABLE `closed_days` (
  `id` int(11) NOT NULL,
  `closed_date` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `closed_days`
--

INSERT INTO `closed_days` (`id`, `closed_date`, `reason`) VALUES
(1, '2025-01-01', 'New Year'),
(2, '2025-05-01', 'Labour Day'),
(3, '2025-08-31', 'National Day'),
(4, '2025-12-25', 'Christmas Day');

-- --------------------------------------------------------

--
-- Table structure for table `coaches`
--

CREATE TABLE `coaches` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `specialty` varchar(100) DEFAULT NULL,
  `price_per_hour` decimal(10,2) NOT NULL DEFAULT 20.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coaches`
--

INSERT INTO `coaches` (`id`, `admin_id`, `name`, `specialty`, `price_per_hour`, `is_active`) VALUES
(1, 2, 'Coach Lim', '🏸 Professional Training - Overall skill improvement, power hitting, consistency', 25.00, 1),
(2, 3, 'Coach Wong', '🎯 Technique & Footwork - Basic strokes, footwork, body positioning', 20.00, 1),
(3, 4, 'Coach Tan', '🏆 Strategy & Match Play - Game tactics, mental training, competition prep', 30.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `courts`
--

CREATE TABLE `courts` (
  `id` int(11) NOT NULL,
  `court_name` varchar(100) NOT NULL,
  `court_type` enum('Standard','Training') NOT NULL DEFAULT 'Standard',
  `location` varchar(255) DEFAULT NULL,
  `facilities` varchar(255) DEFAULT NULL,
  `price_off_peak` decimal(10,2) NOT NULL DEFAULT 10.00 COMMENT '非高峰价格 8am-2pm',
  `price_peak` decimal(10,2) NOT NULL DEFAULT 15.00 COMMENT '高峰价格 3pm-1am',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courts`
--

INSERT INTO `courts` (`id`, `court_name`, `court_type`, `location`, `facilities`, `price_off_peak`, `price_peak`, `is_active`, `created_at`) VALUES
(1, 'Court A', 'Standard', 'Main Hall 1', 'Shower, Locker', 10.00, 15.00, 1, '2026-04-30 16:17:09'),
(2, 'Court B', 'Standard', 'Main Hall 1', 'Shower, Locker', 10.00, 15.00, 1, '2026-04-30 16:17:09'),
(3, 'Court C', 'Standard', 'Main Hall 2', 'Shower, Locker', 10.00, 15.00, 1, '2026-04-30 16:17:09'),
(4, 'Court D', 'Standard', 'Main Hall 2', 'Shower, Locker', 10.00, 15.00, 1, '2026-04-30 16:17:09'),
(5, 'Court E', 'Standard', 'Main Hall 3', 'Shower, Locker', 10.00, 15.00, 1, '2026-04-30 16:17:09'),
(6, 'Court F', 'Standard', 'Main Hall 3', 'Shower, Locker', 10.00, 15.00, 1, '2026-04-30 16:17:09'),
(7, 'Court G', 'Standard', 'Main Hall 4', 'Shower, Locker', 10.00, 15.00, 1, '2026-04-30 16:17:09'),
(8, 'Court H', 'Training', 'Training Hall', 'Coaching area, Video analysis, Shower', 10.00, 15.00, 1, '2026-04-30 16:17:09'),
(9, 'Court I', 'Training', 'Training Hall', 'Coaching area, Video analysis, Shower', 10.00, 15.00, 1, '2026-04-30 16:17:09'),
(10, 'Court J', 'Training', 'Training Hall', 'Coaching area, Video analysis, Shower', 10.00, 15.00, 1, '2026-04-30 16:17:09');

-- --------------------------------------------------------

--
-- Table structure for table `court_availability`
--

CREATE TABLE `court_availability` (
  `id` int(11) NOT NULL,
  `court_id` int(11) NOT NULL,
  `day_of_week` tinyint(1) NOT NULL COMMENT '1=Mon,2=Tue,...,7=Sun',
  `start_time` time NOT NULL DEFAULT '08:00:00',
  `end_time` time NOT NULL DEFAULT '01:00:00',
  `slot_duration` int(11) DEFAULT 60
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `court_availability`
--

INSERT INTO `court_availability` (`id`, `court_id`, `day_of_week`, `start_time`, `end_time`, `slot_duration`) VALUES
(1, 1, 1, '08:00:00', '01:00:00', 60),
(2, 2, 1, '08:00:00', '01:00:00', 60),
(3, 3, 1, '08:00:00', '01:00:00', 60),
(4, 4, 1, '08:00:00', '01:00:00', 60),
(5, 5, 1, '08:00:00', '01:00:00', 60),
(6, 6, 1, '08:00:00', '01:00:00', 60),
(7, 7, 1, '08:00:00', '01:00:00', 60),
(8, 8, 1, '08:00:00', '01:00:00', 60),
(9, 9, 1, '08:00:00', '01:00:00', 60),
(10, 10, 1, '08:00:00', '01:00:00', 60),
(11, 1, 2, '08:00:00', '01:00:00', 60),
(12, 2, 2, '08:00:00', '01:00:00', 60),
(13, 3, 2, '08:00:00', '01:00:00', 60),
(14, 4, 2, '08:00:00', '01:00:00', 60),
(15, 5, 2, '08:00:00', '01:00:00', 60),
(16, 6, 2, '08:00:00', '01:00:00', 60),
(17, 7, 2, '08:00:00', '01:00:00', 60),
(18, 8, 2, '08:00:00', '01:00:00', 60),
(19, 9, 2, '08:00:00', '01:00:00', 60),
(20, 10, 2, '08:00:00', '01:00:00', 60),
(21, 1, 3, '08:00:00', '01:00:00', 60),
(22, 2, 3, '08:00:00', '01:00:00', 60),
(23, 3, 3, '08:00:00', '01:00:00', 60),
(24, 4, 3, '08:00:00', '01:00:00', 60),
(25, 5, 3, '08:00:00', '01:00:00', 60),
(26, 6, 3, '08:00:00', '01:00:00', 60),
(27, 7, 3, '08:00:00', '01:00:00', 60),
(28, 8, 3, '08:00:00', '01:00:00', 60),
(29, 9, 3, '08:00:00', '01:00:00', 60),
(30, 10, 3, '08:00:00', '01:00:00', 60),
(31, 1, 4, '08:00:00', '01:00:00', 60),
(32, 2, 4, '08:00:00', '01:00:00', 60),
(33, 3, 4, '08:00:00', '01:00:00', 60),
(34, 4, 4, '08:00:00', '01:00:00', 60),
(35, 5, 4, '08:00:00', '01:00:00', 60),
(36, 6, 4, '08:00:00', '01:00:00', 60),
(37, 7, 4, '08:00:00', '01:00:00', 60),
(38, 8, 4, '08:00:00', '01:00:00', 60),
(39, 9, 4, '08:00:00', '01:00:00', 60),
(40, 10, 4, '08:00:00', '01:00:00', 60),
(41, 1, 5, '08:00:00', '01:00:00', 60),
(42, 2, 5, '08:00:00', '01:00:00', 60),
(43, 3, 5, '08:00:00', '01:00:00', 60),
(44, 4, 5, '08:00:00', '01:00:00', 60),
(45, 5, 5, '08:00:00', '01:00:00', 60),
(46, 6, 5, '08:00:00', '01:00:00', 60),
(47, 7, 5, '08:00:00', '01:00:00', 60),
(48, 8, 5, '08:00:00', '01:00:00', 60),
(49, 9, 5, '08:00:00', '01:00:00', 60),
(50, 10, 5, '08:00:00', '01:00:00', 60),
(51, 1, 6, '08:00:00', '01:00:00', 60),
(52, 2, 6, '08:00:00', '01:00:00', 60),
(53, 3, 6, '08:00:00', '01:00:00', 60),
(54, 4, 6, '08:00:00', '01:00:00', 60),
(55, 5, 6, '08:00:00', '01:00:00', 60),
(56, 6, 6, '08:00:00', '01:00:00', 60),
(57, 7, 6, '08:00:00', '01:00:00', 60),
(58, 8, 6, '08:00:00', '01:00:00', 60),
(59, 9, 6, '08:00:00', '01:00:00', 60),
(60, 10, 6, '08:00:00', '01:00:00', 60),
(61, 1, 7, '08:00:00', '01:00:00', 60),
(62, 2, 7, '08:00:00', '01:00:00', 60),
(63, 3, 7, '08:00:00', '01:00:00', 60),
(64, 4, 7, '08:00:00', '01:00:00', 60),
(65, 5, 7, '08:00:00', '01:00:00', 60),
(66, 6, 7, '08:00:00', '01:00:00', 60),
(67, 7, 7, '08:00:00', '01:00:00', 60),
(68, 8, 7, '08:00:00', '01:00:00', 60),
(69, 9, 7, '08:00:00', '01:00:00', 60),
(70, 10, 7, '08:00:00', '01:00:00', 60);

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

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `discount_applied` decimal(10,2) DEFAULT 0.00,
  `final_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` enum('pending','success','failed') DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` varchar(100) DEFAULT NULL,
  `status` enum('Pending','In Progress','Done') NOT NULL DEFAULT 'Pending',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `created_at`) VALUES
(1, 'John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+60123456789', '2026-04-30 16:17:09'),
(2, 'Alice Tan', 'alice@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2...', '+60111222333', '2026-04-30 16:34:47'),
(3, 'Michael Lee', 'michael@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2...', '+60199888777', '2026-04-30 16:34:47'),
(4, 'Siti Aminah', 'siti@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2...', '+60122334455', '2026-04-30 16:34:47'),
(5, 'Ahmad Firdaus', 'ahmad@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2...', '+60155667788', '2026-04-30 16:34:47'),
(6, 'Rachel Lim', 'rachel@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2...', '+60166778899', '2026-04-30 16:34:47'),
(7, 'Daniel Wong', 'daniel@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2...', '+60177889900', '2026-04-30 16:34:47'),
(8, 'Priya Kumar', 'priya@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2...', '+60188990011', '2026-04-30 16:34:47'),
(9, 'Jason Teh', 'jason@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2...', '+60199001122', '2026-04-30 16:34:47'),
(10, 'Nurul Huda', 'nurul@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2...', '+60110101010', '2026-04-30 16:34:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bookings_user` (`user_id`),
  ADD KEY `fk_bookings_court` (`court_id`);

--
-- Indexes for table `closed_days`
--
ALTER TABLE `closed_days`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_closed_date` (`closed_date`);

--
-- Indexes for table `coaches`
--
ALTER TABLE `coaches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_id` (`admin_id`);

--
-- Indexes for table `courts`
--
ALTER TABLE `courts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `court_availability`
--
ALTER TABLE `court_availability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_availability_court` (`court_id`);

--
-- Indexes for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`,`type`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `fk_payments_booking` (`booking_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `closed_days`
--
ALTER TABLE `closed_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `coaches`
--
ALTER TABLE `coaches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `courts`
--
ALTER TABLE `courts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `court_availability`
--
ALTER TABLE `court_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_bookings_court` FOREIGN KEY (`court_id`) REFERENCES `courts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coaches`
--
ALTER TABLE `coaches`
  ADD CONSTRAINT `fk_coaches_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `court_availability`
--
ALTER TABLE `court_availability`
  ADD CONSTRAINT `fk_availability_court` FOREIGN KEY (`court_id`) REFERENCES `courts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
