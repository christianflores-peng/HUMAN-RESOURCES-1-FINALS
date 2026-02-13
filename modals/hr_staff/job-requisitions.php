<?php
require_once '../../includes/session_helper.php';
require_once '../../includes/rbac_helper.php';
require_once '../../includes/spa_helper.php';
startSecureSession();
$is_ajax = is_spa_ajax();

if (!isset($_SESSION['user_id'])) { header('Location: ../../index.php'); exit(); }
$userId = $_SESSION['user_id'];
if (!isHRStaff($userId) && !isAdmin($userId)) { header('Location: ../../index.php'); exit(); }
if (!$is_ajax) { header('Location: index.php?page=job-requisitions'); exit(); }

require_once '../../database/config.php';

$successMsg = null;
$errorMsg = null;

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_requisition'])) {
        $reqId = intval($_POST['requisition_id']);
        try {
            updateRecord("UPDATE job_requisitions SET status = 'Approved', approved_by = ?, approved_at = NOW() WHERE id = ?", [$userId, $reqId]);
            logAuditAction($userId, 'EDIT', 'job_requisitions', $reqId, null, ['status' => 'Approved'], "Approved job requisition");
            $successMsg = "Requisition approved successfully!";
        } catch (Exception $e) { $errorMsg = "Failed to approve: " . $e->getMessage(); }
    }
    if (isset($_POST['reject_requisition'])) {
        $reqId = intval($_POST['requisition_id']);
        $reason = trim($_POST['rejection_reason'] ?? '');
        try {
            updateRecord("UPDATE job_requisitions SET status = 'Rejected', approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ?", [$userId, $reason, $reqId]);
            logAuditAction($userId, 'EDIT', 'job_requisitions', $reqId, null, ['status' => 'Rejected'], "Rejected job requisition");
            $successMsg = "Requisition rejected.";
        } catch (Exception $e) { $errorMsg = "Failed to reject: " . $e->getMessage(); }
    }
    if (isset($_POST['create_posting_from_req'])) {
        $reqId = intval($_POST['requisition_id']);
        $req = fetchSingle("SELECT jr.*, d.department_name FROM job_requisitions jr LEFT JOIN departments d ON jr.department_id = d.id WHERE jr.id = ?", [$reqId]);
        if ($req) {
            try {
                $postingId = insertRecord(
                    "INSERT INTO job_postings (requisition_id, title, description, requirements, department_id, employment_type, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, 'Open', ?, NOW())",
                    [$reqId, $req['job_title'], $req['justification'] ?? '', $req['requirements'] ?? '', $req['department_id'], $req['employment_type'], $userId]
                );
                updateRecord("UPDATE job_requisitions SET status = 'Filled', linked_posting_id = ? WHERE id = ?", [$postingId, $reqId]);
                $successMsg = "Job posting created from requisition!";
            } catch (Exception $e) { $errorMsg = "Failed: " . $e->getMessage(); }
        }
    }
}

// Fetch requisitions
$filterStatus = $_GET['status'] ?? '';
$where = "1=1";
$params = [];
if ($filterStatus) { $where .= " AND jr.status = ?"; $params[] = $filterStatus; }

try {
    $requisitions = fetchAll("
        SELECT jr.*, d.department_name, 
               ua.first_name as req_first, ua.last_name as req_last,
               ap.first_name as app_first, ap.last_name as app_last
        FROM job_requisitions jr
        LEFT JOIN departments d ON jr.department_id = d.id
        LEFT JOIN user_accounts ua ON jr.requested_by = ua.id
        LEFT JOIN user_accounts ap ON jr.approved_by = ap.id
        WHERE {$where}
        ORDER BY jr.created_at DESC
    ", $params);
} catch (Exception $e) { $requisitions = []; }

// Stats
$pendingCount = 0; $approvedCount = 0; $totalCount = count($requisitions);
foreach ($requisitions as $r) {
    if ($r['status'] === 'Pending') $pendingCount++;
    if ($r['status'] === 'Approved') $approvedCount++;
}
?>
<div data-page-title="Job Requisitions">
<style>
    .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; }
    .header h1 { font-size: 1.35rem; color: #e2e8f0; margin-bottom: 0.25rem; }
    .header p { color: #94a3b8; font-size: 0.85rem; }
    .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    .stat-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.25rem; border: 1px solid rgba(58, 69, 84, 0.5); display: flex; align-items: center; gap: 1rem; }
    .stat-card .icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
    .stat-card.pending .icon { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
    .stat-card.approved .icon { background: rgba(16, 185, 129, 0.2); color: #10b981; }
    .stat-card.total .icon { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
    .stat-card h3 { font-size: 1.5rem; color: #e2e8f0; }
    .stat-card p { font-size: 0.8rem; color: #94a3b8; }
    .filters-bar { display: flex; gap: 0.75rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
    .filter-btn { padding: 0.5rem 1rem; background: rgba(30, 41, 54, 0.6); border: 1px solid rgba(58, 69, 84, 0.5); border-radius: 8px; color: #94a3b8; font-size: 0.85rem; cursor: pointer; text-decoration: none; transition: all 0.3s; }
    .filter-btn:hover, .filter-btn.active { background: rgba(14, 165, 233, 0.15); border-color: #0ea5e9; color: #0ea5e9; }
    .req-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1rem; }
    .req-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
    .req-title { font-size: 1.1rem; color: #e2e8f0; font-weight: 600; margin-bottom: 0.25rem; }
    .req-meta { color: #94a3b8; font-size: 0.8rem; display: flex; gap: 1rem; flex-wrap: wrap; }
    .req-meta span { display: flex; align-items: center; gap: 0.3rem; }
    .req-body { color: #cbd5e1; font-size: 0.9rem; line-height: 1.6; margin-bottom: 1rem; padding: 0.75rem; background: rgba(15, 23, 42, 0.4); border-radius: 8px; }
    .req-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .btn { padding: 0.5rem 1rem; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 500; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 0.4rem; }
    .btn-approve { background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
    .btn-approve:hover { background: rgba(16, 185, 129, 0.3); }
    .btn-reject { background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
    .btn-reject:hover { background: rgba(239, 68, 68, 0.3); }
    .btn-post { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; border: 1px solid rgba(14, 165, 233, 0.3); }
    .btn-post:hover { background: rgba(14, 165, 233, 0.3); }
    .status-badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .status-badge.pending { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
    .status-badge.approved { background: rgba(16, 185, 129, 0.2); color: #34d399; }
    .status-badge.rejected { background: rgba(239, 68, 68, 0.2); color: #f87171; }
    .status-badge.filled { background: rgba(14, 165, 233, 0.2); color: #38bdf8; }
    .status-badge.cancelled { background: rgba(107, 114, 128, 0.2); color: #9ca3af; }
    .urgency-badge { padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.7rem; font-weight: 600; }
    .urgency-badge.critical { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
    .urgency-badge.high { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
    .urgency-badge.medium { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
    .urgency-badge.low { background: rgba(107, 114, 128, 0.2); color: #9ca3af; }
    .alert { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; }
    .alert-success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .alert-error { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
    .empty-state { text-align: center; padding: 4rem 2rem; color: #94a3b8; }
    .empty-state i, .empty-state svg { width: 4rem; height: 4rem; color: #475569; margin-bottom: 1rem; }
    .reject-reason { width: 100%; padding: 0.5rem; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(58, 69, 84, 0.5); border-radius: 6px; color: #e2e8f0; font-size: 0.85rem; margin-top: 0.5rem; resize: vertical; min-height: 60px; }
    .reject-reason:focus { outline: none; border-color: #ef4444; }
    @media (max-width: 768px) { .stats-row { grid-template-columns: 1fr; } .req-header { flex-direction: column; gap: 0.5rem; } }
</style>

    <div class="header">
        <div>
            <h1>Job Requisitions</h1>
            <p>Review and approve staff requests from Hiring Managers</p>
        </div>
    </div>

    <?php if ($successMsg): ?><div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div><?php endif; ?>
    <?php if ($errorMsg): ?><div class="alert alert-error"><?php echo htmlspecialchars($errorMsg); ?></div><?php endif; ?>

    <div class="stats-row">
        <div class="stat-card pending">
            <div class="icon"><i data-lucide="clock"></i></div>
            <div><h3><?php echo $pendingCount; ?></h3><p>Pending Approval</p></div>
        </div>
        <div class="stat-card approved">
            <div class="icon"><i data-lucide="check-circle"></i></div>
            <div><h3><?php echo $approvedCount; ?></h3><p>Approved</p></div>
        </div>
        <div class="stat-card total">
            <div class="icon"><i data-lucide="file-text"></i></div>
            <div><h3><?php echo $totalCount; ?></h3><p>Total Requisitions</p></div>
        </div>
    </div>

    <div class="filters-bar">
        <a href="job-requisitions.php" class="filter-btn <?php echo !$filterStatus ? 'active' : ''; ?>">All</a>
        <a href="job-requisitions.php?status=Pending" class="filter-btn <?php echo $filterStatus === 'Pending' ? 'active' : ''; ?>">Pending</a>
        <a href="job-requisitions.php?status=Approved" class="filter-btn <?php echo $filterStatus === 'Approved' ? 'active' : ''; ?>">Approved</a>
        <a href="job-requisitions.php?status=Rejected" class="filter-btn <?php echo $filterStatus === 'Rejected' ? 'active' : ''; ?>">Rejected</a>
        <a href="job-requisitions.php?status=Filled" class="filter-btn <?php echo $filterStatus === 'Filled' ? 'active' : ''; ?>">Filled</a>
    </div>

    <?php if (empty($requisitions)): ?>
        <div class="empty-state">
            <i data-lucide="inbox"></i>
            <h3 style="color: #e2e8f0; margin-bottom: 0.5rem;">No Requisitions Found</h3>
            <p>Job requisitions from Hiring Managers will appear here.</p>
        </div>
    <?php else: ?>
        <?php foreach ($requisitions as $req): ?>
        <div class="req-card">
            <div class="req-header">
                <div>
                    <div class="req-title"><?php echo htmlspecialchars($req['job_title']); ?></div>
                    <div class="req-meta">
                        <span><i data-lucide="building-2" style="width:14px;height:14px;"></i> <?php echo htmlspecialchars($req['department_name'] ?? 'N/A'); ?></span>
                        <span><i data-lucide="user" style="width:14px;height:14px;"></i> <?php echo htmlspecialchars(($req['req_first'] ?? '') . ' ' . ($req['req_last'] ?? '')); ?></span>
                        <span><i data-lucide="users" style="width:14px;height:14px;"></i> <?php echo $req['positions_needed']; ?> position(s)</span>
                        <span><i data-lucide="calendar" style="width:14px;height:14px;"></i> <?php echo date('M d, Y', strtotime($req['created_at'])); ?></span>
                    </div>
                </div>
                <div style="display:flex;gap:0.5rem;align-items:center;">
                    <span class="urgency-badge <?php echo strtolower($req['urgency']); ?>"><?php echo $req['urgency']; ?></span>
                    <span class="status-badge <?php echo strtolower($req['status']); ?>"><?php echo $req['status']; ?></span>
                </div>
            </div>

            <?php if (!empty($req['justification'])): ?>
            <div class="req-body"><?php echo nl2br(htmlspecialchars($req['justification'])); ?></div>
            <?php endif; ?>

            <?php if (!empty($req['requirements'])): ?>
            <div style="margin-bottom:1rem;">
                <strong style="color:#cbd5e1;font-size:0.85rem;">Requirements:</strong>
                <div style="color:#94a3b8;font-size:0.85rem;margin-top:0.25rem;"><?php echo nl2br(htmlspecialchars($req['requirements'])); ?></div>
            </div>
            <?php endif; ?>

            <?php if ($req['status'] === 'Pending'): ?>
            <div class="req-actions">
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="requisition_id" value="<?php echo $req['id']; ?>">
                    <button type="submit" name="approve_requisition" class="btn btn-approve"><i data-lucide="check" style="width:16px;height:16px;"></i> Approve</button>
                </form>
                <form method="POST" style="display:inline-block;">
                    <input type="hidden" name="requisition_id" value="<?php echo $req['id']; ?>">
                    <textarea name="rejection_reason" class="reject-reason" placeholder="Reason for rejection (optional)..."></textarea>
                    <button type="submit" name="reject_requisition" class="btn btn-reject" style="margin-top:0.5rem;"><i data-lucide="x" style="width:16px;height:16px;"></i> Reject</button>
                </form>
            </div>
            <?php elseif ($req['status'] === 'Approved'): ?>
            <div class="req-actions">
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="requisition_id" value="<?php echo $req['id']; ?>">
                    <button type="submit" name="create_posting_from_req" class="btn btn-post"><i data-lucide="plus" style="width:16px;height:16px;"></i> Create Job Posting</button>
                </form>
            </div>
            <?php elseif ($req['status'] === 'Rejected' && !empty($req['rejection_reason'])): ?>
            <div style="padding:0.5rem 0.75rem;background:rgba(239,68,68,0.1);border-radius:6px;color:#f87171;font-size:0.85rem;">
                <strong>Rejection Reason:</strong> <?php echo htmlspecialchars($req['rejection_reason']); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

<script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
</div>
