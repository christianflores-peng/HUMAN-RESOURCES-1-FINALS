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
    header('Location: index.php?page=job-postings');
    exit();
}

require_once '../../database/config.php';

// Get departments
$departments = [];
try {
    $departments = fetchAll("SELECT * FROM departments ORDER BY department_name");
} catch (Exception $e) {
    $departments = [];
}

// Handle Create/Update
$successMsg = null;
$errorMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_posting'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $requirements = trim($_POST['requirements']);
        $departmentId = intval($_POST['department_id']);
        $employmentType = trim($_POST['employment_type']);
        $location = trim($_POST['location']);

        try {
            insertRecord(
                "INSERT INTO job_postings (title, description, requirements, department_id, employment_type, location, status, created_by, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, 'Open', ?, NOW())",
                [$title, $description, $requirements, $departmentId, $employmentType, $location, $userId]
            );
            $successMsg = "Job posting created successfully!";
        } catch (Exception $e) {
            $errorMsg = "Failed to create posting: " . $e->getMessage();
        }
    }

    if (isset($_POST['update_posting'])) {
        $postingId = intval($_POST['posting_id']);
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $requirements = trim($_POST['requirements']);
        $departmentId = intval($_POST['department_id']);
        $employmentType = trim($_POST['employment_type']);
        $location = trim($_POST['location']);

        try {
            updateRecord(
                "UPDATE job_postings SET title=?, description=?, requirements=?, department_id=?, employment_type=?, location=? WHERE id=?",
                [$title, $description, $requirements, $departmentId, $employmentType, $location, $postingId]
            );
            $successMsg = "Job posting updated successfully!";
        } catch (Exception $e) {
            $errorMsg = "Failed to update posting: " . $e->getMessage();
        }
    }

    if (isset($_POST['toggle_status'])) {
        $postingId = intval($_POST['posting_id']);
        $newStatus = $_POST['new_status'];
        try {
            updateRecord("UPDATE job_postings SET status=? WHERE id=?", [$newStatus, $postingId]);
            $successMsg = "Posting status changed to {$newStatus}!";
        } catch (Exception $e) {
            $errorMsg = "Failed to update status: " . $e->getMessage();
        }
    }
}

// Fetch postings
$filterStatus = $_GET['status'] ?? '';
$where = "1=1";
$params = [];
if ($filterStatus) {
    $where .= " AND jp.status = ?";
    $params[] = $filterStatus;
}

try {
    $postings = fetchAll(
        "SELECT jp.*, d.department_name, 
                (SELECT COUNT(*) FROM job_applications ja WHERE ja.job_posting_id = jp.id) as application_count
         FROM job_postings jp
         LEFT JOIN departments d ON jp.department_id = d.id
         WHERE {$where}
         ORDER BY jp.created_at DESC",
        $params
    );
} catch (Exception $e) {
    $postings = [];
}

try {
    $totalOpen = fetchSingle("SELECT COUNT(*) as c FROM job_postings WHERE status='Open'")['c'] ?? 0;
    $totalClosed = fetchSingle("SELECT COUNT(*) as c FROM job_postings WHERE status='Closed'")['c'] ?? 0;
    $totalAll = $totalOpen + $totalClosed;
} catch (Exception $e) {
    $totalOpen = 0; $totalClosed = 0; $totalAll = 0;
}
?>
<div data-page-title="Job Postings">
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

        .header h1 { font-size: 1.35rem; color: #e2e8f0; }
        .header p { color: #94a3b8; font-size: 0.85rem; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.25rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            color: white;
        }

        .btn-primary { background: #0ea5e9; }
        .btn-primary:hover { background: #0284c7; }
        .btn-success { background: #10b981; }
        .btn-success:hover { background: #059669; }
        .btn-warning { background: #f59e0b; color: #1a2942; }
        .btn-warning:hover { background: #d97706; }
        .btn-danger { background: #ef4444; }
        .btn-danger:hover { background: #dc2626; }
        .btn-sm { padding: 0.35rem 0.75rem; font-size: 0.8rem; }

        .status-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .status-tab {
            padding: 0.5rem 1rem;
            background: rgba(30, 41, 54, 0.6);
            border: 1px solid rgba(58, 69, 84, 0.5);
            border-radius: 8px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .status-tab:hover, .status-tab.active {
            background: rgba(14, 165, 233, 0.15);
            border-color: #0ea5e9;
            color: #0ea5e9;
        }

        .status-tab .count {
            background: rgba(58, 69, 84, 0.5);
            padding: 0.1rem 0.4rem;
            border-radius: 6px;
            font-size: 0.75rem;
            margin-left: 0.3rem;
        }

        .status-tab.active .count { background: rgba(14, 165, 233, 0.3); }

        .postings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1.25rem;
        }

        .posting-card {
            background: rgba(30, 41, 54, 0.6);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(58, 69, 84, 0.5);
            transition: transform 0.2s;
        }

        .posting-card:hover { transform: translateY(-2px); }

        .posting-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.75rem;
        }

        .posting-title { font-size: 1.05rem; font-weight: 600; color: #e2e8f0; }

        .posting-status {
            padding: 0.25rem 0.6rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .posting-status.open { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .posting-status.closed { background: rgba(239, 68, 68, 0.2); color: #f87171; }

        .posting-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 0.75rem;
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .posting-meta span {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .posting-meta i { width: 14px; height: 14px; }

        .posting-desc {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .posting-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 0.75rem;
            border-top: 1px solid rgba(58, 69, 84, 0.5);
        }

        .app-count {
            font-size: 0.85rem;
            color: #0ea5e9;
            font-weight: 500;
        }

        .posting-actions { display: flex; gap: 0.5rem; }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-success { background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #6ee7b7; }
        .alert-error { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; }

        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active { display: flex; }

        .modal-content {
            background: rgba(30, 41, 54, 0.98);
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid rgba(58, 69, 84, 0.5);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(58, 69, 84, 0.5);
        }

        .modal-header h2 {
            font-size: 1.2rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-close {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 1.5rem;
        }

        .modal-close:hover { color: #ef4444; }

        .modal-body { padding: 1.5rem; }

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

        .form-group textarea { min-height: 100px; resize: vertical; }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0ea5e9;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #94a3b8;
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .postings-grid { grid-template-columns: 1fr; }
        }
    </style>
            <div class="header">
                <div>
                    <h1>Job Postings</h1>
                    <p>Create and manage job openings</p>
                </div>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                    <button class="btn btn-primary" onclick="openCreateModal()">
                        <i data-lucide="plus" style="width:18px;height:18px;"></i> New Posting
                    </button>
                </div>
            </div>

            <?php if ($successMsg): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($errorMsg); ?></div>
            <?php endif; ?>

            <div class="status-tabs">
                <a href="job-postings.php" class="status-tab <?php echo !$filterStatus ? 'active' : ''; ?>">All <span class="count"><?php echo $totalAll; ?></span></a>
                <a href="job-postings.php?status=Open" class="status-tab <?php echo $filterStatus === 'Open' ? 'active' : ''; ?>">Open <span class="count"><?php echo $totalOpen; ?></span></a>
                <a href="job-postings.php?status=Closed" class="status-tab <?php echo $filterStatus === 'Closed' ? 'active' : ''; ?>">Closed <span class="count"><?php echo $totalClosed; ?></span></a>
            </div>

            <?php if (empty($postings)): ?>
                <div class="empty-state">
                    <i data-lucide="briefcase" style="width:3rem;height:3rem;margin-bottom:1rem;"></i>
                    <p>No job postings found. Create your first posting!</p>
                </div>
            <?php else: ?>
            <div class="postings-grid">
                <?php foreach ($postings as $posting): ?>
                <div class="posting-card">
                    <div class="posting-header">
                        <div class="posting-title"><?php echo htmlspecialchars($posting['title']); ?></div>
                        <span class="posting-status <?php echo strtolower($posting['status']); ?>"><?php echo htmlspecialchars($posting['status']); ?></span>
                    </div>
                    <div class="posting-meta">
                        <span><i data-lucide="building-2"></i> <?php echo htmlspecialchars($posting['department_name'] ?? 'N/A'); ?></span>
                        <span><i data-lucide="map-pin"></i> <?php echo htmlspecialchars($posting['location'] ?? 'N/A'); ?></span>
                        <span><i data-lucide="clock"></i> <?php echo htmlspecialchars($posting['employment_type'] ?? 'Full-time'); ?></span>
                    </div>
                    <div class="posting-desc"><?php echo htmlspecialchars($posting['description'] ?? ''); ?></div>
                    <div class="posting-footer">
                        <span class="app-count">
                            <i data-lucide="users" style="width:14px;height:14px;vertical-align:middle;"></i>
                            <?php echo $posting['application_count']; ?> applicant<?php echo $posting['application_count'] != 1 ? 's' : ''; ?>
                        </span>
                        <div class="posting-actions">
                            <button class="btn btn-sm btn-primary" onclick='openEditModal(<?php echo json_encode($posting); ?>)'>
                                <i data-lucide="pencil" style="width:14px;height:14px;"></i> Edit
                            </button>
                            <?php if ($posting['status'] === 'Open'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="toggle_status" value="1">
                                <input type="hidden" name="posting_id" value="<?php echo $posting['id']; ?>">
                                <input type="hidden" name="new_status" value="Closed">
                                <button type="submit" class="btn btn-sm btn-danger"
                                    data-confirm-title="Close Posting"
                                    data-confirm-variant="danger"
                                    data-confirm-ok="Yes, close"
                                    data-confirm-cancel="Cancel"
                                    data-confirm="Close this posting?">
                                    <i data-lucide="x-circle" style="width:14px;height:14px;"></i> Close
                                </button>
                            </form>
                            <?php else: ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="toggle_status" value="1">
                                <input type="hidden" name="posting_id" value="<?php echo $posting['id']; ?>">
                                <input type="hidden" name="new_status" value="Open">
                                <button type="submit" class="btn btn-sm btn-success"
                                    data-confirm-title="Reopen Posting"
                                    data-confirm-ok="Yes, reopen"
                                    data-confirm-cancel="Cancel"
                                    data-confirm="Reopen this posting?">
                                    <i data-lucide="check-circle" style="width:14px;height:14px;"></i> Reopen
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
    <!-- Create/Edit Modal -->
    <div class="modal" id="postingModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i data-lucide="briefcase"></i> <span id="modalTitle">New Job Posting</span></h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="postingForm">
                    <input type="hidden" name="posting_id" id="postingId">

                    <div class="form-group">
                        <label>Job Title *</label>
                        <input type="text" name="title" id="postingTitleInput" required placeholder="e.g., Truck Driver - Class A">
                    </div>

                    <div class="form-group">
                        <label>Department *</label>
                        <select name="department_id" id="postingDept" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Employment Type</label>
                        <select name="employment_type" id="postingType">
                            <option value="Full-time">Full-time</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Contract">Contract</option>
                            <option value="Temporary">Temporary</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" id="postingLocation" placeholder="e.g., Metro Manila">
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="postingDesc" placeholder="Job description..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Requirements</label>
                        <textarea name="requirements" id="postingReqs" placeholder="Job requirements..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-success" style="width:100%;justify-content:center;" id="submitBtn">
                        <i data-lucide="check-circle"></i> <span id="submitLabel">Create Posting</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'New Job Posting';
            document.getElementById('submitLabel').textContent = 'Create Posting';
            document.getElementById('postingForm').reset();
            document.getElementById('postingId').value = '';

            // Set hidden field for create
            let existing = document.querySelector('input[name="create_posting"]');
            if (!existing) {
                let input = document.createElement('input');
                input.type = 'hidden'; input.name = 'create_posting'; input.value = '1';
                document.getElementById('postingForm').appendChild(input);
            }
            let upd = document.querySelector('input[name="update_posting"]');
            if (upd) upd.remove();

            document.getElementById('postingModal').classList.add('active');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function openEditModal(posting) {
            document.getElementById('modalTitle').textContent = 'Edit Job Posting';
            document.getElementById('submitLabel').textContent = 'Update Posting';
            document.getElementById('postingId').value = posting.id;
            document.getElementById('postingTitleInput').value = posting.title || '';
            document.getElementById('postingDept').value = posting.department_id || '';
            document.getElementById('postingType').value = posting.employment_type || 'Full-time';
            document.getElementById('postingLocation').value = posting.location || '';
            document.getElementById('postingDesc').value = posting.description || '';
            document.getElementById('postingReqs').value = posting.requirements || '';

            // Set hidden field for update
            let existing = document.querySelector('input[name="update_posting"]');
            if (!existing) {
                let input = document.createElement('input');
                input.type = 'hidden'; input.name = 'update_posting'; input.value = '1';
                document.getElementById('postingForm').appendChild(input);
            }
            let crt = document.querySelector('input[name="create_posting"]');
            if (crt) crt.remove();

            document.getElementById('postingModal').classList.add('active');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function closeModal() {
            document.getElementById('postingModal').classList.remove('active');
        }

        document.getElementById('postingModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

    if (typeof lucide !== 'undefined') { lucide.createIcons(); }
</script>
</div>
