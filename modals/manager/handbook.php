<?php
require_once '../../includes/session_helper.php';
require_once '../../includes/rbac_helper.php';
require_once '../../includes/spa_helper.php';
startSecureSession();
$is_ajax = is_spa_ajax();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit();
}

$userId = $_SESSION['user_id'];
$user = getUserWithRole($userId);

if (!$user || !in_array($user['role_type'] ?? '', ['Manager', 'Admin', 'HR_Staff'])) {
    header('Location: ../../index.php');
    exit();
}

if (!$is_ajax) {
    header('Location: index.php?page=handbook');
    exit();
}

require_once '../../database/config.php';

$message = null;
$messageType = null;

// Handle handbook upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_handbook'])) {
    $handbookTitle = trim($_POST['handbook_title']);
    $handbookDescription = trim($_POST['handbook_description']);
    $documentType = $_POST['document_type'] ?? 'Handbook';

    if (isset($_FILES['handbook_file']) && $_FILES['handbook_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/handbooks/';
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
                    $message = "Handbook uploaded successfully!";
                    $messageType = "success";
                } catch (Exception $e) {
                    $message = "Database error: " . $e->getMessage();
                    $messageType = "error";
                }
            } else {
                $message = "Failed to move uploaded file.";
                $messageType = "error";
            }
        } else {
            $message = "Only PDF files are allowed.";
            $messageType = "error";
        }
    } else {
        $message = "Please select a file to upload.";
        $messageType = "error";
    }
}

// Fetch existing handbooks
$handbooks = [];
try {
    $handbooks = fetchAll("
        SELECT ed.*, ua.first_name, ua.last_name
        FROM employee_documents ed
        LEFT JOIN user_accounts ua ON ed.uploaded_by = ua.id
        WHERE ed.user_id = 0 AND ed.document_type IN ('Handbook', 'Policy', 'SOP')
        ORDER BY ed.uploaded_at DESC
    ");
} catch (Exception $e) { $handbooks = []; }
?>
<div data-page-title="Employee Handbook">
<style>
        .header {
            background: rgba(30, 41, 54, 0.6);
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 { font-size: 1.35rem; color: #e2e8f0; margin-bottom: 0.25rem; }
        .header p { color: #94a3b8; font-size: 0.85rem; }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-success { background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #6ee7b7; }
        .alert-warning { background: rgba(245, 158, 11, 0.2); border: 1px solid #f59e0b; color: #fbbf24; }
        .alert-error { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 1.5rem;
        }

        .card {
            background: rgba(30, 41, 54, 0.6);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(58, 69, 84, 0.5);
        }

        .card h2 {
            font-size: 1.1rem;
            color: #e2e8f0;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #cbd5e1; font-size: 0.9rem; }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(58, 69, 84, 0.5);
            border-radius: 6px;
            color: #e2e8f0;
            font-size: 0.95rem;
            font-family: inherit;
        }

        .form-group textarea { min-height: 80px; resize: vertical; }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #f59e0b;
        }

        .file-input-wrapper {
            position: relative;
            padding: 2rem;
            border: 2px dashed rgba(58, 69, 84, 0.5);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.3s;
        }

        .file-input-wrapper:hover { border-color: #f59e0b; }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-input-wrapper p { color: #94a3b8; font-size: 0.9rem; }
        .file-input-wrapper .hint { color: #64748b; font-size: 0.8rem; margin-top: 0.25rem; }

        .submit-btn {
            width: 100%;
            padding: 0.75rem;
            background: #f59e0b;
            color: #1a2942;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .submit-btn:hover { background: #d97706; }

        .handbook-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1rem;
        }

        .handbook-item {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 10px;
            padding: 1.25rem;
            border: 1px solid rgba(58, 69, 84, 0.5);
            transition: all 0.2s;
        }

        .handbook-item:hover { border-color: rgba(245, 158, 11, 0.5); }

        .handbook-icon {
            width: 40px; height: 40px;
            border-radius: 8px;
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 0.75rem;
        }

        .handbook-title { font-weight: 600; color: #e2e8f0; font-size: 0.95rem; margin-bottom: 0.25rem; }

        .handbook-type {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 600;
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
            margin-bottom: 0.5rem;
        }

        .handbook-meta { font-size: 0.8rem; color: #94a3b8; }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #94a3b8;
        }

        @media (max-width: 1024px) { .grid-2 { grid-template-columns: 1fr; } }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
            <div class="header">
                <div>
                    <h1>Employee Handbook</h1>
                    <p>Upload and manage policy documents for your team</p>
                </div>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="grid-2">
                <div class="card">
                    <h2><i data-lucide="upload"></i> Upload Document</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="upload_handbook" value="1">

                        <div class="form-group">
                            <label>Document Title *</label>
                            <input type="text" name="handbook_title" required placeholder="e.g., Employee Safety Manual">
                        </div>

                        <div class="form-group">
                            <label>Document Type</label>
                            <select name="document_type">
                                <option value="Handbook">Handbook</option>
                                <option value="Policy">Policy Document</option>
                                <option value="SOP">Standard Operating Procedure</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="handbook_description" placeholder="Brief description of the document..."></textarea>
                        </div>

                        <div class="form-group">
                            <label>PDF File *</label>
                            <div class="file-input-wrapper" id="dropZone">
                                <input type="file" name="handbook_file" accept=".pdf" required id="fileInput">
                                <i data-lucide="upload-cloud" style="width:2rem;height:2rem;color:#f59e0b;margin-bottom:0.5rem;"></i>
                                <p id="fileName">Click or drag to upload PDF</p>
                                <p class="hint">Only PDF files are accepted</p>
                            </div>
                        </div>

                        <button type="submit" class="submit-btn">
                            <i data-lucide="upload" style="width:16px;height:16px;"></i> Upload Handbook
                        </button>
                    </form>
                </div>

                <div class="card">
                    <h2><i data-lucide="library"></i> Uploaded Documents</h2>
                    <?php if (empty($handbooks)): ?>
                        <div class="empty-state">
                            <i data-lucide="book-open" style="width:3rem;height:3rem;margin-bottom:0.5rem;"></i>
                            <p>No handbooks uploaded yet</p>
                        </div>
                    <?php else: ?>
                    <div class="handbook-grid">
                        <?php foreach ($handbooks as $hb): ?>
                        <div class="handbook-item">
                            <div class="handbook-icon"><i data-lucide="file-text"></i></div>
                            <div class="handbook-title"><?php echo htmlspecialchars($hb['document_name']); ?></div>
                            <span class="handbook-type"><?php echo htmlspecialchars($hb['document_type']); ?></span>
                            <div class="handbook-meta">
                                Uploaded: <?php echo date('M d, Y', strtotime($hb['uploaded_at'])); ?>
                                <?php if (!empty($hb['first_name'])): ?>
                                    <br>By: <?php echo htmlspecialchars($hb['first_name'] . ' ' . $hb['last_name']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
<script>
    document.getElementById('fileInput').addEventListener('change', function() {
        if (this.files.length > 0) {
            document.getElementById('fileName').textContent = this.files[0].name;
        }
    });

    if (typeof lucide !== 'undefined') { lucide.createIcons(); }
</script>
</div>
