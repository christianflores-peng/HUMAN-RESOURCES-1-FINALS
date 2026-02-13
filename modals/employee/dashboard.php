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
    header('Location: index.php?page=dashboard');
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

$onboarding_progress = fetchAll("
    SELECT eop.*, ot.task_name, ot.task_description
    FROM employee_onboarding_progress eop
    JOIN onboarding_tasks ot ON ot.id = eop.task_id
    WHERE eop.user_id = ?
", [$user_id]);

$completed_tasks = count(array_filter($onboarding_progress, fn($t) => $t['status'] === 'Completed'));
$total_tasks = count($onboarding_progress);
$progress_percent = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;

// Fetch recent recognitions for this employee
$myRecognitions = [];
try {
    $myRecognitions = fetchAll("
        SELECT sr.*, g.first_name as g_first, g.last_name as g_last
        FROM social_recognitions sr
        LEFT JOIN user_accounts g ON sr.given_by = g.id
        WHERE sr.recipient_id = ?
        ORDER BY sr.created_at DESC LIMIT 5
    ", [$user_id]);
} catch (Exception $e) { $myRecognitions = []; }

// Fetch latest public recognitions (wall preview)
$wallPosts = [];
try {
    $wallPosts = fetchAll("
        SELECT sr.*, r.first_name as r_first, r.last_name as r_last, g.first_name as g_first, g.last_name as g_last
        FROM social_recognitions sr
        LEFT JOIN user_accounts r ON sr.recipient_id = r.id
        LEFT JOIN user_accounts g ON sr.given_by = g.id
        WHERE sr.is_public = 1
        ORDER BY sr.created_at DESC LIMIT 3
    ", []);
} catch (Exception $e) { $wallPosts = []; }

$badgeEmojis = ['star'=>'&#11088;','heart'=>'&#10084;&#65039;','fire'=>'&#128293;','trophy'=>'&#127942;','clap'=>'&#128079;','rocket'=>'&#128640;','sparkles'=>'&#10024;','thumbs-up'=>'&#128077;'];
?>
<div data-page-title="Employee Dashboard">
<style>
        .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
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
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .recognition-item { padding: 0.875rem; background: rgba(15, 23, 42, 0.6); border-radius: 8px; margin-bottom: 0.6rem; border-left: 3px solid #f59e0b; display: flex; align-items: start; gap: 0.75rem; }
        .recognition-item .badge-emoji { font-size: 1.5rem; flex-shrink: 0; }
        .recognition-item .rec-content { flex: 1; }
        .recognition-item .rec-message { color: #e2e8f0; font-size: 0.9rem; margin-bottom: 0.25rem; }
        .recognition-item .rec-from { color: #94a3b8; font-size: 0.8rem; }
        .recognition-item .rec-time { color: #64748b; font-size: 0.75rem; }
        .wall-item { padding: 0.875rem; background: rgba(15, 23, 42, 0.6); border-radius: 8px; margin-bottom: 0.6rem; }
        .wall-item .wall-header { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem; }
        .wall-item .wall-badge { font-size: 1.25rem; }
        .wall-item .wall-name { color: #e2e8f0; font-weight: 500; font-size: 0.9rem; }
        .wall-item .wall-message { color: #cbd5e1; font-size: 0.85rem; line-height: 1.5; }
        .wall-item .wall-footer { display: flex; justify-content: space-between; margin-top: 0.4rem; font-size: 0.75rem; color: #64748b; }
        .quick-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.5rem; }
        .quick-action-btn { display: flex; align-items: center; gap: 0.75rem; padding: 1rem 1.25rem; background: rgba(30, 41, 54, 0.6); border: 1px solid rgba(58, 69, 84, 0.5); border-radius: 12px; color: #e2e8f0; text-decoration: none; transition: all 0.3s; font-size: 0.9rem; }
        .quick-action-btn:hover { background: rgba(14, 165, 233, 0.1); border-color: #0ea5e9; }
        .quick-action-btn i { color: #0ea5e9; }
        @media (max-width: 768px) { .grid-2 { grid-template-columns: 1fr; } .quick-actions { grid-template-columns: 1fr; } }
    </style>
            <div class="header">
                <div>
                    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h1>
                    <p>Employee Dashboard - <?php echo date('l, F d, Y'); ?></p>
                </div>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon"><i data-lucide="badge-check"></i></div>
                    <h3><?php echo htmlspecialchars($employee['employee_id'] ?? 'N/A'); ?></h3>
                    <p>Employee ID</p>
                </div>
                <div class="stat-card">
                    <div class="icon"><i data-lucide="building"></i></div>
                    <h3><?php echo htmlspecialchars($employee['department_name'] ?? 'N/A'); ?></h3>
                    <p>Department</p>
                </div>
                <div class="stat-card">
                    <div class="icon"><i data-lucide="briefcase"></i></div>
                    <h3><?php echo htmlspecialchars($employee['position_name'] ?? 'N/A'); ?></h3>
                    <p>Position</p>
                </div>
                <div class="stat-card">
                    <div class="icon"><i data-lucide="calendar"></i></div>
                    <h3><?php echo $employee['hire_date'] ? date('M d, Y', strtotime($employee['hire_date'])) : 'N/A'; ?></h3>
                    <p>Start Date</p>
                </div>
            </div>

            <?php if (!empty($onboarding_progress)): ?>
            <div class="card">
                <h2><i data-lucide="list-checks"></i>Onboarding Progress</h2>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="color: #94a3b8; font-size: 0.85rem;"><?php echo $completed_tasks; ?> of <?php echo $total_tasks; ?> tasks completed</span>
                    <span style="color: #0ea5e9; font-weight: 600;"><?php echo $progress_percent; ?>%</span>
                </div>
                <div class="progress-bar"><div class="progress-fill" style="width: <?php echo $progress_percent; ?>%;"></div></div>
                <ul class="task-list">
                    <?php foreach (array_slice($onboarding_progress, 0, 5) as $task): ?>
                    <li class="task-item">
                        <div class="task-checkbox <?php echo $task['status'] === 'completed' ? 'completed' : ''; ?>">
                            <?php if ($task['status'] === 'completed'): ?><i data-lucide="check" style="width: 1rem; height: 1rem;"></i><?php endif; ?>
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

            <div class="quick-actions">
                <a href="onboarding.php" class="quick-action-btn spa-link">
                    <i data-lucide="list-checks"></i>
                    <div>
                        <strong>Onboarding Checklist</strong>
                        <p style="color: #94a3b8; font-size: 0.8rem;">Complete your tasks</p>
                    </div>
                </a>
                <a href="requirements.php" class="quick-action-btn spa-link">
                    <i data-lucide="upload"></i>
                    <div>
                        <strong>Submit Requirements</strong>
                        <p style="color: #94a3b8; font-size: 0.8rem;">Upload documents</p>
                    </div>
                </a>
                <a href="recognition-wall.php" class="quick-action-btn spa-link">
                    <i data-lucide="heart" style="color: #f59e0b;"></i>
                    <div>
                        <strong>Recognition Wall</strong>
                        <p style="color: #94a3b8; font-size: 0.8rem;">Give kudos to colleagues</p>
                    </div>
                </a>
                <a href="profile.php" class="quick-action-btn spa-link">
                    <i data-lucide="user"></i>
                    <div>
                        <strong>My Profile</strong>
                        <p style="color: #94a3b8; font-size: 0.8rem;">View & update info</p>
                    </div>
                </a>
            </div>

            <div class="grid-2">
                <div class="card">
                    <h2><i data-lucide="star"></i> My Kudos</h2>
                    <?php if (empty($myRecognitions)): ?>
                        <p style="color: #94a3b8; text-align: center; padding: 2rem;">No kudos received yet. Keep up the great work!</p>
                    <?php else: ?>
                        <?php foreach ($myRecognitions as $rec): ?>
                        <div class="recognition-item">
                            <div class="badge-emoji"><?php echo $badgeEmojis[$rec['badge_icon']] ?? '&#11088;'; ?></div>
                            <div class="rec-content">
                                <div class="rec-message"><?php echo htmlspecialchars(mb_strimwidth($rec['message'], 0, 120, '...')); ?></div>
                                <div class="rec-from">
                                    <?php if ($rec['is_system_generated']): ?>System
                                    <?php else: ?>From: <?php echo htmlspecialchars(($rec['g_first'] ?? '') . ' ' . ($rec['g_last'] ?? '')); ?>
                                    <?php endif; ?>
                                    <span class="rec-time">&bull; <?php echo date('M d', strtotime($rec['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <a href="recognition-wall.php" class="spa-link" style="display:block;text-align:center;color:#f59e0b;padding:0.75rem;font-size:0.85rem;text-decoration:none;">View Recognition Wall &rarr;</a>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h2><i data-lucide="megaphone"></i> Latest Wall Posts</h2>
                    <?php if (empty($wallPosts)): ?>
                        <p style="color: #94a3b8; text-align: center; padding: 2rem;">No posts yet. Be the first to give kudos!</p>
                    <?php else: ?>
                        <?php foreach ($wallPosts as $wp): ?>
                        <div class="wall-item">
                            <div class="wall-header">
                                <span class="wall-badge"><?php echo $badgeEmojis[$wp['badge_icon']] ?? '&#11088;'; ?></span>
                                <span class="wall-name"><?php echo htmlspecialchars(($wp['r_first'] ?? '') . ' ' . ($wp['r_last'] ?? '')); ?></span>
                            </div>
                            <div class="wall-message"><?php echo htmlspecialchars(mb_strimwidth($wp['message'], 0, 100, '...')); ?></div>
                            <div class="wall-footer">
                                <span><?php if (!$wp['is_system_generated']): ?>From: <?php echo htmlspecialchars(($wp['g_first'] ?? '') . ' ' . ($wp['g_last'] ?? '')); ?><?php else: ?>System<?php endif; ?></span>
                                <span><?php echo date('M d, h:i A', strtotime($wp['created_at'])); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <a href="recognition-wall.php" class="spa-link" style="display:block;text-align:center;color:#f59e0b;padding:0.75rem;font-size:0.85rem;text-decoration:none;">See All &rarr;</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <h2><i data-lucide="info"></i>Employee Information</h2>
                <div class="info-grid">
                    <div class="info-item"><div class="info-label">Full Name</div><div class="info-value"><?php echo htmlspecialchars($user_name); ?></div></div>
                    <div class="info-item"><div class="info-label">Email</div><div class="info-value"><?php echo htmlspecialchars($_SESSION['personal_email'] ?? 'N/A'); ?></div></div>
                    <div class="info-item"><div class="info-label">Department</div><div class="info-value"><?php echo htmlspecialchars($employee['department_name'] ?? 'N/A'); ?></div></div>
                    <div class="info-item"><div class="info-label">Position</div><div class="info-value"><?php echo htmlspecialchars($employee['position_name'] ?? 'N/A'); ?></div></div>
                </div>
            </div>
<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
</div>
