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
    header('Location: index.php?page=job-postings-admin');
    exit();
}

require_once '../../database/config.php';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $posting_id = intval($_POST['posting_id'] ?? 0);
    
    if ($action === 'force_close' && $posting_id > 0) {
        try {
            executeQuery("UPDATE job_postings SET status = 'Closed', updated_at = NOW() WHERE id = ?", [$posting_id]);
            echo '<div class="alert alert-success">Job posting #' . $posting_id . ' force-closed by Admin.</div>';
        } catch (Exception $e) {
            echo '<div class="alert alert-error">Error closing posting.</div>';
        }
    }
    
    if ($action === 'reopen' && $posting_id > 0) {
        try {
            executeQuery("UPDATE job_postings SET status = 'Open', updated_at = NOW() WHERE id = ?", [$posting_id]);
            echo '<div class="alert alert-success">Job posting #' . $posting_id . ' reopened by Admin.</div>';
        } catch (Exception $e) {
            echo '<div class="alert alert-error">Error reopening posting.</div>';
        }
    }
    
    if ($action === 'override_status' && $posting_id > 0) {
        $new_status = $_POST['new_status'] ?? '';
        if (in_array($new_status, ['Open', 'Closed', 'Draft', 'On Hold'])) {
            try {
                executeQuery("UPDATE job_postings SET status = ?, updated_at = NOW() WHERE id = ?", [$new_status, $posting_id]);
                echo '<div class="alert alert-success">Status overridden to "' . htmlspecialchars($new_status) . '".</div>';
            } catch (Exception $e) {
                echo '<div class="alert alert-error">Error overriding status.</div>';
            }
        }
    }
    
    if ($action === 'update_budget' && $posting_id > 0) {
        $salary_min = floatval($_POST['salary_min'] ?? 0);
        $salary_max = floatval($_POST['salary_max'] ?? 0);
        try {
            executeQuery("UPDATE job_postings SET salary_min = ?, salary_max = ?, updated_at = NOW() WHERE id = ?",
                [$salary_min, $salary_max, $posting_id]);
            echo '<div class="alert alert-success">Budget updated for posting #' . $posting_id . '.</div>';
        } catch (Exception $e) {
            echo '<div class="alert alert-error">Error updating budget.</div>';
        }
    }
}

// Filters
$filter_status = $_GET['status'] ?? '';
$filter_dept = intval($_GET['dept'] ?? 0);
$search = trim($_GET['search'] ?? '');

$where = [];
$params = [];

if ($filter_status) {
    $where[] = "jp.status = ?";
    $params[] = $filter_status;
}
if ($filter_dept > 0) {
    $where[] = "jp.department_id = ?";
    $params[] = $filter_dept;
}
if ($search) {
    $where[] = "(jp.title LIKE ? OR jp.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $postings = fetchAll("
        SELECT jp.*, d.department_name,
            (SELECT COUNT(*) FROM job_applications ja WHERE ja.job_posting_id = jp.id) as applicant_count,
            (SELECT COUNT(*) FROM job_applications ja WHERE ja.job_posting_id = jp.id AND ja.status = 'Hired') as hired_count
        FROM job_postings jp
        LEFT JOIN departments d ON d.id = jp.department_id
        $where_sql
        ORDER BY jp.created_at DESC
    ", $params);
} catch (Exception $e) {
    $postings = [];
}

try {
    $departments = fetchAll("SELECT id, department_name FROM departments ORDER BY department_name");
} catch (Exception $e) {
    $departments = [];
}

// Budget summary
try {
    $budget_summary = fetchSingle("
        SELECT 
            COUNT(*) as total_postings,
            SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_count,
            ROUND(AVG(salary_min), 0) as avg_min_salary,
            ROUND(AVG(salary_max), 0) as avg_max_salary
        FROM job_postings
        WHERE salary_min > 0 OR salary_max > 0
    ");
} catch (Exception $e) {
    $budget_summary = ['total_postings' => 0, 'open_count' => 0, 'avg_min_salary' => 0, 'avg_max_salary' => 0];
}
?>
<div data-page-title="Job Postings Override">
<style>
    .jp-header { background:rgba(30,41,54,0.6); border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; }
    .jp-header h1 { font-size:1.5rem; color:#e2e8f0; margin-bottom:0.25rem; }
    .jp-header p { color:#94a3b8; font-size:0.9rem; }
    .jp-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:1rem; margin-bottom:1.5rem; }
    .jp-stat { background:rgba(30,41,54,0.6); border-radius:10px; padding:1rem; border:1px solid rgba(58,69,84,0.5); text-align:center; }
    .jp-stat h3 { font-size:1.5rem; color:#e2e8f0; }
    .jp-stat p { font-size:0.8rem; color:#94a3b8; }
    .jp-filters { background:rgba(30,41,54,0.6); border-radius:12px; padding:1rem 1.5rem; margin-bottom:1.5rem; display:flex; gap:0.75rem; flex-wrap:wrap; align-items:center; }
    .jp-filters input, .jp-filters select { padding:0.5rem 0.75rem; background:rgba(15,23,42,0.6); border:1px solid rgba(58,69,84,0.5); border-radius:8px; color:#e2e8f0; font-size:0.85rem; }
    .jp-filters input { min-width:200px; }
    .jp-card { background:rgba(30,41,54,0.6); border-radius:12px; padding:1.5rem; border:1px solid rgba(58,69,84,0.5); margin-bottom:1.5rem; }
    .jp-card h2 { font-size:1.1rem; color:#e2e8f0; margin-bottom:1rem; display:flex; align-items:center; gap:0.5rem; }
    .jp-table { width:100%; border-collapse:collapse; }
    .jp-table th { text-align:left; padding:0.6rem 0.75rem; color:#94a3b8; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; border-bottom:1px solid rgba(58,69,84,0.5); }
    .jp-table td { padding:0.75rem; color:#e2e8f0; font-size:0.85rem; border-bottom:1px solid rgba(58,69,84,0.2); vertical-align:middle; }
    .jp-table tr:hover td { background:rgba(15,23,42,0.3); }
    .status-open { background:rgba(16,185,129,0.2); color:#10b981; padding:0.2rem 0.5rem; border-radius:4px; font-size:0.75rem; font-weight:600; }
    .status-closed { background:rgba(239,68,68,0.2); color:#ef4444; padding:0.2rem 0.5rem; border-radius:4px; font-size:0.75rem; font-weight:600; }
    .status-draft { background:rgba(251,191,36,0.2); color:#fbbf24; padding:0.2rem 0.5rem; border-radius:4px; font-size:0.75rem; font-weight:600; }
    .status-hold { background:rgba(139,92,246,0.2); color:#8b5cf6; padding:0.2rem 0.5rem; border-radius:4px; font-size:0.75rem; font-weight:600; }
    .act-btn { background:rgba(58,69,84,0.5); border:none; color:#94a3b8; padding:0.3rem 0.5rem; border-radius:4px; cursor:pointer; font-size:0.75rem; margin-right:0.25rem; transition:all 0.2s; }
    .act-btn:hover { background:rgba(58,69,84,0.8); color:#e2e8f0; }
    .act-btn.danger:hover { background:rgba(239,68,68,0.3); color:#ef4444; }
    .act-btn.success:hover { background:rgba(16,185,129,0.3); color:#10b981; }
    .alert { padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:0.85rem; }
    .alert-success { background:rgba(16,185,129,0.15); color:#10b981; border:1px solid rgba(16,185,129,0.3); }
    .alert-error { background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.3); }
    .budget-cell { font-size:0.8rem; color:#94a3b8; }
    .empty-state { text-align:center; padding:2rem; color:#64748b; font-size:0.85rem; }
    .override-form { display:inline-flex; gap:0.25rem; align-items:center; }
    .override-form select { padding:0.25rem 0.4rem; background:rgba(15,23,42,0.6); border:1px solid rgba(58,69,84,0.5); border-radius:4px; color:#e2e8f0; font-size:0.75rem; }

    /* Custom confirmation modal */
    .jp-modal { position:fixed; inset:0; background:rgba(0,0,0,0.65); display:none; align-items:center; justify-content:center; z-index:2000; }
    .jp-modal.active { display:flex; }
    .jp-modal-card { width:min(520px, 92vw); background:rgba(30,41,54,0.98); border:1px solid rgba(58,69,84,0.6); border-radius:14px; box-shadow:0 25px 70px rgba(0,0,0,0.55); overflow:hidden; }
    .jp-modal-header { padding:1rem 1.25rem; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid rgba(58,69,84,0.5); }
    .jp-modal-header h3 { margin:0; font-size:1rem; color:#e2e8f0; display:flex; align-items:center; gap:0.5rem; }
    .jp-modal-body { padding:1.1rem 1.25rem; color:#cbd5e1; font-size:0.9rem; line-height:1.45; }
    .jp-modal-footer { padding:1rem 1.25rem; display:flex; justify-content:flex-end; gap:0.6rem; border-top:1px solid rgba(58,69,84,0.5); }
    .jp-modal-btn { border:none; border-radius:10px; padding:0.55rem 0.9rem; cursor:pointer; font-size:0.85rem; font-weight:600; display:inline-flex; align-items:center; gap:0.4rem; }
    .jp-modal-btn.cancel { background:rgba(100,116,139,0.25); color:#e2e8f0; border:1px solid rgba(100,116,139,0.35); }
    .jp-modal-btn.cancel:hover { background:rgba(100,116,139,0.35); }
    .jp-modal-btn.confirm { background:rgba(239,68,68,0.15); color:#fecaca; border:1px solid rgba(239,68,68,0.35); }
    .jp-modal-btn.confirm:hover { background:rgba(239,68,68,0.22); }
    .jp-modal-close { background:none; border:none; color:#94a3b8; cursor:pointer; font-size:1.2rem; padding:0.25rem 0.4rem; }
    .jp-modal-close:hover { color:#e2e8f0; }
</style>

<div class="jp-header">
    <div>
        <h1><i data-lucide="briefcase" style="width:22px;height:22px;display:inline;vertical-align:middle;color:#ef4444;margin-right:0.4rem;"></i>Job Postings Override</h1>
        <p>Force close, reopen, override status, and manage budget for all job postings</p>
    </div>
</div>

<!-- Budget Summary -->
<div class="jp-stats">
    <div class="jp-stat">
        <h3><?php echo count($postings); ?></h3>
        <p>Total Postings</p>
    </div>
    <div class="jp-stat">
        <h3 style="color:#10b981;"><?php echo count(array_filter($postings, fn($p) => $p['status'] === 'Open')); ?></h3>
        <p>Open</p>
    </div>
    <div class="jp-stat">
        <h3 style="color:#ef4444;"><?php echo count(array_filter($postings, fn($p) => $p['status'] === 'Closed')); ?></h3>
        <p>Closed</p>
    </div>
    <div class="jp-stat">
        <h3 style="color:#fbbf24;">₱<?php echo number_format($budget_summary['avg_min_salary'] ?? 0); ?> - ₱<?php echo number_format($budget_summary['avg_max_salary'] ?? 0); ?></h3>
        <p>Avg Salary Range</p>
    </div>
</div>

<!-- Filters -->
<div class="jp-filters">
    <form method="GET" style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center;width:100%;">
        <input type="hidden" name="page" value="job-postings-admin">
        <input type="text" name="search" placeholder="Search postings..." value="<?php echo htmlspecialchars($search); ?>">
        <select name="status">
            <option value="">All Status</option>
            <option value="Open" <?php echo $filter_status === 'Open' ? 'selected' : ''; ?>>Open</option>
            <option value="Closed" <?php echo $filter_status === 'Closed' ? 'selected' : ''; ?>>Closed</option>
            <option value="Draft" <?php echo $filter_status === 'Draft' ? 'selected' : ''; ?>>Draft</option>
        </select>
        <select name="dept">
            <option value="0">All Departments</option>
            <?php foreach ($departments as $d): ?>
            <option value="<?php echo $d['id']; ?>" <?php echo $filter_dept == $d['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['department_name']); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="act-btn" style="padding:0.5rem 1rem;">Filter</button>
    </form>
</div>

<!-- Postings Table -->
<div class="jp-card">
    <h2><i data-lucide="list"></i> All Job Postings (<?php echo count($postings); ?>)</h2>
    <?php if (empty($postings)): ?>
        <div class="empty-state">No job postings found.</div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="jp-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Applicants</th>
                    <th>Hired</th>
                    <th>Salary Range</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($postings as $jp): ?>
                <tr>
                    <td>#<?php echo $jp['id']; ?></td>
                    <td style="font-weight:500;"><?php echo htmlspecialchars($jp['title']); ?></td>
                    <td><?php echo htmlspecialchars($jp['department_name'] ?? 'N/A'); ?></td>
                    <td>
                        <?php
                        $sc = match($jp['status']) {
                            'Open' => 'status-open',
                            'Closed' => 'status-closed',
                            'Draft' => 'status-draft',
                            default => 'status-hold'
                        };
                        ?>
                        <span class="<?php echo $sc; ?>"><?php echo htmlspecialchars($jp['status']); ?></span>
                    </td>
                    <td><?php echo $jp['applicant_count']; ?></td>
                    <td style="color:#10b981;font-weight:600;"><?php echo $jp['hired_count']; ?></td>
                    <td class="budget-cell">
                        <?php if (!empty($jp['salary_min']) || !empty($jp['salary_max'])): ?>
                            ₱<?php echo number_format($jp['salary_min'] ?? 0); ?> - ₱<?php echo number_format($jp['salary_max'] ?? 0); ?>
                        <?php else: ?>
                            <span style="color:#64748b;">Not set</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:0.8rem;color:#94a3b8;"><?php echo date('M d, Y', strtotime($jp['created_at'])); ?></td>
                    <td>
                        <?php if ($jp['status'] === 'Open'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="force_close">
                            <input type="hidden" name="posting_id" value="<?php echo $jp['id']; ?>">
                            <button type="button" class="act-btn danger" title="Force Close"
                                onclick="openForceCloseConfirm(this.form, <?php echo (int)$jp['id']; ?>, '<?php echo htmlspecialchars(addslashes($jp['title'])); ?>')">
                                Close
                            </button>
                        </form>
                        <?php elseif ($jp['status'] === 'Closed'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="reopen">
                            <input type="hidden" name="posting_id" value="<?php echo $jp['id']; ?>">
                            <button type="submit" class="act-btn success" title="Reopen">Reopen</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" class="override-form">
                            <input type="hidden" name="action" value="override_status">
                            <input type="hidden" name="posting_id" value="<?php echo $jp['id']; ?>">
                            <select name="new_status">
                                <option value="Open">Open</option>
                                <option value="Closed">Closed</option>
                                <option value="Draft">Draft</option>
                                <option value="On Hold">On Hold</option>
                            </select>
                            <button type="submit" class="act-btn" title="Override">Set</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Force Close Confirm Modal -->
<div class="jp-modal" id="jpForceCloseModal" aria-hidden="true">
    <div class="jp-modal-card" role="dialog" aria-modal="true" aria-labelledby="jpForceCloseTitle">
        <div class="jp-modal-header">
            <h3 id="jpForceCloseTitle"><i data-lucide="alert-triangle" style="width:18px;height:18px;color:#ef4444;"></i> Confirm Force Close</h3>
            <button class="jp-modal-close" type="button" onclick="closeForceCloseConfirm()" aria-label="Close">&times;</button>
        </div>
        <div class="jp-modal-body">
            <div id="jpForceCloseText">Force close this posting?</div>
            <div style="margin-top:0.75rem;color:#94a3b8;font-size:0.82rem;">This will set the posting status to <strong style="color:#fecaca;">Closed</strong>.</div>
        </div>
        <div class="jp-modal-footer">
            <button class="jp-modal-btn cancel" type="button" onclick="closeForceCloseConfirm()">
                <i data-lucide="x" style="width:16px;height:16px;"></i> Cancel
            </button>
            <button class="jp-modal-btn confirm" type="button" onclick="confirmForceClose()">
                <i data-lucide="lock" style="width:16px;height:16px;"></i> Yes, Close
            </button>
        </div>
    </div>
</div>

<script>
let __jpForceCloseForm = null;

function openForceCloseConfirm(formEl, postingId, title) {
    __jpForceCloseForm = formEl || null;
    const modal = document.getElementById('jpForceCloseModal');
    const text = document.getElementById('jpForceCloseText');
    const safeTitle = (title || '').toString().trim();
    text.textContent = safeTitle
        ? `Force close job posting #${postingId} (${safeTitle})?`
        : `Force close job posting #${postingId}?`;
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeForceCloseConfirm() {
    const modal = document.getElementById('jpForceCloseModal');
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    __jpForceCloseForm = null;
}

function confirmForceClose() {
    if (!__jpForceCloseForm) {
        closeForceCloseConfirm();
        return;
    }
    // This will go through SPA submit handler, but without relying on native confirm().
    if (typeof __jpForceCloseForm.requestSubmit === 'function') {
        __jpForceCloseForm.requestSubmit();
    } else {
        __jpForceCloseForm.submit();
    }
    closeForceCloseConfirm();
}

// click-outside closes
document.getElementById('jpForceCloseModal')?.addEventListener('click', (e) => {
    if (e.target && e.target.id === 'jpForceCloseModal') closeForceCloseConfirm();
});

if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
</div>
