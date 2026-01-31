-- Update job_applications table to support complete workflow
-- Add new columns for tracking application workflow stages

ALTER TABLE `job_applications` 
MODIFY COLUMN `status` ENUM(
    'new', 
    'screening', 
    'interview', 
    'road_test', 
    'offer_sent', 
    'hired', 
    'rejected', 
    'withdrawn'
) DEFAULT 'new';

-- Add interview details columns
ALTER TABLE `job_applications` 
ADD COLUMN IF NOT EXISTS `interview_type` ENUM('face_to_face', 'online', 'phone') DEFAULT NULL AFTER `status`,
ADD COLUMN IF NOT EXISTS `interview_date` DATETIME DEFAULT NULL AFTER `interview_type`,
ADD COLUMN IF NOT EXISTS `interview_location` VARCHAR(255) DEFAULT NULL AFTER `interview_date`,
ADD COLUMN IF NOT EXISTS `interview_notes` TEXT DEFAULT NULL AFTER `interview_location`,
ADD COLUMN IF NOT EXISTS `interview_status` ENUM('scheduled', 'completed', 'cancelled', 'rescheduled') DEFAULT NULL AFTER `interview_notes`;

-- Add road test details columns
ALTER TABLE `job_applications`
ADD COLUMN IF NOT EXISTS `road_test_date` DATETIME DEFAULT NULL AFTER `interview_status`,
ADD COLUMN IF NOT EXISTS `road_test_location` VARCHAR(255) DEFAULT NULL AFTER `road_test_date`,
ADD COLUMN IF NOT EXISTS `road_test_notes` TEXT DEFAULT NULL AFTER `road_test_location`,
ADD COLUMN IF NOT EXISTS `road_test_result` ENUM('passed', 'failed', 'pending') DEFAULT NULL AFTER `road_test_notes`,
ADD COLUMN IF NOT EXISTS `drivers_license_verified` TINYINT(1) DEFAULT 0 AFTER `road_test_result`;

-- Add offer details columns
ALTER TABLE `job_applications`
ADD COLUMN IF NOT EXISTS `offer_sent_date` DATETIME DEFAULT NULL AFTER `drivers_license_verified`,
ADD COLUMN IF NOT EXISTS `offer_salary` DECIMAL(10,2) DEFAULT NULL AFTER `offer_sent_date`,
ADD COLUMN IF NOT EXISTS `offer_start_date` DATE DEFAULT NULL AFTER `offer_salary`,
ADD COLUMN IF NOT EXISTS `offer_accepted` TINYINT(1) DEFAULT NULL AFTER `offer_start_date`,
ADD COLUMN IF NOT EXISTS `offer_accepted_date` DATETIME DEFAULT NULL AFTER `offer_accepted`,
ADD COLUMN IF NOT EXISTS `offer_notes` TEXT DEFAULT NULL AFTER `offer_accepted_date`;

-- Add hired details columns
ALTER TABLE `job_applications`
ADD COLUMN IF NOT EXISTS `hired_date` DATE DEFAULT NULL AFTER `offer_notes`,
ADD COLUMN IF NOT EXISTS `hired_by` INT DEFAULT NULL AFTER `hired_date`,
ADD COLUMN IF NOT EXISTS `employee_id_assigned` VARCHAR(20) DEFAULT NULL AFTER `hired_by`;

-- Add rejection details
ALTER TABLE `job_applications`
ADD COLUMN IF NOT EXISTS `rejected_date` DATETIME DEFAULT NULL AFTER `employee_id_assigned`,
ADD COLUMN IF NOT EXISTS `rejected_reason` TEXT DEFAULT NULL AFTER `rejected_date`,
ADD COLUMN IF NOT EXISTS `rejected_by` INT DEFAULT NULL AFTER `rejected_reason`;

-- Add status change tracking
ALTER TABLE `job_applications`
ADD COLUMN IF NOT EXISTS `status_updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `rejected_by`,
ADD COLUMN IF NOT EXISTS `status_updated_by` INT DEFAULT NULL AFTER `status_updated_at`;

-- Add foreign keys
ALTER TABLE `job_applications`
ADD CONSTRAINT `fk_hired_by` FOREIGN KEY (`hired_by`) REFERENCES `user_accounts`(`id`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_rejected_by` FOREIGN KEY (`rejected_by`) REFERENCES `user_accounts`(`id`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_status_updated_by` FOREIGN KEY (`status_updated_by`) REFERENCES `user_accounts`(`id`) ON DELETE SET NULL;

-- Create application_status_history table for tracking all status changes
CREATE TABLE IF NOT EXISTS `application_status_history` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `application_id` INT NOT NULL,
    `old_status` VARCHAR(50),
    `new_status` VARCHAR(50) NOT NULL,
    `changed_by` INT NOT NULL,
    `changed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT,
    FOREIGN KEY (`application_id`) REFERENCES `job_applications`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`changed_by`) REFERENCES `user_accounts`(`id`) ON DELETE CASCADE,
    INDEX `idx_application_id` (`application_id`),
    INDEX `idx_changed_at` (`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create applicant_notifications table
CREATE TABLE IF NOT EXISTS `applicant_notifications` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `application_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `notification_type` ENUM('status_change', 'interview_scheduled', 'road_test_scheduled', 'offer_sent', 'hired', 'rejected', 'general') NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `read_at` TIMESTAMP NULL,
    FOREIGN KEY (`application_id`) REFERENCES `job_applications`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `user_accounts`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_is_read` (`is_read`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create interview_schedules table for detailed interview management
CREATE TABLE IF NOT EXISTS `interview_schedules` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `application_id` INT NOT NULL,
    `interview_type` ENUM('face_to_face', 'online', 'phone') NOT NULL,
    `scheduled_date` DATETIME NOT NULL,
    `location` VARCHAR(255),
    `meeting_link` VARCHAR(500),
    `interviewer_id` INT,
    `interviewer_notes` TEXT,
    `applicant_notes` TEXT,
    `status` ENUM('scheduled', 'completed', 'cancelled', 'rescheduled', 'no_show') DEFAULT 'scheduled',
    `result` ENUM('passed', 'failed', 'pending') DEFAULT 'pending',
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`application_id`) REFERENCES `job_applications`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`interviewer_id`) REFERENCES `user_accounts`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `user_accounts`(`id`) ON DELETE CASCADE,
    INDEX `idx_application_id` (`application_id`),
    INDEX `idx_scheduled_date` (`scheduled_date`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create road_test_schedules table
CREATE TABLE IF NOT EXISTS `road_test_schedules` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `application_id` INT NOT NULL,
    `scheduled_date` DATETIME NOT NULL,
    `location` VARCHAR(255) NOT NULL,
    `venue_details` TEXT,
    `examiner_id` INT,
    `examiner_notes` TEXT,
    `license_number` VARCHAR(50),
    `license_expiry` DATE,
    `license_verified` TINYINT(1) DEFAULT 0,
    `test_result` ENUM('passed', 'failed', 'pending', 'no_show') DEFAULT 'pending',
    `result_notes` TEXT,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`application_id`) REFERENCES `job_applications`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`examiner_id`) REFERENCES `user_accounts`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `user_accounts`(`id`) ON DELETE CASCADE,
    INDEX `idx_application_id` (`application_id`),
    INDEX `idx_scheduled_date` (`scheduled_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create job_offers table
CREATE TABLE IF NOT EXISTS `job_offers` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `application_id` INT NOT NULL,
    `position_title` VARCHAR(255) NOT NULL,
    `department_id` INT,
    `salary_offered` DECIMAL(10,2) NOT NULL,
    `employment_type` ENUM('Full-time', 'Part-time', 'Contract', 'Temporary') DEFAULT 'Full-time',
    `start_date` DATE NOT NULL,
    `offer_letter_path` VARCHAR(500),
    `benefits` TEXT,
    `terms_conditions` TEXT,
    `status` ENUM('pending', 'accepted', 'rejected', 'expired') DEFAULT 'pending',
    `accepted_date` DATETIME,
    `rejected_date` DATETIME,
    `rejection_reason` TEXT,
    `expiry_date` DATE,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`application_id`) REFERENCES `job_applications`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `user_accounts`(`id`) ON DELETE CASCADE,
    INDEX `idx_application_id` (`application_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
