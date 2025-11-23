-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 23, 2025 at 12:29 PM
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
-- Database: `eaaps_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `expected_start_time` time DEFAULT NULL,
  `expected_end_time` time DEFAULT NULL,
  `status` enum('Present','Absent','Late','On Leave','Undertime') DEFAULT 'Present',
  `snapshot_path` varchar(500) DEFAULT NULL,
  `check_type` enum('in','out') DEFAULT 'in',
  `is_synced` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_logs`
--

INSERT INTO `attendance_logs` (`id`, `employee_id`, `date`, `time_in`, `time_out`, `expected_start_time`, `expected_end_time`, `status`, `snapshot_path`, `check_type`, `is_synced`, `created_at`, `updated_at`) VALUES
(39, 27, '2025-11-21', NULL, NULL, '04:42:00', '07:42:00', 'Absent', NULL, 'in', 0, '2025-11-21 10:38:34', '2025-11-21 10:38:34'),
(41, 31, '2025-11-21', NULL, NULL, '06:38:00', '16:39:00', 'Absent', NULL, 'in', 0, '2025-11-21 10:38:34', '2025-11-21 10:38:34'),
(42, 25, '2025-11-21', NULL, NULL, '11:00:00', '13:30:00', 'Absent', NULL, 'in', 0, '2025-11-21 11:01:06', '2025-11-21 11:01:06'),
(43, 29, '2025-11-21', NULL, NULL, '01:39:00', '06:46:00', 'Absent', NULL, 'in', 0, '2025-11-21 11:24:58', '2025-11-21 11:24:58'),
(46, 16, '2025-11-21', '2025-11-21 11:33:00', NULL, '12:00:00', '20:30:00', 'Present', 'uploads/snapshots/snapshot_46_1763726027.png', 'in', 0, '2025-11-21 11:33:59', '2025-11-23 07:30:14'),
(47, 26, '2025-11-21', NULL, NULL, '00:00:00', '01:30:00', 'Absent', NULL, 'in', 0, '2025-11-21 11:51:54', '2025-11-21 11:51:54'),
(48, 16, '2025-11-23', '2025-11-23 15:27:39', '2025-11-23 15:38:59', '07:30:00', '16:30:00', 'Undertime', 'uploads/snapshots/snapshot_48_1763883539.png', 'in', 0, '2025-11-23 07:27:39', '2025-11-23 07:38:59'),
(49, 31, '2025-11-23', '2025-11-23 15:29:27', '2025-11-23 15:39:30', '16:30:00', '17:30:00', 'Undertime', 'uploads/snapshots/snapshot_49_1763883570.png', 'in', 0, '2025-11-23 07:29:27', '2025-11-23 07:39:30'),
(51, 34, '2025-11-23', '2025-11-23 15:57:17', NULL, '17:00:00', '18:30:00', 'Present', 'uploads/snapshots/snapshot_51_1763884637.png', 'in', 0, '2025-11-23 07:57:17', '2025-11-23 07:57:17');

-- --------------------------------------------------------

--
-- Table structure for table `backup_restore_settings`
--

CREATE TABLE `backup_restore_settings` (
  `id` int(11) NOT NULL,
  `backup_frequency` enum('daily','weekly','monthly') DEFAULT 'weekly',
  `session_timeout_minutes` int(11) DEFAULT 30,
  `backup_location` varchar(500) DEFAULT 'Local Server (/backups)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `backup_restore_settings`
--

INSERT INTO `backup_restore_settings` (`id`, `backup_frequency`, `session_timeout_minutes`, `backup_location`, `created_at`, `updated_at`) VALUES
(1, 'weekly', 30, 'Local Server (/backups)', '2025-11-21 12:44:20', '2025-11-21 12:44:20');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `created_at`, `updated_at`) VALUES
(7, 'Computer Engineering', '2025-11-08 04:44:38', '2025-11-08 04:44:38'),
(8, 'Education', '2025-11-08 04:45:06', '2025-11-08 04:45:06');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `marital_status` enum('Single','Married','Divorced','Widowed','Other') DEFAULT 'Single',
  `status` enum('Active','Inactive','On Leave') DEFAULT 'Active',
  `email` varchar(255) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `emergency_contact_name` varchar(100) NOT NULL DEFAULT '',
  `emergency_contact_phone` varchar(20) NOT NULL DEFAULT '',
  `emergency_contact_relationship` varchar(50) NOT NULL DEFAULT '',
  `date_joined` date NOT NULL,
  `department_id` int(11) NOT NULL,
  `job_position_id` int(11) NOT NULL,
  `rate_per_hour` decimal(10,2) NOT NULL DEFAULT 0.00,
  `annual_paid_leave_days` int(11) DEFAULT 15,
  `annual_unpaid_leave_days` int(11) DEFAULT 5,
  `annual_sick_leave_days` int(11) DEFAULT 10,
  `avatar_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `first_name`, `last_name`, `address`, `gender`, `marital_status`, `status`, `email`, `contact_number`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `date_joined`, `department_id`, `job_position_id`, `rate_per_hour`, `annual_paid_leave_days`, `annual_unpaid_leave_days`, `annual_sick_leave_days`, `avatar_path`, `created_at`, `updated_at`) VALUES
(16, NULL, 'Alexander', 'Osias', 'So. Bugho', 'Male', 'Single', 'Active', 'alexanderosias123@gmail.com', '09305909175', 'Annaliza Osias', '09432487398', 'Mother', '2025-11-10', 7, 13, 100.00, 15, 5, 10, 'uploads/avatars/emp_16_1762879611.png', '2025-11-10 12:57:16', '2025-11-11 16:46:51'),
(25, 40, 'fgagafgaf', 'fdsafsda', 'So. Bugho', 'Male', 'Single', 'Active', 'alexande@gmail.com', '09305909175', 'Alexander Osias', '09305909175', 'hakdog', '2025-11-20', 7, 11, 120.00, 15, 5, 10, NULL, '2025-11-20 14:17:46', '2025-11-20 14:17:46'),
(26, 41, 'jak', 'kdfads', 'So. Bugho', 'Male', 'Single', 'Active', 'alexandeias123@gmail.com', '09305909175', 'Alexander Osias', '09305909175', 'dsflkadsf', '2025-11-20', 8, 11, 120.00, 15, 5, 10, NULL, '2025-11-20 14:21:00', '2025-11-20 14:21:00'),
(27, 42, 'pahpgfd', 'etekcvca', 'So. Bugho', 'Male', 'Single', 'Active', 'alexanas123@gmail.com', '09305909175', 'Alexander Osias', '09305909175', 'Wife', '2025-11-20', 7, 11, 120.00, 15, 5, 10, NULL, '2025-11-20 14:21:34', '2025-11-20 14:21:34'),
(29, 44, 'yoyta', 'baloga', 'So. Bugho', 'Male', 'Single', 'Active', 'anderosias123@gmail.com', '09305909175', 'Alexander Osias', '09305909175', 'Wife', '2025-11-20', 7, 13, 100.00, 15, 5, 10, 'uploads/avatars/emp_29_1763725934.png', '2025-11-20 14:23:17', '2025-11-21 11:52:15'),
(30, 45, 'macky', 'paksiw', 'So. Bugho', 'Male', 'Single', 'Active', 'alexderosias123@gmail.com', '09305909175', 'Alexander Osias', '09305909175', 'Mother', '2025-11-20', 8, 12, 150.00, 15, 5, 10, NULL, '2025-11-20 14:24:21', '2025-11-20 14:24:21'),
(31, 46, 'jake', 'ampong', 'So. Bugho', 'Male', 'Single', 'Active', 'alexandsias123@gmail.com', '09305909175', 'Alexander Osias', '09305909175', 'gfgdgdsgf', '2025-11-20', 8, 11, 120.00, 15, 5, 10, 'uploads/avatars/emp_31_1763664006.jpg', '2025-11-20 14:24:59', '2025-11-22 19:29:35'),
(32, 47, 'Aleajandcedor', 'Osias', 'So. Bugho', 'Male', 'Single', 'Active', 'alexa23dsf@gmail.com', '09305909175', 'Alexander Osias', '09305909175', 'Mother', '2025-11-20', 7, 13, 100.00, 15, 5, 10, NULL, '2025-11-20 14:25:50', '2025-11-20 14:25:50'),
(34, 50, 'Daniela', 'Osias', 'So. Bugho', 'Female', 'Single', 'Active', 'danielaosias@gmail.com', '09305909175', 'Alexander Osias', '09305909175', 'Father', '2025-11-23', 8, 13, 100.00, 15, 5, 10, NULL, '2025-11-23 07:55:47', '2025-11-23 07:55:47');

-- --------------------------------------------------------

--
-- Table structure for table `job_positions`
--

CREATE TABLE `job_positions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `rate_per_hour` decimal(10,2) DEFAULT 0.00,
  `payroll_frequency` enum('daily','weekly','bi-weekly','monthly') DEFAULT 'bi-weekly',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_positions`
--

INSERT INTO `job_positions` (`id`, `name`, `rate_per_hour`, `payroll_frequency`, `created_at`, `updated_at`) VALUES
(11, 'Cashier', 120.00, 'bi-weekly', '2025-11-08 09:48:47', '2025-11-08 09:48:47'),
(12, 'Dean', 150.00, 'bi-weekly', '2025-11-10 05:24:52', '2025-11-10 05:24:52'),
(13, 'Instructor', 100.00, 'bi-weekly', '2025-11-10 12:56:32', '2025-11-10 12:56:32');

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type` enum('Sick','Vacation','Unpaid') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `code` varchar(6) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `code`, `expires_at`, `created_at`) VALUES
(53, 'alexanderosias123@gmail.com', '976709', '2025-11-21 10:24:22', '2025-11-21 17:14:22');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `payroll_period_start` date NOT NULL,
  `payroll_period_end` date NOT NULL,
  `gross_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `philhealth_deduction` decimal(10,2) DEFAULT 0.00,
  `sss_deduction` decimal(10,2) DEFAULT 0.00,
  `pagibig_deduction` decimal(10,2) DEFAULT 0.00,
  `other_deductions` decimal(10,2) DEFAULT 0.00,
  `total_deductions` decimal(10,2) GENERATED ALWAYS AS (`philhealth_deduction` + `sss_deduction` + `pagibig_deduction` + `other_deductions`) STORED,
  `net_pay` decimal(10,2) GENERATED ALWAYS AS (`gross_pay` - `total_deductions`) STORED,
  `paid_status` enum('Unpaid','Paid') DEFAULT 'Unpaid',
  `payment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qr_codes`
--

CREATE TABLE `qr_codes` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `qr_data` text NOT NULL,
  `qr_image_path` varchar(500) NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qr_codes`
--

INSERT INTO `qr_codes` (`id`, `employee_id`, `qr_data`, `qr_image_path`, `generated_at`) VALUES
(13, 16, 'ID:16|First:Alexander|Last:Osias|Position:Instructor|Joined:2025-11-10', 'qrcodes/AlexanderOsias.png', '2025-11-10 12:57:16'),
(19, 25, 'ID:25|First:fgagafgaf|Last:fdsafsda|Position:Cashier|Joined:2025-11-20', 'qrcodes/fgagafgaffdsafsda.png', '2025-11-20 14:17:46'),
(20, 26, 'ID:26|First:jak|Last:kdfads|Position:Cashier|Joined:2025-11-20', 'qrcodes/jakkdfads.png', '2025-11-20 14:21:00'),
(21, 27, 'ID:27|First:pahpgfd|Last:etekcvca|Position:Cashier|Joined:2025-11-20', 'qrcodes/pahpgfdetekcvca.png', '2025-11-20 14:21:34'),
(23, 29, 'ID:29|First:yoyta|Last:baloga|Position:Instructor|Joined:2025-11-20', 'qrcodes/yoytabaloga.png', '2025-11-20 14:23:17'),
(24, 30, 'ID:30|First:macky|Last:paksiw|Position:Dean|Joined:2025-11-20', 'qrcodes/mackypaksiw.png', '2025-11-20 14:24:21'),
(25, 31, 'ID:31|First:jake|Last:ampong|Position:Cashier|Joined:2025-11-20', 'qrcodes/jakeampong.png', '2025-11-20 14:24:59'),
(26, 32, 'ID:32|First:Aleajandcedor|Last:Osias|Position:Instructor|Joined:2025-11-20', 'qrcodes/AleajandcedorOsias.png', '2025-11-20 14:25:50'),
(28, 34, 'ID:34|First:Daniela|Last:Osias|Position:Instructor|Joined:2025-11-23', 'qrcodes/DanielaOsias.png', '2025-11-23 07:55:48');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `shift_name` varchar(100) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `employee_id`, `day_of_week`, `shift_name`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES
(39, 31, 'Friday', 'PE', '06:38:00', '16:39:00', '2025-11-20 17:39:22', '2025-11-20 17:39:22'),
(40, 29, 'Friday', 'PE', '01:39:00', '04:39:00', '2025-11-20 17:41:15', '2025-11-20 17:41:15'),
(41, 29, 'Friday', 'ITP 101 - Lab 3', '04:41:00', '06:46:00', '2025-11-20 17:41:48', '2025-11-20 17:41:48'),
(42, 27, 'Friday', 'PE', '04:42:00', '07:42:00', '2025-11-20 17:42:17', '2025-11-20 17:42:17'),
(43, 26, 'Friday', 'PE', '00:00:00', '01:30:00', '2025-11-20 17:48:56', '2025-11-20 17:48:56'),
(44, 16, 'Friday', 'ITP 101 - Lab 3', '17:30:00', '19:00:00', '2025-11-21 09:23:50', '2025-11-21 09:23:50'),
(46, 16, 'Friday', 'sda', '15:00:00', '17:30:00', '2025-11-21 09:49:15', '2025-11-21 09:49:15'),
(47, 16, 'Friday', 'Ethics', '12:00:00', '14:30:00', '2025-11-21 10:12:21', '2025-11-21 10:12:21'),
(48, 16, 'Friday', 'ITP 111 - Lab 3', '19:00:00', '20:30:00', '2025-11-21 10:21:58', '2025-11-21 10:21:58'),
(49, 25, 'Friday', 'kalld', '11:00:00', '13:30:00', '2025-11-21 11:01:01', '2025-11-21 11:01:01'),
(50, 16, 'Sunday', 'Math', '07:30:00', '16:30:00', '2025-11-23 07:27:15', '2025-11-23 07:27:15'),
(51, 31, 'Sunday', 'PE', '16:30:00', '17:30:00', '2025-11-23 07:28:50', '2025-11-23 07:28:50'),
(53, 34, 'Sunday', 'ITP 101 - Lab 3', '17:00:00', '18:30:00', '2025-11-23 07:56:17', '2025-11-23 07:56:17');

-- --------------------------------------------------------

--
-- Table structure for table `school_settings`
--

CREATE TABLE `school_settings` (
  `id` int(11) NOT NULL,
  `logo_path` varchar(500) DEFAULT NULL,
  `system_name` varchar(100) NOT NULL DEFAULT 'EAAPS Admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `annual_paid_leave_days` int(11) DEFAULT 15,
  `annual_unpaid_leave_days` int(11) DEFAULT 5,
  `annual_sick_leave_days` int(11) DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_settings`
--

INSERT INTO `school_settings` (`id`, `logo_path`, `system_name`, `created_at`, `updated_at`, `annual_paid_leave_days`, `annual_unpaid_leave_days`, `annual_sick_leave_days`) VALUES
(1, 'uploads/logos/logo_1763815197.jpg', 'EAAPS Admin', '2025-11-21 12:44:20', '2025-11-23 07:04:15', 11, 5, 5);

-- --------------------------------------------------------

--
-- Table structure for table `snapshots`
--

CREATE TABLE `snapshots` (
  `id` int(11) NOT NULL,
  `attendance_log_id` int(11) NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `captured_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `snapshots`
--

INSERT INTO `snapshots` (`id`, `attendance_log_id`, `image_path`, `captured_at`) VALUES
(51, 46, 'uploads/snapshots/snapshot_46_1763724839.png', '2025-11-21 11:33:59'),
(52, 46, 'uploads/snapshots/snapshot_46_1763726027.png', '2025-11-21 11:53:47'),
(53, 48, 'uploads/snapshots/snapshot_48_1763882859.png', '2025-11-23 07:27:39'),
(54, 49, 'uploads/snapshots/snapshot_49_1763882967.png', '2025-11-23 07:29:27'),
(55, 48, 'uploads/snapshots/snapshot_48_1763883539.png', '2025-11-23 07:38:59'),
(56, 49, 'uploads/snapshots/snapshot_49_1763883570.png', '2025-11-23 07:39:30'),
(57, 51, 'uploads/snapshots/snapshot_51_1763884637.png', '2025-11-23 07:57:17');

-- --------------------------------------------------------

--
-- Table structure for table `tax_deduction_settings`
--

CREATE TABLE `tax_deduction_settings` (
  `id` int(11) NOT NULL,
  `philhealth_rate` decimal(5,2) DEFAULT 5.00,
  `philhealth_min` decimal(10,2) DEFAULT 500.00,
  `philhealth_max` decimal(10,2) DEFAULT 5000.00,
  `philhealth_split_5050` tinyint(1) DEFAULT 1,
  `pagibig_threshold` decimal(10,2) DEFAULT 1500.00,
  `pagibig_employee_low_rate` decimal(5,2) DEFAULT 1.00,
  `pagibig_employee_high_rate` decimal(5,2) DEFAULT 2.00,
  `pagibig_employer_rate` decimal(5,2) DEFAULT 2.00,
  `sss_msc_min` int(11) DEFAULT 10000,
  `sss_msc_max` int(11) DEFAULT 20000,
  `sss_er_contribution` decimal(10,2) DEFAULT 1160.00,
  `sss_ee_contribution` decimal(10,2) DEFAULT 450.00,
  `income_tax_rate` decimal(5,2) DEFAULT 10.00,
  `tax_calculation_rule` enum('standard','flat','custom') DEFAULT 'flat',
  `custom_tax_formula` text DEFAULT NULL,
  `auto_apply_deductions` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tax_deduction_settings`
--

INSERT INTO `tax_deduction_settings` (`id`, `philhealth_rate`, `philhealth_min`, `philhealth_max`, `philhealth_split_5050`, `pagibig_threshold`, `pagibig_employee_low_rate`, `pagibig_employee_high_rate`, `pagibig_employer_rate`, `sss_msc_min`, `sss_msc_max`, `sss_er_contribution`, `sss_ee_contribution`, `income_tax_rate`, `tax_calculation_rule`, `custom_tax_formula`, `auto_apply_deductions`, `created_at`, `updated_at`) VALUES
(1, 5.00, 500.00, 5000.00, 1, 1500.00, 1.00, 2.00, 2.00, 10000, 20000, 1160.00, 450.00, 10.00, 'flat', NULL, 1, '2025-11-21 12:44:20', '2025-11-21 12:44:20');

-- --------------------------------------------------------

--
-- Table structure for table `time_date_settings`
--

CREATE TABLE `time_date_settings` (
  `id` int(11) NOT NULL,
  `default_timezone` varchar(50) NOT NULL DEFAULT 'PHST',
  `date_format` enum('MM/DD/YYYY','DD/MM/YYYY','YYYY-MM-DD') DEFAULT 'MM/DD/YYYY',
  `auto_logout_time_hours` decimal(6,5) DEFAULT 1.00000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_date_settings`
--

INSERT INTO `time_date_settings` (`id`, `default_timezone`, `date_format`, `auto_logout_time_hours`, `created_at`, `updated_at`) VALUES
(1, 'PHST', 'DD/MM/YYYY', 0.00000, '2025-11-21 12:44:20', '2025-11-22 16:08:35');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `avatar_path` varchar(255) DEFAULT NULL,
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '["admin"]' CHECK (json_valid(`roles`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone_number`, `address`, `department_id`, `password_hash`, `is_active`, `created_at`, `updated_at`, `avatar_path`, `roles`) VALUES
(37, 'Giyu', 'Tomioka', 'alexanderosias123@gmail.com', '09305909175', 'So. Bugho, Barangay Dampigan, Sta. Rita, Samar', 7, '$2y$10$8/j8QM7seXrRd0o2mMiKvutj5XPGRLw9IMNtuY87gWkGo0WFig4c2', 1, '2025-11-16 07:15:52', '2025-11-22 17:34:01', 'uploads/avatars/user_37_1763477253.jpg', '[\"admin\"]'),
(40, 'fgagafgaf', 'fdsafsda', 'alexande@gmail.com', '09305909175', 'So. Bugho', 7, '$2y$10$RHvQnO9LjWR1sCpu0AY2cuY8Kppf2Ok6rFIcD6/CHtP.kiwTgw4y6', 1, '2025-11-20 14:17:46', '2025-11-20 14:17:46', NULL, '[\"admin\"]'),
(41, 'jak', 'kdfads', 'alexandeias123@gmail.com', '09305909175', 'So. Bugho', 8, '$2y$10$GaHG4nhDdkrZJkX5ULhIpuW0ZJd0KpKEz7yA8TWFk7eI8vGIqJb2e', 1, '2025-11-20 14:21:00', '2025-11-20 14:21:00', NULL, '[\"admin\"]'),
(42, 'pahpgfd', 'etekcvca', 'alexanas123@gmail.com', '09305909175', 'So. Bugho', 7, '$2y$10$dxgRXHNxwsui5ATIPe1ya.O6LCDkjg4DbULSO7L3./31JQ7FpPFaK', 1, '2025-11-20 14:21:34', '2025-11-20 14:21:34', NULL, '[\"admin\"]'),
(44, 'yoyta', 'baloga', 'anderosias123@gmail.com', '09305909175', 'So. Bugho', 7, '$2y$10$kG8BbCjctlWs942Iw8j3l.j4d1ibD/.kJs5IvS0tzQbygsywa9QK.', 1, '2025-11-20 14:23:17', '2025-11-20 14:23:17', NULL, '[\"admin\"]'),
(45, 'macky', 'paksiw', 'alexderosias123@gmail.com', '09305909175', 'So. Bugho', 8, '$2y$10$ZPvlKi24ENcDFabndMhBxOTJc4UPuMScsayeH7KmFQRTs97j4INkO', 1, '2025-11-20 14:24:21', '2025-11-20 14:24:21', NULL, '[\"admin\"]'),
(46, 'jake', 'ampong', 'alexandsias123@gmail.com', '09305909175', 'So. Bugho', 8, '$2y$10$ipBRVbwhyEP7n0EafdzxRO/zbnrYOEAuUfm7PM6pr8ueQsLuy1HB2', 1, '2025-11-20 14:24:59', '2025-11-20 14:24:59', NULL, '[\"admin\"]'),
(47, 'Aleajandcedor', 'Osias', 'alexa23dsf@gmail.com', '09305909175', 'So. Bugho', 7, '$2y$10$3OmOcyNguS31aolONPXd0OHv50CZlF/jKdDv34zgx7A2qULjsjMri', 1, '2025-11-20 14:25:50', '2025-11-20 14:25:50', NULL, '[\"admin\"]'),
(50, 'Daniela', 'Osias', 'danielaosias@gmail.com', '09305909175', 'So. Bugho', 8, '$2y$10$odR4P8M8FP9se.SW/hIVfeCxSXH60WScnQYCMVeYQM9GuHZvgFBiC', 1, '2025-11-23 07:55:47', '2025-11-23 08:16:05', 'uploads/avatars/user_50_1763885765.jpg', '[\"admin\"]'),
(52, 'Alexander', 'Osias', 'alexafdf@gmail.com', '09305909175', 'So. Bugho\r\nBarangay Dampigan', 7, '$2y$10$nwFV5Vx0cLCfjBTfjdaYU.wkn5oqozQJgzhe5ipwaF./WSfRvKJHu', 1, '2025-11-23 11:27:21', '2025-11-23 11:27:21', NULL, '[\"admin\",\"head_admin\"]');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_date` (`employee_id`,`date`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `backup_restore_settings`
--
ALTER TABLE `backup_restore_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `job_position_id` (`job_position_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_date_joined` (`date_joined`),
  ADD KEY `idx_marital_status` (`marital_status`),
  ADD KEY `idx_emergency_contact` (`emergency_contact_phone`),
  ADD KEY `fk_employees_user` (`user_id`);

--
-- Indexes for table `job_positions`
--
ALTER TABLE `job_positions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_period` (`employee_id`,`payroll_period_start`),
  ADD KEY `idx_period` (`payroll_period_start`,`payroll_period_end`),
  ADD KEY `idx_paid_status` (`paid_status`);

--
-- Indexes for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `idx_employee` (`employee_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_shift` (`employee_id`,`day_of_week`,`shift_name`),
  ADD KEY `idx_employee_day` (`employee_id`,`day_of_week`);

--
-- Indexes for table `school_settings`
--
ALTER TABLE `school_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `snapshots`
--
ALTER TABLE `snapshots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attendance` (`attendance_log_id`);

--
-- Indexes for table `tax_deduction_settings`
--
ALTER TABLE `tax_deduction_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `time_date_settings`
--
ALTER TABLE `time_date_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `backup_restore_settings`
--
ALTER TABLE `backup_restore_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `job_positions`
--
ALTER TABLE `job_positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qr_codes`
--
ALTER TABLE `qr_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `school_settings`
--
ALTER TABLE `school_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `snapshots`
--
ALTER TABLE `snapshots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `tax_deduction_settings`
--
ALTER TABLE `tax_deduction_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `time_date_settings`
--
ALTER TABLE `time_date_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `fk_attendance_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employees_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_employees_jobposition` FOREIGN KEY (`job_position_id`) REFERENCES `job_positions` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_employees_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `fk_payroll_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD CONSTRAINT `fk_qrcodes_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `fk_schedules_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `snapshots`
--
ALTER TABLE `snapshots`
  ADD CONSTRAINT `fk_snapshots_attendance` FOREIGN KEY (`attendance_log_id`) REFERENCES `attendance_logs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
