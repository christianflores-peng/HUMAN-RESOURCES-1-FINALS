<?php
require_once '../includes/session_helper.php';
startSecureSession();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../database/config.php';

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle DELETE action
if (isset($_POST['delete_user'])) {
    $delete_id = $_POST['user_id'];
    try {
        executeQuery("DELETE FROM user_accounts WHERE id = ?", [$delete_id]);
        $success_message = "User account deleted successfully.";
    } catch (Exception $e) {
        $error_message = "Failed to delete user: " . $e->getMessage();
    }
}

// Handle ACTIVATE/DEACTIVATE action
if (isset($_POST['toggle_status'])) {
    $toggle_id = $_POST['user_id'];
    $new_status = $_POST['new_status'];
    try {
        executeQuery("UPDATE user_accounts SET status = ? WHERE id = ?", [$new_status, $toggle_id]);
        $success_message = "User status updated successfully.";
    } catch (Exception $e) {
        $error_message = "Failed to update status: " . $e->getMessage();
    }
}

// Handle CREATE/UPDATE action
if (isset($_POST['save_user'])) {
    $edit_id = $_POST['user_id'] ?? null;
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $role_id = $_POST['role_id'];
    $department_id = $_POST['department_id'] ?? null;
    $status = $_POST['status'];
    
    try {
        if ($edit_id) {
            // UPDATE
            executeQuery(
                "UPDATE user_accounts SET first_name = ?, last_name = ?, personal_email = ?, role_id = ?, department_id = ?, status = ? WHERE id = ?",
                [$first_name, $last_name, $email, $role_id, $department_id, $status, $edit_id]
            );
            $success_message = "User account updated successfully.";
        } else {
            // CREATE
            $password = password_hash('Password123!', PASSWORD_DEFAULT);
            executeQuery(
                "INSERT INTO user_accounts (first_name, last_name, personal_email, password, role_id, department_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                [$first_name, $last_name, $email, $password, $role_id, $department_id, $status]
            );
            $success_message = "User account created successfully. Default password: Password123!";
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
    $where_conditions[] = "(ua.first_name LIKE ? OR ua.last_name LIKE ? OR ua.personal_email LIKE ? OR ua.employee_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Fetch users
$users = fetchAll("
    SELECT ua.*, r.role_name, r.role_type, d.department_name
    FROM user_accounts ua
    LEFT JOIN roles r ON r.id = ua.role_id
    LEFT JOIN departments d ON d.id = ua.department_id
    $where_clause
    ORDER BY ua.created_at DESC
", $params);

// Fetch roles and departments for dropdowns
$roles = fetchAll("SELECT id, role_name FROM roles ORDER BY role_name");
$departments = fetchAll("SELECT id, department_name FROM departments ORDER BY department_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Accounts - HR1</title>
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
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: rgba(15, 23, 42, 0.95);
            border-right: 1px solid rgba(58, 69, 84, 0.5);
            padding: 1.5rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .logo-section {
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(58, 69, 84, 0.5);
            margin-bottom: 1.5rem;
        }

        .logo-section img {
            width: 60px;
            margin-bottom: 0.5rem;
        }

        .logo-section h2 {
            font-size: 1.1rem;
            color: #ef4444;
        }

        .logo-section p {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        .admin-badge {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            padding: 0.35rem 0.75rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
            margin-top: 0.5rem;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border-left: 3px solid #ef4444;
        }

        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 2rem;
        }

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
        .status-badge.inactive { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .status-badge.suspended { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

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
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-section">
                <img src="assets/images/slate.png" alt="SLATE Logo">
                <h2>Admin Portal</h2>
                <p><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></p>
                <span class="admin-badge">ADMINISTRATOR</span>
            </div>

            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="admin-dashboard.php" class="nav-link">
                        <span class="material-symbols-outlined">dashboard</span>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin-accounts.php" class="nav-link active">
                        <span class="material-symbols-outlined">manage_accounts</span>
                        Manage Accounts
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin-audit-logs.php" class="nav-link">
                        <span class="material-symbols-outlined">history</span>
                        Audit Logs
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin-roles.php" class="nav-link">
                        <span class="material-symbols-outlined">shield_person</span>
                        Roles & Permissions
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin-settings.php" class="nav-link">
                        <span class="material-symbols-outlined">settings</span>
                        System Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <span class="material-symbols-outlined">logout</span>
                        Logout
                    </a>
                </li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Manage Accounts</h1>
                <button onclick="openCreateModal()" class="btn btn-primary">
                    <span class="material-symbols-outlined">add</span>
                    Create New User
                </button>
            </div>

            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <span class="material-symbols-outlined">check_circle</span>
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
            <div class="alert alert-error">
                <span class="material-symbols-outlined">error</span>
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
                                <option value="Suspended" <?php echo $filter_status === 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
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
                            <span class="material-symbols-outlined">search</span>
                            Apply Filters
                        </button>
                        <a href="admin-accounts.php" class="btn btn-secondary">
                            <span class="material-symbols-outlined">clear</span>
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
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="new_status" value="<?php echo $user['status'] === 'Active' ? 'Inactive' : 'Active'; ?>">
                                        <button type="submit" name="toggle_status" class="btn btn-sm btn-warning" onclick="return confirm('Toggle user status?')">
                                            <span class="material-symbols-outlined"><?php echo $user['status'] === 'Active' ? 'block' : 'check_circle'; ?></span>
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </form>
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
                            <option value="Suspended">Suspended</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" name="save_user" class="btn btn-success">
                        <span class="material-symbols-outlined">save</span>
                        Save User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Create New User';
            document.getElementById('userId').value = '';
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
    </script>
</body>
</html>
