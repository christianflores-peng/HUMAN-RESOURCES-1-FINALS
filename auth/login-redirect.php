<?php
session_start();

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role_type'])) {
    $roleType = $_SESSION['role_type'];
    
    switch ($roleType) {
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
}

// Preserve query string if present
$queryString = $_SERVER['QUERY_STRING'] ?? '';
$redirect = !empty($queryString) ? '?' . $queryString : '';

// Redirect to the login page in partials directory
header('Location: login.php' . $redirect);
exit();
?>
