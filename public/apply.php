<?php
// NOTE: Legacy apply flow. The canonical applicant apply flow is now:
//   /auth/register-applicant.php?job_id=...&source=apply
// Keep this file as a backward-compatible redirect for old links.

$params = $_GET;
if (isset($params['job_id'])) {
    $params['job_id'] = (int)$params['job_id'];
}

// If a job_id is present, force apply mode.
if (!empty($params['job_id'])) {
    $params['source'] = 'apply';
}

$qs = http_build_query($params);
$dest = '../auth/register-applicant.php' . (!empty($qs) ? ('?' . $qs) : '');
header('Location: ' . $dest);
exit();

require_once '../database/config.php';
require_once '../includes/email_generator.php';
require_once '../includes/email_helper.php';

$job_id = intval($_GET['job_id'] ?? 0);
$success_message = '';
$error_message = '';

// Get job details
try {
    $sql = "SELECT jp.*, d.department_name
            FROM job_postings jp
            LEFT JOIN departments d ON jp.department_id = d.id
            WHERE jp.id = ? AND jp.status = 'Open'";
    
    $job = fetchSingle($sql, [$job_id]);
    
    if (!$job) {
        throw new Exception("Job posting not found or no longer available.");
    }
    
    // Format salary display
    $salary_range = '';
    if ($job['salary_min'] || $job['salary_max']) {
        if ($job['salary_min'] && $job['salary_max']) {
            $salary_range = '$' . number_format($job['salary_min']) . ' - $' . number_format($job['salary_max']);
        } elseif ($job['salary_min']) {
            $salary_range = 'From $' . number_format($job['salary_min']);
        } elseif ($job['salary_max']) {
            $salary_range = 'Up to $' . number_format($job['salary_max']);
        }
    } else {
        $salary_range = 'Competitive';
    }
    $job['salary_display'] = $salary_range;
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $job = null;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application']) && $job) {
    try {
        // Validate required fields
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $cover_letter = trim($_POST['cover_letter'] ?? '');
        
        if (empty($first_name) || empty($last_name) || empty($email) || empty($cover_letter)) {
            throw new Exception("Please fill in all required fields.");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }
        
        // Check if already applied
        $existing_application = fetchSingle(
            "SELECT id FROM job_applications WHERE job_posting_id = ? AND email = ?",
            [$job_id, $email]
        );
        
        if ($existing_application) {
            throw new Exception("You have already applied for this position with this email address.");
        }
        
        // Handle file upload (resume) - REQUIRED
        $resume_path = null;
        
        // Check if resume file was uploaded
        if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Please upload your resume. Resume is required to submit your application.");
        }
        
        $upload_dir = 'uploads/resumes/';
        
        // Create upload directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf', 'doc', 'docx'];
        
        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception("Please upload a PDF, DOC, or DOCX file for your resume.");
        }
        
        if ($_FILES['resume']['size'] > 5 * 1024 * 1024) { // 5MB limit
            throw new Exception("Resume file size must be less than 5MB.");
        }
        
        $filename = 'resume_' . $job_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
        $resume_path = $upload_dir . $filename;
        
        if (!move_uploaded_file($_FILES['resume']['tmp_name'], $resume_path)) {
            throw new Exception("Failed to upload resume. Please try again.");
        }
        
        // Insert application into database
        $sql = "INSERT INTO job_applications (job_posting_id, first_name, last_name, email, phone, 
                resume_path, cover_letter, status, applied_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'new', NOW())";
        
        $params = [$job_id, $first_name, $last_name, $email, $phone, $resume_path, $cover_letter];
        
        $application_id = insertRecord($sql, $params);
        
        if ($application_id) {
            // Create user account for the applicant
            try {
                // Check if user account already exists
                $existing_user = fetchSingle(
                    "SELECT id FROM user_accounts WHERE personal_email = ?",
                    [$email]
                );
                
                if (!$existing_user) {
                    // Get Applicant role ID
                    $applicant_role = fetchSingle(
                        "SELECT id FROM roles WHERE role_type = 'Applicant' LIMIT 1"
                    );
                    
                    if (!$applicant_role) {
                        // Create Applicant role if it doesn't exist
                        $role_id = insertRecord(
                            "INSERT INTO roles (role_name, role_type, access_level, description) VALUES (?, ?, ?, ?)",
                            ['Applicant', 'Applicant', 1, 'Job applicant with limited access']
                        );
                    } else {
                        $role_id = $applicant_role['id'];
                    }
                    
                    // Generate temporary password
                    $temp_password = bin2hex(random_bytes(4)); // 8-character password
                    $password_hash = password_hash($temp_password, PASSWORD_DEFAULT);
                    
                    // Create user account
                    $user_sql = "INSERT INTO user_accounts (first_name, last_name, personal_email, phone,
                                password_hash, role_id, status, created_at) 
                                VALUES (?, ?, ?, ?, ?, ?, 'Active', NOW())";
                    
                    $user_id = insertRecord($user_sql, [
                        $first_name,
                        $last_name,
                        $email,
                        $phone,
                        $password_hash,
                        $role_id
                    ]);
                    
                    if ($user_id) {
                        // Send welcome email with login credentials using email helper
                        $emailSent = sendApplicantWelcomeEmail(
                            $email,
                            $first_name,
                            $last_name,
                            $job['title'],
                            $temp_password
                        );
                        
                        if (!$emailSent) {
                            error_log("Failed to send welcome email to: {$email}");
                        }
                    }
                }
            } catch (Exception $e) {
                // Log error but don't fail the application
                error_log("Failed to create user account for applicant: " . $e->getMessage());
            }
            
            $success_message = "Your application has been submitted successfully! Check your email ({$email}) for your login credentials to track your application status.";
            // Clear form data
            $_POST = [];
        } else {
            throw new Exception("Failed to submit application. Please try again.");
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        
        // Clean up uploaded file if application failed
        if (isset($resume_path) && file_exists($resume_path)) {
            unlink($resume_path);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $job ? 'Apply for ' . htmlspecialchars($job['title']) : 'Job Application'; ?> | HR1 Management</title>
    <script src="https://unpkg.com/lucide@latest"></script>
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
            height: 100vh;
            padding: 1rem;
            color: #f8fafc;
            overflow: hidden;
        }

        .application-wrapper {
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            height: calc(100vh - 2rem);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo-section img {
            width: 65px;
            height: auto;
        }

        .logo-section h1 {
            font-size: 1.25rem;
            color: #ffffff;
            font-weight: 600;
        }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            text-decoration: none;
            color: #0ea5e9;
            font-weight: 500;
            transition: all 0.3s;
            padding: 0.45rem 1rem;
            border: 1px solid #0ea5e9;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .back-btn:hover {
            background: rgba(14, 165, 233, 0.1);
        }

        .back-btn i {
            width: 1.1rem;
            height: 1.1rem;
        }

        .job-header {
            background: rgba(30, 41, 54, 0.6);
            padding: 0.65rem;
            border-radius: 8px;
            text-align: center;
            backdrop-filter: blur(10px);
            flex-shrink: 0;
        }

        .job-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .job-meta {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            color: #cbd5e1;
            font-size: 0.7rem;
        }

        .job-meta-item i {
            width: 0.85rem;
            height: 0.85rem;
            color: #0ea5e9;
        }

        .content-container {
            background: rgba(30, 41, 54, 0.6);
            border-radius: 12px;
            padding: 0.65rem 0.65rem 0.5rem 0.65rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            overflow-y: auto;
            backdrop-filter: blur(10px);
            flex: 1;
        }

        .page-title {
            text-align: center;
            margin-bottom: 0.25rem;
        }

        .page-title h2 {
            font-size: 0.9rem;
            color: #ffffff;
            font-weight: 600;
        }

        .page-subtitle {
            text-align: center;
            color: #94a3b8;
            margin-bottom: 0.5rem;
            font-size: 0.7rem;
        }

        .alert {
            padding: 0.5rem;
            border-radius: 6px;
            margin-bottom: 0.5rem;
            font-size: 0.75rem;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }

        .form-section {
            background: rgba(42, 53, 68, 0.5);
            border: 1px solid rgba(58, 69, 84, 0.5);
            border-radius: 6px;
            padding: 0.5rem;
        }

        .form-section.full-width {
            grid-column: 1 / -1;
            margin-top: 0.5rem;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            margin-bottom: 0.5rem;
            padding-bottom: 0;
            border-bottom: none;
        }

        .section-icon {
            color: #0ea5e9;
            font-size: 0.85rem;
        }

        .section-title {
            font-size: 0.75rem;
            color: #0ea5e9;
            font-weight: 500;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 0.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            color: #e2e8f0;
            font-weight: 400;
            margin-bottom: 0.25rem;
            font-size: 0.7rem;
        }

        .required {
            color: #ef4444;
        }

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 0.4rem 0.6rem;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(58, 69, 84, 0.6);
            border-radius: 4px;
            color: #e2e8f0;
            font-size: 0.75rem;
            transition: all 0.3s;
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: #0ea5e9;
            background: #1a2332;
        }

        .form-input::placeholder,
        .form-textarea::placeholder {
            color: #64748b;
        }

        .form-textarea {
            resize: vertical;
            min-height: 45px;
        }

        .file-upload {
            border: 2px dashed rgba(58, 69, 84, 0.6);
            border-radius: 4px;
            padding: 0.65rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: rgba(15, 23, 42, 0.3);
        }

        .file-upload:hover {
            border-color: #0ea5e9;
            background: #1e2936;
        }

        .file-upload input[type="file"] {
            display: none;
        }

        .file-upload-label {
            color: #cbd5e1;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .file-info {
            color: #94a3b8;
            font-size: 0.65rem;
            margin-top: 0.25rem;
        }

        .button-group {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.65rem;
            justify-content: center;
        }

        .btn {
            padding: 0.45rem 1.1rem;
            border: none;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            text-decoration: none;
        }

        .btn i {
            width: 0.95rem;
            height: 0.95rem;
        }

        .btn-secondary {
            background: rgba(71, 85, 105, 0.8);
            color: #ffffff;
            border: 1px solid rgba(100, 116, 139, 0.5);
        }

        .btn-secondary:hover {
            background: rgba(51, 65, 85, 0.9);
            transform: translateY(-1px);
        }

        .btn-primary {
            background: #0ea5e9;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #0284c7;
            transform: translateY(-1px);
        }

        .footer {
            text-align: center;
            color: #94a3b8;
            font-size: 0.65rem;
            padding: 0.35rem;
            flex-shrink: 0;
        }

        @media (max-width: 968px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
            
            .job-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php $logo_path = '../assets/images/slate.png'; include '../includes/loading-screen.php'; ?>
    
    <div class="application-wrapper">
        <!-- Header -->
        <div class="header">
            <div class="logo-section">
                <img src="../assets/images/slate.png" alt="SLATE Logo">
                <h1>SLATE</h1>
            </div>
            <a href="careers.php" class="back-btn">
                <i data-lucide="arrow-left"></i>
                Back to Careers
            </a>
        </div>

        <?php if ($job): ?>
            <!-- Job Details -->
            <div class="job-header">
                <h1 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h1>
                <div class="job-meta">
                    <div class="job-meta-item">
                        <i data-lucide="building"></i>
                        <span><?php echo htmlspecialchars($job['department_name']); ?></span>
                    </div>
                    <div class="job-meta-item">
                        <i data-lucide="map-pin"></i>
                        <span><?php echo htmlspecialchars($job['location']); ?></span>
                    </div>
                    <div class="job-meta-item">
                        <i data-lucide="briefcase"></i>
                        <span><?php echo htmlspecialchars($job['employment_type']); ?></span>
                    </div>
                    <div class="job-meta-item">
                        <i data-lucide="dollar-sign"></i>
                        <span><?php echo htmlspecialchars($job['salary_display']); ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Content -->
        <div class="content-container">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <strong>✓ Success!</strong> <?php echo htmlspecialchars($success_message); ?>
                    <div style="margin-top: 0.75rem;">
                        <a href="../auth/terms.php" class="btn btn-primary" style="display: inline-flex;">Continue to Registration</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($job && empty($success_message)): ?>
                <div class="page-title">
                    <h2>Apply for this Position</h2>
                </div>
                <p class="page-subtitle">We're excited to learn more about you! Please fill out the form below.</p>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <!-- Personal Information -->
                        <div class="form-section">
                            <div class="section-header">
                                <i data-lucide="user" class="section-icon"></i>
                                <h3 class="section-title">Personal Information</h3>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">First Name <span class="required">*</span></label>
                                    <input type="text" name="first_name" class="form-input" placeholder="Enter first name" required
                                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Last Name <span class="required">*</span></label>
                                    <input type="text" name="last_name" class="form-input" placeholder="Enter last name" required
                                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Email Address <span class="required">*</span></label>
                                    <input type="email" name="email" class="form-input" placeholder="Enter email address" required
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" name="phone" class="form-input" placeholder="Enter phone number"
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Resume Upload -->
                        <div class="form-section">
                            <div class="section-header">
                                <i data-lucide="file-text" class="section-icon"></i>
                                <h3 class="section-title">Resume</h3>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Upload Resume <span class="required">*</span></label>
                                <div class="file-upload" onclick="document.getElementById('resume').click()">
                                    <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx" onchange="updateFileName(this)">
                                    <div class="file-upload-label" id="file-label">
                                        <i data-lucide="upload" style="width: 1.5rem; height: 1.5rem; color: #0ea5e9;"></i>
                                        <p style="margin-top: 0.35rem; font-size: 0.7rem;">Click to upload your resume</p>
                                        <p style="font-size: 0.65rem; color: #64748b;">No file chosen</p>
                                    </div>
                                </div>
                                <p class="file-info">Accepted formats: PDF, DOC, DOCX (Max 5MB)</p>
                            </div>
                        </div>
                    </div>

                    <!-- Cover Letter -->
                    <div class="form-section" style="margin-top: 0.5rem;">
                        <div class="section-header">
                            <i data-lucide="edit" class="section-icon"></i>
                            <h3 class="section-title">Cover Letter</h3>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tell us why you're perfect for this role <span class="required">*</span></label>
                            <textarea name="cover_letter" class="form-textarea" rows="3" required 
                                      placeholder="Share your motivation, relevant experience, and what makes you a great fit for this position..."><?php echo htmlspecialchars($_POST['cover_letter'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="button-group" style="margin-top: 0.75rem;">
                        <a href="careers.php" class="btn btn-secondary">
                            <i data-lucide="arrow-left"></i>
                            Cancel
                        </a>
                        <button type="submit" name="submit_application" class="btn btn-primary">
                            <i data-lucide="send"></i>
                            Submit Application
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            © 2025 SLATE Freight Management System. All rights reserved.
        </div>
    </div>

    <script>
        function updateFileName(input) {
            const label = document.getElementById('file-label');
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                label.innerHTML = `
                    <i data-lucide="check-circle" style="width: 1.5rem; height: 1.5rem; color: #10b981;"></i>
                    <p style="margin-top: 0.35rem; color: #10b981; font-size: 0.7rem;">File Selected</p>
                    <p style="font-size: 0.65rem; color: #cbd5e1;">${file.name} (${sizeMB} MB)</p>
                `;
                lucide.createIcons();
            }
        }
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
