-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 27, 2026 at 05:40 PM
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
-- Database: `task_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `approvals`
--

CREATE TABLE `approvals` (
  `id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `notification` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','half_day') DEFAULT 'absent',
  `total_work_seconds` int(11) DEFAULT 0 COMMENT 'Total seconds worked',
  `formatted_time` time DEFAULT '00:00:00',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `task_id`, `date`, `status`, `total_work_seconds`, `formatted_time`, `created_at`, `updated_at`) VALUES
(1, 7, 1, '2025-12-15', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(2, 7, 1, '2025-12-16', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(3, 7, 1, '2025-12-17', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(4, 7, 1, '2025-12-18', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(5, 7, 1, '2025-12-19', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(6, 7, 1, '2025-12-22', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(7, 7, 1, '2025-12-23', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(8, 7, 1, '2025-12-24', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(9, 7, 1, '2025-12-25', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(10, 7, 1, '2025-12-26', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(11, 7, 1, '2025-12-29', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(12, 7, 1, '2025-12-30', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(13, 7, 1, '2025-12-31', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(14, 7, 1, '2026-01-01', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(15, 7, 1, '2026-01-02', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(16, 7, 1, '2026-01-05', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(17, 7, 1, '2026-01-06', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(18, 7, 1, '2026-01-07', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(19, 7, 1, '2026-01-08', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(20, 7, 1, '2026-01-09', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(21, 7, 1, '2026-01-12', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(22, 7, 1, '2026-01-13', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(23, 7, 1, '2026-01-14', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(24, 7, 1, '2026-01-15', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(25, 7, 1, '2026-01-16', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(26, 7, 1, '2026-01-19', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(27, 7, 1, '2026-01-20', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(28, 7, 1, '2026-01-21', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(29, 7, 1, '2026-01-22', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(30, 7, 1, '2026-01-23', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(31, 7, 1, '2026-01-26', 'absent', 0, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 15:11:57'),
(32, 7, 1, '2026-01-27', 'absent', 4913, '00:00:00', '2026-01-27 15:11:57', '2026-01-27 16:33:50');

-- --------------------------------------------------------

--
-- Table structure for table `certificate`
--

CREATE TABLE `certificate` (
  `id` int(11) NOT NULL,
  `intern_id` int(11) NOT NULL,
  `approve_status` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `certificate`
--

INSERT INTO `certificate` (`id`, `intern_id`, `approve_status`, `created_at`) VALUES
(1, 3, 0, '2025-12-08 15:06:49'),
(2, 4, 0, '2025-12-08 15:11:14'),
(3, 5, 0, '2025-12-08 15:12:18'),
(4, 6, 0, '2025-12-12 15:39:46'),
(5, 7, 0, '2025-12-13 19:06:56'),
(6, 8, 0, '2025-12-19 06:33:45'),
(7, 9, 0, '2025-12-25 13:20:59'),
(8, 11, 0, '2025-12-25 13:26:12'),
(9, 12, 0, '2025-12-25 14:30:13'),
(10, 14, 0, '2025-12-26 05:22:49'),
(11, 15, 0, '2025-12-26 05:23:46'),
(12, 16, 0, '2025-12-29 05:33:43'),
(13, 17, 0, '2025-12-30 14:21:02'),
(14, 18, 0, '2025-12-30 14:24:53'),
(15, 19, 0, '2025-12-31 14:04:15'),
(16, 20, 0, '2026-01-07 06:08:37'),
(17, 22, 0, '2026-01-14 17:14:13'),
(18, 25, 0, '2026-01-25 09:40:52'),
(19, 26, 0, '2026-01-25 09:45:33'),
(20, 27, 0, '2026-01-25 09:55:09'),
(21, 28, 0, '2026-01-25 10:02:46'),
(22, 29, 0, '2026-01-25 10:11:11'),
(23, 30, 0, '2026-01-25 10:22:04'),
(24, 31, 0, '2026-01-25 10:24:32'),
(25, 32, 0, '2026-01-25 10:35:33'),
(26, 33, 0, '2026-01-25 10:40:04'),
(27, 34, 0, '2026-01-25 10:42:14'),
(28, 35, 0, '2026-01-25 10:53:14'),
(29, 36, 0, '2026-01-25 10:54:11'),
(30, 37, 0, '2026-01-25 14:42:51'),
(31, 38, 0, '2026-01-25 17:12:41'),
(32, 39, 0, '2026-01-25 17:15:33'),
(33, 40, 0, '2026-01-25 17:27:07'),
(34, 41, 0, '2026-01-25 17:35:07'),
(35, 43, 0, '2026-01-25 17:40:01'),
(36, 44, 0, '2026-01-25 17:44:10'),
(37, 45, 0, '2026-01-25 17:54:23'),
(38, 46, 0, '2026-01-25 17:57:10'),
(39, 47, 0, '2026-01-25 18:19:03'),
(40, 48, 0, '2026-01-25 18:37:33'),
(41, 49, 0, '2026-01-26 15:20:45');

-- --------------------------------------------------------

--
-- Table structure for table `email_queue`
--

CREATE TABLE `email_queue` (
  `id` int(11) NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `to_name` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT 'Welcome to DawoodTech NextGen',
  `template` enum('welcome_offer') DEFAULT 'welcome_offer',
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `error` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `sent_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_queue`
--

INSERT INTO `email_queue` (`id`, `to_email`, `to_name`, `subject`, `template`, `data`, `status`, `attempts`, `error`, `created_at`, `sent_at`) VALUES
(17, 'chaudharyiqra105@gmail.com', 'Iqra Arshad', 'Welcome to DawoodTech NextGen', 'welcome_offer', '{\"name\": \"Iqra Arshad\", \"email\": \"chaudharyiqra105@gmail.com\", \"tech_id\": 5, \"password\": \"RHaf&^^moc9C\", \"tech_name\": \"Graphic Design\"}', 'pending', 0, NULL, '2026-01-14 22:14:13', NULL),
(18, 'qamargill4227@gmail.com', 'Qamar Naveed', 'Welcome to DawoodTech NextGen', 'welcome_offer', '{\"name\": \"Qamar Naveed\", \"email\": \"qamargill4227@gmail.com\", \"password\": \"+CDD%L&5lBkV\", \"tech_name\": \"MERN Stack\"}', 'pending', 0, NULL, '2026-01-25 14:40:52', NULL),
(19, 'qamargill4227@gmail.com', 'Qamar Naveed', 'Welcome to DawoodTech NextGen', 'welcome_offer', '{\"name\": \"Qamar Naveed\", \"email\": \"qamargill4227@gmail.com\", \"password\": \"cN1>p[^e3vVj\", \"tech_name\": \"MERN Stack\"}', 'pending', 0, NULL, '2026-01-25 14:45:33', NULL),
(20, 'qamargill4227@gmail.com', 'Qamar Naveed', 'Welcome to DawoodTech NextGen', 'welcome_offer', '{\"name\": \"Qamar Naveed\", \"email\": \"qamargill4227@gmail.com\", \"password\": \"4i#I[>MqqW](\", \"tech_name\": \"MERN Stack\"}', 'pending', 0, NULL, '2026-01-25 14:55:09', NULL),
(21, 'qamargill4227@gmail.com', 'Qamar Naveed', 'Welcome to DawoodTech NextGen', 'welcome_offer', '{\"name\": \"Qamar Naveed\", \"email\": \"qamargill4227@gmail.com\", \"password\": \"UWG6i<>dWa(x\", \"tech_name\": \"MERN Stack\"}', 'pending', 0, NULL, '2026-01-25 15:02:46', NULL),
(22, 'qamargill4227@gmail.com', 'Qamar Naveed', 'Welcome to DawoodTech NextGen', 'welcome_offer', '{\"name\": \"Qamar Naveed\", \"email\": \"qamargill4227@gmail.com\", \"password\": \"s$#b!2nvlZQC\", \"tech_name\": \"MERN Stack\"}', 'pending', 0, NULL, '2026-01-25 15:11:11', NULL),
(23, 'qamargill4227@gmail.com', 'Qamar Naveed', 'Welcome to DawoodTech NextGen', 'welcome_offer', '{\"name\": \"Qamar Naveed\", \"email\": \"qamargill4227@gmail.com\", \"password\": \"([fL?HsiUe0k\", \"tech_name\": \"MERN Stack\"}', 'pending', 0, NULL, '2026-01-25 15:22:04', NULL),
(24, 'qamargill4227@gmail.com', 'Qamar Naveed', 'Welcome to DawoodTech NextGen', 'welcome_offer', '{\"name\": \"Qamar Naveed\", \"email\": \"qamargill4227@gmail.com\", \"password\": \"Ddy@4$iXjcI#\", \"tech_name\": \"MERN Stack\"}', 'pending', 0, NULL, '2026-01-25 15:24:32', NULL),
(25, 'qamargill4227@gmail.com', 'Qamar Naveed', 'Welcome to DawoodTech NextGen', 'welcome_offer', '{\"name\": \"Qamar Naveed\", \"email\": \"qamargill4227@gmail.com\", \"password\": \"d_qVtZGEi&2_\", \"tech_name\": \"MERN Stack\"}', 'pending', 0, NULL, '2026-01-25 15:35:33', NULL),
(26, 'arimran1058@gmail.com', 'Qamar Naveed', 'Welcome to DawoodTech NextGen', 'welcome_offer', '{\"name\": \"Qamar Naveed\", \"email\": \"arimran1058@gmail.com\", \"password\": \"]C&bOr3AbH25\", \"tech_name\": \"MERN Stack\"}', 'pending', 0, NULL, '2026-01-25 15:40:04', NULL),
(27, 'arimran1058@gmail.com', 'Qamar Naveed', 'Welcome to DawoodTech NextGen', 'welcome_offer', '{\"name\": \"Qamar Naveed\", \"email\": \"arimran1058@gmail.com\", \"password\": \"#r2o%5=0L+S^\", \"tech_name\": \"MERN Stack\"}', 'pending', 0, NULL, '2026-01-25 15:42:14', NULL),
(28, 'arimran1058@gmail.com', 'Qamar Naveed', 'Welcome to DawoodTech NextGen', 'welcome_offer', '{\"name\": \"Qamar Naveed\", \"email\": \"arimran1058@gmail.com\", \"password\": \"3[sRyBv2Sze%\", \"tech_name\": \"MERN Stack\", \"mbl_number\": \"923061061544\"}', 'pending', 0, NULL, '2026-01-25 15:53:14', NULL),
(29, 'arimran1058@gmail.com', 'Qamar Naveed', 'Welcome to DawoodTech NextGen', 'welcome_offer', '{\"name\": \"Qamar Naveed\", \"email\": \"arimran1058@gmail.com\", \"password\": \"}b>k0NwuYY?Y\", \"tech_name\": \"MERN Stack\", \"mbl_number\": \"923061061544\"}', 'pending', 0, NULL, '2026-01-25 15:54:11', NULL),
(30, 'arimran1058@gmail.com', 'Qamar Naveed', 'Welcome to DawoodTech NextGen', 'welcome_offer', '{\"name\": \"Qamar Naveed\", \"email\": \"arimran1058@gmail.com\", \"password\": \"T&CFrrv89j#@\", \"tech_name\": \"MERN Stack\", \"mbl_number\": \"923061061544\"}', 'pending', 0, NULL, '2026-01-25 19:42:51', NULL),
(31, 'arimran1058@gmail.com', 'Qamar Naveed', 'Welcome to DawoodTech NextGen', 'welcome_offer', '{\"name\": \"Qamar Naveed\", \"email\": \"arimran1058@gmail.com\", \"password\": \">[p*7M11twBx\", \"tech_name\": \"MERN Stack\", \"mbl_number\": \"+923176349954\"}', 'pending', 0, NULL, '2026-01-25 22:12:41', NULL),
(32, 'arimran1058@gmail.com', 'Qamar Naveed', 'Welcome to DawoodTech NextGen', 'welcome_offer', '{\"name\": \"Qamar Naveed\", \"email\": \"arimran1058@gmail.com\", \"password\": \"LleS%I<K^p0y\", \"tech_name\": \"MERN Stack\", \"mbl_number\": \"+923176349954\"}', 'pending', 0, NULL, '2026-01-25 22:15:33', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL,
  `mbl_number` varchar(255) NOT NULL,
  `city` varchar(255) DEFAULT NULL,
  `cnic` varchar(255) NOT NULL,
  `technology_id` bigint(20) NOT NULL,
  `internship_type` int(11) NOT NULL DEFAULT 0,
  `experience` int(11) NOT NULL DEFAULT 0,
  `status` enum('new','contact','interview','hire','rejected') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `interview_start` datetime DEFAULT NULL,
  `interview_end` datetime DEFAULT NULL,
  `platform` enum('Google Meet','Physical','Zoom') DEFAULT NULL,
  `Remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registrations`
--

INSERT INTO `registrations` (`id`, `name`, `email`, `country`, `mbl_number`, `city`, `cnic`, `technology_id`, `internship_type`, `experience`, `status`, `created_at`, `updated_at`, `interview_start`, `interview_end`, `platform`, `Remarks`) VALUES
(1, 'Qamar Naveed', 'qamargill427@gmail.com', 'Pakistan', '+9203061061544', 'Faisalabad', '33303-8963605-1', 3, 0, 2, 'rejected', '2025-12-27 19:14:20', '2026-01-26 19:08:20', '2026-01-26 16:30:00', '2026-01-26 17:30:00', '', ''),
(2, 'Manahil Jamil', 'manahil.jamilh@gmail.com', 'Pakistan', '+923351224929', 'Karachi', '42201-8750778-6', 4, 0, 0, 'rejected', '2025-12-27 23:26:00', '2026-01-19 22:42:42', NULL, NULL, NULL, NULL),
(3, 'Isha Javed', 'ahsibajwa001@gmail.com', 'Pakistan', '+923211091394', 'Hyderabad', '44203-8570466-4', 2, 0, 1, 'rejected', '2025-12-28 12:10:17', '2026-01-19 22:42:33', NULL, NULL, NULL, NULL),
(4, 'Abdullah Akram Gondal', 'abdullahakramgondal7@gmail.com', 'Pakistan', '+923308115502', 'Lahore', '35202-3164139-3', 4, 0, 0, 'rejected', '2025-12-28 17:55:50', '2026-01-19 22:43:06', NULL, NULL, NULL, NULL),
(5, 'Abdul  Hannan', 'abdulhananjameel@gmail.com', 'Pakistan', '+923000791579', 'Multan', '36302-8201431-8', 3, 0, 1, 'rejected', '2025-12-28 18:58:13', '2026-01-19 22:42:21', NULL, NULL, NULL, NULL),
(6, 'Muhammad Amir Waheed', 'amirwaaheed@gmail.com', 'Pakistan', '+923118950491', 'Bagh Ajk', '82102-3180898-3', 2, 0, 1, 'rejected', '2025-12-29 10:34:22', '2025-12-29 11:00:10', NULL, NULL, NULL, NULL),
(7, 'Hussnain Bukhari', 'hussnainbukhari58@gmail.com', 'Pakistan', '+923146793354', 'SADIQ ABAD', '31304-1899927-5', 3, 0, 1, 'contact', '2025-12-29 18:19:18', '2025-12-30 12:42:56', NULL, NULL, NULL, NULL),
(8, 'Zunaira Nawaz', 'zunairanawaz777@gmail.com', 'Pakistan', '+923402080031', 'Lahore', '34604-0698380-4', 5, 0, 3, 'contact', '2025-12-30 02:58:40', '2025-12-30 12:43:03', NULL, NULL, NULL, NULL),
(9, 'Muhammad Ishaq', 'muhammadishaq.dev@gmail.com', 'Pakistan', '+923365140958', 'Haripur', '13101-6800123-9', 3, 0, 0, 'contact', '2025-12-30 14:16:49', '2025-12-30 18:27:34', NULL, NULL, NULL, NULL),
(10, 'Shivani ', 'shivanibatra978@gmail.com', 'Pakistan', '+923123269180', 'Ghotki', '45103-9584292-2', 9, 0, 0, 'contact', '2025-12-30 15:42:59', '2025-12-30 18:27:40', NULL, NULL, NULL, NULL),
(11, 'Teesha Mahesh ', 'preetmanitisha@gmail.com', 'Pakistan', '+923063218695', 'Ghokti ', '45102-5793298-8', 4, 0, 0, 'contact', '2025-12-30 15:45:38', '2025-12-30 18:27:45', NULL, NULL, NULL, NULL),
(12, 'Sher Dad', 'sherdadboshti9559@gmail.com', 'Pakistan', '+923175491670', 'Rawalpindi', '37405-6140176-9', 8, 0, 0, 'contact', '2025-12-30 16:57:58', '2025-12-30 18:27:50', NULL, NULL, NULL, NULL),
(13, 'Amna Ashiq', 'amnaashiq5577@gmail.com', 'Pakistan', '+923144137175', 'VEHARI', '36603-0512694-0', 8, 0, 1, 'contact', '2025-12-30 18:56:45', '2025-12-30 19:06:34', NULL, NULL, NULL, NULL),
(14, 'abdul moeed', 'Abdmoeed686@gmail.com', 'Pakistan', '+923040813805', 'Jahanian', '36101-7236670-7', 2, 0, 0, 'contact', '2025-12-30 23:01:46', '2025-12-31 10:22:53', NULL, NULL, NULL, NULL),
(15, 'Munazza Akhlaq ', 'munazzamunazza229@gmail.com', 'Pakistan', '+923129997270', 'Bagh', '54400-1042580-4', 10, 0, 0, 'hire', '2025-12-31 10:47:17', '2025-12-31 10:52:22', NULL, NULL, NULL, NULL),
(16, 'Saeed Ahmed', 'saeedahmednasar77@gmail.com', 'Pakistan', '+923188083605', 'Lahore', '56302-5276376-9', 2, 0, 2, 'contact', '2025-12-31 10:57:37', '2025-12-31 11:00:37', NULL, NULL, NULL, NULL),
(17, 'Abdul Rehman ', 'abdulrehmaniqbal588@gmail.com', 'Pakistan', '+923366884835', 'Karachi', '42101-9176559-7', 4, 0, 2, 'contact', '2025-12-31 15:15:59', '2026-01-01 10:04:36', NULL, NULL, NULL, NULL),
(18, 'Syeda Yashfeen Zehra Zaidi', 'yashfeen.s3018@gmail.com', 'Pakistan', '+923032887827', 'Karachi', '42501-7165768-0', 4, 0, 1, 'contact', '2026-01-01 00:30:29', '2026-01-01 10:04:40', NULL, NULL, NULL, NULL),
(20, 'Muhammad Rizwan', 'rizwan01107@gmail.com', 'Pakistan', '+923137033662', 'LAYYAH', '32202-1098356-3', 2, 0, 0, 'contact', '2026-01-01 14:00:40', '2026-01-02 10:41:48', NULL, NULL, NULL, NULL),
(23, 'Eshaal Malik', 'eshaalmzaa@gmail.com', 'Pakistan', '+923099878009', 'Karachi', '42201-1262137-4', 9, 0, 0, 'contact', '2026-01-01 22:38:59', '2026-01-02 10:41:43', NULL, NULL, NULL, NULL),
(24, 'Hamza Asad', 'hamidzehri42@gmail.com', 'Pakistan', '+923357981318', 'Karachi', '51401-2575358-1', 2, 0, 0, 'contact', '2026-01-02 14:46:45', '2026-01-05 11:08:17', NULL, NULL, NULL, NULL),
(25, 'Salman sabir', 'salman.sabir7665@gmail.com', 'Pakistan', '+923030375913', 'Lahore ', '32304-5912800-9', 3, 0, 1, 'contact', '2026-01-02 14:48:06', '2026-01-05 11:06:40', NULL, NULL, NULL, NULL),
(26, 'Mian Hamza Anwar', 'mianhamzaanwar12@gmail.com', 'Pakistan', '+923194765320', 'Lahore', '31301-4695376-3', 8, 0, 1, 'contact', '2026-01-02 14:50:11', '2026-01-05 11:06:34', NULL, NULL, NULL, NULL),
(27, 'Vishal Dewani ', 'vishaldewani500@gmail.com', 'Pakistan', '+923460941674', 'Hyderabad ', '41306-5565750-9', 3, 0, 0, 'contact', '2026-01-02 14:53:49', '2026-01-05 11:06:25', NULL, NULL, NULL, NULL),
(28, 'Ashad Ahmed', 'ashahmed30200312@gmail.com', 'Pakistan', '+923022492815', 'Karachi', '42101-8609082-1', 3, 0, 0, 'contact', '2026-01-02 14:54:34', '2026-01-05 11:06:17', NULL, NULL, NULL, NULL),
(29, 'Syed M Shees Rafiq', 'sheesshah556@gmail.com', 'Pakistan', '+923265861911', 'Lahore ', '35202-9154170-5', 3, 0, 2, 'contact', '2026-01-02 14:57:32', '2026-01-05 11:06:11', NULL, NULL, NULL, NULL),
(30, 'Kanwal Maqsood', 'kanwalmaqsood1997@gmail.com', 'Pakistan', '+923284196607', 'Sheikhupura ', '35404-8127976-6', 2, 0, 1, 'contact', '2026-01-02 15:25:29', '2026-01-05 11:06:03', NULL, NULL, NULL, NULL),
(31, 'Sanjay', 'sanjaydembra40@gmail.com', 'Pakistan', '+923072102055', 'Karachi', '45102-6808342-3', 5, 0, 0, 'contact', '2026-01-02 15:57:46', '2026-01-05 11:05:56', NULL, NULL, NULL, NULL),
(32, 'Muhammad Ahsaan', 'ahsaanlatif99@gmail.com', 'Pakistan', '+923472558697', 'Karachi', '42101-7681807-9', 3, 0, 0, 'contact', '2026-01-02 16:55:21', '2026-01-05 11:05:50', NULL, NULL, NULL, NULL),
(33, 'Aimen Ahmed', 'aimenahmed567@gmail.com', 'Pakistan', '+923336666113', 'Lahore', '17201-9982970-0', 4, 0, 0, 'contact', '2026-01-02 17:19:54', '2026-01-05 11:05:42', NULL, NULL, NULL, NULL),
(34, 'Muhammad Talha', 'talhaafzaal2004@gmail.com', 'Pakistan', '+923477891501', 'Islamabad ', '34202-7209004-7', 2, 0, 2, 'contact', '2026-01-02 21:20:26', '2026-01-05 11:05:35', NULL, NULL, NULL, NULL),
(35, 'Kabir Khan', 'mohtasimarif25@gmail.com', 'Pakistan', '+923195196684', 'Attock', '14301-9713854-1', 4, 0, 4, 'contact', '2026-01-02 21:54:07', '2026-01-05 11:01:49', NULL, NULL, NULL, NULL),
(36, 'Anmol hindhu', 'anmoldholia12@gmail.com', 'Pakistan', '+923011281533', 'Ghotki', '45104-7291973-8', 8, 0, 0, 'contact', '2026-01-02 22:51:00', '2026-01-05 11:01:43', NULL, NULL, NULL, NULL),
(37, 'Kashish Kukreja', 'kashishds2023@gmail.com', 'Pakistan', '+923012891905', 'KARACHI', '45102-9640746-8', 4, 0, 1, 'contact', '2026-01-03 01:48:23', '2026-01-05 11:01:36', NULL, NULL, NULL, NULL),
(38, 'Saif Ur Rehman Cheema', 'saificheema673@gmail.com', 'Pakistan', '+923328527197', 'Islamabad', '34201-9333023-1', 10, 0, 2, 'contact', '2026-01-03 02:47:09', '2026-01-05 11:01:29', NULL, NULL, NULL, NULL),
(39, 'Danish Jamal', 'babadanish120@gmail.com', 'Pakistan', '+923021564548', 'Karachi Sindh', '43105-2531529-7', 4, 0, 2, 'rejected', '2026-01-03 03:56:07', '2026-01-19 21:07:49', NULL, NULL, NULL, NULL),
(40, 'Bhura lal', 'bh1976054@gmail.com', 'Pakistan', '+9203146086855', 'Karachi ', '44401-4326739-3', 4, 0, 0, 'contact', '2026-01-03 09:22:31', '2026-01-05 11:01:14', NULL, NULL, NULL, NULL),
(41, 'Hassan Rasheed', 'craftycode121@gmail.com', 'Pakistan', '+923337094473', 'Karachi', '43507-3261760-0', 4, 0, 4, 'contact', '2026-01-03 11:22:50', '2026-01-05 11:01:06', NULL, NULL, NULL, NULL),
(42, 'Mumta Bai', 'punjwanimumta@gmail.com', 'Pakistan', '+923362039802', 'Karachi', '45104-3261462-4', 9, 0, 0, 'contact', '2026-01-03 11:46:54', '2026-01-05 11:00:59', NULL, NULL, NULL, NULL),
(43, 'Suleman raza', 'sulemanraza193@gmail.com', 'Pakistan', '+923448620825', 'Sialkot', '34603-1942693-5', 3, 0, 0, 'contact', '2026-01-03 12:36:30', '2026-01-05 11:00:52', NULL, NULL, NULL, NULL),
(44, 'Mehdi Rizvi ', 'smmrr110@gmail.com', 'Pakistan', '+923232769744', 'Karachi', '42101-5077434-7', 9, 0, 0, 'contact', '2026-01-03 15:54:11', '2026-01-05 11:00:40', NULL, NULL, NULL, NULL),
(45, 'Muhammad Arham Ansari', 'muhammadarham3939@gmail.com', 'Pakistan', '+923708423939', 'Hyderabad', '41303-0570949-9', 7, 0, 0, 'contact', '2026-01-03 20:50:24', '2026-01-05 10:56:05', NULL, NULL, NULL, NULL),
(46, 'Ghani Abdul Rehman Khan', 'ghanikhan1014@gmail.com', 'Pakistan', '+923149794228', 'Abbottabad', '13302-5027359-7', 4, 0, 0, 'contact', '2026-01-03 22:11:44', '2026-01-05 10:55:55', NULL, NULL, NULL, NULL),
(47, 'Emaan', 'emaankashif7965@gmail.com', 'Pakistan', '+923014275057', 'Faisalabad', '33102-4497438-6', 4, 0, 0, 'contact', '2026-01-04 18:05:58', '2026-01-05 10:55:48', NULL, NULL, NULL, NULL),
(51, 'Dheeraj raja', 'dheerajpohwani9@gmail.com', 'Pakistan', '+923337725703', 'Karachi', '43203-1827981-7', 6, 0, 0, 'contact', '2026-01-05 03:21:12', '2026-01-05 10:55:42', NULL, NULL, NULL, NULL),
(52, 'Muhammad Arslan', 'arslangee268@gmail.com', 'Pakistan', '+923180400223', 'Toba Tek Singh', '33303-6336036-9', 4, 0, 0, 'contact', '2026-01-05 07:24:02', '2026-01-05 10:55:38', NULL, NULL, NULL, NULL),
(53, 'Ahmad Ali', 'workmy.chd@gmail.com', 'Pakistan', '+923195403624', 'Peshawar', '17101-0383904-9', 4, 0, 2, 'contact', '2026-01-05 08:45:18', '2026-01-05 10:55:29', NULL, NULL, NULL, NULL),
(54, 'Muhammad Hassan', 'm.hassan143743@gmail.com', 'Pakistan', '+923004702797', 'Lahore', '35501-0351546-1', 7, 0, 2, 'contact', '2026-01-05 09:05:07', '2026-01-05 10:55:25', NULL, NULL, NULL, NULL),
(55, 'Muqadas ', 'muqadasakram.13@gmail.com', 'Pakistan', '+923198288490', 'Dadu', '41201-1708773-0', 4, 0, 3, 'contact', '2026-01-05 09:07:51', '2026-01-05 10:55:18', NULL, NULL, NULL, NULL),
(56, 'Fatima Manzoor ', 'fatemahmnzor@gmail.com', 'Pakistan', '+923306877749', 'Multan', '36302-0361297-6', 4, 0, 0, 'hire', '2026-01-05 09:18:19', '2026-01-07 11:42:08', NULL, NULL, NULL, NULL),
(57, 'Muhammad Faizan ', 'faizanejaz862@gmail.com', 'Pakistan', '+923091449304', 'Lahore ', '35202-2086394-9', 4, 0, 2, 'contact', '2026-01-05 09:59:24', '2026-01-05 10:55:07', NULL, NULL, NULL, NULL),
(58, 'ANEELA IQBAL', 'iqbalaneela67@gmail.com', 'Pakistan', '+923058910741', 'isakhel punjab', '38301-0373418-8', 3, 0, 0, 'contact', '2026-01-05 13:34:51', '2026-01-07 12:04:32', NULL, NULL, NULL, NULL),
(59, 'Ume Habiba ', 'makeupbycosmeticstore@gmail.com', 'Pakistan', '+923004022936', 'Multan', '32202-5211355-2', 5, 0, 0, 'contact', '2026-01-05 15:32:59', '2026-01-07 12:04:38', NULL, NULL, NULL, NULL),
(60, 'Parkash', 'parkashhanjharo2020@gmail.com', 'Pakistan', '+923403367789', 'Sukkur ', '44302-0309994-1', 4, 0, 1, 'contact', '2026-01-05 15:44:58', '2026-01-07 12:04:43', NULL, NULL, NULL, NULL),
(61, 'Mahnoor Kalsoom', 'mahnoorkalsoom512@gmail.com', 'Pakistan', '+923341578355', 'islamabad', '61101-9892319-6', 4, 0, 0, 'contact', '2026-01-05 17:09:56', '2026-01-07 12:04:48', NULL, NULL, NULL, NULL),
(62, 'Afeera Anis', 'afeeraanis@gmail.com', 'Pakistan', '+923343461488', 'Karachi', '42101-6344663-6', 4, 0, 1, 'contact', '2026-01-05 21:12:07', '2026-01-07 12:04:52', NULL, NULL, NULL, NULL),
(63, 'Sohail Akhtar ', 'sohailakhtarchanna@gmail.com', 'Pakistan', '+923040586405', 'Mirpur Mathelo ', '45104-2727906-7', 9, 0, 0, 'contact', '2026-01-05 21:52:45', '2026-01-07 12:04:58', NULL, NULL, NULL, NULL),
(64, 'Areeba Tariq', 'areebat1010@gmail.com', 'Pakistan', '+923140316215', 'Karachi', '42101-5206540-2', 4, 0, 0, 'contact', '2026-01-06 02:48:29', '2026-01-07 12:05:02', NULL, NULL, NULL, NULL),
(65, 'Farhan Ilyas', 'farhanilyas1122s@gmail.com', 'Pakistan', '+923456528518', 'Gujranwala', '34402-9813842-7', 3, 0, 0, 'contact', '2026-01-06 21:31:32', '2026-01-07 12:05:06', NULL, NULL, NULL, NULL),
(66, 'Sohaib Ali', 'zebi65871@gmail.com', 'Pakistan', '+923151532458', 'Hazro Attock', '37106-0241171-1', 9, 0, 0, 'contact', '2026-01-07 01:21:18', '2026-01-07 12:05:11', NULL, NULL, NULL, NULL),
(67, 'Amber Khan', 'amberkhan7092@gmail.com', 'Pakistan', '+923022741518', 'Karachi ', '42101-4673637-2', 7, 0, 0, 'contact', '2026-01-07 17:42:49', '2026-01-08 22:05:58', NULL, NULL, NULL, NULL),
(68, 'Haj Wali ', 'hajwalii3@gmail.com', 'Pakistan', '+923485647024', 'Abbottabad ', '71401-3504427-5', 4, 0, 0, 'contact', '2026-01-07 18:42:09', '2026-01-08 22:06:02', NULL, NULL, NULL, NULL),
(69, 'Shafqatullah', 'kshafqatullah2@gmail.com', 'Pakistan', '+923022334974', 'Karachi', '45105-2468603-5', 8, 0, 0, 'contact', '2026-01-07 22:47:06', '2026-01-08 22:06:07', NULL, NULL, NULL, NULL),
(70, 'Areesha kanwal', 'kanwalareesha35@gmail.com', 'Pakistan', '+92132114563', 'karachi', '42201-3621952-8', 2, 0, 0, 'contact', '2026-01-08 12:06:59', '2026-01-08 22:06:10', NULL, NULL, NULL, NULL),
(71, 'Nazish', 'nazishminahilazanahmed@gmail.com', 'Pakistan', '+923042106297', 'Sahiwal', '35404-5766291-0', 7, 0, 0, 'contact', '2026-01-08 18:28:22', '2026-01-08 22:06:17', NULL, NULL, NULL, NULL),
(72, 'Mahek jalbani', 'mahekjalbani304@gmail.com', 'Pakistan', '+923024720808', 'Mirpur Mathelo', '45104-5792044-4', 5, 0, 0, 'contact', '2026-01-08 19:24:20', '2026-01-08 22:06:21', NULL, NULL, NULL, NULL),
(73, 'ume habiba', 'www.habiba911@gmail.com', 'Pakistan', '+923166275438', 'Kamra', '37106-0177968-2', 2, 0, 0, 'contact', '2026-01-08 20:07:43', '2026-01-08 22:06:25', NULL, NULL, NULL, NULL),
(74, 'MUHAMMAD SHAH MEER KHAN', 'kshahmeer2000@gmail.com', 'Pakistan', '+923344254488', 'Lahore', '35201-7067948-3', 2, 0, 0, 'contact', '2026-01-08 23:06:30', '2026-01-09 22:35:00', NULL, NULL, NULL, NULL),
(75, 'Atif Khan', 'atifkhan86302@gmail.com', 'Pakistan', '+923498563703', 'karachi', '71502-1417617-9', 2, 0, 0, 'rejected', '2026-01-09 20:21:41', '2026-01-20 16:31:58', NULL, NULL, NULL, NULL),
(76, 'Muhammad Abdullah', 'malikabdullahh052@gmail.com', 'Pakistan', '+923304147890', 'islamabad', '61101-3041634-5', 8, 0, 0, 'contact', '2026-01-09 21:35:55', '2026-01-09 22:34:53', NULL, NULL, NULL, NULL),
(77, 'Muhammad Qasim Nizam', 'qasimnizam9@gmail.com', 'Pakistan', '+923071464131', 'Fasilabad', '33104-6235169-3', 2, 0, 0, 'contact', '2026-01-09 23:14:03', '2026-01-10 22:49:01', NULL, NULL, NULL, NULL),
(78, 'Muhammad Taha', 'mtahaazmat03@gmail.com', 'Pakistan', '+923112010328', 'karachi', '42101-2938473-1', 4, 0, 0, 'contact', '2026-01-10 00:07:04', '2026-01-10 22:48:55', NULL, NULL, NULL, NULL),
(79, 'Hanza Akhlaq', 'skhanzaakhlaqk64@gmail.com', 'Pakistan', '+923348469311', 'Rawlakot', '82303-4398169-5', 2, 0, 0, 'contact', '2026-01-10 23:08:12', '2026-01-13 22:14:34', NULL, NULL, NULL, NULL),
(80, 'Muhammad Rizwan', 'muhammadrizwan232005@gmail.com', 'Pakistan', '+923051477796', 'Multan', '36303-7934990-5', 2, 0, 0, 'rejected', '2026-01-11 10:07:49', '2026-01-20 16:25:59', NULL, NULL, NULL, NULL),
(81, 'Adina Siddique', 'adinasiddique0307@gmail.com', 'Pakistan', '+923082167086', 'Karachi ', '31203-8198578-2', 5, 0, 0, 'rejected', '2026-01-11 11:49:49', '2026-01-20 16:25:26', NULL, NULL, NULL, NULL),
(82, 'Asad Ali  Rahimoon', 'arahimoon48@gmail.com', 'Pakistan', '+923372510172', 'Karachi', '42601-0367115-7', 7, 0, 0, 'rejected', '2026-01-11 14:03:08', '2026-01-20 16:23:21', NULL, NULL, NULL, NULL),
(85, 'Fatima Ashraf', 'fattimaashraf79@gmail.com', 'Pakistan', '+923175907797', 'TAXILA', '33104-6953436-6', 4, 0, 0, 'rejected', '2026-01-12 13:47:25', '2026-01-19 22:49:07', NULL, NULL, NULL, NULL),
(86, 'Amna Mazhar', 'amna39mazhar@gmail.com', 'Pakistan', '+923338890473', 'Islamabad', '36502-8103937-6', 5, 0, 0, 'contact', '2026-01-12 19:19:13', '2026-01-13 22:15:03', NULL, NULL, NULL, NULL),
(87, 'Khadija Naeem', 'khadijanaeem.1907@gmail.com', 'Pakistan', '+923395401711', 'Rawalpindi', '37405-0243404-6', 3, 0, 0, 'contact', '2026-01-12 23:35:09', '2026-01-13 22:15:07', NULL, NULL, NULL, NULL),
(88, 'Asad Ali', 'asadali99878@gmail.com', 'Pakistan', '+923024544008', 'Kasur', '35102-6564767-9', 7, 0, 0, 'rejected', '2026-01-13 09:49:33', '2026-01-19 22:41:00', NULL, NULL, NULL, NULL),
(90, 'Saad Ali', 'saadalii16598@gmail.com', 'Pakistan', '+923258816466', 'LAHORE', '35202-0814568-7', 9, 0, 0, 'contact', '2026-01-13 13:15:02', '2026-01-13 22:15:16', NULL, NULL, NULL, NULL),
(91, 'Muqaddas Amina Hamid', 'muqaddasamina999@gmail.com', 'Pakistan', '+923119291289', 'Islamabad', '42201-4617323-6', 4, 0, 0, 'rejected', '2026-01-13 18:23:05', '2026-01-19 22:46:27', NULL, NULL, NULL, NULL),
(92, 'Sardar Muhammad Azam Khan', 'fk1924329@gmail.com', 'Pakistan', '+923160368433', 'Sukkur ', '45504-2889408-1', 10, 0, 2, 'rejected', '2026-01-14 15:33:22', '2026-01-19 22:45:52', NULL, NULL, NULL, NULL),
(94, 'Mehwish Parveen ', 'mehwishanwar055@gmail.com', 'Pakistan', '+923281480281', 'Faisalabad', '33100-3022395-6', 10, 0, 0, 'contact', '2026-01-15 12:53:16', '2026-01-15 21:14:47', NULL, NULL, NULL, NULL),
(95, 'Zain Tanveer', 'zaintanveer1632@gmail.com', 'Pakistan', '+9203275061822', 'Rawalpindi', '37405-6953385-9', 4, 0, 0, 'contact', '2026-01-15 19:11:46', '2026-01-15 21:14:41', NULL, NULL, NULL, NULL),
(96, 'Aizaz Muhammad', 'aizazkh0n@gmail.com', 'Pakistan', '+923175656534', 'Sawabi', '16203-0397159-5', 4, 0, 0, 'contact', '2026-01-15 21:46:30', '2026-01-15 22:24:10', NULL, NULL, NULL, NULL),
(97, 'Zeeshan Arif', 'arifzeshan23@gmail.com', 'Pakistan', '+923463322480', 'islamabad', '71301-6451751-7', 2, 0, 0, 'contact', '2026-01-15 22:34:05', '2026-01-18 21:01:11', NULL, NULL, NULL, NULL),
(99, 'Zeeshan Arif', 'alpineauranaturals@gmail.com', 'Pakistan', '+923463322480', 'islamabad', '71301-6451751-7', 2, 0, 0, 'contact', '2026-01-15 22:36:51', '2026-01-18 21:01:17', NULL, NULL, NULL, NULL),
(100, 'iqra arshad ', 'chaudharyiqra105@gmail.com', 'Pakistan', '+923293924485', 'Karachi', '34203-0447257-4', 5, 0, 0, 'hire', '2026-01-16 14:18:12', '2026-01-18 21:01:25', NULL, NULL, NULL, NULL),
(101, 'Mehboob Ali', 'mehboob56ali78@gmail.com', 'Pakistan', '+923166804951', 'Rawalpindi', '37203-5886024-1', 4, 0, 0, 'rejected', '2026-01-18 16:10:23', '2026-01-21 09:45:45', NULL, NULL, NULL, NULL),
(103, 'Tayyaba ', 'jtayyaba570@gmail.com', 'Pakistan', '+923556552644', 'Bagh', '82103-6049460-8', 9, 0, 0, 'contact', '2026-01-18 21:05:11', '2026-01-18 21:20:35', NULL, NULL, NULL, NULL),
(104, 'Faryal Idrees', 'faryalidrees64@gmail.com', 'Pakistan', '+923449163297', 'Wadppaga', '17301-9656303-2', 4, 0, 1, 'rejected', '2026-01-18 23:47:39', '2026-01-19 22:24:56', NULL, NULL, NULL, NULL),
(105, 'Bo Floyd', 'vybu@gmail.com', 'Pakistan', '+92234567890', 'Ipsum id eiusmod re', '12345-6789009-8', 2, 0, 0, 'rejected', '2026-01-19 14:16:08', '2026-01-19 14:17:07', NULL, NULL, NULL, NULL),
(107, 'Asad Ali', 'asadali99790@gmail.com', 'Pakistan', '+923431893874', 'Kasur', '35102-6564767-9', 7, 0, 0, 'contact', '2026-01-19 23:38:15', '2026-01-20 14:37:06', NULL, NULL, NULL, NULL),
(108, 'Mustajab zahra', 'singmeuzzi@gmail.com', 'Pakistan', '+920332121225', 'Quetta', '54401-6585822-2', 7, 0, 0, 'contact', '2026-01-20 00:12:25', '2026-01-20 14:29:21', NULL, NULL, NULL, NULL),
(109, 'Laiba ', 'laibanoor0145@gmail.com', 'Pakistan', '+923080543167', 'Jaranwala', '33104-0396738-8', 9, 0, 0, 'contact', '2026-01-20 12:09:30', '2026-01-20 14:29:16', NULL, NULL, NULL, NULL),
(112, 'Halima sadia', 'halimasadiamunaf@gmail.com', 'Pakistan', '+923367445548', 'Karachi', '42301-0966084-0', 7, 0, 0, 'contact', '2026-01-20 20:17:48', '2026-01-20 22:05:44', NULL, NULL, NULL, NULL),
(113, 'Shifa Shahid', 'shifashahid321@gmail.com', 'Pakistan', '+923156008501', 'Sargodha', '38403-6770071-0', 7, 0, 0, 'contact', '2026-01-21 11:48:50', '2026-01-22 11:38:32', NULL, NULL, NULL, NULL),
(114, 'Mohsin Ali', 'alilaptop393@gmail.com', 'Pakistan', '+923169817913', 'TALAGANG', '37203-7417203-1', 2, 0, 2, 'rejected', '2026-01-21 16:49:25', '2026-01-22 11:38:32', NULL, NULL, NULL, NULL),
(115, 'Usama ', 'hafizusamaahmad321@gmail.com', 'Pakistan', '+923115421583', 'Mardan', '16101-8481826-7', 4, 0, 0, 'contact', '2026-01-21 20:53:03', '2026-01-22 11:38:32', NULL, NULL, NULL, NULL),
(116, 'Sana Ullah ', 'sanaullahrustmani814517@gmail.com', 'Pakistan', '+923357986332', 'Lahore ', '32404-6078236-5', 4, 0, 1, 'contact', '2026-01-21 22:38:26', '2026-01-22 11:38:32', NULL, NULL, NULL, NULL),
(117, 'Zainab Shakeel', 'zainabshakeel400@gmail.com', 'Pakistan', '+923331368461', 'Sukkur ', '45504-0777890-0', 4, 0, 0, 'rejected', '2026-01-22 00:54:37', '2026-01-26 20:12:54', '2026-01-26 21:00:00', '2026-01-26 21:15:00', 'Google Meet', ''),
(118, 'Muhammad Nouman', 'numanrauf826@gmail.com', 'Pakistan', '+923264466626', 'Lahore', '35201-9409852-9', 9, 0, 1, 'contact', '2026-01-22 01:23:40', '2026-01-22 11:38:32', NULL, NULL, NULL, NULL),
(119, 'Didar Ali', 'didarali1129@gmail.com', 'Pakistan', '+923420032958', 'Rawalpindi', '15201-6770325-5', 4, 0, 0, 'contact', '2026-01-22 02:56:10', '2026-01-22 11:38:32', NULL, NULL, NULL, NULL),
(120, 'Fehmida Taj ', 'fehmidataj27@gmail.com', 'Pakistan', '+923067125195', 'Lahore', '35301-5680195-8', 4, 0, 0, 'rejected', '2026-01-22 07:53:33', '2026-01-22 11:59:32', NULL, NULL, NULL, NULL),
(122, 'Noman Ali Tasawar', 'dearnomee@gmail.com', 'Pakistan', '+923481993303', 'Islamabad', '12103-2059211-1', 4, 0, 0, 'contact', '2026-01-22 07:58:47', '2026-01-22 11:38:32', NULL, NULL, NULL, NULL),
(123, 'Hoor Fayaz', 'hoorf2004@gmail.com', 'Pakistan', '+923141988998', 'Abbottabad', '13101-0275372-6', 4, 0, 2, 'contact', '2026-01-22 08:04:22', '2026-01-22 11:38:32', NULL, NULL, NULL, NULL),
(124, 'Awais Ahmad ', 'awaisahmad246369@gmail.com', 'Pakistan', '+923427443395', 'Taxila', '17201-0185033-3', 4, 0, 1, 'contact', '2026-01-22 10:39:03', '2026-01-22 11:38:32', NULL, NULL, NULL, NULL),
(125, 'Sana Abid', 'inspirey22@gmail.com', 'Pakistan', '+923294432924', 'Gujranwlaa ', '34101-4916224-4', 7, 0, 0, 'contact', '2026-01-22 14:15:59', '2026-01-22 20:43:46', NULL, NULL, NULL, NULL),
(126, 'Muhammad Yasin Khan', 'engr.yasin.ai@gmail.com', 'Pakistan', '+923288712547', 'Matta Swat KPK', '15601-6226908-3', 4, 0, 1, 'contact', '2026-01-22 14:51:34', '2026-01-22 20:43:40', NULL, NULL, NULL, NULL),
(127, 'Ubada Dawood', 'ubkhan2001@gmail.com', 'Pakistan', '+923498885162', 'Rawalpindi', '71301-6898113-6', 4, 0, 1, 'contact', '2026-01-22 16:07:45', '2026-01-22 20:43:33', NULL, NULL, NULL, NULL),
(128, 'Mahnoor ', 'mahnoor.rajput11927@gmail.com', 'Pakistan', '+923173322610', 'Sukkur', '45504-4684661-0', 3, 0, 1, 'contact', '2026-01-22 20:46:43', '2026-01-22 20:50:17', NULL, NULL, NULL, NULL),
(129, 'AbdUrRehman Butt', 'kingsabdbutt@gmail.com', 'Pakistan', '+923190149406', 'Burewala', '36601-7610450-1', 5, 0, 1, 'contact', '2026-01-22 20:52:52', '2026-01-22 21:03:43', NULL, NULL, NULL, NULL),
(130, 'Areesha Ijaz', 'areeshaijaz93@gmail.com', 'Pakistan', '+923254455978', 'Renala Khurd', '35303-3757269-0', 9, 0, 0, 'contact', '2026-01-22 21:00:10', '2026-01-22 21:03:48', NULL, NULL, NULL, NULL),
(131, 'Noor kazmi ', 'nnkazmi082@gmail.com', 'Pakistan', '+923333657181', 'Abbottabad', '13102-0618371-0', 2, 0, 0, 'contact', '2026-01-22 22:53:27', '2026-01-25 23:00:09', NULL, NULL, NULL, NULL),
(132, 'Syed Qumber Ali Naqvi', 'syednaqviqumber55@gmail.com', 'Pakistan', '+923345923971', 'Islamabad', '35202-9362599-1', 4, 0, 0, 'contact', '2026-01-23 19:47:43', '2026-01-26 12:33:10', NULL, NULL, NULL, NULL),
(133, 'Sarah Chaudary', 'chaudharysarah71@gmail.com', 'Pakistan', '+923172003181', 'Lahore', '34101-9502629-8', 2, 0, 0, 'contact', '2026-01-23 20:26:09', '2026-01-26 12:33:05', NULL, NULL, NULL, NULL),
(134, 'Raksha Devi', 'rakshadara11@gmail.com', 'Pakistan', '+9203368082084', 'Karachi', '43504-0662391-8', 2, 0, 0, 'interview', '2026-01-23 22:35:20', '2026-01-26 14:46:42', '2026-01-26 22:00:00', '2026-01-26 22:15:00', 'Google Meet', NULL),
(135, 'Muneeza Mehboob', 'muneezamehboob3@gmail.com', 'Pakistan', '+923290314424', 'Multan', '36302-5924091-4', 4, 0, 1, 'interview', '2026-01-23 22:44:52', '2026-01-26 19:15:44', '2026-01-28 21:30:00', '2026-01-28 21:45:00', 'Google Meet', NULL),
(136, 'Iqra Jamali', 'iqrajamali67@gmail.com', 'Pakistan', '+923093714140', 'NAWAB SHAH', '45403-5044824-8', 2, 0, 2, 'contact', '2026-01-23 23:24:18', '2026-01-26 12:32:54', NULL, NULL, NULL, NULL),
(140, 'Uzair ', 'uzairofficials001@gmail.com', 'Pakistan', '+923706264133', 'Lahore ', '33102-1400771-1', 2, 0, 0, 'interview', '2026-01-24 10:51:10', '2026-01-26 19:27:23', '2026-01-27 21:00:00', '2026-01-27 21:15:00', 'Google Meet', NULL),
(141, 'Muhammad Saffan', 'muhammadsaffan60@gmail.com', 'Pakistan', '+923378043053', 'Jhelum', '37302-1937073-5', 4, 0, 2, 'rejected', '2026-01-24 15:40:09', '2026-01-26 21:28:26', '2026-01-26 21:15:00', '2026-01-26 21:30:00', 'Google Meet', ''),
(142, 'Junaid Ali', 'junaaid.26@gmail.com', 'Pakistan', '+923098730221', 'Abbottabad ', '17201-7727964-7', 4, 0, 1, 'contact', '2026-01-24 16:53:25', '2026-01-26 12:32:38', NULL, NULL, NULL, NULL),
(143, 'Anees Ahmad', 'aneesahmad2k21@gmail.com', 'Pakistan', '+923444580685', 'Tarbela', '16202-9296079-9', 4, 0, 0, 'contact', '2026-01-24 20:49:20', '2026-01-26 12:32:34', NULL, NULL, NULL, NULL),
(144, 'Zoha Saeed', 'zohasaeed63@gmail.com', 'Pakistan', '+923304264222', 'Lahore', '35202-5275031-0', 5, 0, 0, 'rejected', '2026-01-24 22:47:48', '2026-01-26 14:03:16', NULL, NULL, NULL, ''),
(145, 'Mahnoor adil', 'nooradildar7864@gmail.com', 'Pakistan', '+923164172383', 'Gujranwala', '34101-1713681-2', 2, 0, 2, 'rejected', '2026-01-25 19:11:55', '2026-01-26 21:43:50', '2026-01-26 21:30:00', '2026-01-26 21:45:00', 'Google Meet', 'We regret to inform you that your application has not been selected, as the interview was left incomplete and the behavior did not meet our professional standards.'),
(146, 'Bushra', 'bushra.msaleem@gmail.com', 'Pakistan', '+923347241497', 'Daharki', '45101-6909543-0', 9, 0, 0, 'contact', '2026-01-25 22:23:29', '2026-01-25 23:17:19', NULL, NULL, NULL, NULL),
(147, 'Ahsan Ali Leghari', 'ak7111master@gmail.com', 'Pakistan', '+923191646922', 'Ghotki', '45102-7904088-5', 8, 0, 0, 'interview', '2026-01-26 07:32:11', '2026-01-26 14:31:57', '2026-01-26 21:45:00', '2026-01-26 22:00:00', 'Google Meet', NULL),
(148, 'Malik Farhan', 'faree.one5@gmail.com', 'Pakistan', '+923077778751', 'Faisalabad', '33102-7659087-3', 4, 0, 2, 'contact', '2026-01-26 09:43:43', '2026-01-26 12:32:16', NULL, NULL, NULL, NULL),
(149, 'Muhammed Noman', 'iamnoman8361@gmail.com', 'Pakistan', '+923220298361', 'Dunyapur', '36201-7227036-7', 4, 0, 2, 'new', '2026-01-26 13:09:15', '2026-01-26 13:09:15', NULL, NULL, NULL, NULL),
(150, 'zahra tariq', 'zahratariq142@gmail.com', 'Pakistan', '+923403209234', 'Rawalpindi', '37405-1265062-8', 5, 0, 0, 'new', '2026-01-26 16:53:04', '2026-01-26 16:53:04', NULL, NULL, NULL, NULL),
(152, 'Ronik Kumar', 'ronikkumar98@gmail.com', 'Pakistan', '+923419682094', 'Karachi', '45102-1671099-3', 8, 0, 0, 'new', '2026-01-26 19:08:31', '2026-01-26 19:08:31', NULL, NULL, NULL, NULL),
(153, 'Hafsa umair', 'hafsaalam95@gmail.com', 'Pakistan', '+923302425851', 'karachi', '42101-8574251-2', 5, 0, 2, 'hire', '2026-01-26 19:41:29', '2026-01-26 20:20:45', NULL, NULL, NULL, NULL),
(154, 'Prena Goindani', 'goindaniprerna20@gmail.com', 'Pakistan', '+923152487833', 'DEHARKI', '45101-9519991-2', 8, 0, 0, 'new', '2026-01-26 20:45:27', '2026-01-26 20:45:27', NULL, NULL, NULL, NULL),
(155, 'Nasim Ahmad', 'nasimshazad002@gmail.com', 'Pakistan', '+923259951510', 'Peshawar City ', '21708-3452591-7', 5, 0, 0, 'new', '2026-01-26 21:47:10', '2026-01-26 21:47:10', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `assign_to` int(11) DEFAULT NULL,
  `notification` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','working','complete','pending_review','approved','rejected','needs_improvement','expired') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `due_date` date NOT NULL,
  `live_url` varchar(255) NOT NULL,
  `github_repo` varchar(255) NOT NULL,
  `additional_notes` text NOT NULL,
  `review_notes` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `created_by`, `assign_to`, `notification`, `status`, `created_at`, `updated_at`, `started_at`, `completed_at`, `due_date`, `live_url`, `github_repo`, `additional_notes`, `review_notes`, `reviewed_at`, `reviewed_by`) VALUES
(1, 'Testing 1', '<p>Testing</p>', 2, 7, 1, '', '2026-01-27 15:11:28', '2026-01-27 20:11:28', '2026-01-27 20:11:57', '2026-01-27 21:33:50', '2026-02-03', 'http://localhost/phpmyadmin/index.php?route=/sql&pos=0&db=task_management&table=tasks', 'https://github.com/', '', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `technologies`
--

CREATE TABLE `technologies` (
  `id` int(11) NOT NULL,
  `name` varchar(30) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `technologies`
--

INSERT INTO `technologies` (`id`, `name`, `status`, `created_at`) VALUES
(2, 'Full Stack Development', 1, '2025-11-19 15:54:12'),
(3, 'MERN Stack', 1, '2025-11-19 19:10:05'),
(4, 'Machine Learning', 1, '2025-11-23 17:53:43'),
(5, 'Graphic Design', 1, '2025-11-23 19:32:12'),
(6, 'PHP Laravel', 1, '2025-11-23 22:18:30'),
(7, 'Digital Marketing', 1, '2025-12-08 20:10:28'),
(8, 'Frontend Development', 1, '2025-12-12 20:38:52'),
(9, 'Data Science', 1, '2025-12-27 23:50:32'),
(10, 'App Development', 1, '2025-12-30 19:22:58');

-- --------------------------------------------------------

--
-- Table structure for table `time_logs`
--

CREATE TABLE `time_logs` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `duration` int(11) DEFAULT 0 COMMENT 'in seconds'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_logs`
--

INSERT INTO `time_logs` (`id`, `task_id`, `user_id`, `start_time`, `end_time`, `duration`) VALUES
(1, 1, 7, '2026-01-27 20:11:57', '2026-01-27 21:33:50', 4913);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `plain_password` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_role` int(11) NOT NULL DEFAULT 2,
  `status` int(11) NOT NULL DEFAULT 1,
  `tech_id` int(11) NOT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `temp_col` varchar(255) DEFAULT NULL,
  `internship_type` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `plain_password`, `password`, `user_role`, `status`, `tech_id`, `supervisor_id`, `created_at`, `temp_col`, `internship_type`) VALUES
(1, 'Admin', 'admin@taskdesk.org', 'GenNext@@0099', '$2y$10$g4fapOYFN.YzSCuWgUch7uodI/zHRRhMtfysNb8AN/tIOyAID1PT2', 1, 1, 0, NULL, '2025-12-08 14:55:31', NULL, 1),
(2, 'Qamar Naveed', 'qamargill427@gmail.com', '1122', '$2y$10$g4fapOYFN.YzSCuWgUch7uodI/zHRRhMtfysNb8AN/tIOyAID1PT2', 3, 1, 0, NULL, '2025-12-08 14:56:47', NULL, 1),
(3, 'Muhammad Mudassir', 'muhammadmudassir8877@gmail.com', '5aLJms47gu1', '$2y$10$72h8iSO5RRV3MEvjUEFZNew9TaEgTv9ruMbUX/WBJ.UnMjnhyJJv2', 2, 1, 3, 2, '2025-12-08 15:06:49', NULL, 1),
(4, 'Fiza', 'fizaa7343@gmail.com', '5aLJms47gu1', '$2y$10$H7b5eSIHn0xAJ4ok2WyXmOTNicnmketc3JLUYkT0bMig3dlJiPw9m', 2, 1, 7, 2, '2025-12-08 15:11:14', NULL, 1),
(5, 'Faryal Naz', 'faryalnaz711@gmail.com', '5aLJms47gu1', '$2y$10$Z3wrp18h7fG3zgCrrqKjNefDNSi4UOGg/GoTPFtxZCHl7N5/WDBBe', 2, 1, 4, 2, '2025-12-08 15:12:18', NULL, 1),
(6, 'Vandna', 'wandhnarejhra@gmail.com', 'AFPYI&2af3]E', '$2y$10$UBtEeIyBO2gCqPoc.o7Wl.9Pw3hvcIpptUkrhiYjOAy2LRwNn5y66', 2, 1, 8, 2, '2025-12-12 15:39:46', NULL, 1),
(7, 'Abdul Rehman', 'arimran7315@gmail.com', 'c]j0%OxipKCq', '$2y$10$HH2dDM6Nus0dba0c7lc8JOHECypd0NRhJWYXg40t7/kK0LP4VHQO.', 2, 1, 6, 2, '2025-12-13 19:06:56', NULL, 0),
(14, 'Fajar Shakeel', 'manhalhads@gmail.com', '?zuE;t]zp~$?GS8', '$2y$10$o2y/ydBySoC52QNBkDVmqONTKQCFVcMTiHKgiJT28wY1CdZimGZQO', 2, 1, 4, 2, '2025-12-26 05:22:49', NULL, 1),
(16, 'Mehwish Zahra', 'mehwishzahra086@gmail.com', 'VHA$FJAkio37', '$2y$10$HnMIV6UIV5VmkV33hj79ruVEjkbfh1FR2oN8yzp/dhbBoaGfMo3B.', 2, 1, 7, 2, '2025-12-29 05:33:43', NULL, 1),
(18, 'Munazza Akhlaq', 'munazzamunazza229@gmail.com', '(_I[0L72pbH%', '$2y$10$seTiN.RdGEDoAjoNHfK/eu8hIhlxduzBa4O4561NCQLtg3P4XboAa', 2, 1, 10, 2, '2025-12-30 14:24:53', NULL, 1),
(19, 'Areesha Arain', 'areeshaarain2006@gmail.com', 'a@_TA76[wGyJ', '$2y$10$3n391BQKZm0kTfPZ5JmojeoNyaf9uSHa7NmbX1Ps1kd3XJMPpoJ5W', 2, 1, 7, 2, '2025-12-31 14:04:15', NULL, 1),
(20, 'Fatima Manzoor', 'fatemahmnzor@gmail.com', 'b9=6LSW]6Ccx', '$2y$10$GaRFS.55dlC6ESEisABrIOWaIPRcDE/IvAux6aESkuPELu6Q0aPnu', 2, 1, 4, 21, '2026-01-07 06:08:37', NULL, 1),
(21, 'Ahmed Naeem', 'ahmednaeemnaeemgulzar521@gmail.com', '7WkoQJ&Mz[nq', '$2y$10$ggGWqo2dXP.gVunp2/cPwuldAQW6sxEtpbtLMRAHZj9J.lQMfGKxy', 3, 1, 0, NULL, '2026-01-07 06:31:00', NULL, 1),
(22, 'Iqra Arshad', 'chaudharyiqra105@gmail.com', 'RHaf&^^moc9C', '$2y$10$qIcgwbrW1MBAADNTy6V4BejCgaEGjJRmV3WIvE5yCUnuZlfcrbi.2', 2, 1, 5, 2, '2026-01-14 17:14:13', NULL, 1),
(23, 'Manager', 'manager@taskdesk.org', 'TaskDesk@345', '$2y$10$Zgz13iXGsRyiwvKG74qOL.wLfEFgwPEg7Y7Lm41glJmX77d7WVqr6', 4, 1, 0, 0, '2026-01-19 08:42:17', NULL, 1),
(46, 'Qamar Naveed', 'code.learners.edu.pk@gmail.com', '&+f7O&9z3h@3', '$2y$10$cfzx8yqGgMOKbujOfPD8eOg5tyJJWFfzvDHxh/SjcggEfSO.LYSbC', 2, 1, 3, 2, '2026-01-25 17:57:10', NULL, 1),
(49, 'Hafsa umair', 'hafsaalam95@gmail.com', 'u*7ZJcYTztaP', '$2y$10$w13dIeGVMzF9xf2J72GSoea2iL.2ap1hug4aBSnLsBVEVhfwok.R.', 2, 1, 5, 2, '2026-01-26 15:20:45', NULL, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`date`);

--
-- Indexes for table `certificate`
--
ALTER TABLE `certificate`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`) USING BTREE,
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_technology_id` (`technology_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_tech_id` (`technology_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `technologies`
--
ALTER TABLE `technologies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `time_logs`
--
ALTER TABLE `time_logs`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `certificate`
--
ALTER TABLE `certificate`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `email_queue`
--
ALTER TABLE `email_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=156;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `technologies`
--
ALTER TABLE `technologies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `time_logs`
--
ALTER TABLE `time_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
