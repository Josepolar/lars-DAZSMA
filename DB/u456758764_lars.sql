-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 18, 2025 at 01:40 PM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u456758764_lars`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `game_activities`
--

CREATE TABLE `game_activities` (
  `game_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `time_limit` int(11) DEFAULT 30,
  `default_points` int(11) DEFAULT 100,
  `due_date` datetime DEFAULT NULL,
  `show_leaderboard` tinyint(1) DEFAULT 1,
  `status` enum('draft','active','completed','archived') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `game_activities`
--

INSERT INTO `game_activities` (`game_id`, `subject_id`, `teacher_id`, `title`, `description`, `time_limit`, `default_points`, `due_date`, `show_leaderboard`, `status`, `created_at`, `updated_at`) VALUES
(0, 1, 62, 'Filipino Quiz', 'Ang quiz na ito ay idinisenyo para sa mga mag-aaral ng Grade 7 upang masukat ang kanilang kaalaman sa batayang konsepto ng asignaturang Filipino tulad ng wika, panitikan, at gramatika.', 30, 100, '2025-12-23 06:33:00', 1, 'active', '2025-12-16 06:33:45', '2025-12-16 06:38:43');

-- --------------------------------------------------------

--
-- Table structure for table `game_options`
--

CREATE TABLE `game_options` (
  `option_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_text` varchar(255) NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `option_order` int(11) NOT NULL,
  `color_code` varchar(20) DEFAULT 'blue'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `game_options`
--

INSERT INTO `game_options` (`option_id`, `question_id`, `option_text`, `is_correct`, `option_order`, `color_code`) VALUES
(45, 12, '1', 1, 0, 'red'),
(46, 12, '3', 0, 1, 'blue'),
(47, 12, '2', 0, 2, 'yellow'),
(48, 12, '4', 0, 3, 'green'),
(49, 13, '1', 1, 0, 'red'),
(50, 13, '3', 0, 1, 'blue'),
(51, 13, '2', 0, 2, 'yellow'),
(52, 13, '4', 0, 3, 'green'),
(0, 0, 'Pantig', 0, 0, 'red'),
(0, 0, 'Salita', 0, 1, 'blue'),
(0, 0, 'Morpema', 1, 2, 'yellow'),
(0, 0, 'Pangungusap', 0, 3, 'green'),
(0, 0, 'Cupid at Psyche', 1, 0, 'red'),
(0, 0, 'Alamat ng Pinya', 0, 1, 'blue'),
(0, 0, 'Noli Me Tangere', 0, 2, 'yellow'),
(0, 0, 'Ibong Adarna', 0, 3, 'green'),
(0, 0, 'Magturo ng aral', 0, 0, 'red'),
(0, 0, 'Magbigay-aliw', 0, 1, 'blue'),
(0, 0, 'Magpaliwanag ng pinagmulan', 1, 2, 'yellow'),
(0, 0, 'Maglahad ng kasaysayan', 0, 3, 'green'),
(0, 0, 'Siya ay kumain ng masarap.', 1, 0, 'red'),
(0, 0, 'Kumain siya ng masarap na.', 0, 1, 'blue'),
(0, 0, 'Kumain masarap siya.', 0, 2, 'yellow'),
(0, 0, 'Masarap siya kumain ng.', 0, 3, 'green'),
(0, 0, 'Homonim', 0, 0, 'red'),
(0, 0, 'Homopono', 0, 1, 'blue'),
(0, 0, 'Sinonim', 0, 2, 'yellow'),
(0, 0, 'Homograp', 1, 3, 'green'),
(0, 0, '1', 1, 0, 'red'),
(0, 0, '3', 0, 1, 'blue'),
(0, 0, '2', 0, 2, 'yellow'),
(0, 0, '4', 0, 3, 'green'),
(0, 0, '1', 1, 0, 'red'),
(0, 0, '3', 0, 1, 'blue'),
(0, 0, '2', 0, 2, 'yellow'),
(0, 0, '4', 0, 3, 'green'),
(0, 0, '1', 0, 0, 'red'),
(0, 0, '2', 1, 1, 'blue'),
(0, 0, '3', 0, 2, 'yellow'),
(0, 0, '4', 0, 3, 'green'),
(0, 0, 'Pantig', 0, 0, 'red'),
(0, 0, 'Salita', 0, 1, 'blue'),
(0, 0, 'Morpema', 1, 2, 'yellow'),
(0, 0, 'Pangungusap', 0, 3, 'green'),
(0, 0, 'Ibong Adarna', 0, 0, 'red'),
(0, 0, 'Alamat ng Pinya', 0, 1, 'blue'),
(0, 0, 'Cupid at Psyche', 1, 2, 'yellow'),
(0, 0, 'Noli Me Tangere', 0, 3, 'green'),
(0, 0, 'Magbigay-aliw', 0, 0, 'red'),
(0, 0, 'Magpaliwanag ng pinagmulan', 1, 1, 'blue'),
(0, 0, 'Magturo ng aral', 0, 2, 'yellow'),
(0, 0, 'Maglahad ng kasaysayan', 0, 3, 'green'),
(0, 0, 'Kumain siya ng masarap na.', 0, 0, 'red'),
(0, 0, 'Siya ay kumain ng masarap.', 1, 1, 'blue'),
(0, 0, 'Kumain masarap siya.', 0, 2, 'yellow'),
(0, 0, 'Masarap siya kumain ng.', 0, 3, 'green'),
(0, 0, 'Homograp', 1, 0, 'red'),
(0, 0, 'Homonim', 0, 1, 'blue'),
(0, 0, 'Homopono', 0, 2, 'yellow'),
(0, 0, 'Sinonim', 0, 3, 'green'),
(0, 0, 'Pantig', 0, 0, 'red'),
(0, 0, 'Salita', 0, 1, 'blue'),
(0, 0, 'Morpema', 1, 2, 'yellow'),
(0, 0, 'Pangungusap', 0, 3, 'green'),
(0, 0, 'Ibong Adarna', 0, 0, 'red'),
(0, 0, 'Alamat ng Pinya', 0, 1, 'blue'),
(0, 0, 'Cupid at Psyche', 1, 2, 'yellow'),
(0, 0, 'Noli Me Tangere', 0, 3, 'green'),
(0, 0, 'Magbigay-aliw', 0, 0, 'red'),
(0, 0, 'Magpaliwanag ng pinagmulan', 1, 1, 'blue'),
(0, 0, 'Magturo ng aral', 0, 2, 'yellow'),
(0, 0, 'Maglahad ng kasaysayan', 0, 3, 'green'),
(0, 0, 'Kumain siya ng masarap na.', 0, 0, 'red'),
(0, 0, 'Kumain masarap siya.', 0, 1, 'blue'),
(0, 0, 'Masarap siya kumain ng.', 0, 2, 'yellow'),
(0, 0, 'Siya ay kumain ng masarap.', 1, 3, 'green'),
(0, 0, 'Homonim', 0, 0, 'red'),
(0, 0, 'Homopono', 0, 1, 'blue'),
(0, 0, 'Homograp', 1, 2, 'yellow'),
(0, 0, 'Sinonim', 0, 3, 'green');

-- --------------------------------------------------------

--
-- Table structure for table `game_questions`
--

CREATE TABLE `game_questions` (
  `question_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_order` int(11) NOT NULL,
  `time_limit` int(11) DEFAULT 30,
  `points` int(11) DEFAULT 1000,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `game_questions`
--

INSERT INTO `game_questions` (`question_id`, `game_id`, `question_text`, `question_order`, `time_limit`, `points`, `image_url`, `created_at`) VALUES
(12, 19, 'press', 1, 30, 100, NULL, '2025-12-10 18:44:08'),
(13, 19, 'press 2', 2, 30, 1000, NULL, '2025-12-10 18:44:21'),
(0, 0, 'Ano ang tawag sa pinakamaliit na yunit ng wika na may kahulugan?', 1, 30, 100, NULL, '2025-12-16 06:34:01'),
(0, 0, 'Alin sa mga sumusunod ang halimbawa ng mitolohiya?', 2, 30, 100, NULL, '2025-12-16 06:34:13'),
(0, 0, 'Ano ang pangunahing layunin ng alamat?', 3, 30, 100, NULL, '2025-12-16 06:34:26'),
(0, 0, 'Alin ang tamang pangungusap?', 4, 30, 100, NULL, '2025-12-16 06:34:40'),
(0, 0, 'Ano ang tawag sa mga salitang iisa ang baybay ngunit magkaiba ang kahulugan at bigkas?', 5, 30, 100, NULL, '2025-12-16 06:34:53');

-- --------------------------------------------------------

--
-- Table structure for table `game_responses`
--

CREATE TABLE `game_responses` (
  `response_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `session_id` int(11) DEFAULT NULL,
  `question_id` int(11) NOT NULL,
  `selected_option_id` int(11) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `time_taken` int(11) DEFAULT NULL,
  `points_earned` int(11) DEFAULT 0,
  `answered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `game_responses`
--

INSERT INTO `game_responses` (`response_id`, `game_id`, `student_id`, `session_id`, `question_id`, `selected_option_id`, `is_correct`, `time_taken`, `points_earned`, `answered_at`) VALUES
(0, 0, 19, 0, 0, 0, 0, 107, 0, '2025-12-16 03:37:02');

-- --------------------------------------------------------

--
-- Table structure for table `game_sessions`
--

CREATE TABLE `game_sessions` (
  `session_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `total_score` int(11) DEFAULT 0,
  `total_correct` int(11) DEFAULT 0,
  `total_questions` int(11) DEFAULT 0,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `game_sessions`
--

INSERT INTO `game_sessions` (`session_id`, `game_id`, `student_id`, `total_score`, `total_correct`, `total_questions`, `started_at`, `completed_at`) VALUES
(30, 19, 24, 0, 0, 2, '2025-12-10 18:49:30', '2025-12-10 18:49:43'),
(31, 19, 16, 0, 0, 2, '2025-12-10 18:50:37', '2025-12-10 18:50:45'),
(32, 19, 15, 0, 0, 2, '2025-12-10 18:52:18', '2025-12-10 18:52:30'),
(33, 19, 20, 0, 0, 2, '2025-12-10 18:54:07', '2025-12-10 18:54:18'),
(37, 19, 60, 0, 0, 2, '2025-12-10 20:43:10', NULL),
(38, 19, 60, 0, 0, 2, '2025-12-10 20:46:24', NULL),
(0, 0, 17, 0, 0, 5, '2025-12-16 06:38:53', '2025-12-16 07:41:18'),
(0, 0, 17, 0, 0, 5, '2025-12-16 07:40:59', '2025-12-16 07:41:18');

-- --------------------------------------------------------

--
-- Table structure for table `matching_games`
--

CREATE TABLE `matching_games` (
  `matching_game_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `game_type` enum('image-to-text','text-to-text','image-to-image','number-to-text') DEFAULT 'image-to-text',
  `time_limit` int(11) DEFAULT 300,
  `show_leaderboard` tinyint(1) DEFAULT 1,
  `points_per_pair` int(11) DEFAULT 100,
  `due_date` datetime DEFAULT NULL,
  `status` enum('draft','active','completed','archived') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `matching_games`
--

INSERT INTO `matching_games` (`matching_game_id`, `subject_id`, `teacher_id`, `title`, `description`, `game_type`, `time_limit`, `show_leaderboard`, `points_per_pair`, `due_date`, `status`, `created_at`, `updated_at`) VALUES
(0, 1, 62, 'Matching Game 1', 'Itugma ang mga salita sa Hanay A sa tamang kaugnay na salita o kahulugan sa Hanay B. Isulat ang titik ng tamang sagot.', 'text-to-text', 180, 1, 100, '2025-12-23 06:39:00', 'active', '2025-12-16 06:39:48', '2025-12-16 06:40:22');

-- --------------------------------------------------------

--
-- Table structure for table `matching_pairs`
--

CREATE TABLE `matching_pairs` (
  `pair_id` int(11) NOT NULL,
  `matching_game_id` int(11) NOT NULL,
  `left_item_text` varchar(255) DEFAULT NULL,
  `left_item_image` varchar(255) DEFAULT NULL,
  `right_item_text` varchar(255) NOT NULL,
  `right_item_image` varchar(255) DEFAULT NULL,
  `pair_order` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `matching_pairs`
--

INSERT INTO `matching_pairs` (`pair_id`, `matching_game_id`, `left_item_text`, `left_item_image`, `right_item_text`, `right_item_image`, `pair_order`, `created_at`) VALUES
(46, 16, '', '../../uploads/matching_games/left_6939bfbf87a28_1765392319.png', 'qr', NULL, 1, '2025-12-10 18:45:19'),
(47, 16, '', '../../uploads/matching_games/left_6939bfc57d288_1765392325.JPEG', 'painting', NULL, 2, '2025-12-10 18:45:25'),
(48, 16, '', '../../uploads/matching_games/left_6939bfd09337d_1765392336.jpg', 'avatar', NULL, 3, '2025-12-10 18:45:36'),
(49, 16, '', '../../uploads/matching_games/left_6939bfdcbb3d0_1765392348.jpg', 'comms and suggestion', NULL, 4, '2025-12-10 18:45:48'),
(0, 0, 'Pangngalan', NULL, 'Tao, hayop, lugar, o bagay', NULL, 1, '2025-12-16 06:39:56'),
(0, 0, 'Pandiwa', NULL, 'Kilos o galaw', NULL, 2, '2025-12-16 06:40:00'),
(0, 0, 'Pang-uri', NULL, 'Naglalarawan ng katangian o anyo', NULL, 3, '2025-12-16 06:40:06'),
(0, 0, 'Tauhan', NULL, 'Gumaganap sa kuwento', NULL, 4, '2025-12-16 06:40:12'),
(0, 0, 'Tagpuan', NULL, 'Lugar at panahon kung saan naganap ang kuwento', NULL, 5, '2025-12-16 06:40:21');

-- --------------------------------------------------------

--
-- Table structure for table `matching_responses`
--

CREATE TABLE `matching_responses` (
  `response_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `pair_id` int(11) NOT NULL,
  `student_answer_pair_id` int(11) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `attempts` int(11) DEFAULT 1,
  `time_taken` int(11) DEFAULT 0,
  `points_earned` int(11) DEFAULT 0,
  `matched_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores individual student responses for matching games. pair_id is the correct answer, student_answer_pair_id is what the student matched.';

--
-- Dumping data for table `matching_responses`
--

INSERT INTO `matching_responses` (`response_id`, `session_id`, `pair_id`, `student_answer_pair_id`, `is_correct`, `attempts`, `time_taken`, `points_earned`, `matched_at`) VALUES
(69, 28, 47, 47, 1, 1, 0, 100, '2025-12-10 18:48:35'),
(70, 28, 49, 49, 1, 1, 0, 100, '2025-12-10 18:48:35'),
(71, 28, 48, 48, 1, 1, 0, 100, '2025-12-10 18:48:35'),
(72, 28, 46, 46, 1, 1, 0, 100, '2025-12-10 18:48:35'),
(73, 29, 49, 49, 1, 1, 0, 100, '2025-12-10 18:50:31'),
(74, 29, 47, 47, 1, 1, 0, 100, '2025-12-10 18:50:31'),
(75, 29, 48, 48, 1, 1, 0, 100, '2025-12-10 18:50:31'),
(76, 29, 46, 46, 1, 1, 0, 100, '2025-12-10 18:50:31'),
(77, 30, 49, 49, 1, 1, 0, 100, '2025-12-10 18:52:07'),
(78, 30, 46, 46, 1, 1, 0, 100, '2025-12-10 18:52:07'),
(79, 30, 47, 47, 1, 1, 0, 100, '2025-12-10 18:52:07'),
(80, 30, 48, 48, 1, 1, 0, 100, '2025-12-10 18:52:07'),
(81, 31, 47, 47, 1, 1, 0, 100, '2025-12-10 18:54:01'),
(82, 31, 48, 48, 1, 1, 0, 100, '2025-12-10 18:54:01'),
(83, 31, 49, 49, 1, 1, 0, 100, '2025-12-10 18:54:01'),
(84, 31, 46, 46, 1, 1, 0, 100, '2025-12-10 18:54:01'),
(85, 32, 48, 48, 1, 1, 0, 100, '2025-12-10 20:44:03'),
(86, 32, 47, 47, 1, 1, 0, 100, '2025-12-10 20:44:03'),
(87, 32, 46, 46, 1, 1, 0, 100, '2025-12-10 20:44:03'),
(88, 32, 49, 49, 1, 1, 0, 100, '2025-12-10 20:44:03'),
(0, 0, 0, 0, 1, 1, 0, 100, '2025-12-16 07:41:55'),
(0, 0, 0, 0, 1, 1, 0, 100, '2025-12-16 07:41:55'),
(0, 0, 0, 0, 1, 1, 0, 100, '2025-12-16 07:41:55'),
(0, 0, 0, 0, 1, 1, 0, 100, '2025-12-16 07:41:55'),
(0, 0, 0, 0, 1, 1, 0, 100, '2025-12-16 07:41:55');

-- --------------------------------------------------------

--
-- Table structure for table `matching_sessions`
--

CREATE TABLE `matching_sessions` (
  `session_id` int(11) NOT NULL,
  `matching_game_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `total_score` int(11) DEFAULT 0,
  `total_correct` int(11) DEFAULT 0,
  `total_pairs` int(11) DEFAULT 0,
  `time_taken` int(11) DEFAULT 0,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `matching_sessions`
--

INSERT INTO `matching_sessions` (`session_id`, `matching_game_id`, `student_id`, `total_score`, `total_correct`, `total_pairs`, `time_taken`, `started_at`, `completed_at`) VALUES
(28, 16, 24, 660, 4, 4, 8, '2025-12-10 18:48:25', '2025-12-10 18:48:35'),
(29, 16, 16, 645, 4, 4, 11, '2025-12-10 18:50:18', '2025-12-10 18:50:31'),
(30, 16, 15, 660, 4, 4, 8, '2025-12-10 18:51:57', '2025-12-10 18:52:07'),
(31, 16, 20, 655, 4, 4, 9, '2025-12-10 18:53:50', '2025-12-10 18:54:01'),
(32, 16, 60, 495, 4, 4, 41, '2025-12-10 20:43:19', '2025-12-10 20:44:03'),
(0, 0, 17, 1250, 5, 5, 30, '2025-12-16 07:41:23', '2025-12-16 07:41:55');

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
(4, 'MATH 10', '10', '2025-09-14 14:57:17', '2025-09-14 14:57:17'),
(5, 'fil', '7', '2025-12-10 19:34:07', '2025-12-10 19:34:07');

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
(10, 65, 3),
(11, 66, 4);

-- --------------------------------------------------------

--
-- Table structure for table `typing_games`
--

CREATE TABLE `typing_games` (
  `typing_game_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `difficulty` enum('easy','medium','hard') DEFAULT 'medium',
  `time_limit` int(11) DEFAULT 60 COMMENT 'Time limit in seconds',
  `min_wpm` int(11) DEFAULT 0 COMMENT 'Minimum WPM to pass',
  `show_leaderboard` tinyint(1) DEFAULT 1,
  `status` enum('active','inactive','draft') DEFAULT 'active',
  `due_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `typing_games`
--

INSERT INTO `typing_games` (`typing_game_id`, `subject_id`, `teacher_id`, `title`, `description`, `difficulty`, `time_limit`, `min_wpm`, `show_leaderboard`, `status`, `due_date`, `created_at`, `updated_at`) VALUES
(0, 1, 62, 'Speed Typing', 'Ang Filipino speed typing game na ito ay tumutulong sa mga mag-aaral ng Grade 7 na mapahusay ang kanilang bilis at kawastuhan sa pagta-type habang pinapalalim ang kanilang pag-unawa sa wikang Filipino. Magta-type ang mga mag-aaral ng maiikling talata na may temang pangwika at pampanitikan sa loob ng itinakdang oras.', 'medium', 60, 20, 1, 'active', '2025-12-23 06:37:00', '2025-12-16 06:37:49', '2025-12-16 06:37:49');

-- --------------------------------------------------------

--
-- Table structure for table `typing_sessions`
--

CREATE TABLE `typing_sessions` (
  `session_id` int(11) NOT NULL,
  `typing_game_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `text_id` int(11) NOT NULL,
  `total_characters` int(11) DEFAULT 0,
  `correct_characters` int(11) DEFAULT 0,
  `wrong_characters` int(11) DEFAULT 0,
  `wpm` decimal(6,2) DEFAULT 0.00 COMMENT 'Words per minute',
  `accuracy` decimal(5,2) DEFAULT 0.00 COMMENT 'Accuracy percentage',
  `total_score` int(11) DEFAULT 0,
  `time_taken` int(11) DEFAULT 0 COMMENT 'Time taken in seconds',
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `typing_sessions`
--

INSERT INTO `typing_sessions` (`session_id`, `typing_game_id`, `student_id`, `text_id`, `total_characters`, `correct_characters`, `wrong_characters`, `wpm`, `accuracy`, `total_score`, `time_taken`, `completed_at`, `created_at`) VALUES
(6, 2, 24, 3, 44, 44, 0, 21.00, 100.00, 487, 26, '2025-12-11 02:49:19', '2025-12-10 18:49:19'),
(7, 2, 16, 3, 44, 44, 0, 27.00, 100.00, 607, 20, '2025-12-11 02:51:15', '2025-12-10 18:51:15'),
(8, 2, 15, 3, 44, 44, 0, 37.00, 100.00, 784, 14, '2025-12-11 02:52:57', '2025-12-10 18:52:57'),
(9, 2, 20, 3, 44, 44, 0, 30.00, 100.00, 662, 18, '2025-12-11 02:54:45', '2025-12-10 18:54:45'),
(10, 2, 60, 3, 44, 44, 0, 22.00, 100.00, 512, 24, '2025-12-11 04:45:58', '2025-12-10 20:45:58'),
(11, 3, 24, 4, 36, 36, 0, 26.00, 100.00, 605, 17, '2025-12-12 11:42:11', '2025-12-12 03:42:11'),
(0, 3, 17, 4, 36, 36, 0, 30.00, 100.00, 677, 15, '2025-12-16 05:30:05', '2025-12-16 05:30:05'),
(0, 0, 17, 0, 222, 141, 3, 28.00, 98.00, 412, 60, '2025-12-16 07:43:10', '2025-12-16 07:43:10');

-- --------------------------------------------------------

--
-- Table structure for table `typing_texts`
--

CREATE TABLE `typing_texts` (
  `text_id` int(11) NOT NULL,
  `typing_game_id` int(11) NOT NULL,
  `text_content` text NOT NULL,
  `text_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `typing_texts`
--

INSERT INTO `typing_texts` (`text_id`, `typing_game_id`, `text_content`, `text_order`, `created_at`) VALUES
(3, 2, 'The quick brown fox jumps over the lazy dog.', 1, '2025-12-10 18:46:58'),
(4, 3, 'How vexingly quick daft zebras jump!', 1, '2025-12-12 03:41:36'),
(0, 0, 'Ang wika ay mahalagang bahagi ng ating pang-araw-araw na buhay. Ito ang ginagamit natin upang maipahayag ang ating saloobin, damdamin, at ideya. Sa pamamagitan ng wika, nagkakaroon ng pagkakaunawaan ang mga tao sa lipunan.', 1, '2025-12-16 06:37:49');

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
(65, 'scienceteacher1799', '123', 'science@lars.edu.ph', 3, 'Science', 'teacher', '2025-09-17 07:25:09', '2025-09-17 07:25:09', NULL, NULL),
(66, 'joselars@gmail.com', 'jose123', 'joselars@gmail.com@lars.edu.ph', 3, 'hayden', 'commando', '2025-12-10 19:30:46', '2025-12-10 19:30:46', NULL, NULL);

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
(243, 24, 'Login', NULL, '2025-09-17 12:59:12', '::1'),
(244, 3, 'Login', NULL, '2025-10-23 15:04:53', '::1'),
(245, 5, 'Login', NULL, '2025-10-23 15:07:00', '::1'),
(246, 62, 'Login', NULL, '2025-10-23 16:35:21', NULL),
(247, 62, 'Created Activity', 31, '2025-10-23 16:35:59', '::1'),
(248, 14, 'Login', NULL, '2025-10-23 16:36:41', '::1'),
(249, 62, 'Login', NULL, '2025-10-23 16:37:08', NULL),
(250, 62, 'Login', NULL, '2025-11-08 06:34:09', NULL),
(251, 7, 'Login', NULL, '2025-11-08 06:39:47', '::1'),
(252, 24, 'Login', NULL, '2025-11-08 06:40:14', '::1'),
(253, 62, 'Login', NULL, '2025-11-08 06:40:41', NULL),
(254, 62, 'Deleted Activity', 31, '2025-11-08 06:56:27', '::1'),
(255, 62, 'Deleted Activity', 30, '2025-11-08 06:56:29', '::1'),
(256, 62, 'Deleted Activity', 29, '2025-11-08 06:56:31', '::1'),
(258, 62, 'Created Game Activity: g34234', NULL, '2025-11-08 07:19:00', '::1'),
(259, 62, 'Created Game Activity: 89789778', NULL, '2025-11-08 07:19:54', '::1'),
(260, 62, 'Created Game Activity: dasdasd', NULL, '2025-11-08 07:22:37', '::1'),
(261, 62, 'Created Game Activity: ewwewr', NULL, '2025-11-08 07:28:36', '::1'),
(262, 62, 'Added question to game (ID: 6)', NULL, '2025-11-08 07:29:15', '::1'),
(263, 62, 'Deleted Game Activity (ID: 6)', NULL, '2025-11-08 07:31:29', '::1'),
(264, 62, 'Deleted Game Activity (ID: 5)', NULL, '2025-11-08 07:31:30', '::1'),
(265, 62, 'Created Game Activity: 123', NULL, '2025-11-08 07:31:36', '::1'),
(266, 62, 'Added question to game (ID: 7)', NULL, '2025-11-08 07:31:42', '::1'),
(267, 62, 'Deleted Game Activity (ID: 7)', NULL, '2025-11-08 07:33:21', '::1'),
(268, 62, 'Created Game Activity: 123', NULL, '2025-11-08 07:33:24', '::1'),
(269, 62, 'Added question to game (ID: 8)', NULL, '2025-11-08 07:33:30', '::1'),
(270, 24, 'Login', NULL, '2025-11-08 07:51:54', '::1'),
(271, 7, 'Login', NULL, '2025-11-08 13:28:55', '::1'),
(272, 62, 'Login', NULL, '2025-11-08 13:29:24', NULL),
(273, 24, 'Login', NULL, '2025-11-08 13:37:55', '::1'),
(274, 62, 'Login', NULL, '2025-11-08 14:07:08', NULL),
(275, 62, 'Changed Game Status to Active (Game ID: 8)', NULL, '2025-11-08 14:07:15', '::1'),
(276, 24, 'Login', NULL, '2025-11-08 14:07:29', '::1'),
(277, 62, 'Login', NULL, '2025-11-08 14:08:33', NULL),
(278, 62, 'Created Game Activity: hello', NULL, '2025-11-08 14:08:41', '::1'),
(279, 62, 'Added question to game (ID: 9)', NULL, '2025-11-08 14:08:47', '::1'),
(280, 62, 'Changed Game Status to Active (Game ID: 9)', NULL, '2025-11-08 14:08:49', '::1'),
(281, 24, 'Login', NULL, '2025-11-08 14:09:03', '::1'),
(282, 62, 'Login', NULL, '2025-11-08 14:18:05', NULL),
(283, 62, 'Created Game Activity: test 2', NULL, '2025-11-08 14:18:14', '::1'),
(284, 62, 'Added question to game (ID: 10)', NULL, '2025-11-08 14:18:31', '::1'),
(285, 62, 'Changed Game Status to Active (Game ID: 10)', NULL, '2025-11-08 14:18:37', '::1'),
(286, 24, 'Login', NULL, '2025-11-08 14:18:55', '::1'),
(287, 24, 'Logout', NULL, '2025-11-08 14:24:19', '::1'),
(288, 16, 'Login', NULL, '2025-11-08 14:24:35', '::1'),
(289, 16, 'Logout', NULL, '2025-11-08 14:30:45', '::1'),
(290, 15, 'Login', NULL, '2025-11-08 14:30:53', '::1'),
(291, 62, 'Login', NULL, '2025-11-08 15:26:08', NULL),
(292, 24, 'Login', NULL, '2025-11-08 16:15:46', '::1'),
(293, 62, 'Login', NULL, '2025-11-08 16:22:02', NULL),
(294, 24, 'Login', NULL, '2025-11-08 16:38:46', '::1'),
(295, 62, 'Login', NULL, '2025-11-08 16:38:55', NULL),
(296, 24, 'Login', NULL, '2025-11-08 16:39:39', '::1'),
(297, 62, 'Login', NULL, '2025-11-08 16:39:47', NULL),
(298, 24, 'Login', NULL, '2025-11-08 16:49:52', '::1'),
(299, 62, 'Login', NULL, '2025-11-08 17:17:51', NULL),
(300, 62, 'Created Game Activity: sdasd', NULL, '2025-11-08 17:18:41', '::1'),
(301, 24, 'Login', NULL, '2025-11-08 17:21:34', '::1'),
(302, 62, 'Login', NULL, '2025-11-08 17:44:49', NULL),
(303, 62, 'Deleted Game Activity (ID: 11)', NULL, '2025-11-08 17:44:55', '::1'),
(304, 24, 'Login', NULL, '2025-11-08 17:45:32', '::1'),
(305, 62, 'Login', NULL, '2025-11-08 17:46:21', NULL),
(306, 24, 'Login', NULL, '2025-11-08 17:46:48', '::1'),
(307, 62, 'Login', NULL, '2025-11-08 17:56:44', NULL),
(308, 20, 'Login', NULL, '2025-11-08 18:16:21', '::1'),
(309, 62, 'Login', NULL, '2025-11-08 18:17:15', NULL),
(310, 16, 'Login', NULL, '2025-11-08 18:21:20', '::1'),
(311, 16, 'Logout', NULL, '2025-11-08 18:21:47', '::1'),
(312, 16, 'Login', NULL, '2025-11-08 18:21:59', '::1'),
(313, 62, 'Login', NULL, '2025-11-08 18:22:30', NULL),
(314, 15, 'Login', NULL, '2025-11-08 18:35:00', '::1'),
(315, 62, 'Login', NULL, '2025-11-08 18:38:07', NULL),
(316, 20, 'Login', NULL, '2025-11-08 18:39:56', '::1'),
(317, 20, 'Logout', NULL, '2025-11-08 18:44:39', '::1'),
(318, 24, 'Login', NULL, '2025-11-08 18:44:42', '::1'),
(319, 62, 'Login', NULL, '2025-11-08 18:57:47', NULL),
(320, 62, 'Deleted Game Activity (ID: 10)', NULL, '2025-11-08 19:08:03', '::1'),
(321, 62, 'Deleted Game Activity (ID: 9)', NULL, '2025-11-08 19:08:06', '::1'),
(322, 62, 'Deleted Game Activity (ID: 8)', NULL, '2025-11-08 19:08:08', '::1'),
(323, 62, 'Deleted Game Activity (ID: 1)', NULL, '2025-11-08 19:08:10', '::1'),
(324, 20, 'Login', NULL, '2025-11-08 19:10:28', '::1'),
(325, 62, 'Login', NULL, '2025-11-08 19:10:51', NULL),
(326, 20, 'Login', NULL, '2025-11-08 19:13:19', '::1'),
(327, 20, 'Logout', NULL, '2025-11-08 19:13:47', '::1'),
(328, 15, 'Login', NULL, '2025-11-08 19:13:49', '::1'),
(329, 62, 'Login', NULL, '2025-11-08 19:31:42', NULL),
(330, 16, 'Login', NULL, '2025-11-08 19:32:17', '::1'),
(331, 62, 'Login', NULL, '2025-11-08 19:32:54', NULL),
(332, 16, 'Login', NULL, '2025-11-08 19:33:13', '::1'),
(333, 62, 'Login', NULL, '2025-11-08 19:33:30', NULL),
(334, 16, 'Login', NULL, '2025-11-08 19:34:17', '::1'),
(335, 62, 'Login', NULL, '2025-11-08 19:34:59', NULL),
(336, 15, 'Login', NULL, '2025-11-08 20:05:18', '::1'),
(337, 62, 'Login', NULL, '2025-11-08 20:23:03', NULL),
(338, 62, 'Deleted Game Activity (ID: 14)', NULL, '2025-11-10 14:20:43', '::1'),
(339, 62, 'Deleted Game Activity (ID: 13)', NULL, '2025-11-10 14:20:45', '::1'),
(340, 62, 'Deleted Game Activity (ID: 12)', NULL, '2025-11-10 14:20:47', '::1'),
(341, 24, 'Login', NULL, '2025-11-10 14:47:22', '::1'),
(342, 62, 'Login', NULL, '2025-11-10 14:47:30', NULL),
(343, 20, 'Login', NULL, '2025-11-10 14:48:25', '::1'),
(344, 62, 'Login', NULL, '2025-11-10 14:52:07', NULL),
(345, 24, 'Login', NULL, '2025-11-12 12:08:49', '::1'),
(346, 62, 'Login', NULL, '2025-11-12 12:21:49', NULL),
(347, 24, 'Login', NULL, '2025-11-12 12:23:52', '::1'),
(348, 24, 'Logout', NULL, '2025-11-12 12:28:40', '::1'),
(349, 20, 'Login', NULL, '2025-11-12 12:28:43', '::1'),
(350, 62, 'Login', NULL, '2025-11-12 12:29:19', NULL),
(351, 15, 'Login', NULL, '2025-11-12 12:38:13', '::1'),
(352, 62, 'Login', NULL, '2025-11-12 12:39:24', NULL),
(353, 20, 'Login', NULL, '2025-11-12 12:49:07', '::1'),
(354, 62, 'Login', NULL, '2025-11-12 12:49:18', NULL),
(355, 62, 'Changed Game Status to Active (Game ID: 17)', NULL, '2025-11-12 12:49:22', '::1'),
(356, 20, 'Login', NULL, '2025-11-12 12:49:26', '::1'),
(357, 62, 'Login', NULL, '2025-11-12 12:49:54', NULL),
(358, 62, 'Deleted Game Activity (ID: 18)', NULL, '2025-11-12 12:52:41', '::1'),
(359, 62, 'Deleted Game Activity (ID: 17)', NULL, '2025-11-12 12:52:43', '::1'),
(360, 7, 'Login', NULL, '2025-11-12 14:08:39', '::1'),
(361, 3, 'Login', NULL, '2025-11-12 14:09:24', '::1'),
(362, 62, 'Login', NULL, '2025-11-14 11:29:20', NULL),
(363, 62, 'Logout', NULL, '2025-11-14 11:31:04', '::1'),
(364, 62, 'Login', NULL, '2025-11-14 11:34:59', NULL),
(365, 7, 'Login', NULL, '2025-12-10 13:57:57', '::1'),
(366, 3, 'Login', NULL, '2025-12-10 13:58:25', '::1'),
(367, 7, 'Login', NULL, '2025-12-10 13:58:57', '::1'),
(368, 3, 'Login', NULL, '2025-12-10 13:59:56', '::1'),
(369, 7, 'Login', NULL, '2025-12-10 14:00:22', '::1'),
(370, 15, 'Login', NULL, '2025-12-10 14:01:56', '::1'),
(371, 7, 'Login', NULL, '2025-12-10 14:03:28', '::1'),
(372, 15, 'Login', NULL, '2025-12-10 14:03:42', '::1'),
(373, 3, 'Login', NULL, '2025-12-10 16:07:42', '::1'),
(374, 16, 'Login', NULL, '2025-12-10 16:08:35', '::1'),
(375, 62, 'Login', NULL, '2025-12-10 16:16:11', NULL),
(376, 15, 'Login', NULL, '2025-12-10 16:27:10', '::1'),
(377, 15, 'Logout', NULL, '2025-12-10 16:39:16', '::1'),
(378, 20, 'Login', NULL, '2025-12-10 16:39:20', '::1'),
(379, 62, 'Login', NULL, '2025-12-10 16:41:45', NULL),
(380, 16, 'Login', NULL, '2025-12-10 16:57:44', '::1'),
(381, 16, 'Logout', NULL, '2025-12-10 16:58:02', '::1'),
(382, 24, 'Login', NULL, '2025-12-10 16:58:10', '::1'),
(383, 62, 'Login', NULL, '2025-12-10 17:07:53', NULL),
(384, 20, 'Login', NULL, '2025-12-10 17:10:47', '::1'),
(385, 20, 'Logout', NULL, '2025-12-10 17:10:57', '::1'),
(386, 24, 'Login', NULL, '2025-12-10 17:11:02', '::1'),
(387, 24, 'Logout', NULL, '2025-12-10 17:18:17', '::1'),
(388, 20, 'Login', NULL, '2025-12-10 17:18:20', '::1'),
(389, 62, 'Login', NULL, '2025-12-10 17:18:46', NULL),
(390, 24, 'Login', NULL, '2025-12-10 17:22:47', '::1'),
(391, 7, 'Login', NULL, '2025-12-10 17:56:38', '::1'),
(392, 3, 'Login', NULL, '2025-12-10 18:10:09', '::1'),
(393, 7, 'Login', NULL, '2025-12-10 18:13:18', '::1'),
(394, 62, 'Login', NULL, '2025-12-10 18:14:12', NULL),
(395, 62, 'Logout', NULL, '2025-12-10 18:14:35', '::1'),
(396, 16, 'Login', NULL, '2025-12-10 18:14:47', '::1'),
(397, 16, 'Logout', NULL, '2025-12-10 18:17:41', '::1'),
(398, 16, 'Login', NULL, '2025-12-10 18:17:50', '::1'),
(399, 62, 'Login', NULL, '2025-12-10 18:18:50', NULL),
(400, 7, 'Login', NULL, '2025-12-10 18:40:24', '::1'),
(401, 62, 'Login', NULL, '2025-12-10 18:40:53', NULL),
(402, 62, 'Reset leaderboard - Type: all, Deleted: 11 sessions', NULL, '2025-12-10 18:41:24', '::1'),
(403, 20, 'Login', NULL, '2025-12-10 18:41:37', '::1'),
(404, 62, 'Login', NULL, '2025-12-10 18:42:00', NULL),
(405, 20, 'Login', NULL, '2025-12-10 18:47:08', '::1'),
(406, 20, 'Logout', NULL, '2025-12-10 18:47:21', '::1'),
(407, 24, 'Login', NULL, '2025-12-10 18:47:26', '::1'),
(408, 62, 'Login', NULL, '2025-12-10 18:47:49', NULL),
(409, 62, 'Changed Game Status to Active (Game ID: 19)', NULL, '2025-12-10 18:47:52', '::1'),
(410, 24, 'Login', NULL, '2025-12-10 18:48:19', '::1'),
(411, 24, 'Logout', NULL, '2025-12-10 18:50:11', '::1'),
(412, 16, 'Login', NULL, '2025-12-10 18:50:15', '::1'),
(413, 16, 'Logout', NULL, '2025-12-10 18:51:52', '::1'),
(414, 15, 'Login', NULL, '2025-12-10 18:51:55', '::1'),
(415, 15, 'Logout', NULL, '2025-12-10 18:53:41', '::1'),
(416, 20, 'Login', NULL, '2025-12-10 18:53:48', '::1'),
(417, 62, 'Login', NULL, '2025-12-10 18:55:46', NULL),
(418, 24, 'Login', NULL, '2025-12-10 18:57:04', '::1'),
(419, 62, 'Login', NULL, '2025-12-10 18:57:17', NULL),
(420, 62, 'Changed Game Status to Active (Game ID: 20)', NULL, '2025-12-10 18:57:21', '::1'),
(421, 24, 'Login', NULL, '2025-12-10 18:57:26', '::1'),
(422, 62, 'Login', NULL, '2025-12-10 19:00:14', NULL),
(423, 20, 'Login', NULL, '2025-12-10 19:13:36', '::1'),
(424, 7, 'Login', NULL, '2025-12-10 19:21:34', '::1'),
(425, 3, 'Login', NULL, '2025-12-10 19:26:40', '::1'),
(426, 7, 'Login', NULL, '2025-12-10 19:27:24', '::1'),
(427, 7, 'Added Teacher', 66, '2025-12-10 19:30:46', NULL),
(428, 7, 'Added Subject', 5, '2025-12-10 19:34:07', NULL),
(429, 7, 'Assigned Subject to Teacher', 66, '2025-12-10 19:35:24', NULL),
(430, 7, 'Logout', NULL, '2025-12-10 19:59:17', '::1'),
(431, 62, 'Login', NULL, '2025-12-10 19:59:43', NULL),
(432, 20, 'Login', NULL, '2025-12-10 20:34:47', '::1'),
(433, 7, 'Login', NULL, '2025-12-10 20:36:05', '::1'),
(434, 60, 'Login', NULL, '2025-12-10 20:36:18', '::1'),
(435, 3, 'Login', NULL, '2025-12-12 03:27:36', '::1'),
(436, 7, 'Login', NULL, '2025-12-12 03:28:06', '::1'),
(437, 62, 'Login', NULL, '2025-12-12 03:28:43', NULL),
(438, 15, 'Login', NULL, '2025-12-12 03:31:51', '::1'),
(439, 7, 'Login', NULL, '2025-12-12 03:33:41', '::1'),
(440, 3, 'Login', NULL, '2025-12-12 03:34:26', '::1'),
(441, 62, 'Login', NULL, '2025-12-12 03:34:52', NULL),
(442, 3, 'Login', NULL, '2025-12-12 03:37:51', '::1'),
(443, 7, 'Login', NULL, '2025-12-12 03:38:24', '::1'),
(444, 62, 'Login', NULL, '2025-12-12 03:38:42', NULL),
(445, 62, 'Deleted Game Activity (ID: 20)', NULL, '2025-12-12 03:39:26', '::1'),
(446, 24, 'Login', NULL, '2025-12-12 03:39:38', '::1'),
(447, 62, 'Login', NULL, '2025-12-12 03:40:05', NULL),
(448, 24, 'Login', NULL, '2025-12-12 03:41:45', '::1'),
(449, 62, 'Login', NULL, '2025-12-12 03:42:40', NULL),
(0, 24, 'Login', NULL, '2025-12-16 02:32:58', '136.158.10.144'),
(0, 62, 'Login', NULL, '2025-12-16 02:33:52', NULL),
(0, 24, 'Login', NULL, '2025-12-16 02:37:00', '136.158.10.144'),
(0, 24, 'Login', NULL, '2025-12-16 02:38:04', '136.158.10.144'),
(0, 62, 'Login', NULL, '2025-12-16 02:38:44', NULL),
(0, 24, 'Login', NULL, '2025-12-16 02:40:08', '136.158.10.144'),
(0, 19, 'Login', NULL, '2025-12-16 03:34:08', '136.158.10.125'),
(0, 62, 'Login', NULL, '2025-12-16 04:17:25', NULL),
(0, 17, 'Login', NULL, '2025-12-16 04:18:38', '136.158.10.125'),
(0, 17, 'Login', NULL, '2025-12-16 04:19:07', '136.158.10.125'),
(0, 17, 'Login', NULL, '2025-12-16 04:20:21', '136.158.10.125'),
(0, 17, 'Login', NULL, '2025-12-16 06:21:10', '136.158.10.125'),
(0, 62, 'Login', NULL, '2025-12-16 06:23:24', NULL),
(0, 17, 'Login', NULL, '2025-12-16 06:24:48', '136.158.10.125'),
(0, 62, 'Login', NULL, '2025-12-16 06:25:08', NULL),
(0, 17, 'Login', NULL, '2025-12-16 06:27:36', '136.158.10.125'),
(0, 24, 'Login', NULL, '2025-12-16 06:30:42', '136.158.10.144'),
(0, 62, 'Login', NULL, '2025-12-16 06:31:19', NULL),
(0, 62, 'Deleted Game Activity (ID: 19)', NULL, '2025-12-16 06:33:29', '136.158.10.144'),
(0, 62, 'Login', NULL, '2025-12-16 06:35:38', NULL),
(0, 62, 'Reset leaderboard - Type: all, Deleted: 25 sessions', NULL, '2025-12-16 06:35:50', '136.158.10.125'),
(0, 17, 'Login', NULL, '2025-12-16 06:35:59', '136.158.10.125'),
(0, 62, 'Login', NULL, '2025-12-16 06:38:29', NULL),
(0, 62, 'Changed Game Status to Active (Game ID: 0)', NULL, '2025-12-16 06:38:43', '136.158.10.125'),
(0, 17, 'Login', NULL, '2025-12-16 06:38:50', '136.158.10.125'),
(0, 62, 'Login', NULL, '2025-12-16 07:39:10', NULL),
(0, 17, 'Login', NULL, '2025-12-16 07:40:44', '136.158.10.125'),
(0, 17, 'Logout', NULL, '2025-12-16 09:25:33', '2405:8d40:4097:326:b134:9c02:aa62:df45'),
(0, 24, 'Login', NULL, '2025-12-16 09:25:39', '2405:8d40:4097:326:b134:9c02:aa62:df45'),
(0, 7, 'Login', NULL, '2025-12-16 10:24:36', '203.160.161.70'),
(0, 4, 'Login', NULL, '2025-12-16 10:24:41', '203.160.161.70'),
(0, 62, 'Login', NULL, '2025-12-16 10:25:21', NULL),
(0, 7, 'Login', NULL, '2025-12-16 10:26:47', '203.160.161.70'),
(0, 4, 'Login', NULL, '2025-12-16 10:37:23', '203.160.161.70'),
(0, 62, 'Login', NULL, '2025-12-16 10:42:06', NULL),
(0, 17, 'Login', NULL, '2025-12-16 10:42:29', '203.160.161.70');

-- --------------------------------------------------------

--
-- Table structure for table `v_login_statistics`
--

CREATE TABLE `v_login_statistics` (
  `login_date` date DEFAULT NULL,
  `total_attempts` bigint(21) DEFAULT NULL,
  `successful_logins` decimal(22,0) DEFAULT NULL,
  `failed_attempts` decimal(22,0) DEFAULT NULL,
  `unique_users` bigint(21) DEFAULT NULL,
  `unique_ips` bigint(21) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v_student_accounts`
--

CREATE TABLE `v_student_accounts` (
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `full_name` varchar(101) DEFAULT NULL,
  `grade_level` varchar(2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by_staff_id` int(11) DEFAULT NULL,
  `created_by_staff_username` varchar(50) DEFAULT NULL,
  `created_by_staff_name` varchar(101) DEFAULT NULL,
  `creation_method` enum('manual','bulk_upload') DEFAULT NULL,
  `creation_timestamp` timestamp NULL DEFAULT NULL,
  `total_logins` bigint(21) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v_student_activity_scores`
--

CREATE TABLE `v_student_activity_scores` (
  `submission_id` int(11) DEFAULT NULL,
  `activity_id` int(11) DEFAULT NULL,
  `activity_title` varchar(200) DEFAULT NULL,
  `activity_type` enum('quiz','assignment','recitation','exam') DEFAULT NULL,
  `student_name` varchar(101) DEFAULT NULL,
  `grade_level` varchar(2) DEFAULT NULL,
  `subject_name` varchar(100) DEFAULT NULL,
  `total_score` decimal(5,2) DEFAULT NULL,
  `max_score` decimal(5,2) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `submission_status` enum('not_started','in_progress','submitted','graded') DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `teacher_name` varchar(101) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
