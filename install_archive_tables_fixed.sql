-- Student Archive Tables (Fixed)
-- Run this script to set up the archive feature

-- Table to store archive batches
CREATE TABLE IF NOT EXISTS `student_archives` (
  `archive_id` int(11) NOT NULL AUTO_INCREMENT,
  `school_year` varchar(50) NOT NULL,
  `archive_status` enum('pending', 'completed', 'failed') DEFAULT 'pending',
  `archived_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `archived_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`archive_id`),
  KEY `archived_by` (`archived_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table to store individual student archive records
CREATE TABLE IF NOT EXISTS `student_archive_records` (
  `record_id` int(11) NOT NULL AUTO_INCREMENT,
  `archive_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `grade_level` varchar(2) NOT NULL,
  `section` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `action` enum('promoted', 'graduated') DEFAULT 'promoted',
  `archived_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`record_id`),
  KEY `archive_id` (`archive_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key for archive_records to archives
ALTER TABLE `student_archive_records` 
ADD CONSTRAINT `student_archive_records_ibfk_1` 
FOREIGN KEY (`archive_id`) REFERENCES `student_archives` (`archive_id`) 
ON DELETE CASCADE;

-- Add foreign key for archives to users (if users table exists)
ALTER TABLE `student_archives` 
ADD CONSTRAINT `student_archives_ibfk_1` 
FOREIGN KEY (`archived_by`) REFERENCES `users` (`user_id`) 
ON DELETE SET NULL;
