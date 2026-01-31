<?php
/**
 * Setup OTP Verification Table
 * Run this file once to create the required table
 */

require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS otp_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        email VARCHAR(255) NOT NULL,
        phone_number VARCHAR(20) NULL,
        otp_code VARCHAR(6) NOT NULL,
        otp_type ENUM('login', 'registration', 'password_reset') DEFAULT 'login',
        is_verified BOOLEAN DEFAULT FALSE,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        verified_at DATETIME NULL,
        attempts INT DEFAULT 0,
        max_attempts INT DEFAULT 3,
        INDEX idx_email (email),
        INDEX idx_otp_code (otp_code),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql);
    
    echo "<h2 style='color: green;'>✅ OTP Verification table created successfully!</h2>";
    echo "<p>You can now use the registration and login pages with OTP verification.</p>";
    echo "<p><a href='../partials/register-applicant.php?terms_accepted=true'>Go to Registration</a></p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ Error creating table:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
