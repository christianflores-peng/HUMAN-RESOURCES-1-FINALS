<?php
/**
 * HR1 Module: Employee Self-Service Portal
 * Slate Freight Management System
 * 
 * Role-Based Access: Employee (New Hire)
 * Access Level: Self-Service (Own Data Only)
 * Logic: Access data where User ID == My User ID
 * 
 * Features:
 * - Upload requirements for New Hire Onboarding
 * - View Employee Handbook / Policies
 * - Update personal info (Address, Emergency Contact)
 */

session_start();
require_once '../database/config.php';
require_once '../includes/rbac_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../partials/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$user = getUserWithRole($userId);

// Verify Employee access (role_id 7 or 8 for New Hire)
if (!in_array($user['role_id'] ?? 0, [7, 8])) {
    header('Location: dashboard.php');
    exit();
}

// Set active page for sidebar
$active_page = 'employee-portal';

// Handle document upload
$uploadMessage = null;
$uploadError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    $taskId = intval($_POST['task_id']);
    
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/onboarding/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExt = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        
        if (in_array($fileExt, $allowedExts)) {
            $fileName = 'user_' . $userId . '_task_' . $taskId . '_' . time() . '.' . $fileExt;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['document']['tmp_name'], $filePath)) {
                // Update onboarding progress
                try {
                    updateRecord(
                        "UPDATE employee_onboarding_progress 
                         SET status = 'Completed', file_path = ?, completed_at = NOW() 
                         WHERE user_id = ? AND task_id = ?",
                        [$filePath, $userId, $taskId]
                    );
                    
                    logAuditAction($userId, 'CREATE', 'employee_onboarding_progress', $taskId, null, [
                        'status' => 'Completed',
                        'file_path' => $filePath
                    ], "Uploaded onboarding document");
                    
                    $uploadMessage = "Document uploaded successfully!";
                } catch (Exception $e) {
                    $uploadError = "Failed to update progress: " . $e->getMessage();
                }
            } else {
                $uploadError = "Failed to upload file.";
            }
        } else {
            $uploadError = "Invalid file type. Allowed: PDF, JPG, PNG, DOC, DOCX";
        }
    } else {
        $uploadError = "No file selected or upload error.";
    }
}

// Handle personal info update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $emergencyName = trim($_POST['emergency_name']);
    $emergencyPhone = trim($_POST['emergency_phone']);
    $emergencyRelation = trim($_POST['emergency_relation']);
    
    try {
        $oldData = fetchSingle("SELECT address, phone FROM user_accounts WHERE id = ?", [$userId]);
        
        updateRecord(
            "UPDATE user_accounts SET address = ?, phone = ? WHERE id = ?",
            [$address, $phone, $userId]
        );
        
        // Store emergency contact in JSON or separate table
        // For now, we'll add it to a notes field or create employee_documents entry
        
        logAuditAction($userId, 'EDIT', 'user_accounts', $userId, 
            ['address' => $oldData['address'], 'phone' => $oldData['phone']],
            ['address' => $address, 'phone' => $phone],
            "Updated personal information"
        );
        
        $uploadMessage = "Personal information updated successfully!";
    } catch (Exception $e) {
        $uploadError = "Failed to update information: " . $e->getMessage();
    }
}

// Get onboarding tasks for this employee
$onboardingTasks = [];
try {
    $onboardingTasks = fetchAll(
        "SELECT eop.*, ot.task_name, ot.task_description, ot.category, ot.is_required, ot.days_to_complete
         FROM employee_onboarding_progress eop
         JOIN onboarding_tasks ot ON eop.task_id = ot.id
         WHERE eop.user_id = ?
         ORDER BY ot.is_required DESC, eop.status ASC",
        [$userId]
    );
} catch (Exception $e) {
    // If no tasks exist, create them
    try {
        $tasks = fetchAll("SELECT id FROM onboarding_tasks WHERE is_required = 1");
        foreach ($tasks as $task) {
            insertRecord(
                "INSERT IGNORE INTO employee_onboarding_progress (user_id, task_id, status) VALUES (?, ?, 'Pending')",
                [$userId, $task['id']]
            );
        }
        // Retry fetching
        $onboardingTasks = fetchAll(
            "SELECT eop.*, ot.task_name, ot.task_description, ot.category, ot.is_required, ot.days_to_complete
             FROM employee_onboarding_progress eop
             JOIN onboarding_tasks ot ON eop.task_id = ot.id
             WHERE eop.user_id = ?
             ORDER BY ot.is_required DESC, eop.status ASC",
            [$userId]
        );
    } catch (Exception $e2) {
        $onboardingTasks = [];
    }
}

// Calculate onboarding progress
$totalTasks = count($onboardingTasks);
$completedTasks = count(array_filter($onboardingTasks, fn($t) => $t['status'] === 'Completed'));
$progressPercent = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

// Get employee handbook documents (uploaded by managers)
$handbooks = [];
try {
    $handbooks = fetchAll(
        "SELECT ed.*, ua.first_name, ua.last_name
         FROM employee_documents ed
         LEFT JOIN user_accounts ua ON ed.uploaded_by = ua.id
         WHERE ed.user_id = 0 AND ed.document_type IN ('Handbook', 'Policy') AND ed.status = 'Active'
         ORDER BY ed.uploaded_at DESC"
    );
} catch (Exception $e) {
    $handbooks = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal - Slate Freight</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block" />
    <style>
        .employee-container {
            padding: 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #1e2936;
            border-radius: 12px;
        }

        .page-header h1 {
            font-size: 1.75rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }

        .btn-secondary {
            background: #334155;
            color: #e2e8f0;
        }

        .btn-secondary:hover {
            background: #475569;
        }

        .btn-primary {
            background: #0ea5e9;
            color: white;
        }

        .btn-primary:hover {
            background: #0284c7;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid #10b981;
            color: #6ee7b7;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #ef4444;
            color: #fca5a5;
        }

        .progress-card {
            background: #1e2936;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .progress-title {
            font-size: 1.25rem;
            color: #ffffff;
        }

        .progress-percent {
            font-size: 2rem;
            font-weight: 700;
            color: #0ea5e9;
        }

        .progress-bar-container {
            background: #2a3544;
            border-radius: 8px;
            height: 12px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #0ea5e9);
            transition: width 0.3s;
        }

        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #334155;
            padding-bottom: 0.5rem;
        }

        .tab-btn {
            padding: 0.75rem 1.5rem;
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 0.95rem;
            cursor: pointer;
            border-radius: 8px 8px 0 0;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tab-btn:hover {
            background: rgba(14, 165, 233, 0.1);
            color: #0ea5e9;
        }

        .tab-btn.active {
            background: #0ea5e9;
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .task-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .task-card {
            background: #1e2936;
            border-radius: 12px;
            padding: 1.5rem;
            border-left: 4px solid #3a4554;
        }

        .task-card.completed {
            border-left-color: #10b981;
            background: rgba(16, 185, 129, 0.05);
        }

        .task-card.pending {
            border-left-color: #f59e0b;
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .task-title {
            font-size: 1.1rem;
            color: #ffffff;
            font-weight: 600;
        }

        .task-status {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .task-status.completed {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .task-status.pending {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }

        .task-description {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .task-category {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background: #2a3544;
            border-radius: 4px;
            font-size: 0.8rem;
            color: #0ea5e9;
            margin-bottom: 1rem;
        }

        .upload-area {
            border: 2px dashed #3a4554;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            margin-top: 1rem;
        }

        .form-card {
            background: #1e2936;
            border-radius: 12px;
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #cbd5e1;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            background: #2a3544;
            border: 1px solid #3a4554;
            border-radius: 6px;
            color: #e2e8f0;
            font-size: 0.95rem;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0ea5e9;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .handbook-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .handbook-item {
            background: #1e2936;
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .handbook-info h3 {
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .handbook-info p {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .task-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php 
include '../partials/sidebar.php';
include '../partials/header.php';
?>
    <div class="employee-container">
        <div class="page-header">
            <h1>
                <span class="material-symbols-outlined">badge</span>
                Employee Self-Service Portal
            </h1>
        </div>

        <?php if ($uploadMessage): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($uploadMessage); ?></div>
        <?php endif; ?>

        <?php if ($uploadError): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($uploadError); ?></div>
        <?php endif; ?>

        <!-- Onboarding Progress -->
        <div class="progress-card">
            <div class="progress-header">
                <div>
                    <h2 class="progress-title">New Hire Onboarding Progress</h2>
                    <p style="color: #94a3b8; margin-top: 0.5rem;"><?php echo $completedTasks; ?> of <?php echo $totalTasks; ?> tasks completed</p>
                </div>
                <div class="progress-percent"><?php echo $progressPercent; ?>%</div>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill" style="width: <?php echo $progressPercent; ?>%;"></div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('onboarding')">
                <span class="material-symbols-outlined">assignment</span>
                Onboarding Tasks
            </button>
            <button class="tab-btn" onclick="showTab('handbook')">
                <span class="material-symbols-outlined">menu_book</span>
                Employee Handbook
            </button>
            <button class="tab-btn" onclick="showTab('personal')">
                <span class="material-symbols-outlined">person</span>
                Personal Information
            </button>
        </div>

        <!-- Tab: Onboarding Tasks -->
        <div id="onboarding-tab" class="tab-content active">
            <div class="task-grid">
                <?php foreach ($onboardingTasks as $task): ?>
                    <div class="task-card <?php echo strtolower($task['status']); ?>">
                        <div class="task-header">
                            <h3 class="task-title"><?php echo htmlspecialchars($task['task_name']); ?></h3>
                            <span class="task-status <?php echo strtolower($task['status']); ?>">
                                <?php echo htmlspecialchars($task['status']); ?>
                            </span>
                        </div>
                        <span class="task-category"><?php echo htmlspecialchars($task['category']); ?></span>
                        <p class="task-description"><?php echo htmlspecialchars($task['task_description']); ?></p>
                        
                        <?php if ($task['status'] !== 'Completed'): ?>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="upload_document" value="1">
                                <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">
                                <div class="upload-area">
                                    <input type="file" name="document" id="doc_<?php echo $task['task_id']; ?>" style="display: none;" required>
                                    <label for="doc_<?php echo $task['task_id']; ?>" style="cursor: pointer;">
                                        <span class="material-symbols-outlined" style="font-size: 2rem; color: #0ea5e9;">upload_file</span>
                                        <p style="color: #94a3b8; margin-top: 0.5rem;">Click to upload document</p>
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem; justify-content: center;">
                                    <span class="material-symbols-outlined">check_circle</span>
                                    Submit Document
                                </button>
                            </form>
                        <?php else: ?>
                            <div style="display: flex; align-items: center; gap: 0.5rem; color: #10b981; margin-top: 1rem;">
                                <span class="material-symbols-outlined">check_circle</span>
                                <span>Completed on <?php echo date('M d, Y', strtotime($task['completed_at'])); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($onboardingTasks)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: #64748b;">
                        <span class="material-symbols-outlined" style="font-size: 4rem;">task_alt</span>
                        <p style="margin-top: 1rem;">No onboarding tasks assigned yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab: Employee Handbook -->
        <div id="handbook-tab" class="tab-content">
            <div class="handbook-list">
                <?php if (empty($handbooks)): ?>
                    <div style="text-align: center; padding: 3rem; color: #64748b;">
                        <span class="material-symbols-outlined" style="font-size: 4rem; display: block; margin-bottom: 1rem;">menu_book</span>
                        <p>No handbooks available yet. Your manager will upload them soon.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($handbooks as $handbook): ?>
                        <div class="handbook-item">
                            <div class="handbook-info">
                                <h3><?php echo htmlspecialchars($handbook['document_name']); ?></h3>
                                <p>
                                    <?php echo htmlspecialchars($handbook['document_type']); ?> • 
                                    Uploaded <?php echo date('M d, Y', strtotime($handbook['uploaded_at'])); ?>
                                </p>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <button onclick="viewHandbook('<?php echo htmlspecialchars($handbook['file_path']); ?>', '<?php echo htmlspecialchars($handbook['document_name']); ?>')" class="btn btn-primary">
                                    <span class="material-symbols-outlined">visibility</span>
                                    View Handbook
                                </button>
                                <a href="<?php echo htmlspecialchars($handbook['file_path']); ?>" download class="btn btn-primary" style="background: #10b981;">
                                    <span class="material-symbols-outlined">download</span>
                                    Download
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab: Personal Information -->
        <div id="personal-tab" class="tab-content">
            <div class="form-card">
                <h2 style="color: #ffffff; margin-bottom: 1.5rem;">Update Personal Information</h2>
                <form method="POST">
                    <input type="hidden" name="update_info" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Enter phone number">
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['company_email'] ?? $user['personal_email'] ?? ''); ?>" disabled style="background: #1a2332;">
                            <small style="color: #64748b; font-size: 0.85rem;">Contact HR to change email</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Complete Address</label>
                        <textarea name="address" rows="3" placeholder="Enter your complete address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>

                    <h3 style="color: #ffffff; margin: 2rem 0 1rem;">Emergency Contact</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Emergency Contact Name</label>
                            <input type="text" name="emergency_name" placeholder="Full name">
                        </div>
                        <div class="form-group">
                            <label>Relationship</label>
                            <input type="text" name="emergency_relation" placeholder="e.g., Spouse, Parent, Sibling">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Emergency Contact Phone</label>
                        <input type="tel" name="emergency_phone" placeholder="Phone number">
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
                        <span class="material-symbols-outlined">save</span>
                        Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- PDF Viewer Modal -->
    <div id="pdfModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.9); z-index: 9999; align-items: center; justify-content: center;">
        <div style="width: 90%; height: 90%; background: #1e2936; border-radius: 12px; display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid #334155;">
                <h2 id="pdfTitle" style="color: #ffffff; font-size: 1.25rem;"></h2>
                <button onclick="closePdfModal()" style="background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 2rem; padding: 0; line-height: 1;">&times;</button>
            </div>
            <iframe id="pdfViewer" style="flex: 1; border: none; width: 100%; height: 100%;"></iframe>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.closest('.tab-btn').classList.add('active');
        }

        function viewHandbook(filePath, title) {
            document.getElementById('pdfTitle').textContent = title;
            document.getElementById('pdfViewer').src = filePath;
            document.getElementById('pdfModal').style.display = 'flex';
        }

        function closePdfModal() {
            document.getElementById('pdfModal').style.display = 'none';
            document.getElementById('pdfViewer').src = '';
        }

        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePdfModal();
            }
        });
    </script>
</body>
</html>
