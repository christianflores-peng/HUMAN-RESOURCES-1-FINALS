-- OTP Verification Table
-- Stores one-time passwords for login and registration verification

CREATE TABLE IF NOT EXISTS otp_verifications (
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
    INDEX idx_expires (expires_at),
    FOREIGN KEY (user_id) REFERENCES user_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Clean up expired OTPs (run periodically)
-- DELETE FROM otp_verifications WHERE expires_at < NOW();
