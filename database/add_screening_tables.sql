-- Migration: Add screening questions and assessments for job postings
-- Run this SQL in phpMyAdmin to add screening functionality

-- Use existing database
USE `hr1_hr1data`;

-- --------------------------------------------------------
-- Create screening_questions table
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `screening_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_posting_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('text', 'multiple_choice', 'yes_no', 'rating') DEFAULT 'text',
  `required` tinyint(1) DEFAULT 1,
  `options` text DEFAULT NULL COMMENT 'JSON array for multiple choice options',
  `correct_answer` varchar(255) DEFAULT NULL COMMENT 'For assessment questions',
  `points` int(11) DEFAULT 0 COMMENT 'Points for correct answer',
  `order_number` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `job_posting_id` (`job_posting_id`),
  CONSTRAINT `fk_screening_job_posting` FOREIGN KEY (`job_posting_id`) REFERENCES `job_postings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Create screening_responses table
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `screening_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `response_text` text DEFAULT NULL,
  `score` int(11) DEFAULT 0,
  `is_correct` tinyint(1) DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `fk_response_application` FOREIGN KEY (`application_id`) REFERENCES `job_applications`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_response_question` FOREIGN KEY (`question_id`) REFERENCES `screening_questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Create screening_assessments table (for overall assessment tracking)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `screening_assessments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `total_score` int(11) DEFAULT 0,
  `max_score` int(11) DEFAULT 0,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `pass_status` enum('pending', 'passed', 'failed') DEFAULT 'pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `application_id` (`application_id`),
  CONSTRAINT `fk_assessment_application` FOREIGN KEY (`application_id`) REFERENCES `job_applications`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Verification: Check if tables were created
-- --------------------------------------------------------

SELECT 'Screening tables created successfully!' AS status;
