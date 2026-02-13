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

if (!isHRStaff($userId) && !isAdmin($userId)) {
    header('Location: ../../index.php');
    exit();
}

if (!$is_ajax) {
    header('Location: index.php?page=documents');
    exit();
}

require_once '../../database/config.php';

$hr_user_id = $_SESSION['user_id'];

// Handle verify/reject actions via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_ajax_post = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $action = $_POST['action'] ?? '';
    $doc_id = intval($_POST['doc_id'] ?? 0);
    
    if ($doc_id > 0 && in_array($action, ['approve', 'reject'])) {
        $new_status = $action === 'approve' ? 'approved' : 'rejected';
        $remarks = trim($_POST['remarks'] ?? '');
        
        try {
            executeQuery(
                "UPDATE employee_requirements SET status = ?, remarks = ?, verified_by = ?, verified_at = NOW(), updated_at = NOW() WHERE id = ?",
                [$new_status, $remarks, $hr_user_id, $doc_id]
            );
            if ($is_ajax_post) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Document ' . ($action === 'approve' ? 'approved' : 'rejected') . ' successfully.', 'reload' => true]);
                exit();
            }
        } catch (Exception $e) {
            if ($is_ajax_post) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
                exit();
            }
        }
    }
}

// Filter
$filter_status = $_GET['status'] ?? 'all';

// Fetch submitted documents from employee_requirements
$documents = [];
try {
    $where = '';
    $params = [];
    if ($filter_status !== 'all') {
        $where = 'WHERE er.status = ?';
        $params[] = $filter_status;
    }
    $documents = fetchAll("
        SELECT er.*, ua.first_name, ua.last_name, ua.employee_id, d.department_name,
               v.first_name as verifier_first, v.last_name as verifier_last
        FROM employee_requirements er
        LEFT JOIN user_accounts ua ON er.user_id = ua.id
        LEFT JOIN departments d ON ua.department_id = d.id
        LEFT JOIN user_accounts v ON er.verified_by = v.id
        $where
        ORDER BY FIELD(er.status, 'pending', 'rejected', 'approved'), er.uploaded_at DESC
    ", $params);
} catch (Exception $e) {
    $documents = [];
}

// Stats
$stats_all = count($documents);
$stats_pending = count(array_filter($documents, fn($d) => $d['status'] === 'pending'));
$stats_approved = count(array_filter($documents, fn($d) => $d['status'] === 'approved'));
$stats_rejected = count(array_filter($documents, fn($d) => $d['status'] === 'rejected'));

// Document type labels
$doc_type_labels = [
    'valid_id' => 'Valid Government ID',
    'nbi_clearance' => 'NBI Clearance',
    'barangay_clearance' => 'Barangay Clearance',
    'birth_certificate' => 'Birth Certificate',
    'sss_id' => 'SSS ID/E1 Form',
    'philhealth_id' => 'PhilHealth ID/MDR',
    'pagibig_id' => 'Pag-IBIG ID/MID',
    'tin_id' => 'TIN ID/Certificate',
    'diploma' => 'Diploma/TOR',
    'medical_cert' => 'Medical Certificate',
    'drivers_license' => "Driver's License",
    '2x2_photo' => '2x2 ID Photo',
];
?>
<div data-page-title="Document Verification">
<style>
    .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; }
    .header h1 { font-size: 1.35rem; color: #e2e8f0; margin-bottom: 0.25rem; }
    .header p { color: #94a3b8; font-size: 0.85rem; }

    .stats-row { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
    .stat-pill { padding: 0.5rem 1.25rem; border-radius: 8px; font-size: 0.85rem; font-weight: 500; cursor: pointer; border: 1px solid rgba(58,69,84,0.5); background: rgba(30,41,54,0.6); color: #94a3b8; transition: all 0.3s; text-decoration: none; }
    .stat-pill:hover { border-color: #0ea5e9; color: #e2e8f0; }
    .stat-pill.active { background: rgba(14,165,233,0.15); border-color: #0ea5e9; color: #0ea5e9; }
    .stat-pill .count { font-weight: 700; margin-right: 0.3rem; }

    .doc-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; height: calc(100vh - 260px); }
    .panel { background: rgba(30,41,54,0.6); border-radius: 12px; border: 1px solid rgba(58,69,84,0.5); display: flex; flex-direction: column; overflow: hidden; }
    .panel-header { padding: 1rem 1.25rem; border-bottom: 1px solid rgba(58,69,84,0.5); font-weight: 600; color: #e2e8f0; display: flex; justify-content: space-between; align-items: center; font-size: 0.95rem; }
    .panel-header i { width: 18px; height: 18px; vertical-align: middle; margin-right: 0.3rem; }
    .panel-body { flex: 1; overflow-y: auto; padding: 1rem; }

    .doc-item { background: rgba(15,23,42,0.6); border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem; cursor: pointer; border: 2px solid transparent; transition: all 0.2s; }
    .doc-item:hover { border-color: rgba(14,165,233,0.5); }
    .doc-item.selected { border-color: #0ea5e9; background: rgba(14,165,233,0.08); }
    .doc-item-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.4rem; }
    .doc-employee-name { font-weight: 600; color: #e2e8f0; font-size: 0.9rem; }
    .doc-status-badge { padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.72rem; font-weight: 600; text-transform: capitalize; }
    .doc-status-badge.pending { background: rgba(245,158,11,0.2); color: #fbbf24; }
    .doc-status-badge.approved { background: rgba(16,185,129,0.2); color: #10b981; }
    .doc-status-badge.rejected { background: rgba(239,68,68,0.2); color: #ef4444; }
    .doc-type { font-size: 0.85rem; color: #0ea5e9; margin-bottom: 0.2rem; }
    .doc-meta { font-size: 0.78rem; color: #94a3b8; }

    .preview-area { flex: 1; display: flex; align-items: center; justify-content: center; background: rgba(10,25,41,0.6); margin: 1rem; border-radius: 8px; min-height: 300px; overflow: hidden; }
    .preview-area img { max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 4px; }
    .preview-area iframe { width: 100%; height: 100%; border: none; border-radius: 4px; }
    .preview-placeholder { text-align: center; color: #64748b; }
    .preview-placeholder i { width: 4rem; height: 4rem; margin-bottom: 1rem; }

    .doc-actions-bar { padding: 1rem; display: flex; gap: 0.75rem; border-top: 1px solid rgba(58,69,84,0.5); }
    .btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.6rem 1.25rem; border-radius: 8px; font-size: 0.85rem; font-weight: 500; cursor: pointer; border: none; transition: all 0.3s; flex: 1; justify-content: center; color: white; }
    .btn-approve { background: #10b981; }
    .btn-approve:hover { background: #059669; }
    .btn-reject { background: #ef4444; }
    .btn-reject:hover { background: #dc2626; }
    .btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .btn i { width: 16px; height: 16px; }

    .empty-state { text-align: center; padding: 3rem 1rem; color: #64748b; }
    .empty-state i { width: 3rem; height: 3rem; margin-bottom: 1rem; }

    .doc-detail-row { font-size: 0.8rem; color: #94a3b8; margin-top: 0.3rem; }
    .doc-detail-row strong { color: #cbd5e1; }

    @media (max-width: 1024px) { .doc-layout { grid-template-columns: 1fr; height: auto; } }
    @media (max-width: 768px) { .stats-row { flex-direction: column; } }
</style>

    <div class="header">
        <div>
            <h1>Document Verification</h1>
            <p>Review and verify employee submitted documents</p>
        </div>
        <div class="header-actions">
            <?php include '../../includes/header-notifications.php'; ?>
        </div>
    </div>

    <div class="stats-row">
        <a href="#" class="stat-pill <?php echo $filter_status === 'all' ? 'active' : ''; ?>" onclick="filterDocs('all')"><span class="count"><?php echo $stats_all; ?></span> All</a>
        <a href="#" class="stat-pill <?php echo $filter_status === 'pending' ? 'active' : ''; ?>" onclick="filterDocs('pending')"><span class="count"><?php echo $stats_pending; ?></span> Pending</a>
        <a href="#" class="stat-pill <?php echo $filter_status === 'approved' ? 'active' : ''; ?>" onclick="filterDocs('approved')"><span class="count"><?php echo $stats_approved; ?></span> Approved</a>
        <a href="#" class="stat-pill <?php echo $filter_status === 'rejected' ? 'active' : ''; ?>" onclick="filterDocs('rejected')"><span class="count"><?php echo $stats_rejected; ?></span> Rejected</a>
    </div>

    <div class="doc-layout">
        <div class="panel">
            <div class="panel-header">
                <span><i data-lucide="file-text"></i> Submitted Documents</span>
                <span style="font-size:0.8rem;color:#94a3b8;"><?php echo count($documents); ?> items</span>
            </div>
            <div class="panel-body">
                <?php if (empty($documents)): ?>
                    <div class="empty-state">
                        <i data-lucide="inbox"></i>
                        <p>No documents found<?php echo $filter_status !== 'all' ? ' with status "' . $filter_status . '"' : ''; ?>.</p>
                        <p style="font-size:0.8rem;margin-top:0.5rem;">Documents will appear here when employees upload their requirements.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($documents as $idx => $doc): 
                        $doc_label = $doc_type_labels[$doc['document_type']] ?? ucwords(str_replace('_', ' ', $doc['document_type']));
                    ?>
                    <div class="doc-item" data-doc-id="<?php echo $doc['id']; ?>" data-file-path="<?php echo htmlspecialchars($doc['file_path']); ?>" data-status="<?php echo $doc['status']; ?>" onclick="selectDocument(this)">
                        <div class="doc-item-header">
                            <span class="doc-employee-name"><?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></span>
                            <span class="doc-status-badge <?php echo $doc['status']; ?>"><?php echo $doc['status']; ?></span>
                        </div>
                        <div class="doc-type"><?php echo htmlspecialchars($doc_label); ?></div>
                        <div class="doc-meta">
                            Uploaded: <?php echo date('M d, Y h:i A', strtotime($doc['uploaded_at'])); ?>
                            &bull; <?php echo htmlspecialchars($doc['department_name'] ?? 'N/A'); ?>
                        </div>
                        <?php if ($doc['status'] === 'approved' && $doc['verifier_first']): ?>
                        <div class="doc-detail-row">Approved by: <strong><?php echo htmlspecialchars($doc['verifier_first'] . ' ' . $doc['verifier_last']); ?></strong> on <?php echo date('M d, Y', strtotime($doc['verified_at'])); ?></div>
                        <?php elseif ($doc['status'] === 'rejected'): ?>
                        <div class="doc-detail-row" style="color:#ef4444;">Reason: <?php echo htmlspecialchars($doc['remarks'] ?: 'No reason given'); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <span><i data-lucide="eye"></i> Preview</span>
                <span id="previewDocType" style="font-size:0.8rem;color:#94a3b8;">Select a document</span>
            </div>
            <div class="preview-area" id="docPreviewArea">
                <div class="preview-placeholder">
                    <i data-lucide="file-search"></i>
                    <p>Select a document from the list to preview</p>
                </div>
            </div>
            <div class="doc-actions-bar">
                <button class="btn btn-approve" id="btnApprove" onclick="approveDocument()" disabled>
                    <i data-lucide="check-circle"></i> Approve
                </button>
                <button class="btn btn-reject" id="btnReject" onclick="rejectDocument()" disabled>
                    <i data-lucide="x-circle"></i> Reject
                </button>
            </div>
        </div>
    </div>

<script>
    let selectedDocEl = null;
    let selectedDocId = null;

    function filterDocs(status) {
        event.preventDefault();
        if (typeof HR1SPA !== 'undefined') {
            HR1SPA.loadPage('documents', true, status !== 'all' ? 'status=' + status : '');
        }
    }

    function selectDocument(el) {
        if (selectedDocEl) selectedDocEl.classList.remove('selected');
        el.classList.add('selected');
        selectedDocEl = el;
        selectedDocId = el.getAttribute('data-doc-id');
        var filePath = el.getAttribute('data-file-path');
        var status = el.getAttribute('data-status');
        var name = el.querySelector('.doc-employee-name').textContent;
        var docType = el.querySelector('.doc-type').textContent;

        document.getElementById('previewDocType').textContent = docType + ' — ' + name;

        var previewArea = document.getElementById('docPreviewArea');
        var fullPath = '../../' + filePath;
        var ext = filePath.split('.').pop().toLowerCase();

        if (['jpg','jpeg','png','gif','webp'].includes(ext)) {
            previewArea.innerHTML = '<img src="' + fullPath + '" alt="Document Preview">';
        } else if (ext === 'pdf') {
            previewArea.innerHTML = '<iframe src="' + fullPath + '"></iframe>';
        } else {
            previewArea.innerHTML = '<div class="preview-placeholder"><i data-lucide="file"></i><p>' + docType + '</p><p style="margin-top:0.5rem;"><a href="' + fullPath + '" target="_blank" style="color:#0ea5e9;">Download File</a></p></div>';
        }

        // Enable/disable buttons based on status
        var btnApprove = document.getElementById('btnApprove');
        var btnReject = document.getElementById('btnReject');
        btnApprove.disabled = (status === 'approved');
        btnReject.disabled = (status === 'rejected');

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function docAction(action, remarks) {
        if (!selectedDocId) return;
        var formData = new FormData();
        formData.append('action', action);
        formData.append('doc_id', selectedDocId);
        formData.append('remarks', remarks || '');

        fetch('documents.php?ajax=1', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                if (typeof HR1SPA !== 'undefined') {
                    HR1SPA.showToast(data.message, 'success');
                    HR1SPA.loadPage('documents');
                } else {
                    alert(data.message);
                    location.reload();
                }
            } else {
                if (typeof HR1SPA !== 'undefined') HR1SPA.showToast(data.message, 'error');
                else alert(data.message);
            }
        })
        .catch(function(err) {
            console.error(err);
            alert('Action failed. Please try again.');
        });
    }

    function approveDocument() {
        if (!selectedDocId) { alert('Please select a document first.'); return; }
        if (confirm('Approve this document as verified and authentic?')) {
            docAction('approve', '');
        }
    }

    function rejectDocument() {
        if (!selectedDocId) { alert('Please select a document first.'); return; }
        var reason = prompt('Enter rejection reason:');
        if (reason !== null && reason.trim() !== '') {
            docAction('reject', reason.trim());
        }
    }

    if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
</div>
