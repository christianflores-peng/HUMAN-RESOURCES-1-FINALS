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
if (!$is_ajax) { header('Location: index.php?page=interview-panel'); exit(); }

require_once '../../database/config.php';

$successMsg = null;
$errorMsg = null;

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $intId = intval($_POST['interview_id']);
    $feedback = trim($_POST['interviewer_feedback'] ?? '');
    $rating = intval($_POST['rating'] ?? 0);
    $status = $_POST['new_status'] ?? 'Completed';
    
    if ($feedback && $rating > 0) {
        try {
            updateRecord("UPDATE interview_schedules SET status = ?, interviewer_feedback = ?, rating = ? WHERE id = ? AND interviewer_id = ?", 
                [$status, $feedback, $rating, $intId, $userId]);
            logAuditAction($userId, 'EDIT', 'interview_schedules', $intId, null, ['status' => $status], "Submitted interview feedback");
            $successMsg = "Feedback submitted successfully!";
        } catch (Exception $e) { $errorMsg = "Failed: " . $e->getMessage(); }
    } else {
        $errorMsg = "Please provide feedback and a rating.";
    }
}

// Handle recommend action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recommend_action'])) {
    $appId = intval($_POST['application_id']);
    $action = $_POST['recommendation'] ?? '';
    $actionMap = ['hire' => 'Offer', 'reject' => 'Rejected', 'road_test' => 'Road Test'];
    if (isset($actionMap[$action])) {
        try {
            updateRecord("UPDATE job_applications SET status = ? WHERE id = ?", [$actionMap[$action], $appId]);
            logAuditAction($userId, 'EDIT', 'job_applications', $appId, null, ['status' => $actionMap[$action]], "Manager recommendation: {$action}");
            $successMsg = "Recommendation submitted!";
        } catch (Exception $e) { $errorMsg = "Failed: " . $e->getMessage(); }
    }
}

// Fetch my interviews (where I'm the interviewer)
try {
    $myInterviews = fetchAll("
        SELECT isch.*, 
               ja.id as app_id, ja.status as app_status,
               app.first_name as app_first, app.last_name as app_last, app.email as app_email, app.phone as app_phone,
               jp.title as job_title, d.department_name
        FROM interview_schedules isch
        LEFT JOIN job_applications ja ON isch.application_id = ja.id
        LEFT JOIN user_accounts app ON ja.user_id = app.id
        LEFT JOIN job_postings jp ON ja.job_posting_id = jp.id
        LEFT JOIN departments d ON jp.department_id = d.id
        WHERE isch.interviewer_id = ?
        ORDER BY 
            CASE isch.status 
                WHEN 'Scheduled' THEN 1 
                WHEN 'Confirmed' THEN 2 
                WHEN 'Completed' THEN 3 
                ELSE 4 
            END,
            isch.scheduled_date ASC, isch.scheduled_time ASC
    ", [$userId]);
} catch (Exception $e) { $myInterviews = []; }

$todayCount = 0; $pendingFeedback = 0; $completedCount = 0;
$today = date('Y-m-d');
foreach ($myInterviews as $i) {
    if ($i['scheduled_date'] === $today && in_array($i['status'], ['Scheduled', 'Confirmed'])) $todayCount++;
    if ($i['status'] === 'Completed' && empty($i['interviewer_feedback'])) $pendingFeedback++;
    if ($i['status'] === 'Completed') $completedCount++;
}
?>
<div data-page-title="Interview Panel">
<style>
    .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; }
    .header h1 { font-size: 1.35rem; color: #e2e8f0; margin-bottom: 0.25rem; }
    .header p { color: #94a3b8; font-size: 0.85rem; }
    .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    .stat-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.25rem; border: 1px solid rgba(58, 69, 84, 0.5); display: flex; align-items: center; gap: 1rem; }
    .stat-card .icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
    .stat-card.today .icon { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
    .stat-card.pending .icon { background: rgba(139, 92, 246, 0.2); color: #a78bfa; }
    .stat-card.done .icon { background: rgba(16, 185, 129, 0.2); color: #10b981; }
    .stat-card h3 { font-size: 1.5rem; color: #e2e8f0; }
    .stat-card p { font-size: 0.8rem; color: #94a3b8; }
    .interview-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1rem; }
    .interview-card.today-highlight { border-left: 3px solid #f59e0b; }
    .interview-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
    .interview-name { font-size: 1.1rem; color: #e2e8f0; font-weight: 600; }
    .interview-job { color: #0ea5e9; font-size: 0.9rem; }
    .interview-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; margin-bottom: 1rem; }
    .detail-item { display: flex; align-items: center; gap: 0.4rem; color: #94a3b8; font-size: 0.85rem; }
    .detail-item i { width: 16px; height: 16px; color: #64748b; }
    .status-badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .status-badge.scheduled { background: rgba(14, 165, 233, 0.2); color: #38bdf8; }
    .status-badge.confirmed { background: rgba(16, 185, 129, 0.2); color: #34d399; }
    .status-badge.completed { background: rgba(139, 92, 246, 0.2); color: #a78bfa; }
    .status-badge.cancelled { background: rgba(107, 114, 128, 0.2); color: #9ca3af; }
    .status-badge.no-show { background: rgba(239, 68, 68, 0.2); color: #f87171; }
    .feedback-form { background: rgba(15, 23, 42, 0.4); border-radius: 8px; padding: 1rem; margin-top: 1rem; }
    .feedback-form h4 { color: #e2e8f0; font-size: 0.95rem; margin-bottom: 0.75rem; }
    .form-group { display: flex; flex-direction: column; gap: 0.3rem; margin-bottom: 0.75rem; }
    .form-group label { color: #94a3b8; font-size: 0.8rem; font-weight: 500; }
    .form-group textarea { padding: 0.6rem; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(58, 69, 84, 0.5); border-radius: 8px; color: #e2e8f0; font-size: 0.85rem; resize: vertical; min-height: 80px; }
    .form-group textarea:focus { outline: none; border-color: #0ea5e9; }
    .star-rating { display: flex; gap: 0.25rem; margin-bottom: 0.75rem; }
    .star-rating input[type="radio"] { display: none; }
    .star-rating label { font-size: 1.5rem; color: #475569; cursor: pointer; transition: color 0.2s; }
    .star-rating label:hover, .star-rating label:hover ~ label, .star-rating input:checked ~ label { color: #f59e0b; }
    .star-rating { direction: rtl; }
    .btn { padding: 0.5rem 1rem; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 500; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 0.4rem; }
    .btn-primary { background: linear-gradient(135deg, #0ea5e9, #0284c7); color: white; }
    .btn-primary:hover { transform: translateY(-1px); }
    .btn-hire { background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
    .btn-hire:hover { background: rgba(16, 185, 129, 0.3); }
    .btn-reject { background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
    .btn-reject:hover { background: rgba(239, 68, 68, 0.3); }
    .btn-test { background: rgba(245, 158, 11, 0.2); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); }
    .btn-test:hover { background: rgba(245, 158, 11, 0.3); }
    .recommend-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.75rem; }
    .existing-feedback { background: rgba(139, 92, 246, 0.1); padding: 0.75rem; border-radius: 8px; margin-top: 0.75rem; }
    .existing-feedback strong { color: #a78bfa; font-size: 0.85rem; }
    .existing-feedback p { color: #cbd5e1; font-size: 0.85rem; margin-top: 0.25rem; }
    .rating-display { display: flex; gap: 0.15rem; margin-top: 0.25rem; }
    .rating-display .star { color: #475569; font-size: 1rem; }
    .rating-display .star.filled { color: #f59e0b; }
    .alert { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; }
    .alert-success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .alert-error { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
    .empty-state { text-align: center; padding: 4rem 2rem; color: #94a3b8; }
    .empty-state i { width: 4rem; height: 4rem; color: #475569; margin-bottom: 1rem; }
    @media (max-width: 768px) { .stats-row { grid-template-columns: 1fr; } }
</style>

    <div class="header">
        <h1>Interview Panel</h1>
        <p>Conduct interviews, provide feedback, and recommend candidates</p>
    </div>

    <?php if ($successMsg): ?><div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div><?php endif; ?>
    <?php if ($errorMsg): ?><div class="alert alert-error"><?php echo htmlspecialchars($errorMsg); ?></div><?php endif; ?>

    <div class="stats-row">
        <div class="stat-card today">
            <div class="icon"><i data-lucide="calendar"></i></div>
            <div><h3><?php echo $todayCount; ?></h3><p>Today's Interviews</p></div>
        </div>
        <div class="stat-card pending">
            <div class="icon"><i data-lucide="message-square"></i></div>
            <div><h3><?php echo $pendingFeedback; ?></h3><p>Pending Feedback</p></div>
        </div>
        <div class="stat-card done">
            <div class="icon"><i data-lucide="check-circle"></i></div>
            <div><h3><?php echo $completedCount; ?></h3><p>Completed</p></div>
        </div>
    </div>

    <?php if (empty($myInterviews)): ?>
        <div class="empty-state">
            <i data-lucide="message-square"></i>
            <h3 style="color: #e2e8f0; margin-bottom: 0.5rem;">No Interviews Assigned</h3>
            <p>Interviews scheduled by HR will appear here.</p>
        </div>
    <?php else: ?>
        <?php foreach ($myInterviews as $int): ?>
        <div class="interview-card <?php echo ($int['scheduled_date'] === $today && in_array($int['status'], ['Scheduled','Confirmed'])) ? 'today-highlight' : ''; ?>">
            <div class="interview-header">
                <div>
                    <div class="interview-name"><?php echo htmlspecialchars(($int['app_first'] ?? '') . ' ' . ($int['app_last'] ?? '')); ?></div>
                    <div class="interview-job"><?php echo htmlspecialchars($int['job_title'] ?? 'N/A'); ?> &mdash; <?php echo htmlspecialchars($int['department_name'] ?? ''); ?></div>
                </div>
                <span class="status-badge <?php echo strtolower($int['status']); ?>"><?php echo $int['status']; ?></span>
            </div>

            <div class="interview-details">
                <div class="detail-item"><i data-lucide="calendar"></i> <?php echo date('M d, Y', strtotime($int['scheduled_date'])); ?></div>
                <div class="detail-item"><i data-lucide="clock"></i> <?php echo date('h:i A', strtotime($int['scheduled_time'])); ?> (<?php echo $int['duration_minutes']; ?> min)</div>
                <div class="detail-item"><i data-lucide="video"></i> <?php echo $int['interview_type']; ?></div>
                <?php if (!empty($int['app_email'])): ?>
                <div class="detail-item"><i data-lucide="mail"></i> <?php echo htmlspecialchars($int['app_email']); ?></div>
                <?php endif; ?>
                <?php if (!empty($int['location'])): ?>
                <div class="detail-item"><i data-lucide="map-pin"></i> <?php echo htmlspecialchars($int['location']); ?></div>
                <?php endif; ?>
                <?php if (!empty($int['meeting_link'])): ?>
                <div class="detail-item"><i data-lucide="link"></i> <a href="<?php echo htmlspecialchars($int['meeting_link']); ?>" target="_blank" style="color:#38bdf8;">Join Meeting</a></div>
                <?php endif; ?>
            </div>

            <?php if (!empty($int['notes'])): ?>
            <div style="background:rgba(15,23,42,0.4);padding:0.5rem 0.75rem;border-radius:6px;color:#94a3b8;font-size:0.85rem;margin-bottom:0.75rem;">
                <strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($int['notes'])); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($int['interviewer_feedback'])): ?>
            <div class="existing-feedback">
                <strong>Your Feedback:</strong>
                <p><?php echo nl2br(htmlspecialchars($int['interviewer_feedback'])); ?></p>
                <?php if ($int['rating']): ?>
                <div class="rating-display">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                    <span class="star <?php echo $s <= $int['rating'] ? 'filled' : ''; ?>">&#9733;</span>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($int['status'] === 'Completed' && in_array($int['app_status'], ['For Interview', 'Shortlisted'])): ?>
            <div class="recommend-actions">
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="application_id" value="<?php echo $int['app_id']; ?>">
                    <input type="hidden" name="recommendation" value="hire">
                    <button type="submit" name="recommend_action" class="btn btn-hire"><i data-lucide="thumbs-up" style="width:14px;height:14px;"></i> Recommend Hire</button>
                </form>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="application_id" value="<?php echo $int['app_id']; ?>">
                    <input type="hidden" name="recommendation" value="road_test">
                    <button type="submit" name="recommend_action" class="btn btn-test"><i data-lucide="car" style="width:14px;height:14px;"></i> Send to Road Test</button>
                </form>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="application_id" value="<?php echo $int['app_id']; ?>">
                    <input type="hidden" name="recommendation" value="reject">
                    <button type="submit" name="recommend_action" class="btn btn-reject"><i data-lucide="thumbs-down" style="width:14px;height:14px;"></i> Reject</button>
                </form>
            </div>
            <?php endif; ?>

            <?php elseif (in_array($int['status'], ['Scheduled', 'Confirmed', 'Completed'])): ?>
            <div class="feedback-form">
                <h4><i data-lucide="edit" style="width:16px;height:16px;display:inline;vertical-align:middle;"></i> Submit Feedback</h4>
                <form method="POST">
                    <input type="hidden" name="interview_id" value="<?php echo $int['id']; ?>">
                    <input type="hidden" name="new_status" value="Completed">
                    <div class="form-group">
                        <label>Rating *</label>
                        <div class="star-rating">
                            <?php for ($s = 5; $s >= 1; $s--): ?>
                            <input type="radio" name="rating" value="<?php echo $s; ?>" id="star<?php echo $int['id']; ?>_<?php echo $s; ?>">
                            <label for="star<?php echo $int['id']; ?>_<?php echo $s; ?>">&#9733;</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Feedback *</label>
                        <textarea name="interviewer_feedback" required placeholder="Your assessment of the candidate... e.g., Strong communication skills, has valid license, 5 years driving experience..."></textarea>
                    </div>
                    <button type="submit" name="submit_feedback" class="btn btn-primary"><i data-lucide="send" style="width:14px;height:14px;"></i> Submit Feedback</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

<script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
</div>
