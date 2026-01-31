# PHP.ini Update Instructions for Email Configuration

## File Location
`c:\laragon\bin\php\php-8.3.20-Win32-vs16-x64\php.ini`

## Changes Needed

### Find the [mail function] section and update these lines:

**Before:**
```ini
[mail function]
; For Win32 only.
; http://php.net/smtp
SMTP = localhost
; http://php.net/smtp-port
smtp_port = 25
```

**After:**
```ini
[mail function]
; For Win32 only.
; http://php.net/smtp
SMTP = localhost
; http://php.net/smtp-port
smtp_port = 1025
```

## Steps to Update:

1. **Open php.ini file:**
   - Press `Windows + R`
   - Type: `notepad c:\laragon\bin\php\php-8.3.20-Win32-vs16-x64\php.ini`
   - Press Enter

2. **Find [mail function] section:**
   - Press `Ctrl + F`
   - Search for: `[mail function]`
   - Click "Find Next"

3. **Update the settings:**
   - Find the line: `smtp_port = 25`
   - Change it to: `smtp_port = 1025`
   - The SMTP line should already be: `SMTP = localhost`

4. **Save the file:**
   - Press `Ctrl + S`
   - Close Notepad

5. **Restart Laragon:**
   - Open Laragon
   - Click "Stop All"
   - Wait 3 seconds
   - Click "Start All"

## Verify Configuration:

Create a test file `c:\laragon\www\HR1\test_email.php`:

```php
<?php
// Test email configuration
$to = "test@example.com";
$subject = "Test Email from SLATE HR";
$message = "This is a test email to verify SMTP configuration.";
$headers = "From: noreply@slatefreight.com\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";

if (mail($to, $subject, $message, $headers)) {
    echo "Email sent successfully! Check MailHog at http://localhost:8025";
} else {
    echo "Email failed to send. Check php.ini configuration.";
}
?>
```

Then visit: `http://localhost/HR1/test_email.php`

Check MailHog at: `http://localhost:8025`

## Important Notes:

- **MailHog must be running** before sending emails
- Port **1025** is for MailHog SMTP server
- Port **8025** is for MailHog web interface
- After changing php.ini, **always restart Laragon**

## Troubleshooting:

**Email not appearing in MailHog:**
- Check if MailHog is running (look for MailHog window)
- Verify php.ini changes were saved
- Confirm Laragon was restarted
- Check Windows Firewall isn't blocking port 1025

**Can't edit php.ini:**
- Run Notepad as Administrator
- Right-click Notepad → "Run as administrator"
- Then open the php.ini file
