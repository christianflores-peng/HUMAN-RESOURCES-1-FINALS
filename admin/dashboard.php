<?php
require_once '../includes/session_helper.php';
startSecureSession();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../database/config.php';

$user_id = $_SESSION['user_id'];

// Fetch statistics
$total_users = fetchSingle("SELECT COUNT(*) as count FROM user_accounts")['count'];
$active_users = fetchSingle("SELECT COUNT(*) as count FROM user_accounts WHERE status = 'Active'")['count'];
$inactive_users = fetchSingle("SELECT COUNT(*) as count FROM user_accounts WHERE status != 'Active'")['count'];
$total_applications = fetchSingle("SELECT COUNT(*) as count FROM job_applications")['count'];
$recent_logs = fetchSingle("SELECT COUNT(*) as count FROM application_status_history WHERE DATE(changed_at) = CURDATE()")['count'];

// Fetch recent activity
$recent_activities = fetchAll("
    SELECT ash.*, ja.first_name, ja.last_name, ua.first_name as changed_by_first, ua.last_name as changed_by_last
    FROM application_status_history ash
    LEFT JOIN job_applications ja ON ja.id = ash.application_id
    LEFT JOIN user_accounts ua ON ua.id = ash.changed_by
    ORDER BY ash.changed_at DESC
    LIMIT 10
");

// Fetch user distribution by role
$role_distribution = fetchAll("
    SELECT r.role_name, COUNT(ua.id) as count
    FROM roles r
    LEFT JOIN user_accounts ua ON ua.role_id = r.id
    GROUP BY r.id, r.role_name
    ORDER BY count DESC
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a1929 0%, #1a2942 100%);
            min-height: 100vh;
            color: #f8fafc;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: rgba(15, 23, 42, 0.95);
            border-right: 1px solid rgba(58, 69, 84, 0.5);
            padding: 1.5rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .logo-section {
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(58, 69, 84, 0.5);
            margin-bottom: 1.5rem;
        }

        .logo-section img {
            width: 60px;
            margin-bottom: 0.5rem;
        }

        .logo-section h2 {
            font-size: 1.1rem;
            color: #ef4444;
        }

        .logo-section p {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        .admin-badge {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            padding: 0.35rem 0.75rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
            margin-top: 0.5rem;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border-left: 3px solid #ef4444;
        }

        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 2rem;
        }

        .header {
            background: rgba(30, 41, 54, 0.6);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 1.5rem;
            color: #e2e8f0;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(30, 41, 54, 0.6);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(58, 69, 84, 0.5);
        }

        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.75rem;
        }

        .stat-card.users .icon { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
        .stat-card.active .icon { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .stat-card.inactive .icon { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .stat-card.applications .icon { background: rgba(139, 92, 246, 0.2); color: #8b5cf6; }
        .stat-card.logs .icon { background: rgba(236, 72, 153, 0.2); color: #ec4899; }

        .stat-card h3 {
            font-size: 2rem;
            color: #e2e8f0;
            margin-bottom: 0.25rem;
        }

        .stat-card p {
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .card {
            background: rgba(30, 41, 54, 0.6);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(58, 69, 84, 0.5);
            margin-bottom: 1.5rem;
        }

        .card h2 {
            font-size: 1.2rem;
            color: #e2e8f0;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .activity-item {
            padding: 1rem;
            background: rgba(15, 23, 42, 0.6);
            border-radius: 8px;
            margin-bottom: 0.75rem;
            border-left: 3px solid #0ea5e9;
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.5rem;
        }

        .activity-title {
            color: #e2e8f0;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .activity-time {
            color: #94a3b8;
            font-size: 0.75rem;
        }

        .activity-details {
            color: #94a3b8;
            font-size: 0.85rem;
        }

        .role-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: rgba(15, 23, 42, 0.6);
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .role-name {
            color: #e2e8f0;
            font-size: 0.9rem;
        }

        .role-count {
            background: rgba(14, 165, 233, 0.2);
            color: #0ea5e9;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
        }

        @media (max-width: 1024px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-section">
                <img src="assets/images/slate.png" alt="SLATE Logo">
                <h2>Admin Portal</h2>
                <p><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></p>
                <span class="admin-badge">ADMINISTRATOR</span>
            </div>

            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="admin-dashboard.php" class="nav-link active">
                        <span class="material-symbols-outlined">dashboard</span>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin-accounts.php" class="nav-link">
                        <span class="material-symbols-outlined">manage_accounts</span>
                        Manage Accounts
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin-audit-logs.php" class="nav-link">
                        <span class="material-symbols-outlined">history</span>
                        Audit Logs
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin-roles.php" class="nav-link">
                        <span class="material-symbols-outlined">shield_person</span>
                        Roles & Permissions
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin-settings.php" class="nav-link">
                        <span class="material-symbols-outlined">settings</span>
                        System Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <span class="material-symbols-outlined">logout</span>
                        Logout
                    </a>
                </li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Admin Dashboard</h1>
                <p>System overview and recent activities</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card users">
                    <div class="icon">
                        <span class="material-symbols-outlined">group</span>
                    </div>
                    <h3><?php echo number_format($total_users); ?></h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-card active">
                    <div class="icon">
                        <span class="material-symbols-outlined">check_circle</span>
                    </div>
                    <h3><?php echo number_format($active_users); ?></h3>
                    <p>Active Users</p>
                </div>
                <div class="stat-card inactive">
                    <div class="icon">
                        <span class="material-symbols-outlined">warning</span>
                    </div>
                    <h3><?php echo number_format($inactive_users); ?></h3>
                    <p>Inactive Users</p>
                </div>
                <div class="stat-card applications">
                    <div class="icon">
                        <span class="material-symbols-outlined">description</span>
                    </div>
                    <h3><?php echo number_format($total_applications); ?></h3>
                    <p>Total Applications</p>
                </div>
                <div class="stat-card logs">
                    <div class="icon">
                        <span class="material-symbols-outlined">history</span>
                    </div>
                    <h3><?php echo number_format($recent_logs); ?></h3>
                    <p>Today's Activities</p>
                </div>
            </div>

            <div class="grid-2">
                <div class="card">
                    <h2>
                        <span class="material-symbols-outlined">notifications</span>
                        Recent Activities
                    </h2>
                    <?php if (empty($recent_activities)): ?>
                        <p style="color: #94a3b8; text-align: center; padding: 2rem;">No recent activities</p>
                    <?php else: ?>
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-header">
                                <div class="activity-title">
                                    <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?>
                                    - Status changed to <?php echo htmlspecialchars($activity['new_status']); ?>
                                </div>
                                <div class="activity-time">
                                    <?php echo date('M d, g:i A', strtotime($activity['changed_at'])); ?>
                                </div>
                            </div>
                            <div class="activity-details">
                                Changed by: <?php echo htmlspecialchars($activity['changed_by_first'] . ' ' . $activity['changed_by_last']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h2>
                        <span class="material-symbols-outlined">pie_chart</span>
                        User Distribution
                    </h2>
                    <?php foreach ($role_distribution as $role): ?>
                    <div class="role-item">
                        <span class="role-name"><?php echo htmlspecialchars($role['role_name']); ?></span>
                        <span class="role-count"><?php echo number_format($role['count']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
