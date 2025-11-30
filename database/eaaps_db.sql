-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 30, 2025 at 04:51 PM
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
(12, 'Computer Engineering', '2025-11-30 15:35:13', '2025-11-30 15:35:13');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `payroll_frequency` enum('daily','weekly','bi-weekly','monthly') DEFAULT 'bi-weekly',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_positions`
--

INSERT INTO `job_positions` (`id`, `name`, `working_hours_per_day`, `rate_per_day`, `rate_per_hour`, `payroll_frequency`, `created_at`, `updated_at`) VALUES
(25, 'System Administrator', 8.00, 120.00, 15.00, 'bi-weekly', '2025-11-30 15:41:04', '2025-11-30 15:41:04');

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
(1, 'uploads/logos/logo_1763922067.jpg', 'EAAPS Admins', '2025-11-21 12:44:20', '2025-11-27 17:45:32', 10, 10, 10);

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
(1, 'PHST', 'DD/MM/YYYY', 0.00000, '2025-11-21 12:44:20', '2025-11-27 17:49:22');

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
(68, 'Admin', 'Admin', 'admin@gmail.com', '09305909175', 'Tacloban City', 12, '$2y$10$eFmGsmOld4JDMgkJunzcx.IQo6gPwS8CvtMecl0rY21mm30oZgCYy', 1, '2025-11-25 09:15:54', '2025-11-30 15:35:43', 'uploads/avatars/user_68_1764516943.png', '[\"head_admin\"]');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `backup_restore_settings`
--
ALTER TABLE `backup_restore_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `job_positions`
--
ALTER TABLE `job_positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `school_settings`
--
ALTER TABLE `school_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `snapshots`
--
ALTER TABLE `snapshots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `special_events`
--
ALTER TABLE `special_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

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
