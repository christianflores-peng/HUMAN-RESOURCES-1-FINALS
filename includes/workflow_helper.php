<?php
/**
 * Workflow Helper Functions
 * Handles application status changes and workflow management
 */

require_once __DIR__ . '/../database/config.php';

/**
 * Change application status and log the change
 */
function changeApplicationStatus($application_id, $new_status, $changed_by, $notes = null) {
    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();
        
        // Get current status
        $current = fetchSingle("SELECT status FROM job_applications WHERE id = ?", [$application_id]);
        $old_status = $current['status'] ?? null;
        
        // Update application status
        executeQuery("
            UPDATE job_applications 
            SET status = ?, status_updated_at = NOW(), status_updated_by = ?
            WHERE id = ?
        ", [$new_status, $changed_by, $application_id]);
        
        // Log status change
        insertRecord("
            INSERT INTO application_status_history 
            (application_id, old_status, new_status, changed_by, notes)
            VALUES (?, ?, ?, ?, ?)
        ", [$application_id, $old_status, $new_status, $changed_by, $notes]);
        
        // Create notification for applicant
        $app = fetchSingle("
            SELECT ja.email, ua.id as user_id 
            FROM job_applications ja
            LEFT JOIN user_accounts ua ON ua.personal_email = ja.email
            WHERE ja.id = ?
        ", [$application_id]);
        
        if ($app && $app['user_id']) {
            $notification_title = getStatusChangeNotificationTitle($new_status);
            $notification_message = getStatusChangeNotificationMessage($new_status);
            
            createNotification(
                $application_id,
                $app['user_id'],
                'status_change',
                $notification_title,
                $notification_message
            );
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        if (isset($pdo)) $pdo->rollBack();
        error_log("Error changing application status: " . $e->getMessage());
        return false;
    }
}

/**
 * Create notification for applicant
 */
function createNotification($application_id, $user_id, $type, $title, $message) {
    try {
        insertRecord("
            INSERT INTO applicant_notifications 
            (application_id, user_id, notification_type, title, message)
            VALUES (?, ?, ?, ?, ?)
        ", [$application_id, $user_id, $type, $title, $message]);
        return true;
    } catch (Exception $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Schedule interview
 */
function scheduleInterview($application_id, $interview_type, $scheduled_date, $location, $meeting_link, $created_by) {
    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();
        
        // Create interview schedule
        $interview_id = insertRecord("
            INSERT INTO interview_schedules 
            (application_id, interview_type, scheduled_date, location, meeting_link, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [$application_id, $interview_type, $scheduled_date, $location, $meeting_link, $created_by]);
        
        // Update application
        executeQuery("
            UPDATE job_applications 
            SET interview_type = ?, interview_date = ?, interview_location = ?, interview_status = 'scheduled'
            WHERE id = ?
        ", [$interview_type, $scheduled_date, $location, $application_id]);
        
        // Change status to interview
        changeApplicationStatus($application_id, 'interview', $created_by, 'Interview scheduled');
        
        // Create notification
        $app = fetchSingle("
            SELECT ja.email, ua.id as user_id, ja.first_name
            FROM job_applications ja
            LEFT JOIN user_accounts ua ON ua.personal_email = ja.email
            WHERE ja.id = ?
        ", [$application_id]);
        
        if ($app && $app['user_id']) {
            $interview_type_text = ucfirst(str_replace('_', ' ', $interview_type));
            createNotification(
                $application_id,
                $app['user_id'],
                'interview_scheduled',
                'Interview Scheduled',
                "Your {$interview_type_text} interview has been scheduled for " . date('F d, Y at h:i A', strtotime($scheduled_date))
            );
        }
        
        $pdo->commit();
        return $interview_id;
        
    } catch (Exception $e) {
        if (isset($pdo)) $pdo->rollBack();
        error_log("Error scheduling interview: " . $e->getMessage());
        return false;
    }
}

/**
 * Schedule road test
 */
function scheduleRoadTest($application_id, $scheduled_date, $location, $venue_details, $created_by) {
    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();
        
        // Create road test schedule
        $road_test_id = insertRecord("
            INSERT INTO road_test_schedules 
            (application_id, scheduled_date, location, venue_details, created_by)
            VALUES (?, ?, ?, ?, ?)
        ", [$application_id, $scheduled_date, $location, $venue_details, $created_by]);
        
        // Update application
        executeQuery("
            UPDATE job_applications 
            SET road_test_date = ?, road_test_location = ?
            WHERE id = ?
        ", [$scheduled_date, $location, $application_id]);
        
        // Change status to road_test
        changeApplicationStatus($application_id, 'road_test', $created_by, 'Road test scheduled');
        
        // Create notification
        $app = fetchSingle("
            SELECT ja.email, ua.id as user_id
            FROM job_applications ja
            LEFT JOIN user_accounts ua ON ua.personal_email = ja.email
            WHERE ja.id = ?
        ", [$application_id]);
        
        if ($app && $app['user_id']) {
            createNotification(
                $application_id,
                $app['user_id'],
                'road_test_scheduled',
                'Road Test Scheduled',
                "Your road test has been scheduled for " . date('F d, Y at h:i A', strtotime($scheduled_date)) . " at {$location}. Please bring your valid driver's license."
            );
        }
        
        $pdo->commit();
        return $road_test_id;
        
    } catch (Exception $e) {
        if (isset($pdo)) $pdo->rollBack();
        error_log("Error scheduling road test: " . $e->getMessage());
        return false;
    }
}

/**
 * Send job offer
 */
function sendJobOffer($application_id, $position_title, $department_id, $salary, $employment_type, $start_date, $benefits, $created_by) {
    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();
        
        // Create job offer
        $offer_id = insertRecord("
            INSERT INTO job_offers 
            (application_id, position_title, department_id, salary_offered, employment_type, start_date, benefits, created_by, expiry_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))
        ", [$application_id, $position_title, $department_id, $salary, $employment_type, $start_date, $benefits, $created_by]);
        
        // Update application
        executeQuery("
            UPDATE job_applications 
            SET offer_sent_date = NOW(), offer_salary = ?, offer_start_date = ?
            WHERE id = ?
        ", [$salary, $start_date, $application_id]);
        
        // Change status to offer_sent
        changeApplicationStatus($application_id, 'offer_sent', $created_by, 'Job offer sent');
        
        // Create notification
        $app = fetchSingle("
            SELECT ja.email, ua.id as user_id, ja.first_name
            FROM job_applications ja
            LEFT JOIN user_accounts ua ON ua.personal_email = ja.email
            WHERE ja.id = ?
        ", [$application_id]);
        
        if ($app && $app['user_id']) {
            createNotification(
                $application_id,
                $app['user_id'],
                'offer_sent',
                'Job Offer Received!',
                "Congratulations! You have received a job offer for {$position_title}. Please review and respond within 7 days."
            );
        }
        
        $pdo->commit();
        return $offer_id;
        
    } catch (Exception $e) {
        if (isset($pdo)) $pdo->rollBack();
        error_log("Error sending job offer: " . $e->getMessage());
        return false;
    }
}

/**
 * Hire applicant
 */
function hireApplicant($application_id, $hired_by, $employee_id = null) {
    try {
        $pdo = getDBConnection();
        $pdo->beginTransaction();
        
        // Get applicant info
        $app = fetchSingle("
            SELECT ja.*, ua.id as user_id, jp.department_id
            FROM job_applications ja
            LEFT JOIN user_accounts ua ON ua.personal_email = ja.email
            LEFT JOIN job_postings jp ON jp.id = ja.job_posting_id
            WHERE ja.id = ?
        ", [$application_id]);
        
        if (!$app) {
            throw new Exception("Application not found");
        }
        
        // Generate employee ID if not provided
        if (!$employee_id) {
            $employee_id = generateEmployeeId();
        }
        
        // Update application
        executeQuery("
            UPDATE job_applications 
            SET hired_date = CURDATE(), hired_by = ?, employee_id_assigned = ?
            WHERE id = ?
        ", [$hired_by, $employee_id, $application_id]);
        
        // Update user account to Employee role
        if ($app['user_id']) {
            $employee_role = fetchSingle("SELECT id FROM roles WHERE role_type = 'Employee' LIMIT 1");
            if ($employee_role) {
                executeQuery("
                    UPDATE user_accounts 
                    SET role_id = ?, employee_id = ?, department_id = ?, status = 'Active'
                    WHERE id = ?
                ", [$employee_role['id'], $employee_id, $app['department_id'], $app['user_id']]);
            }
        }
        
        // Change status to hired
        changeApplicationStatus($application_id, 'hired', $hired_by, 'Applicant hired');
        
        // Create notification
        if ($app['user_id']) {
            createNotification(
                $application_id,
                $app['user_id'],
                'hired',
                'Congratulations! You\'re Hired!',
                "Welcome to the team! Your employee ID is {$employee_id}. Please complete your onboarding requirements."
            );
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        if (isset($pdo)) $pdo->rollBack();
        error_log("Error hiring applicant: " . $e->getMessage());
        return false;
    }
}

/**
 * Reject applicant
 */
function rejectApplicant($application_id, $rejected_by, $reason) {
    try {
        executeQuery("
            UPDATE job_applications 
            SET rejected_date = NOW(), rejected_by = ?, rejected_reason = ?
            WHERE id = ?
        ", [$rejected_by, $application_id, $reason]);
        
        changeApplicationStatus($application_id, 'rejected', $rejected_by, $reason);
        
        // Create notification
        $app = fetchSingle("
            SELECT ja.email, ua.id as user_id
            FROM job_applications ja
            LEFT JOIN user_accounts ua ON ua.personal_email = ja.email
            WHERE ja.id = ?
        ", [$application_id]);
        
        if ($app && $app['user_id']) {
            createNotification(
                $application_id,
                $app['user_id'],
                'rejected',
                'Application Update',
                "Thank you for your interest. Unfortunately, we have decided to move forward with other candidates at this time."
            );
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error rejecting applicant: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate unique employee ID
 */
function generateEmployeeId() {
    $year = date('Y');
    $last_employee = fetchSingle("
        SELECT employee_id FROM user_accounts 
        WHERE employee_id LIKE ? 
        ORDER BY employee_id DESC LIMIT 1
    ", ["EMP{$year}%"]);
    
    if ($last_employee && $last_employee['employee_id']) {
        $last_num = (int)substr($last_employee['employee_id'], -4);
        $new_num = str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $new_num = '0001';
    }
    
    return "EMP{$year}{$new_num}";
}

/**
 * Get notification title for status change
 */
function getStatusChangeNotificationTitle($status) {
    $titles = [
        'new' => 'Application Received',
        'screening' => 'Application Under Review',
        'interview' => 'Interview Stage',
        'road_test' => 'Road Test Stage',
        'offer_sent' => 'Job Offer Sent',
        'hired' => 'Congratulations!',
        'rejected' => 'Application Update'
    ];
    return $titles[$status] ?? 'Status Update';
}

/**
 * Get notification message for status change
 */
function getStatusChangeNotificationMessage($status) {
    $messages = [
        'new' => 'Your application has been received and is being reviewed.',
        'screening' => 'Your application is currently under review by our team.',
        'interview' => 'Your application has progressed to the interview stage.',
        'road_test' => 'You have been selected for a road test.',
        'offer_sent' => 'Congratulations! You have received a job offer.',
        'hired' => 'Welcome to the team! Please complete your onboarding.',
        'rejected' => 'Thank you for your interest. We have decided to move forward with other candidates.'
    ];
    return $messages[$status] ?? 'Your application status has been updated.';
}

/**
 * Get unread notification count for user
 */
function getUnreadNotificationCount($user_id) {
    $result = fetchSingle("
        SELECT COUNT(*) as count 
        FROM applicant_notifications 
        WHERE user_id = ? AND is_read = 0
    ", [$user_id]);
    return (int)($result['count'] ?? 0);
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($notification_id) {
    try {
        executeQuery("
            UPDATE applicant_notifications 
            SET is_read = 1, read_at = NOW()
            WHERE id = ?
        ", [$notification_id]);
        return true;
    } catch (Exception $e) {
        error_log("Error marking notification as read: " . $e->getMessage());
        return false;
    }
}
?>
