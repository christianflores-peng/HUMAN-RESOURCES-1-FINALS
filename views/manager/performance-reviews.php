<?php
require_once '../../includes/session_helper.php';
require_once '../../includes/rbac_helper.php';
require_once '../../includes/spa_helper.php';
startSecureSession();
$is_ajax = is_spa_ajax();

if (!isset($_SESSION['user_id'])) { header('Location: ../../index.php'); exit(); }
$userId = $_SESSION['user_id'];
$user = getUserWithRole($userId);
if (!$user || !in_array($user['role_type'] ?? '', ['Manager', 'Admin', 'HR_Staff'])) {
    header('Location: ../../index.php'); exit();
}
if (!$is_ajax) { header('Location: index.php?page=performance-reviews'); exit(); }

require_once '../../database/config.php';

$successMsg = null;
$errorMsg = null;

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $empId = intval($_POST['employee_id']);
    $reviewType = $_POST['review_type'] ?? 'Probation';
    $safety = intval($_POST['safety_score'] ?? 0);
    $punctuality = intval($_POST['punctuality_score'] ?? 0);
    $quality = intval($_POST['quality_score'] ?? 0);
    $teamwork = intval($_POST['teamwork_score'] ?? 0);
    $overall = round(($safety + $punctuality + $quality + $teamwork) / 4, 1);
    $recommendation = $_POST['recommendation'] ?? 'Regularize';
    $comments = trim($_POST['comments'] ?? '');

    if ($empId && $safety && $punctuality && $quality && $teamwork) {
        try {
            insertRecord(
                "INSERT INTO performance_reviews (employee_id, reviewer_id, review_type, safety_score, punctuality_score, quality_score, teamwork_score, overall_score, recommendation, comments, status, review_period_start, review_period_end) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Submitted', DATE_SUB(NOW(), INTERVAL 3 MONTH), NOW())",
                [$empId, $userId, $reviewType, $safety, $punctuality, $quality, $teamwork, $overall, $recommendation, $comments]
            );
            logAuditAction($userId, 'CREATE', 'performance_reviews', null, null, null, "Submitted {$reviewType} review for employee #{$empId}");
            $successMsg = "Performance review submitted!";
        } catch (Exception $e) { $errorMsg = "Failed: " . $e->getMessage(); }
    } else {
        $errorMsg = "Please fill in all score fields.";
    }
}

// Fetch team members (employees in manager's department)
try {
    $teamMembers = fetchAll("
        SELECT ua.id, ua.first_name, ua.last_name, ua.employee_id, ua.created_at,
               d.department_name, r.role_name
        FROM user_accounts ua
        LEFT JOIN departments d ON ua.department_id = d.id
        LEFT JOIN roles r ON ua.role_id = r.id
        WHERE ua.department_id = ? AND ua.id != ? AND r.role_type = 'Employee'
        ORDER BY ua.first_name
    ", [$user['department_id'] ?? 0, $userId]);
} catch (Exception $e) { $teamMembers = []; }

// Fetch existing reviews
try {
    $reviews = fetchAll("
        SELECT pr.*, ua.first_name, ua.last_name, ua.employee_id as emp_code,
               d.department_name
        FROM performance_reviews pr
        LEFT JOIN user_accounts ua ON pr.employee_id = ua.id
        LEFT JOIN departments d ON ua.department_id = d.id
        WHERE pr.reviewer_id = ?
        ORDER BY pr.created_at DESC
    ", [$userId]);
} catch (Exception $e) { $reviews = []; }

$pendingReviews = count(array_filter($reviews, fn($r) => $r['status'] === 'Draft'));
$submittedReviews = count(array_filter($reviews, fn($r) => $r['status'] === 'Submitted'));
?>
<div data-page-title="Performance Reviews">
<style>
    .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
    .header h1 { font-size: 1.35rem; color: #e2e8f0; margin-bottom: 0.25rem; }
    .header p { color: #94a3b8; font-size: 0.85rem; }
    .btn-primary { padding: 0.6rem 1.2rem; background: linear-gradient(135deg, #0ea5e9, #0284c7); color: white; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; transition: all 0.3s; }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3); }
    .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    .stat-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.25rem; border: 1px solid rgba(58, 69, 84, 0.5); display: flex; align-items: center; gap: 1rem; }
    .stat-card .icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
    .stat-card.team .icon { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
    .stat-card.pending .icon { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
    .stat-card.done .icon { background: rgba(16, 185, 129, 0.2); color: #10b981; }
    .stat-card h3 { font-size: 1.5rem; color: #e2e8f0; }
    .stat-card p { font-size: 0.8rem; color: #94a3b8; }
    .review-form { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1.5rem; display: none; }
    .review-form.show { display: block; }
    .review-form h3 { color: #e2e8f0; font-size: 1.1rem; margin-bottom: 1rem; }
    .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
    .form-group { display: flex; flex-direction: column; gap: 0.3rem; }
    .form-group.full { grid-column: 1 / -1; }
    .form-group label { color: #94a3b8; font-size: 0.8rem; font-weight: 500; }
    .form-group input, .form-group select, .form-group textarea { padding: 0.6rem; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(58, 69, 84, 0.5); border-radius: 8px; color: #e2e8f0; font-size: 0.85rem; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #0ea5e9; }
    .form-group textarea { resize: vertical; min-height: 80px; }
    .form-group input[type="range"] { accent-color: #0ea5e9; }
    .score-display { display: flex; justify-content: space-between; font-size: 0.75rem; color: #64748b; }
    .form-actions { display: flex; gap: 0.5rem; margin-top: 1rem; }
    .btn-secondary { padding: 0.6rem 1.2rem; background: rgba(107, 114, 128, 0.2); color: #9ca3af; border: 1px solid rgba(107, 114, 128, 0.3); border-radius: 8px; font-size: 0.85rem; cursor: pointer; }
    .review-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1rem; }
    .review-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
    .review-name { font-size: 1.1rem; color: #e2e8f0; font-weight: 600; }
    .review-meta { color: #94a3b8; font-size: 0.8rem; }
    .scores-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1rem; }
    .score-item { text-align: center; padding: 0.75rem; background: rgba(15, 23, 42, 0.4); border-radius: 8px; }
    .score-item .score-value { font-size: 1.25rem; font-weight: 700; color: #e2e8f0; }
    .score-item .score-label { font-size: 0.7rem; color: #94a3b8; margin-top: 0.2rem; }
    .overall-score { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: rgba(14, 165, 233, 0.1); border-radius: 8px; margin-bottom: 0.75rem; }
    .overall-score .big-score { font-size: 2rem; font-weight: 700; color: #0ea5e9; }
    .overall-score .score-info { font-size: 0.85rem; color: #94a3b8; }
    .recommendation-badge { padding: 0.3rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .recommendation-badge.regularize { background: rgba(16, 185, 129, 0.2); color: #34d399; }
    .recommendation-badge.extend { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
    .recommendation-badge.terminate { background: rgba(239, 68, 68, 0.2); color: #f87171; }
    .recommendation-badge.promote { background: rgba(139, 92, 246, 0.2); color: #a78bfa; }
    .status-badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .status-badge.draft { background: rgba(107, 114, 128, 0.2); color: #9ca3af; }
    .status-badge.submitted { background: rgba(14, 165, 233, 0.2); color: #38bdf8; }
    .status-badge.finalized { background: rgba(16, 185, 129, 0.2); color: #34d399; }
    .alert { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; }
    .alert-success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .alert-error { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
    .empty-state { text-align: center; padding: 4rem 2rem; color: #94a3b8; }
    .empty-state i { width: 4rem; height: 4rem; color: #475569; margin-bottom: 1rem; }
    @media (max-width: 768px) { .stats-row { grid-template-columns: 1fr; } .form-grid { grid-template-columns: 1fr; } .scores-grid { grid-template-columns: repeat(2, 1fr); } }
</style>

    <div class="header">
        <div>
            <h1>Performance Reviews</h1>
            <p>Evaluate probationary employees - 3rd/5th month reviews</p>
        </div>
        <button class="btn-primary" onclick="document.getElementById('reviewForm').classList.toggle('show')">
            <i data-lucide="plus" style="width:16px;height:16px;"></i> New Review
        </button>
    </div>

    <?php if ($successMsg): ?><div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div><?php endif; ?>
    <?php if ($errorMsg): ?><div class="alert alert-error"><?php echo htmlspecialchars($errorMsg); ?></div><?php endif; ?>

    <div class="stats-row">
        <div class="stat-card team">
            <div class="icon"><i data-lucide="users"></i></div>
            <div><h3><?php echo count($teamMembers); ?></h3><p>Team Members</p></div>
        </div>
        <div class="stat-card pending">
            <div class="icon"><i data-lucide="clock"></i></div>
            <div><h3><?php echo $pendingReviews; ?></h3><p>Draft Reviews</p></div>
        </div>
        <div class="stat-card done">
            <div class="icon"><i data-lucide="check-circle"></i></div>
            <div><h3><?php echo $submittedReviews; ?></h3><p>Submitted</p></div>
        </div>
    </div>

    <div id="reviewForm" class="review-form">
        <h3><i data-lucide="clipboard-check" style="width:20px;height:20px;display:inline;vertical-align:middle;"></i> Create Performance Review</h3>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Employee *</label>
                    <select name="employee_id" required>
                        <option value="">Select employee...</option>
                        <?php foreach ($teamMembers as $tm): ?>
                        <option value="<?php echo $tm['id']; ?>"><?php echo htmlspecialchars($tm['first_name'] . ' ' . $tm['last_name'] . ' (' . ($tm['employee_id'] ?? 'N/A') . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Review Type</label>
                    <select name="review_type">
                        <option value="3-Month">3rd Month Review</option>
                        <option value="5-Month">5th Month Review</option>
                        <option value="6-Month">6th Month Review</option>
                        <option value="Probation">Probation Review</option>
                        <option value="Annual">Annual Review</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Safety Score (1-10) *</label>
                    <input type="range" name="safety_score" min="1" max="10" value="5" oninput="this.nextElementSibling.textContent=this.value">
                    <span style="color:#0ea5e9;font-weight:600;text-align:center;">5</span>
                    <div class="score-display"><span>Poor</span><span>Excellent</span></div>
                </div>
                <div class="form-group">
                    <label>Punctuality Score (1-10) *</label>
                    <input type="range" name="punctuality_score" min="1" max="10" value="5" oninput="this.nextElementSibling.textContent=this.value">
                    <span style="color:#0ea5e9;font-weight:600;text-align:center;">5</span>
                    <div class="score-display"><span>Poor</span><span>Excellent</span></div>
                </div>
                <div class="form-group">
                    <label>Quality of Work Score (1-10) *</label>
                    <input type="range" name="quality_score" min="1" max="10" value="5" oninput="this.nextElementSibling.textContent=this.value">
                    <span style="color:#0ea5e9;font-weight:600;text-align:center;">5</span>
                    <div class="score-display"><span>Poor</span><span>Excellent</span></div>
                </div>
                <div class="form-group">
                    <label>Teamwork Score (1-10) *</label>
                    <input type="range" name="teamwork_score" min="1" max="10" value="5" oninput="this.nextElementSibling.textContent=this.value">
                    <span style="color:#0ea5e9;font-weight:600;text-align:center;">5</span>
                    <div class="score-display"><span>Poor</span><span>Excellent</span></div>
                </div>
                <div class="form-group">
                    <label>Recommendation</label>
                    <select name="recommendation">
                        <option value="Regularize">Regularize (Confirm Employment)</option>
                        <option value="Extend Probation">Extend Probation</option>
                        <option value="Terminate">Terminate</option>
                        <option value="Promote">Promote</option>
                    </select>
                </div>
                <div class="form-group full">
                    <label>Comments</label>
                    <textarea name="comments" placeholder="Detailed assessment... e.g., Driver A has zero accidents in 3 months, always on-time for deliveries, good rapport with warehouse team..."></textarea>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="submit_review" class="btn-primary"><i data-lucide="send" style="width:16px;height:16px;"></i> Submit Review</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('reviewForm').classList.remove('show')">Cancel</button>
            </div>
        </form>
    </div>

    <?php if (empty($reviews)): ?>
        <div class="empty-state">
            <i data-lucide="clipboard-check"></i>
            <h3 style="color: #e2e8f0; margin-bottom: 0.5rem;">No Reviews Yet</h3>
            <p>Click "New Review" to evaluate a team member's performance.</p>
        </div>
    <?php else: ?>
        <?php foreach ($reviews as $rev): ?>
        <div class="review-card">
            <div class="review-header">
                <div>
                    <div class="review-name"><?php echo htmlspecialchars(($rev['first_name'] ?? '') . ' ' . ($rev['last_name'] ?? '')); ?></div>
                    <div class="review-meta"><?php echo htmlspecialchars($rev['department_name'] ?? 'N/A'); ?> &bull; <?php echo $rev['review_type']; ?> &bull; <?php echo date('M d, Y', strtotime($rev['created_at'])); ?></div>
                </div>
                <div style="display:flex;gap:0.5rem;">
                    <span class="recommendation-badge <?php echo strtolower(str_replace(' ', '-', $rev['recommendation'])); ?>"><?php echo $rev['recommendation']; ?></span>
                    <span class="status-badge <?php echo strtolower($rev['status']); ?>"><?php echo $rev['status']; ?></span>
                </div>
            </div>

            <div class="overall-score">
                <div class="big-score"><?php echo number_format($rev['overall_score'], 1); ?></div>
                <div class="score-info">Overall Score<br><small>out of 10</small></div>
            </div>

            <div class="scores-grid">
                <div class="score-item"><div class="score-value"><?php echo $rev['safety_score']; ?></div><div class="score-label">Safety</div></div>
                <div class="score-item"><div class="score-value"><?php echo $rev['punctuality_score']; ?></div><div class="score-label">Punctuality</div></div>
                <div class="score-item"><div class="score-value"><?php echo $rev['quality_score']; ?></div><div class="score-label">Quality</div></div>
                <div class="score-item"><div class="score-value"><?php echo $rev['teamwork_score']; ?></div><div class="score-label">Teamwork</div></div>
            </div>

            <?php if (!empty($rev['comments'])): ?>
            <div style="background:rgba(15,23,42,0.4);padding:0.75rem;border-radius:8px;color:#cbd5e1;font-size:0.85rem;">
                <?php echo nl2br(htmlspecialchars($rev['comments'])); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

<script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
</div>
