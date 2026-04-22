-- ======================================================
-- 客户模块数据库 (BadmintonHub - Customer Module)
-- 仅包含客户功能必需的表
-- ======================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- 表: `users` (用户信息，注册/登录)
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nric` varchar(50) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 表: `courts` (场地信息，客户查看)
-- --------------------------------------------------------
CREATE TABLE `courts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `court_name` varchar(100) NOT NULL,
  `court_type` varchar(50) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `facilities` varchar(255) DEFAULT NULL,
  `price_per_hour` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 表: `court_availability` (每周可用时段，用于生成可选时间)
-- --------------------------------------------------------
CREATE TABLE `court_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `court_id` int(11) NOT NULL,
  `day_of_week` tinyint(1) NOT NULL COMMENT '1=Mon,2=Tue,...,7=Sun',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slot_duration` int(11) DEFAULT 60,
  PRIMARY KEY (`id`),
  KEY `fk_availability_court` (`court_id`),
  CONSTRAINT `fk_availability_court` FOREIGN KEY (`court_id`) REFERENCES `courts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 表: `closed_days` (场馆关闭日期，不可预订)
-- --------------------------------------------------------
CREATE TABLE `closed_days` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `closed_date` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_closed_date` (`closed_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 表: `otp_codes` (验证码，用于注册/登录)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ======================================================
-- 示例数据 (方便测试)
-- ======================================================
INSERT INTO `courts` (`court_name`, `court_type`, `location`, `facilities`, `price_per_hour`) VALUES
('Court A', 'Standard', 'Main Hall 1', 'Shower, Locker', 25.00),
('Court B', 'Standard', 'Main Hall 2', 'Shower, Locker', 25.00),
('Court C', 'Premium', 'VIP Zone', 'Shower, Locker, Cafe', 45.00);

-- 为每个场地插入默认可用时段 (周一至周日 08:00-22:00)
INSERT INTO `court_availability` (`court_id`, `day_of_week`, `start_time`, `end_time`)
SELECT c.id, d.day, '08:00:00', '22:00:00'
FROM courts c
CROSS JOIN (SELECT 1 as day UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7) d;

INSERT INTO `closed_days` (`closed_date`, `reason`) VALUES
('2025-01-01', 'New Year'),
('2025-05-01', 'Labour Day');

COMMIT;