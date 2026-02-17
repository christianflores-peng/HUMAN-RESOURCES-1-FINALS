# 📋 Sub-domain Files Update Checklist

## 🎯 Critical Files (MUST UPDATE)

### 1. **database/config.php** ⚠️ HIGHEST PRIORITY
**Why:** Contains database password that was updated
**Line 13:** 
```php
define('DB_PASS', 'Ip6dLgwFNDKadiPJ');  // OLD: sOp1q^PyPm7Flz8q or ax#wO%GyP*KodxNA
```

**Impact if not updated:**
- ❌ Login will fail
- ❌ Registration will fail
- ❌ All database operations will fail
- ❌ Users cannot access the system

---

## 📁 Files Modified in Recent Sessions

### Authentication & Registration
These files were updated with new features and bug fixes:

1. **auth/login.php**
   - Password verification logic
   - OTP handling
   - Session management

2. **auth/register-applicant.php**
   - Job ID parameter handling
   - Redirect to job details after registration
   - OTP verification flow

3. **auth/register-portal.php**
   - Pre-selection of registration type
   - URL parameter handling for applicant flow

4. **auth/register-employee.php**
   - Employee registration with department
   - OTP verification

### Public Pages
5. **public/careers.php**
   - Dynamic employment type filter
   - Updated "Apply Now" button links
   - Register button routing

6. **index.php**
   - Changed Register button to Quick Access button
   - Anchor link to quick access section

### Handbook System (NEW FEATURE)
7. **views/manager/handbook.php**
   - Complete handbook management system
   - PDF upload and viewing
   - Role-based access control
   - Versioning system

8. **views/admin/handbook.php**
9. **views/employee/handbook.php**
10. **views/hr_staff/handbook.php**
    - Proxy files for handbook module

### Dashboard Navigation
11. **views/admin/index.php**
12. **views/employee/index.php**
13. **views/hr_staff/index.php**
14. **views/manager/index.php**
    - Added handbook navigation entries

---

## 🔧 Configuration Files

### Email Configuration
**includes/smtp_config.php**
- Current settings are correct
- Gmail: ljhay130@gmail.com
- App password: gfvwnmiedpateiou
- **Status:** ✅ No update needed unless you changed email settings

---

## 📊 Priority Levels

### 🔴 CRITICAL (Upload Immediately)
1. `database/config.php` - Database connection will fail without this

### 🟡 HIGH (Upload Soon)
2. `auth/login.php` - Login improvements
3. `auth/register-applicant.php` - Registration flow fixes
4. `auth/register-portal.php` - Registration routing
5. `public/careers.php` - Apply button functionality

### 🟢 MEDIUM (Upload When Convenient)
6. `index.php` - Quick Access button
7. All handbook files (if you want the handbook feature on production)
8. Dashboard navigation files (for handbook menu)

### ⚪ LOW (Optional)
9. Documentation files (.md files) - Not needed on production server

---

## 🚀 Deployment Methods

### Method 1: Automated Script
```bash
# Step 1: Check FTP structure
php utils/ftp_check_structure.php

# Step 2: Update FTP server in scripts
# Edit utils/ftp_deploy_config.php line 14:
# $ftp_server = 'ftp.yourdomain.com';

# Step 3: Deploy
php utils/ftp_deploy_config.php
```

### Method 2: Manual FTP (FileZilla/WinSCP)
See: `docs/FTP_DEPLOYMENT_GUIDE.md`

---

## ✅ Verification After Upload

### 1. Test Database Connection
Upload and visit: `https://yoursubdomain.com/utils/test_db_connection.php`

Expected: All tests pass ✓

### 2. Test Login
1. Go to login page
2. Enter valid credentials
3. Should login successfully

### 3. Test Registration
1. Go to careers page
2. Click "Apply Now"
3. Complete registration
4. Should redirect to job details

### 4. Test Handbook (if uploaded)
1. Login as any role
2. Click "Handbook" in sidebar
3. Should see handbook page

---

## 🔍 What Changed and Why

### Database Password Update
**Old passwords tried:**
- `ax#wO%GyP*KodxNA` (initial)
- `sOp1q^PyPm7Flz8q` (first update)

**Current password:**
- `Ip6dLgwFNDKadiPJ` (latest, correct for production)

### Feature Additions
1. **Handbook System** - Admin-managed PDF handbooks for all roles
2. **Job Application Flow** - Seamless flow from careers to registration to job details
3. **Quick Access Button** - Better UX on homepage
4. **Dynamic Filters** - Employment type filter based on actual job postings

---

## 📞 Troubleshooting

### If database connection still fails after upload:
1. Verify file was actually uploaded (check timestamp)
2. Check if multiple config.php files exist on server
3. Verify database credentials with hosting control panel
4. Check database server is running
5. Verify database name matches: `hr1_hr1data`
6. Verify database user matches: `hr1_hr1slate`

### If FTP upload fails:
1. Verify FTP credentials
2. Check FTP server address
3. Try different FTP client
4. Check file permissions on server
5. Contact hosting support

---

## 📝 Quick Command Reference

```bash
# Check FTP structure
php utils/ftp_check_structure.php

# Deploy config files
php utils/ftp_deploy_config.php

# Test database (after upload to server)
# Visit: https://yoursubdomain.com/utils/test_db_connection.php
```

---

## 🎯 Minimum Required Upload

If you only have time for one file:
**Upload ONLY:** `database/config.php`

This will fix the immediate database connection issue. Other files can be uploaded later for additional features and improvements.

---

## ✅ Deployment Complete Checklist

- [ ] Uploaded `database/config.php` with new password
- [ ] Tested database connection (test_db_connection.php)
- [ ] Tested login functionality
- [ ] Tested registration flow
- [ ] Verified all features working
- [ ] Deleted test_db_connection.php from server (security)
- [ ] Documented any issues encountered
- [ ] Notified team that deployment is complete

---

**Last Updated:** Database password changed to `Ip6dLgwFNDKadiPJ`
**Critical Action:** Upload `database/config.php` to fix database connection on sub-domain
