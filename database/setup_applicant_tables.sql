-- Setup script for Applicant Registration Tables
-- Run this script to ensure all required tables exist for applicant portal

USE `hr1_hr1data`;

-- Ensure Applicant role exists
INSERT IGNORE INTO `roles` (`role_name`, `role_type`, `description`, `access_level`) 
VALUES ('Applicant', 'Applicant', 'External portal access - apply for jobs', 'external');

-- Create applicant_profiles table if not exists
CREATE TABLE IF NOT EXISTS `applicant_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `resume_path` varchar(255) DEFAULT NULL,
  `cover_letter` text DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `education_level` varchar(100) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `portfolio_url` varchar(255) DEFAULT NULL,
  `availability` varchar(50) DEFAULT 'Immediate',
  `expected_salary` varchar(100) DEFAULT NULL,
  `preferred_location` varchar(255) DEFAULT NULL,
  `work_authorization` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure job_applications table has correct structure
CREATE TABLE IF NOT EXISTS `job_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_posting_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `resume_path` varchar(255) DEFAULT NULL,
  `cover_letter` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'new',
  `applied_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Verify Applicant role exists and get its ID
SELECT id, role_name, role_type FROM roles WHERE role_type = 'Applicant';
