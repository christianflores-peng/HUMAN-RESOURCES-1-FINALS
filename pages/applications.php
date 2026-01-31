<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check if user has admin access (block Applicants from admin panel)
$admin_roles = ['Administrator', 'HR Manager', 'Recruiter', 'Manager', 'Supervisor', 'Employee'];
if (!in_array($_SESSION['role'] ?? '', $admin_roles)) {
    header('Location: ../careers.php');
    exit();
}

require_once '../database/config.php';

// Initialize PDO connection
$pdo = getDBConnection();

// Helper for safe output
function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $application_id = (int)$_POST['application_id'];
    $new_status = $_POST['status'];
    $notes = trim($_POST['notes'] ?? '');
    
    $allowed_statuses = ['new', 'reviewed', 'screening', 'interview', 'offer', 'hired', 'rejected'];
    
    if (in_array($new_status, $allowed_statuses)) {
        try {
            $sql = "UPDATE job_applications 
                    SET status = ?, notes = ?, reviewed_by = ?, updated_at = NOW() 
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$new_status, $notes, $_SESSION['user_id'], $application_id]);
            $_SESSION['success'] = "Application status updated successfully.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    }
    
    header('Location: applications.php');
    exit();
}

// Handle application deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $application_id = (int)$_POST['application_id'];
    
    try {
        // Get resume path before deleting
        $app = fetchSingle("SELECT resume_path FROM job_applications WHERE id = ?", [$application_id]);
        
        // Delete from database
        $sql = "DELETE FROM job_applications WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$application_id]);
        
        // Delete resume file if exists
        if ($app && $app['resume_path'] && file_exists('../' . $app['resume_path'])) {
            unlink('../' . $app['resume_path']);
        }
        
        $_SESSION['success'] = "Application deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header('Location: applications.php');
    exit();
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$job_filter = $_GET['job_id'] ?? 'all';

// Build query based on filters
$where_clauses = [];
$params = [];

if ($status_filter !== 'all') {
    $where_clauses[] = "ja.status = ?";
    $params[] = $status_filter;
}

if ($job_filter !== 'all') {
    $where_clauses[] = "ja.job_posting_id = ?";
    $params[] = (int)$job_filter;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Fetch all applications
try {
    $sql = "SELECT ja.id, ja.job_posting_id, ja.first_name, ja.last_name, ja.email, 
                   ja.phone, ja.resume_path, ja.cover_letter, ja.status, ja.applied_date,
                   ja.notes, ja.reviewed_by, ja.created_at, ja.updated_at,
                   jp.title as job_title, 
                   d.name as department_name,
                   u.first_name as reviewer_first_name,
                   u.last_name as reviewer_last_name
            FROM job_applications ja
            LEFT JOIN job_postings jp ON ja.job_posting_id = jp.id
            LEFT JOIN departments d ON jp.department_id = d.id
            LEFT JOIN users u ON ja.reviewed_by = u.id
            $where_sql
            ORDER BY ja.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $applications = [];
    $error_msg = "Error loading applications: Query execution failed.";
    error_log("Applications query error: " . $e->getMessage());
}

// Get job postings for filter dropdown
try {
    $stmt = $pdo->query("SELECT id, title FROM job_postings WHERE status IN ('active', 'draft') ORDER BY title");
    $job_postings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $job_postings = [];
    error_log("Job postings query error: " . $e->getMessage());
}

// Get statistics
try {
    $total_result = $pdo->query("SELECT COUNT(*) as count FROM job_applications")->fetch(PDO::FETCH_ASSOC);
    $new_result = $pdo->query("SELECT COUNT(*) as count FROM job_applications WHERE status = 'new'")->fetch(PDO::FETCH_ASSOC);
    $reviewed_result = $pdo->query("SELECT COUNT(*) as count FROM job_applications WHERE status = 'reviewed'")->fetch(PDO::FETCH_ASSOC);
    $interview_result = $pdo->query("SELECT COUNT(*) as count FROM job_applications WHERE status = 'interview'")->fetch(PDO::FETCH_ASSOC);
    $hired_result = $pdo->query("SELECT COUNT(*) as count FROM job_applications WHERE status = 'hired'")->fetch(PDO::FETCH_ASSOC);
    
    $stats = [
        'total' => $total_result['count'] ?? 0,
        'new' => $new_result['count'] ?? 0,
        'reviewed' => $reviewed_result['count'] ?? 0,
        'interview' => $interview_result['count'] ?? 0,
        'hired' => $hired_result['count'] ?? 0
    ];
} catch (PDOException $e) {
    $stats = ['total' => 0, 'new' => 0, 'reviewed' => 0, 'interview' => 0, 'hired' => 0];
    error_log("Statistics query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRMS - Job Applications</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .submodule { display: none; }
        .submodule.active { display: block; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--card-bg, #1e293b);
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid var(--border-color, #334155);
        }
        
        .stat-card h4 {
            color: var(--text-secondary, #94a3b8);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color, #3b82f6);
        }
        
        .filters {
            background: var(--card-bg, #1e293b);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filters label {
            font-weight: 500;
        }
        
        .filters select {
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid var(--border-color, #334155);
            background: var(--input-bg, #0f172a);
            color: var(--text-primary, #f1f5f9);
        }
        
        .application-row {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .application-row:hover {
            background-color: rgba(59, 130, 246, 0.1);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-new { background: #3b82f6; color: white; }
        .status-reviewed { background: #8b5cf6; color: white; }
        .status-screening { background: #f59e0b; color: white; }
        .status-interview { background: #10b981; color: white; }
        .status-offer { background: #06b6d4; color: white; }
        .status-hired { background: #22c55e; color: white; }
        .status-rejected { background: #ef4444; color: white; }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal-content {
            background: var(--card-bg, #1e293b);
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .detail-item {
            padding: 1rem;
            background: var(--bg-secondary, #0f172a);
            border-radius: 4px;
        }
        
        .detail-item label {
            display: block;
            font-size: 0.875rem;
            color: var(--text-secondary, #94a3b8);
            margin-bottom: 0.25rem;
        }
        
        .detail-item .value {
            font-weight: 500;
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
    </style>
</head>
<body>
    <?php 
    $active_page = 'applications';
    $page_title = 'Job Applications';
    include '../partials/sidebar.php';
    include '../partials/header.php';
    ?>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= h($_SESSION['success']); ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?= h($_SESSION['error']); ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-error">
            <?= h($error_msg); ?>
        </div>
    <?php endif; ?>

    <div id="applications-module" class="module active">
        <div class="module-header">
            <h2>Job Applications</h2>
            <a href="../careers.php" class="btn btn-primary" target="_blank">View Public Job Board</a>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h4>Total Applications</h4>
                <div class="number"><?= $stats['total'] ?></div>
            </div>
            <div class="stat-card">
                <h4>New Applications</h4>
                <div class="number"><?= $stats['new'] ?></div>
            </div>
            <div class="stat-card">
                <h4>In Review</h4>
                <div class="number"><?= $stats['reviewed'] ?></div>
            </div>
            <div class="stat-card">
                <h4>Interviews</h4>
                <div class="number"><?= $stats['interview'] ?></div>
            </div>
            <div class="stat-card">
                <h4>Hired</h4>
                <div class="number"><?= $stats['hired'] ?></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <div>
                    <label for="status">Status:</label>
                    <select name="status" id="status" onchange="this.form.submit()">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="new" <?= $status_filter === 'new' ? 'selected' : '' ?>>New</option>
                        <option value="reviewed" <?= $status_filter === 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                        <option value="screening" <?= $status_filter === 'screening' ? 'selected' : '' ?>>Screening</option>
                        <option value="interview" <?= $status_filter === 'interview' ? 'selected' : '' ?>>Interview</option>
                        <option value="offer" <?= $status_filter === 'offer' ? 'selected' : '' ?>>Offer</option>
                        <option value="hired" <?= $status_filter === 'hired' ? 'selected' : '' ?>>Hired</option>
                        <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                <div>
                    <label for="job_id">Job Position:</label>
                    <select name="job_id" id="job_id" onchange="this.form.submit()">
                        <option value="all" <?= $job_filter === 'all' ? 'selected' : '' ?>>All Positions</option>
                        <?php foreach ($job_postings as $job): ?>
                            <option value="<?= $job['id'] ?>" <?= $job_filter == $job['id'] ? 'selected' : '' ?>>
                                <?= h($job['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($status_filter !== 'all' || $job_filter !== 'all'): ?>
                    <a href="applications.php" class="btn btn-sm">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Applications Table -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Applicant Name</th>
                        <th>Job Position</th>
                        <th>Department</th>
                        <th>Applied Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($applications)): ?>
                        <?php foreach ($applications as $app): ?>
                            <tr class="application-row" onclick="viewApplication(<?= htmlspecialchars(json_encode($app), ENT_QUOTES, 'UTF-8') ?>)">
                                <td><?= h($app['first_name'] . ' ' . $app['last_name']) ?></td>
                                <td><?= h($app['job_title']) ?></td>
                                <td><?= h($app['department_name']) ?></td>
                                <td><?= date('M d, Y', strtotime($app['applied_date'])) ?></td>
                                <td>
                                    <span class="status-badge status-<?= h($app['status']) ?>">
                                        <?= ucfirst(h($app['status'])) ?>
                                    </span>
                                </td>
                                <td onclick="event.stopPropagation()">
                                    <button class="btn btn-sm" onclick="viewApplication(<?= htmlspecialchars(json_encode($app), ENT_QUOTES, 'UTF-8') ?>)">View</button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteApplication(<?= $app['id'] ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No applications found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include '../modals/components/applications-modal.php'; ?>

    <?php include '../partials/footer.php'; ?>
</body>
</html>
