<?php
/**
 * HR1 Module: Admin Dashboard (System Guardian)
 * Slate Freight Management System
 * 
 * Role-Based Access: Admin (Full Access)
 * Features:
 * - User Access Management
 * - Configuration Settings
 * - Audit Logs (CCTV of the System)
 * - System Statistics
 */

session_start();
require_once '../database/config.php';
require_once '../includes/rbac_helper.php';
require_once '../includes/email_generator.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../partials/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$user = getUserWithRole($userId);

// Verify admin access
if (!isAdmin($userId)) {
    header('Location: ../pages/dashboard.php');
    exit();
}

// Handle user status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $targetUserId = intval($_POST['user_id']);
    $newStatus = $_POST['status'];
    $newRoleId = intval($_POST['role_id']);
    
    try {
        // Get old values for audit
        $oldUser = fetchSingle("SELECT status, role_id FROM user_accounts WHERE id = ?", [$targetUserId]);
        
        updateRecord(
            "UPDATE user_accounts SET status = ?, role_id = ? WHERE id = ?",
            [$newStatus, $newRoleId, $targetUserId]
        );
        
        logAuditAction($userId, 'EDIT', 'user_accounts', $targetUserId, 
            ['status' => $oldUser['status'], 'role_id' => $oldUser['role_id']],
            ['status' => $newStatus, 'role_id' => $newRoleId],
            "Updated user permissions"
        );
        
        $successMessage = "User updated successfully!";
    } catch (Exception $e) {
        $errorMessage = "Failed to update user: " . $e->getMessage();
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $targetUserId = intval($_POST['user_id']);
    
    try {
        $tempPassword = bin2hex(random_bytes(8));
        $passwordHash = password_hash($tempPassword, PASSWORD_DEFAULT);
        
        updateRecord(
            "UPDATE user_accounts SET password_hash = ? WHERE id = ?",
            [$passwordHash, $targetUserId]
        );
        
        logAuditAction($userId, 'EDIT', 'user_accounts', $targetUserId, null, null,
            "Reset password for user"
        );
        
        $resetMessage = "Password reset! New temporary password: " . $tempPassword;
    } catch (Exception $e) {
        $errorMessage = "Failed to reset password: " . $e->getMessage();
    }
}

// Get all users
$users = [];
try {
    $users = fetchAll(
        "SELECT ua.*, r.role_name, r.role_type, d.department_name
         FROM user_accounts ua
         LEFT JOIN roles r ON ua.role_id = r.id
         LEFT JOIN departments d ON ua.department_id = d.id
         ORDER BY ua.created_at DESC"
    );
} catch (Exception $e) {
    // Try legacy users table
    try {
        $users = fetchAll("SELECT * FROM users ORDER BY created_at DESC");
    } catch (Exception $e2) {
        $users = [];
    }
}

// Get roles for dropdown
$roles = [];
try {
    $roles = fetchAll("SELECT * FROM roles ORDER BY role_name");
} catch (Exception $e) {
    $roles = [];
}

// Get audit logs
$auditLogs = [];
try {
    $auditLogs = fetchAll(
        "SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 50"
    );
} catch (Exception $e) {
    $auditLogs = [];
}

// Get system stats
$stats = [
    'total_users' => count($users),
    'active_users' => count(array_filter($users, fn($u) => ($u['status'] ?? 'Active') === 'Active')),
    'total_actions_today' => 0,
    'login_count_today' => 0
];

try {
    $stats['total_actions_today'] = fetchSingle(
        "SELECT COUNT(*) as count FROM audit_logs WHERE DATE(created_at) = CURDATE()"
    )['count'] ?? 0;
    
    $stats['login_count_today'] = fetchSingle(
        "SELECT COUNT(*) as count FROM audit_logs WHERE action = 'LOGIN' AND DATE(created_at) = CURDATE()"
    )['count'] ?? 0;
} catch (Exception $e) {
    // Keep default values
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Slate Freight</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a1929 0%, #1a2942 100%);
            min-height: 100vh;
            color: #f8fafc;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        /* Header */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 1rem 1.5rem;
            background: #1e2936;
            border-radius: 12px;
        }

        .header-title h1 {
            font-size: 1.5rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .header-title p {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #0ea5e9;
            color: white;
        }

        .btn-primary:hover {
            background: #0284c7;
        }

        .btn-secondary {
            background: #334155;
            color: #e2e8f0;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: #1e2936;
            border-radius: 12px;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: rgba(14, 165, 233, 0.2);
            color: #0ea5e9;
        }

        .stat-content h3 {
            font-size: 1.5rem;
            color: #ffffff;
        }

        .stat-content p {
            font-size: 0.85rem;
            color: #94a3b8;
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid #334155;
            padding-bottom: 0.5rem;
        }

        .tab-btn {
            padding: 0.75rem 1.25rem;
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 0.95rem;
            cursor: pointer;
            border-radius: 8px 8px 0 0;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tab-btn:hover {
            background: rgba(14, 165, 233, 0.1);
            color: #0ea5e9;
        }

        .tab-btn.active {
            background: #0ea5e9;
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Section Card */
        .section-card {
            background: #1e2936;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #334155;
        }

        .section-title {
            font-size: 1.1rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid #334155;
        }

        .data-table th {
            font-weight: 600;
            color: #94a3b8;
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        .data-table td {
            color: #e2e8f0;
            font-size: 0.9rem;
        }

        .data-table tr:hover {
            background: rgba(14, 165, 233, 0.05);
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-badge.active {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .status-badge.inactive {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .status-badge.pending {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }

        /* Action Badges */
        .action-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .action-badge.view { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
        .action-badge.create { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .action-badge.edit { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .action-badge.delete { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .action-badge.login { background: rgba(139, 92, 246, 0.2); color: #8b5cf6; }
        .action-badge.hire { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .action-badge.system { background: rgba(100, 116, 139, 0.2); color: #94a3b8; }

        /* User Info */
        .user-info-cell {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            color: #ffffff;
        }

        .user-email {
            font-size: 0.8rem;
            color: #0ea5e9;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: #1e2936;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #334155;
        }

        .modal-header h2 {
            font-size: 1.25rem;
            color: #ffffff;
        }

        .modal-close {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 1.5rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #cbd5e1;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            background: #2a3544;
            border: 1px solid #3a4554;
            border-radius: 6px;
            color: #e2e8f0;
            font-size: 0.95rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #0ea5e9;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* Permission Grid */
        .permission-grid {
            display: grid;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .permission-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            background: #2a3544;
            border-radius: 6px;
        }

        .permission-label {
            color: #e2e8f0;
        }

        .permission-toggle {
            display: flex;
            gap: 0.5rem;
        }

        .toggle-btn {
            padding: 0.25rem 0.5rem;
            border: none;
            border-radius: 4px;
            font-size: 0.75rem;
            cursor: pointer;
        }

        .toggle-btn.yes {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .toggle-btn.no {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .toggle-btn.active {
            opacity: 1;
        }

        .toggle-btn:not(.active) {
            opacity: 0.4;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid #10b981;
            color: #6ee7b7;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #ef4444;
            color: #fca5a5;
        }

        .alert-info {
            background: rgba(14, 165, 233, 0.2);
            border: 1px solid #0ea5e9;
            color: #7dd3fc;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-header {
                flex-direction: column;
                gap: 1rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .tabs {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
<?php $logo_path = '../assets/images/slate.png'; include '../includes/loading-screen.php'; ?>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-title">
                <h1>
                    <span class="material-symbols-outlined">admin_panel_settings</span>
                    System Administration
                </h1>
                <p>Admin Dashboard - Full System Control</p>
            </div>
            <a href="dashboard.php" class="btn btn-secondary">
                <span class="material-symbols-outlined">arrow_back</span>
                Back to Dashboard
            </a>
        </div>

        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <?php if (isset($resetMessage)): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($resetMessage); ?></div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <span class="material-symbols-outlined">group</span>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_users']; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <span class="material-symbols-outlined">check_circle</span>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['active_users']; ?></h3>
                    <p>Active Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <span class="material-symbols-outlined">history</span>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_actions_today']; ?></h3>
                    <p>Actions Today</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <span class="material-symbols-outlined">login</span>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['login_count_today']; ?></h3>
                    <p>Logins Today</p>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('users')">
                <span class="material-symbols-outlined">manage_accounts</span>
                User Management
            </button>
            <button class="tab-btn" onclick="showTab('audit')">
                <span class="material-symbols-outlined">security</span>
                Audit Logs
            </button>
            <button class="tab-btn" onclick="showTab('settings')">
                <span class="material-symbols-outlined">settings</span>
                System Settings
            </button>
        </div>

        <!-- User Management Tab -->
        <div id="users-tab" class="tab-content active">
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="material-symbols-outlined">badge</span>
                        User Access Management
                    </h2>
                    <button class="btn btn-primary btn-sm" onclick="showAddUserModal()">
                        <span class="material-symbols-outlined">person_add</span>
                        Add User
                    </button>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Last Login</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #94a3b8;">No users found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td>
                                        <div class="user-info-cell">
                                            <span class="user-name"><?php echo htmlspecialchars(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? $u['username'] ?? 'N/A')); ?></span>
                                            <span class="user-email"><?php echo htmlspecialchars($u['company_email'] ?? $u['email'] ?? 'N/A'); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($u['role_name'] ?? $u['role_type'] ?? $u['role'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($u['department_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo $u['last_login'] ? date('M d, H:i', strtotime($u['last_login'])) : 'Never'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($u['status'] ?? 'active'); ?>">
                                            <?php echo htmlspecialchars($u['status'] ?? 'Active'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-secondary btn-sm" onclick="editUserPermissions(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? $u['username'] ?? '')); ?>')">
                                            <span class="material-symbols-outlined" style="font-size: 16px;">edit</span>
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Audit Logs Tab -->
        <div id="audit-tab" class="tab-content">
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="material-symbols-outlined">monitoring</span>
                        Audit Logs - System Activity
                    </h2>
                    <span style="color: #94a3b8; font-size: 0.85rem;">Last 50 actions</span>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($auditLogs)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #94a3b8;">No audit logs found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($auditLogs as $log): ?>
                                <tr>
                                    <td><?php echo date('M d, H:i A', strtotime($log['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($log['user_email'] ?? 'System'); ?></td>
                                    <td>
                                        <span class="action-badge <?php echo strtolower($log['action']); ?>">
                                            <?php echo htmlspecialchars($log['action']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['detail'] ?? $log['module'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- System Settings Tab -->
        <div id="settings-tab" class="tab-content">
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="material-symbols-outlined">tune</span>
                        Configuration Settings
                    </h2>
                </div>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div style="background: #2a3544; padding: 1.25rem; border-radius: 8px;">
                        <h3 style="color: #0ea5e9; margin-bottom: 1rem; font-size: 1rem;">
                            <span class="material-symbols-outlined" style="vertical-align: middle;">work</span>
                            Job Titles
                        </h3>
                        <p style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 0.75rem;">Manage available job titles/positions</p>
                        <button class="btn btn-secondary btn-sm">
                            <span class="material-symbols-outlined" style="font-size: 16px;">edit</span>
                            Edit List
                        </button>
                    </div>

                    <div style="background: #2a3544; padding: 1.25rem; border-radius: 8px;">
                        <h3 style="color: #0ea5e9; margin-bottom: 1rem; font-size: 1rem;">
                            <span class="material-symbols-outlined" style="vertical-align: middle;">apartment</span>
                            Departments
                        </h3>
                        <p style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 0.75rem;">Manage company departments</p>
                        <button class="btn btn-secondary btn-sm">
                            <span class="material-symbols-outlined" style="font-size: 16px;">edit</span>
                            Edit List
                        </button>
                    </div>

                    <div style="background: #2a3544; padding: 1.25rem; border-radius: 8px;">
                        <h3 style="color: #0ea5e9; margin-bottom: 1rem; font-size: 1rem;">
                            <span class="material-symbols-outlined" style="vertical-align: middle;">event_busy</span>
                            Leave Types
                        </h3>
                        <p style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 0.75rem;">Configure leave type options</p>
                        <button class="btn btn-secondary btn-sm">
                            <span class="material-symbols-outlined" style="font-size: 16px;">edit</span>
                            Edit List
                        </button>
                    </div>

                    <div style="background: #2a3544; padding: 1.25rem; border-radius: 8px;">
                        <h3 style="color: #0ea5e9; margin-bottom: 1rem; font-size: 1rem;">
                            <span class="material-symbols-outlined" style="vertical-align: middle;">mail</span>
                            Email Domain
                        </h3>
                        <p style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 0.75rem;">Current: @slatefreight.com</p>
                        <button class="btn btn-secondary btn-sm">
                            <span class="material-symbols-outlined" style="font-size: 16px;">edit</span>
                            Change
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../modals/components/admin-modals.php'; ?>
</body>
</html>
