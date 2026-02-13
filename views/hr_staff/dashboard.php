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
    header('Location: index.php?page=dashboard');
    exit();
}

require_once '../../database/config.php';

// Fetch HR statistics
try {
    $total_applicants = fetchSingle("SELECT COUNT(*) as count FROM job_applications")['count'] ?? 0;
} catch (Exception $e) { $total_applicants = 0; }

try {
    $new_today = fetchSingle("SELECT COUNT(*) as count FROM job_applications WHERE DATE(applied_date) = CURDATE()")['count'] ?? 0;
} catch (Exception $e) { $new_today = 0; }

try {
    $pending_interviews = fetchSingle("SELECT COUNT(*) as count FROM job_applications WHERE status IN ('interview', 'for interview')")['count'] ?? 0;
} catch (Exception $e) { $pending_interviews = 0; }

try {
    $active_postings = fetchSingle("SELECT COUNT(*) as count FROM job_postings WHERE status = 'Open'")['count'] ?? 0;
} catch (Exception $e) { $active_postings = 0; }

try {
    $hired_count = fetchSingle("SELECT COUNT(*) as count FROM job_applications WHERE status IN ('hired', 'accepted')")['count'] ?? 0;
} catch (Exception $e) { $hired_count = 0; }

try {
    $total_employees = fetchSingle("SELECT COUNT(*) as count FROM user_accounts WHERE status = 'Active'")['count'] ?? 0;
} catch (Exception $e) { $total_employees = 0; }

// Recent applications
try {
    $recent_applications = fetchAll("
        SELECT ja.*, jp.title as job_title, d.department_name
        FROM job_applications ja
        LEFT JOIN job_postings jp ON ja.job_posting_id = jp.id
        LEFT JOIN departments d ON jp.department_id = d.id
        ORDER BY ja.applied_date DESC
        LIMIT 10
    ");
} catch (Exception $e) { $recent_applications = []; }

// Recent hires
try {
    $recent_hires = fetchAll("
        SELECT ua.first_name, ua.last_name, ua.company_email, ua.created_at, d.department_name, r.role_name
        FROM user_accounts ua
        LEFT JOIN departments d ON ua.department_id = d.id
        LEFT JOIN roles r ON ua.role_id = r.id
        WHERE ua.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY ua.created_at DESC
        LIMIT 8
    ");
} catch (Exception $e) { $recent_hires = []; }

// Pipeline summary
try {
    $pipeline = fetchAll("
        SELECT status, COUNT(*) as count
        FROM job_applications
        GROUP BY status
        ORDER BY count DESC
    ");
} catch (Exception $e) { $pipeline = []; }
?>
<div data-page-title="HR Dashboard">
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

        .stat-card.applicants .icon { background: rgba(139, 92, 246, 0.2); color: #8b5cf6; }
        .stat-card.new-today .icon { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
        .stat-card.interviews .icon { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .stat-card.postings .icon { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .stat-card.hired .icon { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .stat-card.employees .icon { background: rgba(236, 72, 153, 0.2); color: #ec4899; }

        .stat-card h3 { font-size: 1.75rem; color: #e2e8f0; margin-bottom: 0.15rem; }
        .stat-card p { font-size: 0.8rem; color: #94a3b8; }

        .grid-2 {
            display: grid;
            grid-template-columns: 2fr 1fr;
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

        .app-item {
            padding: 0.875rem;
            background: rgba(15, 23, 42, 0.6);
            border-radius: 8px;
            margin-bottom: 0.6rem;
            border-left: 3px solid #8b5cf6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .app-item-info .name { color: #e2e8f0; font-weight: 500; font-size: 0.9rem; }
        .app-item-info .detail { color: #94a3b8; font-size: 0.8rem; margin-top: 0.15rem; }

        .app-status {
            padding: 0.25rem 0.6rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .app-status.new { background: rgba(99, 102, 241, 0.2); color: #818cf8; }
        .app-status.screening { background: rgba(139, 92, 246, 0.2); color: #a78bfa; }
        .app-status.interview { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
        .app-status.hired { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .app-status.offer_sent { background: rgba(14, 165, 233, 0.2); color: #38bdf8; }
        .app-status.road_test { background: rgba(239, 68, 68, 0.2); color: #f87171; }

        .pipeline-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.65rem 0.75rem;
            background: rgba(15, 23, 42, 0.6);
            border-radius: 8px;
            margin-bottom: 0.4rem;
        }

        .pipeline-item .label { color: #e2e8f0; font-size: 0.9rem; text-transform: capitalize; }

        .pipeline-item .count {
            background: rgba(14, 165, 233, 0.2);
            color: #0ea5e9;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .hire-item {
            padding: 0.75rem;
            background: rgba(15, 23, 42, 0.6);
            border-radius: 8px;
            margin-bottom: 0.5rem;
            border-left: 3px solid #10b981;
        }

        .hire-item .name { color: #e2e8f0; font-weight: 500; font-size: 0.9rem; }
        .hire-item .detail { color: #94a3b8; font-size: 0.8rem; margin-top: 0.15rem; }

        .quick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            background: rgba(30, 41, 54, 0.6);
            border: 1px solid rgba(58, 69, 84, 0.5);
            border-radius: 12px;
            color: #e2e8f0;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .quick-action-btn:hover {
            background: rgba(14, 165, 233, 0.1);
            border-color: #0ea5e9;
        }

        .quick-action-btn i { color: #0ea5e9; }

        @media (max-width: 1024px) {
            .grid-2 { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .quick-actions { grid-template-columns: 1fr; }
        }
    </style>
            <div class="header">
                <div>
                    <h1>HR Dashboard</h1>
                    <p>Recruitment overview and quick actions</p>
                </div>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                    <div style="text-align: right;">
                        <p style="color: #e2e8f0; font-weight: 500;"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></p>
                        <p style="color: #0ea5e9; font-size: 0.8rem;"><?php echo date('l, F j, Y'); ?></p>
                    </div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card applicants">
                    <div class="icon"><i data-lucide="users"></i></div>
                    <h3><?php echo number_format($total_applicants); ?></h3>
                    <p>Total Applicants</p>
                </div>
                <div class="stat-card new-today">
                    <div class="icon"><i data-lucide="sparkles"></i></div>
                    <h3><?php echo number_format($new_today); ?></h3>
                    <p>New Today</p>
                </div>
                <div class="stat-card interviews">
                    <div class="icon"><i data-lucide="calendar"></i></div>
                    <h3><?php echo number_format($pending_interviews); ?></h3>
                    <p>Pending Interviews</p>
                </div>
                <div class="stat-card postings">
                    <div class="icon"><i data-lucide="briefcase"></i></div>
                    <h3><?php echo number_format($active_postings); ?></h3>
                    <p>Active Postings</p>
                </div>
                <div class="stat-card hired">
                    <div class="icon"><i data-lucide="user-check"></i></div>
                    <h3><?php echo number_format($hired_count); ?></h3>
                    <p>Total Hired</p>
                </div>
                <div class="stat-card employees">
                    <div class="icon"><i data-lucide="building-2"></i></div>
                    <h3><?php echo number_format($total_employees); ?></h3>
                    <p>Active Employees</p>
                </div>
            </div>

            <div class="quick-actions">
                <a href="job-requisitions.php" class="quick-action-btn spa-link">
                    <i data-lucide="file-plus"></i>
                    <div>
                        <strong>Job Requisitions</strong>
                        <p style="color: #94a3b8; font-size: 0.8rem;">Approve manager requests</p>
                    </div>
                </a>
                <a href="applicant-screening.php" class="quick-action-btn spa-link">
                    <i data-lucide="filter"></i>
                    <div>
                        <strong>Applicant Screening</strong>
                        <p style="color: #94a3b8; font-size: 0.8rem;">Screen & shortlist candidates</p>
                    </div>
                </a>
                <a href="interview-management.php" class="quick-action-btn spa-link">
                    <i data-lucide="calendar-check"></i>
                    <div>
                        <strong>Interview Management</strong>
                        <p style="color: #94a3b8; font-size: 0.8rem;">Schedule & track interviews</p>
                    </div>
                </a>
                <a href="recruitment-pipeline.php" class="quick-action-btn spa-link">
                    <i data-lucide="kanban"></i>
                    <div>
                        <strong>Recruitment Pipeline</strong>
                        <p style="color: #94a3b8; font-size: 0.8rem;">Manage applicant pipeline</p>
                    </div>
                </a>
                <a href="onboarding-tracker.php" class="quick-action-btn spa-link">
                    <i data-lucide="clipboard-list"></i>
                    <div>
                        <strong>Onboarding Tracker</strong>
                        <p style="color: #94a3b8; font-size: 0.8rem;">Track new hire onboarding</p>
                    </div>
                </a>
                <a href="documents.php" class="quick-action-btn spa-link">
                    <i data-lucide="folder-check"></i>
                    <div>
                        <strong>Document Verification</strong>
                        <p style="color: #94a3b8; font-size: 0.8rem;">Verify employee documents</p>
                    </div>
                </a>
            </div>

            <div class="grid-2">
                <div class="card">
                    <h2><i data-lucide="inbox"></i> Recent Applications</h2>
                    <?php if (empty($recent_applications)): ?>
                        <p style="color: #94a3b8; text-align: center; padding: 2rem;">No applications yet</p>
                    <?php else: ?>
                        <?php foreach (array_slice($recent_applications, 0, 8) as $app): ?>
                        <div class="app-item">
                            <div class="app-item-info">
                                <div class="name"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></div>
                                <div class="detail"><?php echo htmlspecialchars($app['job_title'] ?? 'N/A'); ?> &bull; <?php echo htmlspecialchars($app['department_name'] ?? 'N/A'); ?> &bull; <?php echo date('M d', strtotime($app['applied_date'])); ?></div>
                            </div>
                            <?php
                                $status = strtolower($app['status'] ?? 'new');
                                $statusMap = ['new'=>'new','review'=>'screening','screening'=>'screening','interview'=>'interview','for interview'=>'interview','road_test'=>'road_test','testing'=>'road_test','offer'=>'offer_sent','offer_sent'=>'offer_sent','hired'=>'hired','accepted'=>'hired'];
                                $statusClass = $statusMap[$status] ?? 'new';
                            ?>
                            <span class="app-status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($app['status']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div>
                    <div class="card">
                        <h2><i data-lucide="git-branch"></i> Pipeline Summary</h2>
                        <?php if (empty($pipeline)): ?>
                            <p style="color: #94a3b8; text-align: center; padding: 1rem;">No data</p>
                        <?php else: ?>
                            <?php foreach ($pipeline as $p): ?>
                            <div class="pipeline-item">
                                <span class="label"><?php echo htmlspecialchars(str_replace('_', ' ', $p['status'])); ?></span>
                                <span class="count"><?php echo number_format($p['count']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="card">
                        <h2><i data-lucide="user-plus"></i> Recent Hires</h2>
                        <?php if (empty($recent_hires)): ?>
                            <p style="color: #94a3b8; text-align: center; padding: 1rem;">No recent hires</p>
                        <?php else: ?>
                            <?php foreach (array_slice($recent_hires, 0, 5) as $hire): ?>
                            <div class="hire-item">
                                <div class="name"><?php echo htmlspecialchars($hire['first_name'] . ' ' . $hire['last_name']); ?></div>
                                <div class="detail"><?php echo htmlspecialchars($hire['role_name'] ?? 'N/A'); ?> &bull; <?php echo htmlspecialchars($hire['department_name'] ?? 'N/A'); ?></div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
</div>
