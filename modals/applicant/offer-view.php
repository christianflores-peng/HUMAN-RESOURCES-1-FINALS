<?php
require_once '../../includes/session_helper.php';
startSecureSession();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Applicant') {
    header('Location: ../../login.php');
    exit();
}

require_once '../../database/config.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$user_email = $_SESSION['personal_email'] ?? '';

$application_id = $_GET['id'] ?? 0;

// Fetch application with offer details
$application = fetchSingle("
    SELECT ja.*, 
           COALESCE(jp.title, 'General Application') as job_title,
           COALESCE(jp.location, 'N/A') as location,
           COALESCE(d.department_name, 'Not Assigned') as department_name,
           jo.id as offer_id, jo.position_title, jo.salary_offered, jo.employment_type as offer_employment_type,
           jo.start_date, jo.benefits, jo.status as offer_status, jo.created_at as offer_date,
           jo.accepted_at, jo.rejected_at
    FROM job_applications ja
    LEFT JOIN job_postings jp ON jp.id = ja.job_posting_id
    LEFT JOIN departments d ON d.id = jp.department_id
    LEFT JOIN job_offers jo ON jo.application_id = ja.id
    WHERE ja.id = ? AND ja.email = ?
", [$application_id, $user_email]);

if (!$application || !$application['offer_id']) {
    header('Location: applications.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle offer response
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'accept_offer') {
        try {
            executeQuery("UPDATE job_offers SET status = 'accepted', accepted_at = NOW() WHERE id = ?", [$application['offer_id']]);
            $success_message = "Congratulations! You have accepted the job offer. HR will contact you shortly with next steps.";
            
            // Refresh data
            $application['offer_status'] = 'accepted';
            $application['accepted_at'] = date('Y-m-d H:i:s');
        } catch (Exception $e) {
            $error_message = "Failed to accept offer. Please try again.";
        }
    } elseif ($action === 'reject_offer') {
        $reason = $_POST['rejection_reason'] ?? '';
        try {
            executeQuery("UPDATE job_offers SET status = 'rejected', rejected_at = NOW(), rejection_reason = ? WHERE id = ?", [$reason, $application['offer_id']]);
            executeQuery("UPDATE job_applications SET status = 'rejected' WHERE id = ?", [$application_id]);
            $success_message = "You have declined the job offer. Thank you for your interest.";
            
            $application['offer_status'] = 'rejected';
        } catch (Exception $e) {
            $error_message = "Failed to process your response. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Offer - HR1</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #0a1929 0%, #1a2942 100%); min-height: 100vh; color: #f8fafc; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: rgba(15, 23, 42, 0.95); border-right: 1px solid rgba(58, 69, 84, 0.5); padding: 1.5rem 0; position: fixed; height: 100vh; overflow-y: auto; }
        .logo-section { padding: 0 1.5rem 1.5rem; border-bottom: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1.5rem; }
        .logo-section img { width: 60px; margin-bottom: 0.5rem; }
        .logo-section h2 { font-size: 1.1rem; color: #0ea5e9; margin-bottom: 0.25rem; }
        .logo-section p { font-size: 0.75rem; color: #94a3b8; }
        .nav-menu { list-style: none; }
        .nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; color: #cbd5e1; text-decoration: none; transition: all 0.3s; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(14, 165, 233, 0.1); color: #0ea5e9; border-left: 3px solid #0ea5e9; }
        .main-content { flex: 1; margin-left: 260px; padding: 2rem; }
        .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5rem; color: #e2e8f0; }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .user-avatar { width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, #0ea5e9, #10b981); display: flex; align-items: center; justify-content: center; font-weight: 600; }
        
        .offer-card { background: rgba(30, 41, 54, 0.6); border-radius: 16px; padding: 2rem; border: 1px solid rgba(58, 69, 84, 0.5); max-width: 800px; margin: 0 auto; }
        .offer-header { text-align: center; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid rgba(58, 69, 84, 0.5); }
        .offer-icon { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #10b981, #059669); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
        .offer-icon .material-symbols-outlined { font-size: 2.5rem; color: white; }
        .offer-header h2 { font-size: 1.75rem; color: #10b981; margin-bottom: 0.5rem; }
        .offer-header p { color: #94a3b8; }
        
        .offer-details { margin-bottom: 2rem; }
        .detail-row { display: flex; justify-content: space-between; padding: 1rem; border-bottom: 1px solid rgba(58, 69, 84, 0.3); }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #94a3b8; display: flex; align-items: center; gap: 0.5rem; }
        .detail-label .material-symbols-outlined { font-size: 1.2rem; color: #0ea5e9; }
        .detail-value { color: #e2e8f0; font-weight: 600; text-align: right; }
        .salary-value { font-size: 1.5rem; color: #10b981; }
        
        .benefits-section { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
        .benefits-section h3 { color: #10b981; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .benefits-list { color: #cbd5e1; line-height: 1.8; white-space: pre-line; }
        
        .offer-actions { display: flex; gap: 1rem; justify-content: center; }
        .btn { padding: 0.85rem 2rem; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 600; }
        .btn-accept { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .btn-accept:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4); }
        .btn-decline { background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
        .btn-decline:hover { background: rgba(239, 68, 68, 0.3); }
        .btn-secondary { background: rgba(100, 116, 139, 0.3); color: #cbd5e1; }
        
        .status-badge { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; margin-top: 1rem; }
        .status-badge.accepted { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .status-badge.rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .status-badge.pending { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        
        .alert { padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; }
        .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); color: #10b981; }
        .alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: rgba(30, 41, 54, 0.95); border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%; border: 1px solid rgba(58, 69, 84, 0.5); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-header h3 { color: #e2e8f0; }
        .modal-close { background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; color: #cbd5e1; margin-bottom: 0.5rem; }
        .form-group textarea { width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(58, 69, 84, 0.5); border-radius: 8px; color: #e2e8f0; min-height: 100px; resize: vertical; }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .offer-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-section">
                <img src="../../assets/images/slate.png" alt="SLATE Logo">
                <h2>Applicant Portal</h2>
                <p><?php echo htmlspecialchars($user_name); ?></p>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><span class="material-symbols-outlined">dashboard</span>Dashboard</a></li>
                <li class="nav-item"><a href="applications.php" class="nav-link active"><span class="material-symbols-outlined">work</span>My Applications</a></li>
                <li class="nav-item"><a href="../../public/careers.php" class="nav-link"><span class="material-symbols-outlined">search</span>Browse Jobs</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link"><span class="material-symbols-outlined">person</span>My Profile</a></li>
                <li class="nav-item"><a href="notifications.php" class="nav-link"><span class="material-symbols-outlined">notifications</span>Notifications</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link"><span class="material-symbols-outlined">logout</span>Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <div>
                    <h1>Job Offer</h1>
                    <p style="color: #94a3b8;">Review your job offer details</p>
                </div>
                <div class="user-info">
                    <?php include '../../includes/header-notifications.php'; ?>
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?></div>
                </div>
            </div>

            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <span class="material-symbols-outlined">check_circle</span>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
            <div class="alert alert-error">
                <span class="material-symbols-outlined">error</span>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
            <?php endif; ?>

            <div class="offer-card">
                <div class="offer-header">
                    <div class="offer-icon">
                        <span class="material-symbols-outlined">celebration</span>
                    </div>
                    <h2>Congratulations!</h2>
                    <p>You have received a job offer from SLATE Freight Management</p>
                    
                    <?php if ($application['offer_status'] === 'accepted'): ?>
                    <span class="status-badge accepted"><span class="material-symbols-outlined">check_circle</span> Offer Accepted</span>
                    <?php elseif ($application['offer_status'] === 'rejected'): ?>
                    <span class="status-badge rejected"><span class="material-symbols-outlined">cancel</span> Offer Declined</span>
                    <?php else: ?>
                    <span class="status-badge pending"><span class="material-symbols-outlined">schedule</span> Pending Response</span>
                    <?php endif; ?>
                </div>

                <div class="offer-details">
                    <div class="detail-row">
                        <span class="detail-label"><span class="material-symbols-outlined">work</span> Position</span>
                        <span class="detail-value"><?php echo htmlspecialchars($application['position_title']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><span class="material-symbols-outlined">business</span> Department</span>
                        <span class="detail-value"><?php echo htmlspecialchars($application['department_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><span class="material-symbols-outlined">location_on</span> Location</span>
                        <span class="detail-value"><?php echo htmlspecialchars($application['location']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><span class="material-symbols-outlined">schedule</span> Employment Type</span>
                        <span class="detail-value"><?php echo htmlspecialchars($application['offer_employment_type']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><span class="material-symbols-outlined">payments</span> Salary Offered</span>
                        <span class="detail-value salary-value">₱<?php echo number_format($application['salary_offered'], 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><span class="material-symbols-outlined">event</span> Start Date</span>
                        <span class="detail-value"><?php echo date('F d, Y', strtotime($application['start_date'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><span class="material-symbols-outlined">mail</span> Offer Date</span>
                        <span class="detail-value"><?php echo date('F d, Y', strtotime($application['offer_date'])); ?></span>
                    </div>
                </div>

                <?php if ($application['benefits']): ?>
                <div class="benefits-section">
                    <h3><span class="material-symbols-outlined">redeem</span> Benefits Package</h3>
                    <div class="benefits-list"><?php echo nl2br(htmlspecialchars($application['benefits'])); ?></div>
                </div>
                <?php endif; ?>

                <?php if ($application['offer_status'] === 'pending'): ?>
                <div class="offer-actions">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="accept_offer">
                        <button type="submit" class="btn btn-accept" onclick="return confirm('Are you sure you want to accept this job offer?')">
                            <span class="material-symbols-outlined">check_circle</span>
                            Accept Offer
                        </button>
                    </form>
                    <button type="button" class="btn btn-decline" onclick="openDeclineModal()">
                        <span class="material-symbols-outlined">cancel</span>
                        Decline Offer
                    </button>
                </div>
                <?php elseif ($application['offer_status'] === 'accepted'): ?>
                <div style="text-align: center; padding: 1.5rem; background: rgba(16, 185, 129, 0.1); border-radius: 12px;">
                    <p style="color: #10b981; font-size: 1.1rem; margin-bottom: 0.5rem;">
                        <span class="material-symbols-outlined" style="vertical-align: middle;">verified</span>
                        You accepted this offer on <?php echo date('F d, Y', strtotime($application['accepted_at'])); ?>
                    </p>
                    <p style="color: #94a3b8;">HR will contact you shortly with onboarding instructions.</p>
                </div>
                <?php endif; ?>

                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="applications.php" class="btn btn-secondary">
                        <span class="material-symbols-outlined">arrow_back</span>
                        Back to Applications
                    </a>
                </div>
            </div>
        </main>
    </div>

    <!-- Decline Modal -->
    <div id="declineModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Decline Job Offer</h3>
                <button class="modal-close" onclick="closeDeclineModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="reject_offer">
                <div class="form-group">
                    <label>Reason for declining (optional)</label>
                    <textarea name="rejection_reason" placeholder="Please let us know why you're declining this offer..."></textarea>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeDeclineModal()">Cancel</button>
                    <button type="submit" class="btn btn-decline">Confirm Decline</button>
                </div>
            </form>
        </div>
    </div>

    <?php include '../../includes/logout-modal.php'; ?>
    
    <script>
        function openDeclineModal() {
            document.getElementById('declineModal').classList.add('active');
        }
        function closeDeclineModal() {
            document.getElementById('declineModal').classList.remove('active');
        }
        document.getElementById('declineModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeclineModal();
        });
    </script>
</body>
</html>
