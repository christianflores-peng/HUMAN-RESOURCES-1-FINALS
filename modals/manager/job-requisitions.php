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
if (!$is_ajax) { header('Location: index.php?page=job-requisitions'); exit(); }

require_once '../../database/config.php';

$successMsg = null;
$errorMsg = null;

// Fetch departments
try { $departments = fetchAll("SELECT * FROM departments ORDER BY department_name"); } catch (Exception $e) { $departments = []; }

// Handle create requisition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_requisition'])) {
    $jobTitle = trim($_POST['job_title'] ?? '');
    $deptId = intval($_POST['department_id'] ?? 0);
    $positions = intval($_POST['positions_needed'] ?? 1);
    $empType = $_POST['employment_type'] ?? 'Full-time';
    $urgency = $_POST['urgency'] ?? 'Medium';
    $justification = trim($_POST['justification'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $startDate = $_POST['preferred_start_date'] ?? null;
    $salaryMin = floatval($_POST['salary_range_min'] ?? 0) ?: null;
    $salaryMax = floatval($_POST['salary_range_max'] ?? 0) ?: null;

    if ($jobTitle && $deptId) {
        try {
            insertRecord(
                "INSERT INTO job_requisitions (requested_by, department_id, job_title, positions_needed, employment_type, urgency, justification, requirements, preferred_start_date, salary_range_min, salary_range_max, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')",
                [$userId, $deptId, $jobTitle, $positions, $empType, $urgency, $justification, $requirements, $startDate ?: null, $salaryMin, $salaryMax]
            );
            logAuditAction($userId, 'CREATE', 'job_requisitions', null, null, null, "Created job requisition: {$jobTitle}");
            $successMsg = "Job requisition submitted for approval!";
        } catch (Exception $e) { $errorMsg = "Failed: " . $e->getMessage(); }
    } else {
        $errorMsg = "Job title and department are required.";
    }
}

// Handle cancel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_requisition'])) {
    $reqId = intval($_POST['requisition_id']);
    try {
        updateRecord("UPDATE job_requisitions SET status = 'Cancelled' WHERE id = ? AND requested_by = ? AND status = 'Pending'", [$reqId, $userId]);
        $successMsg = "Requisition cancelled.";
    } catch (Exception $e) { $errorMsg = "Failed: " . $e->getMessage(); }
}

// Fetch my requisitions
try {
    $myRequisitions = fetchAll("
        SELECT jr.*, d.department_name,
               ap.first_name as app_first, ap.last_name as app_last
        FROM job_requisitions jr
        LEFT JOIN departments d ON jr.department_id = d.id
        LEFT JOIN user_accounts ap ON jr.approved_by = ap.id
        WHERE jr.requested_by = ?
        ORDER BY jr.created_at DESC
    ", [$userId]);
} catch (Exception $e) { $myRequisitions = []; }

$pendingCount = count(array_filter($myRequisitions, fn($r) => $r['status'] === 'Pending'));
$approvedCount = count(array_filter($myRequisitions, fn($r) => $r['status'] === 'Approved'));
$filledCount = count(array_filter($myRequisitions, fn($r) => $r['status'] === 'Filled'));
?>
<div data-page-title="Job Requisitions">
<style>
    .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
    .header h1 { font-size: 1.35rem; color: #e2e8f0; margin-bottom: 0.25rem; }
    .header p { color: #94a3b8; font-size: 0.85rem; }
    .btn-primary { padding: 0.6rem 1.2rem; background: linear-gradient(135deg, #0ea5e9, #0284c7); color: white; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; transition: all 0.3s; }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3); }
    .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    .stat-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.25rem; border: 1px solid rgba(58, 69, 84, 0.5); display: flex; align-items: center; gap: 1rem; }
    .stat-card .icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
    .stat-card.pending .icon { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
    .stat-card.approved .icon { background: rgba(16, 185, 129, 0.2); color: #10b981; }
    .stat-card.filled .icon { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
    .stat-card h3 { font-size: 1.5rem; color: #e2e8f0; }
    .stat-card p { font-size: 0.8rem; color: #94a3b8; }
    .create-form { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1.5rem; display: none; }
    .create-form.show { display: block; }
    .create-form h3 { color: #e2e8f0; font-size: 1.1rem; margin-bottom: 1rem; }
    .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
    .form-group { display: flex; flex-direction: column; gap: 0.3rem; }
    .form-group.full { grid-column: 1 / -1; }
    .form-group label { color: #94a3b8; font-size: 0.8rem; font-weight: 500; }
    .form-group input, .form-group select, .form-group textarea { padding: 0.6rem; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(58, 69, 84, 0.5); border-radius: 8px; color: #e2e8f0; font-size: 0.85rem; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #0ea5e9; }
    .form-group textarea { resize: vertical; min-height: 80px; }
    .form-actions { display: flex; gap: 0.5rem; margin-top: 1rem; }
    .req-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1rem; }
    .req-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem; }
    .req-title { font-size: 1.1rem; color: #e2e8f0; font-weight: 600; margin-bottom: 0.25rem; }
    .req-meta { color: #94a3b8; font-size: 0.8rem; display: flex; gap: 1rem; flex-wrap: wrap; }
    .req-meta span { display: flex; align-items: center; gap: 0.3rem; }
    .req-body { color: #cbd5e1; font-size: 0.9rem; line-height: 1.6; margin-bottom: 0.75rem; padding: 0.75rem; background: rgba(15, 23, 42, 0.4); border-radius: 8px; }
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
    .btn { padding: 0.5rem 1rem; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 500; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 0.4rem; }
    .btn-cancel-req { background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
    .btn-cancel-req:hover { background: rgba(239, 68, 68, 0.3); }
    .btn-secondary { background: rgba(107, 114, 128, 0.2); color: #9ca3af; border: 1px solid rgba(107, 114, 128, 0.3); }
    .alert { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; }
    .alert-success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .alert-error { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
    .empty-state { text-align: center; padding: 4rem 2rem; color: #94a3b8; }
    .empty-state i { width: 4rem; height: 4rem; color: #475569; margin-bottom: 1rem; }
    .rejection-note { background: rgba(239, 68, 68, 0.1); padding: 0.5rem 0.75rem; border-radius: 6px; color: #f87171; font-size: 0.85rem; margin-top: 0.5rem; }
    @media (max-width: 768px) { .stats-row { grid-template-columns: 1fr; } .form-grid { grid-template-columns: 1fr; } }
</style>

    <div class="header">
        <div>
            <h1>Job Requisitions</h1>
            <p>Request new staff for your department</p>
        </div>
        <button class="btn-primary" onclick="document.getElementById('createForm').classList.toggle('show')">
            <i data-lucide="plus" style="width:16px;height:16px;"></i> New Requisition
        </button>
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
        <div class="stat-card filled">
            <div class="icon"><i data-lucide="user-check"></i></div>
            <div><h3><?php echo $filledCount; ?></h3><p>Filled / Posted</p></div>
        </div>
    </div>

    <div id="createForm" class="create-form">
        <h3><i data-lucide="file-plus" style="width:20px;height:20px;display:inline;vertical-align:middle;"></i> Create Job Requisition</h3>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Job Title *</label>
                    <input type="text" name="job_title" required placeholder="e.g., Truck Driver - Class A">
                </div>
                <div class="form-group">
                    <label>Department *</label>
                    <select name="department_id" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Positions Needed</label>
                    <input type="number" name="positions_needed" value="1" min="1" max="50">
                </div>
                <div class="form-group">
                    <label>Employment Type</label>
                    <select name="employment_type">
                        <option value="Full-time">Full-time</option>
                        <option value="Part-time">Part-time</option>
                        <option value="Contract">Contract</option>
                        <option value="Seasonal">Seasonal</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Urgency</label>
                    <select name="urgency">
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                        <option value="Critical">Critical</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Preferred Start Date</label>
                    <input type="date" name="preferred_start_date" min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Salary Range (Min)</label>
                    <input type="number" name="salary_range_min" placeholder="e.g., 15000" step="100">
                </div>
                <div class="form-group">
                    <label>Salary Range (Max)</label>
                    <input type="number" name="salary_range_max" placeholder="e.g., 25000" step="100">
                </div>
                <div class="form-group full">
                    <label>Justification / Reason *</label>
                    <textarea name="justification" required placeholder="Why is this position needed? e.g., Fleet Dept needs 5 new Truck Drivers due to increased delivery volume..."></textarea>
                </div>
                <div class="form-group full">
                    <label>Requirements / Qualifications</label>
                    <textarea name="requirements" placeholder="e.g., Professional Driver's License (Restriction Code 8), 2+ years experience, clean driving record..."></textarea>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="create_requisition" class="btn-primary"><i data-lucide="send" style="width:16px;height:16px;"></i> Submit Requisition</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('createForm').classList.remove('show')">Cancel</button>
            </div>
        </form>
    </div>

    <?php if (empty($myRequisitions)): ?>
        <div class="empty-state">
            <i data-lucide="file-plus"></i>
            <h3 style="color: #e2e8f0; margin-bottom: 0.5rem;">No Requisitions Yet</h3>
            <p>Click "New Requisition" to request new staff for your department.</p>
        </div>
    <?php else: ?>
        <?php foreach ($myRequisitions as $req): ?>
        <div class="req-card">
            <div class="req-header">
                <div>
                    <div class="req-title"><?php echo htmlspecialchars($req['job_title']); ?></div>
                    <div class="req-meta">
                        <span><i data-lucide="building-2" style="width:14px;height:14px;"></i> <?php echo htmlspecialchars($req['department_name'] ?? 'N/A'); ?></span>
                        <span><i data-lucide="users" style="width:14px;height:14px;"></i> <?php echo $req['positions_needed']; ?> position(s)</span>
                        <span><i data-lucide="briefcase" style="width:14px;height:14px;"></i> <?php echo $req['employment_type']; ?></span>
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

            <?php if ($req['status'] === 'Approved' && !empty($req['app_first'])): ?>
            <div style="color:#34d399;font-size:0.85rem;margin-top:0.5rem;">
                <i data-lucide="check" style="width:14px;height:14px;display:inline;vertical-align:middle;"></i>
                Approved by <?php echo htmlspecialchars($req['app_first'] . ' ' . $req['app_last']); ?> on <?php echo date('M d, Y', strtotime($req['approved_at'])); ?>
            </div>
            <?php endif; ?>

            <?php if ($req['status'] === 'Rejected'): ?>
            <div class="rejection-note">
                <strong>Rejected</strong><?php if (!empty($req['rejection_reason'])): ?>: <?php echo htmlspecialchars($req['rejection_reason']); ?><?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($req['status'] === 'Pending'): ?>
            <div style="margin-top:0.75rem;">
                <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this requisition?');">
                    <input type="hidden" name="requisition_id" value="<?php echo $req['id']; ?>">
                    <button type="submit" name="cancel_requisition" class="btn btn-cancel-req"><i data-lucide="x" style="width:14px;height:14px;"></i> Cancel Request</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

<script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
</div>
