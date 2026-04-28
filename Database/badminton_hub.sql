-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 28, 2026 at 05:31 PM
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
  `specialisation` varchar(100) DEFAULT NULL COMMENT 'Court type or coaching specialty',
  `is_coach` tinyint(1) NOT NULL DEFAULT 0,
  `first_login` tinyint(1) NOT NULL DEFAULT 0,
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `role`, `status`, `specialisation`, `is_coach`, `first_login`, `reset_token`, `token_expiry`, `created_at`) VALUES
(1, 'superadmin', 'chinzx1814@gmail.com', '$2y$10$x5NsQGVwkkp5f4oivtMd..D9tsrJLMICxeSDnSe0peEwVN77QeFGu', 'Superadmin', 'Active', NULL, 0, 1, '55f066589a5ee0238d7271c19ddb4a18a5bc9a0e6225055cd0b52ae75b4ddbe6', '2026-04-24 08:11:45', '2026-04-24 05:13:48'),
(4, 'admin', '', '$2y$10$lKJR3lV5IxZSt0Wasb3dUuCFGooQPJd4EBDFSc0xb30V.N7quWA/e', 'Admin', 'Active', NULL, 0, 1, NULL, NULL, '2026-04-28 15:16:02');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `coach_name` varchar(100) DEFAULT NULL COMMENT 'Assigned coach (if any)',
  `court_name` varchar(100) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('Pending','Confirmed','Completed','Cancelled','Rescheduled') DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `session_type` enum('Casual Play','Training','Tournament','Friendly Game') DEFAULT 'Casual Play',
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('Pending','Confirmed','Cancelled','Completed') DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(2, '2025-05-01', 'Labour Day');

-- --------------------------------------------------------

--
-- Table structure for table `courts`
--

CREATE TABLE `courts` (
  `id` int(11) NOT NULL,
  `court_name` varchar(100) NOT NULL,
  `court_type` varchar(50) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `facilities` varchar(255) DEFAULT NULL,
  `price_per_hour` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courts`
--

INSERT INTO `courts` (`id`, `court_name`, `court_type`, `location`, `facilities`, `price_per_hour`, `is_active`, `created_at`) VALUES
(1, 'Court A', 'Standard', 'Main Hall 1', 'Shower, Locker', 25.00, 1, '2026-04-24 05:13:48'),
(2, 'Court B', 'Standard', 'Main Hall 2', 'Shower, Locker', 25.00, 1, '2026-04-24 05:13:48'),
(3, 'Court C', 'Premium', 'VIP Zone', 'Shower, Locker, Cafe', 45.00, 1, '2026-04-24 05:13:48');

-- --------------------------------------------------------

--
-- Table structure for table `court_availability`
--

CREATE TABLE `court_availability` (
  `id` int(11) NOT NULL,
  `court_id` int(11) NOT NULL,
  `day_of_week` tinyint(1) NOT NULL COMMENT '1=Mon,2=Tue,...,7=Sun',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slot_duration` int(11) DEFAULT 60
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `court_availability`
--

INSERT INTO `court_availability` (`id`, `court_id`, `day_of_week`, `start_time`, `end_time`, `slot_duration`) VALUES
(1, 1, 1, '08:00:00', '22:00:00', 60),
(2, 2, 1, '08:00:00', '22:00:00', 60),
(3, 3, 1, '08:00:00', '22:00:00', 60),
(4, 1, 2, '08:00:00', '22:00:00', 60),
(5, 2, 2, '08:00:00', '22:00:00', 60),
(6, 3, 2, '08:00:00', '22:00:00', 60),
(7, 1, 3, '08:00:00', '22:00:00', 60),
(8, 2, 3, '08:00:00', '22:00:00', 60),
(9, 3, 3, '08:00:00', '22:00:00', 60),
(10, 1, 4, '08:00:00', '22:00:00', 60),
(11, 2, 4, '08:00:00', '22:00:00', 60),
(12, 3, 4, '08:00:00', '22:00:00', 60),
(13, 1, 5, '08:00:00', '22:00:00', 60),
(14, 2, 5, '08:00:00', '22:00:00', 60),
(15, 3, 5, '08:00:00', '22:00:00', 60),
(16, 1, 6, '08:00:00', '22:00:00', 60),
(17, 2, 6, '08:00:00', '22:00:00', 60),
(18, 3, 6, '08:00:00', '22:00:00', 60),
(19, 1, 7, '08:00:00', '22:00:00', 60),
(20, 2, 7, '08:00:00', '22:00:00', 60),
(21, 3, 7, '08:00:00', '22:00:00', 60);

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
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `closed_days`
--
ALTER TABLE `closed_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `courts`
--
ALTER TABLE `courts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `court_availability`
--
ALTER TABLE `court_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
