-- Add verification_doc column to users table
-- Run this in phpMyAdmin to add the verification document column

USE `hr1_hr1data`;

-- Add verification_doc column
-- Note: If column already exists, this will show an error (safe to ignore)
ALTER TABLE `users` 
ADD COLUMN `verification_doc` VARCHAR(255) DEFAULT NULL;

-- Verify the column was added
DESCRIBE `users`;
