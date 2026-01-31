<?php
require_once '../../includes/session_helper.php';
startSecureSession();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Employee') {
    header('Location: ../../index.php');
    exit();
}

require_once '../../database/config.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

$employee = fetchSingle("
    SELECT e.*, d.department_name, p.position_name
    FROM employees e
    LEFT JOIN departments d ON d.id = e.department_id
    LEFT JOIN positions p ON p.id = e.position_id
    WHERE e.user_id = ?
", [$user_id]);

$onboarding_progress = fetchAll("
    SELECT eop.*, ot.task_name, ot.description as task_description
    FROM employee_onboarding_progress eop
    JOIN onboarding_tasks ot ON ot.id = eop.task_id
    WHERE eop.employee_id = ?
    ORDER BY ot.order_number
", [$employee['id'] ?? 0]);

$completed_tasks = count(array_filter($onboarding_progress, fn($t) => $t['status'] === 'completed'));
$total_tasks = count($onboarding_progress);
$progress_percent = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - HR1</title>
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
        .header h1 { font-size: 1.5rem; color: #e2e8f0; margin-bottom: 0.25rem; }
        .header p { color: #94a3b8; font-size: 0.9rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); }
        .stat-card .icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
        .stat-card h3 { font-size: 1.75rem; color: #e2e8f0; margin-bottom: 0.25rem; }
        .stat-card p { font-size: 0.85rem; color: #94a3b8; }
        .card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1.5rem; }
        .card h2 { font-size: 1.2rem; color: #e2e8f0; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .progress-bar { height: 10px; background: rgba(58, 69, 84, 0.5); border-radius: 5px; overflow: hidden; margin-bottom: 1rem; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #0ea5e9, #10b981); border-radius: 5px; transition: width 0.5s; }
        .task-list { list-style: none; }
        .task-item { display: flex; align-items: center; gap: 1rem; padding: 0.75rem 0; border-bottom: 1px solid rgba(58, 69, 84, 0.3); }
        .task-item:last-child { border-bottom: none; }
        .task-checkbox { width: 24px; height: 24px; border-radius: 50%; border: 2px solid rgba(58, 69, 84, 0.5); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .task-checkbox.completed { background: #10b981; border-color: #10b981; color: white; }
        .task-name { flex: 1; color: #e2e8f0; font-size: 0.9rem; }
        .task-name.completed { text-decoration: line-through; color: #94a3b8; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .info-item { padding: 0.75rem; background: rgba(15, 23, 42, 0.4); border-radius: 8px; }
        .info-label { color: #94a3b8; font-size: 0.8rem; margin-bottom: 0.25rem; }
        .info-value { color: #e2e8f0; font-size: 0.95rem; font-weight: 500; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-section">
                <img src="../../assets/images/slate.png" alt="SLATE Logo">
                <h2>Employee Portal</h2>
                <p><?php echo htmlspecialchars($user_name); ?></p>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link active"><span class="material-symbols-outlined">dashboard</span>Dashboard</a></li>
                <li class="nav-item"><a href="onboarding.php" class="nav-link"><span class="material-symbols-outlined">checklist</span>Onboarding</a></li>
                <li class="nav-item"><a href="requirements.php" class="nav-link"><span class="material-symbols-outlined">upload_file</span>Submit Requirements</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link"><span class="material-symbols-outlined">person</span>My Profile</a></li>
                <li class="nav-item"><a href="documents.php" class="nav-link"><span class="material-symbols-outlined">folder</span>Documents</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link"><span class="material-symbols-outlined">logout</span>Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h1>
                <p>Employee Dashboard - <?php echo date('l, F d, Y'); ?></p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon"><span class="material-symbols-outlined">badge</span></div>
                    <h3><?php echo htmlspecialchars($employee['employee_id'] ?? 'N/A'); ?></h3>
                    <p>Employee ID</p>
                </div>
                <div class="stat-card">
                    <div class="icon"><span class="material-symbols-outlined">business</span></div>
                    <h3><?php echo htmlspecialchars($employee['department_name'] ?? 'N/A'); ?></h3>
                    <p>Department</p>
                </div>
                <div class="stat-card">
                    <div class="icon"><span class="material-symbols-outlined">work</span></div>
                    <h3><?php echo htmlspecialchars($employee['position_name'] ?? 'N/A'); ?></h3>
                    <p>Position</p>
                </div>
                <div class="stat-card">
                    <div class="icon"><span class="material-symbols-outlined">calendar_today</span></div>
                    <h3><?php echo $employee['start_date'] ? date('M d, Y', strtotime($employee['start_date'])) : 'N/A'; ?></h3>
                    <p>Start Date</p>
                </div>
            </div>

            <?php if (!empty($onboarding_progress)): ?>
            <div class="card">
                <h2><span class="material-symbols-outlined">checklist</span>Onboarding Progress</h2>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="color: #94a3b8; font-size: 0.85rem;"><?php echo $completed_tasks; ?> of <?php echo $total_tasks; ?> tasks completed</span>
                    <span style="color: #0ea5e9; font-weight: 600;"><?php echo $progress_percent; ?>%</span>
                </div>
                <div class="progress-bar"><div class="progress-fill" style="width: <?php echo $progress_percent; ?>%;"></div></div>
                <ul class="task-list">
                    <?php foreach (array_slice($onboarding_progress, 0, 5) as $task): ?>
                    <li class="task-item">
                        <div class="task-checkbox <?php echo $task['status'] === 'completed' ? 'completed' : ''; ?>">
                            <?php if ($task['status'] === 'completed'): ?><span class="material-symbols-outlined" style="font-size: 1rem;">check</span><?php endif; ?>
                        </div>
                        <span class="task-name <?php echo $task['status'] === 'completed' ? 'completed' : ''; ?>"><?php echo htmlspecialchars($task['task_name']); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($total_tasks > 5): ?>
                <a href="onboarding.php" style="display: block; text-align: center; color: #0ea5e9; margin-top: 1rem; text-decoration: none;">View All Tasks</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="card">
                <h2><span class="material-symbols-outlined">info</span>Employee Information</h2>
                <div class="info-grid">
                    <div class="info-item"><div class="info-label">Full Name</div><div class="info-value"><?php echo htmlspecialchars($user_name); ?></div></div>
                    <div class="info-item"><div class="info-label">Email</div><div class="info-value"><?php echo htmlspecialchars($_SESSION['personal_email'] ?? 'N/A'); ?></div></div>
                    <div class="info-item"><div class="info-label">Department</div><div class="info-value"><?php echo htmlspecialchars($employee['department_name'] ?? 'N/A'); ?></div></div>
                    <div class="info-item"><div class="info-label">Position</div><div class="info-value"><?php echo htmlspecialchars($employee['position_name'] ?? 'N/A'); ?></div></div>
                </div>
            </div>
        </main>
    </div>
    <?php include '../../includes/logout-modal.php'; ?>
</body>
</html>
