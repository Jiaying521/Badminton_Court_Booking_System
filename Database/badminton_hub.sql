-- ======================================================
-- 数据库: `badminton_hub`
-- 羽毛球场地预订系统 - 完整数据库结构
-- 营业时间: 早上 8:00 - 凌晨 1:00
-- 价格: 8am-2pm = RM10/小时, 3pm-1am = RM15/小时
-- 场地类型: Standard, Training (共10个)
-- ======================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- 表: `users` (用户信息)
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 表: `admins` (管理员)
-- --------------------------------------------------------
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Superadmin','Admin','Staff') NOT NULL DEFAULT 'Admin',
  `status` enum('Active','Inactive','Suspended') NOT NULL DEFAULT 'Inactive',
  `first_login` tinyint(1) NOT NULL DEFAULT 0,
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 表: `courts` (场地信息)
-- --------------------------------------------------------
CREATE TABLE `courts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `court_name` varchar(100) NOT NULL,
  `court_type` enum('Standard','Training') NOT NULL DEFAULT 'Standard',
  `location` varchar(255) DEFAULT NULL,
  `facilities` varchar(255) DEFAULT NULL,
  `price_off_peak` decimal(10,2) NOT NULL DEFAULT 10.00 COMMENT '非高峰价格 8am-2pm',
  `price_peak` decimal(10,2) NOT NULL DEFAULT 15.00 COMMENT '高峰价格 3pm-1am',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 表: `court_availability` (每周可用时段)
-- --------------------------------------------------------
CREATE TABLE `court_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `court_id` int(11) NOT NULL,
  `day_of_week` tinyint(1) NOT NULL COMMENT '1=Mon,2=Tue,...,7=Sun',
  `start_time` time NOT NULL DEFAULT '08:00:00',
  `end_time` time NOT NULL DEFAULT '01:00:00',
  `slot_duration` int(11) DEFAULT 60,
  PRIMARY KEY (`id`),
  KEY `fk_availability_court` (`court_id`),
  CONSTRAINT `fk_availability_court` FOREIGN KEY (`court_id`) REFERENCES `courts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 表: `bookings` (预订记录)
-- --------------------------------------------------------
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `court_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `session_type` enum('Casual Play','Training','Tournament','Friendly Game') DEFAULT 'Casual Play',
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('Pending','Confirmed','Cancelled','Completed') DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_bookings_user` (`user_id`),
  KEY `fk_bookings_court` (`court_id`),
  CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bookings_court` FOREIGN KEY (`court_id`) REFERENCES `courts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 表: `payments` (支付记录)
-- --------------------------------------------------------
CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `discount_applied` decimal(10,2) DEFAULT 0.00,
  `final_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` enum('pending','success','failed') DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`payment_id`),
  KEY `fk_payments_booking` (`booking_id`),
  CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 表: `tasks` (任务管理)
-- --------------------------------------------------------
CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` varchar(100) DEFAULT NULL,
  `status` enum('Pending','In Progress','Done') NOT NULL DEFAULT 'Pending',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 表: `closed_days` (关闭日期)
-- --------------------------------------------------------
CREATE TABLE `closed_days` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `closed_date` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_closed_date` (`closed_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 表: `otp_codes` (验证码)
-- --------------------------------------------------------
CREATE TABLE `otp_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `code` varchar(6) NOT NULL,
  `type` enum('register','login') NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `email` (`email`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 教练表
CREATE TABLE `coaches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `specialty` varchar(100) DEFAULT NULL,
  `price_per_hour` decimal(10,2) NOT NULL DEFAULT 20.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ======================================================
-- 示例数据
-- ======================================================

-- 插入管理员
INSERT INTO `admins` (`id`, `username`, `email`, `password`, `role`, `status`, `first_login`) VALUES
(1, 'superadmin', 'admin@smasharena.com', '$2y$10$x5NsQGVwkkp5f4oivtMd..D9tsrJLMICxeSDnSe0peEwVN77QeFGu', 'Superadmin', 'Active', 1);

-- 插入10个场地 (Standard 和 Training)
INSERT INTO `courts` (`id`, `court_name`, `court_type`, `location`, `facilities`, `price_off_peak`, `price_peak`, `is_active`) VALUES
(1, 'Court A', 'Standard', 'Main Hall 1', 'Shower, Locker', 10.00, 15.00, 1),
(2, 'Court B', 'Standard', 'Main Hall 1', 'Shower, Locker', 10.00, 15.00, 1),
(3, 'Court C', 'Standard', 'Main Hall 2', 'Shower, Locker', 10.00, 15.00, 1),
(4, 'Court D', 'Standard', 'Main Hall 2', 'Shower, Locker', 10.00, 15.00, 1),
(5, 'Court E', 'Standard', 'Main Hall 3', 'Shower, Locker', 10.00, 15.00, 1),
(6, 'Court F', 'Standard', 'Main Hall 3', 'Shower, Locker', 10.00, 15.00, 1),
(7, 'Court G', 'Standard', 'Main Hall 4', 'Shower, Locker', 10.00, 15.00, 1),
(8, 'Court H', 'Training', 'Training Hall', 'Coaching area, Video analysis, Shower', 10.00, 15.00, 1),
(9, 'Court I', 'Training', 'Training Hall', 'Coaching area, Video analysis, Shower', 10.00, 15.00, 1),
(10, 'Court J', 'Training', 'Training Hall', 'Coaching area, Video analysis, Shower', 10.00, 15.00, 1);

-- 插入营业时间 (周一至周日 早上8点 - 凌晨1点) - 为所有10个场地添加
INSERT INTO `court_availability` (`court_id`, `day_of_week`, `start_time`, `end_time`) VALUES
-- 周一 (day_of_week = 1)
(1, 1, '08:00:00', '01:00:00'), (2, 1, '08:00:00', '01:00:00'), (3, 1, '08:00:00', '01:00:00'),
(4, 1, '08:00:00', '01:00:00'), (5, 1, '08:00:00', '01:00:00'), (6, 1, '08:00:00', '01:00:00'),
(7, 1, '08:00:00', '01:00:00'), (8, 1, '08:00:00', '01:00:00'), (9, 1, '08:00:00', '01:00:00'),
(10, 1, '08:00:00', '01:00:00'),
-- 周二 (2)
(1, 2, '08:00:00', '01:00:00'), (2, 2, '08:00:00', '01:00:00'), (3, 2, '08:00:00', '01:00:00'),
(4, 2, '08:00:00', '01:00:00'), (5, 2, '08:00:00', '01:00:00'), (6, 2, '08:00:00', '01:00:00'),
(7, 2, '08:00:00', '01:00:00'), (8, 2, '08:00:00', '01:00:00'), (9, 2, '08:00:00', '01:00:00'),
(10, 2, '08:00:00', '01:00:00'),
-- 周三 (3)
(1, 3, '08:00:00', '01:00:00'), (2, 3, '08:00:00', '01:00:00'), (3, 3, '08:00:00', '01:00:00'),
(4, 3, '08:00:00', '01:00:00'), (5, 3, '08:00:00', '01:00:00'), (6, 3, '08:00:00', '01:00:00'),
(7, 3, '08:00:00', '01:00:00'), (8, 3, '08:00:00', '01:00:00'), (9, 3, '08:00:00', '01:00:00'),
(10, 3, '08:00:00', '01:00:00'),
-- 周四 (4)
(1, 4, '08:00:00', '01:00:00'), (2, 4, '08:00:00', '01:00:00'), (3, 4, '08:00:00', '01:00:00'),
(4, 4, '08:00:00', '01:00:00'), (5, 4, '08:00:00', '01:00:00'), (6, 4, '08:00:00', '01:00:00'),
(7, 4, '08:00:00', '01:00:00'), (8, 4, '08:00:00', '01:00:00'), (9, 4, '08:00:00', '01:00:00'),
(10, 4, '08:00:00', '01:00:00'),
-- 周五 (5)
(1, 5, '08:00:00', '01:00:00'), (2, 5, '08:00:00', '01:00:00'), (3, 5, '08:00:00', '01:00:00'),
(4, 5, '08:00:00', '01:00:00'), (5, 5, '08:00:00', '01:00:00'), (6, 5, '08:00:00', '01:00:00'),
(7, 5, '08:00:00', '01:00:00'), (8, 5, '08:00:00', '01:00:00'), (9, 5, '08:00:00', '01:00:00'),
(10, 5, '08:00:00', '01:00:00'),
-- 周六 (6)
(1, 6, '08:00:00', '01:00:00'), (2, 6, '08:00:00', '01:00:00'), (3, 6, '08:00:00', '01:00:00'),
(4, 6, '08:00:00', '01:00:00'), (5, 6, '08:00:00', '01:00:00'), (6, 6, '08:00:00', '01:00:00'),
(7, 6, '08:00:00', '01:00:00'), (8, 6, '08:00:00', '01:00:00'), (9, 6, '08:00:00', '01:00:00'),
(10, 6, '08:00:00', '01:00:00'),
-- 周日 (7)
(1, 7, '08:00:00', '01:00:00'), (2, 7, '08:00:00', '01:00:00'), (3, 7, '08:00:00', '01:00:00'),
(4, 7, '08:00:00', '01:00:00'), (5, 7, '08:00:00', '01:00:00'), (6, 7, '08:00:00', '01:00:00'),
(7, 7, '08:00:00', '01:00:00'), (8, 7, '08:00:00', '01:00:00'), (9, 7, '08:00:00', '01:00:00'),
(10, 7, '08:00:00', '01:00:00');

-- 插入关闭日期
INSERT INTO `closed_days` (`closed_date`, `reason`) VALUES
('2025-01-01', 'New Year'),
('2025-05-01', 'Labour Day'),
('2025-08-31', 'National Day'),
('2025-12-25', 'Christmas Day');

-- 插入示例用户 (密码: 123456, 需要包含符号和长度要求)
-- 实际使用时需要密码至少6位+符号，这里仅作示例结构
INSERT INTO `users` (`name`, `email`, `password`, `phone`) VALUES
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+60123456789');

-- 插入教练数据
INSERT INTO `coaches` (`id`, `name`, `specialty`, `price_per_hour`, `is_active`) VALUES
(1, 'Coach Lim', '🏸 Professional Training - Overall skill improvement, power hitting, consistency', 25.00, 1),
(2, 'Coach Wong', '🎯 Technique & Footwork - Basic strokes, footwork, body positioning', 20.00, 1),
(3, 'Coach Tan', '🏆 Strategy & Match Play - Game tactics, mental training, competition prep', 30.00, 1);

COMMIT;