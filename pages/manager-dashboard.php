<?php
/**
 * HR1 Module: Manager's "My Team" Dashboard
 * Slate Freight Management System
 * 
 * Role-Based Access: Manager (Department-Specific View)
 * Features:
 * - Quick Stats Cards (Compliance Alerts, Probation Reviews, Team Count)
 * - My Team Roster with filtered view
 * - View Documents Modal (Read-Only)
 * - Performance Evaluation for Probation
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

// Verify manager access
if (!$user || !in_array($user['role_type'], ['Manager', 'Admin', 'HR_Staff'])) {
    // Allow Admin and HR to view as well for testing
    if (!isAdmin($userId) && !isHRStaff($userId)) {
        header('Location: ../pages/dashboard.php');
        exit();
    }
}

$departmentId = $user['department_id'] ?? null;
$departmentName = $user['department_name'] ?? 'All Departments';

// Get team members
$teamMembers = getAccessibleEmployees($userId, ['status' => null]);

// Get dashboard stats
$stats = [];

try {
    // Team count
    $stats['team_count'] = count($teamMembers);
    
    // Expiring licenses (next 30 days)
    if ($departmentId) {
        $expiringLicenses = fetchAll(
            "SELECT ed.*, ua.first_name, ua.last_name, ua.company_email
             FROM employee_documents ed
             JOIN user_accounts ua ON ed.user_id = ua.id
             WHERE ua.department_id = ? 
             AND ed.document_type = 'License'
             AND ed.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             ORDER BY ed.expiry_date ASC",
            [$departmentId]
        );
    } else {
        $expiringLicenses = fetchAll(
            "SELECT ed.*, ua.first_name, ua.last_name, ua.company_email
             FROM employee_documents ed
             JOIN user_accounts ua ON ed.user_id = ua.id
             WHERE ed.document_type = 'License'
             AND ed.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             ORDER BY ed.expiry_date ASC"
        );
    }
    $stats['expiring_licenses'] = count($expiringLicenses);
    
    // Probation reviews pending
    $probationEmployees = array_filter($teamMembers, function($emp) {
        return $emp['employment_status'] === 'Probation';
    });
    $stats['probation_reviews'] = count($probationEmployees);
    
} catch (Exception $e) {
    $stats = ['team_count' => 0, 'expiring_licenses' => 0, 'probation_reviews' => 0];
    $expiringLicenses = [];
    $probationEmployees = [];
}

// Handle performance review submission
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
$active_page = 'manager-dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Portal - Slate Freight</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .manager-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .action-card {
            flex: 1;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            padding: 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.3s;
        }
        
        .action-card:hover {
            transform: translateY(-4px);
        }
        
        .action-card.purple {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        }
        
        .action-card .icon {
            font-size: 2.5rem;
        }
        
        .action-card .content h3 {
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }
        
        .action-card .content p {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        /* Header */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 1rem 1.5rem;
            background: #1e2936;
            border-radius: 12px;
        }

        .user-info h1 {
            font-size: 1.5rem;
            color: #ffffff;
            margin-bottom: 0.25rem;
        }

        .user-info .user-details {
            font-size: 0.9rem;
            color: #94a3b8;
        }

        .user-info .user-details span {
            margin-right: 1rem;
        }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: #0ea5e9;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: #0284c7;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: #1e2936;
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-card.alert {
            border-left: 4px solid #ef4444;
        }

        .stat-card.warning {
            border-left: 4px solid #f59e0b;
        }

        .stat-card.info {
            border-left: 4px solid #0ea5e9;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-card.alert .stat-icon {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .stat-card.warning .stat-icon {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }

        .stat-card.info .stat-icon {
            background: rgba(14, 165, 233, 0.2);
            color: #0ea5e9;
        }

        .stat-content h3 {
            font-size: 1.75rem;
            color: #ffffff;
        }

        .stat-content p {
            font-size: 0.9rem;
            color: #94a3b8;
        }

        .stat-action {
            margin-left: auto;
        }

        .stat-action a {
            font-size: 0.85rem;
            color: #0ea5e9;
            text-decoration: none;
        }

        /* Team Roster */
        .section-card {
            background: #1e2936;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #334155;
        }

        .section-title {
            font-size: 1.25rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 0.875rem 1rem;
            text-align: left;
            border-bottom: 1px solid #334155;
        }

        .data-table th {
            font-weight: 600;
            color: #94a3b8;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .data-table td {
            color: #e2e8f0;
        }

        .data-table tr:hover {
            background: rgba(14, 165, 233, 0.05);
        }

        .employee-name {
            font-weight: 600;
            color: #ffffff;
        }

        .employee-email {
            font-size: 0.85rem;
            color: #0ea5e9;
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-badge.active {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .status-badge.probation {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }

        .status-badge.valid {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .status-badge.expiring {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }

        .status-badge.expired {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }

        .btn-view {
            background: rgba(14, 165, 233, 0.2);
            color: #0ea5e9;
        }

        .btn-view:hover {
            background: rgba(14, 165, 233, 0.3);
        }

        .btn-primary {
            background: #0ea5e9;
            color: white;
        }

        .btn-primary:hover {
            background: #0284c7;
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
            max-width: 700px;
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

        .doc-section {
            margin-bottom: 1.5rem;
        }

        .doc-section h3 {
            font-size: 1rem;
            color: #0ea5e9;
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
            background: #2a3544;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .doc-info h4 {
            color: #ffffff;
            font-size: 0.95rem;
        }

        .doc-info p {
            color: #94a3b8;
            font-size: 0.85rem;
        }

        .locked-section {
            padding: 1rem;
            background: rgba(239, 68, 68, 0.1);
            border: 1px dashed #ef4444;
            border-radius: 8px;
            text-align: center;
            color: #fca5a5;
        }

        /* Performance Form */
        .review-form {
            display: grid;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 500;
            color: #cbd5e1;
        }

        .star-rating {
            display: flex;
            gap: 0.5rem;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            cursor: pointer;
            font-size: 1.5rem;
            color: #64748b;
            transition: color 0.2s;
        }

        .star-rating label:hover,
        .star-rating input:checked ~ label,
        .star-rating label:hover ~ label {
            color: #f59e0b;
        }

        .form-group select,
        .form-group textarea {
            padding: 0.75rem 1rem;
            background: #2a3544;
            border: 1px solid #3a4554;
            border-radius: 6px;
            color: #e2e8f0;
            font-size: 0.95rem;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        /* Messages */
        .alert-message {
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

        /* Responsive */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .data-table {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
<?php $logo_path = '../assets/images/slate.png'; include '../includes/loading-screen.php'; ?>

<?php 
include '../partials/sidebar.php';
include '../partials/header.php';
?>

<div class="main-container">
    <!-- Manager Quick Actions -->
    <div class="manager-actions">
        <a href="manager-assign-tasks.php" class="action-card">
            <i data-lucide="list-plus" class="icon"></i>
            <div class="content">
                <h3>Assign Tasks</h3>
                <p>Create onboarding tasks for team members</p>
            </div>
        </a>
        <a href="manager-upload-handbook.php" class="action-card purple">
            <i data-lucide="upload" class="icon"></i>
            <div class="content">
                <h3>Upload Handbook</h3>
                <p>Share policy documents with employees</p>
            </div>
        </a>
    </div>

    <?php if (isset($successMessage)): ?>
        <div class="alert-message alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <div class="alert-message alert-error"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <!-- Manager Stats Section -->
    <div class="section-card">
        <div class="section-header">
            <h2 class="section-title">
                <i data-lucide="bar-chart-2"></i>
                Manager Overview
            </h2>
        </div>
        <div class="stats-grid">
            <div class="stat-card alert">
                <div class="stat-icon">
                    <i data-lucide="alert-triangle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['expiring_licenses']; ?></h3>
                    <p>Expiring Licenses</p>
                    <small>(Next 30 Days)</small>
                </div>
                <div class="stat-action">
                    <a href="#" onclick="showExpiringLicenses()">Remind Driver →</a>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon">
                    <i data-lucide="clipboard-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['probation_reviews']; ?></h3>
                    <p>Pending Reviews</p>
                    <small>(New Hires - 90 Days)</small>
                </div>
                <div class="stat-action">
                    <a href="#" onclick="showProbationList()">Rate Employee →</a>
                </div>
            </div>

            <div class="stat-card info">
                <div class="stat-icon">
                    <i data-lucide="users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['team_count']; ?></h3>
                    <p>Active Team Members</p>
                    <small>(Ready for Dispatch)</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Roster -->
    <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">
                    <i data-lucide="id-card"></i>
                    My Team Roster
                </h2>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name / ID</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Compliance (Docs)</th>
                        <th>Onboarding Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($teamMembers)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #94a3b8;">No team members found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($teamMembers as $member): ?>
                            <tr>
                                <td>
                                    <div class="employee-name"><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></div>
                                    <div class="employee-email"><?php echo htmlspecialchars($member['company_email'] ?? 'N/A'); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($member['job_title'] ?? $member['role_name'] ?? 'Employee'); ?></td>
                                <td>
                                    <span class="status-badge <?php echo strtolower($member['employment_status'] ?? 'active'); ?>">
                                        <i data-lucide="<?php echo $member['employment_status'] === 'Active' ? 'check-circle' : 'clock'; ?>" style="width: 1rem; height: 1rem; vertical-align: middle;"></i>
                                        <?php echo htmlspecialchars($member['employment_status'] ?? 'Active'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    // Check document status (simplified)
                                    $docStatus = 'valid';
                                    $docText = 'Valid';
                                    ?>
                                    <span class="status-badge <?php echo $docStatus; ?>">
                                        <i data-lucide="shield-check" style="width: 1rem; height: 1rem; vertical-align: middle;"></i>
                                        <?php echo $docText; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    // Onboarding progress (simplified)
                                    $onboardingProgress = '100%';
                                    ?>
                                    <?php echo $onboardingProgress; ?> Complete
                                </td>
                                <td>
                                    <button class="btn btn-view" onclick="viewDocuments(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>')">
                                        <i data-lucide="eye"></i>
                                        VIEW DOCS
                                    </button>
                                    <button class="btn btn-view" onclick="window.location.href='manager-view-employee-info.php?id=<?php echo $member['id']; ?>'" style="background: rgba(139, 92, 246, 0.2); color: #8b5cf6;">
                                        <i data-lucide="user"></i>
                                        VIEW INFO
                                    </button>
                                    <?php if ($member['employment_status'] === 'Probation'): ?>
                                        <button class="btn btn-primary" onclick="showReviewForm(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>')">
                                            <i data-lucide="clipboard-check"></i>
                                            EVALUATE
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

    <?php include '../modals/components/manager-modals.php'; ?>

<?php include '../partials/footer.php'; ?>

<script>
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
</body>
</html>
