# 🚀 FTP Deployment Guide - Sub-domain Database Fix

## 📋 Problem
Database connection issues on sub-domain caused by outdated database credentials in config files.

## 🔑 FTP Credentials
- **Username:** `hr1slateXCZ_domain_ftp`
- **Password:** `HihMzLPiO%vacL8b`
- **Server:** Your sub-domain FTP server (e.g., `ftp.yourdomain.com`)
- **Port:** 21 (default FTP)

---

## 🛠️ Solution Options

### **Option 1: Automated FTP Upload (Recommended)**

#### Step 1: Check Directory Structure
```bash
php utils/ftp_check_structure.php
```

This will:
- Connect to your FTP server
- List directory structure
- Find existing config files
- Show you the correct paths

#### Step 2: Update FTP Server Address
Edit `utils/ftp_deploy_config.php` and `utils/ftp_check_structure.php`:
```php
$ftp_server = 'ftp.yourdomain.com'; // Replace with your actual FTP server
```

Common FTP server formats:
- `ftp.yourdomain.com`
- `yourdomain.com`
- `subdomain.yourdomain.com`
- IP address (e.g., `123.45.67.89`)

#### Step 3: Update Remote Paths (if needed)
Based on the structure check, update paths in `utils/ftp_deploy_config.php`:
```php
$remote_base_path = '/public_html/'; // Common: /public_html/, /www/, /httpdocs/
```

#### Step 4: Deploy
```bash
php utils/ftp_deploy_config.php
```

This will upload:
- ✅ `database/config.php` (with updated password: `Ip6dLgwFNDKadiPJ`)
- ✅ `includes/smtp_config.php` (email configuration)

---

### **Option 2: Manual FTP Upload**

#### Using FileZilla (Windows/Mac/Linux)

1. **Download FileZilla** (if not installed):
   - https://filezilla-project.org/

2. **Connect to FTP:**
   - Host: `ftp.yourdomain.com` (or your FTP server)
   - Username: `hr1slateXCZ_domain_ftp`
   - Password: `HihMzLPiO%vacL8b`
   - Port: `21`
   - Click "Quickconnect"

3. **Navigate to Remote Directory:**
   - Look for: `/public_html/` or `/www/` or `/httpdocs/`
   - Find the `database` folder

4. **Upload Files:**
   - **Local side:** Navigate to `c:\laragon\www\HR1\database\`
   - **Remote side:** Navigate to `/public_html/database/` (or equivalent)
   - Drag and drop `config.php` from local to remote
   - Confirm overwrite when prompted

5. **Verify Upload:**
   - Right-click `config.php` on remote side
   - Select "View/Edit"
   - Check that line 13 has: `define('DB_PASS', 'Ip6dLgwFNDKadiPJ');`

#### Using WinSCP (Windows)

1. **Download WinSCP** (if not installed):
   - https://winscp.net/

2. **New Session:**
   - File protocol: `FTP`
   - Host name: `ftp.yourdomain.com`
   - Port: `21`
   - User name: `hr1slateXCZ_domain_ftp`
   - Password: `HihMzLPiO%vacL8b`
   - Click "Login"

3. **Upload Files:**
   - Left panel: Navigate to `c:\laragon\www\HR1\database\`
   - Right panel: Navigate to `/public_html/database/`
   - Drag `config.php` from left to right
   - Confirm overwrite

---

## 📁 Files That Need Updating on Sub-domain

### **Critical Files (Must Update)**
1. **`database/config.php`**
   - Line 13: `define('DB_PASS', 'Ip6dLgwFNDKadiPJ');`
   - This fixes the database connection issue

### **Optional Files (Recommended to Update)**
2. **`includes/smtp_config.php`**
   - Email configuration (already correct, but good to sync)

3. **`auth/login.php`**
   - If you made any recent changes to login logic

4. **`auth/register-applicant.php`**
   - If you updated registration flow

5. **`views/manager/handbook.php`**
   - If you added the handbook feature recently

---

## ✅ Verification Steps

After uploading files:

### 1. Test Database Connection
Visit: `https://yoursubdomain.com/utils/test_db_connection.php`

Expected result:
```
✓ Database connection successful
✓ Database: hr1_hr1data
✓ User: hr1_hr1slate
```

### 2. Test Login
1. Go to your sub-domain login page
2. Try logging in with a valid user
3. Should work without errors

### 3. Check Error Logs
If still having issues:
- Check FTP server error logs
- Check PHP error logs on server
- Enable error display temporarily in `database/config.php`:
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```

---

## 🐛 Troubleshooting

### Issue: "Cannot connect to FTP server"
**Solutions:**
- Verify FTP server address is correct
- Check if port 21 is open (try port 22 for SFTP)
- Try using your domain name instead of `ftp.domain.com`
- Check with hosting provider for correct FTP address

### Issue: "Login failed"
**Solutions:**
- Double-check username and password (copy-paste to avoid typos)
- Verify credentials with hosting control panel
- Check if FTP account is active

### Issue: "Permission denied" when uploading
**Solutions:**
- Check file permissions on server
- Ensure FTP user has write access
- Try uploading to different directory first
- Contact hosting support

### Issue: "Database connection still failing after upload"
**Solutions:**
- Verify the file was actually uploaded (check file size/date)
- Check if there are multiple config.php files on server
- Ensure database credentials match your hosting database
- Check if database server is running
- Verify database name, username match hosting setup

---

## 📞 Support

If you continue having issues:
1. Check hosting control panel for database credentials
2. Verify database is accessible from web server
3. Contact hosting support with error messages
4. Check server PHP error logs for detailed errors

---

## 🎯 Quick Reference

### Files to Upload
```
Local Path                          → Remote Path
─────────────────────────────────────────────────────────────
c:\laragon\www\HR1\database\config.php
                                    → /public_html/database/config.php

c:\laragon\www\HR1\includes\smtp_config.php
                                    → /public_html/includes/smtp_config.php
```

### Updated Credentials
```php
// Database (in config.php)
DB_USER: hr1_hr1slate
DB_PASS: Ip6dLgwFNDKadiPJ
DB_NAME: hr1_hr1data

// FTP
Username: hr1slateXCZ_domain_ftp
Password: HihMzLPiO%vacL8b
```

---

## ✅ Deployment Complete!

Once files are uploaded and verified, your sub-domain should have working database connections.
