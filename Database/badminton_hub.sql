-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 05, 2026 at 09:58 AM
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
  `coach_price_per_hour` decimal(10,2) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `role`, `status`, `specialisation`, `is_coach`, `coach_price_per_hour`, `reset_token`, `token_expiry`, `created_at`) VALUES
(1, 'superadmin', 'chinzx1814@gmail.com', '$2y$10$x5NsQGVwkkp5f4oivtMd..D9tsrJLMICxeSDnSe0peEwVN77QeFGu', 'Superadmin', 'Active', NULL, 0, 0.00, '6e65f2df57e2231428b98e31d6b08f18c8d1acfb3326db32d903e640866bb7fe', '2026-06-05 06:43:54', '2026-04-30 16:17:09'),
(2, 'Coach Lim', 'coach.lim@smasharena.com', '$2y$10$0lBfa23QtHMftiHohzzAjeQQKBt5qNffLSkbubScELAAKyDJO18PK', 'Coach', 'Active', 'Singles Coaching', 1, 50.00, NULL, NULL, '2026-04-30 16:17:09'),
(3, 'Coach Wong', 'coach.wong@smasharena.com', '$2y$10$0lBfa23QtHMftiHohzzAjeQQKBt5qNffLSkbubScELAAKyDJO18PK', 'Coach', 'Active', 'Doubles Coaching', 1, 20.00, NULL, NULL, '2026-04-30 16:17:09'),
(4, 'Coach Tan', 'coach.tan@smasharena.com', '$2y$10$0lBfa23QtHMftiHohzzAjeQQKBt5qNffLSkbubScELAAKyDJO18PK', 'Coach', 'Active', 'Junior Development', 1, 30.00, NULL, NULL, '2026-04-30 16:17:09'),
(5, 'admin', '', '$2y$10$0lBfa23QtHMftiHohzzAjeQQKBt5qNffLSkbubScELAAKyDJO18PK', 'Admin', 'Active', NULL, 0, 0.00, NULL, NULL, '2026-04-30 16:37:03');

--
-- Triggers `admins`
--
DELIMITER $$
CREATE TRIGGER `trg_admins_before_insert` BEFORE INSERT ON `admins` FOR EACH ROW BEGIN
    IF NEW.role IN ('Superadmin', 'Admin') THEN
        SET NEW.is_coach             = 0;
        SET NEW.specialisation       = NULL;
        SET NEW.coach_price_per_hour = 0.00;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_admins_before_update` BEFORE UPDATE ON `admins` FOR EACH ROW BEGIN
    -- 如果 role 本来就是 / 即将改成 Superadmin 或 Admin
    IF NEW.role IN ('Superadmin', 'Admin') THEN
        SET NEW.is_coach             = 0;
        SET NEW.specialisation       = NULL;
        SET NEW.coach_price_per_hour = 0.00;
    END IF;

    -- 额外防止：把 Superadmin/Admin 的 is_coach 手动改成 1
    IF OLD.role IN ('Superadmin', 'Admin') AND NEW.is_coach = 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Superadmin/Admin 账号不能设置为 Coach (is_coach must be 0)';
    END IF;
END
$$
DELIMITER ;

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
  `cancellation_fee` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `court_id`, `booking_date`, `start_time`, `end_time`, `total_hours`, `coach_id`, `coach_hours`, `coach_price_total`, `session_type`, `total_price`, `status`, `cancellation_fee`, `notes`, `created_at`) VALUES
(6, 2, 1, '2026-05-02', '09:00:00', '11:00:00', 2, NULL, 0, 0.00, 'Training', 40.00, 'Confirmed', 0.00, 'Alice morning training', '2026-04-30 16:34:47'),
(7, 3, 2, '2026-05-03', '14:00:00', '16:00:00', 2, NULL, 0, 0.00, '', 50.00, 'Pending', 0.00, 'Michael weekend match', '2026-04-30 16:34:47'),
(8, 4, 1, '2026-05-04', '08:00:00', '10:00:00', 2, NULL, 0, 0.00, 'Training', 40.00, 'Confirmed', 0.00, 'Siti fitness practice', '2026-04-30 16:34:47'),
(9, 5, 3, '2026-05-04', '18:00:00', '20:00:00', 2, NULL, 0, 0.00, '', 50.00, 'Confirmed', 0.00, 'Ahmad evening session', '2026-04-30 16:34:47'),
(10, 6, 2, '2026-05-05', '10:00:00', '12:00:00', 2, NULL, 0, 0.00, 'Training', 40.00, 'Cancelled', 0.00, 'Rachel cancelled booking', '2026-04-30 16:34:47'),
(11, 7, 1, '2026-05-05', '13:00:00', '15:00:00', 2, NULL, 0, 0.00, 'Training', 40.00, 'Confirmed', 0.00, 'Daniel weekday practice', '2026-04-30 16:34:47'),
(12, 8, 3, '2026-05-06', '16:00:00', '18:00:00', 2, NULL, 0, 0.00, '', 50.00, 'Completed', 0.00, 'Priya competitive game', '2026-04-30 16:34:47'),
(13, 9, 2, '2026-05-06', '19:00:00', '21:00:00', 2, NULL, 0, 0.00, 'Training', 40.00, 'Confirmed', 0.00, 'Jason night practice', '2026-04-30 16:34:47'),
(14, 10, 1, '2026-05-07', '07:00:00', '09:00:00', 2, NULL, 0, 0.00, 'Training', 40.00, 'Confirmed', 0.00, 'Nurul early session', '2026-04-30 16:34:47'),
(15, 11, 1, '2026-05-15', '21:00:00', '23:00:00', 2, 0, 0, 0.00, 'Casual Play', 30.00, 'Cancelled', 0.00, '', '2026-05-07 03:29:21'),
(16, 11, 1, '2026-05-08', '09:00:00', '11:00:00', 2, 0, 0, 0.00, 'Casual Play', 20.00, 'Cancelled', 0.00, '', '2026-05-07 03:40:37'),
(17, 11, 1, '2026-05-08', '11:00:00', '01:00:00', 14, 0, 0, 0.00, 'Casual Play', 195.00, 'Cancelled', 0.00, '', '2026-05-07 03:43:56'),
(18, 11, 1, '2026-05-08', '12:00:00', '18:00:00', 6, 0, 0, 0.00, 'Casual Play', 80.00, 'Cancelled', 0.00, '', '2026-05-07 03:44:12'),
(19, 11, 3, '2026-05-08', '08:00:00', '10:00:00', 2, 0, 0, 0.00, 'Casual Play', 20.00, 'Cancelled', 0.00, '', '2026-05-07 03:50:54'),
(20, 11, 3, '2026-05-08', '14:00:00', '20:00:00', 6, 0, 0, 0.00, 'Casual Play', 90.00, 'Cancelled', 0.00, '', '2026-05-07 03:59:49'),
(21, 11, 1, '2026-05-23', '08:00:00', '09:00:00', 1, 0, 0, 0.00, 'Casual Play', 10.00, 'Cancelled', 0.00, '', '2026-05-07 04:02:04'),
(22, 11, 1, '2026-05-22', '10:00:00', '11:00:00', 1, 0, 0, 0.00, 'Casual Play', 10.00, 'Cancelled', 0.00, '', '2026-05-07 04:02:30'),
(23, 11, 2, '2026-05-08', '16:00:00', '21:00:00', 5, 0, 0, 0.00, 'Casual Play', 75.00, 'Cancelled', 0.00, '', '2026-05-07 04:02:54'),
(24, 11, 1, '2026-05-15', '10:00:00', '16:00:00', 6, 0, 0, 0.00, 'Casual Play', 70.00, 'Cancelled', 0.00, '', '2026-05-07 04:05:31'),
(25, 11, 2, '2026-05-08', '13:00:00', '14:00:00', 1, 0, 0, 0.00, 'Casual Play', 10.00, 'Cancelled', 0.00, '', '2026-05-07 04:05:43'),
(26, 11, 2, '2026-05-29', '13:00:00', '00:00:00', 11, 0, 0, 0.00, 'Casual Play', 160.00, 'Cancelled', 0.00, '', '2026-05-07 04:06:31'),
(27, 11, 1, '2026-05-08', '09:00:00', '10:00:00', 1, 0, 0, 0.00, 'Casual Play', 10.00, 'Pending', 0.00, '', '2026-05-07 04:16:32'),
(28, 11, 1, '2026-05-07', '13:00:00', '14:00:00', 1, 0, 0, 0.00, 'Casual Play', 10.00, 'Confirmed', 0.00, '', '2026-05-07 04:24:15'),
(29, 11, 2, '2026-05-08', '10:00:00', '12:00:00', 2, 0, 0, 0.00, 'Casual Play', 20.00, 'Pending', 0.00, '', '2026-05-07 04:40:34'),
(30, 11, 2, '2026-05-28', '08:00:00', '09:00:00', 1, 0, 0, 0.00, 'Casual Play', 10.00, 'Pending', 0.00, '', '2026-05-07 05:24:25'),
(31, 11, 2, '2026-05-14', '10:00:00', '11:00:00', 1, 0, 0, 0.00, 'Casual Play', 10.00, 'Pending', 0.00, '', '2026-05-07 05:33:03'),
(32, 11, 2, '2026-05-23', '09:00:00', '10:00:00', 1, 0, 0, 0.00, 'Casual Play', 10.00, 'Confirmed', 0.00, '', '2026-05-07 05:37:58'),
(33, 1, 5, '2026-05-18', '16:00:00', '17:00:00', 1, 0, 0, 0.00, 'Casual Play', 15.00, 'Confirmed', 0.00, '', '2026-05-18 07:36:21'),
(34, 1, 8, '2026-05-18', '16:00:00', '17:00:00', 1, 4, 1, 10.00, 'Casual Play', 224.00, 'Confirmed', 0.00, '', '2026-05-18 07:59:13'),
(35, 1, 1, '2026-05-21', '09:00:00', '11:00:00', 2, 0, 0, 0.00, 'Casual Play', 20.00, 'Cancelled', 10.00, '', '2026-05-20 10:42:20'),
(36, 1, 1, '2026-05-22', '10:00:00', '12:00:00', 2, 0, 0, 0.00, 'Casual Play', 20.00, 'Cancelled', 10.00, '', '2026-05-20 11:02:24'),
(37, 1, 1, '2026-05-21', '14:00:00', '15:00:00', 1, 0, 0, 0.00, 'Casual Play', 15.00, 'Cancelled', 10.00, '', '2026-05-20 11:11:59'),
(38, 1, 1, '2026-05-21', '15:00:00', '16:00:00', 1, 0, 0, 0.00, 'Casual Play', 15.00, 'Cancelled', 10.00, '', '2026-05-20 11:16:04'),
(39, 1, 2, '2026-05-21', '12:00:00', '14:00:00', 2, 0, 0, 0.00, 'Casual Play', 319.00, 'Confirmed', 0.00, '', '2026-05-20 11:28:04'),
(40, 1, 3, '2026-05-21', '09:00:00', '11:00:00', 2, 0, 0, 0.00, 'Casual Play', 219.00, 'Cancelled', 10.00, '', '2026-05-20 11:28:53'),
(41, 1, 3, '2026-05-21', '11:00:00', '13:00:00', 2, 0, 0, 0.00, 'Casual Play', 20.00, 'Cancelled', 10.00, '', '2026-05-20 11:31:45'),
(42, 1, 3, '2026-05-21', '10:00:00', '12:00:00', 2, 0, 0, 0.00, 'Casual Play', 20.00, 'Confirmed', 0.00, '', '2026-05-20 11:35:21'),
(43, 1, 1, '2026-05-21', '10:00:00', '12:00:00', 2, 0, 0, 0.00, 'Casual Play', 20.00, 'Confirmed', 0.00, '', '2026-05-20 11:40:50'),
(44, 1, 1, '2026-05-29', '10:00:00', '11:00:00', 1, 0, 0, 0.00, 'Casual Play', 10.00, 'Pending', 0.00, '', '2026-05-20 11:49:45'),
(45, 1, 1, '2026-05-21', '13:00:00', '15:00:00', 2, 0, 0, 0.00, 'Casual Play', 25.00, 'Confirmed', 0.00, '', '2026-05-20 12:21:58'),
(46, 1, 1, '2026-05-22', '10:00:00', '12:00:00', 2, 0, 0, 0.00, 'Casual Play', 20.00, 'Confirmed', 0.00, '', '2026-05-21 04:36:43'),
(47, 1, 2, '2026-05-28', '10:00:00', '11:00:00', 1, 0, 0, 0.00, 'Casual Play', 10.00, 'Pending', 0.00, '', '2026-05-21 06:43:16'),
(48, 1, 8, '2026-05-21', '15:00:00', '16:00:00', 1, 1, 1, 25.00, 'Casual Play', 40.00, 'Pending', 0.00, '', '2026-05-21 06:43:43'),
(49, 1, 8, '2026-05-21', '16:00:00', '17:00:00', 1, 1, 1, 25.00, 'Casual Play', 40.00, 'Confirmed', 0.00, '', '2026-05-21 06:44:20'),
(50, 1, 2, '2026-05-29', '09:00:00', '10:00:00', 1, 0, 0, 0.00, 'Casual Play', 10.00, 'Pending', 0.00, '', '2026-05-21 15:28:45'),
(51, 1, 2, '2026-05-27', '10:00:00', '11:00:00', 1, 0, 0, 0.00, 'Casual Play', 10.00, 'Pending', 0.00, '', '2026-05-26 17:05:37'),
(52, 11, 2, '2026-06-10', '08:45:00', '11:42:00', 3, 1, 3, 150.00, 'Casual Play', 180.00, 'Cancelled', 0.00, '', '2026-06-05 00:42:35'),
(53, 1, 2, '2026-06-24', '09:00:00', '18:00:00', 9, 0, 0, 0.00, 'Casual Play', 110.00, 'Pending', 0.00, '', '2026-06-05 03:28:27');

-- --------------------------------------------------------

--
-- Table structure for table `booking_addons`
--

CREATE TABLE `booking_addons` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_addons`
--

INSERT INTO `booking_addons` (`id`, `booking_id`, `product_id`, `quantity`, `price`) VALUES
(1, 34, 7, 1, 199.00),
(2, 39, 6, 1, 299.00),
(3, 40, 7, 1, 199.00);

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
  `phone` varchar(20) DEFAULT NULL,
  `price_per_hour` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `gender` enum('Male','Female') DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `profile_img` varchar(255) DEFAULT NULL,
  `availability_status` enum('Available','On Leave','Sick','Off Day') DEFAULT 'Available',
  `available_from` time DEFAULT NULL,
  `available_to` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coaches`
--

INSERT INTO `coaches` (`id`, `admin_id`, `name`, `specialty`, `phone`, `price_per_hour`, `is_active`, `gender`, `age`, `profile_img`, `availability_status`, `available_from`, `available_to`) VALUES
(1, 2, 'Coach Lim', 'Singles Coaching', '', 50.00, 1, '', 40, '1779345778_coach.png', 'Available', NULL, NULL),
(2, 3, 'Coach Wong', 'Doubles Coaching', '', 20.00, 1, '', 0, NULL, 'Available', NULL, NULL),
(3, 4, 'Coach Tan', 'Junior Development', '', 30.00, 1, 'Male', NULL, NULL, 'Available', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `coach_availability`
--

CREATE TABLE `coach_availability` (
  `id` int(11) NOT NULL,
  `coach_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('On Leave','Sick','Off Day','Custom Hours') NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_role` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coach_availability_log`
--

CREATE TABLE `coach_availability_log` (
  `id` int(11) NOT NULL,
  `coach_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `action` enum('created','updated','deleted') NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `old_start_time` time DEFAULT NULL,
  `old_end_time` time DEFAULT NULL,
  `new_start_time` time DEFAULT NULL,
  `new_end_time` time DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `changed_by_role` varchar(20) DEFAULT NULL,
  `changed_by_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('unread','read','replied') DEFAULT 'unread',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `court_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courts`
--

INSERT INTO `courts` (`id`, `court_name`, `court_type`, `location`, `facilities`, `price_off_peak`, `price_peak`, `court_image`, `is_active`, `created_at`) VALUES
(1, 'Court A', 'Standard', 'Main Hall 1', 'Shower, Locker', 10.00, 15.00, 'court_a.png', 1, '2026-04-30 16:17:09'),
(2, 'Court B', 'Standard', 'Main Hall 1', 'Shower, Locker', 10.00, 15.00, 'court_b.png', 1, '2026-04-30 16:17:09'),
(3, 'Court C', 'Standard', 'Main Hall 2', 'Shower, Locker', 10.00, 15.00, 'court_c.png', 1, '2026-04-30 16:17:09'),
(4, 'Court D', 'Standard', 'Main Hall 2', 'Shower, Locker', 10.00, 15.00, 'court_d.png', 1, '2026-04-30 16:17:09'),
(5, 'Court E', 'Standard', 'Main Hall 3', 'Shower, Locker', 10.00, 15.00, 'court_e.png', 1, '2026-04-30 16:17:09'),
(6, 'Court F', 'Standard', 'Main Hall 3', 'Shower, Locker', 10.00, 15.00, 'court_f.png', 1, '2026-04-30 16:17:09'),
(7, 'Court G', 'Standard', 'Main Hall 4', 'Shower, Locker', 10.00, 15.00, 'court_g.png', 1, '2026-04-30 16:17:09'),
(8, 'Court H', 'Training', 'Training Hall', 'Coaching area, Video analysis, Shower', 10.00, 15.00, 'court_h.png', 1, '2026-04-30 16:17:09'),
(9, 'Court I', 'Training', 'Training Hall', 'Coaching area, Video analysis, Shower', 10.00, 15.00, 'court_i.png', 1, '2026-04-30 16:17:09'),
(10, 'Court J', 'Training', 'Training Hall', 'Coaching area, Video analysis, Shower', 10.00, 15.00, 'court_j.png', 1, '2026-04-30 16:17:09');

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
(1, 1, 1, '08:00:00', '20:00:00', 60),
(2, 2, 1, '08:00:00', '20:00:00', 60),
(3, 3, 1, '08:00:00', '20:00:00', 60),
(4, 4, 1, '08:00:00', '20:00:00', 60),
(5, 5, 1, '08:00:00', '20:00:00', 60),
(6, 6, 1, '08:00:00', '20:00:00', 60),
(7, 7, 1, '08:00:00', '20:00:00', 60),
(8, 8, 1, '08:00:00', '20:00:00', 60),
(9, 9, 1, '08:00:00', '20:00:00', 60),
(10, 10, 1, '08:00:00', '20:00:00', 60),
(11, 1, 2, '08:00:00', '20:00:00', 60),
(12, 2, 2, '08:00:00', '20:00:00', 60),
(13, 3, 2, '08:00:00', '20:00:00', 60),
(14, 4, 2, '08:00:00', '20:00:00', 60),
(15, 5, 2, '08:00:00', '20:00:00', 60),
(16, 6, 2, '08:00:00', '20:00:00', 60),
(17, 7, 2, '08:00:00', '20:00:00', 60),
(18, 8, 2, '08:00:00', '20:00:00', 60),
(19, 9, 2, '08:00:00', '20:00:00', 60),
(20, 10, 2, '08:00:00', '20:00:00', 60),
(21, 1, 3, '08:00:00', '20:00:00', 60),
(22, 2, 3, '08:00:00', '20:00:00', 60),
(23, 3, 3, '08:00:00', '20:00:00', 60),
(24, 4, 3, '08:00:00', '20:00:00', 60),
(25, 5, 3, '08:00:00', '20:00:00', 60),
(26, 6, 3, '08:00:00', '20:00:00', 60),
(27, 7, 3, '08:00:00', '20:00:00', 60),
(28, 8, 3, '08:00:00', '20:00:00', 60),
(29, 9, 3, '08:00:00', '20:00:00', 60),
(30, 10, 3, '08:00:00', '20:00:00', 60),
(31, 1, 4, '08:00:00', '20:00:00', 60),
(32, 2, 4, '08:00:00', '20:00:00', 60),
(33, 3, 4, '08:00:00', '20:00:00', 60),
(34, 4, 4, '08:00:00', '20:00:00', 60),
(35, 5, 4, '08:00:00', '20:00:00', 60),
(36, 6, 4, '08:00:00', '20:00:00', 60),
(37, 7, 4, '08:00:00', '20:00:00', 60),
(38, 8, 4, '08:00:00', '20:00:00', 60),
(39, 9, 4, '08:00:00', '20:00:00', 60),
(40, 10, 4, '08:00:00', '20:00:00', 60),
(41, 1, 5, '08:00:00', '20:00:00', 60),
(42, 2, 5, '08:00:00', '20:00:00', 60),
(43, 3, 5, '08:00:00', '20:00:00', 60),
(44, 4, 5, '08:00:00', '20:00:00', 60),
(45, 5, 5, '08:00:00', '20:00:00', 60),
(46, 6, 5, '08:00:00', '20:00:00', 60),
(47, 7, 5, '08:00:00', '20:00:00', 60),
(48, 8, 5, '08:00:00', '20:00:00', 60),
(49, 9, 5, '08:00:00', '20:00:00', 60),
(50, 10, 5, '08:00:00', '20:00:00', 60),
(51, 1, 6, '08:00:00', '20:00:00', 60),
(52, 2, 6, '08:00:00', '20:00:00', 60),
(53, 3, 6, '08:00:00', '20:00:00', 60),
(54, 4, 6, '08:00:00', '20:00:00', 60),
(55, 5, 6, '08:00:00', '20:00:00', 60),
(56, 6, 6, '08:00:00', '20:00:00', 60),
(57, 7, 6, '08:00:00', '20:00:00', 60),
(58, 8, 6, '08:00:00', '20:00:00', 60),
(59, 9, 6, '08:00:00', '20:00:00', 60),
(60, 10, 6, '08:00:00', '20:00:00', 60),
(61, 1, 7, '08:00:00', '20:00:00', 60),
(62, 2, 7, '08:00:00', '20:00:00', 60),
(63, 3, 7, '08:00:00', '20:00:00', 60),
(64, 4, 7, '08:00:00', '20:00:00', 60),
(65, 5, 7, '08:00:00', '20:00:00', 60),
(66, 6, 7, '08:00:00', '20:00:00', 60),
(67, 7, 7, '08:00:00', '20:00:00', 60),
(68, 8, 7, '08:00:00', '20:00:00', 60),
(69, 9, 7, '08:00:00', '20:00:00', 60),
(70, 10, 7, '08:00:00', '20:00:00', 60);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `recipient_role` enum('Superadmin','Admin','Coach','All') NOT NULL,
  `recipient_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` varchar(30) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `recipient_role`, `recipient_id`, `type`, `title`, `message`, `reference_id`, `reference_type`, `is_read`, `created_at`) VALUES
(1, 'Admin', NULL, 'confirmed', 'Booking Confirmed', 'Booking #32 for wz at Court B on 23 May 2026 09:00 AM has been confirmed.', 32, 'booking', 1, '2026-05-26 14:55:05'),
(2, 'Superadmin', NULL, 'confirmed', 'Booking Confirmed', 'Booking #32 for wz at Court B on 23 May 2026 09:00 AM has been confirmed.', 32, 'booking', 1, '2026-05-26 14:55:05'),
(3, 'Admin', NULL, 'new_booking', 'New Booking Pending', 'Booking #44 from John Doe for Court A on 29 May 2026 is waiting for confirmation.', 44, 'booking', 1, '2026-05-26 14:55:08'),
(4, 'Superadmin', NULL, 'new_booking', 'New Booking Pending', 'Booking #44 from John Doe for Court A on 29 May 2026 is waiting for confirmation.', 44, 'booking', 1, '2026-05-26 14:55:08'),
(5, 'Coach', 2, 'booking', 'New Session Assigned', 'You have a new coaching session on 2026-06-10 at 08:45.', 52, 'booking', 1, '2026-06-05 00:42:35'),
(6, 'Admin', NULL, 'cancelled', 'Booking Cancelled', 'Booking #52 for wz at Court B on 10 Jun 2026 has been cancelled.', 52, 'booking', 0, '2026-06-05 04:00:39'),
(7, 'Superadmin', NULL, 'cancelled', 'Booking Cancelled', 'Booking #52 for wz at Court B on 10 Jun 2026 has been cancelled.', 52, 'booking', 1, '2026-06-05 04:00:39'),
(8, 'Coach', 2, 'cancelled', 'Session Cancelled', 'Your coaching session at Court B on 10 Jun 2026 08:45 AM has been cancelled.', 52, 'booking', 1, '2026-06-05 04:00:39');

-- --------------------------------------------------------

--
-- Table structure for table `otp_codes`
--

CREATE TABLE `otp_codes` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `code` varchar(6) NOT NULL,
  `type` enum('register','login','reset') NOT NULL,
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

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `amount`, `discount_applied`, `final_amount`, `payment_method`, `payment_status`, `transaction_id`, `payment_date`) VALUES
(5, 26, 160.00, 0.00, 160.00, 'Center App Wallet', 'success', NULL, '2026-05-07 04:14:24'),
(6, 28, 10.00, 0.00, 10.00, 'App Wallet', 'success', NULL, '2026-05-07 04:39:33'),
(8, 34, 224.00, 0.00, 224.00, 'TNG', 'success', NULL, '2026-05-18 08:00:12'),
(9, 35, 20.00, 0.00, 20.00, 'Online Payment', 'success', NULL, '2026-05-20 11:01:33'),
(10, 36, 10.00, 0.00, 10.00, 'Refund', 'success', 'REF_1779275497_36', '2026-05-20 11:11:37'),
(11, 38, 15.00, 0.00, 15.00, 'Online Payment', 'success', NULL, '2026-05-20 11:19:48'),
(12, 38, 15.00, 0.00, 15.00, 'Online Payment', 'success', NULL, '2026-05-20 11:21:26'),
(13, 38, 15.00, 0.00, 15.00, 'Online Payment', 'success', NULL, '2026-05-20 11:26:07'),
(14, 38, 15.00, 0.00, 15.00, 'Online Payment', 'success', NULL, '2026-05-20 11:26:15'),
(15, 37, 10.00, 0.00, 5.00, 'Refund', 'success', 'REF_1779276471_37', '2026-05-20 11:27:51'),
(16, 38, 10.00, 0.00, 5.00, 'Refund', 'success', 'REF_1779276474_38', '2026-05-20 11:27:54'),
(17, 39, 319.00, 0.00, 319.00, 'Online Payment', 'success', NULL, '2026-05-20 11:28:24'),
(18, 40, 219.00, 0.00, 219.00, 'Online Payment', 'success', NULL, '2026-05-20 11:29:26'),
(19, 41, 20.00, 0.00, 20.00, 'Online Payment', 'success', NULL, '2026-05-20 11:34:52'),
(20, 40, 10.00, 0.00, 209.00, 'Refund', 'success', 'REF_1779276899_40', '2026-05-20 11:34:59'),
(21, 35, 10.00, 0.00, 10.00, 'Refund', 'success', 'REF_1779276903_35', '2026-05-20 11:35:03'),
(22, 41, 10.00, 0.00, 10.00, 'Refund', 'success', 'REF_1779276906_41', '2026-05-20 11:35:06'),
(23, 42, 20.00, 0.00, 20.00, 'Online Payment', 'success', NULL, '2026-05-20 11:39:50'),
(24, 44, 10.00, 0.00, 10.00, 'App Wallet', 'success', NULL, '2026-05-20 11:49:57'),
(25, 45, 25.00, 0.00, 25.00, 'Online Payment', 'success', NULL, '2026-05-21 03:34:56'),
(26, 33, 15.00, 0.00, 15.00, 'App Wallet', 'success', NULL, '2026-05-21 03:35:12'),
(27, 46, 20.00, 5.00, 15.00, 'App Wallet', 'success', NULL, '2026-05-21 04:49:31'),
(28, 43, 20.00, 0.00, 20.00, 'App Wallet', 'success', NULL, '2026-05-21 04:50:23'),
(29, 49, 40.00, 0.00, 40.00, 'App Wallet', 'success', NULL, '2026-05-21 06:45:34');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category` enum('racket','string','shuttlecock','grip','snack','drink') NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category`, `name`, `description`, `price`, `image_url`, `stock`, `is_active`) VALUES
(1, 'racket', 'Yonex Astrox 100ZZ', 'Professional grade, head heavy balance', 899.00, 'rackets/yonex_astrox_100zz.jpg', 0, 1),
(2, 'racket', 'Yonex Nanflare 800', 'Ultra light, head light balance', 799.00, 'rackets/yonex_nanoflare_800.jpg', 0, 1),
(3, 'racket', 'Li-Ning Axforce 80', 'Powerful smash, stiff shaft', 699.00, 'rackets/li-ning_axforce_80.jpg', 0, 1),
(4, 'racket', 'Victor Thruster F', 'Enhanced power, box frame', 649.00, 'rackets/victor_thruster_f.jpg', 0, 1),
(5, 'racket', 'Yonex Arcsaber 11', 'All-round performance', 599.00, 'rackets/yonex_arcsaber_11.jpg', 0, 1),
(6, 'racket', 'Apacs Z-Ziggler', 'High speed, affordable', 299.00, 'rackets/apacs_z_ziggler.jpg', 0, 1),
(7, 'racket', 'Protech Classic', 'Entry level, good for beginners', 199.00, 'rackets/protech_classic.jpg', 0, 1),
(8, 'racket', 'Yonex Astrox 99', 'Extreme power for advanced', 899.00, 'rackets/yonex_astrox_99.jpg', 0, 1),
(9, 'racket', 'Victor Auraspeed 90S', 'Fast swing, aerodynamic frame', 749.00, 'rackets/victor_auraspeed_90s.jpg', 0, 1),
(10, 'racket', 'Li-Ning 3D Calibar 900', '3D frame design, powerful', 799.00, 'rackets/li-ning_3d_calibar_900.jpg', 0, 1),
(11, 'string', 'Yonex BG-65', 'Durable, all-round performance', 35.00, 'strings/yonex_bg-65.png', 0, 1),
(12, 'string', 'Yonex BG-66 Ultimax', 'Repulsive power, thin gauge', 40.00, 'strings/yonex_bg-66_ultimax.png', 0, 1),
(13, 'string', 'Yonex BG-80 Power', 'Rough surface for spin', 45.00, 'strings/yonex_bg-80_power.png', 0, 1),
(14, 'string', 'Li-Ning No.1', 'High repulsion, durable', 42.00, 'strings/li-ning_no.1.png', 0, 1),
(15, 'string', 'Victor VBS-66N', 'Excellent control', 38.00, 'strings/victor_vbs-66n.png', 0, 1),
(16, 'string', 'Apacs L66', 'Affordable performance', 28.00, 'strings/apacs_l66.png', 0, 1),
(17, 'shuttlecock', 'Aeroplane EG1130 (Speed 77)', 'Tournament grade, goose feather', 85.00, 'shuttlecocks/aeroplane_eg1130_(speed_77).jpg', 0, 1),
(18, 'shuttlecock', 'Protech Masterpiece', 'High durability, consistent flight', 75.00, 'shuttlecocks/protech_masterpiece.jpg', 0, 1),
(19, 'shuttlecock', 'Yonex Aerosensa 30', 'Official tournament shuttle', 95.00, 'shuttlecocks/yonex_aerosensa_30.jpg', 0, 1),
(20, 'shuttlecock', 'RSL Classic Tourney', 'Premium quality, good speed', 78.00, 'shuttlecocks/rsl_classic_tourney.jpg', 0, 1),
(21, 'shuttlecock', 'Apacs 900 Feather Shuttlecock', 'Good for training', 55.00, 'shuttlecocks/apacs_900_feather_shuttlecock.jpg', 0, 1),
(22, 'grip', 'Yonex Super Grap (Red)', 'Tacky feel, absorbs sweat', 12.00, 'grips/yonex_super_grap_(red).jpg', 0, 1),
(23, 'grip', 'Yonex Super Grap (Yellow)', 'Tacky feel, absorbs sweat', 12.00, 'grips/yonex_super_grap_(yellow).jpg', 0, 1),
(24, 'grip', 'Yonex Super Grap (Black)', 'Tacky feel, absorbs sweat', 12.00, 'grips/yonex_super_grap_(black).jpg', 0, 1),
(25, 'grip', 'Li-Ning GP1000', 'Cushioning, anti-slip', 15.00, 'grips/li_ning_gp1000.jpg', 0, 1),
(26, 'grip', 'Victor GR233', 'Excellent absorption', 14.00, 'grips/victor_gr233.jpg', 0, 1),
(27, 'grip', 'Apacs Cushion Grip', 'Affordable, comfortable', 8.00, 'grips/apacs_cushion_grip.jpg', 0, 1),
(28, 'snack', 'KitKat Chocolate', 'Crispy wafer chocolate bar', 4.50, 'snacks/kitkat_chocolate.jpg', 0, 1),
(29, 'snack', 'Oreo Biscuits', 'Original flavor', 3.50, 'snacks/oreo_biscuits.jpg', 0, 1),
(30, 'snack', 'Pringles Original', 'Potato chips', 6.50, 'snacks/pringles_original.jpg', 0, 1),
(31, 'snack', 'Mister Potato', 'Crispy potato snack', 4.00, 'snacks/mister_potato.jpg', 0, 1),
(32, 'snack', 'Cadbury Dairy Milk', 'Milk chocolate bar', 5.00, 'snacks/cadbury_dairy_milk.jpg', 0, 1),
(33, 'drink', '100 Plus Isotonic', 'Replenish energy, 500ml', 3.50, 'drinks/100_plus_isotonic.jpg', 0, 1),
(34, 'drink', 'Mineral Water', '500ml', 1.50, 'drinks/mineral_water.jpg', 0, 1),
(35, 'drink', 'Coca Cola', '330ml can', 2.50, 'drinks/coca_cola.jpg', 0, 1),
(36, 'drink', 'Sprite', '330ml can', 2.50, 'drinks/sprite.jpg', 0, 1),
(37, 'drink', 'Revive Isotonic', 'Sports drink, 500ml', 3.00, 'drinks/revive_isotonic.jpg', 0, 1),
(38, 'drink', 'Milo', 'Chocolate malt drink', 3.00, 'drinks/milo.jpg', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `promo_codes`
--

CREATE TABLE `promo_codes` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `valid_from` date NOT NULL,
  `valid_until` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(100) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'open_time', '08:00', '2026-05-09 11:20:24'),
(2, 'close_time', '20:00', '2026-05-09 11:26:35'),
(3, 'peak_start', '16:00', '2026-05-09 11:27:15'),
(4, 'peak_end', '20:00', '2026-05-09 11:27:23'),
(5, 'off_peak_price', '10', '2026-06-04 16:47:16'),
(6, 'peak_price', '15', '2026-06-04 16:47:16'),
(7, 'cancellation_hours', '2', '2026-06-04 15:54:37'),
(8, 'cancellation_fee', '10', '2026-06-04 16:47:16'),
(9, 'contact_phone', '+603-1234 5678', '2026-06-04 15:54:37'),
(10, 'contact_email', 'smasharenabadminton@gmail.com', '2026-06-04 15:54:37'),
(11, 'contact_whatsapp', '+60 12-345 6789', '2026-06-04 15:54:37'),
(12, 'address', '123 Jalan Badminton, Kuala Lumpur, Malaysia', '2026-06-04 15:54:37');

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
  `gender` enum('Male','Female') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `wallet_balance` decimal(10,2) DEFAULT 0.00,
  `loyalty_points` int(11) NOT NULL DEFAULT 0,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `gender`, `created_at`, `wallet_balance`, `loyalty_points`, `profile_picture`) VALUES
(1, 'John Doe', 'john@example.com', '$2y$10$0lBfa23QtHMftiHohzzAjeQQKBt5qNffLSkbubScELAAKyDJO18PK', '+60123456789', NULL, '2026-04-30 16:17:09', 209.00, 0, NULL),
(2, 'Alice Tan', 'alice@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2...', '+60111222333', NULL, '2026-04-30 16:34:47', 0.00, 0, NULL),
(3, 'Michael Lee', 'michael@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2...', '+60199888777', NULL, '2026-04-30 16:34:47', 0.00, 0, NULL),
(4, 'Siti Aminah', 'siti@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2...', '+60122334455', NULL, '2026-04-30 16:34:47', 0.00, 0, NULL),
(5, 'Ahmad Firdaus', 'ahmad@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2...', '+60155667788', NULL, '2026-04-30 16:34:47', 0.00, 0, NULL),
(6, 'Rachel Lim', 'rachel@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2...', '+60166778899', NULL, '2026-04-30 16:34:47', 0.00, 0, NULL),
(7, 'Daniel Wong', 'daniel@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2...', '+60177889900', NULL, '2026-04-30 16:34:47', 0.00, 0, NULL),
(8, 'Priya Kumar', 'priya@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2...', '+60188990011', NULL, '2026-04-30 16:34:47', 0.00, 0, NULL),
(9, 'Jason Teh', 'jason@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2...', '+60199001122', NULL, '2026-04-30 16:34:47', 0.00, 0, NULL),
(10, 'Nurul Huda', 'nurul@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2...', '+60110101010', NULL, '2026-04-30 16:34:47', 0.00, 0, NULL),
(11, 'wz', 'zhefurry@gmail.com', '$2y$10$ti8t5iVME5.hWJhMY0cE5ukBX67z0z4xPn8HL0pskzUS9Kn0PL9iS', '+60123456789', NULL, '2026-04-14 03:28:20', 70.00, 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `user_vouchers`
--

CREATE TABLE `user_vouchers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `voucher_id` int(11) NOT NULL,
  `is_used` int(11) DEFAULT 0,
  `redeemed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_vouchers`
--

INSERT INTO `user_vouchers` (`id`, `user_id`, `voucher_id`, `is_used`, `redeemed_at`) VALUES
(1, 1, 1, 1, '2026-05-21 04:42:19');

-- --------------------------------------------------------

--
-- Table structure for table `voucher`
--

CREATE TABLE `voucher` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `points_required` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `voucher`
--

INSERT INTO `voucher` (`id`, `title`, `discount_amount`, `points_required`, `description`) VALUES
(1, 'RM 5.00 Court Discount', 5.00, 50, 'Redeem with 50 points'),
(2, 'RM 10.00 Court Discount', 10.00, 100, 'Redeem with 100 points'),
(3, 'RM 20.00 Mega Saver', 20.00, 180, 'Redeem with 180 points');

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
-- Indexes for table `booking_addons`
--
ALTER TABLE `booking_addons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_addon_booking` (`booking_id`),
  ADD KEY `fk_addon_product` (`product_id`);

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
-- Indexes for table `coach_availability`
--
ALTER TABLE `coach_availability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coach_id` (`coach_id`);

--
-- Indexes for table `coach_availability_log`
--
ALTER TABLE `coach_availability_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coach_id` (`coach_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `promo_codes`
--
ALTER TABLE `promo_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

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
-- Indexes for table `user_vouchers`
--
ALTER TABLE `user_vouchers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `voucher`
--
ALTER TABLE `voucher`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `booking_addons`
--
ALTER TABLE `booking_addons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `closed_days`
--
ALTER TABLE `closed_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `coaches`
--
ALTER TABLE `coaches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `coach_availability`
--
ALTER TABLE `coach_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coach_availability_log`
--
ALTER TABLE `coach_availability_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `promo_codes`
--
ALTER TABLE `promo_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user_vouchers`
--
ALTER TABLE `user_vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `voucher`
--
ALTER TABLE `voucher`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- Constraints for table `booking_addons`
--
ALTER TABLE `booking_addons`
  ADD CONSTRAINT `fk_addon_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_addon_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coaches`
--
ALTER TABLE `coaches`
  ADD CONSTRAINT `fk_coaches_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `coach_availability`
--
ALTER TABLE `coach_availability`
  ADD CONSTRAINT `coach_availability_ibfk_1` FOREIGN KEY (`coach_id`) REFERENCES `coaches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coach_availability_log`
--
ALTER TABLE `coach_availability_log`
  ADD CONSTRAINT `coach_availability_log_ibfk_1` FOREIGN KEY (`coach_id`) REFERENCES `coaches` (`id`) ON DELETE CASCADE;

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

-- 1. 添加 cancellation_count 字段到 users 表
ALTER TABLE users ADD COLUMN cancellation_count INT DEFAULT 0 AFTER wallet_balance;

-- 2. 添加 reschedule_count 字段到 bookings 表
ALTER TABLE bookings ADD COLUMN reschedule_count INT DEFAULT 0 AFTER status;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
