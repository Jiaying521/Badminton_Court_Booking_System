-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 13, 2026 at 08:18 AM
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
  `token_expiry` datetime DEFAULT NULL,
  `is_doctor` tinyint(1) NOT NULL DEFAULT 0,
  `specialisation` varchar(100) DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT NULL,
  `language` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `role`, `reset_token`, `token_expiry`, `is_doctor`, `specialisation`, `gender`, `language`, `bio`, `profile_image`) VALUES
(0, 'heybi', 'chinzx1814@gmail.com', '$2y$10$x5NsQGVwkkp5f4oivtMd..D9tsrJLMICxeSDnSe0peEwVN77QeFGu', 'Superadmin', '42692f8398994ed579c6f07ae531e8f954dc210f30d9cd99e037f8ab856ec721', '2026-04-07 15:41:20', 0, NULL, NULL, NULL, NULL, NULL),
(1, 'admin', '-', '$2y$10$lKJR3lV5IxZSt0Wasb3dUuCFGooQPJd4EBDFSc0xb30V.N7quWA/e', 'Admin', NULL, NULL, 1, 'Dermatology', 'Female', 'English, Chinese', 'Skin specialist expert in acne and eczema treatment.', NULL),
(2, 'Adam', 'adam@careconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Doctor', NULL, NULL, 1, 'Cardiology', 'Male', 'English, Malay', 'Adult cardiologist specializing in heart disease, hypertension, and cholesterol management. For patients aged 18+.', NULL),
(3,'Lisa', 'lisa@careconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Doctor', NULL, NULL, 1, 'Cardiology', 'Female', 'English, Chinese', 'Pediatric cardiologist for children and adolescents. Expert in congenital heart defects and pediatric heart health.', NULL),
(4,'Mark', 'mark@careconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Doctor', NULL, NULL, 1, 'Dermatology', 'Male', 'English, Malay', 'Adult dermatology: acne, eczema, psoriasis, skin cancer screening. Specialized in adult skin conditions.', NULL),
(5,'Sarah', 'sarah@careconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Doctor', NULL, NULL, 1, 'Dermatology', 'Female', 'English, Chinese', 'Pediatric dermatology: birthmarks, infant eczema, warts, and teenage acne. Gentle care for young skin.', NULL),
(6,'Wong', 'wong@careconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Doctor', NULL, NULL, 1, 'Orthopedics', 'Male', 'English, Malay', 'Adult orthopedics: fractures, arthritis, joint replacement, sports injuries for adults.', NULL),
(7,'Anna', 'anna@careconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Doctor', NULL, NULL, 1, 'Orthopedics', 'Female', 'English, Chinese', 'Pediatric orthopedics: growth plate fractures, scoliosis, clubfoot, and child sports injuries.', NULL),
(8,'Paul', 'paul@careconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Doctor', NULL, NULL, 1, 'Neurology', 'Male', 'English, Malay', 'Adult neurologist: stroke, migraine, epilepsy, Parkinson\'s disease. For patients 16+.', NULL),
(9,'Emma', 'emma@careconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Doctor', NULL, NULL, 1, 'Neurology', 'Female', 'English, Chinese', 'Pediatric neurologist: developmental delays, seizures, headaches, cerebral palsy in children.', NULL),
(10,'SophiaTeen', 'sophia@careconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Doctor', NULL, NULL, 1, 'Pediatrics', 'Female', 'English, Malay, Chinese', 'Adolescent medicine specialist: ages 10-21, focusing on teen health, mental health, and preventive care.', NULL),
(11, 'Emily Tan', 'emilytan@careconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Doctor', NULL, NULL, 1, 'Pediatrics', 'Female', 'English, Malay, Chinese', 'Experienced pediatrician dedicated to providing quality care.', NULL),
(12,'Mike', 'mike@careconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Doctor', NULL, NULL, 1, 'General Practice', 'Male', 'English, Malay', 'Family doctor for adults: general check-ups, acute illness, chronic disease management.', NULL),
(13,'Jenny', 'jenny@careconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Doctor', NULL, NULL, 1, 'General Practice', 'Female', 'English, Chinese', 'Family doctor for children: well-child visits, vaccinations, common childhood illnesses.', NULL);

-- --------------------------------------------------------
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
-- Table structure for table `billing_info`
--

CREATE TABLE `billing_info` (
  `patient_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `billing_address` text NOT NULL,
  `default_payment_method` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing_info`
--

INSERT INTO `billing_info` (`patient_id`, `full_name`, `billing_address`, `default_payment_method`) VALUES
(1, 'Ali Bin Ahmad', 'No 12, Jalan Merdeka, Melaka', 'Credit Card');

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
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `discount_applied` decimal(10,2) DEFAULT 0.00,
  `final_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` varchar(50) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `amount`, `discount_applied`, `final_amount`, `payment_method`, `payment_status`, `payment_date`) VALUES
(12, 50.00, 20.00, 30.00, 'Credit Card', 'Refunded', '2026-04-01 13:07:42'),
(13, 50.00, 20.00, 30.00, 'Credit Card', 'Refunded', '2026-04-01 13:32:02'),
(14, 0.00, 0.00, 0.00, '', 'Success', '2026-04-01 13:36:17'),
(15, 0.00, 0.00, 0.00, '', 'Success', '2026-04-03 01:54:05'),
(16, 0.00, 0.00, 0.00, '', 'Failed', '2026-04-03 01:56:39'),
(17, 0.00, 0.00, 0.00, '', 'Success', '2026-04-03 01:59:27'),
(18, 0.00, 0.00, 0.00, '', 'Success', '2026-04-03 02:02:45'),
(19, 0.00, 0.00, 0.00, '', 'Success', '2026-04-08 02:28:48'),
(20, 0.00, 0.00, 0.00, '', 'Failed', '2026-04-08 02:59:46'),
(21, 50.00, 0.00, 50.00, 'Credit Card', 'Success', '2026-04-08 03:00:19'),
(22, 50.00, 0.00, 50.00, 'Credit Card', 'Success', '2026-04-08 03:11:27'),
(23, 50.00, 0.00, 50.00, 'Bank Transfer', 'Success', '2026-04-08 03:11:43'),
(24, 50.00, 20.00, 30.00, 'Credit Card', 'Failed', '2026-04-09 13:33:42'),
(25, 50.00, 0.00, 50.00, 'Credit Card', 'Success', '2026-04-09 13:33:47'),
(26, 0.00, 0.00, 0.00, '', 'Success', '2026-04-12 12:05:17'),
(27, 50.00, 20.00, 30.00, 'Credit Card', 'Success', '2026-04-13 05:39:55'),
(28, 50.00, 20.00, 30.00, 'E-Wallet', 'Success', '2026-04-13 05:40:02'),
(29, 0.00, 0.00, 0.00, '', 'Failed', '2026-04-13 05:53:14');

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
-- Indexes for table `billing_info`
--
ALTER TABLE `billing_info`
  ADD PRIMARY KEY (`patient_id`);

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
  ADD PRIMARY KEY (`payment_id`);

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
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
