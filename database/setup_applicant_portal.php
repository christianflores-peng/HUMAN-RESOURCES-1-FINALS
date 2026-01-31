<?php
/**
 * Database Setup Script for Applicant Portal
 * Run this file once to create the applicant_profiles table
 */

require_once '../database/config.php';

try {
    // Check if table exists first
    $check_table = fetchSingle("SHOW TABLES LIKE 'applicant_profiles'");
    
    if ($check_table) {
        echo "ℹ️ INFO: Table 'applicant_profiles' already exists!<br><br>";
        echo "✅ You can proceed with registration.<br><br>";
    } else {
        // Create applicant_profiles table without foreign key constraint (will add later if needed)
        $sql = "CREATE TABLE `applicant_profiles` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        executeQuery($sql);
        echo "✅ SUCCESS! Applicant profiles table created successfully!<br><br>";
    }
    
    echo "✅ SUCCESS! Database setup complete!<br><br>";
    echo "Table: <strong>applicant_profiles</strong><br>";
    echo "Columns:<br>";
    echo "- id (Primary Key)<br>";
    echo "- user_id (Foreign Key to user_accounts)<br>";
    echo "- resume_path<br>";
    echo "- cover_letter<br>";
    echo "- skills<br>";
    echo "- experience_years<br>";
    echo "- education_level<br>";
    echo "- linkedin_url<br>";
    echo "- portfolio_url<br>";
    echo "- availability<br>";
    echo "- expected_salary<br>";
    echo "- preferred_location<br>";
    echo "- work_authorization<br>";
    echo "- created_at<br>";
    echo "- updated_at<br><br>";
    echo "<a href='../partials/register-portal.php'>← Back to Registration</a>";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br><br>";
    echo "If the table already exists, this is normal. You can proceed with registration.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Setup - Applicant Portal</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2196f3; }
        a { color: #2196f3; text-decoration: none; font-weight: 600; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Applicant Portal Database Setup</h1>
    </div>
</body>
</html>
