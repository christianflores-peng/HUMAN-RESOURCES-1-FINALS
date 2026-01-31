<?php
/**
 * User Management Utility
 * Admin tool for managing users and registration system
 */

session_start();
require_once '../database/config.php';
require_once '../includes/rbac_helper.php';

// Check if user is logged in and has admin privileges using RBAC
if (!isset($_SESSION['user_id'])) {
    header('Location: ../partials/login.php');
    exit();
}

// Use RBAC function instead of hardcoded role check
if (!isAdmin($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php');
    exit();
}

$message = '';
$error_message = '';

// Handle user management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_user':
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? 'Employee';
                $full_name = trim($_POST['full_name'] ?? '');
                $company = trim($_POST['company'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                
                if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
                    $error_message = 'Please fill in all required fields.';
                } else {
                    // Check if user already exists in user_accounts (primary) or users (legacy)
                    $existing = fetchSingle("SELECT id FROM user_accounts WHERE company_email = ? OR personal_email = ?", [$email, $email]);
                    if (!$existing) {
                        $existing = fetchSingle("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
                    }
                    if ($existing) {
                        $error_message = 'Username or email already exists.';
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Parse full name into first and last name
                        $name_parts = explode(' ', $full_name, 2);
                        $first_name = $name_parts[0];
                        $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
                        
                        // Get role_id based on role name
                        $role_id = 7; // Default to Employee
                        $roleMapping = ['Administrator' => 1, 'Admin' => 1, 'HR Staff' => 2, 'HR Manager' => 3, 'Fleet Manager' => 4, 'Warehouse Manager' => 5, 'Logistics Manager' => 6, 'Employee' => 7, 'New Hire' => 8];
                        if (isset($roleMapping[$role])) {
                            $role_id = $roleMapping[$role];
                        }
                        
                        $user_id = insertRecord(
                            "INSERT INTO user_accounts (first_name, last_name, personal_email, phone, password_hash, role_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'Active', NOW())",
                            [$first_name, $last_name, $email, $phone, $hashed_password, $role_id]
                        );
                        
                        if ($user_id) {
                            $message = "User '$full_name' created successfully!";
                        } else {
                            $error_message = 'Failed to create user.';
                        }
                    }
                }
                break;
                
            case 'update_user':
                $user_id = (int)($_POST['user_id'] ?? 0);
                $role = $_POST['role'] ?? '';
                $full_name = trim($_POST['full_name'] ?? '');
                $company = trim($_POST['company'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                
                if ($user_id > 0) {
                    // Parse full name into first and last name
                    $name_parts = explode(' ', $full_name, 2);
                    $first_name = $name_parts[0];
                    $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
                    
                    // Get role_id based on role name
                    $role_id = 7; // Default to Employee
                    $roleMapping = ['Administrator' => 1, 'Admin' => 1, 'HR Staff' => 2, 'HR Manager' => 3, 'Fleet Manager' => 4, 'Warehouse Manager' => 5, 'Logistics Manager' => 6, 'Employee' => 7, 'New Hire' => 8];
                    if (isset($roleMapping[$role])) {
                        $role_id = $roleMapping[$role];
                    }
                    
                    // Try to update user_accounts first
                    $updated = updateRecord(
                        "UPDATE user_accounts SET first_name = ?, last_name = ?, phone = ?, role_id = ? WHERE id = ?",
                        [$first_name, $last_name, $phone, $role_id, $user_id]
                    );
                    
                    // Fallback to legacy users table if no rows affected
                    if ($updated == 0) {
                        $updated = updateRecord(
                            "UPDATE users SET role = ?, full_name = ?, company = ?, phone = ? WHERE id = ?",
                            [$role, $full_name, $company, $phone, $user_id]
                        );
                    }
                    
                    if ($updated > 0) {
                        $message = "User updated successfully!";
                    } else {
                        $error_message = 'No changes made or user not found.';
                    }
                } else {
                    $error_message = 'Invalid user ID.';
                }
                break;
                
            case 'delete_user':
                $user_id = (int)($_POST['user_id'] ?? 0);
                
                if ($user_id > 0 && $user_id != $_SESSION['user_id']) {
                    // Try to delete from user_accounts first
                    $deleted = updateRecord("DELETE FROM user_accounts WHERE id = ?", [$user_id]);
                    
                    // Fallback to legacy users table if no rows affected
                    if ($deleted == 0) {
                        $deleted = updateRecord("DELETE FROM users WHERE id = ?", [$user_id]);
                    }
                    
                    if ($deleted > 0) {
                        $message = "User deleted successfully!";
                    } else {
                        $error_message = 'User not found or could not be deleted.';
                    }
                } else {
                    $error_message = 'Cannot delete your own account or invalid user ID.';
                }
                break;
                
            case 'reset_password':
                $user_id = (int)($_POST['user_id'] ?? 0);
                $new_password = $_POST['new_password'] ?? '';
                
                if ($user_id > 0 && !empty($new_password)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Try to update user_accounts first
                    $updated = updateRecord(
                        "UPDATE user_accounts SET password_hash = ? WHERE id = ?",
                        [$hashed_password, $user_id]
                    );
                    
                    // Fallback to legacy users table if no rows affected
                    if ($updated == 0) {
                        $updated = updateRecord(
                            "UPDATE users SET password = ? WHERE id = ?",
                            [$hashed_password, $user_id]
                        );
                    }
                    
                    if ($updated > 0) {
                        $message = "Password reset successfully!";
                    } else {
                        $error_message = 'Failed to reset password.';
                    }
                } else {
                    $error_message = 'Invalid user ID or empty password.';
                }
                break;
        }
    } catch (Exception $e) {
        $error_message = 'Error: ' . $e->getMessage();
    }
}

// Get all users from user_accounts (primary) with fallback to users (legacy)
try {
    // First try user_accounts table
    $users = fetchAll(
        "SELECT ua.id, ua.company_email as username, r.role_name as role, 
                CONCAT(ua.first_name, ' ', ua.last_name) as full_name, 
                ua.personal_email as email, ua.phone, ua.created_at
         FROM user_accounts ua
         LEFT JOIN roles r ON ua.role_id = r.id
         ORDER BY ua.created_at DESC"
    );
    
    // If empty, fallback to legacy users table
    if (empty($users)) {
        $users = fetchAll("SELECT id, username, role, full_name, email, company, phone, created_at FROM users ORDER BY created_at DESC");
    }
} catch (Exception $e) {
    $users = [];
    $error_message = 'Failed to load users: ' . $e->getMessage();
}

// Get user statistics
try {
    $stats = fetchSingle("SELECT 
        COUNT(*) as total_users,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_users_week,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_users_month
        FROM users");
    
    $role_stats = fetchAll("SELECT role, COUNT(*) as count FROM users GROUP BY role ORDER BY count DESC");
} catch (Exception $e) {
    $stats = ['total_users' => 0, 'new_users_week' => 0, 'new_users_month' => 0];
    $role_stats = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - HR1 System</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: #1e293b;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #3b82f6;
        }
        
        .stat-label {
            color: #cbd5e1;
            margin-top: 0.5rem;
        }
        
        .management-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .tab-button {
            padding: 0.75rem 1.5rem;
            background: #334155;
            color: #cbd5e1;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .tab-button.active {
            background: #3b82f6;
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            background: #1e293b;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .users-table th,
        .users-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #334155;
        }
        
        .users-table th {
            background: #0f172a;
            color: #f8fafc;
            font-weight: 600;
        }
        
        .users-table td {
            color: #cbd5e1;
        }
        
        .users-table tr:hover {
            background: #334155;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            color: #cbd5e1;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            background: #1e293b;
            border: 2px solid #334155;
            border-radius: 8px;
            color: #f8fafc;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        
        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .role-admin { background: #dc2626; color: white; }
        .role-hr { background: #2563eb; color: white; }
        .role-recruiter { background: #059669; color: white; }
        .role-employee { background: #6b7280; color: white; }
        .role-manager { background: #7c3aed; color: white; }
        .role-supervisor { background: #ea580c; color: white; }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1>👥 User Management</h1>
        <p>Manage users and registration system</p>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['new_users_week']; ?></div>
                <div class="stat-label">New This Week</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['new_users_month']; ?></div>
                <div class="stat-label">New This Month</div>
            </div>
        </div>
        
        <!-- Role Distribution -->
        <?php if (!empty($role_stats)): ?>
        <div style="background: #1e293b; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem;">
            <h3>Role Distribution</h3>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <?php foreach ($role_stats as $role_stat): ?>
                    <div style="background: #334155; padding: 0.5rem 1rem; border-radius: 20px;">
                        <span class="role-badge role-<?php echo strtolower(str_replace(' ', '-', $role_stat['role'])); ?>">
                            <?php echo htmlspecialchars($role_stat['role']); ?>
                        </span>
                        <span style="color: #cbd5e1; margin-left: 0.5rem;"><?php echo $role_stat['count']; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Management Tabs -->
        <div class="management-tabs">
            <button class="tab-button active" onclick="showTab('users')">View Users</button>
            <button class="tab-button" onclick="showTab('add')">Add User</button>
            <button class="tab-button" onclick="showTab('settings')">Settings</button>
        </div>
        
        <!-- Users Tab -->
        <div id="users" class="tab-content active">
            <h2>All Users</h2>
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Company</th>
                        <th>Phone</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo strtolower(str_replace(' ', '-', $user['role'])); ?>">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($user['company'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-warning" onclick="editUser(<?php echo $user['id']; ?>)">Edit</button>
                                <button class="btn btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Add User Tab -->
        <div id="add" class="tab-content">
            <h2>Add New User</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_user">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="username">Username *</label>
                        <input type="text" id="username" name="username" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email">Email *</label>
                        <input type="email" id="email" name="email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="password">Password *</label>
                        <input type="password" id="password" name="password" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="role">Role *</label>
                        <select id="role" name="role" class="form-input" required>
                            <option value="Employee">Employee</option>
                            <option value="Manager">Manager</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Recruiter">Recruiter</option>
                            <option value="HR Manager">HR Manager</option>
                            <option value="Administrator">Administrator</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="company">Company</label>
                        <input type="text" id="company" name="company" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" class="form-input">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Add User</button>
            </form>
        </div>
        
        <!-- Settings Tab -->
        <div id="settings" class="tab-content">
            <h2>System Settings</h2>
            <div style="background: #1e293b; padding: 1.5rem; border-radius: 10px;">
                <h3>Database Status</h3>
                <p>✅ Database connection: Active</p>
                <p>✅ Registration system: Enabled</p>
                <p>✅ User management: Active</p>
                
                <h3>Quick Actions</h3>
                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <a href="verify-database.php" class="btn btn-primary">Verify Database</a>
                    <a href="implement-database.php" class="btn btn-primary">Run Implementation</a>
                    <a href="register.php" class="btn btn-primary">Test Registration</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all buttons
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(button => button.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        function editUser(userId) {
            // This would open a modal or redirect to edit page
            alert('Edit functionality would be implemented here for user ID: ' + userId);
        }
        
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
