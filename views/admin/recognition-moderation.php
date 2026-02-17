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
    header('Location: index.php?page=recognition-moderation');
    exit();
}

require_once '../../database/config.php';

// Ensure recognition table/columns exist and match module expectations
try {
    executeQuery("CREATE TABLE IF NOT EXISTS social_recognitions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        recipient_id INT NOT NULL,
        given_by INT NULL,
        recognition_type VARCHAR(50) DEFAULT 'Kudos',
        message TEXT NOT NULL,
        badge_icon VARCHAR(50) DEFAULT 'star',
        points INT DEFAULT 0,
        is_system_generated TINYINT(1) DEFAULT 0,
        is_public TINYINT(1) DEFAULT 1,
        is_hidden TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_recipient_id (recipient_id),
        INDEX idx_given_by (given_by),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $dbName = defined('DB_NAME') ? DB_NAME : 'hr1_hr1data';
    $hasIsHidden = fetchSingle(
        "SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'social_recognitions' AND COLUMN_NAME = 'is_hidden'",
        [$dbName]
    );
    if (($hasIsHidden['c'] ?? 0) == 0) {
        executeQuery("ALTER TABLE social_recognitions ADD COLUMN is_hidden TINYINT(1) DEFAULT 0 AFTER is_public");
    }
} catch (Exception $e) {
    // Non-fatal: page still shows graceful empty/error states.
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $post_id = intval($_POST['post_id'] ?? 0);
    
    if ($action === 'delete_post' && $post_id > 0) {
        try {
            executeQuery("DELETE FROM social_recognitions WHERE id = ?", [$post_id]);
            echo '<div class="alert alert-success">Recognition post deleted.</div>';
        } catch (Exception $e) {
            echo '<div class="alert alert-error">Error deleting post.</div>';
        }
    }
    
    if ($action === 'hide_post' && $post_id > 0) {
        try {
            executeQuery("UPDATE social_recognitions SET is_hidden = 1, updated_at = NOW() WHERE id = ?", [$post_id]);
            echo '<div class="alert alert-success">Post hidden from recognition wall.</div>';
        } catch (Exception $e) {
            // Fallback if is_hidden column doesn't exist
            try {
                executeQuery("DELETE FROM social_recognitions WHERE id = ?", [$post_id]);
                echo '<div class="alert alert-success">Post removed (is_hidden column may need migration).</div>';
            } catch (Exception $e2) {
                echo '<div class="alert alert-error">Error hiding post.</div>';
            }
        }
    }
    
    if ($action === 'unhide_post' && $post_id > 0) {
        try {
            executeQuery("UPDATE social_recognitions SET is_hidden = 0, updated_at = NOW() WHERE id = ?", [$post_id]);
            echo '<div class="alert alert-success">Post restored to recognition wall.</div>';
        } catch (Exception $e) {
            echo '<div class="alert alert-error">Error restoring post.</div>';
        }
    }
    
    if ($action === 'bulk_delete') {
        $ids = $_POST['selected_ids'] ?? '';
        $id_array = array_filter(array_map('intval', explode(',', $ids)));
        if (!empty($id_array)) {
            try {
                $placeholders = implode(',', array_fill(0, count($id_array), '?'));
                executeQuery("DELETE FROM social_recognitions WHERE id IN ($placeholders)", $id_array);
                echo '<div class="alert alert-success">' . count($id_array) . ' posts deleted.</div>';
            } catch (Exception $e) {
                echo '<div class="alert alert-error">Error performing bulk delete.</div>';
            }
        }
    }
}

// Filters
$filter_type = $_GET['type'] ?? '';
$search = trim($_GET['search'] ?? '');
$filter_date = $_GET['date_range'] ?? '';

$where = [];
$params = [];

if ($search) {
    $where[] = "(sr.message LIKE ? OR sender.first_name LIKE ? OR sender.last_name LIKE ? OR receiver.first_name LIKE ? OR receiver.last_name LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%", "%$search%"]);
}
if ($filter_type) {
    $where[] = "sr.recognition_type = ?";
    $params[] = $filter_type;
}
if ($filter_date === 'today') {
    $where[] = "DATE(sr.created_at) = CURDATE()";
} elseif ($filter_date === 'week') {
    $where[] = "sr.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($filter_date === 'month') {
    $where[] = "sr.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $posts = fetchAll("
        SELECT sr.*,
            sender.first_name as sender_first, sender.last_name as sender_last,
            receiver.first_name as receiver_first, receiver.last_name as receiver_last,
            sd.department_name as sender_dept, rd.department_name as receiver_dept
        FROM social_recognitions sr
        LEFT JOIN user_accounts sender ON sender.id = sr.given_by
        LEFT JOIN user_accounts receiver ON receiver.id = sr.recipient_id
        LEFT JOIN departments sd ON sd.id = sender.department_id
        LEFT JOIN departments rd ON rd.id = receiver.department_id
        $where_sql
        ORDER BY sr.created_at DESC
        LIMIT 100
    ", $params);
} catch (Exception $e) {
    $posts = [];
}

// Stats
try {
    $total_posts = fetchSingle("SELECT COUNT(*) as count FROM social_recognitions")['count'] ?? 0;
    $today_posts = fetchSingle("SELECT COUNT(*) as count FROM social_recognitions WHERE DATE(created_at) = CURDATE()")['count'] ?? 0;
    $week_posts = fetchSingle("SELECT COUNT(*) as count FROM social_recognitions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count'] ?? 0;
} catch (Exception $e) {
    $total_posts = 0; $today_posts = 0; $week_posts = 0;
}

// Top recognized
try {
    $top_recognized = fetchAll("
        SELECT ua.first_name, ua.last_name, d.department_name, COUNT(sr.id) as kudos_count
        FROM social_recognitions sr
        JOIN user_accounts ua ON ua.id = sr.recipient_id
        LEFT JOIN departments d ON d.id = ua.department_id
        WHERE sr.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY sr.recipient_id, ua.first_name, ua.last_name, d.department_name
        ORDER BY kudos_count DESC
        LIMIT 5
    ");
} catch (Exception $e) {
    $top_recognized = [];
}
?>
<div data-page-title="Post Moderation">
<style>
    .mod-header { background:rgba(30,41,54,0.6); border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; }
    .mod-header h1 { font-size:1.5rem; color:#e2e8f0; margin-bottom:0.25rem; }
    .mod-header p { color:#94a3b8; font-size:0.9rem; }
    .mod-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:1rem; margin-bottom:1.5rem; }
    .mod-stat { background:rgba(30,41,54,0.6); border-radius:10px; padding:1rem; border:1px solid rgba(58,69,84,0.5); text-align:center; }
    .mod-stat h3 { font-size:1.4rem; color:#e2e8f0; }
    .mod-stat p { font-size:0.75rem; color:#94a3b8; }
    .mod-grid { display:grid; grid-template-columns:2fr 1fr; gap:1.5rem; margin-bottom:1.5rem; }
    .mod-card { background:rgba(30,41,54,0.6); border-radius:12px; padding:1.5rem; border:1px solid rgba(58,69,84,0.5); margin-bottom:1.5rem; }
    .mod-card h2 { font-size:1.1rem; color:#e2e8f0; margin-bottom:1rem; display:flex; align-items:center; gap:0.5rem; }
    .mod-card h2 i { width:18px; height:18px; }
    .mod-filters { background:rgba(30,41,54,0.6); border-radius:12px; padding:1rem 1.5rem; margin-bottom:1.5rem; display:flex; gap:0.75rem; flex-wrap:wrap; align-items:center; }
    .mod-filters input, .mod-filters select { padding:0.5rem 0.75rem; background:rgba(15,23,42,0.6); border:1px solid rgba(58,69,84,0.5); border-radius:8px; color:#e2e8f0; font-size:0.85rem; }
    .mod-filters input { min-width:200px; }
    .post-item { background:rgba(15,23,42,0.6); border-radius:10px; padding:1rem; margin-bottom:0.75rem; border:1px solid rgba(58,69,84,0.3); position:relative; }
    .post-top { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.5rem; }
    .post-sender { color:#e2e8f0; font-size:0.9rem; font-weight:500; }
    .post-arrow { color:#64748b; font-size:0.8rem; margin:0 0.3rem; }
    .post-receiver { color:#0ea5e9; font-size:0.9rem; font-weight:500; }
    .post-time { color:#64748b; font-size:0.75rem; }
    .post-message { color:#94a3b8; font-size:0.85rem; margin-bottom:0.5rem; padding:0.5rem; background:rgba(15,23,42,0.4); border-radius:6px; border-left:3px solid #8b5cf6; }
    .post-meta { display:flex; gap:0.75rem; font-size:0.75rem; color:#64748b; margin-bottom:0.5rem; }
    .post-actions { display:flex; gap:0.4rem; }
    .act-btn { background:rgba(58,69,84,0.5); border:none; color:#94a3b8; padding:0.3rem 0.6rem; border-radius:4px; cursor:pointer; font-size:0.75rem; transition:all 0.2s; }
    .act-btn:hover { background:rgba(58,69,84,0.8); color:#e2e8f0; }
    .act-btn.danger:hover { background:rgba(239,68,68,0.3); color:#ef4444; }
    .top-item { display:flex; justify-content:space-between; align-items:center; padding:0.6rem 0.75rem; background:rgba(15,23,42,0.6); border-radius:8px; margin-bottom:0.5rem; }
    .top-name { color:#e2e8f0; font-size:0.85rem; }
    .top-dept { color:#64748b; font-size:0.75rem; }
    .top-count { background:rgba(14,165,233,0.2); color:#0ea5e9; padding:0.2rem 0.6rem; border-radius:12px; font-size:0.8rem; font-weight:600; }
    .alert { padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:0.85rem; }
    .alert-success { background:rgba(16,185,129,0.15); color:#10b981; border:1px solid rgba(16,185,129,0.3); }
    .alert-error { background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.3); }
    .empty-state { text-align:center; padding:2rem; color:#64748b; font-size:0.85rem; }
    .type-badge { padding:0.15rem 0.5rem; border-radius:4px; font-size:0.7rem; font-weight:600; }
    .type-kudos { background:rgba(139,92,246,0.2); color:#8b5cf6; }
    .type-welcome { background:rgba(16,185,129,0.2); color:#10b981; }
    .type-default { background:rgba(100,116,139,0.2); color:#94a3b8; }
    @media(max-width:1024px) { .mod-grid { grid-template-columns:1fr; } }
</style>

<div class="mod-header">
    <div>
        <h1><i data-lucide="message-square-warning" style="width:22px;height:22px;display:inline;vertical-align:middle;color:#ef4444;margin-right:0.4rem;"></i>Post Moderation</h1>
        <p>Review, hide, or delete inappropriate recognition posts and comments</p>
    </div>
</div>

<div class="mod-stats">
    <div class="mod-stat"><h3><?php echo number_format($total_posts); ?></h3><p>Total Posts</p></div>
    <div class="mod-stat"><h3 style="color:#10b981;"><?php echo number_format($today_posts); ?></h3><p>Today</p></div>
    <div class="mod-stat"><h3 style="color:#0ea5e9;"><?php echo number_format($week_posts); ?></h3><p>This Week</p></div>
    <div class="mod-stat"><h3 style="color:#8b5cf6;"><?php echo count($posts); ?></h3><p>Showing</p></div>
</div>

<!-- Filters -->
<div class="mod-filters">
    <form method="GET" style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center;width:100%;">
        <input type="hidden" name="page" value="recognition-moderation">
        <input type="text" name="search" placeholder="Search message or name..." value="<?php echo htmlspecialchars($search); ?>">
        <select name="type">
            <option value="">All Types</option>
            <option value="Kudos" <?php echo $filter_type === 'Kudos' ? 'selected' : ''; ?>>Kudos</option>
            <option value="Welcome" <?php echo $filter_type === 'Welcome' ? 'selected' : ''; ?>>Welcome</option>
            <option value="Achievement" <?php echo $filter_type === 'Achievement' ? 'selected' : ''; ?>>Achievement</option>
        </select>
        <select name="date_range">
            <option value="">All Time</option>
            <option value="today" <?php echo $filter_date === 'today' ? 'selected' : ''; ?>>Today</option>
            <option value="week" <?php echo $filter_date === 'week' ? 'selected' : ''; ?>>This Week</option>
            <option value="month" <?php echo $filter_date === 'month' ? 'selected' : ''; ?>>This Month</option>
        </select>
        <button type="submit" class="act-btn" style="padding:0.5rem 1rem;">Filter</button>
    </form>
</div>

<div class="mod-grid">
    <!-- Posts List -->
    <div class="mod-card">
        <h2><i data-lucide="message-square"></i> Recognition Posts (<?php echo count($posts); ?>)</h2>
        <?php if (empty($posts)): ?>
            <div class="empty-state">No recognition posts found.</div>
        <?php else: ?>
            <?php foreach ($posts as $post):
                $type_class = match($post['recognition_type'] ?? '') {
                    'Kudos' => 'type-kudos',
                    'Welcome' => 'type-welcome',
                    default => 'type-default'
                };
            ?>
            <div class="post-item">
                <div class="post-top">
                    <div>
                        <span class="post-sender"><?php echo htmlspecialchars(($post['sender_first'] ?? 'System') . ' ' . ($post['sender_last'] ?? '')); ?></span>
                        <span class="post-arrow">→</span>
                        <span class="post-receiver"><?php echo htmlspecialchars(($post['receiver_first'] ?? '') . ' ' . ($post['receiver_last'] ?? '')); ?></span>
                        <span class="type-badge <?php echo $type_class; ?>" style="margin-left:0.4rem;"><?php echo htmlspecialchars($post['recognition_type'] ?? 'Post'); ?></span>
                    </div>
                    <span class="post-time"><?php echo date('M d, Y g:i A', strtotime($post['created_at'])); ?></span>
                </div>
                <div class="post-message"><?php echo htmlspecialchars($post['message'] ?? ''); ?></div>
                <div class="post-meta">
                    <span>From: <?php echo htmlspecialchars($post['sender_dept'] ?? 'N/A'); ?></span>
                    <span>To: <?php echo htmlspecialchars($post['receiver_dept'] ?? 'N/A'); ?></span>
                    <?php if (!empty($post['points'])): ?>
                    <span style="color:#fbbf24;">+<?php echo $post['points']; ?> pts</span>
                    <?php endif; ?>
                </div>
                <div class="post-actions">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete_post">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <button type="submit" class="act-btn danger"
                            data-confirm-title="Delete Post"
                            data-confirm-variant="danger"
                            data-confirm-ok="Yes, delete"
                            data-confirm-cancel="Cancel"
                            data-confirm="Delete this post permanently?">
                            <i data-lucide="trash-2" style="width:12px;height:12px;display:inline;vertical-align:middle;margin-right:0.2rem;"></i>Delete
                        </button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="hide_post">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <button type="submit" class="act-btn">
                            <i data-lucide="eye-off" style="width:12px;height:12px;display:inline;vertical-align:middle;margin-right:0.2rem;"></i>Hide
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Top Recognized Sidebar -->
    <div>
        <div class="mod-card">
            <h2><i data-lucide="trophy"></i> Top Recognized (30 days)</h2>
            <?php if (empty($top_recognized)): ?>
                <div class="empty-state">No data yet</div>
            <?php else: ?>
                <?php foreach ($top_recognized as $i => $tr): ?>
                <div class="top-item">
                    <div>
                        <div class="top-name"><?php echo ($i + 1) . '. ' . htmlspecialchars($tr['first_name'] . ' ' . $tr['last_name']); ?></div>
                        <div class="top-dept"><?php echo htmlspecialchars($tr['department_name'] ?? 'N/A'); ?></div>
                    </div>
                    <span class="top-count"><?php echo $tr['kudos_count']; ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
</div>
