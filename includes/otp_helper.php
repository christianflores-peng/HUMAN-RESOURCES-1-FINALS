<?php
/**
 * OTP Helper Functions
 * Handles OTP generation, sending, and verification
 */

require_once __DIR__ . '/../database/config.php';

/**
 * Generate a 6-digit OTP code
 */
function generateOTP() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Create and store OTP for a user
 */
function createOTP($email, $phone_number = null, $otp_type = 'login', $user_id = null) {
    $otp_code = generateOTP();

    // OTP expiry is fixed to 1 minute (system-wide)
    $expiry_minutes = 1;
    $expires_at = date('Y-m-d H:i:s', strtotime('+' . $expiry_minutes . ' minute'));

    // Keep System Settings UI consistent (best-effort)
    try {
        executeQuery(
            "INSERT INTO system_settings (setting_key, setting_value) VALUES ('otp_expiry_minutes', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
            [(string)$expiry_minutes]
        );
    } catch (Exception $e) {
        // ignore (table may not exist yet)
    }
    
    // Delete any existing OTPs for this email and type
    executeQuery("DELETE FROM otp_verifications WHERE email = ? AND otp_type = ?", [$email, $otp_type]);
    
    // Insert new OTP
    insertRecord("INSERT INTO otp_verifications (user_id, email, phone_number, otp_code, otp_type, expires_at) VALUES (?, ?, ?, ?, ?, ?)", 
        [$user_id, $email, $phone_number, $otp_code, $otp_type, $expires_at]);
    
    return $otp_code;
}

/**
 * Verify OTP code
 */
function verifyOTP($email, $otp_code, $otp_type = 'login') {
    $otp = fetchSingle("SELECT * FROM otp_verifications WHERE email = ? AND otp_code = ? AND otp_type = ? AND is_verified = 0", 
        [$email, $otp_code, $otp_type]);
    
    if (!$otp) {
        return ['success' => false, 'message' => 'Invalid OTP code.'];
    }
    
    // Check if expired
    if (strtotime($otp['expires_at']) < time()) {
        executeQuery("DELETE FROM otp_verifications WHERE id = ?", [$otp['id']]);
        return ['success' => false, 'message' => 'OTP has expired. Please request a new one.'];
    }
    
    // Check attempts
    if ($otp['attempts'] >= $otp['max_attempts']) {
        executeQuery("DELETE FROM otp_verifications WHERE id = ?", [$otp['id']]);
        return ['success' => false, 'message' => 'Too many failed attempts. Please request a new OTP.'];
    }
    
    // Mark as verified
    executeQuery("UPDATE otp_verifications SET is_verified = 1, verified_at = NOW() WHERE id = ?", [$otp['id']]);
    
    return ['success' => true, 'message' => 'OTP verified successfully.', 'user_id' => $otp['user_id']];
}

/**
 * Increment failed OTP attempts
 */
function incrementOTPAttempts($email, $otp_type = 'login') {
    executeQuery("UPDATE otp_verifications SET attempts = attempts + 1 WHERE email = ? AND otp_type = ? AND is_verified = 0", 
        [$email, $otp_type]);
}

/**
 * Send OTP via Email
 */
function sendOTPEmail($email, $otp_code, $user_name = 'User') {
    require_once __DIR__ . '/email_helper.php';
    
    $subject = 'Your SLATE Verification Code';
    $body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
        <div style='text-align: center; margin-bottom: 30px;'>
            <h1 style='color: #0ea5e9; margin: 0;'>SLATE</h1>
            <p style='color: #64748b; margin: 5px 0;'>Freight Management System</p>
        </div>
        
        <div style='background: #f8fafc; border-radius: 12px; padding: 30px; text-align: center;'>
            <h2 style='color: #1e293b; margin: 0 0 10px;'>Hello, {$user_name}!</h2>
            <p style='color: #64748b; margin: 0 0 25px;'>Your verification code is:</p>
            
            <div style='background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: white; font-size: 32px; font-weight: bold; letter-spacing: 8px; padding: 20px 40px; border-radius: 8px; display: inline-block;'>
                {$otp_code}
            </div>
            
            <p style='color: #94a3b8; margin: 25px 0 0; font-size: 14px;'>
                This code will expire in <strong>1 minute</strong>.<br>
                If you didn't request this code, please ignore this email.
            </p>
        </div>
        
        <div style='text-align: center; margin-top: 30px; color: #94a3b8; font-size: 12px;'>
            <p>&copy; " . date('Y') . " SLATE Freight Management System. All rights reserved.</p>
        </div>
    </div>
    ";
    
    return sendHtmlEmail($email, $subject, $body);
}

/**
 * Send OTP via SMS (placeholder - requires SMS gateway integration)
 */
function sendOTPSMS($phone_number, $otp_code) {
    // TODO: Integrate with SMS gateway (Twilio, Semaphore, etc.)
    // For now, this is a placeholder that logs the OTP
    error_log("OTP SMS to {$phone_number}: {$otp_code}");
    
    // Return true for demo purposes
    // In production, integrate with actual SMS provider
    return true;
}

/**
 * Send OTP to both Email and SMS
 */
function sendOTP($email, $otp_code, $user_name = 'User', $phone_number = null) {
    $email_sent = sendOTPEmail($email, $otp_code, $user_name);
    $sms_sent = $phone_number ? sendOTPSMS($phone_number, $otp_code) : true;
    
    return ['email' => $email_sent, 'sms' => $sms_sent];
}

/**
 * Mask email address for display (e.g. ch*****es@gmail.com)
 */
function maskEmail($email) {
    if (empty($email) || strpos($email, '@') === false) return '***@***.com';
    list($local, $domain) = explode('@', $email);
    $len = strlen($local);
    if ($len <= 2) {
        $masked_local = $local[0] . str_repeat('*', max($len - 1, 1));
    } else {
        $show = max(2, floor($len * 0.3));
        $masked_local = substr($local, 0, $show) . str_repeat('*', $len - $show);
    }
    return $masked_local . '@' . $domain;
}

/**
 * Clean up expired OTPs
 */
function cleanupExpiredOTPs() {
    executeQuery("DELETE FROM otp_verifications WHERE expires_at < NOW()");
}
?>
