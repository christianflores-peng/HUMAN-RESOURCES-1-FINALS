<?php
/**
 * Role-Based Portal Router
 * Routes users to their appropriate dashboard based on role_type
 * 
 * Directory Structure:
 * - Admin → modals/admin/dashboard.php
 * - HR_Staff → modals/hr_staff/dashboard.php
 * - Manager → modals/manager/dashboard.php
 * - Applicant → modals/applicant/dashboard.php
 * - Employee → modals/employee/dashboard.php
 */

require_once 'includes/session_helper.php';
startSecureSession();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: partials/login.php');
    exit();
}

// Route based on role
$role = $_SESSION['role_type'] ?? '';

switch ($role) {
    case 'Admin':
        // Admin portal - admin/ directory
        header('Location: modals/admin/dashboard.php');
        break;
        
    case 'HR_Staff':
        // HR Staff - recruitment dashboard
        header('Location: modals/hr_staff/dashboard.php');
        break;
        
    case 'Manager':
        // Manager - manager dashboard
        header('Location: modals/manager/dashboard.php');
        break;
        
    case 'Applicant':
        // Applicant portal - modals/applicant/
        header('Location: modals/applicant/dashboard.php');
        break;
        
    case 'Employee':
        // Employee portal - modals/employee/
        header('Location: modals/employee/dashboard.php');
        break;
        
    default:
        // Fallback to general dashboard
        header('Location: pages/dashboard.php');
        break;
}
exit();
