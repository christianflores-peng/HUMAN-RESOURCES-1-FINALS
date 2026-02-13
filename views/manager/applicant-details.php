<?php
require_once '../../includes/session_helper.php';
require_once '../../includes/spa_helper.php';
startSecureSession();
$is_ajax = is_spa_ajax();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_type'], ['Manager', 'HR_Staff', 'Admin'])) {
    header('Location: ../../index.php');
    exit();
}

if (!$is_ajax) {
    $id = isset($_GET['id']) ? '&id=' . urlencode($_GET['id']) : '';
    header('Location: index.php?page=applicant-details' . $id);
    exit();
}

require_once '../../database/config.php';
require_once '../../includes/workflow_helper.php';

$application_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    switch ($action) {
        case 'move_to_screening':
            changeApplicationStatus($application_id, 'screening', $user_id, 'Moved to screening');
            header("Location: applicant-details.php?id=$application_id&success=moved_screening");
            exit();
        case 'schedule_interview':
            scheduleInterview($application_id, $_POST['interview_type'], $_POST['interview_date'], $_POST['interview_location'] ?? '', $_POST['meeting_link'] ?? '', $user_id);
            header("Location: applicant-details.php?id=$application_id&success=interview_scheduled");
            exit();
        case 'schedule_road_test':
            scheduleRoadTest($application_id, $_POST['road_test_date'], $_POST['road_test_location'], $_POST['venue_details'] ?? '', $user_id);
            header("Location: applicant-details.php?id=$application_id&success=road_test_scheduled");
            exit();
        case 'send_offer':
            sendJobOffer($application_id, $_POST['position_title'], $_POST['department_id'], $_POST['salary'], $_POST['employment_type'], $_POST['start_date'], $_POST['benefits'] ?? '', $user_id);
            header("Location: applicant-details.php?id=$application_id&success=offer_sent");
            exit();
        case 'hire_applicant':
            hireApplicant($application_id, $user_id);
            header("Location: applicant-details.php?id=$application_id&success=hired");
            exit();
        case 'reject_applicant':
            rejectApplicant($application_id, $user_id, $_POST['rejection_reason']);
            header("Location: applicant-details.php?id=$application_id&success=rejected");
            exit();
    }
}

$application = fetchSingle("
    SELECT ja.*, COALESCE(jp.title, 'General Application') as job_title, COALESCE(jp.description, 'No specific job description') as job_description,
           COALESCE(jp.location, 'N/A') as location, COALESCE(jp.employment_type, 'To Be Determined') as employment_type, jp.salary_min, jp.salary_max,
           COALESCE(jp.department_id, 0) as department_id, COALESCE(d.department_name, 'Not Assigned') as department_name,
           isch.scheduled_date as interview_date, isch.location as interview_location, isch.meeting_link, isch.interview_type, isch.status as interview_status,
           rts.scheduled_date as road_test_date, rts.location as road_test_location, rts.venue_details as road_test_venue, rts.test_result as road_test_result,
           jo.salary_offered, jo.start_date as offer_start_date, jo.benefits, jo.status as offer_status
    FROM job_applications ja
    LEFT JOIN job_postings jp ON jp.id = ja.job_posting_id
    LEFT JOIN departments d ON d.id = jp.department_id
    LEFT JOIN interview_schedules isch ON isch.application_id = ja.id
    LEFT JOIN road_test_schedules rts ON rts.application_id = ja.id
    LEFT JOIN job_offers jo ON jo.application_id = ja.id
    WHERE ja.id = ?
", [$application_id]);

if (!$application) { header('Location: dashboard.php'); exit(); }

$status_history = fetchAll("SELECT ash.*, ua.first_name, ua.last_name FROM application_status_history ash LEFT JOIN user_accounts ua ON ua.id = ash.changed_by WHERE ash.application_id = ? ORDER BY ash.changed_at DESC", [$application_id]);
$departments = fetchAll("SELECT id, department_name FROM departments ORDER BY department_name");

$success_messages = ['moved_screening' => 'Applicant moved to screening stage', 'interview_scheduled' => 'Interview scheduled successfully', 'road_test_scheduled' => 'Road test scheduled successfully', 'offer_sent' => 'Job offer sent successfully', 'hired' => 'Applicant hired successfully!', 'rejected' => 'Applicant rejected'];
$success_message = isset($_GET['success']) ? ($success_messages[$_GET['success']] ?? '') : '';
?>
<div data-page-title="Applicant Details - <?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?>">
<style>
        .container { max-width: 1400px; margin: 0 auto; }
        .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5rem; color: #e2e8f0; }
        .btn { padding: 0.65rem 1.25rem; border: none; border-radius: 6px; font-size: 0.9rem; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-secondary { background: rgba(100, 116, 139, 0.3); color: #cbd5e1; }
        .btn-secondary:hover { background: rgba(100, 116, 139, 0.5); }
        .btn-primary { background: #0ea5e9; color: white; }
        .btn-primary:hover { background: #0284c7; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; }
        .card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1.5rem; }
        .card h2 { font-size: 1.2rem; color: #e2e8f0; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .card h2 i[data-lucide] { color: #0ea5e9; width: 1.2rem; height: 1.2rem; }
        .status-badge { display: inline-block; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; margin-bottom: 1rem; }
        .status-badge.new { background: rgba(99, 102, 241, 0.2); color: #6366f1; }
        .status-badge.screening { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .status-badge.interview { background: rgba(139, 92, 246, 0.2); color: #8b5cf6; }
        .status-badge.road_test { background: rgba(236, 72, 153, 0.2); color: #ec4899; }
        .status-badge.offer_sent { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .status-badge.hired { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .status-badge.rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .info-row { display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid rgba(58, 69, 84, 0.3); }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #94a3b8; font-size: 0.9rem; }
        .info-value { color: #e2e8f0; font-size: 0.9rem; font-weight: 500; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.75rem; }
        .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); color: #10b981; }
        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; color: #cbd5e1; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .form-input, .form-select, .form-textarea { width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(58, 69, 84, 0.5); border-radius: 6px; color: #e2e8f0; font-size: 0.9rem; }
        .form-textarea { min-height: 100px; resize: vertical; }
        .action-buttons { display: flex; gap: 0.75rem; flex-wrap: wrap; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: rgba(30, 41, 54, 0.95); border-radius: 12px; padding: 2rem; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; border: 1px solid rgba(58, 69, 84, 0.5); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-header h3 { color: #e2e8f0; font-size: 1.2rem; }
        .modal-close { background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1.5rem; }
        .timeline { position: relative; padding-left: 2rem; }
        .timeline-item { position: relative; padding-bottom: 1.5rem; }
        .timeline-item:before { content: ''; position: absolute; left: -2rem; top: 0; width: 12px; height: 12px; border-radius: 50%; background: #0ea5e9; border: 3px solid rgba(30, 41, 54, 0.6); }
        .timeline-item:after { content: ''; position: absolute; left: -1.7rem; top: 12px; width: 2px; height: calc(100% - 12px); background: rgba(58, 69, 84, 0.5); }
        .timeline-item:last-child:after { display: none; }
        .timeline-date { color: #94a3b8; font-size: 0.8rem; margin-bottom: 0.25rem; }
        .timeline-content { color: #e2e8f0; font-size: 0.9rem; }
        @media (max-width: 1024px) { .content-grid { grid-template-columns: 1fr; } }
    </style>
    <div class="container">
        <div class="header">
            <h1>Applicant Details</h1>
            <div class="header-actions">
                <?php include '../../includes/header-notifications.php'; ?>
                <a href="#" data-page="dashboard" class="btn btn-secondary"><i data-lucide="arrow-left"></i>Back to Dashboard</a>
            </div>
        </div>

        <?php if ($success_message): ?>
        <div class="alert alert-success"><i data-lucide="check-circle"></i><span><?php echo htmlspecialchars($success_message); ?></span></div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="main-content">
                <div class="card">
                    <h2><i data-lucide="user"></i>Applicant Information</h2>
                    <span class="status-badge <?php echo strtolower($application['status']); ?>"><?php echo ucfirst(str_replace('_', ' ', $application['status'])); ?></span>
                    <div class="info-row"><span class="info-label">Full Name</span><span class="info-value"><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></span></div>
                    <div class="info-row"><span class="info-label">Email</span><span class="info-value"><?php echo htmlspecialchars($application['email']); ?></span></div>
                    <div class="info-row"><span class="info-label">Phone</span><span class="info-value"><?php echo htmlspecialchars($application['phone'] ?? 'N/A'); ?></span></div>
                    <div class="info-row"><span class="info-label">Position Applied</span><span class="info-value"><?php echo htmlspecialchars($application['job_title']); ?></span></div>
                    <div class="info-row"><span class="info-label">Department</span><span class="info-value"><?php echo htmlspecialchars($application['department_name']); ?></span></div>
                    <div class="info-row"><span class="info-label">Applied Date</span><span class="info-value"><?php echo date('M d, Y', strtotime($application['applied_date'])); ?></span></div>
                    <?php if ($application['resume_path']): ?>
                    <div class="info-row"><span class="info-label">Resume</span><span class="info-value"><a href="../../<?php echo htmlspecialchars($application['resume_path']); ?>" target="_blank" style="color: #0ea5e9;">View Resume</a></span></div>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h2><i data-lucide="settings"></i>Actions</h2>
                    <div class="action-buttons">
                        <?php if ($application['status'] === 'new'): ?>
                        <form method="POST" style="display: inline;"><input type="hidden" name="action" value="move_to_screening"><button type="submit" class="btn btn-primary"><i data-lucide="arrow-right"></i>Move to Screening</button></form>
                        <?php endif; ?>
                        <?php if ($application['status'] === 'screening'): ?>
                        <button onclick="openModal('interviewModal')" class="btn btn-primary"><i data-lucide="calendar"></i>Schedule Interview</button>
                        <?php endif; ?>
                        <?php if ($application['status'] === 'interview'): ?>
                        <button onclick="openModal('roadTestModal')" class="btn btn-primary"><i data-lucide="car"></i>Schedule Road Test</button>
                        <?php endif; ?>
                        <?php if ($application['status'] === 'road_test'): ?>
                        <button onclick="openModal('offerModal')" class="btn btn-success"><i data-lucide="mail"></i>Send Job Offer</button>
                        <?php endif; ?>
                        <?php if ($application['status'] === 'offer_sent'): ?>
                        <form method="POST" style="display: inline;"><input type="hidden" name="action" value="hire_applicant"><button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to hire this applicant?')"><i data-lucide="check-circle"></i>Hire Applicant</button></form>
                        <?php endif; ?>
                        <?php if (!in_array($application['status'], ['hired', 'rejected'])): ?>
                        <button onclick="openModal('rejectModal')" class="btn btn-danger"><i data-lucide="x-circle"></i>Reject</button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($status_history)): ?>
                <div class="card">
                    <h2><i data-lucide="history"></i>Status History</h2>
                    <div class="timeline">
                        <?php foreach ($status_history as $history): ?>
                        <div class="timeline-item">
                            <div class="timeline-date"><?php echo date('M d, Y - h:i A', strtotime($history['changed_at'])); ?></div>
                            <div class="timeline-content">Status changed to: <strong><?php echo ucfirst(str_replace('_', ' ', $history['new_status'])); ?></strong>
                                <?php if ($history['first_name']): ?><br><span style="color: #0ea5e9; font-size: 0.85rem;">by <?php echo htmlspecialchars($history['first_name'] . ' ' . $history['last_name']); ?></span><?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="sidebar-content">
                <div class="card">
                    <h2><i data-lucide="info"></i>Quick Info</h2>
                    <div class="info-row"><span class="info-label">Application ID</span><span class="info-value">#<?php echo $application['id']; ?></span></div>
                    <div class="info-row"><span class="info-label">Current Stage</span><span class="info-value"><?php echo ucfirst(str_replace('_', ' ', $application['status'])); ?></span></div>
                    <?php if ($application['interview_date']): ?>
                    <div class="info-row"><span class="info-label">Interview Date</span><span class="info-value"><?php echo date('M d, Y', strtotime($application['interview_date'])); ?></span></div>
                    <?php endif; ?>
                    <?php if ($application['road_test_date']): ?>
                    <div class="info-row"><span class="info-label">Road Test Date</span><span class="info-value"><?php echo date('M d, Y', strtotime($application['road_test_date'])); ?></span></div>
                    <?php endif; ?>
                    <?php if ($application['employee_id_assigned']): ?>
                    <div class="info-row"><span class="info-label">Employee ID</span><span class="info-value"><?php echo htmlspecialchars($application['employee_id_assigned']); ?></span></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div id="interviewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3>Schedule Interview</h3><button class="modal-close" onclick="closeModal('interviewModal')">&times;</button></div>
            <form method="POST">
                <input type="hidden" name="action" value="schedule_interview">
                <div class="form-group"><label class="form-label">Interview Type</label><select name="interview_type" class="form-select" required><option value="face_to_face">Face to Face</option><option value="online">Online</option><option value="phone">Phone</option></select></div>
                <div class="form-group"><label class="form-label">Date & Time</label><input type="datetime-local" name="interview_date" class="form-input" required></div>
                <div class="form-group"><label class="form-label">Location</label><input type="text" name="interview_location" class="form-input" placeholder="Office address or venue"></div>
                <div class="form-group"><label class="form-label">Meeting Link (for Online)</label><input type="url" name="meeting_link" class="form-input" placeholder="https://meet.google.com/..."></div>
                <div class="action-buttons"><button type="submit" class="btn btn-primary">Schedule Interview</button><button type="button" class="btn btn-secondary" onclick="closeModal('interviewModal')">Cancel</button></div>
            </form>
        </div>
    </div>

    <div id="roadTestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3>Schedule Road Test</h3><button class="modal-close" onclick="closeModal('roadTestModal')">&times;</button></div>
            <form method="POST">
                <input type="hidden" name="action" value="schedule_road_test">
                <div class="form-group"><label class="form-label">Date & Time</label><input type="datetime-local" name="road_test_date" class="form-input" required></div>
                <div class="form-group"><label class="form-label">Location</label><input type="text" name="road_test_location" class="form-input" required placeholder="Test venue address"></div>
                <div class="form-group"><label class="form-label">Venue Details</label><textarea name="venue_details" class="form-textarea" placeholder="Additional instructions..."></textarea></div>
                <div class="action-buttons"><button type="submit" class="btn btn-primary">Schedule Road Test</button><button type="button" class="btn btn-secondary" onclick="closeModal('roadTestModal')">Cancel</button></div>
            </form>
        </div>
    </div>

    <div id="offerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3>Send Job Offer</h3><button class="modal-close" onclick="closeModal('offerModal')">&times;</button></div>
            <form method="POST">
                <input type="hidden" name="action" value="send_offer">
                <div class="form-group"><label class="form-label">Position Title</label><input type="text" name="position_title" class="form-input" value="<?php echo htmlspecialchars($application['job_title']); ?>" required></div>
                <div class="form-group"><label class="form-label">Department</label><select name="department_id" class="form-select" required><?php foreach ($departments as $dept): ?><option value="<?php echo $dept['id']; ?>" <?php echo $dept['id'] == $application['department_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['department_name']); ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label class="form-label">Salary Offered (₱)</label><input type="number" name="salary" class="form-input" step="0.01" required></div>
                <div class="form-group"><label class="form-label">Employment Type</label><select name="employment_type" class="form-select" required><option value="Full-time">Full-time</option><option value="Part-time">Part-time</option><option value="Contract">Contract</option></select></div>
                <div class="form-group"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-input" required></div>
                <div class="form-group"><label class="form-label">Benefits</label><textarea name="benefits" class="form-textarea" placeholder="Health insurance, vacation days, etc."></textarea></div>
                <div class="action-buttons"><button type="submit" class="btn btn-success">Send Offer</button><button type="button" class="btn btn-secondary" onclick="closeModal('offerModal')">Cancel</button></div>
            </form>
        </div>
    </div>

    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3>Reject Applicant</h3><button class="modal-close" onclick="closeModal('rejectModal')">&times;</button></div>
            <form method="POST">
                <input type="hidden" name="action" value="reject_applicant">
                <div class="form-group"><label class="form-label">Rejection Reason</label><textarea name="rejection_reason" class="form-textarea" required placeholder="Please provide a reason..."></textarea></div>
                <div class="action-buttons"><button type="submit" class="btn btn-danger">Confirm Rejection</button><button type="button" class="btn btn-secondary" onclick="closeModal('rejectModal')">Cancel</button></div>
            </form>
        </div>
    </div>

<script>
    function openModal(id) { document.getElementById(id).classList.add('active'); }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); }
    document.querySelectorAll('.modal').forEach(modal => { modal.addEventListener('click', e => { if (e.target === modal) modal.classList.remove('active'); }); });
    
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
</div>
