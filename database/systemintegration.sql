-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 18, 2025 at 08:00 AM
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
-- Database: `systemintegration`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_create_document_request` (IN `p_user_id` INT, IN `p_doctype_id` INT, IN `p_purpose` VARCHAR(255), IN `p_urgency` ENUM('Normal','Urgent'), IN `p_comments` VARCHAR(255))   BEGIN
    DECLARE v_fee DECIMAL(10,2);
    
    -- Get the base fee for the document type
    SELECT BaseFee INTO v_fee FROM document_types WHERE Doctype_id = p_doctype_id;
    
    -- Insert the new request
    INSERT INTO `document_requests` (
        User_id,
        Doctype_id,
        Purpose,
        Urgency,
        Comments,
        Payment_status,
        Status
    ) VALUES (
        p_user_id,
        p_doctype_id,
        p_purpose,
        p_urgency,
        p_comments,
        IF(v_fee > 0, 'Pending', 'Waived'),
        'Pending'
    );
    
    -- Log the action
    INSERT INTO `event_logs` (User_id, Action)
    VALUES (p_user_id, CONCAT('Created Request #', LAST_INSERT_ID()));
    
    SELECT LAST_INSERT_ID() AS New_Request_Id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_update_request_status` (IN `p_request_id` INT, IN `p_new_status` ENUM('Pending','Processing','Approved','Rejected','Completed'), IN `p_updated_by` INT)   BEGIN
    DECLARE v_old_status VARCHAR(50);
    
    -- Get current status
    SELECT Status INTO v_old_status 
    FROM document_requests 
    WHERE Request_id = p_request_id;
    
    -- Update the status
    UPDATE document_requests 
    SET Status = p_new_status,
        Completion_date = IF(p_new_status = 'Completed', NOW(), Completion_date)
    WHERE Request_id = p_request_id;
    
    -- Log the action
    INSERT INTO event_logs (User_id, Action)
    VALUES (p_updated_by, CONCAT('Updated Request #', p_request_id, ' from ', v_old_status, ' to ', p_new_status));
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `academic_term`
--

CREATE TABLE `academic_term` (
  `term_id` int(11) NOT NULL,
  `term_name` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity`
--

CREATE TABLE `activity` (
  `ActivityID` int(11) NOT NULL,
  `SubSchedID` int(11) DEFAULT NULL,
  `Title` varchar(200) NOT NULL,
  `Description` text DEFAULT NULL,
  `ActivityType` varchar(50) DEFAULT 'general',
  `TotalPoints` int(11) DEFAULT 100,
  `DueDate` datetime DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activitysubmission`
--

CREATE TABLE `activitysubmission` (
  `SubmissionID` int(11) NOT NULL,
  `ActivityID` int(11) DEFAULT NULL,
  `StudID` int(11) DEFAULT NULL,
  `FilePath` varchar(500) DEFAULT NULL,
  `FileName` varchar(255) DEFAULT NULL,
  `TextResponse` text DEFAULT NULL,
  `SubmittedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `Grade` decimal(5,2) DEFAULT NULL,
  `Feedback` text DEFAULT NULL,
  `GradedBy` int(11) DEFAULT NULL,
  `GradedAt` datetime DEFAULT NULL,
  `Status` varchar(20) DEFAULT 'submitted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `UserID`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(0, 4, 'login', 'guidance_users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 07:26:49'),
(1, 4, 'login', 'guidance_users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 08:31:46'),
(2, 4, 'logout', 'guidance_users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 08:34:00'),
(3, 7, 'login', 'guidance_users', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 08:36:44'),
(4, 7, 'update_profile', 'students', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 08:37:05'),
(5, 7, 'update_profile', 'students', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 08:37:08'),
(6, 7, 'update_profile', 'students', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 08:37:18'),
(7, 7, 'update_profile', 'students', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 08:37:24'),
(8, 7, 'update_profile', 'students', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 08:37:26'),
(9, 7, 'update_profile', 'students', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 08:37:40'),
(10, 7, 'update_profile', 'students', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 08:38:06'),
(11, 7, 'update_profile', 'students', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 08:38:16'),
(12, 7, 'update_profile', 'students', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 08:38:28'),
(13, 7, 'update_profile', 'students', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 08:38:36'),
(14, 7, 'logout', 'guidance_users', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 08:47:40'),
(15, 7, 'login', 'guidance_users', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 08:47:51'),
(16, 7, 'logout', 'guidance_users', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 08:52:28'),
(17, 4, 'login', 'guidance_users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 08:52:42'),
(18, 4, 'update_user', 'guidance_users', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 08:52:55'),
(19, 4, 'update_student_profile', 'students', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 08:52:55'),
(20, 4, 'create_case', 'cases', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 08:59:59'),
(21, 4, 'assign_case_counselor', 'case_counselors', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 08:59:59'),
(22, 4, 'create_user_student', 'guidance_users', 8, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 09:03:27'),
(23, 4, 'create_student_profile', 'students', 8, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 09:03:27'),
(24, 4, 'login', 'guidance_users', 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 09:06:10'),
(25, 4, 'delete_student_profile', 'students', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 09:10:05'),
(26, 4, 'delete_user', 'guidance_users', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 09:10:05'),
(27, 4, 'delete_student_profile', 'students', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 09:10:09'),
(28, 4, 'delete_user', 'guidance_users', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-15 09:10:09');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `Fname` varchar(100) NOT NULL,
  `Lname` varchar(100) NOT NULL,
  `Email` varchar(150) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `Fname`, `Lname`, `Email`, `Password`, `Role`, `user_id`, `created_at`) VALUES
(1, 'admin', 'admin', 'admin@gmail.com', '$2y$10$EJ3ojkPl/gv97TBLI9e7ZuzHZ0Wm8qR4zCsT2e4i27KdnT7DkjB4a', 'Admin', NULL, '2025-12-15 09:06:19');

-- --------------------------------------------------------

--
-- Table structure for table `allowance_releases`
--

CREATE TABLE `allowance_releases` (
  `release_id` int(11) NOT NULL,
  `scholar_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `all_users_view`
--

CREATE TABLE `all_users_view` (
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','student','instructor') DEFAULT NULL,
  `user_ref_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcement`
--

CREATE TABLE `announcement` (
  `AnnouncementID` int(11) NOT NULL,
  `SubSchedID` int(11) DEFAULT NULL,
  `Title` varchar(200) NOT NULL,
  `Content` text DEFAULT NULL,
  `PostedBy` int(11) DEFAULT NULL,
  `PostedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `application_id` int(11) NOT NULL,
  `scholar_id` int(11) DEFAULT NULL,
  `application_type` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `application_documents`
--

CREATE TABLE `application_documents` (
  `document_id` int(11) NOT NULL,
  `application_id` int(11) DEFAULT NULL,
  `document_name` varchar(150) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `application_status_history`
--

CREATE TABLE `application_status_history` (
  `history_id` int(11) NOT NULL,
  `application_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `case_id` int(11) DEFAULT NULL,
  `StudID` int(11) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `appointment_date` datetime DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `appointment_type` varchar(50) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `reminder_sent` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_weights`
--

CREATE TABLE `assessment_weights` (
  `weight_id` int(11) NOT NULL,
  `component_name` varchar(100) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignment`
--

CREATE TABLE `assignment` (
  `AssignmentID` int(11) NOT NULL,
  `SubSchedID` int(11) DEFAULT NULL,
  `Title` varchar(200) NOT NULL,
  `Description` text DEFAULT NULL,
  `Instructions` text DEFAULT NULL,
  `TotalPoints` int(11) DEFAULT 100,
  `DueDate` datetime DEFAULT NULL,
  `AllowLate` tinyint(1) DEFAULT 0,
  `IsActive` tinyint(1) DEFAULT 1,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignmentsubmission`
--

CREATE TABLE `assignmentsubmission` (
  `SubmissionID` int(11) NOT NULL,
  `AssignmentID` int(11) DEFAULT NULL,
  `StudID` int(11) DEFAULT NULL,
  `FilePath` varchar(500) DEFAULT NULL,
  `FileName` varchar(255) DEFAULT NULL,
  `FileType` varchar(50) DEFAULT NULL,
  `Comments` text DEFAULT NULL,
  `SubmittedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `Grade` decimal(5,2) DEFAULT NULL,
  `Feedback` text DEFAULT NULL,
  `GradedBy` int(11) DEFAULT NULL,
  `GradedAt` datetime DEFAULT NULL,
  `Status` varchar(20) DEFAULT 'submitted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `time_in` time NOT NULL,
  `status` enum('Present','Absent') NOT NULL,
  `marked_by` int(10) UNSIGNED DEFAULT NULL,
  `marked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `attendance_date` date DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `employee_id` int(11) DEFAULT NULL,
  `expected_start_time` time DEFAULT NULL,
  `expected_end_time` time DEFAULT NULL,
  `snapshot_path` varchar(500) DEFAULT NULL,
  `check_type` enum('in','out') DEFAULT 'in',
  `is_synced` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs_security`
--

CREATE TABLE `attendance_logs_security` (
  `log_id` int(11) NOT NULL,
  `user_security_id` int(11) DEFAULT NULL,
  `log_date` date DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_logs_security`
--

INSERT INTO `attendance_logs_security` (`log_id`, `user_security_id`, `log_date`, `time_in`, `time_out`) VALUES
(1, 1, '2025-12-15', '06:00:00', '18:00:00'),
(2, 2, '2025-12-15', '18:00:00', '06:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_queue`
--

CREATE TABLE `attendance_queue` (
  `queue_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `queued_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_queue`
--

INSERT INTO `attendance_queue` (`queue_id`, `student_id`, `subject_id`, `queued_at`) VALUES
(1, 1, 1, '2025-12-15 08:50:54'),
(2, 2, 1, '2025-12-15 08:45:54');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_schedules`
--

CREATE TABLE `attendance_schedules` (
  `schedule_id` int(11) NOT NULL,
  `schedule_name` varchar(100) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_schedules_security`
--

CREATE TABLE `attendance_schedules_security` (
  `schedule_id` int(11) NOT NULL,
  `user_security_id` int(11) DEFAULT NULL,
  `schedule_date` date DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_schedules_security`
--

INSERT INTO `attendance_schedules_security` (`schedule_id`, `user_security_id`, `schedule_date`, `time_in`, `time_out`) VALUES
(1, 1, '2025-12-16', '06:00:00', '18:00:00'),
(2, 2, '2025-12-16', '18:00:00', '06:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_settings`
--

CREATE TABLE `attendance_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_name` varchar(100) DEFAULT NULL,
  `setting_value` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `auto_ot_minutes` int(11) DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_settings_security`
--

CREATE TABLE `attendance_settings_security` (
  `setting_id` int(11) NOT NULL,
  `setting_name` varchar(100) DEFAULT NULL,
  `setting_value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_settings_security`
--

INSERT INTO `attendance_settings_security` (`setting_id`, `setting_name`, `setting_value`) VALUES
(1, 'patrol_interval_minutes', '30'),
(2, 'report_time', '08:00'),
(3, 'shift_change_time', '18:00');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_sync_queue`
--

CREATE TABLE `attendance_sync_queue` (
  `id` int(11) NOT NULL,
  `sync_type` varchar(50) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` varchar(100) NOT NULL,
  `action` varchar(20) NOT NULL DEFAULT 'insert',
  `data` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `audit_id` int(11) NOT NULL,
  `action` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`audit_id`, `action`, `description`, `performed_by`, `created_at`) VALUES
(0, 'Created new Teacher: jake123', NULL, 1, '2025-12-17 08:54:40');

-- --------------------------------------------------------

--
-- Table structure for table `backup_attendance`
--

CREATE TABLE `backup_attendance` (
  `id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `student_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `time_in` time NOT NULL,
  `status` enum('Present','Absent') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `marked_by` int(10) UNSIGNED DEFAULT NULL,
  `marked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backup_enrollments`
--

CREATE TABLE `backup_enrollments` (
  `id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `student_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backup_restore_settings`
--

CREATE TABLE `backup_restore_settings` (
  `id` int(11) NOT NULL,
  `backup_time` time DEFAULT NULL,
  `restore_enabled` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backup_students`
--

CREATE TABLE `backup_students` (
  `id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `student_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `course` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `year_level` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `qr_code_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `photo_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `class_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backup_users`
--

CREATE TABLE `backup_users` (
  `id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','teacher') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'teacher',
  `teacher_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `insId` int(11) DEFAULT NULL COMMENT 'Links to enrollment system instructors.InsID',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `billing_id` int(11) NOT NULL,
  `StudID` int(11) DEFAULT NULL,
  `fee_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `billing_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `balance` decimal(10,2) NOT NULL,
  `total_fees` decimal(10,2) DEFAULT 0.00,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing`
--

INSERT INTO `billing` (`billing_id`, `StudID`, `fee_id`, `amount`, `billing_date`, `status`, `total_amount`, `balance`, `total_fees`, `paid_amount`, `created_at`) VALUES
(1, 23, NULL, NULL, NULL, NULL, 0.00, -8000.00, 0.00, 8000.00, '2025-12-15 09:02:34'),
(2, 24, NULL, NULL, NULL, NULL, 0.00, -4000.00, 2000.00, 6000.00, '2025-12-15 09:27:38'),
(3, 2024118, NULL, NULL, NULL, NULL, 0.00, 0.00, 2462.00, 2462.00, '2025-12-17 09:26:07');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `BookID` int(11) NOT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `year_published` int(11) DEFAULT NULL,
  `copies` int(11) DEFAULT 0,
  `available` int(11) DEFAULT 0,
  `SubjectID` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `price` int(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`BookID`, `isbn`, `title`, `author`, `category`, `publisher`, `year_published`, `copies`, `available`, `SubjectID`, `created_at`, `price`) VALUES
(1, '0021', 'GenMath', 'Sia', NULL, 'Sia', 2025, 10, 10, 1, '2025-12-14 10:20:16', 0);

-- --------------------------------------------------------

--
-- Table structure for table `cases`
--

CREATE TABLE `cases` (
  `case_id` int(11) NOT NULL,
  `case_number` varchar(20) DEFAULT NULL,
  `StudID` int(11) DEFAULT NULL,
  `case_type_id` int(11) DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  `priority_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `presenting_issue` text DEFAULT NULL,
  `case_opened_date` date DEFAULT NULL,
  `case_closed_date` date DEFAULT NULL,
  `last_session_date` date DEFAULT NULL,
  `next_appointment_date` datetime DEFAULT NULL,
  `total_sessions` int(11) DEFAULT 0,
  `outcome_summary` text DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `case_counselors`
--

CREATE TABLE `case_counselors` (
  `case_counselor_id` int(11) NOT NULL,
  `case_id` int(11) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `role_in_case` varchar(50) DEFAULT NULL,
  `assigned_date` date DEFAULT NULL,
  `removed_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `case_documents`
--

CREATE TABLE `case_documents` (
  `document_id` int(11) NOT NULL,
  `case_id` int(11) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `document_name` varchar(255) DEFAULT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `document_category` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `case_statuses`
--

CREATE TABLE `case_statuses` (
  `status_id` int(11) NOT NULL,
  `status_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `color_code` varchar(7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `case_statuses`
--

INSERT INTO `case_statuses` (`status_id`, `status_name`, `description`, `color_code`) VALUES
(5, 'Active', 'Open case, currently being handled', '#007bff'),
(6, 'Follow-up Needed', 'Requires follow-up action from counselor', '#fd7e14'),
(7, 'Pending Closure', 'Case is ready for closure, awaiting final approval', '#6f42c1'),
(8, 'Closed', 'Case has been fully resolved and closed', '#6c757d');

-- --------------------------------------------------------

--
-- Table structure for table `case_types`
--

CREATE TABLE `case_types` (
  `case_type_id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `color_code` varchar(7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `case_types`
--

INSERT INTO `case_types` (`case_type_id`, `type_name`, `description`, `color_code`) VALUES
(7, 'Academic', 'Academic performance and schoolwork concerns', '#17a2b8'),
(8, 'Behavioral', 'Behavioral or conduct-related cases', '#fd7e14'),
(9, 'Social-Emotional', 'Emotional, social, or mental health concerns', '#6f42c1'),
(10, 'Career', 'Career guidance and planning', '#20c997'),
(11, 'Family', 'Family or home-related issues', '#6610f2'),
(12, 'Other', 'Cases that do not fit a specific category', '#6c757d');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `term_id` int(11) DEFAULT NULL,
  `class_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_users`
--

CREATE TABLE `class_users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','student','instructor') NOT NULL,
  `user_ref_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_users`
--

INSERT INTO `class_users` (`user_id`, `username`, `password`, `role`, `user_ref_id`, `created_at`) VALUES
(1, 'admin', '$2y$10$t4UPL80VlCodyLcclDqgie1drMS05AD6v8chB4tOcGMfRP5Py18a6', 'admin', NULL, '2025-10-28 19:28:15');

-- --------------------------------------------------------

--
-- Table structure for table `clearances`
--

CREATE TABLE `clearances` (
  `clearance_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `clearance_type` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clearances`
--

INSERT INTO `clearances` (`clearance_id`, `student_id`, `clearance_type`, `status`, `issued_at`) VALUES
(1, 1, 'Graduation Clearance', 'Pending', '2025-12-15 08:50:54'),
(2, 2, 'Transfer Clearance', 'Issued', '2025-12-14 08:50:54');

-- --------------------------------------------------------

--
-- Table structure for table `clearances_doc`
--

CREATE TABLE `clearances_doc` (
  `clearance_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `department` varchar(50) NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `signed_by` int(11) DEFAULT NULL,
  `signed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_queue`
--

CREATE TABLE `client_queue` (
  `queue_id` int(11) NOT NULL,
  `service_type_id` int(11) DEFAULT NULL,
  `client_name` varchar(150) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `queued_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client_queue`
--

INSERT INTO `client_queue` (`queue_id`, `service_type_id`, `client_name`, `status`, `queued_at`) VALUES
(1, 1, 'Juan Dela Cruz', 'Waiting', '2025-12-15 08:50:54'),
(2, 2, 'Maria Santos', 'In Service', '2025-12-15 08:45:54');

-- --------------------------------------------------------

--
-- Table structure for table `connection_debug`
--

CREATE TABLE `connection_debug` (
  `id` int(11) NOT NULL,
  `source` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_code` varchar(50) DEFAULT NULL,
  `course_name` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `department` varchar(255) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `status` enum('Active','Inactive','Archived') DEFAULT 'Active',
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `current_backup_attendance`
--

CREATE TABLE `current_backup_attendance` (
  `id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `student_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `time_in` time NOT NULL,
  `status` enum('Present','Absent') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `marked_by` int(10) UNSIGNED DEFAULT NULL,
  `marked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `current_backup_enrollments`
--

CREATE TABLE `current_backup_enrollments` (
  `id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `student_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `current_backup_students`
--

CREATE TABLE `current_backup_students` (
  `id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `student_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `course` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `year_level` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `qr_code_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `photo_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `class_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `current_backup_subjects`
--

CREATE TABLE `current_backup_subjects` (
  `id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `subject_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `subject_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `units` int(11) NOT NULL DEFAULT 3,
  `schedule` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `room` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `instructor` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `teacher_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `current_backup_users`
--

CREATE TABLE `current_backup_users` (
  `id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','teacher') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'teacher',
  `teacher_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `insId` int(11) DEFAULT NULL COMMENT 'Links to enrollment system instructors.InsID',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`, `created_at`, `updated_at`) VALUES
(1, 'System Administrator', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `dept`
--

CREATE TABLE `dept` (
  `DeptID` int(11) NOT NULL,
  `DeptName` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dept`
--

INSERT INTO `dept` (`DeptID`, `DeptName`) VALUES
(4, 'Green Dragons'),
(5, 'Knights and the Nightingale'),
(3, 'LA Warriors'),
(1, 'Soaring Phoenix'),
(2, 'Super Tycoons');

-- --------------------------------------------------------

--
-- Table structure for table `document_requests`
--

CREATE TABLE `document_requests` (
  `Request_id` int(11) NOT NULL,
  `User_id` int(11) NOT NULL,
  `Doctype_id` int(11) NOT NULL,
  `Purpose` varchar(255) NOT NULL,
  `Urgency` enum('Normal','Urgent') NOT NULL,
  `processing_days` int(11) DEFAULT 3,
  `requires_clearance` tinyint(1) DEFAULT 0,
  `is_digital` tinyint(1) DEFAULT 1,
  `Status` enum('Pending','Processing','Approved','Rejected','Completed') DEFAULT 'Pending' COMMENT 'Current status of the request',
  `clearance_status` enum('Pending','In Progress','Completed') DEFAULT 'Pending',
  `Payment_status` enum('Pending','For Cash Payment','Paid','Rejected','Waived') NOT NULL DEFAULT 'Pending',
  `Comments` varchar(255) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `Request_date` datetime DEFAULT current_timestamp(),
  `expected_release_date` date DEFAULT NULL,
  `Completion_date` datetime DEFAULT NULL,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Payment_id` int(11) DEFAULT NULL,
  `pickup_schedule_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores document requests from users';

--
-- Triggers `document_requests`
--
DELIMITER $$
CREATE TRIGGER `check_completion_date` BEFORE UPDATE ON `document_requests` FOR EACH ROW BEGIN
    IF NEW.Completion_date IS NOT NULL AND NEW.Completion_date < NEW.Request_date THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Completion date cannot be before request date';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_count_daily_request` BEFORE INSERT ON `document_requests` FOR EACH ROW BEGIN
    DECLARE today_count INT;
    DECLARE max_limit INT DEFAULT 30;
    
    -- Get or create today's counter
    INSERT IGNORE INTO `request_counters` (`date`) VALUES (CURDATE());
    
    -- Check limit
    SELECT `count`, `max_limit` INTO today_count, max_limit
    FROM `request_counters` WHERE `date` = CURDATE();
    
    IF today_count >= max_limit THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Daily request limit reached. Please try again tomorrow.';
    END IF;
    
    -- Increment counter
    UPDATE `request_counters` SET `count` = `count` + 1 WHERE `date` = CURDATE();
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `validate_status_transition` BEFORE UPDATE ON `document_requests` FOR EACH ROW BEGIN
    DECLARE valid_transition BOOLEAN DEFAULT FALSE;
    
    -- Define valid status transitions
    SET valid_transition = CASE
        WHEN OLD.Status = 'Pending' AND NEW.Status IN ('Processing', 'Approved', 'Rejected') THEN TRUE
        WHEN OLD.Status = 'Processing' AND NEW.Status IN ('Approved', 'Rejected') THEN TRUE
        WHEN OLD.Status = 'Approved' AND NEW.Status IN ('Completed', 'Rejected') THEN TRUE
        WHEN OLD.Status = 'Rejected' AND NEW.Status = 'Pending' THEN TRUE
        WHEN OLD.Status = NEW.Status THEN TRUE -- Same status is allowed
        ELSE FALSE
    END;
    
    IF NOT valid_transition THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid status transition';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `document_types`
--

CREATE TABLE `document_types` (
  `Doctype_id` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Requirements_approval` tinyint(4) DEFAULT 0,
  `requires_clearance` tinyint(4) DEFAULT 0,
  `Description` text DEFAULT NULL,
  `BaseFee` decimal(10,2) DEFAULT 0.00,
  `processing_days` int(11) DEFAULT 3,
  `Digital` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_types`
--

INSERT INTO `document_types` (`Doctype_id`, `Name`, `Requirements_approval`, `requires_clearance`, `Description`, `BaseFee`, `processing_days`, `Digital`, `created_at`, `updated_at`) VALUES
(1, 'Transcript of Records (TOR)', 1, 0, 'Official academic record of courses taken and grades earned', 200.00, 3, 1, '2025-12-12 06:29:33', '2025-12-12 06:29:33'),
(2, 'Certificate of Enrollment (COE)', 0, 0, 'Proof of current enrollment status', 0.00, 3, 1, '2025-12-12 06:29:33', '2025-12-12 06:29:33'),
(3, 'Good Moral Certificate (GMC)', 0, 0, 'Certification of good moral character', 0.00, 3, 1, '2025-12-12 06:29:33', '2025-12-12 06:29:33'),
(4, 'Diploma', 1, 0, 'Official diploma or degree certificate', 500.00, 3, 1, '2025-12-12 06:29:33', '2025-12-12 06:29:33'),
(5, 'Certificate of Graduation', 1, 0, 'Official certificate confirming completion of degree program', 150.00, 3, 1, '2025-12-12 06:29:33', '2025-12-12 06:29:33'),
(6, 'Certificate of Completion', 0, 0, 'Certificate of completion for specific courses or programs', 100.00, 3, 1, '2025-12-12 06:29:33', '2025-12-12 06:29:33'),
(7, 'Certified True Copy', 0, 0, 'Authentication and certified true copies of academic documents', 50.00, 3, 1, '2025-12-12 06:29:33', '2025-12-12 06:29:33'),
(8, 'Recommendation Letter', 0, 0, 'Official letter of recommendation from faculty or administration', 0.00, 3, 1, '2025-12-12 06:29:33', '2025-12-12 06:29:33'),
(10, 'Verification', 0, 1, 'For other document papers', 200.00, 3, 1, '2025-12-17 02:40:31', '2025-12-17 02:40:49'),
(14, 'ahahahaa', 0, 0, 'haahahaha', 30.00, 3, 0, '2025-12-17 09:14:24', '2025-12-17 09:14:24'),
(15, 'hahahah', 0, 1, 'hasjhdsahjdjsa', 878.00, 3, 1, '2025-12-17 09:23:44', '2025-12-17 09:23:44');

-- --------------------------------------------------------

--
-- Table structure for table `eaaps_attendance_settings`
--

CREATE TABLE `eaaps_attendance_settings` (
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
-- Dumping data for table `eaaps_attendance_settings`
--

INSERT INTO `eaaps_attendance_settings` (`id`, `late_threshold_minutes`, `undertime_threshold_minutes`, `regular_overtime_multiplier`, `holiday_overtime_multiplier`, `auto_ot_minutes`, `created_at`, `updated_at`) VALUES
(1, 15, 30, 1.25, 2.00, 30, '2025-11-21 04:44:20', '2025-11-21 04:44:20');

-- --------------------------------------------------------

--
-- Table structure for table `eaaps_payroll_settings`
--

CREATE TABLE `eaaps_payroll_settings` (
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
-- Dumping data for table `eaaps_payroll_settings`
--

INSERT INTO `eaaps_payroll_settings` (`id`, `regular_holiday_rate`, `regular_holiday_ot_rate`, `special_nonworking_rate`, `special_nonworking_ot_rate`, `special_working_rate`, `special_working_ot_rate`, `created_at`, `updated_at`) VALUES
(1, 2.00, 2.60, 1.30, 1.69, 1.30, 1.69, '2025-11-21 04:44:20', '2025-11-21 04:44:20');

-- --------------------------------------------------------

--
-- Table structure for table `eaaps_school_settings`
--

CREATE TABLE `eaaps_school_settings` (
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
-- Dumping data for table `eaaps_school_settings`
--

INSERT INTO `eaaps_school_settings` (`id`, `logo_path`, `system_name`, `created_at`, `updated_at`, `annual_paid_leave_days`, `annual_unpaid_leave_days`, `annual_sick_leave_days`) VALUES
(1, 'uploads/logos/logo_1763922067.jpg', 'EAAPS Admin', '2025-11-21 04:44:20', '2025-12-01 00:48:04', 10, 10, 10);

-- --------------------------------------------------------

--
-- Table structure for table `eaaps_tax_deduction_settings`
--

CREATE TABLE `eaaps_tax_deduction_settings` (
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
-- Dumping data for table `eaaps_tax_deduction_settings`
--

INSERT INTO `eaaps_tax_deduction_settings` (`id`, `philhealth_rate`, `philhealth_min`, `philhealth_max`, `philhealth_split_5050`, `pagibig_threshold`, `pagibig_employee_low_rate`, `pagibig_employee_high_rate`, `pagibig_employer_rate`, `sss_msc_min`, `sss_msc_max`, `sss_er_contribution`, `sss_ee_contribution`, `income_tax_rate`, `tax_calculation_rule`, `custom_tax_formula`, `auto_apply_deductions`, `created_at`, `updated_at`) VALUES
(1, 5.00, 500.00, 5000.00, 1, 1500.00, 1.00, 2.00, 2.00, 10000, 20000, 1160.00, 450.00, 10.00, 'flat', NULL, 1, '2025-11-21 04:44:20', '2025-11-21 04:44:20');

-- --------------------------------------------------------

--
-- Table structure for table `eaaps_time_date_settings`
--

CREATE TABLE `eaaps_time_date_settings` (
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
-- Dumping data for table `eaaps_time_date_settings`
--

INSERT INTO `eaaps_time_date_settings` (`id`, `default_timezone`, `date_format`, `auto_logout_time_hours`, `created_at`, `updated_at`, `grace_in_minutes`, `grace_out_minutes`, `company_hours_per_day`) VALUES
(1, 'PHST', 'DD/MM/YYYY', 0.00000, '2025-11-21 04:44:20', '2025-12-02 09:41:28', 6, 5, 8.00);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `position_id` int(11) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `marital_status` enum('Single','Married','Divorced','Widowed','Other') DEFAULT 'Single',
  `status` enum('Active','Inactive','On Leave') DEFAULT 'Active',
  `email` varchar(255) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_relationship` varchar(50) DEFAULT NULL,
  `rate_per_hour` decimal(10,2) DEFAULT 0.00,
  `rate_per_day` decimal(10,2) DEFAULT 0.00,
  `annual_paid_leave_days` int(11) DEFAULT 15,
  `annual_unpaid_leave_days` int(11) DEFAULT 5,
  `annual_sick_leave_days` int(11) DEFAULT 10,
  `avatar_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `user_id`, `first_name`, `last_name`, `department_id`, `position_id`, `hire_date`, `address`, `gender`, `marital_status`, `status`, `email`, `contact_number`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `rate_per_hour`, `rate_per_day`, `annual_paid_leave_days`, `annual_unpaid_leave_days`, `annual_sick_leave_days`, `avatar_path`, `created_at`, `updated_at`, `date_of_birth`) VALUES
(1, 0, 'Alexander', 'Osias', 1, 1, '2025-12-18', 'So. Bugho', 'Male', 'Single', 'Active', 'alexanderosias123@gmail.com', '09305909175', 'Alexander Osias', '09305909175', 'Father', 15.00, 120.00, 10, 10, 10, NULL, NULL, NULL, '2003-04-05');

-- --------------------------------------------------------

--
-- Table structure for table `employee_schedules`
--

CREATE TABLE `employee_schedules` (
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
-- Table structure for table `enrollment`
--

CREATE TABLE `enrollment` (
  `EnrollID` int(11) NOT NULL,
  `StudID` int(11) NOT NULL,
  `EPeriodID` int(11) DEFAULT NULL,
  `Semester` varchar(10) DEFAULT NULL,
  `Summer` tinyint(1) DEFAULT 0,
  `Status` varchar(20) DEFAULT 'pending',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `SubSchedID` int(11) DEFAULT NULL,
  `EnrollDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollment`
--

INSERT INTO `enrollment` (`EnrollID`, `StudID`, `EPeriodID`, `Semester`, `Summer`, `Status`, `CreatedAt`, `SubSchedID`, `EnrollDate`) VALUES
(0, 2024111, 1, '1st Sem', 0, 'completed', '2025-12-17 07:22:09', NULL, '2025-12-17 09:18:33');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `enrollment_date` date DEFAULT NULL,
  `StudID` int(11) NOT NULL COMMENT 'FK to students table',
  `schedule_id` int(11) NOT NULL COMMENT 'FK to schedules table',
  `status` enum('Enrolled','Waitlist','Dropped') NOT NULL COMMENT 'Enrollment status'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`enrollment_id`, `student_id`, `class_id`, `enrollment_date`, `StudID`, `schedule_id`, `status`) VALUES
(4, NULL, NULL, NULL, 2024110, 1001, 'Waitlist'),
(8, NULL, NULL, NULL, 1, 1006, 'Enrolled'),
(9, NULL, NULL, NULL, 1, 1003, 'Enrolled'),
(10, NULL, NULL, NULL, 12345, 1003, 'Enrolled'),
(11, NULL, NULL, NULL, 1, 1011, 'Enrolled'),
(13, NULL, NULL, NULL, 12345, 1007, 'Enrolled'),
(14, NULL, NULL, NULL, 6789, 1003, 'Enrolled'),
(15, NULL, NULL, NULL, 6789, 1013, 'Enrolled'),
(16, NULL, NULL, NULL, 3457, 1013, 'Enrolled');

-- --------------------------------------------------------

--
-- Table structure for table `enrollperiod`
--

CREATE TABLE `enrollperiod` (
  `EPeriodID` int(11) NOT NULL,
  `SchoolYear` varchar(9) NOT NULL,
  `Semester` varchar(20) DEFAULT '1st Sem',
  `CreatedAt` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollperiod`
--

INSERT INTO `enrollperiod` (`EPeriodID`, `SchoolYear`, `Semester`, `CreatedAt`) VALUES
(1, '2024-2025', '1st Sem', '2025-11-08');

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `equipment_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `equipment_name` varchar(150) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `status` enum('available','in_use','maintenance','disposed') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_logs`
--

CREATE TABLE `event_logs` (
  `event_id` int(11) NOT NULL,
  `event_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_logs`
--

INSERT INTO `event_logs` (`event_id`, `event_type`, `description`, `created_at`) VALUES
(4, 'System Update', 'Security patches applied', '2025-12-14 08:50:54'),
(5, 'Database Maintenance', 'Index optimization completed', '2025-12-13 08:50:54');

-- --------------------------------------------------------

--
-- Table structure for table `event_logs_doc`
--

CREATE TABLE `event_logs_doc` (
  `Log_id` int(11) NOT NULL,
  `User_id` int(11) NOT NULL,
  `Action` varchar(255) NOT NULL,
  `Log_time` datetime DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_logs_doc`
--

INSERT INTO `event_logs_doc` (`Log_id`, `User_id`, `Action`, `Log_time`, `created_at`, `updated_at`) VALUES
(1, 6, 'Logged out', '2025-11-22 20:49:48', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(4, 6, 'Registrar', '2025-11-22 21:20:39', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(5, 6, 'Approved Request #2', '2025-11-22 21:21:09', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(6, 6, 'Logged out', '2025-11-23 05:36:54', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(7, 7, 'Admin', '2025-11-23 05:37:04', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(8, 7, 'Admin', '2025-11-23 06:40:46', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(9, 7, 'Logged out', '2025-11-23 09:03:34', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(10, 7, 'Admin', '2025-11-23 09:03:38', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(11, 7, 'Logged out', '2025-11-23 09:03:48', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(12, 6, 'Registrar', '2025-11-23 09:03:54', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(13, 6, 'Logged out', '2025-11-23 11:17:50', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(14, 7, 'Admin', '2025-11-23 11:17:54', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(15, 7, 'Admin', '2025-11-23 19:53:05', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(16, 7, 'Logged out', '2025-11-23 20:20:15', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(17, 6, 'Registrar', '2025-11-24 07:48:46', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(18, 6, 'Completed Request #2', '2025-11-24 07:48:54', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(19, 6, 'Approved Request #1', '2025-11-24 08:29:53', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(20, 6, 'Approved Request #1', '2025-11-24 08:31:10', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(21, 6, 'Logged out', '2025-11-24 08:32:02', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(24, 6, 'Registrar', '2025-11-24 08:33:03', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(25, 6, 'Approved Request #3', '2025-11-24 08:33:10', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(26, 6, 'Logged out', '2025-11-24 10:39:53', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(28, 7, 'Admin', '2025-11-24 12:14:36', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(29, 7, 'Logged out', '2025-11-24 12:14:46', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(33, 6, 'Registrar', '2025-11-24 12:26:38', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(34, 6, 'Logged out', '2025-11-24 12:27:08', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(37, 7, 'Admin', '2025-11-24 12:28:23', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(38, 7, 'Logged out', '2025-11-24 12:35:51', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(39, 6, 'Registrar', '2025-11-24 12:35:56', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(40, 6, 'Completed Request #3', '2025-11-24 12:36:21', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(41, 6, 'Logged out', '2025-11-24 12:38:04', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(46, 6, 'Registrar', '2025-11-24 12:41:21', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(47, 6, 'Approved Request #5', '2025-11-24 12:41:40', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(48, 6, 'Marked Request #5 as Processing', '2025-11-24 12:41:46', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(49, 6, 'Logged out', '2025-11-24 12:42:01', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(52, 6, 'Registrar', '2025-11-24 12:42:33', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(53, 7, 'Admin', '2025-11-24 19:38:21', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(54, 7, 'Approved Request #5', '2025-11-24 19:38:43', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(55, 7, 'Approved Request #4', '2025-11-24 19:38:52', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(56, 7, 'Logged out', '2025-11-24 19:39:00', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(59, 7, 'Admin', '2025-11-24 19:40:46', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(60, 7, 'Approved Request #6', '2025-11-24 19:40:54', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(61, 7, 'Approved Request #6', '2025-11-24 19:41:02', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(62, 7, 'Logged out', '2025-11-24 19:41:12', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(65, 7, 'Admin', '2025-11-24 19:44:06', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(66, 7, 'Logged out', '2025-11-24 19:57:08', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(70, 6, 'Registrar', '2025-11-24 19:58:42', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(71, 6, 'Logged out', '2025-11-24 19:59:31', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(75, 7, 'Admin', '2025-11-24 20:00:30', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(76, 7, 'Logged out', '2025-11-24 20:23:56', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(78, 6, 'Registrar', '2025-11-24 20:24:41', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(81, 6, 'Registrar', '2025-11-24 20:52:11', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(82, 6, 'Logged out', '2025-11-24 21:14:48', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(85, 6, 'Registrar', '2025-11-24 21:15:19', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(86, 6, 'Completed Request #5', '2025-11-24 22:12:48', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(87, 6, 'Logged out', '2025-11-24 22:13:20', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(91, 6, 'Registrar', '2025-11-24 22:14:17', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(92, 6, 'Logged out', '2025-11-24 22:41:48', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(97, 6, 'Registrar', '2025-11-24 22:48:54', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(98, 6, 'Logged out', '2025-11-24 22:49:59', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(103, 6, 'Registrar', '2025-11-24 22:50:45', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(104, 6, 'Completed Request #4', '2025-11-24 22:51:12', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(105, 6, 'Logged out', '2025-11-24 22:51:55', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(108, 7, 'Admin', '2025-11-24 22:52:17', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(111, 6, 'Registrar', '2025-11-26 08:40:50', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(112, 6, 'Logged out', '2025-11-26 08:40:59', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(113, 7, 'Admin', '2025-11-26 08:41:03', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(114, 7, 'Logged out', '2025-11-26 08:41:32', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(118, 6, 'Registrar', '2025-11-26 08:53:25', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(119, 6, 'Approved Request #7', '2025-11-26 08:53:36', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(124, 6, 'Registrar', '2025-11-29 22:15:10', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(125, 6, 'Approved Request #8', '2025-11-29 22:16:10', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(126, 6, 'Logged out', '2025-11-29 22:16:32', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(131, 6, 'Registrar', '2025-11-29 22:17:44', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(132, 6, 'Logged out', '2025-11-29 22:18:14', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(133, 6, 'Registrar', '2025-11-29 22:18:49', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(134, 7, 'Admin', '2025-12-03 11:28:18', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(135, 7, 'Logged out', '2025-12-03 11:28:36', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(138, 6, 'Registrar', '2025-12-03 11:29:26', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(139, 6, 'Logged out', '2025-12-03 11:32:56', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(145, 6, 'Registrar', '2025-12-03 16:31:05', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(146, 6, 'Approved Request #10', '2025-12-03 16:31:12', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(147, 6, 'Logged out', '2025-12-03 16:32:01', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(152, 6, 'Registrar', '2025-12-03 17:07:35', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(153, 6, 'Logged out', '2025-12-03 17:08:46', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(154, 7, 'Admin', '2025-12-03 17:08:51', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(155, 7, 'Logged out', '2025-12-03 17:09:51', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(158, 7, 'Admin', '2025-12-10 09:19:28', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(159, 7, 'Logged out', '2025-12-10 09:19:39', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(161, 7, 'Admin', '2025-12-10 10:21:14', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(163, 7, 'Admin', '2025-12-10 10:30:56', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(164, 7, 'Logged out', '2025-12-10 10:38:16', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(165, 6, 'Registrar', '2025-12-10 10:38:23', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(166, 6, 'Approved Request #12', '2025-12-10 10:38:29', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(167, 6, 'Approved Request #12', '2025-12-10 10:38:37', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(168, 6, 'Registrar', '2025-12-12 13:28:14', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(169, 6, 'Logged out', '2025-12-12 14:09:31', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(172, 6, 'Registrar', '2025-12-12 14:41:08', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(173, 6, 'Logged out', '2025-12-12 14:42:20', '2025-12-12 15:34:19', '2025-12-12 15:34:19'),
(180, 6, 'Registrar', '2025-12-14 20:38:45', '2025-12-14 12:38:45', '2025-12-14 12:38:45'),
(181, 6, 'Added new document type: asdsadasd', '2025-12-14 21:10:40', '2025-12-14 13:10:40', '2025-12-14 13:10:40'),
(182, 6, 'Deactivated document type ID: 9', '2025-12-14 21:10:58', '2025-12-14 13:10:58', '2025-12-14 13:10:58'),
(183, 6, 'Logged out', '2025-12-14 22:08:44', '2025-12-14 14:08:44', '2025-12-14 14:08:44'),
(185, 6, 'Registrar', '2025-12-15 10:44:40', '2025-12-15 02:44:40', '2025-12-15 02:44:40'),
(188, 6, 'Registrar', '2025-12-15 17:22:40', '2025-12-15 09:22:40', '2025-12-15 09:22:40'),
(189, 6, 'Logged out', '2025-12-15 17:24:03', '2025-12-15 09:24:03', '2025-12-15 09:24:03'),
(201, 12, 'Student', '2025-12-17 10:34:27', '2025-12-17 02:34:27', '2025-12-17 02:34:27'),
(202, 12, 'Logged out', '2025-12-17 10:34:48', '2025-12-17 02:34:48', '2025-12-17 02:34:48'),
(203, 6, 'Registrar', '2025-12-17 10:34:53', '2025-12-17 02:34:53', '2025-12-17 02:34:53'),
(204, 6, 'Added new document type: Verification', '2025-12-17 10:40:31', '2025-12-17 02:40:31', '2025-12-17 02:40:31'),
(205, 6, 'Activated document type ID: 10', '2025-12-17 10:40:49', '2025-12-17 02:40:49', '2025-12-17 02:40:49'),
(206, 6, 'Logged out', '2025-12-17 10:40:56', '2025-12-17 02:40:56', '2025-12-17 02:40:56'),
(207, 13, 'Student', '2025-12-17 11:06:48', '2025-12-17 03:06:48', '2025-12-17 03:06:48'),
(208, 13, 'Logged out', '2025-12-17 11:07:52', '2025-12-17 03:07:52', '2025-12-17 03:07:52'),
(209, 13, 'Student', '2025-12-17 11:07:56', '2025-12-17 03:07:56', '2025-12-17 03:07:56'),
(210, 13, 'Logged out', '2025-12-17 11:07:58', '2025-12-17 03:07:58', '2025-12-17 03:07:58'),
(211, 14, 'Student', '2025-12-17 11:13:14', '2025-12-17 03:13:14', '2025-12-17 03:13:14'),
(212, 14, 'Logged out', '2025-12-17 11:16:18', '2025-12-17 03:16:18', '2025-12-17 03:16:18'),
(213, 7, 'Admin', '2025-12-17 11:16:40', '2025-12-17 03:16:40', '2025-12-17 03:16:40'),
(214, 7, 'Logged out', '2025-12-17 11:16:42', '2025-12-17 03:16:42', '2025-12-17 03:16:42'),
(215, 6, 'Registrar', '2025-12-17 11:16:47', '2025-12-17 03:16:47', '2025-12-17 03:16:47'),
(216, 15, 'Student', '2025-12-17 15:18:22', '2025-12-17 07:18:22', '2025-12-17 07:18:22'),
(217, 15, 'Logged out', '2025-12-17 15:18:36', '2025-12-17 07:18:36', '2025-12-17 07:18:36');

-- --------------------------------------------------------

--
-- Table structure for table `fees`
--

CREATE TABLE `fees` (
  `fee_id` int(11) NOT NULL,
  `fee_name` varchar(150) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fee_code` varchar(20) NOT NULL,
  `school_year` varchar(9) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `course` varchar(100) NOT NULL DEFAULT 'ALL',
  `year_level` varchar(20) NOT NULL DEFAULT 'ALL'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fees`
--

INSERT INTO `fees` (`fee_id`, `fee_name`, `amount`, `description`, `created_at`, `fee_code`, `school_year`, `semester`, `updated_at`, `course`, `year_level`) VALUES
(1, NULL, 3600.00, 'BSIT Prelim Tuition Fee Payment', '2025-12-16 16:40:24', 'BSIT_PTUITION', '2025 - 26', '1st Semester', '2025-12-17 00:40:24', 'BSIT', '1st Year'),
(4, 'Medical Fee', 300.00, 'Health services fee', '2025-12-15 08:50:54', '', '', '', '2025-12-15 16:50:54', 'ALL', 'ALL'),
(5, NULL, 2000.00, 'Prelim Tuition Fee', '2025-12-15 08:58:33', 'TUITION', '2025-26', '1st Sem', '2025-12-15 16:58:33', 'BSIT', '1st Year'),
(6, NULL, 750.00, 'ID FEE', '2025-12-17 09:08:53', 'ID FEE', 'ALL', 'ALL', '2025-12-17 17:08:53', 'ALL', 'ALL'),
(8, NULL, 1231.00, 'qwrqwq', '2025-12-17 09:25:59', 'qwrq', '2025-26', '1st Semester', '2025-12-17 17:25:59', 'Bachelor of Science in Information Technology', '1st Year'),
(9, NULL, 123114.00, 'qwrtqwe', '2025-12-17 09:27:16', 'qtqwq', '2025-26', '1st Semester', '2025-12-17 17:27:16', 'Bachelor of Science in Information Technology', '1st Year');

-- --------------------------------------------------------

--
-- Table structure for table `gpa`
--

CREATE TABLE `gpa` (
  `gpa_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `gpa_value` decimal(4,2) DEFAULT NULL,
  `calculated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `standing` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gpa`
--

INSERT INTO `gpa` (`gpa_id`, `student_id`, `gpa_value`, `calculated_at`, `standing`) VALUES
(1, 1, 3.50, '2025-12-15 08:50:54', ''),
(2, 2, 3.25, '2025-12-15 08:50:54', '');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `grade_id` int(11) NOT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `grade` decimal(5,2) DEFAULT NULL,
  `StudID` varchar(50) NOT NULL,
  `subject_code` varchar(50) NOT NULL,
  `midterm` decimal(5,2) DEFAULT 0.00,
  `final` decimal(5,2) DEFAULT 0.00,
  `final_grade` decimal(5,2) DEFAULT 0.00,
  `status` varchar(10) DEFAULT 'Enrolled',
  `year_level` varchar(20) DEFAULT NULL,
  `course` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gradesummary`
--

CREATE TABLE `gradesummary` (
  `GradeID` int(11) NOT NULL,
  `StudID` int(11) DEFAULT NULL,
  `SubSchedID` int(11) DEFAULT NULL,
  `QuizGrade` decimal(5,2) DEFAULT 0.00,
  `AssignmentGrade` decimal(5,2) DEFAULT 0.00,
  `ActivityGrade` decimal(5,2) DEFAULT 0.00,
  `FinalGrade` decimal(5,2) DEFAULT 0.00,
  `Remarks` varchar(50) DEFAULT NULL,
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade_scale`
--

CREATE TABLE `grade_scale` (
  `scale_id` int(11) NOT NULL,
  `min_grade` decimal(5,2) DEFAULT NULL,
  `max_grade` decimal(5,2) DEFAULT NULL,
  `equivalent` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grade_scale`
--

INSERT INTO `grade_scale` (`scale_id`, `min_grade`, `max_grade`, `equivalent`) VALUES
(1, 97.00, 100.00, '1.00'),
(2, 94.00, 96.99, '1.25'),
(3, 91.00, 93.99, '1.50'),
(4, 88.00, 90.99, '1.75'),
(5, 85.00, 87.99, '2.00');

-- --------------------------------------------------------

--
-- Table structure for table `grading_periods`
--

CREATE TABLE `grading_periods` (
  `period_id` int(11) NOT NULL,
  `term_id` int(11) DEFAULT NULL,
  `period_name` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guards`
--

CREATE TABLE `guards` (
  `guard_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guards`
--

INSERT INTO `guards` (`guard_id`, `username`, `password`, `full_name`, `is_active`) VALUES
(1, 'guard1', '$2y$10$MLxEKk2ip6flfvSD4zZte.mYvCjOlAaRlLA7H5DkPhOFfVsxNCiqq', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `guidance_settings`
--

CREATE TABLE `guidance_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guidance_settings`
--

INSERT INTO `guidance_settings` (`setting_id`, `setting_key`, `setting_value`, `description`, `category`, `updated_by`, `updated_at`) VALUES
(4, 'system_name', 'ADFC Guidance System', 'Name of the guidance system', 'general', 1, '2025-12-08 08:05:22'),
(5, 'school_year', '2024-2025', 'Current academic school year', 'general', 1, '2025-12-08 08:05:22'),
(6, 'timezone', 'Asia/Manila', 'System timezone for all date/time operations', 'general', 1, '2025-12-08 08:05:22'),
(7, 'date_format', 'MM/DD/YYYY', 'Preferred date display format', 'general', 1, '2025-12-08 08:05:22'),
(8, 'max_users', '50', 'Maximum number of users allowed in the system', 'user_management', 1, '2025-12-08 08:05:22'),
(9, 'session_timeout', '30', 'User session timeout in minutes', 'user_management', 1, '2025-12-08 08:05:22'),
(10, 'allow_registration', '1', 'Allow new users to register themselves', 'user_management', 1, '2025-12-08 08:05:22'),
(11, 'require_approval', '0', 'New users require admin approval', 'user_management', 1, '2025-12-08 08:05:22'),
(12, 'email_notifications', '1', 'Send notifications via email', 'notifications', 1, '2025-12-08 08:05:22'),
(13, 'appointment_reminders', '1', 'Send appointment reminders', 'notifications', 1, '2025-12-08 08:05:22'),
(14, 'case_updates', '0', 'Notify on case status changes', 'notifications', 1, '2025-12-08 08:05:22'),
(15, 'reminder_time', '1 hour', 'Time before appointments to send reminders', 'notifications', 1, '2025-12-08 08:05:22'),
(16, 'backup_frequency', 'Weekly', 'Frequency of automatic database backups', 'data_management', 1, '2025-12-08 08:05:22'),
(17, 'retention_period', '3 years', 'How long to keep records before archiving', 'data_management', 1, '2025-12-08 08:05:22'),
(18, 'anonymize_records', '0', 'Remove personal data from old records', 'data_management', 1, '2025-12-08 08:05:22'),
(19, 'two_factor_auth', '0', 'Require 2FA for all users', 'security', 1, '2025-11-23 11:13:37'),
(20, 'password_policy', '1', 'Require strong passwords', 'security', 1, '2025-12-08 08:05:22'),
(21, 'password_expiry', '90', 'Number of days before passwords expire', 'security', 1, '2025-12-08 08:05:22'),
(22, 'login_attempts', '5', 'Maximum failed login attempts before lockout', 'security', 1, '2025-12-08 08:05:22'),
(23, 'theme', 'Light', 'System visual theme', 'appearance', 1, '2025-12-08 08:05:22'),
(24, 'language', 'English', 'System language', 'appearance', 1, '2025-12-08 08:05:22'),
(25, 'compact_mode', '0', 'Use compact interface elements', 'appearance', 1, '2025-12-08 08:05:23'),
(26, 'high_contrast', '0', 'Increase color contrast for accessibility', 'appearance', 1, '2025-12-08 08:05:23'),
(27, 'default_reminder_hours', '24', 'Default reminder hours before appointment', 'appointments', 1, '2025-12-05 22:25:35');

-- --------------------------------------------------------

--
-- Table structure for table `guidance_users`
--

CREATE TABLE `guidance_users` (
  `UserID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `guidance_users`
--

INSERT INTO `guidance_users` (`UserID`, `Username`, `email`, `Password`, `Role`) VALUES
(1, 'adfcguidance', 'adfcguidance@example.com', '$2y$10$njZ4dhYYsH4JUyf2T66tX.STI3.ra0I00R9wXRdspnwFgjSqlAbLW', 'Admin');

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `holiday_id` int(11) NOT NULL,
  `holiday_name` varchar(100) DEFAULT NULL,
  `holiday_date` date DEFAULT NULL,
  `holiday_type` enum('regular','special_non_working','special_working') DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `honors_classification`
--

CREATE TABLE `honors_classification` (
  `honor_id` int(11) NOT NULL,
  `min_gpa` decimal(4,2) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `honors_classification`
--

INSERT INTO `honors_classification` (`honor_id`, `min_gpa`, `title`) VALUES
(1, 3.80, 'Summa Cum Laude'),
(2, 3.60, 'Magna Cum Laude'),
(3, 3.40, 'Cum Laude');

-- --------------------------------------------------------

--
-- Table structure for table `instructor`
--

CREATE TABLE `instructor` (
  `InsID` int(11) NOT NULL,
  `FName` varchar(50) NOT NULL,
  `LName` varchar(50) NOT NULL,
  `MI` varchar(5) DEFAULT NULL,
  `ExtName` varchar(10) DEFAULT NULL,
  `Sex` varchar(10) DEFAULT NULL,
  `CivilStatus` varchar(20) DEFAULT NULL,
  `Address` varchar(150) DEFAULT NULL,
  `PhoneNo` varchar(15) DEFAULT NULL,
  `HighestDeg` varchar(100) DEFAULT NULL,
  `ProfLicense` varchar(50) DEFAULT NULL,
  `DeptID` int(11) DEFAULT NULL,
  `PrimaryTeachingDisp` varchar(20) DEFAULT NULL,
  `FullTimePartTime` varchar(20) DEFAULT NULL,
  `BachelorDegree` varchar(100) DEFAULT NULL,
  `BachelorSchoolID` varchar(50) DEFAULT NULL,
  `MastersDegree` varchar(100) DEFAULT NULL,
  `MastersSchoolID` varchar(50) DEFAULT NULL,
  `DoctorateDegree` varchar(100) DEFAULT NULL,
  `DoctorateSchoolID` varchar(50) DEFAULT NULL,
  `Tenure` varchar(20) DEFAULT NULL,
  `FacultyRank` varchar(20) DEFAULT NULL,
  `TeachingLoad` varchar(20) DEFAULT NULL,
  `SubjectsTaught` text DEFAULT NULL,
  `AnnualSalary` decimal(12,2) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `MName` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructor`
--

INSERT INTO `instructor` (`InsID`, `FName`, `LName`, `MI`, `ExtName`, `Sex`, `CivilStatus`, `Address`, `PhoneNo`, `HighestDeg`, `ProfLicense`, `DeptID`, `PrimaryTeachingDisp`, `FullTimePartTime`, `BachelorDegree`, `BachelorSchoolID`, `MastersDegree`, `MastersSchoolID`, `DoctorateDegree`, `DoctorateSchoolID`, `Tenure`, `FacultyRank`, `TeachingLoad`, `SubjectsTaught`, `AnnualSalary`, `UserID`, `MName`) VALUES
(1, 'Francisco', 'Rivas', '', NULL, '1', NULL, NULL, NULL, '903', '24', 1, '460100', '1', 'Bachelor of Science in Information Technology', '178912', 'Master in Information Technology', '178912', '', '', '1', '20', '30', 'System Integration, System Architecture', 1.00, NULL, NULL),
(2, 'Jake', 'Cornista', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Calabia');

-- --------------------------------------------------------

--
-- Table structure for table `instructors`
--

CREATE TABLE `instructors` (
  `instructor_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `hired_date` date DEFAULT NULL,
  `max_hours_week` int(11) NOT NULL COMMENT 'Maximum teaching hours per week',
  `availability` varchar(100) DEFAULT NULL COMMENT 'Available schedule slots',
  `name` varchar(100) NOT NULL COMMENT 'Instructor’s name',
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructors`
--

INSERT INTO `instructors` (`instructor_id`, `user_id`, `department`, `hired_date`, `max_hours_week`, `availability`, `name`, `username`, `password`) VALUES
(33, NULL, 'IT', NULL, 10, 'TTH 7:00-5:00', 'Prof. Adela Onlao', 'adela', '$2y$10$6qaXI4dNqoaZpNhbeek.i.XbHBZX3.RYZOKTJeuQ3/WDMqLceaeMa'),
(36, NULL, 'CS', NULL, 10, 'TTH 7:00-5:00', 'Prof.Dennis Gresola', 'Dennis', NULL),
(37, 32, 'IT', NULL, 15, 'MWF 7:00-6:00', 'Prof. Francisco Rivas', 'Francisco', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `job_positions`
--

CREATE TABLE `job_positions` (
  `position_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `position_name` varchar(100) DEFAULT NULL,
  `working_hours_per_day` decimal(5,2) DEFAULT 8.00,
  `rate_per_day` decimal(10,2) DEFAULT 0.00,
  `rate_per_hour` decimal(10,2) DEFAULT 0.00,
  `payroll_frequency` enum('weekly','bi-weekly','monthly') DEFAULT 'bi-weekly',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_positions`
--

INSERT INTO `job_positions` (`position_id`, `department_id`, `position_name`, `working_hours_per_day`, `rate_per_day`, `rate_per_hour`, `payroll_frequency`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Instructor', 8.00, 120.00, 15.00, 'bi-weekly', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `keys_m`
--

CREATE TABLE `keys_m` (
  `KeyID` int(11) NOT NULL,
  `key_name` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `Room_ID` varchar(50) DEFAULT NULL,
  `Key_Code` varchar(100) NOT NULL,
  `QRCode` varchar(255) DEFAULT NULL,
  `Location` varchar(150) DEFAULT NULL,
  `Date_Added` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `keys_m`
--

INSERT INTO `keys_m` (`KeyID`, `key_name`, `description`, `status`, `created_at`, `Room_ID`, `Key_Code`, `QRCode`, `Location`, `Date_Added`) VALUES
(1, NULL, NULL, 'Available', '2025-12-17 09:14:55', 'Lab1', 'K-Lab1', 'K-Lab1', 'Laboratory 1', '2025-12-17 02:11:46');

-- --------------------------------------------------------

--
-- Table structure for table `key_logs`
--

CREATE TABLE `key_logs` (
  `LogID` int(11) NOT NULL,
  `KeyID` int(11) NOT NULL,
  `Key_UserID` int(11) NOT NULL,
  `Location` varchar(150) NOT NULL,
  `Date` date NOT NULL,
  `TimeBorrowed` time NOT NULL,
  `TimeReturned` time DEFAULT NULL,
  `DueDate` datetime NOT NULL,
  `Status` enum('Borrowed','Returned','Overdue','Lost') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `overdue_notified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `key_logs`
--

INSERT INTO `key_logs` (`LogID`, `KeyID`, `Key_UserID`, `Location`, `Date`, `TimeBorrowed`, `TimeReturned`, `DueDate`, `Status`, `created_at`, `updated_at`, `overdue_notified`) VALUES
(1, 0, 0, 'Laboratory 1', '2025-12-17', '17:15:44', NULL, '2025-12-17 22:09:00', 'Borrowed', '2025-12-17 09:15:44', '2025-12-17 09:15:44', 0);

-- --------------------------------------------------------

--
-- Table structure for table `key_management`
--

CREATE TABLE `key_management` (
  `key_id` int(11) NOT NULL,
  `key_name` varchar(100) DEFAULT NULL,
  `key_type_id` int(11) DEFAULT NULL,
  `status` enum('available','issued','lost') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `key_management`
--

INSERT INTO `key_management` (`key_id`, `key_name`, `key_type_id`, `status`, `created_at`) VALUES
(1, 'Main Office Key', 1, 'available', '2025-12-15 08:50:54'),
(2, 'Storage Room Key', 2, 'issued', '2025-12-15 08:50:54');

-- --------------------------------------------------------

--
-- Table structure for table `key_management_security`
--

CREATE TABLE `key_management_security` (
  `id_security` int(11) NOT NULL,
  `key_name_security` varchar(100) NOT NULL,
  `key_code_security` varchar(50) NOT NULL,
  `key_type_id_security` int(11) NOT NULL,
  `location_security` varchar(100) NOT NULL,
  `current_holder_security` int(11) DEFAULT NULL,
  `borrowed_at_security` timestamp NULL DEFAULT NULL,
  `expected_return_security` timestamp NULL DEFAULT NULL,
  `actual_return_security` timestamp NULL DEFAULT NULL,
  `status_security` enum('available','borrowed','maintenance','lost','retired') DEFAULT 'available',
  `condition_notes_security` text DEFAULT NULL,
  `last_maintenance_security` date DEFAULT NULL,
  `created_by_security` int(11) NOT NULL,
  `created_at_security` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at_security` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `key_management_security`
--

INSERT INTO `key_management_security` (`id_security`, `key_name_security`, `key_code_security`, `key_type_id_security`, `location_security`, `current_holder_security`, `borrowed_at_security`, `expected_return_security`, `actual_return_security`, `status_security`, `condition_notes_security`, `last_maintenance_security`, `created_by_security`, `created_at_security`, `updated_at_security`) VALUES
(1, 'Lab 1 Key', 'KEY-LAB 1', 1, 'First Floor Laboratory', NULL, NULL, NULL, NULL, 'available', NULL, NULL, 1, '2025-10-28 11:30:05', '2025-12-02 15:28:59'),
(2, 'Lab 2 Key', 'KEY-LAB-2', 1, 'First Floor Laboratory', NULL, NULL, NULL, NULL, 'available', NULL, NULL, 1, '2025-10-28 11:30:05', '2025-12-02 15:30:00'),
(3, 'Lab 3 Key', 'KEY-LAB-3', 1, 'First Floor Laboratory', NULL, NULL, NULL, '2025-11-24 00:33:54', 'available', NULL, NULL, 1, '2025-10-28 11:30:05', '2025-12-02 15:30:35'),
(4, 'Faculty Room', 'KEY-FAC', 1, 'Faculty Building Room ', NULL, NULL, NULL, '2025-12-11 06:04:03', 'available', '\nStatus Update: OK pamn', NULL, 1, '2025-10-28 11:30:05', '2025-12-11 06:04:28'),
(7, 'Key Room 208', 'Key 208', 1, 'Second Floor Room 208', NULL, NULL, NULL, NULL, 'available', '', NULL, 12, '2025-12-02 21:20:34', '2025-12-02 21:20:34'),
(8, 'Key Room 206', 'Key 206', 1, 'Second Floor Room 206', NULL, '2025-12-11 04:28:02', '2025-12-12 04:28:02', '2025-12-11 04:28:22', 'available', '', NULL, 12, '2025-12-02 21:23:45', '2025-12-11 04:28:22'),
(9, 'Key Room 305', 'Key 305', 1, 'Third Floor Room 305', NULL, NULL, NULL, NULL, 'available', '', NULL, 12, '2025-12-02 21:30:03', '2025-12-02 21:30:03'),
(10, 'Key Room 302', 'Key 302', 1, 'Third Floor', NULL, NULL, NULL, NULL, 'available', 'OK pa', NULL, 13, '2025-12-09 04:42:40', '2025-12-09 04:42:40');

-- --------------------------------------------------------

--
-- Table structure for table `key_transaction_logs`
--

CREATE TABLE `key_transaction_logs` (
  `transaction_id` int(11) NOT NULL,
  `key_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` enum('borrowed','returned') DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `key_transaction_logs`
--

INSERT INTO `key_transaction_logs` (`transaction_id`, `key_id`, `user_id`, `action`, `transaction_date`) VALUES
(1, 2, 1, 'borrowed', '2025-12-15 06:50:54'),
(2, 1, 2, 'borrowed', '2025-12-15 07:50:54');

-- --------------------------------------------------------

--
-- Table structure for table `key_transaction_logs_security`
--

CREATE TABLE `key_transaction_logs_security` (
  `id_security` int(11) NOT NULL,
  `key_id_security` int(11) NOT NULL,
  `user_id_security` int(11) NOT NULL,
  `transaction_type_security` enum('borrow','return','maintenance','transfer') NOT NULL,
  `transaction_time_security` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes_security` text DEFAULT NULL,
  `admin_id_security` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `key_transaction_logs_security`
--

INSERT INTO `key_transaction_logs_security` (`id_security`, `key_id_security`, `user_id_security`, `transaction_type_security`, `transaction_time_security`, `notes_security`, `admin_id_security`) VALUES
(8, 7, 12, '', '2025-12-02 21:20:34', 'Key added to inventory: Key Room 208 (Key 208)', 12),
(9, 8, 12, '', '2025-12-02 21:23:45', 'Key added to inventory: Key Room 206 (Key 206)', 12),
(10, 9, 12, '', '2025-12-02 21:30:03', 'Key added to inventory: Key Room 305 (Key 305)', 12),
(12, 8, 10, 'borrow', '2025-12-11 04:28:02', 'Key borrowed by security guard', 10),
(13, 8, 10, 'return', '2025-12-11 04:28:22', 'Key returned by security guard', 10),
(15, 4, 10, '', '2025-12-11 06:04:28', 'Status changed to available: OK pamn', 10);

-- --------------------------------------------------------

--
-- Table structure for table `key_types`
--

CREATE TABLE `key_types` (
  `key_type_id` int(11) NOT NULL,
  `key_type_name` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `key_types`
--

INSERT INTO `key_types` (`key_type_id`, `key_type_name`, `description`) VALUES
(1, 'Room Key', 'Keys for classroom doors'),
(2, 'Cabinet Key', 'Keys for storage cabinets'),
(3, 'Office Key', 'Keys for office doors');

-- --------------------------------------------------------

--
-- Table structure for table `key_types_security`
--

CREATE TABLE `key_types_security` (
  `id_security` int(11) NOT NULL,
  `type_name_security` varchar(50) NOT NULL,
  `description_security` text DEFAULT NULL,
  `is_active_security` tinyint(1) DEFAULT 1,
  `created_at_security` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `key_types_security`
--

INSERT INTO `key_types_security` (`id_security`, `type_name_security`, `description_security`, `is_active_security`, `created_at_security`) VALUES
(1, 'Room Key', 'Keys for individual rooms and offices', 1, '2025-10-28 11:30:05'),
(2, 'Master Key', 'Master keys for building access', 1, '2025-10-28 11:30:05'),
(3, 'Cabinet Key', 'Keys for storage cabinets and lockers', 1, '2025-10-28 11:30:05'),
(4, 'Vehicle Key', 'Keys for institutional vehicles', 1, '2025-10-28 11:30:05'),
(5, 'Special Access', 'Keys for restricted areas', 1, '2025-10-28 11:30:05');

-- --------------------------------------------------------

--
-- Table structure for table `key_users`
--

CREATE TABLE `key_users` (
  `Key_UsersID` int(11) NOT NULL,
  `Lname` varchar(100) NOT NULL,
  `Fname` varchar(100) NOT NULL,
  `Department` varchar(100) NOT NULL,
  `Role` enum('Instructor','Staff') NOT NULL,
  `Email` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `key_users`
--

INSERT INTO `key_users` (`Key_UsersID`, `Lname`, `Fname`, `Department`, `Role`, `Email`, `created_at`, `updated_at`) VALUES
(1, 'Baquiran', 'Rommel', 'IT', 'Instructor', 'baquiran@gmail.com', '2025-12-17 09:15:30', '2025-12-17 09:15:30');

-- --------------------------------------------------------

--
-- Table structure for table `laboratories`
--

CREATE TABLE `laboratories` (
  `lab_id` int(11) NOT NULL,
  `lab_name` varchar(100) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laboratories`
--

INSERT INTO `laboratories` (`lab_id`, `lab_name`, `location`, `created_at`) VALUES
(1, 'Computer Lab 1', 'Building A, Room 101', '2025-12-15 08:50:54'),
(2, 'Science Lab', 'Building B, Room 201', '2025-12-15 08:50:54');

-- --------------------------------------------------------

--
-- Table structure for table `laboratories_security`
--

CREATE TABLE `laboratories_security` (
  `id_security` int(11) NOT NULL,
  `lab_code_security` varchar(20) NOT NULL,
  `lab_name_security` varchar(100) NOT NULL,
  `building_security` varchar(50) NOT NULL,
  `room_number_security` varchar(20) NOT NULL,
  `capacity_security` int(11) DEFAULT NULL,
  `is_active_security` tinyint(1) DEFAULT 1,
  `created_at_security` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laboratories_security`
--

INSERT INTO `laboratories_security` (`id_security`, `lab_code_security`, `lab_name_security`, `building_security`, `room_number_security`, `capacity_security`, `is_active_security`, `created_at_security`) VALUES
(1, 'LAB-1', 'Computer Laboratory 1', 'First Floor', 'Room Lab 1', 40, 1, '2025-10-28 11:30:05'),
(2, 'LAB-2', 'Computer Laboratory 2', 'First Floor', 'Room Lab 2', 40, 1, '2025-10-28 11:30:05'),
(3, 'LAB-3', 'Computer Laboratory 3\r\n', 'First Floor', 'Room Lab 3', 40, 1, '2025-10-28 11:30:05');

-- --------------------------------------------------------

--
-- Table structure for table `lab_access_logs`
--

CREATE TABLE `lab_access_logs` (
  `access_id` int(11) NOT NULL,
  `lab_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `access_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_access_logs`
--

INSERT INTO `lab_access_logs` (`access_id`, `lab_id`, `user_id`, `access_time`) VALUES
(1, 1, 1, '2025-12-15 06:50:54'),
(2, 1, 2, '2025-12-15 07:50:54');

-- --------------------------------------------------------

--
-- Table structure for table `lab_access_logs_security`
--

CREATE TABLE `lab_access_logs_security` (
  `id_security` int(11) NOT NULL,
  `user_id_security` int(11) NOT NULL,
  `lab_id_security` int(11) NOT NULL,
  `access_type_security` enum('entry','exit') NOT NULL,
  `access_time_security` timestamp NOT NULL DEFAULT current_timestamp(),
  `purpose_security` varchar(200) DEFAULT NULL,
  `notes_security` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_equipment`
--

CREATE TABLE `lab_equipment` (
  `equipment_id` int(11) NOT NULL,
  `lab_id` int(11) DEFAULT NULL,
  `equipment_name` varchar(100) DEFAULT NULL,
  `status` enum('working','damaged','maintenance') DEFAULT 'working'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_equipment`
--

INSERT INTO `lab_equipment` (`equipment_id`, `lab_id`, `equipment_name`, `status`) VALUES
(1, 1, 'Computer Unit 1', 'working'),
(2, 1, 'Computer Unit 2', 'maintenance'),
(3, 2, 'Microscope', 'working');

-- --------------------------------------------------------

--
-- Table structure for table `lab_equipment_security`
--

CREATE TABLE `lab_equipment_security` (
  `id_security` int(11) NOT NULL,
  `lab_id_security` int(11) NOT NULL,
  `equipment_name_security` varchar(100) NOT NULL,
  `model_security` varchar(100) DEFAULT NULL,
  `serial_number_security` varchar(100) DEFAULT NULL,
  `purchase_date_security` date DEFAULT NULL,
  `warranty_expiry_security` date DEFAULT NULL,
  `status_security` enum('operational','maintenance','damaged','retired') DEFAULT 'operational',
  `last_maintenance_security` date DEFAULT NULL,
  `next_maintenance_security` date DEFAULT NULL,
  `notes_security` text DEFAULT NULL,
  `created_at_security` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at_security` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_equipment_security`
--

INSERT INTO `lab_equipment_security` (`id_security`, `lab_id_security`, `equipment_name_security`, `model_security`, `serial_number_security`, `purchase_date_security`, `warranty_expiry_security`, `status_security`, `last_maintenance_security`, `next_maintenance_security`, `notes_security`, `created_at_security`, `updated_at_security`) VALUES
(1, 1, 'Desktop Computer', 'Dell Optiplex 7070', 'SN-DELL-001', NULL, NULL, 'operational', NULL, NULL, NULL, '2025-10-28 11:30:05', '2025-10-28 11:30:05'),
(2, 1, 'Monitor', 'Dell 24-inch', 'SN-MON-001', NULL, NULL, 'operational', NULL, NULL, NULL, '2025-10-28 11:30:05', '2025-10-28 11:30:05'),
(3, 1, 'Network Switch', 'Cisco 24-port', 'SN-CISCO-001', NULL, NULL, 'operational', NULL, NULL, NULL, '2025-10-28 11:30:05', '2025-10-28 11:30:05'),
(4, 2, 'Desktop Computer', 'HP ProDesk', 'SN-HP-001', NULL, NULL, 'maintenance', NULL, NULL, NULL, '2025-10-28 11:30:05', '2025-10-28 11:30:05');

-- --------------------------------------------------------

--
-- Table structure for table `lab_incidents`
--

CREATE TABLE `lab_incidents` (
  `incident_id` int(11) NOT NULL,
  `lab_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `reported_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_incidents`
--

INSERT INTO `lab_incidents` (`incident_id`, `lab_id`, `description`, `reported_at`) VALUES
(1, 1, 'Computer #5 not booting', '2025-12-14 08:50:54'),
(2, 2, 'Microscope lens broken', '2025-12-13 08:50:54');

-- --------------------------------------------------------

--
-- Table structure for table `lab_incidents_security`
--

CREATE TABLE `lab_incidents_security` (
  `id_security` int(11) NOT NULL,
  `reported_by_security` int(11) NOT NULL,
  `lab_id_security` int(11) NOT NULL,
  `equipment_id_security` int(11) DEFAULT NULL,
  `incident_type_security` enum('damage','theft','unauthorized_access','software_issue','maintenance','other') NOT NULL,
  `title_security` varchar(200) NOT NULL,
  `description_security` text NOT NULL,
  `severity_security` enum('low','medium','high','critical') NOT NULL,
  `status_security` enum('reported','under_review','in_progress','resolved','closed') DEFAULT 'reported',
  `reported_date_security` date NOT NULL,
  `estimated_cost_security` decimal(10,2) DEFAULT NULL,
  `resolution_notes_security` text DEFAULT NULL,
  `resolved_by_security` int(11) DEFAULT NULL,
  `resolved_at_security` timestamp NULL DEFAULT NULL,
  `created_at_security` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at_security` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_incidents_security`
--

INSERT INTO `lab_incidents_security` (`id_security`, `reported_by_security`, `lab_id_security`, `equipment_id_security`, `incident_type_security`, `title_security`, `description_security`, `severity_security`, `status_security`, `reported_date_security`, `estimated_cost_security`, `resolution_notes_security`, `resolved_by_security`, `resolved_at_security`, `created_at_security`, `updated_at_security`) VALUES
(5, 13, 1, 1, 'damage', 'Naguba an monitor', 'Naguba', 'medium', 'reported', '2025-12-07', 1500.00, NULL, NULL, NULL, '2025-12-07 06:45:45', '2025-12-07 06:45:45');

-- --------------------------------------------------------

--
-- Table structure for table `learningmaterials`
--

CREATE TABLE `learningmaterials` (
  `MaterialID` int(11) NOT NULL,
  `SubSchedID` int(11) DEFAULT NULL,
  `Title` varchar(200) NOT NULL,
  `Description` text DEFAULT NULL,
  `FilePath` varchar(500) DEFAULT NULL,
  `FileType` varchar(50) DEFAULT NULL,
  `FileSize` int(11) DEFAULT NULL,
  `UploadedBy` int(11) DEFAULT NULL,
  `UploadedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `FolderID` int(11) DEFAULT NULL,
  `OriginalFileName` varchar(500) DEFAULT NULL,
  `Downloads` int(11) DEFAULT 0,
  `IsPublished` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `leave_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `leave_type` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `days` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `deducted_from` varchar(50) DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `admin_feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`log_id`, `user_id`, `login_time`, `ip_address`) VALUES
(0, 1, '2025-12-17 08:05:47', NULL),
(1, 1, '2025-12-15 08:22:38', NULL),
(2, 1, '2025-12-15 08:23:57', NULL),
(3, 1, '2025-12-15 08:58:41', NULL),
(4, 1, '2025-12-15 08:58:52', NULL),
(5, 1, '2025-12-15 08:59:29', NULL),
(6, 1, '2025-12-15 09:01:24', NULL),
(7, 24, '2025-12-15 09:02:11', NULL),
(8, 1, '2025-12-15 09:04:12', NULL),
(9, 1, '2025-12-15 09:04:20', NULL),
(10, 1, '2025-12-15 09:04:41', NULL),
(11, 1, '2025-12-15 09:06:09', NULL),
(12, 1, '2025-12-15 09:06:49', NULL),
(13, 24, '2025-12-15 09:08:14', NULL),
(14, 24, '2025-12-15 09:14:25', NULL),
(15, 1, '2025-12-17 08:30:59', NULL),
(16, 1, '2025-12-17 08:31:13', NULL),
(17, 2024111, '2025-12-17 08:33:22', NULL),
(18, 1, '2025-12-17 08:33:52', NULL),
(19, 1, '2025-12-17 08:35:17', NULL),
(20, 1, '2025-12-17 08:36:00', NULL),
(21, 1, '2025-12-17 08:37:02', NULL),
(22, 1, '2025-12-17 08:40:02', NULL),
(23, 1, '2025-12-17 08:42:19', NULL),
(24, 1, '2025-12-17 08:43:34', NULL),
(25, 1, '2025-12-17 08:47:42', NULL),
(26, 1, '2025-12-17 08:55:45', NULL),
(27, 1, '2025-12-17 08:57:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `log_id` int(11) NOT NULL,
  `KeyID` int(11) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `Location` varchar(255) DEFAULT NULL,
  `Date` date DEFAULT NULL,
  `TimeBorrowed` time DEFAULT NULL,
  `TimeReturned` time DEFAULT NULL,
  `DueDate` date DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL,
  `log_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lost_found_categories`
--

CREATE TABLE `lost_found_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lost_found_categories`
--

INSERT INTO `lost_found_categories` (`category_id`, `category_name`) VALUES
(1, 'Electronics'),
(2, 'Books'),
(3, 'Personal Items'),
(4, 'Clothing');

-- --------------------------------------------------------

--
-- Table structure for table `lost_found_categories_security`
--

CREATE TABLE `lost_found_categories_security` (
  `id_security` int(11) NOT NULL,
  `category_name_security` varchar(50) NOT NULL,
  `description_security` text DEFAULT NULL,
  `is_active_security` tinyint(1) DEFAULT 1,
  `created_at_security` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lost_found_categories_security`
--

INSERT INTO `lost_found_categories_security` (`id_security`, `category_name_security`, `description_security`, `is_active_security`, `created_at_security`) VALUES
(1, 'Electronics', 'Mobile phones, laptops, tablets, calculators, etc.', 1, '2025-10-28 11:30:05'),
(2, 'Documents', 'IDs, certificates, notebooks, books, etc.', 1, '2025-10-28 11:30:05'),
(3, 'Accessories', 'Bags, wallets, keys, jewelry, etc.', 1, '2025-10-28 11:30:05'),
(4, 'Clothing', 'Jackets, uniforms, hats, shoes, etc.', 1, '2025-10-28 11:30:05'),
(5, 'School Supplies', 'Pens, notebooks, binders, etc.', 1, '2025-10-28 11:30:05'),
(6, 'Personal Items', 'Water bottles, lunch boxes, etc.', 1, '2025-10-28 11:30:05');

-- --------------------------------------------------------

--
-- Table structure for table `lost_found_items`
--

CREATE TABLE `lost_found_items` (
  `item_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `item_name` varchar(100) DEFAULT NULL,
  `status` enum('lost','found','claimed') DEFAULT 'lost',
  `reported_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lost_found_items`
--

INSERT INTO `lost_found_items` (`item_id`, `category_id`, `item_name`, `status`, `reported_at`) VALUES
(1, 2, 'Calculus Textbook', 'found', '2025-12-14 08:50:54'),
(2, 3, 'Student ID Card', 'claimed', '2025-12-13 08:50:54'),
(3, 1, 'Wireless Mouse', 'lost', '2025-12-15 08:50:54');

-- --------------------------------------------------------

--
-- Table structure for table `lost_found_items_security`
--

CREATE TABLE `lost_found_items_security` (
  `id_security` int(11) NOT NULL,
  `reported_by_security` int(11) NOT NULL,
  `item_type_security` enum('lost','found') NOT NULL,
  `category_id_security` int(11) NOT NULL,
  `item_name_security` varchar(100) NOT NULL,
  `description_security` text NOT NULL,
  `location_found_security` varchar(100) NOT NULL,
  `date_reported_security` date NOT NULL,
  `date_occurred_security` date NOT NULL,
  `color_security` varchar(50) DEFAULT NULL,
  `brand_security` varchar(50) DEFAULT NULL,
  `serial_number_security` varchar(100) DEFAULT NULL,
  `contact_info_security` varchar(255) DEFAULT NULL,
  `status_security` enum('open','claimed','resolved','archived') DEFAULT 'open',
  `is_verified_security` tinyint(1) DEFAULT 0,
  `photo_path_security` varchar(255) DEFAULT NULL,
  `claimed_by_security` int(11) DEFAULT NULL,
  `claimed_at_security` timestamp NULL DEFAULT NULL,
  `resolved_by_security` int(11) DEFAULT NULL,
  `resolved_at_security` timestamp NULL DEFAULT NULL,
  `resolution_notes_security` text DEFAULT NULL,
  `verification_notes_security` text DEFAULT NULL,
  `created_at_security` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at_security` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lost_found_items_security`
--

INSERT INTO `lost_found_items_security` (`id_security`, `reported_by_security`, `item_type_security`, `category_id_security`, `item_name_security`, `description_security`, `location_found_security`, `date_reported_security`, `date_occurred_security`, `color_security`, `brand_security`, `serial_number_security`, `contact_info_security`, `status_security`, `is_verified_security`, `photo_path_security`, `claimed_by_security`, `claimed_at_security`, `resolved_by_security`, `resolved_at_security`, `resolution_notes_security`, `verification_notes_security`, `created_at_security`, `updated_at_security`) VALUES
(10, 13, 'lost', 1, 'phone', 'Nawawara an kan jisil phone', 'lab 1', '2025-12-04', '2025-12-07', 'black', 'tecno', 'asjdhajsdhad', '09823827378', 'open', 0, 'lost_found_1765082957_6935074daec44.png', NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-06 20:49:17', '2025-12-11 07:12:18'),
(12, 14, 'lost', 1, 'Hp Laptop', 'Aray KOh', 'Lab 1', '2025-12-12', '2025-12-12', 'Gray', 'HP', '', NULL, 'resolved', 0, '693aeb22efbf2_1765468962.png', NULL, NULL, 11, '2025-12-11 09:07:22', 'Found by another user. Found item ID: 13. Reported by user ID: 11', NULL, '2025-12-11 08:02:42', '2025-12-11 09:07:22'),
(13, 11, 'found', 1, 'Hp Laptop', 'Found item matches a lost item report.\r\n\r\nLost Item Details:\r\n• Lost on: Dec 12, 2025\r\n• Lost at: Lab 1\r\n• Reported by: Rommel Baquiran', 'Lab 1', '2025-12-12', '2025-12-12', 'Gray', 'HP', '', NULL, 'resolved', 1, '693afa4a1da3f_1765472842.png', 14, '2025-12-11 09:09:10', 10, '2025-12-11 09:12:02', 'Claim details: This is mine i have a proof Proof: hheheh', 'its yours', '2025-12-11 09:07:22', '2025-12-11 09:12:02');

-- --------------------------------------------------------

--
-- Table structure for table `lost_keys`
--

CREATE TABLE `lost_keys` (
  `lost_key_id` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `Fname` varchar(100) DEFAULT NULL,
  `Lname` varchar(100) DEFAULT NULL,
  `Email` varchar(150) DEFAULT NULL,
  `Key_Code` varchar(100) DEFAULT NULL,
  `Location` varchar(255) DEFAULT NULL,
  `Date_Reported` date DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL,
  `key_id` int(11) DEFAULT NULL,
  `reported_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE `maintenance` (
  `maintenance_id` int(11) NOT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `maintenance_type` varchar(100) DEFAULT NULL,
  `performed_by` varchar(150) DEFAULT NULL,
  `maintenance_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `materialaccesslog`
--

CREATE TABLE `materialaccesslog` (
  `LogID` int(11) NOT NULL,
  `MaterialID` int(11) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `Action` varchar(50) DEFAULT 'view',
  `AccessedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `materialfolder`
--

CREATE TABLE `materialfolder` (
  `FolderID` int(11) NOT NULL,
  `SubSchedID` int(11) DEFAULT NULL,
  `ParentFolderID` int(11) DEFAULT NULL,
  `FolderName` varchar(200) NOT NULL,
  `Description` text DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `materialfolder`
--

INSERT INTO `materialfolder` (`FolderID`, `SubSchedID`, `ParentFolderID`, `FolderName`, `Description`, `CreatedBy`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 2, NULL, 'hshshs', 'sjdjjd', 33, '2025-12-17 09:01:09', '2025-12-17 09:01:09'),
(2, 2, NULL, 'hshshs', 'intro', 33, '2025-12-17 09:05:59', '2025-12-17 09:05:59'),
(3, 3, NULL, 'dsjsdd', 'sjfhdsjf', 33, '2025-12-17 09:06:11', '2025-12-17 09:06:11');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `MemberID` int(11) NOT NULL,
  `SchoolID` varchar(50) NOT NULL,
  `fname` varchar(100) NOT NULL,
  `lname` varchar(100) NOT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `member_type` enum('Student','Faculty') DEFAULT 'Student',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`MemberID`, `SchoolID`, `fname`, `lname`, `course`, `year_level`, `contact`, `member_type`, `status`, `created_at`) VALUES
(1, 'ADFC-110', 'Lovely', 'Sia', '', '', '09919815177', 'Faculty', 'active', '2025-12-14 03:13:45');

-- --------------------------------------------------------

--
-- Table structure for table `misconducts`
--

CREATE TABLE `misconducts` (
  `id` int(11) NOT NULL,
  `student_id` varchar(32) NOT NULL,
  `name` varchar(128) NOT NULL,
  `course` varchar(128) NOT NULL,
  `year_level` varchar(32) NOT NULL,
  `reason` text NOT NULL,
  `strikes` int(11) NOT NULL DEFAULT 1,
  `date_recorded` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `module_id` int(11) NOT NULL,
  `module_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `notice_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notices`
--

INSERT INTO `notices` (`notice_id`, `type`, `message`, `title`, `content`, `created_at`) VALUES
(0, 'General', 'Finals Exam will be held on 2025-12-17. Please prepare and review your lessons.', 'Finals Exam', NULL, '2025-12-17 08:52:35');

-- --------------------------------------------------------

--
-- Table structure for table `notice_reads`
--

CREATE TABLE `notice_reads` (
  `id` int(11) NOT NULL,
  `notice_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(150) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fname` varchar(100) DEFAULT NULL,
  `lname` varchar(100) DEFAULT NULL,
  `gmail` varchar(150) DEFAULT NULL,
  `dateadded` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `title`, `message`, `is_read`, `created_at`, `fname`, `lname`, `gmail`, `dateadded`) VALUES
(1, 1, '⏰ Key Overdue Alert', 'The key K-LAB1 from Laboratory 1 is overdue.', 0, '2025-12-17 06:59:49', NULL, NULL, NULL, '2025-12-17 14:59:49');

-- --------------------------------------------------------

--
-- Table structure for table `notifications_for_id`
--

CREATE TABLE `notifications_for_id` (
  `id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `seen` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `overtime_requests`
--

CREATE TABLE `overtime_requests` (
  `overtime_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `hours` decimal(5,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `attendance_log_id` int(11) DEFAULT NULL,
  `scheduled_end_time` time DEFAULT NULL,
  `actual_out_time` datetime DEFAULT NULL,
  `raw_ot_minutes` int(11) DEFAULT 0,
  `approved_ot_minutes` int(11) DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(255) DEFAULT NULL,
  `code` varchar(6) DEFAULT NULL,
  `expired_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `id` int(11) NOT NULL,
  `StudID` int(11) DEFAULT NULL,
  `course` varchar(50) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `payment_type` varchar(50) DEFAULT NULL,
  `amount_due` decimal(10,2) DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `status` enum('Unpaid','Partially Paid','Paid') DEFAULT NULL,
  `receipt_no` varchar(20) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `payment_date` date DEFAULT NULL,
  `billing_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `payment_date`, `billing_id`, `amount_paid`, `cashier_id`, `receipt_number`, `description`) VALUES
(1, '2025-12-15', 1, 2000.00, 1, 'OR-64999', 'TUITION - Prelim Tuition Fee Payment'),
(2, '2025-12-15', 1, 2000.00, 1, 'OR-64999', 'TUITION - Prelim Tuition Fee Payment'),
(3, '2025-12-15', 1, 2000.00, 1, 'OR-64999', 'TUITION - Prelim Tuition Fee Payment'),
(4, '2025-12-15', 1, 2000.00, 1, 'OR-64999', 'TUITION - Prelim Tuition Fee Payment'),
(5, '2025-12-15', 2, 2000.00, 1, 'OR-69031', 'TUITION - Prelim Tuition Fee Payment'),
(6, '2025-12-15', 2, 2000.00, 1, 'OR-69031', 'TUITION - Prelim Tuition Fee Payment'),
(7, '2025-12-16', 2, 2000.00, 1, 'OR-91128', 'TUITION - Prelim Tuition Fee Payment'),
(13, '2025-12-17', 3, 1231.00, 1, 'OR-47331', 'qwrq - qwrqwq Payment'),
(14, '2025-12-17', 3, 1231.00, 1, 'OR-25998', 'qwrq - qwrqwq Payment');

-- --------------------------------------------------------

--
-- Table structure for table `payments_doc`
--

CREATE TABLE `payments_doc` (
  `payment_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payment_method` enum('GCash','PayMaya','Cash') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `payment_status` enum('Pending','For Cash Payment','Paid','Rejected') DEFAULT 'Pending',
  `date_submitted` datetime DEFAULT current_timestamp(),
  `date_verified` datetime DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores payment information for document requests';

--
-- Triggers `payments_doc`
--
DELIMITER $$
CREATE TRIGGER `check_payment_amount` BEFORE INSERT ON `payments_doc` FOR EACH ROW BEGIN
    IF NEW.amount <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Payment amount must be greater than 0';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `payment_status`
--

CREATE TABLE `payment_status` (
  `status_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_status`
--

INSERT INTO `payment_status` (`status_id`, `student_id`, `status`, `updated_at`) VALUES
(1, 1, 'Partially Paid', '2025-12-15 08:50:54'),
(2, 2, 'Paid', '2025-12-15 08:50:54');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `payroll_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `basic_salary` decimal(10,2) DEFAULT NULL,
  `net_salary` decimal(10,2) DEFAULT NULL,
  `payroll_date` date DEFAULT NULL,
  `payroll_period_start` date DEFAULT NULL,
  `payroll_period_end` date DEFAULT NULL,
  `gross_pay` decimal(10,2) DEFAULT 0.00,
  `overtime_pay` decimal(12,2) DEFAULT 0.00,
  `holiday_pay` decimal(12,2) DEFAULT 0.00,
  `philhealth_deduction` decimal(10,2) DEFAULT 0.00,
  `sss_deduction` decimal(10,2) DEFAULT 0.00,
  `pagibig_deduction` decimal(10,2) DEFAULT 0.00,
  `other_deductions` decimal(10,2) DEFAULT 0.00,
  `total_deductions` decimal(10,2) GENERATED ALWAYS AS (`philhealth_deduction` + `sss_deduction` + `pagibig_deduction` + `other_deductions`) STORED,
  `net_pay` decimal(10,2) GENERATED ALWAYS AS (`gross_pay` - `total_deductions`) STORED,
  `paid_status` enum('Unpaid','Paid') DEFAULT 'Unpaid',
  `payment_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_audit`
--

CREATE TABLE `payroll_audit` (
  `audit_id` int(11) NOT NULL,
  `payroll_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `action_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_settings`
--

CREATE TABLE `payroll_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_name` varchar(100) DEFAULT NULL,
  `setting_value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `permission_id` int(11) NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions_for_id`
--

CREATE TABLE `permissions_for_id` (
  `PermissionID` int(11) NOT NULL,
  `PermissionName` varchar(50) NOT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permission_keys`
--

CREATE TABLE `permission_keys` (
  `permission_id` int(11) NOT NULL,
  `key_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permission_keys_security`
--

CREATE TABLE `permission_keys_security` (
  `id_security` int(11) NOT NULL,
  `permission_key_security` varchar(50) NOT NULL,
  `permission_name_security` varchar(100) NOT NULL,
  `description_security` text DEFAULT NULL,
  `module_security` varchar(50) NOT NULL,
  `is_active_security` tinyint(1) DEFAULT 1,
  `created_at_security` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permission_keys_security`
--

INSERT INTO `permission_keys_security` (`id_security`, `permission_key_security`, `permission_name_security`, `description_security`, `module_security`, `is_active_security`, `created_at_security`) VALUES
(1, 'report_violations', 'Report Violations', 'Can report new violations and incidents', 'violations', 1, '2025-10-30 03:55:47'),
(2, 'view_violations', 'View Violations', 'Can view violations list and details', 'violations', 1, '2025-10-30 03:55:47'),
(3, 'review_violations', 'Review Violations', 'Can review and verify reported violations', 'violations', 1, '2025-10-30 03:55:47'),
(4, 'edit_violations', 'Edit Violations', 'Can edit existing violation records', 'violations', 1, '2025-10-30 03:55:47'),
(5, 'delete_violations', 'Delete Violations', 'Can delete violation records', 'violations', 1, '2025-10-30 03:55:47'),
(6, 'check_attendance', 'Check Attendance', 'Can check in/out for attendance', 'attendance', 1, '2025-10-30 03:55:47'),
(7, 'view_attendance', 'View Attendance', 'Can view attendance logs and records', 'attendance', 1, '2025-10-30 03:55:47'),
(8, 'manage_attendance', 'Manage Attendance', 'Can manage and edit attendance records', 'attendance', 1, '2025-10-30 03:55:47'),
(9, 'access_labs', 'Access Laboratories', 'Can access laboratory areas', 'lab_security', 1, '2025-10-30 03:55:47'),
(10, 'manage_labs', 'Manage Laboratories', 'Can manage laboratory access and incidents', 'lab_security', 1, '2025-10-30 03:55:47'),
(11, 'report_lab_incidents', 'Report Lab Incidents', 'Can report laboratory incidents and issues', 'lab_security', 1, '2025-10-30 03:55:47'),
(12, 'view_lab_incidents', 'View Lab Incidents', 'Can view laboratory incident reports', 'lab_security', 1, '2025-10-30 03:55:47'),
(13, 'borrow_keys', 'Borrow Keys', 'Can borrow keys from key management', 'key_management', 1, '2025-10-30 03:55:47'),
(14, 'return_keys', 'Return Keys', 'Can return borrowed keys', 'key_management', 1, '2025-10-30 03:55:47'),
(15, 'manage_keys', 'Manage Keys', 'Can manage key inventory and assignments', 'key_management', 1, '2025-10-30 03:55:47'),
(16, 'view_keys', 'View Keys', 'Can view key management system', 'key_management', 1, '2025-10-30 03:55:47'),
(17, 'view_student_info', 'View Student Information', 'Can view student profiles and information', 'student_data', 1, '2025-10-30 03:55:47'),
(18, 'edit_student_info', 'Edit Student Information', 'Can edit student information', 'student_data', 1, '2025-10-30 03:55:47'),
(19, 'manage_users', 'Manage Users', 'Can create, edit, and manage user accounts', 'user_management', 1, '2025-10-30 03:55:47'),
(20, 'view_users', 'View Users', 'Can view user list and profiles', 'user_management', 1, '2025-10-30 03:55:47'),
(21, 'deactivate_users', 'Deactivate Users', 'Can deactivate user accounts', 'user_management', 1, '2025-10-30 03:55:47'),
(22, 'view_reports', 'View Reports', 'Can view system reports and analytics', 'reports', 1, '2025-10-30 03:55:47'),
(23, 'generate_reports', 'Generate Reports', 'Can generate custom reports', 'reports', 1, '2025-10-30 03:55:47'),
(24, 'export_reports', 'Export Reports', 'Can export reports to various formats', 'reports', 1, '2025-10-30 03:55:47'),
(25, 'manage_system', 'Manage System Settings', 'Can manage system configuration and settings', 'system', 1, '2025-10-30 03:55:47'),
(26, 'view_system_logs', 'View System Logs', 'Can view system activity logs', 'system', 1, '2025-10-30 03:55:47'),
(27, 'manage_permissions', 'Manage Permissions', 'Can manage user permissions and roles', 'system', 1, '2025-10-30 03:55:47'),
(28, 'report_lost_found', 'Report Lost & Found', 'Can report lost and found items', 'lost_found', 1, '2025-10-30 03:55:47'),
(29, 'view_lost_found', 'View Lost & Found', 'Can view lost and found items', 'lost_found', 1, '2025-10-30 03:55:47'),
(30, 'manage_lost_found', 'Manage Lost & Found', 'Can manage lost and found items', 'lost_found', 1, '2025-10-30 03:55:47'),
(31, 'claim_lost_found', 'Claim Lost & Found', 'Can claim lost and found items', 'lost_found', 1, '2025-10-30 03:55:47'),
(32, 'view_assignments', 'View Assignments', 'Can view security assignments and schedules', 'security_team', 1, '2025-10-30 03:55:47'),
(33, 'manage_assignments', 'Manage Assignments', 'Can manage security team assignments', 'security_team', 1, '2025-10-30 03:55:47'),
(34, 'view_security_team', 'View Security Team', 'Can view security team members', 'security_team', 1, '2025-10-30 03:55:47'),
(35, 'manage_security_team', 'Manage Security Team', 'Can manage security team members', 'security_team', 1, '2025-10-30 03:55:47');

-- --------------------------------------------------------

--
-- Table structure for table `pickup_schedules`
--

CREATE TABLE `pickup_schedules` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `pickup_date` date NOT NULL,
  `pickup_code` varchar(20) DEFAULT NULL,
  `status` enum('Scheduled','Ready','Picked Up') DEFAULT 'Scheduled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `portal_logs`
--

CREATE TABLE `portal_logs` (
  `id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `priority_levels`
--

CREATE TABLE `priority_levels` (
  `priority_id` int(11) NOT NULL,
  `priority_name` varchar(20) NOT NULL,
  `priority_value` int(11) DEFAULT NULL,
  `color_code` varchar(7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `priority_levels`
--

INSERT INTO `priority_levels` (`priority_id`, `priority_name`, `priority_value`, `color_code`) VALUES
(4, 'High', 3, '#dc3545'),
(5, 'Medium', 2, '#ffc107'),
(6, 'Low', 1, '#28a745');

-- --------------------------------------------------------

--
-- Table structure for table `program`
--

CREATE TABLE `program` (
  `ProgramID` int(11) NOT NULL,
  `ProgName` varchar(100) NOT NULL,
  `Major` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program`
--

INSERT INTO `program` (`ProgramID`, `ProgName`, `Major`) VALUES
(1, 'Bachelor of Science in Information Technology', 'Programming'),
(2, 'Bachelor of Science in Tourism Management', 'Saka Saka');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `program_id` int(11) NOT NULL,
  `program_code` varchar(50) DEFAULT NULL,
  `program_name` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `name` varchar(100) NOT NULL COMMENT 'Full name of the program (e.g., BS Information Technology)',
  `abbr` varchar(10) NOT NULL COMMENT 'Abbreviated name (e.g., BSIT)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`program_id`, `program_code`, `program_name`, `created_at`, `name`, `abbr`) VALUES
(1, NULL, NULL, '2025-12-17 06:49:22', 'BS Information Technology', 'BSIT'),
(2, NULL, NULL, '2025-12-17 06:49:22', 'BS Computer Science', 'BSCS'),
(3, NULL, NULL, '2025-12-17 06:49:22', 'BS Electrical Engineering', 'BSEE'),
(4, NULL, NULL, '2025-12-17 06:49:22', 'BS Nursing', 'BSN'),
(6, NULL, NULL, '2025-12-17 06:49:22', 'BS Criminology', 'BSCRIM');

-- --------------------------------------------------------

--
-- Table structure for table `provisional_attendance`
--

CREATE TABLE `provisional_attendance` (
  `provisional_attendance_id` int(11) NOT NULL,
  `provisional_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `attendance_date` date DEFAULT NULL,
  `status` enum('present','absent','late') DEFAULT 'present'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `provisional_students`
--

CREATE TABLE `provisional_students` (
  `provisional_id` int(11) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qr_codes`
--

CREATE TABLE `qr_codes` (
  `qr_id` int(11) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `employee_id` int(11) DEFAULT NULL,
  `qr_data` text DEFAULT NULL,
  `qr_image_path` varchar(500) DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `queue`
--

CREATE TABLE `queue` (
  `queue_id` int(11) NOT NULL,
  `queue_number` int(11) NOT NULL,
  `window_id` int(11) DEFAULT NULL,
  `window_name` varchar(50) DEFAULT NULL,
  `transaction_type` int(11) DEFAULT NULL,
  `course_program` varchar(50) DEFAULT NULL,
  `client_type` varchar(20) DEFAULT NULL,
  `client_name` varchar(100) DEFAULT NULL,
  `client_id` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Waiting',
  `check_in_time` datetime DEFAULT current_timestamp(),
  `printed` tinyint(4) DEFAULT 0,
  `generated_by` enum('guard','public') DEFAULT 'guard'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `queue`
--

INSERT INTO `queue` (`queue_id`, `queue_number`, `window_id`, `window_name`, `transaction_type`, `course_program`, `client_type`, `client_name`, `client_id`, `status`, `check_in_time`, `printed`, `generated_by`) VALUES
(1, 1, 3, 'Counter 1', 3, 'BSIT', 'Student', 'Denise Sombilon', '21-360203', 'Waiting', '2025-12-09 21:28:07', 0, 'public'),
(2, 2, 3, 'Counter 1', 3, 'BSIT', 'Student', 'Dior', '230094', 'Waiting', '2025-12-09 21:28:38', 0, 'guard'),
(3, 1, 6, 'Registrar', 7, 'BSM', 'Alumni', 'Dimna', 'NA', 'Waiting', '2025-12-09 21:29:33', 0, 'public'),
(4, 1, 5, 'Releasing', 7, 'MASTERAL', 'Visitor', 'Gen', 'dd', 'Waiting', '2025-12-09 21:30:36', 1, 'guard'),
(5, 3, 3, 'Counter 1', 3, 'BSIT', 'Student', 'Denise Sombilon', '21-360203', 'Waiting', '2025-12-09 21:31:28', 1, 'guard'),
(6, 4, 3, 'Counter 1', 3, 'BSIT', 'Student', 'Denise Sombilon', '21-360203', 'Waiting', '2025-12-09 21:36:25', 0, 'guard'),
(7, 1, 1, 'Window 1', 1, 'BSIT', 'Student', 'Denise Sombilon', '21-360203', 'Waiting', '2025-12-09 21:37:39', 1, 'guard'),
(8, 2, 1, 'Window 1', 1, 'BSIT', 'Student', 'Denise Sombilon', '21-360203', 'Waiting', '2025-12-09 21:41:13', 0, 'public'),
(9, 5, 3, 'Counter 1', 3, 'BSIT', 'Student', 'Denise Sombilon', '21-360203', 'Waiting', '2025-12-09 21:55:24', 0, 'public'),
(10, 6, 3, 'Counter 1', 3, 'BSIT', 'Student', 'Denise Sombilon', '21-360203', 'Waiting', '2025-12-09 22:01:11', 0, 'public'),
(11, 1, 4, 'Counter 2', 3, 'BSA', 'Student', 'Denise Sombilon', '21-360203', 'Waiting', '2025-12-09 22:14:09', 0, 'public'),
(12, 7, 3, 'Counter 1', 3, 'BSIT', 'Student', 'Denise Sombilon', '21-360203', 'Waiting', '2025-12-09 22:17:44', 0, 'public'),
(13, 8, 3, 'Counter 1', 3, 'BSIT', 'Student', 'Denise Sombilon', '21-360203', 'Waiting', '2025-12-09 23:48:53', 1, 'guard'),
(14, 2, 6, 'Registrar', 5, 'BSIT', 'Student', 'Denise Sombilon', '21-360203', 'Waiting', '2025-12-09 23:51:11', 0, 'guard'),
(15, 9, 3, 'Counter 1', 3, 'BSIT', 'Student', 'Denise Sombilon', '21-360203', 'Waiting', '2025-12-09 23:51:48', 0, 'public'),
(16, 10, 3, 'Counter 1', 3, 'BSIT', 'Student', 'Denise Sombilon', '21-360203', 'Waiting', '2025-12-10 00:03:17', 0, 'public'),
(17, 2, 5, 'Releasing', 7, 'PHD', 'Alumni', 'Dior', 'NA', 'Waiting', '2025-12-10 00:04:45', 0, 'public'),
(18, 11, 3, 'Counter 1', 3, 'BSIT', 'Student', 'Denise Sombilon', '21-360203', 'Waiting', '2025-12-10 00:19:47', 0, 'public'),
(19, 12, 3, 'Counter 1', 3, 'BSIT', 'Student', 'Denise Sombilon', '21-360203', 'Waiting', '2025-12-10 00:21:35', 0, 'public'),
(20, 13, 3, 'Counter 1', 3, 'BSIT', 'Student', 'Denise Sombilon', '21-360203', 'Waiting', '2025-12-10 00:21:37', 0, 'public'),
(21, 14, 3, 'Counter 1', 3, 'BSIT', 'Student', 'Denise Sombilon', '21-360203', 'Waiting', '2025-12-10 00:22:04', 0, 'public'),
(22, 1, 1, 'Window 1', 1, 'BSIT', 'Student', 'Denise Sombilon', '21-360203', 'Waiting', '2025-12-10 01:02:34', 0, 'guard'),
(23, 1, 3, 'Counter 1', 3, 'BSIT', 'Student', 'Dior', 'NA', 'Waiting', '2025-12-10 01:02:50', 0, 'public'),
(24, 2, 1, 'Window 1', 1, 'BSIT', 'Student', 'Dior', 'NA', 'Waiting', '2025-12-10 01:03:01', 0, 'public'),
(25, 1, 3, 'Counter 1', 2, 'BSIT', 'Student', 'Denise Sombilon', '21-360203', 'Waiting', '2025-12-10 01:08:12', 1, 'guard'),
(26, 2, 3, 'Counter 1', 2, 'BSIT', 'Student', 'Dior', '230094', 'Waiting', '2025-12-10 01:08:39', 0, 'public'),
(27, 3, 3, 'Counter 1', 2, 'BSIT', 'Student', 'Dior', '230094', 'Waiting', '2025-12-10 01:10:54', 0, 'public'),
(28, 4, 3, 'Counter 1', 2, 'BSIT', 'Student', 'Dior', '230094', 'Waiting', '2025-12-10 01:10:55', 0, 'public'),
(29, 1, 6, 'Registrar', 8, 'BSCS', 'Alumni', 'Dior', 'NA', 'Waiting', '2025-12-10 01:11:05', 0, 'public'),
(30, 2, 6, 'Registrar', 10, 'BSIT', 'Alumni', 'Dior', '21-360203', 'Waiting', '2025-12-10 01:11:41', 0, 'public'),
(31, 3, 6, 'Registrar', 10, 'BSIT', 'Visitor', 'Denise Sombilon', '21-360203', 'Waiting', '2025-12-10 01:14:33', 0, 'public'),
(32, 4, 6, 'Registrar', 10, 'BSCE', 'Student', 'Denise Sombilon', '21-360203', 'Waiting', '2025-12-10 01:15:01', 0, 'public'),
(33, 1, 1, 'Window 1', 1, 'BSIT', 'Student', 'Denise Marsha Eliza A. Sombilon', '21-360203', 'Waiting', '2025-12-15 15:47:28', 1, 'guard'),
(34, 1, 3, 'Counter 1', 3, 'BSIT', 'Student', 'Denise Marsha Eliza A. Sombilon', '21-360203', 'Waiting', '2025-12-15 15:58:38', 1, 'guard'),
(35, 1, 5, 'Releasing', 7, 'MASTERAL', 'Visitor', 'Denise Marsha Eliza A. Sombilon', '21-360203', 'Waiting', '2025-12-15 15:59:51', 1, 'guard'),
(36, 1, 6, 'Registrar', 10, 'BSIT', 'Student', 'Denise Marsha Eliza A. Sombilon', '21-360203', 'Waiting', '2025-12-15 16:00:18', 0, 'public');

-- --------------------------------------------------------

--
-- Table structure for table `quiz`
--

CREATE TABLE `quiz` (
  `QuizID` int(11) NOT NULL,
  `SubSchedID` int(11) DEFAULT NULL,
  `Title` varchar(200) NOT NULL,
  `Description` text DEFAULT NULL,
  `TimeLimit` int(11) DEFAULT 60,
  `TotalPoints` int(11) DEFAULT 100,
  `StartDate` datetime DEFAULT NULL,
  `EndDate` datetime DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `CreatedBy` int(11) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quizanswer`
--

CREATE TABLE `quizanswer` (
  `AnswerID` int(11) NOT NULL,
  `AttemptID` int(11) DEFAULT NULL,
  `QuestionID` int(11) DEFAULT NULL,
  `ChoiceID` int(11) DEFAULT NULL,
  `TextAnswer` text DEFAULT NULL,
  `IsCorrect` tinyint(1) DEFAULT 0,
  `PointsEarned` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quizattempt`
--

CREATE TABLE `quizattempt` (
  `AttemptID` int(11) NOT NULL,
  `QuizID` int(11) DEFAULT NULL,
  `StudID` int(11) DEFAULT NULL,
  `Score` decimal(5,2) DEFAULT 0.00,
  `TotalPoints` int(11) DEFAULT 0,
  `StartTime` datetime DEFAULT NULL,
  `EndTime` datetime DEFAULT NULL,
  `Status` varchar(20) DEFAULT 'in_progress'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quizchoice`
--

CREATE TABLE `quizchoice` (
  `ChoiceID` int(11) NOT NULL,
  `QuestionID` int(11) DEFAULT NULL,
  `ChoiceText` text NOT NULL,
  `IsCorrect` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quizquestion`
--

CREATE TABLE `quizquestion` (
  `QuestionID` int(11) NOT NULL,
  `QuizID` int(11) DEFAULT NULL,
  `QuestionText` text NOT NULL,
  `QuestionType` varchar(20) DEFAULT 'multiple_choice',
  `Points` int(11) DEFAULT 1,
  `OrderNum` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `report_type` varchar(100) DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_counters`
--

CREATE TABLE `request_counters` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `count` int(11) DEFAULT 0,
  `max_limit` int(11) DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `request_counters`
--

INSERT INTO `request_counters` (`id`, `date`, `count`, `max_limit`) VALUES
(1, '2025-12-13', 0, 30),
(133, '2025-12-14', 0, 30),
(221, '2025-12-15', 0, 30),
(240, '2025-12-16', 0, 30),
(246, '2025-12-17', 0, 30);

-- --------------------------------------------------------

--
-- Table structure for table `rolepermissions`
--

CREATE TABLE `rolepermissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `permission_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rolepermissions_for_id`
--

CREATE TABLE `rolepermissions_for_id` (
  `RolePermissionID` int(11) NOT NULL,
  `RoleID` int(11) NOT NULL,
  `PermissionID` int(11) NOT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles_for_id`
--

CREATE TABLE `roles_for_id` (
  `RoleID` int(11) NOT NULL,
  `RoleName` varchar(50) NOT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_modules`
--

CREATE TABLE `role_modules` (
  `id` int(11) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `module_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_name` varchar(50) DEFAULT NULL,
  `building` varchar(100) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `room_number` varchar(20) NOT NULL COMMENT 'Room number or code',
  `room_type` enum('Lecture Hall','Computer Lab','Engineering Lab','Seminar Room') NOT NULL COMMENT 'Type of classroom',
  `floor` varchar(20) DEFAULT NULL COMMENT 'Floor location',
  `equipment` varchar(100) DEFAULT NULL COMMENT 'Special equipment (e.g., projector)',
  `status` enum('Available','Occupied','Maintenance') DEFAULT 'Available',
  `current_class_name` varchar(100) DEFAULT 'No Current Class',
  `current_professor` varchar(100) DEFAULT 'N/A',
  `current_time_slot` varchar(50) DEFAULT 'N/A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `room_name`, `building`, `capacity`, `room_number`, `room_type`, `floor`, `equipment`, `status`, `current_class_name`, `current_professor`, `current_time_slot`) VALUES
(100, NULL, NULL, 80, 'C201', 'Lecture Hall', '2nd Floor', 'Projector, Whiteboard', 'Occupied', 'College Algebra', 'Prof. Adela Onlao', '10:30:00 - 12:00:00'),
(101, NULL, NULL, 30, 'C305', 'Computer Lab', '1st Floor', '30 PCs, Projector, Aircon. Whiteboard', 'Available', 'No Current Class', 'N/A', 'N/A'),
(102, NULL, NULL, 25, 'E110', 'Engineering Lab', '1st Floor', 'Oscilloscopes, Benches', 'Occupied', 'Circuit Analysis', 'Prof. Adela Onlao', '09:00:00 - 10:00:00'),
(105, NULL, NULL, 40, '203', 'Computer Lab', '2nd Floor', 'Whiteboard', 'Occupied', 'Web Development', 'Prof. Francisco Rivas', '14:00:00 - 17:00:00'),
(106, NULL, NULL, 30, 'Lab1', 'Computer Lab', '1st Floor', '30 PCs, Projector, Aircon. Whiteboard', 'Available', 'No Current Class', 'N/A', 'N/A'),
(107, NULL, NULL, 40, '301', 'Lecture Hall', '3rd Floor', 'Whiteboard', 'Occupied', 'College Algebra', 'Prof. Adela Onlao', '07:00:00 - 08:00:00'),
(108, NULL, NULL, 40, '404', 'Computer Lab', '4rth Floor', 'Whiteboard', 'Occupied', 'Web Development', 'Prof.Dennis Gresola', '09:00:00 - 10:00:00'),
(109, NULL, NULL, 45, '501', 'Computer Lab', '5th Floor', 'Whiteboard', 'Occupied', 'System Architecture', 'Prof.Dennis Gresola', '09:00:00 - 10:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `work_start` time DEFAULT NULL,
  `work_end` time DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `unit` int(11) DEFAULT NULL,
  `schedule` varchar(100) DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `instructor` varchar(100) DEFAULT NULL,
  `course` varchar(50) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `SubjectID` int(11) NOT NULL COMMENT 'FK to subjects table',
  `instructor_id` int(11) NOT NULL COMMENT 'FK to instructors table',
  `section_id` int(11) NOT NULL COMMENT 'FK to sections table',
  `room_id` int(11) NOT NULL COMMENT 'FK to rooms table',
  `start_time` time NOT NULL COMMENT 'Class start time',
  `end_time` time NOT NULL COMMENT 'Class end time',
  `semester` enum('Fall','Spring','Summer') NOT NULL COMMENT 'Academic semester',
  `academic_year` varchar(20) NOT NULL COMMENT 'Academic year (e.g., 2025–2026)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`schedule_id`, `employee_id`, `work_start`, `work_end`, `code`, `subject`, `unit`, `schedule`, `room`, `instructor`, `course`, `year_level`, `SubjectID`, `instructor_id`, `section_id`, `room_id`, `start_time`, `end_time`, `semester`, `academic_year`) VALUES
(1001, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 37, 201, 105, '14:00:00', '17:00:00', 'Fall', '2025-2026'),
(1003, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 33, 101, 100, '10:30:00', '12:00:00', 'Fall', '2025-2026'),
(1006, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 33, 101, 102, '09:00:00', '10:00:00', 'Fall', '2025-2026'),
(1007, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 33, 201, 107, '07:00:00', '08:00:00', 'Spring', '2025-2026'),
(1008, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 37, 102, 105, '15:00:00', '16:00:00', 'Spring', '2025-2026'),
(1010, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 33, 101, 105, '16:00:00', '17:00:00', 'Summer', '2025-2026'),
(1011, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, 36, 209, 109, '09:00:00', '10:00:00', 'Fall', '2025-2026'),
(1013, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 36, 205, 108, '09:00:00', '10:00:00', 'Summer', '2025-2026');

-- --------------------------------------------------------

--
-- Table structure for table `scholars`
--

CREATE TABLE `scholars` (
  `scholar_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `scholarship_type` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `school_settings`
--

CREATE TABLE `school_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_name` varchar(100) DEFAULT NULL,
  `setting_value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `section_id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `section_name` varchar(50) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `name` varchar(50) NOT NULL COMMENT 'Section name (e.g., BSIT 3A)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`section_id`, `program_id`, `section_name`, `year_level`, `name`) VALUES
(101, 1, NULL, '1', 'BSIT-1 BLOCK-1'),
(102, 1, NULL, '3', 'BSIT-3 BLOCK-3'),
(201, 1, NULL, '1', 'BSIT-1 BLOCK-2'),
(205, 1, NULL, '3', 'BSIT-3 BLOCK-1'),
(206, 3, NULL, '2', 'BSEE-2 BLOCK-3'),
(207, 3, NULL, '2', 'BSEE-2 BLOCK-2'),
(208, 2, NULL, '3', 'BSCS-3 BLOCK-2'),
(209, 3, NULL, '3', 'BSEE-3 BLOCK-1'),
(210, 4, NULL, '2', 'BSN-2 BLOCK-2'),
(212, 4, NULL, '1', 'BSN-1 BLOCK-4');

-- --------------------------------------------------------

--
-- Table structure for table `security_assignments`
--

CREATE TABLE `security_assignments` (
  `assignment_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `assigned_area` varchar(100) DEFAULT NULL,
  `assigned_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_assignments_security`
--

CREATE TABLE `security_assignments_security` (
  `id_security` int(11) NOT NULL,
  `guard_id_security` int(11) NOT NULL,
  `post_security` varchar(100) NOT NULL,
  `shift_security` enum('morning','afternoon','night') NOT NULL,
  `assignment_date_security` date NOT NULL,
  `notes_security` text DEFAULT NULL,
  `assigned_by_security` int(11) NOT NULL,
  `created_at_security` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_type`
--

CREATE TABLE `service_type` (
  `service_type_id` int(11) NOT NULL,
  `service_name` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_type`
--

INSERT INTO `service_type` (`service_type_id`, `service_name`, `description`) VALUES
(1, 'Document Request', 'Request for academic documents'),
(2, 'Counseling Service', 'Guidance and counseling services'),
(3, 'Financial Inquiry', 'Tuition and fee inquiries'),
(4, 'Academic Advising', 'Course and program advising');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `session_id` int(11) NOT NULL,
  `case_id` int(11) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `session_date` datetime DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `session_type` varchar(50) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `session_notes` text DEFAULT NULL,
  `interventions_used` text DEFAULT NULL,
  `student_response` text DEFAULT NULL,
  `homework_assigned` text DEFAULT NULL,
  `next_session_plan` text DEFAULT NULL,
  `session_outcome` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `snapshots`
--

CREATE TABLE `snapshots` (
  `snapshot_id` int(11) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `attendance_log_id` int(11) DEFAULT NULL,
  `captured_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `special_events`
--

CREATE TABLE `special_events` (
  `event_id` int(11) NOT NULL,
  `event_name` varchar(150) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `paid` enum('yes','no','partial') DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_profiles`
--

CREATE TABLE `staff_profiles` (
  `StaffProfileID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Role` varchar(50) NOT NULL,
  `FName` varchar(100) NOT NULL,
  `LName` varchar(100) NOT NULL,
  `MName` varchar(100) DEFAULT NULL,
  `Age` int(11) DEFAULT NULL,
  `Email` varchar(150) DEFAULT NULL,
  `ContactNumber` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_profiles`
--

INSERT INTO `staff_profiles` (`StaffProfileID`, `UserID`, `Role`, `FName`, `LName`, `MName`, `Age`, `Email`, `ContactNumber`, `profile_picture`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 0, 'General Counselor', 'Vincent', 'Adorza', 'Ramos', 32, 'escabeche@gmail.com', '0912331231', 'uploads/avatars/user_temp_1765957641_2c471b02.jpg', '2025-12-17 07:47:21', '2025-12-17 07:47:21'),
(0, 1, 'Admin', '', '', '', 0, 'adfcguidance@example.com', '', NULL, '2025-12-17 08:38:43', '2025-12-17 08:38:43'),
(0, 1, 'Admin', '', '', '', 0, 'adfcguidance@example.com', '', NULL, '2025-12-17 08:38:58', '2025-12-17 08:38:58');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `StudID` int(11) NOT NULL,
  `StudType` varchar(20) DEFAULT NULL,
  `SchoolID` varchar(20) DEFAULT NULL,
  `FName` varchar(50) DEFAULT NULL,
  `LName` varchar(50) DEFAULT NULL,
  `MName` varchar(50) DEFAULT NULL,
  `ExtName` varchar(10) DEFAULT NULL,
  `Age` int(3) DEFAULT NULL,
  `DateOfBirth` date DEFAULT NULL,
  `PlaceOfBirth` varchar(100) DEFAULT NULL,
  `Province` varchar(50) DEFAULT NULL,
  `Sex` varchar(10) DEFAULT NULL,
  `CivilStatus` varchar(20) DEFAULT NULL,
  `Religion` varchar(20) DEFAULT NULL,
  `Nationality` varchar(50) DEFAULT NULL,
  `PhoneNo` varchar(15) DEFAULT NULL,
  `EmailAddr` varchar(100) DEFAULT NULL,
  `FathersName` varchar(100) DEFAULT NULL,
  `MothersMName` varchar(100) DEFAULT NULL,
  `Course` varchar(50) DEFAULT NULL,
  `YearLvl` varchar(10) DEFAULT NULL,
  `Semester` varchar(10) DEFAULT NULL,
  `Block` varchar(20) DEFAULT NULL,
  `DeptID` int(11) DEFAULT NULL,
  `Summer` tinyint(1) DEFAULT 0,
  `CHEDScholar` varchar(10) DEFAULT 'No',
  `EnrollmentClass` varchar(20) DEFAULT 'Regular',
  `Address` varchar(150) DEFAULT NULL,
  `Guardian` varchar(100) DEFAULT NULL,
  `LastSchoolAtt` varchar(100) DEFAULT NULL,
  `QRcode` varchar(255) DEFAULT NULL,
  `IsGraduate` tinyint(1) DEFAULT 0,
  `GrantAuthorityNumber` varchar(100) DEFAULT NULL,
  `YearGranted` varchar(10) DEFAULT NULL,
  `DateGranted` timestamp NULL DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Full name of student',
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `program_id` int(11) NOT NULL COMMENT 'FK to programs table',
  `UserID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`StudID`, `StudType`, `SchoolID`, `FName`, `LName`, `MName`, `ExtName`, `Age`, `DateOfBirth`, `PlaceOfBirth`, `Province`, `Sex`, `CivilStatus`, `Religion`, `Nationality`, `PhoneNo`, `EmailAddr`, `FathersName`, `MothersMName`, `Course`, `YearLvl`, `Semester`, `Block`, `DeptID`, `Summer`, `CHEDScholar`, `EnrollmentClass`, `Address`, `Guardian`, `LastSchoolAtt`, `QRcode`, `IsGraduate`, `GrantAuthorityNumber`, `YearGranted`, `DateGranted`, `user_id`, `name`, `username`, `password`, `program_id`, `UserID`) VALUES
(2024118, 'New Student', '456258', 'Raphael', 'Tayag', 'Enrile', '', 30, '1995-12-07', 'Javier, Leyte', NULL, 'Male', 'Single', 'INC', 'Filipino', '09876543218', 'Raprap@gmail.com', 'Abdul James Tayag', 'Ella Mae Bulaklak Enrile', 'Bachelor of Science in Information Technology', '1st Year', '1st Sem', '0', 1, 0, 'Yes', 'Regular', 'Brgy Binuntugon, Javier, Leyte', 'Abdul James Tayag', 'ACLC', 'qr_456258_1765963496.png', 0, NULL, NULL, NULL, NULL, '', NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_doc`
--

CREATE TABLE `student_doc` (
  `StudID` int(11) NOT NULL,
  `SchoolID` varchar(20) NOT NULL,
  `FName` varchar(50) NOT NULL,
  `LName` varchar(50) NOT NULL,
  `DateOfBirth` date NOT NULL,
  `Sex` enum('Male','Female','Other') DEFAULT NULL,
  `EmailAddr` varchar(100) NOT NULL,
  `PhoneNo` varchar(15) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `status` enum('Active','Inactive','Graduated','Suspended') DEFAULT 'Active',
  `has_registered` tinyint(1) DEFAULT 0,
  `registered_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_doc`
--

INSERT INTO `student_doc` (`StudID`, `SchoolID`, `FName`, `LName`, `DateOfBirth`, `Sex`, `EmailAddr`, `PhoneNo`, `Address`, `status`, `has_registered`, `registered_at`, `created_at`) VALUES
(1, 'STU-2024001', 'Buddy', 'Dudzzz', '2000-05-15', 'Male', 'buddydudzzz@gmail.com', '09691894248', 'Sample Address', 'Active', 1, '2025-12-17 11:06:36', '2025-11-22 13:02:37'),
(2, 'STU-2024002', 'Jake', 'Cornista', '2001-03-22', 'Male', 'cornistajake6@gmail.com', '097866787', 'Another Address', 'Active', 1, '2025-12-17 11:08:54', '2025-12-10 02:18:27'),
(3, 'STU-2024003', 'Charles', 'Parena', '1999-11-30', 'Male', 'zofri467@gmail.com', '09691894248', 'Third Address', 'Active', 1, '2025-12-17 11:13:01', '2025-11-26 00:52:18'),
(4, 'TEST-001', 'Test', 'User', '0000-00-00', NULL, 'test@email.com', NULL, NULL, 'Active', 0, NULL, '2025-12-16 06:51:35'),
(5, 'STU-2024004', 'jhun', 'adolf', '2025-12-16', 'Male', 'junjun@gmail.com', '09837238273', 'tagpuro sangyaw', 'Active', 1, '2025-12-17 15:17:05', '2025-12-17 07:16:33');

-- --------------------------------------------------------

--
-- Table structure for table `student_guidance`
--

CREATE TABLE `student_guidance` (
  `StudID` int(11) NOT NULL,
  `StudType` varchar(20) DEFAULT NULL,
  `SchoolID` varchar(20) DEFAULT NULL,
  `FName` varchar(50) NOT NULL,
  `LName` varchar(50) NOT NULL,
  `MName` varchar(50) DEFAULT NULL,
  `ExtName` varchar(10) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `Age` int(11) DEFAULT NULL,
  `DateOfBirth` date DEFAULT NULL,
  `PlaceOfBirth` varchar(100) DEFAULT NULL,
  `Province` varchar(100) DEFAULT NULL,
  `Sex` varchar(10) DEFAULT NULL,
  `CivilStatus` varchar(20) DEFAULT NULL,
  `Religion` varchar(50) DEFAULT NULL,
  `PersonalEmail` varchar(255) DEFAULT NULL,
  `StudentPhone` varchar(50) DEFAULT NULL,
  `GuardianPhone` varchar(50) DEFAULT NULL,
  `GuardianName` varchar(255) DEFAULT NULL,
  `CompleteAddress` varchar(500) DEFAULT NULL,
  `YearLevel` varchar(50) DEFAULT NULL,
  `Program` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_identification`
--

CREATE TABLE `student_identification` (
  `StudentID` int(11) NOT NULL,
  `StudentNumber` varchar(20) NOT NULL,
  `FullName` varchar(100) NOT NULL,
  `Course` varchar(50) NOT NULL,
  `YearLevel` int(11) NOT NULL,
  `ContactNo` varchar(15) NOT NULL,
  `GuardianName` varchar(100) NOT NULL,
  `GuardianContact` varchar(15) NOT NULL,
  `Birthdate` date NOT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `Qr_token` varchar(64) NOT NULL,
  `Photo` varchar(255) DEFAULT NULL,
  `Signature` varchar(255) DEFAULT NULL,
  `Qrimage` varchar(255) NOT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `ExpiryDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_identification`
--

INSERT INTO `student_identification` (`StudentID`, `StudentNumber`, `FullName`, `Course`, `YearLevel`, `ContactNo`, `GuardianName`, `GuardianContact`, `Birthdate`, `Address`, `Qr_token`, `Photo`, `Signature`, `Qrimage`, `CreatedAt`, `ExpiryDate`) VALUES
(0, '22-843763', 'Juan Versoza', 'BSCS', 2, '09436278382', 'Test5', '09347623842', '2003-02-17', 'hahahha', '44b61da84b275278e9a9296a92a4a5a1', '1765953875_1705282658145-removebg-preview.png', '1765953875_b1256934-da0f-4fd4-83bc-f681da624725 (1).png', 'qr_img/44b61da84b275278e9a9296a92a4a5a1.png', '2025-12-17 15:39:35', '2028-12-17'),
(0, '22-433034', 'Hay Versoza', 'BSCE', 4, '09436278382', 'Test5', '09347623842', '2003-02-17', 'test', '0d96aab8a2f89d6bd73c4f0bd2c33aff', '1765953959_image-removebg-preview (9).png', '1765953959_b1256934-da0f-4fd4-83bc-f681da624725 (1).png', 'qr_img/0d96aab8a2f89d6bd73c4f0bd2c33aff.png', '2025-12-17 15:40:59', '2026-12-17');

-- --------------------------------------------------------

--
-- Table structure for table `student_instructor_assignments`
--

CREATE TABLE `student_instructor_assignments` (
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_passwords`
--

CREATE TABLE `student_passwords` (
  `password_id` int(11) NOT NULL,
  `stud_id` int(11) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_passwords`
--

INSERT INTO `student_passwords` (`password_id`, `stud_id`, `password_hash`, `reset_token`, `reset_token_expires`, `created_at`, `updated_at`) VALUES
(3, 1, '$2y$10$uSf/3MI402wwS2yLlTR0neBVnqDw.KE62wnX.PpaIRu/SFgzI0dFe', NULL, NULL, '2025-12-17 03:06:36', '2025-12-17 03:06:36'),
(4, 2, '$2y$10$HjOh1BJnBG.2/GETxP4ZyO285S3/Zhfu8u2C.R0HF7GGOcnJpUr8m', NULL, NULL, '2025-12-17 03:08:54', '2025-12-17 03:08:54'),
(5, 3, '$2y$10$ql/kliR70FBLDYdGfxFUZOcXB1jj5lXPjhldIlXSs9bz3/apbchPu', NULL, NULL, '2025-12-17 03:13:01', '2025-12-17 03:13:01'),
(6, 5, '$2y$10$Wqv4h14DQsgpItRJaRMHue7PQoQA7cXeHEME1sv4IKRASmG.mQT6.', NULL, NULL, '2025-12-17 07:17:05', '2025-12-17 07:17:05');

-- --------------------------------------------------------

--
-- Table structure for table `student_requests`
--

CREATE TABLE `student_requests` (
  `RequestID` int(11) NOT NULL,
  `FullName` varchar(100) NOT NULL,
  `Course` varchar(50) NOT NULL,
  `YearLevel` int(11) NOT NULL,
  `ContactNo` varchar(15) NOT NULL,
  `GuardianName` varchar(100) NOT NULL,
  `GuardianContact` varchar(15) NOT NULL,
  `Birthdate` date NOT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `Qr_token` varchar(64) NOT NULL,
  `Qrimage` varchar(255) DEFAULT NULL,
  `Photo` varchar(255) DEFAULT 'user.png',
  `Signature` varchar(255) NOT NULL,
  `Status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `ApprovedAt` datetime DEFAULT NULL,
  `RejectionReason` text DEFAULT NULL,
  `RejectedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_schedules`
--

CREATE TABLE `student_schedules` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_subjects`
--

CREATE TABLE `student_subjects` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_subjects`
--

INSERT INTO `student_subjects` (`id`, `student_id`, `subject_id`, `assigned_at`, `created_by`, `created_at`) VALUES
(0, 22, 5, NULL, NULL, '2025-12-17 08:43:44');

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE `subject` (
  `SubjectID` int(11) NOT NULL,
  `SubCode` varchar(20) NOT NULL,
  `SubName` varchar(50) NOT NULL,
  `DescTitle` varchar(150) DEFAULT NULL,
  `Unit` decimal(3,1) DEFAULT NULL,
  `Cost` decimal(10,2) DEFAULT NULL,
  `type` enum('Lecture','Lab','Lecture/Lab') NOT NULL COMMENT 'Subject type',
  `year_level` int(11) NOT NULL COMMENT 'Year level required'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject`
--

INSERT INTO `subject` (`SubjectID`, `SubCode`, `SubName`, `DescTitle`, `Unit`, `Cost`, `type`, `year_level`) VALUES
(1, 'ITP114', 'General Mathematics', 'Gen Math', 3.0, NULL, 'Lecture', 0),
(2, 'CS101', 'Introduction to Programming', NULL, 3.0, NULL, 'Lecture/Lab', 1),
(3, 'IT302', 'Web Development', NULL, 3.0, NULL, 'Lab', 3),
(4, 'EE301', 'Circuit Analysis', NULL, 4.0, NULL, 'Lecture', 3),
(5, 'MATH101', 'College Algebra', NULL, 3.0, NULL, 'Lecture', 1),
(6, 'ITP 411L', 'System Integration', NULL, 4.0, NULL, 'Lab', 0),
(9, 'ITP 301L', 'System Architecture', NULL, 3.0, NULL, 'Lecture/Lab', 3),
(11, 'ITE 401L', 'System Integration', NULL, 4.0, NULL, 'Lab', 4);

-- --------------------------------------------------------

--
-- Table structure for table `subjectenrollment`
--

CREATE TABLE `subjectenrollment` (
  `EnrollID` int(11) NOT NULL,
  `StudID` int(11) NOT NULL,
  `SubjectID` int(11) NOT NULL,
  `EPeriodID` int(11) DEFAULT NULL,
  `EnrollDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjectenrollment`
--

INSERT INTO `subjectenrollment` (`EnrollID`, `StudID`, `SubjectID`, `EPeriodID`, `EnrollDate`) VALUES
(0, 2024111, 1, 1, '2025-12-17 07:26:51'),
(0, 2024111, 2, 1, '2025-12-17 07:26:51'),
(0, 2024111, 3, 1, '2025-12-17 07:26:51');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(10) UNSIGNED NOT NULL,
  `subject_code` varchar(50) NOT NULL,
  `subject_name` varchar(191) NOT NULL,
  `units` int(11) NOT NULL DEFAULT 3,
  `schedule` varchar(191) NOT NULL,
  `room` varchar(50) NOT NULL,
  `instructor` varchar(100) NOT NULL,
  `teacher_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subsched`
--

CREATE TABLE `subsched` (
  `SubSchedID` int(11) NOT NULL,
  `Schedule` varchar(50) DEFAULT NULL,
  `Room` varchar(30) DEFAULT NULL,
  `SubjectID` int(11) NOT NULL,
  `InsID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subsched`
--

INSERT INTO `subsched` (`SubSchedID`, `Schedule`, `Room`, `SubjectID`, `InsID`) VALUES
(1, '1:00-2:00 MW', 'LAB 2', 1, NULL),
(2, '7:00-8:00 MW', 'LAB 2', 2, NULL),
(3, '8:00-10:00 MW', 'LAB 2', 3, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL,
  `log_type` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`log_id`, `log_type`, `message`, `created_at`) VALUES
(1, 'Student Registration', 'Yanyan Dacles (ID: 22-40685) registered for the BSIT program (1st Year).', '2025-12-15 08:30:50'),
(0, 'Student Updated', 'Student information updated - ID: 24, Name: John Smith, Course: BSIT, Year: 1st Year, Semester: 1st Semester', '2025-12-17 08:16:11'),
(0, 'Notice Posted', 'New notice posted - Title: Finals Exam, Type: General', '2025-12-17 08:52:35');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs_security`
--

CREATE TABLE `system_logs_security` (
  `id_security` int(11) NOT NULL,
  `user_id_security` int(11) DEFAULT NULL,
  `action_security` varchar(100) NOT NULL,
  `description_security` text DEFAULT NULL,
  `module_security` varchar(50) NOT NULL,
  `record_id_security` int(11) DEFAULT NULL,
  `ip_address_security` varchar(45) DEFAULT NULL,
  `user_agent_security` text DEFAULT NULL,
  `created_at_security` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs_security`
--

INSERT INTO `system_logs_security` (`id_security`, `user_id_security`, `action_security`, `description_security`, `module_security`, `record_id_security`, `ip_address_security`, `user_agent_security`, `created_at_security`) VALUES
(0, 15, 'user_logout', 'User logged out of system', 'auth', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-15 08:35:49');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_name` varchar(100) DEFAULT NULL,
  `setting_value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tax_deduction_settings`
--

CREATE TABLE `tax_deduction_settings` (
  `tax_id` int(11) NOT NULL,
  `tax_name` varchar(100) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`teacher_id`, `user_id`, `department`) VALUES
(2, 34, 'Computer Science');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_subjects`
--

CREATE TABLE `teacher_subjects` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_subjects`
--

INSERT INTO `teacher_subjects` (`id`, `teacher_id`, `subject_id`) VALUES
(1, 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `teller_windows`
--

CREATE TABLE `teller_windows` (
  `window_id` int(11) NOT NULL,
  `window_name` varchar(50) NOT NULL,
  `window_type` varchar(50) NOT NULL,
  `transaction_types` text NOT NULL,
  `programs` text NOT NULL,
  `current_queue` int(11) DEFAULT 1,
  `is_active` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teller_windows`
--

INSERT INTO `teller_windows` (`window_id`, `window_name`, `window_type`, `transaction_types`, `programs`, `current_queue`, `is_active`) VALUES
(1, 'Window 1', 'Grade Evaluation', 'Grade Evaluation', 'BSIT,BSCE,BSCPE,BSCS,DIP-IT,BSBA,BSA,BSEntr,AB-COMM,DIP-Entrep,MED,MM,MBA-HA,MBA,PHD,MIT,MAED', 1, 1),
(2, 'Window 2', 'Grade Evaluation', 'Grade Evaluation', 'BSCRIM,BSED,BEED,BSN,BSM,AB-Psych,BSTM,BSHM,DIP-TM,DIP-HM,DIP-CUL', 1, 1),
(3, 'Counter 1', 'Payment/Clearance', 'Payment,Clearance,Other Transactions', 'BSCE,BSIT,BSCOE,BSCS,BSCRIM,STEM', 1, 1),
(4, 'Counter 2', 'Payment/Clearance', 'Payment,Clearance,Other Transactions', 'AB,BEED,BSED,BSA,BSBA,BSENTERP,BSHM,BSTM,BSN,BSMID,DIP-TM,DIP-HM,DIP-ENTREP,ABM,GAS,HUMMS,MASTERAL,PHD', 1, 1),
(5, 'Releasing', 'Document Release', 'TOR Request,GMC Request,Document Release', 'PHD,MASTERAL,BSED,BSHM,BSTM,BSN,BSA,BSTM,BSHM,ENTREP,ABMC,ABPSYCH,BSBA,BEED', 1, 1),
(6, 'Registrar', 'Registrar Services', 'Enrollment,Registration,Course Crediting,Scholarship Application', 'ALL', 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `thirteenth_month_payroll`
--

CREATE TABLE `thirteenth_month_payroll` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `total_basic` decimal(12,2) DEFAULT 0.00,
  `thirteenth_amount` decimal(12,2) DEFAULT 0.00,
  `paid_status` enum('Paid','Unpaid') DEFAULT 'Unpaid',
  `payment_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `time_date_settings`
--

CREATE TABLE `time_date_settings` (
  `id` int(11) NOT NULL,
  `timezone` varchar(100) DEFAULT NULL,
  `format` varchar(50) DEFAULT NULL,
  `grace_in_minutes` int(11) DEFAULT 0,
  `grace_out_minutes` int(11) DEFAULT 0,
  `company_hours_per_day` decimal(5,2) DEFAULT 8.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `transaction_id` int(11) NOT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `transaction_type` enum('issued','returned') NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `TransactID` int(11) NOT NULL,
  `MemberID` int(11) DEFAULT NULL,
  `BookID` int(11) DEFAULT NULL,
  `borrowed_at` datetime DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `status` enum('borrowed','returned') DEFAULT 'borrowed',
  `fine` decimal(8,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaction_log`
--

CREATE TABLE `transaction_log` (
  `log_id` int(11) NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaction_types`
--

CREATE TABLE `transaction_types` (
  `type_id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_types`
--

INSERT INTO `transaction_types` (`type_id`, `type_name`, `category`) VALUES
(1, 'Grade Evaluation', 'evaluation'),
(2, 'Payment', 'payment'),
(3, 'Clearance', 'payment'),
(4, 'Other Transactions', 'payment'),
(5, 'TOR Request', 'release'),
(6, 'GMC Request', 'release'),
(7, 'Document Release', 'release'),
(8, 'Enrollment', 'registrar'),
(9, 'Registration', 'registrar'),
(10, 'Course Crediting', 'registrar'),
(11, 'Scholarship Application', 'registrar');

-- --------------------------------------------------------

--
-- Table structure for table `tuition_fees`
--

CREATE TABLE `tuition_fees` (
  `fee_id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `effective_year` varchar(20) DEFAULT NULL,
  `course` varchar(50) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `total_fee` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `rate_per_unit` decimal(10,2) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `lec` decimal(5,2) DEFAULT NULL,
  `lab` decimal(5,2) DEFAULT NULL,
  `units` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `rate_lec` decimal(10,2) DEFAULT NULL,
  `rate_lab` decimal(10,2) DEFAULT NULL,
  `rate_unit` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Fname` varchar(100) DEFAULT NULL,
  `Lname` varchar(100) DEFAULT NULL,
  `Email` varchar(150) DEFAULT NULL,
  `Department` varchar(150) DEFAULT NULL,
  `DeptID` int(11) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `avatar` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `Username`, `Fname`, `Lname`, `Email`, `Department`, `DeptID`, `fullname`, `Password`, `Role`, `created_at`, `avatar`, `phone_number`, `avatar_path`, `is_active`) VALUES
(1, 'admin', NULL, NULL, 'admin@gmail.com', NULL, NULL, NULL, 'admin123', 'Admin', '2025-12-14 14:25:03', NULL, NULL, NULL, 1),
(2, 'staff1', NULL, NULL, 'staff1@gmail.com', NULL, NULL, NULL, 'staff123', 'staff', '2025-12-14 14:25:03', NULL, NULL, NULL, 1),
(3, 'staff2', NULL, NULL, 'staff2@gmail.com', NULL, NULL, NULL, 'staff123', 'staff', '2025-12-14 14:25:03', NULL, NULL, NULL, 1),
(4, 'ricoabad', NULL, NULL, 'ricoabad@gmail.com', NULL, NULL, 'Johnwill', '$2y$10$1AUfJ7njn6p25HIwn3OMNeSq0v9pbD7D7dP.l0NackCCT2VAt8wIa', 'Librarian', '2025-12-14 14:25:03', NULL, NULL, NULL, 1),
(5, 'guidanceadmin', NULL, NULL, 'guidanceadmin@gmail.com', NULL, NULL, NULL, '$2y$10$lVz6696kND7xNCJ0Y3dRD.gU0duqnonpW/26JSS4LjBOstOq3BH.G', 'admin', '2025-12-14 16:04:51', NULL, NULL, NULL, 1),
(6, 'Yanyan', 'Yanyan', 'Dacles', 'yanyan12ros@gmail.com', NULL, NULL, NULL, '$2y$10$28MF2qducIhE7thfJQD74OmytC7OaAno.uQwfoN3kEL07t9lDI6yS', 'student', '2025-12-15 08:30:50', NULL, NULL, NULL, 1),
(7, 'sambortoy', NULL, NULL, 'sambortoy@gmail.com', NULL, NULL, NULL, 'sammy123', 'staff', '2025-12-15 08:41:46', NULL, NULL, NULL, 1),
(8, 'scholar1', 'Mark', 'Lee', 'mark.lee@example.com', NULL, NULL, NULL, '$2y$10$hashedpassword123', 'student', '2025-12-15 08:50:54', NULL, NULL, NULL, 1),
(9, 'scholar2', 'Anna', 'Tan', 'anna.tan@example.com', NULL, NULL, NULL, '$2y$10$hashedpassword456', 'student', '2025-12-15 08:50:54', NULL, NULL, NULL, 1),
(11, 'totoy', 'System', 'Administrator', 'admin@example.com', 'IT', 1, 'System Administrator', '$2y$10$wH4YQ3Dk8LwY8r4X1bXv3eVbJk9pZJwYF7c7bZ8nK7dG8P3l5Oq4e', 'head_admin', '2025-12-15 09:18:16', NULL, NULL, NULL, 1),
(12, 'Christian', NULL, NULL, 'yanyan12ros@gmail.com', NULL, NULL, NULL, 'admin123', '', '2025-12-15 09:21:10', NULL, NULL, NULL, 1),
(13, 'katkat', NULL, NULL, 'katkat@gmail.com', NULL, NULL, 'katrina', '$2y$10$68KBpHMV0NTT9VQSvQwF5OV9WAWV/lIQ08k.cFW6DJme2QonyDvl6', 'admin', '2025-12-15 09:21:49', NULL, NULL, NULL, 1),
(14, 'cashier', 'cedrick', 'bermejo', 'cashier@gmail.com', NULL, NULL, 'cedrick bermejo', '$2y$10$od00rGgnEXujWEzXRf9DL.A/4I7sO.zwUAzrSXOADHbJ2BvDjpPtu', 'Cashier', '2025-12-16 14:00:39', NULL, NULL, NULL, 1),
(15, 'qwrq', 'qwqe', 'qweq', 'qwrq@gmail.com', NULL, NULL, 'qwqe qweq', '$2y$10$1Txg7AaLmqBPtv37.wctLecFdi38ZRhjaPWYK3o9U/UbNYLQmjiqW', 'Cashier', '2025-12-16 15:43:04', NULL, NULL, NULL, 1),
(28, 'admin123', NULL, NULL, 'ricolovemarjori@gmail.com', NULL, NULL, NULL, '$2y$10$1fiZ8X4dx9sZo57gBDP3puCNEAtv4Nrw5UWBANuWMYYHYv9iN5Aoi', 'Student', '2025-12-17 08:05:12', NULL, NULL, NULL, 1),
(29, 'Dacles', NULL, NULL, 'christiandacles11@gmail.com', NULL, NULL, NULL, '$2y$10$IEkcvXFDS/sb3h7KeXJWieVuNpqUrSFh2ifWz/P2ambCBmr/9FO/2', 'admin', '2025-12-14 07:50:30', NULL, NULL, NULL, 1),
(30, 'siaaa', NULL, NULL, NULL, NULL, NULL, NULL, 'siasia', 'admin', '2025-12-17 08:27:37', NULL, NULL, NULL, 1),
(32, 'admin101@gmail.com', 'admin1', 'admin111', 'admin101@lms.edu', NULL, NULL, NULL, '$2y$10$OxF1atMy26WIzC82UnufCOlbg5v4p795VEl2d3KBMPEtDx.o/LucG', 'student', '2025-12-17 08:35:36', NULL, NULL, NULL, 1),
(33, 'admin@lms.edu', NULL, NULL, 'admin@lms.edu', NULL, NULL, NULL, '$2y$10$O7C0OQzj4dD.CyGsdMMA2euExfPwuE7u7Az.ZnmK9TwLpJJLj4L7C', 'Admin', '2025-12-17 08:37:08', NULL, NULL, NULL, 1),
(35, 'GR', NULL, NULL, NULL, NULL, NULL, NULL, '8365f20e6414ae81dc4be25972e2fa77', 'Teacher', '2025-12-17 09:03:39', NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users_doc`
--

CREATE TABLE `users_doc` (
  `User_id` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `MiddleName` varchar(50) DEFAULT NULL,
  `ExtensionName` varchar(10) DEFAULT NULL,
  `Phone` varchar(20) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Gender` enum('Male','Female','Other') DEFAULT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` enum('Student','Registrar','Admin','Guest') NOT NULL DEFAULT 'Student',
  `Status` enum('Active','Inactive','Pending') NOT NULL DEFAULT 'Active',
  `Created_at` datetime DEFAULT current_timestamp(),
  `Updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='System users including students, registrars, and admins';

--
-- Dumping data for table `users_doc`
--

INSERT INTO `users_doc` (`User_id`, `FirstName`, `LastName`, `MiddleName`, `ExtensionName`, `Phone`, `Email`, `Gender`, `Password`, `Role`, `Status`, `Created_at`, `Updated_at`) VALUES
(2, 'Maria', 'Santos', NULL, NULL, '09998887777', 'maria@registrar.com', 'Female', '$2y$10$1z5nqO3W1pX9h1nV4C3KkOTzvT9DNhC8F1T7J7oVwTQl8f4eNhwFG', 'Registrar', 'Active', '2025-11-10 09:44:40', '2025-11-10 09:44:40'),
(5, 'admin', 'admin', NULL, NULL, '09343243225', 'admin@gmail.com', 'Male', '$2y$10$WecyxKkQuEEwTg75/O80ROzHgfxey.yop42CZs37K0TIHuJWgr21m', 'Admin', 'Active', '2025-11-18 07:23:02', '2025-11-18 07:23:24'),
(6, 'ghin', 'Adolfo', NULL, NULL, '09486665639', 'ghin@gmail.com', 'Female', '$2y$10$QQOQA6RWuEtDqjWqSVwR4.tqYzycztyehQuBMp7SoBVWi4Y5FaRB6', 'Registrar', 'Active', '2025-11-18 10:45:37', '2025-11-18 10:46:01'),
(7, 'Administrator', 'admin', NULL, NULL, '09691894248', 'AdolfoArghinMark@gmail.com', 'Male', '$2y$10$svHRXyXEzQMW4pbcxOU29.K6MF0/sLKh9ae9r/A66MtNwEojkCSXO', 'Admin', 'Active', '2025-11-22 20:51:25', '2025-11-22 20:52:25'),
(12, 'Jake', 'Cornista', NULL, NULL, '097866787', 'cornistajake6@gmail.com', NULL, '$2y$10$G3lHb8kJ74t8jhIJtym9pOMen3tAAFFEUcrEUl51642mKVrO/y.iC', 'Student', 'Active', '2025-12-17 10:33:06', '2025-12-17 10:33:06'),
(13, 'Buddy', 'Dudzzz', NULL, NULL, '09691894248', 'buddydudzzz@gmail.com', NULL, '$2y$10$uSf/3MI402wwS2yLlTR0neBVnqDw.KE62wnX.PpaIRu/SFgzI0dFe', 'Student', 'Active', '2025-12-17 11:06:36', '2025-12-17 11:06:36'),
(14, 'Charles', 'Parena', NULL, NULL, '09691894248', 'zofri467@gmail.com', NULL, '$2y$10$ql/kliR70FBLDYdGfxFUZOcXB1jj5lXPjhldIlXSs9bz3/apbchPu', 'Student', 'Active', '2025-12-17 11:13:01', '2025-12-17 11:13:01'),
(15, 'jhun', 'adolf', NULL, NULL, '09837238273', 'junjun@gmail.com', NULL, '$2y$10$Wqv4h14DQsgpItRJaRMHue7PQoQA7cXeHEME1sv4IKRASmG.mQT6.', 'Student', 'Active', '2025-12-17 15:17:05', '2025-12-17 15:17:05');

--
-- Triggers `users_doc`
--
DELIMITER $$
CREATE TRIGGER `validate_email_format` BEFORE INSERT ON `users_doc` FOR EACH ROW BEGIN
    IF NEW.Email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+.[A-Za-z]{2,}$' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid email format';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `users_employee`
--

CREATE TABLE `users_employee` (
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
-- Dumping data for table `users_employee`
--

INSERT INTO `users_employee` (`id`, `first_name`, `last_name`, `email`, `phone_number`, `address`, `department_id`, `password_hash`, `is_active`, `created_at`, `updated_at`, `avatar_path`, `roles`) VALUES
(0, 'Alexander', 'Osias', 'alexanderosias123@gmail.com', '09305909175', 'So. Bugho', 1, '$2y$10$h6DDUHev8U5PhpT/60UFuer5uQHinicxdcJKmsIWaqsjt00jJlFPO', 1, '2025-12-18 06:55:08', '2025-12-18 06:55:08', NULL, '[\"employee\"]'),
(1, 'rommel', 'baquiran', 'superadmin@gmail.com', '092637346353', 'taga balay', 1, '$2y$10$eFmGsmOld4JDMgkJunzcx.IQo6gPwS8CvtMecl0rY21mm30oZgCYy', 1, '2025-12-15 09:33:24', '2025-12-15 09:33:24', NULL, '[\"head_admin\"]');

-- --------------------------------------------------------

--
-- Table structure for table `users_for_id`
--

CREATE TABLE `users_for_id` (
  `UserID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `FullName` varchar(100) NOT NULL,
  `RoleID` int(11) NOT NULL,
  `Status` varchar(20) DEFAULT 'Active',
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `LastLogin` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users_for_id`
--

INSERT INTO `users_for_id` (`UserID`, `Username`, `Password`, `FullName`, `RoleID`, `Status`, `CreatedAt`, `LastLogin`) VALUES
(1, 'Admin', '$2y$10$FWatJd5WdUoL5DDQpIMr7eTOAgLjrXL4Aq/qW1jHb25bH6BRfk07e', 'Administrator', 1, 'Active', '2025-11-15 17:27:08', '2025-12-17 15:39:04'),
(2, 'username', '$2y$10$RnkLgQpoeaqfzc/Dc8WgDezuGSpffCNM4BCehAOeqTIQe8QKRYqO2', 'First User', 2, 'Active', '2025-11-15 17:27:34', '2025-12-15 15:51:29'),
(3, 'Staffa', '$2y$10$wcN7UvJju48M6ocu3zLS9eoXrlP4pGH6EGPboL2KJWQ9V7.FiVWJ.', 'First User', 2, 'Active', '2025-11-15 18:01:01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users_security`
--

CREATE TABLE `users_security` (
  `id_security` int(11) NOT NULL,
  `username_security` varchar(50) NOT NULL,
  `email_security` varchar(100) NOT NULL,
  `password_security` varchar(255) NOT NULL,
  `role_security` enum('super_admin','admin','security_manager','security_guard','instructor','employee','student') NOT NULL,
  `full_name_security` varchar(100) NOT NULL,
  `student_id_security` varchar(20) DEFAULT NULL,
  `employee_id_security` varchar(20) DEFAULT NULL,
  `department_security` varchar(100) DEFAULT NULL,
  `phone_security` varchar(20) DEFAULT NULL,
  `is_active_security` tinyint(1) DEFAULT 1,
  `last_login_security` timestamp NULL DEFAULT NULL,
  `created_at_security` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at_security` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users_security`
--

INSERT INTO `users_security` (`id_security`, `username_security`, `email_security`, `password_security`, `role_security`, `full_name_security`, `student_id_security`, `employee_id_security`, `department_security`, `phone_security`, `is_active_security`, `last_login_security`, `created_at_security`, `updated_at_security`) VALUES
(1, 'superadmin', 'superadminadfc@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'Super Administrator', NULL, 'EMP-001', 'Administration', '+639123456789', 1, '2025-12-12 00:58:18', '2025-10-28 11:30:05', '2025-12-12 00:58:18'),
(10, 'LadyGuard', 'ladyguard@gmail.com', '$2y$10$ZbNniZIY49jSgePQphEWIOEHsbk8U1sQ4ydC6fJGNq5FSgHR/VU.2', 'security_guard', 'Ate Guard', '', '121243', 'Security', '09373628472', 1, '2025-12-12 04:21:24', '2025-11-30 18:26:00', '2025-12-12 04:21:24'),
(11, 'shane', 'shane@gmail.com', '$2y$10$3rCZSuikIYkYHdV2gyLm9Ok/OO2wDOh8ruTp0w.gGk3VQJeQhX0C2', 'student', 'Jezelle Shane Hechanova', '12-12345', '', 'IT', '09461461277', 1, '2025-12-11 09:24:51', '2025-11-30 18:28:50', '2025-12-11 09:24:51'),
(12, 'sec.manager', 'sec@gmail.com', '$2y$10$riXWeVZmE.Et5CRpOKTDVeX9kacYN/DiCYD7OauJIyUboYKc5h23G', 'security_manager', 'Security Manager', '', '101010', 'Security', '09372647362', 1, '2025-12-11 21:28:40', '2025-12-02 15:51:33', '2025-12-11 21:28:40'),
(13, 'adfcadmin', 'admin@gmail.com', '$2y$10$DrqpRDP9uuyd8zlGWJskDebyAAN7BEXBXALri.bcPpMxDlLuNOMXe', 'admin', 'Administrator', '', '12345', 'Administrator', '09273676182', 1, '2025-12-10 22:43:36', '2025-12-06 04:51:44', '2025-12-10 22:43:36'),
(14, 'rommel', 'rommelbaquiran15@gmail.com', '$2y$10$LbmivRuAbY3qyc80C0D2IONpxTjaqv7Xcfs8Ic3vA9fuLqj6y1rDK', 'student', 'Rommel Baquiran', '12-12346', NULL, 'IT', '09661498101', 1, '2025-12-12 00:50:20', '2025-12-11 05:16:35', '2025-12-12 00:50:20'),
(15, 'rivas', 'rivas@gmail.com', '$2y$10$G9qex53y.hDkEmGTnIRbWu5jK6lSi5XdalO6WpESo1YnMHffg0nr6', 'instructor', 'Francis Rivas', '', '98-12374', 'IT INSTRUCTOR', '09635464737', 1, '2025-12-11 21:08:43', '2025-12-11 19:23:27', '2025-12-11 21:08:43'),
(16, 'paul', 'paul@gmail.com', '$2y$10$Sqc/qpGy.G9hJdXFgQ2y2OBKJn5s9rx6TfKwRvJorFXHPZ77BTdJS', 'employee', 'Paul Employee', '', '09-09876', 'ADFC', '09463738274', 1, '2025-12-12 01:25:21', '2025-12-11 20:07:51', '2025-12-12 01:25:21'),
(0, 'salas', 'salas@gmail.com', '$2y$10$an/OzhGRtQYWn8WPQkepGu10HRC06y3FkhcEu8n/.1/SOPWRLDG4.', 'instructor', 'Romme', NULL, NULL, NULL, '', 1, '2025-12-15 08:55:50', '2025-12-15 08:40:15', '2025-12-15 08:55:50'),
(1, 'superadmin', 'superadminadfc@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'Super Administrator', NULL, 'EMP-001', 'Administration', '+639123456789', 1, '2025-12-12 00:58:18', '2025-10-28 11:30:05', '2025-12-12 00:58:18'),
(10, 'LadyGuard', 'ladyguard@gmail.com', '$2y$10$ZbNniZIY49jSgePQphEWIOEHsbk8U1sQ4ydC6fJGNq5FSgHR/VU.2', 'security_guard', 'Ate Guard', '', '121243', 'Security', '09373628472', 1, '2025-12-12 04:21:24', '2025-11-30 18:26:00', '2025-12-12 04:21:24'),
(11, 'shane', 'shane@gmail.com', '$2y$10$3rCZSuikIYkYHdV2gyLm9Ok/OO2wDOh8ruTp0w.gGk3VQJeQhX0C2', 'student', 'Jezelle Shane Hechanova', '12-12345', '', 'IT', '09461461277', 1, '2025-12-11 09:24:51', '2025-11-30 18:28:50', '2025-12-11 09:24:51'),
(12, 'sec.manager', 'sec@gmail.com', '$2y$10$riXWeVZmE.Et5CRpOKTDVeX9kacYN/DiCYD7OauJIyUboYKc5h23G', 'security_manager', 'Security Manager', '', '101010', 'Security', '09372647362', 1, '2025-12-11 21:28:40', '2025-12-02 15:51:33', '2025-12-11 21:28:40'),
(13, 'adfcadmin', 'admin@gmail.com', '$2y$10$DrqpRDP9uuyd8zlGWJskDebyAAN7BEXBXALri.bcPpMxDlLuNOMXe', 'admin', 'Administrator', '', '12345', 'Administrator', '09273676182', 1, '2025-12-10 22:43:36', '2025-12-06 04:51:44', '2025-12-10 22:43:36'),
(14, 'rommel', 'rommelbaquiran15@gmail.com', '$2y$10$LbmivRuAbY3qyc80C0D2IONpxTjaqv7Xcfs8Ic3vA9fuLqj6y1rDK', 'student', 'Rommel Baquiran', '12-12346', NULL, 'IT', '09661498101', 1, '2025-12-12 00:50:20', '2025-12-11 05:16:35', '2025-12-12 00:50:20'),
(15, 'rivas', 'rivas@gmail.com', '$2y$10$G9qex53y.hDkEmGTnIRbWu5jK6lSi5XdalO6WpESo1YnMHffg0nr6', 'instructor', 'Francis Rivas', '', '98-12374', 'IT INSTRUCTOR', '09635464737', 1, '2025-12-11 21:08:43', '2025-12-11 19:23:27', '2025-12-11 21:08:43'),
(16, 'paul', 'paul@gmail.com', '$2y$10$Sqc/qpGy.G9hJdXFgQ2y2OBKJn5s9rx6TfKwRvJorFXHPZ77BTdJS', 'employee', 'Paul Employee', '', '09-09876', 'ADFC', '09463738274', 1, '2025-12-12 01:25:21', '2025-12-11 20:07:51', '2025-12-12 01:25:21'),
(0, 'makoy', 'makoy@gmail.com', '$2y$10$SZh0UlzPSI3bOquQHzcTvOf5hnKA8pSpHuAZ3yInnfX1V82b3EzSO', 'employee', 'mak', NULL, NULL, NULL, '', 1, '2025-12-15 08:55:50', '2025-12-15 08:55:19', '2025-12-15 08:55:50');

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `permission_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `permission_name` varchar(100) DEFAULT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions_security`
--

CREATE TABLE `user_permissions_security` (
  `id_security` int(11) NOT NULL,
  `user_id_security` int(11) NOT NULL,
  `permission_key_security` varchar(50) NOT NULL,
  `permission_value_security` tinyint(1) DEFAULT 1,
  `granted_at_security` timestamp NOT NULL DEFAULT current_timestamp(),
  `granted_by_security` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_permissions_security`
--

INSERT INTO `user_permissions_security` (`id_security`, `user_id_security`, `permission_key_security`, `permission_value_security`, `granted_at_security`, `granted_by_security`) VALUES
(259, 1, 'access_labs', 1, '2025-10-30 03:55:47', 1),
(260, 1, 'borrow_keys', 1, '2025-10-30 03:55:47', 1),
(261, 1, 'check_attendance', 1, '2025-10-30 03:55:47', 1),
(262, 1, 'claim_lost_found', 1, '2025-10-30 03:55:47', 1),
(263, 1, 'deactivate_users', 1, '2025-10-30 03:55:47', 1),
(264, 1, 'delete_violations', 1, '2025-10-30 03:55:47', 1),
(265, 1, 'edit_student_info', 1, '2025-10-30 03:55:47', 1),
(266, 1, 'edit_violations', 1, '2025-10-30 03:55:47', 1),
(267, 1, 'export_reports', 1, '2025-10-30 03:55:47', 1),
(268, 1, 'generate_reports', 1, '2025-10-30 03:55:47', 1),
(269, 1, 'manage_assignments', 1, '2025-10-30 03:55:47', 1),
(270, 1, 'manage_attendance', 1, '2025-10-30 03:55:47', 1),
(271, 1, 'manage_keys', 1, '2025-10-30 03:55:47', 1),
(272, 1, 'manage_labs', 1, '2025-10-30 03:55:47', 1),
(273, 1, 'manage_lost_found', 1, '2025-10-30 03:55:47', 1),
(274, 1, 'manage_permissions', 1, '2025-10-30 03:55:47', 1),
(275, 1, 'manage_security_team', 1, '2025-10-30 03:55:47', 1),
(276, 1, 'manage_system', 1, '2025-10-30 03:55:47', 1),
(277, 1, 'manage_users', 1, '2025-10-30 03:55:47', 1),
(278, 1, 'report_lab_incidents', 1, '2025-10-30 03:55:47', 1),
(279, 1, 'report_lost_found', 1, '2025-10-30 03:55:47', 1),
(280, 1, 'report_violations', 1, '2025-10-30 03:55:47', 1),
(281, 1, 'return_keys', 1, '2025-10-30 03:55:47', 1),
(282, 1, 'review_violations', 1, '2025-10-30 03:55:47', 1),
(283, 1, 'view_assignments', 1, '2025-10-30 03:55:47', 1),
(284, 1, 'view_attendance', 1, '2025-10-30 03:55:47', 1),
(285, 1, 'view_keys', 1, '2025-10-30 03:55:47', 1),
(286, 1, 'view_lab_incidents', 1, '2025-10-30 03:55:47', 1),
(287, 1, 'view_lost_found', 1, '2025-10-30 03:55:47', 1),
(288, 1, 'view_reports', 1, '2025-10-30 03:55:47', 1),
(289, 1, 'view_security_team', 1, '2025-10-30 03:55:47', 1),
(290, 1, 'view_student_info', 1, '2025-10-30 03:55:47', 1),
(291, 1, 'view_system_logs', 1, '2025-10-30 03:55:47', 1),
(292, 1, 'view_users', 1, '2025-10-30 03:55:47', 1),
(293, 1, 'view_violations', 1, '2025-10-30 03:55:47', 1),
(474, 12, 'manage_attendance', 1, '2025-12-02 18:43:51', 1),
(475, 12, 'manage_keys', 1, '2025-12-02 18:43:51', 1),
(476, 12, 'view_keys', 1, '2025-12-02 18:43:51', 1),
(477, 12, 'access_labs', 1, '2025-12-02 18:43:51', 1),
(478, 12, 'manage_labs', 1, '2025-12-02 18:43:51', 1),
(479, 12, 'report_lab_incidents', 1, '2025-12-02 18:43:51', 1),
(480, 12, 'view_lab_incidents', 1, '2025-12-02 18:43:51', 1),
(481, 12, 'manage_lost_found', 1, '2025-12-02 18:43:51', 1),
(482, 12, 'view_lost_found', 1, '2025-12-02 18:43:51', 1),
(483, 12, 'export_reports', 1, '2025-12-02 18:43:51', 1),
(484, 12, 'generate_reports', 1, '2025-12-02 18:43:51', 1),
(485, 12, 'view_reports', 1, '2025-12-02 18:43:51', 1),
(486, 12, 'manage_assignments', 1, '2025-12-02 18:43:51', 1),
(487, 12, 'manage_security_team', 1, '2025-12-02 18:43:51', 1),
(488, 12, 'view_assignments', 1, '2025-12-02 18:43:51', 1),
(489, 12, 'view_security_team', 1, '2025-12-02 18:43:51', 1),
(490, 12, 'delete_violations', 1, '2025-12-02 18:43:51', 1),
(491, 12, 'edit_violations', 1, '2025-12-02 18:43:51', 1),
(492, 12, 'report_violations', 1, '2025-12-02 18:43:51', 1),
(493, 12, 'review_violations', 1, '2025-12-02 18:43:51', 1),
(494, 12, 'view_violations', 1, '2025-12-02 18:43:51', 1),
(495, 13, 'manage_attendance', 1, '2025-12-06 04:56:50', 1),
(496, 13, 'manage_keys', 1, '2025-12-06 04:56:50', 1),
(497, 13, 'access_labs', 1, '2025-12-06 04:56:50', 1),
(498, 13, 'manage_labs', 1, '2025-12-06 04:56:50', 1),
(499, 13, 'report_lab_incidents', 1, '2025-12-06 04:56:50', 1),
(500, 13, 'view_lab_incidents', 1, '2025-12-06 04:56:50', 1),
(501, 13, 'manage_lost_found', 1, '2025-12-06 04:56:50', 1),
(502, 13, 'report_lost_found', 1, '2025-12-06 04:56:50', 1),
(503, 13, 'export_reports', 1, '2025-12-06 04:56:50', 1),
(504, 13, 'generate_reports', 1, '2025-12-06 04:56:50', 1),
(505, 13, 'view_reports', 1, '2025-12-06 04:56:50', 1),
(506, 13, 'manage_permissions', 1, '2025-12-06 04:56:50', 1),
(507, 13, 'manage_system', 1, '2025-12-06 04:56:50', 1),
(508, 13, 'deactivate_users', 1, '2025-12-06 04:56:50', 1),
(509, 13, 'manage_users', 1, '2025-12-06 04:56:50', 1),
(510, 13, 'view_users', 1, '2025-12-06 04:56:50', 1),
(511, 13, 'delete_violations', 1, '2025-12-06 04:56:50', 1),
(512, 13, 'edit_violations', 1, '2025-12-06 04:56:50', 1),
(513, 13, 'view_violations', 1, '2025-12-06 04:56:50', 1),
(514, 10, 'check_attendance', 1, '2025-12-11 00:28:14', 1),
(515, 10, 'manage_attendance', 1, '2025-12-11 00:28:14', 1),
(516, 10, 'view_attendance', 1, '2025-12-11 00:28:14', 1),
(517, 10, 'manage_keys', 1, '2025-12-11 00:28:14', 1),
(518, 10, 'report_lab_incidents', 1, '2025-12-11 00:28:14', 1),
(519, 10, 'view_lab_incidents', 1, '2025-12-11 00:28:14', 1),
(520, 10, 'claim_lost_found', 1, '2025-12-11 00:28:14', 1),
(521, 10, 'manage_lost_found', 1, '2025-12-11 00:28:14', 1),
(522, 10, 'report_lost_found', 1, '2025-12-11 00:28:14', 1),
(523, 10, 'view_lost_found', 1, '2025-12-11 00:28:14', 1),
(524, 10, 'edit_violations', 1, '2025-12-11 00:28:14', 1),
(525, 10, 'report_violations', 1, '2025-12-11 00:28:14', 1),
(526, 10, 'review_violations', 1, '2025-12-11 00:28:14', 1),
(566, 15, 'view_attendance', 1, '2025-12-11 19:55:57', 1),
(567, 15, 'borrow_keys', 1, '2025-12-11 19:55:57', 1),
(568, 15, 'manage_keys', 1, '2025-12-11 19:55:57', 1),
(569, 15, 'return_keys', 1, '2025-12-11 19:55:57', 1),
(570, 15, 'view_keys', 1, '2025-12-11 19:55:57', 1),
(571, 15, 'claim_lost_found', 1, '2025-12-11 19:55:57', 1),
(572, 15, 'manage_lost_found', 1, '2025-12-11 19:55:57', 1),
(573, 15, 'report_lost_found', 1, '2025-12-11 19:55:57', 1),
(574, 15, 'view_lost_found', 1, '2025-12-11 19:55:57', 1),
(575, 15, 'edit_violations', 1, '2025-12-11 19:55:57', 1),
(576, 15, 'report_violations', 1, '2025-12-11 19:55:57', 1),
(577, 15, 'review_violations', 1, '2025-12-11 19:55:57', 1),
(578, 15, 'view_violations', 1, '2025-12-11 19:55:57', 1),
(586, 16, 'check_attendance', 1, '2025-12-11 20:08:55', 1),
(587, 16, 'view_attendance', 1, '2025-12-11 20:08:55', 1),
(588, 16, 'borrow_keys', 1, '2025-12-11 20:08:55', 1),
(589, 16, 'view_keys', 1, '2025-12-11 20:08:55', 1),
(590, 16, 'claim_lost_found', 1, '2025-12-11 20:08:55', 1),
(591, 16, 'report_lost_found', 1, '2025-12-11 20:08:55', 1),
(592, 16, 'view_lost_found', 1, '2025-12-11 20:08:55', 1);

-- --------------------------------------------------------

--
-- Table structure for table `violations`
--

CREATE TABLE `violations` (
  `violation_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `violation_type_id` int(11) DEFAULT NULL,
  `violation_date` date DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `violations_security`
--

CREATE TABLE `violations_security` (
  `id_security` int(11) NOT NULL,
  `reported_by_security` int(11) NOT NULL,
  `student_id_security` varchar(20) NOT NULL,
  `student_name_security` varchar(100) NOT NULL,
  `violation_type_id_security` int(11) NOT NULL,
  `severity_security` enum('low','medium','high','critical') NOT NULL,
  `description_security` text NOT NULL,
  `location_security` varchar(100) NOT NULL,
  `points_security` int(11) NOT NULL,
  `status_security` enum('reported','under_review','verified','resolved','dismissed') DEFAULT 'reported',
  `evidence_photo_security` varchar(255) DEFAULT NULL,
  `reviewed_by_security` int(11) DEFAULT NULL,
  `reviewed_at_security` timestamp NULL DEFAULT NULL,
  `resolution_notes_security` text DEFAULT NULL,
  `created_at_security` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at_security` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `violations_security`
--

INSERT INTO `violations_security` (`id_security`, `reported_by_security`, `student_id_security`, `student_name_security`, `violation_type_id_security`, `severity_security`, `description_security`, `location_security`, `points_security`, `status_security`, `evidence_photo_security`, `reviewed_by_security`, `reviewed_at_security`, `resolution_notes_security`, `created_at_security`, `updated_at_security`) VALUES
(9, 10, '12-12345', 'Jezelle Shane Hechanova', 8, 'high', 'nag hihits', 'On 6th Floor', 3, 'reported', NULL, 12, '2025-12-11 17:59:55', 'Adik na', '2025-12-06 01:02:55', '2025-12-11 17:59:55'),
(11, 10, '12-12346', 'Rommel Baquiran', 2, 'low', 'asdasdad', 'Main gate', 1, 'reported', 'violations_1765541234_693c05727dfa6.jpg', NULL, NULL, 'Cute hahahah', '2025-12-12 04:07:14', '2025-12-12 04:07:14');

-- --------------------------------------------------------

--
-- Table structure for table `violation_types`
--

CREATE TABLE `violation_types` (
  `violation_type_id` int(11) NOT NULL,
  `violation_name` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `violation_types_security`
--

CREATE TABLE `violation_types_security` (
  `id_security` int(11) NOT NULL,
  `type_name_security` varchar(100) NOT NULL,
  `severity_security` enum('low','medium','high','critical') NOT NULL,
  `default_points_security` int(11) DEFAULT 1,
  `description_security` text DEFAULT NULL,
  `is_active_security` tinyint(1) DEFAULT 1,
  `created_by_security` int(11) DEFAULT NULL,
  `created_at_security` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `violation_types_security`
--

INSERT INTO `violation_types_security` (`id_security`, `type_name_security`, `severity_security`, `default_points_security`, `description_security`, `is_active_security`, `created_by_security`, `created_at_security`) VALUES
(1, 'No ID', 'medium', 2, 'Not wearing identification inside campus premises', 1, 1, '2025-10-28 11:30:05'),
(2, 'Improper Uniform', 'low', 1, 'Not following prescribed dress code/uniform policy', 1, 1, '2025-10-28 11:30:05'),
(4, 'Unauthorized Area', 'high', 3, 'Accessing restricted areas without permission', 1, 1, '2025-10-28 11:30:05'),
(5, 'Disruptive Behavior', 'medium', 2, 'Causing disturbance in academic areas', 1, 1, '2025-10-28 11:30:05'),
(6, 'Academic Dishonesty', 'critical', 5, 'Cheating, plagiarism, or other academic violations', 1, 1, '2025-10-28 11:30:05'),
(7, 'Vandalism', 'high', 4, 'Damaging school property', 1, 1, '2025-10-28 11:30:05'),
(8, 'Smoking in Campus', 'high', 3, 'Smoking in non-designated areas', 1, 1, '2025-10-28 11:30:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_term`
--
ALTER TABLE `academic_term`
  ADD PRIMARY KEY (`term_id`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `allowance_releases`
--
ALTER TABLE `allowance_releases`
  ADD PRIMARY KEY (`release_id`),
  ADD KEY `scholar_id` (`scholar_id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `scholar_id` (`scholar_id`);

--
-- Indexes for table `application_documents`
--
ALTER TABLE `application_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `application_status_history`
--
ALTER TABLE `application_status_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`);

--
-- Indexes for table `assessment_weights`
--
ALTER TABLE `assessment_weights`
  ADD PRIMARY KEY (`weight_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_student_date` (`student_id`,`date`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `fk_attendance_user` (`marked_by`);

--
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_attendance_employee` (`employee_id`);

--
-- Indexes for table `attendance_queue`
--
ALTER TABLE `attendance_queue`
  ADD PRIMARY KEY (`queue_id`);

--
-- Indexes for table `attendance_schedules`
--
ALTER TABLE `attendance_schedules`
  ADD PRIMARY KEY (`schedule_id`);

--
-- Indexes for table `attendance_settings`
--
ALTER TABLE `attendance_settings`
  ADD PRIMARY KEY (`setting_id`);

--
-- Indexes for table `attendance_sync_queue`
--
ALTER TABLE `attendance_sync_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sync_type` (`sync_type`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`audit_id`);

--
-- Indexes for table `backup_restore_settings`
--
ALTER TABLE `backup_restore_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `billing`
--
ALTER TABLE `billing`
  ADD PRIMARY KEY (`billing_id`),
  ADD KEY `fee_id` (`fee_id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`BookID`),
  ADD KEY `fk_books_subject` (`SubjectID`);

--
-- Indexes for table `cases`
--
ALTER TABLE `cases`
  ADD PRIMARY KEY (`case_id`);

--
-- Indexes for table `case_counselors`
--
ALTER TABLE `case_counselors`
  ADD PRIMARY KEY (`case_counselor_id`);

--
-- Indexes for table `case_documents`
--
ALTER TABLE `case_documents`
  ADD PRIMARY KEY (`document_id`);

--
-- Indexes for table `case_statuses`
--
ALTER TABLE `case_statuses`
  ADD PRIMARY KEY (`status_id`);

--
-- Indexes for table `case_types`
--
ALTER TABLE `case_types`
  ADD PRIMARY KEY (`case_type_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`class_id`),
  ADD KEY `term_id` (`term_id`);

--
-- Indexes for table `clearances`
--
ALTER TABLE `clearances`
  ADD PRIMARY KEY (`clearance_id`);

--
-- Indexes for table `clearances_doc`
--
ALTER TABLE `clearances_doc`
  ADD PRIMARY KEY (`clearance_id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `client_queue`
--
ALTER TABLE `client_queue`
  ADD PRIMARY KEY (`queue_id`),
  ADD KEY `service_type_id` (`service_type_id`);

--
-- Indexes for table `connection_debug`
--
ALTER TABLE `connection_debug`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `dept`
--
ALTER TABLE `dept`
  ADD PRIMARY KEY (`DeptID`),
  ADD UNIQUE KEY `uq_dept_name` (`DeptName`);

--
-- Indexes for table `document_requests`
--
ALTER TABLE `document_requests`
  ADD PRIMARY KEY (`Request_id`),
  ADD KEY `User_id` (`User_id`),
  ADD KEY `pickup_schedule_id` (`pickup_schedule_id`),
  ADD KEY `fk_document_requests_doctype` (`Doctype_id`),
  ADD KEY `fk_document_requests_payment` (`Payment_id`),
  ADD KEY `idx_status` (`Status`),
  ADD KEY `idx_urgency` (`Urgency`),
  ADD KEY `idx_request_date` (`Request_date`);

--
-- Indexes for table `document_types`
--
ALTER TABLE `document_types`
  ADD PRIMARY KEY (`Doctype_id`),
  ADD UNIQUE KEY `Name` (`Name`);

--
-- Indexes for table `eaaps_attendance_settings`
--
ALTER TABLE `eaaps_attendance_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eaaps_payroll_settings`
--
ALTER TABLE `eaaps_payroll_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eaaps_school_settings`
--
ALTER TABLE `eaaps_school_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eaaps_tax_deduction_settings`
--
ALTER TABLE `eaaps_tax_deduction_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `eaaps_time_date_settings`
--
ALTER TABLE `eaaps_time_date_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `position_id` (`position_id`),
  ADD KEY `fk_employees_user` (`user_id`);

--
-- Indexes for table `employee_schedules`
--
ALTER TABLE `employee_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_shift` (`employee_id`,`day_of_week`,`shift_name`),
  ADD KEY `idx_employee_day` (`employee_id`,`day_of_week`);

--
-- Indexes for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD PRIMARY KEY (`EnrollID`),
  ADD KEY `idx_enrollment_student` (`StudID`),
  ADD KEY `idx_enrollment_period` (`EPeriodID`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `enrollperiod`
--
ALTER TABLE `enrollperiod`
  ADD PRIMARY KEY (`EPeriodID`),
  ADD UNIQUE KEY `uq_period_year_sem` (`SchoolYear`,`Semester`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`equipment_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `event_logs`
--
ALTER TABLE `event_logs`
  ADD PRIMARY KEY (`event_id`);

--
-- Indexes for table `event_logs_doc`
--
ALTER TABLE `event_logs_doc`
  ADD PRIMARY KEY (`Log_id`),
  ADD KEY `fk_event_logs_user` (`User_id`);

--
-- Indexes for table `fees`
--
ALTER TABLE `fees`
  ADD PRIMARY KEY (`fee_id`),
  ADD UNIQUE KEY `uq_fee_code` (`fee_code`),
  ADD UNIQUE KEY `uq_unique_fee_per_term` (`fee_code`,`school_year`,`semester`,`course`,`year_level`);

--
-- Indexes for table `gpa`
--
ALTER TABLE `gpa`
  ADD PRIMARY KEY (`gpa_id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`grade_id`),
  ADD KEY `enrollment_id` (`enrollment_id`);

--
-- Indexes for table `gradesummary`
--
ALTER TABLE `gradesummary`
  ADD PRIMARY KEY (`GradeID`),
  ADD KEY `StudID` (`StudID`),
  ADD KEY `SubSchedID` (`SubSchedID`);

--
-- Indexes for table `grade_scale`
--
ALTER TABLE `grade_scale`
  ADD PRIMARY KEY (`scale_id`);

--
-- Indexes for table `grading_periods`
--
ALTER TABLE `grading_periods`
  ADD PRIMARY KEY (`period_id`),
  ADD KEY `term_id` (`term_id`);

--
-- Indexes for table `guards`
--
ALTER TABLE `guards`
  ADD PRIMARY KEY (`guard_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `guidance_settings`
--
ALTER TABLE `guidance_settings`
  ADD PRIMARY KEY (`setting_id`);

--
-- Indexes for table `guidance_users`
--
ALTER TABLE `guidance_users`
  ADD PRIMARY KEY (`UserID`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`holiday_id`);

--
-- Indexes for table `honors_classification`
--
ALTER TABLE `honors_classification`
  ADD PRIMARY KEY (`honor_id`);

--
-- Indexes for table `instructor`
--
ALTER TABLE `instructor`
  ADD PRIMARY KEY (`InsID`),
  ADD KEY `idx_instructor_dept` (`DeptID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `instructors`
--
ALTER TABLE `instructors`
  ADD PRIMARY KEY (`instructor_id`);

--
-- Indexes for table `job_positions`
--
ALTER TABLE `job_positions`
  ADD PRIMARY KEY (`position_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `keys_m`
--
ALTER TABLE `keys_m`
  ADD PRIMARY KEY (`KeyID`);

--
-- Indexes for table `key_logs`
--
ALTER TABLE `key_logs`
  ADD PRIMARY KEY (`LogID`),
  ADD KEY `fk_keylogs_users` (`Key_UserID`),
  ADD KEY `fk_keylogs_keys` (`KeyID`);

--
-- Indexes for table `key_management`
--
ALTER TABLE `key_management`
  ADD PRIMARY KEY (`key_id`),
  ADD KEY `key_type_id` (`key_type_id`);

--
-- Indexes for table `key_management_security`
--
ALTER TABLE `key_management_security`
  ADD PRIMARY KEY (`id_security`),
  ADD UNIQUE KEY `key_code` (`key_code_security`),
  ADD KEY `key_type_id` (`key_type_id_security`),
  ADD KEY `current_holder` (`current_holder_security`),
  ADD KEY `created_by` (`created_by_security`),
  ADD KEY `idx_key_management_status` (`status_security`);

--
-- Indexes for table `key_transaction_logs`
--
ALTER TABLE `key_transaction_logs`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `key_id` (`key_id`);

--
-- Indexes for table `key_transaction_logs_security`
--
ALTER TABLE `key_transaction_logs_security`
  ADD PRIMARY KEY (`id_security`),
  ADD KEY `key_id` (`key_id_security`),
  ADD KEY `user_id` (`user_id_security`),
  ADD KEY `admin_id` (`admin_id_security`);

--
-- Indexes for table `key_types`
--
ALTER TABLE `key_types`
  ADD PRIMARY KEY (`key_type_id`);

--
-- Indexes for table `key_types_security`
--
ALTER TABLE `key_types_security`
  ADD PRIMARY KEY (`id_security`);

--
-- Indexes for table `key_users`
--
ALTER TABLE `key_users`
  ADD PRIMARY KEY (`Key_UsersID`);

--
-- Indexes for table `laboratories`
--
ALTER TABLE `laboratories`
  ADD PRIMARY KEY (`lab_id`);

--
-- Indexes for table `laboratories_security`
--
ALTER TABLE `laboratories_security`
  ADD PRIMARY KEY (`id_security`),
  ADD UNIQUE KEY `lab_code` (`lab_code_security`);

--
-- Indexes for table `lab_access_logs`
--
ALTER TABLE `lab_access_logs`
  ADD PRIMARY KEY (`access_id`),
  ADD KEY `lab_id` (`lab_id`);

--
-- Indexes for table `lab_access_logs_security`
--
ALTER TABLE `lab_access_logs_security`
  ADD PRIMARY KEY (`id_security`),
  ADD KEY `user_id` (`user_id_security`),
  ADD KEY `lab_id` (`lab_id_security`);

--
-- Indexes for table `lab_equipment`
--
ALTER TABLE `lab_equipment`
  ADD PRIMARY KEY (`equipment_id`),
  ADD KEY `lab_id` (`lab_id`);

--
-- Indexes for table `lab_equipment_security`
--
ALTER TABLE `lab_equipment_security`
  ADD PRIMARY KEY (`id_security`),
  ADD UNIQUE KEY `serial_number` (`serial_number_security`),
  ADD KEY `lab_id` (`lab_id_security`);

--
-- Indexes for table `lab_incidents`
--
ALTER TABLE `lab_incidents`
  ADD PRIMARY KEY (`incident_id`),
  ADD KEY `lab_id` (`lab_id`);

--
-- Indexes for table `lab_incidents_security`
--
ALTER TABLE `lab_incidents_security`
  ADD PRIMARY KEY (`id_security`),
  ADD KEY `reported_by` (`reported_by_security`),
  ADD KEY `lab_id` (`lab_id_security`),
  ADD KEY `equipment_id` (`equipment_id_security`),
  ADD KEY `resolved_by` (`resolved_by_security`),
  ADD KEY `idx_lab_incidents_status` (`status_security`);

--
-- Indexes for table `learningmaterials`
--
ALTER TABLE `learningmaterials`
  ADD PRIMARY KEY (`MaterialID`),
  ADD KEY `SubSchedID` (`SubSchedID`),
  ADD KEY `UploadedBy` (`UploadedBy`),
  ADD KEY `FolderID` (`FolderID`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`leave_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `fk_leave_approver` (`approved_by`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `lost_found_categories`
--
ALTER TABLE `lost_found_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `lost_found_categories_security`
--
ALTER TABLE `lost_found_categories_security`
  ADD PRIMARY KEY (`id_security`);

--
-- Indexes for table `lost_found_items`
--
ALTER TABLE `lost_found_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `lost_found_items_security`
--
ALTER TABLE `lost_found_items_security`
  ADD PRIMARY KEY (`id_security`),
  ADD KEY `reported_by` (`reported_by_security`),
  ADD KEY `category_id` (`category_id_security`),
  ADD KEY `claimed_by` (`claimed_by_security`),
  ADD KEY `resolved_by` (`resolved_by_security`),
  ADD KEY `idx_lost_found_status` (`status_security`);

--
-- Indexes for table `lost_keys`
--
ALTER TABLE `lost_keys`
  ADD PRIMARY KEY (`lost_key_id`),
  ADD KEY `key_id` (`key_id`);

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`maintenance_id`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Indexes for table `materialaccesslog`
--
ALTER TABLE `materialaccesslog`
  ADD PRIMARY KEY (`LogID`),
  ADD KEY `MaterialID` (`MaterialID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `materialfolder`
--
ALTER TABLE `materialfolder`
  ADD PRIMARY KEY (`FolderID`),
  ADD KEY `SubSchedID` (`SubSchedID`),
  ADD KEY `ParentFolderID` (`ParentFolderID`),
  ADD KEY `CreatedBy` (`CreatedBy`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`MemberID`);

--
-- Indexes for table `misconducts`
--
ALTER TABLE `misconducts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`module_id`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`notice_id`);

--
-- Indexes for table `notice_reads`
--
ALTER TABLE `notice_reads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notice_id` (`notice_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`);

--
-- Indexes for table `notifications_for_id`
--
ALTER TABLE `notifications_for_id`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  ADD PRIMARY KEY (`overtime_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`reset_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`);

--
-- Indexes for table `payments_doc`
--
ALTER TABLE `payments_doc`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `fk_payments_verified_by` (`verified_by`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD KEY `fk_payroll_employee` (`employee_id`);

--
-- Indexes for table `pickup_schedules`
--
ALTER TABLE `pickup_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_id` (`request_id`);

--
-- Indexes for table `program`
--
ALTER TABLE `program`
  ADD PRIMARY KEY (`ProgramID`);

--
-- Indexes for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD PRIMARY KEY (`qr_id`),
  ADD KEY `fk_qrcodes_employee` (`employee_id`);

--
-- Indexes for table `quiz`
--
ALTER TABLE `quiz`
  ADD PRIMARY KEY (`QuizID`),
  ADD KEY `SubSchedID` (`SubSchedID`),
  ADD KEY `CreatedBy` (`CreatedBy`);

--
-- Indexes for table `quizanswer`
--
ALTER TABLE `quizanswer`
  ADD PRIMARY KEY (`AnswerID`),
  ADD KEY `AttemptID` (`AttemptID`),
  ADD KEY `QuestionID` (`QuestionID`),
  ADD KEY `ChoiceID` (`ChoiceID`);

--
-- Indexes for table `quizattempt`
--
ALTER TABLE `quizattempt`
  ADD PRIMARY KEY (`AttemptID`),
  ADD KEY `QuizID` (`QuizID`),
  ADD KEY `StudID` (`StudID`);

--
-- Indexes for table `quizchoice`
--
ALTER TABLE `quizchoice`
  ADD PRIMARY KEY (`ChoiceID`),
  ADD KEY `QuestionID` (`QuestionID`);

--
-- Indexes for table `quizquestion`
--
ALTER TABLE `quizquestion`
  ADD PRIMARY KEY (`QuestionID`),
  ADD KEY `QuizID` (`QuizID`);

--
-- Indexes for table `request_counters`
--
ALTER TABLE `request_counters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `date` (`date`);

--
-- Indexes for table `special_events`
--
ALTER TABLE `special_events`
  ADD PRIMARY KEY (`event_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`StudID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `student_doc`
--
ALTER TABLE `student_doc`
  ADD PRIMARY KEY (`StudID`),
  ADD UNIQUE KEY `student_number` (`SchoolID`),
  ADD UNIQUE KEY `EmailAddr` (`EmailAddr`);

--
-- Indexes for table `student_guidance`
--
ALTER TABLE `student_guidance`
  ADD PRIMARY KEY (`StudID`);

--
-- Indexes for table `student_passwords`
--
ALTER TABLE `student_passwords`
  ADD PRIMARY KEY (`password_id`),
  ADD UNIQUE KEY `stud_id` (`stud_id`),
  ADD KEY `idx_student_passwords_stud_id` (`stud_id`);

--
-- Indexes for table `subject`
--
ALTER TABLE `subject`
  ADD PRIMARY KEY (`SubjectID`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- Indexes for table `subsched`
--
ALTER TABLE `subsched`
  ADD PRIMARY KEY (`SubSchedID`);

--
-- Indexes for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher_subject` (`teacher_id`,`subject_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`);

--
-- Indexes for table `users_doc`
--
ALTER TABLE `users_doc`
  ADD PRIMARY KEY (`User_id`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `users_employee`
--
ALTER TABLE `users_employee`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users_for_id`
--
ALTER TABLE `users_for_id`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD KEY `RoleID` (`RoleID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_sync_queue`
--
ALTER TABLE `attendance_sync_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `billing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cases`
--
ALTER TABLE `cases`
  MODIFY `case_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `case_counselors`
--
ALTER TABLE `case_counselors`
  MODIFY `case_counselor_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `case_documents`
--
ALTER TABLE `case_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clearances_doc`
--
ALTER TABLE `clearances_doc`
  MODIFY `clearance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `document_requests`
--
ALTER TABLE `document_requests`
  MODIFY `Request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `document_types`
--
ALTER TABLE `document_types`
  MODIFY `Doctype_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employee_schedules`
--
ALTER TABLE `employee_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_logs_doc`
--
ALTER TABLE `event_logs_doc`
  MODIFY `Log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=218;

--
-- AUTO_INCREMENT for table `fees`
--
ALTER TABLE `fees`
  MODIFY `fee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `gradesummary`
--
ALTER TABLE `gradesummary`
  MODIFY `GradeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `guards`
--
ALTER TABLE `guards`
  MODIFY `guard_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `guidance_users`
--
ALTER TABLE `guidance_users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `instructor`
--
ALTER TABLE `instructor`
  MODIFY `InsID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `job_positions`
--
ALTER TABLE `job_positions`
  MODIFY `position_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `keys_m`
--
ALTER TABLE `keys_m`
  MODIFY `KeyID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `key_logs`
--
ALTER TABLE `key_logs`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `key_users`
--
ALTER TABLE `key_users`
  MODIFY `Key_UsersID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `learningmaterials`
--
ALTER TABLE `learningmaterials`
  MODIFY `MaterialID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `leave_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lost_keys`
--
ALTER TABLE `lost_keys`
  MODIFY `lost_key_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `materialaccesslog`
--
ALTER TABLE `materialaccesslog`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `materialfolder`
--
ALTER TABLE `materialfolder`
  MODIFY `FolderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `payments_doc`
--
ALTER TABLE `payments_doc`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `program`
--
ALTER TABLE `program`
  MODIFY `ProgramID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `qr_codes`
--
ALTER TABLE `qr_codes`
  MODIFY `qr_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz`
--
ALTER TABLE `quiz`
  MODIFY `QuizID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quizanswer`
--
ALTER TABLE `quizanswer`
  MODIFY `AnswerID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quizattempt`
--
ALTER TABLE `quizattempt`
  MODIFY `AttemptID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quizchoice`
--
ALTER TABLE `quizchoice`
  MODIFY `ChoiceID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quizquestion`
--
ALTER TABLE `quizquestion`
  MODIFY `QuestionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `StudID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2024119;

--
-- AUTO_INCREMENT for table `student_guidance`
--
ALTER TABLE `student_guidance`
  MODIFY `StudID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subject`
--
ALTER TABLE `subject`
  MODIFY `SubjectID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `subsched`
--
ALTER TABLE `subsched`
  MODIFY `SubSchedID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `users_for_id`
--
ALTER TABLE `users_for_id`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `fk_attendance_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employees_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_employees_position` FOREIGN KEY (`position_id`) REFERENCES `job_positions` (`position_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_employees_user` FOREIGN KEY (`user_id`) REFERENCES `users_employee` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `employee_schedules`
--
ALTER TABLE `employee_schedules`
  ADD CONSTRAINT `fk_employee_schedules_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `gradesummary`
--
ALTER TABLE `gradesummary`
  ADD CONSTRAINT `gradesummary_ibfk_1` FOREIGN KEY (`StudID`) REFERENCES `student` (`StudID`) ON DELETE CASCADE,
  ADD CONSTRAINT `gradesummary_ibfk_2` FOREIGN KEY (`SubSchedID`) REFERENCES `subsched` (`SubSchedID`) ON DELETE CASCADE;

--
-- Constraints for table `instructor`
--
ALTER TABLE `instructor`
  ADD CONSTRAINT `instructor_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE SET NULL;

--
-- Constraints for table `learningmaterials`
--
ALTER TABLE `learningmaterials`
  ADD CONSTRAINT `learningmaterials_ibfk_1` FOREIGN KEY (`SubSchedID`) REFERENCES `subsched` (`SubSchedID`) ON DELETE CASCADE,
  ADD CONSTRAINT `learningmaterials_ibfk_2` FOREIGN KEY (`UploadedBy`) REFERENCES `users` (`UserID`) ON DELETE SET NULL,
  ADD CONSTRAINT `learningmaterials_ibfk_3` FOREIGN KEY (`FolderID`) REFERENCES `materialfolder` (`FolderID`) ON DELETE SET NULL;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `fk_leave_approver` FOREIGN KEY (`approved_by`) REFERENCES `users_employee` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_leave_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `materialaccesslog`
--
ALTER TABLE `materialaccesslog`
  ADD CONSTRAINT `materialaccesslog_ibfk_1` FOREIGN KEY (`MaterialID`) REFERENCES `learningmaterials` (`MaterialID`) ON DELETE CASCADE,
  ADD CONSTRAINT `materialaccesslog_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `fk_payroll_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD CONSTRAINT `fk_qrcodes_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quiz`
--
ALTER TABLE `quiz`
  ADD CONSTRAINT `quiz_ibfk_1` FOREIGN KEY (`SubSchedID`) REFERENCES `subsched` (`SubSchedID`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_ibfk_2` FOREIGN KEY (`CreatedBy`) REFERENCES `users` (`UserID`) ON DELETE SET NULL;

--
-- Constraints for table `quizanswer`
--
ALTER TABLE `quizanswer`
  ADD CONSTRAINT `quizanswer_ibfk_1` FOREIGN KEY (`AttemptID`) REFERENCES `quizattempt` (`AttemptID`) ON DELETE CASCADE,
  ADD CONSTRAINT `quizanswer_ibfk_2` FOREIGN KEY (`QuestionID`) REFERENCES `quizquestion` (`QuestionID`) ON DELETE CASCADE,
  ADD CONSTRAINT `quizanswer_ibfk_3` FOREIGN KEY (`ChoiceID`) REFERENCES `quizchoice` (`ChoiceID`) ON DELETE SET NULL;

--
-- Constraints for table `quizattempt`
--
ALTER TABLE `quizattempt`
  ADD CONSTRAINT `quizattempt_ibfk_1` FOREIGN KEY (`QuizID`) REFERENCES `quiz` (`QuizID`) ON DELETE CASCADE,
  ADD CONSTRAINT `quizattempt_ibfk_2` FOREIGN KEY (`StudID`) REFERENCES `student` (`StudID`) ON DELETE CASCADE;

--
-- Constraints for table `quizchoice`
--
ALTER TABLE `quizchoice`
  ADD CONSTRAINT `quizchoice_ibfk_1` FOREIGN KEY (`QuestionID`) REFERENCES `quizquestion` (`QuestionID`) ON DELETE CASCADE;

--
-- Constraints for table `quizquestion`
--
ALTER TABLE `quizquestion`
  ADD CONSTRAINT `quizquestion_ibfk_1` FOREIGN KEY (`QuizID`) REFERENCES `quiz` (`QuizID`) ON DELETE CASCADE;

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
