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
    header('Location: index.php?page=rewards-config');
    exit();
}

require_once '../../database/config.php';

// Ensure rewards/recognition tables exist and contain expected columns
try {
    executeQuery("CREATE TABLE IF NOT EXISTS rewards_catalog (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT NULL,
        points_cost INT NOT NULL DEFAULT 0,
        category VARCHAR(100) DEFAULT 'General',
        quantity_available INT DEFAULT -1,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_rewards_category (category),
        INDEX idx_rewards_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    executeQuery("CREATE TABLE IF NOT EXISTS reward_redemptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        reward_id INT NOT NULL,
        points_spent INT NOT NULL DEFAULT 0,
        status VARCHAR(50) DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_redemptions_user (user_id),
        INDEX idx_redemptions_reward (reward_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    executeQuery("CREATE TABLE IF NOT EXISTS social_recognitions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        recipient_id INT NOT NULL,
        given_by INT NULL,
        recognition_type VARCHAR(50) DEFAULT 'Kudos',
        message TEXT NOT NULL,
        points INT DEFAULT 0,
        is_public TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_social_recipient (recipient_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $dbName = defined('DB_NAME') ? DB_NAME : 'hr1_hr1data';
    $hasPointsCost = fetchSingle("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'rewards_catalog' AND COLUMN_NAME = 'points_cost'", [$dbName]);
    if (($hasPointsCost['c'] ?? 0) == 0) {
        executeQuery("ALTER TABLE rewards_catalog ADD COLUMN points_cost INT NOT NULL DEFAULT 0 AFTER description");
        $hasLegacyPointsRequired = fetchSingle("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'rewards_catalog' AND COLUMN_NAME = 'points_required'", [$dbName]);
        if (($hasLegacyPointsRequired['c'] ?? 0) > 0) {
            executeQuery("UPDATE rewards_catalog SET points_cost = IFNULL(points_required, 0) WHERE points_cost = 0");
        }
    }

    $hasQuantity = fetchSingle("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'rewards_catalog' AND COLUMN_NAME = 'quantity_available'", [$dbName]);
    if (($hasQuantity['c'] ?? 0) == 0) {
        executeQuery("ALTER TABLE rewards_catalog ADD COLUMN quantity_available INT DEFAULT -1 AFTER category");
    }

    $hasRewardUpdatedAt = fetchSingle("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'rewards_catalog' AND COLUMN_NAME = 'updated_at'", [$dbName]);
    if (($hasRewardUpdatedAt['c'] ?? 0) == 0) {
        executeQuery("ALTER TABLE rewards_catalog ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
    }

    $hasRedemptionUser = fetchSingle("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'reward_redemptions' AND COLUMN_NAME = 'user_id'", [$dbName]);
    if (($hasRedemptionUser['c'] ?? 0) == 0) {
        executeQuery("ALTER TABLE reward_redemptions ADD COLUMN user_id INT NULL AFTER id");
    }

    $hasRedemptionCreatedAt = fetchSingle("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'reward_redemptions' AND COLUMN_NAME = 'created_at'", [$dbName]);
    if (($hasRedemptionCreatedAt['c'] ?? 0) == 0) {
        executeQuery("ALTER TABLE reward_redemptions ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status");
    }

    $hasRecipientId = fetchSingle("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'social_recognitions' AND COLUMN_NAME = 'recipient_id'", [$dbName]);
    if (($hasRecipientId['c'] ?? 0) == 0) {
        executeQuery("ALTER TABLE social_recognitions ADD COLUMN recipient_id INT NULL AFTER id");
        $hasReceiverId = fetchSingle("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'social_recognitions' AND COLUMN_NAME = 'receiver_id'", [$dbName]);
        if (($hasReceiverId['c'] ?? 0) > 0) {
            executeQuery("UPDATE social_recognitions SET recipient_id = receiver_id WHERE recipient_id IS NULL");
        }
    }

    $hasPoints = fetchSingle("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'social_recognitions' AND COLUMN_NAME = 'points'", [$dbName]);
    if (($hasPoints['c'] ?? 0) == 0) {
        executeQuery("ALTER TABLE social_recognitions ADD COLUMN points INT DEFAULT 0 AFTER message");
    }
} catch (Exception $e) {
    // Non-fatal defensive setup; page-level actions already handle query failures.
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_reward') {
        $name = trim($_POST['reward_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $points_cost = intval($_POST['points_cost'] ?? 0);
        $category = trim($_POST['category'] ?? 'General');
        $quantity = intval($_POST['quantity'] ?? -1);
        
        if (empty($name) || $points_cost <= 0) {
            echo '<div class="alert alert-error">Reward name and points cost are required.</div>';
        } else {
            try {
                executeQuery("INSERT INTO rewards_catalog (name, description, points_cost, category, quantity_available, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())",
                    [$name, $description, $points_cost, $category, $quantity]);
                echo '<div class="alert alert-success">Reward "' . htmlspecialchars($name) . '" added.</div>';
            } catch (Exception $e) {
                echo '<div class="alert alert-error">Error adding reward. Table may need migration.</div>';
            }
        }
    }
    
    if ($action === 'update_reward') {
        $reward_id = intval($_POST['reward_id'] ?? 0);
        $name = trim($_POST['reward_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $points_cost = intval($_POST['points_cost'] ?? 0);
        $category = trim($_POST['category'] ?? 'General');
        $quantity = intval($_POST['quantity'] ?? -1);
        
        if ($reward_id > 0 && !empty($name)) {
            try {
                executeQuery("UPDATE rewards_catalog SET name = ?, description = ?, points_cost = ?, category = ?, quantity_available = ?, updated_at = NOW() WHERE id = ?",
                    [$name, $description, $points_cost, $category, $quantity, $reward_id]);
                echo '<div class="alert alert-success">Reward updated.</div>';
            } catch (Exception $e) {
                echo '<div class="alert alert-error">Error updating reward.</div>';
            }
        }
    }
    
    if ($action === 'toggle_reward') {
        $reward_id = intval($_POST['reward_id'] ?? 0);
        if ($reward_id > 0) {
            try {
                executeQuery("UPDATE rewards_catalog SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?", [$reward_id]);
                echo '<div class="alert alert-success">Reward status toggled.</div>';
            } catch (Exception $e) {
                echo '<div class="alert alert-error">Error toggling reward.</div>';
            }
        }
    }
    
    if ($action === 'delete_reward') {
        $reward_id = intval($_POST['reward_id'] ?? 0);
        if ($reward_id > 0) {
            try {
                executeQuery("DELETE FROM rewards_catalog WHERE id = ?", [$reward_id]);
                echo '<div class="alert alert-success">Reward deleted.</div>';
            } catch (Exception $e) {
                echo '<div class="alert alert-error">Error deleting reward.</div>';
            }
        }
    }
    
    if ($action === 'update_points_config') {
        $kudos_points = intval($_POST['kudos_points'] ?? 10);
        $welcome_points = intval($_POST['welcome_points'] ?? 5);
        $achievement_points = intval($_POST['achievement_points'] ?? 25);
        
        try {
            $configs = [
                ['kudos_points_value', $kudos_points],
                ['welcome_points_value', $welcome_points],
                ['achievement_points_value', $achievement_points],
            ];
            foreach ($configs as $cfg) {
                executeQuery("INSERT INTO system_settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()",
                    [$cfg[0], $cfg[1], $cfg[1]]);
            }
            echo '<div class="alert alert-success">Points configuration updated.</div>';
        } catch (Exception $e) {
            echo '<div class="alert alert-error">Error updating points config.</div>';
        }
    }
}

// Fetch rewards catalog
try {
    $rewards = fetchAll("SELECT * FROM rewards_catalog ORDER BY category, points_cost ASC");
} catch (Exception $e) {
    $rewards = [];
}

// Fetch redemption history
try {
    $redemptions = fetchAll("
        SELECT rr.*, ua.first_name, ua.last_name, rc.name as reward_name, rc.points_cost
        FROM reward_redemptions rr
        LEFT JOIN user_accounts ua ON ua.id = rr.user_id
        LEFT JOIN rewards_catalog rc ON rc.id = rr.reward_id
        ORDER BY rr.created_at DESC
        LIMIT 20
    ");
} catch (Exception $e) {
    $redemptions = [];
}

// Fetch points config from system_settings
try {
    $kudos_pts = fetchSingle("SELECT setting_value FROM system_settings WHERE setting_key = 'kudos_points_value'")['setting_value'] ?? 10;
    $welcome_pts = fetchSingle("SELECT setting_value FROM system_settings WHERE setting_key = 'welcome_points_value'")['setting_value'] ?? 5;
    $achievement_pts = fetchSingle("SELECT setting_value FROM system_settings WHERE setting_key = 'achievement_points_value'")['setting_value'] ?? 25;
} catch (Exception $e) {
    $kudos_pts = 10; $welcome_pts = 5; $achievement_pts = 25;
}

// Top earners
try {
    $top_earners = fetchAll("
        SELECT ua.first_name, ua.last_name, d.department_name,
            COALESCE(SUM(sr.points), 0) as total_points
        FROM social_recognitions sr
        JOIN user_accounts ua ON ua.id = sr.recipient_id
        LEFT JOIN departments d ON d.id = ua.department_id
        GROUP BY sr.recipient_id, ua.first_name, ua.last_name, d.department_name
        ORDER BY total_points DESC
        LIMIT 10
    ");
} catch (Exception $e) {
    $top_earners = [];
}

$reward_categories = ['General', 'Gift Cards', 'Company Merch', 'Time Off', 'Experience', 'Donation'];
?>
<div data-page-title="Rewards Configuration">
<style>
    .rw-header { background:rgba(30,41,54,0.6); border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; }
    .rw-header h1 { font-size:1.5rem; color:#e2e8f0; margin-bottom:0.25rem; }
    .rw-header p { color:#94a3b8; font-size:0.9rem; }
    .rw-grid { display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem; }
    .rw-card { background:rgba(30,41,54,0.6); border-radius:12px; padding:1.5rem; border:1px solid rgba(58,69,84,0.5); margin-bottom:1.5rem; }
    .rw-card h2 { font-size:1.1rem; color:#e2e8f0; margin-bottom:1rem; display:flex; align-items:center; gap:0.5rem; }
    .rw-card h2 i { width:18px; height:18px; }
    .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem; }
    .form-group { margin-bottom:0.75rem; }
    .form-group label { display:block; color:#94a3b8; font-size:0.85rem; margin-bottom:0.3rem; }
    .form-group input, .form-group select, .form-group textarea { width:100%; padding:0.5rem 0.75rem; background:rgba(15,23,42,0.6); border:1px solid rgba(58,69,84,0.5); border-radius:8px; color:#e2e8f0; font-size:0.85rem; }
    .form-group textarea { min-height:60px; resize:vertical; }
    .btn-primary { background:#ef4444; color:#fff; border:none; padding:0.5rem 1rem; border-radius:8px; cursor:pointer; font-size:0.85rem; font-weight:500; }
    .btn-primary:hover { background:#dc2626; }
    .btn-secondary { background:rgba(58,69,84,0.5); color:#e2e8f0; border:none; padding:0.5rem 1rem; border-radius:8px; cursor:pointer; font-size:0.85rem; }
    .reward-item { background:rgba(15,23,42,0.6); border-radius:10px; padding:1rem; margin-bottom:0.75rem; border:1px solid rgba(58,69,84,0.3); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem; }
    .reward-info { flex:1; min-width:200px; }
    .reward-name { color:#e2e8f0; font-size:0.95rem; font-weight:600; }
    .reward-desc { color:#94a3b8; font-size:0.8rem; margin-top:0.2rem; }
    .reward-badges { display:flex; gap:0.4rem; margin-top:0.4rem; flex-wrap:wrap; }
    .reward-points { background:rgba(251,191,36,0.2); color:#fbbf24; padding:0.3rem 0.75rem; border-radius:8px; font-size:0.9rem; font-weight:700; white-space:nowrap; }
    .badge-sm { padding:0.15rem 0.5rem; border-radius:4px; font-size:0.7rem; font-weight:600; }
    .badge-active { background:rgba(16,185,129,0.2); color:#10b981; }
    .badge-inactive { background:rgba(239,68,68,0.2); color:#ef4444; }
    .badge-cat { background:rgba(139,92,246,0.2); color:#8b5cf6; }
    .badge-qty { background:rgba(14,165,233,0.2); color:#0ea5e9; }
    .reward-actions { display:flex; gap:0.4rem; }
    .act-btn { background:rgba(58,69,84,0.5); border:none; color:#94a3b8; padding:0.3rem 0.6rem; border-radius:4px; cursor:pointer; font-size:0.75rem; transition:all 0.2s; }
    .act-btn:hover { background:rgba(58,69,84,0.8); color:#e2e8f0; }
    .act-btn.danger:hover { background:rgba(239,68,68,0.3); color:#ef4444; }
    .pts-config { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; }
    .pts-box { background:rgba(15,23,42,0.6); border-radius:10px; padding:1rem; text-align:center; border:1px solid rgba(58,69,84,0.3); }
    .pts-box label { display:block; color:#94a3b8; font-size:0.8rem; margin-bottom:0.5rem; }
    .pts-box input { width:80px; text-align:center; padding:0.5rem; background:rgba(15,23,42,0.8); border:1px solid rgba(58,69,84,0.5); border-radius:8px; color:#fbbf24; font-size:1.25rem; font-weight:700; }
    .pts-box .pts-icon { font-size:1.5rem; margin-bottom:0.25rem; }
    .earner-row { display:flex; justify-content:space-between; align-items:center; padding:0.6rem 0.75rem; background:rgba(15,23,42,0.6); border-radius:8px; margin-bottom:0.5rem; }
    .earner-name { color:#e2e8f0; font-size:0.85rem; }
    .earner-dept { color:#64748b; font-size:0.75rem; }
    .earner-pts { color:#fbbf24; font-size:0.85rem; font-weight:600; }
    .redeem-item { display:flex; justify-content:space-between; align-items:center; padding:0.6rem 0.75rem; background:rgba(15,23,42,0.6); border-radius:8px; margin-bottom:0.5rem; font-size:0.8rem; }
    .redeem-item .redeem-user { color:#e2e8f0; }
    .redeem-item .redeem-reward { color:#94a3b8; }
    .redeem-item .redeem-pts { color:#fbbf24; font-weight:600; }
    .redeem-item .redeem-date { color:#64748b; font-size:0.75rem; }
    .alert { padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:0.85rem; }
    .alert-success { background:rgba(16,185,129,0.15); color:#10b981; border:1px solid rgba(16,185,129,0.3); }
    .alert-error { background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.3); }
    .empty-state { text-align:center; padding:2rem; color:#64748b; font-size:0.85rem; }
    @media(max-width:1024px) { .rw-grid { grid-template-columns:1fr; } .pts-config { grid-template-columns:1fr; } }
    @media(max-width:768px) { .form-grid { grid-template-columns:1fr; } }
</style>

<div class="rw-header">
    <div>
        <h1><i data-lucide="gift" style="width:22px;height:22px;display:inline;vertical-align:middle;color:#ef4444;margin-right:0.4rem;"></i>Rewards Configuration</h1>
        <p>Configure points system, manage reward catalog, and track redemptions</p>
    </div>
    <button class="btn-primary" onclick="document.getElementById('add-reward-form').style.display = document.getElementById('add-reward-form').style.display === 'none' ? 'block' : 'none';">
        <i data-lucide="plus" style="width:14px;height:14px;display:inline;vertical-align:middle;margin-right:0.3rem;"></i>Add Reward
    </button>
</div>

<!-- Points Configuration -->
<div class="rw-card">
    <h2><i data-lucide="settings"></i> Points Configuration</h2>
    <p style="color:#94a3b8;font-size:0.8rem;margin-bottom:1rem;">Set how many points are awarded for each recognition type.</p>
    <form method="POST">
        <input type="hidden" name="action" value="update_points_config">
        <div class="pts-config">
            <div class="pts-box">
                <div class="pts-icon">🤝</div>
                <label>Kudos Points</label>
                <input type="number" name="kudos_points" value="<?php echo intval($kudos_pts); ?>" min="1" max="1000">
            </div>
            <div class="pts-box">
                <div class="pts-icon">👋</div>
                <label>Welcome Post Points</label>
                <input type="number" name="welcome_points" value="<?php echo intval($welcome_pts); ?>" min="1" max="1000">
            </div>
            <div class="pts-box">
                <div class="pts-icon">🏆</div>
                <label>Achievement Points</label>
                <input type="number" name="achievement_points" value="<?php echo intval($achievement_pts); ?>" min="1" max="1000">
            </div>
        </div>
        <div style="text-align:center;margin-top:1rem;">
            <button type="submit" class="btn-primary">Save Points Config</button>
        </div>
    </form>
</div>

<!-- Add Reward Form -->
<div class="rw-card" id="add-reward-form" style="display:none;">
    <h2><i data-lucide="plus-circle"></i> Add New Reward</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add_reward">
        <div class="form-grid">
            <div class="form-group">
                <label>Reward Name</label>
                <input type="text" name="reward_name" placeholder="e.g. Coffee Gift Card" required>
            </div>
            <div class="form-group">
                <label>Points Cost</label>
                <input type="number" name="points_cost" placeholder="100" min="1" required>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category">
                    <?php foreach ($reward_categories as $cat): ?>
                    <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Quantity (-1 = unlimited)</label>
                <input type="number" name="quantity" value="-1" min="-1">
            </div>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" placeholder="Describe the reward..."></textarea>
        </div>
        <div style="display:flex;gap:0.75rem;">
            <button type="submit" class="btn-primary">Add Reward</button>
            <button type="button" class="btn-secondary" onclick="document.getElementById('add-reward-form').style.display='none';">Cancel</button>
        </div>
    </form>
</div>

<!-- Rewards Catalog -->
<div class="rw-card">
    <h2><i data-lucide="shopping-bag"></i> Rewards Catalog (<?php echo count($rewards); ?>)</h2>
    <?php if (empty($rewards)): ?>
        <div class="empty-state">
            <i data-lucide="gift" style="width:40px;height:40px;margin-bottom:0.5rem;color:#64748b;"></i>
            <p>No rewards configured yet. Add your first reward above.</p>
        </div>
    <?php else: ?>
        <?php
        $current_cat = '';
        foreach ($rewards as $rw):
            $cat = $rw['category'] ?? 'General';
            if ($cat !== $current_cat):
                $current_cat = $cat;
        ?>
            <div style="font-size:0.8rem;color:#94a3b8;margin:1rem 0 0.5rem;text-transform:uppercase;letter-spacing:0.05em;"><?php echo htmlspecialchars($current_cat); ?></div>
        <?php endif; ?>
        <div class="reward-item">
            <div class="reward-info">
                <div class="reward-name"><?php echo htmlspecialchars($rw['name']); ?></div>
                <?php if (!empty($rw['description'])): ?>
                <div class="reward-desc"><?php echo htmlspecialchars($rw['description']); ?></div>
                <?php endif; ?>
                <div class="reward-badges">
                    <span class="badge-sm badge-cat"><?php echo htmlspecialchars($cat); ?></span>
                    <?php if (!empty($rw['is_active'])): ?>
                        <span class="badge-sm badge-active">Active</span>
                    <?php else: ?>
                        <span class="badge-sm badge-inactive">Inactive</span>
                    <?php endif; ?>
                    <?php if (isset($rw['quantity_available']) && $rw['quantity_available'] >= 0): ?>
                        <span class="badge-sm badge-qty">Qty: <?php echo $rw['quantity_available']; ?></span>
                    <?php else: ?>
                        <span class="badge-sm badge-qty">Unlimited</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="reward-points"><?php echo number_format($rw['points_cost']); ?> pts</div>
            <div class="reward-actions">
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="toggle_reward">
                    <input type="hidden" name="reward_id" value="<?php echo $rw['id']; ?>">
                    <button type="submit" class="act-btn"><?php echo $rw['is_active'] ? 'Disable' : 'Enable'; ?></button>
                </form>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="delete_reward">
                    <input type="hidden" name="reward_id" value="<?php echo $rw['id']; ?>">
                    <button type="submit" class="act-btn danger"
                        data-confirm-title="Delete Reward"
                        data-confirm-variant="danger"
                        data-confirm-ok="Yes, delete"
                        data-confirm-cancel="Cancel"
                        data-confirm="Delete this reward?">
                        Delete
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Bottom Grid: Top Earners + Redemption History -->
<div class="rw-grid">
    <div class="rw-card">
        <h2><i data-lucide="trophy"></i> Top Points Earners</h2>
        <?php if (empty($top_earners)): ?>
            <div class="empty-state">No points data yet</div>
        <?php else: ?>
            <?php foreach ($top_earners as $i => $te): ?>
            <div class="earner-row">
                <div>
                    <div class="earner-name"><?php echo ($i + 1) . '. ' . htmlspecialchars($te['first_name'] . ' ' . $te['last_name']); ?></div>
                    <div class="earner-dept"><?php echo htmlspecialchars($te['department_name'] ?? 'N/A'); ?></div>
                </div>
                <span class="earner-pts"><?php echo number_format($te['total_points']); ?> pts</span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="rw-card">
        <h2><i data-lucide="receipt"></i> Recent Redemptions</h2>
        <?php if (empty($redemptions)): ?>
            <div class="empty-state">No redemptions yet</div>
        <?php else: ?>
            <?php foreach ($redemptions as $rd): ?>
            <div class="redeem-item">
                <span class="redeem-user"><?php echo htmlspecialchars(($rd['first_name'] ?? '') . ' ' . ($rd['last_name'] ?? '')); ?></span>
                <span class="redeem-reward"><?php echo htmlspecialchars($rd['reward_name'] ?? 'N/A'); ?></span>
                <span class="redeem-pts">-<?php echo number_format($rd['points_cost'] ?? 0); ?></span>
                <span class="redeem-date"><?php echo date('M d', strtotime($rd['created_at'])); ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
</div>
