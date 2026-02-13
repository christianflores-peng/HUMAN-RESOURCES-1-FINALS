-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 13, 2026 at 11:22 PM
-- Server version: 8.0.42
-- PHP Version: 8.3.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hr1_hr1data`
--

-- --------------------------------------------------------

--
-- Table structure for table `applicant_notifications`
--

CREATE TABLE `applicant_notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'general',
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `link` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applicant_profiles`
--

CREATE TABLE `applicant_profiles` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `resume_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_letter` text COLLATE utf8mb4_unicode_ci,
  `skills` text COLLATE utf8mb4_unicode_ci,
  `experience_years` int DEFAULT NULL,
  `education_level` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linkedin_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `portfolio_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `availability` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Immediate',
  `expected_salary` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `work_authorization` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `application_status_history`
--

CREATE TABLE `application_status_history` (
  `id` int NOT NULL,
  `application_id` int NOT NULL,
  `old_status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `new_status` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `changed_by` int DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_general_ci,
  `changed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `approval_workflows`
--

CREATE TABLE `approval_workflows` (
  `id` int NOT NULL,
  `workflow_name` varchar(255) NOT NULL,
  `module` varchar(100) NOT NULL,
  `steps` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `approval_workflows`
--

INSERT INTO `approval_workflows` (`id`, `workflow_name`, `module`, `steps`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Job Requisition Approval', 'Performance Review', '[{\"label\": \"Role: Employee\", \"value\": \"role:Employee\"}]', 1, 104, '2026-02-13 18:32:37', '2026-02-13 18:32:37');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `action` enum('VIEW','CREATE','EDIT','DELETE','LOGIN','LOGOUT','APPROVE','REJECT','HIRE','SYSTEM') NOT NULL,
  `module` varchar(100) DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `record_type` varchar(50) DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `detail` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `user_email`, `action`, `module`, `record_id`, `record_type`, `old_values`, `new_values`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 6, 'hr@slatefreight.com', 'LOGIN', 'user_accounts', 6, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-25 21:32:34'),
(2, 5, 'admin@slatefreight.com', 'LOGIN', 'user_accounts', 5, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-25 21:42:59'),
(3, 5, 'admin@slatefreight.com', 'EDIT', 'user_accounts', 9, 'user_accounts', '{\"status\": \"Active\", \"role_id\": 9}', '{\"status\": \"Active\", \"role_id\": 1}', 'Updated user permissions', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-25 21:43:45'),
(4, 8, 'employee@slatefreight.com', 'LOGIN', 'user_accounts', 8, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-25 21:45:27'),
(5, 8, 'employee@slatefreight.com', 'LOGIN', 'user_accounts', 8, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-25 22:00:13'),
(6, 7, 'manager@slatefreight.com', 'LOGIN', 'user_accounts', 7, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-25 23:03:46'),
(7, 7, 'manager@slatefreight.com', 'CREATE', 'employee_onboarding_progress', NULL, 'employee_onboarding_progress', NULL, '{\"task_name\": \"Truck Delivers\", \"employee_id\": 8}', 'Assigned onboarding task to employee', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 00:08:13'),
(8, 9, 'System', 'LOGIN', 'user_accounts', 9, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 00:47:58'),
(9, 9, 'System', 'LOGIN', 'user_accounts', 9, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 00:48:23'),
(10, 5, 'admin@slatefreight.com', 'LOGIN', 'user_accounts', 5, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 15:31:28'),
(11, 5, 'admin@slatefreight.com', 'LOGIN', 'user_accounts', 5, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 16:59:12'),
(12, 5, 'admin@slatefreight.com', 'CREATE', 'job_postings', 3, 'job_postings', NULL, '{\"title\": \"Truck Driver\", \"status\": \"Open\", \"department_id\": 7}', 'Created job posting: Truck Driver', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 17:04:48'),
(13, 5, 'admin@slatefreight.com', 'CREATE', 'job_postings', 4, 'job_postings', NULL, '{\"title\": \"Truck Driver\", \"status\": \"Open\", \"department_id\": 4}', 'Created job posting: Truck Driver', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 17:28:34'),
(14, 5, 'admin@slatefreight.com', 'LOGIN', 'user_accounts', 5, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 22:43:00'),
(15, 5, 'admin@slatefreight.com', 'LOGIN', 'user_accounts', 5, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 00:25:45'),
(16, 5, 'admin@slatefreight.com', 'EDIT', 'job_applications', 1, 'job_applications', NULL, '{\"status\": \"screening\"}', 'Changed applicant status to: screening', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 00:26:07'),
(17, 5, 'admin@slatefreight.com', 'EDIT', 'job_applications', 1, 'job_applications', NULL, '{\"status\": \"interview\"}', 'Changed applicant status to: interview', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 00:26:11'),
(18, 5, 'admin@slatefreight.com', 'EDIT', 'job_applications', 1, 'job_applications', NULL, '{\"status\": \"road_test\"}', 'Changed applicant status to: road_test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 00:26:13'),
(19, 5, 'admin@slatefreight.com', 'EDIT', 'job_applications', 1, 'job_applications', NULL, '{\"status\": \"offer_sent\"}', 'Changed applicant status to: offer_sent', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 00:26:16'),
(20, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 1, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00001\", \"company_email\": \"kyrie.irving@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Kyrie Irving', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 01:54:19'),
(21, NULL, 'System', 'SYSTEM', 'user_accounts', 53, 'user_accounts', NULL, '{\"company_email\": \"kyrie.irving@slatefreight.com\"}', 'Created user account: kyrie.irving@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 01:54:19'),
(22, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 1, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00002\", \"company_email\": \"kyrie.irving1@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Kyrie Irving', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 01:56:50'),
(23, NULL, 'System', 'SYSTEM', 'user_accounts', 54, 'user_accounts', NULL, '{\"company_email\": \"kyrie.irving1@slatefreight.com\"}', 'Created user account: kyrie.irving1@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 01:56:50'),
(24, 5, 'admin@slatefreight.com', 'LOGIN', 'user_accounts', 5, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:11:00'),
(25, 5, 'admin@slatefreight.com', 'EDIT', 'job_applications', 2, 'job_applications', NULL, '{\"status\": \"screening\"}', 'Changed applicant status to: screening', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:14:27'),
(26, 5, 'admin@slatefreight.com', 'EDIT', 'job_applications', 2, 'job_applications', NULL, '{\"status\": \"interview\"}', 'Changed applicant status to: interview', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:14:31'),
(27, 5, 'admin@slatefreight.com', 'EDIT', 'job_applications', 2, 'job_applications', NULL, '{\"status\": \"road_test\"}', 'Changed applicant status to: road_test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:14:34'),
(28, 5, 'admin@slatefreight.com', 'EDIT', 'job_applications', 2, 'job_applications', NULL, '{\"status\": \"offer_sent\"}', 'Changed applicant status to: offer_sent', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:14:37'),
(29, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00003\", \"company_email\": \"earl.flores@slatefreight.com\", \"department_id\": 8}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:14:50'),
(30, NULL, 'System', 'SYSTEM', 'user_accounts', 55, 'user_accounts', NULL, '{\"company_email\": \"earl.flores@slatefreight.com\"}', 'Created user account: earl.flores@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:14:50'),
(31, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00004\", \"company_email\": \"earl.flores1@slatefreight.com\", \"department_id\": 8}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:15:40'),
(32, NULL, 'System', 'SYSTEM', 'user_accounts', 56, 'user_accounts', NULL, '{\"company_email\": \"earl.flores1@slatefreight.com\"}', 'Created user account: earl.flores1@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:15:40'),
(33, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00005\", \"company_email\": \"earl.flores2@slatefreight.com\", \"department_id\": 8}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:16:31'),
(34, NULL, 'System', 'SYSTEM', 'user_accounts', 57, 'user_accounts', NULL, '{\"company_email\": \"earl.flores2@slatefreight.com\"}', 'Created user account: earl.flores2@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:16:31'),
(35, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00006\", \"company_email\": \"earl.flores3@slatefreight.com\", \"department_id\": 8}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:17:22'),
(36, NULL, 'System', 'SYSTEM', 'user_accounts', 58, 'user_accounts', NULL, '{\"company_email\": \"earl.flores3@slatefreight.com\"}', 'Created user account: earl.flores3@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:17:22'),
(37, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00007\", \"company_email\": \"earl.flores4@slatefreight.com\", \"department_id\": 8}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:18:13'),
(38, NULL, 'System', 'SYSTEM', 'user_accounts', 59, 'user_accounts', NULL, '{\"company_email\": \"earl.flores4@slatefreight.com\"}', 'Created user account: earl.flores4@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:18:13'),
(39, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00008\", \"company_email\": \"earl.flores5@slatefreight.com\", \"department_id\": 8}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:19:04'),
(40, NULL, 'System', 'SYSTEM', 'user_accounts', 60, 'user_accounts', NULL, '{\"company_email\": \"earl.flores5@slatefreight.com\"}', 'Created user account: earl.flores5@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:19:04'),
(41, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00009\", \"company_email\": \"earl.flores6@slatefreight.com\", \"department_id\": 8}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:19:55'),
(42, NULL, 'System', 'SYSTEM', 'user_accounts', 61, 'user_accounts', NULL, '{\"company_email\": \"earl.flores6@slatefreight.com\"}', 'Created user account: earl.flores6@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:19:55'),
(43, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00010\", \"company_email\": \"earl.flores7@slatefreight.com\", \"department_id\": 8}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:20:46'),
(44, NULL, 'System', 'SYSTEM', 'user_accounts', 62, 'user_accounts', NULL, '{\"company_email\": \"earl.flores7@slatefreight.com\"}', 'Created user account: earl.flores7@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:20:47'),
(45, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00011\", \"company_email\": \"earl.flores8@slatefreight.com\", \"department_id\": 8}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:21:38'),
(46, NULL, 'System', 'SYSTEM', 'user_accounts', 63, 'user_accounts', NULL, '{\"company_email\": \"earl.flores8@slatefreight.com\"}', 'Created user account: earl.flores8@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:21:38'),
(47, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00012\", \"company_email\": \"earl.flores9@slatefreight.com\", \"department_id\": 8}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:22:28'),
(48, NULL, 'System', 'SYSTEM', 'user_accounts', 64, 'user_accounts', NULL, '{\"company_email\": \"earl.flores9@slatefreight.com\"}', 'Created user account: earl.flores9@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:22:28'),
(49, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00013\", \"company_email\": \"earl.flores10@slatefreight.com\", \"department_id\": 8}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:23:20'),
(50, NULL, 'System', 'SYSTEM', 'user_accounts', 65, 'user_accounts', NULL, '{\"company_email\": \"earl.flores10@slatefreight.com\"}', 'Created user account: earl.flores10@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:23:20'),
(51, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00014\", \"company_email\": \"earl.flores11@slatefreight.com\", \"department_id\": 8}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:24:11'),
(52, NULL, 'System', 'SYSTEM', 'user_accounts', 66, 'user_accounts', NULL, '{\"company_email\": \"earl.flores11@slatefreight.com\"}', 'Created user account: earl.flores11@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:24:11'),
(53, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00015\", \"company_email\": \"earl.flores12@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:25:03'),
(54, NULL, 'System', 'SYSTEM', 'user_accounts', 67, 'user_accounts', NULL, '{\"company_email\": \"earl.flores12@slatefreight.com\"}', 'Created user account: earl.flores12@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:25:03'),
(55, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00016\", \"company_email\": \"earl.flores13@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:25:54'),
(56, NULL, 'System', 'SYSTEM', 'user_accounts', 68, 'user_accounts', NULL, '{\"company_email\": \"earl.flores13@slatefreight.com\"}', 'Created user account: earl.flores13@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:25:54'),
(57, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00017\", \"company_email\": \"earl.flores14@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:26:45'),
(58, NULL, 'System', 'SYSTEM', 'user_accounts', 69, 'user_accounts', NULL, '{\"company_email\": \"earl.flores14@slatefreight.com\"}', 'Created user account: earl.flores14@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:26:46'),
(59, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00018\", \"company_email\": \"earl.flores15@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:27:37'),
(60, NULL, 'System', 'SYSTEM', 'user_accounts', 70, 'user_accounts', NULL, '{\"company_email\": \"earl.flores15@slatefreight.com\"}', 'Created user account: earl.flores15@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:27:37'),
(61, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00019\", \"company_email\": \"earl.flores16@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:28:28'),
(62, NULL, 'System', 'SYSTEM', 'user_accounts', 71, 'user_accounts', NULL, '{\"company_email\": \"earl.flores16@slatefreight.com\"}', 'Created user account: earl.flores16@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:28:28'),
(63, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00020\", \"company_email\": \"earl.flores17@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:29:19'),
(64, NULL, 'System', 'SYSTEM', 'user_accounts', 72, 'user_accounts', NULL, '{\"company_email\": \"earl.flores17@slatefreight.com\"}', 'Created user account: earl.flores17@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:29:19'),
(65, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00021\", \"company_email\": \"earl.flores18@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:30:10'),
(66, NULL, 'System', 'SYSTEM', 'user_accounts', 73, 'user_accounts', NULL, '{\"company_email\": \"earl.flores18@slatefreight.com\"}', 'Created user account: earl.flores18@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:30:10'),
(67, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00022\", \"company_email\": \"earl.flores19@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:31:01'),
(68, NULL, 'System', 'SYSTEM', 'user_accounts', 74, 'user_accounts', NULL, '{\"company_email\": \"earl.flores19@slatefreight.com\"}', 'Created user account: earl.flores19@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:31:01'),
(69, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00023\", \"company_email\": \"earl.flores20@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:31:52'),
(70, NULL, 'System', 'SYSTEM', 'user_accounts', 75, 'user_accounts', NULL, '{\"company_email\": \"earl.flores20@slatefreight.com\"}', 'Created user account: earl.flores20@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:31:52'),
(71, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00024\", \"company_email\": \"earl.flores21@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:32:42'),
(72, NULL, 'System', 'SYSTEM', 'user_accounts', 76, 'user_accounts', NULL, '{\"company_email\": \"earl.flores21@slatefreight.com\"}', 'Created user account: earl.flores21@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:32:42'),
(73, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00025\", \"company_email\": \"earl.flores22@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:33:33'),
(74, NULL, 'System', 'SYSTEM', 'user_accounts', 77, 'user_accounts', NULL, '{\"company_email\": \"earl.flores22@slatefreight.com\"}', 'Created user account: earl.flores22@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:33:33'),
(75, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00026\", \"company_email\": \"earl.flores23@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:34:24'),
(76, NULL, 'System', 'SYSTEM', 'user_accounts', 78, 'user_accounts', NULL, '{\"company_email\": \"earl.flores23@slatefreight.com\"}', 'Created user account: earl.flores23@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:34:24'),
(77, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00027\", \"company_email\": \"earl.flores24@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:35:15'),
(78, NULL, 'System', 'SYSTEM', 'user_accounts', 79, 'user_accounts', NULL, '{\"company_email\": \"earl.flores24@slatefreight.com\"}', 'Created user account: earl.flores24@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:35:15'),
(79, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00028\", \"company_email\": \"earl.flores25@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:36:06'),
(80, NULL, 'System', 'SYSTEM', 'user_accounts', 80, 'user_accounts', NULL, '{\"company_email\": \"earl.flores25@slatefreight.com\"}', 'Created user account: earl.flores25@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:36:06'),
(81, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00029\", \"company_email\": \"earl.flores26@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:36:57'),
(82, NULL, 'System', 'SYSTEM', 'user_accounts', 81, 'user_accounts', NULL, '{\"company_email\": \"earl.flores26@slatefreight.com\"}', 'Created user account: earl.flores26@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:36:57'),
(83, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00030\", \"company_email\": \"earl.flores27@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:37:47'),
(84, NULL, 'System', 'SYSTEM', 'user_accounts', 82, 'user_accounts', NULL, '{\"company_email\": \"earl.flores27@slatefreight.com\"}', 'Created user account: earl.flores27@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:37:47'),
(85, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00031\", \"company_email\": \"earl.flores28@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:38:38'),
(86, NULL, 'System', 'SYSTEM', 'user_accounts', 83, 'user_accounts', NULL, '{\"company_email\": \"earl.flores28@slatefreight.com\"}', 'Created user account: earl.flores28@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:38:38'),
(87, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00032\", \"company_email\": \"earl.flores29@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:39:29'),
(88, NULL, 'System', 'SYSTEM', 'user_accounts', 84, 'user_accounts', NULL, '{\"company_email\": \"earl.flores29@slatefreight.com\"}', 'Created user account: earl.flores29@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:39:29'),
(89, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00033\", \"company_email\": \"earl.flores30@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:40:19'),
(90, NULL, 'System', 'SYSTEM', 'user_accounts', 85, 'user_accounts', NULL, '{\"company_email\": \"earl.flores30@slatefreight.com\"}', 'Created user account: earl.flores30@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:40:19'),
(91, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00034\", \"company_email\": \"earl.flores31@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:41:10'),
(92, NULL, 'System', 'SYSTEM', 'user_accounts', 86, 'user_accounts', NULL, '{\"company_email\": \"earl.flores31@slatefreight.com\"}', 'Created user account: earl.flores31@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:41:10'),
(93, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00035\", \"company_email\": \"earl.flores32@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:42:01'),
(94, NULL, 'System', 'SYSTEM', 'user_accounts', 87, 'user_accounts', NULL, '{\"company_email\": \"earl.flores32@slatefreight.com\"}', 'Created user account: earl.flores32@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:42:01'),
(95, 5, 'admin@slatefreight.com', 'EDIT', 'job_applications', 2, 'job_applications', NULL, '{\"status\": \"offer_sent\"}', 'Changed applicant status to: offer_sent', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:42:52'),
(96, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00036\", \"company_email\": \"earl.flores33@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:42:52'),
(97, NULL, 'System', 'SYSTEM', 'user_accounts', 88, 'user_accounts', NULL, '{\"company_email\": \"earl.flores33@slatefreight.com\"}', 'Created user account: earl.flores33@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:42:52'),
(98, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00037\", \"company_email\": \"earl.flores34@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:43:43'),
(99, NULL, 'System', 'SYSTEM', 'user_accounts', 89, 'user_accounts', NULL, '{\"company_email\": \"earl.flores34@slatefreight.com\"}', 'Created user account: earl.flores34@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:43:43'),
(100, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00038\", \"company_email\": \"earl.flores35@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:44:33'),
(101, NULL, 'System', 'SYSTEM', 'user_accounts', 90, 'user_accounts', NULL, '{\"company_email\": \"earl.flores35@slatefreight.com\"}', 'Created user account: earl.flores35@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:44:33'),
(102, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00039\", \"company_email\": \"earl.flores36@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:45:24'),
(103, NULL, 'System', 'SYSTEM', 'user_accounts', 91, 'user_accounts', NULL, '{\"company_email\": \"earl.flores36@slatefreight.com\"}', 'Created user account: earl.flores36@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:45:24'),
(104, 5, 'admin@slatefreight.com', 'EDIT', 'job_applications', 2, 'job_applications', NULL, '{\"status\": \"offer_sent\"}', 'Changed applicant status to: offer_sent', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:46:14'),
(105, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00040\", \"company_email\": \"earl.flores37@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:46:15'),
(106, NULL, 'System', 'SYSTEM', 'user_accounts', 92, 'user_accounts', NULL, '{\"company_email\": \"earl.flores37@slatefreight.com\"}', 'Created user account: earl.flores37@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:46:15'),
(107, 5, 'admin@slatefreight.com', 'EDIT', 'job_applications', 2, 'job_applications', NULL, '{\"status\": \"offer_sent\"}', 'Changed applicant status to: offer_sent', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:47:05'),
(108, 5, 'admin@slatefreight.com', 'HIRE', 'job_applications', 2, 'job_applications', NULL, '{\"employee_id\": \"EMP-2026-00041\", \"company_email\": \"earl.flores38@slatefreight.com\", \"department_id\": 4}', 'Hired applicant: Earl Flores', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:47:06'),
(109, NULL, 'System', 'SYSTEM', 'user_accounts', 93, 'user_accounts', NULL, '{\"company_email\": \"earl.flores38@slatefreight.com\"}', 'Created user account: earl.flores38@slatefreight.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 03:47:06'),
(110, 24, 'russelwestbrook@gmail.com', 'LOGIN', 'user_accounts', 24, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 05:23:25'),
(111, 5, 'admin@slatefreight.com', 'LOGIN', 'user_accounts', 5, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 05:25:01'),
(112, 5, 'admin@slatefreight.com', 'EDIT', 'job_applications', 3, 'job_applications', NULL, '{\"status\": \"screening\"}', 'Changed applicant status to: screening', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 05:31:51'),
(113, 25, 'maechrisanta@slatefreight.com', 'LOGIN', 'user_accounts', 25, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 05:48:34'),
(114, 26, 'cris12345', 'LOGIN', 'user_accounts', 26, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 06:20:05'),
(115, 5, 'admin@slatefreight.com', 'LOGIN', 'user_accounts', 5, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 10:22:45'),
(116, 5, 'admin@slatefreight.com', 'LOGIN', 'user_accounts', 5, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 12:09:34'),
(117, 5, 'admin@slatefreight.com', 'LOGIN', 'user_accounts', 5, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:28:10'),
(118, 100, 'System', 'LOGIN', 'user_accounts', 100, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 05:18:09'),
(119, 99, 'System', 'LOGIN', 'user_accounts', 99, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36 Edg/92.0.902.67', '2026-01-30 09:58:51'),
(120, 99, 'System', 'LOGIN', 'user_accounts', 99, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36 Edg/92.0.902.67', '2026-01-30 10:22:36'),
(121, 99, 'System', 'LOGIN', 'user_accounts', 99, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36 Edg/92.0.902.67', '2026-01-30 10:57:03'),
(122, 99, 'System', 'LOGIN', 'user_accounts', 99, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36 Edg/92.0.902.67', '2026-01-30 10:57:23'),
(123, 99, 'System', 'LOGIN', 'user_accounts', 99, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36 Edg/92.0.902.67', '2026-01-30 11:15:36'),
(124, 99, 'System', 'LOGIN', 'user_accounts', 99, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36 Edg/92.0.902.67', '2026-01-30 12:11:25'),
(125, 101, 'System', 'LOGIN', 'user_accounts', 101, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 07:13:34'),
(126, 5, 'admin@slatefreight.com', 'LOGIN', 'user_accounts', 5, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 07:31:28'),
(127, 5, 'admin@slatefreight.com', 'EDIT', 'job_applications', 23, 'job_applications', NULL, '{\"status\": \"screening\"}', 'Changed applicant status to: screening', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 07:32:45'),
(128, 7, 'manager@slatefreight.com', 'LOGIN', 'user_accounts', 7, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 07:37:08'),
(129, 5, 'admin@slatefreight.com', 'LOGIN', 'user_accounts', 5, 'user_accounts', NULL, NULL, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 09:23:08'),
(130, 103, 'System', 'LOGIN', 'user_accounts', 103, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 10:55:02'),
(131, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 00:17:51'),
(132, 106, 'System', 'LOGIN', 'user_accounts', 106, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 12:13:01'),
(133, 107, 'System', 'LOGIN', 'user_accounts', 107, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 13:01:41'),
(134, 106, 'System', 'LOGIN', 'user_accounts', 106, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 01:46:56'),
(135, 108, 'System', 'LOGIN', 'user_accounts', 108, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-06 14:49:46'),
(136, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 14:17:15'),
(137, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 07:19:39'),
(138, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 08:17:56'),
(139, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 09:09:01'),
(140, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 09:11:26'),
(141, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 09:13:59'),
(142, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 10:01:21'),
(143, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 10:27:47'),
(144, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 10:29:48'),
(145, 6, 'hr@slatefreight.com', 'LOGIN', 'user_accounts', 6, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 10:32:17'),
(146, 6, 'hr@slatefreight.com', 'LOGIN', 'user_accounts', 6, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 13:27:02'),
(147, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 19:32:18'),
(148, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 19:32:54'),
(149, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 19:55:07'),
(150, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 22:03:01'),
(151, 6, 'hr@slatefreight.com', 'LOGIN', 'user_accounts', 6, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 22:12:22'),
(152, 6, 'hr@slatefreight.com', 'LOGIN', 'user_accounts', 6, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 03:13:26'),
(153, 8, 'employee@slatefreight.com', 'LOGIN', 'user_accounts', 8, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 03:16:09'),
(154, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 04:31:40');
INSERT INTO `audit_logs` (`id`, `user_id`, `user_email`, `action`, `module`, `record_id`, `record_type`, `old_values`, `new_values`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(155, 106, 'System', 'LOGIN', 'user_accounts', 106, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 04:34:20'),
(156, 110, 'System', 'LOGIN', 'user_accounts', 110, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:17:16'),
(157, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:25:39'),
(158, 8, 'employee@slatefreight.com', 'LOGIN', 'user_accounts', 8, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:31:53'),
(159, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 08:09:27'),
(160, 106, 'System', 'LOGIN', 'user_accounts', 106, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 22:03:58'),
(161, 8, 'employee@slatefreight.com', 'LOGIN', 'user_accounts', 8, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 22:09:45'),
(162, 6, 'hr@slatefreight.com', 'LOGIN', 'user_accounts', 6, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 22:18:13'),
(163, 7, 'manager@slatefreight.com', 'LOGIN', 'user_accounts', 7, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 22:24:01'),
(164, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 22:28:54'),
(165, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 13:08:25'),
(166, 8, 'employee@slatefreight.com', 'LOGIN', 'user_accounts', 8, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 13:35:21'),
(167, 6, 'hr@slatefreight.com', 'LOGIN', 'user_accounts', 6, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 23:14:28'),
(168, 7, 'manager@slatefreight.com', 'LOGIN', 'user_accounts', 7, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 23:25:37'),
(169, 8, 'employee@slatefreight.com', 'LOGIN', 'user_accounts', 8, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 23:30:08'),
(170, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-13 06:51:20'),
(171, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-13 07:08:55'),
(172, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-13 08:58:14'),
(173, 104, 'System', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-13 09:44:52'),
(174, 104, 'admin@slatefreight.com', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-13 18:31:14'),
(175, 106, 'System', 'LOGIN', 'user_accounts', 106, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-13 19:46:46'),
(176, 7, 'manager@slatefreight.com', 'LOGIN', 'user_accounts', 7, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-13 19:48:20'),
(177, 6, 'hr@slatefreight.com', 'LOGIN', 'user_accounts', 6, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-13 19:50:43'),
(178, 104, 'admin@slatefreight.com', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-13 21:34:14'),
(179, 104, 'admin@slatefreight.com', 'LOGIN', 'user_accounts', 104, 'user_accounts', NULL, NULL, 'User logged in with OTP verification', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-13 22:40:03');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int NOT NULL,
  `department_code` varchar(20) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `parent_department_id` int DEFAULT NULL,
  `manager_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `department_code`, `department_name`, `parent_department_id`, `manager_id`, `created_at`) VALUES
(1, 'HR', 'Human Resources', NULL, NULL, '2026-01-25 21:25:31'),
(2, 'FLEET', 'Fleet Operations', NULL, NULL, '2026-01-25 21:25:31'),
(3, 'LOGISTICS', 'Logistics', NULL, NULL, '2026-01-25 21:25:31'),
(4, 'WAREHOUSE', 'Warehouse', NULL, NULL, '2026-01-25 21:25:31'),
(5, 'FINANCE', 'Finance', NULL, NULL, '2026-01-25 21:25:31'),
(6, 'IT', 'Information Technology', NULL, NULL, '2026-01-25 21:25:31'),
(7, 'DISPATCH', 'Dispatch Center', NULL, NULL, '2026-01-25 21:25:31'),
(8, 'MAINTENANCE', 'Vehicle Maintenance', NULL, NULL, '2026-01-25 21:25:31');

-- --------------------------------------------------------

--
-- Table structure for table `employee_documents`
--

CREATE TABLE `employee_documents` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `document_type` enum('License','Medical Certificate','NBI Clearance','201 File','Contract','Resume','ID','Certificate','Other') NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('Valid','Expiring Soon','Expired','Pending Verification','Rejected') DEFAULT 'Pending Verification',
  `verified_by` int DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `uploaded_by` int DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_onboarding_progress`
--

CREATE TABLE `employee_onboarding_progress` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `task_id` int NOT NULL,
  `status` enum('Pending','In Progress','Completed','Overdue') DEFAULT 'Pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `verified_by` int DEFAULT NULL,
  `notes` text,
  `file_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employee_onboarding_progress`
--

INSERT INTO `employee_onboarding_progress` (`id`, `user_id`, `task_id`, `status`, `completed_at`, `verified_by`, `notes`, `file_path`, `created_at`) VALUES
(1, 7, 10, 'Pending', NULL, NULL, NULL, NULL, '2026-01-25 23:41:27'),
(2, 8, 11, 'Completed', '2026-01-26 00:08:50', NULL, NULL, '../uploads/onboarding/user_8_task_11_1769386130.docx', '2026-01-26 00:08:13');

-- --------------------------------------------------------

--
-- Table structure for table `employee_requirements`
--

CREATE TABLE `employee_requirements` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `document_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `remarks` text COLLATE utf8mb4_general_ci,
  `verified_by` int DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_requirements`
--

INSERT INTO `employee_requirements` (`id`, `user_id`, `document_type`, `file_path`, `status`, `remarks`, `verified_by`, `verified_at`, `uploaded_at`, `updated_at`) VALUES
(1, 8, '2x2_photo', 'uploads/requirements/8/2x2_photo_1770939176.jpg', 'pending', NULL, NULL, NULL, '2026-02-12 23:32:56', '2026-02-12 23:32:56');

-- --------------------------------------------------------

--
-- Table structure for table `exam_schedules`
--

CREATE TABLE `exam_schedules` (
  `id` int NOT NULL,
  `application_id` int NOT NULL,
  `exam_type` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `scheduled_date` datetime NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `examiner_id` int DEFAULT NULL,
  `status` enum('Scheduled','Completed','Cancelled','No Show') COLLATE utf8mb4_general_ci DEFAULT 'Scheduled',
  `score` decimal(5,2) DEFAULT NULL,
  `result` enum('Pass','Fail','Pending') COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interviews`
--

CREATE TABLE `interviews` (
  `id` int NOT NULL,
  `application_id` int NOT NULL,
  `interview_type` enum('Phone Screen','Technical','HR','Manager','Road Test','Final') NOT NULL,
  `scheduled_date` datetime NOT NULL,
  `interviewer_id` int DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` enum('Scheduled','Completed','Cancelled','No Show') DEFAULT 'Scheduled',
  `notes` text,
  `rating` tinyint DEFAULT NULL,
  `recommendation` enum('Proceed','Reject','On Hold') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `interview_schedules`
--

CREATE TABLE `interview_schedules` (
  `id` int NOT NULL,
  `application_id` int NOT NULL,
  `interviewer_id` int DEFAULT NULL,
  `scheduled_date` datetime NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `interview_type` enum('Phone','Video','In-Person') COLLATE utf8mb4_general_ci DEFAULT 'In-Person',
  `status` enum('Scheduled','Completed','Cancelled','No Show') COLLATE utf8mb4_general_ci DEFAULT 'Scheduled',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_applications`
--

CREATE TABLE `job_applications` (
  `id` int NOT NULL,
  `job_posting_id` int DEFAULT NULL,
  `requisition_id` int DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `resume_path` varchar(500) DEFAULT NULL,
  `screening_notes` text,
  `screened_by` int DEFAULT NULL,
  `screened_at` datetime DEFAULT NULL,
  `cover_letter` text,
  `status` enum('new','screening','interview','road_test','offer_sent','hired','rejected','withdrawn') DEFAULT 'new',
  `hired_date` datetime DEFAULT NULL,
  `applied_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `interview_date` datetime DEFAULT NULL,
  `interview_notes` text,
  `hr_notes` text,
  `rating` tinyint DEFAULT NULL,
  `processed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `employee_id_assigned` varchar(20) DEFAULT NULL,
  `hired_by` int DEFAULT NULL,
  `rejected_by` int DEFAULT NULL,
  `status_updated_by` int DEFAULT NULL
) ;

--
-- Dumping data for table `job_applications`
--

INSERT INTO `job_applications` (`id`, `job_posting_id`, `requisition_id`, `first_name`, `last_name`, `middle_name`, `email`, `phone`, `address`, `resume_path`, `screening_notes`, `screened_by`, `screened_at`, `cover_letter`, `status`, `hired_date`, `applied_date`, `interview_date`, `interview_notes`, `hr_notes`, `rating`, `processed_by`, `created_at`, `updated_at`, `employee_id_assigned`, `hired_by`, `rejected_by`, `status_updated_by`) VALUES
(25, NULL, NULL, 'CHRISTIAN', 'FLORES', NULL, 'christianvizmonte222@gmail.com', '09484914234', NULL, 'uploads/resumes/resume_104_1769905029.docx', NULL, NULL, NULL, 'APPLY AKO WORK EHH', 'new', NULL, '2026-02-01 00:17:09', NULL, NULL, NULL, NULL, NULL, '2026-02-01 00:17:09', '2026-02-01 00:17:09', NULL, NULL, NULL, NULL),
(26, NULL, NULL, 'Shawn', 'Villanueva', NULL, 'shawnvill0608@gmail.com', '09484914234', NULL, 'uploads/resumes/resume_105_1769947722.docx', NULL, NULL, NULL, 'fjshdagsdnlsetadfsd', 'new', NULL, '2026-02-01 12:08:42', NULL, NULL, NULL, NULL, NULL, '2026-02-01 12:08:42', '2026-02-01 12:08:42', NULL, NULL, NULL, NULL),
(27, NULL, NULL, 'Shawn', 'Villanueva', NULL, 'shawnvill0608@gmail.com', '09484914234', NULL, 'uploads/resumes/resume_106_1769947957.docx', NULL, NULL, NULL, 'asdawszdhfghkl;jkh', 'new', NULL, '2026-02-01 12:12:37', NULL, NULL, NULL, NULL, NULL, '2026-02-01 12:12:37', '2026-02-01 12:12:37', NULL, NULL, NULL, NULL),
(28, NULL, NULL, 'Lynette', 'Arambulo', NULL, 'lynettearambulo948@gmail.com', '09910904731', NULL, 'uploads/resumes/resume_107_1769950853.docx', NULL, NULL, NULL, 'asfsdhfgjfghkjdfsgfdf', 'new', NULL, '2026-02-01 13:00:53', NULL, NULL, NULL, NULL, NULL, '2026-02-01 13:00:53', '2026-02-01 13:00:53', NULL, NULL, NULL, NULL),
(29, NULL, NULL, 'Orlindo', 'Leonards', NULL, 'orlindoleonards@gmail.com', '09910904871', NULL, 'uploads/resumes/resume_108_1770389337.docx', NULL, NULL, NULL, 'sdghjkxdfg', 'new', NULL, '2026-02-06 14:48:57', NULL, NULL, NULL, NULL, NULL, '2026-02-06 14:48:57', '2026-02-06 14:48:57', NULL, NULL, NULL, NULL),
(30, NULL, NULL, 'James', 'Harden', NULL, 'jimskan18@gmail.com', '09910904871', NULL, 'uploads/resumes/resume_110_1770704170.docx', NULL, NULL, NULL, 'nsafjsdhgssdjgksjkjgj', 'new', NULL, '2026-02-10 06:16:12', NULL, NULL, NULL, NULL, NULL, '2026-02-10 06:16:12', '2026-02-10 06:16:12', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `job_offers`
--

CREATE TABLE `job_offers` (
  `id` int NOT NULL,
  `application_id` int NOT NULL,
  `salary_offered` decimal(12,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `position_title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `status` enum('pending','accepted','rejected','expired') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `offer_letter_path` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `accepted_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_general_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_postings`
--

CREATE TABLE `job_postings` (
  `id` int NOT NULL,
  `requisition_id` int DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `department_id` int DEFAULT NULL,
  `employment_type` enum('Full-time','Part-time','Contract','Temporary') DEFAULT 'Full-time',
  `salary_min` decimal(10,2) DEFAULT NULL,
  `salary_max` decimal(10,2) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `requirements` text,
  `responsibilities` text,
  `status` enum('Draft','Open','Closed','On Hold') DEFAULT 'Open',
  `created_by` int DEFAULT NULL,
  `posted_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `closing_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `job_postings`
--

INSERT INTO `job_postings` (`id`, `requisition_id`, `title`, `description`, `department_id`, `employment_type`, `salary_min`, `salary_max`, `location`, `requirements`, `responsibilities`, `status`, `created_by`, `posted_date`, `closing_date`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Truck Driver', 'APPLY NA, BASTA MAY LISENSYA LAMANG', 7, 'Full-time', 10000.00, 30000.00, 'LAGUNA', 'RESUME LANG O KAYA BIODATA', 'TAGA DELIVER LANG NG MGA ITEMS', 'Open', NULL, '2026-01-26 17:01:15', '2026-01-31', '2026-01-26 17:01:15', '2026-02-13 18:33:35');

-- --------------------------------------------------------

--
-- Table structure for table `job_requisitions`
--

CREATE TABLE `job_requisitions` (
  `id` int NOT NULL,
  `requested_by` int NOT NULL,
  `department_id` int NOT NULL,
  `job_title` varchar(255) NOT NULL,
  `positions_needed` int DEFAULT '1',
  `employment_type` enum('Full-time','Part-time','Contract','Seasonal') DEFAULT 'Full-time',
  `urgency` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
  `justification` text,
  `requirements` text,
  `preferred_start_date` date DEFAULT NULL,
  `salary_range_min` decimal(10,2) DEFAULT NULL,
  `salary_range_max` decimal(10,2) DEFAULT NULL,
  `status` enum('Draft','Pending','Approved','Rejected','Filled','Cancelled') DEFAULT 'Pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text,
  `linked_posting_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `onboarding_requirements`
--

CREATE TABLE `onboarding_requirements` (
  `id` int NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `description` text,
  `is_mandatory` tinyint(1) DEFAULT '1',
  `category` varchar(50) DEFAULT 'General',
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `onboarding_tasks`
--

CREATE TABLE `onboarding_tasks` (
  `id` int NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `task_description` text,
  `category` enum('Documents','Training','IT Setup','Orientation','Compliance','Other') NOT NULL,
  `is_required` tinyint(1) DEFAULT '1',
  `days_to_complete` int DEFAULT '7',
  `department_specific` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `onboarding_tasks`
--

INSERT INTO `onboarding_tasks` (`id`, `task_name`, `task_description`, `category`, `is_required`, `days_to_complete`, `department_specific`, `created_at`) VALUES
(1, 'Submit NBI Clearance', 'Upload your NBI Clearance document', 'Documents', 1, 7, NULL, '2026-01-25 21:25:32'),
(2, 'Submit Medical Certificate', 'Upload your medical certificate from accredited clinic', 'Documents', 1, 7, NULL, '2026-01-25 21:25:32'),
(3, 'Submit SSS/PhilHealth/Pag-IBIG IDs', 'Upload government ID documents', 'Documents', 1, 14, NULL, '2026-01-25 21:25:32'),
(4, 'Complete Company Orientation', 'Attend the company orientation session', 'Orientation', 1, 3, NULL, '2026-01-25 21:25:32'),
(5, 'Read Employee Handbook', 'Read and acknowledge the employee handbook', 'Compliance', 1, 5, NULL, '2026-01-25 21:25:32'),
(6, 'IT Account Setup', 'Setup your company email and system access', 'IT Setup', 1, 1, NULL, '2026-01-25 21:25:32'),
(7, 'Safety Training', 'Complete mandatory safety training', 'Training', 1, 7, NULL, '2026-01-25 21:25:32'),
(8, 'Submit Driver License', 'Upload valid professional driver license (for drivers only)', 'Documents', 1, 3, NULL, '2026-01-25 21:25:32'),
(9, 'Submit Driver\'s Copy', 'Can you submit the driver\'s copy, before it proceed to deliver the orders', 'Orientation', 1, 1, NULL, '2026-01-25 23:06:44'),
(10, 'Training about submission of Driver\'s Copy.', 'Learn it about the Driver\'s Copy, which is the agreement of the Copy. Submit it within 7 days', 'Training', 1, 7, NULL, '2026-01-25 23:41:26'),
(11, 'Truck Delivers', 'Deliver the items in destination within 7 days', 'Compliance', 1, 7, NULL, '2026-01-26 00:08:13');

-- --------------------------------------------------------

--
-- Table structure for table `otp_verifications`
--

CREATE TABLE `otp_verifications` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `otp_code` varchar(6) NOT NULL,
  `otp_type` enum('login','registration','password_reset','delete_user','archive_user','toggle_status') DEFAULT 'login',
  `is_verified` tinyint(1) DEFAULT '0',
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `verified_at` datetime DEFAULT NULL,
  `attempts` int DEFAULT '0',
  `max_attempts` int DEFAULT '3'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `otp_verifications`
--

INSERT INTO `otp_verifications` (`id`, `user_id`, `email`, `phone_number`, `otp_code`, `otp_type`, `is_verified`, `expires_at`, `created_at`, `verified_at`, `attempts`, `max_attempts`) VALUES
(32, NULL, 'christianvizmonte222@gmail.com', '09484914234', '517740', 'registration', 1, '2026-02-01 08:21:37', '2026-02-01 00:16:37', '2026-02-01 08:16:51', 0, 3),
(35, NULL, 'shawnvill0608@gmail.com', '09484914234', '706840', 'registration', 1, '2026-02-01 20:17:08', '2026-02-01 12:12:08', '2026-02-01 20:12:30', 0, 3),
(38, NULL, 'lynettearambulo948@gmail.com', '09910904731', '623398', 'registration', 1, '2026-02-01 21:05:16', '2026-02-01 13:00:16', '2026-02-01 21:00:43', 0, 3),
(39, 107, 'lynettearambulo948@gmail.com', NULL, '022706', 'login', 1, '2026-02-01 21:06:24', '2026-02-01 13:01:24', '2026-02-01 21:01:41', 0, 3),
(41, 7, 'manager@slatefreight.com', NULL, '508398', 'login', 0, '2026-02-01 21:43:37', '2026-02-01 13:38:37', NULL, 0, 3),
(44, NULL, 'orlindoleonards@gmail.com', '09910904871', '545632', 'registration', 1, '2026-02-06 22:53:29', '2026-02-06 14:48:29', '2026-02-06 22:48:57', 0, 3),
(45, 108, 'orlindoleonards@gmail.com', NULL, '473567', 'login', 1, '2026-02-06 22:54:11', '2026-02-06 14:49:11', '2026-02-06 22:49:46', 0, 3),
(47, 109, 'admin@slate.com', NULL, '013165', 'login', 0, '2026-02-07 22:20:27', '2026-02-07 14:15:28', NULL, 0, 3),
(54, 104, 'christianvizmonte222@gmail.com', NULL, '555444', 'delete_user', 1, '2026-02-08 17:14:57', '2026-02-08 09:09:57', '2026-02-08 17:10:26', 0, 3),
(59, 8, 'emp@slatefreight.com', NULL, '656622', 'login', 0, '2026-02-08 18:09:17', '2026-02-08 10:04:17', NULL, 0, 3),
(62, 104, 'admin@slatefreight.com', NULL, '869964', 'login', 0, '2026-02-08 18:14:27', '2026-02-08 10:09:27', NULL, 0, 3),
(80, NULL, 'jimskan18@gmail.com', '09910904871', '985609', 'registration', 1, '2026-02-10 14:20:24', '2026-02-10 06:15:24', '2026-02-10 14:16:10', 0, 3),
(81, 110, 'jimskan18@gmail.com', NULL, '034930', 'login', 1, '2026-02-10 14:21:56', '2026-02-10 06:16:56', '2026-02-10 14:17:16', 0, 3),
(86, 104, 'christianvizmonte222@gmail.com', NULL, '990498', 'archive_user', 1, '2026-02-11 16:14:56', '2026-02-11 08:09:56', '2026-02-11 16:10:22', 0, 3),
(98, 8, 'fboyboy50@gmail.com', NULL, '551012', 'login', 1, '2026-02-13 07:33:51', '2026-02-12 23:28:51', '2026-02-13 07:30:08', 1, 3),
(109, 104, 'christianvizmonte222@gmail.com', NULL, '952390', 'toggle_status', 1, '2026-02-14 03:31:24', '2026-02-13 19:26:24', '2026-02-14 03:26:36', 0, 3),
(110, 106, 'shawnvill0608@gmail.com', NULL, '158631', 'login', 1, '2026-02-14 03:51:12', '2026-02-13 19:46:12', '2026-02-14 03:46:46', 0, 3),
(111, 7, 'remediosboy7@gmail.com', NULL, '211999', 'login', 1, '2026-02-14 03:52:28', '2026-02-13 19:47:28', '2026-02-14 03:48:20', 0, 3),
(112, 6, 'fboy40224@gmail.com', NULL, '244243', 'login', 1, '2026-02-14 03:54:34', '2026-02-13 19:49:34', '2026-02-14 03:50:43', 0, 3),
(115, NULL, 'emmanueleljhaelloren@lanting.ph.education', '09484914234', '933189', 'registration', 0, '2026-02-14 04:56:05', '2026-02-13 20:51:05', NULL, 0, 3),
(116, NULL, 'emmanueleljhayelloren@gmail.com', '09484914234', '470480', 'registration', 0, '2026-02-14 04:57:10', '2026-02-13 20:52:11', NULL, 0, 3),
(118, NULL, 'rjcanuday1@gmail.com', '09910904871', '362147', 'registration', 0, '2026-02-14 05:01:16', '2026-02-13 20:56:16', NULL, 0, 3),
(121, 104, 'christianvizmonte222@gmail.com', NULL, '866626', 'login', 1, '2026-02-14 06:44:36', '2026-02-13 22:39:38', '2026-02-14 06:40:03', 0, 3);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_goals`
--

CREATE TABLE `performance_goals` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `set_by` int NOT NULL,
  `goal_title` varchar(255) NOT NULL,
  `goal_description` text,
  `category` enum('Safety','Punctuality','Quality','Teamwork','Compliance','Custom') DEFAULT 'Custom',
  `target_value` varchar(255) DEFAULT NULL,
  `current_value` varchar(255) DEFAULT NULL,
  `weight` int DEFAULT '25',
  `status` enum('Active','Completed','Failed','Cancelled') DEFAULT 'Active',
  `due_date` date DEFAULT NULL,
  `review_period` enum('3-Month','5-Month','6-Month','Annual') DEFAULT '3-Month',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_reviews`
--

CREATE TABLE `performance_reviews` (
  `id` int NOT NULL,
  `employee_id` int NOT NULL,
  `reviewer_id` int NOT NULL,
  `review_type` enum('Probation','Quarterly','Annual','Project') NOT NULL,
  `review_period_start` date DEFAULT NULL,
  `review_period_end` date DEFAULT NULL,
  `safety_score` tinyint DEFAULT NULL,
  `punctuality_score` tinyint DEFAULT NULL,
  `quality_score` tinyint DEFAULT NULL,
  `teamwork_score` tinyint DEFAULT NULL,
  `overall_score` decimal(3,2) DEFAULT NULL,
  `recommendation` enum('Pass Probation','Extend Probation','Terminate','Promote','Maintain') DEFAULT NULL,
  `comments` text,
  `status` enum('Draft','Submitted','HR Review','Approved','Rejected') DEFAULT 'Draft',
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `pre_employment_requirements`
--

CREATE TABLE `pre_employment_requirements` (
  `id` int NOT NULL,
  `application_id` int NOT NULL,
  `requirement_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `is_required` tinyint(1) DEFAULT '1',
  `status` enum('Pending','Submitted','Verified','Rejected') COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `file_path` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `verified_by` int DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rewards_catalog`
--

CREATE TABLE `rewards_catalog` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `points_cost` int NOT NULL DEFAULT '0',
  `category` varchar(50) DEFAULT 'General',
  `quantity_available` int DEFAULT '-1',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reward_redemptions`
--

CREATE TABLE `reward_redemptions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `reward_id` int NOT NULL,
  `points_spent` int NOT NULL DEFAULT '0',
  `status` enum('Pending','Approved','Rejected','Fulfilled') DEFAULT 'Pending',
  `approved_by` int DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `road_test_schedules`
--

CREATE TABLE `road_test_schedules` (
  `id` int NOT NULL,
  `application_id` int NOT NULL,
  `scheduled_date` datetime NOT NULL,
  `vehicle_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `route` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `examiner_id` int DEFAULT NULL,
  `status` enum('Scheduled','Completed','Cancelled','No Show') COLLATE utf8mb4_general_ci DEFAULT 'Scheduled',
  `result` enum('Pass','Fail','Pending') COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `role_type` enum('Admin','HR_Staff','Manager','Employee','Applicant') NOT NULL,
  `description` text,
  `access_level` enum('full','functional','department','self','external') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `role_type`, `description`, `access_level`, `created_at`) VALUES
(1, 'System Administrator', 'Admin', 'Full system access', 'full', '2026-01-25 21:25:31'),
(2, 'HR Staff', 'HR_Staff', 'Functional access - applicant management', 'functional', '2026-01-25 21:25:31'),
(3, 'HR Manager', 'HR_Staff', 'HR department head', 'functional', '2026-01-25 21:25:31'),
(4, 'Fleet Manager', 'Manager', 'Fleet Operations manager', 'department', '2026-01-25 21:25:31'),
(5, 'Warehouse Manager', 'Manager', 'Warehouse Operations manager', 'department', '2026-01-25 21:25:31'),
(6, 'Logistics Manager', 'Manager', 'Logistics manager', 'department', '2026-01-25 21:25:31'),
(7, 'Employee', 'Employee', 'Self-service access - own data only', 'self', '2026-01-25 21:25:31'),
(8, 'New Hire', 'Employee', 'Limited employee access during onboarding', 'self', '2026-01-25 21:25:31'),
(9, 'Applicant', 'Applicant', 'External portal access', 'external', '2026-01-25 21:25:31');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int NOT NULL,
  `role_id` int NOT NULL,
  `module` varchar(100) NOT NULL,
  `can_view` tinyint(1) DEFAULT '0',
  `can_create` tinyint(1) DEFAULT '0',
  `can_edit` tinyint(1) DEFAULT '0',
  `can_delete` tinyint(1) DEFAULT '0',
  `can_approve` tinyint(1) DEFAULT '0',
  `scope` enum('all','department','own') DEFAULT 'own'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `module`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_approve`, `scope`) VALUES
(1, 1, 'recruitment_settings', 1, 1, 1, 1, 1, 'all'),
(2, 1, 'job_requisition', 1, 1, 1, 1, 1, 'all'),
(3, 1, 'applicant_profiles', 1, 1, 1, 1, 1, 'all'),
(4, 1, 'onboarding_checklist', 1, 1, 1, 1, 1, 'all'),
(5, 1, 'employee_documents', 1, 1, 1, 1, 1, 'all'),
(6, 1, 'user_management', 1, 1, 1, 1, 1, 'all'),
(7, 1, 'audit_logs', 1, 0, 0, 0, 0, 'all'),
(8, 1, 'system_settings', 1, 1, 1, 1, 1, 'all'),
(9, 2, 'recruitment_settings', 1, 0, 0, 0, 0, 'all'),
(10, 2, 'job_requisition', 1, 1, 1, 0, 1, 'all'),
(11, 2, 'applicant_profiles', 1, 1, 1, 0, 1, 'all'),
(12, 2, 'onboarding_checklist', 1, 1, 1, 1, 1, 'all'),
(13, 2, 'employee_documents', 1, 1, 1, 0, 1, 'all'),
(14, 2, 'user_management', 1, 0, 0, 0, 0, 'all'),
(15, 4, 'recruitment_settings', 0, 0, 0, 0, 0, 'department'),
(16, 4, 'job_requisition', 1, 1, 0, 0, 0, 'department'),
(17, 4, 'applicant_profiles', 1, 0, 0, 0, 0, 'department'),
(18, 4, 'onboarding_checklist', 1, 0, 0, 0, 0, 'department'),
(19, 4, 'employee_documents', 1, 0, 0, 0, 0, 'department'),
(20, 4, 'performance_reviews', 1, 1, 1, 0, 0, 'department'),
(21, 7, 'onboarding_checklist', 1, 0, 1, 0, 0, 'own'),
(22, 7, 'employee_documents', 1, 1, 0, 0, 0, 'own'),
(23, 7, 'personal_info', 1, 0, 1, 0, 0, 'own');

-- --------------------------------------------------------

--
-- Table structure for table `social_recognitions`
--

CREATE TABLE `social_recognitions` (
  `id` int NOT NULL,
  `sender_id` int DEFAULT NULL,
  `receiver_id` int DEFAULT NULL,
  `recipient_id` int NOT NULL,
  `given_by` int DEFAULT NULL,
  `recognition_type` enum('Welcome','Kudos','Achievement','Milestone','Shoutout') DEFAULT 'Kudos',
  `message` text NOT NULL,
  `badge_icon` varchar(50) DEFAULT 'star',
  `is_system_generated` tinyint(1) DEFAULT '0',
  `is_public` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `points` int DEFAULT '0',
  `is_hidden` tinyint(1) DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'company_name', 'SLATE Freight Management', '2026-02-08 07:31:12', '2026-02-08 07:31:12'),
(2, 'company_email', 'admin@slatefreight.com', '2026-02-08 07:31:12', '2026-02-08 07:31:12'),
(3, 'company_phone', '09484914234', '2026-02-08 07:31:12', '2026-02-08 07:31:12'),
(4, 'company_address', 'Blk 4 Lot 19 Sitio Evergreen Pasong Tamo Quezon City', '2026-02-08 07:31:12', '2026-02-08 07:31:12'),
(5, 'timezone', 'Asia/Manila', '2026-02-08 07:31:12', '2026-02-08 07:31:12'),
(6, 'date_format', 'Y-m-d', '2026-02-08 07:31:12', '2026-02-08 07:31:12'),
(7, 'records_per_page', '25', '2026-02-08 07:31:12', '2026-02-08 07:31:12'),
(8, 'session_timeout', '10', '2026-02-08 07:31:41', '2026-02-08 07:31:41'),
(9, 'max_login_attempts', '10', '2026-02-08 07:31:41', '2026-02-08 07:31:41'),
(10, 'otp_expiry_minutes', '1', '2026-02-08 07:31:41', '2026-02-08 07:31:41'),
(11, 'password_min_length', '8', '2026-02-08 07:31:41', '2026-02-08 07:31:41'),
(12, 'maintenance_mode', '0', '2026-02-08 07:31:41', '2026-02-08 07:31:41'),
(13, 'allow_registration', '1', '2026-02-08 07:31:41', '2026-02-08 07:31:41');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('Administrator','HR Manager','Recruiter','Employee','Manager','Supervisor','Applicant','Service Provider','Supplier') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Employee',
  `full_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `company` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `verification_doc` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `full_name`, `email`, `company`, `phone`, `created_at`, `verification_doc`) VALUES
(1, 'admin', '$2y$12$.j.7vsnJ5mPvDUUNkQS6puP9Jm/xtib8q5t99aOp.JWMAKSI2rFx.', 'Administrator', 'System Administrator', 'admin@slate.com', 'SLATE Freight Management', '555-0001', '2025-09-09 00:54:34', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_accounts`
--

CREATE TABLE `user_accounts` (
  `id` int NOT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `personal_email` varchar(100) DEFAULT NULL,
  `company_email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role_id` int DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `job_title` varchar(100) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `employment_status` enum('Active','Probation','On Leave','Terminated','Resigned') DEFAULT 'Active',
  `profile_picture` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `status` enum('Active','Inactive','Pending','Archived') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `regularized_at` datetime DEFAULT NULL,
  `regularized_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_accounts`
--

INSERT INTO `user_accounts` (`id`, `employee_id`, `username`, `first_name`, `last_name`, `middle_name`, `personal_email`, `company_email`, `phone`, `password_hash`, `role_id`, `department_id`, `job_title`, `hire_date`, `employment_status`, `profile_picture`, `last_login`, `status`, `created_at`, `updated_at`, `regularized_at`, `regularized_by`) VALUES
(6, 'HR-001', 'hr@slatefreight.com', 'Boy', 'Reyes', NULL, 'fboy40224@gmail.com', 'hr@slatefreight.com', '09172222222', '$2y$10$eIvbsNFi/YE1GGtz.ZMdDOcyzoQXW7ByEVDQkan8dIPwuWI8Lvry.', 2, 1, 'HR Specialist', '2026-01-01', 'Active', NULL, '2026-02-13 19:50:43', 'Active', '2026-01-25 21:32:21', '2026-02-13 19:50:43', NULL, NULL),
(7, 'MGR-001', 'manager@slatefreight.com', 'Leonard', 'Fernandez', NULL, 'remediosboy7@gmail.com', 'manager@slatefreight.com', '09173333333', '$2y$10$ratjX9IOt2ogCpzTnzyqy.lGlfSYeY9c4KKpT1k8KAx9zbBm5hOg.', 4, 2, 'Fleet Operations Manager', '2026-01-01', 'Active', NULL, '2026-02-13 19:48:20', 'Active', '2026-01-25 21:32:21', '2026-02-13 19:48:20', NULL, NULL),
(8, 'EMP-001', 'emp@slatefreight.com', 'Vergel', 'Perez', NULL, 'fboyboy50@gmail.com', 'employee@slatefreight.com', '09174444444', '$2y$10$BaIPpbgVc7VVkDBc9ECZbutkSDrXVyfY1aw.xYtB7xZsQm1M5NWdm', 7, 2, 'Truck Driver', '2026-01-01', 'Active', NULL, '2026-02-12 23:30:08', 'Inactive', '2026-01-25 21:32:21', '2026-02-13 19:27:03', NULL, NULL),
(104, NULL, 'admin@slatefreight.com', 'CHRISTIAN', 'FLORES', NULL, 'christianvizmonte222@gmail.com', 'admin@slatefreight.com', '09484914234', '$2y$10$jmuM3h9yeIbQ25emXlZ1wuRATo1JPTCjzAsawyzn63r0U5C64M5Nm', 1, NULL, NULL, NULL, 'Active', NULL, '2026-02-13 22:40:03', 'Active', '2026-02-01 00:17:09', '2026-02-13 22:40:03', NULL, NULL),
(106, NULL, 'applicant@slatefreight.com', 'Shawn', 'Villanueva', NULL, 'shawnvill0608@gmail.com', NULL, '09484914234', '$2y$10$j5inXNHPYt1dVh20DyzjmeLiQ4EBI2VAQJw.Jcwr.BTuNeGhWO/ny', 9, NULL, NULL, NULL, 'Active', NULL, '2026-02-13 19:46:46', 'Active', '2026-02-01 12:12:37', '2026-02-13 19:46:46', NULL, NULL),
(107, NULL, NULL, 'Lynette', 'Arambulo', NULL, 'lynettearambulo948@gmail.com', NULL, '09910904731', '$2y$10$ees4ikVCN4as9ufBym/Ul.LVQL6qFj9GjowDmh6j0On/KeReVEE1C', 9, NULL, NULL, NULL, 'Active', NULL, '2026-02-01 13:01:41', 'Active', '2026-02-01 13:00:52', '2026-02-13 19:26:09', NULL, NULL),
(108, NULL, NULL, 'Orlindo', 'Leonards', NULL, 'orlindoleonards@gmail.com', NULL, '09910904871', '$2y$10$p5F52YkWWGi0Um3a1Cu1EOfXgncR3ZQOdPsRPR/kNUjx4ILA09MK6', 9, NULL, NULL, NULL, 'Active', NULL, '2026-02-06 14:49:46', 'Active', '2026-02-06 14:48:57', '2026-02-06 14:49:46', NULL, NULL),
(110, NULL, NULL, 'James', 'Harden', NULL, 'jimskan18@gmail.com', NULL, '09910904871', '$2y$10$.8W9QKyQzlny/Z/7Si5MMut1cXcgzwmSHocDlblS0RqyYAPhl8VUm', 9, NULL, NULL, NULL, 'Active', NULL, '2026-02-10 06:17:16', 'Active', '2026-02-10 06:16:10', '2026-02-10 06:17:16', NULL, NULL),
(111, NULL, NULL, 'crisanta mae', 'albelda', NULL, '09483946751ian@gmail.com', NULL, '09852208905', '$2y$10$LPDPkZBWlVoXvVfyXBY7/O0z7LHZJ0yrgiQJGmEJJyGBCrHZwQugS', 9, NULL, NULL, NULL, 'Active', NULL, NULL, 'Active', '2026-02-13 06:59:25', '2026-02-13 06:59:25', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applicant_notifications`
--
ALTER TABLE `applicant_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`);

--
-- Indexes for table `applicant_profiles`
--
ALTER TABLE `applicant_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `application_status_history`
--
ALTER TABLE `application_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_app` (`application_id`),
  ADD KEY `idx_changed_by` (`changed_by`);

--
-- Indexes for table `approval_workflows`
--
ALTER TABLE `approval_workflows`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `department_code` (`department_code`),
  ADD KEY `parent_department_id` (`parent_department_id`);

--
-- Indexes for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `employee_onboarding_progress`
--
ALTER TABLE `employee_onboarding_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_task` (`user_id`,`task_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `employee_requirements`
--
ALTER TABLE `employee_requirements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_doc` (`user_id`,`document_type`);

--
-- Indexes for table `exam_schedules`
--
ALTER TABLE `exam_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_app` (`application_id`);

--
-- Indexes for table `interviews`
--
ALTER TABLE `interviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `interviewer_id` (`interviewer_id`),
  ADD KEY `idx_scheduled_date` (`scheduled_date`);

--
-- Indexes for table `interview_schedules`
--
ALTER TABLE `interview_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_app` (`application_id`),
  ADD KEY `idx_interviewer` (`interviewer_id`);

--
-- Indexes for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_job_posting` (`job_posting_id`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `job_offers`
--
ALTER TABLE `job_offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_app` (`application_id`);

--
-- Indexes for table `job_postings`
--
ALTER TABLE `job_postings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_department` (`department_id`);

--
-- Indexes for table `job_requisitions`
--
ALTER TABLE `job_requisitions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `onboarding_requirements`
--
ALTER TABLE `onboarding_requirements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `onboarding_tasks`
--
ALTER TABLE `onboarding_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_specific` (`department_specific`);

--
-- Indexes for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_otp_code` (`otp_code`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `performance_goals`
--
ALTER TABLE `performance_goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `set_by` (`set_by`);

--
-- Indexes for table `performance_reviews`
--
ALTER TABLE `performance_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `reviewer_id` (`reviewer_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `pre_employment_requirements`
--
ALTER TABLE `pre_employment_requirements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_app` (`application_id`);

--
-- Indexes for table `rewards_catalog`
--
ALTER TABLE `rewards_catalog`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reward_redemptions`
--
ALTER TABLE `reward_redemptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reward_id` (`reward_id`);

--
-- Indexes for table `road_test_schedules`
--
ALTER TABLE `road_test_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_app` (`application_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_module` (`role_id`,`module`);

--
-- Indexes for table `social_recognitions`
--
ALTER TABLE `social_recognitions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipient_id` (`recipient_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_accounts`
--
ALTER TABLE `user_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `company_email` (`company_email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `department_id` (`department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applicant_notifications`
--
ALTER TABLE `applicant_notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applicant_profiles`
--
ALTER TABLE `applicant_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `application_status_history`
--
ALTER TABLE `application_status_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `approval_workflows`
--
ALTER TABLE `approval_workflows`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=180;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `employee_documents`
--
ALTER TABLE `employee_documents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_onboarding_progress`
--
ALTER TABLE `employee_onboarding_progress`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `employee_requirements`
--
ALTER TABLE `employee_requirements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `exam_schedules`
--
ALTER TABLE `exam_schedules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `interviews`
--
ALTER TABLE `interviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `interview_schedules`
--
ALTER TABLE `interview_schedules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_offers`
--
ALTER TABLE `job_offers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_postings`
--
ALTER TABLE `job_postings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `job_requisitions`
--
ALTER TABLE `job_requisitions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `onboarding_requirements`
--
ALTER TABLE `onboarding_requirements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `onboarding_tasks`
--
ALTER TABLE `onboarding_tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `performance_goals`
--
ALTER TABLE `performance_goals`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `performance_reviews`
--
ALTER TABLE `performance_reviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pre_employment_requirements`
--
ALTER TABLE `pre_employment_requirements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rewards_catalog`
--
ALTER TABLE `rewards_catalog`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reward_redemptions`
--
ALTER TABLE `reward_redemptions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `road_test_schedules`
--
ALTER TABLE `road_test_schedules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `social_recognitions`
--
ALTER TABLE `social_recognitions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `user_accounts`
--
ALTER TABLE `user_accounts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applicant_notifications`
--
ALTER TABLE `applicant_notifications`
  ADD CONSTRAINT `applicant_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `applicant_profiles`
--
ALTER TABLE `applicant_profiles`
  ADD CONSTRAINT `fk_applicant_user` FOREIGN KEY (`user_id`) REFERENCES `user_accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `approval_workflows`
--
ALTER TABLE `approval_workflows`
  ADD CONSTRAINT `approval_workflows_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `user_accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`parent_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD CONSTRAINT `employee_documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_documents_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `user_accounts` (`id`);

--
-- Constraints for table `employee_onboarding_progress`
--
ALTER TABLE `employee_onboarding_progress`
  ADD CONSTRAINT `employee_onboarding_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_onboarding_progress_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `onboarding_tasks` (`id`),
  ADD CONSTRAINT `employee_onboarding_progress_ibfk_3` FOREIGN KEY (`verified_by`) REFERENCES `user_accounts` (`id`);

--
-- Constraints for table `employee_requirements`
--
ALTER TABLE `employee_requirements`
  ADD CONSTRAINT `employee_requirements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `interviews`
--
ALTER TABLE `interviews`
  ADD CONSTRAINT `interviews_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `job_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `interviews_ibfk_2` FOREIGN KEY (`interviewer_id`) REFERENCES `user_accounts` (`id`);

--
-- Constraints for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD CONSTRAINT `job_applications_ibfk_1` FOREIGN KEY (`job_posting_id`) REFERENCES `job_postings` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `job_applications_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `user_accounts` (`id`);

--
-- Constraints for table `job_postings`
--
ALTER TABLE `job_postings`
  ADD CONSTRAINT `job_postings_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `job_postings_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `user_accounts` (`id`);

--
-- Constraints for table `job_requisitions`
--
ALTER TABLE `job_requisitions`
  ADD CONSTRAINT `job_requisitions_ibfk_1` FOREIGN KEY (`requested_by`) REFERENCES `user_accounts` (`id`),
  ADD CONSTRAINT `job_requisitions_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `onboarding_requirements`
--
ALTER TABLE `onboarding_requirements`
  ADD CONSTRAINT `onboarding_requirements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `user_accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `onboarding_tasks`
--
ALTER TABLE `onboarding_tasks`
  ADD CONSTRAINT `onboarding_tasks_ibfk_1` FOREIGN KEY (`department_specific`) REFERENCES `departments` (`id`);

--
-- Constraints for table `performance_goals`
--
ALTER TABLE `performance_goals`
  ADD CONSTRAINT `performance_goals_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `user_accounts` (`id`),
  ADD CONSTRAINT `performance_goals_ibfk_2` FOREIGN KEY (`set_by`) REFERENCES `user_accounts` (`id`);

--
-- Constraints for table `performance_reviews`
--
ALTER TABLE `performance_reviews`
  ADD CONSTRAINT `performance_reviews_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `user_accounts` (`id`),
  ADD CONSTRAINT `performance_reviews_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `user_accounts` (`id`),
  ADD CONSTRAINT `performance_reviews_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `user_accounts` (`id`);

--
-- Constraints for table `reward_redemptions`
--
ALTER TABLE `reward_redemptions`
  ADD CONSTRAINT `reward_redemptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reward_redemptions_ibfk_2` FOREIGN KEY (`reward_id`) REFERENCES `rewards_catalog` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `social_recognitions`
--
ALTER TABLE `social_recognitions`
  ADD CONSTRAINT `social_recognitions_ibfk_1` FOREIGN KEY (`recipient_id`) REFERENCES `user_accounts` (`id`);

--
-- Constraints for table `user_accounts`
--
ALTER TABLE `user_accounts`
  ADD CONSTRAINT `user_accounts_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `user_accounts_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
