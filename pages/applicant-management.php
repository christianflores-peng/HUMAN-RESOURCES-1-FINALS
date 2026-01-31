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

// Get current user info
$current_user = $_SESSION['username'] ?? 'User';
$current_role = $_SESSION['role'] ?? 'Employee';
// Database
require_once '../database/config.php';

// Safe output helper
function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Update applicant status
    if ($action === 'update_status') {
        $application_id = (int)($_POST['application_id'] ?? 0);
        $new_status = $_POST['new_status'] ?? '';
        
        $valid_statuses = ['new', 'reviewed', 'screening', 'interview', 'offer', 'hired', 'rejected'];
        
        if ($application_id && in_array($new_status, $valid_statuses)) {
            try {
                $pdo = getDBConnection();
                
                // If hiring the applicant, also add them to the employees table
                if ($new_status === 'hired') {
                    // Get applicant details
                    $applicant = fetchSingle(
                        "SELECT ja.*, jp.title AS job_title, jp.department_id 
                         FROM job_applications ja 
                         JOIN job_postings jp ON jp.id = ja.job_posting_id 
                         WHERE ja.id = ?",
                        [$application_id]
                    );
                    
                    if ($applicant) {
                        // Check if employee already exists with this email
                        $existingEmployee = fetchSingle(
                            "SELECT id FROM employees WHERE email = ?",
                            [$applicant['email']]
                        );
                        
                        if (!$existingEmployee) {
                            // Create a user account for the new employee
                            $username = strtolower($applicant['first_name'] . '.' . $applicant['last_name']);
                            $default_password = password_hash('Welcome123!', PASSWORD_DEFAULT);
                            
                            // Check if username exists, add number if needed
                            $usernameCheck = fetchSingle("SELECT id FROM users WHERE username = ?", [$username]);
                            if ($usernameCheck) {
                                $username = $username . rand(100, 999);
                            }
                            
                            // Insert user account
                            $userStmt = $pdo->prepare(
                                "INSERT INTO users (username, password, role, full_name, email, created_at) 
                                 VALUES (?, ?, 'Employee', ?, ?, NOW())"
                            );
                            $userStmt->execute([
                                $username,
                                $default_password,
                                $applicant['first_name'] . ' ' . $applicant['last_name'],
                                $applicant['email']
                            ]);
                            $new_user_id = $pdo->lastInsertId();
                            
                            // Generate employee_id
                            $employee_id = 'EMP' . str_pad($new_user_id, 5, '0', STR_PAD_LEFT);
                            
                            // Insert into employees table
                            $stmt = $pdo->prepare(
                                "INSERT INTO employees (user_id, employee_id, first_name, last_name, email, phone, position, department_id, hire_date, status, created_at) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'active', NOW())"
                            );
                            $stmt->execute([
                                $new_user_id,
                                $employee_id,
                                $applicant['first_name'],
                                $applicant['last_name'],
                                $applicant['email'],
                                $applicant['phone'] ?? null,
                                $applicant['job_title'],
                                $applicant['department_id'],
                            ]);
                            
                            $success_message = "Applicant hired successfully! Username: {$username}, Default password: Welcome123!";
                        } else {
                            $success_message = "Applicant hired! (Employee record already exists)";
                        }
                    }
                }
                
                // Update the application status
                executeQuery(
                    "UPDATE job_applications SET status = ?, updated_at = NOW() WHERE id = ?",
                    [$new_status, $application_id]
                );
                
                if (empty($success_message)) {
                    $success_message = "Applicant status updated successfully!";
                }
            } catch (Exception $e) {
                $error_message = "Error updating status: " . $e->getMessage();
            }
        } else {
            $error_message = "Invalid status or application ID.";
        }
    }
    
    // Redirect to avoid form resubmission
    if ($success_message || $error_message) {
        $_SESSION['success_message'] = $success_message;
        $_SESSION['error_message'] = $error_message;
        header('Location: applicant-management.php');
        exit();
    }
}

// Get messages from session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Prepare data buckets
$pipeline = [
    'applied'   => ['label' => 'Applied',   'statuses' => ['new'],                    'count' => 0, 'items' => []],
    'screening' => ['label' => 'Screening', 'statuses' => ['screening','reviewed'],  'count' => 0, 'items' => []],
    'interview' => ['label' => 'Interview', 'statuses' => ['interview'],             'count' => 0, 'items' => []],
    'offer'     => ['label' => 'Offer',     'statuses' => ['offer'],                 'count' => 0, 'items' => []],
];

// Load counts and samples for each column
foreach ($pipeline as $key => $cfg) {
    $placeholders = implode(',', array_fill(0, count($cfg['statuses']), '?'));
    try {
        $row = fetchSingle("SELECT COUNT(*) as c FROM job_applications WHERE status IN ($placeholders)", $cfg['statuses']);
        $pipeline[$key]['count'] = (int)($row['c'] ?? 0);
    } catch (Exception $e) {
        $pipeline[$key]['count'] = 0;
    }
    try {
        $pipeline[$key]['items'] = fetchAll(
            "SELECT ja.id, ja.first_name, ja.last_name, ja.status, jp.title AS job_title, ja.created_at
             FROM job_applications ja
             JOIN job_postings jp ON jp.id = ja.job_posting_id
             WHERE ja.status IN ($placeholders)
             ORDER BY ja.created_at DESC
             LIMIT 6",
            $cfg['statuses']
        );
    } catch (Exception $e) {
        $pipeline[$key]['items'] = [];
    }
}

// Fetch document statistics
$documentStats = [
    'resumes' => 0,
    'cover_letters' => 0,
    'certificates' => 0,
    'background_checks' => 0
];

try {
    // Count resumes (applications with resume_path)
    $resumeCount = fetchSingle("SELECT COUNT(*) as c FROM job_applications WHERE resume_path IS NOT NULL AND resume_path != ''");
    $documentStats['resumes'] = (int)($resumeCount['c'] ?? 0);
    
    // Count cover letters (applications with cover_letter)
    $coverLetterCount = fetchSingle("SELECT COUNT(*) as c FROM job_applications WHERE cover_letter IS NOT NULL AND cover_letter != ''");
    $documentStats['cover_letters'] = (int)($coverLetterCount['c'] ?? 0);
    
    // For now, certificates and background checks are placeholders
    // These would need additional tables/columns to track properly
    $documentStats['certificates'] = 0;
    $documentStats['background_checks'] = 0;
} catch (Exception $e) {
    // Keep default zeros
}

// Fetch all applicants with documents for the document management section
$applicantsWithDocuments = [];
try {
    $applicantsWithDocuments = fetchAll(
        "SELECT ja.id, ja.first_name, ja.last_name, ja.email, ja.resume_path, ja.cover_letter, 
                ja.status, jp.title AS job_title, ja.created_at
         FROM job_applications ja
         JOIN job_postings jp ON jp.id = ja.job_posting_id
         WHERE ja.resume_path IS NOT NULL OR ja.cover_letter IS NOT NULL
         ORDER BY ja.created_at DESC"
    );
} catch (Exception $e) {
    $applicantsWithDocuments = [];
}

// Fetch selected applicant details if view parameter is present
$selectedApplicant = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $viewId = (int)$_GET['view'];
    try {
        $selectedApplicant = fetchSingle(
            "SELECT ja.*, jp.title AS job_title, jp.description AS job_description, 
                    jp.requirements AS job_requirements, jp.location, jp.employment_type,
                    jp.salary_min, jp.salary_max
             FROM job_applications ja
             JOIN job_postings jp ON jp.id = ja.job_posting_id
             WHERE ja.id = ?",
            [$viewId]
        );
    } catch (Exception $e) {
        $selectedApplicant = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Management System - Applicant Management</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<?php 
$active_page = 'applicant-management';
$page_title = 'Applicant Management';
include '../partials/sidebar.php';
include '../partials/header.php';
?>
            <!-- Applicant Management Module -->
            <div id="applicant-management-module" class="module active">
                <div class="module-header">
                    <h2>Applicant Management</h2>
                    <button class="btn btn-primary" onclick="showSubModule('tracking')">View Pipeline</button>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo h($success_message); ?></div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-error"><?php echo h($error_message); ?></div>
                <?php endif; ?>

                <div class="submodule-nav">
                    <button class="submodule-btn active" data-submodule="tracking">Tracking</button>
                    <button class="submodule-btn" data-submodule="documents">Document Management</button>
                    <button class="submodule-btn" data-submodule="collaboration">Collaboration</button>
                </div>

                <!-- Tracking Submodule -->
                <div id="tracking" class="submodule active">
                    <div class="kanban-board">
                        <div class="kanban-column" data-status="new">
                            <h4>Applied <span class="count"><?php echo (int)$pipeline['applied']['count']; ?></span></h4>
                            <div class="kanban-cards">
                                <?php if (!empty($pipeline['applied']['items'])): ?>
                                    <?php foreach ($pipeline['applied']['items'] as $item): ?>
                                        <div class="kanban-card" draggable="true" data-app-id="<?php echo (int)$item['id']; ?>">
                                            <h5><?php echo h($item['first_name'] . ' ' . $item['last_name']); ?></h5>
                                            <p><?php echo h($item['job_title']); ?></p>
                                            <small>New application</small>
                                            <div style="display: flex; gap: 0.25rem; margin-top: 0.5rem; flex-wrap: wrap;">
                                                <button class="btn btn-sm" onclick="viewApplicantDetails(<?php echo $item['id']; ?>)" style="flex: 1; font-size: 0.7rem; padding: 0.25rem 0.5rem;">View</button>
                                                <button class="btn btn-sm btn-primary" onclick="progressApplicant(<?php echo $item['id']; ?>, 'new')" style="flex: 1; font-size: 0.7rem; padding: 0.25rem 0.5rem;">Proceed</button>
                                                <button class="btn btn-sm btn-danger" onclick="rejectApplicantStatus(<?php echo $item['id']; ?>)" style="flex: 1; font-size: 0.7rem; padding: 0.25rem 0.5rem;">Reject</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="kanban-card"><h5>No applicants yet</h5><p>—</p></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="kanban-column" data-status="screening">
                            <h4>Screening <span class="count"><?php echo (int)$pipeline['screening']['count']; ?></span></h4>
                            <div class="kanban-cards">
                                <?php if (!empty($pipeline['screening']['items'])): ?>
                                    <?php foreach ($pipeline['screening']['items'] as $item): ?>
                                        <div class="kanban-card" draggable="true" data-app-id="<?php echo (int)$item['id']; ?>">
                                            <h5><?php echo h($item['first_name'] . ' ' . $item['last_name']); ?></h5>
                                            <p><?php echo h($item['job_title']); ?></p>
                                            <small>In screening</small>
                                            <div style="display: flex; gap: 0.25rem; margin-top: 0.5rem; flex-wrap: wrap;">
                                                <button class="btn btn-sm" onclick="viewApplicantDetails(<?php echo $item['id']; ?>)" style="flex: 1; font-size: 0.7rem; padding: 0.25rem 0.5rem;">View</button>
                                                <button class="btn btn-sm btn-primary" onclick="progressApplicant(<?php echo $item['id']; ?>, 'screening')" style="flex: 1; font-size: 0.7rem; padding: 0.25rem 0.5rem;">Proceed</button>
                                                <button class="btn btn-sm btn-danger" onclick="rejectApplicantStatus(<?php echo $item['id']; ?>)" style="flex: 1; font-size: 0.7rem; padding: 0.25rem 0.5rem;">Reject</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="kanban-card"><h5>Nothing in screening</h5><p>—</p></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="kanban-column" data-status="interview">
                            <h4>Interview <span class="count"><?php echo (int)$pipeline['interview']['count']; ?></span></h4>
                            <div class="kanban-cards">
                                <?php if (!empty($pipeline['interview']['items'])): ?>
                                    <?php foreach ($pipeline['interview']['items'] as $item): ?>
                                        <div class="kanban-card" draggable="true" data-app-id="<?php echo (int)$item['id']; ?>">
                                            <h5><?php echo h($item['first_name'] . ' ' . $item['last_name']); ?></h5>
                                            <p><?php echo h($item['job_title']); ?></p>
                                            <small>Interview stage</small>
                                            <div style="display: flex; gap: 0.25rem; margin-top: 0.5rem; flex-wrap: wrap;">
                                                <button class="btn btn-sm" onclick="viewApplicantDetails(<?php echo $item['id']; ?>)" style="flex: 1; font-size: 0.7rem; padding: 0.25rem 0.5rem;">View</button>
                                                <button class="btn btn-sm btn-primary" onclick="progressApplicant(<?php echo $item['id']; ?>, 'interview')" style="flex: 1; font-size: 0.7rem; padding: 0.25rem 0.5rem;">Proceed</button>
                                                <button class="btn btn-sm btn-danger" onclick="rejectApplicantStatus(<?php echo $item['id']; ?>)" style="flex: 1; font-size: 0.7rem; padding: 0.25rem 0.5rem;">Reject</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="kanban-card"><h5>No interviews scheduled</h5><p>—</p></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="kanban-column" data-status="offer">
                            <h4>Offer <span class="count"><?php echo (int)$pipeline['offer']['count']; ?></span></h4>
                            <div class="kanban-cards">
                                <?php if (!empty($pipeline['offer']['items'])): ?>
                                    <?php foreach ($pipeline['offer']['items'] as $item): ?>
                                        <div class="kanban-card" draggable="true" data-app-id="<?php echo (int)$item['id']; ?>">
                                            <h5><?php echo h($item['first_name'] . ' ' . $item['last_name']); ?></h5>
                                            <p><?php echo h($item['job_title']); ?></p>
                                            <small>Offer pending</small>
                                            <div style="display: flex; gap: 0.25rem; margin-top: 0.5rem; flex-wrap: wrap;">
                                                <button class="btn btn-sm" onclick="viewApplicantDetails(<?php echo $item['id']; ?>)" style="flex: 1; font-size: 0.7rem; padding: 0.25rem 0.5rem;">View</button>
                                                <button class="btn btn-sm btn-success" onclick="progressApplicant(<?php echo $item['id']; ?>, 'offer')" style="flex: 1; font-size: 0.7rem; padding: 0.25rem 0.5rem;">Hire</button>
                                                <button class="btn btn-sm btn-danger" onclick="rejectApplicantStatus(<?php echo $item['id']; ?>)" style="flex: 1; font-size: 0.7rem; padding: 0.25rem 0.5rem;">Reject</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="kanban-card"><h5>No offers yet</h5><p>—</p></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Document Management Submodule -->
                <div id="documents" class="submodule">
                    <div class="document-manager">
                        <h3>Document Management</h3>
                        <div class="document-grid">
                            <div class="document-card">
                                <div class="document-icon"><span class="material-symbols-outlined">description</span></div>
                                <h4>Resumes</h4>
                                <p><?php echo $documentStats['resumes']; ?> files</p>
                                <button class="btn btn-sm" onclick="filterDocuments('resume')">Manage</button>
                            </div>
                            <div class="document-card">
                                <div class="document-icon"><span class="material-symbols-outlined" style="font-size: 2rem; color: #0ea5e9;">description</span></div>
                                <h4>Cover Letters</h4>
                                <p><?php echo $documentStats['cover_letters']; ?> files</p>
                                <button class="btn btn-sm" onclick="filterDocuments('cover_letter')">Manage</button>
                            </div>
                            <div class="document-card">
                                <div class="document-icon"><span class="material-symbols-outlined">workspace_premium</span></div>
                                <h4>Certificates</h4>
                                <p><?php echo $documentStats['certificates']; ?> files</p>
                                <button class="btn btn-sm" onclick="filterDocuments('certificate')">Manage</button>
                            </div>
                            <div class="document-card">
                                <div class="document-icon"><span class="material-symbols-outlined">verified</span></div>
                                <h4>Background Checks</h4>
                                <p><?php echo $documentStats['background_checks']; ?> files</p>
                                <button class="btn btn-sm" onclick="filterDocuments('background')">Manage</button>
                            </div>
                        </div>

                        <!-- Applicant Documents Table -->
                        <div class="table-container" style="margin-top: 2rem;">
                            <h4 style="margin-bottom: 1rem;">Applicant Documents</h4>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Applicant Name</th>
                                        <th>Email</th>
                                        <th>Position</th>
                                        <th>Status</th>
                                        <th>Resume</th>
                                        <th>Cover Letter</th>
                                        <th>Applied Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($applicantsWithDocuments)): ?>
                                        <?php foreach ($applicantsWithDocuments as $applicant): ?>
                                            <tr>
                                                <td><?php echo h($applicant['first_name'] . ' ' . $applicant['last_name']); ?></td>
                                                <td><?php echo h($applicant['email']); ?></td>
                                                <td><?php echo h($applicant['job_title']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo h($applicant['status']); ?>">
                                                        <?php echo ucfirst(h($applicant['status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($applicant['resume_path'])): ?>
                                                        <a href="view_file.php?file=<?php echo urlencode($applicant['resume_path']); ?>" target="_blank" class="btn btn-sm btn-primary">
                                                            <span class="material-symbols-outlined" style="font-size: 1rem; vertical-align: middle;">visibility</span> View
                                                        </a>
                                                    <?php else: ?>
                                                        <span style="color: #64748b;">No resume</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($applicant['cover_letter'])): ?>
                                                        <button class="btn btn-sm btn-primary" onclick="viewCoverLetter(<?php echo $applicant['id']; ?>, '<?php echo h(addslashes($applicant['first_name'] . ' ' . $applicant['last_name'])); ?>')">
                                                            <span class="material-symbols-outlined" style="font-size: 1rem; vertical-align: middle;">description</span> View
                                                        </button>
                                                    <?php else: ?>
                                                        <span style="color: #64748b;">No cover letter</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($applicant['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center; color: #64748b; padding: 2rem;">
                                                No documents found. Applicants will appear here once they upload their resumes or cover letters.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Collaboration Submodule -->
                <div id="collaboration" class="submodule">
                    <div class="collaboration-tools">
                        <h3>Team Collaboration</h3>
                        <div class="collaboration-grid">
                            <div class="collab-card">
                                <h4>Interview Feedback</h4>
                                <p>Collect and review feedback from interview panels</p>
                                <button class="btn btn-primary">View Feedback</button>
                            </div>
                            <div class="collab-card">
                                <h4>Hiring Committee</h4>
                                <p>Coordinate with hiring committee members</p>
                                <button class="btn btn-primary">Committee Notes</button>
                            </div>
                            <div class="collab-card">
                                <h4>Reference Checks</h4>
                                <p>Manage reference check process</p>
                                <button class="btn btn-primary">Check References</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

<style>
.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.alert-success {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: #6ee7b7;
}

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #fca5a5;
}
</style>

<!-- Applicant Details Modal -->
<?php if ($selectedApplicant): ?>
<div id="applicantDetailsModal" class="modal" style="display:flex; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); justify-content:center; align-items:center; z-index:1000;">
    <div class="modal-content" style="background:var(--card-bg, #1e293b); padding:2rem; border-radius:8px; width:90%; max-width:900px; max-height:85vh; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3 style="margin:0;">Applicant Details</h3>
            <button type="button" class="btn" onclick="closeApplicantModal()" style="padding:0.5rem 1rem;">✕ Close</button>
        </div>
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem;">
            <!-- Personal Information -->
            <div>
                <h4 style="color:#60a5fa; margin-bottom:1rem;">Personal Information</h4>
                <div style="background:#0f172a; padding:1rem; border-radius:6px;">
                    <p style="margin:0.5rem 0;"><strong>Name:</strong> <?= h($selectedApplicant['first_name'] . ' ' . $selectedApplicant['last_name']) ?></p>
                    <p style="margin:0.5rem 0;"><strong>Email:</strong> <?= h($selectedApplicant['email']) ?></p>
                    <p style="margin:0.5rem 0;"><strong>Phone:</strong> <?= h($selectedApplicant['phone'] ?? 'N/A') ?></p>
                    <p style="margin:0.5rem 0;"><strong>Applied Date:</strong> <?= date('M d, Y', strtotime($selectedApplicant['created_at'])) ?></p>
                    <p style="margin:0.5rem 0;"><strong>Status:</strong> <span class="badge badge-<?= h($selectedApplicant['status']) ?>"><?= ucfirst(h($selectedApplicant['status'])) ?></span></p>
                </div>
            </div>
            
            <!-- Job Information -->
            <div>
                <h4 style="color:#60a5fa; margin-bottom:1rem;">Job Information</h4>
                <div style="background:#0f172a; padding:1rem; border-radius:6px;">
                    <p style="margin:0.5rem 0;"><strong>Position:</strong> <?= h($selectedApplicant['job_title']) ?></p>
                    <p style="margin:0.5rem 0;"><strong>Location:</strong> <?= h($selectedApplicant['location'] ?? 'N/A') ?></p>
                    <p style="margin:0.5rem 0;"><strong>Type:</strong> <?= h($selectedApplicant['employment_type'] ?? 'N/A') ?></p>
                    <?php if ($selectedApplicant['salary_min'] || $selectedApplicant['salary_max']): ?>
                    <p style="margin:0.5rem 0;"><strong>Salary Range:</strong> 
                        <?php 
                        if ($selectedApplicant['salary_min'] && $selectedApplicant['salary_max']) {
                            echo '$' . number_format($selectedApplicant['salary_min']) . ' - $' . number_format($selectedApplicant['salary_max']);
                        } elseif ($selectedApplicant['salary_min']) {
                            echo 'From $' . number_format($selectedApplicant['salary_min']);
                        } else {
                            echo 'Up to $' . number_format($selectedApplicant['salary_max']);
                        }
                        ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Documents Section -->
        <div style="margin-top:2rem;">
            <h4 style="color:#60a5fa; margin-bottom:1rem;">Documents</h4>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div style="background:#0f172a; padding:1rem; border-radius:6px;">
                    <p style="margin:0 0 0.5rem 0;"><strong><span class="material-symbols-outlined" style="font-size: 1rem; vertical-align: middle;">description</span> Resume:</strong></p>
                    <?php if (!empty($selectedApplicant['resume_path'])): ?>
                        <button class="btn btn-sm btn-primary" onclick="toggleResumeViewer()">View Resume</button>
                        <a href="view_file.php?file=<?= urlencode($selectedApplicant['resume_path']) ?>&download=1" target="_blank" class="btn btn-sm btn-primary" style="margin-left:0.5rem;">Download</a>
                    <?php else: ?>
                        <span style="color:#64748b;">No resume uploaded</span>
                    <?php endif; ?>
                </div>
                <div style="background:#0f172a; padding:1rem; border-radius:6px;">
                    <p style="margin:0 0 0.5rem 0;"><strong><span class="material-symbols-outlined" style="font-size: 1rem; vertical-align: middle;">description</span> Cover Letter:</strong></p>
                    <?php if (!empty($selectedApplicant['cover_letter'])): ?>
                        <button class="btn btn-sm btn-primary" onclick="viewCoverLetterInline('<?= h(addslashes($selectedApplicant['first_name'] . ' ' . $selectedApplicant['last_name'])) ?>')">View Cover Letter</button>
                    <?php else: ?>
                        <span style="color:#64748b;">No cover letter</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Resume Viewer (Hidden by default) -->
        <?php if (!empty($selectedApplicant['resume_path'])): ?>
        <div id="resumeViewer" style="display:none; margin-top:1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <h4 style="color:#60a5fa; margin:0;">Resume Preview</h4>
                <button class="btn btn-sm" onclick="toggleResumeViewer()">✕ Close</button>
            </div>
            <div style="background:#0f172a; border-radius:6px; overflow:hidden; height:600px;">
                <iframe 
                    src="view_file.php?file=<?= urlencode($selectedApplicant['resume_path']) ?>" 
                    style="width:100%; height:100%; border:none;"
                    title="Resume Preview">
                </iframe>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Cover Letter Content (Hidden by default) -->
        <?php if (!empty($selectedApplicant['cover_letter'])): ?>
        <div id="inlineCoverLetter" style="display:none; margin-top:1rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <h4 style="color:#60a5fa; margin:0;">Cover Letter</h4>
                <button class="btn btn-sm" onclick="viewCoverLetterInline()">✕ Close</button>
            </div>
            <div style="background:#0f172a; padding:1.5rem; border-radius:6px; white-space:pre-wrap; line-height:1.6; color:#e2e8f0;">
                <?= h($selectedApplicant['cover_letter']) ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Notes Section -->
        <?php if (!empty($selectedApplicant['notes'])): ?>
        <div style="margin-top:2rem;">
            <h4 style="color:#60a5fa; margin-bottom:1rem;">Notes</h4>
            <div style="background:#0f172a; padding:1rem; border-radius:6px; color:#e2e8f0;">
                <?= nl2br(h($selectedApplicant['notes'])) ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Action Buttons -->
        <div style="display:flex; gap:1rem; margin-top:2rem; justify-content:flex-end;">
            <button class="btn btn-primary" onclick="progressApplicantFromModal(<?= $selectedApplicant['id'] ?>, '<?= $selectedApplicant['status'] ?>')">Proceed to Next Stage</button>
            <button class="btn btn-danger" onclick="rejectApplicantStatus(<?= $selectedApplicant['id'] ?>)">Reject</button>
            <button class="btn" onclick="closeApplicantModal()">Close</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Cover Letter Modal -->
<div id="coverLetterModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:1000;">
    <div class="modal-content" style="background:var(--card-bg, #1e293b); padding:2rem; border-radius:8px; width:90%; max-width:700px; max-height:80vh; overflow-y:auto;">
        <h3 style="margin-bottom:1rem;" id="coverLetterTitle">Cover Letter</h3>
        <div id="coverLetterContent" style="background:#0f172a; padding:1.5rem; border-radius:6px; white-space:pre-wrap; line-height:1.6; color:#e2e8f0;">
        </div>
        <div style="display:flex; justify-content:flex-end; margin-top:1.5rem;">
            <button type="button" class="btn" onclick="closeCoverLetterModal()">Close</button>
        </div>
    </div>
</div>

<script>
// Cover letter data embedded from PHP
const coverLetters = {
    <?php foreach ($applicantsWithDocuments as $applicant): ?>
        <?php if (!empty($applicant['cover_letter'])): ?>
            <?php echo $applicant['id']; ?>: <?php echo json_encode($applicant['cover_letter']); ?>,
        <?php endif; ?>
    <?php endforeach; ?>
};

function viewCoverLetter(applicationId, applicantName) {
    const modal = document.getElementById('coverLetterModal');
    const title = document.getElementById('coverLetterTitle');
    const content = document.getElementById('coverLetterContent');
    
    title.textContent = 'Cover Letter - ' + applicantName;
    content.textContent = coverLetters[applicationId] || 'No cover letter content available.';
    
    modal.style.display = 'flex';
}

function closeCoverLetterModal() {
    document.getElementById('coverLetterModal').style.display = 'none';
}

function filterDocuments(type) {
    const table = document.querySelector('#documents table tbody');
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        if (row.cells.length === 1) return; // Skip "no documents" row
        
        const resumeCell = row.cells[4];
        const coverLetterCell = row.cells[5];
        
        let showRow = false;
        
        if (type === 'resume') {
            showRow = resumeCell.querySelector('a') !== null;
        } else if (type === 'cover_letter') {
            showRow = coverLetterCell.querySelector('button') !== null;
        } else {
            showRow = true; // Show all for other types
        }
        
        row.style.display = showRow ? '' : 'none';
    });
}

// View applicant details
function viewApplicantDetails(applicationId) {
    // Redirect to a detailed view page or open modal
    window.location.href = 'applicant-management.php?view=' + applicationId;
}

// Close applicant details modal
function closeApplicantModal() {
    window.location.href = 'applicant-management.php';
}

// Toggle resume viewer
function toggleResumeViewer() {
    const resumeViewer = document.getElementById('resumeViewer');
    if (resumeViewer) {
        if (resumeViewer.style.display === 'none') {
            resumeViewer.style.display = 'block';
        } else {
            resumeViewer.style.display = 'none';
        }
    }
}

// View cover letter inline in the modal
function viewCoverLetterInline(applicantName) {
    const coverLetterDiv = document.getElementById('inlineCoverLetter');
    if (coverLetterDiv) {
        if (coverLetterDiv.style.display === 'none') {
            coverLetterDiv.style.display = 'block';
        } else {
            coverLetterDiv.style.display = 'none';
        }
    }
}

// Progress applicant from modal
function progressApplicantFromModal(applicationId, currentStatus) {
    progressApplicant(applicationId, currentStatus);
}

// Applicant workflow progression
function progressApplicant(applicationId, currentStatus) {
    // Define the workflow progression
    const workflow = {
        'new': { next: 'screening', message: 'Move applicant to Screening stage?' },
        'screening': { next: 'interview', message: 'Move applicant to Interview stage?' },
        'interview': { next: 'offer', message: 'Move applicant to Offer stage?' },
        'offer': { next: 'hired', message: 'Mark applicant as Hired?' }
    };
    
    const progression = workflow[currentStatus];
    
    if (!progression) {
        alert('Invalid status progression');
        return;
    }
    
    if (confirm(progression.message)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'applicant-management.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'update_status';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'application_id';
        idInput.value = applicationId;
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'new_status';
        statusInput.value = progression.next;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        form.appendChild(statusInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function rejectApplicantStatus(applicationId) {
    if (confirm('Reject this applicant? This action will mark their application as rejected.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'applicant-management.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'update_status';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'application_id';
        idInput.value = applicationId;
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'new_status';
        statusInput.value = 'rejected';
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        form.appendChild(statusInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('coverLetterModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeCoverLetterModal();
            }
        });
    }
});
</script>

<?php include '../partials/footer.php'; ?>
