<?php
require_once __DIR__ . '/../../includes/session_helper.php';
require_once __DIR__ . '/../../includes/rbac_helper.php';
require_once __DIR__ . '/../../includes/spa_helper.php';
startSecureSession();
$is_ajax = is_spa_ajax();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit();
}

$userId = $_SESSION['user_id'];
$user = getUserWithRole($userId);

if (!$user || !in_array($user['role_type'] ?? '', ['Employee', 'Manager', 'Admin', 'HR_Staff'])) {
    header('Location: ../../index.php');
    exit();
}

$role_type = $user['role_type'] ?? '';
$is_admin_user = $role_type === 'Admin';
$can_upload_handbook = in_array($role_type, ['Admin', 'HR_Staff', 'Manager'], true);

function handbook_allowed_upload_targets($role_type) {
    if ($role_type === 'Admin') {
        return [
            'all' => 'Handbook_All',
            'employee' => 'Handbook_Employee',
            'manager' => 'Handbook_Manager',
            'hr_staff' => 'Handbook_HR_Staff',
            'admin' => 'Handbook_Admin'
        ];
    }

    if ($role_type === 'HR_Staff') {
        return [
            'manager' => 'Handbook_Manager'
        ];
    }

    if ($role_type === 'Manager') {
        return [
            'employee' => 'Handbook_Employee'
        ];
    }

    return [];
}

if (!$is_ajax) {
    header('Location: index.php?page=handbook');
    exit();
}

require_once __DIR__ . '/../../database/config.php';

function handbook_public_path($stored_path) {
    $normalized = str_replace('\\', '/', (string)$stored_path);
    $uploads_pos = stripos($normalized, 'uploads/');
    if ($uploads_pos !== false) {
        $normalized = substr($normalized, $uploads_pos);
    }
    return '../../' . ltrim($normalized, '/');
}

function handbook_audience_label($document_type) {
    $map = [
        'Handbook_All' => 'All Staff',
        'Handbook_Employee' => 'Employee',
        'Handbook_Manager' => 'Manager',
        'Handbook_HR_Staff' => 'HR Staff',
        'Handbook_Admin' => 'Admin'
    ];
    return $map[$document_type] ?? 'General';
}

function handbook_encode_name($title, $version, $effective_date) {
    $payload = [
        'title' => $title,
        'version' => $version,
        'effective_date' => $effective_date
    ];
    return 'HBMETA::' . base64_encode(json_encode($payload));
}

function handbook_decode_name($stored_name) {
    $raw = (string)$stored_name;
    if (strpos($raw, 'HBMETA::') !== 0) {
        return [
            'title' => $raw,
            'version' => '1.0',
            'effective_date' => ''
        ];
    }

    $encoded = substr($raw, 8);
    $decoded = json_decode(base64_decode($encoded), true);
    if (!is_array($decoded)) {
        return [
            'title' => $raw,
            'version' => '1.0',
            'effective_date' => ''
        ];
    }

    return [
        'title' => trim((string)($decoded['title'] ?? $raw)),
        'version' => trim((string)($decoded['version'] ?? '1.0')),
        'effective_date' => trim((string)($decoded['effective_date'] ?? ''))
    ];
}

$message = null;
$messageType = null;

// Handle handbook upload (Role-scoped)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_handbook']) && $can_upload_handbook) {
    $allowed_targets = handbook_allowed_upload_targets($role_type);
    $handbookTitle = trim($_POST['handbook_title']);
    $version = trim($_POST['version'] ?? '1.0');
    $effective_date = trim($_POST['effective_date'] ?? '');
    if ($effective_date === '') {
        $effective_date = date('Y-m-d');
    }
    $meta_name = handbook_encode_name($handbookTitle, $version, $effective_date);
    $audience = $_POST['audience'] ?? 'all';
    $documentType = $allowed_targets[$audience] ?? null;

    if (!$documentType) {
        $message = 'You are not allowed to upload handbook for the selected audience.';
        $messageType = 'error';
    } elseif (isset($_FILES['handbook_file']) && $_FILES['handbook_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../uploads/handbooks/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileExt = strtolower(pathinfo($_FILES['handbook_file']['name'], PATHINFO_EXTENSION));

        if ($fileExt === 'pdf') {
            $fileName = 'handbook_' . time() . '_' . uniqid() . '.pdf';
            $filePath = $uploadDir . $fileName;
            $dbFilePath = 'uploads/handbooks/' . $fileName;

            if (move_uploaded_file($_FILES['handbook_file']['tmp_name'], $filePath)) {
                try {
                    insertRecord(
                        "INSERT INTO employee_documents (user_id, document_type, document_name, file_path, uploaded_by, uploaded_at, status)
                         VALUES (0, ?, ?, ?, ?, NOW(), 'Active')",
                        [$documentType, $meta_name, $dbFilePath, $userId]
                    );
                    logAuditAction($userId, 'CREATE', 'employee_documents', null, null, [
                        'document_type' => $documentType,
                        'document_name' => $handbookTitle,
                        'version' => $version,
                        'effective_date' => $effective_date
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
    $visible_types = ['Handbook_All', 'Handbook', 'Policy', 'SOP'];
    if ($role_type === 'Employee') {
        $visible_types[] = 'Handbook_Employee';
    } elseif ($role_type === 'Manager') {
        $visible_types[] = 'Handbook_Manager';
    } elseif ($role_type === 'HR_Staff') {
        $visible_types[] = 'Handbook_HR_Staff';
    } elseif ($role_type === 'Admin') {
        $visible_types[] = 'Handbook_Employee';
        $visible_types[] = 'Handbook_Manager';
        $visible_types[] = 'Handbook_HR_Staff';
        $visible_types[] = 'Handbook_Admin';
    }

    $placeholders = implode(',', array_fill(0, count($visible_types), '?'));
    $handbooks = fetchAll("
        SELECT ed.*, ua.first_name, ua.last_name
        FROM employee_documents ed
        LEFT JOIN user_accounts ua ON ed.uploaded_by = ua.id
        WHERE ed.user_id = 0 AND ed.document_type IN ($placeholders)
        ORDER BY ed.uploaded_at DESC
    ", $visible_types);

    // Enrich handbook metadata and identify latest version per title+audience
    $latest_index_by_key = [];
    foreach ($handbooks as $idx => &$hb) {
        $meta = handbook_decode_name($hb['document_name'] ?? '');
        $hb['meta_title'] = $meta['title'];
        $hb['meta_version'] = $meta['version'] === '' ? '1.0' : $meta['version'];
        $hb['meta_effective_date'] = $meta['effective_date'];

        $key = strtolower($hb['meta_title']) . '|' . ($hb['document_type'] ?? '');
        if (!isset($latest_index_by_key[$key])) {
            $latest_index_by_key[$key] = $idx;
            continue;
        }

        $current_idx = $latest_index_by_key[$key];
        $current = $handbooks[$current_idx];
        $version_cmp = version_compare($hb['meta_version'], $current['meta_version']);
        if ($version_cmp > 0) {
            $latest_index_by_key[$key] = $idx;
        } elseif ($version_cmp === 0) {
            $effective_new = strtotime($hb['meta_effective_date'] ?: '1970-01-01');
            $effective_current = strtotime(($current['meta_effective_date'] ?? '') ?: '1970-01-01');
            if ($effective_new > $effective_current) {
                $latest_index_by_key[$key] = $idx;
            } elseif ($effective_new === $effective_current) {
                $uploaded_new = strtotime($hb['uploaded_at'] ?? '1970-01-01');
                $uploaded_current = strtotime($current['uploaded_at'] ?? '1970-01-01');
                if ($uploaded_new > $uploaded_current) {
                    $latest_index_by_key[$key] = $idx;
                }
            }
        }
    }
    unset($hb);

    foreach ($handbooks as $idx => &$hb) {
        $key = strtolower(($hb['meta_title'] ?? '')) . '|' . ($hb['document_type'] ?? '');
        $hb['is_latest'] = isset($latest_index_by_key[$key]) && $latest_index_by_key[$key] === $idx;
    }
    unset($hb);
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

        .grid-1 {
            display: grid;
            grid-template-columns: 1fr;
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

        .latest-badge {
            display: inline-block;
            margin-left: 0.4rem;
            padding: 0.15rem 0.45rem;
            border-radius: 999px;
            font-size: 0.68rem;
            font-weight: 700;
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.45);
            color: #34d399;
            vertical-align: middle;
        }

        .handbook-meta { font-size: 0.8rem; color: #94a3b8; }

        .handbook-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.85rem;
            flex-wrap: wrap;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            text-decoration: none;
            border: 1px solid rgba(58, 69, 84, 0.7);
            border-radius: 6px;
            padding: 0.35rem 0.55rem;
            color: #cbd5e1;
            font-size: 0.75rem;
            background: rgba(15, 23, 42, 0.6);
        }

        .action-btn:hover {
            border-color: #f59e0b;
            color: #f8fafc;
        }

        .inline-viewer {
            display: none;
            margin-top: 0.8rem;
            border: 1px solid rgba(58, 69, 84, 0.7);
            border-radius: 8px;
            overflow: hidden;
            height: 340px;
        }

        .inline-viewer.active {
            display: block;
        }

        .inline-viewer iframe {
            width: 100%;
            height: 100%;
            border: 0;
            background: #0f172a;
        }

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
                    <p>Role-based handbook website with downloadable PDF files managed by Admin</p>
                </div>
                <div class="header-actions">
                    <?php include __DIR__ . '/../../includes/header-notifications.php'; ?>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php $upload_targets = handbook_allowed_upload_targets($role_type); ?>
            <div class="<?php echo $can_upload_handbook ? 'grid-2' : 'grid-1'; ?>">
                <?php if ($can_upload_handbook): ?>
                <div class="card">
                    <h2><i data-lucide="upload"></i> Upload Document</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="upload_handbook" value="1">

                        <div class="form-group">
                            <label>Document Title *</label>
                            <input type="text" name="handbook_title" required placeholder="e.g., Employee Safety Manual">
                        </div>

                        <div class="form-group">
                            <label>Version *</label>
                            <input type="text" name="version" required value="1.0" placeholder="e.g., 1.0 or 2.1.3">
                        </div>

                        <div class="form-group">
                            <label>Effective Date *</label>
                            <input type="date" name="effective_date" required value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="form-group">
                            <label>Audience</label>
                            <div style="margin-bottom:0.45rem;font-size:0.8rem;color:#94a3b8;">
                                <?php if ($role_type === 'HR_Staff'): ?>
                                    Upload target: <strong style="color:#f8fafc;">Manager only</strong>
                                <?php elseif ($role_type === 'Manager'): ?>
                                    Upload target: <strong style="color:#f8fafc;">Employee only</strong>
                                <?php elseif ($role_type === 'Admin'): ?>
                                    Upload target: <strong style="color:#f8fafc;">Any role</strong>
                                <?php endif; ?>
                            </div>
                            <select name="audience">
                                <?php foreach ($upload_targets as $target_key => $target_type): ?>
                                    <option value="<?php echo htmlspecialchars($target_key); ?>">
                                        <?php echo htmlspecialchars(handbook_audience_label($target_type)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
                <?php endif; ?>

                <div class="card">
                    <h2><i data-lucide="library"></i> Handbook Library</h2>
                    <?php if (empty($handbooks)): ?>
                        <div class="empty-state">
                            <i data-lucide="book-open" style="width:3rem;height:3rem;margin-bottom:0.5rem;"></i>
                            <p>No handbooks uploaded yet</p>
                        </div>
                    <?php else: ?>
                    <div class="handbook-grid">
                        <?php foreach ($handbooks as $hb): ?>
                        <?php $public_path = handbook_public_path($hb['file_path'] ?? ''); ?>
                        <div class="handbook-item">
                            <div class="handbook-icon"><i data-lucide="file-text"></i></div>
                            <div class="handbook-title"><?php echo htmlspecialchars($hb['meta_title'] ?? ($hb['document_name'] ?? '')); ?></div>
                            <span class="handbook-type"><?php echo htmlspecialchars(handbook_audience_label($hb['document_type'] ?? '')); ?></span>
                            <?php if (!empty($hb['is_latest'])): ?>
                                <span class="latest-badge">LATEST</span>
                            <?php endif; ?>
                            <div class="handbook-meta">
                                Version: <?php echo htmlspecialchars($hb['meta_version'] ?? '1.0'); ?>
                                <?php if (!empty($hb['meta_effective_date'])): ?>
                                    <br>Effective: <?php echo date('M d, Y', strtotime($hb['meta_effective_date'])); ?>
                                <?php endif; ?>
                                <br>
                                Uploaded: <?php echo date('M d, Y', strtotime($hb['uploaded_at'])); ?>
                                <?php if (!empty($hb['first_name'])): ?>
                                    <br>By: <?php echo htmlspecialchars($hb['first_name'] . ' ' . $hb['last_name']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="handbook-actions">
                                <a class="action-btn" href="#" onclick="toggleViewer('viewer-<?php echo (int)$hb['id']; ?>'); return false;">
                                    <i data-lucide="monitor"></i> Web View
                                </a>
                                <a class="action-btn" href="<?php echo htmlspecialchars($public_path); ?>" target="_blank" rel="noopener noreferrer">
                                    <i data-lucide="external-link"></i> Open PDF
                                </a>
                                <a class="action-btn" href="<?php echo htmlspecialchars($public_path); ?>" download>
                                    <i data-lucide="download"></i> Download PDF
                                </a>
                            </div>
                            <div class="inline-viewer" id="viewer-<?php echo (int)$hb['id']; ?>">
                                <iframe src="<?php echo htmlspecialchars($public_path); ?>#view=FitH" title="Handbook PDF viewer"></iframe>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
<script>
    const fileInput = document.getElementById('fileInput');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                document.getElementById('fileName').textContent = this.files[0].name;
            }
        });
    }

    function toggleViewer(id) {
        const viewer = document.getElementById(id);
        if (viewer) {
            viewer.classList.toggle('active');
        }
    }

    if (typeof lucide !== 'undefined') { lucide.createIcons(); }
</script>
</div>
