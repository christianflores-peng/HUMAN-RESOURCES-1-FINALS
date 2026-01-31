<?php
/**
 * HR1 Module: Create/Edit Job Posting
 * Slate Freight Management System
 * 
 * Role-Based Access: HR Staff, Admin
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
    $departments = [];
}

// Handle form submission
$successMessage = null;
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_job'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $departmentId = intval($_POST['department_id']);
    $employmentType = $_POST['employment_type'];
    $salaryMin = !empty($_POST['salary_min']) ? floatval($_POST['salary_min']) : null;
    $salaryMax = !empty($_POST['salary_max']) ? floatval($_POST['salary_max']) : null;
    $location = trim($_POST['location']);
    $requirements = trim($_POST['requirements']);
    $responsibilities = trim($_POST['responsibilities']);
    $closingDate = !empty($_POST['closing_date']) ? $_POST['closing_date'] : null;
    $status = $_POST['status'];
    
    try {
        $jobId = insertRecord(
            "INSERT INTO job_postings (title, description, department_id, employment_type, salary_min, salary_max, location, requirements, responsibilities, status, created_by, closing_date) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$title, $description, $departmentId, $employmentType, $salaryMin, $salaryMax, $location, $requirements, $responsibilities, $status, $userId, $closingDate]
        );
        
        logAuditAction($userId, 'CREATE', 'job_postings', $jobId, null, [
            'title' => $title,
            'department_id' => $departmentId,
            'status' => $status
        ], "Created job posting: {$title}");
        
        $successMessage = "Job posting created successfully!";
        
        // Redirect to HR dashboard after 2 seconds
        header("refresh:2;url=hr-recruitment-dashboard.php");
        
    } catch (Exception $e) {
        $errorMessage = "Failed to create job posting: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Job Posting - Slate Freight</title>
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
            padding: 2rem;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 1.75rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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

        .btn-secondary {
            background: #334155;
            color: #e2e8f0;
        }

        .btn-secondary:hover {
            background: #475569;
        }

        .btn-primary {
            background: #0ea5e9;
            color: white;
        }

        .btn-primary:hover {
            background: #0284c7;
        }

        .form-card {
            background: #1e2936;
            border-radius: 12px;
            padding: 2rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
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

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #cbd5e1;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            background: #2a3544;
            border: 1px solid #3a4554;
            border-radius: 6px;
            color: #e2e8f0;
            font-size: 0.95rem;
            font-family: inherit;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0ea5e9;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-hint {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-top: 0.25rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .form-actions .btn {
            flex: 1;
            justify-content: center;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <span class="material-symbols-outlined">work</span>
                Create New Job Posting
            </h1>
            <a href="hr-recruitment-dashboard.php" class="btn btn-secondary">
                <span class="material-symbols-outlined">arrow_back</span>
                Back
            </a>
        </div>

        <?php if ($successMessage): ?>
            <div class="alert alert-success">
                <strong>✅ Success!</strong> <?php echo htmlspecialchars($successMessage); ?>
                <br><small>Redirecting to dashboard...</small>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-error">
                <strong>❌ Error:</strong> <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST">
                <input type="hidden" name="create_job" value="1">

                <div class="form-group">
                    <label>Job Title *</label>
                    <input type="text" name="title" required placeholder="e.g., Truck Driver - Class A">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Department *</label>
                        <select name="department_id" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Employment Type *</label>
                        <select name="employment_type" required>
                            <option value="Full-time">Full-time</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Contract">Contract</option>
                            <option value="Temporary">Temporary</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Job Description *</label>
                    <textarea name="description" required placeholder="Describe the role and what the position entails..."></textarea>
                </div>

                <div class="form-group">
                    <label>Requirements *</label>
                    <textarea name="requirements" required placeholder="List the qualifications, skills, and experience required..."></textarea>
                </div>

                <div class="form-group">
                    <label>Responsibilities</label>
                    <textarea name="responsibilities" placeholder="List the key responsibilities and duties..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Minimum Salary (₱)</label>
                        <input type="number" name="salary_min" step="0.01" placeholder="35000">
                        <div class="form-hint">Optional - leave blank to hide</div>
                    </div>

                    <div class="form-group">
                        <label>Maximum Salary (₱)</label>
                        <input type="number" name="salary_max" step="0.01" placeholder="50000">
                        <div class="form-hint">Optional - leave blank to hide</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Location *</label>
                        <input type="text" name="location" required placeholder="e.g., Manila, Laguna">
                    </div>

                    <div class="form-group">
                        <label>Closing Date</label>
                        <input type="date" name="closing_date">
                        <div class="form-hint">Optional - leave blank for open-ended</div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" required>
                        <option value="Draft">Draft (not visible to applicants)</option>
                        <option value="Open" selected>Open (accepting applications)</option>
                        <option value="On Hold">On Hold</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-outlined">add_circle</span>
                        Create Job Posting
                    </button>
                    <a href="hr-recruitment-dashboard.php" class="btn btn-secondary">
                        <span class="material-symbols-outlined">cancel</span>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
