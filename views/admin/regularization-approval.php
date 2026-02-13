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
    header('Location: index.php?page=regularization-approval');
    exit();
}

require_once '../../database/config.php';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $employee_id = intval($_POST['employee_id'] ?? 0);
    
    if ($action === 'approve_regularization' && $employee_id > 0) {
        try {
            executeQuery("UPDATE user_accounts SET employment_status = 'Regular', regularized_at = NOW(), regularized_by = ? WHERE id = ?",
                [$_SESSION['user_id'], $employee_id]);
            echo '<div class="alert alert-success">Employee regularized successfully.</div>';
        } catch (Exception $e) {
            // Fallback if columns don't exist yet
            try {
                executeQuery("UPDATE user_accounts SET status = 'Active' WHERE id = ?", [$employee_id]);
                echo '<div class="alert alert-success">Employee status updated. (employment_status column may need migration)</div>';
            } catch (Exception $e2) {
                echo '<div class="alert alert-error">Error processing regularization.</div>';
            }
        }
    }
    
    if ($action === 'reject_regularization' && $employee_id > 0) {
        $reason = trim($_POST['reason'] ?? '');
        try {
            executeQuery("UPDATE user_accounts SET employment_status = 'Probationary - Extended', updated_at = NOW() WHERE id = ?", [$employee_id]);
            echo '<div class="alert alert-success">Regularization rejected. Probation extended.</div>';
        } catch (Exception $e) {
            echo '<div class="alert alert-error">Error processing rejection.</div>';
        }
    }
    
    if ($action === 'terminate' && $employee_id > 0) {
        $reason = trim($_POST['reason'] ?? '');
        try {
            executeQuery("UPDATE user_accounts SET status = 'Inactive', updated_at = NOW() WHERE id = ?", [$employee_id]);
            echo '<div class="alert alert-success">Employee terminated and set to Inactive.</div>';
        } catch (Exception $e) {
            echo '<div class="alert alert-error">Error processing termination.</div>';
        }
    }
}

// Fetch probationary employees (hired within last 6 months)
try {
    $probationary = fetchAll("
        SELECT ua.id, ua.first_name, ua.last_name,
            COALESCE(ua.company_email, ua.personal_email, '') as email,
            ua.created_at,
            d.department_name, r.role_name,
            DATEDIFF(NOW(), ua.created_at) as days_employed,
            ROUND(DATEDIFF(NOW(), ua.created_at) / 30, 1) as months_employed
        FROM user_accounts ua
        JOIN roles r ON r.id = ua.role_id
        LEFT JOIN departments d ON d.id = ua.department_id
        WHERE r.role_type = 'Employee'
        AND ua.status = 'Active'
        AND ua.created_at >= DATE_SUB(NOW(), INTERVAL 7 MONTH)
        ORDER BY ua.created_at ASC
    ");
} catch (Exception $e) {
    $probationary = [];
}

// Fetch performance reviews for these employees
$review_map = [];
try {
    if (!empty($probationary)) {
        $emp_ids = array_column($probationary, 'id');
        $placeholders = implode(',', array_fill(0, count($emp_ids), '?'));
        $reviews = fetchAll("
            SELECT pr.employee_id, pr.review_type, pr.rating, pr.overall_score, pr.status, pr.reviewer_id,
                pr.created_at, ua.first_name as reviewer_first, ua.last_name as reviewer_last
            FROM performance_reviews pr
            LEFT JOIN user_accounts ua ON ua.id = pr.reviewer_id
            WHERE pr.employee_id IN ($placeholders)
            ORDER BY pr.created_at DESC
        ", $emp_ids);
        foreach ($reviews as $rev) {
            $review_map[$rev['employee_id']][] = $rev;
        }
    }
} catch (Exception $e) {
    $review_map = [];
}

// Fetch requirement completion stats
$req_map = [];
try {
    if (!empty($probationary)) {
        $emp_ids = array_column($probationary, 'id');
        $placeholders = implode(',', array_fill(0, count($emp_ids), '?'));
        $reqs = fetchAll("
            SELECT user_id,
                COUNT(*) as total,
                SUM(CASE WHEN LOWER(status) = 'approved' OR remarks LIKE 'Admin bypass:%' THEN 1 ELSE 0 END) as completed
            FROM employee_requirements
            WHERE user_id IN ($placeholders)
            GROUP BY user_id
        ", $emp_ids);
        foreach ($reqs as $r) {
            $req_map[$r['user_id']] = $r;
        }
    }
} catch (Exception $e) {
    $req_map = [];
}

// Fetch onboarding completion
$onboard_map = [];
try {
    if (!empty($probationary)) {
        $emp_ids = array_column($probationary, 'id');
        $placeholders = implode(',', array_fill(0, count($emp_ids), '?'));
        $onboard = fetchAll("
            SELECT eop.user_id,
                COUNT(*) as total_tasks,
                SUM(CASE WHEN eop.status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks
            FROM employee_onboarding_progress eop
            WHERE eop.user_id IN ($placeholders)
            GROUP BY eop.user_id
        ", $emp_ids);
        foreach ($onboard as $ob) {
            $onboard_map[$ob['user_id']] = $ob;
        }
    }
} catch (Exception $e) {
    $onboard_map = [];
}

// Categorize employees
$due_3rd = [];
$due_5th = [];
$overdue = [];
$not_yet = [];

foreach ($probationary as $emp) {
    $months = floatval($emp['months_employed']);
    if ($months >= 5.5) {
        $overdue[] = $emp;
    } elseif ($months >= 4.5) {
        $due_5th[] = $emp;
    } elseif ($months >= 2.5) {
        $due_3rd[] = $emp;
    } else {
        $not_yet[] = $emp;
    }
}
?>
<div data-page-title="Regularization Approval">
<style>
    .reg-header { background:rgba(30,41,54,0.6); border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; }
    .reg-header h1 { font-size:1.5rem; color:#e2e8f0; margin-bottom:0.25rem; }
    .reg-header p { color:#94a3b8; font-size:0.9rem; }
    .reg-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:1rem; margin-bottom:1.5rem; }
    .reg-stat { background:rgba(30,41,54,0.6); border-radius:10px; padding:1rem; border:1px solid rgba(58,69,84,0.5); text-align:center; }
    .reg-stat h3 { font-size:1.5rem; color:#e2e8f0; }
    .reg-stat p { font-size:0.8rem; color:#94a3b8; }
    .reg-card { background:rgba(30,41,54,0.6); border-radius:12px; padding:1.5rem; border:1px solid rgba(58,69,84,0.5); margin-bottom:1.5rem; }
    .reg-card h2 { font-size:1.1rem; color:#e2e8f0; margin-bottom:1rem; display:flex; align-items:center; gap:0.5rem; }
    .reg-card h2 i { width:18px; height:18px; }
    .emp-item { background:rgba(15,23,42,0.6); border-radius:10px; padding:1.25rem; margin-bottom:0.75rem; border:1px solid rgba(58,69,84,0.3); }
    .emp-top { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.75rem; flex-wrap:wrap; gap:0.5rem; }
    .emp-name { color:#e2e8f0; font-size:1rem; font-weight:600; }
    .emp-dept { color:#94a3b8; font-size:0.8rem; }
    .emp-tenure { font-size:0.85rem; font-weight:600; }
    .emp-checks { display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:0.75rem; }
    .check-item { display:flex; align-items:center; gap:0.4rem; font-size:0.8rem; padding:0.3rem 0.6rem; border-radius:6px; background:rgba(15,23,42,0.4); }
    .check-pass { color:#10b981; }
    .check-fail { color:#ef4444; }
    .check-warn { color:#fbbf24; }
    .check-na { color:#64748b; }
    .emp-reviews { margin-bottom:0.75rem; }
    .review-tag { display:inline-block; padding:0.2rem 0.5rem; border-radius:4px; font-size:0.7rem; margin-right:0.4rem; margin-bottom:0.25rem; }
    .rv-complete { background:rgba(16,185,129,0.2); color:#10b981; }
    .rv-pending { background:rgba(251,191,36,0.2); color:#fbbf24; }
    .emp-actions { display:flex; gap:0.5rem; flex-wrap:wrap; }
    .btn-approve { background:#10b981; color:#fff; border:none; padding:0.4rem 0.85rem; border-radius:6px; cursor:pointer; font-size:0.8rem; font-weight:500; }
    .btn-approve:hover { background:#059669; }
    .btn-reject { background:rgba(251,191,36,0.2); color:#fbbf24; border:1px solid rgba(251,191,36,0.3); padding:0.4rem 0.85rem; border-radius:6px; cursor:pointer; font-size:0.8rem; }
    .btn-reject:hover { background:rgba(251,191,36,0.3); }
    .btn-terminate { background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.3); padding:0.4rem 0.85rem; border-radius:6px; cursor:pointer; font-size:0.8rem; }
    .btn-terminate:hover { background:rgba(239,68,68,0.3); }
    .alert { padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:0.85rem; }
    .alert-success { background:rgba(16,185,129,0.15); color:#10b981; border:1px solid rgba(16,185,129,0.3); }
    .alert-error { background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.3); }
    .empty-state { text-align:center; padding:2rem; color:#64748b; font-size:0.85rem; }
    .section-badge { font-size:0.75rem; padding:0.15rem 0.5rem; border-radius:4px; margin-left:0.5rem; }
    .badge-urgent { background:rgba(239,68,68,0.2); color:#ef4444; }
    .badge-due { background:rgba(249,115,22,0.2); color:#f97316; }
    .badge-upcoming { background:rgba(14,165,233,0.2); color:#0ea5e9; }
</style>

<div class="reg-header">
    <div>
        <h1><i data-lucide="badge-check" style="width:22px;height:22px;display:inline;vertical-align:middle;color:#ef4444;margin-right:0.4rem;"></i>Regularization Approval</h1>
        <p>Final checker — review probationary employees for regularization, extension, or termination</p>
    </div>
</div>

<div class="reg-stats">
    <div class="reg-stat"><h3><?php echo count($probationary); ?></h3><p>Total Probationary</p></div>
    <div class="reg-stat"><h3 style="color:#ef4444;"><?php echo count($overdue); ?></h3><p>Overdue (5.5+ mo)</p></div>
    <div class="reg-stat"><h3 style="color:#f97316;"><?php echo count($due_5th); ?></h3><p>5th Month Due</p></div>
    <div class="reg-stat"><h3 style="color:#0ea5e9;"><?php echo count($due_3rd); ?></h3><p>3rd Month Due</p></div>
    <div class="reg-stat"><h3 style="color:#64748b;"><?php echo count($not_yet); ?></h3><p>Not Yet Due</p></div>
</div>

<?php
function renderEmployeeList($employees, $review_map, $req_map, $onboard_map, $section_title, $badge_class) {
    if (empty($employees)) return;
?>
<div class="reg-card">
    <h2><i data-lucide="users"></i> <?php echo $section_title; ?> (<?php echo count($employees); ?>)<span class="section-badge <?php echo $badge_class; ?>"><?php echo count($employees); ?></span></h2>
    <?php foreach ($employees as $emp):
        $reviews = $review_map[$emp['id']] ?? [];
        $reqs = $req_map[$emp['id']] ?? null;
        $onboard = $onboard_map[$emp['id']] ?? null;
        
        $req_complete = $reqs ? ($reqs['total'] > 0 && $reqs['completed'] >= $reqs['total']) : false;
        $onboard_complete = $onboard ? ($onboard['total_tasks'] > 0 && $onboard['completed_tasks'] >= $onboard['total_tasks']) : false;
        $has_reviews = !empty($reviews);
        
        $tenure_color = $emp['months_employed'] >= 5.5 ? '#ef4444' : ($emp['months_employed'] >= 4.5 ? '#f97316' : '#0ea5e9');
    ?>
    <div class="emp-item">
        <div class="emp-top">
            <div>
                <div class="emp-name"><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></div>
                <div class="emp-dept"><?php echo htmlspecialchars($emp['department_name'] ?? 'N/A'); ?> · <?php echo htmlspecialchars($emp['role_name']); ?> · <?php echo htmlspecialchars($emp['email']); ?></div>
            </div>
            <div class="emp-tenure" style="color:<?php echo $tenure_color; ?>;">
                <?php echo $emp['months_employed']; ?> months
                <div style="font-size:0.7rem;color:#64748b;font-weight:400;">Since <?php echo date('M d, Y', strtotime($emp['created_at'])); ?></div>
            </div>
        </div>
        
        <div class="emp-checks">
            <div class="check-item <?php echo $req_complete ? 'check-pass' : ($reqs ? 'check-fail' : 'check-na'); ?>">
                <i data-lucide="<?php echo $req_complete ? 'check-circle' : ($reqs ? 'x-circle' : 'minus-circle'); ?>" style="width:14px;height:14px;"></i>
                Requirements: <?php echo $reqs ? ($reqs['completed'] . '/' . $reqs['total']) : 'N/A'; ?>
            </div>
            <div class="check-item <?php echo $onboard_complete ? 'check-pass' : ($onboard ? 'check-warn' : 'check-na'); ?>">
                <i data-lucide="<?php echo $onboard_complete ? 'check-circle' : ($onboard ? 'alert-circle' : 'minus-circle'); ?>" style="width:14px;height:14px;"></i>
                Onboarding: <?php echo $onboard ? ($onboard['completed_tasks'] . '/' . $onboard['total_tasks']) : 'N/A'; ?>
            </div>
            <div class="check-item <?php echo $has_reviews ? 'check-pass' : 'check-warn'; ?>">
                <i data-lucide="<?php echo $has_reviews ? 'check-circle' : 'alert-circle'; ?>" style="width:14px;height:14px;"></i>
                Reviews: <?php echo $has_reviews ? count($reviews) : '0'; ?>
            </div>
        </div>
        
        <?php if ($has_reviews): ?>
        <div class="emp-reviews">
            <?php foreach ($reviews as $rev): ?>
            <span class="review-tag <?php echo ($rev['status'] ?? '') === 'Completed' ? 'rv-complete' : 'rv-pending'; ?>">
                <?php echo htmlspecialchars($rev['review_type'] ?? 'Review'); ?>
                <?php if (!empty($rev['rating'])): ?> — <?php echo $rev['rating']; ?>/5<?php elseif (!empty($rev['overall_score'])): ?> — <?php echo $rev['overall_score']; ?>/5<?php endif; ?>
                by <?php echo htmlspecialchars(($rev['reviewer_first'] ?? '') . ' ' . ($rev['reviewer_last'] ?? '')); ?>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="emp-actions">
            <form method="POST" style="display:inline;" onsubmit="return confirm('Approve regularization for this employee?');">
                <input type="hidden" name="action" value="approve_regularization">
                <input type="hidden" name="employee_id" value="<?php echo $emp['id']; ?>">
                <button type="submit" class="btn-approve"><i data-lucide="check" style="width:13px;height:13px;display:inline;vertical-align:middle;margin-right:0.2rem;"></i>Regularize</button>
            </form>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Extend probation for this employee?');">
                <input type="hidden" name="action" value="reject_regularization">
                <input type="hidden" name="employee_id" value="<?php echo $emp['id']; ?>">
                <button type="submit" class="btn-reject"><i data-lucide="clock" style="width:13px;height:13px;display:inline;vertical-align:middle;margin-right:0.2rem;"></i>Extend Probation</button>
            </form>
            <form method="POST" style="display:inline;" onsubmit="return confirm('TERMINATE this employee? This will set their account to Inactive.');">
                <input type="hidden" name="action" value="terminate">
                <input type="hidden" name="employee_id" value="<?php echo $emp['id']; ?>">
                <button type="submit" class="btn-terminate"><i data-lucide="user-x" style="width:13px;height:13px;display:inline;vertical-align:middle;margin-right:0.2rem;"></i>Terminate</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php } ?>

<?php
renderEmployeeList($overdue, $review_map, $req_map, $onboard_map, 'Overdue — Past 5.5 Months', 'badge-urgent');
renderEmployeeList($due_5th, $review_map, $req_map, $onboard_map, '5th Month Review Due', 'badge-due');
renderEmployeeList($due_3rd, $review_map, $req_map, $onboard_map, '3rd Month Review Due', 'badge-upcoming');
renderEmployeeList($not_yet, $review_map, $req_map, $onboard_map, 'Not Yet Due (< 2.5 months)', 'badge-upcoming');
?>

<?php if (empty($probationary)): ?>
<div class="reg-card">
    <div class="empty-state">
        <i data-lucide="badge-check" style="width:40px;height:40px;margin-bottom:0.5rem;color:#64748b;"></i>
        <p>No probationary employees found.</p>
    </div>
</div>
<?php endif; ?>

<script>
if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
</div>
