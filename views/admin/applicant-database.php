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
    header('Location: index.php?page=applicant-database');
    exit();
}

require_once '../../database/config.php';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'blacklist') {
        $app_id = intval($_POST['application_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        if ($app_id > 0) {
            try {
                executeQuery("UPDATE job_applications SET status = 'Blacklisted', rejection_reason = ?, updated_at = NOW() WHERE id = ?",
                    [$reason ?: 'Blacklisted by Admin', $app_id]);
                executeQuery("INSERT INTO application_status_history (application_id, old_status, new_status, changed_by, remarks, changed_at) 
                    VALUES (?, (SELECT status FROM job_applications WHERE id = ? LIMIT 1), 'Blacklisted', ?, ?, NOW())",
                    [$app_id, $app_id, $_SESSION['user_id'], $reason ?: 'Blacklisted by Admin']);
                echo '<div class="alert alert-success">Applicant blacklisted successfully.</div>';
            } catch (Exception $e) {
                echo '<div class="alert alert-error">Error blacklisting applicant.</div>';
            }
        }
    }
    
    if ($action === 'unblacklist') {
        $app_id = intval($_POST['application_id'] ?? 0);
        if ($app_id > 0) {
            try {
                executeQuery("UPDATE job_applications SET status = 'New Apply', rejection_reason = NULL, updated_at = NOW() WHERE id = ?", [$app_id]);
                echo '<div class="alert alert-success">Applicant removed from blacklist.</div>';
            } catch (Exception $e) {
                echo '<div class="alert alert-error">Error removing from blacklist.</div>';
            }
        }
    }
    
    if ($action === 'gdpr_delete') {
        $app_id = intval($_POST['application_id'] ?? 0);
        if ($app_id > 0) {
            try {
                // Anonymize personal data instead of hard delete
                executeQuery("UPDATE job_applications SET 
                    first_name = 'REDACTED', last_name = 'REDACTED', email = CONCAT('redacted_', id, '@deleted.local'),
                    phone = NULL, resume_path = NULL, cover_letter = NULL, rejection_reason = 'Data Privacy - Deleted by Admin',
                    status = 'Rejected', updated_at = NOW()
                    WHERE id = ?", [$app_id]);
                echo '<div class="alert alert-success">Applicant data anonymized per data privacy request.</div>';
            } catch (Exception $e) {
                echo '<div class="alert alert-error">Error processing data deletion.</div>';
            }
        }
    }
}

// Filters
$search = trim($_GET['search'] ?? '');
$filter_status = $_GET['status'] ?? '';
$filter_dept = intval($_GET['dept'] ?? 0);
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

$where = [];
$params = [];

if ($search) {
    $where[] = "(ja.first_name LIKE ? OR ja.last_name LIKE ? OR ja.email LIKE ? OR CONCAT(ja.first_name,' ',ja.last_name) LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}
if ($filter_status) {
    $where[] = "ja.status = ?";
    $params[] = $filter_status;
}
if ($filter_dept > 0) {
    $where[] = "jp.department_id = ?";
    $params[] = $filter_dept;
}
if ($filter_date_from) {
    $where[] = "DATE(ja.applied_date) >= ?";
    $params[] = $filter_date_from;
}
if ($filter_date_to) {
    $where[] = "DATE(ja.applied_date) <= ?";
    $params[] = $filter_date_to;
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $applicants = fetchAll("
        SELECT ja.*, jp.title as job_title, d.department_name,
            (SELECT COUNT(*) FROM application_status_history ash WHERE ash.application_id = ja.id) as history_count
        FROM job_applications ja
        LEFT JOIN job_postings jp ON jp.id = ja.job_posting_id
        LEFT JOIN departments d ON d.id = jp.department_id
        $where_sql
        ORDER BY ja.applied_date DESC
        LIMIT 200
    ", $params);
} catch (Exception $e) {
    $applicants = [];
}

try {
    $departments = fetchAll("SELECT id, department_name FROM departments ORDER BY department_name");
} catch (Exception $e) {
    $departments = [];
}

try {
    $status_counts = fetchAll("SELECT status, COUNT(*) as count FROM job_applications GROUP BY status ORDER BY count DESC");
} catch (Exception $e) {
    $status_counts = [];
}

$total_applicants = array_sum(array_column($status_counts, 'count'));
$blacklisted_count = 0;
foreach ($status_counts as $sc) {
    if ($sc['status'] === 'Blacklisted') $blacklisted_count = $sc['count'];
}
?>
<div data-page-title="Applicant Database">
<style>
    .adb-header { background:rgba(30,41,54,0.6); border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; }
    .adb-header h1 { font-size:1.5rem; color:#e2e8f0; margin-bottom:0.25rem; }
    .adb-header p { color:#94a3b8; font-size:0.9rem; }
    .adb-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:1rem; margin-bottom:1.5rem; }
    .adb-stat { background:rgba(30,41,54,0.6); border-radius:10px; padding:1rem; border:1px solid rgba(58,69,84,0.5); text-align:center; }
    .adb-stat h3 { font-size:1.4rem; color:#e2e8f0; }
    .adb-stat p { font-size:0.75rem; color:#94a3b8; }
    .adb-filters { background:rgba(30,41,54,0.6); border-radius:12px; padding:1rem 1.5rem; margin-bottom:1.5rem; }
    .adb-filter-row { display:flex; gap:0.75rem; flex-wrap:wrap; align-items:center; }
    .adb-filters input, .adb-filters select { padding:0.5rem 0.75rem; background:rgba(15,23,42,0.6); border:1px solid rgba(58,69,84,0.5); border-radius:8px; color:#e2e8f0; font-size:0.85rem; }
    .adb-filters input[type="text"] { min-width:220px; }
    .adb-filters input[type="date"] { min-width:140px; }
    .adb-card { background:rgba(30,41,54,0.6); border-radius:12px; padding:1.5rem; border:1px solid rgba(58,69,84,0.5); margin-bottom:1.5rem; }
    .adb-card h2 { font-size:1.1rem; color:#e2e8f0; margin-bottom:1rem; display:flex; align-items:center; gap:0.5rem; }
    .adb-table { width:100%; border-collapse:collapse; }
    .adb-table th { text-align:left; padding:0.6rem 0.75rem; color:#94a3b8; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; border-bottom:1px solid rgba(58,69,84,0.5); }
    .adb-table td { padding:0.65rem 0.75rem; color:#e2e8f0; font-size:0.85rem; border-bottom:1px solid rgba(58,69,84,0.2); vertical-align:middle; }
    .adb-table tr:hover td { background:rgba(15,23,42,0.3); }
    .status-badge { padding:0.2rem 0.5rem; border-radius:4px; font-size:0.7rem; font-weight:600; white-space:nowrap; }
    .st-new { background:rgba(14,165,233,0.2); color:#0ea5e9; }
    .st-screening { background:rgba(139,92,246,0.2); color:#8b5cf6; }
    .st-interview { background:rgba(249,115,22,0.2); color:#f97316; }
    .st-hired { background:rgba(16,185,129,0.2); color:#10b981; }
    .st-rejected { background:rgba(239,68,68,0.2); color:#ef4444; }
    .st-blacklisted { background:rgba(239,68,68,0.3); color:#fca5a5; }
    .st-offer { background:rgba(236,72,153,0.2); color:#ec4899; }
    .st-default { background:rgba(100,116,139,0.2); color:#94a3b8; }
    .act-btn { background:rgba(58,69,84,0.5); border:none; color:#94a3b8; padding:0.25rem 0.5rem; border-radius:4px; cursor:pointer; font-size:0.75rem; transition:all 0.2s; }
    .act-btn:hover { background:rgba(58,69,84,0.8); color:#e2e8f0; }
    .act-btn.danger { color:#fca5a5; }
    .act-btn.danger:hover { background:rgba(239,68,68,0.3); color:#ef4444; }
    .act-btn.warn:hover { background:rgba(251,191,36,0.3); color:#fbbf24; }
    .alert { padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:0.85rem; }
    .alert-success { background:rgba(16,185,129,0.15); color:#10b981; border:1px solid rgba(16,185,129,0.3); }
    .alert-error { background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.3); }
    .empty-state { text-align:center; padding:2rem; color:#64748b; font-size:0.85rem; }
    .bl-modal-overlay { display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.6); z-index:1000; align-items:center; justify-content:center; }
    .bl-modal-overlay.active { display:flex; }
    .bl-modal { background:#1e2936; border-radius:12px; padding:1.5rem; width:90%; max-width:450px; border:1px solid rgba(58,69,84,0.5); }
    .bl-modal h3 { color:#e2e8f0; margin-bottom:1rem; font-size:1.1rem; }
    .bl-modal textarea { width:100%; padding:0.6rem; background:rgba(15,23,42,0.6); border:1px solid rgba(58,69,84,0.5); border-radius:8px; color:#e2e8f0; font-size:0.85rem; min-height:80px; resize:vertical; margin-bottom:1rem; }
    .bl-modal .btn-row { display:flex; gap:0.75rem; justify-content:flex-end; }
    .btn-primary { background:#ef4444; color:#fff; border:none; padding:0.5rem 1rem; border-radius:8px; cursor:pointer; font-size:0.85rem; }
    .btn-primary:hover { background:#dc2626; }
    .btn-secondary { background:rgba(58,69,84,0.5); color:#e2e8f0; border:none; padding:0.5rem 1rem; border-radius:8px; cursor:pointer; font-size:0.85rem; }
</style>

<div class="adb-header">
    <div>
        <h1><i data-lucide="database" style="width:22px;height:22px;display:inline;vertical-align:middle;color:#ef4444;margin-right:0.4rem;"></i>Applicant Database</h1>
        <p>Full search, blacklist management, and data privacy controls</p>
    </div>
</div>

<!-- Stats -->
<div class="adb-stats">
    <div class="adb-stat"><h3><?php echo number_format($total_applicants); ?></h3><p>Total Applicants</p></div>
    <?php foreach (array_slice($status_counts, 0, 5) as $sc): ?>
    <div class="adb-stat">
        <h3><?php echo number_format($sc['count']); ?></h3>
        <p><?php echo htmlspecialchars($sc['status']); ?></p>
    </div>
    <?php endforeach; ?>
    <div class="adb-stat"><h3 style="color:#ef4444;"><?php echo $blacklisted_count; ?></h3><p>Blacklisted</p></div>
</div>

<!-- Filters -->
<div class="adb-filters">
    <form method="GET" class="adb-filter-row">
        <input type="hidden" name="page" value="applicant-database">
        <input type="text" name="search" placeholder="Search name or email..." value="<?php echo htmlspecialchars($search); ?>">
        <select name="status">
            <option value="">All Status</option>
            <option value="New" <?php echo $filter_status === 'New' ? 'selected' : ''; ?>>New</option>
            <option value="Screening" <?php echo $filter_status === 'Screening' ? 'selected' : ''; ?>>Screening</option>
            <option value="Interview" <?php echo $filter_status === 'Interview' ? 'selected' : ''; ?>>Interview</option>
            <option value="Road_Test" <?php echo $filter_status === 'Road_Test' ? 'selected' : ''; ?>>Road Test</option>
            <option value="Offer_Sent" <?php echo $filter_status === 'Offer_Sent' ? 'selected' : ''; ?>>Offer Sent</option>
            <option value="Hired" <?php echo $filter_status === 'Hired' ? 'selected' : ''; ?>>Hired</option>
            <option value="Rejected" <?php echo $filter_status === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
            <option value="Blacklisted" <?php echo $filter_status === 'Blacklisted' ? 'selected' : ''; ?>>Blacklisted</option>
        </select>
        <select name="dept">
            <option value="0">All Departments</option>
            <?php foreach ($departments as $d): ?>
            <option value="<?php echo $d['id']; ?>" <?php echo $filter_dept == $d['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['department_name']); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>" title="From date">
        <input type="date" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>" title="To date">
        <button type="submit" class="act-btn" style="padding:0.5rem 1rem;">Search</button>
    </form>
</div>

<!-- Applicant Table -->
<div class="adb-card">
    <h2><i data-lucide="users"></i> Results (<?php echo count($applicants); ?><?php echo count($applicants) >= 200 ? '+' : ''; ?>)</h2>
    <?php if (empty($applicants)): ?>
        <div class="empty-state">No applicants found matching your criteria.</div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="adb-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Position</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Applied</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applicants as $app): 
                    $status_class = match($app['status']) {
                        'New', 'New Apply' => 'st-new',
                        'Review', 'Screening' => 'st-screening',
                        'Interview', 'For Interview' => 'st-interview',
                        'Road_Test', 'Road Test', 'Testing' => 'st-default',
                        'Offer', 'Offer_Sent', 'Offer Sent' => 'st-offer',
                        'Hired' => 'st-hired',
                        'Rejected', 'Withdrawn' => 'st-rejected',
                        'Blacklisted' => 'st-blacklisted',
                        default => 'st-default'
                    };
                ?>
                <tr>
                    <td>#<?php echo $app['id']; ?></td>
                    <td style="font-weight:500;"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></td>
                    <td style="font-size:0.8rem;color:#94a3b8;"><?php echo htmlspecialchars($app['email'] ?? ''); ?></td>
                    <td style="font-size:0.8rem;"><?php echo htmlspecialchars($app['job_title'] ?? 'N/A'); ?></td>
                    <td style="font-size:0.8rem;color:#94a3b8;"><?php echo htmlspecialchars($app['department_name'] ?? 'N/A'); ?></td>
                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($app['status']); ?></span></td>
                    <td style="font-size:0.8rem;color:#94a3b8;"><?php echo $app['applied_date'] ? date('M d, Y', strtotime($app['applied_date'])) : 'N/A'; ?></td>
                    <td style="white-space:nowrap;">
                        <?php if ($app['status'] !== 'Blacklisted'): ?>
                        <button class="act-btn danger" onclick="openBlacklist(<?php echo $app['id']; ?>, '<?php echo htmlspecialchars(addslashes($app['first_name'] . ' ' . $app['last_name'])); ?>')" title="Blacklist">
                            <i data-lucide="ban" style="width:12px;height:12px;"></i>
                        </button>
                        <?php else: ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="unblacklist">
                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                            <button type="submit" class="act-btn" title="Remove from blacklist" style="color:#10b981;">Unblock</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('PERMANENTLY anonymize this applicant\'s data? This cannot be undone.');">
                            <input type="hidden" name="action" value="gdpr_delete">
                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                            <button type="submit" class="act-btn warn" title="Data Privacy Delete">
                                <i data-lucide="shield-off" style="width:12px;height:12px;"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Blacklist Modal -->
<div class="bl-modal-overlay" id="bl-modal">
    <div class="bl-modal">
        <h3><i data-lucide="ban" style="width:18px;height:18px;display:inline;vertical-align:middle;color:#ef4444;margin-right:0.4rem;"></i>Blacklist Applicant</h3>
        <p style="color:#94a3b8;font-size:0.85rem;margin-bottom:1rem;">Blacklisting: <strong id="bl-name" style="color:#e2e8f0;"></strong></p>
        <form method="POST">
            <input type="hidden" name="action" value="blacklist">
            <input type="hidden" name="application_id" id="bl-app-id" value="">
            <textarea name="reason" placeholder="Reason for blacklisting (optional)..."></textarea>
            <div class="btn-row">
                <button type="button" class="btn-secondary" onclick="closeBlacklist()">Cancel</button>
                <button type="submit" class="btn-primary">Confirm Blacklist</button>
            </div>
        </form>
    </div>
</div>

<script>
function openBlacklist(id, name) {
    document.getElementById('bl-app-id').value = id;
    document.getElementById('bl-name').textContent = name;
    document.getElementById('bl-modal').classList.add('active');
}
function closeBlacklist() {
    document.getElementById('bl-modal').classList.remove('active');
}
if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
</div>
