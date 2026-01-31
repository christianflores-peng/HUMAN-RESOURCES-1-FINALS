<?php
require_once '../../includes/session_helper.php';
startSecureSession();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_type'], ['Manager', 'HR_Staff', 'Admin'])) {
    header('Location: ../../index.php');
    exit();
}

require_once '../../database/config.php';

$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

$job_postings = fetchAll("
    SELECT jp.*, d.department_name, 
           (SELECT COUNT(*) FROM job_applications ja WHERE ja.job_posting_id = jp.id) as application_count
    FROM job_postings jp
    LEFT JOIN departments d ON d.id = jp.department_id
    ORDER BY jp.created_at DESC
");

$departments = fetchAll("SELECT id, department_name FROM departments ORDER BY department_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Postings - HR1</title>
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
        .btn-primary { background: #0ea5e9; color: white; }
        .btn-primary:hover { background: #0284c7; }
        .jobs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; }
        .job-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); transition: all 0.3s; }
        .job-card:hover { border-color: #0ea5e9; transform: translateY(-2px); }
        .job-title { font-size: 1.1rem; color: #e2e8f0; font-weight: 600; margin-bottom: 0.5rem; }
        .job-meta { display: flex; flex-wrap: wrap; gap: 1rem; color: #94a3b8; font-size: 0.85rem; margin-bottom: 1rem; }
        .meta-item { display: flex; align-items: center; gap: 0.35rem; }
        .job-stats { display: flex; gap: 1.5rem; padding-top: 1rem; border-top: 1px solid rgba(58, 69, 84, 0.3); }
        .stat { text-align: center; }
        .stat-value { font-size: 1.25rem; font-weight: 600; color: #0ea5e9; }
        .stat-label { font-size: 0.75rem; color: #94a3b8; }
        .status-badge { padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-badge.active { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .status-badge.closed { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .status-badge.draft { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .empty-state { text-align: center; padding: 4rem 2rem; color: #94a3b8; }
        .empty-state .material-symbols-outlined { font-size: 5rem; color: #475569; margin-bottom: 1rem; }
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
                <li class="nav-item"><a href="recruitment-dashboard.php" class="nav-link"><span class="material-symbols-outlined">dashboard</span>Recruitment Pipeline</a></li>
                <li class="nav-item"><a href="job-postings.php" class="nav-link active"><span class="material-symbols-outlined">work</span>Job Postings</a></li>
                <li class="nav-item"><a href="../../index.php" class="nav-link"><span class="material-symbols-outlined">home</span>Main Dashboard</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link"><span class="material-symbols-outlined">logout</span>Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Job Postings</h1>
                <button class="btn btn-primary" onclick="openModal('createJobModal')"><span class="material-symbols-outlined">add</span>Create Job Posting</button>
            </div>

            <?php if (empty($job_postings)): ?>
            <div class="empty-state">
                <span class="material-symbols-outlined">work_off</span>
                <h3>No Job Postings</h3>
                <p>Create your first job posting to start receiving applications.</p>
            </div>
            <?php else: ?>
            <div class="jobs-grid">
                <?php foreach ($job_postings as $job): ?>
                <div class="job-card">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                        <div class="job-title"><?php echo htmlspecialchars($job['title']); ?></div>
                        <span class="status-badge <?php echo strtolower($job['status']); ?>"><?php echo ucfirst($job['status']); ?></span>
                    </div>
                    <div class="job-meta">
                        <div class="meta-item"><span class="material-symbols-outlined" style="font-size: 1rem;">business</span><?php echo htmlspecialchars($job['department_name'] ?? 'N/A'); ?></div>
                        <div class="meta-item"><span class="material-symbols-outlined" style="font-size: 1rem;">location_on</span><?php echo htmlspecialchars($job['location'] ?? 'N/A'); ?></div>
                        <div class="meta-item"><span class="material-symbols-outlined" style="font-size: 1rem;">schedule</span><?php echo htmlspecialchars($job['employment_type'] ?? 'Full-time'); ?></div>
                    </div>
                    <div class="job-stats">
                        <div class="stat"><div class="stat-value"><?php echo $job['application_count']; ?></div><div class="stat-label">Applications</div></div>
                        <div class="stat"><div class="stat-value"><?php echo date('M d', strtotime($job['created_at'])); ?></div><div class="stat-label">Posted</div></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <?php include '../../includes/logout-modal.php'; ?>
    <script>
        function openModal(id) { document.getElementById(id)?.classList.add('active'); }
        function closeModal(id) { document.getElementById(id)?.classList.remove('active'); }
    </script>
</body>
</html>
