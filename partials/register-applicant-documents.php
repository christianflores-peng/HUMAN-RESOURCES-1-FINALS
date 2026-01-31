<?php
require_once '../includes/session_helper.php';

// Start secure session
startSecureSession();

// Check if user came from step 2
if (!isset($_SESSION['registration_data'])) {
    header('Location: register-portal.php');
    exit();
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        require_once '../database/config.php';
        require_once '../includes/email_helper.php';
        
        // Get data from session
        $first_name = $_SESSION['registration_data']['first_name'];
        $last_name = $_SESSION['registration_data']['last_name'];
        $email = $_SESSION['registration_data']['email'];
        $phone = $_SESSION['registration_data']['phone'];
        $password_hash = $_SESSION['registration_data']['password_hash'];
        
        $cover_letter = trim($_POST['cover_letter'] ?? '');
    
        // Handle resume upload with security
        $resume_path = '';
        $temp_file_path = '';
        
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/resumes/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['pdf', 'doc', 'docx'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            // Verify file type by MIME
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $_FILES['resume']['tmp_name']);
            finfo_close($finfo);
            
            $allowed_mimes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            
            if (!in_array($file_ext, $allowed_exts)) {
                $error_message = 'Invalid file extension. Only PDF, DOC, and DOCX files are allowed.';
            } elseif (!in_array($mime_type, $allowed_mimes)) {
                $error_message = 'Invalid file type. Only PDF and Word documents are allowed.';
            } elseif ($_FILES['resume']['size'] > $max_size) {
                $error_message = 'File size exceeds 5MB limit.';
            } else {
                // Sanitize filename
                $safe_filename = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($_FILES['resume']['name'], PATHINFO_FILENAME));
                $temp_file_path = $upload_dir . 'temp_' . uniqid() . '_' . time() . '.' . $file_ext;
                
                if (!move_uploaded_file($_FILES['resume']['tmp_name'], $temp_file_path)) {
                    $error_message = 'Failed to upload file. Please try again.';
                }
            }
        }
    
        if (empty($error_message)) {
            try {
                // Get Applicant role ID dynamically
                $applicant_role = fetchSingle("SELECT id FROM roles WHERE role_type = 'Applicant' LIMIT 1");
                
                if (!$applicant_role) {
                    throw new Exception("Applicant role not found in system");
                }
                
                $role_id = $applicant_role['id'];
                
                // Start database transaction
                $pdo = getDBConnection();
                $pdo->beginTransaction();
                
                try {
                    // Insert into user_accounts table
                    $user_id = insertRecord(
                        "INSERT INTO user_accounts (first_name, last_name, personal_email, phone, password_hash, role_id, status, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, 'Active', NOW())",
                        [$first_name, $last_name, $email, $phone, $password_hash, $role_id]
                    );
                    
                    // Rename temp file to final location with user_id
                    if (!empty($temp_file_path) && file_exists($temp_file_path)) {
                        $file_ext = pathinfo($temp_file_path, PATHINFO_EXTENSION);
                        $resume_path = 'resume_' . $user_id . '_' . time() . '.' . $file_ext;
                        $final_path = '../uploads/resumes/' . $resume_path;
                        rename($temp_file_path, $final_path);
                    }
                    
                    // Insert into applicant_profiles table
                    $profile_id = insertRecord(
                        "INSERT INTO applicant_profiles (user_id, resume_path, cover_letter, created_at) 
                         VALUES (?, ?, ?, NOW())",
                        [$user_id, $resume_path, $cover_letter]
                    );
                    
                    // Create a general job application entry so managers can see this applicant
                    // This allows the applicant to appear in the recruitment dashboard
                    $application_id = insertRecord(
                        "INSERT INTO job_applications 
                        (job_posting_id, first_name, last_name, email, phone, resume_path, cover_letter, status, applied_date) 
                        VALUES (NULL, ?, ?, ?, ?, ?, ?, 'new', NOW())",
                        [$first_name, $last_name, $email, $phone, 'uploads/resumes/' . $resume_path, $cover_letter]
                    );
                    
                    // Commit transaction
                    $pdo->commit();
                    
                    // Clear session data
                    unset($_SESSION['registration_data']);
                    
                    $success_message = 'Registration successful! You can now login with your email and password.';
                    header('refresh:2;url=login.php');
                    
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $pdo->rollBack();
                    
                    // Clean up temp file
                    if (!empty($temp_file_path) && file_exists($temp_file_path)) {
                        unlink($temp_file_path);
                    }
                    
                    throw $e;
                }
                
            } catch (Exception $e) {
                $error_message = 'Registration failed: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Registration - Step 3 - HR1</title>
    <link rel="stylesheet" href="css/styles.css">
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
            height: 100vh;
            padding: 1rem;
            color: #f8fafc;
            overflow: hidden;
        }

        .registration-wrapper {
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .header {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            margin-bottom: 1rem;
            flex-shrink: 0;
            position: relative;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            position: absolute;
            left: 0;
        }

        .logo-section img {
            width: 65px;
            height: auto;
        }

        .progress-steps {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.3rem;
        }

        .step-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #10b981;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .step.active .step-number {
            background: #0ea5e9;
        }

        .step-label {
            font-size: 0.75rem;
            color: #0ea5e9;
            font-weight: 500;
        }

        .step-connector {
            width: 60px;
            height: 2px;
            background: #10b981;
            margin-bottom: 1rem;
        }

        .content-container {
            background: rgba(30, 41, 54, 0.6);
            border-radius: 12px;
            padding: 1rem 0.75rem 0.75rem 0.75rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            overflow-y: auto;
            backdrop-filter: blur(10px);
        }

        .page-title {
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .page-title h2 {
            color: #e2e8f0;
            font-size: 1.2rem;
            margin-bottom: 0.3rem;
        }

        .page-subtitle {
            color: #94a3b8;
            font-size: 0.75rem;
            text-align: center;
            margin-bottom: 1rem;
        }

        .alert {
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }

        .form-section {
            background: rgba(42, 53, 68, 0.5);
            border: 1px solid rgba(58, 69, 84, 0.5);
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 0.6rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.6rem;
            margin-bottom: 0.6rem;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(58, 69, 84, 0.5);
        }

        .section-icon {
            color: #0ea5e9;
            font-size: 1.2rem;
        }

        .section-title {
            color: #0ea5e9;
            font-size: 1rem;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 0.6rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            color: #e2e8f0;
            font-weight: 400;
            margin-bottom: 0.3rem;
            font-size: 0.75rem;
        }

        .form-textarea {
            width: 100%;
            padding: 0.5rem 0.75rem;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(58, 69, 84, 0.6);
            border-radius: 4px;
            color: #e2e8f0;
            font-size: 0.8rem;
            font-family: inherit;
            resize: vertical;
            transition: all 0.3s;
        }

        .form-textarea:focus {
            outline: none;
            border-color: #0ea5e9;
            background: #1a2332;
        }

        .form-textarea::placeholder {
            color: #64748b;
        }

        .file-upload {
            border: 2px dashed rgba(58, 69, 84, 0.6);
            border-radius: 4px;
            padding: 1rem;
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
            font-size: 0.8rem;
            font-weight: 500;
        }

        .file-info {
            color: #94a3b8;
            font-size: 0.7rem;
            margin-top: 0.35rem;
        }

        .button-group {
            display: flex;
            gap: 0.6rem;
            margin-top: 1rem;
            justify-content: center;
        }

        .btn {
            padding: 0.55rem 1.3rem;
            border: none;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.35rem;
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

        .login-link {
            text-align: center;
            margin-top: 1rem;
            color: #cbd5e1;
            font-size: 0.75rem;
        }

        .login-link a {
            color: #0ea5e9;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .footer {
            text-align: center;
            margin-top: 0.75rem;
            color: #64748b;
            font-size: 0.7rem;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <div class="registration-wrapper">
        <!-- Header -->
        <div class="header">
            <div class="logo-section">
                <img src="../assets/images/slate.png" alt="SLATE Logo">
            </div>
            
            <!-- Progress Steps -->
            <div class="progress-steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-label">Type</div>
                </div>
                <div class="step-connector"></div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-label">Registration Details</div>
                </div>
                <div class="step-connector"></div>
                <div class="step active">
                    <div class="step-number">3</div>
                    <div class="step-label">Documents</div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="content-container">
            <div class="page-title">
                <h2>Upload Documents</h2>
            </div>
            <p class="page-subtitle">Upload your resume and cover letter to complete your registration</p>

            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <?php echo getCSRFTokenField(); ?>
                <!-- Documents Section -->
                <div class="form-section">
                    <div class="section-header">
                        <span class="material-symbols-outlined section-icon">description</span>
                        <h3 class="section-title">Documents</h3>
                    </div>

                    <div class="form-row">
                        <!-- Resume Upload -->
                        <div class="form-group">
                            <label class="form-label">Upload Resume *</label>
                            <div class="file-upload" onclick="document.getElementById('resume').click()">
                                <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx" onchange="updateFileName(this)">
                                <div class="file-upload-label" id="file-label">
                                    <span class="material-symbols-outlined" style="font-size: 2rem; color: #0ea5e9;">upload_file</span>
                                    <p style="margin-top: 0.5rem;">Click to upload</p>
                                    <p style="font-size: 0.75rem; color: #64748b;">No file chosen</p>
                                </div>
                            </div>
                            <p class="file-info">PDF, DOC, DOCX (Max 5MB)</p>
                        </div>

                        <!-- Cover Letter -->
                        <div class="form-group">
                            <label class="form-label">Cover Letter *</label>
                            <textarea name="cover_letter" class="form-textarea" rows="8" placeholder="Tell us why you're perfect for this role..."></textarea>
                            <p class="file-info">Share your motivation and experience</p>
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='register-applicant.php?terms_accepted=true'">
                        <span class="material-symbols-outlined">arrow_back</span>
                        Previous
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-outlined">check_circle</span>
                        Complete Registration
                    </button>
                </div>
            </form>

            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
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
                label.innerHTML = `
                    <span class="material-symbols-outlined" style="font-size: 2rem; color: #10b981;">check_circle</span>
                    <p style="margin-top: 0.5rem; color: #10b981;">File Selected</p>
                    <p style="font-size: 0.85rem; color: #cbd5e1;">${input.files[0].name}</p>
                `;
            }
        }
    </script>
</body>
</html>
