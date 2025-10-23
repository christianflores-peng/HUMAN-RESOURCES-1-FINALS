# HR Management System Database Setup Guide

## Step-by-Step Setup Instructions for phpMyAdmin

### 1. Access phpMyAdmin
- Open your web browser
- Go to: `http://localhost/phpmyadmin` (XAMPP)
- Login with your credentials (usually `root` with no password for local development)

### 2. Check Existing Database
Your system already has a database called `hr1_hr1data` with a `users` table.

### 3. Import the HR Management Schema

#### Option A: Import SQL File
1. Click on the `hr1_hr1data` database in the left sidebar
2. Click on the "Import" tab
3. Click "Choose File" and select `hr_management_schema.sql`
4. Click "Go" to execute the script

#### Option B: Manual SQL Execution
1. Click on the `hr1_hr1data` database in the left sidebar
2. Click on the "SQL" tab
3. Copy and paste the contents of `hr_management_schema.sql`
4. Click "Go" to execute

### 4. Verify Database Structure
After successful import, you should see these tables:
- `users` (existing)
- `departments`
- `employees`
- `job_postings`
- `job_applications`
- `interviews`
- `onboarding_tasks`
- `performance_goals`
- `performance_reviews`
- `recognition_awards`
- `rewards_catalog`
- `reward_redemptions`

### 5. Sample Data
The schema includes sample data for:
- 6 departments (Engineering, Marketing, Sales, HR, Design, Finance)
- 7 reward items (gift cards, PTO, training, etc.)

### 6. Configure PHP Connection
1. Open `database/config.php`
2. Update these settings if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'hr1_hr1data');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Usually empty for Laragon
   ```

### 7. Test Database Connection
Create a test file to verify your connection:

```php
<?php
require_once 'database/config.php';

if (testDBConnection()) {
    echo "✅ Database connection successful!";
} else {
    echo "❌ Database connection failed!";
}
?>
```

## Database Schema Overview

### Core Tables:
- **users**: Authentication and user roles
- **employees**: Employee information and profiles
- **departments**: Company departments and structure

### Recruitment Tables:
- **job_postings**: Available job positions
- **job_applications**: Applications from candidates
- **interviews**: Interview scheduling and feedback

### Performance Management:
- **performance_goals**: Employee goals and objectives
- **performance_reviews**: Performance evaluation records

### Recognition System:
- **recognition_awards**: Peer recognition and awards
- **rewards_catalog**: Available rewards for redemption
- **reward_redemptions**: Reward redemption history

### Onboarding:
- **onboarding_tasks**: Tasks for new employee onboarding

## Security Considerations

1. **Password Hashing**: User passwords are hashed using PHP's `password_hash()`
2. **Prepared Statements**: All database queries use prepared statements to prevent SQL injection
3. **Session Management**: Secure session handling for user authentication
4. **Input Validation**: All user inputs are validated and sanitized

## Next Steps

1. Run the SQL schema in phpMyAdmin
2. Test the database connection
3. Create some test job postings
4. Add employee records
5. Test the full application workflow

## Troubleshooting

### Common Issues:
1. **Connection Failed**: Check DB credentials in config.php
2. **Tables Not Created**: Ensure SQL script ran without errors
3. **Permission Denied**: Check MySQL user permissions
4. **Foreign Key Errors**: Import tables in the correct order (departments first, then employees, etc.)

### Support:
- Check phpMyAdmin error logs
- Verify MySQL service is running
- Ensure database name matches exactly: `hr1_hr1data`
