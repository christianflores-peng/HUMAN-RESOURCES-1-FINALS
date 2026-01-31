# 📧 Gmail SMTP Setup Guide - SLATE HR System

## ✅ What I Fixed:

1. **Email System**: Updated to use **real Gmail SMTP** (not just MailHog testing)
2. **PHPMailer**: Created custom PHPMailer class for Gmail integration
3. **Phone Number**: Verified it's being saved correctly in both tables
4. **Email Helper**: Updated to send to actual Gmail addresses

---

## 🔧 SETUP REQUIRED (IMPORTANTE!)

### **Step 1: Get Gmail App Password**

**Why?** Gmail doesn't allow regular passwords for third-party apps. You need an "App Password".

**How to get it:**

1. **Go to Google Account Security:**
   - Visit: https://myaccount.google.com/security
   - Or: Google Account → Security

2. **Enable 2-Step Verification** (if not enabled):
   - Scroll to "How you sign in to Google"
   - Click "2-Step Verification"
   - Follow the setup process

3. **Generate App Password:**
   - Visit: https://myaccount.google.com/apppasswords
   - Or: Google Account → Security → 2-Step Verification → App passwords
   - Select app: **Mail**
   - Select device: **Windows Computer** (or Other)
   - Click **Generate**
   - Copy the **16-character password** (e.g., `abcd efgh ijkl mnop`)

4. **Important:**
   - Remove spaces from the password: `abcdefghijklmnop`
   - Keep this password secure!

---

### **Step 2: Update SMTP Configuration**

**Edit this file:**
```
c:\laragon\www\HR1\includes\smtp_config.php
```

**Update these lines:**

```php
// REPLACE THESE WITH YOUR ACTUAL CREDENTIALS
'smtp_username' => 'your-actual-email@gmail.com', // Your Gmail address
'smtp_password' => 'abcdefghijklmnop', // Your 16-char App Password (no spaces)

// From Email Settings
'from_email' => 'your-actual-email@gmail.com', // Same as smtp_username
'from_name' => 'SLATE Freight HR System',
```

**Example (with fake credentials):**
```php
'smtp_username' => 'hr.slatefreight@gmail.com',
'smtp_password' => 'xyzw1234abcd5678',
'from_email' => 'hr.slatefreight@gmail.com',
'from_name' => 'SLATE Freight HR System',
```

---

### **Step 3: Test Email Sending**

**Option 1: Use Test Page**
```
http://localhost/HR1/test_email.php
```
- Click "Send Test Email"
- Check if email arrives in your Gmail inbox

**Option 2: Submit Real Application**
```
http://localhost/HR1/careers.php
```
- Apply for a job
- Use your real Gmail address
- Check if you receive login credentials

---

## 📋 Files Created/Updated:

| File | Purpose |
|------|---------|
| `includes/PHPMailer.php` | Custom PHPMailer for Gmail SMTP |
| `includes/smtp_config.php` | **YOU MUST EDIT THIS** with your Gmail credentials |
| `includes/email_helper.php` | Updated to use PHPMailer instead of PHP mail() |
| `test_email.php` | Test page to verify email sending |

---

## 🔍 Troubleshooting:

### **Error: "SMTP not configured"**
**Cause:** You haven't updated `smtp_config.php` yet
**Fix:** Edit `smtp_config.php` with your Gmail credentials

### **Error: "Authentication failed"**
**Cause:** Wrong Gmail email or App Password
**Fix:** 
- Double-check your Gmail address
- Make sure you're using **App Password** (not regular password)
- Remove spaces from App Password

### **Error: "Failed to connect to SMTP server"**
**Cause:** Port blocked or Gmail SMTP unreachable
**Fix:**
- Check internet connection
- Try port 465 instead of 587 in `smtp_config.php`
- Check firewall settings

### **Email not received in Gmail**
**Possible causes:**
1. Check **Spam/Junk** folder
2. Wait a few minutes (sometimes delayed)
3. Check Gmail "All Mail" folder
4. Verify recipient email is correct

---

## 🎯 What Happens Now:

### **When someone applies via apply.php:**

1. ✅ Application saved to `job_applications` table
2. ✅ Phone number saved in `job_applications.phone`
3. ✅ User account created in `user_accounts` table
4. ✅ Phone number saved in `user_accounts.phone`
5. ✅ Temporary password generated
6. ✅ **Email sent to applicant's Gmail** with:
   - Login credentials (email + temp password)
   - Link to login page
   - Welcome message

### **Applicant receives:**
- Email subject: "Welcome to SLATE Freight - Your Application Account"
- Email contains:
  - Their login email
  - Temporary password
  - Login link
  - Instructions

---

## 🔐 Security Notes:

1. **Never commit `smtp_config.php` to Git**
   - Add to `.gitignore`
   - Keep credentials private

2. **Use App Password, not regular password**
   - More secure
   - Can be revoked anytime

3. **Change temp passwords**
   - Applicants should change password after first login

4. **Monitor email sending**
   - Check error logs: `c:\laragon\www\HR1\error.log`
   - Or Apache error log

---

## 📊 Verification Checklist:

Before going live, verify:

- [ ] Gmail App Password generated
- [ ] `smtp_config.php` updated with real credentials
- [ ] Test email sent successfully via `test_email.php`
- [ ] Test application submitted via `apply.php`
- [ ] Email received in Gmail inbox (not spam)
- [ ] Login credentials work
- [ ] Phone number saved in database

---

## 🚀 Quick Start Commands:

**1. Edit SMTP config:**
```
notepad c:\laragon\www\HR1\includes\smtp_config.php
```

**2. Test email:**
```
http://localhost/HR1/test_email.php
```

**3. Check logs:**
```
notepad c:\laragon\www\HR1\error.log
```

---

## 📞 Phone Number Saving:

**Verified working in:**
- ✅ `job_applications` table → `phone` column
- ✅ `user_accounts` table → `phone` column

**Both are saved when application is submitted!**

---

## 💡 Important Notes:

### **MailHog vs Gmail SMTP:**

**MailHog (localhost:1025):**
- ✅ For **testing only**
- ✅ Emails trapped locally
- ❌ **Does NOT send to real Gmail**

**Gmail SMTP (smtp.gmail.com:587):**
- ✅ Sends to **real Gmail addresses**
- ✅ Production-ready
- ✅ Requires Gmail credentials

**Current setup:** Uses **Gmail SMTP** for real email delivery.

---

## 🎉 Summary:

**What you need to do:**
1. Get Gmail App Password (16 characters)
2. Edit `smtp_config.php` with your credentials
3. Test via `test_email.php`
4. Done! Emails will be sent to real Gmail addresses

**What's already done:**
- ✅ PHPMailer implemented
- ✅ Email helper updated
- ✅ Phone number saving verified
- ✅ User account creation working
- ✅ Welcome email template ready

---

**Need help?** Check error logs or test with `test_email.php` first!
