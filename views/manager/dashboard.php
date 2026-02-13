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
$user = getUserWithRole($userId);

if (!$user || !in_array($user['role_type'] ?? '', ['Manager', 'Admin', 'HR_Staff'])) {
    header('Location: ../../index.php');
    exit();
}

if (!$is_ajax) {
    header('Location: index.php?page=dashboard');
    exit();
}

require_once '../../database/config.php';

$departmentId = $user['department_id'] ?? null;
$departmentName = $user['department_name'] ?? 'All Departments';

// Get team members
$teamMembers = [];
try {
    $teamMembers = getAccessibleEmployees($userId, ['status' => null]);
} catch (Exception $e) { $teamMembers = []; }

// Stats
$teamCount = count($teamMembers);

$expiringLicenses = 0;
try {
    if ($departmentId) {
        $result = fetchSingle("SELECT COUNT(*) as c FROM employee_documents ed JOIN user_accounts ua ON ed.user_id = ua.id WHERE ua.department_id = ? AND ed.document_type = 'License' AND ed.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)", [$departmentId]);
    } else {
        $result = fetchSingle("SELECT COUNT(*) as c FROM employee_documents ed JOIN user_accounts ua ON ed.user_id = ua.id WHERE ed.document_type = 'License' AND ed.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
    }
    $expiringLicenses = $result['c'] ?? 0;
} catch (Exception $e) { $expiringLicenses = 0; }

$probationCount = count(array_filter($teamMembers, fn($m) => ($m['employment_status'] ?? '') === 'Probation'));

// Active job postings
$activePostings = 0;
try {
    $activePostings = fetchSingle("SELECT COUNT(*) as c FROM job_postings WHERE status = 'Open'")['c'] ?? 0;
} catch (Exception $e) {}

// Total applicants
$totalApplicants = 0;
try {
    $totalApplicants = fetchSingle("SELECT COUNT(*) as c FROM job_applications")['c'] ?? 0;
} catch (Exception $e) {}

// Recent activities
$recentActivities = [];
try {
    $recentActivities = fetchAll("
        SELECT ash.*, ja.first_name, ja.last_name, ua.first_name as changed_by_first, ua.last_name as changed_by_last
        FROM application_status_history ash
        LEFT JOIN job_applications ja ON ja.id = ash.application_id
        LEFT JOIN user_accounts ua ON ua.id = ash.changed_by
        ORDER BY ash.changed_at DESC LIMIT 8
    ");
} catch (Exception $e) { $recentActivities = []; }
?>
<div data-page-title="Manager Dashboard">
<style>
        .header {
            background: rgba(30, 41, 54, 0.6);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 { font-size: 1.5rem; color: #e2e8f0; margin-bottom: 0.25rem; }
        .header p { color: #94a3b8; font-size: 0.9rem; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(30, 41, 54, 0.6);
            border-radius: 12px;
            padding: 1.25rem;
            border: 1px solid rgba(58, 69, 84, 0.5);
            transition: transform 0.2s;
        }

        .stat-card:hover { transform: translateY(-2px); }

        .stat-card .icon {
            width: 45px; height: 45px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 0.75rem;
        }

        .stat-card.team .icon { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
        .stat-card.alert .icon { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .stat-card.probation .icon { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .stat-card.postings .icon { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .stat-card.applicants .icon { background: rgba(139, 92, 246, 0.2); color: #8b5cf6; }

        .stat-card h3 { font-size: 1.75rem; color: #e2e8f0; margin-bottom: 0.15rem; }
        .stat-card p { font-size: 0.8rem; color: #94a3b8; }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1.25rem;
            background: rgba(30, 41, 54, 0.6);
            border: 1px solid rgba(58, 69, 84, 0.5);
            border-radius: 12px;
            color: #e2e8f0;
            text-decoration: none;
            transition: all 0.3s;
        }

        .quick-action-btn:hover {
            background: rgba(245, 158, 11, 0.1);
            border-color: #f59e0b;
        }

        .quick-action-btn i { color: #f59e0b; }
        .quick-action-btn strong { font-size: 0.95rem; }
        .quick-action-btn p { color: #94a3b8; font-size: 0.8rem; margin-top: 0.15rem; }

        .grid-2 {
            display: grid;
            grid-template-columns: 3fr 2fr;
            gap: 1.5rem;
        }

        .card {
            background: rgba(30, 41, 54, 0.6);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(58, 69, 84, 0.5);
            margin-bottom: 1.5rem;
        }

        .card h2 {
            font-size: 1.15rem;
            color: #e2e8f0;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .team-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: rgba(15, 23, 42, 0.6);
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .team-item .name { color: #e2e8f0; font-weight: 500; font-size: 0.9rem; }
        .team-item .role { color: #94a3b8; font-size: 0.8rem; }

        .status-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 8px;
            font-size: 0.72rem;
            font-weight: 600;
        }

        .status-badge.active { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .status-badge.probation { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }

        .activity-item {
            padding: 0.875rem;
            background: rgba(15, 23, 42, 0.6);
            border-radius: 8px;
            margin-bottom: 0.6rem;
            border-left: 3px solid #f59e0b;
        }

        .activity-item .title { color: #e2e8f0; font-size: 0.9rem; font-weight: 500; }
        .activity-item .detail { color: #94a3b8; font-size: 0.8rem; margin-top: 0.15rem; }
        .activity-item .time { color: #64748b; font-size: 0.75rem; margin-top: 0.15rem; }

        @media (max-width: 1024px) { .grid-2 { grid-template-columns: 1fr; } }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .quick-actions { grid-template-columns: 1fr; }
        }
    </style>
            <div class="header">
                <div>
                    <h1>Manager Dashboard</h1>
                    <p><?php echo htmlspecialchars($departmentName); ?> - Team overview</p>
                </div>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                    <div style="text-align: right;">
                        <p style="color: #e2e8f0; font-weight: 500;"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></p>
                        <p style="color: #f59e0b; font-size: 0.8rem;"><?php echo date('l, F j, Y'); ?></p>
                    </div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card team">
                    <div class="icon"><i data-lucide="users"></i></div>
                    <h3><?php echo $teamCount; ?></h3>
                    <p>Team Members</p>
                </div>
                <div class="stat-card alert">
                    <div class="icon"><i data-lucide="alert-triangle"></i></div>
                    <h3><?php echo $expiringLicenses; ?></h3>
                    <p>Expiring Licenses</p>
                </div>
                <div class="stat-card probation">
                    <div class="icon"><i data-lucide="clipboard-check"></i></div>
                    <h3><?php echo $probationCount; ?></h3>
                    <p>Pending Reviews</p>
                </div>
                <div class="stat-card postings">
                    <div class="icon"><i data-lucide="briefcase"></i></div>
                    <h3><?php echo $activePostings; ?></h3>
                    <p>Active Postings</p>
                </div>
                <div class="stat-card applicants">
                    <div class="icon"><i data-lucide="file-text"></i></div>
                    <h3><?php echo $totalApplicants; ?></h3>
                    <p>Total Applicants</p>
                </div>
            </div>

            <div class="quick-actions">
                <a href="job-requisitions.php" class="quick-action-btn spa-link">
                    <i data-lucide="file-plus"></i>
                    <div>
                        <strong>Job Requisitions</strong>
                        <p>Request new staff for your dept</p>
                    </div>
                </a>
                <a href="interview-panel.php" class="quick-action-btn spa-link">
                    <i data-lucide="message-square"></i>
                    <div>
                        <strong>Interview Panel</strong>
                        <p>Conduct interviews & feedback</p>
                    </div>
                </a>
                <a href="my-team.php" class="quick-action-btn spa-link">
                    <i data-lucide="users"></i>
                    <div>
                        <strong>My Team</strong>
                        <p>View team members & documents</p>
                    </div>
                </a>
                <a href="performance-reviews.php" class="quick-action-btn spa-link">
                    <i data-lucide="clipboard-check"></i>
                    <div>
                        <strong>Performance Reviews</strong>
                        <p>Evaluate probationary employees</p>
                    </div>
                </a>
                <a href="goal-setting.php" class="quick-action-btn spa-link">
                    <i data-lucide="target"></i>
                    <div>
                        <strong>Goal Setting</strong>
                        <p>Set probation targets</p>
                    </div>
                </a>
                <a href="handbook.php" class="quick-action-btn spa-link">
                    <i data-lucide="book-open"></i>
                    <div>
                        <strong>Handbook</strong>
                        <p>Share policy documents</p>
                    </div>
                </a>
            </div>

            <div class="grid-2">
                <div class="card">
                    <h2><i data-lucide="id-card"></i> Team Members</h2>
                    <?php if (empty($teamMembers)): ?>
                        <p style="color: #94a3b8; text-align: center; padding: 2rem;">No team members found</p>
                    <?php else: ?>
                        <?php foreach (array_slice($teamMembers, 0, 8) as $member): ?>
                        <div class="team-item">
                            <div>
                                <div class="name"><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></div>
                                <div class="role"><?php echo htmlspecialchars($member['job_title'] ?? $member['role_name'] ?? 'Employee'); ?></div>
                            </div>
                            <span class="status-badge <?php echo strtolower($member['employment_status'] ?? 'active'); ?>">
                                <?php echo htmlspecialchars($member['employment_status'] ?? 'Active'); ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (count($teamMembers) > 8): ?>
                        <a href="my-team.php" style="display:block;text-align:center;color:#f59e0b;padding:0.75rem;font-size:0.85rem;text-decoration:none;">View all <?php echo count($teamMembers); ?> members &rarr;</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h2><i data-lucide="bell"></i> Recent Activity</h2>
                    <?php if (empty($recentActivities)): ?>
                        <p style="color: #94a3b8; text-align: center; padding: 2rem;">No recent activities</p>
                    <?php else: ?>
                        <?php foreach (array_slice($recentActivities, 0, 6) as $activity): ?>
                        <div class="activity-item">
                            <div class="title"><?php echo htmlspecialchars(($activity['first_name'] ?? '') . ' ' . ($activity['last_name'] ?? '')); ?></div>
                            <div class="detail">Status: <?php echo htmlspecialchars($activity['new_status'] ?? 'N/A'); ?></div>
                            <div class="time"><?php echo date('M d, g:i A', strtotime($activity['changed_at'])); ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
<script>
    if (typeof lucide !== 'undefined') { lucide.createIcons(); }
</script>
</div>
