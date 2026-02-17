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
    header('Location: index.php?page=approval-workflows');
    exit();
}

require_once '../../database/config.php';

// Ensure backing table exists (some environments may not have run migrations yet)
try {
    executeQuery("CREATE TABLE IF NOT EXISTS approval_workflows (
        id INT AUTO_INCREMENT PRIMARY KEY,
        workflow_name VARCHAR(255) NOT NULL,
        module VARCHAR(100) NOT NULL,
        steps JSON NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_module (module),
        INDEX idx_active (is_active),
        CONSTRAINT fk_approval_workflows_created_by FOREIGN KEY (created_by) REFERENCES user_accounts(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    // Keep page usable; actions below will surface errors if table operations fail.
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_workflow') {
        $workflow_name = trim($_POST['workflow_name'] ?? '');
        $module = trim($_POST['module'] ?? '');
        $steps_json = $_POST['steps'] ?? '[]';
        $workflow_id = intval($_POST['workflow_id'] ?? 0);
        
        if (empty($workflow_name) || empty($module)) {
            echo '<div class="alert alert-error">Workflow name and module are required.</div>';
        } else {
            try {
                if ($workflow_id > 0) {
                    executeQuery("UPDATE approval_workflows SET workflow_name = ?, module = ?, steps = ?, updated_at = NOW() WHERE id = ?",
                        [$workflow_name, $module, $steps_json, $workflow_id]);
                    echo '<div class="alert alert-success">Workflow updated successfully.</div>';
                } else {
                    executeQuery("INSERT INTO approval_workflows (workflow_name, module, steps, is_active, created_by, created_at) VALUES (?, ?, ?, 1, ?, NOW())",
                        [$workflow_name, $module, $steps_json, $_SESSION['user_id']]);
                    echo '<div class="alert alert-success">Workflow created successfully.</div>';
                }
            } catch (Exception $e) {
                echo '<div class="alert alert-error">Error saving workflow. Table may need migration.</div>';
            }
        }
    }
    
    if ($action === 'toggle_workflow') {
        $wf_id = intval($_POST['workflow_id'] ?? 0);
        try {
            executeQuery("UPDATE approval_workflows SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?", [$wf_id]);
            echo '<div class="alert alert-success">Workflow status toggled.</div>';
        } catch (Exception $e) {
            echo '<div class="alert alert-error">Error toggling workflow.</div>';
        }
    }
    
    if ($action === 'delete_workflow') {
        $wf_id = intval($_POST['workflow_id'] ?? 0);
        try {
            executeQuery("DELETE FROM approval_workflows WHERE id = ?", [$wf_id]);
            echo '<div class="alert alert-success">Workflow deleted.</div>';
        } catch (Exception $e) {
            echo '<div class="alert alert-error">Error deleting workflow.</div>';
        }
    }
}

// Fetch existing workflows
try {
    $workflows = fetchAll("
        SELECT aw.*, ua.first_name, ua.last_name
        FROM approval_workflows aw
        LEFT JOIN user_accounts ua ON ua.id = aw.created_by
        ORDER BY aw.module, aw.workflow_name
    ");
} catch (Exception $e) {
    $workflows = [];
}

// Fetch roles for step assignment
try {
    $roles = fetchAll("SELECT id, role_name, role_type FROM roles ORDER BY role_name");
} catch (Exception $e) {
    $roles = [];
}

// Fetch managers for step assignment
try {
    $managers = fetchAll("
        SELECT ua.id, ua.first_name, ua.last_name, r.role_name
        FROM user_accounts ua
        JOIN roles r ON r.id = ua.role_id
        WHERE r.role_type IN ('Admin', 'HR_Staff', 'Manager')
        AND ua.status = 'Active'
        ORDER BY ua.last_name, ua.first_name
    ");
} catch (Exception $e) {
    $managers = [];
}

$modules = ['Job Requisition', 'Job Posting', 'Offer Letter', 'Onboarding', 'Regularization', 'Performance Review'];
?>
<div data-page-title="Approval Workflows">
<style>
    .wf-header { background:rgba(30,41,54,0.6); border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; }
    .wf-header h1 { font-size:1.5rem; color:#e2e8f0; margin-bottom:0.25rem; }
    .wf-header p { color:#94a3b8; font-size:0.9rem; }
    .wf-card { background:rgba(30,41,54,0.6); border-radius:12px; padding:1.5rem; border:1px solid rgba(58,69,84,0.5); margin-bottom:1.5rem; }
    .wf-card h2 { font-size:1.1rem; color:#e2e8f0; margin-bottom:1rem; display:flex; align-items:center; gap:0.5rem; }
    .wf-item { background:rgba(15,23,42,0.6); border-radius:8px; padding:1rem; margin-bottom:0.75rem; border:1px solid rgba(58,69,84,0.3); }
    .wf-item-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem; }
    .wf-item-name { color:#e2e8f0; font-size:0.95rem; font-weight:600; }
    .wf-item-module { background:rgba(139,92,246,0.2); color:#8b5cf6; padding:0.2rem 0.6rem; border-radius:6px; font-size:0.75rem; }
    .wf-item-steps { color:#94a3b8; font-size:0.8rem; margin-bottom:0.5rem; }
    .wf-item-meta { display:flex; gap:1rem; align-items:center; font-size:0.75rem; color:#64748b; }
    .wf-badge-active { background:rgba(16,185,129,0.2); color:#10b981; padding:0.15rem 0.5rem; border-radius:4px; font-size:0.75rem; }
    .wf-badge-inactive { background:rgba(239,68,68,0.2); color:#ef4444; padding:0.15rem 0.5rem; border-radius:4px; font-size:0.75rem; }
    .wf-actions { display:flex; gap:0.5rem; }
    .wf-actions button { background:rgba(58,69,84,0.5); border:none; color:#94a3b8; padding:0.3rem 0.6rem; border-radius:6px; cursor:pointer; font-size:0.8rem; transition:all 0.2s; }
    .wf-actions button:hover { background:rgba(58,69,84,0.8); color:#e2e8f0; }
    .wf-actions button.danger:hover { background:rgba(239,68,68,0.3); color:#ef4444; }
    .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem; }
    .form-group { margin-bottom:1rem; }
    .form-group label { display:block; color:#94a3b8; font-size:0.85rem; margin-bottom:0.4rem; }
    .form-group input, .form-group select, .form-group textarea { width:100%; padding:0.6rem 0.75rem; background:rgba(15,23,42,0.6); border:1px solid rgba(58,69,84,0.5); border-radius:8px; color:#e2e8f0; font-size:0.85rem; }
    .form-group select { cursor:pointer; }
    .btn-primary { background:#ef4444; color:#fff; border:none; padding:0.6rem 1.25rem; border-radius:8px; cursor:pointer; font-size:0.85rem; font-weight:500; transition:background 0.2s; }
    .btn-primary:hover { background:#dc2626; }
    .btn-secondary { background:rgba(58,69,84,0.5); color:#e2e8f0; border:none; padding:0.6rem 1.25rem; border-radius:8px; cursor:pointer; font-size:0.85rem; }
    .step-list { margin:0.5rem 0; }
    .step-item { display:flex; align-items:center; gap:0.75rem; padding:0.5rem 0.75rem; background:rgba(15,23,42,0.4); border-radius:6px; margin-bottom:0.4rem; }
    .step-num { width:24px; height:24px; border-radius:50%; background:rgba(239,68,68,0.2); color:#ef4444; display:flex; align-items:center; justify-content:center; font-size:0.75rem; font-weight:700; flex-shrink:0; }
    .step-detail { flex:1; color:#e2e8f0; font-size:0.85rem; }
    .step-remove { background:none; border:none; color:#64748b; cursor:pointer; font-size:0.85rem; }
    .step-remove:hover { color:#ef4444; }
    .alert { padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:0.85rem; }
    .alert-success { background:rgba(16,185,129,0.15); color:#10b981; border:1px solid rgba(16,185,129,0.3); }
    .alert-error { background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.3); }
    .empty-state { text-align:center; padding:2rem; color:#64748b; font-size:0.85rem; }
    @media(max-width:768px) { .form-grid { grid-template-columns:1fr; } }
</style>

<div class="wf-header">
    <div>
        <h1><i data-lucide="git-branch" style="width:22px;height:22px;display:inline;vertical-align:middle;color:#ef4444;margin-right:0.4rem;"></i>Approval Workflows</h1>
        <p>Configure approval chains for job requisitions, postings, offers, and more</p>
    </div>
    <button class="btn-primary" onclick="document.getElementById('wf-form-section').style.display = document.getElementById('wf-form-section').style.display === 'none' ? 'block' : 'none';">
        <i data-lucide="plus" style="width:14px;height:14px;display:inline;vertical-align:middle;margin-right:0.3rem;"></i>New Workflow
    </button>
</div>

<!-- CREATE/EDIT FORM -->
<div class="wf-card" id="wf-form-section" style="display:none;">
    <h2><i data-lucide="plus-circle"></i> Create Workflow</h2>
    <form method="POST" id="wf-form">
        <input type="hidden" name="action" value="save_workflow">
        <input type="hidden" name="workflow_id" id="wf-edit-id" value="0">
        <div class="form-grid">
            <div class="form-group">
                <label>Workflow Name</label>
                <input type="text" name="workflow_name" id="wf-name" placeholder="e.g. Job Requisition Approval" required>
            </div>
            <div class="form-group">
                <label>Module</label>
                <select name="module" id="wf-module" required>
                    <option value="">Select module...</option>
                    <?php foreach ($modules as $mod): ?>
                    <option value="<?php echo $mod; ?>"><?php echo $mod; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Approval Steps</label>
            <div style="display:flex;gap:0.5rem;margin-bottom:0.5rem;">
                <select id="step-role" style="flex:1;padding:0.5rem;background:rgba(15,23,42,0.6);border:1px solid rgba(58,69,84,0.5);border-radius:6px;color:#e2e8f0;font-size:0.85rem;">
                    <option value="">Select approver role/person...</option>
                    <?php foreach ($roles as $role): ?>
                    <option value="role:<?php echo htmlspecialchars($role['role_name']); ?>">Role: <?php echo htmlspecialchars($role['role_name']); ?></option>
                    <?php endforeach; ?>
                    <?php foreach ($managers as $mgr): ?>
                    <option value="user:<?php echo $mgr['id']; ?>">User: <?php echo htmlspecialchars($mgr['first_name'] . ' ' . $mgr['last_name'] . ' (' . $mgr['role_name'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn-primary" onclick="addStep()" style="white-space:nowrap;">+ Add Step</button>
            </div>
            <div class="step-list" id="step-list"></div>
            <input type="hidden" name="steps" id="steps-json" value="[]">
        </div>
        <div style="display:flex;gap:0.75rem;">
            <button type="submit" class="btn-primary">Save Workflow</button>
            <button type="button" class="btn-secondary" onclick="resetForm()">Cancel</button>
        </div>
    </form>
</div>

<!-- EXISTING WORKFLOWS -->
<div class="wf-card">
    <h2><i data-lucide="list"></i> Configured Workflows (<?php echo count($workflows); ?>)</h2>
    <?php if (empty($workflows)): ?>
        <div class="empty-state">
            <i data-lucide="git-branch" style="width:40px;height:40px;margin-bottom:0.5rem;color:#64748b;"></i>
            <p>No approval workflows configured yet.</p>
            <p style="font-size:0.8rem;margin-top:0.25rem;">Create your first workflow to define approval chains.</p>
        </div>
    <?php else: ?>
        <?php
        $current_module = '';
        foreach ($workflows as $wf):
            if ($wf['module'] !== $current_module):
                $current_module = $wf['module'];
        ?>
            <div style="font-size:0.8rem;color:#94a3b8;margin:1rem 0 0.5rem;text-transform:uppercase;letter-spacing:0.05em;"><?php echo htmlspecialchars($current_module); ?></div>
        <?php endif; ?>
        <div class="wf-item">
            <div class="wf-item-header">
                <div style="display:flex;align-items:center;gap:0.75rem;">
                    <span class="wf-item-name"><?php echo htmlspecialchars($wf['workflow_name']); ?></span>
                    <span class="wf-item-module"><?php echo htmlspecialchars($wf['module']); ?></span>
                    <?php if (!empty($wf['is_active'])): ?>
                        <span class="wf-badge-active">Active</span>
                    <?php else: ?>
                        <span class="wf-badge-inactive">Inactive</span>
                    <?php endif; ?>
                </div>
                <div class="wf-actions">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="toggle_workflow">
                        <input type="hidden" name="workflow_id" value="<?php echo $wf['id']; ?>">
                        <button type="submit" title="Toggle active"><?php echo $wf['is_active'] ? 'Disable' : 'Enable'; ?></button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete_workflow">
                        <input type="hidden" name="workflow_id" value="<?php echo $wf['id']; ?>">
                        <button type="submit" class="danger" title="Delete"
                            data-confirm-title="Delete Workflow"
                            data-confirm-variant="danger"
                            data-confirm-ok="Yes, delete"
                            data-confirm-cancel="Cancel"
                            data-confirm="Delete this workflow?">
                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php
            $steps = json_decode($wf['steps'] ?? '[]', true);
            if (!empty($steps)):
            ?>
            <div class="wf-item-steps">
                <?php foreach ($steps as $i => $step): ?>
                    <span class="step-num" style="display:inline-flex;width:18px;height:18px;font-size:0.65rem;"><?php echo $i + 1; ?></span>
                    <?php echo htmlspecialchars($step['label'] ?? $step['value'] ?? 'Step'); ?>
                    <?php if ($i < count($steps) - 1): ?> → <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="wf-item-meta">
                <span>Created by: <?php echo htmlspecialchars(($wf['first_name'] ?? '') . ' ' . ($wf['last_name'] ?? '')); ?></span>
                <span>Created: <?php echo date('M d, Y', strtotime($wf['created_at'])); ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
let steps = [];

function addStep() {
    const sel = document.getElementById('step-role');
    if (!sel.value) return;
    steps.push({ value: sel.value, label: sel.options[sel.selectedIndex].text });
    sel.value = '';
    renderSteps();
}

function removeStep(idx) {
    steps.splice(idx, 1);
    renderSteps();
}

function renderSteps() {
    const list = document.getElementById('step-list');
    list.innerHTML = steps.map((s, i) =>
        '<div class="step-item">' +
        '<span class="step-num">' + (i + 1) + '</span>' +
        '<span class="step-detail">' + s.label + '</span>' +
        '<button type="button" class="step-remove" onclick="removeStep(' + i + ')">✕</button>' +
        '</div>'
    ).join('');
    document.getElementById('steps-json').value = JSON.stringify(steps);
}

function resetForm() {
    document.getElementById('wf-form-section').style.display = 'none';
    document.getElementById('wf-form').reset();
    document.getElementById('wf-edit-id').value = '0';
    steps = [];
    renderSteps();
}

if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
</div>
