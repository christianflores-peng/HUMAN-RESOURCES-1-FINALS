<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../pages/dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../database/config.php';
    
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $employee_id = trim($_POST['employee_id'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    
    // Handle file upload
    $verification_doc = '';
    if (isset($_FILES['verification_doc']) && $_FILES['verification_doc']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/verification/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['verification_doc']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png'];
        
        if (in_array($file_ext, $allowed_exts)) {
            $verification_doc = uniqid() . '_' . basename($_FILES['verification_doc']['name']);
            move_uploaded_file($_FILES['verification_doc']['tmp_name'], $upload_dir . $verification_doc);
        }
    }
    
    // Validation
    if (empty($full_name) || empty($email) || empty($username) || empty($password)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } else {
        try {
            // Check in user_accounts table (primary) and users table (legacy)
            $existing_user = fetchSingle("SELECT id FROM user_accounts WHERE company_email = ? OR personal_email = ?", [$email, $email]);
            if (!$existing_user) {
                $existing_user = fetchSingle("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
            }
            
            if ($existing_user) {
                $error_message = 'Username or email already exists.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Parse full name into first and last name
                $name_parts = explode(' ', $full_name, 2);
                $first_name = $name_parts[0];
                $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
                
                // Get department_id from departments table if department is selected
                $department_id = null;
                if (!empty($department)) {
                    $dept = fetchSingle("SELECT id FROM departments WHERE department_code = ? OR department_name LIKE ?", [$department, "%$department%"]);
                    if ($dept) {
                        $department_id = $dept['id'];
                    }
                }
                
                // Insert into user_accounts table with Employee role (role_id: 7)
                $user_id = insertRecord(
                    "INSERT INTO user_accounts (first_name, last_name, personal_email, phone, password_hash, role_id, department_id, status, created_at) 
                     VALUES (?, ?, ?, ?, ?, 7, ?, 'Pending', NOW())",
                    [$first_name, $last_name, $email, $phone, $hashed_password, $department_id]
                );
                
                if ($user_id) {
                    $success_message = 'Registration successful! Redirecting to login...';
                    header('refresh:2;url=login.php');
                } else {
                    $error_message = 'Registration failed. Please try again.';
                }
            }
        } catch (Exception $e) {
            $error_message = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Registration - HR1</title>
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
            margin-bottom: 0.2rem;
            margin-top: 0.25rem;
        }

        .page-title h2 {
            font-size: 0.95rem;
            color: #ffffff;
            font-weight: 600;
        }

        .page-subtitle {
            text-align: center;
            color: #94a3b8;
            margin-bottom: 0.6rem;
            font-size: 0.7rem;
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
            gap: 0.6rem;
        }

        .form-section {
            background: rgba(42, 53, 68, 0.5);
            border: 1px solid rgba(58, 69, 84, 0.5);
            border-radius: 6px;
            padding: 0.65rem;
        }

        .form-section.full-width {
            grid-column: 1 / -1;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            margin-bottom: 0.65rem;
            padding-bottom: 0;
            border-bottom: none;
        }

        .section-icon {
            color: #0ea5e9;
            font-size: 0.95rem;
        }

        .section-title {
            font-size: 0.85rem;
            color: #0ea5e9;
            font-weight: 500;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.6rem;
            margin-bottom: 0.6rem;
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

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 0.5rem 0.75rem;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(58, 69, 84, 0.6);
            border-radius: 4px;
            color: #e2e8f0;
            font-size: 0.8rem;
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
            min-height: 55px;
        }

        .password-field {
            position: relative;
        }

        .password-field input {
            padding-right: 3rem;
        }

        .password-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #0ea5e9;
            font-size: 20px;
            pointer-events: none;
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
            font-size: 0.9rem;
        }

        .btn-primary:hover {
            background: #0284c7;
            transform: translateY(-1px);
        }

        .footer {
            text-align: center;
            color: #94a3b8;
            font-size: 0.75rem;
            padding: 0.5rem;
        }

        .login-link {
            text-align: center;
            font-size: 0.7rem;
            color: #94a3b8;
            margin-top: 0.75rem;
        }

        .login-link a {
            color: #0ea5e9;
            text-decoration: none;
            font-weight: 500;
        }

        @media (max-width: 968px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php $logo_path = '../assets/images/slate.png'; include '../includes/loading-screen.php'; ?>
    
    <div class="registration-wrapper">
        <!-- Header -->
        <div class="header">
            <div class="logo-section">
                <img src="../assets/images/slate.png" alt="SLATE Logo">
            </div>
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
        </div>

        <!-- Content -->
        <div class="content-container">
            <div class="page-title">
                <h2>Employee Registration</h2>
            </div>
            <p class="page-subtitle">Please provide your personal information and employment details</p>

            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <!-- Personal Information -->
                    <div class="form-section">
                        <div class="section-header">
                            <span class="material-symbols-outlined section-icon">person</span>
                            <h3 class="section-title">Personal Information</h3>
                        </div>

                        <div class="form-row" style="margin-bottom: 0.5rem;">
                            <div class="form-group" style="margin-bottom: 0.5rem;">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="full_name" class="form-input" placeholder="Enter your full name" required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0.5rem;">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="email" class="form-input" placeholder="Enter email address" required>
                            </div>
                        </div>

                        <div class="form-row" style="margin-bottom: 0.5rem;">
                            <div class="form-group" style="margin-bottom: 0.5rem;">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-input" placeholder="Enter phone number">
                            </div>
                            <div class="form-group" style="margin-bottom: 0.5rem;">
                                <label class="form-label">Username *</label>
                                <input type="text" name="username" class="form-input" placeholder="Enter username" required>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 0.5rem;">
                            <label class="form-label">Password *</label>
                            <div class="password-field">
                                <input type="password" name="password" class="form-input" placeholder="Enter password" required>
                                <span class="material-symbols-outlined password-icon">visibility</span>
                            </div>
                        </div>

                        <div class="form-group full-width" style="margin-bottom: 0;">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-textarea" placeholder="Enter your complete address"></textarea>
                        </div>
                    </div>

                    <!-- Employment Details -->
                    <div class="form-section">
                        <div class="section-header">
                            <span class="material-symbols-outlined section-icon">badge</span>
                            <h3 class="section-title">Employment Details</h3>
                        </div>

                        <div class="form-row" style="margin-bottom: 0.5rem;">
                            <div class="form-group" style="margin-bottom: 0.5rem;">
                                <label class="form-label">Employee ID</label>
                                <input type="text" name="employee_id" class="form-input" placeholder="Enter employee ID">
                            </div>
                            <div class="form-group" style="margin-bottom: 0.5rem;">
                                <label class="form-label">Department</label>
                                <select name="department" class="form-select">
                                    <option value="">Select Department</option>
                                    <option value="human_resources">Human Resources</option>
                                    <option value="finance">Finance</option>
                                    <option value="operations">Operations</option>
                                    <option value="marketing">Marketing</option>
                                    <option value="it">Information Technology</option>
                                    <option value="sales">Sales</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row" style="margin-bottom: 0.5rem;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Position</label>
                                <input type="text" name="position" class="form-input" placeholder="Enter your position">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-input">
                            </div>
                        </div>

                        <!-- Verification Document (inside Employment Details border) -->
                        <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgba(58, 69, 84, 0.5);">
                            <div class="section-header" style="margin-bottom: 0.4rem;">
                                <span class="material-symbols-outlined section-icon">description</span>
                                <h3 class="section-title">Verification Document</h3>
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" style="margin-bottom: 0.25rem;">Upload Verification Document</label>
                                <div class="file-upload" onclick="document.getElementById('verification_doc').click()" style="padding: 0.5rem;">
                                    <input type="file" id="verification_doc" name="verification_doc" accept=".pdf,.jpg,.jpeg,.png" onchange="updateFileName(this)">
                                    <div class="file-upload-label" id="file-label">
                                        <span class="material-symbols-outlined" style="font-size: 1.2rem; color: #0ea5e9;">upload_file</span>
                                        <p style="margin-top: 0.2rem; margin-bottom: 0.1rem; font-size: 0.7rem;">Choose File</p>
                                        <p style="font-size: 0.6rem; color: #64748b; margin: 0;">No file chosen</p>
                                    </div>
                                </div>
                                <p class="file-info" style="margin-top: 0.2rem; margin-bottom: 0;">Accepted formats: PDF, JPG, JPEG, PNG (Required)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='register-portal.php?terms_accepted=true'">
                        <span class="material-symbols-outlined">arrow_back</span>
                        Previous
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-outlined">check_circle</span>
                        Register
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
