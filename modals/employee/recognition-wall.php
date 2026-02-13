<?php
require_once '../../includes/session_helper.php';
require_once '../../includes/spa_helper.php';
startSecureSession();
$is_ajax = is_spa_ajax();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Employee') {
    header('Location: ../../index.php'); exit();
}
if (!$is_ajax) { header('Location: index.php?page=recognition-wall'); exit(); }

require_once '../../database/config.php';
$userId = $_SESSION['user_id'];

$successMsg = null;
$errorMsg = null;

// Handle give kudos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['give_kudos'])) {
    $recipientId = intval($_POST['recipient_id']);
    $message = trim($_POST['message'] ?? '');
    $badge = $_POST['badge_icon'] ?? 'star';

    if ($recipientId && $message && $recipientId !== $userId) {
        try {
            insertRecord(
                "INSERT INTO social_recognitions (recipient_id, given_by, recognition_type, message, badge_icon, is_system_generated, is_public) VALUES (?, ?, 'Kudos', ?, ?, 0, 1)",
                [$recipientId, $userId, $message, $badge]
            );
            $successMsg = "Kudos sent!";
        } catch (Exception $e) { $errorMsg = "Failed: " . $e->getMessage(); }
    } else {
        $errorMsg = $recipientId === $userId ? "You can't give kudos to yourself." : "Please select a colleague and write a message.";
    }
}

// Fetch all public recognitions
try {
    $recognitions = fetchAll("
        SELECT sr.*, 
               r.first_name as r_first, r.last_name as r_last,
               g.first_name as g_first, g.last_name as g_last
        FROM social_recognitions sr
        LEFT JOIN user_accounts r ON sr.recipient_id = r.id
        LEFT JOIN user_accounts g ON sr.given_by = g.id
        WHERE sr.is_public = 1
        ORDER BY sr.created_at DESC
        LIMIT 50
    ", []);
} catch (Exception $e) { $recognitions = []; }

// Fetch colleagues for kudos dropdown
try {
    $colleagues = fetchAll("
        SELECT ua.id, ua.first_name, ua.last_name, d.department_name
        FROM user_accounts ua
        LEFT JOIN departments d ON ua.department_id = d.id
        LEFT JOIN roles r ON ua.role_id = r.id
        WHERE ua.id != ? AND r.role_type = 'Employee' AND ua.status = 'Active'
        ORDER BY ua.first_name
    ", [$userId]);
} catch (Exception $e) { $colleagues = []; }

// Count my kudos received
$myKudos = count(array_filter($recognitions, fn($r) => $r['recipient_id'] == $userId));
$myGiven = count(array_filter($recognitions, fn($r) => $r['given_by'] == $userId));

$badgeIcons = [
    'star' => '&#11088;',
    'heart' => '&#10084;&#65039;',
    'fire' => '&#128293;',
    'trophy' => '&#127942;',
    'clap' => '&#128079;',
    'rocket' => '&#128640;',
    'sparkles' => '&#10024;',
    'thumbs-up' => '&#128077;'
];
?>
<div data-page-title="Recognition Wall">
<style>
    .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
    .header h1 { font-size: 1.35rem; color: #e2e8f0; margin-bottom: 0.25rem; }
    .header p { color: #94a3b8; font-size: 0.85rem; }
    .btn-primary { padding: 0.6rem 1.2rem; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; transition: all 0.3s; }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3); }
    .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    .stat-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.25rem; border: 1px solid rgba(58, 69, 84, 0.5); display: flex; align-items: center; gap: 1rem; }
    .stat-card .icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .stat-card.received .icon { background: rgba(245, 158, 11, 0.2); }
    .stat-card.given .icon { background: rgba(16, 185, 129, 0.2); }
    .stat-card.total .icon { background: rgba(139, 92, 246, 0.2); }
    .stat-card h3 { font-size: 1.5rem; color: #e2e8f0; }
    .stat-card p { font-size: 0.8rem; color: #94a3b8; }
    .kudos-form { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1.5rem; display: none; }
    .kudos-form.show { display: block; }
    .kudos-form h3 { color: #e2e8f0; font-size: 1.1rem; margin-bottom: 1rem; }
    .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
    .form-group { display: flex; flex-direction: column; gap: 0.3rem; }
    .form-group.full { grid-column: 1 / -1; }
    .form-group label { color: #94a3b8; font-size: 0.8rem; font-weight: 500; }
    .form-group select, .form-group textarea { padding: 0.6rem; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(58, 69, 84, 0.5); border-radius: 8px; color: #e2e8f0; font-size: 0.85rem; }
    .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #f59e0b; }
    .form-group textarea { resize: vertical; min-height: 80px; }
    .badge-selector { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .badge-option { width: 40px; height: 40px; border-radius: 8px; border: 2px solid rgba(58, 69, 84, 0.5); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; cursor: pointer; transition: all 0.2s; }
    .badge-option:hover, .badge-option.selected { border-color: #f59e0b; background: rgba(245, 158, 11, 0.1); }
    .badge-option input { display: none; }
    .form-actions { display: flex; gap: 0.5rem; margin-top: 1rem; }
    .btn-secondary { padding: 0.6rem 1.2rem; background: rgba(107, 114, 128, 0.2); color: #9ca3af; border: 1px solid rgba(107, 114, 128, 0.3); border-radius: 8px; font-size: 0.85rem; cursor: pointer; }
    .recognition-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.25rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1rem; }
    .recognition-card.welcome { border-left: 3px solid #10b981; background: rgba(16, 185, 129, 0.05); }
    .recognition-card.kudos { border-left: 3px solid #f59e0b; }
    .recognition-card.achievement { border-left: 3px solid #a78bfa; }
    .recognition-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; }
    .recognition-badge { width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .recognition-card.welcome .recognition-badge { background: rgba(16, 185, 129, 0.2); }
    .recognition-card.kudos .recognition-badge { background: rgba(245, 158, 11, 0.2); }
    .recognition-card.achievement .recognition-badge { background: rgba(139, 92, 246, 0.2); }
    .recognition-info .name { font-weight: 600; color: #e2e8f0; font-size: 0.95rem; }
    .recognition-info .type { font-size: 0.75rem; color: #94a3b8; }
    .recognition-message { color: #cbd5e1; font-size: 0.9rem; line-height: 1.6; margin-bottom: 0.5rem; }
    .recognition-footer { display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: #64748b; }
    .recognition-from { color: #94a3b8; }
    .alert { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; }
    .alert-success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .alert-error { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
    .empty-state { text-align: center; padding: 4rem 2rem; color: #94a3b8; }
    .empty-state .big-emoji { font-size: 4rem; margin-bottom: 1rem; }
    @media (max-width: 768px) { .stats-row { grid-template-columns: 1fr; } .form-grid { grid-template-columns: 1fr; } }
</style>

    <div class="header">
        <div>
            <h1>&#127942; Recognition Wall</h1>
            <p>Celebrate achievements and give kudos to your teammates</p>
        </div>
        <button class="btn-primary" onclick="document.getElementById('kudosForm').classList.toggle('show')">
            <i data-lucide="heart" style="width:16px;height:16px;"></i> Give Kudos
        </button>
    </div>

    <?php if ($successMsg): ?><div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div><?php endif; ?>
    <?php if ($errorMsg): ?><div class="alert alert-error"><?php echo htmlspecialchars($errorMsg); ?></div><?php endif; ?>

    <div class="stats-row">
        <div class="stat-card received">
            <div class="icon">&#11088;</div>
            <div><h3><?php echo $myKudos; ?></h3><p>Kudos Received</p></div>
        </div>
        <div class="stat-card given">
            <div class="icon">&#128077;</div>
            <div><h3><?php echo $myGiven; ?></h3><p>Kudos Given</p></div>
        </div>
        <div class="stat-card total">
            <div class="icon">&#127881;</div>
            <div><h3><?php echo count($recognitions); ?></h3><p>Total Posts</p></div>
        </div>
    </div>

    <div id="kudosForm" class="kudos-form">
        <h3>&#128079; Give Kudos to a Colleague</h3>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Who deserves recognition? *</label>
                    <select name="recipient_id" required>
                        <option value="">Select colleague...</option>
                        <?php foreach ($colleagues as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name'] . ' (' . ($c['department_name'] ?? 'N/A') . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Badge</label>
                    <div class="badge-selector">
                        <?php foreach ($badgeIcons as $key => $emoji): ?>
                        <label class="badge-option" onclick="this.querySelector('input').checked=true; document.querySelectorAll('.badge-option').forEach(b=>b.classList.remove('selected')); this.classList.add('selected');">
                            <input type="radio" name="badge_icon" value="<?php echo $key; ?>" <?php echo $key === 'star' ? 'checked' : ''; ?>>
                            <?php echo $emoji; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-group full">
                    <label>Message *</label>
                    <textarea name="message" required placeholder="e.g., Great job on your first week! Your positive attitude really makes a difference..."></textarea>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="give_kudos" class="btn-primary">&#128640; Send Kudos</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('kudosForm').classList.remove('show')">Cancel</button>
            </div>
        </form>
    </div>

    <?php if (empty($recognitions)): ?>
        <div class="empty-state">
            <div class="big-emoji">&#127881;</div>
            <h3 style="color: #e2e8f0; margin-bottom: 0.5rem;">No Recognitions Yet</h3>
            <p>Be the first to give kudos to a colleague!</p>
        </div>
    <?php else: ?>
        <?php foreach ($recognitions as $rec): ?>
        <div class="recognition-card <?php echo strtolower($rec['recognition_type']); ?>">
            <div class="recognition-header">
                <div class="recognition-badge">
                    <?php echo $badgeIcons[$rec['badge_icon']] ?? '&#11088;'; ?>
                </div>
                <div class="recognition-info">
                    <div class="name"><?php echo htmlspecialchars(($rec['r_first'] ?? '') . ' ' . ($rec['r_last'] ?? '')); ?></div>
                    <div class="type"><?php echo $rec['recognition_type']; ?> <?php echo $rec['is_system_generated'] ? '(System)' : ''; ?></div>
                </div>
            </div>
            <div class="recognition-message"><?php echo nl2br(htmlspecialchars($rec['message'])); ?></div>
            <div class="recognition-footer">
                <?php if (!$rec['is_system_generated'] && !empty($rec['g_first'])): ?>
                <span class="recognition-from">From: <?php echo htmlspecialchars($rec['g_first'] . ' ' . $rec['g_last']); ?></span>
                <?php else: ?>
                <span class="recognition-from">System</span>
                <?php endif; ?>
                <span><?php echo date('M d, Y h:i A', strtotime($rec['created_at'])); ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

<script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
</div>
