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
    header('Location: index.php?page=profile');
    exit();
}

require_once '../../database/config.php';

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    if (empty($first_name) || empty($last_name)) {
        $error_message = 'First name and last name are required.';
    } elseif (!empty($phone) && !preg_match('/^[0-9]{10,11}$/', $phone)) {
        $error_message = 'Please enter a valid phone number (10-11 digits).';
    } else {
        try {
            $updated = updateRecord(
                "UPDATE user_accounts SET first_name = ?, last_name = ?, phone = ?, address = ? WHERE id = ?",
                [$first_name, $last_name, $phone, $address, $user_id]
            );
            if ($updated) {
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $success_message = 'Profile updated successfully!';
            } else {
                $error_message = 'Failed to update profile.';
            }
        } catch (Exception $e) {
            $error_message = 'Error: ' . $e->getMessage();
        }
    }
}

$profile = fetchSingle("SELECT * FROM user_accounts WHERE id = ?", [$user_id]);

try {
    $applicant_profile = fetchSingle("SELECT * FROM applicant_profiles WHERE user_id = ?", [$user_id]);
} catch (Exception $e) {
    $applicant_profile = null;
}
?>
<div data-page-title="My Profile">
<style>
        .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
        .header h1 { font-size: 1.5rem; color: #e2e8f0; }
        .card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1.5rem; }
        .card h2 { font-size: 1.2rem; color: #e2e8f0; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; }
        .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); color: #10b981; }
        .alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-label { display: block; color: #cbd5e1; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .form-input, .form-textarea { width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(58, 69, 84, 0.5); border-radius: 6px; color: #e2e8f0; font-size: 0.9rem; }
        .form-input:focus, .form-textarea:focus { outline: none; border-color: #0ea5e9; }
        .form-textarea { min-height: 100px; resize: vertical; }
        .btn { padding: 0.65rem 1.25rem; border: none; border-radius: 6px; font-size: 0.9rem; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: #0ea5e9; color: white; }
        .btn-primary:hover { background: #0284c7; }
        .info-row { display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid rgba(58, 69, 84, 0.3); }
        .info-label { color: #94a3b8; font-size: 0.9rem; }
        .info-value { color: #e2e8f0; font-size: 0.9rem; }
    </style>
            <div class="header">
                <h1>My Profile</h1>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                </div>
            </div>

            <?php if ($success_message): ?>
            <div class="alert alert-success"><i data-lucide="check-circle"></i><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
            <div class="alert alert-error"><i data-lucide="alert-circle"></i><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="card">
                <h2><i data-lucide="edit"></i>Edit Personal Information</h2>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-input" value="<?php echo htmlspecialchars($profile['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-input" value="<?php echo htmlspecialchars($profile['last_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-input" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>" placeholder="09123456789">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email (Cannot be changed)</label>
                            <input type="email" class="form-input" value="<?php echo htmlspecialchars($profile['personal_email']); ?>" disabled style="opacity: 0.6;">
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-textarea"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i data-lucide="save"></i>Save Changes</button>
                </form>
            </div>

            <div class="card">
                <h2><i data-lucide="file-text"></i>Application Documents</h2>
                <?php if ($applicant_profile): ?>
                <div class="info-row">
                    <span class="info-label">Resume</span>
                    <span class="info-value"><?php if ($applicant_profile['resume_path']): ?><a href="../../<?php echo htmlspecialchars($applicant_profile['resume_path']); ?>" target="_blank" style="color: #0ea5e9;">View Resume</a><?php else: ?>Not uploaded<?php endif; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Cover Letter</span>
                    <span class="info-value"><?php echo $applicant_profile['cover_letter'] ? 'Submitted' : 'Not submitted'; ?></span>
                </div>
                <?php else: ?>
                <p style="color: #94a3b8;">No documents uploaded yet.</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2><i data-lucide="info"></i>Account Information</h2>
                <div class="info-row">
                    <span class="info-label">Account Status</span>
                    <span class="info-value" style="color: <?php echo $profile['status'] === 'Active' ? '#10b981' : '#f59e0b'; ?>;"><?php echo htmlspecialchars($profile['status']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Member Since</span>
                    <span class="info-value"><?php echo date('F d, Y', strtotime($profile['created_at'])); ?></span>
                </div>
            </div>
<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
</div>
