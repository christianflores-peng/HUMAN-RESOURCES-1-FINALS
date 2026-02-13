<?php
require_once '../includes/session_helper.php';
require_once '../database/config.php';
require_once '../includes/otp_helper.php';

// Start secure session
startSecureSession();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: login-redirect.php');
    exit();
}

$error_message = '';
$success_message = '';
$show_otp_modal = false;
$pending_email = '';
$pending_phone = '';
$job_id = intval($_GET['job_id'] ?? 0);
$source = trim($_GET['source'] ?? '');
if ($job_id > 0 && $source === 'apply') {
    $_SESSION['apply_job_id'] = $job_id;
}

// Handle OTP verification for registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_registration_otp'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $otp_code = trim($_POST['otp_code'] ?? '');
        $pending_email = $_POST['pending_email'] ?? '';
        $job_id = intval($_POST['job_id'] ?? 0);
        $source = trim($_POST['source'] ?? '');
        if ($job_id > 0 && $source === 'apply') {
            $_SESSION['apply_job_id'] = $job_id;
        }
        
        if (empty($otp_code)) {
            $error_message = 'Please enter the verification code.';
            $show_otp_modal = true;
            $pending_phone = $_SESSION['pending_registration']['phone'] ?? '';
        } else {
            $result = verifyOTP($pending_email, $otp_code, 'registration');
            
            if ($result['success']) {
                // OTP verified, complete registration with documents
                require_once '../includes/email_helper.php';
                
                $first_name = $_SESSION['pending_registration']['first_name'];
                $last_name = $_SESSION['pending_registration']['last_name'];
                $email = $_SESSION['pending_registration']['email'];
                $phone = $_SESSION['pending_registration']['phone'];
                $password_hash = $_SESSION['pending_registration']['password_hash'];
                $cover_letter = $_SESSION['pending_registration']['cover_letter'] ?? '';
                $resume_temp_path = $_SESSION['pending_registration']['resume_temp_path'] ?? '';
                
                try {
                    // Get Applicant role ID
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
                        
                        // Move temp file to final location with user_id
                        $resume_path = '';
                        if (!empty($resume_temp_path) && file_exists($resume_temp_path)) {
                            $file_ext = pathinfo($resume_temp_path, PATHINFO_EXTENSION);
                            $resume_path = 'resume_' . $user_id . '_' . time() . '.' . $file_ext;
                            $final_path = '../uploads/resumes/' . $resume_path;
                            rename($resume_temp_path, $final_path);
                        }
                        
                        // Insert into applicant_profiles table
                        $profile_id = insertRecord(
                            "INSERT INTO applicant_profiles (user_id, resume_path, cover_letter, created_at) 
                             VALUES (?, ?, ?, NOW())",
                            [$user_id, $resume_path, $cover_letter]
                        );
                        
                        // Create a general job application entry
                        $application_id = insertRecord(
                            "INSERT INTO job_applications 
                            (job_posting_id, first_name, last_name, email, phone, resume_path, cover_letter, status, applied_date) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'new', NOW())",
                            [$_SESSION['apply_job_id'] ?? null, $first_name, $last_name, $email, $phone, 'uploads/resumes/' . $resume_path, $cover_letter]
                        );
                        
                        // Commit transaction
                        $pdo->commit();
                        
                        // Clear session data
                        unset($_SESSION['pending_registration']);
                        $apply_job_id = $_SESSION['apply_job_id'] ?? 0;
                        unset($_SESSION['apply_job_id']);

                        $success_message = 'Registration successful! You can now login with your email and password.';
                        if ($apply_job_id > 0) {
                            header('refresh:2;url=../public/job_details.php?id=' . $apply_job_id);
                        } else {
                            header('refresh:2;url=login.php');
                        }
                        
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        
                        // Clean up temp file
                        if (!empty($resume_temp_path) && file_exists($resume_temp_path)) {
                            unlink($resume_temp_path);
                        }
                        
                        throw $e;
                    }
                    
                } catch (Exception $e) {
                    $error_message = 'Registration failed: ' . $e->getMessage();
                }
            } else {
                incrementOTPAttempts($pending_email, 'registration');
                $error_message = $result['message'];
                $show_otp_modal = true;
                $pending_phone = $_SESSION['pending_registration']['phone'] ?? '';
            }
        }
    }
}

// Handle resend OTP for registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_registration_otp'])) {
    $pending_email = $_POST['pending_email'] ?? '';
    $pending_data = $_SESSION['pending_registration'] ?? null;
    $job_id = intval($_POST['job_id'] ?? 0);
    $source = trim($_POST['source'] ?? '');
    if ($job_id > 0 && $source === 'apply') {
        $_SESSION['apply_job_id'] = $job_id;
    }
    
    if ($pending_data && !empty($pending_email)) {
        $otp_code = createOTP($pending_email, $pending_data['phone'] ?? null, 'registration');
        $user_name = $pending_data['first_name'] . ' ' . $pending_data['last_name'];
        sendOTP($pending_email, $otp_code, $user_name, $pending_data['phone'] ?? null);
        $success_message = 'A new verification code has been sent.';
        $pending_phone = $pending_data['phone'] ?? '';
    }
    $show_otp_modal = true;
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $cover_letter = trim($_POST['cover_letter'] ?? '');
        $job_id = intval($_POST['job_id'] ?? 0);
        $source = trim($_POST['source'] ?? '');
        if ($job_id > 0 && $source === 'apply') {
            $_SESSION['apply_job_id'] = $job_id;
        }
    
        // Validation
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            $error_message = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error_message = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
        } else {
            // Handle resume upload
            $resume_temp_path = '';
            
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
                    $resume_temp_path = $upload_dir . 'temp_' . uniqid() . '_' . time() . '.' . $file_ext;
                    
                    if (!move_uploaded_file($_FILES['resume']['tmp_name'], $resume_temp_path)) {
                        $error_message = 'Failed to upload file. Please try again.';
                    }
                }
            }
            
            if (empty($error_message)) {
                try {
                    // Check for existing email in user_accounts table only
                    $existing_email = fetchSingle("SELECT id FROM user_accounts WHERE personal_email = ? OR company_email = ?", [$email, $email]);
                    
                    if ($existing_email) {
                        $error_message = 'Email address already exists. Please use a different email or login if you already have an account.';
                        
                        // Clean up temp file
                        if (!empty($resume_temp_path) && file_exists($resume_temp_path)) {
                            unlink($resume_temp_path);
                        }
                    } else {
                        // Store data temporarily and send OTP
                        $_SESSION['pending_registration'] = [
                            'first_name' => $first_name,
                            'last_name' => $last_name,
                            'email' => $email,
                            'phone' => $phone,
                            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                            'cover_letter' => $cover_letter,
                            'resume_temp_path' => $resume_temp_path
                        ];
                        
                        // Generate and send OTP
                        $otp_code = createOTP($email, $phone, 'registration');
                        $user_name = $first_name . ' ' . $last_name;
                        sendOTP($email, $otp_code, $user_name, $phone);
                        
                        $pending_email = $email;
                        $pending_phone = $phone;
                        $show_otp_modal = true;
                        $success_message = 'Verification code sent to your email' . (!empty($phone) ? ' and phone' : '') . '.';
                    }
                } catch (Exception $e) {
                    $error_message = 'Registration failed: ' . $e->getMessage();
                    error_log("Registration error: " . $e->getMessage());
                    
                    // Clean up temp file
                    if (!empty($resume_temp_path) && file_exists($resume_temp_path)) {
                        unlink($resume_temp_path);
                    }
                }
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
    <title>Applicant Registration - HR1</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0a1929;
            min-height: 50vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #f8fafc;
            padding: 1rem;
        }

        .registration-wrapper {
            max-width: 1100px;
            max-height: 800px;
            width: 100%;
            display: flex;
            flex-direction: column;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .progress-steps {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.2rem;
        }

        .step-number {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #10b981;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.7rem;
        }

        .step.active .step-number {
            background: #0ea5e9;
        }

        .step-label {
            font-size: 0.65rem;
            color: #94a3b8;
            font-weight: 500;
        }

        .step.active .step-label {
            color: #0ea5e9;
        }

        .step-connector {
            width: 40px;
            height: 2px;
            background: #10b981;
            margin-bottom: 0.5rem;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo-section img {
            width: 70px;
            height: auto;
        }

        .content-container {
            background: rgba(30, 41, 54, 0.6);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
        }

        .page-title {
            text-align: center;
            margin-bottom: 0.25rem;
        }

        .page-title h2 {
            font-size: 1.1rem;
            color: #ffffff;
            font-weight: 600;
        }

        .page-subtitle {
            text-align: center;
            color: #94a3b8;
            margin-bottom: 0.75rem;
            font-size: 0.75rem;
        }

        .alert {
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.85rem;
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
            gap: 0.75rem;
        }

        .form-section {
            background: #1e2936;
            border-radius: 8px;
            padding: 0.75rem;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            margin-bottom: 0.5rem;
        }

        .section-icon {
            color: #0ea5e9;
            font-size: 1rem;
        }

        .section-title {
            font-size: 0.85rem;
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
            color: #ffffff
            font-weight: 400;
            margin-bottom: 0.25rem;
            font-size: 0.7rem;
        }

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 0.5rem 0.65rem;
            background: #2a3544;
            border: 1px solid #3a4554;
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
            background: #2f3a4a;
        }

        .form-input::placeholder,
        .form-textarea::placeholder {
            color: #64748b;
        }

        .form-textarea {
            resize: vertical;
            min-height: 50px;
        }

        .password-field {
            position: relative;
        }

        .password-field input {
            padding-right: 2.5rem;
        }

        .password-icon {
            position: absolute;
            right: 0.65rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 18px;
            pointer-events: none;
        }

        .file-upload {
            border: 2px dashed #3a4554;
            border-radius: 6px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #2a3544;
            min-height: 100px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .file-upload:hover {
            border-color: #e3e7e9ff;
            background: #2f3a4a;
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
            color: #64748b;
            font-size: 0.65rem;
            margin-top: 0.25rem;
        }

        .button-group {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.75rem;
            justify-content: center;
        }

        .btn {
            padding: 0.5rem 1.25rem;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            min-width: 100px;
            justify-content: center;
        }

        .btn-secondary {
            background: #475569;
            color: #ffffff;
        }

        .btn-secondary:hover {
            background: #64748b;
        }

        .btn-primary {
            background: #0ea5e9;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #0284c7;
        }

        .footer {
            text-align: center;
            color: #dae0e7ff;
            font-size: 0.7rem;
            padding: 0.5rem;
            border-top: 1px solid rgba(100, 116, 139, 0.2);
            margin-top: auto;
        }

        .footer-bottom {
            text-align: center;
            color: #dae0e7ff;
            font-size: 0.5rem;
            padding: 0.5rem;
            border-top: 1px solid rgba(100, 116, 139, 0.2);
            margin-top: auto;
            gap: 2.0rem;
        }

        .login-link {
            text-align: center;
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 0.5rem;
        }

        .login-link a {
            color: #0ea5e9;
            text-decoration: none;
            font-weight: 500;
        }

        .form-textarea {
            width: 100%;
            padding: 0.5rem 0.65rem;
            background: #2a3544;
            border: 1px solid #3a4554;
            border-radius: 4px;
            color: #e2e8f0;
            font-size: 0.75rem;
            font-family: inherit;
            resize: vertical;
            transition: all 0.3s;
        }

        .form-textarea:focus {
            outline: none;
            border-color: #0ea5e9;
            background: #2f3a4a;
        }

        .form-textarea::placeholder {
            color: #64748b;
        }

        .file-info {
            color: #64748b;
            font-size: 0.65rem;
            margin-top: 0.25rem;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php $logo_path = '../assets/images/slate.png'; include '../includes/loading-screen.php'; ?>
    
    <div class="registration-wrapper">
        <!-- Content -->
        <div class="content-container">
            <!-- Header -->
            <div class="header">
                <div class="progress-steps">
                    <div class="step">
                        <div class="step-number">✓</div>
                        <div class="step-label">Type</div>
                    </div>
                    <div class="step-connector"></div>
                    <div class="step active">
                        <div class="step-number">2</div>
                        <div class="step-label">Registration Details</div>
                    </div>
                </div>
                <div class="logo-section">
                    <img src="../assets/images/slate.png" alt="SLATE Logo">
                </div>
            </div>

            <div class="page-title">
                <h2>Applicant Registration</h2>
            </div>
            <p class="page-subtitle">Create your account to access the applicant portal and apply for jobs</p>

            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="registrationForm">
                <?php echo getCSRFTokenField(); ?>
                <?php if ($job_id > 0 && $source === 'apply'): ?>
                <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                <input type="hidden" name="source" value="apply">
                <?php endif; ?>
                <input type="hidden" name="register" value="1">
                
                <!-- Combined Form Section -->
                <div class="form-section">
                    <!-- Personal Information -->
                    <div class="section-header">
                        <i data-lucide="user" class="section-icon"></i>
                        <h3 class="section-title">Personal Information</h3>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">First Name </label>
                            <input type="text" name="first_name" class="form-input" placeholder="Enter first name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name </label>
                            <input type="text" name="last_name" class="form-input" placeholder="Enter last name" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Email Address </label>
                            <input type="email" name="email" class="form-input" placeholder="Enter email address" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number </label>
                            <input type="tel" name="phone" class="form-input" placeholder="Enter phone number" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Password </label>
                            <div class="password-field">
                                <input type="password" id="password" name="password" class="form-input" placeholder="Enter password (min 6 characters)" required>
                                <i data-lucide="eye" class="password-icon" id="togglePassword" style="cursor: pointer; pointer-events: auto;"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm Password </label>
                            <div class="password-field">
                                <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Confirm password" required>
                                <i data-lucide="eye" class="password-icon" id="toggleConfirmPassword" style="cursor: pointer; pointer-events: auto;"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Documents Section with 5vh gap -->
                    <div style="margin-top: 5vh;">
                        <div class="section-header">
                            <i data-lucide="file-text" class="section-icon"></i>
                            <h3 class="section-title">Documents</h3>
                        </div>

                        <div class="form-row">
                            <!-- Resume Upload -->
                            <div class="form-group">
                                <label class="form-label">Upload Resume</label>
                                <div class="file-upload" onclick="document.getElementById('resume').click()">
                                    <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx" onchange="updateFileName(this)">
                                    <div class="file-upload-label" id="file-label">
                                        <i data-lucide="upload" style="width: 2rem; height: 2rem; color: #0ea5e9;"></i>
                                        <p style="margin-top: 0.5rem;">Click to upload</p>
                                        <p style="font-size: 0.75rem; color: #64748b;">No file chosen</p>
                                    </div>
                                </div>
                                <p class="file-info">PDF, DOC, DOCX (Max 5MB)</p>
                            </div>

                            <!-- Cover Letter -->
                            <div class="form-group">
                                <label class="form-label">Cover Letter</label>
                                <textarea name="cover_letter" class="form-textarea" rows="8" placeholder="Tell us why you're perfect for this role..."></textarea>
                                <p class="file-info">Share your motivation and experience</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='register-portal.php?terms_accepted=true'">
                        <i data-lucide="arrow-left"></i>
                        Previous
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="check-circle"></i>
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
                    <i data-lucide="check-circle" style="width: 2rem; height: 2rem; color: #10b981;"></i>
                    <p style="margin-top: 0.5rem; color: #10b981;">File Selected</p>
                    <p style="font-size: 0.85rem; color: #cbd5e1;">${input.files[0].name}</p>
                `;
                lucide.createIcons();
            }
        }

        // Password toggle functionality using event delegation
        document.querySelectorAll('.password-field').forEach(field => {
            field.addEventListener('click', function(e) {
                const icon = field.querySelector('.password-icon, svg.lucide');
                if (!icon) return;
                // Only trigger if clicking on the icon/svg area
                if (e.target.closest('.password-icon') || e.target.closest('svg.lucide')) {
                    const input = field.querySelector('input');
                    const isPassword = input.getAttribute('type') === 'password';
                    input.setAttribute('type', isPassword ? 'text' : 'password');
                    // Replace the SVG with a new <i> tag so lucide can re-render
                    const oldIcon = field.querySelector('svg.lucide') || field.querySelector('.password-icon');
                    const newIcon = document.createElement('i');
                    newIcon.setAttribute('data-lucide', isPassword ? 'eye-off' : 'eye');
                    newIcon.className = 'password-icon';
                    newIcon.style.cssText = 'cursor: pointer; pointer-events: auto;';
                    oldIcon.replaceWith(newIcon);
                    lucide.createIcons();
                }
            });
        });
    </script>

    <!-- OTP Verification Modal for Registration -->
    <div id="otpModal" class="otp-modal <?php echo $show_otp_modal ? 'active' : ''; ?>">
        <div class="otp-modal-content">
            <div class="otp-header">
                <img src="../assets/images/slate.png" alt="SLATE Logo" class="otp-logo">
                <h2>Verify Your Account</h2>
                <p>Enter the 6-digit code sent to <strong style="color:#0ea5e9;"><?php echo htmlspecialchars(maskEmail($pending_email)); ?></strong></p>
            </div>

            <?php if (!empty($error_message) && $show_otp_modal): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if (!empty($success_message) && $show_otp_modal): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="otp-form">
                <?php echo getCSRFTokenField(); ?>
                <input type="hidden" name="verify_registration_otp" value="1">
                <input type="hidden" name="pending_email" value="<?php echo htmlspecialchars($pending_email); ?>">
                <?php if ($job_id > 0 && $source === 'apply'): ?>
                <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                <input type="hidden" name="source" value="apply">
                <?php endif; ?>
                
                <div class="otp-inputs">
                    <input type="text" maxlength="1" class="otp-digit" data-index="0" autofocus>
                    <input type="text" maxlength="1" class="otp-digit" data-index="1">
                    <input type="text" maxlength="1" class="otp-digit" data-index="2">
                    <input type="text" maxlength="1" class="otp-digit" data-index="3">
                    <input type="text" maxlength="1" class="otp-digit" data-index="4">
                    <input type="text" maxlength="1" class="otp-digit" data-index="5">
                </div>
                <input type="hidden" name="otp_code" id="otp_code_hidden">
                
                <button type="submit" class="otp-submit-btn">Verify & Continue</button>
            </form>

            <div class="otp-footer">
                <p>Didn't receive the code?</p>
                <form method="POST" action="" style="display: inline;">
                    <?php echo getCSRFTokenField(); ?>
                    <input type="hidden" name="resend_registration_otp" value="1">
                    <input type="hidden" name="pending_email" value="<?php echo htmlspecialchars($pending_email); ?>">
                    <?php if ($job_id > 0 && $source === 'apply'): ?>
                    <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                    <input type="hidden" name="source" value="apply">
                    <?php endif; ?>
                    <button type="submit" class="resend-btn">Resend Code</button>
                </form>
                <button type="button" class="back-btn" onclick="closeOtpModal()">Back to Registration</button>
            </div>

            <div class="otp-timer">
                <i data-lucide="timer"></i>
                <span id="otpTimer">Code expires in 5:00</span>
            </div>
        </div>
    </div>

    <style>
        .otp-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(10, 25, 41, 0.95);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }
        .otp-modal.active { display: flex; }
        .otp-modal-content {
            background: #1e2936;
            border-radius: 20px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            text-align: center;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(58, 69, 84, 0.5);
        }
        .otp-header { margin-bottom: 2rem; }
        .otp-logo { width: 80px; height: auto; margin-bottom: 1rem; }
        .otp-header h2 { color: #ffffff; font-size: 1.5rem; margin-bottom: 0.5rem; }
        .otp-header p { color: #94a3b8; font-size: 0.9rem; }
        .otp-inputs { display: flex; gap: 0.75rem; justify-content: center; margin-bottom: 1.5rem; }
        .otp-digit {
            width: 50px; height: 60px;
            background: #2a3544;
            border: 2px solid #3a4554;
            border-radius: 10px;
            color: #ffffff;
            font-size: 1.5rem;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
        }
        .otp-digit:focus {
            outline: none;
            border-color: #0ea5e9;
            background: #2f3a4a;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.2);
        }
        .otp-submit-btn {
            width: 100%; padding: 1rem;
            background: #0ea5e9;
            border: none; border-radius: 8px;
            color: white; font-size: 1rem; font-weight: 600;
            cursor: pointer; transition: all 0.3s ease;
        }
        .otp-submit-btn:hover { background: #0284c7; }
        .otp-footer { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(58, 69, 84, 0.5); }
        .otp-footer p { color: #94a3b8; font-size: 0.9rem; margin-bottom: 0.75rem; }
        .resend-btn, .back-btn {
            background: none; border: none;
            color: #0ea5e9; font-size: 0.9rem;
            cursor: pointer; padding: 0.5rem 1rem;
            transition: color 0.3s;
        }
        .resend-btn:hover, .back-btn:hover { color: #38bdf8; }
        .back-btn { color: #94a3b8; display: block; margin: 0.5rem auto 0; }
        .otp-timer {
            margin-top: 1rem;
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
            color: #f59e0b; font-size: 0.85rem;
        }
        .otp-timer i { width: 1.1rem; height: 1.1rem; }
    </style>

    <script>
        // OTP Input handling
        const otpDigits = document.querySelectorAll('.otp-digit');
        const otpHidden = document.getElementById('otp_code_hidden');

        if (otpDigits.length > 0) {
            otpDigits.forEach((digit, index) => {
                digit.addEventListener('input', (e) => {
                    const value = e.target.value;
                    if (value.length === 1 && index < otpDigits.length - 1) {
                        otpDigits[index + 1].focus();
                    }
                    updateHiddenOTP();
                });

                digit.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !digit.value && index > 0) {
                        otpDigits[index - 1].focus();
                    }
                });

                digit.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').slice(0, 6);
                    pastedData.split('').forEach((char, i) => {
                        if (otpDigits[i]) otpDigits[i].value = char;
                    });
                    updateHiddenOTP();
                    if (pastedData.length === 6) otpDigits[5].focus();
                });
            });
        }

        function updateHiddenOTP() {
            let otp = '';
            otpDigits.forEach(digit => otp += digit.value);
            if (otpHidden) otpHidden.value = otp;
        }

        function closeOtpModal() {
            document.getElementById('otpModal').classList.remove('active');
            otpDigits.forEach(digit => digit.value = '');
            if (otpHidden) otpHidden.value = '';
        }

        <?php if ($show_otp_modal): ?>
        let timeLeft = 300;
        const timerDisplay = document.getElementById('otpTimer');
        
        const countdown = setInterval(() => {
            timeLeft--;
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerDisplay.textContent = `Code expires in ${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 0) {
                clearInterval(countdown);
                timerDisplay.textContent = 'Code expired. Please resend.';
                timerDisplay.style.color = '#ef4444';
            }
        }, 1000);

        document.querySelector('.otp-digit[data-index="0"]')?.focus();
        <?php endif; ?>
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
