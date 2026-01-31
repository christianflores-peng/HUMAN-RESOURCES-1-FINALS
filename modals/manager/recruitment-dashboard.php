<?php
require_once '../../includes/session_helper.php';
startSecureSession();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_type'], ['Manager', 'HR_Staff', 'Admin'])) {
    header('Location: ../../index.php');
    exit();
}

require_once '../../database/config.php';
require_once '../../includes/workflow_helper.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

$applications_by_status = [
    'new' => fetchAll("SELECT ja.*, COALESCE(jp.title, 'General Application') as job_title, COALESCE(jp.location, 'N/A') as location, COALESCE(d.department_name, 'Not Assigned') as department_name FROM job_applications ja LEFT JOIN job_postings jp ON jp.id = ja.job_posting_id LEFT JOIN departments d ON d.id = jp.department_id WHERE ja.status = 'new' ORDER BY ja.applied_date DESC"),
    'screening' => fetchAll("SELECT ja.*, COALESCE(jp.title, 'General Application') as job_title, COALESCE(jp.location, 'N/A') as location, COALESCE(d.department_name, 'Not Assigned') as department_name FROM job_applications ja LEFT JOIN job_postings jp ON jp.id = ja.job_posting_id LEFT JOIN departments d ON d.id = jp.department_id WHERE ja.status = 'screening' ORDER BY ja.applied_date DESC"),
    'interview' => fetchAll("SELECT ja.*, COALESCE(jp.title, 'General Application') as job_title, COALESCE(jp.location, 'N/A') as location, COALESCE(d.department_name, 'Not Assigned') as department_name, isch.scheduled_date as interview_date FROM job_applications ja LEFT JOIN job_postings jp ON jp.id = ja.job_posting_id LEFT JOIN departments d ON d.id = jp.department_id LEFT JOIN interview_schedules isch ON isch.application_id = ja.id AND isch.status = 'scheduled' WHERE ja.status = 'interview' ORDER BY ja.applied_date DESC"),
    'road_test' => fetchAll("SELECT ja.*, COALESCE(jp.title, 'General Application') as job_title, COALESCE(jp.location, 'N/A') as location, COALESCE(d.department_name, 'Not Assigned') as department_name, rts.scheduled_date as road_test_date FROM job_applications ja LEFT JOIN job_postings jp ON jp.id = ja.job_posting_id LEFT JOIN departments d ON d.id = jp.department_id LEFT JOIN road_test_schedules rts ON rts.application_id = ja.id WHERE ja.status = 'road_test' ORDER BY ja.applied_date DESC"),
    'offer_sent' => fetchAll("SELECT ja.*, COALESCE(jp.title, 'General Application') as job_title, COALESCE(jp.location, 'N/A') as location, COALESCE(d.department_name, 'Not Assigned') as department_name, jo.salary_offered, jo.status as offer_status FROM job_applications ja LEFT JOIN job_postings jp ON jp.id = ja.job_posting_id LEFT JOIN departments d ON d.id = jp.department_id LEFT JOIN job_offers jo ON jo.application_id = ja.id WHERE ja.status = 'offer_sent' ORDER BY ja.applied_date DESC"),
    'hired' => fetchAll("SELECT ja.*, COALESCE(jp.title, 'General Application') as job_title, COALESCE(jp.location, 'N/A') as location, COALESCE(d.department_name, 'Not Assigned') as department_name, ja.employee_id_assigned, ja.hired_date FROM job_applications ja LEFT JOIN job_postings jp ON jp.id = ja.job_posting_id LEFT JOIN departments d ON d.id = jp.department_id WHERE ja.status = 'hired' ORDER BY ja.hired_date DESC LIMIT 10")
];

$stats = ['total' => 0, 'new' => count($applications_by_status['new']), 'screening' => count($applications_by_status['screening']), 'interview' => count($applications_by_status['interview']), 'road_test' => count($applications_by_status['road_test']), 'offer_sent' => count($applications_by_status['offer_sent']), 'hired' => count($applications_by_status['hired'])];
$stats['total'] = array_sum($stats);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recruitment Dashboard - HR1</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #0a1929 0%, #1a2942 100%); min-height: 100vh; color: #f8fafc; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: rgba(15, 23, 42, 0.95); border-right: 1px solid rgba(58, 69, 84, 0.5); padding: 1.5rem 0; position: fixed; height: 100vh; overflow-y: auto; }
        .logo-section { padding: 0 1.5rem 1.5rem; border-bottom: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1.5rem; }
        .logo-section img { width: 60px; margin-bottom: 0.5rem; }
        .logo-section h2 { font-size: 1.1rem; color: #0ea5e9; margin-bottom: 0.25rem; }
        .logo-section p { font-size: 0.75rem; color: #94a3b8; }
        .nav-menu { list-style: none; }
        .nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; color: #cbd5e1; text-decoration: none; transition: all 0.3s; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(14, 165, 233, 0.1); color: #0ea5e9; border-left: 3px solid #0ea5e9; }
        .main-content { flex: 1; margin-left: 260px; padding: 2rem; }
        .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
        .header h1 { font-size: 1.5rem; color: #e2e8f0; }
        .header p { color: #94a3b8; font-size: 0.9rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.25rem; border: 1px solid rgba(58, 69, 84, 0.5); cursor: pointer; transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-2px); border-color: #0ea5e9; }
        .stat-card .icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem; font-size: 1.5rem; }
        .stat-card.total .icon { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
        .stat-card.new .icon { background: rgba(99, 102, 241, 0.2); color: #6366f1; }
        .stat-card.screening .icon { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .stat-card.interview .icon { background: rgba(139, 92, 246, 0.2); color: #8b5cf6; }
        .stat-card.road_test .icon { background: rgba(236, 72, 153, 0.2); color: #ec4899; }
        .stat-card.offer .icon { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .stat-card.hired .icon { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .stat-card h3 { font-size: 1.8rem; color: #e2e8f0; margin-bottom: 0.25rem; }
        .stat-card p { font-size: 0.85rem; color: #94a3b8; }
        .pipeline-board { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; }
        .pipeline-column { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1rem; border: 1px solid rgba(58, 69, 84, 0.5); min-height: 400px; }
        .column-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid rgba(58, 69, 84, 0.5); }
        .column-title { font-size: 0.95rem; font-weight: 600; color: #e2e8f0; display: flex; align-items: center; gap: 0.5rem; }
        .column-count { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .applicant-card { background: rgba(15, 23, 42, 0.8); border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem; border: 1px solid rgba(58, 69, 84, 0.5); cursor: pointer; transition: all 0.3s; }
        .applicant-card:hover { border-color: #0ea5e9; transform: translateX(4px); }
        .applicant-name { font-size: 0.95rem; font-weight: 600; color: #e2e8f0; margin-bottom: 0.25rem; }
        .applicant-job { font-size: 0.8rem; color: #94a3b8; margin-bottom: 0.5rem; }
        .applicant-meta { display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: #64748b; }
        .empty-state { text-align: center; padding: 2rem; color: #64748b; }
        .empty-state .material-symbols-outlined { font-size: 3rem; margin-bottom: 0.5rem; opacity: 0.3; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-section">
                <img src="../../assets/images/slate.png" alt="SLATE Logo">
                <h2>HR Recruitment</h2>
                <p><?php echo htmlspecialchars($user_name); ?></p>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="recruitment-dashboard.php" class="nav-link active"><span class="material-symbols-outlined">dashboard</span>Recruitment Pipeline</a></li>
                <li class="nav-item"><a href="job-postings.php" class="nav-link"><span class="material-symbols-outlined">work</span>Job Postings</a></li>
                <li class="nav-item"><a href="../../index.php" class="nav-link"><span class="material-symbols-outlined">home</span>Main Dashboard</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link"><span class="material-symbols-outlined">logout</span>Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Recruitment Pipeline</h1>
                <p>Manage applicants and hiring process</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card total"><div class="icon"><span class="material-symbols-outlined">groups</span></div><h3><?php echo $stats['total']; ?></h3><p>Total Applicants</p></div>
                <div class="stat-card new"><div class="icon"><span class="material-symbols-outlined">person_add</span></div><h3><?php echo $stats['new']; ?></h3><p>New Apply</p></div>
                <div class="stat-card screening"><div class="icon"><span class="material-symbols-outlined">fact_check</span></div><h3><?php echo $stats['screening']; ?></h3><p>Screening</p></div>
                <div class="stat-card interview"><div class="icon"><span class="material-symbols-outlined">event</span></div><h3><?php echo $stats['interview']; ?></h3><p>For Interview</p></div>
                <div class="stat-card road_test"><div class="icon"><span class="material-symbols-outlined">directions_car</span></div><h3><?php echo $stats['road_test']; ?></h3><p>Road Test</p></div>
                <div class="stat-card offer"><div class="icon"><span class="material-symbols-outlined">mail</span></div><h3><?php echo $stats['offer_sent']; ?></h3><p>Offer Sent</p></div>
                <div class="stat-card hired"><div class="icon"><span class="material-symbols-outlined">check_circle</span></div><h3><?php echo $stats['hired']; ?></h3><p>HIRED</p></div>
            </div>

            <div class="pipeline-board">
                <?php 
                $columns = [
                    'new' => ['title' => 'New Apply', 'icon' => 'person_add'],
                    'screening' => ['title' => 'Screening', 'icon' => 'fact_check'],
                    'interview' => ['title' => 'For Interview', 'icon' => 'event'],
                    'road_test' => ['title' => 'Road Test', 'icon' => 'directions_car'],
                    'offer_sent' => ['title' => 'Offer Sent', 'icon' => 'mail'],
                    'hired' => ['title' => 'HIRED', 'icon' => 'check_circle']
                ];
                foreach ($columns as $status => $col): ?>
                <div class="pipeline-column">
                    <div class="column-header">
                        <span class="column-title"><span class="material-symbols-outlined"><?php echo $col['icon']; ?></span><?php echo $col['title']; ?></span>
                        <span class="column-count"><?php echo count($applications_by_status[$status]); ?></span>
                    </div>
                    <?php if (empty($applications_by_status[$status])): ?>
                    <div class="empty-state"><span class="material-symbols-outlined">inbox</span><p>No applicants</p></div>
                    <?php else: ?>
                    <?php foreach ($applications_by_status[$status] as $app): ?>
                    <div class="applicant-card" onclick="window.location.href='applicant-details.php?id=<?php echo $app['id']; ?>'">
                        <div class="applicant-name"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></div>
                        <div class="applicant-job"><?php echo htmlspecialchars($app['job_title']); ?></div>
                        <div class="applicant-meta">
                            <span><span class="material-symbols-outlined" style="font-size: 0.9rem;">schedule</span> <?php echo date('M d', strtotime($app['applied_date'])); ?></span>
                            <span><?php echo htmlspecialchars($app['department_name'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
    <?php include '../../includes/logout-modal.php'; ?>
</body>
</html>
