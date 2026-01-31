-- HR1 Module: Role-Based Access Control (RBAC) Schema
-- Slate Freight Management System
-- Version: 1.0

-- Select the database first
USE `hr1_hr1data`;

-- Disable foreign key checks FIRST (before anything else)
SET FOREIGN_KEY_CHECKS = 0;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- DROP existing tables to avoid structure conflicts
-- WARNING: This will delete existing data in these tables!
-- Must drop tables with FK references first (in reverse dependency order)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `employee_onboarding_progress`;
DROP TABLE IF EXISTS `performance_reviews`;
DROP TABLE IF EXISTS `employee_documents`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `onboarding_tasks`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `user_accounts`;
DROP TABLE IF EXISTS `reward_redemptions`;
DROP TABLE IF EXISTS `rewards_catalog`;
DROP TABLE IF EXISTS `recognition_awards`;
DROP TABLE IF EXISTS `registered_employees`;
DROP TABLE IF EXISTS `performance_goals`;
DROP TABLE IF EXISTS `screening_responses`;
DROP TABLE IF EXISTS `screening_questions`;
DROP TABLE IF EXISTS `screening_assessments`;
DROP TABLE IF EXISTS `interviews`;
DROP TABLE IF EXISTS `job_applications`;
DROP TABLE IF EXISTS `job_postings`;
DROP TABLE IF EXISTS `task_templates`;
DROP TABLE IF EXISTS `orientation_schedules`;
DROP TABLE IF EXISTS `applicants`;
DROP TABLE IF EXISTS `employees`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `roles`;

START TRANSACTION;

-- --------------------------------------------------------
-- Table: roles - Define system roles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `role_name` VARCHAR(50) NOT NULL UNIQUE,
    `role_type` ENUM('Admin', 'HR_Staff', 'Manager', 'Employee', 'Applicant') NOT NULL,
    `description` TEXT,
    `access_level` ENUM('full', 'functional', 'department', 'self', 'external') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default roles (IGNORE duplicates)
INSERT IGNORE INTO `roles` (`role_name`, `role_type`, `description`, `access_level`) VALUES
('System Administrator', 'Admin', 'Full system access - configure settings, user management, audit logs', 'full'),
('HR Staff', 'HR_Staff', 'Functional access - applicant management, onboarding, job postings', 'functional'),
('HR Manager', 'HR_Staff', 'HR department head with approval rights', 'functional'),
('Fleet Manager', 'Manager', 'Department-specific access for Fleet Operations', 'department'),
('Warehouse Manager', 'Manager', 'Department-specific access for Warehouse Operations', 'department'),
('Logistics Manager', 'Manager', 'Department-specific access for Logistics', 'department'),
('Employee', 'Employee', 'Self-service access - own data only', 'self'),
('New Hire', 'Employee', 'Limited employee access during onboarding', 'self'),
('Applicant', 'Applicant', 'External portal access - apply for jobs', 'external');

-- --------------------------------------------------------
-- Table: departments - Company departments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `department_code` VARCHAR(20) NOT NULL UNIQUE,
    `department_name` VARCHAR(100) NOT NULL,
    `parent_department_id` INT NULL,
    `manager_id` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`parent_department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default departments for Freight (IGNORE duplicates)
INSERT IGNORE INTO `departments` (`department_code`, `department_name`) VALUES
('HR', 'Human Resources'),
('FLEET', 'Fleet Operations'),
('LOGISTICS', 'Logistics'),
('WAREHOUSE', 'Warehouse'),
('FINANCE', 'Finance'),
('IT', 'Information Technology'),
('DISPATCH', 'Dispatch Center'),
('MAINTENANCE', 'Vehicle Maintenance');

-- --------------------------------------------------------
-- Table: user_accounts - Enhanced user table with company email
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_accounts` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `employee_id` VARCHAR(20) UNIQUE,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `middle_name` VARCHAR(50),
    `personal_email` VARCHAR(100),
    `company_email` VARCHAR(100) UNIQUE,
    `phone` VARCHAR(20),
    `password_hash` VARCHAR(255),
    `role_id` INT,
    `department_id` INT,
    `job_title` VARCHAR(100),
    `hire_date` DATE,
    `employment_status` ENUM('Active', 'Probation', 'On Leave', 'Terminated', 'Resigned') DEFAULT 'Active',
    `profile_picture` VARCHAR(255),
    `last_login` TIMESTAMP NULL,
    `status` ENUM('Active', 'Inactive', 'Pending') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`),
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: role_permissions - Define what each role can do
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `role_id` INT NOT NULL,
    `module` VARCHAR(100) NOT NULL,
    `can_view` TINYINT(1) DEFAULT 0,
    `can_create` TINYINT(1) DEFAULT 0,
    `can_edit` TINYINT(1) DEFAULT 0,
    `can_delete` TINYINT(1) DEFAULT 0,
    `can_approve` TINYINT(1) DEFAULT 0,
    `scope` ENUM('all', 'department', 'own') DEFAULT 'own',
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_role_module` (`role_id`, `module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert permissions based on RBAC Matrix
-- Admin permissions (Full Access)
INSERT IGNORE INTO `role_permissions` (`role_id`, `module`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_approve`, `scope`) VALUES
(1, 'recruitment_settings', 1, 1, 1, 1, 1, 'all'),
(1, 'job_requisition', 1, 1, 1, 1, 1, 'all'),
(1, 'applicant_profiles', 1, 1, 1, 1, 1, 'all'),
(1, 'onboarding_checklist', 1, 1, 1, 1, 1, 'all'),
(1, 'employee_documents', 1, 1, 1, 1, 1, 'all'),
(1, 'user_management', 1, 1, 1, 1, 1, 'all'),
(1, 'audit_logs', 1, 0, 0, 0, 0, 'all'),
(1, 'system_settings', 1, 1, 1, 1, 1, 'all');

-- HR Staff permissions (Functional Access)
INSERT IGNORE INTO `role_permissions` (`role_id`, `module`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_approve`, `scope`) VALUES
(2, 'recruitment_settings', 1, 0, 0, 0, 0, 'all'),
(2, 'job_requisition', 1, 1, 1, 0, 1, 'all'),
(2, 'applicant_profiles', 1, 1, 1, 0, 1, 'all'),
(2, 'onboarding_checklist', 1, 1, 1, 1, 1, 'all'),
(2, 'employee_documents', 1, 1, 1, 0, 1, 'all'),
(2, 'user_management', 1, 0, 0, 0, 0, 'all');

-- Manager permissions (Department Access)
INSERT IGNORE INTO `role_permissions` (`role_id`, `module`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_approve`, `scope`) VALUES
(4, 'recruitment_settings', 0, 0, 0, 0, 0, 'department'),
(4, 'job_requisition', 1, 1, 0, 0, 0, 'department'),
(4, 'applicant_profiles', 1, 0, 0, 0, 0, 'department'),
(4, 'onboarding_checklist', 1, 0, 0, 0, 0, 'department'),
(4, 'employee_documents', 1, 0, 0, 0, 0, 'department'),
(4, 'performance_reviews', 1, 1, 1, 0, 0, 'department');

-- Employee permissions (Self Access)
INSERT IGNORE INTO `role_permissions` (`role_id`, `module`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_approve`, `scope`) VALUES
(7, 'onboarding_checklist', 1, 0, 1, 0, 0, 'own'),
(7, 'employee_documents', 1, 1, 0, 0, 0, 'own'),
(7, 'personal_info', 1, 0, 1, 0, 0, 'own');

-- --------------------------------------------------------
-- Table: employee_documents - Track employee documents/licenses
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `employee_documents` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `document_type` ENUM('License', 'Medical Certificate', 'NBI Clearance', '201 File', 'Contract', 'Resume', 'ID', 'Certificate', 'Other') NOT NULL,
    `document_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500),
    `issue_date` DATE,
    `expiry_date` DATE,
    `status` ENUM('Valid', 'Expiring Soon', 'Expired', 'Pending Verification', 'Rejected') DEFAULT 'Pending Verification',
    `verified_by` INT,
    `verified_at` TIMESTAMP NULL,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `user_accounts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`verified_by`) REFERENCES `user_accounts`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: audit_logs - Track all system actions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT,
    `user_email` VARCHAR(100),
    `action` ENUM('VIEW', 'CREATE', 'EDIT', 'DELETE', 'LOGIN', 'LOGOUT', 'APPROVE', 'REJECT', 'HIRE', 'SYSTEM') NOT NULL,
    `module` VARCHAR(100),
    `record_id` INT,
    `record_type` VARCHAR(50),
    `old_values` JSON,
    `new_values` JSON,
    `detail` TEXT,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: onboarding_tasks - Track new hire onboarding
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `onboarding_tasks` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `task_name` VARCHAR(255) NOT NULL,
    `task_description` TEXT,
    `category` ENUM('Documents', 'Training', 'IT Setup', 'Orientation', 'Compliance', 'Other') NOT NULL,
    `is_required` TINYINT(1) DEFAULT 1,
    `days_to_complete` INT DEFAULT 7,
    `department_specific` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`department_specific`) REFERENCES `departments`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default onboarding tasks (IGNORE duplicates)
INSERT IGNORE INTO `onboarding_tasks` (`task_name`, `task_description`, `category`, `is_required`, `days_to_complete`) VALUES
('Submit NBI Clearance', 'Upload your NBI Clearance document', 'Documents', 1, 7),
('Submit Medical Certificate', 'Upload your medical certificate from accredited clinic', 'Documents', 1, 7),
('Submit SSS/PhilHealth/Pag-IBIG IDs', 'Upload government ID documents', 'Documents', 1, 14),
('Complete Company Orientation', 'Attend the company orientation session', 'Orientation', 1, 3),
('Read Employee Handbook', 'Read and acknowledge the employee handbook', 'Compliance', 1, 5),
('IT Account Setup', 'Setup your company email and system access', 'IT Setup', 1, 1),
('Safety Training', 'Complete mandatory safety training', 'Training', 1, 7),
('Submit Driver License', 'Upload valid professional driver license (for drivers only)', 'Documents', 1, 3);

-- --------------------------------------------------------
-- Table: employee_onboarding_progress - Track individual progress
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `employee_onboarding_progress` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `task_id` INT NOT NULL,
    `status` ENUM('Pending', 'In Progress', 'Completed', 'Overdue') DEFAULT 'Pending',
    `completed_at` TIMESTAMP NULL,
    `verified_by` INT,
    `notes` TEXT,
    `file_path` VARCHAR(500),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `user_accounts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`task_id`) REFERENCES `onboarding_tasks`(`id`),
    FOREIGN KEY (`verified_by`) REFERENCES `user_accounts`(`id`),
    UNIQUE KEY `unique_user_task` (`user_id`, `task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: job_postings - For recruitment and job posting management
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `job_postings` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `department_id` INT,
    `employment_type` ENUM('Full-time', 'Part-time', 'Contract', 'Temporary') DEFAULT 'Full-time',
    `salary_min` DECIMAL(10,2),
    `salary_max` DECIMAL(10,2),
    `location` VARCHAR(255),
    `requirements` TEXT,
    `responsibilities` TEXT,
    `status` ENUM('Draft', 'Open', 'Closed', 'On Hold') DEFAULT 'Open',
    `created_by` INT,
    `posted_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `closing_date` DATE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`),
    FOREIGN KEY (`created_by`) REFERENCES `user_accounts`(`id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_department` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: job_applications - Track applicant submissions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `job_applications` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `job_posting_id` INT,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `middle_name` VARCHAR(100),
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20),
    `address` TEXT,
    `resume_path` VARCHAR(500),
    `cover_letter` TEXT,
    `status` ENUM('New', 'Review', 'Screening', 'Interview', 'For Interview', 'Road_Test', 'Testing', 'Offer', 'Offer_Sent', 'Hired', 'Rejected', 'Withdrawn') DEFAULT 'New',
    `applied_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `interview_date` DATETIME,
    `interview_notes` TEXT,
    `hr_notes` TEXT,
    `rating` TINYINT CHECK (rating BETWEEN 1 AND 5),
    `processed_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`job_posting_id`) REFERENCES `job_postings`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`processed_by`) REFERENCES `user_accounts`(`id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_job_posting` (`job_posting_id`),
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: interviews - Track interview schedules and results
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `interviews` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `application_id` INT NOT NULL,
    `interview_type` ENUM('Phone Screen', 'Technical', 'HR', 'Manager', 'Road Test', 'Final') NOT NULL,
    `scheduled_date` DATETIME NOT NULL,
    `interviewer_id` INT,
    `location` VARCHAR(255),
    `status` ENUM('Scheduled', 'Completed', 'Cancelled', 'No Show') DEFAULT 'Scheduled',
    `notes` TEXT,
    `rating` TINYINT CHECK (rating BETWEEN 1 AND 5),
    `recommendation` ENUM('Proceed', 'Reject', 'On Hold') NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`application_id`) REFERENCES `job_applications`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`interviewer_id`) REFERENCES `user_accounts`(`id`),
    INDEX `idx_scheduled_date` (`scheduled_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: performance_reviews - For probation/initial performance
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `performance_reviews` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `employee_id` INT NOT NULL,
    `reviewer_id` INT NOT NULL,
    `review_type` ENUM('Probation', 'Quarterly', 'Annual', 'Project') NOT NULL,
    `review_period_start` DATE,
    `review_period_end` DATE,
    `safety_score` TINYINT CHECK (safety_score BETWEEN 1 AND 5),
    `punctuality_score` TINYINT CHECK (punctuality_score BETWEEN 1 AND 5),
    `quality_score` TINYINT CHECK (quality_score BETWEEN 1 AND 5),
    `teamwork_score` TINYINT CHECK (teamwork_score BETWEEN 1 AND 5),
    `overall_score` DECIMAL(3,2),
    `recommendation` ENUM('Pass Probation', 'Extend Probation', 'Terminate', 'Promote', 'Maintain') NULL,
    `comments` TEXT,
    `status` ENUM('Draft', 'Submitted', 'HR Review', 'Approved', 'Rejected') DEFAULT 'Draft',
    `approved_by` INT,
    `approved_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`employee_id`) REFERENCES `user_accounts`(`id`),
    FOREIGN KEY (`reviewer_id`) REFERENCES `user_accounts`(`id`),
    FOREIGN KEY (`approved_by`) REFERENCES `user_accounts`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

COMMIT;
