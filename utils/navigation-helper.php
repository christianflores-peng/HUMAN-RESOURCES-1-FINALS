<?php
/**
 * Navigation Helper - Quick access to all system modules
 * This file provides easy navigation links for development and testing
 */

session_start();
$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation Helper - SLATE HR System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0f172a;
            color: #f8fafc;
            margin: 0;
            padding: 2rem;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #3b82f6;
            text-align: center;
            margin-bottom: 2rem;
        }
        .nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .nav-section {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1.5rem;
        }
        .nav-section h3 {
            color: #3b82f6;
            margin-bottom: 1rem;
            border-bottom: 2px solid #334155;
            padding-bottom: 0.5rem;
        }
        .nav-links {
            list-style: none;
            padding: 0;
        }
        .nav-links li {
            margin-bottom: 0.5rem;
        }
        .nav-links a {
            color: #cbd5e1;
            text-decoration: none;
            padding: 0.5rem;
            display: block;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .nav-links a:hover {
            background: #334155;
            color: #f8fafc;
        }
        .status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-public { background: #10b981; color: white; }
        .status-protected { background: #f59e0b; color: white; }
        .status-admin { background: #ef4444; color: white; }
        .user-info {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        .user-info h3 {
            color: #3b82f6;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 SLATE HR Management System - Navigation Helper</h1>
        
        <?php if ($is_logged_in): ?>
            <div class="user-info">
                <h3>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!</h3>
                <p>Role: <?php echo htmlspecialchars($_SESSION['role'] ?? 'Employee'); ?></p>
                <a href="logout.php" style="color: #ef4444;">Logout</a>
            </div>
        <?php else: ?>
            <div class="user-info">
                <h3>Not Logged In</h3>
                <p>Some features require authentication</p>
                <a href="partials/login.php" style="color: #3b82f6;">Login</a> | 
                <a href="partials/register.php" style="color: #3b82f6;">Register</a>
            </div>
        <?php endif; ?>
        
        <div class="nav-grid">
            <!-- Main System -->
            <div class="nav-section">
                <h3>🏠 Main System</h3>
                <ul class="nav-links">
                    <li><a href="index.php">Home Page <span class="status status-public">Public</span></a></li>
                    <li><a href="partials/login.php">Login <span class="status status-public">Public</span></a></li>
                    <li><a href="partials/register.php">Register <span class="status status-public">Public</span></a></li>
                    <li><a href="applicant-portal.php">Applicant Portal <span class="status status-protected">Protected</span></a></li>
                </ul>
            </div>
            
            <!-- Job System -->
            <div class="nav-section">
                <h3>💼 Job System</h3>
                <ul class="nav-links">
                    <li><a href="careers.php">Careers Page <span class="status status-public">Public</span></a></li>
                    <li><a href="apply.php">Apply for Jobs <span class="status status-public">Public</span></a></li>
                    <li><a href="job_details.php">Job Details <span class="status status-public">Public</span></a></li>
                </ul>
            </div>
            
            <!-- SPA Portals -->
            <div class="nav-section">
                <h3>👨‍💼 Admin Portal</h3>
                <ul class="nav-links">
                    <li><a href="../modals/admin/index.php">Admin Dashboard <span class="status status-admin">Admin</span></a></li>
                    <li><a href="../modals/admin/index.php?page=accounts">User Management <span class="status status-admin">Admin</span></a></li>
                    <li><a href="../modals/admin/index.php?page=applicant-database">Applicant Database <span class="status status-admin">Admin</span></a></li>
                    <li><a href="../modals/admin/index.php?page=regularization-approval">Regularization <span class="status status-admin">Admin</span></a></li>
                    <li><a href="../modals/admin/index.php?page=recognition-moderation">Recognition Moderation <span class="status status-admin">Admin</span></a></li>
                </ul>
            </div>
            
            <!-- HR Staff Portal -->
            <div class="nav-section">
                <h3>📋 HR Staff Portal</h3>
                <ul class="nav-links">
                    <li><a href="../modals/hr_staff/index.php">HR Dashboard <span class="status status-protected">Protected</span></a></li>
                    <li><a href="../modals/hr_staff/index.php?page=job-requisitions">Job Requisitions <span class="status status-protected">Protected</span></a></li>
                    <li><a href="../modals/hr_staff/index.php?page=job-postings">Job Postings <span class="status status-protected">Protected</span></a></li>
                    <li><a href="../modals/hr_staff/index.php?page=applicant-screening">Applicant Screening <span class="status status-protected">Protected</span></a></li>
                    <li><a href="../modals/hr_staff/index.php?page=recruitment-pipeline">Recruitment Pipeline <span class="status status-protected">Protected</span></a></li>
                    <li><a href="../modals/hr_staff/index.php?page=onboarding-tracker">Onboarding Tracker <span class="status status-protected">Protected</span></a></li>
                </ul>
            </div>
            
            <!-- Manager Portal -->
            <div class="nav-section">
                <h3>� Manager Portal</h3>
                <ul class="nav-links">
                    <li><a href="../modals/manager/index.php">Manager Dashboard <span class="status status-protected">Protected</span></a></li>
                    <li><a href="../modals/manager/index.php?page=job-requisitions">Job Requisitions <span class="status status-protected">Protected</span></a></li>
                    <li><a href="../modals/manager/index.php?page=goal-setting">Goal Setting <span class="status status-protected">Protected</span></a></li>
                    <li><a href="../modals/manager/index.php?page=performance-reviews">Performance Reviews <span class="status status-protected">Protected</span></a></li>
                </ul>
            </div>
            
            <!-- Testing & Development -->
            <div class="nav-section">
                <h3>🧪 Testing & Development</h3>
                <ul class="nav-links">
                    <li><a href="test-portal.php">Test Portal <span class="status status-public">Public</span></a></li>
                    <li><a href="test-registration.php">Test Registration <span class="status status-public">Public</span></a></li>
                    <li><a href="database/test_connection.php">Test Database <span class="status status-public">Public</span></a></li>
                    <li><a href="database/sample_data.php">Sample Data <span class="status status-public">Public</span></a></li>
                </ul>
            </div>
            
            <!-- Documentation -->
            <div class="nav-section">
                <h3>📚 Documentation</h3>
                <ul class="nav-links">
                    <li><a href="FILE_ORGANIZATION_GUIDE.md">File Organization Guide</a></li>
                    <li><a href="APPLICANT_PORTAL_README.md">Applicant Portal README</a></li>
                    <li><a href="LOGIN_CREDENTIALS.md">Login Credentials</a></li>
                    <li><a href="PROJECT_STATUS.md">Project Status</a></li>
                    <li><a href="database/DATABASE_SETUP_GUIDE.md">Database Setup Guide</a></li>
                </ul>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 2rem; color: #64748b;">
            <p>💡 <strong>Tip:</strong> Use this page for quick navigation during development and testing</p>
            <p>🔒 <strong>Security:</strong> Protected pages require login. Admin pages may require specific roles.</p>
        </div>
    </div>
</body>
</html>
