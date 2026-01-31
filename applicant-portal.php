<?php
require_once 'includes/session_helper.php';
startSecureSession();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Route based on role
$role = $_SESSION['role_type'] ?? '';

// Applicants go to their own dashboard
if ($role === 'Applicant') {
    header('Location: modals/applicant/dashboard.php');
    exit();
}

// Managers/HR/Admin go to recruitment dashboard
if (in_array($role, ['Manager', 'HR_Staff', 'Admin'])) {
    header('Location: modals/manager/recruitment-dashboard.php');
    exit();
}

// Employees go to employee dashboard
if ($role === 'Employee') {
    header('Location: modals/employee/dashboard.php');
    exit();
}

// Fallback - redirect to login
header('Location: login.php');
exit();
