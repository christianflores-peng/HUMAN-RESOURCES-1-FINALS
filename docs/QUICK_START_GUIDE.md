# HR1 Applicant Tracking System - Quick Start Guide

## 🚀 GET STARTED IN 5 MINUTES!

**Welcome to your new HR1 Applicant Tracking System!**

This guide will help you get the system up and running quickly.

---

## 📋 STEP 1: APPLY DATABASE UPDATES

### **Option A: Web-Based Update (Recommended)**

1. Open your browser and navigate to:
   ```
   http://localhost/HR1/utils/run_database_update.php
   ```

2. The script will automatically:
   - Create 5 new tables
   - Add 20+ new columns to `job_applications`
   - Set up foreign keys and indexes

3. Wait for "✓ Database update completed!" message

### **Option B: Manual Update (phpMyAdmin)**

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select database: `hr1_hr1data`
3. Click "Import" tab
4. Choose file: `c:\laragon\www\HR1\database\update_application_workflow.sql`
5. Click "Go"

---

## 📋 STEP 2: TEST THE SYSTEM

### **Test Applicant Portal**

1. **Register as Applicant:**
   ```
   http://localhost/HR1/partials/register-applicant.php
   ```
   - Fill in: Name, Email, Phone, Password
   - Upload: Resume and Cover Letter
   - Complete registration

2. **Login and View Dashboard:**
   ```
   http://localhost/HR1/applicant-dashboard.php
   ```
   - You should see your dashboard
   - Stats cards showing 0 applications
   - Empty state message

3. **Apply for a Job:**
   ```
   http://localhost/HR1/careers.php
   ```
   - Browse available jobs
   - Click "Apply Now"
   - Submit application

4. **Check Application Status:**
   ```
   http://localhost/HR1/applicant-dashboard.php
   ```
   - Your application should now appear
   - Status: "New"
   - Click "View Details" to see more

---

### **Test Manager Portal**

1. **Login as Manager/HR:**
   - Use existing Manager account
   - Or create one via user management

2. **Access Recruitment Dashboard:**
   ```
   http://localhost/HR1/manager-recruitment-dashboard.php
   ```
   - You should see the Kanban pipeline board
   - 6 columns: New Apply, Screening, Interview, Road Test, Offer Sent, Hired
   - Your test application should appear in "New Apply"

3. **Manage Applicant:**
   - Click on applicant card
   - You'll see applicant details page
   - Try these actions:
     - ✅ Move to Screening
     - ✅ Schedule Interview
     - ✅ Schedule Road Test
     - ✅ Send Job Offer
     - ✅ Hire Applicant

---

### **Test Employee Portal**

1. **After Hiring an Applicant:**
   - The applicant automatically becomes an employee
   - They get a new Employee ID (e.g., EMP20260001)
   - Role changes from "Applicant" to "Employee"

2. **Login as Employee:**
   ```
   http://localhost/HR1/employee-dashboard.php
   ```
   - Welcome banner appears
   - Onboarding progress shown
   - Pending tasks listed

3. **Submit Requirements:**
   ```
   http://localhost/HR1/employee-requirements.php
   ```
   - Upload required documents
   - Add optional notes
   - Submit each requirement

---

## 📋 STEP 3: UNDERSTAND THE WORKFLOW

### **Complete Applicant Journey:**

```
1. NEW APPLY
   ↓ (Manager clicks "Move to Screening")
   
2. SCREENING
   ↓ (Manager clicks "Schedule Interview")
   
3. INTERVIEW
   - Manager schedules Face-to-Face or Online interview
   - Applicant receives notification
   - Applicant sees schedule in portal
   ↓ (Manager clicks "Schedule Road Test")
   
4. ROAD TEST
   - Manager schedules road test with venue
   - Applicant receives notification with location
   - Manager verifies driver's license
   ↓ (Manager clicks "Send Job Offer")
   
5. OFFER SENT
   - Manager creates offer (salary, start date, benefits)
   - Applicant receives offer in portal
   - Applicant views offer details
   ↓ (Manager clicks "Hire Applicant")
   
6. HIRED
   - Employee ID assigned automatically
   - Role changed to Employee
   - Access to Employee Portal granted
   - Onboarding tasks created
```

---

## 📋 STEP 4: KEY FEATURES TO TRY

### **For Applicants:**
- ✅ View application status in real-time
- ✅ Check interview schedule (date, time, location)
- ✅ View road test venue details
- ✅ Read job offer details
- ✅ Track application timeline
- ✅ Receive notifications

### **For Managers:**
- ✅ View all applicants in pipeline board
- ✅ Schedule interviews (Face-to-Face/Online/Phone)
- ✅ Schedule road tests with venue details
- ✅ Create job offers with salary and benefits
- ✅ Hire applicants with one click
- ✅ Reject applicants with reason
- ✅ View applicant history

### **For Employees:**
- ✅ View onboarding progress
- ✅ Submit required documents
- ✅ Upload files (IDs, certificates, etc.)
- ✅ Track task completion
- ✅ View employee dashboard

---

## 📋 STEP 5: CUSTOMIZE YOUR SYSTEM

### **Add Onboarding Tasks:**

1. Go to phpMyAdmin
2. Select `hr1_hr1data` database
3. Open `onboarding_tasks` table
4. Add new tasks:
   ```sql
   INSERT INTO onboarding_tasks 
   (task_name, task_description, category, is_required, days_to_complete)
   VALUES 
   ('Submit Valid ID', 'Upload government-issued ID', 'Documents', 1, 7);
   ```

### **Add Departments:**

1. Open `departments` table
2. Add your company departments:
   ```sql
   INSERT INTO departments (department_code, department_name)
   VALUES ('SALES', 'Sales Department');
   ```

### **Create Job Postings:**

1. Access HR Recruitment Dashboard
2. Create job postings for your company
3. Applicants can then apply through careers page

---

## 🎯 COMMON TASKS

### **How to Schedule an Interview:**

1. Go to Manager Recruitment Dashboard
2. Click on applicant in "Screening" column
3. Click "Schedule Interview" button
4. Fill in modal form:
   - Interview Type: Face-to-Face/Online/Phone
   - Date & Time
   - Location (for Face-to-Face)
   - Meeting Link (for Online)
5. Click "Schedule Interview"
6. Applicant receives notification automatically

### **How to Schedule a Road Test:**

1. Click on applicant in "Interview" column
2. Click "Schedule Road Test" button
3. Fill in modal form:
   - Date & Time
   - Location/Venue
   - Venue Details (parking, contact, etc.)
4. Click "Schedule Road Test"
5. Applicant receives notification with venue details

### **How to Send a Job Offer:**

1. Click on applicant in "Road Test" column
2. Click "Send Job Offer" button
3. Fill in modal form:
   - Position Title
   - Department
   - Salary Offered
   - Employment Type
   - Start Date
   - Benefits
4. Click "Send Offer"
5. Applicant receives offer in portal

### **How to Hire an Applicant:**

1. Click on applicant in "Offer Sent" column
2. Click "Hire Applicant" button
3. Confirm hiring
4. System automatically:
   - Assigns Employee ID
   - Changes role to Employee
   - Grants Employee Portal access
   - Creates onboarding tasks

---

## 🐛 TROUBLESHOOTING

### **Problem: Database update fails**
**Solution:** 
- Check if tables already exist
- Run update script again (it handles duplicates)
- Check PHP error logs

### **Problem: Can't access applicant dashboard**
**Solution:**
- Make sure you're logged in as Applicant
- Check session is active
- Clear browser cache

### **Problem: Manager can't see applicants**
**Solution:**
- Verify user has Manager or HR_Staff role
- Check if applications exist in database
- Refresh the page

### **Problem: File upload fails**
**Solution:**
- Check `uploads/employee_documents/` folder exists
- Verify folder permissions (755)
- Check PHP upload_max_filesize setting

### **Problem: Notifications not showing**
**Solution:**
- Check `applicant_notifications` table
- Verify user_id matches
- Refresh applicant dashboard

---

## 📞 SUPPORT

### **Check Logs:**
- PHP Errors: `c:\laragon\www\HR1\error_log`
- Database Errors: Check phpMyAdmin SQL tab

### **Clear Test Data:**
```
http://localhost/HR1/utils/clear_test_accounts.php
```

### **Database Backup:**
```sql
-- In phpMyAdmin, select hr1_hr1data and click Export
-- Or use command line:
mysqldump -u root hr1_hr1data > backup.sql
```

---

## 🎓 TRAINING RESOURCES

### **For HR Staff:**
1. Read: `docs/APPLICANT_TRACKING_SYSTEM_PLAN.md`
2. Practice: Move test applicants through all stages
3. Learn: Each modal form and its purpose

### **For Applicants:**
1. Register and complete profile
2. Apply for test job
3. Track application status
4. View notifications

### **For Employees:**
1. Access employee dashboard after hiring
2. Submit all required documents
3. Complete onboarding tasks

---

## ✅ CHECKLIST

Before going live, make sure:

- [ ] Database updated successfully
- [ ] Test applicant registration works
- [ ] Test manager can view pipeline
- [ ] Test interview scheduling
- [ ] Test road test scheduling
- [ ] Test job offer creation
- [ ] Test hiring process
- [ ] Test employee portal access
- [ ] Test document upload
- [ ] All onboarding tasks added
- [ ] All departments added
- [ ] Job postings created
- [ ] HR staff trained
- [ ] Backup system in place

---

## 🎉 YOU'RE READY!

Your HR1 Applicant Tracking System is now ready to use!

**Key URLs to Bookmark:**
- Applicant Portal: `http://localhost/HR1/applicant-dashboard.php`
- Manager Portal: `http://localhost/HR1/manager-recruitment-dashboard.php`
- Employee Portal: `http://localhost/HR1/employee-dashboard.php`
- Careers Page: `http://localhost/HR1/careers.php`

**GOOD LUCK SA DEFENSE, PRE! 🚀💪**

---

**Need Help?**
- Read: `docs/IMPLEMENTATION_SUMMARY.md` for detailed documentation
- Check: `docs/APPLICANT_TRACKING_SYSTEM_PLAN.md` for system architecture

**Developed by Windsurf Cascade AI**  
**For Christian Flores (CEEJHAY)**  
**January 30, 2026**
