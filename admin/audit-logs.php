<?php
require_once '../includes/session_helper.php';
startSecureSession();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../database/config.php';

$user_id = $_SESSION['user_id'];

// Filters
$filter_date = $_GET['date'] ?? '';
$filter_action = $_GET['action'] ?? '';
$filter_user = $_GET['user'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where_conditions = [];
$params = [];

if ($filter_date) {
    $where_conditions[] = "DATE(ash.changed_at) = ?";
    $params[] = $filter_date;
}

if ($filter_action) {
    $where_conditions[] = "ash.new_status = ?";
    $params[] = $filter_action;
}

if ($filter_user) {
    $where_conditions[] = "ash.changed_by = ?";
    $params[] = $filter_user;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Fetch audit logs
$logs = fetchAll("
    SELECT ash.*, 
           ja.first_name, ja.last_name, ja.email,
           ua.first_name as changed_by_first, ua.last_name as changed_by_last,
           COALESCE(jp.title, 'General Application') as job_title
    FROM application_status_history ash
    LEFT JOIN job_applications ja ON ja.id = ash.application_id
    LEFT JOIN user_accounts ua ON ua.id = ash.changed_by
    LEFT JOIN job_postings jp ON jp.id = ja.job_posting_id
    $where_clause
    ORDER BY ash.changed_at DESC
    LIMIT $per_page OFFSET $offset
", $params);

// Get total count for pagination
$total_logs = fetchSingle("
    SELECT COUNT(*) as count 
    FROM application_status_history ash
    $where_clause
", $params)['count'];

$total_pages = ceil($total_logs / $per_page);

// Get unique actions for filter
$actions = fetchAll("SELECT DISTINCT new_status FROM application_status_history ORDER BY new_status");

// Get users for filter
$users = fetchAll("
    SELECT DISTINCT ua.id, ua.first_name, ua.last_name
    FROM user_accounts ua
    INNER JOIN application_status_history ash ON ash.changed_by = ua.id
    ORDER BY ua.first_name, ua.last_name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - HR1</title>
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
        }

        .header h1 {
            font-size: 1.5rem;
            color: #e2e8f0;
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        }

        .btn-primary {
            background: #ef4444;
            color: white;
        }

        .btn-primary:hover {
            background: #dc2626;
        }

        .btn-secondary {
            background: rgba(100, 116, 139, 0.3);
            color: #cbd5e1;
        }

        .btn-secondary:hover {
            background: rgba(100, 116, 139, 0.5);
        }

        .logs-table {
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
            background: rgba(14, 165, 233, 0.05);
        }

        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.new { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .status-badge.screening { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .status-badge.interview { background: rgba(139, 92, 246, 0.2); color: #8b5cf6; }
        .status-badge.road_test { background: rgba(236, 72, 153, 0.2); color: #ec4899; }
        .status-badge.offer_sent { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
        .status-badge.hired { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .status-badge.rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .page-btn {
            padding: 0.5rem 0.75rem;
            background: rgba(30, 41, 54, 0.6);
            border: 1px solid rgba(58, 69, 84, 0.5);
            border-radius: 6px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .page-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: #ef4444;
            color: #ef4444;
        }

        .page-btn.active {
            background: #ef4444;
            border-color: #ef4444;
            color: white;
        }

        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #94a3b8;
        }

        .empty-state .material-symbols-outlined {
            font-size: 5rem;
            color: #475569;
            margin-bottom: 1rem;
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
                    <a href="admin-accounts.php" class="nav-link">
                        <span class="material-symbols-outlined">manage_accounts</span>
                        Manage Accounts
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin-audit-logs.php" class="nav-link active">
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
                <h1>Audit Logs</h1>
            </div>

            <div class="filters-card">
                <form method="GET" action="">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label class="filter-label">Date</label>
                            <input type="date" name="date" class="filter-input" value="<?php echo htmlspecialchars($filter_date); ?>">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Action</label>
                            <select name="action" class="filter-select">
                                <option value="">All Actions</option>
                                <?php foreach ($actions as $action): ?>
                                <option value="<?php echo htmlspecialchars($action['new_status']); ?>" <?php echo $filter_action === $action['new_status'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($action['new_status']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">User</label>
                            <select name="user" class="filter-select">
                                <option value="">All Users</option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $filter_user == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
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
                        <a href="admin-audit-logs.php" class="btn btn-secondary">
                            <span class="material-symbols-outlined">clear</span>
                            Clear Filters
                        </a>
                    </div>
                </form>
            </div>

            <div class="logs-table">
                <?php if (empty($logs)): ?>
                <div class="empty-state">
                    <span class="material-symbols-outlined">history</span>
                    <h3>No Audit Logs Found</h3>
                    <p>No activities match your filters.</p>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Applicant</th>
                            <th>Job Position</th>
                            <th>Action</th>
                            <th>Changed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo date('M d, Y g:i A', strtotime($log['changed_at'])); ?></td>
                            <td>
                                <?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?>
                                <br>
                                <small style="color: #94a3b8;"><?php echo htmlspecialchars($log['email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($log['job_title']); ?></td>
                            <td>
                                <span class="status-badge <?php echo strtolower(str_replace(' ', '_', $log['new_status'])); ?>">
                                    <?php echo htmlspecialchars($log['old_status']); ?> → <?php echo htmlspecialchars($log['new_status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log['changed_by_first'] . ' ' . $log['changed_by_last']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&date=<?php echo urlencode($filter_date); ?>&action=<?php echo urlencode($filter_action); ?>&user=<?php echo urlencode($filter_user); ?>" class="page-btn">
                        <span class="material-symbols-outlined">chevron_left</span>
                    </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>&date=<?php echo urlencode($filter_date); ?>&action=<?php echo urlencode($filter_action); ?>&user=<?php echo urlencode($filter_user); ?>" 
                       class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&date=<?php echo urlencode($filter_date); ?>&action=<?php echo urlencode($filter_action); ?>&user=<?php echo urlencode($filter_user); ?>" class="page-btn">
                        <span class="material-symbols-outlined">chevron_right</span>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
