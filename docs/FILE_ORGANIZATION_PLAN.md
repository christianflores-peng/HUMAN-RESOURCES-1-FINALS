# HR1 File Organization Plan

## 📁 New Folder Structure

```
c:\laragon\www\HR1\
│
├── portals/                          # All portal files organized by role
│   ├── applicant/                    # Applicant Portal (8 files)
│   │   ├── dashboard.php
│   │   ├── applications.php
│   │   ├── application-details.php
│   │   ├── profile.php
│   │   ├── notifications.php
│   │   ├── interview-schedule.php
│   │   ├── road-test-info.php
│   │   └── offer-view.php
│   │
│   ├── manager/                      # Manager/HR Portal (7 files)
│   │   ├── recruitment-dashboard.php
│   │   ├── applicant-details.php
│   │   ├── screening.php
│   │   ├── schedule-interview.php
│   │   ├── schedule-road-test.php
│   │   ├── send-offer.php
│   │   └── hire-applicant.php
│   │
│   ├── employee/                     # Employee Portal (5 files)
│   │   ├── dashboard.php
│   │   ├── onboarding.php
│   │   ├── requirements.php
│   │   ├── profile.php
│   │   └── documents.php
│   │
│   └── admin/                        # Admin Portal (3 files)
│       ├── dashboard.php
│       ├── accounts.php
│       └── audit-logs.php
│
├── includes/                         # Helper functions and utilities
│   ├── session_helper.php
│   ├── rbac_helper.php
│   ├── workflow_helper.php
│   └── notification_helper.php
│
├── database/                         # Database related files
│   ├── config.php
│   ├── hr1_rbac_schema.sql
│   └── update_application_workflow.sql
│
├── partials/                         # Reusable components
│   ├── header.php
│   ├── footer.php
│   ├── login.php
│   ├── register-applicant.php
│   └── register-applicant-documents.php
│
├── modals/                           # Modal dialogs
│   └── hr-recruitment-modals.php
│
├── assets/                           # Static assets
│   ├── css/
│   ├── js/
│   └── images/
│
├── uploads/                          # User uploaded files
│   ├── resumes/
│   ├── documents/
│   └── requirements/
│
├── utils/                            # Utility scripts
│   └── run_database_update.php
│
├── docs/                             # Documentation
│   ├── APPLICANT_TRACKING_SYSTEM_PLAN.md
│   ├── IMPLEMENTATION_SUMMARY.md
│   ├── QUICK_START_GUIDE.md
│   └── FILE_ORGANIZATION_PLAN.md
│
├── pages/                            # Legacy pages (to be migrated)
│   ├── dashboard.php
│   ├── applicant-dashboard.php
│   └── edit-profile.php
│
├── index.php                         # Main entry point
├── logout.php                        # Logout handler
└── my-account.php                    # Account management
```

---

## 🔄 File Migration Map

### Applicant Portal Files (Move to `portals/applicant/`)
- `applicant-dashboard.php` → `portals/applicant/dashboard.php`
- `applicant-applications.php` → `portals/applicant/applications.php`
- `applicant-application-details.php` → `portals/applicant/application-details.php`
- `applicant-profile.php` → `portals/applicant/profile.php`
- `applicant-notifications.php` → `portals/applicant/notifications.php`
- `applicant-interview-schedule.php` → `portals/applicant/interview-schedule.php`
- `applicant-road-test-info.php` → `portals/applicant/road-test-info.php`
- `applicant-offer-view.php` → `portals/applicant/offer-view.php`

### Manager Portal Files (Move to `portals/manager/`)
- `manager-recruitment-dashboard.php` → `portals/manager/recruitment-dashboard.php`
- `manager-applicant-details.php` → `portals/manager/applicant-details.php`
- `manager-screening.php` → `portals/manager/screening.php`
- `manager-schedule-interview.php` → `portals/manager/schedule-interview.php`
- `manager-schedule-road-test.php` → `portals/manager/schedule-road-test.php`
- `manager-send-offer.php` → `portals/manager/send-offer.php`
- `manager-hire-applicant.php` → `portals/manager/hire-applicant.php`

### Employee Portal Files (Move to `portals/employee/`)
- `employee-dashboard.php` → `portals/employee/dashboard.php`
- `employee-onboarding.php` → `portals/employee/onboarding.php`
- `employee-requirements.php` → `portals/employee/requirements.php`
- `employee-profile.php` → `portals/employee/profile.php`
- `employee-documents.php` → `portals/employee/documents.php`

### Admin Portal Files (Move to `portals/admin/`)
- `admin-dashboard.php` → `portals/admin/dashboard.php`
- `admin-accounts.php` → `portals/admin/accounts.php`
- `admin-audit-logs.php` → `portals/admin/audit-logs.php`

---

## 🔧 Required Path Updates

After moving files, update the following paths in each file:

### 1. Include Paths
**OLD:** `require_once 'includes/session_helper.php';`
**NEW:** `require_once '../../includes/session_helper.php';`

**OLD:** `require_once 'database/config.php';`
**NEW:** `require_once '../../database/config.php';`

### 2. Asset Paths
**OLD:** `<img src="assets/images/slate.png">`
**NEW:** `<img src="../../assets/images/slate.png">`

### 3. Navigation Links
**OLD:** `<a href="applicant-dashboard.php">`
**NEW:** `<a href="dashboard.php">` (within same portal folder)

**OLD:** `<a href="manager-recruitment-dashboard.php">`
**NEW:** `<a href="../manager/recruitment-dashboard.php">` (cross-portal)

### 4. Form Actions
**OLD:** `<form action="manager-schedule-interview.php">`
**NEW:** `<form action="schedule-interview.php">` (within same portal)

### 5. Redirects
**OLD:** `header('Location: applicant-dashboard.php');`
**NEW:** `header('Location: dashboard.php');`

**OLD:** `header('Location: index.php');`
**NEW:** `header('Location: ../../index.php');`

---

## ✅ Benefits of New Structure

1. **Better Organization** - Files grouped by role/function
2. **Easier Navigation** - Clear folder hierarchy
3. **Scalability** - Easy to add new features per portal
4. **Maintenance** - Simpler to locate and update files
5. **Security** - Can apply folder-level permissions
6. **Team Collaboration** - Developers can work on specific portals
7. **Clear Separation** - Each portal is independent

---

## 🚀 Implementation Steps

1. ✅ Create folder structure
2. ✅ Copy files to new locations (keep originals as backup)
3. ✅ Update all include paths
4. ✅ Update all asset paths
5. ✅ Update all navigation links
6. ✅ Update all form actions
7. ✅ Update all redirects
8. ✅ Test each portal thoroughly
9. ✅ Delete old files after verification
10. ✅ Update documentation

---

**Status:** Ready for Implementation
**Last Updated:** January 30, 2026
**Impact:** All 23 portal files will be reorganized
