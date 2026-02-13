<?php
/**
 * Role-Based Portal Router
 * Routes users to their appropriate portal based on role_type
 * 
 * Directory Structure:
 * - Admin → views/admin/index.php
 * - HR_Staff → views/hr_staff/index.php
 * - Manager → views/manager/index.php
 * - Applicant → views/applicant/index.php
 * - Employee → views/employee/index.php
 */

require_once '../includes/session_helper.php';
startSecureSession();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Route based on role
$role = $_SESSION['role_type'] ?? '';

switch ($role) {
    case 'Admin':
        header('Location: ../views/admin/index.php');
        break;
        
    case 'HR_Staff':
        header('Location: ../views/hr_staff/index.php');
        break;
        
    case 'Manager':
        header('Location: ../views/manager/index.php');
        break;
        
    case 'Applicant':
        header('Location: ../views/applicant/index.php');
        break;
        
    case 'Employee':
        header('Location: ../views/employee/index.php');
        break;
        
    default:
        header('Location: ../index.php');
        break;
}
exit();
