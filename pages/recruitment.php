<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get current user info
$current_user = $_SESSION['username'] ?? 'User';
$current_role = $_SESSION['role'] ?? 'Employee';
// Database
require_once '../database/config.php';

// Small helper for safe output
function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Load Active/Draft job requisitions with application counts
try {
    $jobRequisitions = fetchAll(
        "SELECT jp.id,
                jp.title,
                d.name AS department,
                jp.status,
                (SELECT COUNT(*) FROM job_applications ja WHERE ja.job_posting_id = jp.id) AS applications
         FROM job_postings jp
         JOIN departments d ON d.id = jp.department_id
         WHERE jp.status IN ('active','draft')
         ORDER BY jp.created_at DESC"
    );
} catch (Exception $e) {
    $jobRequisitions = [];
}

// Load recent applications for screening view
try {
    $recentApplications = fetchAll(
        "SELECT ja.id,
                ja.first_name,
                ja.last_name,
                ja.status,
                jp.title AS job_title
         FROM job_applications ja
         JOIN job_postings jp ON jp.id = ja.job_posting_id
         ORDER BY ja.created_at DESC
         LIMIT 8"
    );
} catch (Exception $e) {
    $recentApplications = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Management System - Recruitment</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<?php 
$active_page = 'recruitment';
$page_title = 'Recruitment Management';
include '../partials/sidebar.php';
include '../partials/header.php';
?>
            <!-- Recruitment Module -->
            <div id="recruitment-module" class="module active">
                <div class="module-header">
                    <h2>Recruitment Management</h2>
                    <button class="btn btn-primary" onclick="showSubModule('job-requisition')">New Job Requisition</button>
                </div>

                <div class="submodule-nav">
                    <button class="submodule-btn active" data-submodule="job-requisition">Job Requisition</button>
                    <button class="submodule-btn" data-submodule="sourcing">Sourcing</button>
                    <button class="submodule-btn" data-submodule="screening">Screening</button>
                    <button class="submodule-btn" data-submodule="interview">Interview Process</button>
                </div>

                <!-- Job Requisition Submodule -->
                <div id="job-requisition" class="submodule active">
                    <div class="form-container">
                        <h3>Create Job Requisition</h3>
                        <form class="job-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Job Title</label>
                                    <input type="text" placeholder="e.g., Senior Software Engineer">
                                </div>
                                <div class="form-group">
                                    <label>Department</label>
                                    <select>
                                        <option>Engineering</option>
                                        <option>Marketing</option>
                                        <option>Sales</option>
                                        <option>HR</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Location</label>
                                    <input type="text" placeholder="e.g., San Francisco, CA">
                                </div>
                                <div class="form-group">
                                    <label>Employment Type</label>
                                    <select>
                                        <option>Full-time</option>
                                        <option>Part-time</option>
                                        <option>Contract</option>
                                        <option>Internship</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Job Description</label>
                                <textarea rows="4" placeholder="Describe the role and responsibilities..."></textarea>
                            </div>
                            <div class="form-group">
                                <label>Requirements</label>
                                <textarea rows="3" placeholder="List required skills and qualifications..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Create Requisition</button>
                        </form>
                    </div>

                    <div class="table-container">
                        <h3>Active Job Requisitions</h3>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Job Title</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Applications</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($jobRequisitions)) : ?>
                                    <?php foreach ($jobRequisitions as $req) : ?>
                                        <tr>
                                            <td><?php echo h($req['title']); ?></td>
                                            <td><?php echo h($req['department']); ?></td>
                                            <td>
                                                <?php
                                                    $status = strtolower((string)$req['status']);
                                                    $badgeClass = $status === 'active' ? 'active' : ($status === 'draft' ? 'new' : '');
                                                ?>
                                                <span class="status-badge <?php echo h($badgeClass); ?>"><?php echo h(ucfirst($status)); ?></span>
                                            </td>
                                            <td><?php echo (int)$req['applications']; ?></td>
                                            <td>
                                                <button class="btn btn-sm">View</button>
                                                <button class="btn btn-sm">Edit</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center;">No job requisitions found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sourcing Submodule -->
                <div id="sourcing" class="submodule">
                    <div class="sourcing-tools">
                        <h3>Candidate Sourcing</h3>
                        <div class="sourcing-grid">
                            <div class="sourcing-card">
                                <h4>Job Boards</h4>
                                <p>Post to LinkedIn, Indeed, Glassdoor</p>
                                <button class="btn btn-primary">Post Jobs</button>
                            </div>
                            <div class="sourcing-card">
                                <h4>Social Media</h4>
                                <p>Share on company social channels</p>
                                <button class="btn btn-primary">Share</button>
                            </div>
                            <div class="sourcing-card">
                                <h4>Employee Referrals</h4>
                                <p>Internal referral program</p>
                                <button class="btn btn-primary">Send Referral</button>
                            </div>
                            <div class="sourcing-card">
                                <h4>Talent Pool</h4>
                                <p>Search existing candidate database</p>
                                <button class="btn btn-primary">Search</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Screening Submodule -->
                <div id="screening" class="submodule">
                    <div class="screening-container">
                        <h3>Application Screening</h3>
                        <div class="filter-bar">
                            <input type="text" placeholder="Search candidates..." class="search-input">
                            <select class="filter-select">
                                <option>All Positions</option>
                                <option>Software Engineer</option>
                                <option>Marketing Manager</option>
                            </select>
                            <select class="filter-select">
                                <option>All Status</option>
                                <option>New</option>
                                <option>Reviewed</option>
                                <option>Shortlisted</option>
                            </select>
                        </div>
                        
                        <div class="candidates-grid">
                            <?php if (!empty($recentApplications)) : ?>
                                <?php foreach ($recentApplications as $app) : ?>
                                    <div class="candidate-card">
                                        <div class="candidate-header">
                                            <h4><?php echo h($app['first_name'] . ' ' . $app['last_name']); ?></h4>
                                            <?php $aStatus = strtolower((string)$app['status']); ?>
                                            <span class="status-badge <?php echo h($aStatus); ?>"><?php echo h(ucfirst($aStatus)); ?></span>
                                        </div>
                                        <p><?php echo h($app['job_title']); ?></p>
                                        <p>Recent application</p>
                                        <div class="candidate-actions">
                                            <button class="btn btn-sm btn-success">Shortlist</button>
                                            <button class="btn btn-sm">View Resume</button>
                                            <button class="btn btn-sm btn-danger">Reject</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <div class="candidate-card">
                                    <div class="candidate-header">
                                        <h4>No applications yet</h4>
                                        <span class="status-badge new">New</span>
                                    </div>
                                    <p>Applications will appear here as they arrive.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Interview Process Submodule -->
                <div id="interview" class="submodule">
                    <div class="interview-container">
                        <h3>Interview Management</h3>
                        <div class="interview-calendar">
                            <h4>Upcoming Interviews</h4>
                            <div class="interview-list">
                                <div class="interview-item">
                                    <div class="interview-time">
                                        <span class="date">Jan 20</span>
                                        <span class="time">2:00 PM</span>
                                    </div>
                                    <div class="interview-details">
                                        <h5>John Smith - Software Engineer</h5>
                                        <p>Technical Interview with Engineering Team</p>
                                    </div>
                                    <div class="interview-actions">
                                        <button class="btn btn-sm">Reschedule</button>
                                        <button class="btn btn-sm btn-primary">Join</button>
                                    </div>
                                </div>
                                
                                <div class="interview-item">
                                    <div class="interview-time">
                                        <span class="date">Jan 21</span>
                                        <span class="time">10:00 AM</span>
                                    </div>
                                    <div class="interview-details">
                                        <h5>Sarah Johnson - Marketing Manager</h5>
                                        <p>Final Interview with VP Marketing</p>
                                    </div>
                                    <div class="interview-actions">
                                        <button class="btn btn-sm">Reschedule</button>
                                        <button class="btn btn-sm btn-primary">Join</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<?php include '../partials/footer.php'; ?>
