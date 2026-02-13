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
    header('Location: index.php?page=my-team');
    exit();
}

require_once '../../database/config.php';
require_once '../../includes/email_generator.php';

$departmentName = $user['department_name'] ?? 'All Departments';

// Handle performance review submission
$successMessage = null;
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $employeeId = intval($_POST['employee_id']);
    $safetyScore = intval($_POST['safety_score']);
    $punctualityScore = intval($_POST['punctuality_score']);
    $qualityScore = intval($_POST['quality_score']);
    $teamworkScore = intval($_POST['teamwork_score']);
    $recommendation = $_POST['recommendation'];
    $comments = trim($_POST['comments']);
    $overallScore = ($safetyScore + $punctualityScore + $qualityScore + $teamworkScore) / 4;

    try {
        insertRecord(
            "INSERT INTO performance_reviews (
                employee_id, reviewer_id, review_type, review_period_start, review_period_end,
                safety_score, punctuality_score, quality_score, teamwork_score,
                overall_score, recommendation, comments, status
            ) VALUES (?, ?, 'Probation', DATE_SUB(CURDATE(), INTERVAL 90 DAY), CURDATE(), ?, ?, ?, ?, ?, ?, ?, 'Submitted')",
            [$employeeId, $userId, $safetyScore, $punctualityScore, $qualityScore, $teamworkScore, $overallScore, $recommendation, $comments]
        );
        logAuditAction($userId, 'CREATE', 'performance_reviews', null, null, [
            'employee_id' => $employeeId,
            'recommendation' => $recommendation
        ], "Submitted probation review for employee ID: {$employeeId}");
        $successMessage = "Performance review submitted successfully!";
    } catch (Exception $e) {
        $errorMessage = "Failed to submit review: " . $e->getMessage();
    }
}

// Get team members
$teamMembers = [];
try {
    $teamMembers = getAccessibleEmployees($userId, ['status' => null]);
} catch (Exception $e) { $teamMembers = []; }

// Stats
$teamCount = count($teamMembers);
$activeCount = count(array_filter($teamMembers, fn($m) => ($m['employment_status'] ?? '') === 'Active'));
$probationCount = count(array_filter($teamMembers, fn($m) => ($m['employment_status'] ?? '') === 'Probation'));
?>
<div data-page-title="My Team">
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

        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: rgba(30, 41, 54, 0.6);
            border-radius: 12px;
            padding: 1.25rem;
            border: 1px solid rgba(58, 69, 84, 0.5);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 45px; height: 45px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }

        .stat-icon.blue { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
        .stat-icon.green { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .stat-icon.amber { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }

        .stat-value { font-size: 1.5rem; font-weight: 700; color: #e2e8f0; }
        .stat-label { font-size: 0.8rem; color: #94a3b8; }

        .table-card {
            background: rgba(30, 41, 54, 0.6);
            border-radius: 12px;
            border: 1px solid rgba(58, 69, 84, 0.5);
            overflow: hidden;
        }

        .table-card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(58, 69, 84, 0.5);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-card-header h2 {
            font-size: 1.1rem;
            color: #e2e8f0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table-wrapper { overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; }

        th {
            background: rgba(15, 23, 42, 0.6);
            padding: 0.875rem 1rem;
            text-align: left;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            font-weight: 600;
        }

        td {
            padding: 0.875rem 1rem;
            border-top: 1px solid rgba(58, 69, 84, 0.3);
            font-size: 0.9rem;
            color: #e2e8f0;
        }

        tr:hover td { background: rgba(245, 158, 11, 0.03); }

        .emp-name { font-weight: 600; color: #e2e8f0; }
        .emp-email { font-size: 0.8rem; color: #0ea5e9; }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.6rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-badge.active { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .status-badge.probation { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
        .status-badge.valid { background: rgba(16, 185, 129, 0.2); color: #34d399; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.75rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            text-decoration: none;
            color: white;
        }

        .btn-view { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
        .btn-view:hover { background: rgba(14, 165, 233, 0.3); }
        .btn-info { background: rgba(139, 92, 246, 0.2); color: #a78bfa; }
        .btn-info:hover { background: rgba(139, 92, 246, 0.3); }
        .btn-primary { background: #f59e0b; color: #1a2942; }
        .btn-primary:hover { background: #d97706; }

        .btn i { width: 14px; height: 14px; }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-success { background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #6ee7b7; }
        .alert-error { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; }

        /* Modal */
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
            max-width: 700px;
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
            font-size: 1.15rem;
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

        .doc-section { margin-bottom: 1.5rem; }

        .doc-section h3 {
            font-size: 1rem;
            color: #f59e0b;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .doc-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            background: rgba(15, 23, 42, 0.6);
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .doc-info h4 { color: #e2e8f0; font-size: 0.95rem; }
        .doc-info p { color: #94a3b8; font-size: 0.85rem; }

        .locked-section {
            padding: 1rem;
            background: rgba(239, 68, 68, 0.1);
            border: 1px dashed #ef4444;
            border-radius: 8px;
            text-align: center;
            color: #fca5a5;
        }

        .review-form { display: grid; gap: 1rem; }

        .form-group { display: flex; flex-direction: column; gap: 0.5rem; }
        .form-group label { font-weight: 500; color: #cbd5e1; font-size: 0.9rem; }

        .form-group select,
        .form-group textarea {
            padding: 0.75rem 1rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(58, 69, 84, 0.5);
            border-radius: 6px;
            color: #e2e8f0;
            font-size: 0.95rem;
            font-family: inherit;
        }

        .form-group textarea { min-height: 100px; resize: vertical; }

        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #f59e0b;
        }

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
        }

        .submit-btn:hover { background: #d97706; }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .stats-row { grid-template-columns: 1fr; }
        }
    </style>
            <div class="header">
                <div>
                    <h1>My Team</h1>
                    <p><?php echo htmlspecialchars($departmentName); ?> - Team roster & compliance</p>
                </div>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                </div>
            </div>

            <?php if ($successMessage): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
            <?php endif; ?>
            <?php if ($errorMessage): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php endif; ?>

            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon blue"><i data-lucide="users"></i></div>
                    <div>
                        <div class="stat-value"><?php echo $teamCount; ?></div>
                        <div class="stat-label">Total Members</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i data-lucide="check-circle"></i></div>
                    <div>
                        <div class="stat-value"><?php echo $activeCount; ?></div>
                        <div class="stat-label">Active</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon amber"><i data-lucide="clock"></i></div>
                    <div>
                        <div class="stat-value"><?php echo $probationCount; ?></div>
                        <div class="stat-label">On Probation</div>
                    </div>
                </div>
            </div>

            <div class="table-card">
                <div class="table-card-header">
                    <h2><i data-lucide="id-card"></i> Team Roster</h2>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Name / Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Compliance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($teamMembers)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center;color:#94a3b8;padding:2rem;">No team members found</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($teamMembers as $member): ?>
                            <tr>
                                <td>
                                    <div class="emp-name"><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></div>
                                    <div class="emp-email"><?php echo htmlspecialchars($member['company_email'] ?? 'N/A'); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($member['job_title'] ?? $member['role_name'] ?? 'Employee'); ?></td>
                                <td>
                                    <span class="status-badge <?php echo strtolower($member['employment_status'] ?? 'active'); ?>">
                                        <?php echo htmlspecialchars($member['employment_status'] ?? 'Active'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge valid">
                                        <i data-lucide="shield-check" style="width:12px;height:12px;"></i> Valid
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-view" onclick="viewDocuments('<?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>')">
                                        <i data-lucide="eye"></i> Docs
                                    </button>
                                    <?php if (($member['employment_status'] ?? '') === 'Probation'): ?>
                                    <button class="btn btn-primary" onclick="showReviewForm(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>')">
                                        <i data-lucide="clipboard-check"></i> Evaluate
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

    <!-- View Documents Modal -->
    <div class="modal" id="documentsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i data-lucide="folder-open"></i> Employee Documents - <span id="docEmployeeName"></span></h2>
                <button class="modal-close" onclick="closeModal('documentsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="doc-section">
                    <h3><i data-lucide="id-card"></i> Licenses & Certifications</h3>
                    <div class="doc-item">
                        <div class="doc-info">
                            <h4>Commercial Driver's License (CDL)</h4>
                            <p>Status: <span class="status-badge valid">Valid</span></p>
                        </div>
                        <button class="btn btn-view"><i data-lucide="eye"></i></button>
                    </div>
                    <div class="doc-item">
                        <div class="doc-info">
                            <h4>Medical Certificate</h4>
                            <p>Status: <span class="status-badge valid">Valid</span></p>
                        </div>
                        <button class="btn btn-view"><i data-lucide="eye"></i></button>
                    </div>
                </div>
                <div class="doc-section">
                    <h3><i data-lucide="file-text"></i> HR Files</h3>
                    <div class="doc-item">
                        <div class="doc-info">
                            <h4>201 File (Bio-data)</h4>
                            <p>Viewable</p>
                        </div>
                        <button class="btn btn-view"><i data-lucide="eye"></i></button>
                    </div>
                    <div class="doc-item">
                        <div class="doc-info">
                            <h4>Employment Contract</h4>
                            <p>Viewable</p>
                        </div>
                        <button class="btn btn-view"><i data-lucide="eye"></i></button>
                    </div>
                </div>
                <div class="doc-section">
                    <h3><i data-lucide="lock"></i> Restricted Files</h3>
                    <div class="locked-section">
                        <p>Salary / Payslips - HIDDEN / LOCKED</p>
                        <small>Only HR Staff and Admin have access to Payroll Management</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Review Modal -->
    <div class="modal" id="reviewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i data-lucide="clipboard-check"></i> Probation Evaluation - <span id="reviewEmployeeName"></span></h2>
                <button class="modal-close" onclick="closeModal('reviewModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" class="review-form">
                    <input type="hidden" name="submit_review" value="1">
                    <input type="hidden" name="employee_id" id="reviewEmployeeId">

                    <div class="form-group">
                        <label>Safety Score</label>
                        <select name="safety_score" required>
                            <option value="">Select Rating (1-5)</option>
                            <option value="1">1 - Poor</option>
                            <option value="2">2 - Below Average</option>
                            <option value="3">3 - Average</option>
                            <option value="4">4 - Good</option>
                            <option value="5">5 - Excellent</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Punctuality Score</label>
                        <select name="punctuality_score" required>
                            <option value="">Select Rating (1-5)</option>
                            <option value="1">1 - Poor</option>
                            <option value="2">2 - Below Average</option>
                            <option value="3">3 - Average</option>
                            <option value="4">4 - Good</option>
                            <option value="5">5 - Excellent</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Quality Score</label>
                        <select name="quality_score" required>
                            <option value="">Select Rating (1-5)</option>
                            <option value="1">1 - Poor</option>
                            <option value="2">2 - Below Average</option>
                            <option value="3">3 - Average</option>
                            <option value="4">4 - Good</option>
                            <option value="5">5 - Excellent</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Teamwork Score</label>
                        <select name="teamwork_score" required>
                            <option value="">Select Rating (1-5)</option>
                            <option value="1">1 - Poor</option>
                            <option value="2">2 - Below Average</option>
                            <option value="3">3 - Average</option>
                            <option value="4">4 - Good</option>
                            <option value="5">5 - Excellent</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Recommendation</label>
                        <select name="recommendation" required>
                            <option value="">Select Recommendation</option>
                            <option value="Pass Probation">Pass Probation - Regularize</option>
                            <option value="Extend Probation">Extend Probation (30 more days)</option>
                            <option value="Terminate">Terminate Employment</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Comments / Notes</label>
                        <textarea name="comments" placeholder="Additional observations about the employee's performance..."></textarea>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i data-lucide="send"></i> Submit Evaluation to HR
                    </button>
                </form>
            </div>
        </div>
    </div>

<script>
    function viewDocuments(employeeName) {
        document.getElementById('docEmployeeName').textContent = employeeName;
        document.getElementById('documentsModal').classList.add('active');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function showReviewForm(employeeId, employeeName) {
        document.getElementById('reviewEmployeeId').value = employeeId;
        document.getElementById('reviewEmployeeName').textContent = employeeName;
        document.getElementById('reviewModal').classList.add('active');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }

    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('active');
        });
    });

    if (typeof lucide !== 'undefined') { lucide.createIcons(); }
</script>
</div>
