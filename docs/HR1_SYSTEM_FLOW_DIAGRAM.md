# HR1 - SLATE Freight Management System
## Complete System Flow & Architecture Documentation

---

## 📁 **PROJECT STRUCTURE**

```
HR1/
├── 🗄️ database/           # Database schemas, migrations, setup scripts
├── 📄 pages/              # Main application pages (dashboards, management)
├── 🔐 partials/           # Authentication & registration components
├── 🎨 css/                # Stylesheets
├── ⚙️ includes/           # Helper functions, configs, utilities
├── 🖼️ assets/             # Images, logos, static files
├── 📤 uploads/            # User-uploaded files (resumes, documents)
├── 🪟 modals/             # Modal dialogs
└── 📚 Documentation/      # README files, guides, workflows
```

---

## 🏗️ **SYSTEM ARCHITECTURE**

```
┌─────────────────────────────────────────────────────────────────┐
│                     HR1 SYSTEM ARCHITECTURE                      │
└─────────────────────────────────────────────────────────────────┘

┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Frontend   │────▶│   Backend    │────▶│   Database   │
│  (HTML/CSS)  │     │     (PHP)    │     │    (MySQL)   │
└──────────────┘     └──────────────┘     └──────────────┘
       │                     │                     │
       │                     │                     │
       ▼                     ▼                     ▼
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│  JavaScript  │     │   Includes   │     │    Tables    │
│   (Dynamic)  │     │   (Helpers)  │     │  (Entities)  │
└──────────────┘     └──────────────┘     └──────────────┘
```

---

## 👥 **USER ROLES & ACCESS LEVELS**

```
┌─────────────────────────────────────────────────────────────────┐
│                         USER HIERARCHY                           │
└─────────────────────────────────────────────────────────────────┘

                    ┌──────────────┐
                    │   ADMIN      │ (Full Access)
                    │  Role ID: 1  │
                    └──────┬───────┘
                           │
           ┌───────────────┼───────────────┐
           │               │               │
    ┌──────▼──────┐ ┌─────▼──────┐ ┌─────▼──────┐
    │   HR STAFF  │ │  MANAGER   │ │  EMPLOYEE  │
    │ Role ID: 2  │ │ Role ID: 3 │ │ Role ID: 4 │
    └─────────────┘ └────────────┘ └────────────┘
           │
    ┌──────▼──────┐
    │  APPLICANT  │ (Public Portal)
    │ Role ID: 9  │
    └─────────────┘
```

---

## 🔄 **APPLICANT REGISTRATION FLOW**

```
┌─────────────────────────────────────────────────────────────────┐
│              APPLICANT REGISTRATION (3-STEP PROCESS)             │
└─────────────────────────────────────────────────────────────────┘

START
  │
  ▼
┌─────────────────────────────────────┐
│  STEP 1: Choose Account Type        │
│  File: register-portal.php          │
│  ────────────────────────────────   │
│  • Select "Applicant"               │
│  • Accept Terms & Conditions        │
│  • Click "Continue"                 │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  STEP 2: Personal Information       │
│  File: register-applicant.php       │
│  ────────────────────────────────   │
│  • First Name, Last Name            │
│  • Email Address (unique)           │
│  • Phone Number                     │
│  • Password (min 6 chars)           │
│  • Confirm Password                 │
│  ────────────────────────────────   │
│  Validation:                        │
│  ✓ Email format check               │
│  ✓ Email uniqueness check           │
│  ✓ Password match check             │
│  ────────────────────────────────   │
│  Action: Store in SESSION           │
│  Click "Next" →                     │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  STEP 3: Upload Documents           │
│  File: register-applicant-          │
│        documents.php                │
│  ────────────────────────────────   │
│  • Resume Upload (optional)         │
│    - Formats: PDF, DOC, DOCX        │
│    - Max Size: 5MB                  │
│  • Cover Letter (optional)          │
│    - Text area input                │
│  ────────────────────────────────   │
│  Action: Create Account             │
│  • Insert into user_accounts        │
│  • Insert into applicant_profiles   │
│  • Clear SESSION data               │
│  Click "Complete Registration" →    │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  SUCCESS: Account Created           │
│  ────────────────────────────────   │
│  • Status: Active                   │
│  • Role: Applicant (ID: 9)          │
│  • Redirect to: login.php           │
└──────────────┬──────────────────────┘
               │
               ▼
              END
```

---

## 🔐 **LOGIN & AUTHENTICATION FLOW**

```
┌─────────────────────────────────────────────────────────────────┐
│                    LOGIN AUTHENTICATION FLOW                     │
└─────────────────────────────────────────────────────────────────┘

START (login.php or partials/login.php)
  │
  ▼
┌─────────────────────────────────────┐
│  User Enters Credentials            │
│  • Email/Username                   │
│  • Password                         │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  Check user_accounts Table          │
│  • Query by email                   │
│  • Verify password_hash             │
└──────────────┬──────────────────────┘
               │
         ┌─────┴─────┐
         │           │
    ✓ Valid     ✗ Invalid
         │           │
         │           ▼
         │     ┌─────────────────┐
         │     │  Error Message  │
         │     │  Return to Login│
         │     └─────────────────┘
         │
         ▼
┌─────────────────────────────────────┐
│  Create Session                     │
│  • $_SESSION['user_id']             │
│  • $_SESSION['username']            │
│  • $_SESSION['role_type']           │
│  • $_SESSION['role_id']             │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  Role-Based Redirect                │
└──────────────┬──────────────────────┘
               │
    ┌──────────┼──────────┬──────────┐
    │          │          │          │
    ▼          ▼          ▼          ▼
┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐
│ Admin  │ │HR Staff│ │Manager │ │Applicant│
│Dashboard│ │Dashboard│ │Dashboard│ │Portal  │
└────────┘ └────────┘ └────────┘ └────────┘
    │          │          │          │
    ▼          ▼          ▼          ▼
  index.php  index.php  index.php  applicant-
                                    dashboard.php
```

---

## 📋 **JOB APPLICATION WORKFLOW**

```
┌─────────────────────────────────────────────────────────────────┐
│                   JOB APPLICATION PROCESS                        │
└─────────────────────────────────────────────────────────────────┘

START (Public Job Portal)
  │
  ▼
┌─────────────────────────────────────┐
│  1. Browse Jobs                     │
│  File: careers.php                  │
│  ────────────────────────────────   │
│  • View active job postings         │
│  • Filter by department/location    │
│  • Click "Apply Now"                │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  2. View Job Details                │
│  File: job_details.php              │
│  ────────────────────────────────   │
│  • Job description                  │
│  • Requirements                     │
│  • Salary range                     │
│  • Click "Apply for this Position"  │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  3. Submit Application              │
│  File: apply.php                    │
│  ────────────────────────────────   │
│  • Personal Information             │
│    - First Name, Last Name          │
│    - Email, Phone                   │
│  • Upload Resume                    │
│  • Cover Letter                     │
│  ────────────────────────────────   │
│  Action: Insert into                │
│  job_applications table             │
│  Status: "new"                      │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  4. Application Submitted           │
│  • Email confirmation sent          │
│  • Application ID generated         │
│  • Status: "new"                    │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  5. HR Review Process               │
│  File: screening.php                │
│  ────────────────────────────────   │
│  Status Flow:                       │
│  new → screening → interview →      │
│  offer → hired / rejected           │
└──────────────┬──────────────────────┘
               │
               ▼
              END
```

---

## 🎯 **APPLICANT PORTAL DASHBOARD**

```
┌─────────────────────────────────────────────────────────────────┐
│                    APPLICANT DASHBOARD FLOW                      │
└─────────────────────────────────────────────────────────────────┘

Login as Applicant
  │
  ▼
┌─────────────────────────────────────┐
│  Applicant Dashboard                │
│  File: applicant-dashboard.php      │
│  ────────────────────────────────   │
│  📊 OVERVIEW SECTION                │
│  • Total Applications               │
│  • Applications in Review           │
│  • Interview Scheduled              │
│  • Profile Completion %             │
│  ────────────────────────────────   │
│  📝 MY APPLICATIONS                 │
│  • List of submitted applications   │
│  • Status badges (color-coded)      │
│  • Applied date                     │
│  • View details button              │
│  ────────────────────────────────   │
│  👤 PROFILE INFORMATION             │
│  • Name, Email, Phone               │
│  • Resume (download link)           │
│  • Cover Letter                     │
│  • Edit Profile button              │
│  ────────────────────────────────   │
│  💼 AVAILABLE JOBS                  │
│  • Browse new job postings          │
│  • Quick apply button               │
│  • Link to careers page             │
└─────────────────────────────────────┘
```

---

## 🗄️ **DATABASE STRUCTURE**

```
┌─────────────────────────────────────────────────────────────────┐
│                      DATABASE TABLES                             │
└─────────────────────────────────────────────────────────────────┘

┌──────────────────┐
│  user_accounts   │ (Main user authentication)
├──────────────────┤
│ • id (PK)        │
│ • first_name     │
│ • last_name      │
│ • personal_email │
│ • company_email  │
│ • phone          │
│ • password_hash  │
│ • role_id (FK)   │───┐
│ • status         │   │
│ • created_at     │   │
└──────────────────┘   │
                       │
┌──────────────────┐   │
│  roles           │◄──┘
├──────────────────┤
│ • id (PK)        │
│ • role_name      │
│ • role_type      │
│ • access_level   │
└──────────────────┘

┌──────────────────────┐
│  applicant_profiles  │ (Applicant-specific data)
├──────────────────────┤
│ • id (PK)            │
│ • user_id (FK)       │───┐
│ • resume_path        │   │
│ • cover_letter       │   │
│ • skills             │   │
│ • experience_years   │   │
│ • education_level    │   │
│ • linkedin_url       │   │
│ • portfolio_url      │   │
│ • availability       │   │
│ • expected_salary    │   │
│ • created_at         │   │
│ • updated_at         │   │
└──────────────────────┘   │
                           │
                    Links to user_accounts

┌──────────────────────┐
│  job_applications    │ (Application submissions)
├──────────────────────┤
│ • id (PK)            │
│ • job_posting_id (FK)│
│ • first_name         │
│ • last_name          │
│ • email              │
│ • phone              │
│ • resume_path        │
│ • cover_letter       │
│ • status             │
│ • applied_date       │
└──────────────────────┘

┌──────────────────────┐
│  job_postings        │ (Job listings)
├──────────────────────┤
│ • id (PK)            │
│ • title              │
│ • department_id (FK) │
│ • description        │
│ • requirements       │
│ • salary_range       │
│ • location           │
│ • employment_type    │
│ • status             │
│ • posted_date        │
└──────────────────────┘
```

---

## 📂 **KEY FILES & THEIR PURPOSES**

### **🔐 Authentication & Registration**
```
partials/
├── login.php                    # User login form & authentication
├── register-portal.php          # Step 1: Choose account type
├── register-applicant.php       # Step 2: Personal information
└── register-applicant-documents.php  # Step 3: Upload documents
```

### **📄 Main Pages**
```
pages/
├── applicant-dashboard.php      # Applicant portal dashboard
├── admin-dashboard.php          # Admin control panel
├── hr-dashboard.php             # HR staff dashboard
├── employee-dashboard.php       # Employee portal
└── manager-dashboard.php        # Manager dashboard
```

### **💼 Job Application System**
```
Root Files:
├── careers.php                  # Public job listings
├── job_details.php              # Individual job details
├── apply.php                    # Job application form
└── screening.php                # HR screening interface
```

### **⚙️ Core Includes**
```
includes/
├── config.php                   # Database configuration
├── db_helper.php                # Database query functions
├── rbac_helper.php              # Role-based access control
├── email_helper.php             # Email sending functions
├── smtp_config.php              # SMTP email configuration
├── GmailSMTP.php                # Gmail SMTP handler
└── email_generator.php          # Email template generator
```

### **🗄️ Database Scripts**
```
database/
├── config.php                   # Database connection
├── db_helper.php                # Query helper functions
├── create_applicant_profiles_table.sql  # Table schema
└── setup_applicant_portal.php   # Database setup script
```

### **🛠️ Utility Tools**
```
Root Files:
├── check_email.php              # Email existence checker
├── clear_test_accounts.php      # Delete test accounts
├── test_email.php               # Email sending test
├── test_smtp_connection.php     # SMTP connection test
└── phpinfo.php                  # PHP configuration info
```

---

## 🔄 **DATA FLOW DIAGRAM**

```
┌─────────────────────────────────────────────────────────────────┐
│                    APPLICATION DATA FLOW                         │
└─────────────────────────────────────────────────────────────────┘

USER INPUT
    │
    ▼
┌─────────────┐
│   Browser   │
│  (Frontend) │
└──────┬──────┘
       │ HTTP Request
       ▼
┌─────────────┐
│  PHP Script │
│  (Backend)  │
└──────┬──────┘
       │
       ├─────────────┐
       │             │
       ▼             ▼
┌─────────────┐ ┌─────────────┐
│  Validation │ │   Session   │
│   & Logic   │ │  Management │
└──────┬──────┘ └──────┬──────┘
       │               │
       ▼               ▼
┌─────────────────────────┐
│    Database Queries     │
│  (db_helper.php)        │
└──────────┬──────────────┘
           │
           ▼
┌─────────────────────────┐
│    MySQL Database       │
│  • user_accounts        │
│  • applicant_profiles   │
│  • job_applications     │
│  • job_postings         │
└──────────┬──────────────┘
           │
           ▼
┌─────────────────────────┐
│    Response/Redirect    │
│  • Success message      │
│  • Error message        │
│  • Page redirect        │
└──────────┬──────────────┘
           │
           ▼
       Browser
     (Display)
```

---

## 🔒 **SECURITY FEATURES**

```
┌─────────────────────────────────────────────────────────────────┐
│                      SECURITY MEASURES                           │
└─────────────────────────────────────────────────────────────────┘

1. PASSWORD SECURITY
   ├─ Password hashing (password_hash)
   ├─ Minimum 6 characters
   ├─ Password confirmation required
   └─ Show/hide password toggle

2. SESSION MANAGEMENT
   ├─ session_start() on protected pages
   ├─ Session variables for user data
   ├─ Session destruction on logout
   └─ Session timeout handling

3. INPUT VALIDATION
   ├─ Email format validation
   ├─ Required field checks
   ├─ SQL injection prevention (prepared statements)
   └─ XSS prevention (htmlspecialchars)

4. FILE UPLOAD SECURITY
   ├─ File type validation (PDF, DOC, DOCX)
   ├─ File size limits (5MB max)
   ├─ Unique filename generation
   └─ Secure upload directory

5. ROLE-BASED ACCESS CONTROL (RBAC)
   ├─ Role verification on page load
   ├─ Permission checks (hasPermission)
   ├─ Access level enforcement
   └─ Unauthorized access redirect

6. DATABASE SECURITY
   ├─ Prepared statements (PDO)
   ├─ Parameterized queries
   ├─ Foreign key constraints
   └─ Unique constraints on emails
```

---

## 📧 **EMAIL SYSTEM FLOW**

```
┌─────────────────────────────────────────────────────────────────┐
│                    EMAIL NOTIFICATION SYSTEM                     │
└─────────────────────────────────────────────────────────────────┘

Trigger Event
    │
    ▼
┌─────────────────────────────────────┐
│  Email Generator                    │
│  File: email_generator.php          │
│  ────────────────────────────────   │
│  • Generate email template          │
│  • Populate with user data          │
│  • Format HTML content              │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  Email Helper                       │
│  File: email_helper.php             │
│  ────────────────────────────────   │
│  • sendHtmlEmail() function         │
│  • Prepare email headers            │
│  • Call SMTP handler                │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  Gmail SMTP Handler                 │
│  File: GmailSMTP.php                │
│  ────────────────────────────────   │
│  • Connect to Gmail SMTP            │
│  • Authenticate with App Password   │
│  • Send email via TLS               │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  Gmail Server                       │
│  • smtp.gmail.com:587               │
│  • TLS encryption                   │
│  • Deliver to recipient             │
└─────────────────────────────────────┘

Email Types:
├─ Registration confirmation
├─ Application received
├─ Interview invitation
├─ Application status update
└─ Password reset (future)
```

---

## 🎨 **FRONTEND STRUCTURE**

```
┌─────────────────────────────────────────────────────────────────┐
│                      FRONTEND COMPONENTS                         │
└─────────────────────────────────────────────────────────────────┘

CSS/
├── styles.css                   # Global styles
└── [Component-specific styles in <style> tags]

JavaScript/
├── Password toggle functionality
├── File upload preview
├── Form validation
├── Dynamic content loading
└── Modal interactions

UI Components:
├── Navigation bars
├── Sidebar menus
├── Data tables
├── Forms (multi-step)
├── Cards & panels
├── Modals & dialogs
├── Progress indicators
└── Status badges

Design System:
├── Color scheme: Dark blue theme
├── Typography: Segoe UI, sans-serif
├── Icons: Material Symbols
├── Responsive: Mobile-friendly
└── Animations: Smooth transitions
```

---

## 🚀 **DEPLOYMENT CHECKLIST**

```
┌─────────────────────────────────────────────────────────────────┐
│                    PRODUCTION DEPLOYMENT                         │
└─────────────────────────────────────────────────────────────────┘

1. DATABASE SETUP
   ☐ Run setup_applicant_portal.php
   ☐ Verify all tables created
   ☐ Check foreign key constraints
   ☐ Create database backups

2. CONFIGURATION
   ☐ Update database/config.php
   ☐ Set production database credentials
   ☐ Configure SMTP settings
   ☐ Set Gmail App Password
   ☐ Update file upload paths

3. SECURITY
   ☐ Change default passwords
   ☐ Enable HTTPS/SSL
   ☐ Set secure session settings
   ☐ Configure file permissions
   ☐ Enable error logging

4. TESTING
   ☐ Test applicant registration
   ☐ Test login/logout
   ☐ Test job application
   ☐ Test email sending
   ☐ Test file uploads
   ☐ Test all user roles

5. DOCUMENTATION
   ☐ Update README.md
   ☐ Document API endpoints
   ☐ Create user manual
   ☐ Document admin procedures
```

---

## 📊 **APPLICATION STATUS WORKFLOW**

```
┌─────────────────────────────────────────────────────────────────┐
│                  APPLICATION STATUS LIFECYCLE                    │
└─────────────────────────────────────────────────────────────────┘

    START
      │
      ▼
┌──────────┐
│   NEW    │ (Application submitted)
└────┬─────┘
     │
     ▼
┌──────────┐
│SCREENING │ (HR reviewing application)
└────┬─────┘
     │
     ├─────────┬─────────┐
     │         │         │
     ▼         ▼         ▼
┌─────────┐ ┌─────────┐ ┌─────────┐
│INTERVIEW│ │ OFFER   │ │REJECTED │
└────┬────┘ └────┬────┘ └─────────┘
     │           │           │
     │           ▼           │
     │      ┌─────────┐     │
     │      │  HIRED  │     │
     │      └─────────┘     │
     │                      │
     └──────────────────────┘
              END
```

---

## 🔧 **TROUBLESHOOTING TOOLS**

```
┌─────────────────────────────────────────────────────────────────┐
│                      DIAGNOSTIC TOOLS                            │
└─────────────────────────────────────────────────────────────────┘

1. check_email.php
   • Check if email exists in database
   • View user account details
   • Delete test accounts

2. clear_test_accounts.php
   • List all applicant accounts
   • Bulk delete test accounts
   • Clean database for testing

3. test_smtp_connection.php
   • Test SMTP server connection
   • Verify TLS support
   • Check authentication
   • Test email sending

4. test_email.php
   • Send test emails
   • Verify email templates
   • Check delivery status

5. phpinfo.php
   • View PHP configuration
   • Check installed extensions
   • Verify server settings
```

---

## 📝 **IMPORTANT NOTES**

### **Session Management**
- All protected pages must call `session_start()`
- Check user authentication before page load
- Store minimal data in session
- Clear session on logout

### **Database Queries**
- Always use prepared statements
- Use db_helper.php functions:
  - `fetchAll()` - Get multiple rows
  - `fetchSingle()` - Get single row
  - `insertRecord()` - Insert and return ID
  - `executeQuery()` - Execute any query

### **File Uploads**
- Store in `uploads/` directory
- Use unique filenames (uniqid())
- Validate file types and sizes
- Store only file paths in database

### **Email Sending**
- Use Gmail SMTP (smtp.gmail.com:587)
- Requires Gmail App Password
- TLS encryption enabled
- HTML email templates supported

### **Role-Based Access**
- Check role on every protected page
- Use `hasPermission()` function
- Redirect unauthorized users
- Log access attempts

---

## 🎯 **QUICK REFERENCE**

### **Common URLs**
```
Registration:    /partials/register-portal.php
Login:           /partials/login.php
Applicant Portal:/pages/applicant-dashboard.php
Job Listings:    /careers.php
Apply for Job:   /apply.php
Admin Dashboard: /index.php
```

### **Database Tables**
```
user_accounts        - User authentication
applicant_profiles   - Applicant data
job_applications     - Application submissions
job_postings         - Job listings
roles                - User roles
departments          - Company departments
```

### **Key Functions**
```
getUserWithRole()    - Get user data with role
hasPermission()      - Check user permissions
sendHtmlEmail()      - Send email
insertRecord()       - Insert and get ID
fetchSingle()        - Get one record
fetchAll()           - Get multiple records
```

---

## 📚 **DOCUMENTATION FILES**

- `SYSTEM_FLOW.md` - Detailed system flow
- `JOB_APPLICATION_WORKFLOW.md` - Application process
- `APPLICANT_PORTAL_README.md` - Applicant portal guide
- `LOGIN_CREDENTIALS.md` - Test credentials
- `EMAIL_SETUP_GUIDE.md` - Email configuration
- `FILE_ORGANIZATION.md` - Project structure

---

**Last Updated:** January 30, 2026  
**Version:** 1.0  
**System:** SLATE Freight Management System - HR1 Module
