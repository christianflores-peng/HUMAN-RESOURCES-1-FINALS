<?php
/**
 * Role-Based Portal Router
 * Routes users to their appropriate portal based on role_type
 * 
 * Directory Structure:
 * - Admin → modals/admin/index.php
 * - HR_Staff → modals/hr_staff/index.php
 * - Manager → modals/manager/index.php
 * - Applicant → modals/applicant/index.php
 * - Employee → modals/employee/index.php
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
        header('Location: modals/admin/index.php');
        break;
        
    case 'HR_Staff':
        header('Location: modals/hr_staff/index.php');
        break;
        
    case 'Manager':
        header('Location: modals/manager/index.php');
        break;
        
    case 'Applicant':
        header('Location: modals/applicant/index.php');
        break;
        
    case 'Employee':
        header('Location: modals/employee/index.php');
        break;
        
    default:
        header('Location: index.php');
        break;
}
exit();
