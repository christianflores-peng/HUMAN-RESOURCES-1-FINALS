<?php
require_once '../../includes/session_helper.php';
startSecureSession();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Employee') {
    header('Location: ../../index.php');
    exit();
}

require_once '../../database/config.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

$employee = fetchSingle("
    SELECT e.*, u.first_name, u.last_name, u.personal_email, u.phone_number,
           d.department_name, p.position_name
    FROM employees e
    JOIN user_accounts u ON u.id = e.user_id
    LEFT JOIN departments d ON d.id = e.department_id
    LEFT JOIN positions p ON p.id = e.position_id
    WHERE e.user_id = ?
", [$user_id]);

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone_number'] ?? '';
    $emergency_name = $_POST['emergency_contact_name'] ?? '';
    $emergency_phone = $_POST['emergency_contact_phone'] ?? '';
    
    try {
        executeQuery("UPDATE user_accounts SET phone_number = ? WHERE id = ?", [$phone, $user_id]);
        executeQuery("UPDATE employees SET emergency_contact_name = ?, emergency_contact_phone = ? WHERE user_id = ?", 
            [$emergency_name, $emergency_phone, $user_id]);
        $success_message = "Profile updated successfully!";
        $employee = fetchSingle("
            SELECT e.*, u.first_name, u.last_name, u.personal_email, u.phone_number,
                   d.department_name, p.position_name
            FROM employees e
            JOIN user_accounts u ON u.id = e.user_id
            LEFT JOIN departments d ON d.id = e.department_id
            LEFT JOIN positions p ON p.id = e.position_id
            WHERE e.user_id = ?
        ", [$user_id]);
    } catch (Exception $e) {
        $error_message = "Failed to update profile.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - HR1</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #0a1929 0%, #1a2942 100%); min-height: 100vh; color: #f8fafc; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: rgba(15, 23, 42, 0.95); border-right: 1px solid rgba(58, 69, 84, 0.5); padding: 1.5rem 0; position: fixed; height: 100vh; overflow-y: auto; }
        .logo-section { padding: 0 1.5rem 1.5rem; border-bottom: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1.5rem; }
        .logo-section img { width: 60px; margin-bottom: 0.5rem; }
        .logo-section h2 { font-size: 1.1rem; color: #0ea5e9; }
        .logo-section p { font-size: 0.75rem; color: #94a3b8; }
        .nav-menu { list-style: none; }
        .nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; color: #cbd5e1; text-decoration: none; transition: all 0.3s; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(14, 165, 233, 0.1); color: #0ea5e9; border-left: 3px solid #0ea5e9; }
        .main-content { flex: 1; margin-left: 260px; padding: 2rem; }
        .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
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
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-section">
                <img src="../../assets/images/slate.png" alt="SLATE Logo">
                <h2>Employee Portal</h2>
                <p><?php echo htmlspecialchars($user_name); ?></p>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><span class="material-symbols-outlined">dashboard</span>Dashboard</a></li>
                <li class="nav-item"><a href="onboarding.php" class="nav-link"><span class="material-symbols-outlined">checklist</span>Onboarding</a></li>
                <li class="nav-item"><a href="requirements.php" class="nav-link"><span class="material-symbols-outlined">upload_file</span>Submit Requirements</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link active"><span class="material-symbols-outlined">person</span>My Profile</a></li>
                <li class="nav-item"><a href="documents.php" class="nav-link"><span class="material-symbols-outlined">folder</span>Documents</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link"><span class="material-symbols-outlined">logout</span>Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header"><h1>My Profile</h1></div>

            <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="card">
                <h2><span class="material-symbols-outlined">badge</span>Employment Information</h2>
                <div class="info-row"><span class="info-label">Employee ID</span><span class="info-value"><?php echo htmlspecialchars($employee['employee_id'] ?? 'N/A'); ?></span></div>
                <div class="info-row"><span class="info-label">Department</span><span class="info-value"><?php echo htmlspecialchars($employee['department_name'] ?? 'N/A'); ?></span></div>
                <div class="info-row"><span class="info-label">Position</span><span class="info-value"><?php echo htmlspecialchars($employee['position_name'] ?? 'N/A'); ?></span></div>
                <div class="info-row"><span class="info-label">Start Date</span><span class="info-value"><?php echo $employee['start_date'] ? date('F d, Y', strtotime($employee['start_date'])) : 'N/A'; ?></span></div>
            </div>

            <div class="card">
                <h2><span class="material-symbols-outlined">edit</span>Edit Contact Information</h2>
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
                            <input type="text" name="phone_number" value="<?php echo htmlspecialchars($employee['phone_number'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Emergency Contact Name</label>
                            <input type="text" name="emergency_contact_name" value="<?php echo htmlspecialchars($employee['emergency_contact_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Emergency Contact Phone</label>
                            <input type="text" name="emergency_contact_phone" value="<?php echo htmlspecialchars($employee['emergency_contact_phone'] ?? ''); ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </main>
    </div>
    <?php include '../../includes/logout-modal.php'; ?>
</body>
</html>
