<?php
require_once '../../includes/session_helper.php';
require_once '../../includes/spa_helper.php';
startSecureSession();
$is_ajax = is_spa_ajax();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Applicant') {
    header('Location: ../../index.php');
    exit();
}

if (!$is_ajax) {
    header('Location: index.php?page=applications');
    exit();
}

require_once '../../database/config.php';

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['personal_email'] ?? '';

$status_filter = $_GET['status'] ?? 'all';

$where_clause = "ja.email = ?";
$params = [$user_email];

if ($status_filter !== 'all') {
    $where_clause .= " AND ja.status = ?";
    $params[] = $status_filter;
}

try {
    $applications = fetchAll("
        SELECT ja.*, 
               COALESCE(jp.title, 'General Application') as job_title,
               COALESCE(jp.location, 'N/A') as location,
               COALESCE(jp.employment_type, 'To Be Determined') as employment_type,
               COALESCE(d.department_name, 'Not Assigned') as department_name
        FROM job_applications ja
        LEFT JOIN job_postings jp ON jp.id = ja.job_posting_id
        LEFT JOIN departments d ON d.id = jp.department_id
        WHERE {$where_clause}
        ORDER BY ja.applied_date DESC
    ", $params);
} catch (Exception $e) {
    $applications = [];
}

$status_counts = ['all' => count($applications), 'new' => 0, 'screening' => 0, 'interview' => 0, 'road_test' => 0, 'offer_sent' => 0, 'hired' => 0, 'rejected' => 0];
foreach ($applications as $app) {
    if (isset($status_counts[$app['status']])) { $status_counts[$app['status']]++; }
}
?>
<div data-page-title="My Applications">
<style>
        .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5rem; color: #e2e8f0; }
        .btn { padding: 0.65rem 1.25rem; border: none; border-radius: 6px; font-size: 0.9rem; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-secondary { background: rgba(100, 116, 139, 0.3); color: #cbd5e1; }
        .btn-secondary:hover { background: rgba(100, 116, 139, 0.5); }
        .btn-primary { background: #0ea5e9; color: white; }
        .btn-primary:hover { background: #0284c7; }
                .filter-tabs { display: flex; gap: 0.5rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .filter-tab { padding: 0.65rem 1.25rem; background: rgba(30, 41, 54, 0.6); border: 1px solid rgba(58, 69, 84, 0.5); border-radius: 8px; color: #cbd5e1; text-decoration: none; font-size: 0.85rem; transition: all 0.3s; display: flex; align-items: center; gap: 0.5rem; }
        .filter-tab:hover { background: rgba(30, 41, 54, 0.8); border-color: #0ea5e9; }
        .filter-tab.active { background: rgba(14, 165, 233, 0.2); border-color: #0ea5e9; color: #0ea5e9; }
        .count-badge { background: rgba(100, 116, 139, 0.3); padding: 0.15rem 0.5rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .filter-tab.active .count-badge { background: rgba(14, 165, 233, 0.3); color: #0ea5e9; }
        .applications-grid { display: grid; gap: 1.5rem; }
        .application-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); transition: all 0.3s; }
        .application-card:hover { border-color: #0ea5e9; transform: translateY(-2px); }
        .card-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
        .card-title { font-size: 1.1rem; color: #e2e8f0; font-weight: 600; margin-bottom: 0.5rem; }
        .card-meta { display: flex; gap: 1.5rem; flex-wrap: wrap; color: #94a3b8; font-size: 0.85rem; margin-bottom: 1rem; }
        .meta-item { display: flex; align-items: center; gap: 0.35rem; }
        .status-badge { padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-badge.new { background: rgba(99, 102, 241, 0.2); color: #6366f1; }
        .status-badge.screening { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .status-badge.interview { background: rgba(139, 92, 246, 0.2); color: #8b5cf6; }
        .status-badge.road_test { background: rgba(236, 72, 153, 0.2); color: #ec4899; }
        .status-badge.offer_sent { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .status-badge.hired { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .status-badge.rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .card-actions { display: flex; gap: 0.75rem; padding-top: 1rem; border-top: 1px solid rgba(58, 69, 84, 0.3); flex-wrap: wrap; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-info { background: #8b5cf6; color: white; }
        .btn-info:hover { background: #7c3aed; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-warning:hover { background: #d97706; }
        .empty-state { text-align: center; padding: 4rem 2rem; color: #94a3b8; }
        .empty-state i { width: 5rem; height: 5rem; color: #475569; margin-bottom: 1rem; }
    </style>
            <div class="header">
                <h1>My Applications</h1>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                    <a href="dashboard.php" class="btn btn-secondary"><i data-lucide="arrow-left"></i>Back to Dashboard</a>
                </div>
            </div>

            <div class="filter-tabs">
                <a href="?status=all" class="filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All Applications<span class="count-badge"><?php echo $status_counts['all']; ?></span></a>
                <a href="?status=new" class="filter-tab <?php echo $status_filter === 'new' ? 'active' : ''; ?>">New<span class="count-badge"><?php echo $status_counts['new']; ?></span></a>
                <a href="?status=screening" class="filter-tab <?php echo $status_filter === 'screening' ? 'active' : ''; ?>">Screening<span class="count-badge"><?php echo $status_counts['screening']; ?></span></a>
                <a href="?status=interview" class="filter-tab <?php echo $status_filter === 'interview' ? 'active' : ''; ?>">Interview<span class="count-badge"><?php echo $status_counts['interview']; ?></span></a>
                <a href="?status=road_test" class="filter-tab <?php echo $status_filter === 'road_test' ? 'active' : ''; ?>">Road Test<span class="count-badge"><?php echo $status_counts['road_test']; ?></span></a>
                <a href="?status=offer_sent" class="filter-tab <?php echo $status_filter === 'offer_sent' ? 'active' : ''; ?>">Offer Sent<span class="count-badge"><?php echo $status_counts['offer_sent']; ?></span></a>
                <a href="?status=hired" class="filter-tab <?php echo $status_filter === 'hired' ? 'active' : ''; ?>">Hired<span class="count-badge"><?php echo $status_counts['hired']; ?></span></a>
            </div>

            <?php if (empty($applications)): ?>
            <div class="empty-state">
                <i data-lucide="inbox"></i>
                <h3>No Applications Found</h3>
                <p>You haven't submitted any applications yet.</p>
            </div>
            <?php else: ?>
            <div class="applications-grid">
                <?php foreach ($applications as $app): ?>
                <div class="application-card">
                    <div class="card-header">
                        <div>
                            <div class="card-title"><?php echo htmlspecialchars($app['job_title']); ?></div>
                            <div class="card-meta">
                                <div class="meta-item"><i data-lucide="building" style="width: 1rem; height: 1rem;"></i><?php echo htmlspecialchars($app['department_name']); ?></div>
                                <div class="meta-item"><i data-lucide="map-pin" style="width: 1rem; height: 1rem;"></i><?php echo htmlspecialchars($app['location']); ?></div>
                                <div class="meta-item"><i data-lucide="calendar" style="width: 1rem; height: 1rem;"></i><?php echo date('M d, Y', strtotime($app['applied_date'])); ?></div>
                            </div>
                        </div>
                        <span class="status-badge <?php echo $app['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?></span>
                    </div>
                    <div class="card-actions">
                        <a href="application-details.php?id=<?php echo $app['id']; ?>" class="btn btn-primary"><i data-lucide="eye"></i>View Details</a>
                        <?php if ($app['status'] === 'offer_sent'): ?>
                        <a href="offer-view.php?id=<?php echo $app['id']; ?>" class="btn btn-success"><i data-lucide="file-text"></i>View Offer</a>
                        <?php elseif ($app['status'] === 'interview'): ?>
                        <a href="interview-schedule.php?id=<?php echo $app['id']; ?>" class="btn btn-info"><i data-lucide="calendar"></i>Interview Info</a>
                        <?php elseif ($app['status'] === 'road_test'): ?>
                        <a href="road-test-info.php?id=<?php echo $app['id']; ?>" class="btn btn-warning"><i data-lucide="car"></i>Road Test Info</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
</div>
