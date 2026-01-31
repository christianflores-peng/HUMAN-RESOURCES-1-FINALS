<?php
require_once '../../includes/session_helper.php';
startSecureSession();

// Check if user is logged in and is an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Applicant') {
    header('Location: ../../partials/login.php');
    exit();
}

require_once '../../database/config.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

// Fetch applicant's profile
try {
    $applicant = fetchSingle("
        SELECT ap.*, ua.personal_email, ua.phone, ua.created_at as registered_date
        FROM applicant_profiles ap
        JOIN user_accounts ua ON ua.id = ap.user_id
        WHERE ap.user_id = ?
    ", [$user_id]);
} catch (Exception $e) {
    // If applicant_profiles doesn't exist, get basic info from user_accounts
    $applicant = fetchSingle("SELECT id, personal_email, phone, created_at as registered_date FROM user_accounts WHERE id = ?", [$user_id]);
}

$user_email = $applicant['personal_email'] ?? $_SESSION['personal_email'] ?? '';

// Fetch applicant's applications with status
try {
    $applications = fetchAll("
        SELECT ja.*, 
               COALESCE(jp.title, 'General Application') as job_title, 
               COALESCE(jp.location, 'N/A') as location, 
               COALESCE(jp.employment_type, 'Full-time') as employment_type,
               jp.department_id, 
               COALESCE(d.department_name, 'Not Assigned') as department_name
        FROM job_applications ja
        LEFT JOIN job_postings jp ON jp.id = ja.job_posting_id
        LEFT JOIN departments d ON d.id = jp.department_id
        WHERE ja.email = ?
        ORDER BY ja.applied_date DESC
    ", [$user_email]);
} catch (Exception $e) {
    $applications = [];
}

// Get application statistics
$stats = [
    'total' => count($applications),
    'new' => 0,
    'screening' => 0,
    'interview' => 0,
    'road_test' => 0,
    'offer_sent' => 0,
    'hired' => 0,
    'rejected' => 0
];

foreach ($applications as $app) {
    $status = strtolower($app['status']);
    if (isset($stats[$status])) {
        $stats[$status]++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Dashboard - HR1</title>
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
        .nav-item { margin-bottom: 0.25rem; }
        .nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; color: #cbd5e1; text-decoration: none; transition: all 0.3s; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(14, 165, 233, 0.1); color: #0ea5e9; border-left: 3px solid #0ea5e9; }
        .nav-link .material-symbols-outlined { font-size: 1.3rem; }
        .main-content { flex: 1; margin-left: 260px; padding: 2rem; }
        .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5rem; color: #e2e8f0; }
        .header p { color: #94a3b8; font-size: 0.9rem; }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .user-avatar { width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, #0ea5e9, #10b981); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1.1rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.25rem; border: 1px solid rgba(58, 69, 84, 0.5); }
        .stat-card .icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem; font-size: 1.5rem; }
        .stat-card.total .icon { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
        .stat-card.screening .icon { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .stat-card.interview .icon { background: rgba(139, 92, 246, 0.2); color: #8b5cf6; }
        .stat-card.hired .icon { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .stat-card h3 { font-size: 1.8rem; color: #e2e8f0; margin-bottom: 0.25rem; }
        .stat-card p { font-size: 0.85rem; color: #94a3b8; }
        .applications-section { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .section-header h2 { font-size: 1.2rem; color: #e2e8f0; }
        .applications-table { width: 100%; border-collapse: collapse; }
        .applications-table th { text-align: left; padding: 0.75rem; border-bottom: 1px solid rgba(58, 69, 84, 0.5); color: #94a3b8; font-size: 0.85rem; font-weight: 500; }
        .applications-table td { padding: 1rem 0.75rem; border-bottom: 1px solid rgba(58, 69, 84, 0.3); color: #cbd5e1; font-size: 0.9rem; }
        .applications-table tr:hover { background: rgba(14, 165, 233, 0.05); }
        .status-badge { display: inline-block; padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-badge.new { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
        .status-badge.screening { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .status-badge.interview { background: rgba(139, 92, 246, 0.2); color: #8b5cf6; }
        .status-badge.road_test { background: rgba(236, 72, 153, 0.2); color: #ec4899; }
        .status-badge.offer_sent { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .status-badge.hired { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .status-badge.rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 6px; font-size: 0.85rem; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-block; }
        .btn-primary { background: #0ea5e9; color: white; }
        .btn-primary:hover { background: #0284c7; }
        .empty-state { text-align: center; padding: 3rem; color: #94a3b8; }
        .empty-state .material-symbols-outlined { font-size: 4rem; margin-bottom: 1rem; opacity: 0.3; }
    </style>
</head>
<body>
    <?php $logo_path = '../../assets/images/slate.png'; include '../../includes/loading-screen.php'; ?>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-section">
                <img src="../../assets/images/slate.png" alt="SLATE Logo">
                <h2>Applicant Portal</h2>
                <p><?php echo htmlspecialchars($user_name); ?></p>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link active"><span class="material-symbols-outlined">dashboard</span>Dashboard</a></li>
                <li class="nav-item"><a href="applications.php" class="nav-link"><span class="material-symbols-outlined">work</span>My Applications</a></li>
                <li class="nav-item"><a href="../../careers.php" class="nav-link"><span class="material-symbols-outlined">search</span>Browse Jobs</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link"><span class="material-symbols-outlined">person</span>My Profile</a></li>
                <li class="nav-item"><a href="notifications.php" class="nav-link"><span class="material-symbols-outlined">notifications</span>Notifications</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link"><span class="material-symbols-outlined">logout</span>Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <div>
                    <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h1>
                    <p>Track your application status and explore new opportunities</p>
                </div>
                <div class="user-info header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?></div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card total"><div class="icon"><span class="material-symbols-outlined">work</span></div><h3><?php echo $stats['total']; ?></h3><p>Total Applications</p></div>
                <div class="stat-card screening"><div class="icon"><span class="material-symbols-outlined">fact_check</span></div><h3><?php echo $stats['screening']; ?></h3><p>In Screening</p></div>
                <div class="stat-card interview"><div class="icon"><span class="material-symbols-outlined">groups</span></div><h3><?php echo $stats['interview']; ?></h3><p>For Interview</p></div>
                <div class="stat-card hired"><div class="icon"><span class="material-symbols-outlined">check_circle</span></div><h3><?php echo $stats['hired']; ?></h3><p>Hired</p></div>
            </div>

            <div class="applications-section">
                <div class="section-header">
                    <h2>Recent Applications</h2>
                    <a href="../../careers.php" class="btn btn-primary">Browse Jobs</a>
                </div>
                <?php if (empty($applications)): ?>
                    <div class="empty-state">
                        <span class="material-symbols-outlined">work_off</span>
                        <h3>No Applications Yet</h3>
                        <p>Start your journey by applying to available positions</p>
                        <a href="../../careers.php" class="btn btn-primary" style="margin-top: 1rem;">Browse Jobs</a>
                    </div>
                <?php else: ?>
                    <table class="applications-table">
                        <thead><tr><th>Job Title</th><th>Department</th><th>Location</th><th>Applied Date</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($app['job_title'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($app['department_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($app['location'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($app['applied_date'])); ?></td>
                                <td><span class="status-badge <?php echo strtolower($app['status']); ?>"><?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?></span></td>
                                <td><a href="application-details.php?id=<?php echo $app['id']; ?>" class="btn btn-primary">View Details</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <?php include '../../includes/logout-modal.php'; ?>
</body>
</html>
