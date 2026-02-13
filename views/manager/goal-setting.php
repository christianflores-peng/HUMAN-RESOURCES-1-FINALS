<?php
require_once '../../includes/session_helper.php';
require_once '../../includes/rbac_helper.php';
require_once '../../includes/spa_helper.php';
startSecureSession();
$is_ajax = is_spa_ajax();

if (!isset($_SESSION['user_id'])) { header('Location: ../../index.php'); exit(); }
$userId = $_SESSION['user_id'];
$user = getUserWithRole($userId);
if (!$user || !in_array($user['role_type'] ?? '', ['Manager', 'Admin', 'HR_Staff'])) {
    header('Location: ../../index.php'); exit();
}
if (!$is_ajax) { header('Location: index.php?page=goal-setting'); exit(); }

require_once '../../database/config.php';

$successMsg = null;
$errorMsg = null;

// Handle create goal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_goal'])) {
    $empId = intval($_POST['employee_id']);
    $title = trim($_POST['goal_title'] ?? '');
    $desc = trim($_POST['goal_description'] ?? '');
    $category = $_POST['category'] ?? 'Custom';
    $target = trim($_POST['target_value'] ?? '');
    $weight = intval($_POST['weight'] ?? 25);
    $dueDate = $_POST['due_date'] ?? null;
    $reviewPeriod = $_POST['review_period'] ?? '3-Month';

    if ($empId && $title) {
        try {
            insertRecord(
                "INSERT INTO performance_goals (employee_id, set_by, goal_title, goal_description, category, target_value, weight, due_date, review_period) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$empId, $userId, $title, $desc, $category, $target, $weight, $dueDate ?: null, $reviewPeriod]
            );
            logAuditAction($userId, 'CREATE', 'performance_goals', null, null, null, "Set goal '{$title}' for employee #{$empId}");
            $successMsg = "Goal created successfully!";
        } catch (Exception $e) { $errorMsg = "Failed: " . $e->getMessage(); }
    } else {
        $errorMsg = "Employee and goal title are required.";
    }
}

// Handle update goal status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_goal'])) {
    $goalId = intval($_POST['goal_id']);
    $status = $_POST['goal_status'] ?? '';
    $currentValue = trim($_POST['current_value'] ?? '');
    if ($goalId && $status) {
        try {
            updateRecord("UPDATE performance_goals SET status = ?, current_value = ? WHERE id = ? AND set_by = ?", [$status, $currentValue, $goalId, $userId]);
            $successMsg = "Goal updated!";
        } catch (Exception $e) { $errorMsg = "Failed: " . $e->getMessage(); }
    }
}

// Fetch team members
try {
    $teamMembers = fetchAll("
        SELECT ua.id, ua.first_name, ua.last_name, ua.employee_id
        FROM user_accounts ua
        LEFT JOIN roles r ON ua.role_id = r.id
        WHERE ua.department_id = ? AND ua.id != ? AND r.role_type = 'Employee'
        ORDER BY ua.first_name
    ", [$user['department_id'] ?? 0, $userId]);
} catch (Exception $e) { $teamMembers = []; }

// Fetch goals set by this manager
try {
    $goals = fetchAll("
        SELECT pg.*, ua.first_name, ua.last_name, ua.employee_id as emp_code
        FROM performance_goals pg
        LEFT JOIN user_accounts ua ON pg.employee_id = ua.id
        WHERE pg.set_by = ?
        ORDER BY pg.status ASC, pg.created_at DESC
    ", [$userId]);
} catch (Exception $e) { $goals = []; }

$activeGoals = count(array_filter($goals, fn($g) => $g['status'] === 'Active'));
$completedGoals = count(array_filter($goals, fn($g) => $g['status'] === 'Completed'));
?>
<div data-page-title="Goal Setting">
<style>
    .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
    .header h1 { font-size: 1.35rem; color: #e2e8f0; margin-bottom: 0.25rem; }
    .header p { color: #94a3b8; font-size: 0.85rem; }
    .btn-primary { padding: 0.6rem 1.2rem; background: linear-gradient(135deg, #0ea5e9, #0284c7); color: white; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; transition: all 0.3s; }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3); }
    .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    .stat-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.25rem; border: 1px solid rgba(58, 69, 84, 0.5); display: flex; align-items: center; gap: 1rem; }
    .stat-card .icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
    .stat-card.active .icon { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
    .stat-card.completed .icon { background: rgba(16, 185, 129, 0.2); color: #10b981; }
    .stat-card.total .icon { background: rgba(139, 92, 246, 0.2); color: #a78bfa; }
    .stat-card h3 { font-size: 1.5rem; color: #e2e8f0; }
    .stat-card p { font-size: 0.8rem; color: #94a3b8; }
    .goal-form { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1.5rem; display: none; }
    .goal-form.show { display: block; }
    .goal-form h3 { color: #e2e8f0; font-size: 1.1rem; margin-bottom: 1rem; }
    .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
    .form-group { display: flex; flex-direction: column; gap: 0.3rem; }
    .form-group.full { grid-column: 1 / -1; }
    .form-group label { color: #94a3b8; font-size: 0.8rem; font-weight: 500; }
    .form-group input, .form-group select, .form-group textarea { padding: 0.6rem; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(58, 69, 84, 0.5); border-radius: 8px; color: #e2e8f0; font-size: 0.85rem; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #0ea5e9; }
    .form-group textarea { resize: vertical; min-height: 60px; }
    .form-actions { display: flex; gap: 0.5rem; margin-top: 1rem; }
    .btn-secondary { padding: 0.6rem 1.2rem; background: rgba(107, 114, 128, 0.2); color: #9ca3af; border: 1px solid rgba(107, 114, 128, 0.3); border-radius: 8px; font-size: 0.85rem; cursor: pointer; }
    .goal-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1rem; }
    .goal-card.active-goal { border-left: 3px solid #0ea5e9; }
    .goal-card.completed-goal { border-left: 3px solid #10b981; }
    .goal-card.failed-goal { border-left: 3px solid #ef4444; }
    .goal-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem; }
    .goal-title { font-size: 1.05rem; color: #e2e8f0; font-weight: 600; }
    .goal-employee { color: #0ea5e9; font-size: 0.85rem; }
    .goal-meta { display: flex; gap: 1rem; flex-wrap: wrap; color: #94a3b8; font-size: 0.8rem; margin-bottom: 0.75rem; }
    .goal-meta span { display: flex; align-items: center; gap: 0.3rem; }
    .goal-progress { display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem; }
    .goal-progress .target { font-size: 0.85rem; color: #94a3b8; }
    .goal-progress .current { font-size: 0.85rem; color: #0ea5e9; font-weight: 600; }
    .status-badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .status-badge.active { background: rgba(14, 165, 233, 0.2); color: #38bdf8; }
    .status-badge.completed { background: rgba(16, 185, 129, 0.2); color: #34d399; }
    .status-badge.failed { background: rgba(239, 68, 68, 0.2); color: #f87171; }
    .status-badge.cancelled { background: rgba(107, 114, 128, 0.2); color: #9ca3af; }
    .category-badge { padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.7rem; font-weight: 600; background: rgba(139, 92, 246, 0.2); color: #a78bfa; }
    .goal-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.75rem; }
    .btn { padding: 0.4rem 0.8rem; border: none; border-radius: 6px; font-size: 0.8rem; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 0.3rem; }
    .btn-complete { background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
    .btn-fail { background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
    .update-input { padding: 0.4rem; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(58, 69, 84, 0.5); border-radius: 6px; color: #e2e8f0; font-size: 0.8rem; width: 120px; }
    .update-input:focus { outline: none; border-color: #0ea5e9; }
    .alert { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; }
    .alert-success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .alert-error { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
    .empty-state { text-align: center; padding: 4rem 2rem; color: #94a3b8; }
    .empty-state i { width: 4rem; height: 4rem; color: #475569; margin-bottom: 1rem; }
    @media (max-width: 768px) { .stats-row { grid-template-columns: 1fr; } .form-grid { grid-template-columns: 1fr; } }
</style>

    <div class="header">
        <div>
            <h1>Goal Setting</h1>
            <p>Set probationary targets for your team members</p>
        </div>
        <button class="btn-primary" onclick="document.getElementById('goalForm').classList.toggle('show')">
            <i data-lucide="plus" style="width:16px;height:16px;"></i> Set New Goal
        </button>
    </div>

    <?php if ($successMsg): ?><div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div><?php endif; ?>
    <?php if ($errorMsg): ?><div class="alert alert-error"><?php echo htmlspecialchars($errorMsg); ?></div><?php endif; ?>

    <div class="stats-row">
        <div class="stat-card active">
            <div class="icon"><i data-lucide="target"></i></div>
            <div><h3><?php echo $activeGoals; ?></h3><p>Active Goals</p></div>
        </div>
        <div class="stat-card completed">
            <div class="icon"><i data-lucide="check-circle"></i></div>
            <div><h3><?php echo $completedGoals; ?></h3><p>Completed</p></div>
        </div>
        <div class="stat-card total">
            <div class="icon"><i data-lucide="list"></i></div>
            <div><h3><?php echo count($goals); ?></h3><p>Total Goals</p></div>
        </div>
    </div>

    <div id="goalForm" class="goal-form">
        <h3><i data-lucide="target" style="width:20px;height:20px;display:inline;vertical-align:middle;"></i> Set Performance Goal</h3>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Employee *</label>
                    <select name="employee_id" required>
                        <option value="">Select employee...</option>
                        <?php foreach ($teamMembers as $tm): ?>
                        <option value="<?php echo $tm['id']; ?>"><?php echo htmlspecialchars($tm['first_name'] . ' ' . $tm['last_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <option value="Safety">Safety</option>
                        <option value="Punctuality">Punctuality</option>
                        <option value="Quality">Quality</option>
                        <option value="Teamwork">Teamwork</option>
                        <option value="Compliance">Compliance</option>
                        <option value="Custom">Custom</option>
                    </select>
                </div>
                <div class="form-group full">
                    <label>Goal Title *</label>
                    <input type="text" name="goal_title" required placeholder="e.g., Zero accidents in first 3 months">
                </div>
                <div class="form-group full">
                    <label>Description</label>
                    <textarea name="goal_description" placeholder="Detailed description of what needs to be achieved..."></textarea>
                </div>
                <div class="form-group">
                    <label>Target Value</label>
                    <input type="text" name="target_value" placeholder="e.g., 0 accidents, 95% on-time">
                </div>
                <div class="form-group">
                    <label>Weight (%)</label>
                    <input type="number" name="weight" value="25" min="5" max="100" step="5">
                </div>
                <div class="form-group">
                    <label>Review Period</label>
                    <select name="review_period">
                        <option value="3-Month">3-Month</option>
                        <option value="5-Month">5-Month</option>
                        <option value="6-Month">6-Month</option>
                        <option value="Annual">Annual</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Due Date</label>
                    <input type="date" name="due_date" min="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="create_goal" class="btn-primary"><i data-lucide="check" style="width:16px;height:16px;"></i> Create Goal</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('goalForm').classList.remove('show')">Cancel</button>
            </div>
        </form>
    </div>

    <?php if (empty($goals)): ?>
        <div class="empty-state">
            <i data-lucide="target"></i>
            <h3 style="color: #e2e8f0; margin-bottom: 0.5rem;">No Goals Set</h3>
            <p>Click "Set New Goal" to create performance targets for your team.</p>
        </div>
    <?php else: ?>
        <?php foreach ($goals as $goal): ?>
        <div class="goal-card <?php echo strtolower($goal['status']); ?>-goal">
            <div class="goal-header">
                <div>
                    <div class="goal-title"><?php echo htmlspecialchars($goal['goal_title']); ?></div>
                    <div class="goal-employee"><?php echo htmlspecialchars(($goal['first_name'] ?? '') . ' ' . ($goal['last_name'] ?? '')); ?></div>
                </div>
                <div style="display:flex;gap:0.5rem;align-items:center;">
                    <span class="category-badge"><?php echo $goal['category']; ?></span>
                    <span class="status-badge <?php echo strtolower($goal['status']); ?>"><?php echo $goal['status']; ?></span>
                </div>
            </div>

            <div class="goal-meta">
                <span><i data-lucide="calendar" style="width:14px;height:14px;"></i> <?php echo $goal['review_period']; ?></span>
                <?php if ($goal['due_date']): ?>
                <span><i data-lucide="clock" style="width:14px;height:14px;"></i> Due: <?php echo date('M d, Y', strtotime($goal['due_date'])); ?></span>
                <?php endif; ?>
                <span><i data-lucide="percent" style="width:14px;height:14px;"></i> Weight: <?php echo $goal['weight']; ?>%</span>
            </div>

            <?php if (!empty($goal['goal_description'])): ?>
            <div style="background:rgba(15,23,42,0.4);padding:0.5rem 0.75rem;border-radius:6px;color:#cbd5e1;font-size:0.85rem;margin-bottom:0.75rem;">
                <?php echo nl2br(htmlspecialchars($goal['goal_description'])); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($goal['target_value'])): ?>
            <div class="goal-progress">
                <span class="target">Target: <?php echo htmlspecialchars($goal['target_value']); ?></span>
                <?php if (!empty($goal['current_value'])): ?>
                <span class="current">Current: <?php echo htmlspecialchars($goal['current_value']); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($goal['status'] === 'Active'): ?>
            <div class="goal-actions">
                <form method="POST" style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                    <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                    <input type="text" name="current_value" class="update-input" placeholder="Current value..." value="<?php echo htmlspecialchars($goal['current_value'] ?? ''); ?>">
                    <input type="hidden" name="goal_status" value="Completed">
                    <button type="submit" name="update_goal" class="btn btn-complete"><i data-lucide="check" style="width:14px;height:14px;"></i> Complete</button>
                </form>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                    <input type="hidden" name="goal_status" value="Failed">
                    <input type="hidden" name="current_value" value="<?php echo htmlspecialchars($goal['current_value'] ?? ''); ?>">
                    <button type="submit" name="update_goal" class="btn btn-fail"><i data-lucide="x" style="width:14px;height:14px;"></i> Failed</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

<script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
</div>
