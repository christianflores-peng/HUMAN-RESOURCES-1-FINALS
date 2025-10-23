# Applicant Management Portal

A modern, database-integrated portal for managing job applications and hiring processes in the HR Management System.

## Features

### 🎯 **Portal Dashboard**
- Real-time statistics and metrics
- Recent applications overview
- Quick access to key functions
- Modern, responsive design matching the system's uniform style

### 📊 **Database Integration**
- Live data from MySQL database
- Real-time application counts
- Status tracking and analytics
- Secure database connections

### 🎨 **Design System**
- Consistent with login page design
- Dark theme with blue accents
- Responsive layout for all devices
- Professional SLATE branding

## Files Created

1. **`applicant-portal.php`** - Main portal page
2. **`test-portal.php`** - Database connection test
3. **`database/sample_data.php`** - Sample data insertion script

## Setup Instructions

### 1. Database Setup
First, ensure your database is set up with the schema:

```bash
# Run the database schema
mysql -u root -p hr1_hr1data < database/hr_management_schema.sql
```

### 2. Test Database Connection
Visit `test-portal.php` to verify your database connection and table structure.

### 3. Insert Sample Data (Optional)
Run `database/sample_data.php` to populate the database with sample job postings and applications for testing.

### 4. Access the Portal
1. Login to the system using `login.php`
2. Navigate to `applicant-portal.php`
3. Or access directly: `http://localhost/HR1/applicant-portal.php`

## Portal Sections

### 📈 **Statistics Dashboard**
- Total Applications
- New Applications (This Week)
- Active Job Postings
- Successful Hires

### 🚀 **Quick Actions**
- **View All Applications** - Browse and manage applications
- **Analytics Dashboard** - Track hiring metrics
- **Job Postings** - Create and manage job postings
- **Team Collaboration** - Coordinate with hiring teams

### 📋 **Recent Applications**
- Live feed of recent applications
- Status indicators with color coding
- Quick access to application details

## Database Tables Used

The portal integrates with these database tables:

- **`users`** - User authentication and roles
- **`departments`** - Department information
- **`job_postings`** - Job posting details
- **`job_applications`** - Application records
- **`employees`** - Employee information

## Status Color Coding

- 🔵 **New** - Blue (new applications)
- 🟡 **Reviewed** - Yellow (under review)
- 🟣 **Screening** - Purple (in screening process)
- 🔵 **Interview** - Cyan (interview stage)
- 🟢 **Offer** - Green (offer extended)
- 🟢 **Hired** - Green (successful hire)
- 🔴 **Rejected** - Red (not selected)

## Customization

### Styling
The portal uses CSS variables for easy theming. Key variables in `css/styles.css`:

```css
:root {
    --primary-color: #3b82f6;
    --bg-primary: #0f172a;
    --bg-secondary: #1e293b;
    --text-primary: #f8fafc;
}
```

### Database Queries
Modify the SQL queries in `applicant-portal.php` to customize:
- Statistics calculations
- Recent applications display
- Status filtering

## Security Features

- Session-based authentication
- SQL injection protection with prepared statements
- Input sanitization with `htmlspecialchars()`
- Secure database connections

## Browser Compatibility

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check `database/config.php` settings
   - Verify MySQL server is running
   - Ensure database exists

2. **No Data Displayed**
   - Run `database/sample_data.php` to add test data
   - Check table structure with `test-portal.php`

3. **Login Required**
   - Access through `login.php` first
   - Or modify session check in `applicant-portal.php`

### Debug Mode
Enable debug mode by setting in `database/config.php`:

```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

## Performance Optimization

- Database indexes on frequently queried columns
- Pagination for large datasets
- Caching for statistics (can be implemented)
- Optimized SQL queries

## Future Enhancements

- Real-time notifications
- Advanced filtering and search
- Export functionality
- Mobile app integration
- Advanced analytics dashboard

## Support

For issues or questions:
1. Check the database connection with `test-portal.php`
2. Verify all required tables exist
3. Check PHP error logs
4. Ensure proper file permissions

---

**Note**: This portal is designed to work with the existing HR Management System database schema. Make sure all tables are properly created before using the portal.
