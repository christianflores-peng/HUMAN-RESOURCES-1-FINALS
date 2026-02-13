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
    header('Location: index.php?page=documents');
    exit();
}

require_once '../../database/config.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

$employee = fetchSingle("
    SELECT ua.*, d.department_name, ua.job_title AS position_name
    FROM user_accounts ua
    LEFT JOIN departments d ON d.id = ua.department_id
    WHERE ua.id = ?
", [$user_id]);

// Fetch submitted requirements (the actual documents employees upload)
$documents = [];
try {
    $documents = fetchAll("
        SELECT * FROM employee_requirements 
        WHERE user_id = ? 
        ORDER BY uploaded_at DESC
    ", [$user_id]);
} catch (Exception $e) {
    $documents = [];
}

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

$total_docs = count($documents);
$approved_docs = count(array_filter($documents, fn($d) => $d['status'] === 'approved'));
$pending_docs = count(array_filter($documents, fn($d) => $d['status'] === 'pending'));
$rejected_docs = count(array_filter($documents, fn($d) => $d['status'] === 'rejected'));
?>
<div data-page-title="My Documents">
<style>
        .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5rem; color: #e2e8f0; margin-bottom: 0.25rem; }
        .header p { color: #94a3b8; font-size: 0.9rem; }
        .doc-stats { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .doc-stat { background: rgba(30, 41, 54, 0.6); border-radius: 10px; padding: 0.75rem 1.25rem; border: 1px solid rgba(58, 69, 84, 0.5); display: flex; align-items: center; gap: 0.75rem; }
        .doc-stat .num { font-size: 1.25rem; font-weight: 700; color: #e2e8f0; }
        .doc-stat .lbl { font-size: 0.8rem; color: #94a3b8; }
        .doc-stat.approved .num { color: #10b981; }
        .doc-stat.pending .num { color: #fbbf24; }
        .doc-stat.rejected .num { color: #ef4444; }
        .documents-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
        .doc-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); transition: all 0.3s; }
        .doc-card:hover { border-color: #0ea5e9; }
        .doc-card.status-approved { border-left: 3px solid #10b981; }
        .doc-card.status-pending { border-left: 3px solid #fbbf24; }
        .doc-card.status-rejected { border-left: 3px solid #ef4444; }
        .doc-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; }
        .doc-icon.approved { background: rgba(16, 185, 129, 0.2); }
        .doc-icon.approved i { color: #10b981; }
        .doc-icon.pending { background: rgba(251, 191, 36, 0.2); }
        .doc-icon.pending i { color: #fbbf24; }
        .doc-icon.rejected { background: rgba(239, 68, 68, 0.2); }
        .doc-icon.rejected i { color: #ef4444; }
        .doc-icon i { width: 1.5rem; height: 1.5rem; }
        .doc-name { color: #e2e8f0; font-weight: 600; margin-bottom: 0.5rem; word-break: break-word; }
        .doc-meta { color: #94a3b8; font-size: 0.8rem; margin-bottom: 0.75rem; }
        .doc-status { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.25rem 0.6rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.75rem; }
        .doc-status.approved { background: rgba(16, 185, 129, 0.15); color: #10b981; }
        .doc-status.pending { background: rgba(251, 191, 36, 0.15); color: #fbbf24; }
        .doc-status.rejected { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
        .doc-remarks { font-size: 0.8rem; color: #ef4444; background: rgba(239, 68, 68, 0.08); padding: 0.5rem 0.75rem; border-radius: 6px; margin-bottom: 0.75rem; }
        .doc-actions { display: flex; gap: 0.75rem; }
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 6px; font-size: 0.85rem; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem; }
        .btn-primary { background: #0ea5e9; color: white; }
        .btn-primary:hover { background: #0284c7; }
        .btn-outline { background: transparent; color: #94a3b8; border: 1px solid rgba(58, 69, 84, 0.5); }
        .btn-outline:hover { border-color: #0ea5e9; color: #0ea5e9; }
        .empty-state { text-align: center; padding: 4rem 2rem; color: #94a3b8; }
        .empty-state i { width: 5rem; height: 5rem; color: #475569; margin-bottom: 1rem; }
    </style>
            <div class="header">
                <div>
                    <h1>My Documents</h1>
                    <p>View all your submitted requirement documents and their verification status</p>
                </div>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                </div>
            </div>

            <div class="doc-stats">
                <div class="doc-stat"><div class="num"><?php echo $total_docs; ?></div><div class="lbl">Total Submitted</div></div>
                <div class="doc-stat approved"><div class="num"><?php echo $approved_docs; ?></div><div class="lbl">Approved</div></div>
                <div class="doc-stat pending"><div class="num"><?php echo $pending_docs; ?></div><div class="lbl">Pending Review</div></div>
                <?php if ($rejected_docs > 0): ?>
                <div class="doc-stat rejected"><div class="num"><?php echo $rejected_docs; ?></div><div class="lbl">Rejected</div></div>
                <?php endif; ?>
            </div>

            <?php if (empty($documents)): ?>
            <div class="empty-state">
                <i data-lucide="folder-x"></i>
                <h3>No Documents Yet</h3>
                <p>You haven't submitted any documents yet. Go to <a href="#" onclick="if(typeof HR1SPA!=='undefined') HR1SPA.loadPage('requirements'); return false;" style="color:#0ea5e9;">Submit Requirements</a> to upload your documents.</p>
            </div>
            <?php else: ?>
            <div class="documents-grid">
                <?php foreach ($documents as $doc):
                    $doc_label = $doc_type_labels[$doc['document_type']] ?? ucwords(str_replace('_', ' ', $doc['document_type']));
                    $status = $doc['status'] ?? 'pending';
                    $status_icon = match($status) {
                        'approved' => 'check-circle',
                        'rejected' => 'x-circle',
                        default => 'clock'
                    };
                    $status_label = match($status) {
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        default => 'Pending Review'
                    };
                ?>
                <div class="doc-card status-<?php echo $status; ?>">
                    <div class="doc-icon <?php echo $status; ?>"><i data-lucide="file-text"></i></div>
                    <div class="doc-name"><?php echo htmlspecialchars($doc_label); ?></div>
                    <span class="doc-status <?php echo $status; ?>">
                        <i data-lucide="<?php echo $status_icon; ?>" style="width:0.85rem;height:0.85rem;"></i>
                        <?php echo $status_label; ?>
                    </span>
                    <div class="doc-meta">
                        <div>Uploaded: <?php echo date('M d, Y h:i A', strtotime($doc['uploaded_at'])); ?></div>
                        <?php if (!empty($doc['verified_at'])): ?>
                        <div>Verified: <?php echo date('M d, Y', strtotime($doc['verified_at'])); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if ($status === 'rejected' && !empty($doc['remarks'])): ?>
                    <div class="doc-remarks">
                        <i data-lucide="alert-circle" style="width:0.85rem;height:0.85rem;display:inline;vertical-align:middle;margin-right:0.3rem;"></i>
                        <?php echo htmlspecialchars($doc['remarks']); ?>
                    </div>
                    <?php endif; ?>
                    <div class="doc-actions">
                        <a href="../../<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn btn-primary">
                            <i data-lucide="eye" style="width: 1rem; height: 1rem;"></i>View
                        </a>
                        <?php if ($status === 'rejected'): ?>
                        <a href="#" onclick="if(typeof HR1SPA!=='undefined') HR1SPA.loadPage('requirements'); return false;" class="btn btn-outline">
                            <i data-lucide="upload" style="width: 1rem; height: 1rem;"></i>Re-upload
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
</div>
