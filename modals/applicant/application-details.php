<?php
require_once '../../includes/session_helper.php';
startSecureSession();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Applicant') {
    header('Location: ../../index.php');
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

// Get interview info from job_applications table directly
$interview = null;
if (!empty($application['interview_date'])) {
    $interview = [
        'scheduled_date' => $application['interview_date'],
        'interview_type' => $application['interview_type'] ?? 'face_to_face',
        'location' => $application['interview_location'] ?? '',
        'notes' => $application['interview_notes'] ?? ''
    ];
}

// Get road test info from job_applications table directly
$road_test = null;
if (!empty($application['road_test_date'])) {
    $road_test = [
        'scheduled_date' => $application['road_test_date'],
        'location' => $application['road_test_location'] ?? '',
        'notes' => $application['road_test_notes'] ?? '',
        'result' => $application['road_test_result'] ?? ''
    ];
}

// Get job offer info from job_applications table directly
$job_offer = null;
if (!empty($application['offer_salary'])) {
    $job_offer = [
        'salary_offered' => $application['offer_salary'],
        'start_date' => $application['offer_start_date'] ?? '',
        'benefits' => $application['offer_benefits'] ?? '',
        'status' => $application['offer_status'] ?? 'pending'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Details - HR1</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #0a1929 0%, #1a2942 100%); min-height: 100vh; color: #f8fafc; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: rgba(15, 23, 42, 0.95); border-right: 1px solid rgba(58, 69, 84, 0.5); padding: 1.5rem 0; position: fixed; height: 100vh; overflow-y: auto; }
        .logo-section { padding: 0 1.5rem 1.5rem; border-bottom: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1.5rem; }
        .logo-section img { width: 60px; margin-bottom: 0.5rem; }
        .logo-section h2 { font-size: 1.1rem; color: #0ea5e9; }
        .logo-section p { font-size: 0.75rem; color: #94a3b8; }
        .nav-menu { list-style: none; }
        .nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; color: #cbd5e1; text-decoration: none; transition: all 0.3s; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(14, 165, 233, 0.1); color: #0ea5e9; border-left: 3px solid #0ea5e9; }
        .main-content { flex: 1; margin-left: 260px; padding: 2rem; }
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
        .info-item .material-symbols-outlined { color: #0ea5e9; }
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
</head>
<body>
    <?php $logo_path = '../../assets/images/slate.png'; include '../../includes/loading-screen.php'; ?>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-section">
                <img src="../../assets/images/slate.png" alt="SLATE Logo">
                <h2>Applicant Portal</h2>
                <p><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></p>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><span class="material-symbols-outlined">dashboard</span>Dashboard</a></li>
                <li class="nav-item"><a href="applications.php" class="nav-link active"><span class="material-symbols-outlined">work</span>My Applications</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link"><span class="material-symbols-outlined">person</span>My Profile</a></li>
                <li class="nav-item"><a href="notifications.php" class="nav-link"><span class="material-symbols-outlined">notifications</span>Notifications</a></li>
                <li class="nav-item"><a href="interview-schedule.php" class="nav-link"><span class="material-symbols-outlined">event</span>Interview Schedule</a></li>
                <li class="nav-item"><a href="road-test-info.php" class="nav-link"><span class="material-symbols-outlined">directions_car</span>Road Test Info</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link"><span class="material-symbols-outlined">logout</span>Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Application Details</h1>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                    <a href="applications.php" class="btn btn-secondary"><span class="material-symbols-outlined">arrow_back</span>Back to Applications</a>
                </div>
            </div>

            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                    <div>
                        <h2 style="margin-bottom: 0.25rem;"><span class="material-symbols-outlined">work</span><?php echo htmlspecialchars($application['job_title']); ?></h2>
                        <p style="color: #94a3b8; font-size: 0.9rem;"><?php echo htmlspecialchars($application['department_name']); ?></p>
                    </div>
                    <span class="status-badge <?php echo $application['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $application['status'])); ?></span>
                </div>
                <div class="info-grid">
                    <div class="info-item"><span class="material-symbols-outlined">calendar_today</span><div><div class="info-label">Applied Date</div><div class="info-value"><?php echo date('F d, Y', strtotime($application['applied_date'])); ?></div></div></div>
                    <div class="info-item"><span class="material-symbols-outlined">location_on</span><div><div class="info-label">Location</div><div class="info-value"><?php echo htmlspecialchars($application['location']); ?></div></div></div>
                    <div class="info-item"><span class="material-symbols-outlined">schedule</span><div><div class="info-label">Employment Type</div><div class="info-value"><?php echo htmlspecialchars($application['employment_type']); ?></div></div></div>
                    <div class="info-item"><span class="material-symbols-outlined">payments</span><div><div class="info-label">Salary Range</div><div class="info-value"><?php echo htmlspecialchars($application['salary_range']); ?></div></div></div>
                </div>

                <?php if ($interview): ?>
                <div class="schedule-card">
                    <h4><span class="material-symbols-outlined" style="font-size: 1rem; vertical-align: middle;">event</span> Interview Scheduled</h4>
                    <div class="schedule-details">
                        <span><?php echo date('F d, Y', strtotime($interview['scheduled_date'])); ?></span>
                        <span><?php echo date('h:i A', strtotime($interview['scheduled_date'])); ?></span>
                        <span><?php echo htmlspecialchars($interview['location'] ?: 'Location TBD'); ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($road_test): ?>
                <div class="schedule-card" style="background: rgba(236, 72, 153, 0.1); border-color: rgba(236, 72, 153, 0.3);">
                    <h4 style="color: #ec4899;"><span class="material-symbols-outlined" style="font-size: 1rem; vertical-align: middle;">directions_car</span> Road Test Scheduled</h4>
                    <div class="schedule-details">
                        <span><?php echo date('F d, Y', strtotime($road_test['scheduled_date'])); ?></span>
                        <span><?php echo date('h:i A', strtotime($road_test['scheduled_date'])); ?></span>
                        <span><?php echo htmlspecialchars($road_test['location'] ?: 'Location TBD'); ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($job_offer): ?>
                <div class="offer-card">
                    <h4><span class="material-symbols-outlined" style="font-size: 1rem; vertical-align: middle;">mail</span> Job Offer Received</h4>
                    <div class="schedule-details">
                        <span>Salary: ₱<?php echo number_format($job_offer['salary_offered'], 2); ?></span>
                        <span>Start Date: <?php echo date('F d, Y', strtotime($job_offer['start_date'])); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($status_history)): ?>
            <div class="card">
                <h2><span class="material-symbols-outlined">history</span>Application Timeline</h2>
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
                <h2><span class="material-symbols-outlined">description</span>Job Description</h2>
                <p style="color: #cbd5e1; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($application['job_description'])); ?></p>
            </div>
            <?php endif; ?>
        </main>
    </div>
    <?php include '../../includes/logout-modal.php'; ?>
</body>
</html>
