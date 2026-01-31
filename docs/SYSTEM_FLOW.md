# HR1 - Human Resources Management System Flow
## Slate Freight Management System

## System Overview

HR1 is a comprehensive Human Resources Management System designed for **Slate Freight** logistics company. It manages the complete employee lifecycle: recruitment, hiring, onboarding, performance tracking, and employee management with Role-Based Access Control (RBAC).

**Company Domain:** `@slatefreight.com`

---

## 1. System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     HR1 SYSTEM ARCHITECTURE                      │
├─────────────────────────────────────────────────────────────────┤
│  PRESENTATION LAYER (UI)                                         │
│  ├── Public Pages (Root Directory)                               │
│  │   ├── index.php (Landing Page)                                │
│  │   ├── careers.php (Job Board)                                 │
│  │   ├── apply.php (Job Application)                             │
│  │   └── my-account.php (Applicant Portal)                       │
│  ├── Admin Panel (/pages/)                                       │
│  │   ├── dashboard.php (Main Dashboard)                          │
│  │   ├── hr-recruitment-dashboard.php (Recruitment Pipeline)     │
│  │   ├── admin-dashboard.php (System Admin)                      │
│  │   └── manager-dashboard.php (Manager View)                    │
│  └── Partials (/partials/) - Reusable Components                 │
├─────────────────────────────────────────────────────────────────┤
│  APPLICATION LAYER (Business Logic)                              │
│  ├── /includes/                                                  │
│  │   ├── rbac_helper.php (Role-Based Access Control)             │
│  │   ├── email_generator.php (Auto Email & Hire Processing)     │
│  │   └── loading-screen.php (UI Component)                       │
│  ├── /pages/api/ (REST Endpoints)                                │
│  └── navigation-helper.php (Route Management)                   │
├─────────────────────────────────────────────────────────────────┤
│  DATA LAYER                                                      │
│  ├── /database/config.php (PDO Connection & Helpers)            │
│  ├── MySQL Database: hr1_hr1data                                 │
│  │   └── Schema: hr1_rbac_schema.sql                             │
│  └── /uploads/ (File Storage)                                    │
│      ├── /resumes/ (Applicant Resumes)                           │
│      ├── /documents/ (Employee Documents)                        │
│      └── /handbooks/ (Company Handbooks)                         │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. Role-Based Access Control (RBAC)

### User Roles Hierarchy

| Role | Type | Access Level | Description |
|------|------|--------------|-------------|
| System Administrator | Admin | Full | Complete system access |
| HR Manager | HR_Staff | Functional | HR department head with approvals |
| HR Staff | HR_Staff | Functional | Recruitment, onboarding, job postings |
| Fleet Manager | Manager | Department | Fleet Operations management |
| Warehouse Manager | Manager | Department | Warehouse Operations management |
| Logistics Manager | Manager | Department | Logistics department management |
| Employee | Employee | Self | Own data access only |
| New Hire | Employee | Self | Limited access during onboarding |
| Applicant | Applicant | External | Job application portal only |

### Departments

| Code | Department Name |
|------|-----------------|
| HR | Human Resources |
| FLEET | Fleet Operations |
| LOGISTICS | Logistics |
| WAREHOUSE | Warehouse |
| FINANCE | Finance |
| IT | Information Technology |
| DISPATCH | Dispatch Center |
| MAINTENANCE | Vehicle Maintenance |

---

## 3. User Journey Flows

### A. APPLICANT FLOW (Job Seeker → Employee)

```
START
  │
  ├─→ [index.php] Landing Page
  │     │
  │     └─→ [careers.php] Browse Active Jobs
  │           │
  │           └─→ [job_details.php?id=X] View Job Details
  │                 │
  │                 └─→ [apply.php?job_id=X] Submit Application
  │                       │
  │                       ├─→ Upload Resume (PDF/DOC/DOCX)
  │                       ├─→ Enter Cover Letter
  │                       └─→ INSERT → job_applications table
  │                             │
  │                             └─→ [partials/register-applicant.php] Create Account
  │                                   │
  │                                   └─→ [my-account.php] Applicant Portal
  │                                         │
  │                                         ├─→ View Application Status
  │                                         ├─→ [screening.php] Take Assessment (if required)
  │                                         └─→ Wait for HR Decision
  │                                               │
  │                                               └─→ IF HIRED:
  │                                                     │
  │                                                     ├─→ Receive Company Email
  │                                                     ├─→ Receive Employee ID
  │                                                     ├─→ Receive Temp Password
  │                                                     └─→ [employee-onboarding.php] Complete Onboarding
  │
END (Now an Employee)
```

### B. HR RECRUITMENT FLOW

```
START
  │
  ├─→ [login.php] HR Staff Login
  │     │
  │     └─→ [pages/hr-recruitment-dashboard.php] Recruitment Pipeline
  │           │
  │           ├─→ TAB: Recruitment Pipeline (Kanban Board)
  │           │     │
  │           │     ├─→ Column: New Apply
  │           │     ├─→ Column: Screening
  │           │     ├─→ Column: For Interview
  │           │     ├─→ Column: Road Test
  │           │     ├─→ Column: Offer Sent
  │           │     └─→ Column: HIRED ← HIRE BUTTON HERE
  │           │           │
  │           │           └─→ processApplicantHire()
  │           │                 │
  │           │                 ├─→ Generate Company Email
  │           │                 ├─→ Generate Employee ID (EMP-YYYY-XXXXX)
  │           │                 ├─→ Create user_accounts record
  │           │                 ├─→ Update job_applications status
  │           │                 └─→ Initialize Onboarding Tasks
  │           │
  │           ├─→ TAB: Document Verification
  │           │     │
  │           │     └─→ Verify uploaded documents
  │           │
  │           └─→ TAB: Onboarding Status
  │                 │
  │                 └─→ Track new hire progress
  │
  ├─→ [pages/recruitment.php] Advanced Recruitment
  │     │
  │     ├─→ Job Requisition Management
  │     ├─→ Screening Questions Setup
  │     ├─→ Interview Scheduling
  │     └─→ Social Media Sharing
  │
  └─→ [pages/create-job-posting.php] Create New Jobs
  │
END
```

### C. MANAGER FLOW

```
START
  │
  ├─→ [login.php] Manager Login
  │     │
  │     └─→ [pages/manager-dashboard.php] Manager Dashboard
  │           │
  │           ├─→ View Department Employees
  │           ├─→ [pages/manager-view-employee-info.php] View Employee Details
  │           ├─→ [pages/manager-assign-tasks.php] Assign Tasks
  │           ├─→ [pages/manager-upload-handbook.php] Upload Handbooks
  │           └─→ View Department Statistics
  │
END
```

### D. EMPLOYEE FLOW

```
START
  │
  ├─→ [login.php] Employee Login (Company Email)
  │     │
  │     └─→ [pages/employee-portal.php] Employee Portal
  │           │
  │           ├─→ View Personal Information
  │           ├─→ View Onboarding Tasks
  │           ├─→ View Performance Reviews
  │           └─→ Access Company Documents
  │
END
```

### E. ADMIN FLOW

```
START
  │
  ├─→ [login.php] Admin Login
  │     │
  │     └─→ [pages/admin-dashboard.php] System Administration
  │           │
  │           ├─→ User Management
  │           ├─→ Role & Permission Management
  │           ├─→ Department Management
  │           ├─→ System Settings
  │           └─→ Audit Logs
  │
END
```

---

## 4. Complete File Structure

### Root Directory Files
```
/HR1/
├── index.php              - Landing page with company info
├── careers.php            - Public job board listing
├── job_details.php        - Individual job details page
├── apply.php              - Job application form with file upload
├── my-account.php         - Applicant portal dashboard
├── login.php              - Login redirect
├── logout.php             - Session destruction
├── auth.php               - Authentication helpers
├── screening.php          - Applicant screening assessment
├── screening-assessment.php - Assessment interface
├── employee-onboarding.php - New hire onboarding portal
├── user-management.php    - User administration
├── navigation-helper.php  - Route/navigation management
└── applicant-portal.php   - Alternate applicant view
```

### /pages/ Directory (Admin/Staff)
```
/pages/
├── dashboard.php              - Main dashboard hub
├── admin-dashboard.php        - System admin controls
├── hr-recruitment-dashboard.php - MAIN RECRUITMENT PIPELINE (Kanban)
├── manager-dashboard.php      - Manager view
├── recruitment.php            - Advanced recruitment tools
├── applications.php           - Application list view
├── applicant-management.php   - Applicant tracking system
├── job-posting.php            - Manage job postings
├── create-job-posting.php     - Create new job postings
├── onboarding.php             - Onboarding management
├── performance.php            - Performance reviews
├── employee-portal.php        - Employee self-service
├── view_file.php              - Document viewer (PDF/Image/DOC)
├── manager-assign-tasks.php   - Task assignment
├── manager-view-employee-info.php - View employee details
├── manager-upload-handbook.php - Upload company handbooks
└── /api/                      - REST API endpoints
    ├── create_goal.php        - Create performance goals
    ├── schedule_review.php    - Schedule performance reviews
    └── update_application_status.php - Update application status
```

### /includes/ Directory (Business Logic)
```
/includes/
├── email_generator.php    - Auto-generate company emails & process hires
├── rbac_helper.php        - Role-based access control functions
└── loading-screen.php     - Loading animation component
```

### /partials/ Directory (Reusable UI Components)
```
/partials/
├── header.php             - Page header
├── footer.php             - Page footer
├── sidebar.php            - Navigation sidebar
├── login.php              - Login form modal
├── register.php           - Registration form
├── register-applicant.php - Applicant registration
├── register-employee.php  - Employee registration
├── register-portal.php    - Portal registration
├── register-info.php      - Registration info display
├── terms.php              - Terms and conditions
├── legal_links.php        - Legal links
└── loading-screen.php     - Loading screen overlay
```

### /database/ Directory
```
/database/
├── config.php                    - PDO connection & helper functions
├── hr1_rbac_schema.sql           - MAIN SCHEMA (roles, permissions, users)
├── hr_management_schema.sql      - HR tables schema
├── hr_management_schema_clean.sql - Clean schema version
├── sample_applicants.sql         - Sample applicant data
├── add_applicant_employee_tables.sql
├── add_screening_tables.sql
├── add_verification_doc_column.sql
├── create_screening_table.sql
├── update_users_table.sql
└── users.sql
```

### /uploads/ Directory
```
/uploads/
├── /resumes/      - Applicant resume files
├── /documents/    - Employee documents
├── /handbooks/    - Company handbooks
└── /profiles/     - Profile pictures
```

---

## 5. Database Schema

### Core Tables

```
┌─────────────────────────────────────────────────────────────────┐
│                         DATABASE: hr1_hr1data                    │
├─────────────────────────────────────────────────────────────────┤

┌─────────────────┐       ┌─────────────────────┐
│     roles       │       │    departments      │
├─────────────────┤       ├─────────────────────┤
│ id (PK)         │       │ id (PK)             │
│ role_name       │       │ department_code     │
│ role_type       │◄──┐   │ department_name     │
│ description     │   │   │ parent_department_id│
│ access_level    │   │   │ manager_id          │
└─────────────────┘   │   └─────────────────────┘
        │             │             │
        │ FK          │             │ FK
        ▼             │             ▼
┌─────────────────────────────────────────────────┐
│                 user_accounts                    │
├─────────────────────────────────────────────────┤
│ id (PK)                                          │
│ employee_id (UNIQUE) - Format: EMP-YYYY-XXXXX   │
│ first_name, last_name, middle_name              │
│ personal_email                                   │
│ company_email (UNIQUE) - @slatefreight.com      │
│ phone                                            │
│ password_hash                                    │
│ role_id (FK) ───────────────────────────────────┘
│ department_id (FK) ─────────────────────────────┘
│ job_title                                        │
│ hire_date                                        │
│ employment_status (Active/Probation/On Leave/   │
│                    Terminated/Resigned)          │
│ profile_picture                                  │
│ last_login                                       │
│ status (Active/Inactive/Pending)                │
└─────────────────────────────────────────────────┘

┌─────────────────────┐
│    job_postings     │
├─────────────────────┤
│ id (PK)             │
│ title               │
│ description         │
│ department_id (FK)  │──→ departments
│ location            │
│ employment_type     │
│ salary_range        │
│ requirements        │
│ status (active/     │
│   inactive/closed)  │
│ closing_date        │
│ created_by (FK)     │──→ user_accounts
└──────────┬──────────┘
           │
           │ 1:N
           ▼
┌─────────────────────────────────────────────────┐
│              job_applications                    │
├─────────────────────────────────────────────────┤
│ id (PK)                                          │
│ job_posting_id (FK) ────────────────────────────┘
│ first_name, last_name, middle_name              │
│ email, phone                                     │
│ resume_path                                      │
│ cover_letter                                     │
│ status (new/screening/interview/road_test/      │
│         offer/hired/rejected)                    │
│ applied_date                                     │
│ hired_date                                       │
│ reviewed_by (FK) ───────────────────────────────→ user_accounts
└─────────────────────────────────────────────────┘

┌─────────────────────┐       ┌─────────────────────┐
│ screening_questions │       │ screening_responses │
├─────────────────────┤       ├─────────────────────┤
│ id (PK)             │◄──────│ question_id (FK)    │
│ job_posting_id (FK) │       │ application_id (FK) │──→ job_applications
│ question_text       │       │ response_text       │
│ question_type       │       │ score               │
└─────────────────────┘       └─────────────────────┘

┌─────────────────────┐       ┌─────────────────────────────┐
│  onboarding_tasks   │       │ employee_onboarding_progress │
├─────────────────────┤       ├─────────────────────────────┤
│ id (PK)             │◄──────│ task_id (FK)                │
│ task_name           │       │ user_id (FK) ───────────────│──→ user_accounts
│ description         │       │ status                      │
│ department_id (FK)  │       │ completed_date              │
│ is_required         │       └─────────────────────────────┘
│ order_sequence      │
└─────────────────────┘

┌─────────────────────┐
│    audit_logs       │
├─────────────────────┤
│ id (PK)             │
│ user_id (FK)        │──→ user_accounts
│ action              │
│ module              │
│ record_id           │
│ old_values (JSON)   │
│ new_values (JSON)   │
│ detail              │
│ ip_address          │
│ created_at          │
└─────────────────────┘

┌─────────────────────┐
│  role_permissions   │
├─────────────────────┤
│ id (PK)             │
│ role_id (FK)        │──→ roles
│ module              │
│ can_view            │
│ can_create          │
│ can_edit            │
│ can_delete          │
│ can_approve         │
│ scope (all/dept/own)│
└─────────────────────┘
```

---

## 6. Key Functions

### /includes/email_generator.php
```php
generateCompanyEmail($firstName, $lastName, $middleName)
├── Creates: firstname.lastname@slatefreight.com
├── Handles duplicates with number suffix
└── Uses middle initial if needed

generateEmployeeId()
├── Format: EMP-YYYY-XXXXX
└── Auto-increments based on existing IDs

processApplicantHire($applicantId, $roleId, $departmentId, $jobTitle, $hiredByUserId)
├── 1. Fetch applicant from job_applications
├── 2. Generate company email
├── 3. Generate employee ID
├── 4. Generate temp password
├── 5. INSERT into user_accounts
├── 6. UPDATE job_applications status = 'hired'
├── 7. Log audit action
├── 8. Initialize onboarding tasks
└── Returns: {success, employee_id, company_email, temp_password, user_id}

initializeOnboardingTasks($userId, $departmentId)
└── Creates onboarding progress entries for new hire

logAuditAction($userId, $action, $module, $recordId, $oldValues, $newValues, $detail)
└── Records all system actions for audit trail
```

### /includes/rbac_helper.php
```php
hasPermission($userId, $module, $action)
└── Checks role_permissions table for access rights

getUserWithRole($userId)
└── Returns user with role and department info

isAdmin($userId)      → Check if user is Admin
isHRStaff($userId)    → Check if user is HR_Staff
isManager($userId)    → Check if user is Manager

getAccessibleEmployees($userId, $filters)
└── Returns employees based on user's access scope

requireRole($allowedRoles, $redirectUrl)
└── Middleware to enforce role-based page access
```

### /database/config.php
```php
getDBConnection()     → Returns PDO connection (singleton)
fetchAll($sql, $params)    → SELECT multiple rows
fetchSingle($sql, $params) → SELECT single row
insertRecord($sql, $params) → INSERT and return lastInsertId
updateRecord($sql, $params) → UPDATE and return rowCount
executeQuery($sql, $params) → Execute any SQL
```

---

## 7. Hiring Process Flow (CRITICAL)

```
[pages/hr-recruitment-dashboard.php]
    │
    ├─→ User clicks "HIRE" button on applicant card
    │
    ├─→ Modal opens: Select Job Title, Department, Role
    │     ├─→ Role Options:
    │     │     ├─→ Employee (role_id: 7)
    │     │     └─→ New Hire/Probation (role_id: 8)
    │     └─→ Department dropdown from departments table
    │
    ├─→ Form submits: action=hire
    │
    └─→ PHP Handler:
          │
          ├─→ processApplicantHire() called
          │     │
          │     ├─→ BEGIN TRANSACTION
          │     │
          │     ├─→ Fetch applicant data
          │     │     SELECT * FROM job_applications WHERE id = ?
          │     │
          │     ├─→ Generate company email
          │     │     firstname.lastname@slatefreight.com
          │     │
          │     ├─→ Generate employee ID
          │     │     EMP-2025-00001 (auto-increment)
          │     │
          │     ├─→ Generate temp password
          │     │     bin2hex(random_bytes(8))
          │     │
          │     ├─→ INSERT INTO user_accounts
          │     │     (employee_id, first_name, last_name,
          │     │      personal_email, company_email, phone,
          │     │      password_hash, role_id, department_id,
          │     │      job_title, hire_date, employment_status)
          │     │
          │     ├─→ UPDATE job_applications
          │     │     SET status='hired', hired_date=NOW()
          │     │
          │     ├─→ Log audit action (HIRE)
          │     │
          │     ├─→ Initialize onboarding tasks
          │     │
          │     ├─→ COMMIT TRANSACTION
          │     │
          │     └─→ Return success with credentials
          │
          └─→ Display result to HR staff
                ├─→ SUCCESS: Show employee_id, company_email, temp_password
                └─→ FAILURE: Show error message
```

---

## 8. Application Status Flow

```
Application Statuses (in order):

┌─────────┐    ┌───────────┐    ┌─────────────┐    ┌───────────┐
│   NEW   │ → │ SCREENING │ → │  INTERVIEW  │ → │ ROAD_TEST │
│ (apply) │    │(assessment)│   │ (scheduled) │    │ (drivers) │
└─────────┘    └───────────┘    └─────────────┘    └───────────┘
                                                         │
                                                         ▼
                                      ┌─────────┐    ┌───────┐
                                      │  OFFER  │ → │ HIRED │
                                      │ (sent)  │    │(final)│
                                      └─────────┘    └───────┘
                                           │
                                           ▼
                                      ┌──────────┐
                                      │ REJECTED │
                                      │  (exit)  │
                                      └──────────┘
```

---

## 9. Security Features

### Path Traversal Protection (view_file.php)
```php
// Sanitize file path
$file_path = str_replace(['../', '..\\', '..'], '', $file_path);
$file_path = preg_replace('/\.\.+/', '', $file_path);

// Verify file is within allowed directory
$base_dir = realpath(__DIR__ . '/../');
$full_path = realpath($base_dir . '/' . $file_path);

if (strpos($full_path, $base_dir) !== 0) {
    die('Access denied');
}
```

### Role-Based Access Control
```php
// Page-level protection
requireRole(['Admin', 'HR_Staff'], '../partials/login.php');

// Action-level protection
if (!hasPermission($_SESSION['user_id'], 'applicant_profiles', 'edit')) {
    die('Permission denied');
}
```

### Password Security
```php
// Hashing
$hash = password_hash($password, PASSWORD_DEFAULT);

// Verification
if (password_verify($input, $stored_hash)) {
    // Login success
}
```

---

## 10. System Entry Points

| User Type | Entry Point | Destination |
|-----------|-------------|-------------|
| **New Visitor** | `index.php` | Landing page |
| **Job Seeker** | `careers.php` | Public job board |
| **Applicant** | `partials/login.php` → `my-account.php` | Applicant portal |
| **HR Staff** | `partials/login.php` → `pages/hr-recruitment-dashboard.php` | Recruitment pipeline |
| **Manager** | `partials/login.php` → `pages/manager-dashboard.php` | Manager portal |
| **Employee** | `partials/login.php` → `pages/employee-portal.php` | Employee self-service |
| **Admin** | `partials/login.php` → `pages/admin-dashboard.php` | System administration |

---

## 11. API Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/pages/api/update_application_status.php` | POST | Update applicant status |
| `/pages/api/create_goal.php` | POST | Create performance goals |
| `/pages/api/schedule_review.php` | POST | Schedule performance reviews |

---

## 12. File Dependencies

### Admin Pages
```
Every /pages/*.php requires:
├── database/config.php (PDO connection)
├── includes/rbac_helper.php (Access control)
├── partials/sidebar.php (Navigation)
├── partials/header.php (Top bar)
└── Session: $_SESSION['user_id']
```

### Public Pages
```
Every public page requires:
├── database/config.php (PDO connection)
└── css/styles.css (Styling)
Optional: Session for logged-in features
```

### Hiring Process
```
pages/hr-recruitment-dashboard.php
├── database/config.php
├── includes/rbac_helper.php
├── includes/email_generator.php ← CRITICAL for hiring
└── partials/sidebar.php
```

---

## 13. Quick Reference

### Default Role IDs
| ID | Role Name |
|----|-----------|
| 1 | System Administrator |
| 2 | HR Staff |
| 3 | HR Manager |
| 4 | Fleet Manager |
| 5 | Warehouse Manager |
| 6 | Logistics Manager |
| 7 | Employee |
| 8 | New Hire |
| 9 | Applicant |

### Default Department IDs
| ID | Code | Name |
|----|------|------|
| 1 | HR | Human Resources |
| 2 | FLEET | Fleet Operations |
| 3 | LOGISTICS | Logistics |
| 4 | WAREHOUSE | Warehouse |
| 5 | FINANCE | Finance |
| 6 | IT | Information Technology |
| 7 | DISPATCH | Dispatch Center |
| 8 | MAINTENANCE | Vehicle Maintenance |

### Employee ID Format
```
EMP-YYYY-XXXXX
Example: EMP-2025-00001
```

### Company Email Format
```
firstname.lastname@slatefreight.com
Example: kyrie.irving@slatefreight.com
```

---

*Last Updated: January 2025*
*Slate Freight Management System - HR1 Module*
