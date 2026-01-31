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

$employmentTypes = [];
try {
    $stmt = $pdo->prepare(
        "SELECT COLUMN_TYPE
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'job_postings' AND COLUMN_NAME = 'employment_type'"
    );
    $stmt->execute([DB_NAME]);
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!empty($col['COLUMN_TYPE']) && preg_match("/^enum\((.*)\)$/i", $col['COLUMN_TYPE'], $m)) {
        $values = str_getcsv($m[1], ',', "'");
        $employmentTypes = array_values(array_filter(array_map('trim', $values), fn($v) => $v !== ''));
    }
} catch (Exception $e) {
    $employmentTypes = [];
}
if (empty($employmentTypes)) {
    $employmentTypes = ['Full-time', 'Part-time', 'Contract', 'Internship', 'Remote'];
}

// Handle job requisition deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    
    try {
        $sql = "DELETE FROM job_postings WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $_SESSION['success'] = "Requisition deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header('Location: recruitment.php');
    exit();
}

// Handle job requisition update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = (int)$_POST['id'];
    $title = trim($_POST['title']);
    $dept_id = (int)$_POST['department_id'];
    $location = trim($_POST['location']);
    $emp_type = $_POST['employment_type'] ?? '';
    if (!in_array($emp_type, $employmentTypes, true)) {
        $emp_type = $employmentTypes[0];
    }
    $salary_min = !empty($_POST['salary_min']) ? (float)$_POST['salary_min'] : NULL;
    $salary_max = !empty($_POST['salary_max']) ? (float)$_POST['salary_max'] : NULL;
    $closing_date = !empty($_POST['closing_date']) ? $_POST['closing_date'] : NULL;
    $description = trim($_POST['description']);
    $requirements = trim($_POST['requirements']);
    $status = $_POST['status'] ?? 'draft';

    if ($title === '' || $dept_id <= 0 || $location === '' || $description === '' || $requirements === '') {
        $_SESSION['error'] = 'Please fill in all required fields.';
        header('Location: recruitment.php');
        exit();
    }

    try {
        $sql = "UPDATE job_postings SET title=?, department_id=?, location=?, employment_type=?, salary_min=?, salary_max=?, closing_date=?, description=?, requirements=?, status=?, updated_at=NOW() WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $dept_id, $location, $emp_type, $salary_min, $salary_max, $closing_date, $description, $requirements, $status, $id]);
        $_SESSION['success'] = "Requisition updated successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header('Location: recruitment.php');
    exit();
}

// Handle screening question creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_screening_question') {
    $job_posting_id = (int)$_POST['job_posting_id'];
    $question_text = trim($_POST['question_text']);
    $question_type = $_POST['question_type'] ?? 'text';
    $required = (int)($_POST['required'] ?? 1);
    
    if ($job_posting_id && $question_text) {
        try {
            $sql = "INSERT INTO screening_questions (job_posting_id, question_text, question_type, required, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$job_posting_id, $question_text, $question_type, $required]);
            $_SESSION['success'] = "Screening question added successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error adding screening question: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Please fill in all required fields.";
    }
    
    header('Location: recruitment.php?screening_job=' . $job_posting_id . '#screening');
    exit();
}

// Handle screening question update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_screening_question') {
    $question_id = (int)$_POST['question_id'];
    $job_posting_id = (int)$_POST['job_posting_id'];
    $question_text = trim($_POST['question_text']);
    $question_type = $_POST['question_type'] ?? 'text';
    $required = (int)($_POST['required'] ?? 1);
    
    if ($question_id && $question_text) {
        try {
            $sql = "UPDATE screening_questions SET question_text = ?, question_type = ?, required = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$question_text, $question_type, $required, $question_id]);
            $_SESSION['success'] = "Screening question updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating screening question: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Please fill in all required fields.";
    }
    
    header('Location: recruitment.php?screening_job=' . $job_posting_id . '#screening');
    exit();
}

// Handle screening question deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_screening_question') {
    $question_id = (int)$_POST['question_id'];
    $job_posting_id = (int)$_POST['job_posting_id'];
    
    if ($question_id) {
        try {
            $sql = "DELETE FROM screening_questions WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$question_id]);
            $_SESSION['success'] = "Screening question deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting screening question: " . $e->getMessage();
        }
    }
    
    header('Location: recruitment.php?screening_job=' . $job_posting_id . '#screening');
    exit();
}

// Handle move to offer action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'move_to_offer') {
    $application_id = (int)$_POST['application_id'];
    
    if ($application_id > 0) {
        try {
            $sql = "UPDATE job_applications SET status = 'offer', updated_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$application_id]);
            $_SESSION['success'] = "Candidate moved to Offer stage successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating application: " . $e->getMessage();
        }
    }
    
    header('Location: recruitment.php#interview');
    exit();
}

// Handle move to interview action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'move_to_interview') {
    $application_id = (int)$_POST['application_id'];
    
    if ($application_id > 0) {
        try {
            $sql = "UPDATE job_applications SET status = 'interview', updated_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$application_id]);
            $_SESSION['success'] = "Candidate moved to Interview stage successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating application: " . $e->getMessage();
        }
    }
    
    header('Location: recruitment.php#screening');
    exit();
}

// Handle reject applicant action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject_applicant') {
    $application_id = (int)$_POST['application_id'];
    
    if ($application_id > 0) {
        try {
            $sql = "UPDATE job_applications SET status = 'rejected', updated_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$application_id]);
            $_SESSION['success'] = "Applicant rejected successfully.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating application: " . $e->getMessage();
        }
    }
    
    header('Location: recruitment.php#screening');
    exit();
}

// Handle move to screening action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'move_to_screening') {
    $application_id = (int)$_POST['application_id'];
    
    if ($application_id > 0) {
        try {
            $sql = "UPDATE job_applications SET status = 'screening', updated_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$application_id]);
            $_SESSION['success'] = "Applicant moved to Screening stage successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating application: " . $e->getMessage();
        }
    }
    
    header('Location: recruitment.php#job-requisition');
    exit();
}

// Handle schedule interview action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'schedule_interview') {
    $application_id = (int)$_POST['application_id'];
    $interview_date = $_POST['interview_date'] ?? '';
    $interview_time = $_POST['interview_time'] ?? '';
    $interview_type = $_POST['interview_type'] ?? '';
    $interview_location = trim($_POST['interview_location'] ?? '');
    $interviewers = trim($_POST['interviewers'] ?? '');
    $interview_notes = trim($_POST['interview_notes'] ?? '');
    
    if ($application_id > 0 && $interview_date && $interview_time && $interview_location) {
        try {
            // Combine date and time
            $interview_datetime = $interview_date . ' ' . $interview_time;
            
            // Update application status to interview and store interview details
            $sql = "UPDATE job_applications 
                    SET status = 'interview', 
                        interview_date = ?,
                        interview_type = ?,
                        interview_location = ?,
                        interviewers = ?,
                        interview_notes = ?,
                        updated_at = NOW() 
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$interview_datetime, $interview_type, $interview_location, $interviewers, $interview_notes, $application_id]);
            
            $_SESSION['success'] = "Interview scheduled successfully! The applicant will be notified.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error scheduling interview: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Please fill in all required fields.";
    }
    
    header('Location: recruitment.php#job-requisition');
    exit();
}

// Handle job requisition creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    // Sanitize inputs
    $title = trim($_POST['title']);
    $dept_id = (int)$_POST['department_id'];
    $location = trim($_POST['location']);
    $emp_type = $_POST['employment_type'] ?? '';
    if (!in_array($emp_type, $employmentTypes, true)) {
        $emp_type = $employmentTypes[0];
    }

    $salary_min = !empty($_POST['salary_min']) ? (float)$_POST['salary_min'] : NULL;
    $salary_max = !empty($_POST['salary_max']) ? (float)$_POST['salary_max'] : NULL;
    $closing_date = !empty($_POST['closing_date']) ? $_POST['closing_date'] : NULL;
    $description = trim($_POST['description']);
    $requirements = trim($_POST['requirements']);
    $status = $_POST['status'] ?? 'draft'; // Get status from form, default to draft
    $posted_by = $_SESSION['user_id']; // Logged in user ID

    if ($title === '' || $dept_id <= 0 || $location === '' || $description === '' || $requirements === '') {
        $_SESSION['error'] = 'Please fill in all required fields.';
        header('Location: recruitment.php');
        exit();
    }

    if (strtolower($title) === 'null') {
        $_SESSION['error'] = "Job title cannot be 'null'.";
        header('Location: recruitment.php');
        exit();
    }

    $dept_exists = fetchSingle("SELECT id FROM departments WHERE id = ?", [$dept_id]);
    if (!$dept_exists) {
        $_SESSION['error'] = 'Selected department is invalid. Please choose a valid department.';
        header('Location: recruitment.php');
        exit();
    }

    $user_exists = fetchSingle("SELECT id FROM users WHERE id = ?", [$posted_by]);
    if (!$user_exists) {
        $_SESSION['error'] = 'Your account is not found in the database. Please log in again using a database user.';
        header('Location: recruitment.php');
        exit();
    }

    try {
        // SQL matching your table: job_postings
        $sql = "INSERT INTO job_postings 
                (title, department_id, location, employment_type, salary_min, salary_max, closing_date, description, requirements, status, posted_by, posted_date, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $dept_id, $location, $emp_type, $salary_min, $salary_max, $closing_date, $description, $requirements, $status, $posted_by]);

        $new_id = $pdo->lastInsertId();
        $_SESSION['success'] = "Requisition created successfully. ID: " . $new_id;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    header('Location: recruitment.php');
    exit();
}

// Small helper for safe output
function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// 1. Load Active/Draft job requisitions with full data for editing
try {
    $jobRequisitions = fetchAll(
        "SELECT jp.id, jp.title, jp.department_id, d.name AS department, jp.location, jp.employment_type, 
                jp.salary_min, jp.salary_max, jp.closing_date, jp.description, jp.requirements, jp.status,
                (SELECT COUNT(*) FROM job_applications ja WHERE ja.job_posting_id = jp.id) AS applications
         FROM job_postings jp
         JOIN departments d ON d.id = jp.department_id
         WHERE jp.status IN ('active','draft')
           AND jp.title IS NOT NULL AND jp.title <> '' AND TRIM(LOWER(jp.title)) <> 'null'
         ORDER BY jp.created_at DESC"
    );
    
    // Fetch departments for the form dropdown
    $departments = fetchAll("SELECT id, name FROM departments ORDER BY name ASC");
} catch (Exception $e) {
    $jobRequisitions = [];
    $departments = [];
}

// 2. Load recent applications
$recentApplications = [];
try {
    $recentApplications = fetchAll(
        "SELECT ja.id, ja.first_name, ja.last_name, ja.email, ja.status, ja.created_at, jp.title AS job_title
         FROM job_applications ja
         JOIN job_postings jp ON jp.id = ja.job_posting_id
         ORDER BY ja.created_at DESC LIMIT 20"
    );
} catch (Exception $e) {
    error_log("Error fetching recent applications: " . $e->getMessage());
    $_SESSION['error'] = "Error loading applications: " . $e->getMessage();
    $recentApplications = [];
}

// 3. Load screening questions if job is selected
$screeningQuestions = [];
$selectedJobId = isset($_GET['screening_job']) ? (int)$_GET['screening_job'] : 0;
if ($selectedJobId > 0) {
    try {
        $screeningQuestions = fetchAll(
            "SELECT * FROM screening_questions 
             WHERE job_posting_id = ? 
             ORDER BY order_number ASC, created_at ASC",
            [$selectedJobId]
        );
    } catch (Exception $e) {
        $screeningQuestions = [];
    }
}

// 4. Load applicants in screening stage
$screeningApplicants = [];
try {
    $screeningApplicants = fetchAll(
        "SELECT ja.id, ja.first_name, ja.last_name, ja.email, ja.status, ja.created_at,
                ja.updated_at, jp.title AS job_title, jp.id AS job_posting_id,
                jp.closing_date
         FROM job_applications ja
         JOIN job_postings jp ON jp.id = ja.job_posting_id
         WHERE ja.status = 'screening'
         ORDER BY ja.created_at DESC"
    );
} catch (Exception $e) {
    $screeningApplicants = [];
}

// 5. Load applicants in interview stage
$interviewApplicants = [];
try {
    $interviewApplicants = fetchAll(
        "SELECT ja.id, ja.first_name, ja.last_name, ja.email, ja.status, ja.created_at,
                jp.title AS job_title, jp.id AS job_posting_id
         FROM job_applications ja
         JOIN job_postings jp ON jp.id = ja.job_posting_id
         WHERE ja.status = 'interview'
         ORDER BY ja.created_at DESC"
    );
} catch (Exception $e) {
    $interviewApplicants = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRMS - Recruitment</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        /* Essential CSS for tab toggling */
        .submodule { display: none; }
        .submodule.active { display: block; }
    </style>
</head>
<body>
    <?php 
    $active_page = 'recruitment';
    $page_title = 'Recruitment Management';
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

    <div id="recruitment-module" class="module active">
        <div class="module-header">
            <h2>Recruitment Management</h2>
            <button class="btn btn-primary" onclick="showRequisitionForm()">New Job Requisition</button>
        </div>

        <div class="submodule-nav">
            <button class="submodule-btn active" onclick="switchTab(event, 'job-requisition')">Job Requisition</button>
            <button class="submodule-btn" onclick="switchTab(event, 'sourcing')">Sourcing</button>
            <button class="submodule-btn" onclick="switchTab(event, 'screening')">Screening</button>
            <button class="submodule-btn" onclick="switchTab(event, 'interview')">Interview Process</button>
        </div>

        <div id="job-requisition" class="submodule active">
            <div class="form-container">
                <h3>Create Job Requisition</h3>
                <form class="requisition-form" action="recruitment.php" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Job Title</label>
                            <input type="text" name="title" required placeholder="e.g., Senior Software Engineer">
                        </div>
                        <div class="form-group">
                            <label>Department</label>
                            <select name="department_id" required>
                                <option value="">Select Department</option>
                                <?php foreach($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"><?= h($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" name="location" required placeholder="e.g., Manila, Philippines">
                        </div>
                        <div class="form-group">
                            <label>Employment Type</label>
                            <select name="employment_type" required>
                                <option value="">Select Type</option>
                                <?php foreach ($employmentTypes as $type): ?>
                                    <option value="<?= h($type) ?>"><?= h($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Minimum Salary</label>
                            <input type="number" name="salary_min" step="0.01" placeholder="e.g., 25000.00">
                        </div>
                        <div class="form-group">
                            <label>Maximum Salary</label>
                            <input type="number" name="salary_max" step="0.01" placeholder="e.g., 35000.00">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Closing Date</label>
                            <input type="date" name="closing_date">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" required>
                                <option value="draft">Draft (Not visible to applicants)</option>
                                <option value="active">Active (Visible to applicants)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Job Description</label>
                        <textarea name="description" rows="4" required placeholder="Describe the role, responsibilities, and what the candidate will be doing..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Requirements</label>
                        <textarea name="requirements" rows="4" required placeholder="List the qualifications, skills, and experience required..."></textarea>
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
                                    <td><?= h($req['title']); ?></td>
                                    <td><?= h($req['department']); ?></td>
                                    <td>
                                        <?php 
                                            $status = strtolower($req['status']);
                                            $badge = ($status === 'active') ? 'active' : 'new';
                                        ?>
                                        <span class="status-badge <?= $badge ?>"><?= ucfirst($status) ?></span>
                                    </td>
                                    <td><?= (int)$req['applications']; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="window.location.href='job-posting.php?id=<?= $req['id'] ?>'">View</button>
                                        <button class="btn btn-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode($req), ENT_QUOTES, 'UTF-8') ?>)">Edit</button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteRequisition(<?= $req['id'] ?>)">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="5" style="text-align:center;">No requisitions found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- New Applications Section -->
            <div class="table-container" style="margin-top: 2rem;">
                <h3>Recent Applications</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Applicant Name</th>
                            <th>Email</th>
                            <th>Position Applied</th>
                            <th>Status</th>
                            <th>Applied Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentApplications)): ?>
                            <?php foreach ($recentApplications as $app): ?>
                                <tr>
                                    <td><?= h($app['first_name'] . ' ' . $app['last_name']); ?></td>
                                    <td><?= h($app['email'] ?? 'N/A'); ?></td>
                                    <td><?= h($app['job_title']); ?></td>
                                    <td>
                                        <span class="status-badge <?= h($app['status']); ?>">
                                            <?= ucfirst(h($app['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($app['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="viewApplication(<?= $app['id']; ?>)">View</button>
                                        <?php if ($app['status'] === 'new'): ?>
                                            <button class="btn btn-sm" onclick="scheduleInterview(<?= $app['id']; ?>, '<?= h($app['first_name'] . ' ' . $app['last_name']); ?>')">Schedule Interview</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #64748b; padding: 2rem;">
                                    No applications yet. Applications will appear here once candidates apply for your job postings.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="sourcing" class="submodule">
            <div class="sourcing-tools">
                <h3>Candidate Sourcing</h3>
                
                <!-- Select Job to Share -->
                <div class="form-group" style="max-width: 500px; margin-bottom: 2rem;">
                    <label>Select Job Posting to Share on Social Media</label>
                    <select id="share_job_select" class="form-control" onchange="updateShareLinks(this.value)">
                        <option value="">-- Select a Job Posting --</option>
                        <?php foreach ($jobRequisitions as $job): ?>
                            <?php if ($job['status'] === 'active'): ?>
                                <option value="<?= $job['id'] ?>" data-title="<?= h($job['title']) ?>"><?= h($job['title']) ?> - <?= h($job['department']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" required placeholder="e.g., Manila, Philippines">
                    </div>
                    <div class="form-group">
                        <label>Employment Type</label>
                        <select name="employment_type" required>
                            <option value="">Select Type</option>
                            <?php foreach ($employmentTypes as $type): ?>
                                <option value="<?= h($type) ?>"><?= h($type) ?></option>
                            <?php endforeach; ?>
                        </select>
        <div id="screening" class="submodule">
            <div class="screening-container">
                <h3>Screening Questions Management</h3>
                
                <!-- Select Job Posting -->
                <div class="form-group" style="max-width: 500px; margin-bottom: 2rem;">
                    <label>Select Job Posting to Add Screening Questions</label>
                    <select id="screening_job_select" class="form-control" onchange="loadScreeningQuestions(this.value)">
                        <option value="">-- Select a Job Posting --</option>
                        <?php foreach ($jobRequisitions as $job): ?>
                            <option value="<?= $job['id'] ?>"><?= h($job['title']) ?> - <?= h($job['department']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Add Question Form -->
                <div id="screening_form_container" style="display: none;">
                    <div class="form-container" style="margin-bottom: 2rem;">
                        <h4>Add Screening Question</h4>
                        <form id="add_screening_form" method="POST" action="recruitment.php">
                            <input type="hidden" name="action" value="add_screening_question">
                            <input type="hidden" name="job_posting_id" id="screening_job_id">
                            
                            <div class="form-group">
                                <label>Question Text</label>
                                <textarea name="question_text" rows="3" required placeholder="Enter your screening question..."></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Question Type</label>
                                    <select name="question_type" required>
                                        <option value="text">Text Answer</option>
                                        <option value="yes_no">Yes/No</option>
                                        <option value="multiple_choice">Multiple Choice</option>
                                        <option value="rating">Rating (1-5)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Required</label>
                                    <select name="required">
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Add Question</button>
                        </form>
                    </div>

                    <!-- Questions List -->
                    <div class="table-container">
                        <h4>Screening Questions</h4>
                        <div id="screening_questions_list">
                            <?php if ($selectedJobId > 0): ?>
                                <?php if (!empty($screeningQuestions)): ?>
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Question</th>
                                                <th>Type</th>
                                                <th>Required</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($screeningQuestions as $index => $question): ?>
                                                <tr>
                                                    <td><?= $index + 1 ?></td>
                                                    <td><?= h($question['question_text']) ?></td>
                                                    <td><span class="badge"><?= ucfirst(str_replace('_', ' ', $question['question_type'])) ?></span></td>
                                                    <td><?= $question['required'] ? 'Yes' : 'No' ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary" onclick="viewQuestion(<?= htmlspecialchars(json_encode($question), ENT_QUOTES, 'UTF-8') ?>)">View</button>
                                                        <button class="btn btn-sm" onclick="editQuestion(<?= htmlspecialchars(json_encode($question), ENT_QUOTES, 'UTF-8') ?>)">Edit</button>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteQuestion(<?= $question['id'] ?>, <?= $selectedJobId ?>)">Delete</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p style="text-align: center; color: #94a3b8;">No screening questions added yet. Use the form above to add questions.</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p style="text-align: center; color: #94a3b8;">Select a job posting to view screening questions</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Screening Applicants List -->
                <div class="table-container" style="margin-top: 3rem;">
                    <h3>Applicants in Screening Stage</h3>
                    <?php if (!empty($screeningApplicants)): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Candidate Name</th>
                                    <th>Email</th>
                                    <th>Position Applied</th>
                                    <th>Applied Date</th>
                                    <th>Last Updated</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($screeningApplicants as $applicant): ?>
                                    <tr>
                                        <td><?= h($applicant['first_name'] . ' ' . $applicant['last_name']) ?></td>
                                        <td><?= h($applicant['email']) ?></td>
                                        <td><?= h($applicant['job_title']) ?></td>
                                        <td><?= date('M d, Y', strtotime($applicant['created_at'])) ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($applicant['updated_at'] ?? $applicant['created_at'])) ?></td>
                                        <td>
                                            <?php if ($applicant['closing_date']): ?>
                                                <?= date('M d, Y', strtotime($applicant['closing_date'])) ?>
                                            <?php else: ?>
                                                <span style="color: #94a3b8;">No deadline</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="status-badge new">Screening</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-success" onclick="moveToInterview(<?= $applicant['id'] ?>)">Move to Interview</button>
                                            <button class="btn btn-sm btn-primary" onclick="moveToOffer(<?= $applicant['id'] ?>)">Move to Offer</button>
                                            <button class="btn btn-sm btn-danger" onclick="rejectApplicant(<?= $applicant['id'] ?>)">Reject</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; color: #94a3b8; padding: 2rem;">No applicants in screening stage. Applicants will appear here when they complete their application and move to screening.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div id="interview" class="submodule">
            <div class="interview-container">
                <h3>Interview Process - Candidates</h3>
                <?php if (!empty($interviewApplicants)): ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Candidate Name</th>
                                    <th>Email</th>
                                    <th>Position Applied</th>
                                    <th>Applied Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($interviewApplicants as $applicant): ?>
                                    <tr>
                                        <td><?= h($applicant['first_name'] . ' ' . $applicant['last_name']) ?></td>
                                        <td><?= h($applicant['email']) ?></td>
                                        <td><?= h($applicant['job_title']) ?></td>
                                        <td><?= date('M d, Y', strtotime($applicant['created_at'])) ?></td>
                                        <td><span class="status-badge active">Interview</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="scheduleInterview(<?= $applicant['id'] ?>)">Schedule</button>
                                            <button class="btn btn-sm btn-success" onclick="moveToOffer(<?= $applicant['id'] ?>)">Move to Offer</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="interview-list">
                        <p style="text-align: center; color: #94a3b8; padding: 2rem;">No candidates in interview stage yet. Move applicants to interview stage from Applicant Management.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    function switchTab(evt, tabName) {
        const submodules = document.getElementsByClassName("submodule");
        for (let i = 0; i < submodules.length; i++) {
            submodules[i].classList.remove("active");
        }
        const buttons = document.getElementsByClassName("submodule-btn");
        for (let i = 0; i < buttons.length; i++) {
            buttons[i].classList.remove("active");
        }
        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.classList.add("active");
    }

    function showRequisitionForm() {
        const submodules = document.getElementsByClassName("submodule");
        for (let i = 0; i < submodules.length; i++) {
            submodules[i].classList.remove("active");
        }
        const buttons = document.getElementsByClassName("submodule-btn");
        for (let i = 0; i < buttons.length; i++) {
            buttons[i].classList.remove("active");
        }
        document.getElementById('job-requisition').classList.add("active");
        if (buttons.length > 0) buttons[0].classList.add("active");
        document.querySelector('.form-container').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function openEditModal(job) {
        document.getElementById('edit_id').value = job.id;
        document.getElementById('edit_title').value = job.title;
        document.getElementById('edit_department_id').value = job.department_id;
        document.getElementById('edit_location').value = job.location;
        document.getElementById('edit_employment_type').value = job.employment_type;
        document.getElementById('edit_salary_min').value = job.salary_min || '';
        document.getElementById('edit_salary_max').value = job.salary_max || '';
        document.getElementById('edit_closing_date').value = job.closing_date || '';
        document.getElementById('edit_description').value = job.description;
        document.getElementById('edit_requirements').value = job.requirements;
        document.getElementById('edit_status').value = job.status;
        document.getElementById('editModal').style.display = 'flex';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    function deleteRequisition(id) {
        if (confirm('Are you sure you want to delete this job requisition? This action cannot be undone.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'recruitment.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = id;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    function loadScreeningQuestions(jobId) {
        if (jobId) {
            // Reload page with the selected job ID to show questions
            window.location.href = 'recruitment.php?screening_job=' + jobId + '#screening';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });
        
        // Auto-select job and show form if coming from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const screeningJob = urlParams.get('screening_job');
        if (screeningJob) {
            // Switch to screening tab
            const submodules = document.getElementsByClassName("submodule");
            for (let i = 0; i < submodules.length; i++) {
                submodules[i].classList.remove("active");
            }
            const buttons = document.getElementsByClassName("submodule-btn");
            for (let i = 0; i < buttons.length; i++) {
                buttons[i].classList.remove("active");
            }
            document.getElementById('screening').classList.add("active");
            buttons[2].classList.add("active"); // Screening is the 3rd button (index 2)
            
            // Set the dropdown value and show form container
            document.getElementById('screening_job_select').value = screeningJob;
            document.getElementById('screening_form_container').style.display = 'block';
            document.getElementById('screening_job_id').value = screeningJob;
        }
    });

    function scheduleInterview(applicationId, applicantName) {
        // Open interview scheduling modal
        document.getElementById('interviewModal').style.display = 'flex';
        document.getElementById('interview_application_id').value = applicationId;
        document.getElementById('interview_applicant_name').textContent = applicantName;
    }
    
    function closeInterviewModal() {
        document.getElementById('interviewModal').style.display = 'none';
        document.getElementById('interviewScheduleForm').reset();
    }
    
    function submitInterviewSchedule() {
        const form = document.getElementById('interviewScheduleForm');
        if (form.checkValidity()) {
            form.submit();
        } else {
            alert('Please fill in all required fields.');
        }
    }

    function viewApplication(applicationId) {
        // Redirect to applicant management page with the application ID
        window.location.href = 'applicant-management.php?view=' + applicationId;
    }

    function moveToOffer(applicationId) {
        if (confirm('Move this candidate to the Offer stage?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'recruitment.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'move_to_offer';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'application_id';
            idInput.value = applicationId;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    function moveToInterview(applicationId) {
        if (confirm('Move this candidate to the Interview stage?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'recruitment.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'move_to_interview';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'application_id';
            idInput.value = applicationId;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    function rejectApplicant(applicationId) {
        if (confirm('Reject this applicant? This will mark their application as rejected.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'recruitment.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'reject_applicant';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'application_id';
            idInput.value = applicationId;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    function moveToScreening(applicationId) {
        if (confirm('Move this applicant to the Screening stage?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'recruitment.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'move_to_screening';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'application_id';
            idInput.value = applicationId;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Social Media Sharing Functions
    function updateShareLinks(jobId) {
        const container = document.getElementById('share_links_container');
        
        if (!jobId) {
            container.style.display = 'none';
            return;
        }
        
        container.style.display = 'block';
        
        // Get the job title from the selected option
        const select = document.getElementById('share_job_select');
        const jobTitle = select.options[select.selectedIndex].getAttribute('data-title') || 'Job Opening';
        
        // Build the job URL (public careers page)
        const baseUrl = window.location.origin + '/HR1/careers.php';
        const jobUrl = baseUrl + '?job=' + jobId;
        
        // Update direct link input
        document.getElementById('job_direct_link').value = jobUrl;
        
        // Encode for sharing
        const encodedUrl = encodeURIComponent(jobUrl);
        const encodedTitle = encodeURIComponent('We are hiring! ' + jobTitle + ' - Apply now!');
        const encodedText = encodeURIComponent('Exciting opportunity! We are looking for a ' + jobTitle + '. Check out the job posting and apply today!');
        
        // Update LinkedIn share link
        document.getElementById('linkedin_share').href = 
            'https://www.linkedin.com/sharing/share-offsite/?url=' + encodedUrl;
        
        // Update Facebook share link
        document.getElementById('facebook_share').href = 
            'https://www.facebook.com/sharer/sharer.php?u=' + encodedUrl + '&quote=' + encodedTitle;
        
        // Update Twitter share link
        document.getElementById('twitter_share').href = 
            'https://twitter.com/intent/tweet?url=' + encodedUrl + '&text=' + encodedText;
    }

    function copyJobLink() {
        const linkInput = document.getElementById('job_direct_link');
        linkInput.select();
        linkInput.setSelectionRange(0, 99999); // For mobile
        
        navigator.clipboard.writeText(linkInput.value).then(function() {
            alert('Job link copied to clipboard!');
        }).catch(function() {
            // Fallback for older browsers
            document.execCommand('copy');
            alert('Job link copied to clipboard!');
        });
    }

    // Screening Question Functions
    function viewQuestion(question) {
        const modal = document.getElementById('viewQuestionModal');
        document.getElementById('view_question_text').textContent = question.question_text;
        document.getElementById('view_question_type').textContent = question.question_type.replace('_', ' ');
        document.getElementById('view_question_required').textContent = question.required ? 'Yes' : 'No';
        modal.style.display = 'flex';
    }

    function editQuestion(question) {
        const modal = document.getElementById('editQuestionModal');
        document.getElementById('edit_question_id').value = question.id;
        document.getElementById('edit_question_job_id').value = question.job_posting_id;
        document.getElementById('edit_question_text').value = question.question_text;
        document.getElementById('edit_question_type').value = question.question_type;
        document.getElementById('edit_question_required').value = question.required ? '1' : '0';
        modal.style.display = 'flex';
    }

    function deleteQuestion(questionId, jobId) {
        if (confirm('Are you sure you want to delete this screening question?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'recruitment.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete_screening_question';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'question_id';
            idInput.value = questionId;
            
            const jobInput = document.createElement('input');
            jobInput.type = 'hidden';
            jobInput.name = 'job_posting_id';
            jobInput.value = jobId;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            form.appendChild(jobInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    function closeViewQuestionModal() {
        document.getElementById('viewQuestionModal').style.display = 'none';
    }

    function closeEditQuestionModal() {
        document.getElementById('editQuestionModal').style.display = 'none';
    }
    </script>

    <?php include '../modals/components/recruitment-modals.php'; ?>

    <?php include '../partials/footer.php'; ?>
</body>
</html>