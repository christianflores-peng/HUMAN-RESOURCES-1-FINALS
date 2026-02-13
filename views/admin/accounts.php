<?php
require_once '../../includes/session_helper.php';
require_once '../../includes/otp_helper.php';
require_once '../../includes/email_generator.php';
require_once '../../includes/spa_helper.php';
startSecureSession();
$is_ajax = is_spa_ajax();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Admin') {
    header('Location: ../../index.php');
    exit();
}

// Redirect direct access to SPA shell
if (!$is_ajax) {
    header('Location: index.php?page=accounts');
    exit();
}

require_once '../../database/config.php';

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';
$show_archive_otp = false;
$pending_archive_id = '';
$pending_archive_name = '';
$show_toggle_otp = false;
$pending_toggle_id = '';
$pending_toggle_name = '';
$pending_toggle_status = '';

// Ensure otp_type ENUM includes custom types
try {
    executeQuery("ALTER TABLE otp_verifications MODIFY COLUMN otp_type ENUM('login', 'registration', 'password_reset', 'delete_user', 'archive_user', 'toggle_status') DEFAULT 'login'");
} catch (Exception $e) {
    // Column may already be updated, continue
}

// Ensure username column exists in user_accounts
try {
    executeQuery("ALTER TABLE user_accounts ADD COLUMN username VARCHAR(50) UNIQUE AFTER employee_id");
} catch (Exception $e) {
    // Column may already exist, continue
}

// Step 1: Request archive - send OTP to admin email
if (isset($_POST['request_archive'])) {
    $archive_id = $_POST['user_id'];
    $admin_email = $_SESSION['personal_email'] ?? $_SESSION['company_email'] ?? '';
    
    if (empty($admin_email)) {
        $error_message = "No admin email found in session. Please re-login.";
    } else {
        try {
            $target_user = fetchSingle("SELECT first_name, last_name FROM user_accounts WHERE id = ?", [$archive_id]);
            $otp_code = createOTP($admin_email, null, 'archive_user', $_SESSION['user_id']);
            $admin_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
            sendOTP($admin_email, $otp_code, $admin_name);
            
            $show_archive_otp = true;
            $pending_archive_id = $archive_id;
            $pending_archive_name = $target_user ? $target_user['first_name'] . ' ' . $target_user['last_name'] : 'Unknown';
            $success_message = "Verification code sent to your email.";
        } catch (Exception $e) {
            $error_message = "Failed to send verification code: " . $e->getMessage();
        }
    }
}

// Step 2: Verify OTP and execute archive
if (isset($_POST['verify_archive_otp'])) {
    $archive_id = $_POST['user_id'];
    $otp_code = trim($_POST['otp_code'] ?? '');
    if (empty($otp_code) && isset($_POST['archive_otp_digits']) && is_array($_POST['archive_otp_digits'])) {
        $otp_code = implode('', array_map(function ($d) {
            return preg_replace('/\D/', '', (string)$d);
        }, $_POST['archive_otp_digits']));
    }
    $admin_email = $_SESSION['personal_email'] ?? $_SESSION['company_email'] ?? '';
    
    if (empty($otp_code)) {
        $error_message = "Please enter the verification code.";
        $show_archive_otp = true;
        $pending_archive_id = $archive_id;
        $target_user = fetchSingle("SELECT first_name, last_name FROM user_accounts WHERE id = ?", [$archive_id]);
        $pending_archive_name = $target_user ? $target_user['first_name'] . ' ' . $target_user['last_name'] : 'Unknown';
    } else {
        $result = verifyOTP($admin_email, $otp_code, 'archive_user');
        if ($result['success']) {
            // OTP verified - proceed with archive
            try {
                executeQuery("UPDATE user_accounts SET status = 'Archived' WHERE id = ?", [$archive_id]);
                $success_message = "User account archived successfully.";
            } catch (Exception $e) {
                $error_message = "Failed to archive user: " . $e->getMessage();
            }
        } else {
            $error_message = "Invalid or expired verification code. Please try again.";
            $show_archive_otp = true;
            $pending_archive_id = $archive_id;
            $target_user = fetchSingle("SELECT first_name, last_name FROM user_accounts WHERE id = ?", [$archive_id]);
            $pending_archive_name = $target_user ? $target_user['first_name'] . ' ' . $target_user['last_name'] : 'Unknown';
        }
    }
}

// Step 1: Request toggle status - send OTP
if (isset($_POST['request_toggle'])) {
    $toggle_id = $_POST['user_id'];
    $new_status = $_POST['new_status'];
    $admin_email = $_SESSION['personal_email'] ?? $_SESSION['company_email'] ?? '';
    
    if (empty($admin_email)) {
        $error_message = "No admin email found in session. Please re-login.";
    } else {
        try {
            $target_user = fetchSingle("SELECT first_name, last_name FROM user_accounts WHERE id = ?", [$toggle_id]);
            $otp_code = createOTP($admin_email, null, 'toggle_status', $_SESSION['user_id']);
            $admin_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
            sendOTP($admin_email, $otp_code, $admin_name);
            
            $show_toggle_otp = true;
            $pending_toggle_id = $toggle_id;
            $pending_toggle_name = $target_user ? $target_user['first_name'] . ' ' . $target_user['last_name'] : 'Unknown';
            $pending_toggle_status = $new_status;
            $success_message = "Verification code sent to your email.";
        } catch (Exception $e) {
            $error_message = "Failed to send verification code: " . $e->getMessage();
        }
    }
}

// Step 2: Verify OTP and toggle status
if (isset($_POST['verify_toggle_otp'])) {
    $toggle_id = $_POST['user_id'];
    $new_status = $_POST['new_status'];
    $otp_code = trim($_POST['otp_code'] ?? '');
    if (empty($otp_code) && isset($_POST['toggle_otp_digits']) && is_array($_POST['toggle_otp_digits'])) {
        $otp_code = implode('', array_map(function ($d) {
            return preg_replace('/\D/', '', (string)$d);
        }, $_POST['toggle_otp_digits']));
    }
    $admin_email = $_SESSION['personal_email'] ?? $_SESSION['company_email'] ?? '';
    
    if (empty($otp_code)) {
        $error_message = "Please enter the verification code.";
        $show_toggle_otp = true;
        $pending_toggle_id = $toggle_id;
        $pending_toggle_status = $new_status;
        $target_user = fetchSingle("SELECT first_name, last_name FROM user_accounts WHERE id = ?", [$toggle_id]);
        $pending_toggle_name = $target_user ? $target_user['first_name'] . ' ' . $target_user['last_name'] : 'Unknown';
    } else {
        $result = verifyOTP($admin_email, $otp_code, 'toggle_status');
        if ($result['success']) {
            try {
                executeQuery("UPDATE user_accounts SET status = ? WHERE id = ?", [$new_status, $toggle_id]);
                $success_message = "User status changed to " . $new_status . " successfully.";
            } catch (Exception $e) {
                $error_message = "Failed to update status: " . $e->getMessage();
            }
        } else {
            $error_message = "Invalid or expired verification code. Please try again.";
            $show_toggle_otp = true;
            $pending_toggle_id = $toggle_id;
            $pending_toggle_status = $new_status;
            $target_user = fetchSingle("SELECT first_name, last_name FROM user_accounts WHERE id = ?", [$toggle_id]);
            $pending_toggle_name = $target_user ? $target_user['first_name'] . ' ' . $target_user['last_name'] : 'Unknown';
        }
    }
}

// Handle CREATE/UPDATE action
if (isset($_POST['save_user'])) {
    $edit_id = $_POST['user_id'] ?? null;
    $username = trim($_POST['username']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password_raw = trim($_POST['password'] ?? '');
    $role_id = !empty($_POST['role_id']) ? $_POST['role_id'] : null;
    $department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
    $status = $_POST['status'];
    
    try {
        if (empty($username)) {
            throw new Exception("Username is required.");
        }
        
        // Check for duplicate username
        $existing = fetchSingle("SELECT id FROM user_accounts WHERE username = ? AND id != ?", [$username, $edit_id ?? 0]);
        if ($existing) {
            throw new Exception("Username '" . $username . "' is already taken.");
        }
        
        if ($edit_id) {
            // UPDATE
            if (!empty($password_raw)) {
                $password_hash = password_hash($password_raw, PASSWORD_DEFAULT);
                executeQuery(
                    "UPDATE user_accounts SET username = ?, first_name = ?, last_name = ?, personal_email = ?, password_hash = ?, role_id = ?, department_id = ?, status = ? WHERE id = ?",
                    [$username, $first_name, $last_name, $email, $password_hash, $role_id, $department_id, $status, $edit_id]
                );
            } else {
                executeQuery(
                    "UPDATE user_accounts SET username = ?, first_name = ?, last_name = ?, personal_email = ?, role_id = ?, department_id = ?, status = ? WHERE id = ?",
                    [$username, $first_name, $last_name, $email, $role_id, $department_id, $status, $edit_id]
                );
            }
            $success_message = "User account updated successfully.";
        } else {
            // CREATE
            if (empty($password_raw)) {
                $password_raw = 'Password123!';
            }
            $password_hash = password_hash($password_raw, PASSWORD_DEFAULT);
            executeQuery(
                "INSERT INTO user_accounts (username, first_name, last_name, personal_email, password_hash, role_id, department_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [$username, $first_name, $last_name, $email, $password_hash, $role_id, $department_id, $status]
            );
            $success_message = "User account created successfully. Password: " . $password_raw;
        }
    } catch (Exception $e) {
        $error_message = "Failed to save user: " . $e->getMessage();
    }
}

// Filters
$filter_status = $_GET['status'] ?? '';
$filter_role = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';

// Build WHERE clause
$where_conditions = [];
$params = [];

if ($filter_status) {
    $where_conditions[] = "ua.status = ?";
    $params[] = $filter_status;
}

if ($filter_role) {
    $where_conditions[] = "ua.role_id = ?";
    $params[] = $filter_role;
}

if ($search) {
    $where_conditions[] = "(ua.first_name LIKE ? OR ua.last_name LIKE ? OR ua.personal_email LIKE ? OR ua.employee_id LIKE ? OR ua.username LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Fetch users
try {
    $users = fetchAll("
        SELECT ua.*, r.role_name, r.role_type, d.department_name
        FROM user_accounts ua
        LEFT JOIN roles r ON r.id = ua.role_id
        LEFT JOIN departments d ON d.id = ua.department_id
        $where_clause
        ORDER BY ua.created_at DESC
    ", $params);
} catch (Exception $e) {
    $users = [];
    $error_message = "Failed to load users: " . $e->getMessage();
}

// Fetch roles and departments for dropdowns
try {
    $roles = fetchAll("SELECT id, role_name FROM roles ORDER BY role_name");
    $departments = fetchAll("SELECT id, department_name FROM departments ORDER BY department_name");
} catch (Exception $e) {
    $roles = [];
    $departments = [];
}
?>
<div data-page-title="Manage Accounts">
<style>
        .header {
            background: rgba(30, 41, 54, 0.6);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 1.5rem;
            color: #e2e8f0;
        }

        .btn {
            padding: 0.65rem 1.25rem;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: #ef4444;
            color: white;
        }

        .btn-primary:hover {
            background: #dc2626;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-secondary {
            background: rgba(100, 116, 139, 0.3);
            color: #cbd5e1;
        }

        .btn-secondary:hover {
            background: rgba(100, 116, 139, 0.5);
        }

        .btn-sm {
            padding: 0.4rem 0.75rem;
            font-size: 0.8rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }

        .filters-card {
            background: rgba(30, 41, 54, 0.6);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(58, 69, 84, 0.5);
            margin-bottom: 1.5rem;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-label {
            color: #cbd5e1;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .filter-input, .filter-select {
            padding: 0.65rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(58, 69, 84, 0.5);
            border-radius: 6px;
            color: #e2e8f0;
            font-size: 0.9rem;
        }

        .filter-input:focus, .filter-select:focus {
            outline: none;
            border-color: #ef4444;
        }

        .users-table {
            background: rgba(30, 41, 54, 0.6);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(58, 69, 84, 0.5);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: rgba(15, 23, 42, 0.8);
        }

        th {
            padding: 1rem;
            text-align: left;
            color: #cbd5e1;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        td {
            padding: 1rem;
            border-top: 1px solid rgba(58, 69, 84, 0.3);
            color: #e2e8f0;
            font-size: 0.9rem;
        }

        tbody tr {
            transition: all 0.3s;
        }

        tbody tr:hover {
            background: rgba(239, 68, 68, 0.05);
        }

        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.active { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .status-badge.inactive { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .status-badge.pending { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .status-badge.archived { background: rgba(148, 163, 184, 0.2); color: #94a3b8; }

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
            border-radius: 12px;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h2 {
            color: #e2e8f0;
            font-size: 1.3rem;
        }

        .close-modal {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 1.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            color: #cbd5e1;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 0.75rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(58, 69, 84, 0.5);
            border-radius: 6px;
            color: #e2e8f0;
            font-size: 0.9rem;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #ef4444;
        }

        .form-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }
    </style>
            <div class="header">
                <h1>Manage Accounts</h1>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                    <button onclick="openCreateModal()" class="btn btn-primary">
                        <i data-lucide="plus"></i>
                        Create New User
                    </button>
                </div>
            </div>

            <?php if ($success_message && !$show_archive_otp && !$show_toggle_otp): ?>
            <div class="alert alert-success">
                <i data-lucide="check-circle"></i>
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>

            <?php if ($error_message && !$show_archive_otp && !$show_toggle_otp): ?>
            <div class="alert alert-error">
                <i data-lucide="alert-circle"></i>
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>

            <div class="filters-card">
                <form method="GET" action="">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label class="filter-label">Search</label>
                            <input type="text" name="search" class="filter-input" placeholder="Name, email, or employee ID..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Status</label>
                            <select name="status" class="filter-select">
                                <option value="">All Status</option>
                                <option value="Active" <?php echo $filter_status === 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $filter_status === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="Pending" <?php echo $filter_status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Archived" <?php echo $filter_status === 'Archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Role</label>
                            <select name="role" class="filter-select">
                                <option value="">All Roles</option>
                                <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>" <?php echo $filter_role == $role['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.75rem;">
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="search"></i>
                            Apply Filters
                        </button>
                        <a href="accounts.php" class="btn btn-secondary">
                            <i data-lucide="x"></i>
                            Clear
                        </a>
                    </div>
                </form>
            </div>

            <div class="users-table">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                <br>
                                <small style="color: #94a3b8;"><?php echo htmlspecialchars($user['personal_email']); ?></small>
                                <?php if (!empty($user['username'])): ?>
                                <br><small style="color: #38bdf8;"><i data-lucide="user" style="width: 11px; height: 11px; display: inline-block; vertical-align: middle;"></i> <?php echo htmlspecialchars($user['username']); ?></small>
                                <?php endif; ?>
                                <?php if ($user['employee_id']): ?>
                                <br><small style="color: #0ea5e9;"><?php echo htmlspecialchars($user['employee_id']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['department_name'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="status-badge <?php echo strtolower($user['status']); ?>">
                                    <?php echo htmlspecialchars($user['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button onclick='openEditModal(<?php echo json_encode($user); ?>)' class="btn btn-sm btn-secondary">
                                        <i data-lucide="edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning" onclick="openToggleConfirm(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['first_name'] . ' ' . $user['last_name'])); ?>', '<?php echo $user['status'] === 'Active' ? 'Inactive' : 'Active'; ?>', '<?php echo $user['status']; ?>')">
                                        <i data-lucide="<?php echo $user['status'] === 'Active' ? 'ban' : 'check-circle'; ?>"></i>
                                    </button>
                                    <?php if ($user['status'] !== 'Archived'): ?>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="openArchiveConfirm(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['first_name'] . ' ' . $user['last_name'])); ?>', '<?php echo htmlspecialchars(addslashes($user['personal_email'] ?? '')); ?>')" title="Archive">
                                        <i data-lucide="archive"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Create/Edit Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Create New User</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" id="userId">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" id="username" class="form-input" placeholder="Enter username" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" id="passwordLabel">Password *</label>
                        <input type="password" name="password" id="password" class="form-input" placeholder="Enter password">
                        <small id="passwordHint" style="color: #94a3b8; font-size: 0.75rem; margin-top: 0.25rem; display: none;">Leave blank to keep current password</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" id="firstName" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="last_name" id="lastName" class="form-input" required>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" id="email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select name="role_id" id="roleId" class="form-select" required>
                            <option value="">Select Role</option>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select name="department_id" id="departmentId" class="form-select">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Status *</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Pending">Pending</option>
                            <option value="Archived">Archived</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" name="save_user" class="btn btn-success">
                        <i data-lucide="save"></i>
                        Save User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Archive Confirmation Modal -->
    <div id="archiveConfirmModal" class="modal">
        <div class="modal-content" style="max-width: 450px; text-align: center;">
            <div style="margin-bottom: 1.5rem;">
                <div style="width: 70px; height: 70px; background: rgba(245, 158, 11, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    <i data-lucide="archive" style="width: 36px; height: 36px; color: #f59e0b;"></i>
                </div>
                <h2 style="color: #e2e8f0; font-size: 1.3rem; margin-bottom: 0.5rem;">Archive User Account</h2>
                <p style="color: #94a3b8; font-size: 0.9rem;">You are about to archive:</p>
                <div style="background: rgba(15, 23, 42, 0.6); border-radius: 8px; padding: 1rem; margin-top: 0.75rem; border: 1px solid rgba(245, 158, 11, 0.2);">
                    <p id="archiveUserName" style="color: #e2e8f0; font-weight: 600; font-size: 1rem;"></p>
                    <p id="archiveUserEmail" style="color: #94a3b8; font-size: 0.85rem;"></p>
                </div>
                <p style="color: #f59e0b; font-size: 0.8rem; margin-top: 0.75rem;">This account will be archived and the user will no longer be able to log in. A verification code will be sent to your email.</p>
            </div>
            <div style="display: flex; gap: 0.75rem; justify-content: center;">
                <button type="button" onclick="closeArchiveConfirm()" class="btn btn-secondary" style="flex: 1;">Cancel</button>
                <form method="POST" style="flex: 1;">
                    <input type="hidden" name="user_id" id="archiveUserId">
                    <button type="submit" name="request_archive" class="btn btn-danger" style="width: 100%;">
                        <i data-lucide="shield-alert" style="width: 16px; height: 16px;"></i>
                        Send Verification Code
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Archive OTP Verification Modal -->
    <div id="archiveOtpModal" class="modal <?php echo $show_archive_otp ? 'active' : ''; ?>">
        <div class="modal-content" style="max-width: 420px; text-align: center;">
            <div style="margin-bottom: 1.5rem;">
                <div style="width: 70px; height: 70px; background: rgba(14, 165, 233, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    <i data-lucide="key-round" style="width: 36px; height: 36px; color: #0ea5e9;"></i>
                </div>
                <h2 style="color: #e2e8f0; font-size: 1.3rem; margin-bottom: 0.5rem;">Verification Required</h2>
                <p style="color: #94a3b8; font-size: 0.9rem;">Enter the 6-digit code sent to your email to confirm archiving of:</p>
                <p style="color: #f59e0b; font-weight: 600; margin-top: 0.5rem;"><?php echo htmlspecialchars($pending_archive_name); ?></p>
            </div>

            <?php if ($error_message && $show_archive_otp): ?>
            <div class="alert alert-error" style="margin-bottom: 1rem; justify-content: center;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <?php if ($success_message && $show_archive_otp): ?>
            <div class="alert alert-success" style="margin-bottom: 1rem; justify-content: center;">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($pending_archive_id); ?>">
                <div class="otp-inputs" style="display: flex; gap: 0.6rem; justify-content: center; margin-bottom: 1.5rem;">
                    <input type="text" name="archive_otp_digits[]" maxlength="1" class="archive-otp-digit" data-index="0" style="width: 48px; height: 56px; background: #2a3544; border: 2px solid #3a4554; border-radius: 10px; color: #fff; font-size: 1.4rem; font-weight: 600; text-align: center; outline: none; transition: all 0.3s;" onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#3a4554'">
                    <input type="text" name="archive_otp_digits[]" maxlength="1" class="archive-otp-digit" data-index="1" style="width: 48px; height: 56px; background: #2a3544; border: 2px solid #3a4554; border-radius: 10px; color: #fff; font-size: 1.4rem; font-weight: 600; text-align: center; outline: none; transition: all 0.3s;" onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#3a4554'">
                    <input type="text" name="archive_otp_digits[]" maxlength="1" class="archive-otp-digit" data-index="2" style="width: 48px; height: 56px; background: #2a3544; border: 2px solid #3a4554; border-radius: 10px; color: #fff; font-size: 1.4rem; font-weight: 600; text-align: center; outline: none; transition: all 0.3s;" onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#3a4554'">
                    <input type="text" name="archive_otp_digits[]" maxlength="1" class="archive-otp-digit" data-index="3" style="width: 48px; height: 56px; background: #2a3544; border: 2px solid #3a4554; border-radius: 10px; color: #fff; font-size: 1.4rem; font-weight: 600; text-align: center; outline: none; transition: all 0.3s;" onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#3a4554'">
                    <input type="text" name="archive_otp_digits[]" maxlength="1" class="archive-otp-digit" data-index="4" style="width: 48px; height: 56px; background: #2a3544; border: 2px solid #3a4554; border-radius: 10px; color: #fff; font-size: 1.4rem; font-weight: 600; text-align: center; outline: none; transition: all 0.3s;" onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#3a4554'">
                    <input type="text" name="archive_otp_digits[]" maxlength="1" class="archive-otp-digit" data-index="5" style="width: 48px; height: 56px; background: #2a3544; border: 2px solid #3a4554; border-radius: 10px; color: #fff; font-size: 1.4rem; font-weight: 600; text-align: center; outline: none; transition: all 0.3s;" onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#3a4554'">
                </div>
                <input type="hidden" name="otp_code" id="archiveOtpHidden">
                
                <div style="display: flex; gap: 0.75rem;">
                    <button type="button" onclick="closeArchiveOtp()" class="btn btn-secondary" style="flex: 1;">Cancel</button>
                    <button type="submit" name="verify_archive_otp" class="btn btn-danger" style="flex: 1;">
                        <i data-lucide="archive" style="width: 16px; height: 16px;"></i>
                        Confirm Archive
                    </button>
                </div>
            </form>

            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(58, 69, 84, 0.5);">
                <p style="color: #94a3b8; font-size: 0.8rem;">
                    <i data-lucide="timer" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle;"></i>
                    <span id="archiveOtpTimer">Code expires in 5:00</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Toggle Status Confirmation Modal -->
    <div id="toggleConfirmModal" class="modal">
        <div class="modal-content" style="max-width: 450px; text-align: center;">
            <div style="margin-bottom: 1.5rem;">
                <div id="toggleIconWrap" style="width: 70px; height: 70px; background: rgba(245, 158, 11, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    <i data-lucide="shield-question" style="width: 36px; height: 36px; color: #f59e0b;"></i>
                </div>
                <h2 style="color: #e2e8f0; font-size: 1.3rem; margin-bottom: 0.5rem;">Change User Status</h2>
                <p style="color: #94a3b8; font-size: 0.9rem;">You are about to change the status of:</p>
                <div style="background: rgba(15, 23, 42, 0.6); border-radius: 8px; padding: 1rem; margin-top: 0.75rem; border: 1px solid rgba(245, 158, 11, 0.2);">
                    <p id="toggleUserName" style="color: #e2e8f0; font-weight: 600; font-size: 1rem;"></p>
                    <p style="margin-top: 0.5rem;">
                        <span id="toggleFromStatus" class="status-badge" style="margin-right: 0.5rem;"></span>
                        <span style="color: #94a3b8;">&rarr;</span>
                        <span id="toggleToStatus" class="status-badge" style="margin-left: 0.5rem;"></span>
                    </p>
                </div>
                <p style="color: #f59e0b; font-size: 0.8rem; margin-top: 0.75rem;">A verification code will be sent to your email to confirm this action.</p>
            </div>
            <div style="display: flex; gap: 0.75rem; justify-content: center;">
                <button type="button" onclick="closeToggleConfirm()" class="btn btn-secondary" style="flex: 1;">Cancel</button>
                <form method="POST" style="flex: 1;">
                    <input type="hidden" name="user_id" id="toggleUserId">
                    <input type="hidden" name="new_status" id="toggleNewStatus">
                    <button type="submit" name="request_toggle" class="btn btn-warning" style="width: 100%;">
                        <i data-lucide="shield-alert" style="width: 16px; height: 16px;"></i>
                        Send Verification Code
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Toggle Status OTP Verification Modal -->
    <div id="toggleOtpModal" class="modal <?php echo $show_toggle_otp ? 'active' : ''; ?>">
        <div class="modal-content" style="max-width: 420px; text-align: center;">
            <div style="margin-bottom: 1.5rem;">
                <div style="width: 70px; height: 70px; background: rgba(14, 165, 233, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    <i data-lucide="key-round" style="width: 36px; height: 36px; color: #0ea5e9;"></i>
                </div>
                <h2 style="color: #e2e8f0; font-size: 1.3rem; margin-bottom: 0.5rem;">Verification Required</h2>
                <p style="color: #94a3b8; font-size: 0.9rem;">Enter the 6-digit code sent to your email to confirm status change for:</p>
                <p style="color: #f59e0b; font-weight: 600; margin-top: 0.5rem;"><?php echo htmlspecialchars($pending_toggle_name); ?></p>
            </div>

            <?php if ($error_message && $show_toggle_otp): ?>
            <div class="alert alert-error" style="margin-bottom: 1rem; justify-content: center;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <?php if ($success_message && $show_toggle_otp): ?>
            <div class="alert alert-success" style="margin-bottom: 1rem; justify-content: center;">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($pending_toggle_id); ?>">
                <input type="hidden" name="new_status" value="<?php echo htmlspecialchars($pending_toggle_status); ?>">
                <div class="otp-inputs" style="display: flex; gap: 0.6rem; justify-content: center; margin-bottom: 1.5rem;">
                    <input type="text" name="toggle_otp_digits[]" maxlength="1" class="toggle-otp-digit" data-index="0" style="width: 48px; height: 56px; background: #2a3544; border: 2px solid #3a4554; border-radius: 10px; color: #fff; font-size: 1.4rem; font-weight: 600; text-align: center; outline: none; transition: all 0.3s;" onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#3a4554'">
                    <input type="text" name="toggle_otp_digits[]" maxlength="1" class="toggle-otp-digit" data-index="1" style="width: 48px; height: 56px; background: #2a3544; border: 2px solid #3a4554; border-radius: 10px; color: #fff; font-size: 1.4rem; font-weight: 600; text-align: center; outline: none; transition: all 0.3s;" onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#3a4554'">
                    <input type="text" name="toggle_otp_digits[]" maxlength="1" class="toggle-otp-digit" data-index="2" style="width: 48px; height: 56px; background: #2a3544; border: 2px solid #3a4554; border-radius: 10px; color: #fff; font-size: 1.4rem; font-weight: 600; text-align: center; outline: none; transition: all 0.3s;" onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#3a4554'">
                    <input type="text" name="toggle_otp_digits[]" maxlength="1" class="toggle-otp-digit" data-index="3" style="width: 48px; height: 56px; background: #2a3544; border: 2px solid #3a4554; border-radius: 10px; color: #fff; font-size: 1.4rem; font-weight: 600; text-align: center; outline: none; transition: all 0.3s;" onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#3a4554'">
                    <input type="text" name="toggle_otp_digits[]" maxlength="1" class="toggle-otp-digit" data-index="4" style="width: 48px; height: 56px; background: #2a3544; border: 2px solid #3a4554; border-radius: 10px; color: #fff; font-size: 1.4rem; font-weight: 600; text-align: center; outline: none; transition: all 0.3s;" onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#3a4554'">
                    <input type="text" name="toggle_otp_digits[]" maxlength="1" class="toggle-otp-digit" data-index="5" style="width: 48px; height: 56px; background: #2a3544; border: 2px solid #3a4554; border-radius: 10px; color: #fff; font-size: 1.4rem; font-weight: 600; text-align: center; outline: none; transition: all 0.3s;" onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#3a4554'">
                </div>
                <input type="hidden" name="otp_code" id="toggleOtpHidden">
                
                <div style="display: flex; gap: 0.75rem;">
                    <button type="button" onclick="closeToggleOtp()" class="btn btn-secondary" style="flex: 1;">Cancel</button>
                    <button type="submit" name="verify_toggle_otp" class="btn btn-warning" style="flex: 1;">
                        <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i>
                        Confirm Change
                    </button>
                </div>
            </form>

            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(58, 69, 84, 0.5);">
                <p style="color: #94a3b8; font-size: 0.8rem;">
                    <i data-lucide="timer" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle;"></i>
                    <span id="toggleOtpTimer">Code expires in 5:00</span>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Toggle status confirmation modal
        function openToggleConfirm(userId, userName, newStatus, currentStatus) {
            document.getElementById('toggleUserId').value = userId;
            document.getElementById('toggleNewStatus').value = newStatus;
            document.getElementById('toggleUserName').textContent = userName;
            
            const fromBadge = document.getElementById('toggleFromStatus');
            fromBadge.textContent = currentStatus;
            fromBadge.className = 'status-badge ' + currentStatus.toLowerCase();
            
            const toBadge = document.getElementById('toggleToStatus');
            toBadge.textContent = newStatus;
            toBadge.className = 'status-badge ' + newStatus.toLowerCase();
            
            document.getElementById('toggleConfirmModal').classList.add('active');
        }

        function closeToggleConfirm() {
            document.getElementById('toggleConfirmModal').classList.remove('active');
        }

        function closeToggleOtp() {
            document.getElementById('toggleOtpModal').classList.remove('active');
        }

        // Close toggle modals on outside click
        document.getElementById('toggleConfirmModal').addEventListener('click', function(e) {
            if (e.target === this) closeToggleConfirm();
        });
        document.getElementById('toggleOtpModal').addEventListener('click', function(e) {
            if (e.target === this) closeToggleOtp();
        });

        // OTP digit input handling for toggle modal
        var toggleOtpDigits = document.querySelectorAll('.toggle-otp-digit');
        var toggleOtpHidden = document.getElementById('toggleOtpHidden');

        toggleOtpDigits.forEach((digit, index) => {
            digit.addEventListener('input', (e) => {
                const value = e.target.value.replace(/[^0-9]/g, '');
                e.target.value = value;
                if (value.length === 1 && index < toggleOtpDigits.length - 1) {
                    toggleOtpDigits[index + 1].focus();
                }
                updateToggleOtp();
            });
            digit.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !digit.value && index > 0) {
                    toggleOtpDigits[index - 1].focus();
                }
            });
            digit.addEventListener('paste', (e) => {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
                pastedData.split('').forEach((char, i) => {
                    if (toggleOtpDigits[i]) toggleOtpDigits[i].value = char;
                });
                updateToggleOtp();
                if (pastedData.length === 6) toggleOtpDigits[5].focus();
            });
        });

        function updateToggleOtp() {
            let otp = '';
            toggleOtpDigits.forEach(d => otp += d.value);
            toggleOtpHidden.value = otp;
        }

        // OTP Timer for toggle modal
        <?php if ($show_toggle_otp): ?>
        (function() {
            let timeLeft = 300;
            const timerEl = document.getElementById('toggleOtpTimer');
            const countdown = setInterval(() => {
                timeLeft--;
                const m = Math.floor(timeLeft / 60);
                const s = timeLeft % 60;
                timerEl.textContent = 'Code expires in ' + m + ':' + s.toString().padStart(2, '0');
                if (timeLeft <= 0) {
                    clearInterval(countdown);
                    timerEl.textContent = 'Code expired. Please try again.';
                    timerEl.style.color = '#ef4444';
                }
            }, 1000);
            document.querySelector('.toggle-otp-digit[data-index="0"]')?.focus();
        })();
        <?php endif; ?>

        // Archive confirmation modal
        function openArchiveConfirm(userId, userName, userEmail) {
            document.getElementById('archiveUserId').value = userId;
            document.getElementById('archiveUserName').textContent = userName;
            document.getElementById('archiveUserEmail').textContent = userEmail;
            document.getElementById('archiveConfirmModal').classList.add('active');
        }

        function closeArchiveConfirm() {
            document.getElementById('archiveConfirmModal').classList.remove('active');
        }

        function closeArchiveOtp() {
            document.getElementById('archiveOtpModal').classList.remove('active');
        }

        // Close modals on outside click
        document.getElementById('archiveConfirmModal').addEventListener('click', function(e) {
            if (e.target === this) closeArchiveConfirm();
        });
        document.getElementById('archiveOtpModal').addEventListener('click', function(e) {
            if (e.target === this) closeArchiveOtp();
        });

        // OTP digit input handling for archive modal
        var archiveOtpDigits = document.querySelectorAll('.archive-otp-digit');
        var archiveOtpHidden = document.getElementById('archiveOtpHidden');

        archiveOtpDigits.forEach((digit, index) => {
            digit.addEventListener('input', (e) => {
                const value = e.target.value.replace(/[^0-9]/g, '');
                e.target.value = value;
                if (value.length === 1 && index < archiveOtpDigits.length - 1) {
                    archiveOtpDigits[index + 1].focus();
                }
                updateArchiveOtp();
            });

            digit.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !digit.value && index > 0) {
                    archiveOtpDigits[index - 1].focus();
                }
            });

            digit.addEventListener('paste', (e) => {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
                pastedData.split('').forEach((char, i) => {
                    if (archiveOtpDigits[i]) archiveOtpDigits[i].value = char;
                });
                updateArchiveOtp();
                if (pastedData.length === 6) archiveOtpDigits[5].focus();
            });
        });

        function updateArchiveOtp() {
            let otp = '';
            archiveOtpDigits.forEach(d => otp += d.value);
            archiveOtpHidden.value = otp;
        }

        // OTP Timer for archive modal
        <?php if ($show_archive_otp): ?>
        (function() {
            let timeLeft = 300;
            const timerEl = document.getElementById('archiveOtpTimer');
            const countdown = setInterval(() => {
                timeLeft--;
                const m = Math.floor(timeLeft / 60);
                const s = timeLeft % 60;
                timerEl.textContent = 'Code expires in ' + m + ':' + s.toString().padStart(2, '0');
                if (timeLeft <= 0) {
                    clearInterval(countdown);
                    timerEl.textContent = 'Code expired. Please try again.';
                    timerEl.style.color = '#ef4444';
                }
            }, 1000);
            document.querySelector('.archive-otp-digit[data-index="0"]')?.focus();
        })();
        <?php endif; ?>

        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Create New User';
            document.getElementById('userId').value = '';
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('password').setAttribute('required', 'required');
            document.getElementById('passwordLabel').textContent = 'Password *';
            document.getElementById('passwordHint').style.display = 'none';
            document.getElementById('firstName').value = '';
            document.getElementById('lastName').value = '';
            document.getElementById('email').value = '';
            document.getElementById('roleId').value = '';
            document.getElementById('departmentId').value = '';
            document.getElementById('status').value = 'Active';
            document.getElementById('userModal').classList.add('active');
        }

        function openEditModal(user) {
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('userId').value = user.id;
            document.getElementById('username').value = user.username || '';
            document.getElementById('password').value = '';
            document.getElementById('password').removeAttribute('required');
            document.getElementById('passwordLabel').textContent = 'Password';
            document.getElementById('passwordHint').style.display = 'block';
            document.getElementById('firstName').value = user.first_name;
            document.getElementById('lastName').value = user.last_name;
            document.getElementById('email').value = user.personal_email;
            document.getElementById('roleId').value = user.role_id;
            document.getElementById('departmentId').value = user.department_id || '';
            document.getElementById('status').value = user.status;
            document.getElementById('userModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('userModal').classList.remove('active');
        }

        // Close modal on outside click
        document.getElementById('userModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Auto-show OTP modals if they should be displayed
        <?php if ($show_toggle_otp): ?>
        // Close any other modals first
        closeToggleConfirm();
        // Show the OTP modal
        document.getElementById('toggleOtpModal').classList.add('active');
        // Focus first OTP digit
        setTimeout(() => {
            document.querySelector('.toggle-otp-digit[data-index="0"]')?.focus();
        }, 100);
        <?php endif; ?>

        <?php if ($show_archive_otp): ?>
        // Close any other modals first
        closeArchiveConfirm();
        // Show the OTP modal
        document.getElementById('archiveOtpModal').classList.add('active');
        // Focus first OTP digit
        setTimeout(() => {
            document.querySelector('.archive-otp-digit[data-index="0"]')?.focus();
        }, 100);
        <?php endif; ?>

        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</div>
