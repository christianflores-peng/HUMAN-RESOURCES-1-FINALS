<?php
/**
 * SMTP Configuration for Email Sending
 * 
 * IMPORTANT: Update these settings with your actual Gmail credentials
 * 
 * For Gmail:
 * 1. Enable 2-Step Verification in your Google Account
 * 2. Generate an App Password: https://myaccount.google.com/apppasswords
 * 3. Use the App Password (not your regular Gmail password)
 */

return [
    // SMTP Server Settings
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587, // Use 587 for TLS, 465 for SSL
    'smtp_secure' => 'tls', // 'tls' or 'ssl'
    
    // Gmail Account Credentials
    // REPLACE 'PASTE-YOUR-16-CHAR-PASSWORD-HERE' WITH YOUR ACTUAL APP PASSWORD FROM GOOGLE
    'smtp_username' => 'ljhay130@gmail.com', // Your Gmail address
    'smtp_password' => 'gfvwnmiedpateiou', // Get from: https://myaccount.google.com/apppasswords
    
    // From Email Settings
    'from_email' => 'ljhay130@gmail.com', // Same as smtp_username
    'from_name' => 'SLATE Freight HR System',
    
    // Reply-To Email (optional)
    'reply_to' => 'hr@slatefreight.com',
    
    // Email Settings
    'charset' => 'UTF-8',
    'timeout' => 30,
    
    // Debug Mode (set to false in production)
    'debug' => false, // Disabled for production
];

/**
 * SETUP INSTRUCTIONS:
 * 
 * 1. Go to: https://myaccount.google.com/security
 * 2. Enable "2-Step Verification" if not already enabled
 * 3. Go to: https://myaccount.google.com/apppasswords
 * 4. Select "Mail" and "Windows Computer" (or Other)
 * 5. Click "Generate"
 * 6. Copy the 16-character password (e.g., "abcd efgh ijkl mnop")
 * 7. Paste it in 'smtp_password' above (remove spaces: "abcdefghijklmnop")
 * 8. Update 'smtp_username' and 'from_email' with your Gmail address
 * 9. Save this file
 * 10. Test by submitting an application in apply.php
 * 
 * SECURITY NOTE:
 * - Never commit this file to version control (add to .gitignore)
 * - Keep your App Password secure
 * - Use environment variables in production
 */
