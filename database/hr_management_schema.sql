-- HR Management System Database Schema
-- Based on existing hr1_hr1data database
-- Run this in phpMyAdmin to create all necessary tables

-- Use existing database
USE `hr1_hr1data`;

-- --------------------------------------------------------
-- Table structure for table `departments`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `manager_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Applicant Management','Recruitment Management','New Hire Onboarding','Performance Management (Initial)','Social Recognition','Competency Management','admin_Human Resource 1','Learning Management','Training Management','Succession Planning','Employee Self-Service (ESS)','admin_Human Resource 2','Time and Attendance System','Shift and Schedule Management','Timesheet Management','Leave Management','Claims and Reimbursement','admin_Human Resource 3','Core Human Capital Management (HCM)','Payroll Management','Compensation Planning','HR Analytics Dashboard','HMO & Benefits Administration','admin_Human Resource 4','Shipment Booking & Routing System','Consolidation & Deconsolidation Management','House & Master Bill of Lading Generator','Shipment File & Tracking System','Purchase Order Integration System','Service Provider Management','admin_Core Transaction 1','Service Network & Route Planner','Rate & Tariff Management System','Standard Operating Procedure (SOP) Manager','Scheduler & Transit Timetable Management','admin_Core Transaction 2','Customer Relationship Management (CRM)','Contract & SLA Monitoring','E-Documentation & Compliance Manager','Business Intelligence & Freight Analytics','Customer Portal & Notification Hub','admin_Core Transaction 3','Smart Warehousing System (SWS)','Procurement & Sourcing Management (PSM)','Project Logistics Tracker (PLT)','Asset Lifecycle & Maintenance (ALMS)','Document Tracking & Logistics Records (DTRS)','admin_Logistics 1','Fleet & Vehicle Management (FVM)','Vehicle Reservation & Dispatch System (VRDS)','Driver and Trip Performance Monitoring','Transport Cost Analysis & Optimization (TCAO)','Mobile Fleet Command App (optional)','admin_Logistics 2','Disbursement','Budget Management','Collection','General Ledger','Accounts Payable / Accounts Receivables','admin_Financials') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `employees`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `employee_id` varchar(20) UNIQUE NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) UNIQUE NOT NULL,
  `phone` varchar(20),
  `department_id` int(11),
  `position` varchar(100),
  `hire_date` date,
  `salary` decimal(10,2),
  `status` enum('active', 'inactive', 'terminated') DEFAULT 'active',
  `manager_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`manager_id`) REFERENCES `employees`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `job_postings`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `job_postings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `department_id` int(11) NOT NULL,
  `location` varchar(100) NOT NULL,
  `employment_type` enum('Full-time', 'Part-time', 'Contract', 'Internship', 'Remote') NOT NULL,
  `salary_min` decimal(10,2),
  `salary_max` decimal(10,2),
  `description` text NOT NULL,
  `requirements` text NOT NULL,
  `status` enum('draft', 'active', 'closed', 'cancelled') DEFAULT 'active',
  `posted_by` int(11) NOT NULL,
  `posted_date` timestamp NULL DEFAULT current_timestamp(),
  `closing_date` date,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`posted_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `job_applications`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `job_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_posting_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20),
  `resume_path` varchar(255),
  `cover_letter` text,
  `status` enum('new', 'reviewed', 'screening', 'interview', 'offer', 'hired', 'rejected') DEFAULT 'new',
  `applied_date` timestamp NULL DEFAULT current_timestamp(),
  `notes` text,
  `reviewed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`job_posting_id`) REFERENCES `job_postings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `interviews`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `interviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `interviewer_id` int(11) NOT NULL,
  `interview_type` enum('phone', 'video', 'in-person', 'technical', 'final') NOT NULL,
  `scheduled_date` datetime NOT NULL,
  `duration_minutes` int(11) DEFAULT 60,
  `location` varchar(200),
  `meeting_link` varchar(255),
  `status` enum('scheduled', 'completed', 'cancelled', 'rescheduled') DEFAULT 'scheduled',
  `feedback` text,
  `rating` tinyint(1) CHECK (rating >= 1 AND rating <= 5),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`application_id`) REFERENCES `job_applications`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`interviewer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `onboarding_tasks`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `onboarding_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `task_name` varchar(200) NOT NULL,
  `description` text,
  `assigned_to` int(11),
  `due_date` date,
  `status` enum('pending', 'in_progress', 'completed', 'overdue') DEFAULT 'pending',
  `completed_date` timestamp NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `performance_goals`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `performance_goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text,
  `category` enum('performance', 'development', 'leadership', 'innovation') NOT NULL,
  `priority` enum('high', 'medium', 'low') DEFAULT 'medium',
  `start_date` date NOT NULL,
  `target_date` date NOT NULL,
  `progress_percentage` tinyint(3) DEFAULT 0 CHECK (progress_percentage >= 0 AND progress_percentage <= 100),
  `status` enum('active', 'completed', 'cancelled', 'overdue') DEFAULT 'active',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `performance_reviews`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `performance_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `review_period_start` date NOT NULL,
  `review_period_end` date NOT NULL,
  `review_type` enum('annual', 'mid-year', '90-day', 'project-based') NOT NULL,
  `overall_rating` tinyint(1) CHECK (overall_rating >= 1 AND overall_rating <= 5),
  `goals_achievement` text,
  `strengths` text,
  `areas_for_improvement` text,
  `development_plan` text,
  `status` enum('draft', 'submitted', 'approved', 'completed') DEFAULT 'draft',
  `due_date` date,
  `completed_date` timestamp NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reviewer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `recognition_awards`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `recognition_awards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_id` int(11) NOT NULL,
  `nominator_id` int(11) NOT NULL,
  `recognition_type` enum('teamwork', 'innovation', 'leadership', 'excellence') NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `points_awarded` int(11) DEFAULT 0,
  `is_public` boolean DEFAULT true,
  `status` enum('pending', 'approved', 'rejected') DEFAULT 'approved',
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`recipient_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`nominator_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `rewards_catalog`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `rewards_catalog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `description` text,
  `points_required` int(11) NOT NULL,
  `category` enum('gift_card', 'pto', 'experience', 'training', 'merchandise') NOT NULL,
  `is_active` boolean DEFAULT true,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `reward_redemptions`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `reward_redemptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `reward_id` int(11) NOT NULL,
  `points_used` int(11) NOT NULL,
  `status` enum('pending', 'approved', 'delivered', 'cancelled') DEFAULT 'pending',
  `requested_date` timestamp NULL DEFAULT current_timestamp(),
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` timestamp NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reward_id`) REFERENCES `rewards_catalog`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Insert sample users
-- --------------------------------------------------------

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$12$.j.7vsnJ5mPvDUUNkQS6puP9Jm/xtib8q5t99aOp.JWMAKSI2rFx.', 'admin_Human Resource 1', '2025-09-09 08:54:34'),
(2, 'hr_manager', '$2y$12$DknfgcIeCp8jV97DK3dfCuIVeGgDG5Bfj1Vx0loulO1tbkNop6CTG', 'Recruitment Management', '2025-09-08 17:03:20'),
(3, 'performance_mgr', '$2y$12$9py/Bu0YTGWQenvNPSYOWOFlPvGdHS67coP2oLWg5KTzRId2naqRq', 'Performance Management (Initial)', '2025-09-08 17:03:36');

-- --------------------------------------------------------
-- Insert sample departments
-- --------------------------------------------------------

INSERT INTO `departments` (`name`, `description`) VALUES
('Engineering', 'Software development and technical teams'),
('Marketing', 'Marketing, communications, and brand management'),
('Sales', 'Sales and business development'),
('Human Resources', 'HR operations and people management'),
('Design', 'UX/UI design and creative teams'),
('Finance', 'Accounting, finance, and business operations');

-- --------------------------------------------------------
-- Insert sample rewards
-- --------------------------------------------------------

INSERT INTO `rewards_catalog` (`name`, `description`, `points_required`, `category`) VALUES
('$50 Amazon Gift Card', 'Amazon gift card for online shopping', 500, 'gift_card'),
('Extra PTO Day', 'Additional paid time off day', 1000, 'pto'),
('Team Lunch', 'Lunch for your team (up to 8 people)', 750, 'experience'),
('Professional Training Course', 'Access to professional development courses', 1500, 'training'),
('$25 Coffee Shop Gift Card', 'Gift card for local coffee shops', 250, 'gift_card'),
('Work From Home Day', 'Additional work from home day', 300, 'pto'),
('Company Merchandise Package', 'Branded company swag package', 400, 'merchandise');

-- --------------------------------------------------------
-- Create indexes for better performance
-- --------------------------------------------------------

CREATE INDEX idx_job_postings_status ON job_postings(status);
CREATE INDEX idx_job_postings_department ON job_postings(department_id);
CREATE INDEX idx_job_applications_status ON job_applications(status);
CREATE INDEX idx_job_applications_job ON job_applications(job_posting_id);
CREATE INDEX idx_employees_department ON employees(department_id);
CREATE INDEX idx_employees_status ON employees(status);
CREATE INDEX idx_performance_goals_employee ON performance_goals(employee_id);
CREATE INDEX idx_recognition_awards_recipient ON recognition_awards(recipient_id);
CREATE INDEX idx_onboarding_tasks_employee ON onboarding_tasks(employee_id);

COMMIT;
