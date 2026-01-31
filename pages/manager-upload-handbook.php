<?php
/**
 * Manager Handbook Upload Page
 * Allows managers to upload employee handbooks and policy documents
 */

session_start();
require_once '../database/config.php';
require_once '../includes/rbac_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../partials/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$user = getUserWithRole($userId);

// Verify manager access
if (!$user || !in_array($user['role_type'], ['Manager', 'Admin', 'HR_Staff'])) {
    if (!isAdmin($userId) && !isHRStaff($userId)) {
        header('Location: ../pages/dashboard.php');
        exit();
    }
}

$message = null;
$messageType = null;

// Handle handbook upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_handbook'])) {
    $handbookTitle = trim($_POST['handbook_title']);
    $handbookDescription = trim($_POST['handbook_description']);
    $documentType = $_POST['document_type'] ?? 'Handbook';
    
    if (isset($_FILES['handbook_file']) && $_FILES['handbook_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/handbooks/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExt = strtolower(pathinfo($_FILES['handbook_file']['name'], PATHINFO_EXTENSION));
        
        if ($fileExt === 'pdf') {
            $fileName = 'handbook_' . time() . '_' . uniqid() . '.pdf';
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['handbook_file']['tmp_name'], $filePath)) {
                try {
                    insertRecord(
                        "INSERT INTO employee_documents (user_id, document_type, document_name, file_path, uploaded_by, uploaded_at, status)
                         VALUES (0, ?, ?, ?, ?, NOW(), 'Active')",
                        [$documentType, $handbookTitle, $filePath, $userId]
                    );
                    
                    logAuditAction($userId, 'CREATE', 'employee_documents', null, null, [
                        'document_type' => $documentType,
                        'document_name' => $handbookTitle
                    ], "Uploaded employee handbook");
                    
                    $message = "Handbook uploaded successfully! Employees can now view it.";
                    $messageType = "success";
                } catch (Exception $e) {
                    $message = "Error saving to database: " . $e->getMessage();
                    $messageType = "error";
                }
            } else {
                $message = "Failed to upload file.";
                $messageType = "error";
            }
        } else {
            $message = "Only PDF files are allowed.";
            $messageType = "error";
        }
    } else {
        $message = "No file selected or upload error.";
        $messageType = "error";
    }
}

// Get existing handbooks
$handbooks = [];
try {
    $handbooks = fetchAll(
        "SELECT ed.*, ua.first_name, ua.last_name
         FROM employee_documents ed
         LEFT JOIN user_accounts ua ON ed.uploaded_by = ua.id
         WHERE ed.user_id = 0 AND ed.document_type IN ('Handbook', 'Policy')
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
    <title>Upload Handbook - Manager Portal</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block" />
</head>
<body>
<?php 
$active_page = 'manager-dashboard';
include '../partials/sidebar.php';
include '../partials/header.php';
?>

<div class="main-container">
    <div style="background: #1e2936; border-radius: 12px; padding: 2rem; margin-bottom: 2rem;">
        <div style="margin-bottom: 1.5rem;">
            <h1 style="color: #ffffff; font-size: 1.75rem;">Upload Employee Handbook</h1>
            <p style="color: #94a3b8; margin-top: 0.5rem;">Upload PDF handbooks and policy documents for employees to view</p>
        </div>

        <?php if ($message): ?>
            <div style="padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; <?php 
                echo $messageType === 'success' ? 'background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #6ee7b7;' : 
                    'background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5;';
            ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" style="display: grid; gap: 1.5rem;">
            <input type="hidden" name="upload_handbook" value="1">
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-weight: 500;">Handbook Title</label>
                    <input type="text" name="handbook_title" required placeholder="e.g., Employee Handbook 2026" style="width: 100%; padding: 0.75rem 1rem; background: #2a3544; border: 1px solid #3a4554; border-radius: 6px; color: #e2e8f0; font-size: 0.95rem;">
                </div>

                <div>
                    <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-weight: 500;">Document Type</label>
                    <select name="document_type" required style="width: 100%; padding: 0.75rem 1rem; background: #2a3544; border: 1px solid #3a4554; border-radius: 6px; color: #e2e8f0; font-size: 0.95rem;">
                        <option value="Handbook">Handbook</option>
                        <option value="Policy">Policy</option>
                    </select>
                </div>
            </div>

            <div>
                <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-weight: 500;">Description</label>
                <textarea name="handbook_description" rows="3" placeholder="Brief description of the handbook contents..." style="width: 100%; padding: 0.75rem 1rem; background: #2a3544; border: 1px solid #3a4554; border-radius: 6px; color: #e2e8f0; font-size: 0.95rem; resize: vertical;"></textarea>
            </div>

            <div>
                <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-weight: 500;">Upload PDF File</label>
                <div style="border: 2px dashed #3a4554; border-radius: 8px; padding: 2rem; text-align: center; background: #2a3544;">
                    <input type="file" name="handbook_file" accept=".pdf" required id="handbookFile" style="display: none;" onchange="updateFileName(this)">
                    <label for="handbookFile" style="cursor: pointer;">
                        <span class="material-symbols-outlined" style="font-size: 3rem; color: #0ea5e9; display: block; margin-bottom: 0.5rem;">upload_file</span>
                        <p style="color: #94a3b8; margin-bottom: 0.5rem;">Click to select PDF file</p>
                        <p id="fileName" style="color: #0ea5e9; font-weight: 500;">No file selected</p>
                    </label>
                </div>
                <p style="color: #64748b; font-size: 0.85rem; margin-top: 0.5rem;">Only PDF files are accepted. Max file size: 10MB</p>
            </div>

            <button type="submit" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.875rem 2rem; background: #0ea5e9; color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer;">
                <span class="material-symbols-outlined">cloud_upload</span>
                Upload Handbook
            </button>
        </form>
    </div>

    <!-- Existing Handbooks -->
    <div style="background: #1e2936; border-radius: 12px; padding: 2rem;">
        <h2 style="color: #ffffff; margin-bottom: 1rem;">Uploaded Handbooks</h2>
        
        <?php if (empty($handbooks)): ?>
            <p style="color: #94a3b8; text-align: center; padding: 2rem;">No handbooks uploaded yet.</p>
        <?php else: ?>
            <div style="display: grid; gap: 1rem;">
                <?php foreach ($handbooks as $handbook): ?>
                    <div style="background: #2a3544; border-radius: 8px; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <span class="material-symbols-outlined" style="font-size: 2.5rem; color: #ef4444;">picture_as_pdf</span>
                            <div>
                                <h3 style="color: #ffffff; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($handbook['document_name']); ?></h3>
                                <p style="color: #94a3b8; font-size: 0.85rem;">
                                    Uploaded by: <?php echo htmlspecialchars(($handbook['first_name'] ?? '') . ' ' . ($handbook['last_name'] ?? 'System')); ?> 
                                    on <?php echo date('M d, Y', strtotime($handbook['uploaded_at'])); ?>
                                </p>
                                <span style="display: inline-block; margin-top: 0.5rem; padding: 0.25rem 0.75rem; background: #334155; border-radius: 12px; font-size: 0.75rem; color: #0ea5e9;">
                                    <?php echo htmlspecialchars($handbook['document_type']); ?>
                                </span>
                            </div>
                        </div>
                        <a href="<?php echo htmlspecialchars($handbook['file_path']); ?>" target="_blank" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.25rem; background: #0ea5e9; color: white; text-decoration: none; border-radius: 6px; font-weight: 500;">
                            <span class="material-symbols-outlined">visibility</span>
                            View PDF
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function updateFileName(input) {
    const fileName = document.getElementById('fileName');
    if (input.files && input.files[0]) {
        fileName.textContent = input.files[0].name;
        fileName.style.color = '#10b981';
    }
}
</script>

<?php include '../partials/footer.php'; ?>
</body>
</html>
