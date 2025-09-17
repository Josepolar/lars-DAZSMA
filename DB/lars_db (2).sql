-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 17, 2025 at 04:20 PM
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
-- Database: `lars_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `AuthenticateStudentLogin` (IN `p_login_input` VARCHAR(100), IN `p_password` VARCHAR(255), IN `p_ip_address` VARCHAR(45), OUT `p_user_id` INT, OUT `p_success` BOOLEAN, OUT `p_message` VARCHAR(255), OUT `p_user_info` TEXT)   BEGIN
    DECLARE v_db_password VARCHAR(255);
    DECLARE v_first_name VARCHAR(50);
    DECLARE v_last_name VARCHAR(50);
    DECLARE v_username VARCHAR(50);
    DECLARE v_grade_level VARCHAR(2);
    DECLARE v_user_found INT DEFAULT 0;
    
    -- Find user by username or email
    SELECT user_id, password, first_name, last_name, username, grade_level
    INTO p_user_id, v_db_password, v_first_name, v_last_name, v_username, v_grade_level
    FROM users 
    WHERE (email = p_login_input OR username = p_login_input) 
    AND role_id = 4
    LIMIT 1;
    
    SET v_user_found = FOUND_ROWS();
    
    IF v_user_found = 0 THEN
        SET p_success = FALSE;
        SET p_message = 'Invalid username/email or account not found';
        SET p_user_id = 0;
        SET p_user_info = NULL;
        
        -- Log failed attempt
        INSERT INTO student_login_audit (username_attempted, ip_address, login_status, failure_reason)
        VALUES (p_login_input, p_ip_address, 'failed', 'User not found');
        
    ELSEIF p_password = v_db_password THEN
        SET p_success = TRUE;
        SET p_message = 'Login successful';
        SET p_user_info = CONCAT('{"user_id":', p_user_id, ',"username":"', v_username, '","first_name":"', v_first_name, '","last_name":"', v_last_name, '","full_name":"', CONCAT(v_first_name, ' ', v_last_name), '","grade_level":"', IFNULL(v_grade_level, ''), '"}');
        
        -- Log successful login
        INSERT INTO student_login_audit (user_id, username_attempted, ip_address, login_status)
        VALUES (p_user_id, p_login_input, p_ip_address, 'success');
        
        -- Also log in user_logs table for compatibility
        INSERT INTO user_logs (user_id, action, ip_address)
        VALUES (p_user_id, 'Login', p_ip_address);
        
    ELSE
        SET p_success = FALSE;
        SET p_message = 'Invalid password';
        SET p_user_info = NULL;
        
        -- Log failed attempt
        INSERT INTO student_login_audit (user_id, username_attempted, ip_address, login_status, failure_reason)
        VALUES (p_user_id, p_login_input, p_ip_address, 'failed', 'Invalid password');
        
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `CreateStudentAccount` (IN `p_first_name` VARCHAR(50), IN `p_last_name` VARCHAR(50), IN `p_username` VARCHAR(50), IN `p_password` VARCHAR(255), IN `p_grade_level` VARCHAR(2), IN `p_created_by_staff_id` INT, OUT `p_user_id` INT, OUT `p_success` BOOLEAN, OUT `p_message` VARCHAR(255))   BEGIN
    DECLARE v_email VARCHAR(100);
    DECLARE v_username_exists INT DEFAULT 0;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = 'Database error occurred while creating account';
        SET p_user_id = 0;
    END;

    START TRANSACTION;
    
    -- Generate email
    SET v_email = CONCAT(p_username, '@lars.edu.ph');
    
    -- Check if username already exists
    SELECT COUNT(*) INTO v_username_exists 
    FROM users 
    WHERE username = p_username;
    
    IF v_username_exists > 0 THEN
        SET p_success = FALSE;
        SET p_message = 'Username already exists';
        SET p_user_id = 0;
        ROLLBACK;
    ELSE
        -- Insert new student
        INSERT INTO users (first_name, last_name, username, email, password, role_id, grade_level)
        VALUES (p_first_name, p_last_name, p_username, v_email, p_password, 4, p_grade_level);
        
        SET p_user_id = LAST_INSERT_ID();
        
        -- Log the account creation
        INSERT INTO account_creation_log (created_user_id, created_by_staff_id, account_type)
        VALUES (p_user_id, p_created_by_staff_id, 'student');
        
        SET p_success = TRUE;
        SET p_message = 'Student account created successfully';
        
        COMMIT;
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `account_creation_log`
--

CREATE TABLE `account_creation_log` (
  `log_id` int(11) NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created_by_staff_id` int(11) NOT NULL,
  `account_type` enum('student','teacher') NOT NULL,
  `creation_method` enum('manual','bulk_upload') DEFAULT 'manual',
  `creation_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `additional_info` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `activity_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `activity_type` enum('quiz','assignment','recitation','exam') DEFAULT 'quiz',
  `total_points` int(11) NOT NULL DEFAULT 100,
  `time_limit` int(11) DEFAULT NULL COMMENT 'Time limit in minutes',
  `due_date` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`activity_id`, `title`, `description`, `teacher_id`, `subject_id`, `activity_type`, `total_points`, `time_limit`, `due_date`, `is_active`, `created_at`, `updated_at`) VALUES
(29, 'Filipino Recitation Quiz – Grade 7', '', 62, 1, 'quiz', 50, 15, '2025-09-17 17:30:00', 1, '2025-09-17 07:34:47', '2025-09-17 07:34:47'),
(30, 'sample 2', '', 62, 1, 'recitation', 30, 10, '2025-09-17 19:52:00', 1, '2025-09-17 09:52:59', '2025-09-17 09:52:59');

-- --------------------------------------------------------

--
-- Table structure for table `activity_analytics`
--

CREATE TABLE `activity_analytics` (
  `analytics_id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `total_students` int(11) DEFAULT 0,
  `completed_submissions` int(11) DEFAULT 0,
  `pending_submissions` int(11) DEFAULT 0,
  `average_score` decimal(5,2) DEFAULT NULL,
  `highest_score` decimal(5,2) DEFAULT NULL,
  `lowest_score` decimal(5,2) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_questions`
--

CREATE TABLE `activity_questions` (
  `question_id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer','essay') DEFAULT 'multiple_choice',
  `points` int(11) NOT NULL DEFAULT 10,
  `question_order` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_questions`
--

INSERT INTO `activity_questions` (`question_id`, `activity_id`, `question_text`, `question_type`, `points`, `question_order`, `created_at`) VALUES
(51, 29, 'Ano ang ibig sabihin ng salitang “pangarap”?', 'multiple_choice', 10, 1, '2025-09-17 07:34:47'),
(52, 29, 'Ano ang pangunahing layunin ng tula o panulaan?', 'multiple_choice', 10, 2, '2025-09-17 07:34:47'),
(53, 30, '1', 'multiple_choice', 10, 1, '2025-09-17 09:52:59'),
(54, 30, '2', 'multiple_choice', 10, 2, '2025-09-17 09:52:59'),
(55, 30, '3', 'multiple_choice', 10, 3, '2025-09-17 09:52:59');

-- --------------------------------------------------------

--
-- Table structure for table `question_choices`
--

CREATE TABLE `question_choices` (
  `choice_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `choice_text` text NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `choice_order` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `question_choices`
--

INSERT INTO `question_choices` (`choice_id`, `question_id`, `choice_text`, `is_correct`, `choice_order`) VALUES
(137, 51, 'Takot', 0, 1),
(138, 51, 'Layunin o minimithing bagay', 1, 2),
(139, 51, 'Pagkakamali', 0, 3),
(140, 51, 'Kaibigan', 0, 4),
(141, 52, 'Maglaro', 0, 1),
(142, 52, 'Magpahayag ng damdamin at kaisipan', 1, 2),
(143, 52, 'Magturo ng matematika', 0, 3),
(144, 52, 'Magbenta ng produkto', 0, 4),
(145, 53, '1', 1, 1),
(146, 53, '2', 0, 2),
(147, 53, '3', 0, 3),
(148, 54, '1', 0, 1),
(149, 54, '2', 1, 2),
(150, 54, '3', 0, 3),
(151, 55, '1', 0, 1),
(152, 55, '2', 0, 2),
(153, 55, '3', 1, 3);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'admin'),
(2, 'staff'),
(4, 'student'),
(3, 'teacher');

-- --------------------------------------------------------

--
-- Table structure for table `student_answers`
--

CREATE TABLE `student_answers` (
  `answer_id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `choice_id` int(11) DEFAULT NULL COMMENT 'For multiple choice questions',
  `answer_text` text DEFAULT NULL COMMENT 'For text-based questions',
  `points_earned` decimal(5,2) DEFAULT 0.00,
  `is_correct` tinyint(1) DEFAULT NULL,
  `answered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_answers`
--

INSERT INTO `student_answers` (`answer_id`, `submission_id`, `question_id`, `choice_id`, `answer_text`, `points_earned`, `is_correct`, `answered_at`) VALUES
(26, 26, 51, 138, NULL, 10.00, 1, '2025-09-17 07:36:21'),
(27, 26, 52, 142, NULL, 10.00, 1, '2025-09-17 07:36:21'),
(28, 27, 51, 138, NULL, 10.00, 1, '2025-09-17 07:37:09'),
(29, 27, 52, 142, NULL, 10.00, 1, '2025-09-17 07:37:09'),
(30, 28, 51, 138, NULL, 10.00, 1, '2025-09-17 07:37:57'),
(31, 28, 52, 141, NULL, 0.00, 0, '2025-09-17 07:37:57'),
(32, 29, 51, 140, NULL, 0.00, 0, '2025-09-17 07:39:08'),
(33, 29, 52, 143, NULL, 0.00, 0, '2025-09-17 07:39:08'),
(34, 30, 51, 138, NULL, 10.00, 1, '2025-09-17 07:39:40'),
(35, 30, 52, 142, NULL, 10.00, 1, '2025-09-17 07:39:40'),
(36, 31, 51, 138, NULL, 10.00, 1, '2025-09-17 07:41:54'),
(37, 31, 52, 142, NULL, 10.00, 1, '2025-09-17 07:41:54'),
(38, 32, 51, 138, NULL, 10.00, 1, '2025-09-17 07:42:24'),
(39, 32, 52, 142, NULL, 10.00, 1, '2025-09-17 07:42:24'),
(40, 33, 51, 138, NULL, 10.00, 1, '2025-09-17 07:43:01'),
(41, 33, 52, 142, NULL, 10.00, 1, '2025-09-17 07:43:01'),
(42, 34, 51, 138, NULL, 10.00, 1, '2025-09-17 07:43:26'),
(43, 34, 52, 142, NULL, 10.00, 1, '2025-09-17 07:43:26'),
(44, 35, 52, 142, NULL, 10.00, 1, '2025-09-17 07:44:11'),
(45, 36, 51, 138, NULL, 10.00, 1, '2025-09-17 07:44:33'),
(46, 36, 52, 142, NULL, 10.00, 1, '2025-09-17 07:44:33'),
(47, 37, 51, 138, NULL, 10.00, 1, '2025-09-17 07:44:57'),
(48, 38, 53, 145, NULL, 10.00, 1, '2025-09-17 09:53:47'),
(49, 38, 54, 149, NULL, 10.00, 1, '2025-09-17 09:53:47'),
(50, 38, 55, 152, NULL, 0.00, 0, '2025-09-17 09:53:47'),
(51, 39, 53, 145, NULL, 10.00, 1, '2025-09-17 10:13:47'),
(52, 39, 54, 149, NULL, 10.00, 1, '2025-09-17 10:13:47'),
(53, 39, 55, 153, NULL, 10.00, 1, '2025-09-17 10:13:47');

-- --------------------------------------------------------

--
-- Table structure for table `student_login_audit`
--

CREATE TABLE `student_login_audit` (
  `audit_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username_attempted` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `login_status` enum('success','failed','blocked') NOT NULL,
  `failure_reason` varchar(255) DEFAULT NULL,
  `attempt_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_submissions`
--

CREATE TABLE `student_submissions` (
  `submission_id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `submission_status` enum('not_started','in_progress','submitted','graded') DEFAULT 'not_started',
  `total_score` decimal(5,2) DEFAULT NULL,
  `max_score` decimal(5,2) NOT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `time_spent` int(11) DEFAULT NULL COMMENT 'Time spent in minutes',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_submissions`
--

INSERT INTO `student_submissions` (`submission_id`, `activity_id`, `student_id`, `submission_status`, `total_score`, `max_score`, `percentage`, `time_spent`, `submitted_at`, `graded_at`, `created_at`, `updated_at`) VALUES
(26, 29, 23, 'submitted', 20.00, 50.00, 40.00, NULL, '2025-09-17 07:36:21', NULL, '2025-09-17 07:36:14', '2025-09-17 07:36:21'),
(27, 29, 24, 'submitted', 20.00, 50.00, 40.00, NULL, '2025-09-17 07:37:09', NULL, '2025-09-17 07:37:04', '2025-09-17 07:37:09'),
(28, 29, 16, 'submitted', 10.00, 50.00, 20.00, NULL, '2025-09-17 07:37:57', NULL, '2025-09-17 07:37:52', '2025-09-17 07:37:57'),
(29, 29, 15, 'submitted', 0.00, 50.00, 0.00, NULL, '2025-09-17 07:39:08', NULL, '2025-09-17 07:39:03', '2025-09-17 07:39:08'),
(30, 29, 60, 'submitted', 20.00, 50.00, 40.00, NULL, '2025-09-17 07:39:40', NULL, '2025-09-17 07:39:36', '2025-09-17 07:39:40'),
(31, 29, 14, 'submitted', 20.00, 50.00, 40.00, NULL, '2025-09-17 07:41:54', NULL, '2025-09-17 07:41:49', '2025-09-17 07:41:54'),
(32, 29, 21, 'submitted', 20.00, 50.00, 40.00, NULL, '2025-09-17 07:42:24', NULL, '2025-09-17 07:42:20', '2025-09-17 07:42:24'),
(33, 29, 19, 'submitted', 20.00, 50.00, 40.00, NULL, '2025-09-17 07:43:01', NULL, '2025-09-17 07:42:57', '2025-09-17 07:43:01'),
(34, 29, 22, 'submitted', 20.00, 50.00, 40.00, NULL, '2025-09-17 07:43:26', NULL, '2025-09-17 07:43:22', '2025-09-17 07:43:26'),
(35, 29, 17, 'submitted', 10.00, 50.00, 20.00, NULL, '2025-09-17 07:44:11', NULL, '2025-09-17 07:44:07', '2025-09-17 07:44:11'),
(36, 29, 18, 'submitted', 20.00, 50.00, 40.00, NULL, '2025-09-17 07:44:33', NULL, '2025-09-17 07:44:26', '2025-09-17 07:44:33'),
(37, 29, 20, 'submitted', 10.00, 50.00, 20.00, NULL, '2025-09-17 07:44:57', NULL, '2025-09-17 07:44:48', '2025-09-17 07:44:57'),
(38, 30, 24, 'submitted', 20.00, 30.00, 66.67, NULL, '2025-09-17 09:53:47', NULL, '2025-09-17 09:53:41', '2025-09-17 09:53:47'),
(39, 30, 16, 'submitted', 30.00, 30.00, 100.00, NULL, '2025-09-17 10:13:47', NULL, '2025-09-17 10:13:38', '2025-09-17 10:13:47');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `grade_level` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_name`, `grade_level`, `created_at`, `updated_at`) VALUES
(1, 'FILIPINO 7', '7', '2025-09-14 14:56:09', '2025-09-14 14:56:09'),
(2, 'ENGLISH 8', '8', '2025-09-14 14:56:53', '2025-09-14 14:56:53'),
(3, 'SCIENCE 9', '9', '2025-09-14 14:57:05', '2025-09-14 14:57:05'),
(4, 'MATH 10', '10', '2025-09-14 14:57:17', '2025-09-14 14:57:17');

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
(8, 62, 1),
(9, 63, 2),
(7, 64, 4),
(10, 65, 3);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `grade_level` varchar(2) DEFAULT NULL,
  `section` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `role_id`, `first_name`, `last_name`, `created_at`, `updated_at`, `grade_level`, `section`) VALUES
(1, 'admin', 'admin123', 'admin@lars.edu.ph', 1, 'System', 'Administrator', '2025-09-14 12:22:01', '2025-09-14 12:22:01', NULL, NULL),
(3, 'adminuser', 'securepass123', 'adminuser@lars.edu.ph', 1, 'New', 'Admin', '2025-09-14 12:25:41', '2025-09-14 12:30:35', NULL, NULL),
(4, 'admin2', 'securepass123', 'admin2@lars.edu.ph', 1, 'New', 'Admin', '2025-09-14 12:30:35', '2025-09-14 12:30:35', NULL, NULL),
(5, 'samanthaagagas8425', 'sammy123', 'sammylars@gmail.com', 2, 'samantha', 'agagas', '2025-09-14 12:32:04', '2025-09-14 12:33:48', NULL, NULL),
(6, 'haydendayap7086', 'hayden123', 'haydenlars@gmail.com', 2, 'hayden', 'dayap', '2025-09-14 12:32:35', '2025-09-14 12:33:55', NULL, NULL),
(7, 'josefernandez3180', 'jose123', 'joselars@gmail.com', 2, 'jose', 'fernandez', '2025-09-14 12:33:19', '2025-09-14 12:33:19', NULL, NULL),
(14, 'johndoe7', 'password123', 'johndoe7@lars.edu.ph', 4, 'John', 'Doe', '2025-09-14 12:39:41', '2025-09-16 18:01:46', '7', 'A'),
(15, 'ethan.c7', 'Eth@n07!', 'ethan.c7@lars.edu.ph', 4, 'Ethan', 'Cruz', '2025-09-14 12:39:41', '2025-09-16 18:01:46', '7', 'A'),
(16, 'jasmineb7', 'Jb#2025!', 'jasmineb7@lars.edu.ph', 4, 'Jasmine', 'Bautista', '2025-09-14 12:39:41', '2025-09-16 18:01:46', '7', 'A'),
(17, 'nathanr07', 'Nr@m07#', 'nathanr07@lars.edu.ph', 4, 'Nathan', 'Ramos', '2025-09-14 12:39:41', '2025-09-16 18:01:46', '7', 'A'),
(18, 'chloe.s07', 'Ch!oE7*', 'chloe.s07@lars.edu.ph', 4, 'Chloe', 'Santiago', '2025-09-14 12:39:41', '2025-09-16 18:01:46', '7', 'A'),
(19, 'adrianm07', 'Am@2025#', 'adrianm07@lars.edu.ph', 4, 'Adrian', 'Morales', '2025-09-14 12:39:41', '2025-09-16 18:01:46', '7', 'A'),
(20, 'bianca.t7', 'Bt*Grd7!', 'bianca.t7@lars.edu.ph', 4, 'Bianca', 'Torres', '2025-09-14 12:39:41', '2025-09-16 18:01:46', '7', 'A'),
(21, 'elijahd7', 'El!07Pwd', 'elijahd7@lars.edu.ph', 4, 'Elijah', 'Dominguez', '2025-09-14 12:39:41', '2025-09-16 18:01:46', '7', 'A'),
(22, 'camillep7', 'Cp@ssw07!', 'camillep7@lars.edu.ph', 4, 'Camille', 'Perez', '2025-09-14 12:39:41', '2025-09-16 18:01:46', '7', 'A'),
(23, 'marcusf07', 'Mf_2025*', 'marcusf07@lars.edu.ph', 4, 'Marcus', 'Fernandez', '2025-09-14 12:39:41', '2025-09-16 18:01:46', '7', 'A'),
(24, 'trisha.a7', 'Ta#7Grd!', 'trisha.a7@lars.edu.ph', 4, 'Trisha', 'Alvarez', '2025-09-14 12:39:41', '2025-09-16 18:01:46', '7', 'A'),
(26, 'johndoe8', 'password123', 'johndoe8@lars.edu.ph', 4, 'John', 'Doe', '2025-09-14 12:41:48', '2025-09-14 16:10:26', '8', NULL),
(27, 'lucasm8', 'Lm@08#Pwd', 'lucasm8@lars.edu.ph', 4, 'Lucas', 'Mendoza', '2025-09-14 12:41:48', '2025-09-14 16:10:26', '8', NULL),
(28, 'alyssa.r8', 'Ar#2025!', 'alyssa.r8@lars.edu.ph', 4, 'Alyssa', 'Reyes', '2025-09-14 12:41:48', '2025-09-14 16:10:26', '8', NULL),
(29, 'danielg08', 'Dg!Grd8*', 'danielg08@lars.edu.ph', 4, 'Daniel', 'Gutierrez', '2025-09-14 12:41:48', '2025-09-14 16:10:26', '8', NULL),
(30, 'sophian8', 'Sn@08Pass', 'sophian8@lars.edu.ph', 4, 'Sophia', 'Navarro', '2025-09-14 12:41:48', '2025-09-14 16:10:26', '8', NULL),
(31, 'mattf08', 'Mf_2025#', 'mattf08@lars.edu.ph', 4, 'Matthew', 'Flores', '2025-09-14 12:41:48', '2025-09-14 16:10:26', '8', NULL),
(32, 'andreac08', 'Ac#8Grd!', 'andreac08@lars.edu.ph', 4, 'Andrea', 'Castillo', '2025-09-14 12:41:48', '2025-09-14 16:10:26', '8', NULL),
(33, 'joshua.s8', 'Js@08Pwd!', 'joshua.s8@lars.edu.ph', 4, 'Joshua', 'Santos', '2025-09-14 12:41:48', '2025-09-14 16:10:26', '8', NULL),
(34, 'katrina.v8', 'Kv!Gr8*', 'katrina.v8@lars.edu.ph', 4, 'Katrina', 'Villanueva', '2025-09-14 12:41:48', '2025-09-14 16:10:26', '8', NULL),
(35, 'chrisr08', 'Cr#2025!', 'chrisr08@lars.edu.ph', 4, 'Christian', 'Ramos', '2025-09-14 12:41:48', '2025-09-14 16:10:26', '8', NULL),
(36, 'denisec8', 'Dc@08#Pwd', 'denisec8@lars.edu.ph', 4, 'Denise', 'Cruz', '2025-09-14 12:41:48', '2025-09-14 16:10:26', '8', NULL),
(37, 'johndoe9', 'password123', 'johndoe9@lars.edu.ph', 4, 'John', 'Doe', '2025-09-14 12:42:08', '2025-09-14 16:10:26', '9', NULL),
(38, 'miguel.s9', 'Ms@nt09!', 'miguel.s9@lars.edu.ph', 4, 'Miguel', 'Santos', '2025-09-14 12:42:08', '2025-09-14 16:10:26', '9', NULL),
(39, 'aira.dc9', 'Aira#2025', 'aira.dc9@lars.edu.ph', 4, 'Aira', 'Dela Cruz', '2025-09-14 12:42:08', '2025-09-14 16:10:26', '9', NULL),
(40, 'jayv_09', 'Jay*vn09', 'jayv_09@lars.edu.ph', 4, 'Jayson', 'Villanueva', '2025-09-14 12:42:08', '2025-09-14 16:10:26', '9', NULL),
(41, 'krystalr09', 'Kry$tal9#', 'krystalr09@lars.edu.ph', 4, 'Krystal', 'Ramirez', '2025-09-14 12:42:08', '2025-09-14 16:10:26', '9', NULL),
(42, 'domm_09', 'Dom!2024', 'domm_09@lars.edu.ph', 4, 'Dominic', 'Mendoza', '2025-09-14 12:42:08', '2025-09-14 16:10:26', '9', NULL),
(43, 'liannef_9', 'LfL0res!', 'liannef_9@lars.edu.ph', 4, 'Lianne', 'Flores', '2025-09-14 12:42:08', '2025-09-14 16:10:26', '9', NULL),
(44, 'carlorey9', 'C@rl0Rey9', 'carlorey9@lars.edu.ph', 4, 'Carlo', 'Reyes', '2025-09-14 12:42:08', '2025-09-14 16:10:26', '9', NULL),
(45, 'hannahc09', 'Hc#9Pass!', 'hannahc09@lars.edu.ph', 4, 'Hannah', 'Castillo', '2025-09-14 12:42:08', '2025-09-14 16:10:26', '9', NULL),
(46, 'jerome_9n', 'JN_2025*', 'jerome_9n@lars.edu.ph', 4, 'Jerome', 'Navarro', '2025-09-14 12:42:08', '2025-09-14 16:10:26', '9', NULL),
(47, 'sofia.g9', 'Sof!a#9', 'sofia.g9@lars.edu.ph', 4, 'Sofia', 'Gutierrez', '2025-09-14 12:42:08', '2025-09-14 16:10:26', '9', NULL),
(48, 'johndoe10', 'password123', 'johndoe10@lars.edu.ph', 4, 'John', 'Doe', '2025-09-14 12:42:24', '2025-09-14 16:10:26', '10', NULL),
(49, 'nathanr10', 'Nr@10Pwd!', 'nathanr10@lars.edu.ph', 4, 'Nathaniel', 'Ramos', '2025-09-14 12:42:24', '2025-09-14 16:10:26', '10', NULL),
(50, 'isabelles10', 'Is#2025*', 'isabelles10@lars.edu.ph', 4, 'Isabelle', 'Santos', '2025-09-14 12:42:24', '2025-09-14 16:10:26', '10', NULL),
(51, 'gabeb10', 'Gb!Grd10#', 'gabeb10@lars.edu.ph', 4, 'Gabriel', 'Bautista', '2025-09-14 12:42:24', '2025-09-14 16:10:26', '10', NULL),
(52, 'clarism10', 'Cm@10Pass', 'clarism10@lars.edu.ph', 4, 'Clarisse', 'Mendoza', '2025-09-14 12:42:24', '2025-09-14 16:10:26', '10', NULL),
(53, 'timv10', 'Tv#2025!', 'timv10@lars.edu.ph', 4, 'Timothy', 'Villanueva', '2025-09-14 12:42:24', '2025-09-14 16:10:26', '10', NULL),
(54, 'francisc10', 'Fc@Pwd10*', 'francisc10@lars.edu.ph', 4, 'Francesca', 'Castillo', '2025-09-14 12:42:24', '2025-09-14 16:10:26', '10', NULL),
(55, 'adriang10', 'Ag!10Grd#', 'adriang10@lars.edu.ph', 4, 'Adrian', 'Gutierrez', '2025-09-14 12:42:24', '2025-09-14 16:10:26', '10', NULL),
(56, 'julianac10', 'Jc@10Pwd!', 'julianac10@lars.edu.ph', 4, 'Juliana', 'Cruz', '2025-09-14 12:42:24', '2025-09-14 16:10:26', '10', NULL),
(57, 'domt10', 'Dt#Pass10', 'domt10@lars.edu.ph', 4, 'Dominic', 'Torres', '2025-09-14 12:42:24', '2025-09-14 16:10:26', '10', NULL),
(58, 'katr10', 'Kr!2025*', 'katr10@lars.edu.ph', 4, 'Katrina', 'Reyes', '2025-09-14 12:42:24', '2025-09-14 16:10:26', '10', NULL),
(59, 'bernard', 'bernard123', 'bernard@lars.edu.ph', 4, 'Jose Rizal', 'Bernard', '2025-09-14 16:12:30', '2025-09-14 16:12:30', '8', NULL),
(60, 'dayap', '123', 'dayap@lars.edu.ph', 4, 'Hayden', 'Dayap', '2025-09-14 17:18:47', '2025-09-16 18:01:46', '7', 'A'),
(62, 'filipinotaeacher9891', '123', 'Filipino@lars.edu.ph', 3, 'Filipino', 'Taeacher', '2025-09-17 07:23:44', '2025-09-17 07:23:44', NULL, NULL),
(63, 'englishteacher6749', '123', 'englishteacher6749@lars.edu.ph', 3, 'english', 'teacher', '2025-09-17 07:24:21', '2025-09-17 07:26:11', NULL, NULL),
(64, 'mathteacher7015', '123', 'math@lars.edu.ph', 3, 'math', 'teacher', '2025-09-17 07:24:44', '2025-09-17 07:24:44', NULL, NULL),
(65, 'scienceteacher1799', '123', 'science@lars.edu.ph', 3, 'Science', 'teacher', '2025-09-17 07:25:09', '2025-09-17 07:25:09', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_logs`
--

CREATE TABLE `user_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `affected_user_id` int(11) DEFAULT NULL,
  `action_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_logs`
--

INSERT INTO `user_logs` (`log_id`, `user_id`, `action`, `affected_user_id`, `action_timestamp`, `ip_address`) VALUES
(1, 1, 'Login', NULL, '2025-09-14 12:22:01', '127.0.0.1'),
(2, 1, 'View Dashboard', NULL, '2025-09-14 12:22:01', '127.0.0.1'),
(5, 4, 'Login', NULL, '2025-09-14 12:31:14', '::1'),
(6, 4, 'Added Staff', 5, '2025-09-14 12:32:04', NULL),
(7, 4, 'Added Staff', 6, '2025-09-14 12:32:35', NULL),
(8, 4, 'Edited Staff', 5, '2025-09-14 12:32:45', NULL),
(9, 4, 'Edited Staff', 6, '2025-09-14 12:32:57', NULL),
(10, 4, 'Added Staff', 7, '2025-09-14 12:33:19', NULL),
(11, 4, 'Edited Staff', 5, '2025-09-14 12:33:28', NULL),
(12, 4, 'Edited Staff', 6, '2025-09-14 12:33:36', NULL),
(13, 4, 'Edited Staff', 5, '2025-09-14 12:33:48', NULL),
(14, 4, 'Edited Staff', 6, '2025-09-14 12:33:55', NULL),
(19, 5, 'Login', NULL, '2025-09-14 12:38:59', '::1'),
(22, 4, 'Login', NULL, '2025-09-14 12:43:07', '::1'),
(23, 5, 'Login', NULL, '2025-09-14 14:46:00', '::1'),
(25, 4, 'Login', NULL, '2025-09-14 14:47:38', '::1'),
(26, 5, 'Login', NULL, '2025-09-14 14:48:48', '::1'),
(29, 5, 'Added Subject', 1, '2025-09-14 14:56:09', NULL),
(31, 5, 'Added Subject', NULL, '2025-09-14 14:56:53', NULL),
(32, 5, 'Added Subject', 3, '2025-09-14 14:57:05', NULL),
(33, 5, 'Added Subject', 4, '2025-09-14 14:57:17', NULL),
(38, 5, 'Logout', NULL, '2025-09-14 15:14:23', '::1'),
(39, 5, 'Login', NULL, '2025-09-14 15:17:37', '::1'),
(41, 4, 'Login', NULL, '2025-09-14 15:46:20', '::1'),
(42, 5, 'Login', NULL, '2025-09-14 15:47:56', '::1'),
(43, 5, 'Added Student', 59, '2025-09-14 16:12:30', NULL),
(44, 59, 'Login', NULL, '2025-09-14 16:13:05', '::1'),
(46, 59, 'Login', NULL, '2025-09-14 16:37:17', '::1'),
(55, 59, 'Logout', NULL, '2025-09-14 17:15:00', '::1'),
(56, 24, 'Login', NULL, '2025-09-14 17:15:20', '::1'),
(57, 4, 'Login', NULL, '2025-09-14 17:18:14', '::1'),
(58, 5, 'Login', NULL, '2025-09-14 17:18:28', '::1'),
(59, 5, 'Added Student', 60, '2025-09-14 17:18:47', NULL),
(60, 60, 'Login', NULL, '2025-09-14 17:19:04', '::1'),
(76, 4, 'Login', NULL, '2025-09-16 14:42:48', '::1'),
(77, 4, 'Logout', NULL, '2025-09-16 14:44:56', '::1'),
(78, 4, 'Login', NULL, '2025-09-16 14:51:11', '::1'),
(79, 7, 'Login', NULL, '2025-09-16 14:57:53', '::1'),
(83, 7, 'Deleted User', NULL, '2025-09-16 14:59:51', NULL),
(84, 4, 'Login', NULL, '2025-09-16 15:00:57', '::1'),
(85, 7, 'Login', NULL, '2025-09-16 15:06:12', '::1'),
(87, 4, 'Logout', NULL, '2025-09-16 15:06:56', '::1'),
(90, 24, 'Login', NULL, '2025-09-16 15:18:10', '::1'),
(97, 24, 'Login', NULL, '2025-09-16 16:59:59', '::1'),
(103, 51, 'Login', NULL, '2025-09-16 17:02:39', '::1'),
(104, 51, 'Logout', NULL, '2025-09-16 17:13:37', '::1'),
(105, 24, 'Login', NULL, '2025-09-16 17:14:18', '::1'),
(108, 24, 'Login', NULL, '2025-09-16 17:15:37', '::1'),
(111, 24, 'Login', NULL, '2025-09-16 17:19:15', '::1'),
(114, 24, 'Logout', NULL, '2025-09-16 17:39:09', '::1'),
(115, 16, 'Login', NULL, '2025-09-16 17:39:27', '::1'),
(116, 16, 'Logout', NULL, '2025-09-16 18:07:49', '::1'),
(117, 15, 'Login', NULL, '2025-09-16 18:08:13', '::1'),
(118, 15, 'Login', NULL, '2025-09-16 18:14:04', '::1'),
(119, 15, 'Login', NULL, '2025-09-16 18:14:13', '::1'),
(120, 15, 'Logout', NULL, '2025-09-16 18:14:36', '::1'),
(121, 15, 'Login', NULL, '2025-09-16 18:14:47', '::1'),
(122, 15, 'Login', NULL, '2025-09-16 18:14:59', '::1'),
(123, 15, 'Logout', NULL, '2025-09-16 18:21:25', '::1'),
(124, 24, 'Login', NULL, '2025-09-16 18:21:54', '::1'),
(125, 24, 'Login', NULL, '2025-09-16 18:48:25', '::1'),
(128, 7, 'Login', NULL, '2025-09-16 19:17:26', '::1'),
(129, 51, 'Login', NULL, '2025-09-16 19:20:14', '::1'),
(130, 51, 'Logout', NULL, '2025-09-16 19:36:36', '::1'),
(131, 15, 'Login', NULL, '2025-09-16 19:36:53', '::1'),
(133, 7, 'Login', NULL, '2025-09-17 03:21:15', '::1'),
(134, 24, 'Login', NULL, '2025-09-17 03:22:53', '::1'),
(136, 16, 'Login', NULL, '2025-09-17 03:28:24', '::1'),
(141, 24, 'Login', NULL, '2025-09-17 03:42:34', '::1'),
(146, 24, 'Login', NULL, '2025-09-17 03:59:19', '::1'),
(148, 24, 'Login', NULL, '2025-09-17 04:07:16', '::1'),
(151, 24, 'Login', NULL, '2025-09-17 04:08:43', '::1'),
(153, 24, 'Login', NULL, '2025-09-17 04:13:40', '::1'),
(159, 24, 'Login', NULL, '2025-09-17 05:32:23', '::1'),
(164, 24, 'Login', NULL, '2025-09-17 05:34:35', '::1'),
(168, 24, 'Login', NULL, '2025-09-17 05:47:29', '::1'),
(170, 4, 'Login', NULL, '2025-09-17 06:04:07', '::1'),
(172, 24, 'Login', NULL, '2025-09-17 06:05:02', '::1'),
(174, 24, 'Login', NULL, '2025-09-17 06:23:25', '::1'),
(175, 24, 'Logout', NULL, '2025-09-17 06:38:33', '::1'),
(176, 16, 'Login', NULL, '2025-09-17 06:38:47', '::1'),
(177, 16, 'Logout', NULL, '2025-09-17 06:39:20', '::1'),
(178, 15, 'Login', NULL, '2025-09-17 06:39:32', '::1'),
(179, 15, 'Logout', NULL, '2025-09-17 06:50:20', '::1'),
(180, 24, 'Login', NULL, '2025-09-17 06:50:33', '::1'),
(181, 24, 'Logout', NULL, '2025-09-17 06:50:40', '::1'),
(182, 16, 'Login', NULL, '2025-09-17 06:50:50', '::1'),
(183, 4, 'Login', NULL, '2025-09-17 07:07:22', '::1'),
(184, 24, 'Login', NULL, '2025-09-17 07:09:49', '::1'),
(185, 24, 'Logout', NULL, '2025-09-17 07:14:03', '::1'),
(186, 59, 'Login', NULL, '2025-09-17 07:14:08', '::1'),
(187, 4, 'Login', NULL, '2025-09-17 07:15:17', '::1'),
(190, 4, 'Added Teacher', 62, '2025-09-17 07:23:44', NULL),
(191, 4, 'Added Teacher', 63, '2025-09-17 07:24:21', NULL),
(192, 4, 'Added Teacher', 64, '2025-09-17 07:24:44', NULL),
(193, 4, 'Added Teacher', 65, '2025-09-17 07:25:10', NULL),
(194, 7, 'Login', NULL, '2025-09-17 07:26:02', '::1'),
(195, 7, 'Edited User', 63, '2025-09-17 07:26:11', NULL),
(196, 7, 'Assigned Subject to Teacher', 64, '2025-09-17 07:26:26', NULL),
(197, 7, 'Assigned Subject to Teacher', 62, '2025-09-17 07:26:37', NULL),
(198, 7, 'Assigned Subject to Teacher', 63, '2025-09-17 07:26:45', NULL),
(199, 7, 'Assigned Subject to Teacher', 65, '2025-09-17 07:26:59', NULL),
(200, 62, 'Login', NULL, '2025-09-17 07:27:24', NULL),
(201, 62, 'Created Activity', 29, '2025-09-17 07:34:47', '::1'),
(202, 23, 'Login', NULL, '2025-09-17 07:35:49', '::1'),
(203, 23, 'Logout', NULL, '2025-09-17 07:36:48', '::1'),
(204, 24, 'Login', NULL, '2025-09-17 07:37:01', '::1'),
(205, 24, 'Logout', NULL, '2025-09-17 07:37:28', '::1'),
(206, 16, 'Login', NULL, '2025-09-17 07:37:49', '::1'),
(207, 62, 'Login', NULL, '2025-09-17 07:38:13', NULL),
(208, 62, 'Logout', NULL, '2025-09-17 07:38:49', '::1'),
(209, 15, 'Login', NULL, '2025-09-17 07:39:01', '::1'),
(210, 15, 'Logout', NULL, '2025-09-17 07:39:21', '::1'),
(211, 60, 'Login', NULL, '2025-09-17 07:39:34', '::1'),
(212, 62, 'Login', NULL, '2025-09-17 07:39:57', NULL),
(213, 62, 'Logout', NULL, '2025-09-17 07:40:18', '::1'),
(214, 62, 'Login', NULL, '2025-09-17 07:40:51', NULL),
(215, 14, 'Login', NULL, '2025-09-17 07:41:47', '::1'),
(216, 14, 'Logout', NULL, '2025-09-17 07:42:12', '::1'),
(217, 21, 'Login', NULL, '2025-09-17 07:42:18', '::1'),
(218, 21, 'Logout', NULL, '2025-09-17 07:42:33', '::1'),
(219, 23, 'Login', NULL, '2025-09-17 07:42:39', '::1'),
(220, 23, 'Logout', NULL, '2025-09-17 07:42:46', '::1'),
(221, 19, 'Login', NULL, '2025-09-17 07:42:55', '::1'),
(222, 19, 'Logout', NULL, '2025-09-17 07:43:09', '::1'),
(223, 22, 'Login', NULL, '2025-09-17 07:43:20', '::1'),
(224, 22, 'Logout', NULL, '2025-09-17 07:43:45', '::1'),
(225, 17, 'Login', NULL, '2025-09-17 07:44:05', '::1'),
(226, 17, 'Logout', NULL, '2025-09-17 07:44:18', '::1'),
(227, 18, 'Login', NULL, '2025-09-17 07:44:24', '::1'),
(228, 18, 'Logout', NULL, '2025-09-17 07:44:40', '::1'),
(229, 20, 'Login', NULL, '2025-09-17 07:44:46', '::1'),
(230, 62, 'Login', NULL, '2025-09-17 07:45:52', NULL),
(231, 24, 'Login', NULL, '2025-09-17 07:47:03', '::1'),
(232, 62, 'Login', NULL, '2025-09-17 07:57:31', NULL),
(233, 24, 'Login', NULL, '2025-09-17 08:38:06', '::1'),
(234, 62, 'Login', NULL, '2025-09-17 09:52:02', NULL),
(235, 62, 'Created Activity', 30, '2025-09-17 09:52:59', '::1'),
(236, 24, 'Login', NULL, '2025-09-17 09:53:13', '::1'),
(237, 62, 'Login', NULL, '2025-09-17 09:54:17', NULL),
(238, 62, 'Logout', NULL, '2025-09-17 10:05:09', '::1'),
(239, 16, 'Login', NULL, '2025-09-17 10:05:19', '::1'),
(240, 62, 'Login', NULL, '2025-09-17 12:56:08', NULL),
(241, 51, 'Login', NULL, '2025-09-17 12:58:35', '::1'),
(242, 51, 'Logout', NULL, '2025-09-17 12:58:56', '::1'),
(243, 24, 'Login', NULL, '2025-09-17 12:59:12', '::1');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_activity_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_activity_summary` (
`activity_id` int(11)
,`title` varchar(200)
,`description` text
,`activity_type` enum('quiz','assignment','recitation','exam')
,`total_points` int(11)
,`due_date` datetime
,`is_active` tinyint(1)
,`teacher_name` varchar(101)
,`subject_name` varchar(100)
,`grade_level` varchar(50)
,`total_students` int(11)
,`completed_submissions` int(11)
,`pending_submissions` int(11)
,`average_score` decimal(5,2)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_login_statistics`
-- (See below for the actual view)
--
CREATE TABLE `v_login_statistics` (
`login_date` date
,`total_attempts` bigint(21)
,`successful_logins` decimal(22,0)
,`failed_attempts` decimal(22,0)
,`unique_users` bigint(21)
,`unique_ips` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_student_accounts`
-- (See below for the actual view)
--
CREATE TABLE `v_student_accounts` (
`user_id` int(11)
,`username` varchar(50)
,`email` varchar(100)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`full_name` varchar(101)
,`grade_level` varchar(2)
,`created_at` timestamp
,`updated_at` timestamp
,`created_by_staff_id` int(11)
,`created_by_staff_username` varchar(50)
,`created_by_staff_name` varchar(101)
,`creation_method` enum('manual','bulk_upload')
,`creation_timestamp` timestamp
,`total_logins` bigint(21)
,`last_login` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_student_activity_scores`
-- (See below for the actual view)
--
CREATE TABLE `v_student_activity_scores` (
`submission_id` int(11)
,`activity_id` int(11)
,`activity_title` varchar(200)
,`activity_type` enum('quiz','assignment','recitation','exam')
,`student_name` varchar(101)
,`grade_level` varchar(2)
,`subject_name` varchar(100)
,`total_score` decimal(5,2)
,`max_score` decimal(5,2)
,`percentage` decimal(5,2)
,`submission_status` enum('not_started','in_progress','submitted','graded')
,`submitted_at` timestamp
,`graded_at` timestamp
,`teacher_name` varchar(101)
);

-- --------------------------------------------------------

--
-- Structure for view `v_activity_summary`
--
DROP TABLE IF EXISTS `v_activity_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_activity_summary`  AS SELECT `a`.`activity_id` AS `activity_id`, `a`.`title` AS `title`, `a`.`description` AS `description`, `a`.`activity_type` AS `activity_type`, `a`.`total_points` AS `total_points`, `a`.`due_date` AS `due_date`, `a`.`is_active` AS `is_active`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `teacher_name`, `s`.`subject_name` AS `subject_name`, `s`.`grade_level` AS `grade_level`, coalesce(`an`.`total_students`,0) AS `total_students`, coalesce(`an`.`completed_submissions`,0) AS `completed_submissions`, coalesce(`an`.`pending_submissions`,0) AS `pending_submissions`, coalesce(`an`.`average_score`,0) AS `average_score`, `a`.`created_at` AS `created_at` FROM (((`activities` `a` join `users` `u` on(`a`.`teacher_id` = `u`.`user_id`)) join `subjects` `s` on(`a`.`subject_id` = `s`.`subject_id`)) left join `activity_analytics` `an` on(`a`.`activity_id` = `an`.`activity_id`)) ORDER BY `a`.`created_at` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `v_login_statistics`
--
DROP TABLE IF EXISTS `v_login_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_login_statistics`  AS SELECT cast(`student_login_audit`.`attempt_timestamp` as date) AS `login_date`, count(0) AS `total_attempts`, sum(case when `student_login_audit`.`login_status` = 'success' then 1 else 0 end) AS `successful_logins`, sum(case when `student_login_audit`.`login_status` = 'failed' then 1 else 0 end) AS `failed_attempts`, count(distinct `student_login_audit`.`user_id`) AS `unique_users`, count(distinct `student_login_audit`.`ip_address`) AS `unique_ips` FROM `student_login_audit` GROUP BY cast(`student_login_audit`.`attempt_timestamp` as date) ORDER BY cast(`student_login_audit`.`attempt_timestamp` as date) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `v_student_accounts`
--
DROP TABLE IF EXISTS `v_student_accounts`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_student_accounts`  AS SELECT `u`.`user_id` AS `user_id`, `u`.`username` AS `username`, `u`.`email` AS `email`, `u`.`first_name` AS `first_name`, `u`.`last_name` AS `last_name`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `full_name`, `u`.`grade_level` AS `grade_level`, `u`.`created_at` AS `created_at`, `u`.`updated_at` AS `updated_at`, `acl`.`created_by_staff_id` AS `created_by_staff_id`, `staff`.`username` AS `created_by_staff_username`, concat(`staff`.`first_name`,' ',`staff`.`last_name`) AS `created_by_staff_name`, `acl`.`creation_method` AS `creation_method`, `acl`.`creation_timestamp` AS `creation_timestamp`, (select count(0) from `student_login_audit` `sla` where `sla`.`user_id` = `u`.`user_id` and `sla`.`login_status` = 'success') AS `total_logins`, (select max(`sla`.`attempt_timestamp`) from `student_login_audit` `sla` where `sla`.`user_id` = `u`.`user_id` and `sla`.`login_status` = 'success') AS `last_login` FROM ((`users` `u` left join `account_creation_log` `acl` on(`u`.`user_id` = `acl`.`created_user_id`)) left join `users` `staff` on(`acl`.`created_by_staff_id` = `staff`.`user_id`)) WHERE `u`.`role_id` = 4 ORDER BY `u`.`grade_level` ASC, `u`.`last_name` ASC, `u`.`first_name` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `v_student_activity_scores`
--
DROP TABLE IF EXISTS `v_student_activity_scores`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_student_activity_scores`  AS SELECT `sub`.`submission_id` AS `submission_id`, `sub`.`activity_id` AS `activity_id`, `a`.`title` AS `activity_title`, `a`.`activity_type` AS `activity_type`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `student_name`, `u`.`grade_level` AS `grade_level`, `s`.`subject_name` AS `subject_name`, `sub`.`total_score` AS `total_score`, `sub`.`max_score` AS `max_score`, `sub`.`percentage` AS `percentage`, `sub`.`submission_status` AS `submission_status`, `sub`.`submitted_at` AS `submitted_at`, `sub`.`graded_at` AS `graded_at`, concat(`t`.`first_name`,' ',`t`.`last_name`) AS `teacher_name` FROM ((((`student_submissions` `sub` join `activities` `a` on(`sub`.`activity_id` = `a`.`activity_id`)) join `users` `u` on(`sub`.`student_id` = `u`.`user_id`)) join `subjects` `s` on(`a`.`subject_id` = `s`.`subject_id`)) join `users` `t` on(`a`.`teacher_id` = `t`.`user_id`)) ORDER BY `sub`.`submitted_at` DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_creation_log`
--
ALTER TABLE `account_creation_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_created_user` (`created_user_id`),
  ADD KEY `idx_created_by` (`created_by_staff_id`),
  ADD KEY `idx_creation_timestamp` (`creation_timestamp`);

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `idx_teacher_id` (`teacher_id`),
  ADD KEY `idx_subject_id` (`subject_id`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_activities_teacher_subject` (`teacher_id`,`subject_id`),
  ADD KEY `idx_activities_due_date` (`due_date`);

--
-- Indexes for table `activity_analytics`
--
ALTER TABLE `activity_analytics`
  ADD PRIMARY KEY (`analytics_id`),
  ADD UNIQUE KEY `unique_activity_analytics` (`activity_id`);

--
-- Indexes for table `activity_questions`
--
ALTER TABLE `activity_questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `idx_activity_id` (`activity_id`),
  ADD KEY `idx_question_order` (`question_order`);

--
-- Indexes for table `question_choices`
--
ALTER TABLE `question_choices`
  ADD PRIMARY KEY (`choice_id`),
  ADD KEY `idx_question_id` (`question_id`),
  ADD KEY `idx_is_correct` (`is_correct`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `student_answers`
--
ALTER TABLE `student_answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD UNIQUE KEY `unique_submission_question` (`submission_id`,`question_id`),
  ADD KEY `idx_submission_id` (`submission_id`),
  ADD KEY `idx_question_id` (`question_id`),
  ADD KEY `idx_choice_id` (`choice_id`),
  ADD KEY `idx_answers_correct` (`is_correct`);

--
-- Indexes for table `student_login_audit`
--
ALTER TABLE `student_login_audit`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_ip` (`ip_address`),
  ADD KEY `idx_audit_timestamp` (`attempt_timestamp`);

--
-- Indexes for table `student_submissions`
--
ALTER TABLE `student_submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD UNIQUE KEY `unique_student_activity` (`activity_id`,`student_id`),
  ADD KEY `idx_activity_id` (`activity_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_submission_status` (`submission_status`),
  ADD KEY `idx_submissions_student_status` (`student_id`,`submission_status`),
  ADD KEY `idx_submissions_activity_status` (`activity_id`,`submission_status`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`);

--
-- Indexes for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher_subject` (`teacher_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_username` (`username`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_role` (`role_id`),
  ADD KEY `idx_users_login_lookup` (`username`,`email`,`role_id`);

--
-- Indexes for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `affected_user_id` (`affected_user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account_creation_log`
--
ALTER TABLE `account_creation_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `activity_analytics`
--
ALTER TABLE `activity_analytics`
  MODIFY `analytics_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_questions`
--
ALTER TABLE `activity_questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `question_choices`
--
ALTER TABLE `question_choices`
  MODIFY `choice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=154;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `student_answers`
--
ALTER TABLE `student_answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `student_login_audit`
--
ALTER TABLE `student_login_audit`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_submissions`
--
ALTER TABLE `student_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `user_logs`
--
ALTER TABLE `user_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=244;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `account_creation_log`
--
ALTER TABLE `account_creation_log`
  ADD CONSTRAINT `account_creation_log_ibfk_1` FOREIGN KEY (`created_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `account_creation_log_ibfk_2` FOREIGN KEY (`created_by_staff_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `fk_activities_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_activities_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_analytics`
--
ALTER TABLE `activity_analytics`
  ADD CONSTRAINT `fk_analytics_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`activity_id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_questions`
--
ALTER TABLE `activity_questions`
  ADD CONSTRAINT `fk_questions_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`activity_id`) ON DELETE CASCADE;

--
-- Constraints for table `question_choices`
--
ALTER TABLE `question_choices`
  ADD CONSTRAINT `fk_choices_question` FOREIGN KEY (`question_id`) REFERENCES `activity_questions` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_answers`
--
ALTER TABLE `student_answers`
  ADD CONSTRAINT `fk_answers_choice` FOREIGN KEY (`choice_id`) REFERENCES `question_choices` (`choice_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_answers_question` FOREIGN KEY (`question_id`) REFERENCES `activity_questions` (`question_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_answers_submission` FOREIGN KEY (`submission_id`) REFERENCES `student_submissions` (`submission_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_login_audit`
--
ALTER TABLE `student_login_audit`
  ADD CONSTRAINT `student_login_audit_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `student_submissions`
--
ALTER TABLE `student_submissions`
  ADD CONSTRAINT `fk_submissions_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`activity_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_submissions_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD CONSTRAINT `teacher_subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);

--
-- Constraints for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD CONSTRAINT `user_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_logs_ibfk_2` FOREIGN KEY (`affected_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
