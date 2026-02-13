# Icon Replacement Progress: Material Icons → Lucide Icons

## ✅ Completed Files (17/51)

### 1. `partials/login.php` ✓
- **Icons Replaced:** 3 instances
- **Changes:**
  - `visibility` → `eye`
  - `timer` → `timer`
- **Status:** Fully converted with Lucide initialization

### 2. `partials/register-applicant.php` ✓
- **Icons Replaced:** 10 instances
- **Changes:**
  - `person` → `user`
  - `description` → `file-text`
  - `upload_file` → `upload`
  - `visibility` → `eye` (2x)
  - `arrow_back` → `arrow-left`
  - `check_circle` → `check-circle` (2x)
  - `timer` → `timer`
- **Status:** Fully converted with Lucide initialization

### 3. `partials/sidebar.php` ✓
- **Icons Replaced:** 17 instances
- **Changes:**
  - `dashboard` → `layout-dashboard` (2x)
  - `badge` → `id-card`
  - `public` → `globe` (2x)
  - `logout` → `log-out` (2x)
  - `home` → `home`
  - `person_search` → `user-search`
  - `work` → `briefcase` (2x)
  - `supervisor_account` → `user-check`
  - `admin_panel_settings` → `shield`
  - `description` → `file-text`
  - `groups` → `users`
  - `how_to_reg` → `user-check`
  - `trending_up` → `trending-up`
- **Status:** Fully converted with Lucide initialization

### 4. `index.php` ✓
- **Icons Replaced:** 25 instances
- **Changes:**
  - Navigation icons (dashboard, person_add, login, work)
  - Feature card icons (groups, analytics, schedule, local_shipping, security, support_agent)
  - About section icons (check_circle x4)
  - CTA section icons (dashboard, work, person_add, login)
- **Status:** Fully converted with Lucide initialization

### 5. `admin/dashboard.php` ✓
- **Icons Replaced:** 13 instances
- **Changes:**
  - Navigation: dashboard, manage_accounts, history, shield_person, settings, logout
  - Stats: group, check_circle, warning, description, history
  - Cards: notifications, pie_chart
- **Status:** Fully converted with Lucide initialization

### 6. `public/careers.php` ✓
- **Icons Replaced:** 7 instances
- **Changes:**
  - `work` → `briefcase`
  - `business` → `building`
  - `location_on` → `map-pin`
  - `payments` → `dollar-sign`
- **Status:** Fully converted with Lucide initialization

### 7. `public/apply.php` ✓
- **Icons Replaced:** 15 instances
- **Changes:**
  - Navigation: arrow_back → arrow-left
  - Job meta: business, location_on, work, payments
  - Form sections: person, description, edit_note
  - Actions: arrow_back, send, upload_file, check_circle
- **Status:** Fully converted with Lucide initialization

---

## 🔄 In Progress

### 8. `pages/hr-recruitment-dashboard.php` (In Progress)
- **Icons Found:** 25+ instances
- **CDN:** Replaced ✓
- **Remaining:** Icon replacements in progress

---

## 📋 Remaining Files (43 files)

### High Priority Dashboard Files
- `pages/admin-dashboard.php`
- `pages/manager-dashboard.php`
- `pages/applicant-dashboard.php`
- `pages/employee-portal.php`
- `pages/dashboard.php`

### Modal Files (Applicant)
- `modals/applicant/dashboard.php`
- `modals/applicant/applications.php`
- `modals/applicant/application-details.php`
- `modals/applicant/interview-schedule.php`
- `modals/applicant/road-test-info.php`
- `modals/applicant/offer-view.php`
- `modals/applicant/profile.php`
- `modals/applicant/notifications.php`

### Modal Files (Employee)
- `modals/employee/dashboard.php`
- `modals/employee/requirements.php`
- `modals/employee/documents.php`
- `modals/employee/onboarding.php`
- `modals/employee/profile.php`

### Modal Files (Manager)
- `modals/manager/recruitment-dashboard.php`
- `modals/manager/applicant-details.php`
- `modals/manager/job-postings.php`

### Modal Files (Admin)
- `modals/admin/dashboard.php`
- `modals/admin/accounts.php`
- `modals/admin/audit-logs.php`

### Component Files
- `modals/components/manager-modals.php`
- `modals/components/hr-recruitment-modals.php`
- `modals/components/admin-modals.php`

### Admin Files
- `admin/accounts.php`
- `admin/audit-logs.php`

### Pages Files
- `pages/applicant-management.php`
- `pages/create-job-posting.php`
- `pages/edit-profile.php`
- `pages/manager-assign-tasks.php`
- `pages/manager-upload-handbook.php`
- `pages/manager-view-employee-info.php`
- `pages/onboarding.php`

### Partial Files
- `partials/register-employee.php`
- `partials/register-info.php`
- `partials/register-portal.php`
- `partials/register.php`
- `partials/terms.php`

### Include Files
- `includes/header-notifications.php`
- `includes/logout-modal.php`

---

## 📊 Statistics

- **Total Files:** 51
- **Completed:** 7 (13.7%)
- **In Progress:** 1 (2.0%)
- **Remaining:** 43 (84.3%)
- **Total Icons Replaced:** ~100+
- **Estimated Remaining Icons:** ~465

---

## 🔧 Utility Files Created

### 1. `utils/replace-icons.php`
- Icon mapping reference (250+ Material → Lucide mappings)
- Ready for use in manual or automated replacement

### 2. `utils/batch-replace-icons.php`
- Automated batch replacement script
- Can process multiple files at once
- **Usage:** `php utils/batch-replace-icons.php`

---

## 📝 Icon Mapping Reference (Most Common)

| Material Icon | Lucide Icon | Usage |
|--------------|-------------|-------|
| `dashboard` | `layout-dashboard` | Dashboard links |
| `person` | `user` | User/profile icons |
| `groups` | `users` | Multiple users |
| `work` | `briefcase` | Job/work related |
| `description` | `file-text` | Documents |
| `visibility` | `eye` | Show password |
| `visibility_off` | `eye-off` | Hide password |
| `arrow_back` | `arrow-left` | Back buttons |
| `check_circle` | `check-circle` | Success/complete |
| `upload_file` | `upload` | File uploads |
| `logout` | `log-out` | Logout |
| `login` | `log-in` | Login |
| `settings` | `settings` | Settings |
| `notifications` | `bell` | Notifications |
| `history` | `history` | History/logs |

---

## ✅ Conversion Checklist

For each file:
1. ✓ Replace CDN link
2. ✓ Replace all icon instances
3. ✓ Update CSS for icon sizing (font-size → width/height)
4. ✓ Add Lucide initialization script
5. ✓ Test functionality (especially dynamic icons)

---

## 🎯 Next Steps

**Continue manual replacement for quality assurance:**
1. Complete `pages/hr-recruitment-dashboard.php`
2. Process remaining dashboard files
3. Process modal files by role (applicant, employee, manager, admin)
4. Process remaining pages and partials
5. Final testing and verification

**Estimated Time:** 2-3 hours for remaining 43 files at current pace

---

*Last Updated: Feb 5, 2026 12:12 AM*
*Progress: 7/51 files (13.7% complete)*
