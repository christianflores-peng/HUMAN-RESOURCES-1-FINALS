<?php
require_once '../../includes/session_helper.php';
require_once '../../includes/spa_helper.php';
startSecureSession();
$is_ajax = is_spa_ajax();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Admin') {
    header('Location: ../../index.php');
    exit();
}

if (!$is_ajax) {
    header('Location: index.php?page=dashboard');
    exit();
}

require_once '../../database/config.php';

$user_id = $_SESSION['user_id'];

// === SYSTEM OVERVIEW STATS ===
try {
    $total_users = fetchSingle("SELECT COUNT(*) as count FROM user_accounts")['count'] ?? 0;
    $active_users = fetchSingle("SELECT COUNT(*) as count FROM user_accounts WHERE status = 'Active'")['count'] ?? 0;
    $inactive_users = fetchSingle("SELECT COUNT(*) as count FROM user_accounts WHERE status != 'Active'")['count'] ?? 0;
} catch (Exception $e) {
    $total_users = 0; $active_users = 0; $inactive_users = 0;
}

try {
    $total_applications = fetchSingle("SELECT COUNT(*) as count FROM job_applications")['count'] ?? 0;
    $active_postings = fetchSingle("SELECT COUNT(*) as count FROM job_postings WHERE status = 'Open'")['count'] ?? 0;
    $total_hired = fetchSingle("SELECT COUNT(*) as count FROM job_applications WHERE status = 'Hired'")['count'] ?? 0;
} catch (Exception $e) {
    $total_applications = 0; $active_postings = 0; $total_hired = 0;
}

try {
    $recent_logs = fetchSingle("SELECT COUNT(*) as count FROM application_status_history WHERE DATE(changed_at) = CURDATE()")['count'] ?? 0;
} catch (Exception $e) {
    $recent_logs = 0;
}

// === TIME-TO-HIRE ANALYTICS ===
try {
    $avg_time_to_hire = fetchSingle("
        SELECT ROUND(AVG(DATEDIFF(
            COALESCE(ash_hired.changed_at, ja.updated_at),
            ja.applied_at
        ))) as avg_days
        FROM job_applications ja
        LEFT JOIN application_status_history ash_hired ON ash_hired.application_id = ja.id AND ash_hired.new_status = 'Hired'
        WHERE ja.status = 'Hired' AND ja.applied_at IS NOT NULL
    ")['avg_days'] ?? 0;
} catch (Exception $e) {
    $avg_time_to_hire = 0;
}

try {
    $time_to_hire_by_dept = fetchAll("
        SELECT d.department_name,
            ROUND(AVG(DATEDIFF(
                COALESCE(ash_hired.changed_at, ja.updated_at),
                ja.applied_at
            ))) as avg_days,
            COUNT(ja.id) as hire_count
        FROM job_applications ja
        LEFT JOIN application_status_history ash_hired ON ash_hired.application_id = ja.id AND ash_hired.new_status = 'Hired'
        LEFT JOIN job_postings jp ON jp.id = ja.job_posting_id
        LEFT JOIN departments d ON d.id = jp.department_id
        WHERE ja.status = 'Hired' AND ja.applied_at IS NOT NULL AND d.department_name IS NOT NULL
        GROUP BY d.id, d.department_name
        ORDER BY avg_days DESC
        LIMIT 6
    ");
} catch (Exception $e) {
    $time_to_hire_by_dept = [];
}

// === SOURCING EFFECTIVENESS ===
try {
    $source_stats = fetchAll("
        SELECT 
            COALESCE(ja.source, 'Direct Apply') as source,
            COUNT(*) as total,
            SUM(CASE WHEN ja.status = 'Hired' THEN 1 ELSE 0 END) as hired
        FROM job_applications ja
        GROUP BY COALESCE(ja.source, 'Direct Apply')
        ORDER BY total DESC
        LIMIT 5
    ");
} catch (Exception $e) {
    $source_stats = [];
}

// === ATTRITION RATE (Probationary) ===
try {
    $probation_total = fetchSingle("
        SELECT COUNT(*) as count FROM user_accounts ua
        JOIN roles r ON r.id = ua.role_id
        WHERE r.role_type = 'Employee'
        AND ua.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    ")['count'] ?? 0;
    
    $probation_left = fetchSingle("
        SELECT COUNT(*) as count FROM user_accounts ua
        JOIN roles r ON r.id = ua.role_id
        WHERE r.role_type = 'Employee'
        AND ua.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        AND ua.status IN ('Inactive', 'Archived')
    ")['count'] ?? 0;
    
    $attrition_rate = $probation_total > 0 ? round(($probation_left / $probation_total) * 100, 1) : 0;
} catch (Exception $e) {
    $probation_total = 0; $probation_left = 0; $attrition_rate = 0;
}

// === PIPELINE SUMMARY ===
try {
    $pipeline = fetchAll("
        SELECT ja.status, COUNT(*) as count
        FROM job_applications ja
        GROUP BY ja.status
        ORDER BY FIELD(ja.status, 'New Apply', 'Screening', 'Interview', 'Road Test', 'Offer Sent', 'Hired', 'Rejected')
    ");
} catch (Exception $e) {
    $pipeline = [];
}

// === SLOW EVALUATORS (Managers with pending reviews) ===
try {
    $slow_evaluators = fetchAll("
        SELECT ua.first_name, ua.last_name, d.department_name,
            COUNT(pr.id) as pending_reviews,
            MIN(pr.created_at) as oldest_pending
        FROM performance_reviews pr
        JOIN user_accounts ua ON ua.id = pr.reviewer_id
        LEFT JOIN departments d ON d.id = ua.department_id
        WHERE pr.status = 'Pending'
        GROUP BY ua.id, ua.first_name, ua.last_name, d.department_name
        HAVING pending_reviews > 0
        ORDER BY oldest_pending ASC
        LIMIT 5
    ");
} catch (Exception $e) {
    $slow_evaluators = [];
}

// === RECENT ACTIVITY ===
try {
    $recent_activities = fetchAll("
        SELECT ash.*, ja.first_name, ja.last_name, ua.first_name as changed_by_first, ua.last_name as changed_by_last
        FROM application_status_history ash
        LEFT JOIN job_applications ja ON ja.id = ash.application_id
        LEFT JOIN user_accounts ua ON ua.id = ash.changed_by
        ORDER BY ash.changed_at DESC
        LIMIT 8
    ");
} catch (Exception $e) {
    $recent_activities = [];
}

// === USER DISTRIBUTION ===
try {
    $role_distribution = fetchAll("
        SELECT r.role_name, r.role_type, COUNT(ua.id) as count
        FROM roles r
        LEFT JOIN user_accounts ua ON ua.role_id = r.id
        GROUP BY r.id, r.role_name, r.role_type
        ORDER BY count DESC
    ");
} catch (Exception $e) {
    $role_distribution = [];
}

// === MONTHLY HIRES (last 6 months) ===
try {
    $monthly_hires = fetchAll("
        SELECT DATE_FORMAT(ua.created_at, '%Y-%m') as month_key,
               DATE_FORMAT(ua.created_at, '%b %Y') as month_label,
               COUNT(*) as count
        FROM user_accounts ua
        JOIN roles r ON r.id = ua.role_id
        WHERE r.role_type = 'Employee'
        AND ua.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month_key, month_label
        ORDER BY month_key ASC
    ");
} catch (Exception $e) {
    $monthly_hires = [];
}

// === PENDING ONBOARDING ===
try {
    $pending_onboarding = fetchSingle("
        SELECT COUNT(DISTINCT ua.id) as count
        FROM user_accounts ua
        JOIN roles r ON r.id = ua.role_id
        LEFT JOIN employee_onboarding_progress eop ON eop.user_id = ua.id AND eop.status = 'Completed'
        LEFT JOIN onboarding_tasks ot ON ot.id = eop.task_id
        WHERE r.role_type = 'Employee'
        AND ua.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        AND ua.status = 'Active'
    ")['count'] ?? 0;
} catch (Exception $e) {
    $pending_onboarding = 0;
}
?>
<div data-page-title="Admin Dashboard">
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
            <div class="header">
                <div>
                    <h1>Admin Dashboard</h1>
                    <p>System overview and recent activities</p>
                </div>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card users">
                    <div class="icon">
                        <i data-lucide="users"></i>
                    </div>
                    <h3><?php echo number_format($total_users); ?></h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-card active">
                    <div class="icon">
                        <i data-lucide="check-circle"></i>
                    </div>
                    <h3><?php echo number_format($active_users); ?></h3>
                    <p>Active Users</p>
                </div>
                <div class="stat-card inactive">
                    <div class="icon">
                        <i data-lucide="alert-triangle"></i>
                    </div>
                    <h3><?php echo number_format($inactive_users); ?></h3>
                    <p>Inactive Users</p>
                </div>
                <div class="stat-card applications">
                    <div class="icon">
                        <i data-lucide="file-text"></i>
                    </div>
                    <h3><?php echo number_format($total_applications); ?></h3>
                    <p>Total Applications</p>
                </div>
                <div class="stat-card logs">
                    <div class="icon">
                        <i data-lucide="history"></i>
                    </div>
                    <h3><?php echo number_format($recent_logs); ?></h3>
                    <p>Today's Activities</p>
                </div>
            </div>

            <div class="grid-2">
                <div class="card">
                    <h2>
                        <i data-lucide="bell"></i>
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
                        <i data-lucide="pie-chart"></i>
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
<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
</div>
