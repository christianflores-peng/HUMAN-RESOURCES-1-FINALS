# HR Management System - File Organization Guide

This document provides a comprehensive overview of the organized file structure for the SLATE HR Management System.

## 📁 Directory Structure

```
hr1/
├── 📄 index.php                          # Main landing page with navigation
├── 📄 login.php                          # User authentication
├── 📄 register.php                       # User registration
├── 📄 logout.php                        # Session termination
├── 📄 applicant-portal.php               # Applicant management portal
├── 📄 careers.php                       # Public job listings
├── 📄 apply.php                         # Job application form
├── 📄 job_details.php                   # Individual job details
├── 📄 test-portal.php                   # Database connection test
├── 📄 test-registration.php             # Registration system test
│
├── 📁 pages/                            # Protected admin pages
│   ├── 📄 dashboard.php                 # Main dashboard
│   ├── 📄 applicant-management.php      # Applicant management
│   ├── 📄 recruitment.php               # Recruitment module
│   ├── 📄 onboarding.php                # Onboarding module
│   ├── 📄 performance.php               # Performance management
│   ├── 📄 recognition.php               # Social recognition
│   └── 📁 api/                          # API endpoints
│       ├── 📄 create_goal.php
│       ├── 📄 schedule_review.php
│       └── 📄 update_application_status.php
│
├── 📁 partials/                         # Reusable components
│   ├── 📄 header.php                    # Page header
│   ├── 📄 sidebar.php                   # Navigation sidebar
│   └── 📄 footer.php                    # Page footer
│
├── 📁 database/                         # Database files
│   ├── 📄 config.php                    # Database configuration
│   ├── 📄 hr_management_schema.sql       # Main database schema
│   ├── 📄 users.sql                     # Users table data
│   ├── 📄 update_users_table.sql        # User table updates
│   ├── 📄 sample_data.php               # Sample data insertion
│   ├── 📄 test_connection.php           # Connection testing
│   └── 📄 DATABASE_SETUP_GUIDE.md      # Database setup guide
│
├── 📁 css/                              # Stylesheets
│   └── 📄 styles.css                    # Main stylesheet
│
├── 📁 js/                               # JavaScript files
│   └── 📄 app.js                        # Main application script
│
├── 📁 assets/                           # Static assets
│   └── 📁 images/
│       └── 📄 slate.png                 # SLATE logo
│
├── 📁 uploads/                          # File uploads
│   └── 📁 resumes/                      # Resume storage
│
└── 📁 Documentation/                    # System documentation
    ├── 📄 APPLICANT_PORTAL_README.md
    ├── 📄 FILE_ORGANIZATION_GUIDE.md
    ├── 📄 LOGIN_CREDENTIALS.md
    ├── 📄 PROJECT_STATUS.md
    └── 📄 CLEANUP_SUMMARY.md
```

## 🎯 Main Entry Points

### 1. **Home Page** (`index.php`)
- **Purpose**: Central navigation hub
- **Features**: 
  - System overview
  - Module access cards
  - Authentication status
  - Quick actions
- **Access**: Public (with login prompts for protected features)

### 2. **Authentication** 
- **Login** (`login.php`): User authentication with demo credentials
- **Register** (`register.php`): New user registration
- **Logout** (`logout.php`): Session termination

### 3. **Applicant Portal** (`applicant-portal.php`)
- **Purpose**: Applicant management dashboard
- **Features**:
  - Real-time statistics
  - Recent applications
  - Quick actions
  - Database integration
- **Access**: Authenticated users only

## 🔐 Protected Areas

### Admin Pages (`pages/` directory)
- **Dashboard** (`dashboard.php`): Main admin dashboard
- **Applicant Management** (`applicant-management.php`): Kanban-style application tracking
- **Recruitment** (`recruitment.php`): Job posting management
- **Onboarding** (`onboarding.php`): New hire processes
- **Performance** (`performance.php`): Performance tracking
- **Recognition** (`recognition.php`): Employee recognition system

### API Endpoints (`pages/api/`)
- **Create Goal** (`create_goal.php`): Performance goal creation
- **Schedule Review** (`schedule_review.php`): Review scheduling
- **Update Application Status** (`update_application_status.php`): Status updates

## 🌐 Public Access

### Job System
- **Careers** (`careers.php`): Public job listings
- **Apply** (`apply.php`): Job application form
- **Job Details** (`job_details.php`): Individual job information

## 🗄️ Database Organization

### Core Tables
- **`users`**: User authentication and profiles
- **`departments`**: Department information
- **`employees`**: Employee records
- **`job_postings`**: Job listings
- **`job_applications`**: Application records
- **`interviews`**: Interview scheduling
- **`onboarding_tasks`**: Onboarding workflows
- **`performance_goals`**: Performance objectives
- **`performance_reviews`**: Review records
- **`recognition_awards`**: Employee recognition
- **`rewards_catalog`**: Available rewards
- **`reward_redemptions`**: Reward claims

### Database Files
- **Schema** (`hr_management_schema.sql`): Complete database structure
- **Users** (`users.sql`): User data
- **Updates** (`update_users_table.sql`): Schema updates
- **Sample Data** (`sample_data.php`): Test data insertion
- **Config** (`config.php`): Connection settings

## 🎨 Design System

### Styling
- **Main Stylesheet** (`css/styles.css`): Complete design system
- **Theme**: Dark with blue accents
- **Responsive**: Mobile-first design
- **Components**: Reusable UI elements

### Assets
- **Logo** (`assets/images/slate.png`): SLATE branding
- **Uploads** (`uploads/`): File storage
- **Resumes** (`uploads/resumes/`): Document storage

## 🧪 Testing & Development

### Test Files
- **Portal Test** (`test-portal.php`): Database connection testing
- **Registration Test** (`test-registration.php`): Registration system testing
- **Connection Test** (`database/test_connection.php`): Database connectivity

### Documentation
- **Setup Guide** (`DATABASE_SETUP_GUIDE.md`): Database setup instructions
- **Portal README** (`APPLICANT_PORTAL_README.md`): Portal documentation
- **Login Credentials** (`LOGIN_CREDENTIALS.md`): Demo account information
- **Project Status** (`PROJECT_STATUS.md`): Development status

## 🚀 Quick Start Guide

### 1. Database Setup
```bash
# Run the main schema
mysql -u root -p hr1_hr1data < database/hr_management_schema.sql

# Update users table
mysql -u root -p hr1_hr1data < database/update_users_table.sql

# Add sample data (optional)
php database/sample_data.php
```

### 2. Test System
```bash
# Test database connection
http://localhost/HR1/test-portal.php

# Test registration
http://localhost/HR1/test-registration.php
```

### 3. Access System
```bash
# Main entry point
http://localhost/HR1/index.php

# Login
http://localhost/HR1/login.php

# Register
http://localhost/HR1/register.php

# Applicant Portal
http://localhost/HR1/applicant-portal.php
```

## 🔧 Configuration

### Database Configuration
Edit `database/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'hr1_hr1data');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### File Permissions
Ensure proper permissions for uploads:
```bash
chmod 755 uploads/
chmod 755 uploads/resumes/
```

## 📱 Navigation Flow

### Public Users
1. **Home** → **Browse Jobs** → **Apply** → **Login/Register**
2. **Careers** → **Job Details** → **Apply**

### Authenticated Users
1. **Home** → **Dashboard** → **Modules**
2. **Applicant Portal** → **Management Tools**
3. **Sidebar Navigation** → **All Modules**

## 🎯 Key Features by Module

### Home Page (`index.php`)
- ✅ System overview
- ✅ Module access cards
- ✅ Authentication status
- ✅ Quick navigation

### Applicant Portal (`applicant-portal.php`)
- ✅ Real-time statistics
- ✅ Recent applications
- ✅ Quick actions
- ✅ Database integration

### Admin Dashboard (`pages/dashboard.php`)
- ✅ HR metrics
- ✅ Employee overview
- ✅ Performance tracking
- ✅ System status

### Job System (`careers.php`, `apply.php`)
- ✅ Public job listings
- ✅ Application forms
- ✅ File uploads
- ✅ Status tracking

## 🔒 Security Features

- ✅ Session-based authentication
- ✅ SQL injection protection
- ✅ Input sanitization
- ✅ File upload security
- ✅ Role-based access control

## 📊 System Status

- ✅ **Database**: Fully integrated
- ✅ **Authentication**: Complete
- ✅ **File Management**: Organized
- ✅ **Navigation**: Unified
- ✅ **Documentation**: Comprehensive
- ✅ **Testing**: Available

---

**Note**: This organization provides a clean, maintainable structure for the HR Management System with clear separation of concerns and easy navigation between modules.
