-- Update users table to include additional registration fields
-- Run this after the main schema to add the new columns

USE `hr1_hr1data`;

-- Add new columns to users table
ALTER TABLE `users` 
ADD COLUMN `full_name` varchar(255) DEFAULT NULL AFTER `role`,
ADD COLUMN `email` varchar(255) DEFAULT NULL AFTER `full_name`,
ADD COLUMN `company` varchar(255) DEFAULT NULL AFTER `email`,
ADD COLUMN `phone` varchar(20) DEFAULT NULL AFTER `company`;

-- Add unique constraint for email
ALTER TABLE `users` 
ADD UNIQUE KEY `email` (`email`);

-- Update existing users with default values if needed
UPDATE `users` 
SET 
    `full_name` = CONCAT('User ', `id`),
    `email` = CONCAT(`username`, '@example.com'),
    `company` = 'SLATE Freight Management',
    `phone` = '555-0000'
WHERE `full_name` IS NULL;

-- Add a default 'Employee' role to the enum if it doesn't exist
-- Note: This might require recreating the table if the enum is already in use
-- For now, we'll use one of the existing roles for new registrations
