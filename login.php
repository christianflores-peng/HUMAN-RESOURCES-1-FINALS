<?php
session_start();

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role_type'])) {
    $roleType = $_SESSION['role_type'];
    
    switch ($roleType) {
        case 'Admin':
            header('Location: modals/admin/dashboard.php');
            break;
        case 'HR_Staff':
            header('Location: modals/hr_staff/dashboard.php');
            break;
        case 'Manager':
            header('Location: modals/manager/dashboard.php');
            break;
        case 'Applicant':
            header('Location: modals/applicant/dashboard.php');
            break;
        case 'Employee':
            header('Location: modals/employee/dashboard.php');
            break;
        default:
            header('Location: pages/dashboard.php');
            break;
    }
    exit();
}

// Preserve query string if present
$queryString = $_SERVER['QUERY_STRING'] ?? '';
$redirect = !empty($queryString) ? '?' . $queryString : '';

// Redirect to the login page in partials directory
header('Location: partials/login.php' . $redirect);
exit();
?>
