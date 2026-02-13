<?php
require_once '../../includes/session_helper.php';
require_once '../../includes/rbac_helper.php';
require_once '../../includes/spa_helper.php';
startSecureSession();
$is_ajax = is_spa_ajax();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit();
}

$userId = $_SESSION['user_id'];

if (!isHRStaff($userId) && !isAdmin($userId)) {
    header('Location: ../../index.php');
    exit();
}

if (!$is_ajax) {
    header('Location: index.php?page=onboarding-tracker');
    exit();
}

require_once '../../database/config.php';

// Fetch recently hired employees (last 90 days) for onboarding tracking
try {
    $newHires = fetchAll("
        SELECT ua.id, ua.first_name, ua.last_name, ua.company_email, ua.employee_id, ua.created_at, ua.status,
               d.department_name, r.role_name
        FROM user_accounts ua
        LEFT JOIN departments d ON ua.department_id = d.id
        LEFT JOIN roles r ON ua.role_id = r.id
        WHERE ua.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
        ORDER BY ua.created_at DESC
    ");
} catch (Exception $e) {
    $newHires = [];
}

// Onboarding checklist items
$checklistItems = [
    'Account Created',
    'Company Email Assigned',
    'Department Assigned',
    'Documents Submitted',
    'Orientation Completed'
];

// Pre-fetch document submission counts and onboarding progress per new hire
$docCounts = [];
$onboardingProgress = [];
foreach ($newHires as $hire) {
    $hireId = $hire['id'];
    try {
        $row = fetchSingle("SELECT COUNT(*) as cnt FROM employee_requirements WHERE user_id = ?", [$hireId]);
        $docCounts[$hireId] = (int)($row['cnt'] ?? 0);
    } catch (Exception $e) {
        $docCounts[$hireId] = 0;
    }
    try {
        $row = fetchSingle("
            SELECT COUNT(*) as total,
                   SUM(CASE WHEN eop.status = 'Completed' THEN 1 ELSE 0 END) as completed
            FROM employee_onboarding_progress eop
            WHERE eop.user_id = ?
        ", [$hireId]);
        $onboardingProgress[$hireId] = [
            'total' => (int)($row['total'] ?? 0),
            'completed' => (int)($row['completed'] ?? 0)
        ];
    } catch (Exception $e) {
        $onboardingProgress[$hireId] = ['total' => 0, 'completed' => 0];
    }
}
?>
<div data-page-title="Onboarding Management">
<style>
        .header {
            background: rgba(30, 41, 54, 0.6);
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 { font-size: 1.35rem; color: #e2e8f0; margin-bottom: 0.25rem; }
        .header p { color: #94a3b8; font-size: 0.85rem; }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: rgba(30, 41, 54, 0.6);
            border-radius: 12px;
            padding: 1.25rem;
            border: 1px solid rgba(58, 69, 84, 0.5);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 45px; height: 45px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }

        .stat-icon.blue { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
        .stat-icon.green { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .stat-icon.amber { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }

        .stat-value { font-size: 1.5rem; font-weight: 700; color: #e2e8f0; }
        .stat-label { font-size: 0.8rem; color: #94a3b8; }

        .onboarding-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.25rem;
        }

        .onboarding-card {
            background: rgba(30, 41, 54, 0.6);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(58, 69, 84, 0.5);
        }

        .onboarding-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.75rem;
        }

        .onboarding-name { font-weight: 600; color: #e2e8f0; font-size: 1rem; }
        .onboarding-role { font-size: 0.85rem; color: #0ea5e9; }

        .onboarding-badge {
            padding: 0.25rem 0.6rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .onboarding-badge.active { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .onboarding-badge.pending { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }

        .onboarding-details {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 1rem;
        }

        .onboarding-details span {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 0.3rem;
        }

        .onboarding-details i { width: 14px; height: 14px; }

        .checklist { margin-top: 0.75rem; }

        .checklist-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0;
            font-size: 0.85rem;
            color: #cbd5e1;
        }

        .checklist-item i { width: 16px; height: 16px; }
        .checklist-item.done { color: #10b981; }
        .checklist-item.done i { color: #10b981; }
        .checklist-item.pending i { color: #64748b; }

        .progress-bar-container {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 4px;
            height: 8px;
            overflow: hidden;
            margin-top: 0.75rem;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #0ea5e9);
            transition: width 0.3s;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-top: 0.4rem;
            font-size: 0.78rem;
            color: #94a3b8;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #94a3b8;
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .stats-row { grid-template-columns: 1fr; }
            .onboarding-grid { grid-template-columns: 1fr; }
        }
    </style>
            <div class="header">
                <div>
                    <h1>Onboarding Management</h1>
                    <p>Track new hire onboarding progress (last 90 days)</p>
                </div>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                </div>
            </div>

            <?php
                $totalNew = count($newHires);
                $activeCount = count(array_filter($newHires, fn($h) => ($h['status'] ?? '') === 'Active'));
                $pendingCount = $totalNew - $activeCount;
            ?>
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon blue"><i data-lucide="user-plus"></i></div>
                    <div>
                        <div class="stat-value"><?php echo $totalNew; ?></div>
                        <div class="stat-label">New Hires (90 days)</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i data-lucide="check-circle"></i></div>
                    <div>
                        <div class="stat-value"><?php echo $activeCount; ?></div>
                        <div class="stat-label">Active / Onboarded</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon amber"><i data-lucide="clock"></i></div>
                    <div>
                        <div class="stat-value"><?php echo $pendingCount; ?></div>
                        <div class="stat-label">Pending Onboarding</div>
                    </div>
                </div>
            </div>

            <?php if (empty($newHires)): ?>
                <div class="empty-state">
                    <i data-lucide="clipboard-check" style="width:3rem;height:3rem;margin-bottom:1rem;"></i>
                    <p>No new hires in the last 90 days</p>
                </div>
            <?php else: ?>
            <div class="onboarding-grid">
                <?php foreach ($newHires as $hire): ?>
                <?php
                    $hireId = $hire['id'];
                    $hasEmail = !empty($hire['company_email']);
                    $hasDept = !empty($hire['department_name']);
                    // Real data: check if employee has submitted at least 1 document
                    $docsSubmitted = ($docCounts[$hireId] ?? 0) > 0;
                    // Real data: check if onboarding orientation tasks are completed
                    $obProgress = $onboardingProgress[$hireId] ?? ['total' => 0, 'completed' => 0];
                    $orientationDone = $obProgress['total'] > 0 && $obProgress['completed'] >= $obProgress['total'];
                    // Calculate checklist completion
                    $completed = 1; // Account Created is always done
                    if ($hasEmail) $completed++;
                    if ($hasDept) $completed++;
                    if ($docsSubmitted) $completed++;
                    if ($orientationDone) $completed++;
                    $totalChecklist = count($checklistItems);
                    $pct = round(($completed / $totalChecklist) * 100);
                ?>
                <div class="onboarding-card">
                    <div class="onboarding-header">
                        <div>
                            <div class="onboarding-name"><?php echo htmlspecialchars($hire['first_name'] . ' ' . $hire['last_name']); ?></div>
                            <div class="onboarding-role"><?php echo htmlspecialchars($hire['role_name'] ?? 'N/A'); ?></div>
                        </div>
                        <span class="onboarding-badge <?php echo $pct >= 100 ? 'active' : 'pending'; ?>">
                            <?php echo $pct >= 100 ? 'Completed' : 'In Progress'; ?>
                        </span>
                    </div>
                    <div class="onboarding-details">
                        <span><i data-lucide="building-2"></i> <?php echo htmlspecialchars($hire['department_name'] ?? 'Unassigned'); ?></span>
                        <span><i data-lucide="calendar"></i> Hired: <?php echo date('M d, Y', strtotime($hire['created_at'])); ?></span>
                        <?php if ($hasEmail): ?>
                        <span><i data-lucide="mail"></i> <?php echo htmlspecialchars($hire['company_email']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="checklist">
                        <div class="checklist-item done"><i data-lucide="check-circle"></i> Account Created</div>
                        <div class="checklist-item <?php echo $hasEmail ? 'done' : 'pending'; ?>"><i data-lucide="<?php echo $hasEmail ? 'check-circle' : 'circle'; ?>"></i> Company Email Assigned</div>
                        <div class="checklist-item <?php echo $hasDept ? 'done' : 'pending'; ?>"><i data-lucide="<?php echo $hasDept ? 'check-circle' : 'circle'; ?>"></i> Department Assigned</div>
                        <div class="checklist-item <?php echo $docsSubmitted ? 'done' : 'pending'; ?>"><i data-lucide="<?php echo $docsSubmitted ? 'check-circle' : 'circle'; ?>"></i> Documents Submitted</div>
                        <div class="checklist-item <?php echo $orientationDone ? 'done' : 'pending'; ?>"><i data-lucide="<?php echo $orientationDone ? 'check-circle' : 'circle'; ?>"></i> Orientation Completed</div>
                    </div>

                    <div class="progress-bar-container">
                        <div class="progress-bar-fill" style="width: <?php echo $pct; ?>%;"></div>
                    </div>
                    <div class="progress-label">
                        <span><?php echo $completed; ?> of <?php echo $totalChecklist; ?> tasks</span>
                        <span><?php echo $pct; ?>%</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
<script>
    if (typeof lucide !== 'undefined') { lucide.createIcons(); }
</script>
</div>
