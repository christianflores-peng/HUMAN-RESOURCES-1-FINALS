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
    header('Location: index.php?page=audit-logs');
    exit();
}

require_once '../../database/config.php';

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
    $where_conditions[] = "DATE(al.created_at) = ?";
    $params[] = $filter_date;
}

if ($filter_action) {
    $where_conditions[] = "al.action = ?";
    $params[] = $filter_action;
}

if ($filter_user) {
    $where_conditions[] = "al.user_id = ?";
    $params[] = $filter_user;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Fetch audit logs
try {
    $logs = fetchAll("
        SELECT al.*, 
               ua.first_name, ua.last_name, ua.company_email, ua.personal_email,
               r.role_name, r.role_type
        FROM audit_logs al
        LEFT JOIN user_accounts ua ON ua.id = al.user_id
        LEFT JOIN roles r ON r.id = ua.role_id
        $where_clause
        ORDER BY al.created_at DESC
        LIMIT $per_page OFFSET $offset
    ", $params);
} catch (Exception $e) {
    $logs = [];
}

// Get total count for pagination
try {
    $total_logs = fetchSingle("
        SELECT COUNT(*) as count 
        FROM audit_logs al
        $where_clause
    ", $params)['count'] ?? 0;
} catch (Exception $e) {
    $total_logs = 0;
}

$total_pages = max(1, ceil($total_logs / $per_page));

// Get unique actions for filter
try {
    $actions = fetchAll("SELECT DISTINCT action FROM audit_logs ORDER BY action");
} catch (Exception $e) {
    $actions = [];
}

// Get users for filter
try {
    $users = fetchAll("
        SELECT DISTINCT ua.id, ua.first_name, ua.last_name
        FROM user_accounts ua
        INNER JOIN audit_logs al ON al.user_id = ua.id
        ORDER BY ua.first_name, ua.last_name
    ");
} catch (Exception $e) {
    $users = [];
}
?>
<div data-page-title="Audit Logs">
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

        .empty-state i {
            width: 5rem;
            height: 5rem;
            color: #475569;
            margin-bottom: 1rem;
        }
    </style>
            <div class="header">
                <div><h1>Audit Logs</h1></div>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                </div>
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
                                <option value="<?php echo htmlspecialchars($action['action']); ?>" <?php echo $filter_action === $action['action'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($action['action']); ?>
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
                            <i data-lucide="search"></i>
                            Apply Filters
                        </button>
                        <a href="#" data-page="audit-logs" class="btn btn-secondary">
                            <i data-lucide="x"></i>
                            Clear Filters
                        </a>
                    </div>
                </form>
            </div>

            <div class="logs-table">
                <?php if (empty($logs)): ?>
                <div class="empty-state">
                    <i data-lucide="history"></i>
                    <h3>No Audit Logs Found</h3>
                    <p>No activities match your filters.</p>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo date('M d, Y g:i A', strtotime($log['created_at'])); ?></td>
                            <td>
                                <?php echo htmlspecialchars(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')); ?>
                                <br>
                                <small style="color: #94a3b8;"><?php echo htmlspecialchars($log['company_email'] ?? $log['personal_email'] ?? $log['user_email'] ?? 'N/A'); ?></small>
                            </td>
                            <td>
                                <span class="status-badge" style="background: rgba(139, 92, 246, 0.2); color: #8b5cf6;">
                                    <?php echo htmlspecialchars($log['role_type'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo strtolower($log['action']); ?>">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log['module'] ?? 'N/A'); ?></td>
                            <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?php echo htmlspecialchars($log['detail'] ?? ''); ?>
                            </td>
                            <td><small style="color: #94a3b8;"><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php endif; ?>
            </div>
<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
</div>
