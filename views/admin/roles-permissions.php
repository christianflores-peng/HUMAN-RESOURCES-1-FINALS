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
    header('Location: index.php?page=roles-permissions');
    exit();
}

require_once '../../database/config.php';

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'update_permissions') {
            $role_id = (int)$_POST['role_id'];
            $permissions = $_POST['permissions'] ?? [];
            
            // Delete existing permissions for this role
            executeQuery("DELETE FROM role_permissions WHERE role_id = ?", [$role_id]);
            
            // Insert new permissions
            foreach ($permissions as $perm) {
                executeQuery(
                    "INSERT INTO role_permissions (role_id, module, can_view, can_create, can_edit, can_delete) VALUES (?, ?, 1, ?, ?, ?)",
                    [$role_id, $perm, 
                     isset($_POST["can_create_{$perm}"]) ? 1 : 0,
                     isset($_POST["can_edit_{$perm}"]) ? 1 : 0,
                     isset($_POST["can_delete_{$perm}"]) ? 1 : 0]
                );
            }
            $success_message = "Permissions updated successfully.";
        } elseif ($action === 'create_role') {
            $role_name = trim($_POST['role_name'] ?? '');
            $role_type = $_POST['role_type'] ?? 'Employee';
            $description = trim($_POST['description'] ?? '');
            $access_level = $_POST['access_level'] ?? 'self';
            
            if (empty($role_name)) {
                $error_message = "Role name is required.";
            } else {
                executeQuery(
                    "INSERT INTO roles (role_name, role_type, description, access_level) VALUES (?, ?, ?, ?)",
                    [$role_name, $role_type, $description, $access_level]
                );
                $success_message = "Role '{$role_name}' created successfully.";
            }
        } elseif ($action === 'delete_role') {
            $role_id = (int)$_POST['role_id'];
            // Check if role is in use
            $in_use = fetchSingle("SELECT COUNT(*) as count FROM user_accounts WHERE role_id = ?", [$role_id]);
            if ($in_use && $in_use['count'] > 0) {
                $error_message = "Cannot delete role - it is assigned to {$in_use['count']} user(s).";
            } else {
                executeQuery("DELETE FROM role_permissions WHERE role_id = ?", [$role_id]);
                executeQuery("DELETE FROM roles WHERE id = ?", [$role_id]);
                $success_message = "Role deleted successfully.";
            }
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Fetch roles with user counts
try {
    $roles = fetchAll("
        SELECT r.*, COUNT(ua.id) as user_count
        FROM roles r
        LEFT JOIN user_accounts ua ON ua.role_id = r.id
        GROUP BY r.id
        ORDER BY r.id ASC
    ");
} catch (Exception $e) {
    $roles = [];
}

// Fetch permissions
try {
    $permissions = fetchAll("SELECT * FROM role_permissions ORDER BY role_id, module");
} catch (Exception $e) {
    $permissions = [];
}

// Group permissions by role_id
$permissions_by_role = [];
foreach ($permissions as $perm) {
    $permissions_by_role[$perm['role_id']][] = $perm;
}

// Available modules
$modules = ['dashboard', 'users', 'job_postings', 'applications', 'interviews', 'onboarding', 'departments', 'reports', 'audit_logs', 'settings'];
?>
<div data-page-title="Roles & Permissions">
<style>
        .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5rem; color: #e2e8f0; margin-bottom: 0.25rem; }
        .header p { color: #94a3b8; font-size: 0.9rem; }
        .card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1.5rem; }
        .card h2 { font-size: 1.2rem; color: #e2e8f0; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }

        .success-msg { background: rgba(16, 185, 129, 0.15); color: #4ade80; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid rgba(16, 185, 129, 0.3); }
        .error-msg { background: rgba(239, 68, 68, 0.15); color: #ff6b6b; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid rgba(239, 68, 68, 0.3); }

        .btn { padding: 0.5rem 1rem; border: none; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 500; transition: all 0.3s; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: #0ea5e9; color: white; }
        .btn-primary:hover { background: #0284c7; }
        .btn-danger { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .btn-danger:hover { background: rgba(239, 68, 68, 0.3); }
        .btn-success { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .btn-success:hover { background: rgba(16, 185, 129, 0.3); }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid rgba(58, 69, 84, 0.3); }
        th { color: #94a3b8; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
        td { color: #e2e8f0; font-size: 0.9rem; }
        tr:hover { background: rgba(15, 23, 42, 0.4); }

        .role-type-badge { padding: 0.25rem 0.6rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        .role-type-badge.Admin { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .role-type-badge.HR_Staff { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
        .role-type-badge.Manager { background: rgba(139, 92, 246, 0.2); color: #8b5cf6; }
        .role-type-badge.Employee { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .role-type-badge.Applicant { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }

        .access-badge { padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; font-weight: 600; background: rgba(58, 69, 84, 0.5); color: #94a3b8; }
        .user-count { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; padding: 0.2rem 0.6rem; border-radius: 10px; font-size: 0.8rem; font-weight: 600; }

        /* Modal */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(10, 25, 41, 0.9); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
        .modal-overlay.active { display: flex; }
        .modal { background: #1e2936; border-radius: 16px; padding: 2rem; width: 100%; max-width: 500px; border: 1px solid rgba(58, 69, 84, 0.5); }
        .modal h3 { color: #e2e8f0; margin-bottom: 1.5rem; font-size: 1.2rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; color: #94a3b8; font-size: 0.85rem; margin-bottom: 0.4rem; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.65rem 0.75rem; background: #2a3544; border: 1px solid #3a4554; border-radius: 8px; color: #e2e8f0; font-size: 0.9rem; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #0ea5e9; }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .modal-actions { display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem; }
        .btn-cancel { background: rgba(58, 69, 84, 0.5); color: #cbd5e1; }
        .btn-cancel:hover { background: rgba(58, 69, 84, 0.8); }

        /* Permissions section */
        .permissions-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; }
        .perm-card { background: rgba(15, 23, 42, 0.6); border-radius: 8px; padding: 1rem; border: 1px solid rgba(58, 69, 84, 0.3); }
        .perm-card h4 { color: #e2e8f0; font-size: 0.9rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem; }
        .perm-module { text-transform: capitalize; }
        .perm-checks { display: flex; gap: 1rem; flex-wrap: wrap; }
        .perm-check { display: flex; align-items: center; gap: 0.35rem; color: #94a3b8; font-size: 0.8rem; }
        .perm-check input[type="checkbox"] { accent-color: #0ea5e9; width: 16px; height: 16px; }

        .tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; }
        .tab { padding: 0.6rem 1.2rem; border-radius: 8px; cursor: pointer; font-size: 0.9rem; background: rgba(58, 69, 84, 0.3); color: #94a3b8; border: none; transition: all 0.3s; }
        .tab.active { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
        .tab:hover { background: rgba(14, 165, 233, 0.1); color: #0ea5e9; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
            <div class="header">
                <div>
                    <h1>Roles & Permissions</h1>
                    <p>Manage system roles and access control</p>
                </div>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                    <button class="btn btn-primary" onclick="openCreateModal()">
                        <i data-lucide="plus"></i> New Role
                    </button>
                </div>
            </div>

            <?php if ($success_message): ?>
                <div class="success-msg"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="tabs">
                <button class="tab active" onclick="switchTab('roles')">Roles</button>
                <button class="tab" onclick="switchTab('permissions')">Permissions Matrix</button>
            </div>

            <!-- Roles Tab -->
            <div id="tab-roles" class="tab-content active">
                <div class="card">
                    <h2><i data-lucide="shield"></i> System Roles</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Role Name</th>
                                <th>Type</th>
                                <th>Access Level</th>
                                <th>Users</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles as $role): ?>
                            <tr>
                                <td><?php echo $role['id']; ?></td>
                                <td style="font-weight: 500;"><?php echo htmlspecialchars($role['role_name']); ?></td>
                                <td><span class="role-type-badge <?php echo $role['role_type']; ?>"><?php echo $role['role_type']; ?></span></td>
                                <td><span class="access-badge"><?php echo $role['access_level']; ?></span></td>
                                <td><span class="user-count"><?php echo $role['user_count']; ?></span></td>
                                <td style="color: #94a3b8; font-size: 0.85rem; max-width: 250px;"><?php echo htmlspecialchars($role['description'] ?? ''); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this role?');">
                                        <input type="hidden" name="action" value="delete_role">
                                        <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;">
                                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Permissions Tab -->
            <div id="tab-permissions" class="tab-content">
                <div class="card">
                    <h2><i data-lucide="key"></i> Permissions Matrix</h2>
                    <p style="color: #94a3b8; margin-bottom: 1.5rem; font-size: 0.9rem;">Select a role to manage its module permissions.</p>
                    
                    <?php foreach ($roles as $role): ?>
                    <details style="margin-bottom: 1rem;">
                        <summary style="cursor: pointer; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border-radius: 8px; color: #e2e8f0; font-weight: 500; display: flex; align-items: center; gap: 0.5rem;">
                            <span class="role-type-badge <?php echo $role['role_type']; ?>" style="margin-left: 0.5rem;"><?php echo $role['role_type']; ?></span>
                            <?php echo htmlspecialchars($role['role_name']); ?>
                            <span class="user-count" style="margin-left: auto;"><?php echo $role['user_count']; ?> users</span>
                        </summary>
                        <form method="POST" style="padding: 1rem;">
                            <input type="hidden" name="action" value="update_permissions">
                            <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                            <div class="permissions-grid">
                                <?php foreach ($modules as $module): 
                                    $has_perm = false;
                                    $can_create = 0; $can_edit = 0; $can_delete = 0;
                                    if (isset($permissions_by_role[$role['id']])) {
                                        foreach ($permissions_by_role[$role['id']] as $p) {
                                            if ($p['module'] === $module) {
                                                $has_perm = true;
                                                $can_create = $p['can_create'];
                                                $can_edit = $p['can_edit'];
                                                $can_delete = $p['can_delete'];
                                                break;
                                            }
                                        }
                                    }
                                ?>
                                <div class="perm-card">
                                    <h4>
                                        <label class="perm-check">
                                            <input type="checkbox" name="permissions[]" value="<?php echo $module; ?>" <?php echo $has_perm ? 'checked' : ''; ?>>
                                            <span class="perm-module"><?php echo str_replace('_', ' ', $module); ?></span>
                                        </label>
                                    </h4>
                                    <div class="perm-checks">
                                        <label class="perm-check"><input type="checkbox" name="can_create_<?php echo $module; ?>" <?php echo $can_create ? 'checked' : ''; ?>> Create</label>
                                        <label class="perm-check"><input type="checkbox" name="can_edit_<?php echo $module; ?>" <?php echo $can_edit ? 'checked' : ''; ?>> Edit</label>
                                        <label class="perm-check"><input type="checkbox" name="can_delete_<?php echo $module; ?>" <?php echo $can_delete ? 'checked' : ''; ?>> Delete</label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div style="margin-top: 1rem; text-align: right;">
                                <button type="submit" class="btn btn-primary">Save Permissions</button>
                            </div>
                        </form>
                    </details>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Create Role Modal -->
    <div id="createModal" class="modal-overlay">
        <div class="modal">
            <h3>Create New Role</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create_role">
                <div class="form-group">
                    <label>Role Name</label>
                    <input type="text" name="role_name" required placeholder="e.g. Finance Manager">
                </div>
                <div class="form-group">
                    <label>Role Type</label>
                    <select name="role_type">
                        <option value="Admin">Admin</option>
                        <option value="HR_Staff">HR Staff</option>
                        <option value="Manager">Manager</option>
                        <option value="Employee" selected>Employee</option>
                        <option value="Applicant">Applicant</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Access Level</label>
                    <select name="access_level">
                        <option value="full">Full</option>
                        <option value="functional">Functional</option>
                        <option value="department">Department</option>
                        <option value="self" selected>Self</option>
                        <option value="external">External</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="Brief description of this role..."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-cancel" onclick="closeCreateModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Role</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(el => el.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');
            event.target.classList.add('active');
        }

        function openCreateModal() {
            document.getElementById('createModal').classList.add('active');
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.remove('active');
        }

        document.getElementById('createModal').addEventListener('click', function(e) {
            if (e.target === this) closeCreateModal();
        });

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</div>
