# Email Setup Guide for SLATE HR System

## Problem
Emails are not being sent from the application because PHP's `mail()` function requires proper SMTP configuration.

## Solution Options

### Option 1: Use Laragon's Built-in Mail Catcher (Recommended for Development)

Laragon doesn't come with a mail server by default. You need to install a mail catcher:

1. **Install MailHog (Recommended)**
   - Download MailHog: https://github.com/mailhog/MailHog/releases
   - Place `MailHog.exe` in `C:\laragon\bin\mailhog\`
   - Run MailHog: Double-click `MailHog.exe`
   - Access web interface: http://localhost:8025

2. **Configure PHP to use MailHog**
   - Open `C:\laragon\bin\php\php-8.x.x\php.ini`
   - Find and update these lines:
   ```ini
   [mail function]
   SMTP = localhost
   smtp_port = 1025
   sendmail_path = "C:\laragon\bin\mailhog\mhsendmail.exe"
   ```
   - Restart Laragon

### Option 2: Use Gmail SMTP (For Production/Testing with Real Emails)

Since PHP's `mail()` doesn't work well on Windows, use PHPMailer:

1. **Install PHPMailer via Composer**
   ```bash
   cd C:\laragon\www\HR1
   composer require phpmailer/phpmailer
   ```

2. **Create SMTP Configuration File**
   Create `includes/smtp_config.php`:
   ```php
   <?php
   return [
       'host' => 'smtp.gmail.com',
       'port' => 587,
       'username' => 'your-email@gmail.com',
       'password' => 'your-app-password', // Use App Password, not regular password
       'encryption' => 'tls',
       'from_email' => 'your-email@gmail.com',
       'from_name' => 'SLATE Freight HR'
   ];
   ```

3. **Get Gmail App Password**
   - Go to: https://myaccount.google.com/apppasswords
   - Create new app password for "Mail"
   - Copy the 16-character password

4. **Update email_helper.php to use PHPMailer**
   (Already prepared in the codebase)

### Option 3: Use Fake Sendmail (Quick Windows Solution)

1. **Download Fake Sendmail**
   - Download: https://github.com/sendmail-tls/sendmail-tls
   - Extract to `C:\laragon\bin\sendmail\`

2. **Configure sendmail.ini**
   ```ini
   [sendmail]
   smtp_server=smtp.gmail.com
   smtp_port=587
   auth_username=your-email@gmail.com
   auth_password=your-app-password
   force_sender=your-email@gmail.com
   ```

3. **Update php.ini**
   ```ini
   sendmail_path = "C:\laragon\bin\sendmail\sendmail.exe -t"
   ```

## Current Implementation

The system now uses:
- `includes/email_helper.php` - Email sending functions
- `sendApplicantWelcomeEmail()` - Sends welcome email with credentials
- Proper HTML email formatting
- Error logging for debugging

## Testing Email

1. Submit a job application
2. Check error logs: `C:\laragon\www\HR1\error.log`
3. If using MailHog: Check http://localhost:8025
4. If using Gmail: Check recipient's inbox

## Troubleshooting

**Emails not sending:**
- Check PHP error logs
- Verify SMTP settings
- Test with simple mail() script
- Check firewall/antivirus blocking SMTP

**Gmail blocking:**
- Use App Password, not regular password
- Enable "Less secure app access" (if needed)
- Check Gmail security settings

## For Production

Use a proper email service:
- SendGrid
- Mailgun
- Amazon SES
- Postmark

These services provide better deliverability and tracking.
