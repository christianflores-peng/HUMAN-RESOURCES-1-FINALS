<?php
session_start();

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

// Get job posting ID from URL
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($job_id <= 0) {
    $_SESSION['error'] = 'Invalid job posting ID.';
    header('Location: recruitment.php');
    exit();
}

// Fetch job posting details
try {
    $job = fetchSingle("
        SELECT jp.*, d.name as department_name, u.full_name as posted_by_name
        FROM job_postings jp
        LEFT JOIN departments d ON d.id = jp.department_id
        LEFT JOIN users u ON u.id = jp.posted_by
        WHERE jp.id = ?
    ", [$job_id]);
    
    if (!$job) {
        $_SESSION['error'] = 'Job posting not found.';
        header('Location: recruitment.php');
        exit();
    }
    
    // Fetch applications for this job
    $applications = fetchAll("
        SELECT ja.*, 
               DATE_FORMAT(ja.created_at, '%M %d, %Y') as applied_date
        FROM job_applications ja
        WHERE ja.job_posting_id = ?
        ORDER BY ja.created_at DESC
    ", [$job_id]);
    
    // Count applications by status
    $status_counts = [
        'new' => 0,
        'reviewed' => 0,
        'screening' => 0,
        'interview' => 0,
        'offer' => 0,
        'hired' => 0,
        'rejected' => 0
    ];
    
    foreach ($applications as $app) {
        if (isset($status_counts[$app['status']])) {
            $status_counts[$app['status']]++;
        }
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Error loading job posting: ' . $e->getMessage();
    header('Location: recruitment.php');
    exit();
}

function h($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($job['title']); ?> - Job Posting</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .job-posting-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #0ea5e9;
            text-decoration: none;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .back-button:hover {
            text-decoration: underline;
        }

        .job-header {
            background: var(--card-bg, #1e293b);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .job-title-section {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1.5rem;
        }

        .job-title {
            font-size: 2rem;
            color: #f8fafc;
            margin-bottom: 0.5rem;
        }

        .job-meta {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            color: #94a3b8;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.875rem;
        }

        .status-badge.active {
            background: #10b981;
            color: white;
        }

        .status-badge.draft {
            background: #f59e0b;
            color: white;
        }

        .status-badge.closed {
            background: #64748b;
            color: white;
        }

        .job-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg, #1e293b);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #0ea5e9;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #94a3b8;
            font-size: 0.95rem;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .job-details {
            background: var(--card-bg, #1e293b);
            border-radius: 12px;
            padding: 2rem;
        }

        .job-details h3 {
            color: #f8fafc;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        .job-details p {
            color: #cbd5e1;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .job-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .sidebar-card {
            background: var(--card-bg, #1e293b);
            border-radius: 12px;
            padding: 1.5rem;
        }

        .sidebar-card h4 {
            color: #f8fafc;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #334155;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #94a3b8;
        }

        .info-value {
            color: #f8fafc;
            font-weight: 500;
        }

        .applications-section {
            margin-top: 2rem;
        }

        .applications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .applications-table {
            background: var(--card-bg, #1e293b);
            border-radius: 12px;
            overflow: hidden;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: #0f172a;
            color: #94a3b8;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.875rem;
        }

        .data-table td {
            padding: 1rem;
            border-top: 1px solid #334155;
            color: #cbd5e1;
        }

        .data-table tr:hover {
            background: #0f172a;
        }

        .app-status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .app-status-badge.new { background: #3b82f6; color: white; }
        .app-status-badge.reviewed { background: #8b5cf6; color: white; }
        .app-status-badge.screening { background: #f59e0b; color: white; }
        .app-status-badge.interview { background: #06b6d4; color: white; }
        .app-status-badge.offer { background: #10b981; color: white; }
        .app-status-badge.hired { background: #059669; color: white; }
        .app-status-badge.rejected { background: #ef4444; color: white; }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #0ea5e9;
            color: white;
        }

        .btn-primary:hover {
            background: #0284c7;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        @media (max-width: 968px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .job-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <?php 
    $active_page = 'recruitment';
    $page_title = 'Job Posting Details';
    include '../partials/sidebar.php';
    include '../partials/header.php';
    ?>

    <div class="job-posting-container">
        <a href="recruitment.php" class="back-button">← Back to Recruitment</a>

        <!-- Job Header -->
        <div class="job-header">
            <div class="job-title-section">
                <div>
                    <h1 class="job-title"><?php echo h($job['title']); ?></h1>
                    <div class="job-meta">
                        <div class="meta-item">
                            <span>📍</span>
                            <span><?php echo h($job['location']); ?></span>
                        </div>
                        <div class="meta-item">
                            <span>🏢</span>
                            <span><?php echo h($job['department_name']); ?></span>
                        </div>
                        <div class="meta-item">
                            <span>💼</span>
                            <span><?php echo h($job['employment_type']); ?></span>
                        </div>
                    </div>
                </div>
                <span class="status-badge <?php echo strtolower($job['status']); ?>">
                    <?php echo h(ucfirst($job['status'])); ?>
                </span>
            </div>
        </div>

        <!-- Statistics -->
        <div class="job-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($applications); ?></div>
                <div class="stat-label">Total Applications</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $status_counts['new'] + $status_counts['reviewed']; ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $status_counts['interview']; ?></div>
                <div class="stat-label">In Interview</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $status_counts['hired']; ?></div>
                <div class="stat-label">Hired</div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Job Details -->
            <div class="job-details">
                <h3>Job Description</h3>
                <p><?php echo nl2br(h($job['description'])); ?></p>

                <h3>Requirements</h3>
                <p><?php echo nl2br(h($job['requirements'])); ?></p>
            </div>

            <!-- Sidebar -->
            <div class="job-sidebar">
                <div class="sidebar-card">
                    <h4>Job Information</h4>
                    <div class="info-row">
                        <span class="info-label">Posted By</span>
                        <span class="info-value"><?php echo h($job['posted_by_name'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Posted Date</span>
                        <span class="info-value"><?php echo date('M d, Y', strtotime($job['created_at'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Closing Date</span>
                        <span class="info-value"><?php echo $job['closing_date'] ? date('M d, Y', strtotime($job['closing_date'])) : 'Open'; ?></span>
                    </div>
                    <?php if ($job['salary_min'] || $job['salary_max']): ?>
                    <div class="info-row">
                        <span class="info-label">Salary Range</span>
                        <span class="info-value">
                            <?php 
                            if ($job['salary_min'] && $job['salary_max']) {
                                echo '$' . number_format($job['salary_min']) . ' - $' . number_format($job['salary_max']);
                            } elseif ($job['salary_min']) {
                                echo 'From $' . number_format($job['salary_min']);
                            } elseif ($job['salary_max']) {
                                echo 'Up to $' . number_format($job['salary_max']);
                            }
                            ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="sidebar-card">
                    <h4>Quick Actions</h4>
                    <button class="btn btn-primary" style="width: 100%; margin-bottom: 0.5rem;" onclick="window.location.href='recruitment.php'">Edit Posting</button>
                    <button class="btn btn-primary" style="width: 100%;" onclick="window.location.href='applicant-management.php?job_id=<?php echo $job_id; ?>'">Manage Applications</button>
                </div>
            </div>
        </div>

        <!-- Applications Section -->
        <div class="applications-section">
            <div class="applications-header">
                <h2 style="color: #f8fafc;">Applications (<?php echo count($applications); ?>)</h2>
                <button class="btn btn-primary" onclick="window.location.href='applicant-management.php?job_id=<?php echo $job_id; ?>'">
                    View All Applications
                </button>
            </div>

            <div class="applications-table">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Applicant Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Applied Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($applications)): ?>
                            <?php foreach (array_slice($applications, 0, 10) as $app): ?>
                                <tr>
                                    <td><?php echo h($app['first_name'] . ' ' . $app['last_name']); ?></td>
                                    <td><?php echo h($app['email']); ?></td>
                                    <td><?php echo h($app['phone']); ?></td>
                                    <td><?php echo h($app['applied_date']); ?></td>
                                    <td>
                                        <span class="app-status-badge <?php echo h($app['status']); ?>">
                                            <?php echo h(ucfirst($app['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="window.location.href='applicant-management.php?id=<?php echo $app['id']; ?>'">
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem; color: #64748b;">
                                    No applications yet for this position.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
