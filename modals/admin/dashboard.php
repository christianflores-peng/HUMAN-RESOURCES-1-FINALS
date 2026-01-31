<?php
require_once '../../includes/session_helper.php';
startSecureSession();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Admin') {
    header('Location: ../../index.php');
    exit();
}

require_once '../../database/config.php';

$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

$total_users = fetchSingle("SELECT COUNT(*) as count FROM user_accounts")['count'] ?? 0;
$active_users = fetchSingle("SELECT COUNT(*) as count FROM user_accounts WHERE status = 'active'")['count'] ?? 0;
$total_applications = fetchSingle("SELECT COUNT(*) as count FROM job_applications")['count'] ?? 0;
$active_jobs = fetchSingle("SELECT COUNT(*) as count FROM job_postings WHERE status = 'active'")['count'] ?? 0;

$recent_activities = fetchAll("
    SELECT ash.*, ja.first_name, ja.last_name, jp.title as job_title, u.first_name as changed_by_first, u.last_name as changed_by_last
    FROM application_status_history ash
    LEFT JOIN job_applications ja ON ja.id = ash.application_id
    LEFT JOIN job_postings jp ON jp.id = ja.job_posting_id
    LEFT JOIN user_accounts u ON u.id = ash.changed_by
    ORDER BY ash.changed_at DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HR1</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #0a1929 0%, #1a2942 100%); min-height: 100vh; color: #f8fafc; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: rgba(15, 23, 42, 0.95); border-right: 1px solid rgba(58, 69, 84, 0.5); padding: 1.5rem 0; position: fixed; height: 100vh; overflow-y: auto; }
        .logo-section { padding: 0 1.5rem 1.5rem; border-bottom: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1.5rem; }
        .logo-section img { width: 60px; margin-bottom: 0.5rem; }
        .logo-section h2 { font-size: 1.1rem; color: #f97316; }
        .logo-section p { font-size: 0.75rem; color: #94a3b8; }
        .nav-menu { list-style: none; }
        .nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; color: #cbd5e1; text-decoration: none; transition: all 0.3s; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(249, 115, 22, 0.1); color: #f97316; border-left: 3px solid #f97316; }
        .main-content { flex: 1; margin-left: 260px; padding: 2rem; }
        .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
        .header h1 { font-size: 1.5rem; color: #e2e8f0; margin-bottom: 0.25rem; }
        .header p { color: #94a3b8; font-size: 0.9rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); }
        .stat-card .icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; }
        .stat-card .icon.users { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
        .stat-card .icon.active { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .stat-card .icon.apps { background: rgba(168, 85, 247, 0.2); color: #a855f7; }
        .stat-card .icon.jobs { background: rgba(249, 115, 22, 0.2); color: #f97316; }
        .stat-card h3 { font-size: 2rem; color: #e2e8f0; margin-bottom: 0.25rem; }
        .stat-card p { font-size: 0.85rem; color: #94a3b8; }
        .card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); }
        .card h2 { font-size: 1.1rem; color: #e2e8f0; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem; }
        .activity-list { list-style: none; }
        .activity-item { display: flex; gap: 1rem; padding: 0.75rem 0; border-bottom: 1px solid rgba(58, 69, 84, 0.3); }
        .activity-item:last-child { border-bottom: none; }
        .activity-icon { width: 36px; height: 36px; border-radius: 50%; background: rgba(14, 165, 233, 0.2); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .activity-icon .material-symbols-outlined { font-size: 1.1rem; color: #0ea5e9; }
        .activity-content { flex: 1; }
        .activity-text { color: #e2e8f0; font-size: 0.9rem; margin-bottom: 0.25rem; }
        .activity-text strong { color: #f97316; }
        .activity-meta { color: #94a3b8; font-size: 0.8rem; }
        .empty-state { text-align: center; padding: 2rem; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-section">
                <img src="../../assets/images/slate.png" alt="SLATE Logo">
                <h2>Admin Portal</h2>
                <p><?php echo htmlspecialchars($user_name); ?></p>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link active"><span class="material-symbols-outlined">dashboard</span>Dashboard</a></li>
                <li class="nav-item"><a href="accounts.php" class="nav-link"><span class="material-symbols-outlined">manage_accounts</span>User Accounts</a></li>
                <li class="nav-item"><a href="audit-logs.php" class="nav-link"><span class="material-symbols-outlined">history</span>Audit Logs</a></li>
                <li class="nav-item"><a href="../../index.php" class="nav-link"><span class="material-symbols-outlined">home</span>Main Dashboard</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link"><span class="material-symbols-outlined">logout</span>Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Admin Dashboard</h1>
                <p>System overview and management - <?php echo date('l, F d, Y'); ?></p>
            </div>

            <div class="stats-grid">
                <div class="stat-card"><div class="icon users"><span class="material-symbols-outlined">group</span></div><h3><?php echo $total_users; ?></h3><p>Total Users</p></div>
                <div class="stat-card"><div class="icon active"><span class="material-symbols-outlined">verified_user</span></div><h3><?php echo $active_users; ?></h3><p>Active Users</p></div>
                <div class="stat-card"><div class="icon apps"><span class="material-symbols-outlined">description</span></div><h3><?php echo $total_applications; ?></h3><p>Total Applications</p></div>
                <div class="stat-card"><div class="icon jobs"><span class="material-symbols-outlined">work</span></div><h3><?php echo $active_jobs; ?></h3><p>Active Jobs</p></div>
            </div>

            <div class="card">
                <h2><span class="material-symbols-outlined">history</span>Recent Activity</h2>
                <?php if (empty($recent_activities)): ?>
                <div class="empty-state"><p>No recent activity to display.</p></div>
                <?php else: ?>
                <ul class="activity-list">
                    <?php foreach ($recent_activities as $activity): ?>
                    <li class="activity-item">
                        <div class="activity-icon"><span class="material-symbols-outlined">swap_horiz</span></div>
                        <div class="activity-content">
                            <div class="activity-text">
                                <strong><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></strong>
                                moved from <?php echo htmlspecialchars($activity['old_status']); ?> to <?php echo htmlspecialchars($activity['new_status']); ?>
                                for <?php echo htmlspecialchars($activity['job_title'] ?? 'Unknown Job'); ?>
                            </div>
                            <div class="activity-meta">
                                By <?php echo htmlspecialchars($activity['changed_by_first'] . ' ' . $activity['changed_by_last']); ?> • 
                                <?php echo date('M d, Y H:i', strtotime($activity['changed_at'])); ?>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <?php include '../../includes/logout-modal.php'; ?>
</body>
</html>
