# 🔑 How to Get Gmail App Password - Step by Step

## ❌ Current Problem:
Your `smtp_config.php` still has: `'smtp_password' => 'your-app-password-here'`

This is a **placeholder**—you need to replace it with your **actual Gmail App Password**.

---

## ✅ Step-by-Step Guide:

### **Step 1: Enable 2-Step Verification**

1. Open: https://myaccount.google.com/security
2. Log in with: **ljhay130@gmail.com**
3. Scroll to **"How you sign in to Google"**
4. Click **"2-Step Verification"**
5. If not enabled:
   - Click **"Get Started"**
   - Follow the setup (use phone number)
   - Complete verification

---

### **Step 2: Generate App Password**

1. **After 2-Step is enabled**, go to: https://myaccount.google.com/apppasswords
   
   **OR:**
   - Google Account → Security
   - Scroll to "2-Step Verification"
   - Click "2-Step Verification"
   - Scroll down to **"App passwords"**
   - Click **"App passwords"**

2. **Select app and device:**
   - App: **Mail**
   - Device: **Windows Computer** (or select "Other" and type "SLATE HR")

3. **Click "Generate"**

4. **Copy the 16-character password**
   - Example: `abcd efgh ijkl mnop`
   - **Important:** Remove spaces → `abcdefghijklmnop`

5. **Save it somewhere safe** (you'll need it in next step)

---

### **Step 3: Update smtp_config.php**

1. **Open file:**
   ```
   c:\laragon\www\HR1\includes\smtp_config.php
   ```

2. **Find line 22:**
   ```php
   'smtp_password' => 'your-app-password-here',
   ```

3. **Replace with your App Password:**
   ```php
   'smtp_password' => 'abcdefghijklmnop', // Your actual 16-char password
   ```

4. **Save the file** (Ctrl + S)

---

### **Step 4: Test Email**

1. **Open browser:**
   ```
   http://localhost/HR1/test_email.php
   ```

2. **Click "Send Test Email"**

3. **Check your Gmail inbox** (ljhay130@gmail.com)
   - Should receive test email
   - Check Spam folder if not in inbox

---

## 🎯 Quick Reference:

| Item | Value |
|------|-------|
| **Gmail Address** | ljhay130@gmail.com |
| **2-Step Verification** | https://myaccount.google.com/security |
| **App Passwords** | https://myaccount.google.com/apppasswords |
| **Config File** | `c:\laragon\www\HR1\includes\smtp_config.php` |
| **Test Page** | http://localhost/HR1/test_email.php |

---

## 📸 Visual Guide:

### **What you'll see when generating App Password:**

1. **Select app:** Mail
2. **Select device:** Windows Computer
3. **Generated password:** `abcd efgh ijkl mnop` (example)
4. **Copy it:** Remove spaces → `abcdefghijklmnop`
5. **Paste in smtp_config.php**

---

## ⚠️ Common Issues:

### **Can't find "App passwords" option**
**Cause:** 2-Step Verification not enabled
**Fix:** Enable 2-Step Verification first (Step 1)

### **"App passwords" is grayed out**
**Cause:** Using Google Workspace account (not personal Gmail)
**Fix:** Contact your Google Workspace admin, or use personal Gmail

### **Generated password doesn't work**
**Cause:** Spaces in password or wrong email
**Fix:** 
- Remove ALL spaces from password
- Make sure `smtp_username` matches your Gmail exactly

---

## 🔐 Security Notes:

1. **App Password is NOT your Gmail password**
   - It's a special 16-character code
   - Generated specifically for third-party apps

2. **Keep it secure**
   - Don't share with anyone
   - Don't commit to Git

3. **Can be revoked anytime**
   - Go to App Passwords page
   - Click "Remove" next to the password

---

## 📝 After Getting App Password:

**Edit this line in smtp_config.php:**

**BEFORE:**
```php
'smtp_password' => 'your-app-password-here',
```

**AFTER (example):**
```php
'smtp_password' => 'xyzw1234abcd5678', // Your actual App Password
```

**Then test at:** http://localhost/HR1/test_email.php

---

## 🚀 Next Steps:

1. [ ] Enable 2-Step Verification
2. [ ] Generate App Password
3. [ ] Copy the 16-character password
4. [ ] Update `smtp_config.php` line 22
5. [ ] Save file
6. [ ] Test at http://localhost/HR1/test_email.php
7. [ ] Check Gmail inbox

---

**Need help?** Screenshot the error and send it to me!
