<?php
require_once '../../includes/session_helper.php';
require_once '../../includes/rbac_helper.php';
require_once '../../includes/spa_helper.php';
startSecureSession();
$is_ajax = is_spa_ajax();

if (!isset($_SESSION['user_id'])) { header('Location: ../../index.php'); exit(); }
$userId = $_SESSION['user_id'];
if (!isHRStaff($userId) && !isAdmin($userId)) { header('Location: ../../index.php'); exit(); }
if (!$is_ajax) { header('Location: index.php?page=interview-management'); exit(); }

require_once '../../database/config.php';

$successMsg = null;
$errorMsg = null;

// Handle schedule interview
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['schedule_interview'])) {
        $appId = intval($_POST['application_id']);
        $interviewerId = intval($_POST['interviewer_id']);
        $type = $_POST['interview_type'] ?? 'In-Person';
        $date = $_POST['scheduled_date'] ?? '';
        $time = $_POST['scheduled_time'] ?? '';
        $duration = intval($_POST['duration_minutes'] ?? 60);
        $location = trim($_POST['location'] ?? '');
        $meetingLink = trim($_POST['meeting_link'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($appId && $interviewerId && $date && $time) {
            try {
                insertRecord(
                    "INSERT INTO interview_schedules (application_id, interviewer_id, interview_type, scheduled_date, scheduled_time, duration_minutes, location, meeting_link, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$appId, $interviewerId, $type, $date, $time, $duration, $location, $meetingLink, $notes, $userId]
                );
                updateRecord("UPDATE job_applications SET status = 'For Interview' WHERE id = ? AND status IN ('Shortlisted','Pending','Submitted')", [$appId]);
                logAuditAction($userId, 'CREATE', 'interview_schedules', null, null, null, "Scheduled interview for application #{$appId}");
                $successMsg = "Interview scheduled successfully!";
            } catch (Exception $e) { $errorMsg = "Failed: " . $e->getMessage(); }
        } else {
            $errorMsg = "Please fill in all required fields.";
        }
    }
    if (isset($_POST['update_interview_status'])) {
        $intId = intval($_POST['interview_id']);
        $status = $_POST['new_status'] ?? '';
        $feedback = trim($_POST['interviewer_feedback'] ?? '');
        $rating = intval($_POST['rating'] ?? 0);
        try {
            updateRecord("UPDATE interview_schedules SET status = ?, interviewer_feedback = ?, rating = ? WHERE id = ?", [$status, $feedback, $rating, $intId]);
            $successMsg = "Interview updated!";
        } catch (Exception $e) { $errorMsg = "Failed: " . $e->getMessage(); }
    }
}

// Fetch interviews
$filterStatus = $_GET['status'] ?? '';
$where = "1=1";
$params = [];
if ($filterStatus) { $where .= " AND isch.status = ?"; $params[] = $filterStatus; }

try {
    $interviews = fetchAll("
        SELECT isch.*, 
               ja.user_id as applicant_user_id,
               app.first_name as app_first, app.last_name as app_last, app.email as app_email,
               intr.first_name as intr_first, intr.last_name as intr_last,
               jp.title as job_title, d.department_name
        FROM interview_schedules isch
        LEFT JOIN job_applications ja ON isch.application_id = ja.id
        LEFT JOIN user_accounts app ON ja.user_id = app.id
        LEFT JOIN user_accounts intr ON isch.interviewer_id = intr.id
        LEFT JOIN job_postings jp ON ja.job_posting_id = jp.id
        LEFT JOIN departments d ON jp.department_id = d.id
        WHERE {$where}
        ORDER BY isch.scheduled_date ASC, isch.scheduled_time ASC
    ", $params);
} catch (Exception $e) { $interviews = []; }

// Fetch candidates eligible for interview (shortlisted or for interview)
try {
    $eligibleApps = fetchAll("
        SELECT ja.id, ja.user_id, ua.first_name, ua.last_name, jp.title as job_title
        FROM job_applications ja
        LEFT JOIN user_accounts ua ON ja.user_id = ua.id
        LEFT JOIN job_postings jp ON ja.job_posting_id = jp.id
        WHERE ja.status IN ('Shortlisted','For Interview')
        ORDER BY ja.applied_at DESC
    ", []);
} catch (Exception $e) { $eligibleApps = []; }

// Fetch managers/interviewers
try {
    $interviewers = fetchAll("
        SELECT ua.id, ua.first_name, ua.last_name, r.role_type
        FROM user_accounts ua
        LEFT JOIN roles r ON ua.role_id = r.id
        WHERE r.role_type IN ('Manager','HR_Staff','Admin')
        ORDER BY ua.first_name
    ", []);
} catch (Exception $e) { $interviewers = []; }

$todayCount = 0; $upcomingCount = 0; $completedCount = 0;
$today = date('Y-m-d');
foreach ($interviews as $i) {
    if ($i['scheduled_date'] === $today && $i['status'] === 'Scheduled') $todayCount++;
    if ($i['scheduled_date'] > $today && $i['status'] === 'Scheduled') $upcomingCount++;
    if ($i['status'] === 'Completed') $completedCount++;
}
?>
<div data-page-title="Interview Management">
<style>
    .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
    .header h1 { font-size: 1.35rem; color: #e2e8f0; margin-bottom: 0.25rem; }
    .header p { color: #94a3b8; font-size: 0.85rem; }
    .btn-primary { padding: 0.6rem 1.2rem; background: linear-gradient(135deg, #0ea5e9, #0284c7); color: white; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; transition: all 0.3s; }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3); }
    .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    .stat-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1rem; border: 1px solid rgba(58, 69, 84, 0.5); text-align: center; }
    .stat-card h3 { font-size: 1.5rem; color: #e2e8f0; }
    .stat-card p { font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem; }
    .stat-card.today { border-left: 3px solid #f59e0b; }
    .stat-card.upcoming { border-left: 3px solid #0ea5e9; }
    .stat-card.completed { border-left: 3px solid #10b981; }
    .schedule-form { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1.5rem; display: none; }
    .schedule-form.show { display: block; }
    .schedule-form h3 { color: #e2e8f0; font-size: 1.1rem; margin-bottom: 1rem; }
    .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
    .form-group { display: flex; flex-direction: column; gap: 0.3rem; }
    .form-group.full { grid-column: 1 / -1; }
    .form-group label { color: #94a3b8; font-size: 0.8rem; font-weight: 500; }
    .form-group input, .form-group select, .form-group textarea { padding: 0.6rem; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(58, 69, 84, 0.5); border-radius: 8px; color: #e2e8f0; font-size: 0.85rem; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #0ea5e9; }
    .form-group textarea { resize: vertical; min-height: 60px; }
    .form-actions { display: flex; gap: 0.5rem; margin-top: 1rem; }
    .interview-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1rem; }
    .interview-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem; }
    .interview-title { font-size: 1rem; color: #e2e8f0; font-weight: 600; }
    .interview-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; margin-bottom: 1rem; }
    .detail-item { display: flex; align-items: center; gap: 0.4rem; color: #94a3b8; font-size: 0.85rem; }
    .detail-item i { width: 16px; height: 16px; color: #64748b; }
    .status-badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .status-badge.scheduled { background: rgba(14, 165, 233, 0.2); color: #38bdf8; }
    .status-badge.confirmed { background: rgba(16, 185, 129, 0.2); color: #34d399; }
    .status-badge.completed { background: rgba(139, 92, 246, 0.2); color: #a78bfa; }
    .status-badge.cancelled { background: rgba(107, 114, 128, 0.2); color: #9ca3af; }
    .status-badge.no-show { background: rgba(239, 68, 68, 0.2); color: #f87171; }
    .btn { padding: 0.4rem 0.8rem; border: none; border-radius: 6px; font-size: 0.8rem; cursor: pointer; transition: all 0.3s; }
    .btn-sm { padding: 0.3rem 0.6rem; font-size: 0.75rem; }
    .btn-cancel { background: rgba(107, 114, 128, 0.2); color: #9ca3af; border: 1px solid rgba(107, 114, 128, 0.3); }
    .btn-success { background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
    .alert { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; }
    .alert-success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .alert-error { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
    .empty-state { text-align: center; padding: 4rem 2rem; color: #94a3b8; }
    .empty-state i { width: 4rem; height: 4rem; color: #475569; margin-bottom: 1rem; }
    .rating-stars { display: flex; gap: 0.25rem; }
    .rating-stars .star { color: #475569; }
    .rating-stars .star.filled { color: #f59e0b; }
    @media (max-width: 768px) { .stats-row { grid-template-columns: 1fr; } .form-grid { grid-template-columns: 1fr; } }
</style>

    <div class="header">
        <div>
            <h1>Interview Management</h1>
            <p>Schedule and manage interviews with Hiring Managers</p>
        </div>
        <button class="btn-primary" onclick="document.getElementById('scheduleForm').classList.toggle('show')">
            <i data-lucide="plus" style="width:16px;height:16px;"></i> Schedule Interview
        </button>
    </div>

    <?php if ($successMsg): ?><div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div><?php endif; ?>
    <?php if ($errorMsg): ?><div class="alert alert-error"><?php echo htmlspecialchars($errorMsg); ?></div><?php endif; ?>

    <div class="stats-row">
        <div class="stat-card today"><h3><?php echo $todayCount; ?></h3><p>Today's Interviews</p></div>
        <div class="stat-card upcoming"><h3><?php echo $upcomingCount; ?></h3><p>Upcoming</p></div>
        <div class="stat-card completed"><h3><?php echo $completedCount; ?></h3><p>Completed</p></div>
    </div>

    <div id="scheduleForm" class="schedule-form">
        <h3><i data-lucide="calendar-plus" style="width:20px;height:20px;display:inline;vertical-align:middle;"></i> Schedule New Interview</h3>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Applicant *</label>
                    <select name="application_id" required>
                        <option value="">Select applicant...</option>
                        <?php foreach ($eligibleApps as $ea): ?>
                        <option value="<?php echo $ea['id']; ?>"><?php echo htmlspecialchars($ea['first_name'] . ' ' . $ea['last_name'] . ' - ' . $ea['job_title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Interviewer (Hiring Manager) *</label>
                    <select name="interviewer_id" required>
                        <option value="">Select interviewer...</option>
                        <?php foreach ($interviewers as $iv): ?>
                        <option value="<?php echo $iv['id']; ?>"><?php echo htmlspecialchars($iv['first_name'] . ' ' . $iv['last_name'] . ' (' . $iv['role_type'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Interview Type</label>
                    <select name="interview_type">
                        <option value="In-Person">In-Person</option>
                        <option value="Phone">Phone</option>
                        <option value="Video">Video</option>
                        <option value="Panel">Panel</option>
                        <option value="Technical">Technical</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Duration (minutes)</label>
                    <input type="number" name="duration_minutes" value="60" min="15" max="180">
                </div>
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="scheduled_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Time *</label>
                    <input type="time" name="scheduled_time" required>
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" placeholder="e.g., Conference Room A">
                </div>
                <div class="form-group">
                    <label>Meeting Link</label>
                    <input type="url" name="meeting_link" placeholder="e.g., https://meet.google.com/...">
                </div>
                <div class="form-group full">
                    <label>Notes</label>
                    <textarea name="notes" placeholder="Additional instructions for the interview..."></textarea>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="schedule_interview" class="btn-primary"><i data-lucide="check" style="width:16px;height:16px;"></i> Schedule</button>
                <button type="button" class="btn btn-cancel" onclick="document.getElementById('scheduleForm').classList.remove('show')">Cancel</button>
            </div>
        </form>
    </div>

    <?php if (empty($interviews)): ?>
        <div class="empty-state">
            <i data-lucide="calendar"></i>
            <h3 style="color: #e2e8f0; margin-bottom: 0.5rem;">No Interviews Scheduled</h3>
            <p>Click "Schedule Interview" to set up a new interview.</p>
        </div>
    <?php else: ?>
        <?php foreach ($interviews as $int): ?>
        <div class="interview-card">
            <div class="interview-header">
                <div>
                    <div class="interview-title"><?php echo htmlspecialchars(($int['app_first'] ?? '') . ' ' . ($int['app_last'] ?? '')); ?></div>
                    <div style="color:#94a3b8;font-size:0.85rem;"><?php echo htmlspecialchars($int['job_title'] ?? 'N/A'); ?> &mdash; <?php echo htmlspecialchars($int['department_name'] ?? ''); ?></div>
                </div>
                <span class="status-badge <?php echo strtolower(str_replace('-', '-', $int['status'])); ?>"><?php echo $int['status']; ?></span>
            </div>
            <div class="interview-details">
                <div class="detail-item"><i data-lucide="calendar"></i> <?php echo date('M d, Y', strtotime($int['scheduled_date'])); ?></div>
                <div class="detail-item"><i data-lucide="clock"></i> <?php echo date('h:i A', strtotime($int['scheduled_time'])); ?> (<?php echo $int['duration_minutes']; ?> min)</div>
                <div class="detail-item"><i data-lucide="video"></i> <?php echo $int['interview_type']; ?></div>
                <div class="detail-item"><i data-lucide="user"></i> Interviewer: <?php echo htmlspecialchars(($int['intr_first'] ?? '') . ' ' . ($int['intr_last'] ?? '')); ?></div>
                <?php if (!empty($int['location'])): ?>
                <div class="detail-item"><i data-lucide="map-pin"></i> <?php echo htmlspecialchars($int['location']); ?></div>
                <?php endif; ?>
                <?php if (!empty($int['meeting_link'])): ?>
                <div class="detail-item"><i data-lucide="link"></i> <a href="<?php echo htmlspecialchars($int['meeting_link']); ?>" target="_blank" style="color:#38bdf8;">Join Meeting</a></div>
                <?php endif; ?>
            </div>
            <?php if (!empty($int['notes'])): ?>
            <div style="background:rgba(15,23,42,0.4);padding:0.5rem 0.75rem;border-radius:6px;color:#94a3b8;font-size:0.85rem;margin-bottom:0.75rem;">
                <?php echo nl2br(htmlspecialchars($int['notes'])); ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($int['interviewer_feedback'])): ?>
            <div style="background:rgba(139,92,246,0.1);padding:0.5rem 0.75rem;border-radius:6px;color:#a78bfa;font-size:0.85rem;margin-bottom:0.75rem;">
                <strong>Feedback:</strong> <?php echo nl2br(htmlspecialchars($int['interviewer_feedback'])); ?>
                <?php if ($int['rating']): ?>
                <div class="rating-stars" style="margin-top:0.25rem;">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                    <span class="star <?php echo $s <= $int['rating'] ? 'filled' : ''; ?>">&#9733;</span>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if ($int['status'] === 'Scheduled' || $int['status'] === 'Confirmed'): ?>
            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="interview_id" value="<?php echo $int['id']; ?>">
                    <input type="hidden" name="new_status" value="Completed">
                    <input type="hidden" name="interviewer_feedback" value="">
                    <input type="hidden" name="rating" value="0">
                    <button type="submit" name="update_interview_status" class="btn btn-success btn-sm">Mark Completed</button>
                </form>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="interview_id" value="<?php echo $int['id']; ?>">
                    <input type="hidden" name="new_status" value="Cancelled">
                    <input type="hidden" name="interviewer_feedback" value="">
                    <input type="hidden" name="rating" value="0">
                    <button type="submit" name="update_interview_status" class="btn btn-cancel btn-sm">Cancel</button>
                </form>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="interview_id" value="<?php echo $int['id']; ?>">
                    <input type="hidden" name="new_status" value="No-Show">
                    <input type="hidden" name="interviewer_feedback" value="">
                    <input type="hidden" name="rating" value="0">
                    <button type="submit" name="update_interview_status" class="btn btn-cancel btn-sm" style="color:#ef4444;">No-Show</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

<script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
</div>
