<?php
require_once '../../includes/session_helper.php';
require_once '../../includes/spa_helper.php';
startSecureSession();
$is_ajax = is_spa_ajax();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Employee') {
    header('Location: ../../index.php');
    exit();
}

if (!$is_ajax) {
    header('Location: index.php?page=onboarding');
    exit();
}

require_once '../../database/config.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

$employee = fetchSingle("
    SELECT ua.*, d.department_name, ua.job_title AS position_name
    FROM user_accounts ua
    LEFT JOIN departments d ON d.id = ua.department_id
    WHERE ua.id = ?
", [$user_id]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'])) {
    $task_id = $_POST['task_id'];
    $status = $_POST['status'] === 'completed' ? 'Completed' : 'Pending';
    executeQuery("UPDATE employee_onboarding_progress SET status = ?, completed_at = ? WHERE user_id = ? AND task_id = ?", 
        [$status, $status === 'Completed' ? date('Y-m-d H:i:s') : null, $user_id, $task_id]);
    header("Location: onboarding.php");
    exit();
}

$onboarding_tasks = fetchAll("
    SELECT eop.*, ot.task_name, ot.task_description, ot.is_required
    FROM employee_onboarding_progress eop
    JOIN onboarding_tasks ot ON ot.id = eop.task_id
    WHERE eop.user_id = ?
", [$user_id]);

$completed = count(array_filter($onboarding_tasks, fn($t) => $t['status'] === 'Completed'));
$total = count($onboarding_tasks);
$progress = $total > 0 ? round(($completed / $total) * 100) : 0;
?>
<div data-page-title="Onboarding Checklist">
<style>
        .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
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
        .empty-state i { width: 5rem; height: 5rem; color: #475569; margin-bottom: 1rem; }
    </style>
            <div class="header">
                <div><h1>Onboarding Checklist</h1></div>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                </div>
            </div>

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
                <i data-lucide="list-checks"></i>
                <h3>No Onboarding Tasks</h3>
                <p>Your onboarding checklist will appear here once assigned.</p>
            </div>
            <?php else: ?>
            <?php foreach ($onboarding_tasks as $task): ?>
            <div class="task-card <?php echo $task['status'] === 'Completed' ? 'completed' : ''; ?>">
                <form method="POST" style="display: flex; align-items: center;">
                    <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">
                    <input type="hidden" name="status" value="<?php echo $task['status'] === 'Completed' ? 'pending' : 'completed'; ?>">
                    <button type="submit" class="task-checkbox <?php echo $task['status'] === 'Completed' ? 'completed' : ''; ?>" style="background: none; border: 2px solid <?php echo $task['status'] === 'Completed' ? '#10b981' : 'rgba(58, 69, 84, 0.5)'; ?>; <?php echo $task['status'] === 'Completed' ? 'background: #10b981;' : ''; ?>">
                        <?php if ($task['status'] === 'Completed'): ?><i data-lucide="check" style="width: 1.1rem; height: 1.1rem; color: white;"></i><?php endif; ?>
                    </button>
                </form>
                <div class="task-content">
                    <div class="task-name <?php echo $task['status'] === 'Completed' ? 'completed' : ''; ?>">
                        <?php echo htmlspecialchars($task['task_name']); ?>
                        <?php if ($task['is_required']): ?><span class="required-badge">Required</span><?php endif; ?>
                    </div>
                    <div class="task-description"><?php echo htmlspecialchars($task['task_description'] ?? ''); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
</div>
