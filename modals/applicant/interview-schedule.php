<?php
require_once '../../includes/session_helper.php';
require_once '../../includes/spa_helper.php';
startSecureSession();
$is_ajax = is_spa_ajax();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Applicant') {
    header('Location: ../../index.php');
    exit();
}

if (!$is_ajax) {
    header('Location: index.php?page=interview-schedule');
    exit();
}

require_once '../../database/config.php';

$user_email = $_SESSION['personal_email'] ?? '';

try {
    // Query interview_schedules (created by HR staff interview-management)
    $interviews = fetchAll("
        SELECT ja.id as application_id, 
               CONCAT(isc.scheduled_date, ' ', isc.scheduled_time) as scheduled_date, 
               isc.interview_type, 
               isc.location, 
               isc.notes,
               COALESCE(jp.title, 'General Application') as job_title,
               COALESCE(d.department_name, 'N/A') as department_name
        FROM job_applications ja
        INNER JOIN interview_schedules isc ON isc.application_id = ja.id
        LEFT JOIN job_postings jp ON ja.job_posting_id = jp.id
        LEFT JOIN departments d ON jp.department_id = d.id
        WHERE ja.email = ?
        ORDER BY isc.scheduled_date DESC, isc.scheduled_time DESC
    ", [$user_email]);
} catch (Exception $e) {
    $interviews = [];
}

// Fallback: also check job_applications.interview_date if no interview_schedules found
if (empty($interviews)) {
    try {
        $interviews = fetchAll("
            SELECT ja.id as application_id, 
                   ja.interview_date as scheduled_date, 
                   ja.interview_type, 
                   ja.interview_location as location, 
                   ja.interview_notes as notes,
                   COALESCE(jp.title, 'General Application') as job_title,
                   COALESCE(d.department_name, 'N/A') as department_name
            FROM job_applications ja
            LEFT JOIN job_postings jp ON ja.job_posting_id = jp.id
            LEFT JOIN departments d ON jp.department_id = d.id
            WHERE ja.email = ? AND ja.interview_date IS NOT NULL
            ORDER BY ja.interview_date DESC
        ", [$user_email]);
    } catch (Exception $e) {
        $interviews = [];
    }
}

$upcoming = array_filter($interviews, fn($i) => !empty($i['scheduled_date']) && strtotime($i['scheduled_date']) >= strtotime('today'));
$past = array_filter($interviews, fn($i) => !empty($i['scheduled_date']) && strtotime($i['scheduled_date']) < strtotime('today'));
?>
<div data-page-title="Interview Schedule">
<style>
        .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
        .header h1 { font-size: 1.5rem; color: #e2e8f0; }
        .section-title { font-size: 1.2rem; color: #e2e8f0; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .interviews-grid { display: grid; gap: 1rem; margin-bottom: 2rem; }
        .interview-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); display: grid; grid-template-columns: auto 1fr auto; gap: 1.5rem; align-items: center; }
        .interview-card.upcoming { border-left: 4px solid #8b5cf6; }
        .interview-card.past { opacity: 0.7; }
        .date-box { background: rgba(139, 92, 246, 0.2); border-radius: 10px; padding: 1rem; text-align: center; min-width: 80px; }
        .date-box .month { font-size: 0.75rem; color: #8b5cf6; text-transform: uppercase; font-weight: 600; }
        .date-box .day { font-size: 1.75rem; font-weight: 700; color: #e2e8f0; }
        .date-box .year { font-size: 0.75rem; color: #94a3b8; }
        .interview-details h3 { font-size: 1.1rem; color: #e2e8f0; margin-bottom: 0.5rem; }
        .interview-meta { display: flex; flex-wrap: wrap; gap: 1rem; color: #94a3b8; font-size: 0.85rem; }
        .meta-item { display: flex; align-items: center; gap: 0.35rem; }
        .interview-type { padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .interview-type.in_person { background: rgba(99, 102, 241, 0.2); color: #6366f1; }
        .interview-type.video { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .interview-type.phone { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .empty-state { text-align: center; padding: 3rem 2rem; color: #94a3b8; background: rgba(30, 41, 54, 0.4); border-radius: 12px; margin-bottom: 2rem; }
        .empty-state i { width: 4rem; height: 4rem; color: #475569; margin-bottom: 0.75rem; }
    </style>
            <div class="header">
                <h1>Interview Schedule</h1>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                </div>
            </div>

            <h2 class="section-title"><i data-lucide="calendar-clock"></i>Upcoming Interviews</h2>
            <?php if (empty($upcoming)): ?>
            <div class="empty-state">
                <i data-lucide="calendar-x"></i>
                <h3>No Upcoming Interviews</h3>
                <p>You don't have any scheduled interviews at the moment.</p>
            </div>
            <?php else: ?>
            <div class="interviews-grid">
                <?php foreach ($upcoming as $interview): ?>
                <div class="interview-card upcoming">
                    <div class="date-box">
                        <div class="month"><?php echo date('M', strtotime($interview['scheduled_date'])); ?></div>
                        <div class="day"><?php echo date('d', strtotime($interview['scheduled_date'])); ?></div>
                        <div class="year"><?php echo date('Y', strtotime($interview['scheduled_date'])); ?></div>
                    </div>
                    <div class="interview-details">
                        <h3><?php echo htmlspecialchars($interview['job_title']); ?></h3>
                        <div class="interview-meta">
                            <div class="meta-item"><i data-lucide="clock" style="width: 1rem; height: 1rem;"></i><?php echo date('h:i A', strtotime($interview['scheduled_date'])); ?></div>
                            <div class="meta-item"><i data-lucide="map-pin" style="width: 1rem; height: 1rem;"></i><?php echo htmlspecialchars($interview['location'] ?: 'To be confirmed'); ?></div>
                            <div class="meta-item"><i data-lucide="building" style="width: 1rem; height: 1rem;"></i><?php echo htmlspecialchars($interview['department_name']); ?></div>
                        </div>
                    </div>
                    <span class="interview-type <?php echo $interview['interview_type'] ?? 'in_person'; ?>"><?php echo ucfirst(str_replace('_', ' ', $interview['interview_type'] ?? 'In Person')); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <h2 class="section-title"><i data-lucide="history"></i>Past Interviews</h2>
            <?php if (empty($past)): ?>
            <div class="empty-state">
                <i data-lucide="calendar-check"></i>
                <h3>No Past Interviews</h3>
                <p>Your interview history will appear here.</p>
            </div>
            <?php else: ?>
            <div class="interviews-grid">
                <?php foreach ($past as $interview): ?>
                <div class="interview-card past">
                    <div class="date-box">
                        <div class="month"><?php echo date('M', strtotime($interview['scheduled_date'])); ?></div>
                        <div class="day"><?php echo date('d', strtotime($interview['scheduled_date'])); ?></div>
                        <div class="year"><?php echo date('Y', strtotime($interview['scheduled_date'])); ?></div>
                    </div>
                    <div class="interview-details">
                        <h3><?php echo htmlspecialchars($interview['job_title']); ?></h3>
                        <div class="interview-meta">
                            <div class="meta-item"><i data-lucide="clock" style="width: 1rem; height: 1rem;"></i><?php echo date('h:i A', strtotime($interview['scheduled_date'])); ?></div>
                            <div class="meta-item"><i data-lucide="map-pin" style="width: 1rem; height: 1rem;"></i><?php echo htmlspecialchars($interview['location'] ?: 'N/A'); ?></div>
                        </div>
                    </div>
                    <span class="interview-type <?php echo $interview['interview_type'] ?? 'in_person'; ?>"><?php echo ucfirst(str_replace('_', ' ', $interview['interview_type'] ?? 'In Person')); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
</div>
