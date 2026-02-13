<?php
require_once '../../includes/session_helper.php';
require_once '../../includes/spa_helper.php';
startSecureSession();
$is_ajax = is_spa_ajax();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Applicant') {
    header('Location: ../../index.php');
    exit();
}

if (!$is_ajax) {
    $id = isset($_GET['id']) ? '&id=' . urlencode($_GET['id']) : '';
    header('Location: index.php?page=application-details' . $id);
    exit();
}

require_once '../../database/config.php';

$application_id = $_GET['id'] ?? 0;
$user_email = $_SESSION['personal_email'] ?? '';

try {
    $application = fetchSingle("
        SELECT ja.*, 
               COALESCE(jp.title, 'General Application') as job_title,
               COALESCE(jp.description, '') as job_description,
               COALESCE(jp.requirements, '') as job_requirements,
               COALESCE(jp.location, 'N/A') as location,
               COALESCE(jp.employment_type, 'To Be Determined') as employment_type,
               COALESCE(jp.salary_range, 'Competitive') as salary_range,
               COALESCE(d.department_name, 'Not Assigned') as department_name
        FROM job_applications ja
        LEFT JOIN job_postings jp ON jp.id = ja.job_posting_id
        LEFT JOIN departments d ON d.id = jp.department_id
        WHERE ja.id = ? AND ja.email = ?
    ", [$application_id, $user_email]);
} catch (Exception $e) {
    $application = null;
}

if (!$application) {
    header('Location: applications.php');
    exit();
}

// Try to get additional details, fallback to empty if tables don't exist
try {
    $status_history = fetchAll("SELECT * FROM application_status_history WHERE application_id = ? ORDER BY changed_at DESC", [$application_id]);
} catch (Exception $e) {
    $status_history = [];
}

// Get interview info from interview_schedules table (created by HR staff)
$interview = null;
try {
    $interview = fetchSingle("
        SELECT CONCAT(scheduled_date, ' ', scheduled_time) as scheduled_date, 
               interview_type, location, notes
        FROM interview_schedules 
        WHERE application_id = ? 
        ORDER BY scheduled_date DESC, scheduled_time DESC LIMIT 1
    ", [$application_id]);
} catch (Exception $e) {
    // Fallback to job_applications columns
    if (!empty($application['interview_date'])) {
        $interview = [
            'scheduled_date' => $application['interview_date'],
            'interview_type' => $application['interview_type'] ?? 'face_to_face',
            'location' => $application['interview_location'] ?? '',
            'notes' => $application['interview_notes'] ?? ''
        ];
    }
}

// Get road test info from road_test_schedules table, fallback to job_applications
$road_test = null;
try {
    $road_test = fetchSingle("
        SELECT CONCAT(scheduled_date) as scheduled_date, 
               location, result_notes as notes, test_result as result
        FROM road_test_schedules 
        WHERE application_id = ? 
        ORDER BY scheduled_date DESC LIMIT 1
    ", [$application_id]);
} catch (Exception $e) {
    if (!empty($application['road_test_date'])) {
        $road_test = [
            'scheduled_date' => $application['road_test_date'],
            'location' => $application['road_test_location'] ?? '',
            'notes' => $application['road_test_notes'] ?? '',
            'result' => $application['road_test_result'] ?? ''
        ];
    }
}

// Get job offer info from job_offers table, fallback to job_applications
$job_offer = null;
try {
    $job_offer = fetchSingle("
        SELECT salary_offered, start_date, benefits, status
        FROM job_offers 
        WHERE application_id = ? 
        ORDER BY created_at DESC LIMIT 1
    ", [$application_id]);
} catch (Exception $e) {
    if (!empty($application['offer_salary'])) {
        $job_offer = [
            'salary_offered' => $application['offer_salary'],
            'start_date' => $application['offer_start_date'] ?? '',
            'benefits' => $application['offer_benefits'] ?? '',
            'status' => $application['offer_status'] ?? 'pending'
        ];
    }
}
?>
<div data-page-title="Application Details">
<style>
        .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5rem; color: #e2e8f0; }
        .btn { padding: 0.65rem 1.25rem; border: none; border-radius: 6px; font-size: 0.9rem; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-secondary { background: rgba(100, 116, 139, 0.3); color: #cbd5e1; }
        .btn-secondary:hover { background: rgba(100, 116, 139, 0.5); }
        .card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1.5rem; }
        .card h2 { font-size: 1.1rem; color: #e2e8f0; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .status-badge { padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; display: inline-block; }
        .status-badge.new { background: rgba(99, 102, 241, 0.2); color: #6366f1; }
        .status-badge.screening { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .status-badge.interview { background: rgba(139, 92, 246, 0.2); color: #8b5cf6; }
        .status-badge.road_test { background: rgba(236, 72, 153, 0.2); color: #ec4899; }
        .status-badge.offer_sent { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .status-badge.hired { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .status-badge.rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; }
        .info-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: rgba(15, 23, 42, 0.4); border-radius: 8px; }
        .info-item i { color: #0ea5e9; width: 1.2rem; height: 1.2rem; }
        .info-label { color: #94a3b8; font-size: 0.8rem; }
        .info-value { color: #e2e8f0; font-size: 0.95rem; font-weight: 500; }
        .timeline { position: relative; padding-left: 2rem; }
        .timeline::before { content: ''; position: absolute; left: 7px; top: 5px; bottom: 5px; width: 2px; background: rgba(58, 69, 84, 0.5); }
        .timeline-item { position: relative; margin-bottom: 1.25rem; }
        .timeline-item::before { content: ''; position: absolute; left: -2rem; top: 5px; width: 12px; height: 12px; border-radius: 50%; background: #0ea5e9; border: 2px solid #1a2942; }
        .timeline-date { color: #94a3b8; font-size: 0.8rem; margin-bottom: 0.25rem; }
        .timeline-content { color: #e2e8f0; font-size: 0.9rem; }
        .schedule-card { background: rgba(139, 92, 246, 0.1); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 10px; padding: 1rem; margin-top: 1rem; }
        .schedule-card h4 { color: #8b5cf6; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .schedule-details { display: flex; flex-wrap: wrap; gap: 1rem; color: #cbd5e1; font-size: 0.85rem; }
        .offer-card { background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 10px; padding: 1rem; margin-top: 1rem; }
        .offer-card h4 { color: #22c55e; font-size: 0.9rem; margin-bottom: 0.5rem; }
    </style>
            <div class="header">
                <h1>Application Details</h1>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                    <a href="applications.php" class="btn btn-secondary"><i data-lucide="arrow-left"></i>Back to Applications</a>
                </div>
            </div>

            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                    <div>
                        <h2 style="margin-bottom: 0.25rem;"><i data-lucide="briefcase"></i><?php echo htmlspecialchars($application['job_title']); ?></h2>
                        <p style="color: #94a3b8; font-size: 0.9rem;"><?php echo htmlspecialchars($application['department_name']); ?></p>
                    </div>
                    <span class="status-badge <?php echo $application['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $application['status'])); ?></span>
                </div>
                <div class="info-grid">
                    <div class="info-item"><i data-lucide="calendar"></i><div><div class="info-label">Applied Date</div><div class="info-value"><?php echo date('F d, Y', strtotime($application['applied_date'])); ?></div></div></div>
                    <div class="info-item"><i data-lucide="map-pin"></i><div><div class="info-label">Location</div><div class="info-value"><?php echo htmlspecialchars($application['location']); ?></div></div></div>
                    <div class="info-item"><i data-lucide="clock"></i><div><div class="info-label">Employment Type</div><div class="info-value"><?php echo htmlspecialchars($application['employment_type']); ?></div></div></div>
                    <div class="info-item"><i data-lucide="dollar-sign"></i><div><div class="info-label">Salary Range</div><div class="info-value"><?php echo htmlspecialchars($application['salary_range']); ?></div></div></div>
                </div>

                <?php if ($interview): ?>
                <div class="schedule-card">
                    <h4><i data-lucide="calendar" style="width: 1rem; height: 1rem; vertical-align: middle;"></i> Interview Scheduled</h4>
                    <div class="schedule-details">
                        <span><?php echo date('F d, Y', strtotime($interview['scheduled_date'])); ?></span>
                        <span><?php echo date('h:i A', strtotime($interview['scheduled_date'])); ?></span>
                        <span><?php echo htmlspecialchars($interview['location'] ?: 'Location TBD'); ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($road_test): ?>
                <div class="schedule-card" style="background: rgba(236, 72, 153, 0.1); border-color: rgba(236, 72, 153, 0.3);">
                    <h4 style="color: #ec4899;"><i data-lucide="car" style="width: 1rem; height: 1rem; vertical-align: middle;"></i> Road Test Scheduled</h4>
                    <div class="schedule-details">
                        <span><?php echo date('F d, Y', strtotime($road_test['scheduled_date'])); ?></span>
                        <span><?php echo date('h:i A', strtotime($road_test['scheduled_date'])); ?></span>
                        <span><?php echo htmlspecialchars($road_test['location'] ?: 'Location TBD'); ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($job_offer): ?>
                <div class="offer-card">
                    <h4><i data-lucide="mail" style="width: 1rem; height: 1rem; vertical-align: middle;"></i> Job Offer Received</h4>
                    <div class="schedule-details">
                        <span>Salary: ₱<?php echo number_format($job_offer['salary_offered'], 2); ?></span>
                        <span>Start Date: <?php echo date('F d, Y', strtotime($job_offer['start_date'])); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($status_history)): ?>
            <div class="card">
                <h2><i data-lucide="history"></i>Application Timeline</h2>
                <div class="timeline">
                    <?php foreach ($status_history as $history): ?>
                    <div class="timeline-item">
                        <div class="timeline-date"><?php echo date('M d, Y h:i A', strtotime($history['changed_at'])); ?></div>
                        <div class="timeline-content">Status changed from <strong><?php echo ucfirst(str_replace('_', ' ', $history['old_status'])); ?></strong> to <strong><?php echo ucfirst(str_replace('_', ' ', $history['new_status'])); ?></strong></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($application['job_description'])): ?>
            <div class="card">
                <h2><i data-lucide="file-text"></i>Job Description</h2>
                <p style="color: #cbd5e1; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($application['job_description'])); ?></p>
            </div>
            <?php endif; ?>
<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
</div>
