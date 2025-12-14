-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 11, 2025 at 12:55 PM
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
(87, 56, '2025-12-11', '2025-12-11 13:29:48', '2025-12-11 13:35:53', '13:22:00', '17:30:00', 'Undertime', 'uploads/snapshots/snapshot_87_1765431353.png', 'in', 1, '2025-12-11 05:29:48', '2025-12-11 05:35:53'),
(88, 57, '2025-12-11', '2025-12-11 20:34:00', '2025-12-11 23:30:00', '20:30:00', '22:00:00', 'Present', NULL, 'in', 1, '2025-12-11 11:40:17', '2025-12-11 11:49:07');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_settings`
--

CREATE TABLE `attendance_settings` (
  `id` int(11) NOT NULL,
  `late_threshold_minutes` int(11) DEFAULT 15,
  `undertime_threshold_minutes` int(11) DEFAULT 30,
  `regular_overtime_multiplier` decimal(5,2) DEFAULT 1.25,
  `holiday_overtime_multiplier` decimal(5,2) DEFAULT 2.00,
  `auto_ot_minutes` int(11) DEFAULT 30,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_settings`
--

INSERT INTO `attendance_settings` (`id`, `late_threshold_minutes`, `undertime_threshold_minutes`, `regular_overtime_multiplier`, `holiday_overtime_multiplier`, `auto_ot_minutes`, `created_at`, `updated_at`) VALUES
(1, 15, 30, 1.25, 2.00, 30, '2025-11-21 12:44:20', '2025-11-21 12:44:20');

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
(12, 'Computer Engineering', '2025-11-30 15:35:13', '2025-11-30 15:35:13'),
(13, 'Engineering Department', '2025-12-11 02:18:40', '2025-12-11 02:18:40');

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
  `rate_per_day` decimal(10,2) DEFAULT 0.00,
  `annual_paid_leave_days` int(11) DEFAULT 15,
  `annual_unpaid_leave_days` int(11) DEFAULT 5,
  `annual_sick_leave_days` int(11) DEFAULT 10,
  `avatar_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `date_of_birth` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `first_name`, `last_name`, `address`, `gender`, `marital_status`, `status`, `email`, `contact_number`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `date_joined`, `department_id`, `job_position_id`, `rate_per_hour`, `rate_per_day`, `annual_paid_leave_days`, `annual_unpaid_leave_days`, `annual_sick_leave_days`, `avatar_path`, `created_at`, `updated_at`, `date_of_birth`) VALUES
(56, 97, 'Alvin', 'Marone', 'basey samar', 'Male', 'Single', 'Active', 'marone@gmail.com', '09876543211', 'narlitosi', '09123456789', 'Father', '2025-12-11', 13, 26, 15.00, 120.00, 10, 10, 10, NULL, '2025-12-11 02:21:39', '2025-12-11 11:34:19', '2005-01-02'),
(57, 98, 'Katrina', 'Cadevero', 'Mudboron, Alangalang Leyte', 'Female', 'Single', 'Active', 'katrina@gmail.com', '09876385422', 'Lina Cadavero', '09827837284', 'Mother', '2025-12-11', 12, 26, 15.00, 120.00, 10, 10, 10, NULL, '2025-12-11 02:48:49', '2025-12-11 02:48:49', '2025-02-04'),
(58, 99, 'Alexander', 'Osias', 'So. Bugho', 'Male', 'Single', 'Active', 'alexanderosias123@gmail.com', '09305909175', 'Alexander Osias', '09305909175', 'brother', '2025-12-11', 13, 26, 15.00, 120.00, 9, 10, 9, NULL, '2025-12-11 05:39:39', '2025-12-11 11:51:26', '2013-01-01');

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('regular','special_non_working','special_working') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`id`, `name`, `type`, `start_date`, `end_date`) VALUES
(9, 'Non Working Holiday', 'special_non_working', '2025-12-08', '2025-12-08');

-- --------------------------------------------------------

--
-- Table structure for table `job_positions`
--

CREATE TABLE `job_positions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `working_hours_per_day` decimal(5,2) NOT NULL DEFAULT 8.00,
  `rate_per_day` decimal(10,2) NOT NULL DEFAULT 0.00,
  `rate_per_hour` decimal(10,2) DEFAULT 0.00,
  `payroll_frequency` enum('weekly','bi-weekly','monthly') DEFAULT 'bi-weekly',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_positions`
--

INSERT INTO `job_positions` (`id`, `name`, `working_hours_per_day`, `rate_per_day`, `rate_per_hour`, `payroll_frequency`, `created_at`, `updated_at`) VALUES
(26, 'Instructor', 8.00, 120.00, 15.00, 'bi-weekly', '2025-12-01 01:20:56', '2025-12-10 08:00:16'),
(27, 'System Administrator', 8.00, 100.00, 12.50, '', '2025-12-03 07:56:37', '2025-12-03 07:56:37');

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type` enum('Paid','Unpaid','Sick') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `deducted_from` enum('Paid','Unpaid','Sick') DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `admin_feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `employee_id`, `leave_type`, `start_date`, `end_date`, `days`, `reason`, `status`, `deducted_from`, `submitted_at`, `approved_at`, `approved_by`, `proof_path`, `admin_feedback`) VALUES
(42, 58, 'Sick', '2025-12-14', '2025-12-16', 1, 'Masakit tak ulo boss. Labi na kay mag iiba pat database. Tala ito. ', 'Approved', 'Sick', '2025-12-11 11:47:06', '2025-12-11 11:51:20', 68, 'uploads/proofs/693aaf3aafd17_headache.gif', NULL),
(43, 58, 'Paid', '2025-12-22', '2025-12-28', 1, 'Mabakasyon la anay ak sir usa ka semana. ', 'Approved', 'Paid', '2025-12-11 11:50:08', '2025-12-11 11:51:26', 68, 'uploads/proofs/693aaff053f2f_cda0805154cbc6b788fa1427c608b0c5--finger-monkeys.jpg', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `overtime_requests`
--

CREATE TABLE `overtime_requests` (
  `id` int(11) NOT NULL,
  `attendance_log_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `scheduled_end_time` time NOT NULL,
  `actual_out_time` datetime NOT NULL,
  `raw_ot_minutes` int(11) NOT NULL DEFAULT 0,
  `approved_ot_minutes` int(11) NOT NULL DEFAULT 0,
  `status` enum('Pending','Approved','Rejected','AutoApproved') DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `overtime_requests`
--

INSERT INTO `overtime_requests` (`id`, `attendance_log_id`, `employee_id`, `date`, `scheduled_end_time`, `actual_out_time`, `raw_ot_minutes`, `approved_ot_minutes`, `status`, `approved_by`, `approved_at`, `remarks`, `created_at`, `updated_at`) VALUES
(4, 88, 57, '2025-12-11', '22:00:00', '2025-12-11 23:30:00', 60, 60, 'Approved', 68, '2025-12-11 19:41:19', 'Ok', '2025-12-11 11:40:48', '2025-12-11 11:41:19');

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
(54, 'alexanderosias123@gmail.com', '784432', '2025-12-10 22:50:00', '2025-12-11 05:40:00');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `basic_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payroll_period_start` date NOT NULL,
  `payroll_period_end` date NOT NULL,
  `gross_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `overtime_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `holiday_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
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

--
-- Dumping data for table `payroll`
--

INSERT INTO `payroll` (`id`, `employee_id`, `basic_pay`, `payroll_period_start`, `payroll_period_end`, `gross_pay`, `overtime_pay`, `holiday_pay`, `philhealth_deduction`, `sss_deduction`, `pagibig_deduction`, `other_deductions`, `paid_status`, `payment_date`, `created_at`, `updated_at`) VALUES
(5, 56, 360.00, '2025-12-01', '2025-12-14', 480.00, 0.00, 120.00, 125.00, 125.00, 7.50, 22.25, 'Paid', '2025-12-11', '2025-12-11 11:36:06', '2025-12-11 11:36:11'),
(6, 57, 360.00, '2025-12-01', '2025-12-14', 360.00, 0.00, 0.00, 125.00, 125.00, 7.50, 10.25, 'Paid', '2025-12-11', '2025-12-11 11:36:06', '2025-12-11 11:36:14'),
(7, 58, 360.00, '2025-12-01', '2025-12-14', 360.00, 0.00, 0.00, 125.00, 125.00, 7.50, 10.25, 'Unpaid', NULL, '2025-12-11 11:36:07', '2025-12-11 11:36:07');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_audit`
--

CREATE TABLE `payroll_audit` (
  `id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll_audit`
--

INSERT INTO `payroll_audit` (`id`, `action`, `period_start`, `period_end`, `role_id`, `created_at`) VALUES
(8, 'finalize_payroll', '2025-12-01', '2025-12-14', 26, '2025-12-10 13:43:47'),
(9, 'finalize_payroll', '2025-12-01', '2025-12-14', 26, '2025-12-11 11:36:07');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_settings`
--

CREATE TABLE `payroll_settings` (
  `id` int(11) NOT NULL,
  `regular_holiday_rate` decimal(5,2) DEFAULT 2.00,
  `regular_holiday_ot_rate` decimal(5,2) DEFAULT 2.60,
  `special_nonworking_rate` decimal(5,2) DEFAULT 1.30,
  `special_nonworking_ot_rate` decimal(5,2) DEFAULT 1.69,
  `special_working_rate` decimal(5,2) DEFAULT 1.30,
  `special_working_ot_rate` decimal(5,2) DEFAULT 1.69,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll_settings`
--

INSERT INTO `payroll_settings` (`id`, `regular_holiday_rate`, `regular_holiday_ot_rate`, `special_nonworking_rate`, `special_nonworking_ot_rate`, `special_working_rate`, `special_working_ot_rate`, `created_at`, `updated_at`) VALUES
(1, 2.00, 2.60, 1.30, 1.69, 1.30, 1.69, '2025-11-21 12:44:20', '2025-11-21 12:44:20');

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
(65, 57, 'First:Katrina|Last:Cadevero|Position:Instructor|Joined:2025-12-11', 'qrcodes/KatrinaCadevero.png', '2025-12-11 02:48:49'),
(66, 58, 'First:Alexander|Last:Osias|Position:Instructor|Joined:2025-12-11', 'qrcodes/AlexanderOsias_58_1.png', '2025-12-11 05:39:40'),
(68, 56, 'First:Alvin|Last:Marone|Position:Instructor|Joined:2025-12-11', 'qrcodes/AlvinMarone.png', '2025-12-11 11:34:19');

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
  `break_minutes` int(11) NOT NULL DEFAULT 0,
  `is_working` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `employee_id`, `day_of_week`, `shift_name`, `start_time`, `end_time`, `break_minutes`, `is_working`, `created_at`, `updated_at`) VALUES
(100, 56, 'Thursday', 'Ethics', '13:22:00', '17:30:00', 0, 1, '2025-12-11 02:22:55', '2025-12-11 02:22:55'),
(101, 58, 'Monday', 'Regular Shift', '08:30:00', '17:30:00', 0, 1, '2025-12-11 11:35:27', '2025-12-11 11:35:27'),
(102, 57, 'Thursday', 'Night Shift', '20:30:00', '22:00:00', 0, 1, '2025-12-11 11:40:12', '2025-12-11 11:40:12');

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
(1, 'uploads/logos/logo_1763922067.jpg', 'EAAPS Admin', '2025-11-21 12:44:20', '2025-12-01 08:48:04', 10, 10, 10);

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
(79, 87, 'uploads/snapshots/snapshot_87_1765430988.png', '2025-12-11 05:29:48'),
(80, 87, 'uploads/snapshots/snapshot_87_1765431353.png', '2025-12-11 05:35:53');

-- --------------------------------------------------------

--
-- Table structure for table `special_events`
--

CREATE TABLE `special_events` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `paid` enum('yes','no','partial') NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `special_events`
--

INSERT INTO `special_events` (`id`, `name`, `start_date`, `end_date`, `paid`, `description`) VALUES
(8, 'Emergency Suspension', '2025-12-11', '2025-12-11', 'yes', 'Emergency suspension due to earthquake.');

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
-- Table structure for table `thirteenth_month_payroll`
--

CREATE TABLE `thirteenth_month_payroll` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `total_basic` decimal(12,2) NOT NULL DEFAULT 0.00,
  `thirteenth_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_status` enum('Paid','Unpaid') NOT NULL DEFAULT 'Unpaid',
  `payment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `grace_in_minutes` int(11) DEFAULT 0,
  `grace_out_minutes` int(11) DEFAULT 0,
  `company_hours_per_day` decimal(5,2) DEFAULT 8.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_date_settings`
--

INSERT INTO `time_date_settings` (`id`, `default_timezone`, `date_format`, `auto_logout_time_hours`, `created_at`, `updated_at`, `grace_in_minutes`, `grace_out_minutes`, `company_hours_per_day`) VALUES
(1, 'PHST', 'DD/MM/YYYY', 0.00000, '2025-11-21 12:44:20', '2025-12-02 17:41:28', 6, 5, 8.00);

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
(68, 'Admin', 'Admin', 'superadmin@gmail.com', '09305909175', 'Tacloban City', 12, '$2y$10$eFmGsmOld4JDMgkJunzcx.IQo6gPwS8CvtMecl0rY21mm30oZgCYy', 1, '2025-11-25 09:15:54', '2025-12-01 09:04:35', 'uploads/avatars/user_68_1764516943.png', '[\"head_admin\"]'),
(94, 'admin', 'admin', 'admin@gmail.com', '09305909175', 'So. Bugho\r\nBarangay Dampigan', 12, '$2y$10$CR/6WFBZwGu5HfvkwIf.Q.IY7S1WlYbxC9VXBdSKfdBv6ElU5wNOO', 1, '2025-12-11 01:26:55', '2025-12-11 11:52:11', NULL, '[\"admin\"]'),
(97, 'Alvin', 'Marone', 'marone@gmail.com', '09876543211', 'basey samar', 13, '$2y$10$VP3KmauW/f3fZR515JJ/xOIWBTRA1upRsQ0LtGiD0ZN6qYUppLYE.', 1, '2025-12-11 02:21:39', '2025-12-11 11:34:19', NULL, '[\"employee\"]'),
(98, 'Katrina', 'Cadevero', 'katrina@gmail.com', '09876385422', 'Mudboron, Alangalang Leyte', 12, '$2y$10$lh3wX7IbKyHRXWvVGzJPtODwNqxyRYXNfC2PCZAtdJNTiEbveNnGm', 1, '2025-12-11 02:48:49', '2025-12-11 02:50:01', NULL, '[\"employee\"]'),
(99, 'Alexander', 'Osias', 'alexanderosias123@gmail.com', '09305909175', 'So. Bugho', 13, '$2y$10$5unqPdo6h5WT/vgfTOXZtedgu5OvuLOCmxHtOLK8Lnppq4cWl4ua6', 1, '2025-12-11 05:39:39', '2025-12-11 05:39:39', NULL, '[\"employee\"]');

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
-- Indexes for table `attendance_settings`
--
ALTER TABLE `attendance_settings`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ot_attendance` (`attendance_log_id`),
  ADD KEY `idx_ot_employee_date` (`employee_id`,`date`);

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
-- Indexes for table `payroll_audit`
--
ALTER TABLE `payroll_audit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_period` (`period_start`,`period_end`),
  ADD KEY `idx_role` (`role_id`);

--
-- Indexes for table `payroll_settings`
--
ALTER TABLE `payroll_settings`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `special_events`
--
ALTER TABLE `special_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tax_deduction_settings`
--
ALTER TABLE `tax_deduction_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `thirteenth_month_payroll`
--
ALTER TABLE `thirteenth_month_payroll`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_emp_year` (`employee_id`,`year`),
  ADD KEY `idx_year_status` (`year`,`paid_status`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `attendance_settings`
--
ALTER TABLE `attendance_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `backup_restore_settings`
--
ALTER TABLE `backup_restore_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `job_positions`
--
ALTER TABLE `job_positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payroll_audit`
--
ALTER TABLE `payroll_audit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payroll_settings`
--
ALTER TABLE `payroll_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `qr_codes`
--
ALTER TABLE `qr_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT for table `school_settings`
--
ALTER TABLE `school_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `snapshots`
--
ALTER TABLE `snapshots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `special_events`
--
ALTER TABLE `special_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tax_deduction_settings`
--
ALTER TABLE `tax_deduction_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `thirteenth_month_payroll`
--
ALTER TABLE `thirteenth_month_payroll`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `time_date_settings`
--
ALTER TABLE `time_date_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

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
-- Constraints for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  ADD CONSTRAINT `fk_ot_attendance` FOREIGN KEY (`attendance_log_id`) REFERENCES `attendance_logs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ot_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
