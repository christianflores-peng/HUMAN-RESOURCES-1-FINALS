-- Migration: Add Applicant role and create applicants table
-- Run this SQL in phpMyAdmin to add support for applicant and employee registration

-- Use existing database
USE `hr1_hr1data`;

-- --------------------------------------------------------
-- Step 1: Modify users table to include Applicant role
-- --------------------------------------------------------

ALTER TABLE `users` 
MODIFY COLUMN `role` enum('Administrator','HR Manager','Recruiter','Employee','Manager','Supervisor','Applicant','Service Provider','Supplier') 
NOT NULL DEFAULT 'Employee';

-- --------------------------------------------------------
-- Step 2: Create applicants table for storing applicant details
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `applicants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `verification_doc` varchar(255) DEFAULT NULL,
  `status` enum('pending', 'verified', 'rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_applicants_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Step 3: Create registered_employees table for employee registration
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `registered_employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `verification_doc` varchar(255) DEFAULT NULL,
  `status` enum('pending', 'approved', 'rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_registered_employees_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Verification: Check if tables were created
-- --------------------------------------------------------

SELECT 'Tables created successfully!' AS status;
