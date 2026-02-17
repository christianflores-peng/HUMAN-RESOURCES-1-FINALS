<?php
/**
 * Real-time Notifications API Endpoint
 * Returns unread notification count and recent notifications
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

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role_type'] ?? '';

$response = [
    'unread_count' => 0,
    'notifications' => [],
    'success' => true
];

try {
    if ($role === 'Applicant') {
        // Applicants use applicant_notifications table
        $unread = fetchAll("
            SELECT * FROM applicant_notifications 
            WHERE user_id = ? AND is_read = 0 
            ORDER BY created_at DESC 
            LIMIT 5
        ", [$user_id]);
        
        $recent = fetchAll("
            SELECT * FROM applicant_notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 10
        ", [$user_id]);
        
        $response['unread_count'] = count($unread);
        $response['notifications'] = array_map(function($notif) {
            return [
                'id' => $notif['id'],
                'title' => $notif['title'],
                'message' => $notif['message'],
                'type' => $notif['notification_type'],
                'is_read' => (bool)$notif['is_read'],
                'created_at' => $notif['created_at'],
                'time_ago' => getTimeAgo($notif['created_at'])
            ];
        }, $recent);
        
    } else {
        // Other roles use audit_logs as activity feed
        if (in_array($role, ['Admin', 'HR_Staff'])) {
            // Admin/HR see all recent system activity
            $recent = fetchAll("
                SELECT id, user_id, user_email, action, module, detail, created_at 
                FROM audit_logs 
                WHERE UPPER(action) NOT IN ('LOGIN','LOGOUT')
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            
            $unread = fetchAll("
                SELECT id FROM audit_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                  AND UPPER(action) NOT IN ('LOGIN','LOGOUT')
            ");
        } else {
            // Manager/Employee see only their own activity
            $recent = fetchAll("
                SELECT id, user_id, user_email, action, module, detail, created_at 
                FROM audit_logs 
                WHERE user_id = ?
                ORDER BY created_at DESC 
                LIMIT 10
            ", [$user_id]);
            
            $unread = fetchAll("
                SELECT id FROM audit_logs 
                WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ", [$user_id]);
        }
        
        $response['unread_count'] = count($unread);
        $response['notifications'] = array_map(function($log) {
            $message = $log['detail'] ?? ($log['action'] ?? 'Activity') . ' on ' . ($log['module'] ?? 'system');
            $actor = trim((string)($log['user_email'] ?? ''));
            if ($actor !== '' && strcasecmp($actor, 'System') !== 0) {
                $message = $actor . ' - ' . $message;
            }
            return [
                'id' => $log['id'],
                'user_id' => (int)($log['user_id'] ?? 0),
                'message' => $message,
                'action' => $log['action'] ?? '',
                'module' => $log['module'] ?? '',
                'user_email' => $log['user_email'] ?? '',
                'is_read' => true, // Audit logs don't have read status
                'created_at' => $log['created_at'],
                'time_ago' => getTimeAgo($log['created_at'])
            ];
        }, $recent);
    }
    
} catch (Exception $e) {
    error_log("Notifications API Error: " . $e->getMessage());
    $response['error'] = 'Failed to fetch notifications';
    $response['success'] = false;
}

echo json_encode($response);

/**
 * Helper function to get time ago string
 */
function getTimeAgo($datetime) {
    $time_ago = time() - strtotime($datetime);
    
    if ($time_ago < 60) {
        return 'Just now';
    } elseif ($time_ago < 3600) {
        return floor($time_ago / 60) . 'm ago';
    } elseif ($time_ago < 86400) {
        return floor($time_ago / 3600) . 'h ago';
    } else {
        return date('M d', strtotime($datetime));
    }
}
?>
