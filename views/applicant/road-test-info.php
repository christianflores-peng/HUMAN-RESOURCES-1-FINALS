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
    header('Location: index.php?page=road-test-info');
    exit();
}

require_once '../../database/config.php';

$user_email = $_SESSION['personal_email'] ?? '';

try {
    $road_tests = fetchAll("
        SELECT ja.id as application_id, ja.road_test_date as scheduled_date, ja.road_test_location as location, ja.road_test_notes as notes, ja.road_test_result as result,
               COALESCE(jp.title, 'General Application') as job_title,
               COALESCE(d.department_name, 'N/A') as department_name
        FROM job_applications ja
        LEFT JOIN job_postings jp ON ja.job_posting_id = jp.id
        LEFT JOIN departments d ON jp.department_id = d.id
        WHERE ja.email = ? AND ja.road_test_date IS NOT NULL
        ORDER BY ja.road_test_date DESC
    ", [$user_email]);
} catch (Exception $e) {
    $road_tests = [];
}

$upcoming = array_filter($road_tests, fn($r) => !empty($r['scheduled_date']) && strtotime($r['scheduled_date']) >= strtotime('today'));
$past = array_filter($road_tests, fn($r) => !empty($r['scheduled_date']) && strtotime($r['scheduled_date']) < strtotime('today'));
?>
<div data-page-title="Road Test Information">
<style>
        .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
        .header h1 { font-size: 1.5rem; color: #e2e8f0; }
        .section-title { font-size: 1.2rem; color: #e2e8f0; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .road-tests-grid { display: grid; gap: 1rem; margin-bottom: 2rem; }
        .road-test-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); }
        .road-test-card.upcoming { border-left: 4px solid #ec4899; }
        .road-test-card.past { opacity: 0.7; }
        .card-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
        .card-title { font-size: 1.1rem; color: #e2e8f0; font-weight: 600; margin-bottom: 0.35rem; }
        .card-subtitle { color: #94a3b8; font-size: 0.85rem; }
        .status-badge { padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-badge.scheduled { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .status-badge.completed { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .status-badge.passed { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .status-badge.failed { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; padding-top: 1rem; border-top: 1px solid rgba(58, 69, 84, 0.3); }
        .detail-item { display: flex; align-items: center; gap: 0.5rem; }
        .detail-item i { width: 1.25rem; height: 1.25rem; color: #ec4899; }
        .detail-label { color: #94a3b8; font-size: 0.8rem; }
        .detail-value { color: #e2e8f0; font-size: 0.9rem; }
        .info-card { background: rgba(236, 72, 153, 0.1); border: 1px solid rgba(236, 72, 153, 0.3); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
        .info-card h3 { color: #ec4899; font-size: 1rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem; }
        .info-card ul { list-style: none; color: #cbd5e1; font-size: 0.9rem; }
        .info-card li { padding: 0.35rem 0; display: flex; align-items: center; gap: 0.5rem; }
        .info-card li::before { content: "✓"; color: #ec4899; font-weight: bold; }
        .empty-state { text-align: center; padding: 3rem 2rem; color: #94a3b8; background: rgba(30, 41, 54, 0.4); border-radius: 12px; margin-bottom: 2rem; }
        .empty-state i { width: 4rem; height: 4rem; color: #475569; margin-bottom: 0.75rem; }
    </style>
            <div class="header">
                <h1>Road Test Information</h1>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                </div>
            </div>

            <div class="info-card">
                <h3><i data-lucide="info"></i>Road Test Requirements</h3>
                <ul>
                    <li>Bring a valid driver's license</li>
                    <li>Arrive at least 15 minutes before your scheduled time</li>
                    <li>Wear appropriate closed-toe shoes</li>
                    <li>Ensure you have had adequate rest before the test</li>
                    <li>Bring any required documentation as specified</li>
                </ul>
            </div>

            <h2 class="section-title"><i data-lucide="calendar-clock"></i>Upcoming Road Tests</h2>
            <?php if (empty($upcoming)): ?>
            <div class="empty-state">
                <i data-lucide="car"></i>
                <h3>No Scheduled Road Tests</h3>
                <p>You don't have any road tests scheduled at the moment.</p>
            </div>
            <?php else: ?>
            <div class="road-tests-grid">
                <?php foreach ($upcoming as $test): ?>
                <div class="road-test-card upcoming">
                    <div class="card-header">
                        <div>
                            <div class="card-title"><?php echo htmlspecialchars($test['job_title']); ?></div>
                            <div class="card-subtitle"><?php echo htmlspecialchars($test['department_name']); ?></div>
                        </div>
                        <span class="status-badge scheduled">Scheduled</span>
                    </div>
                    <div class="details-grid">
                        <div class="detail-item">
                            <i data-lucide="calendar"></i>
                            <div><div class="detail-label">Date</div><div class="detail-value"><?php echo date('F d, Y', strtotime($test['scheduled_date'])); ?></div></div>
                        </div>
                        <div class="detail-item">
                            <i data-lucide="clock"></i>
                            <div><div class="detail-label">Time</div><div class="detail-value"><?php echo date('h:i A', strtotime($test['scheduled_date'])); ?></div></div>
                        </div>
                        <div class="detail-item">
                            <i data-lucide="map-pin"></i>
                            <div><div class="detail-label">Location</div><div class="detail-value"><?php echo htmlspecialchars($test['location'] ?: 'To be confirmed'); ?></div></div>
                        </div>
                        <div class="detail-item">
                            <i data-lucide="truck"></i>
                            <div><div class="detail-label">Vehicle Type</div><div class="detail-value"><?php echo htmlspecialchars($test['vehicle_type'] ?: 'Standard'); ?></div></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <h2 class="section-title"><i data-lucide="history"></i>Past Road Tests</h2>
            <?php if (empty($past)): ?>
            <div class="empty-state">
                <i data-lucide="calendar-check"></i>
                <h3>No Past Road Tests</h3>
                <p>Your road test history will appear here.</p>
            </div>
            <?php else: ?>
            <div class="road-tests-grid">
                <?php foreach ($past as $test): ?>
                <div class="road-test-card past">
                    <div class="card-header">
                        <div>
                            <div class="card-title"><?php echo htmlspecialchars($test['job_title']); ?></div>
                            <div class="card-subtitle"><?php echo date('F d, Y', strtotime($test['scheduled_date'])); ?></div>
                        </div>
                        <span class="status-badge <?php echo strtolower($test['result'] ?? 'completed'); ?>"><?php echo ucfirst($test['result'] ?? 'Completed'); ?></span>
                    </div>
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
