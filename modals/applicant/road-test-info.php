<?php
require_once '../../includes/session_helper.php';
startSecureSession();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Applicant') {
    header('Location: ../../index.php');
    exit();
}

require_once '../../database/config.php';

$user_email = $_SESSION['personal_email'] ?? '';

try {
    $road_tests = fetchAll("
        SELECT ja.id as application_id, ja.road_test_date as scheduled_date, ja.road_test_location as location, ja.road_test_notes as notes, ja.road_test_result as result,
               COALESCE(jp.title, 'General Application') as job_title,
               COALESCE(d.department_name, 'N/A') as department_name
        FROM job_applications ja
        LEFT JOIN job_postings jp ON ja.job_posting_id = jp.id
        LEFT JOIN departments d ON jp.department_id = d.id
        WHERE ja.email = ? AND ja.road_test_date IS NOT NULL
        ORDER BY ja.road_test_date DESC
    ", [$user_email]);
} catch (Exception $e) {
    $road_tests = [];
}

$upcoming = array_filter($road_tests, fn($r) => !empty($r['scheduled_date']) && strtotime($r['scheduled_date']) >= strtotime('today'));
$past = array_filter($road_tests, fn($r) => !empty($r['scheduled_date']) && strtotime($r['scheduled_date']) < strtotime('today'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Road Test Info - HR1</title>
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
        .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
        .header h1 { font-size: 1.5rem; color: #e2e8f0; }
        .section-title { font-size: 1.2rem; color: #e2e8f0; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .road-tests-grid { display: grid; gap: 1rem; margin-bottom: 2rem; }
        .road-test-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); }
        .road-test-card.upcoming { border-left: 4px solid #ec4899; }
        .road-test-card.past { opacity: 0.7; }
        .card-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
        .card-title { font-size: 1.1rem; color: #e2e8f0; font-weight: 600; margin-bottom: 0.35rem; }
        .card-subtitle { color: #94a3b8; font-size: 0.85rem; }
        .status-badge { padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-badge.scheduled { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .status-badge.completed { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .status-badge.passed { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .status-badge.failed { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; padding-top: 1rem; border-top: 1px solid rgba(58, 69, 84, 0.3); }
        .detail-item { display: flex; align-items: center; gap: 0.5rem; }
        .detail-item .material-symbols-outlined { font-size: 1.25rem; color: #ec4899; }
        .detail-label { color: #94a3b8; font-size: 0.8rem; }
        .detail-value { color: #e2e8f0; font-size: 0.9rem; }
        .info-card { background: rgba(236, 72, 153, 0.1); border: 1px solid rgba(236, 72, 153, 0.3); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
        .info-card h3 { color: #ec4899; font-size: 1rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem; }
        .info-card ul { list-style: none; color: #cbd5e1; font-size: 0.9rem; }
        .info-card li { padding: 0.35rem 0; display: flex; align-items: center; gap: 0.5rem; }
        .info-card li::before { content: "✓"; color: #ec4899; font-weight: bold; }
        .empty-state { text-align: center; padding: 3rem 2rem; color: #94a3b8; background: rgba(30, 41, 54, 0.4); border-radius: 12px; margin-bottom: 2rem; }
        .empty-state .material-symbols-outlined { font-size: 4rem; color: #475569; margin-bottom: 0.75rem; }
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
                <li class="nav-item"><a href="applications.php" class="nav-link"><span class="material-symbols-outlined">work</span>My Applications</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link"><span class="material-symbols-outlined">person</span>My Profile</a></li>
                <li class="nav-item"><a href="notifications.php" class="nav-link"><span class="material-symbols-outlined">notifications</span>Notifications</a></li>
                <li class="nav-item"><a href="interview-schedule.php" class="nav-link"><span class="material-symbols-outlined">event</span>Interview Schedule</a></li>
                <li class="nav-item"><a href="road-test-info.php" class="nav-link active"><span class="material-symbols-outlined">directions_car</span>Road Test Info</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link"><span class="material-symbols-outlined">logout</span>Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Road Test Information</h1>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                </div>
            </div>

            <div class="info-card">
                <h3><span class="material-symbols-outlined">info</span>Road Test Requirements</h3>
                <ul>
                    <li>Bring a valid driver's license</li>
                    <li>Arrive at least 15 minutes before your scheduled time</li>
                    <li>Wear appropriate closed-toe shoes</li>
                    <li>Ensure you have had adequate rest before the test</li>
                    <li>Bring any required documentation as specified</li>
                </ul>
            </div>

            <h2 class="section-title"><span class="material-symbols-outlined">upcoming</span>Upcoming Road Tests</h2>
            <?php if (empty($upcoming)): ?>
            <div class="empty-state">
                <span class="material-symbols-outlined">directions_car</span>
                <h3>No Scheduled Road Tests</h3>
                <p>You don't have any road tests scheduled at the moment.</p>
            </div>
            <?php else: ?>
            <div class="road-tests-grid">
                <?php foreach ($upcoming as $test): ?>
                <div class="road-test-card upcoming">
                    <div class="card-header">
                        <div>
                            <div class="card-title"><?php echo htmlspecialchars($test['job_title']); ?></div>
                            <div class="card-subtitle"><?php echo htmlspecialchars($test['department_name']); ?></div>
                        </div>
                        <span class="status-badge scheduled">Scheduled</span>
                    </div>
                    <div class="details-grid">
                        <div class="detail-item">
                            <span class="material-symbols-outlined">calendar_today</span>
                            <div><div class="detail-label">Date</div><div class="detail-value"><?php echo date('F d, Y', strtotime($test['scheduled_date'])); ?></div></div>
                        </div>
                        <div class="detail-item">
                            <span class="material-symbols-outlined">schedule</span>
                            <div><div class="detail-label">Time</div><div class="detail-value"><?php echo date('h:i A', strtotime($test['scheduled_date'])); ?></div></div>
                        </div>
                        <div class="detail-item">
                            <span class="material-symbols-outlined">location_on</span>
                            <div><div class="detail-label">Location</div><div class="detail-value"><?php echo htmlspecialchars($test['location'] ?: 'To be confirmed'); ?></div></div>
                        </div>
                        <div class="detail-item">
                            <span class="material-symbols-outlined">local_shipping</span>
                            <div><div class="detail-label">Vehicle Type</div><div class="detail-value"><?php echo htmlspecialchars($test['vehicle_type'] ?: 'Standard'); ?></div></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <h2 class="section-title"><span class="material-symbols-outlined">history</span>Past Road Tests</h2>
            <?php if (empty($past)): ?>
            <div class="empty-state">
                <span class="material-symbols-outlined">event_available</span>
                <h3>No Past Road Tests</h3>
                <p>Your road test history will appear here.</p>
            </div>
            <?php else: ?>
            <div class="road-tests-grid">
                <?php foreach ($past as $test): ?>
                <div class="road-test-card past">
                    <div class="card-header">
                        <div>
                            <div class="card-title"><?php echo htmlspecialchars($test['job_title']); ?></div>
                            <div class="card-subtitle"><?php echo date('F d, Y', strtotime($test['scheduled_date'])); ?></div>
                        </div>
                        <span class="status-badge <?php echo strtolower($test['result'] ?? 'completed'); ?>"><?php echo ucfirst($test['result'] ?? 'Completed'); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
    <?php include '../../includes/logout-modal.php'; ?>
</body>
</html>
