-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 06, 2026 at 08:06 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.3.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bank_queue_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `username` varchar(50) NOT NULL,
  `attempted_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `queue`
--

CREATE TABLE `queue` (
  `id` int(11) NOT NULL,
  `customer_number` int(11) NOT NULL,
  `status` enum('waiting','serving','served') NOT NULL DEFAULT 'waiting',
  `teller` varchar(50) DEFAULT NULL,
  `client_type` varchar(20) NOT NULL DEFAULT 'Regular',
  `created_at` datetime DEFAULT NULL,
  `called_at` datetime DEFAULT NULL,
  `served_at` datetime DEFAULT NULL,
  `skip_count` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `no_show` tinyint(1) NOT NULL DEFAULT 0,
  `recall_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `kiosk_ip` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `queue`
--

INSERT INTO `queue` (`id`, `customer_number`, `status`, `teller`, `client_type`, `created_at`, `called_at`, `served_at`, `skip_count`, `no_show`, `recall_count`, `kiosk_ip`) VALUES
(1, 1, 'serving', 'teller_1', 'Regular', '2026-11-05 12:03:58', NULL, NULL, 0, 0, 1, '::1'),
(2, 2, 'waiting', NULL, 'Regular', '2026-11-05 14:10:35', NULL, NULL, 0, 0, 0, '::1'),
(3, 3, 'waiting', NULL, 'Regular', '2026-11-05 14:33:08', NULL, NULL, 0, 0, 0, '::1'),
(4, 4, 'waiting', NULL, 'Regular', '2026-11-05 14:33:13', NULL, NULL, 0, 0, 0, '::1'),
(5, 5, 'waiting', NULL, 'Regular', '2026-11-05 14:33:37', NULL, NULL, 0, 0, 0, '::1'),
(6, 6, 'served', 'teller_1', 'Priority', '2026-11-05 14:33:44', '2026-11-05 14:59:15', '2026-11-05 14:59:48', 0, 0, 1, '::1'),
(7, 7, 'waiting', NULL, 'New Accounts', '2026-11-05 14:34:00', NULL, NULL, 0, 0, 0, '::1'),
(8, 8, 'waiting', NULL, 'Regular', '2026-11-05 16:28:38', NULL, NULL, 0, 0, 0, '::1'),
(9, 9, 'waiting', NULL, 'Regular', '2026-11-05 16:28:52', NULL, NULL, 0, 0, 0, '::1'),
(10, 10, 'waiting', NULL, 'Regular', '2026-11-05 16:29:00', NULL, NULL, 0, 0, 0, '::1'),
(11, 11, 'waiting', NULL, 'Regular', '2026-11-05 16:40:05', NULL, NULL, 0, 0, 0, '::1'),
(12, 12, 'waiting', NULL, 'Regular', '2026-11-05 16:40:15', NULL, NULL, 0, 0, 0, '::1'),
(13, 13, 'waiting', NULL, 'Regular', '2026-11-05 16:40:31', NULL, NULL, 0, 0, 0, '::1'),
(14, 14, 'waiting', NULL, 'Regular', '2026-11-05 16:41:11', NULL, NULL, 0, 0, 0, '::1'),
(15, 15, 'waiting', NULL, 'Regular', '2026-11-05 16:41:57', NULL, NULL, 0, 0, 0, '::1'),
(16, 16, 'waiting', NULL, 'Regular', '2026-11-05 16:42:13', NULL, NULL, 0, 0, 0, '::1'),
(17, 17, 'waiting', NULL, 'Regular', '2026-11-05 16:42:52', NULL, NULL, 0, 0, 0, '::1'),
(18, 18, 'waiting', NULL, 'Regular', '2026-11-05 16:43:07', NULL, NULL, 0, 0, 0, '::1'),
(19, 19, 'waiting', NULL, 'Regular', '2026-11-05 16:43:20', NULL, NULL, 0, 0, 0, '::1'),
(20, 20, 'waiting', NULL, 'Regular', '2026-11-05 16:43:30', NULL, NULL, 0, 0, 0, '::1'),
(21, 21, 'waiting', NULL, 'Regular', '2026-11-05 16:43:50', NULL, NULL, 0, 0, 0, '::1'),
(22, 22, 'waiting', NULL, 'Regular', '2026-11-05 16:46:37', NULL, NULL, 0, 0, 0, '::1'),
(23, 23, 'waiting', NULL, 'Regular', '2026-11-05 16:46:43', NULL, NULL, 0, 0, 0, '::1'),
(24, 24, 'waiting', NULL, 'Regular', '2026-11-05 16:46:51', NULL, NULL, 0, 0, 0, '::1'),
(25, 25, 'waiting', NULL, 'Regular', '2026-11-05 16:47:11', NULL, NULL, 0, 0, 0, '::1'),
(26, 26, 'waiting', NULL, 'Regular', '2026-11-05 16:47:34', NULL, NULL, 0, 0, 0, '::1'),
(27, 27, 'waiting', NULL, 'Regular', '2026-11-05 16:47:56', NULL, NULL, 0, 0, 0, '::1'),
(28, 28, 'waiting', NULL, 'Regular', '2026-11-05 16:48:11', NULL, NULL, 0, 0, 0, '::1'),
(29, 29, 'waiting', NULL, 'Regular', '2026-11-05 16:48:29', NULL, NULL, 0, 0, 0, '::1'),
(30, 30, 'waiting', NULL, 'Regular', '2026-11-05 16:48:50', NULL, NULL, 0, 0, 0, '::1'),
(31, 31, 'waiting', NULL, 'Regular', '2026-11-05 16:49:35', NULL, NULL, 0, 0, 0, '::1'),
(32, 32, 'waiting', NULL, 'Regular', '2026-11-05 16:49:47', NULL, NULL, 0, 0, 0, '::1'),
(33, 33, 'waiting', NULL, 'Regular', '2026-11-05 16:50:05', NULL, NULL, 0, 0, 0, '::1'),
(34, 34, 'waiting', NULL, 'Regular', '2026-11-05 16:50:18', NULL, NULL, 0, 0, 0, '::1'),
(35, 35, 'waiting', NULL, 'Regular', '2026-11-05 16:50:52', NULL, NULL, 0, 0, 0, '::1'),
(36, 36, 'waiting', NULL, 'Regular', '2026-11-05 16:51:05', NULL, NULL, 0, 0, 0, '::1'),
(37, 37, 'waiting', NULL, 'Regular', '2026-11-05 16:51:39', NULL, NULL, 0, 0, 0, '::1'),
(38, 38, 'waiting', NULL, 'Regular', '2026-11-05 16:52:11', NULL, NULL, 0, 0, 0, '::1'),
(39, 39, 'waiting', NULL, 'Regular', '2026-11-05 16:52:36', NULL, NULL, 0, 0, 0, '::1'),
(40, 40, 'waiting', NULL, 'Regular', '2026-11-05 16:54:56', NULL, NULL, 0, 0, 0, '::1'),
(41, 41, 'waiting', NULL, 'Regular', '2026-11-05 16:55:05', NULL, NULL, 0, 0, 0, '::1'),
(42, 42, 'waiting', NULL, 'Regular', '2026-11-05 16:55:29', NULL, NULL, 0, 0, 0, '::1'),
(43, 43, 'waiting', NULL, 'Regular', '2026-11-05 17:09:01', NULL, NULL, 0, 0, 0, '::1'),
(44, 44, 'waiting', NULL, 'Regular', '2026-11-05 17:09:41', NULL, NULL, 0, 0, 0, '::1'),
(45, 45, 'waiting', NULL, 'Regular', '2026-11-05 17:15:30', NULL, NULL, 0, 0, 0, '::1'),
(46, 46, 'waiting', NULL, 'Regular', '2026-11-05 17:18:10', NULL, NULL, 0, 0, 0, '::1'),
(47, 47, 'waiting', NULL, 'Regular', '2026-11-05 17:18:33', NULL, NULL, 0, 0, 0, '::1'),
(48, 48, 'waiting', NULL, 'Regular', '2026-11-05 17:18:47', NULL, NULL, 0, 0, 0, '::1'),
(49, 49, 'waiting', NULL, 'Regular', '2026-11-05 17:20:09', NULL, NULL, 0, 0, 0, '::1'),
(50, 50, 'waiting', NULL, 'Regular', '2026-11-05 17:26:04', NULL, NULL, 0, 0, 0, '::1'),
(51, 51, 'waiting', NULL, 'Regular', '2026-11-05 17:28:46', NULL, NULL, 0, 0, 0, '::1');

-- --------------------------------------------------------

--
-- Table structure for table `queue_history`
--

CREATE TABLE `queue_history` (
  `id` int(11) NOT NULL,
  `ticket_date` date NOT NULL,
  `customer_number` int(11) NOT NULL,
  `client_type` varchar(20) NOT NULL,
  `teller` varchar(50) DEFAULT NULL,
  `status` enum('waiting','serving','served') NOT NULL,
  `no_show` tinyint(1) NOT NULL DEFAULT 0,
  `skip_count` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `slip_type` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `called_at` datetime DEFAULT NULL,
  `served_at` datetime DEFAULT NULL,
  `archived_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `queue_history`
--

INSERT INTO `queue_history` (`id`, `ticket_date`, `customer_number`, `client_type`, `teller`, `status`, `no_show`, `skip_count`, `slip_type`, `created_at`, `called_at`, `served_at`, `archived_at`) VALUES
(1, '2026-02-27', 1, 'Regular', 'teller_1', 'served', 0, 0, NULL, '2026-02-27 14:16:31', NULL, NULL, '2026-11-05 11:55:59'),
(2, '2026-02-27', 2, 'Regular', 'teller_1', 'served', 0, 0, NULL, '2026-02-27 14:16:33', NULL, NULL, '2026-11-05 11:55:59'),
(3, '2026-03-30', 3, 'Regular', 'teller_7', 'served', 0, 0, NULL, '2026-03-30 16:30:40', NULL, NULL, '2026-11-05 11:55:59'),
(4, '2026-10-09', 4, 'Regular', 'teller_1', 'served', 0, 0, NULL, '2026-10-09 11:53:36', NULL, NULL, '2026-11-05 11:55:59'),
(5, '2026-10-26', 5, 'Regular', 'teller_2', 'served', 0, 0, NULL, '2026-10-26 08:32:49', NULL, NULL, '2026-11-05 11:55:59'),
(6, '2026-10-26', 6, 'Regular', 'teller_1', 'served', 0, 0, NULL, '2026-10-26 08:47:32', NULL, NULL, '2026-11-05 11:55:59'),
(7, '2026-03-27', 7, 'Other Services', 'teller_1', 'served', 0, 0, NULL, '2026-03-27 14:17:13', NULL, NULL, '2026-11-05 11:55:59'),
(8, '2026-03-27', 8, 'Regular', 'teller_7', 'served', 0, 0, NULL, '2026-03-27 14:18:40', NULL, NULL, '2026-11-05 11:55:59'),
(9, '2026-03-27', 9, 'New Accounts', 'teller_7', 'served', 0, 0, NULL, '2026-03-27 14:18:09', NULL, NULL, '2026-11-05 11:55:59'),
(10, '2026-10-26', 10, 'Regular', 'teller_2', 'served', 0, 0, NULL, '2026-10-26 08:48:20', NULL, NULL, '2026-11-05 11:55:59'),
(11, '2026-11-03', 11, 'Regular', 'teller_2', 'served', 1, 0, NULL, '2026-11-03 11:31:33', '2026-11-03 11:46:39', '2026-11-03 11:46:52', '2026-11-05 11:55:59'),
(12, '2026-11-03', 12, 'Regular', 'teller_2', 'served', 0, 0, 'cash_deposit', '2026-11-03 11:31:33', '2026-11-03 11:47:10', '2026-11-03 11:47:10', '2026-11-05 11:55:59'),
(13, '2026-10-26', 13, 'Priority', 'teller_2', 'served', 0, 0, 'check_deposit', '2026-10-26 08:32:09', NULL, NULL, '2026-11-05 11:55:59'),
(14, '2026-11-03', 14, 'Regular', 'teller_2', 'serving', 0, 0, 'cash_deposit', '2026-11-03 11:31:33', NULL, NULL, '2026-11-05 11:55:59'),
(15, '2026-11-03', 15, 'Regular', NULL, 'waiting', 0, 1, NULL, '2026-11-03 11:31:33', NULL, NULL, '2026-11-05 11:55:59'),
(16, '2026-11-03', 16, 'Regular', 'teller_1', 'served', 0, 0, NULL, '2026-11-03 11:31:33', '2026-11-03 11:49:16', '2026-11-03 11:50:56', '2026-11-05 11:55:59'),
(17, '2026-11-03', 17, 'Regular', NULL, 'waiting', 0, 1, 'cash_deposit', '2026-11-03 11:31:33', NULL, NULL, '2026-11-05 11:55:59'),
(18, '2026-11-03', 18, 'Regular', NULL, 'waiting', 0, 0, NULL, '2026-11-03 11:33:33', NULL, NULL, '2026-11-05 11:55:59'),
(19, '2026-11-03', 19, 'Other Services', NULL, 'waiting', 0, 0, NULL, '2026-11-03 11:37:10', NULL, NULL, '2026-11-05 11:55:59'),
(20, '2026-11-03', 20, 'Regular', NULL, 'waiting', 0, 0, NULL, '2026-11-03 11:51:50', NULL, NULL, '2026-11-05 11:55:59'),
(21, '2026-11-03', 21, 'Regular', NULL, 'waiting', 0, 0, NULL, '2026-11-03 11:52:00', NULL, NULL, '2026-11-05 11:55:59');

-- --------------------------------------------------------

--
-- Table structure for table `queue_slips`
--

CREATE TABLE `queue_slips` (
  `id` int(11) NOT NULL,
  `queue_id` int(11) NOT NULL,
  `slip_type` enum('cash_deposit','withdrawal','check_deposit','ecpay') NOT NULL,
  `slip_data` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','employee','developer') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `session_token` varchar(255) DEFAULT NULL,
  `last_seen` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`, `session_token`, `last_seen`) VALUES
(2, 'csc', '$2y$10$XtlWYNungykXXg7Ar4oGvOKRjsnuYDqbkHNrgVzuqlZdYGpyllzTO', 'admin', '2025-01-10 02:08:37', '03c687144963d768edd6304ab8b4e542b2a55d0794720ce88fce36a676a6ff62', '2026-11-05 13:56:34'),
(16, 'teller_6', '654321', 'employee', '2025-01-20 02:09:33', NULL, NULL),
(18, 'teller_2', '$2y$10$j5pQY0knP9/CCPvUWDlVtOp1sh8LwA8e.wpTetNPFZRCppWFXPmt6', 'employee', '2025-01-20 06:02:09', NULL, NULL),
(19, 'teller_3', '654321', 'employee', '2025-01-20 06:02:09', NULL, NULL),
(20, 'teller_4', '654321', 'employee', '2025-01-20 06:02:09', NULL, NULL),
(21, 'teller_5', '654321', 'employee', '2025-01-20 06:02:09', NULL, NULL),
(22, 'teller_1', '$2y$10$AY69JziE5gwLi1jqh6Ud4.lh2WbN7h1TBe.MiXfZ6jUpW3AXgNrpW', 'employee', '2025-02-11 00:35:42', NULL, NULL),
(23, 'teller_7', '654321', 'employee', '2026-02-26 10:09:50', NULL, NULL),
(24, 'jmb', '$2y$10$QPN7IhDusGnwkyeCgfCdUe7qEGP5D4qdOvbQUXt3QhWa1YN3206by', 'developer', '2026-11-04 03:02:32', '1f2f8a1a590f4e6729e77b46a9ff6e9ed44d2c555886738457e2de954b6e449d', '2026-11-05 14:57:56');

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `video_path` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `uploaded_at` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `videos`
--

INSERT INTO `videos` (`id`, `video_path`, `filename`, `uploaded_at`) VALUES
(56, 'uploads/video_6aec2908cc3fc7.71374917.mp4', 'video_69b770b5eb1c42.05222558.mp4', '2026-11-05 14:58:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ip_time` (`ip`,`attempted_at`),
  ADD KEY `user_time` (`username`,`attempted_at`);

--
-- Indexes for table `queue`
--
ALTER TABLE `queue`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `queue_history`
--
ALTER TABLE `queue_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_date` (`ticket_date`),
  ADD KEY `teller` (`teller`);

--
-- Indexes for table `queue_slips`
--
ALTER TABLE `queue_slips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `queue_id` (`queue_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `queue`
--
ALTER TABLE `queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `queue_history`
--
ALTER TABLE `queue_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `queue_slips`
--
ALTER TABLE `queue_slips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `queue_slips`
--
ALTER TABLE `queue_slips`
  ADD CONSTRAINT `queue_slips_ibfk_1` FOREIGN KEY (`queue_id`) REFERENCES `queue` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
