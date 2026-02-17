<?php
/**
 * Real-time Analytics API Endpoint
 * Returns current analytics data for dashboard auto-refresh
 */

header('Content-Type: application/json');
require_once '../includes/session_helper.php';
require_once '../database/config.php';

startSecureSession();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$role = $_SESSION['role_type'] ?? '';
$user_id = $_SESSION['user_id'];

$analytics = [];

try {
    // Admin Analytics
    if ($role === 'Admin') {
        $analytics['total_users'] = fetchSingle("SELECT COUNT(*) as count FROM user_accounts")['count'] ?? 0;
        $analytics['active_users'] = fetchSingle("SELECT COUNT(*) as count FROM user_accounts WHERE status = 'Active'")['count'] ?? 0;
        $analytics['inactive_users'] = fetchSingle("SELECT COUNT(*) as count FROM user_accounts WHERE status != 'Active'")['count'] ?? 0;
        $analytics['total_applications'] = fetchSingle("SELECT COUNT(*) as count FROM job_applications")['count'] ?? 0;
        $analytics['today_activities'] = fetchSingle("SELECT COUNT(*) as count FROM audit_logs WHERE DATE(created_at) = CURDATE()")['count'] ?? 0;
        $analytics['pending_approvals'] = fetchSingle("SELECT COUNT(*) as count FROM job_applications WHERE status = 'screening'")['count'] ?? 0;
        $analytics['active_postings'] = fetchSingle("SELECT COUNT(*) as count FROM job_postings WHERE status = 'Open'")['count'] ?? 0;
        $analytics['total_hired'] = fetchSingle("SELECT COUNT(*) as count FROM job_applications WHERE status = 'Hired'")['count'] ?? 0;
    }
    
    // HR Staff Analytics
    elseif ($role === 'HR_Staff') {
        $analytics['total_applications'] = fetchSingle("SELECT COUNT(*) as count FROM job_applications")['count'] ?? 0;
        $analytics['pending_screening'] = fetchSingle("SELECT COUNT(*) as count FROM job_applications WHERE status = 'screening'")['count'] ?? 0;
        $analytics['scheduled_interviews'] = fetchSingle("SELECT COUNT(*) as count FROM job_applications WHERE status = 'interview'")['count'] ?? 0;
        $analytics['active_postings'] = fetchSingle("SELECT COUNT(*) as count FROM job_postings WHERE status = 'Open'")['count'] ?? 0;
        $analytics['today_activities'] = fetchSingle("SELECT COUNT(*) as count FROM audit_logs WHERE DATE(created_at) = CURDATE()")['count'] ?? 0;
    }
    
    // Manager Analytics
    elseif ($role === 'Manager') {
        $dept_id = $_SESSION['department_id'] ?? null;
        if ($dept_id) {
            $analytics['team_members'] = fetchSingle("SELECT COUNT(*) as count FROM user_accounts WHERE department_id = ? AND status = 'Active'", [$dept_id])['count'] ?? 0;
            $analytics['pending_reviews'] = fetchSingle("SELECT COUNT(*) as count FROM performance_goals WHERE department_id = ? AND status = 'pending'", [$dept_id])['count'] ?? 0;
            $analytics['open_requisitions'] = fetchSingle("SELECT COUNT(*) as count FROM job_requisitions WHERE department_id = ? AND status = 'Pending'", [$dept_id])['count'] ?? 0;
        }
        $analytics['today_activities'] = fetchSingle("SELECT COUNT(*) as count FROM audit_logs WHERE user_id = ? AND DATE(created_at) = CURDATE()", [$user_id])['count'] ?? 0;
    }
    
    // Employee Analytics
    elseif ($role === 'Employee') {
        $analytics['my_goals'] = fetchSingle("SELECT COUNT(*) as count FROM performance_goals WHERE employee_id = ?", [$user_id])['count'] ?? 0;
        $analytics['pending_tasks'] = fetchSingle("SELECT COUNT(*) as count FROM performance_goals WHERE employee_id = ? AND status = 'in_progress'", [$user_id])['count'] ?? 0;
        $analytics['recognitions'] = fetchSingle("SELECT COUNT(*) as count FROM social_recognitions WHERE recipient_id = ?", [$user_id])['count'] ?? 0;
        $analytics['today_activities'] = fetchSingle("SELECT COUNT(*) as count FROM audit_logs WHERE user_id = ? AND DATE(created_at) = CURDATE()", [$user_id])['count'] ?? 0;
    }
    
    // Applicant Analytics
    elseif ($role === 'Applicant') {
        $analytics['my_applications'] = fetchSingle("SELECT COUNT(*) as count FROM job_applications WHERE user_id = ?", [$user_id])['count'] ?? 0;
        $analytics['pending_applications'] = fetchSingle("SELECT COUNT(*) as count FROM job_applications WHERE user_id = ? AND status IN ('screening', 'interview')", [$user_id])['count'] ?? 0;
        $analytics['scheduled_interviews'] = fetchSingle("SELECT COUNT(*) as count FROM job_applications WHERE user_id = ? AND status = 'interview'", [$user_id])['count'] ?? 0;
        $analytics['unread_notifications'] = fetchSingle("SELECT COUNT(*) as count FROM applicant_notifications WHERE user_id = ? AND is_read = 0", [$user_id])['count'] ?? 0;
    }
    
    // Add timestamp
    $analytics['last_updated'] = date('Y-m-d H:i:s');
    $analytics['success'] = true;
    
} catch (Exception $e) {
    error_log("Analytics API Error: " . $e->getMessage());
    $analytics['error'] = 'Failed to fetch analytics';
    $analytics['success'] = false;
}

echo json_encode($analytics);
?>
