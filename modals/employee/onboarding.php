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

$employee = fetchSingle("SELECT * FROM employees WHERE user_id = ?", [$user_id]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'])) {
    $task_id = $_POST['task_id'];
    $status = $_POST['status'] === 'completed' ? 'completed' : 'pending';
    executeQuery("UPDATE employee_onboarding_progress SET status = ?, completed_at = ? WHERE employee_id = ? AND task_id = ?", 
        [$status, $status === 'completed' ? date('Y-m-d H:i:s') : null, $employee['id'], $task_id]);
    header("Location: onboarding.php");
    exit();
}

$onboarding_tasks = fetchAll("
    SELECT eop.*, ot.task_name, ot.description, ot.required
    FROM employee_onboarding_progress eop
    JOIN onboarding_tasks ot ON ot.id = eop.task_id
    WHERE eop.employee_id = ?
    ORDER BY ot.order_number
", [$employee['id'] ?? 0]);

$completed = count(array_filter($onboarding_tasks, fn($t) => $t['status'] === 'completed'));
$total = count($onboarding_tasks);
$progress = $total > 0 ? round(($completed / $total) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onboarding - HR1</title>
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
        .progress-section { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 2rem; }
        .progress-bar { height: 12px; background: rgba(58, 69, 84, 0.5); border-radius: 6px; overflow: hidden; margin: 1rem 0; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #0ea5e9, #10b981); border-radius: 6px; }
        .task-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1rem; display: flex; gap: 1rem; align-items: start; }
        .task-card.completed { opacity: 0.7; }
        .task-checkbox { width: 30px; height: 30px; border-radius: 50%; border: 2px solid rgba(58, 69, 84, 0.5); display: flex; align-items: center; justify-content: center; flex-shrink: 0; cursor: pointer; transition: all 0.3s; }
        .task-checkbox:hover { border-color: #0ea5e9; }
        .task-checkbox.completed { background: #10b981; border-color: #10b981; color: white; }
        .task-content { flex: 1; }
        .task-name { font-size: 1rem; color: #e2e8f0; font-weight: 600; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.5rem; }
        .task-name.completed { text-decoration: line-through; color: #94a3b8; }
        .required-badge { background: rgba(239, 68, 68, 0.2); color: #ef4444; padding: 0.15rem 0.5rem; border-radius: 10px; font-size: 0.7rem; font-weight: 600; }
        .task-description { color: #94a3b8; font-size: 0.85rem; }
        .empty-state { text-align: center; padding: 4rem 2rem; color: #94a3b8; }
        .empty-state .material-symbols-outlined { font-size: 5rem; color: #475569; margin-bottom: 1rem; }
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
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><span class="material-symbols-outlined">dashboard</span>Dashboard</a></li>
                <li class="nav-item"><a href="onboarding.php" class="nav-link active"><span class="material-symbols-outlined">checklist</span>Onboarding</a></li>
                <li class="nav-item"><a href="requirements.php" class="nav-link"><span class="material-symbols-outlined">upload_file</span>Submit Requirements</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link"><span class="material-symbols-outlined">person</span>My Profile</a></li>
                <li class="nav-item"><a href="documents.php" class="nav-link"><span class="material-symbols-outlined">folder</span>Documents</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link"><span class="material-symbols-outlined">logout</span>Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header"><h1>Onboarding Checklist</h1></div>

            <div class="progress-section">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #e2e8f0; font-weight: 600;">Your Progress</span>
                    <span style="color: #0ea5e9; font-weight: 600; font-size: 1.25rem;"><?php echo $progress; ?>%</span>
                </div>
                <div class="progress-bar"><div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div></div>
                <p style="color: #94a3b8; font-size: 0.85rem;"><?php echo $completed; ?> of <?php echo $total; ?> tasks completed</p>
            </div>

            <?php if (empty($onboarding_tasks)): ?>
            <div class="empty-state">
                <span class="material-symbols-outlined">checklist</span>
                <h3>No Onboarding Tasks</h3>
                <p>Your onboarding checklist will appear here once assigned.</p>
            </div>
            <?php else: ?>
            <?php foreach ($onboarding_tasks as $task): ?>
            <div class="task-card <?php echo $task['status'] === 'completed' ? 'completed' : ''; ?>">
                <form method="POST" style="display: flex; align-items: center;">
                    <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">
                    <input type="hidden" name="status" value="<?php echo $task['status'] === 'completed' ? 'pending' : 'completed'; ?>">
                    <button type="submit" class="task-checkbox <?php echo $task['status'] === 'completed' ? 'completed' : ''; ?>" style="background: none; border: 2px solid <?php echo $task['status'] === 'completed' ? '#10b981' : 'rgba(58, 69, 84, 0.5)'; ?>; <?php echo $task['status'] === 'completed' ? 'background: #10b981;' : ''; ?>">
                        <?php if ($task['status'] === 'completed'): ?><span class="material-symbols-outlined" style="font-size: 1.1rem; color: white;">check</span><?php endif; ?>
                    </button>
                </form>
                <div class="task-content">
                    <div class="task-name <?php echo $task['status'] === 'completed' ? 'completed' : ''; ?>">
                        <?php echo htmlspecialchars($task['task_name']); ?>
                        <?php if ($task['required']): ?><span class="required-badge">Required</span><?php endif; ?>
                    </div>
                    <div class="task-description"><?php echo htmlspecialchars($task['description'] ?? ''); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
    <?php include '../../includes/logout-modal.php'; ?>
</body>
</html>
