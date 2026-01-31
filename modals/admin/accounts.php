<?php
require_once '../../includes/session_helper.php';
startSecureSession();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Admin') {
    header('Location: ../../index.php');
    exit();
}

require_once '../../database/config.php';

$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = password_hash($_POST['password'] ?? 'default123', PASSWORD_DEFAULT);
        $role_id = $_POST['role_id'] ?? 4;
        
        $existing = fetchSingle("SELECT id FROM user_accounts WHERE personal_email = ?", [$email]);
        if ($existing) {
            $error_message = "Email already exists.";
        } else {
            executeQuery("INSERT INTO user_accounts (first_name, last_name, personal_email, password, role_id, status) VALUES (?, ?, ?, ?, ?, 'active')",
                [$first_name, $last_name, $email, $password, $role_id]);
            $success_message = "User created successfully!";
        }
    } elseif ($action === 'toggle_status') {
        $user_id = $_POST['user_id'] ?? 0;
        $current_status = $_POST['current_status'] ?? 'active';
        $new_status = $current_status === 'active' ? 'inactive' : 'active';
        executeQuery("UPDATE user_accounts SET status = ? WHERE id = ?", [$new_status, $user_id]);
        $success_message = "User status updated.";
    } elseif ($action === 'delete') {
        $user_id = $_POST['user_id'] ?? 0;
        executeQuery("DELETE FROM user_accounts WHERE id = ?", [$user_id]);
        $success_message = "User deleted.";
    }
}

$filter_status = $_GET['status'] ?? '';
$filter_role = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';

$where = "WHERE 1=1";
$params = [];
if ($filter_status) { $where .= " AND u.status = ?"; $params[] = $filter_status; }
if ($filter_role) { $where .= " AND r.role_name = ?"; $params[] = $filter_role; }
if ($search) { $where .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.personal_email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

$users = fetchAll("
    SELECT u.*, r.role_name
    FROM user_accounts u
    LEFT JOIN roles r ON r.id = u.role_id
    $where
    ORDER BY u.created_at DESC
", $params);

$roles = fetchAll("SELECT * FROM roles ORDER BY role_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Accounts - HR1 Admin</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #0a1929 0%, #1a2942 100%); min-height: 100vh; color: #f8fafc; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: rgba(15, 23, 42, 0.95); border-right: 1px solid rgba(58, 69, 84, 0.5); padding: 1.5rem 0; position: fixed; height: 100vh; overflow-y: auto; }
        .logo-section { padding: 0 1.5rem 1.5rem; border-bottom: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1.5rem; }
        .logo-section img { width: 60px; margin-bottom: 0.5rem; }
        .logo-section h2 { font-size: 1.1rem; color: #f97316; }
        .logo-section p { font-size: 0.75rem; color: #94a3b8; }
        .nav-menu { list-style: none; }
        .nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; color: #cbd5e1; text-decoration: none; transition: all 0.3s; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(249, 115, 22, 0.1); color: #f97316; border-left: 3px solid #f97316; }
        .main-content { flex: 1; margin-left: 260px; padding: 2rem; }
        .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5rem; color: #e2e8f0; }
        .btn { padding: 0.65rem 1.25rem; border: none; border-radius: 6px; font-size: 0.9rem; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: #f97316; color: white; }
        .btn-primary:hover { background: #ea580c; }
        .btn-sm { padding: 0.4rem 0.75rem; font-size: 0.8rem; }
        .btn-success { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .btn-danger { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .filters { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1rem 1.5rem; margin-bottom: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; }
        .filters select, .filters input { padding: 0.5rem 1rem; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(58, 69, 84, 0.5); border-radius: 6px; color: #e2e8f0; font-size: 0.85rem; }
        .filters select:focus, .filters input:focus { outline: none; border-color: #f97316; }
        .card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; border: 1px solid rgba(58, 69, 84, 0.5); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid rgba(58, 69, 84, 0.3); }
        th { background: rgba(15, 23, 42, 0.4); color: #94a3b8; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; }
        td { color: #e2e8f0; font-size: 0.9rem; }
        .status-badge { padding: 0.3rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-badge.active { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .status-badge.inactive { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .role-badge { padding: 0.3rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .alert-success { background: rgba(34, 197, 94, 0.2); border: 1px solid rgba(34, 197, 94, 0.3); color: #22c55e; }
        .alert-error { background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; }
        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal.active { display: flex; }
        .modal-content { background: #1e293b; border-radius: 12px; padding: 2rem; width: 90%; max-width: 500px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-header h3 { color: #e2e8f0; font-size: 1.25rem; }
        .close-btn { background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; color: #94a3b8; font-size: 0.85rem; margin-bottom: 0.5rem; }
        .form-group input, .form-group select { width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(58, 69, 84, 0.5); border-radius: 8px; color: #e2e8f0; font-size: 0.9rem; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #f97316; }
        .actions { display: flex; gap: 0.5rem; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-section">
                <img src="../../assets/images/slate.png" alt="SLATE Logo">
                <h2>Admin Portal</h2>
                <p><?php echo htmlspecialchars($user_name); ?></p>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><span class="material-symbols-outlined">dashboard</span>Dashboard</a></li>
                <li class="nav-item"><a href="accounts.php" class="nav-link active"><span class="material-symbols-outlined">manage_accounts</span>User Accounts</a></li>
                <li class="nav-item"><a href="audit-logs.php" class="nav-link"><span class="material-symbols-outlined">history</span>Audit Logs</a></li>
                <li class="nav-item"><a href="../../index.php" class="nav-link"><span class="material-symbols-outlined">home</span>Main Dashboard</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link"><span class="material-symbols-outlined">logout</span>Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>User Accounts</h1>
                <button class="btn btn-primary" onclick="openModal()"><span class="material-symbols-outlined">add</span>Add User</button>
            </div>

            <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
            <?php if ($error_message): ?><div class="alert alert-error"><?php echo $error_message; ?></div><?php endif; ?>

            <form method="GET" class="filters">
                <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="status"><option value="">All Status</option><option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>Inactive</option></select>
                <select name="role"><option value="">All Roles</option><?php foreach ($roles as $role): ?><option value="<?php echo $role['role_name']; ?>" <?php echo $filter_role === $role['role_name'] ? 'selected' : ''; ?>><?php echo $role['role_name']; ?></option><?php endforeach; ?></select>
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            </form>

            <div class="card">
                <table>
                    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['personal_email']); ?></td>
                            <td><span class="role-badge"><?php echo htmlspecialchars($user['role_name'] ?? 'N/A'); ?></span></td>
                            <td><span class="status-badge <?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td class="actions">
                                <form method="POST" style="display: inline;"><input type="hidden" name="action" value="toggle_status"><input type="hidden" name="user_id" value="<?php echo $user['id']; ?>"><input type="hidden" name="current_status" value="<?php echo $user['status']; ?>"><button type="submit" class="btn btn-sm <?php echo $user['status'] === 'active' ? 'btn-danger' : 'btn-success'; ?>"><?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?></button></form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div class="modal" id="createModal">
        <div class="modal-content">
            <div class="modal-header"><h3>Add New User</h3><button class="close-btn" onclick="closeModal()">&times;</button></div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-group"><label>First Name</label><input type="text" name="first_name" required></div>
                <div class="form-group"><label>Last Name</label><input type="text" name="last_name" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
                <div class="form-group"><label>Role</label><select name="role_id"><?php foreach ($roles as $role): ?><option value="<?php echo $role['id']; ?>"><?php echo $role['role_name']; ?></option><?php endforeach; ?></select></div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Create User</button>
            </form>
        </div>
    </div>

    <?php include '../../includes/logout-modal.php'; ?>
    <script>
        function openModal() { document.getElementById('createModal').classList.add('active'); }
        function closeModal() { document.getElementById('createModal').classList.remove('active'); }
    </script>
</body>
</html>
