<?php
require_once '../../includes/session_helper.php';
require_once '../../includes/spa_helper.php';
startSecureSession();
$is_ajax = is_spa_ajax();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Employee') {
    header('Location: ../../index.php');
    exit();
}

if (!$is_ajax) {
    header('Location: index.php?page=profile');
    exit();
}

require_once '../../database/config.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

$employee = fetchSingle("
    SELECT ua.*, ua.first_name, ua.last_name, ua.personal_email, ua.phone,
           d.department_name, ua.job_title AS position_name
    FROM user_accounts ua
    LEFT JOIN departments d ON d.id = ua.department_id
    WHERE ua.id = ?
", [$user_id]);

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone_number'] ?? '';
    
    try {
        executeQuery("UPDATE user_accounts SET phone = ? WHERE id = ?", [$phone, $user_id]);
        $success_message = "Profile updated successfully!";
        $employee = fetchSingle("
            SELECT ua.*, ua.first_name, ua.last_name, ua.personal_email, ua.phone,
                   d.department_name, ua.job_title AS position_name
            FROM user_accounts ua
            LEFT JOIN departments d ON d.id = ua.department_id
            WHERE ua.id = ?
        ", [$user_id]);
    } catch (Exception $e) {
        $error_message = "Failed to update profile.";
    }
}
?>
<div data-page-title="My Profile">
<style>
        .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5rem; color: #e2e8f0; }
        .card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1.5rem; }
        .card h2 { font-size: 1.1rem; color: #e2e8f0; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group.full-width { grid-column: span 2; }
        .form-group label { display: block; color: #94a3b8; font-size: 0.85rem; margin-bottom: 0.5rem; }
        .form-group input { width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(58, 69, 84, 0.5); border-radius: 8px; color: #e2e8f0; font-size: 0.9rem; }
        .form-group input:focus { outline: none; border-color: #0ea5e9; }
        .form-group input:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-size: 0.9rem; cursor: pointer; transition: all 0.3s; }
        .btn-primary { background: #0ea5e9; color: white; }
        .btn-primary:hover { background: #0284c7; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .alert-success { background: rgba(34, 197, 94, 0.2); border: 1px solid rgba(34, 197, 94, 0.3); color: #22c55e; }
        .alert-error { background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; }
        .info-row { display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid rgba(58, 69, 84, 0.3); }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #94a3b8; }
        .info-value { color: #e2e8f0; font-weight: 500; }
    </style>
            <div class="header">
                <div><h1>My Profile</h1></div>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                </div>
            </div>

            <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="card">
                <h2><i data-lucide="badge-check"></i>Employment Information</h2>
                <div class="info-row"><span class="info-label">Employee ID</span><span class="info-value"><?php echo htmlspecialchars($employee['employee_id'] ?? 'N/A'); ?></span></div>
                <div class="info-row"><span class="info-label">Department</span><span class="info-value"><?php echo htmlspecialchars($employee['department_name'] ?? 'N/A'); ?></span></div>
                <div class="info-row"><span class="info-label">Position</span><span class="info-value"><?php echo htmlspecialchars($employee['position_name'] ?? 'N/A'); ?></span></div>
                <div class="info-row"><span class="info-label">Start Date</span><span class="info-value"><?php echo $employee['hire_date'] ? date('F d, Y', strtotime($employee['hire_date'])) : 'N/A'; ?></span></div>
            </div>

            <div class="card">
                <h2><i data-lucide="edit"></i>Edit Contact Information</h2>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($employee['first_name'] ?? ''); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($employee['last_name'] ?? ''); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?php echo htmlspecialchars($employee['personal_email'] ?? ''); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone_number" value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Emergency Contact Name</label>
                            <input type="text" name="emergency_contact_name" value="" placeholder="Not yet available">
                        </div>
                        <div class="form-group">
                            <label>Emergency Contact Phone</label>
                            <input type="text" name="emergency_contact_phone" value="" placeholder="Not yet available">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
</div>
