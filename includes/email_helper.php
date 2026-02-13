<?php
/**
 * Email Helper - Send emails using Gmail SMTP with proper authentication
 */

require_once __DIR__ . '/GmailSMTP.php';

/**
 * Send HTML email using Gmail SMTP
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body HTML email body
 * @param string $fromName Sender name
 * @param string $fromEmail Sender email
 * @return bool True if email was sent successfully
 */
function sendHtmlEmail($to, $subject, $body, $fromName = 'SLATE Freight HR', $fromEmail = '') {
    // Validate email
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email address: {$to}");
        return false;
    }
    
    try {
        // Load SMTP config
        $config = include __DIR__ . '/smtp_config.php';
        
        // Check if SMTP is configured
        if (empty($config['smtp_username']) || $config['smtp_username'] === 'your-email@gmail.com' || 
            $config['smtp_password'] === 'PASTE-YOUR-16-CHAR-PASSWORD-HERE') {
            error_log("SMTP not configured. Please update smtp_config.php with your Gmail credentials.");
            return false;
        }
        
        // Set from email (use config if not provided)
        if (empty($fromEmail)) {
            $fromEmail = $config['from_email'];
        }
        
        // Use GmailSMTP class for proper authentication
        $mailer = new GmailSMTP();
        $result = $mailer->send($to, $subject, $body, $fromName, $fromEmail);
        
        if ($result) {
            error_log("Email sent successfully to: {$to}");
        } else {
            error_log("Failed to send email to: {$to}");
            error_log("Error: " . $mailer->getLastError());
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send welcome email to new applicant with login credentials
 * 
 * @param string $email Applicant email
 * @param string $firstName Applicant first name
 * @param string $lastName Applicant last name
 * @param string $jobTitle Job title applied for
 * @param string $tempPassword Temporary password
 * @return bool True if email was sent successfully
 */
function sendApplicantWelcomeEmail($email, $firstName, $lastName, $jobTitle, $tempPassword) {
    $subject = "Welcome to SLATE Freight - Your Application Account";
    
    $loginUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') 
                . "://" . $_SERVER['HTTP_HOST'] . "/HR1/auth/login.php";
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #0ea5e9; color: white; padding: 20px; text-align: center; }
            .content { background: #f9fafb; padding: 30px; }
            .credentials { background: #f0f9ff; padding: 20px; border-left: 4px solid #0ea5e9; margin: 20px 0; }
            .button { display: inline-block; padding: 12px 30px; background: #0ea5e9; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; color: #64748b; font-size: 12px; }
            ul { padding-left: 20px; }
            li { margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Welcome to SLATE Freight!</h1>
            </div>
            <div class='content'>
                <h2>Hello {$firstName} {$lastName},</h2>
                <p>Thank you for applying for the position of <strong>{$jobTitle}</strong>.</p>
                <p>We have created an account for you to track your application status and communicate with our HR team.</p>
                
                <div class='credentials'>
                    <h3 style='margin-top: 0; color: #0284c7;'>Your Login Credentials:</h3>
                    <p><strong>Email:</strong> {$email}</p>
                    <p><strong>Temporary Password:</strong> <code style='background: #e0f2fe; padding: 5px 10px; border-radius: 3px; font-size: 14px;'>{$tempPassword}</code></p>
                </div>
                
                <div style='text-align: center;'>
                    <a href='{$loginUrl}' class='button'>Login to Your Account</a>
                </div>
                
                <p><strong>⚠️ Important:</strong> Please change your password after your first login for security purposes.</p>
                
                <h3>What You Can Do:</h3>
                <ul>
                    <li>Track your application status in real-time</li>
                    <li>View interview schedules (if applicable)</li>
                    <li>Update your profile information</li>
                    <li>Communicate with our HR team</li>
                    <li>Receive notifications about your application</li>
                </ul>
                
                <p>We will review your application carefully and contact you if you are shortlisted for the next stage of the recruitment process.</p>
                
                <p>If you have any questions, please don't hesitate to contact our HR team.</p>
                
                <p>Best regards,<br>
                <strong>SLATE Freight HR Team</strong></p>
            </div>
            <div class='footer'>
                <p>© 2025 SLATE Freight Management System. All rights reserved.</p>
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendHtmlEmail($email, $subject, $body);
}
