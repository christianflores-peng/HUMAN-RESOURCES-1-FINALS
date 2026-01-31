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
    $interviews = fetchAll("
        SELECT ja.id as application_id, ja.interview_date as scheduled_date, ja.interview_type, ja.interview_location as location, ja.interview_notes as notes,
               COALESCE(jp.title, 'General Application') as job_title,
               COALESCE(d.department_name, 'N/A') as department_name
        FROM job_applications ja
        LEFT JOIN job_postings jp ON ja.job_posting_id = jp.id
        LEFT JOIN departments d ON jp.department_id = d.id
        WHERE ja.email = ? AND ja.interview_date IS NOT NULL
        ORDER BY ja.interview_date DESC
    ", [$user_email]);
} catch (Exception $e) {
    $interviews = [];
}

$upcoming = array_filter($interviews, fn($i) => !empty($i['scheduled_date']) && strtotime($i['scheduled_date']) >= strtotime('today'));
$past = array_filter($interviews, fn($i) => !empty($i['scheduled_date']) && strtotime($i['scheduled_date']) < strtotime('today'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Schedule - HR1</title>
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
        .interviews-grid { display: grid; gap: 1rem; margin-bottom: 2rem; }
        .interview-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); display: grid; grid-template-columns: auto 1fr auto; gap: 1.5rem; align-items: center; }
        .interview-card.upcoming { border-left: 4px solid #8b5cf6; }
        .interview-card.past { opacity: 0.7; }
        .date-box { background: rgba(139, 92, 246, 0.2); border-radius: 10px; padding: 1rem; text-align: center; min-width: 80px; }
        .date-box .month { font-size: 0.75rem; color: #8b5cf6; text-transform: uppercase; font-weight: 600; }
        .date-box .day { font-size: 1.75rem; font-weight: 700; color: #e2e8f0; }
        .date-box .year { font-size: 0.75rem; color: #94a3b8; }
        .interview-details h3 { font-size: 1.1rem; color: #e2e8f0; margin-bottom: 0.5rem; }
        .interview-meta { display: flex; flex-wrap: wrap; gap: 1rem; color: #94a3b8; font-size: 0.85rem; }
        .meta-item { display: flex; align-items: center; gap: 0.35rem; }
        .interview-type { padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .interview-type.in_person { background: rgba(99, 102, 241, 0.2); color: #6366f1; }
        .interview-type.video { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .interview-type.phone { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
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
                <li class="nav-item"><a href="interview-schedule.php" class="nav-link active"><span class="material-symbols-outlined">event</span>Interview Schedule</a></li>
                <li class="nav-item"><a href="road-test-info.php" class="nav-link"><span class="material-symbols-outlined">directions_car</span>Road Test Info</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link"><span class="material-symbols-outlined">logout</span>Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Interview Schedule</h1>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                </div>
            </div>

            <h2 class="section-title"><span class="material-symbols-outlined">upcoming</span>Upcoming Interviews</h2>
            <?php if (empty($upcoming)): ?>
            <div class="empty-state">
                <span class="material-symbols-outlined">event_busy</span>
                <h3>No Upcoming Interviews</h3>
                <p>You don't have any scheduled interviews at the moment.</p>
            </div>
            <?php else: ?>
            <div class="interviews-grid">
                <?php foreach ($upcoming as $interview): ?>
                <div class="interview-card upcoming">
                    <div class="date-box">
                        <div class="month"><?php echo date('M', strtotime($interview['scheduled_date'])); ?></div>
                        <div class="day"><?php echo date('d', strtotime($interview['scheduled_date'])); ?></div>
                        <div class="year"><?php echo date('Y', strtotime($interview['scheduled_date'])); ?></div>
                    </div>
                    <div class="interview-details">
                        <h3><?php echo htmlspecialchars($interview['job_title']); ?></h3>
                        <div class="interview-meta">
                            <div class="meta-item"><span class="material-symbols-outlined" style="font-size: 1rem;">schedule</span><?php echo date('h:i A', strtotime($interview['scheduled_date'])); ?></div>
                            <div class="meta-item"><span class="material-symbols-outlined" style="font-size: 1rem;">location_on</span><?php echo htmlspecialchars($interview['location'] ?: 'To be confirmed'); ?></div>
                            <div class="meta-item"><span class="material-symbols-outlined" style="font-size: 1rem;">business</span><?php echo htmlspecialchars($interview['department_name']); ?></div>
                        </div>
                    </div>
                    <span class="interview-type <?php echo $interview['interview_type'] ?? 'in_person'; ?>"><?php echo ucfirst(str_replace('_', ' ', $interview['interview_type'] ?? 'In Person')); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <h2 class="section-title"><span class="material-symbols-outlined">history</span>Past Interviews</h2>
            <?php if (empty($past)): ?>
            <div class="empty-state">
                <span class="material-symbols-outlined">event_available</span>
                <h3>No Past Interviews</h3>
                <p>Your interview history will appear here.</p>
            </div>
            <?php else: ?>
            <div class="interviews-grid">
                <?php foreach ($past as $interview): ?>
                <div class="interview-card past">
                    <div class="date-box">
                        <div class="month"><?php echo date('M', strtotime($interview['scheduled_date'])); ?></div>
                        <div class="day"><?php echo date('d', strtotime($interview['scheduled_date'])); ?></div>
                        <div class="year"><?php echo date('Y', strtotime($interview['scheduled_date'])); ?></div>
                    </div>
                    <div class="interview-details">
                        <h3><?php echo htmlspecialchars($interview['job_title']); ?></h3>
                        <div class="interview-meta">
                            <div class="meta-item"><span class="material-symbols-outlined" style="font-size: 1rem;">schedule</span><?php echo date('h:i A', strtotime($interview['scheduled_date'])); ?></div>
                            <div class="meta-item"><span class="material-symbols-outlined" style="font-size: 1rem;">location_on</span><?php echo htmlspecialchars($interview['location'] ?: 'N/A'); ?></div>
                        </div>
                    </div>
                    <span class="interview-type <?php echo $interview['interview_type'] ?? 'in_person'; ?>"><?php echo ucfirst(str_replace('_', ' ', $interview['interview_type'] ?? 'In Person')); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
    <?php include '../../includes/logout-modal.php'; ?>
</body>
</html>
