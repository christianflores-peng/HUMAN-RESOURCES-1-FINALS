<?php
require_once '../../includes/session_helper.php';
require_once '../../includes/rbac_helper.php';
require_once '../../includes/spa_helper.php';
startSecureSession();
$is_ajax = is_spa_ajax();

if (!isset($_SESSION['user_id'])) { header('Location: ../../index.php'); exit(); }
$userId = $_SESSION['user_id'];
if (!isHRStaff($userId) && !isAdmin($userId)) { header('Location: ../../index.php'); exit(); }
if (!$is_ajax) { header('Location: index.php?page=applicant-screening'); exit(); }

require_once '../../database/config.php';

$successMsg = null;
$errorMsg = null;

// Handle screening actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['screen_applicant'])) {
        $appId = intval($_POST['application_id']);
        $action = $_POST['screen_action'] ?? '';
        $notes = trim($_POST['screening_notes'] ?? '');
        
        $statusMap = [
            'shortlist' => 'Shortlisted',
            'reject' => 'Rejected',
            'interview' => 'For Interview'
        ];
        
        if (isset($statusMap[$action])) {
            try {
                updateRecord("UPDATE job_applications SET status = ?, screening_notes = ?, screened_by = ?, screened_at = NOW() WHERE id = ?", 
                    [$statusMap[$action], $notes, $userId, $appId]);
                logAuditAction($userId, 'EDIT', 'job_applications', $appId, null, ['status' => $statusMap[$action]], "Screened applicant");
                $successMsg = "Applicant marked as {$statusMap[$action]}!";
            } catch (Exception $e) { $errorMsg = "Failed: " . $e->getMessage(); }
        }
    }
}

// Fetch applications for screening
$filterStatus = $_GET['filter'] ?? 'Pending';
$where = "1=1";
$params = [];
if ($filterStatus && $filterStatus !== 'All') { 
    $where .= " AND ja.status = ?"; 
    $params[] = $filterStatus; 
}

try {
    $applications = fetchAll("
        SELECT ja.*, jp.title as job_title, jp.department_id,
               d.department_name,
               ua.first_name, ua.last_name, ua.email, ua.phone,
               sc.first_name as screener_first, sc.last_name as screener_last
        FROM job_applications ja
        LEFT JOIN job_postings jp ON ja.job_posting_id = jp.id
        LEFT JOIN departments d ON jp.department_id = d.id
        LEFT JOIN user_accounts ua ON ja.user_id = ua.id
        LEFT JOIN user_accounts sc ON ja.screened_by = sc.id
        WHERE {$where}
        ORDER BY ja.applied_at DESC
    ", $params);
} catch (Exception $e) { $applications = []; }

// Stats
$stats = ['Pending' => 0, 'Shortlisted' => 0, 'For Interview' => 0, 'Rejected' => 0];
try {
    $allApps = fetchAll("SELECT status, COUNT(*) as cnt FROM job_applications GROUP BY status", []);
    foreach ($allApps as $a) { $stats[$a['status']] = $a['cnt']; }
} catch (Exception $e) {}
?>
<div data-page-title="Applicant Screening">
<style>
    .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; }
    .header h1 { font-size: 1.35rem; color: #e2e8f0; margin-bottom: 0.25rem; }
    .header p { color: #94a3b8; font-size: 0.85rem; }
    .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    .stat-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1rem; border: 1px solid rgba(58, 69, 84, 0.5); text-align: center; cursor: pointer; transition: all 0.3s; text-decoration: none; display: block; }
    .stat-card:hover, .stat-card.active { border-color: #0ea5e9; }
    .stat-card h3 { font-size: 1.5rem; color: #e2e8f0; }
    .stat-card p { font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem; }
    .applicant-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1rem; }
    .applicant-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
    .applicant-name { font-size: 1.1rem; color: #e2e8f0; font-weight: 600; }
    .applicant-meta { color: #94a3b8; font-size: 0.8rem; display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.25rem; }
    .applicant-meta span { display: flex; align-items: center; gap: 0.3rem; }
    .applicant-job { background: rgba(14, 165, 233, 0.1); padding: 0.5rem 0.75rem; border-radius: 8px; margin-bottom: 1rem; }
    .applicant-job strong { color: #38bdf8; font-size: 0.9rem; }
    .applicant-job span { color: #94a3b8; font-size: 0.8rem; }
    .screening-form { display: flex; flex-direction: column; gap: 0.75rem; }
    .screening-notes { width: 100%; padding: 0.6rem; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(58, 69, 84, 0.5); border-radius: 8px; color: #e2e8f0; font-size: 0.85rem; resize: vertical; min-height: 60px; }
    .screening-notes:focus { outline: none; border-color: #0ea5e9; }
    .screening-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .btn { padding: 0.5rem 1rem; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 500; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 0.4rem; }
    .btn-shortlist { background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
    .btn-shortlist:hover { background: rgba(16, 185, 129, 0.3); }
    .btn-interview { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; border: 1px solid rgba(14, 165, 233, 0.3); }
    .btn-interview:hover { background: rgba(14, 165, 233, 0.3); }
    .btn-reject { background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
    .btn-reject:hover { background: rgba(239, 68, 68, 0.3); }
    .status-badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .status-badge.pending { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
    .status-badge.shortlisted { background: rgba(16, 185, 129, 0.2); color: #34d399; }
    .status-badge.for-interview { background: rgba(14, 165, 233, 0.2); color: #38bdf8; }
    .status-badge.rejected { background: rgba(239, 68, 68, 0.2); color: #f87171; }
    .status-badge.hired { background: rgba(139, 92, 246, 0.2); color: #a78bfa; }
    .alert { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; }
    .alert-success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .alert-error { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
    .empty-state { text-align: center; padding: 4rem 2rem; color: #94a3b8; }
    .empty-state i { width: 4rem; height: 4rem; color: #475569; margin-bottom: 1rem; }
    .resume-link { color: #38bdf8; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem; }
    .resume-link:hover { text-decoration: underline; }
    .screened-info { background: rgba(15, 23, 42, 0.4); padding: 0.5rem 0.75rem; border-radius: 6px; font-size: 0.8rem; color: #94a3b8; margin-top: 0.5rem; }
    @media (max-width: 768px) { .stats-row { grid-template-columns: repeat(2, 1fr); } }
</style>

    <div class="header">
        <h1>Applicant Screening</h1>
        <p>Filter and evaluate applicants - Screen resumes, check qualifications, and advance candidates</p>
    </div>

    <?php if ($successMsg): ?><div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div><?php endif; ?>
    <?php if ($errorMsg): ?><div class="alert alert-error"><?php echo htmlspecialchars($errorMsg); ?></div><?php endif; ?>

    <div class="stats-row">
        <a href="applicant-screening.php?filter=Pending" class="stat-card <?php echo $filterStatus === 'Pending' ? 'active' : ''; ?>">
            <h3><?php echo $stats['Pending'] ?? 0; ?></h3>
            <p>Pending Review</p>
        </a>
        <a href="applicant-screening.php?filter=Shortlisted" class="stat-card <?php echo $filterStatus === 'Shortlisted' ? 'active' : ''; ?>">
            <h3><?php echo $stats['Shortlisted'] ?? 0; ?></h3>
            <p>Shortlisted</p>
        </a>
        <a href="applicant-screening.php?filter=For Interview" class="stat-card <?php echo $filterStatus === 'For Interview' ? 'active' : ''; ?>">
            <h3><?php echo $stats['For Interview'] ?? 0; ?></h3>
            <p>For Interview</p>
        </a>
        <a href="applicant-screening.php?filter=Rejected" class="stat-card <?php echo $filterStatus === 'Rejected' ? 'active' : ''; ?>">
            <h3><?php echo $stats['Rejected'] ?? 0; ?></h3>
            <p>Rejected</p>
        </a>
    </div>

    <?php if (empty($applications)): ?>
        <div class="empty-state">
            <i data-lucide="users"></i>
            <h3 style="color: #e2e8f0; margin-bottom: 0.5rem;">No Applicants Found</h3>
            <p>No applicants match the current filter.</p>
        </div>
    <?php else: ?>
        <?php foreach ($applications as $app): ?>
        <div class="applicant-card">
            <div class="applicant-header">
                <div>
                    <div class="applicant-name"><?php echo htmlspecialchars(($app['first_name'] ?? '') . ' ' . ($app['last_name'] ?? '')); ?></div>
                    <div class="applicant-meta">
                        <span><i data-lucide="mail" style="width:14px;height:14px;"></i> <?php echo htmlspecialchars($app['email'] ?? 'N/A'); ?></span>
                        <span><i data-lucide="phone" style="width:14px;height:14px;"></i> <?php echo htmlspecialchars($app['phone'] ?? 'N/A'); ?></span>
                        <span><i data-lucide="calendar" style="width:14px;height:14px;"></i> Applied: <?php echo date('M d, Y', strtotime($app['applied_at'])); ?></span>
                    </div>
                </div>
                <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $app['status'])); ?>"><?php echo $app['status']; ?></span>
            </div>

            <div class="applicant-job">
                <strong><?php echo htmlspecialchars($app['job_title'] ?? 'N/A'); ?></strong>
                <span> &mdash; <?php echo htmlspecialchars($app['department_name'] ?? 'N/A'); ?></span>
            </div>

            <?php if (!empty($app['resume_path'])): ?>
                <a href="../../<?php echo htmlspecialchars($app['resume_path']); ?>" target="_blank" class="resume-link">
                    <i data-lucide="file-text" style="width:14px;height:14px;"></i> View Resume
                </a>
            <?php endif; ?>

            <?php if (!empty($app['screening_notes'])): ?>
            <div class="screened-info">
                <strong>Screening Notes:</strong> <?php echo htmlspecialchars($app['screening_notes']); ?>
                <?php if (!empty($app['screener_first'])): ?>
                    <br><em>Screened by <?php echo htmlspecialchars($app['screener_first'] . ' ' . $app['screener_last']); ?> on <?php echo date('M d, Y', strtotime($app['screened_at'])); ?></em>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($app['status'] === 'Pending' || $app['status'] === 'Submitted'): ?>
            <form method="POST" class="screening-form" style="margin-top:1rem;">
                <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                <textarea name="screening_notes" class="screening-notes" placeholder="Screening notes (e.g., has valid Professional Driver's License, 3 years experience...)"></textarea>
                <div class="screening-actions">
                    <button type="submit" name="screen_applicant" class="btn btn-shortlist" onclick="this.form.querySelector('[name=screen_action]').value='shortlist'"><i data-lucide="check" style="width:16px;height:16px;"></i> Shortlist</button>
                    <button type="submit" name="screen_applicant" class="btn btn-interview" onclick="this.form.querySelector('[name=screen_action]').value='interview'"><i data-lucide="calendar" style="width:16px;height:16px;"></i> Move to Interview</button>
                    <button type="submit" name="screen_applicant" class="btn btn-reject" onclick="this.form.querySelector('[name=screen_action]').value='reject'"><i data-lucide="x" style="width:16px;height:16px;"></i> Reject</button>
                    <input type="hidden" name="screen_action" value="">
                </div>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

<script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
</div>
