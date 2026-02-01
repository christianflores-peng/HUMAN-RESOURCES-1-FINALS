<?php
require_once '../includes/session_helper.php';
require_once '../database/config.php';
require_once '../includes/otp_helper.php';

// Start secure session
startSecureSession();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../pages/dashboard.php');
    exit();
}

$coming_from_terms = isset($_GET['terms_accepted']) && $_GET['terms_accepted'] === 'true';

$error_message = '';
$success_message = '';
$show_otp_modal = false;
$pending_email = '';
$pending_phone = '';

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_general_otp'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $otp_code = trim($_POST['otp_code'] ?? '');
        $pending_email = $_POST['pending_email'] ?? '';
        
        if (empty($otp_code)) {
            $error_message = 'Please enter the verification code.';
            $show_otp_modal = true;
            $pending_phone = $_SESSION['pending_general_registration']['phone'] ?? '';
        } else {
            $result = verifyOTP($pending_email, $otp_code, 'registration');
            
            if ($result['success']) {
                $data = $_SESSION['pending_general_registration'];
                
                try {
                    $user_id = insertRecord(
                        "INSERT INTO users (username, password, role, full_name, email, company, phone, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                        [$data['username'], $data['password_hash'], 'Applicant', $data['full_name'], $data['email'], $data['company'], $data['phone']]
                    );
                    
                    if ($user_id) {
                        unset($_SESSION['pending_general_registration']);
                        $success_message = 'Registration successful! Redirecting to login...';
                        header('refresh:2;url=login.php');
                    } else {
                        $error_message = 'Registration failed. Please try again.';
                    }
                } catch (Exception $e) {
                    $error_message = 'Registration failed: ' . $e->getMessage();
                }
            } else {
                incrementOTPAttempts($pending_email, 'registration');
                $error_message = $result['message'];
                $show_otp_modal = true;
                $pending_phone = $_SESSION['pending_general_registration']['phone'] ?? '';
            }
        }
    }
}

// Handle resend OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_general_otp'])) {
    $pending_email = $_POST['pending_email'] ?? '';
    $pending_data = $_SESSION['pending_general_registration'] ?? null;
    
    if ($pending_data && !empty($pending_email)) {
        $otp_code = createOTP($pending_email, $pending_data['phone'] ?? null, 'registration');
        sendOTP($pending_email, $otp_code, $pending_data['full_name'], $pending_data['phone'] ?? null);
        $success_message = 'A new verification code has been sent.';
        $pending_phone = $pending_data['phone'] ?? '';
    }
    $show_otp_modal = true;
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_general'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $full_name = trim($_POST['full_name'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
            $error_message = 'Please fill in all required fields.';
        } elseif ($password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error_message = 'Password must be at least 6 characters long.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        } else {
            try {
                $existing_user = fetchSingle("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
                
                if ($existing_user) {
                    $error_message = 'Username or email already exists.';
                } else {
                    $_SESSION['pending_general_registration'] = [
                        'username' => $username,
                        'email' => $email,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'full_name' => $full_name,
                        'company' => $company,
                        'phone' => $phone
                    ];
                    
                    $otp_code = createOTP($email, $phone, 'registration');
                    sendOTP($email, $otp_code, $full_name, $phone);
                    
                    $pending_email = $email;
                    $pending_phone = $phone;
                    $show_otp_modal = true;
                    $success_message = 'Verification code sent to your email' . (!empty($phone) ? ' and phone' : '') . '.';
                }
            } catch (Exception $e) {
                $error_message = 'Registration failed. Please try again.';
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
    <title>Register - HR1 Management System</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block" />
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Registration page specific styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a1929 0%, #1a2942 100%);
            min-height: 100vh;
            height: 100vh;
            padding: 1rem;
            color: #f8fafc;
            overflow: hidden;
        }
        
        .register-screen {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 2rem;
        }
        
        .registration-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            height: calc(100vh - 2rem);
            display: flex;
            flex-direction: column;
        }
        
        .register-container {
            background: #1e293b;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            min-height: 600px;
        }
        
        .register-illustration {
            flex: 1;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 3rem;
        }
        
        .illustration-content {
            text-align: center;
            color: white;
            z-index: 2;
        }
        
        .illustration-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .illustration-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        
        .warehouse-illustration {
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .warehouse-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .register-form-section {
            flex: 1;
            background: #0f172a;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .slate-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .slate-logo h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #3b82f6;
        }
        
        .truck-icon {
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .register-title {
            font-size: 2rem;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 0.5rem;
        }
        
        .register-subtitle {
            color: #cbd5e1;
            font-size: 1rem;
        }
        
        .register-form {
            width: 100%;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .form-label {
            display: block;
            color: #cbd5e1;
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: #1e293b;
            border: 2px solid #334155;
            border-radius: 8px;
            color: #f8fafc;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-input::placeholder {
            color: #64748b;
        }
        
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            line-height: 1;
            color: #0ea5e9;
            transition: color 0.3s ease;
            user-select: none;
        }
        
        .password-toggle:hover {
            color: #0284c7;
        }
        
        .register-btn {
            width: 100%;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: none;
            padding: 0.875rem 1.5rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        
        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }
        
        .register-btn:active {
            transform: translateY(0);
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #cbd5e1;
        }
        
        .login-link a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
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
        
        .footer {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            color: #64748b;
            font-size: 0.9rem;
            text-align: center;
        }
        
        .footer a {
            color: #64748b;
            text-decoration: none;
            margin: 0 1rem;
        }
        
        .footer a:hover {
            color: #cbd5e1;
        }
        
        @media (max-width: 768px) {
            .register-container {
                flex-direction: column;
                max-width: 500px;
            }
            
            .register-illustration {
                min-height: 200px;
                padding: 2rem;
            }
            
            .warehouse-illustration {
                width: 200px;
                height: 200px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="register-screen">
        <div class="register-container">
            <!-- Left Section - Illustration -->
            <div class="register-illustration">
                <div class="illustration-content">
                    <div class="warehouse-illustration">
                        <div style="font-size: 4rem;">🏢</div>
                    </div>
                    <h2 class="illustration-title">Join Our Team</h2>
                    <p class="illustration-subtitle">Connect with HR professionals and advance your career</p>
                </div>
            </div>
            
            <!-- Right Section - Registration Form -->
            <div class="register-form-section">
                <div class="register-header">
                    <div class="slate-logo">
                        <div class="truck-icon">H</div>
                        <h1>HR1</h1>
                    </div>
                    <h2 class="register-title">Register</h2>
                    <p class="register-subtitle">Create your account to get started</p>
                </div>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                
                <form class="register-form" method="POST" action="">
                    <?php echo getCSRFTokenField(); ?>
                    <input type="hidden" name="register_general" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" class="form-input" 
                                   placeholder="Enter your full name" required 
                                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="username">Username *</label>
                            <input type="text" id="username" name="username" class="form-input" 
                                   placeholder="Choose a username" required 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-input" 
                               placeholder="Enter your email address" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="password">Password *</label>
                            <div class="password-container">
                                <input type="password" id="password" name="password" class="form-input" 
                                       placeholder="Create a password" required>
                                <button type="button" class="password-toggle material-symbols-outlined" onclick="togglePassword('password')">visibility</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirm Password *</label>
                            <div class="password-container">
                                <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                                       placeholder="Confirm your password" required>
                                <button type="button" class="password-toggle material-symbols-outlined" onclick="togglePassword('confirm_password')">visibility</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="company">Company</label>
                            <input type="text" id="company" name="company" class="form-input" 
                                   placeholder="Your company name" 
                                   value="<?php echo htmlspecialchars($_POST['company'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-input" 
                                   placeholder="Your phone number" 
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <button type="submit" class="register-btn">Create Account</button>
                </form>
                
                <div class="login-link">
                    Already have an account? <a href="login.php">Sign In</a>
                </div>
            </div>
        </div>
        
        <div class="footer">
            © 2025 HR1 Management System. All rights reserved. | 
            <a href="#">Terms & Conditions</a> | 
            <a href="#">Privacy Policy</a>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const toggle = field.nextElementSibling;
            
            if (field.type === 'password') {
                field.type = 'text';
                toggle.textContent = 'visibility_off';
            } else {
                field.type = 'password';
                toggle.textContent = 'visibility';
            }
        }
        
        // Form validation
        document.querySelector('.register-form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>

    <!-- OTP Verification Modal -->
    <div id="otpModal" class="otp-modal <?php echo $show_otp_modal ? 'active' : ''; ?>">
        <div class="otp-modal-content">
            <div class="otp-header">
                <h2>Verify Your Account</h2>
                <p>Enter the 6-digit code sent to your email<?php echo !empty($pending_phone) ? ' and phone' : ''; ?></p>
            </div>

            <?php if (!empty($error_message) && $show_otp_modal): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if (!empty($success_message) && $show_otp_modal): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="otp-form">
                <?php echo getCSRFTokenField(); ?>
                <input type="hidden" name="verify_general_otp" value="1">
                <input type="hidden" name="pending_email" value="<?php echo htmlspecialchars($pending_email); ?>">
                
                <div class="otp-inputs">
                    <input type="text" maxlength="1" class="otp-digit" data-index="0" autofocus>
                    <input type="text" maxlength="1" class="otp-digit" data-index="1">
                    <input type="text" maxlength="1" class="otp-digit" data-index="2">
                    <input type="text" maxlength="1" class="otp-digit" data-index="3">
                    <input type="text" maxlength="1" class="otp-digit" data-index="4">
                    <input type="text" maxlength="1" class="otp-digit" data-index="5">
                </div>
                <input type="hidden" name="otp_code" id="otp_code_hidden">
                
                <button type="submit" class="otp-submit-btn">Verify & Complete</button>
            </form>

            <div class="otp-footer">
                <p>Didn't receive the code?</p>
                <form method="POST" action="" style="display: inline;">
                    <?php echo getCSRFTokenField(); ?>
                    <input type="hidden" name="resend_general_otp" value="1">
                    <input type="hidden" name="pending_email" value="<?php echo htmlspecialchars($pending_email); ?>">
                    <button type="submit" class="resend-btn">Resend Code</button>
                </form>
                <button type="button" class="back-btn" onclick="closeOtpModal()">Back to Registration</button>
            </div>

            <div class="otp-timer">
                <span class="material-symbols-outlined">timer</span>
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
            border-color: #3b82f6;
            background: #2f3a4a;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        .otp-submit-btn {
            width: 100%; padding: 1rem;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border: none; border-radius: 8px;
            color: white; font-size: 1rem; font-weight: 600;
            cursor: pointer; transition: all 0.3s ease;
        }
        .otp-submit-btn:hover { background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); }
        .otp-footer { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(58, 69, 84, 0.5); }
        .otp-footer p { color: #94a3b8; font-size: 0.9rem; margin-bottom: 0.75rem; }
        .resend-btn, .back-btn {
            background: none; border: none;
            color: #3b82f6; font-size: 0.9rem;
            cursor: pointer; padding: 0.5rem 1rem;
            transition: color 0.3s;
        }
        .resend-btn:hover, .back-btn:hover { color: #60a5fa; }
        .back-btn { color: #94a3b8; display: block; margin: 0.5rem auto 0; }
        .otp-timer {
            margin-top: 1rem;
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
            color: #f59e0b; font-size: 0.85rem;
        }
        .otp-timer .material-symbols-outlined { font-size: 1.1rem; }
    </style>

    <script>
        const otpDigits = document.querySelectorAll('.otp-digit');
        const otpHidden = document.getElementById('otp_code_hidden');

        if (otpDigits.length > 0) {
            otpDigits.forEach((digit, index) => {
                digit.addEventListener('input', (e) => {
                    if (e.target.value.length === 1 && index < otpDigits.length - 1) {
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
    </script>
</body>
</html>
