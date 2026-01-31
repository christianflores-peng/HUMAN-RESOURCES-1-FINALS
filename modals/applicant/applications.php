<?php
require_once '../../includes/session_helper.php';
startSecureSession();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Applicant') {
    header('Location: ../../index.php');
    exit();
}

require_once '../../database/config.php';

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['personal_email'] ?? '';

$status_filter = $_GET['status'] ?? 'all';

$where_clause = "ja.email = ?";
$params = [$user_email];

if ($status_filter !== 'all') {
    $where_clause .= " AND ja.status = ?";
    $params[] = $status_filter;
}

try {
    $applications = fetchAll("
        SELECT ja.*, 
               COALESCE(jp.title, 'General Application') as job_title,
               COALESCE(jp.location, 'N/A') as location,
               COALESCE(jp.employment_type, 'To Be Determined') as employment_type,
               COALESCE(d.department_name, 'Not Assigned') as department_name
        FROM job_applications ja
        LEFT JOIN job_postings jp ON jp.id = ja.job_posting_id
        LEFT JOIN departments d ON d.id = jp.department_id
        WHERE {$where_clause}
        ORDER BY ja.applied_date DESC
    ", $params);
} catch (Exception $e) {
    $applications = [];
}

$status_counts = ['all' => count($applications), 'new' => 0, 'screening' => 0, 'interview' => 0, 'road_test' => 0, 'offer_sent' => 0, 'hired' => 0, 'rejected' => 0];
foreach ($applications as $app) {
    if (isset($status_counts[$app['status']])) { $status_counts[$app['status']]++; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - HR1</title>
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
        .btn { padding: 0.65rem 1.25rem; border: none; border-radius: 6px; font-size: 0.9rem; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-secondary { background: rgba(100, 116, 139, 0.3); color: #cbd5e1; }
        .btn-secondary:hover { background: rgba(100, 116, 139, 0.5); }
        .btn-primary { background: #0ea5e9; color: white; }
        .btn-primary:hover { background: #0284c7; }
                .filter-tabs { display: flex; gap: 0.5rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .filter-tab { padding: 0.65rem 1.25rem; background: rgba(30, 41, 54, 0.6); border: 1px solid rgba(58, 69, 84, 0.5); border-radius: 8px; color: #cbd5e1; text-decoration: none; font-size: 0.85rem; transition: all 0.3s; display: flex; align-items: center; gap: 0.5rem; }
        .filter-tab:hover { background: rgba(30, 41, 54, 0.8); border-color: #0ea5e9; }
        .filter-tab.active { background: rgba(14, 165, 233, 0.2); border-color: #0ea5e9; color: #0ea5e9; }
        .count-badge { background: rgba(100, 116, 139, 0.3); padding: 0.15rem 0.5rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .filter-tab.active .count-badge { background: rgba(14, 165, 233, 0.3); color: #0ea5e9; }
        .applications-grid { display: grid; gap: 1.5rem; }
        .application-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); transition: all 0.3s; }
        .application-card:hover { border-color: #0ea5e9; transform: translateY(-2px); }
        .card-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
        .card-title { font-size: 1.1rem; color: #e2e8f0; font-weight: 600; margin-bottom: 0.5rem; }
        .card-meta { display: flex; gap: 1.5rem; flex-wrap: wrap; color: #94a3b8; font-size: 0.85rem; margin-bottom: 1rem; }
        .meta-item { display: flex; align-items: center; gap: 0.35rem; }
        .status-badge { padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-badge.new { background: rgba(99, 102, 241, 0.2); color: #6366f1; }
        .status-badge.screening { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .status-badge.interview { background: rgba(139, 92, 246, 0.2); color: #8b5cf6; }
        .status-badge.road_test { background: rgba(236, 72, 153, 0.2); color: #ec4899; }
        .status-badge.offer_sent { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .status-badge.hired { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .status-badge.rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .card-actions { display: flex; gap: 0.75rem; padding-top: 1rem; border-top: 1px solid rgba(58, 69, 84, 0.3); flex-wrap: wrap; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-info { background: #8b5cf6; color: white; }
        .btn-info:hover { background: #7c3aed; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-warning:hover { background: #d97706; }
        .empty-state { text-align: center; padding: 4rem 2rem; color: #94a3b8; }
        .empty-state .material-symbols-outlined { font-size: 5rem; color: #475569; margin-bottom: 1rem; }
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
                <h1>My Applications</h1>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                    <a href="dashboard.php" class="btn btn-secondary"><span class="material-symbols-outlined">arrow_back</span>Back to Dashboard</a>
                </div>
            </div>

            <div class="filter-tabs">
                <a href="?status=all" class="filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All Applications<span class="count-badge"><?php echo $status_counts['all']; ?></span></a>
                <a href="?status=new" class="filter-tab <?php echo $status_filter === 'new' ? 'active' : ''; ?>">New<span class="count-badge"><?php echo $status_counts['new']; ?></span></a>
                <a href="?status=screening" class="filter-tab <?php echo $status_filter === 'screening' ? 'active' : ''; ?>">Screening<span class="count-badge"><?php echo $status_counts['screening']; ?></span></a>
                <a href="?status=interview" class="filter-tab <?php echo $status_filter === 'interview' ? 'active' : ''; ?>">Interview<span class="count-badge"><?php echo $status_counts['interview']; ?></span></a>
                <a href="?status=road_test" class="filter-tab <?php echo $status_filter === 'road_test' ? 'active' : ''; ?>">Road Test<span class="count-badge"><?php echo $status_counts['road_test']; ?></span></a>
                <a href="?status=offer_sent" class="filter-tab <?php echo $status_filter === 'offer_sent' ? 'active' : ''; ?>">Offer Sent<span class="count-badge"><?php echo $status_counts['offer_sent']; ?></span></a>
                <a href="?status=hired" class="filter-tab <?php echo $status_filter === 'hired' ? 'active' : ''; ?>">Hired<span class="count-badge"><?php echo $status_counts['hired']; ?></span></a>
            </div>

            <?php if (empty($applications)): ?>
            <div class="empty-state">
                <span class="material-symbols-outlined">inbox</span>
                <h3>No Applications Found</h3>
                <p>You haven't submitted any applications yet.</p>
            </div>
            <?php else: ?>
            <div class="applications-grid">
                <?php foreach ($applications as $app): ?>
                <div class="application-card">
                    <div class="card-header">
                        <div>
                            <div class="card-title"><?php echo htmlspecialchars($app['job_title']); ?></div>
                            <div class="card-meta">
                                <div class="meta-item"><span class="material-symbols-outlined" style="font-size: 1rem;">business</span><?php echo htmlspecialchars($app['department_name']); ?></div>
                                <div class="meta-item"><span class="material-symbols-outlined" style="font-size: 1rem;">location_on</span><?php echo htmlspecialchars($app['location']); ?></div>
                                <div class="meta-item"><span class="material-symbols-outlined" style="font-size: 1rem;">calendar_today</span><?php echo date('M d, Y', strtotime($app['applied_date'])); ?></div>
                            </div>
                        </div>
                        <span class="status-badge <?php echo $app['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?></span>
                    </div>
                    <div class="card-actions">
                        <a href="application-details.php?id=<?php echo $app['id']; ?>" class="btn btn-primary"><span class="material-symbols-outlined">visibility</span>View Details</a>
                        <?php if ($app['status'] === 'offer_sent'): ?>
                        <a href="offer-view.php?id=<?php echo $app['id']; ?>" class="btn btn-success"><span class="material-symbols-outlined">description</span>View Offer</a>
                        <?php elseif ($app['status'] === 'interview'): ?>
                        <a href="interview-schedule.php?id=<?php echo $app['id']; ?>" class="btn btn-info"><span class="material-symbols-outlined">event</span>Interview Info</a>
                        <?php elseif ($app['status'] === 'road_test'): ?>
                        <a href="road-test-info.php?id=<?php echo $app['id']; ?>" class="btn btn-warning"><span class="material-symbols-outlined">directions_car</span>Road Test Info</a>
                        <?php endif; ?>
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
