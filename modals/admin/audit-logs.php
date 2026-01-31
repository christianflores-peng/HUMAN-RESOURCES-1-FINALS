<?php
require_once '../../includes/session_helper.php';
startSecureSession();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Admin') {
    header('Location: ../../index.php');
    exit();
}

require_once '../../database/config.php';

$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

$filter_date = $_GET['date'] ?? '';
$filter_action = $_GET['action'] ?? '';

$where = "WHERE 1=1";
$params = [];
if ($filter_date) { $where .= " AND DATE(ash.changed_at) = ?"; $params[] = $filter_date; }
if ($filter_action) { $where .= " AND ash.new_status = ?"; $params[] = $filter_action; }

$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$total = fetchSingle("
    SELECT COUNT(*) as count 
    FROM application_status_history ash 
    $where
", $params)['count'] ?? 0;

$total_pages = ceil($total / $per_page);

$logs = fetchAll("
    SELECT ash.*, 
           ja.first_name as applicant_first, ja.last_name as applicant_last,
           jp.title as job_title,
           u.first_name as user_first, u.last_name as user_last
    FROM application_status_history ash
    LEFT JOIN job_applications ja ON ja.id = ash.application_id
    LEFT JOIN job_postings jp ON jp.id = ja.job_posting_id
    LEFT JOIN user_accounts u ON u.id = ash.changed_by
    $where
    ORDER BY ash.changed_at DESC
    LIMIT $per_page OFFSET $offset
", $params);

$statuses = ['new', 'screening', 'interview', 'road_test', 'offer_sent', 'hired', 'rejected'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - HR1 Admin</title>
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
        .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
        .header h1 { font-size: 1.5rem; color: #e2e8f0; }
        .filters { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1rem 1.5rem; margin-bottom: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; }
        .filters input, .filters select { padding: 0.5rem 1rem; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(58, 69, 84, 0.5); border-radius: 6px; color: #e2e8f0; font-size: 0.85rem; }
        .filters input:focus, .filters select:focus { outline: none; border-color: #f97316; }
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 6px; font-size: 0.85rem; cursor: pointer; transition: all 0.3s; text-decoration: none; }
        .btn-primary { background: #f97316; color: white; }
        .btn-primary:hover { background: #ea580c; }
        .card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; border: 1px solid rgba(58, 69, 84, 0.5); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid rgba(58, 69, 84, 0.3); }
        th { background: rgba(15, 23, 42, 0.4); color: #94a3b8; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; }
        td { color: #e2e8f0; font-size: 0.9rem; }
        .status-badge { padding: 0.25rem 0.6rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .status-badge.new { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
        .status-badge.screening { background: rgba(168, 85, 247, 0.2); color: #a855f7; }
        .status-badge.interview { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .status-badge.road_test { background: rgba(249, 115, 22, 0.2); color: #f97316; }
        .status-badge.offer_sent { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .status-badge.hired { background: rgba(34, 197, 94, 0.3); color: #22c55e; }
        .status-badge.rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .pagination { display: flex; justify-content: center; gap: 0.5rem; margin-top: 1.5rem; }
        .pagination a, .pagination span { padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; color: #e2e8f0; background: rgba(30, 41, 54, 0.6); border: 1px solid rgba(58, 69, 84, 0.5); }
        .pagination a:hover { border-color: #f97316; }
        .pagination .active { background: #f97316; border-color: #f97316; }
        .empty-state { text-align: center; padding: 3rem; color: #94a3b8; }
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
                <li class="nav-item"><a href="accounts.php" class="nav-link"><span class="material-symbols-outlined">manage_accounts</span>User Accounts</a></li>
                <li class="nav-item"><a href="audit-logs.php" class="nav-link active"><span class="material-symbols-outlined">history</span>Audit Logs</a></li>
                <li class="nav-item"><a href="../../index.php" class="nav-link"><span class="material-symbols-outlined">home</span>Main Dashboard</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link"><span class="material-symbols-outlined">logout</span>Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header"><h1>Audit Logs</h1></div>

            <form method="GET" class="filters">
                <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
                <select name="action">
                    <option value="">All Actions</option>
                    <?php foreach ($statuses as $status): ?>
                    <option value="<?php echo $status; ?>" <?php echo $filter_action === $status ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $status)); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="audit-logs.php" class="btn" style="background: rgba(58, 69, 84, 0.5);">Clear</a>
            </form>

            <div class="card">
                <?php if (empty($logs)): ?>
                <div class="empty-state"><p>No audit logs found.</p></div>
                <?php else: ?>
                <table>
                    <thead><tr><th>Date/Time</th><th>Applicant</th><th>Job</th><th>From</th><th>To</th><th>Changed By</th></tr></thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo date('M d, Y H:i', strtotime($log['changed_at'])); ?></td>
                            <td><?php echo htmlspecialchars($log['applicant_first'] . ' ' . $log['applicant_last']); ?></td>
                            <td><?php echo htmlspecialchars($log['job_title'] ?? 'N/A'); ?></td>
                            <td><span class="status-badge <?php echo $log['old_status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $log['old_status'])); ?></span></td>
                            <td><span class="status-badge <?php echo $log['new_status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $log['new_status'])); ?></span></td>
                            <td><?php echo htmlspecialchars($log['user_first'] . ' ' . $log['user_last']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?><a href="?page=<?php echo $page - 1; ?>&date=<?php echo $filter_date; ?>&action=<?php echo $filter_action; ?>">Prev</a><?php endif; ?>
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?>&date=<?php echo $filter_date; ?>&action=<?php echo $filter_action; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?><a href="?page=<?php echo $page + 1; ?>&date=<?php echo $filter_date; ?>&action=<?php echo $filter_action; ?>">Next</a><?php endif; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
    <?php include '../../includes/logout-modal.php'; ?>
</body>
</html>
