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

$employee = fetchSingle("SELECT * FROM employees WHERE user_id = ?", [$user_id]);
$employee_id = $employee['id'] ?? 0;

// Define required documents
$required_documents = [
    ['type' => 'valid_id', 'name' => 'Valid Government ID', 'description' => 'Any valid government-issued ID (Passport, Driver\'s License, SSS, PhilHealth, etc.)', 'icon' => 'badge'],
    ['type' => 'nbi_clearance', 'name' => 'NBI Clearance', 'description' => 'National Bureau of Investigation clearance certificate', 'icon' => 'verified_user'],
    ['type' => 'barangay_clearance', 'name' => 'Barangay Clearance', 'description' => 'Clearance from your local barangay', 'icon' => 'location_city'],
    ['type' => 'birth_certificate', 'name' => 'Birth Certificate', 'description' => 'PSA-issued birth certificate', 'icon' => 'article'],
    ['type' => 'sss_id', 'name' => 'SSS ID/E1 Form', 'description' => 'Social Security System ID or E1 registration form', 'icon' => 'credit_card'],
    ['type' => 'philhealth_id', 'name' => 'PhilHealth ID/MDR', 'description' => 'PhilHealth ID or Member Data Record', 'icon' => 'health_and_safety'],
    ['type' => 'pagibig_id', 'name' => 'Pag-IBIG ID/MID', 'description' => 'Pag-IBIG Fund ID or Member ID number', 'icon' => 'home'],
    ['type' => 'tin_id', 'name' => 'TIN ID/Certificate', 'description' => 'Tax Identification Number ID or certificate', 'icon' => 'receipt_long'],
    ['type' => 'diploma', 'name' => 'Diploma/TOR', 'description' => 'Educational diploma or Transcript of Records', 'icon' => 'school'],
    ['type' => 'medical_cert', 'name' => 'Medical Certificate', 'description' => 'Recent medical certificate from a licensed physician', 'icon' => 'medical_information'],
    ['type' => 'drivers_license', 'name' => 'Driver\'s License', 'description' => 'Professional Driver\'s License (if applicable)', 'icon' => 'directions_car'],
    ['type' => '2x2_photo', 'name' => '2x2 ID Photo', 'description' => 'Recent 2x2 ID photo with white background', 'icon' => 'photo_camera']
];

// Fetch submitted requirements
$submitted = fetchAll("SELECT * FROM employee_requirements WHERE employee_id = ?", [$employee_id]);
$submitted_types = array_column($submitted, 'document_type');
$submitted_map = [];
foreach ($submitted as $s) {
    $submitted_map[$s['document_type']] = $s;
}

$success_message = '';
$error_message = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_requirement'])) {
    $document_type = $_POST['document_type'] ?? '';
    
    if (isset($_FILES['requirement_file']) && $_FILES['requirement_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['requirement_file'];
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $error_message = 'Invalid file type. Please upload PDF, JPG, or PNG files only.';
        } elseif ($file['size'] > $max_size) {
            $error_message = 'File size exceeds 5MB limit.';
        } else {
            $upload_dir = '../../uploads/requirements/' . $employee_id . '/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $document_type . '_' . time() . '.' . $extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $relative_path = 'uploads/requirements/' . $employee_id . '/' . $filename;
                
                // Check if already exists and update, otherwise insert
                $existing = fetchSingle("SELECT id FROM employee_requirements WHERE employee_id = ? AND document_type = ?", [$employee_id, $document_type]);
                
                if ($existing) {
                    executeQuery("UPDATE employee_requirements SET file_path = ?, status = 'pending', updated_at = NOW() WHERE id = ?", [$relative_path, $existing['id']]);
                } else {
                    insertRecord("INSERT INTO employee_requirements (employee_id, document_type, file_path, status, uploaded_at) VALUES (?, ?, ?, 'pending', NOW())", [$employee_id, $document_type, $relative_path]);
                }
                
                $success_message = 'Document uploaded successfully! Waiting for HR verification.';
                
                // Refresh submitted documents
                $submitted = fetchAll("SELECT * FROM employee_requirements WHERE employee_id = ?", [$employee_id]);
                $submitted_types = array_column($submitted, 'document_type');
                $submitted_map = [];
                foreach ($submitted as $s) {
                    $submitted_map[$s['document_type']] = $s;
                }
            } else {
                $error_message = 'Failed to upload file. Please try again.';
            }
        }
    } else {
        $error_message = 'Please select a file to upload.';
    }
}

$total_required = count($required_documents);
$total_submitted = count($submitted);
$total_approved = count(array_filter($submitted, fn($s) => $s['status'] === 'approved'));
$progress_percent = $total_required > 0 ? round(($total_approved / $total_required) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Requirements - HR1</title>
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
        .header h1 { font-size: 1.5rem; color: #e2e8f0; margin-bottom: 0.5rem; }
        .header p { color: #94a3b8; font-size: 0.9rem; }
        
        .progress-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 2rem; }
        .progress-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .progress-header h3 { color: #e2e8f0; font-size: 1.1rem; }
        .progress-stats { display: flex; gap: 2rem; }
        .progress-stat { text-align: center; }
        .progress-stat .number { font-size: 1.5rem; font-weight: 700; color: #0ea5e9; }
        .progress-stat .label { font-size: 0.8rem; color: #94a3b8; }
        .progress-bar { height: 12px; background: rgba(58, 69, 84, 0.5); border-radius: 6px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #0ea5e9, #10b981); border-radius: 6px; transition: width 0.5s; }
        
        .requirements-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; }
        .requirement-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); transition: all 0.3s; }
        .requirement-card:hover { border-color: #0ea5e9; }
        .requirement-card.submitted { border-color: rgba(251, 191, 36, 0.5); }
        .requirement-card.approved { border-color: rgba(16, 185, 129, 0.5); }
        .requirement-card.rejected { border-color: rgba(239, 68, 68, 0.5); }
        
        .requirement-header { display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1rem; }
        .requirement-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .requirement-icon.pending { background: rgba(100, 116, 139, 0.2); color: #94a3b8; }
        .requirement-icon.submitted { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .requirement-icon.approved { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .requirement-icon.rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .requirement-icon .material-symbols-outlined { font-size: 1.5rem; }
        
        .requirement-info h4 { color: #e2e8f0; font-size: 1rem; margin-bottom: 0.25rem; }
        .requirement-info p { color: #94a3b8; font-size: 0.85rem; line-height: 1.4; }
        
        .requirement-status { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; margin-bottom: 1rem; }
        .requirement-status.pending { background: rgba(100, 116, 139, 0.2); color: #94a3b8; }
        .requirement-status.submitted { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .requirement-status.approved { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .requirement-status.rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        
        .upload-form { margin-top: 1rem; }
        .file-input-wrapper { position: relative; margin-bottom: 0.75rem; }
        .file-input { width: 100%; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border: 2px dashed rgba(58, 69, 84, 0.5); border-radius: 8px; color: #cbd5e1; font-size: 0.85rem; cursor: pointer; transition: all 0.3s; }
        .file-input:hover { border-color: #0ea5e9; }
        .file-input::file-selector-button { background: #0ea5e9; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; margin-right: 1rem; cursor: pointer; }
        
        .btn { padding: 0.65rem 1.25rem; border: none; border-radius: 6px; font-size: 0.9rem; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: #0ea5e9; color: white; width: 100%; justify-content: center; }
        .btn-primary:hover { background: #0284c7; }
        .btn-sm { padding: 0.5rem 0.75rem; font-size: 0.8rem; }
        
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; }
        .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); color: #10b981; }
        .alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; }
        
        .uploaded-file { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: rgba(15, 23, 42, 0.4); border-radius: 8px; margin-top: 0.75rem; }
        .uploaded-file .material-symbols-outlined { color: #0ea5e9; }
        .uploaded-file a { color: #0ea5e9; text-decoration: none; font-size: 0.85rem; }
        .uploaded-file a:hover { text-decoration: underline; }
        .uploaded-file .date { color: #64748b; font-size: 0.75rem; margin-left: auto; }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .requirements-grid { grid-template-columns: 1fr; }
            .progress-stats { flex-direction: column; gap: 1rem; }
        }
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
                <li class="nav-item"><a href="requirements.php" class="nav-link active"><span class="material-symbols-outlined">upload_file</span>Submit Requirements</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link"><span class="material-symbols-outlined">person</span>My Profile</a></li>
                <li class="nav-item"><a href="documents.php" class="nav-link"><span class="material-symbols-outlined">folder</span>Documents</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link"><span class="material-symbols-outlined">logout</span>Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Submit Requirements</h1>
                <p>Upload your required documents for employment verification</p>
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

            <div class="progress-card">
                <div class="progress-header">
                    <h3>Requirements Progress</h3>
                    <div class="progress-stats">
                        <div class="progress-stat">
                            <div class="number"><?php echo $total_submitted; ?>/<?php echo $total_required; ?></div>
                            <div class="label">Submitted</div>
                        </div>
                        <div class="progress-stat">
                            <div class="number"><?php echo $total_approved; ?>/<?php echo $total_required; ?></div>
                            <div class="label">Approved</div>
                        </div>
                        <div class="progress-stat">
                            <div class="number"><?php echo $progress_percent; ?>%</div>
                            <div class="label">Complete</div>
                        </div>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $progress_percent; ?>%;"></div>
                </div>
            </div>

            <div class="requirements-grid">
                <?php foreach ($required_documents as $req): 
                    $status = 'pending';
                    $submitted_doc = $submitted_map[$req['type']] ?? null;
                    if ($submitted_doc) {
                        $status = $submitted_doc['status'];
                    }
                ?>
                <div class="requirement-card <?php echo $status; ?>">
                    <div class="requirement-header">
                        <div class="requirement-icon <?php echo $status; ?>">
                            <span class="material-symbols-outlined"><?php echo $req['icon']; ?></span>
                        </div>
                        <div class="requirement-info">
                            <h4><?php echo htmlspecialchars($req['name']); ?></h4>
                            <p><?php echo htmlspecialchars($req['description']); ?></p>
                        </div>
                    </div>
                    
                    <span class="requirement-status <?php echo $status; ?>">
                        <?php if ($status === 'pending'): ?>
                            <span class="material-symbols-outlined" style="font-size: 0.9rem;">hourglass_empty</span> Not Submitted
                        <?php elseif ($status === 'submitted' || $status === 'pending'): ?>
                            <span class="material-symbols-outlined" style="font-size: 0.9rem;">schedule</span> Pending Review
                        <?php elseif ($status === 'approved'): ?>
                            <span class="material-symbols-outlined" style="font-size: 0.9rem;">check_circle</span> Approved
                        <?php elseif ($status === 'rejected'): ?>
                            <span class="material-symbols-outlined" style="font-size: 0.9rem;">cancel</span> Rejected - Please Resubmit
                        <?php endif; ?>
                    </span>
                    
                    <?php if ($submitted_doc): ?>
                    <div class="uploaded-file">
                        <span class="material-symbols-outlined">attachment</span>
                        <a href="../../<?php echo htmlspecialchars($submitted_doc['file_path']); ?>" target="_blank">View Uploaded File</a>
                        <span class="date"><?php echo date('M d, Y', strtotime($submitted_doc['uploaded_at'])); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($status !== 'approved'): ?>
                    <form method="POST" enctype="multipart/form-data" class="upload-form">
                        <input type="hidden" name="document_type" value="<?php echo $req['type']; ?>">
                        <div class="file-input-wrapper">
                            <input type="file" name="requirement_file" class="file-input" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <button type="submit" name="upload_requirement" class="btn btn-primary">
                            <span class="material-symbols-outlined" style="font-size: 1.1rem;">cloud_upload</span>
                            <?php echo $submitted_doc ? 'Re-upload Document' : 'Upload Document'; ?>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
    <?php include '../../includes/logout-modal.php'; ?>
</body>
</html>
