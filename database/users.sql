-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 09, 2025 at 09:18 AM
-- Server version: 10.11.14-MariaDB-ubu2204
-- PHP Version: 8.0.30

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
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Applicant Management','Recruitment Management','New Hire Onboarding','Performance Management (Initial)','Social Recognition','Competency Management','admin_Human Resource 1','Learning Management','Training Management','Succession Planning','Employee Self-Service (ESS)','admin_Human Resource 2','Time and Attendance System','Shift and Schedule Management','Timesheet Management','Leave Management','Claims and Reimbursement','admin_Human Resource 3','Core Human Capital Management (HCM)','Payroll Management','Compensation Planning','HR Analytics Dashboard','HMO & Benefits Administration','admin_Human Resource 4','Shipment Booking & Routing System','Consolidation & Deconsolidation Management','House & Master Bill of Lading Generator','Shipment File & Tracking System','Purchase Order Integration System','Service Provider Management','admin_Core Transaction 1','Service Network & Route Planner','Rate & Tariff Management System','Standard Operating Procedure (SOP) Manager','Scheduler & Transit Timetable Management','admin_Core Transaction 2','Customer Relationship Management (CRM)','Contract & SLA Monitoring','E-Documentation & Compliance Manager','Business Intelligence & Freight Analytics','Customer Portal & Notification Hub','admin_Core Transaction 3','Smart Warehousing System (SWS)','Procurement & Sourcing Management (PSM)','Project Logistics Tracker (PLT)','Asset Lifecycle & Maintenance (ALMS)','Document Tracking & Logistics Records (DTRS)','admin_Logistics 1','Fleet & Vehicle Management (FVM)','Vehicle Reservation & Dispatch System (VRDS)','Driver and Trip Performance Monitoring','Transport Cost Analysis & Optimization (TCAO)','Mobile Fleet Command App (optional)','admin_Logistics 2','Disbursement','Budget Management','Collection','General Ledger','Accounts Payable / Accounts Receivables','admin_Financials') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(4, 'psm', '$2y$12$DknfgcIeCp8jV97DK3dfCuIVeGgDG5Bfj1Vx0loulO1tbkNop6CTG', 'Procurement & Sourcing Management (PSM)', '2025-09-08 17:03:20'),
(5, 'plt', '$2y$12$9py/Bu0YTGWQenvNPSYOWOFlPvGdHS67coP2oLWg5KTzRId2naqRq', 'Project Logistics Tracker (PLT)', '2025-09-08 17:03:36'),
(6, 'alms', '$2y$12$NYlWtso2BQcWmPymBqtl8.AC9Ok1ulI1MKtzxIvOW37WFO3oBEwJW', 'Asset Lifecycle & Maintenance (ALMS)', '2025-09-08 17:04:13'),
(7, 'dtrs', '$2y$12$oVWJ0yHSet3cy43UbxBCa.8FXZHaEp3SltX6vGLms2qGj.sZjmgIG', 'Document Tracking & Logistics Records (DTRS)', '2025-09-08 17:04:31'),
(8, 'warehouse', '$2y$12$dtpR5MjH0M16LCh83DcXV.7t44AO0H4eOOMWd3pVn/qfeE.93xqAG', 'Smart Warehousing System (SWS)', '2025-09-08 18:54:59'),
(11, 'sws', '$2y$12$Ok7TfvV5Z1AuR/1YgAg.rOhqWvtwk2s9WRTcGpqByH88RVSKP7lCG', 'Smart Warehousing System (SWS)', '2025-09-09 08:53:45'),
(12, 'admin', '$2y$12$.j.7vsnJ5mPvDUUNkQS6puP9Jm/xtib8q5t99aOp.JWMAKSI2rFx.', 'admin_Logistics 1', '2025-09-09 08:54:34');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
