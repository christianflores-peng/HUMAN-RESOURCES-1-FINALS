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
    header('Location: index.php?page=requirements-masterlist');
    exit();
}

require_once '../../database/config.php';

// Ensure requirements master table exists in case migrations were not run
try {
    executeQuery("CREATE TABLE IF NOT EXISTS onboarding_requirements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        document_type VARCHAR(100) NOT NULL,
        description TEXT NULL,
        is_mandatory TINYINT(1) DEFAULT 1,
        category VARCHAR(100) DEFAULT 'General',
        is_active TINYINT(1) DEFAULT 1,
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_document_type (document_type),
        INDEX idx_category (category),
        INDEX idx_active (is_active),
        CONSTRAINT fk_onboarding_requirements_created_by FOREIGN KEY (created_by) REFERENCES user_accounts(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    // Keep page running; below actions will show explicit errors if needed.
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_requirement') {
        $doc_type = trim($_POST['document_type'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $is_mandatory = intval($_POST['is_mandatory'] ?? 1);
        $category = trim($_POST['category'] ?? 'General');
        
        if (empty($doc_type)) {
            echo '<div class="alert alert-error">Document type is required.</div>';
        } else {
            try {
                executeQuery("INSERT INTO onboarding_requirements (document_type, description, is_mandatory, category, is_active, created_by, created_at) VALUES (?, ?, ?, ?, 1, ?, NOW())",
                    [$doc_type, $description, $is_mandatory, $category, $_SESSION['user_id']]);
                echo '<div class="alert alert-success">Requirement "' . htmlspecialchars($doc_type) . '" added successfully.</div>';
            } catch (Exception $e) {
                echo '<div class="alert alert-error">Error adding requirement. Table may need migration.</div>';
            }
        }
    }
    
    if ($action === 'update_requirement') {
        $req_id = intval($_POST['requirement_id'] ?? 0);
        $doc_type = trim($_POST['document_type'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $is_mandatory = intval($_POST['is_mandatory'] ?? 1);
        $category = trim($_POST['category'] ?? 'General');
        
        if ($req_id > 0 && !empty($doc_type)) {
            try {
                executeQuery("UPDATE onboarding_requirements SET document_type = ?, description = ?, is_mandatory = ?, category = ?, updated_at = NOW() WHERE id = ?",
                    [$doc_type, $description, $is_mandatory, $category, $req_id]);
                echo '<div class="alert alert-success">Requirement updated.</div>';
            } catch (Exception $e) {
                echo '<div class="alert alert-error">Error updating requirement.</div>';
            }
        }
    }
    
    if ($action === 'toggle_requirement') {
        $req_id = intval($_POST['requirement_id'] ?? 0);
        if ($req_id > 0) {
            try {
                executeQuery("UPDATE onboarding_requirements SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?", [$req_id]);
                echo '<div class="alert alert-success">Requirement status toggled.</div>';
            } catch (Exception $e) {
                echo '<div class="alert alert-error">Error toggling requirement.</div>';
            }
        }
    }
    
    if ($action === 'delete_requirement') {
        $req_id = intval($_POST['requirement_id'] ?? 0);
        if ($req_id > 0) {
            try {
                executeQuery("DELETE FROM onboarding_requirements WHERE id = ?", [$req_id]);
                echo '<div class="alert alert-success">Requirement deleted.</div>';
            } catch (Exception $e) {
                echo '<div class="alert alert-error">Error deleting requirement.</div>';
            }
        }
    }
    
    if ($action === 'bypass_employee') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $req_id = intval($_POST['requirement_id'] ?? 0);
        $bypass_reason = trim($_POST['bypass_reason'] ?? '');
        
        if ($user_id > 0 && $req_id > 0) {
            try {
                $note = 'Admin bypass: ' . $bypass_reason;
                executeQuery("INSERT INTO employee_requirements (user_id, document_type, file_path, status, remarks, uploaded_at, updated_at)
                    VALUES (?, (SELECT document_type FROM onboarding_requirements WHERE id = ?), 'admin_bypass', 'approved', ?, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE status = 'approved', remarks = VALUES(remarks), file_path = 'admin_bypass', updated_at = NOW()",
                    [$user_id, $req_id, $note]);
                echo '<div class="alert alert-success">Requirement bypassed for employee.</div>';
            } catch (Exception $e) {
                echo '<div class="alert alert-error">Error bypassing requirement.</div>';
            }
        }
    }
}

// Fetch masterlist requirements
try {
    $requirements = fetchAll("
        SELECT orq.*, ua.first_name, ua.last_name
        FROM onboarding_requirements orq
        LEFT JOIN user_accounts ua ON ua.id = orq.created_by
        ORDER BY orq.category, orq.document_type
    ");
} catch (Exception $e) {
    $requirements = [];
}

// Fetch employee submission stats
try {
    $submission_stats = fetchAll("
        SELECT er.document_type, 
            COUNT(*) as total_submissions,
            SUM(CASE WHEN LOWER(er.status) = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN LOWER(er.status) = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN LOWER(er.status) = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN er.remarks LIKE 'Admin bypass:%' THEN 1 ELSE 0 END) as bypassed
        FROM employee_requirements er
        GROUP BY er.document_type
    ");
    $stats_map = [];
    foreach ($submission_stats as $ss) {
        $stats_map[$ss['document_type']] = $ss;
    }
} catch (Exception $e) {
    $stats_map = [];
}

// Fetch recent employees for bypass
try {
    $recent_employees = fetchAll("
        SELECT ua.id, ua.first_name, ua.last_name, d.department_name
        FROM user_accounts ua
        JOIN roles r ON r.id = ua.role_id
        LEFT JOIN departments d ON d.id = ua.department_id
        WHERE r.role_type = 'Employee' AND ua.status = 'Active'
        AND ua.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        ORDER BY ua.created_at DESC
        LIMIT 50
    ");
} catch (Exception $e) {
    $recent_employees = [];
}

// Fetch employee submitted requirements for filtering
try {
    $employee_submissions = fetchAll("
        SELECT er.user_id, orq.id as requirement_id, orq.document_type
        FROM employee_requirements er
        JOIN onboarding_requirements orq ON orq.document_type = er.document_type
        WHERE er.status IN ('approved', 'pending')
        OR er.file_path = 'admin_bypass'
    ");
    $submitted_map = [];
    foreach ($employee_submissions as $sub) {
        if (!isset($submitted_map[$sub['user_id']])) {
            $submitted_map[$sub['user_id']] = [];
        }
        $submitted_map[$sub['user_id']][] = $sub['requirement_id'];
    }
} catch (Exception $e) {
    $submitted_map = [];
}

$categories = ['General', 'Government IDs', 'Medical', 'Employment', 'Education', 'Company-Specific'];
?>
<div data-page-title="Requirements Masterlist">
<style>
    .rm-header { background:rgba(30,41,54,0.6); border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; }
    .rm-header h1 { font-size:1.5rem; color:#e2e8f0; margin-bottom:0.25rem; }
    .rm-header p { color:#94a3b8; font-size:0.9rem; }
    .rm-card { background:rgba(30,41,54,0.6); border-radius:12px; padding:1.5rem; border:1px solid rgba(58,69,84,0.5); margin-bottom:1.5rem; }
    .rm-card h2 { font-size:1.1rem; color:#e2e8f0; margin-bottom:1rem; display:flex; align-items:center; gap:0.5rem; }
    .rm-card h2 i { width:18px; height:18px; }
    .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem; }
    .form-group { margin-bottom:0.75rem; }
    .form-group label { display:block; color:#94a3b8; font-size:0.85rem; margin-bottom:0.3rem; }
    .form-group input, .form-group select, .form-group textarea { width:100%; padding:0.5rem 0.75rem; background:rgba(15,23,42,0.6); border:1px solid rgba(58,69,84,0.5); border-radius:8px; color:#e2e8f0; font-size:0.85rem; }
    .form-group textarea { min-height:60px; resize:vertical; }
    .btn-primary { background:#ef4444; color:#fff; border:none; padding:0.5rem 1rem; border-radius:8px; cursor:pointer; font-size:0.85rem; font-weight:500; }
    .btn-primary:hover { background:#dc2626; }
    .btn-secondary { background:rgba(58,69,84,0.5); color:#e2e8f0; border:none; padding:0.5rem 1rem; border-radius:8px; cursor:pointer; font-size:0.85rem; }
    .req-item { background:rgba(15,23,42,0.6); border-radius:8px; padding:1rem; margin-bottom:0.75rem; border:1px solid rgba(58,69,84,0.3); }
    .req-item-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem; }
    .req-item-name { color:#e2e8f0; font-size:0.95rem; font-weight:600; }
    .req-item-badges { display:flex; gap:0.4rem; align-items:center; }
    .badge-mandatory { background:rgba(239,68,68,0.2); color:#ef4444; padding:0.15rem 0.5rem; border-radius:4px; font-size:0.7rem; font-weight:600; }
    .badge-optional { background:rgba(251,191,36,0.2); color:#fbbf24; padding:0.15rem 0.5rem; border-radius:4px; font-size:0.7rem; font-weight:600; }
    .badge-active { background:rgba(16,185,129,0.2); color:#10b981; padding:0.15rem 0.5rem; border-radius:4px; font-size:0.7rem; }
    .badge-inactive { background:rgba(239,68,68,0.2); color:#ef4444; padding:0.15rem 0.5rem; border-radius:4px; font-size:0.7rem; }
    .badge-cat { background:rgba(139,92,246,0.2); color:#8b5cf6; padding:0.15rem 0.5rem; border-radius:4px; font-size:0.7rem; }
    .req-item-desc { color:#94a3b8; font-size:0.8rem; margin-bottom:0.5rem; }
    .req-item-stats { display:flex; gap:1rem; font-size:0.75rem; color:#64748b; margin-bottom:0.5rem; }
    .req-item-stats span { display:flex; align-items:center; gap:0.25rem; }
    .req-item-actions { display:flex; gap:0.4rem; }
    .act-btn { background:rgba(58,69,84,0.5); border:none; color:#94a3b8; padding:0.25rem 0.5rem; border-radius:4px; cursor:pointer; font-size:0.75rem; transition:all 0.2s; }
    .act-btn:hover { background:rgba(58,69,84,0.8); color:#e2e8f0; }
    .act-btn.danger:hover { background:rgba(239,68,68,0.3); color:#ef4444; }
    .cat-title { font-size:0.8rem; color:#94a3b8; margin:1.25rem 0 0.5rem; text-transform:uppercase; letter-spacing:0.05em; padding-bottom:0.25rem; border-bottom:1px solid rgba(58,69,84,0.3); }
    .alert { padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:0.85rem; }
    .alert-success { background:rgba(16,185,129,0.15); color:#10b981; border:1px solid rgba(16,185,129,0.3); }
    .alert-error { background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.3); }
    .empty-state { text-align:center; padding:2rem; color:#64748b; font-size:0.85rem; }
    .bypass-section { background:rgba(251,191,36,0.05); border:1px solid rgba(251,191,36,0.2); border-radius:8px; padding:1rem; margin-top:1rem; }
    .bypass-section h3 { color:#fbbf24; font-size:0.95rem; margin-bottom:0.75rem; }
    @media(max-width:768px) { .form-grid { grid-template-columns:1fr; } }
</style>

<div class="rm-header">
    <div>
        <h1><i data-lucide="clipboard-list" style="width:22px;height:22px;display:inline;vertical-align:middle;color:#ef4444;margin-right:0.4rem;"></i>Requirements Masterlist</h1>
        <p>Manage onboarding document requirements, override and bypass for specific employees</p>
    </div>
    <button class="btn-primary" onclick="document.getElementById('add-form').style.display = document.getElementById('add-form').style.display === 'none' ? 'block' : 'none';">
        <i data-lucide="plus" style="width:14px;height:14px;display:inline;vertical-align:middle;margin-right:0.3rem;"></i>Add Requirement
    </button>
</div>

<!-- Add Requirement Form -->
<div class="rm-card" id="add-form" style="display:none;">
    <h2><i data-lucide="plus-circle"></i> Add New Requirement</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add_requirement">
        <div class="form-grid">
            <div class="form-group">
                <label>Document Type / Name</label>
                <input type="text" name="document_type" placeholder="e.g. SSS ID, NBI Clearance, TIN" required>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category">
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Description / Instructions</label>
            <textarea name="description" placeholder="Instructions for the employee on how to submit this requirement..."></textarea>
        </div>
        <div class="form-group">
            <label>Mandatory?</label>
            <select name="is_mandatory">
                <option value="1">Yes - Required for all new hires</option>
                <option value="0">No - Optional</option>
            </select>
        </div>
        <div style="display:flex;gap:0.75rem;">
            <button type="submit" class="btn-primary">Add Requirement</button>
            <button type="button" class="btn-secondary" onclick="document.getElementById('add-form').style.display='none';">Cancel</button>
        </div>
    </form>
</div>

<!-- Requirements List -->
<div class="rm-card">
    <h2><i data-lucide="list"></i> All Requirements (<?php echo count($requirements); ?>)</h2>
    <?php if (empty($requirements)): ?>
        <div class="empty-state">
            <i data-lucide="clipboard-list" style="width:40px;height:40px;margin-bottom:0.5rem;color:#64748b;"></i>
            <p>No onboarding requirements configured yet.</p>
            <p style="font-size:0.8rem;margin-top:0.25rem;">Add requirements that new hires must submit during onboarding.</p>
        </div>
    <?php else: ?>
        <?php
        $current_cat = '';
        foreach ($requirements as $req):
            $cat = $req['category'] ?? 'General';
            if ($cat !== $current_cat):
                $current_cat = $cat;
        ?>
            <div class="cat-title"><?php echo htmlspecialchars($current_cat); ?></div>
        <?php endif; ?>
        <?php
            $doc_stats = $stats_map[$req['document_type']] ?? null;
        ?>
        <div class="req-item">
            <div class="req-item-header">
                <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                    <span class="req-item-name"><?php echo htmlspecialchars($req['document_type']); ?></span>
                    <span class="badge-cat"><?php echo htmlspecialchars($cat); ?></span>
                    <?php if (!empty($req['is_mandatory'])): ?>
                        <span class="badge-mandatory">Mandatory</span>
                    <?php else: ?>
                        <span class="badge-optional">Optional</span>
                    <?php endif; ?>
                    <?php if (!empty($req['is_active'])): ?>
                        <span class="badge-active">Active</span>
                    <?php else: ?>
                        <span class="badge-inactive">Disabled</span>
                    <?php endif; ?>
                </div>
                <div class="req-item-actions">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="toggle_requirement">
                        <input type="hidden" name="requirement_id" value="<?php echo $req['id']; ?>">
                        <button type="submit" class="act-btn"><?php echo $req['is_active'] ? 'Disable' : 'Enable'; ?></button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete_requirement">
                        <input type="hidden" name="requirement_id" value="<?php echo $req['id']; ?>">
                        <button type="submit" class="act-btn danger"
                            data-confirm-title="Delete Requirement"
                            data-confirm-variant="danger"
                            data-confirm-ok="Yes, delete"
                            data-confirm-cancel="Cancel"
                            data-confirm="Delete this requirement?">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
            <?php if (!empty($req['description'])): ?>
                <div class="req-item-desc"><?php echo htmlspecialchars($req['description']); ?></div>
            <?php endif; ?>
            <?php if ($doc_stats): ?>
            <div class="req-item-stats">
                <span style="color:#0ea5e9;">Submitted: <?php echo $doc_stats['total_submissions']; ?></span>
                <span style="color:#10b981;">Approved: <?php echo $doc_stats['approved']; ?></span>
                <span style="color:#fbbf24;">Pending: <?php echo $doc_stats['pending']; ?></span>
                <span style="color:#ef4444;">Rejected: <?php echo $doc_stats['rejected']; ?></span>
                <?php if ($doc_stats['bypassed'] > 0): ?>
                <span style="color:#8b5cf6;">Bypassed: <?php echo $doc_stats['bypassed']; ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Bypass Section -->
<div class="rm-card">
    <h2><i data-lucide="shield-off"></i> Bypass Requirement for Employee</h2>
    <p style="color:#94a3b8;font-size:0.8rem;margin-bottom:1rem;">Override a specific requirement for an employee (e.g., exemption, delayed submission).</p>
    <form method="POST">
        <input type="hidden" name="action" value="bypass_employee">
        <div class="form-grid">
            <div class="form-group">
                <label>Employee</label>
                <select name="user_id" id="bypass_employee" required onchange="filterRequirements()">
                    <option value="">Select employee...</option>
                    <?php foreach ($recent_employees as $emp): ?>
                    <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' — ' . ($emp['department_name'] ?? 'N/A')); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Requirement to Bypass</label>
                <select name="requirement_id" id="bypass_requirement" required>
                    <option value="">Select employee first...</option>
                    <?php foreach ($requirements as $req): ?>
                    <option value="<?php echo $req['id']; ?>" data-req-id="<?php echo $req['id']; ?>"><?php echo htmlspecialchars($req['document_type']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Reason for Bypass</label>
            <input type="text" name="bypass_reason" placeholder="e.g. Delayed submission approved by HR Head" required>
        </div>
        <button type="submit" class="btn-primary"
            data-confirm-title="Bypass Requirement"
            data-confirm-variant="danger"
            data-confirm-ok="Yes, bypass"
            data-confirm-cancel="Cancel"
            data-confirm="Bypass this requirement?">
            Confirm Bypass
        </button>
    </form>
</div>

<script>
// Employee submitted requirements map (PHP to JS)
const employeeSubmissions = <?php echo json_encode($submitted_map); ?>;

function filterRequirements() {
    const employeeSelect = document.getElementById('bypass_employee');
    const requirementSelect = document.getElementById('bypass_requirement');
    const selectedEmployeeId = employeeSelect.value;
    
    // Reset requirement dropdown
    requirementSelect.innerHTML = '<option value="">Select requirement...</option>';
    
    if (!selectedEmployeeId) {
        requirementSelect.innerHTML = '<option value="">Select employee first...</option>';
        return;
    }
    
    // Get submitted requirement IDs for this employee
    const submittedReqIds = employeeSubmissions[selectedEmployeeId] || [];
    
    // Get all requirement options and filter
    const allOptions = <?php echo json_encode(array_map(function($req) {
        return ['id' => $req['id'], 'name' => $req['document_type']];
    }, $requirements)); ?>;
    
    let availableCount = 0;
    allOptions.forEach(req => {
        // Only show requirements NOT yet submitted/bypassed by this employee
        if (!submittedReqIds.includes(req.id)) {
            const option = document.createElement('option');
            option.value = req.id;
            option.textContent = req.name;
            requirementSelect.appendChild(option);
            availableCount++;
        }
    });
    
    if (availableCount === 0) {
        requirementSelect.innerHTML = '<option value="">All requirements submitted/bypassed</option>';
        requirementSelect.disabled = true;
    } else {
        requirementSelect.disabled = false;
    }
}

if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
</div>
