<?php
require_once '../../includes/session_helper.php';
require_once '../../includes/rbac_helper.php';
require_once '../../includes/email_generator.php';
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
    header('Location: index.php?page=recruitment-pipeline');
    exit();
}

require_once '../../database/config.php';

// Get departments for dropdown
$departments = [];
try {
    $departments = fetchAll("SELECT * FROM departments ORDER BY department_name");
} catch (Exception $e) {
    $departments = [
        ['id' => 1, 'department_name' => 'Human Resources', 'department_code' => 'HR'],
        ['id' => 2, 'department_name' => 'Fleet Operations', 'department_code' => 'FLEET'],
        ['id' => 3, 'department_name' => 'Logistics', 'department_code' => 'LOGISTICS'],
        ['id' => 4, 'department_name' => 'Warehouse', 'department_code' => 'WAREHOUSE'],
        ['id' => 5, 'department_name' => 'Finance', 'department_code' => 'FINANCE'],
        ['id' => 6, 'department_name' => 'Information Technology', 'department_code' => 'IT'],
        ['id' => 7, 'department_name' => 'Dispatch Center', 'department_code' => 'DISPATCH'],
        ['id' => 8, 'department_name' => 'Vehicle Maintenance', 'department_code' => 'MAINTENANCE'],
    ];
}

// Get roles for dropdown
$roles = [];
try {
    $roles = fetchAll("SELECT * FROM roles WHERE role_type IN ('Employee', 'Manager') ORDER BY role_name");
} catch (Exception $e) {
    $roles = [
        ['id' => 4, 'role_name' => 'Fleet Manager'],
        ['id' => 5, 'role_name' => 'Warehouse Manager'],
        ['id' => 6, 'role_name' => 'Logistics Manager'],
        ['id' => 7, 'role_name' => 'Employee'],
        ['id' => 8, 'role_name' => 'New Hire'],
    ];
}

// Handle HIRE action
$hireResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hire_applicant'])) {
    $applicantId = intval($_POST['applicant_id']);
    $roleId = intval($_POST['role_id']);
    $departmentId = intval($_POST['department_id']);
    $jobTitle = trim($_POST['job_title']);
    $hireResult = processApplicantHire($applicantId, $roleId, $departmentId, $jobTitle, $userId);
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $applicantId = intval($_POST['applicant_id']);
    $newStatus = $_POST['new_status'];
    try {
        updateRecord("UPDATE job_applications SET status = ? WHERE id = ?", [$newStatus, $applicantId]);
        logAuditAction($userId, 'EDIT', 'job_applications', $applicantId, null, ['status' => $newStatus], "Changed applicant status to: {$newStatus}");
        $statusMessage = "Status updated successfully!";
    } catch (Exception $e) {
        $statusError = "Failed to update status: " . $e->getMessage();
    }
}

// Get applicants grouped by status
$applicantsByStatus = [
    'new' => [], 'screening' => [], 'interview' => [],
    'road_test' => [], 'offer_sent' => [], 'hired' => []
];

try {
    $allApplicants = fetchAll(
        "SELECT ja.*, jp.title as job_title, jp.department_id, d.department_name
         FROM job_applications ja
         LEFT JOIN job_postings jp ON ja.job_posting_id = jp.id
         LEFT JOIN departments d ON jp.department_id = d.id
         ORDER BY ja.applied_date DESC"
    );
    foreach ($allApplicants as $applicant) {
        $status = strtolower($applicant['status'] ?? 'new');
        $statusMap = [
            'new' => 'new', 'review' => 'screening', 'screening' => 'screening',
            'interview' => 'interview', 'for interview' => 'interview',
            'road_test' => 'road_test', 'testing' => 'road_test',
            'offer' => 'offer_sent', 'offer_sent' => 'offer_sent',
            'hired' => 'hired', 'accepted' => 'hired'
        ];
        $column = $statusMap[$status] ?? 'new';
        $applicantsByStatus[$column][] = $applicant;
    }
} catch (Exception $e) {}

$stats = [
    'total_applicants' => array_sum(array_map('count', $applicantsByStatus)),
    'new_today' => count(array_filter($applicantsByStatus['new'], function($a) {
        return date('Y-m-d', strtotime($a['applied_date'])) === date('Y-m-d');
    })),
    'pending_interviews' => count($applicantsByStatus['interview']),
    'offers_pending' => count($applicantsByStatus['offer_sent'])
];
?>
<div data-page-title="Recruitment Pipeline">
<style>
        .header {
            background: rgba(30, 41, 54, 0.6);
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 { font-size: 1.35rem; color: #e2e8f0; }
        .header p { color: #94a3b8; font-size: 0.85rem; }

        .stats-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .stat-item {
            background: rgba(30, 41, 54, 0.6);
            padding: 1rem 1.25rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border: 1px solid rgba(58, 69, 84, 0.5);
        }

        .stat-icon {
            width: 40px; height: 40px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(14, 165, 233, 0.2);
            color: #0ea5e9;
        }

        .stat-value { font-size: 1.5rem; font-weight: 700; color: #ffffff; }
        .stat-label { font-size: 0.8rem; color: #94a3b8; }

        .kanban-board {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 0.75rem;
            overflow-x: auto;
            padding-bottom: 1rem;
        }

        .kanban-column {
            background: rgba(30, 41, 54, 0.6);
            border-radius: 12px;
            min-width: 220px;
            max-height: calc(100vh - 300px);
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(58, 69, 84, 0.5);
        }

        .column-header {
            padding: 1rem;
            border-bottom: 1px solid rgba(58, 69, 84, 0.5);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .column-title { font-weight: 600; font-size: 0.85rem; color: #ffffff; }

        .column-count {
            background: rgba(58, 69, 84, 0.5);
            color: #94a3b8;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
        }

        .column-header.new { border-top: 3px solid #6366f1; }
        .column-header.screening { border-top: 3px solid #8b5cf6; }
        .column-header.interview { border-top: 3px solid #f59e0b; }
        .column-header.road_test { border-top: 3px solid #ef4444; }
        .column-header.offer_sent { border-top: 3px solid #0ea5e9; }
        .column-header.hired { border-top: 3px solid #10b981; }

        .column-body { flex: 1; overflow-y: auto; padding: 0.75rem; }

        .applicant-card {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 8px;
            padding: 0.875rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .applicant-card:hover {
            border-color: #0ea5e9;
            transform: translateY(-2px);
        }

        .applicant-name { font-weight: 600; color: #ffffff; font-size: 0.85rem; margin-bottom: 0.2rem; }
        .applicant-position { font-size: 0.78rem; color: #0ea5e9; margin-bottom: 0.4rem; }

        .applicant-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.72rem;
            color: #94a3b8;
        }

        .applicant-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid rgba(58, 69, 84, 0.5);
        }

        .action-btn {
            flex: 1;
            padding: 0.35rem;
            border: none;
            border-radius: 4px;
            font-size: 0.72rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            transition: all 0.2s;
        }

        .action-btn.move { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
        .action-btn.hire { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .action-btn.view { background: rgba(100, 116, 139, 0.25); color: #cbd5e1; }
        .action-btn:hover { opacity: 0.8; }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
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
            max-width: 500px;
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
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(58, 69, 84, 0.5);
            border-radius: 6px;
            color: #e2e8f0;
            font-size: 0.95rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #0ea5e9;
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

        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }

        @media (max-width: 1200px) {
            .kanban-board { grid-template-columns: repeat(3, 1fr); }
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .stats-bar { grid-template-columns: repeat(2, 1fr); }
            .kanban-board { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
            <div class="header">
                <div>
                    <h1>Recruitment Pipeline</h1>
                    <p>Manage applicants through the hiring process</p>
                </div>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                </div>
            </div>

            <?php if ($hireResult && $hireResult['success']): ?>
                <div class="alert alert-success">
                    <strong>Hire Successful!</strong> Account Created: <?php echo htmlspecialchars($hireResult['company_email']); ?>
                    <br><small>Employee ID: <?php echo htmlspecialchars($hireResult['employee_id']); ?></small>
                </div>
            <?php elseif ($hireResult && !$hireResult['success']): ?>
                <div class="alert alert-error">
                    <strong>Hire Failed:</strong> <?php echo htmlspecialchars($hireResult['message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($statusMessage)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($statusMessage); ?></div>
            <?php endif; ?>

            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-icon"><i data-lucide="users"></i></div>
                    <div>
                        <div class="stat-value"><?php echo $stats['total_applicants']; ?></div>
                        <div class="stat-label">Total Applicants</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon"><i data-lucide="sparkles"></i></div>
                    <div>
                        <div class="stat-value"><?php echo $stats['new_today']; ?></div>
                        <div class="stat-label">New Today</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon"><i data-lucide="calendar"></i></div>
                    <div>
                        <div class="stat-value"><?php echo $stats['pending_interviews']; ?></div>
                        <div class="stat-label">Pending Interviews</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon"><i data-lucide="mail-check"></i></div>
                    <div>
                        <div class="stat-value"><?php echo $stats['offers_pending']; ?></div>
                        <div class="stat-label">Offers Pending</div>
                    </div>
                </div>
            </div>

            <div class="kanban-board">
                <!-- New Applications -->
                <div class="kanban-column">
                    <div class="column-header new">
                        <span class="column-title">New Apply</span>
                        <span class="column-count"><?php echo count($applicantsByStatus['new']); ?></span>
                    </div>
                    <div class="column-body">
                        <?php foreach ($applicantsByStatus['new'] as $applicant): ?>
                            <div class="applicant-card">
                                <div class="applicant-name"><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></div>
                                <div class="applicant-position"><?php echo htmlspecialchars($applicant['job_title'] ?? 'N/A'); ?></div>
                                <div class="applicant-meta">
                                    <span><?php echo htmlspecialchars($applicant['department_name'] ?? 'N/A'); ?></span>
                                    <span><?php echo date('M d', strtotime($applicant['applied_date'])); ?></span>
                                </div>
                                <div class="applicant-actions">
                                    <button type="button" class="action-btn view" onclick="showApplicantDetails(<?php echo $applicant['id']; ?>)">
                                        <i data-lucide="eye" style="width:14px;height:14px;"></i> Details
                                    </button>
                                    <button class="action-btn move" onclick="moveToStatus(<?php echo $applicant['id']; ?>, 'screening')">
                                        <i data-lucide="arrow-right" style="width:14px;height:14px;"></i> Screen
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Screening -->
                <div class="kanban-column">
                    <div class="column-header screening">
                        <span class="column-title">Screening</span>
                        <span class="column-count"><?php echo count($applicantsByStatus['screening']); ?></span>
                    </div>
                    <div class="column-body">
                        <?php foreach ($applicantsByStatus['screening'] as $applicant): ?>
                            <div class="applicant-card">
                                <div class="applicant-name"><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></div>
                                <div class="applicant-position"><?php echo htmlspecialchars($applicant['job_title'] ?? 'N/A'); ?></div>
                                <div class="applicant-meta">
                                    <span>Check License</span>
                                    <span><?php echo date('M d', strtotime($applicant['applied_date'])); ?></span>
                                </div>
                                <div class="applicant-actions">
                                    <button type="button" class="action-btn view" onclick="showApplicantDetails(<?php echo $applicant['id']; ?>)">
                                        <i data-lucide="eye" style="width:14px;height:14px;"></i> Details
                                    </button>
                                    <button class="action-btn move" onclick="moveToStatus(<?php echo $applicant['id']; ?>, 'interview')">
                                        <i data-lucide="arrow-right" style="width:14px;height:14px;"></i> Interview
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- For Interview -->
                <div class="kanban-column">
                    <div class="column-header interview">
                        <span class="column-title">For Interview</span>
                        <span class="column-count"><?php echo count($applicantsByStatus['interview']); ?></span>
                    </div>
                    <div class="column-body">
                        <?php foreach ($applicantsByStatus['interview'] as $applicant): ?>
                            <div class="applicant-card">
                                <div class="applicant-name"><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></div>
                                <div class="applicant-position"><?php echo htmlspecialchars($applicant['job_title'] ?? 'N/A'); ?></div>
                                <div class="applicant-meta">
                                    <span>Schedule Set</span>
                                    <span><?php echo date('M d', strtotime($applicant['applied_date'])); ?></span>
                                </div>
                                <div class="applicant-actions">
                                    <button type="button" class="action-btn view" onclick="showApplicantDetails(<?php echo $applicant['id']; ?>)">
                                        <i data-lucide="eye" style="width:14px;height:14px;"></i> Details
                                    </button>
                                    <button class="action-btn move" onclick="moveToStatus(<?php echo $applicant['id']; ?>, 'road_test')">
                                        <i data-lucide="arrow-right" style="width:14px;height:14px;"></i> Road Test
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Road Test -->
                <div class="kanban-column">
                    <div class="column-header road_test">
                        <span class="column-title">Road Test</span>
                        <span class="column-count"><?php echo count($applicantsByStatus['road_test']); ?></span>
                    </div>
                    <div class="column-body">
                        <?php foreach ($applicantsByStatus['road_test'] as $applicant): ?>
                            <div class="applicant-card">
                                <div class="applicant-name"><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></div>
                                <div class="applicant-position"><?php echo htmlspecialchars($applicant['job_title'] ?? 'N/A'); ?></div>
                                <div class="applicant-meta">
                                    <span>Logistics/Fleet</span>
                                    <span><?php echo date('M d', strtotime($applicant['applied_date'])); ?></span>
                                </div>
                                <div class="applicant-actions">
                                    <button type="button" class="action-btn view" onclick="showApplicantDetails(<?php echo $applicant['id']; ?>)">
                                        <i data-lucide="eye" style="width:14px;height:14px;"></i> Details
                                    </button>
                                    <button class="action-btn move" onclick="moveToStatus(<?php echo $applicant['id']; ?>, 'offer_sent')">
                                        <i data-lucide="arrow-right" style="width:14px;height:14px;"></i> Send Offer
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Offer Sent -->
                <div class="kanban-column">
                    <div class="column-header offer_sent">
                        <span class="column-title">Offer Sent</span>
                        <span class="column-count"><?php echo count($applicantsByStatus['offer_sent']); ?></span>
                    </div>
                    <div class="column-body">
                        <?php foreach ($applicantsByStatus['offer_sent'] as $applicant): ?>
                            <div class="applicant-card">
                                <div class="applicant-name"><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></div>
                                <div class="applicant-position"><?php echo htmlspecialchars($applicant['job_title'] ?? 'N/A'); ?></div>
                                <div class="applicant-meta">
                                    <span>Waiting Signature</span>
                                    <span><?php echo date('M d', strtotime($applicant['applied_date'])); ?></span>
                                </div>
                                <div class="applicant-actions">
                                    <button type="button" class="action-btn view" onclick="showApplicantDetails(<?php echo $applicant['id']; ?>)">
                                        <i data-lucide="eye" style="width:14px;height:14px;"></i> Details
                                    </button>
                                    <button class="action-btn hire" onclick="showHireModal(<?php echo $applicant['id']; ?>, '<?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?>', '<?php echo htmlspecialchars($applicant['job_title'] ?? ''); ?>')">
                                        <i data-lucide="check-circle" style="width:14px;height:14px;"></i> HIRE
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Hired -->
                <div class="kanban-column">
                    <div class="column-header hired">
                        <span class="column-title">HIRED</span>
                        <span class="column-count"><?php echo count($applicantsByStatus['hired']); ?></span>
                    </div>
                    <div class="column-body">
                        <?php foreach ($applicantsByStatus['hired'] as $applicant): ?>
                            <div class="applicant-card">
                                <div class="applicant-name"><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></div>
                                <div class="applicant-position"><?php echo htmlspecialchars($applicant['job_title'] ?? 'N/A'); ?></div>
                                <div class="applicant-meta">
                                    <span style="color: #10b981;">Onboarding</span>
                                    <span><?php echo date('M d', strtotime($applicant['applied_date'])); ?></span>
                                </div>
                                <div class="applicant-actions">
                                    <button type="button" class="action-btn view" onclick="showApplicantDetails(<?php echo $applicant['id']; ?>)">
                                        <i data-lucide="eye" style="width:14px;height:14px;"></i> Details
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
    <!-- Hire Modal -->
    <div class="modal" id="hireModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i data-lucide="user-check"></i> Confirm Hiring</h2>
                <button class="modal-close" onclick="closeModal('hireModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="hireForm">
                    <input type="hidden" name="hire_applicant" value="1">
                    <input type="hidden" name="applicant_id" id="hireApplicantId">

                    <p style="margin-bottom: 1rem; color: #94a3b8;">
                        Confirm hiring of <strong id="hireApplicantName" style="color: #ffffff;"></strong>?
                    </p>

                    <div class="form-group">
                        <label>Job Title / Role</label>
                        <input type="text" name="job_title" id="hireJobTitle" required placeholder="e.g., Truck Driver - Class A">
                    </div>

                    <div class="form-group">
                        <label>Department</label>
                        <select name="department_id" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Assigned Role</label>
                        <select name="role_id" required>
                            <option value="">Select Role</option>
                            <option value="7">Employee</option>
                            <option value="8">New Hire (Probation)</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="background: rgba(15, 23, 42, 0.6); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <p style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 0.5rem;">
                            <i data-lucide="info" style="width: 16px; height: 16px; vertical-align: middle;"></i>
                            Auto-Fill Action:
                        </p>
                        <p style="color: #0ea5e9; font-size: 0.9rem;">
                            System will generate: <strong>[firstname].[lastname]@slatefreight.com</strong>
                        </p>
                    </div>

                    <button type="submit" class="btn btn-success" style="width: 100%; justify-content: center;" id="hireSubmitBtn">
                        <i data-lucide="check-circle"></i>
                        Confirm Hire & Generate Email
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Status Update Form (Hidden) -->
    <form id="statusForm" method="POST" style="display: none;">
        <input type="hidden" name="update_status" value="1">
        <input type="hidden" name="applicant_id" id="statusApplicantId">
        <input type="hidden" name="new_status" id="statusNewStatus">
    </form>

    <script>
        function showHireModal(applicantId, name, jobTitle) {
            document.getElementById('hireApplicantId').value = applicantId;
            document.getElementById('hireApplicantName').textContent = name;
            document.getElementById('hireJobTitle').value = jobTitle;
            document.getElementById('hireModal').classList.add('active');
        }

        function moveToStatus(applicantId, newStatus) {
            if (confirm('Move this applicant to ' + newStatus.replace('_', ' ').toUpperCase() + '?')) {
                document.getElementById('statusApplicantId').value = applicantId;
                document.getElementById('statusNewStatus').value = newStatus;
                document.getElementById('statusForm').submit();
            }
        }

        function showApplicantDetails(applicationId) {
            if (!applicationId) return;
            const params = 'id=' + encodeURIComponent(applicationId);
            if (window.HR1SPA && typeof window.HR1SPA.loadPage === 'function') {
                window.HR1SPA.loadPage('applicant-details', true, params);
                return;
            }
            window.location.href = 'applicant-details.php?' + params;
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) this.classList.remove('active');
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const hireForm = document.getElementById('hireForm');
            if (hireForm) {
                hireForm.addEventListener('submit', function(e) {
                    const jobTitle = document.querySelector('[name="job_title"]').value.trim();
                    const departmentId = document.querySelector('[name="department_id"]').value;
                    const roleId = document.querySelector('[name="role_id"]').value;
                    if (!jobTitle) { e.preventDefault(); alert('Please enter a job title'); return false; }
                    if (!departmentId) { e.preventDefault(); alert('Please select a department'); return false; }
                    if (!roleId) { e.preventDefault(); alert('Please select a role'); return false; }
                    const submitBtn = document.getElementById('hireSubmitBtn');
                    if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = 'Processing...'; }
                    return true;
                });
            }

            <?php if ($hireResult && $hireResult['success']): ?>
                closeModal('hireModal');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            <?php endif; ?>

        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>
</div>
