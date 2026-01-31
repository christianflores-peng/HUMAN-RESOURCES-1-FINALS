# Modals Folder Organization Guide

## 📁 New Structure for modals/ Folder

```
c:\laragon\www\HR1\modals\
│
├── applicant/                    # Applicant Portal (8 files)
│   ├── dashboard.php
│   ├── applications.php
│   ├── application-details.php
│   ├── profile.php
│   ├── notifications.php
│   ├── interview-schedule.php
│   ├── road-test-info.php
│   └── offer-view.php
│
├── manager/                      # Manager/HR Portal (7 files)
│   ├── recruitment-dashboard.php
│   ├── applicant-details.php
│   ├── screening.php
│   ├── schedule-interview.php
│   ├── schedule-road-test.php
│   ├── send-offer.php
│   └── hire-applicant.php
│
├── employee/                     # Employee Portal (5 files)
│   ├── dashboard.php
│   ├── onboarding.php
│   ├── requirements.php
│   ├── profile.php
│   └── documents.php
│
├── admin/                        # Admin Portal (3 files)
│   ├── dashboard.php
│   ├── accounts.php
│   └── audit-logs.php
│
└── components/                   # Existing modal components
    ├── admin-modals.php
    ├── applications-modal.php
    ├── hr-recruitment-modals.php
    ├── manager-modals.php
    ├── onboarding-modals.php
    └── recruitment-modals.php
```

---

## 🔄 File Migration Instructions

### Step 1: Create Subfolders
Create these folders inside `c:\laragon\www\HR1\modals\`:
- `applicant/`
- `manager/`
- `employee/`
- `admin/`
- `components/` (for existing modal files)

### Step 2: Move Applicant Portal Files
Move from root to `modals/applicant/`:
```
applicant-dashboard.php           → modals/applicant/dashboard.php
applicant-applications.php        → modals/applicant/applications.php
applicant-application-details.php → modals/applicant/application-details.php
applicant-profile.php             → modals/applicant/profile.php
applicant-notifications.php       → modals/applicant/notifications.php
applicant-interview-schedule.php  → modals/applicant/interview-schedule.php
applicant-road-test-info.php      → modals/applicant/road-test-info.php
applicant-offer-view.php          → modals/applicant/offer-view.php
```

### Step 3: Move Manager Portal Files
Move from root to `modals/manager/`:
```
manager-recruitment-dashboard.php → modals/manager/recruitment-dashboard.php
manager-applicant-details.php     → modals/manager/applicant-details.php
manager-screening.php             → modals/manager/screening.php
manager-schedule-interview.php    → modals/manager/schedule-interview.php
manager-schedule-road-test.php    → modals/manager/schedule-road-test.php
manager-send-offer.php            → modals/manager/send-offer.php
manager-hire-applicant.php        → modals/manager/hire-applicant.php
```

### Step 4: Move Employee Portal Files
Move from root to `modals/employee/`:
```
employee-dashboard.php     → modals/employee/dashboard.php
employee-onboarding.php    → modals/employee/onboarding.php
employee-requirements.php  → modals/employee/requirements.php
employee-profile.php       → modals/employee/profile.php
employee-documents.php     → modals/employee/documents.php
```

### Step 5: Move Admin Portal Files
Move from root to `modals/admin/`:
```
admin-dashboard.php   → modals/admin/dashboard.php
admin-accounts.php    → modals/admin/accounts.php
admin-audit-logs.php  → modals/admin/audit-logs.php
```

### Step 6: Move Existing Modal Components
Move existing modal files to `modals/components/`:
```
modals/admin-modals.php        → modals/components/admin-modals.php
modals/applications-modal.php  → modals/components/applications-modal.php
modals/hr-recruitment-modals.php → modals/components/hr-recruitment-modals.php
modals/manager-modals.php      → modals/components/manager-modals.php
modals/onboarding-modals.php   → modals/components/onboarding-modals.php
modals/recruitment-modals.php  → modals/components/recruitment-modals.php
```

---

## 🔧 Required Path Updates After Moving

### 1. Update Include Paths in All Portal Files

**OLD (from root):**
```php
require_once 'includes/session_helper.php';
require_once 'database/config.php';
require_once 'includes/workflow_helper.php';
```

**NEW (from modals/[portal]/):**
```php
require_once '../../includes/session_helper.php';
require_once '../../database/config.php';
require_once '../../includes/workflow_helper.php';
```

### 2. Update Asset Paths

**OLD:**
```html
<img src="assets/images/slate.png" alt="SLATE Logo">
<link rel="stylesheet" href="assets/css/style.css">
```

**NEW:**
```html
<img src="../../assets/images/slate.png" alt="SLATE Logo">
<link rel="stylesheet" href="../../assets/css/style.css">
```

### 3. Update Navigation Links (Within Same Portal)

**OLD:**
```php
<a href="applicant-dashboard.php">Dashboard</a>
<a href="applicant-applications.php">Applications</a>
```

**NEW:**
```php
<a href="dashboard.php">Dashboard</a>
<a href="applications.php">Applications</a>
```

### 4. Update Cross-Portal Links

**Example: From Applicant to Manager:**
```php
<!-- OLD -->
<a href="manager-recruitment-dashboard.php">Manager Portal</a>

<!-- NEW -->
<a href="../manager/recruitment-dashboard.php">Manager Portal</a>
```

### 5. Update Redirects

**OLD:**
```php
header('Location: applicant-dashboard.php');
header('Location: index.php');
```

**NEW:**
```php
header('Location: dashboard.php');
header('Location: ../../index.php');
```

### 6. Update Form Actions

**OLD:**
```php
<form action="manager-schedule-interview.php" method="POST">
```

**NEW:**
```php
<form action="schedule-interview.php" method="POST">
```

### 7. Update Upload Paths

**OLD:**
```php
$upload_dir = 'uploads/resumes/';
```

**NEW:**
```php
$upload_dir = '../../uploads/resumes/';
```

---

## 📝 Quick Find & Replace Guide

For each portal file after moving, use these find & replace patterns:

### Pattern 1: Include Paths
```
FIND:    require_once 'includes/
REPLACE: require_once '../../includes/

FIND:    require_once 'database/
REPLACE: require_once '../../database/
```

### Pattern 2: Asset Paths
```
FIND:    src="assets/
REPLACE: src="../../assets/

FIND:    href="assets/
REPLACE: href="../../assets/
```

### Pattern 3: Upload Paths
```
FIND:    'uploads/
REPLACE: '../../uploads/

FIND:    "uploads/
REPLACE: "../../uploads/
```

### Pattern 4: Root Redirects
```
FIND:    header('Location: index.php
REPLACE: header('Location: ../../index.php

FIND:    header('Location: logout.php
REPLACE: header('Location: ../../logout.php
```

### Pattern 5: Same Portal Links (Remove Prefix)
**For Applicant Portal:**
```
FIND:    applicant-dashboard.php
REPLACE: dashboard.php

FIND:    applicant-applications.php
REPLACE: applications.php
```

**For Manager Portal:**
```
FIND:    manager-recruitment-dashboard.php
REPLACE: recruitment-dashboard.php

FIND:    manager-applicant-details.php
REPLACE: applicant-details.php
```

**For Employee Portal:**
```
FIND:    employee-dashboard.php
REPLACE: dashboard.php

FIND:    employee-requirements.php
REPLACE: requirements.php
```

**For Admin Portal:**
```
FIND:    admin-dashboard.php
REPLACE: dashboard.php

FIND:    admin-accounts.php
REPLACE: accounts.php
```

---

## 🚀 Implementation Checklist

### Phase 1: Preparation
- [ ] Backup entire HR1 folder
- [ ] Create subfolder structure in modals/
- [ ] Test one file first before moving all

### Phase 2: Move Files
- [ ] Move 8 applicant files to modals/applicant/
- [ ] Move 7 manager files to modals/manager/
- [ ] Move 5 employee files to modals/employee/
- [ ] Move 3 admin files to modals/admin/
- [ ] Move 6 modal components to modals/components/

### Phase 3: Update Paths (Per Portal)
- [ ] Update applicant portal includes (8 files)
- [ ] Update manager portal includes (7 files)
- [ ] Update employee portal includes (5 files)
- [ ] Update admin portal includes (3 files)

### Phase 4: Update Assets & Links
- [ ] Update asset paths in all files
- [ ] Update navigation links
- [ ] Update form actions
- [ ] Update redirects

### Phase 5: Testing
- [ ] Test applicant portal login & navigation
- [ ] Test manager portal functionality
- [ ] Test employee portal access
- [ ] Test admin portal CRUD operations
- [ ] Test cross-portal navigation
- [ ] Test file uploads
- [ ] Test all redirects

### Phase 6: Cleanup
- [ ] Delete old files from root (after verification)
- [ ] Update documentation
- [ ] Update index.php redirects if needed

---

## 🎯 Benefits of This Organization

1. **Cleaner Root Directory** - Less clutter in main folder
2. **Logical Grouping** - All portal files grouped by role
3. **Easier Maintenance** - Find files quickly by portal type
4. **Better Security** - Can apply folder-level permissions
5. **Scalability** - Easy to add new features per portal
6. **Team Collaboration** - Developers work on specific portals
7. **Clear Structure** - New developers understand layout quickly

---

## ⚠️ Important Notes

1. **Test First:** Move and update ONE file first to test the pattern
2. **Keep Backups:** Don't delete original files until everything works
3. **Update Index:** May need to update index.php to redirect to new paths
4. **Session Paths:** Session helper paths remain the same (../../includes/)
5. **Database Paths:** Database config paths remain the same (../../database/)
6. **Logout Path:** Update logout links to ../../logout.php

---

## 📊 File Count Summary

**Total Files to Move: 23 Portal Files**
- Applicant Portal: 8 files
- Manager Portal: 7 files
- Employee Portal: 5 files
- Admin Portal: 3 files

**Existing Modal Components: 6 files**
- Move to modals/components/

**Total Files in modals/: 29 files**

---

## 🔗 New URL Structure

After organization:
```
Applicant Portal:
http://localhost/HR1/modals/applicant/dashboard.php

Manager Portal:
http://localhost/HR1/modals/manager/recruitment-dashboard.php

Employee Portal:
http://localhost/HR1/modals/employee/dashboard.php

Admin Portal:
http://localhost/HR1/modals/admin/dashboard.php
```

---

**Status:** Ready for Implementation
**Impact:** All 23 portal files + 6 modal components
**Estimated Time:** 30-45 minutes for complete migration
**Risk Level:** Low (with proper testing)

---

## 💡 Pro Tips

1. Use VS Code's "Find in Files" to update paths across multiple files
2. Test each portal separately after moving
3. Keep a checklist of updated files
4. Use browser dev tools to check for 404 errors
5. Clear browser cache after moving files

---

**READY TO ORGANIZE! 🚀**

Follow this guide step-by-step for a clean, organized modals/ folder structure!
