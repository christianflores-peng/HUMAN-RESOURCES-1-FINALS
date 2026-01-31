<?php
/**
 * HR1 Module: HR Staff Recruitment Dashboard
 * Slate Freight Management System
 * 
 * Role-Based Access: HR Staff (Functional Access)
 * Features:
 * - Recruitment Kanban Board (Drag-and-Drop Pipeline)
 * - HIRE Button with Email Automation Trigger
 * - Document Verification Hub
 * - Onboarding Management
 */

session_start();
require_once '../database/config.php';
require_once '../includes/rbac_helper.php';
require_once '../includes/email_generator.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../partials/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$user = getUserWithRole($userId);

// Verify HR Staff or Admin access
if (!isHRStaff($userId) && !isAdmin($userId)) {
    header('Location: ../pages/dashboard.php');
    exit();
}

// Get departments for dropdown
$departments = [];
try {
    $departments = fetchAll("SELECT * FROM departments ORDER BY department_name");
} catch (Exception $e) {
    // Use default departments (all 8 as per SYSTEM_FLOW.md)
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
    // Use default roles as per SYSTEM_FLOW.md
    $roles = [
        ['id' => 4, 'role_name' => 'Fleet Manager'],
        ['id' => 5, 'role_name' => 'Warehouse Manager'],
        ['id' => 6, 'role_name' => 'Logistics Manager'],
        ['id' => 7, 'role_name' => 'Employee'],
        ['id' => 8, 'role_name' => 'New Hire'],
    ];
}

// Validate that required roles exist for hiring
$requiredRoleIds = [7, 8]; // Employee and New Hire
$existingRoleIds = array_column($roles, 'id');
$missingRoles = array_diff($requiredRoleIds, $existingRoleIds);
if (!empty($missingRoles)) {
    error_log("Warning: Required role IDs missing for hiring: " . implode(', ', $missingRoles));
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
        updateRecord(
            "UPDATE job_applications SET status = ? WHERE id = ?",
            [$newStatus, $applicantId]
        );
        
        logAuditAction($userId, 'EDIT', 'job_applications', $applicantId, null, [
            'status' => $newStatus
        ], "Changed applicant status to: {$newStatus}");
        
        $statusMessage = "Status updated successfully!";
    } catch (Exception $e) {
        $statusError = "Failed to update status: " . $e->getMessage();
    }
}

// Get applicants grouped by status
$applicantsByStatus = [
    'new' => [],
    'screening' => [],
    'interview' => [],
    'road_test' => [],
    'offer_sent' => [],
    'hired' => []
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
        // Map status to kanban columns
        $statusMap = [
            'new' => 'new',
            'review' => 'screening',
            'screening' => 'screening',
            'interview' => 'interview',
            'for interview' => 'interview',
            'road_test' => 'road_test',
            'testing' => 'road_test',
            'offer' => 'offer_sent',
            'offer_sent' => 'offer_sent',
            'hired' => 'hired',
            'accepted' => 'hired'
        ];
        
        $column = $statusMap[$status] ?? 'new';
        $applicantsByStatus[$column][] = $applicant;
    }
} catch (Exception $e) {
    // Keep empty arrays
}

// Get dashboard stats
$stats = [
    'total_applicants' => array_sum(array_map('count', $applicantsByStatus)),
    'new_today' => count(array_filter($applicantsByStatus['new'], function($a) {
        return date('Y-m-d', strtotime($a['applied_date'])) === date('Y-m-d');
    })),
    'pending_interviews' => count($applicantsByStatus['interview']),
    'offers_pending' => count($applicantsByStatus['offer_sent'])
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Recruitment Dashboard - Slate Freight</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a1929 0%, #1a2942 100%);
            min-height: 100vh;
            color: #f8fafc;
        }

        .dashboard-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 1rem;
        }

        /* Header */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding: 1rem 1.5rem;
            background: #1e2936;
            border-radius: 12px;
        }

        .header-title h1 {
            font-size: 1.5rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .header-title p {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .header-actions {
            display: flex;
            gap: 0.75rem;
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

        .btn-primary {
            background: #0ea5e9;
            color: white;
        }

        .btn-primary:hover {
            background: #0284c7;
        }

        .btn-secondary {
            background: #334155;
            color: #e2e8f0;
        }

        .btn-secondary:hover {
            background: #475569;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        /* Stats Bar */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stat-item {
            background: #1e2936;
            padding: 1rem 1.25rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(14, 165, 233, 0.2);
            color: #0ea5e9;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #94a3b8;
        }

        /* Kanban Board */
        .kanban-board {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 0.75rem;
            overflow-x: auto;
            padding-bottom: 1rem;
        }

        .kanban-column {
            background: #1e2936;
            border-radius: 12px;
            min-width: 220px;
            max-height: calc(100vh - 280px);
            display: flex;
            flex-direction: column;
        }

        .column-header {
            padding: 1rem;
            border-bottom: 1px solid #334155;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .column-title {
            font-weight: 600;
            font-size: 0.9rem;
            color: #ffffff;
        }

        .column-count {
            background: #334155;
            color: #94a3b8;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
        }

        .column-header.new { border-top: 3px solid #6366f1; }
        .column-header.screening { border-top: 3px solid #8b5cf6; }
        .column-header.interview { border-top: 3px solid #f59e0b; }
        .column-header.road_test { border-top: 3px solid #ef4444; }
        .column-header.offer_sent { border-top: 3px solid #0ea5e9; }
        .column-header.hired { border-top: 3px solid #10b981; }

        .column-body {
            flex: 1;
            overflow-y: auto;
            padding: 0.75rem;
        }

        /* Applicant Card */
        .applicant-card {
            background: #2a3544;
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

        .applicant-name {
            font-weight: 600;
            color: #ffffff;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .applicant-position {
            font-size: 0.8rem;
            color: #0ea5e9;
            margin-bottom: 0.5rem;
        }

        .applicant-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: #94a3b8;
        }

        .applicant-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid #3a4554;
        }

        .action-btn {
            flex: 1;
            padding: 0.375rem;
            border: none;
            border-radius: 4px;
            font-size: 0.75rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            transition: all 0.2s;
        }

        .action-btn.move {
            background: rgba(14, 165, 233, 0.2);
            color: #0ea5e9;
        }

        .action-btn.hire {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .action-btn:hover {
            opacity: 0.8;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: #1e2936;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #334155;
        }

        .modal-header h2 {
            font-size: 1.25rem;
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

        .modal-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #cbd5e1;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            background: #2a3544;
            border: 1px solid #3a4554;
            border-radius: 6px;
            color: #e2e8f0;
            font-size: 0.95rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #0ea5e9;
        }

        /* Hire Result */
        .hire-result {
            padding: 1.5rem;
            text-align: center;
        }

        .hire-result.success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid #10b981;
            border-radius: 12px;
        }

        .hire-result .icon {
            font-size: 3rem;
            color: #10b981;
            margin-bottom: 1rem;
        }

        .hire-result h3 {
            color: #10b981;
            margin-bottom: 0.5rem;
        }

        .hire-result .email {
            background: #2a3544;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-family: monospace;
            color: #0ea5e9;
            margin: 1rem 0;
        }

        /* Alert Messages */
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

        /* Tab Navigation */
        .tab-nav {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            background: #1e2936;
            padding: 0.5rem;
            border-radius: 10px;
        }

        .tab-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: transparent;
            border: none;
            border-radius: 8px;
            color: #94a3b8;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .tab-btn:hover {
            background: #2a3544;
            color: #ffffff;
        }

        .tab-btn.active {
            background: #0ea5e9;
            color: #ffffff;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Document Verification Hub */
        .doc-verification-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            height: calc(100vh - 320px);
        }

        .doc-list-panel {
            background: #1e2936;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .doc-preview-panel {
            background: #1e2936;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .panel-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #334155;
            font-weight: 600;
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .panel-body {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }

        .doc-item {
            background: #2a3544;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s;
        }

        .doc-item:hover {
            border-color: #0ea5e9;
        }

        .doc-item.selected {
            border-color: #0ea5e9;
            background: rgba(14, 165, 233, 0.1);
        }

        .doc-item-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.5rem;
        }

        .doc-employee-name {
            font-weight: 600;
            color: #ffffff;
        }

        .doc-status {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .doc-status.pending {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }

        .doc-status.verified {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .doc-status.rejected {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .doc-type {
            font-size: 0.85rem;
            color: #0ea5e9;
            margin-bottom: 0.25rem;
        }

        .doc-meta {
            font-size: 0.8rem;
            color: #94a3b8;
        }

        .doc-preview-area {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0a1929;
            margin: 1rem;
            border-radius: 8px;
            min-height: 300px;
        }

        .doc-preview-placeholder {
            text-align: center;
            color: #64748b;
        }

        .doc-preview-placeholder .material-symbols-outlined {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .doc-actions {
            padding: 1rem;
            display: flex;
            gap: 0.75rem;
            border-top: 1px solid #334155;
        }

        .doc-actions .btn {
            flex: 1;
            justify-content: center;
        }

        .btn-verify {
            background: #10b981;
            color: white;
        }

        .btn-verify:hover {
            background: #059669;
        }

        .btn-reject {
            background: #ef4444;
            color: white;
        }

        .btn-reject:hover {
            background: #dc2626;
        }

        /* Onboarding Status */
        .onboarding-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }

        .onboarding-card {
            background: #1e2936;
            border-radius: 12px;
            padding: 1.25rem;
        }

        .onboarding-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .onboarding-name {
            font-weight: 600;
            color: #ffffff;
        }

        .onboarding-position {
            font-size: 0.85rem;
            color: #0ea5e9;
        }

        .onboarding-progress {
            margin-top: 1rem;
        }

        .progress-bar-container {
            background: #2a3544;
            border-radius: 4px;
            height: 8px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #0ea5e9);
            transition: width 0.3s;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: #94a3b8;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .kanban-board {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-bar {
                grid-template-columns: repeat(2, 1fr);
            }

            .kanban-board {
                grid-template-columns: repeat(2, 1fr);
            }

            .dashboard-header {
                flex-direction: column;
                gap: 1rem;
    </style>
</head>
<body>
<?php $logo_path = '../assets/images/slate.png'; include '../includes/loading-screen.php'; ?>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-title">
                <h1>
                    <span class="material-symbols-outlined">work</span>
                    Recruitment Dashboard
                </h1>
                <p>HR Staff View - Manage applicants and hiring process</p>
            </div>
            <div class="header-actions">
                <a href="create-job-posting.php" class="btn btn-primary">
                    <span class="material-symbols-outlined">add</span>
                    Post New Job
                </a>
                <a href="dashboard.php" class="btn btn-secondary">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-nav">
            <button class="tab-btn active" onclick="showTab('kanban')">
                <span class="material-symbols-outlined">view_kanban</span>
                Recruitment Pipeline
            </button>
            <button class="tab-btn" onclick="showTab('documents')">
                <span class="material-symbols-outlined">verified</span>
                Document Verification
            </button>
            <button class="tab-btn" onclick="showTab('onboarding')">
                <span class="material-symbols-outlined">assignment_turned_in</span>
                Onboarding Status
            </button>
        </div>

        <?php if ($hireResult && $hireResult['success']): ?>
            <div class="alert alert-success">
                <strong>✅ Hire Successful!</strong> Account Created: <?php echo htmlspecialchars($hireResult['company_email']); ?>
                <br><small>Employee ID: <?php echo htmlspecialchars($hireResult['employee_id']); ?></small>
            </div>
        <?php elseif ($hireResult && !$hireResult['success']): ?>
            <div class="alert alert-error">
                <strong>❌ Hire Failed:</strong> <?php echo htmlspecialchars($hireResult['message']); ?>
                <br><small style="color: #f87171;">Check Laragon error logs for detailed error information.</small>
            </div>
        <?php endif; ?>

        <?php if (isset($statusMessage)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($statusMessage); ?></div>
        <?php endif; ?>

        <!-- Tab Content: Kanban Pipeline -->
        <div id="kanban-tab" class="tab-content active">
        
        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-icon">
                    <span class="material-symbols-outlined">group</span>
                </div>
                <div>
                    <div class="stat-value"><?php echo $stats['total_applicants']; ?></div>
                    <div class="stat-label">Total Applicants</div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">
                    <span class="material-symbols-outlined">fiber_new</span>
                </div>
                <div>
                    <div class="stat-value"><?php echo $stats['new_today']; ?></div>
                    <div class="stat-label">New Today</div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">
                    <span class="material-symbols-outlined">event</span>
                </div>
                <div>
                    <div class="stat-value"><?php echo $stats['pending_interviews']; ?></div>
                    <div class="stat-label">Pending Interviews</div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">
                    <span class="material-symbols-outlined">mark_email_read</span>
                </div>
                <div>
                    <div class="stat-value"><?php echo $stats['offers_pending']; ?></div>
                    <div class="stat-label">Offers Pending</div>
                </div>
            </div>
        </div>

        <!-- Kanban Board -->
        <div class="kanban-board">
            <!-- New Applications -->
            <div class="kanban-column">
                <div class="column-header new">
                    <span class="column-title">📥 New Apply</span>
                    <span class="column-count"><?php echo count($applicantsByStatus['new']); ?></span>
                </div>
                <div class="column-body">
                    <?php foreach ($applicantsByStatus['new'] as $applicant): ?>
                        <div class="applicant-card" onclick="showApplicantDetails(<?php echo $applicant['id']; ?>)">
                            <div class="applicant-name"><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></div>
                            <div class="applicant-position"><?php echo htmlspecialchars($applicant['job_title'] ?? 'N/A'); ?></div>
                            <div class="applicant-meta">
                                <span><?php echo htmlspecialchars($applicant['department_name'] ?? 'N/A'); ?></span>
                                <span><?php echo date('M d', strtotime($applicant['applied_date'])); ?></span>
                            </div>
                            <div class="applicant-actions">
                                <button class="action-btn move" onclick="event.stopPropagation(); moveToStatus(<?php echo $applicant['id']; ?>, 'screening')">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">arrow_forward</span> Screen
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Screening -->
            <div class="kanban-column">
                <div class="column-header screening">
                    <span class="column-title">🔍 Screening</span>
                    <span class="column-count"><?php echo count($applicantsByStatus['screening']); ?></span>
                </div>
                <div class="column-body">
                    <?php foreach ($applicantsByStatus['screening'] as $applicant): ?>
                        <div class="applicant-card" onclick="showApplicantDetails(<?php echo $applicant['id']; ?>)">
                            <div class="applicant-name"><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></div>
                            <div class="applicant-position"><?php echo htmlspecialchars($applicant['job_title'] ?? 'N/A'); ?></div>
                            <div class="applicant-meta">
                                <span>Check License</span>
                                <span><?php echo date('M d', strtotime($applicant['applied_date'])); ?></span>
                            </div>
                            <div class="applicant-actions">
                                <button class="action-btn move" onclick="event.stopPropagation(); moveToStatus(<?php echo $applicant['id']; ?>, 'interview')">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">arrow_forward</span> Interview
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- For Interview -->
            <div class="kanban-column">
                <div class="column-header interview">
                    <span class="column-title">📅 For Interview</span>
                    <span class="column-count"><?php echo count($applicantsByStatus['interview']); ?></span>
                </div>
                <div class="column-body">
                    <?php foreach ($applicantsByStatus['interview'] as $applicant): ?>
                        <div class="applicant-card" onclick="showApplicantDetails(<?php echo $applicant['id']; ?>)">
                            <div class="applicant-name"><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></div>
                            <div class="applicant-position"><?php echo htmlspecialchars($applicant['job_title'] ?? 'N/A'); ?></div>
                            <div class="applicant-meta">
                                <span>Schedule Set</span>
                                <span><?php echo date('M d', strtotime($applicant['applied_date'])); ?></span>
                            </div>
                            <div class="applicant-actions">
                                <button class="action-btn move" onclick="event.stopPropagation(); moveToStatus(<?php echo $applicant['id']; ?>, 'road_test')">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">arrow_forward</span> Road Test
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Road Test -->
            <div class="kanban-column">
                <div class="column-header road_test">
                    <span class="column-title">🚛 Road Test</span>
                    <span class="column-count"><?php echo count($applicantsByStatus['road_test']); ?></span>
                </div>
                <div class="column-body">
                    <?php foreach ($applicantsByStatus['road_test'] as $applicant): ?>
                        <div class="applicant-card" onclick="showApplicantDetails(<?php echo $applicant['id']; ?>)">
                            <div class="applicant-name"><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></div>
                            <div class="applicant-position"><?php echo htmlspecialchars($applicant['job_title'] ?? 'N/A'); ?></div>
                            <div class="applicant-meta">
                                <span>Logistics/Fleet</span>
                                <span><?php echo date('M d', strtotime($applicant['applied_date'])); ?></span>
                            </div>
                            <div class="applicant-actions">
                                <button class="action-btn move" onclick="event.stopPropagation(); moveToStatus(<?php echo $applicant['id']; ?>, 'offer_sent')">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">arrow_forward</span> Send Offer
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Offer Sent -->
            <div class="kanban-column">
                <div class="column-header offer_sent">
                    <span class="column-title">✉️ Offer Sent</span>
                    <span class="column-count"><?php echo count($applicantsByStatus['offer_sent']); ?></span>
                </div>
                <div class="column-body">
                    <?php foreach ($applicantsByStatus['offer_sent'] as $applicant): ?>
                        <div class="applicant-card" onclick="showApplicantDetails(<?php echo $applicant['id']; ?>)">
                            <div class="applicant-name"><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></div>
                            <div class="applicant-position"><?php echo htmlspecialchars($applicant['job_title'] ?? 'N/A'); ?></div>
                            <div class="applicant-meta">
                                <span>Waiting Signature</span>
                                <span><?php echo date('M d', strtotime($applicant['applied_date'])); ?></span>
                            </div>
                            <div class="applicant-actions">
                                <button class="action-btn hire" onclick="event.stopPropagation(); showHireModal(<?php echo $applicant['id']; ?>, '<?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?>', '<?php echo htmlspecialchars($applicant['job_title'] ?? ''); ?>')">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">check_circle</span> HIRE
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Hired (Magic Column) -->
            <div class="kanban-column">
                <div class="column-header hired">
                    <span class="column-title">✅ HIRED</span>
                    <span class="column-count"><?php echo count($applicantsByStatus['hired']); ?></span>
                </div>
                <div class="column-body">
                    <?php foreach ($applicantsByStatus['hired'] as $applicant): ?>
                        <div class="applicant-card">
                            <div class="applicant-name"><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></div>
                            <div class="applicant-position"><?php echo htmlspecialchars($applicant['job_title'] ?? 'N/A'); ?></div>
                            <div class="applicant-meta">
                                <span style="color: #10b981;">✓ Onboarding</span>
                                <span><?php echo date('M d', strtotime($applicant['applied_date'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        </div> <!-- End Kanban Tab -->

        <!-- Tab Content: Document Verification Hub -->
        <div id="documents-tab" class="tab-content">
            <div class="doc-verification-container">
                <!-- Document List Panel -->
                <div class="doc-list-panel">
                    <div class="panel-header">
                        <span><span class="material-symbols-outlined" style="font-size: 1rem; vertical-align: middle;">description</span> Pending Documents</span>
                        <span class="column-count">5</span>
                    </div>
                    <div class="panel-body">
                        <!-- Sample Document Items -->
                        <div class="doc-item" onclick="selectDocument(this, 'license')">
                            <div class="doc-item-header">
                                <span class="doc-employee-name">Juan Dela Cruz</span>
                                <span class="doc-status pending">Pending</span>
                            </div>
                            <div class="doc-type">Professional Driver's License</div>
                            <div class="doc-meta">Uploaded: Jan 25, 2026 • Fleet Operations</div>
                        </div>
                        
                        <div class="doc-item" onclick="selectDocument(this, 'nbi')">
                            <div class="doc-item-header">
                                <span class="doc-employee-name">Pedro Santos</span>
                                <span class="doc-status pending">Pending</span>
                            </div>
                            <div class="doc-type">NBI Clearance</div>
                            <div class="doc-meta">Uploaded: Jan 24, 2026 • Warehouse</div>
                        </div>
                        
                        <div class="doc-item" onclick="selectDocument(this, 'medical')">
                            <div class="doc-item-header">
                                <span class="doc-employee-name">Maria Garcia</span>
                                <span class="doc-status pending">Pending</span>
                            </div>
                            <div class="doc-type">Medical Certificate</div>
                            <div class="doc-meta">Uploaded: Jan 23, 2026 • Logistics</div>
                        </div>
                        
                        <div class="doc-item" onclick="selectDocument(this, 'sss')">
                            <div class="doc-item-header">
                                <span class="doc-employee-name">Roberto Reyes</span>
                                <span class="doc-status verified">Verified</span>
                            </div>
                            <div class="doc-type">SSS ID</div>
                            <div class="doc-meta">Verified: Jan 22, 2026 • Fleet Operations</div>
                        </div>
                        
                        <div class="doc-item" onclick="selectDocument(this, 'tin')">
                            <div class="doc-item-header">
                                <span class="doc-employee-name">Ana Mendoza</span>
                                <span class="doc-status rejected">Rejected</span>
                            </div>
                            <div class="doc-type">TIN Certificate</div>
                            <div class="doc-meta">Rejected: Jan 21, 2026 • Blurry image</div>
                        </div>
                    </div>
                </div>

                <!-- Document Preview Panel -->
                <div class="doc-preview-panel">
                    <div class="panel-header">
                        <span>🔍 Document Preview</span>
                        <span id="previewDocType">Select a document</span>
                    </div>
                    <div class="doc-preview-area" id="docPreviewArea">
                        <div class="doc-preview-placeholder">
                            <span class="material-symbols-outlined">description</span>
                            <p>Select a document from the list to preview</p>
                        </div>
                    </div>
                    <div class="doc-actions">
                        <button class="btn btn-verify" onclick="verifyDocument()">
                            <span class="material-symbols-outlined">check_circle</span>
                            Verify
                        </button>
                        <button class="btn btn-reject" onclick="rejectDocument()">
                            <span class="material-symbols-outlined">cancel</span>
                            Reject
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Content: Onboarding Status -->
        <div id="onboarding-tab" class="tab-content">
            <div class="onboarding-grid">
                <!-- Sample Onboarding Cards -->
                <div class="onboarding-card">
                    <div class="onboarding-header">
                        <div>
                            <div class="onboarding-name">Fernando Aquino</div>
                            <div class="onboarding-position">Truck Driver - Class A</div>
                        </div>
                        <span class="doc-status pending">In Progress</span>
                    </div>
                    <div style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 0.5rem;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">calendar_today</span>
                        Start Date: Feb 1, 2026
                    </div>
                    <div class="onboarding-progress">
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: 60%;"></div>
                        </div>
                        <div class="progress-label">
                            <span>3 of 5 tasks completed</span>
                            <span>60%</span>
                        </div>
                    </div>
                </div>

                <div class="onboarding-card">
                    <div class="onboarding-header">
                        <div>
                            <div class="onboarding-name">Lucia Morales</div>
                            <div class="onboarding-position">Warehouse Associate</div>
                        </div>
                        <span class="doc-status verified">Completed</span>
                    </div>
                    <div style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 0.5rem;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">calendar_today</span>
                        Start Date: Jan 15, 2026
                    </div>
                    <div class="onboarding-progress">
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: 100%;"></div>
                        </div>
                        <div class="progress-label">
                            <span>5 of 5 tasks completed</span>
                            <span>100%</span>
                        </div>
                    </div>
                </div>

                <div class="onboarding-card">
                    <div class="onboarding-header">
                        <div>
                            <div class="onboarding-name">Ricardo Villanueva</div>
                            <div class="onboarding-position">Truck Driver - Class A</div>
                        </div>
                        <span class="doc-status pending">Just Started</span>
                    </div>
                    <div style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 0.5rem;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">calendar_today</span>
                        Start Date: Feb 5, 2026
                    </div>
                    <div class="onboarding-progress">
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: 20%;"></div>
                        </div>
                        <div class="progress-label">
                            <span>1 of 5 tasks completed</span>
                            <span>20%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../modals/components/hr-recruitment-modals.php'; ?>

    <script>
        // Close modal on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });

        // Tab switching
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active from all tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Activate clicked button
            event.target.closest('.tab-btn').classList.add('active');
        }

        // Document verification functions
        let selectedDocItem = null;

        function selectDocument(element, docType) {
            // Remove selection from previous
            if (selectedDocItem) {
                selectedDocItem.classList.remove('selected');
            }
            
            // Select new item
            element.classList.add('selected');
            selectedDocItem = element;
            
            // Update preview area
            const docTypes = {
                'license': "Professional Driver's License",
                'nbi': 'NBI Clearance',
                'medical': 'Medical Certificate',
                'sss': 'SSS ID',
                'tin': 'TIN Certificate'
            };
            
            document.getElementById('previewDocType').textContent = docTypes[docType] || docType;
            
            // Show sample preview (in real app, this would load actual document)
            document.getElementById('docPreviewArea').innerHTML = `
                <div style="text-align: center; color: #94a3b8;">
                    <span class="material-symbols-outlined" style="font-size: 6rem; color: #0ea5e9;">image</span>
                    <p style="margin-top: 1rem; font-size: 1.1rem;">${docTypes[docType] || docType}</p>
                    <p style="margin-top: 0.5rem; font-size: 0.9rem;">Document preview would appear here</p>
                    <p style="margin-top: 0.25rem; font-size: 0.8rem; color: #64748b;">Click Verify or Reject to process</p>
                </div>
            `;
        }

        function verifyDocument() {
            if (!selectedDocItem) {
                alert('Please select a document first');
                return;
            }
            
            if (confirm('Verify this document as authentic?')) {
                // Update status badge
                const statusBadge = selectedDocItem.querySelector('.doc-status');
                statusBadge.className = 'doc-status verified';
                statusBadge.textContent = 'Verified';
                
                // Update meta
                const metaEl = selectedDocItem.querySelector('.doc-meta');
                metaEl.textContent = 'Verified: ' + new Date().toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'});
                
                alert('Document verified successfully!');
            }
        }

        function rejectDocument() {
            if (!selectedDocItem) {
                alert('Please select a document first');
                return;
            }
            
            const reason = prompt('Enter rejection reason:');
            if (reason) {
                // Update status badge
                const statusBadge = selectedDocItem.querySelector('.doc-status');
                statusBadge.className = 'doc-status rejected';
                statusBadge.textContent = 'Rejected';
                
                // Update meta
                const metaEl = selectedDocItem.querySelector('.doc-meta');
                metaEl.textContent = 'Rejected: ' + reason;
                
                alert('Document rejected. Employee will be notified to re-upload.');
            }
        }

        // Hire form validation and submission
        document.addEventListener('DOMContentLoaded', function() {
            const hireForm = document.getElementById('hireForm');
            if (hireForm) {
                hireForm.addEventListener('submit', function(e) {
                    // Validate required fields
                    const jobTitle = document.querySelector('[name="job_title"]').value.trim();
                    const departmentId = document.querySelector('[name="department_id"]').value;
                    const roleId = document.querySelector('[name="role_id"]').value;
                    
                    if (!jobTitle) {
                        e.preventDefault();
                        alert('Please enter a job title');
                        return false;
                    }
                    
                    if (!departmentId) {
                        e.preventDefault();
                        alert('Please select a department');
                        return false;
                    }
                    
                    if (!roleId) {
                        e.preventDefault();
                        alert('Please select a role');
                        return false;
                    }
                    
                    // Disable button to prevent double submission
                    const submitBtn = document.getElementById('hireSubmitBtn');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="material-symbols-outlined">hourglass_empty</span> Processing...';
                    }
                    
                    return true;
                });
            }

            // Auto-close modal on successful hire
            <?php if ($hireResult && $hireResult['success']): ?>
                closeModal('hireModal');
                // Scroll to success message
                window.scrollTo({ top: 0, behavior: 'smooth' });
            <?php endif; ?>
        });
    </script>
</body>
</html>
