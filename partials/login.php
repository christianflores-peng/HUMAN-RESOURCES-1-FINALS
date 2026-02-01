<?php
require_once '../includes/session_helper.php';
require_once '../database/config.php';
require_once '../includes/rbac_helper.php';
require_once '../includes/email_generator.php';
require_once '../includes/otp_helper.php';

// Start secure session
startSecureSession();

$error_message = '';
$success_message = '';
$show_otp_modal = false;
$pending_email = '';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $otp_code = trim($_POST['otp_code'] ?? '');
        $pending_email = $_POST['pending_email'] ?? '';
        
        if (empty($otp_code)) {
            $error_message = 'Please enter the verification code.';
            $show_otp_modal = true;
        } else {
            $result = verifyOTP($pending_email, $otp_code, 'login');
            
            if ($result['success']) {
                // Get user data and complete login
                $user = fetchSingle(
                    "SELECT ua.*, r.role_name, r.role_type, r.access_level 
                     FROM user_accounts ua 
                     LEFT JOIN roles r ON ua.role_id = r.id 
                     WHERE (ua.company_email = ? OR ua.personal_email = ?) AND ua.status = 'Active'",
                    [$pending_email, $pending_email]
                );
                
                if (!$user) {
                    $user = fetchSingle(
                        "SELECT id, username, password, role, created_at FROM users WHERE username = ?",
                        [$pending_email]
                    );
                    if ($user) {
                        $user['role_type'] = 'Employee';
                        $user['role_name'] = $user['role'];
                    }
                }
                
                if ($user) {
                    // Regenerate session ID
                    regenerateSessionAfterLogin();
                    
                    // Set session variables
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['username'] = $user['company_email'] ?? $user['username'];
                    $_SESSION['first_name'] = $user['first_name'] ?? '';
                    $_SESSION['last_name'] = $user['last_name'] ?? '';
                    $_SESSION['full_name'] = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
                    $_SESSION['personal_email'] = $user['personal_email'] ?? '';
                    $_SESSION['role'] = $user['role_name'] ?? $user['role'];
                    $_SESSION['role_type'] = $user['role_type'] ?? 'Employee';
                    $_SESSION['role_id'] = $user['role_id'] ?? null;
                    $_SESSION['department_id'] = $user['department_id'] ?? null;
                    $_SESSION['login_time'] = time();
                    $_SESSION['last_activity'] = time();
                    
                    // Update last login
                    try {
                        updateRecord("UPDATE user_accounts SET last_login = NOW() WHERE id = ?", [$user['id']]);
                    } catch (Exception $e) {}
                    
                    // Log login action
                    try {
                        logAuditAction($user['id'], 'LOGIN', 'user_accounts', $user['id'], null, null, 'User logged in with OTP verification');
                    } catch (Exception $e) {}
                    
                    // Redirect based on role type (matches directory structure)
                    $roleType = $user['role_type'] ?? 'Employee';
                    
                    switch ($roleType) {
                        case 'Admin':
                            header('Location: ../admin/dashboard.php');
                            break;
                        case 'HR_Staff':
                            header('Location: ../pages/hr-recruitment-dashboard.php');
                            break;
                        case 'Manager':
                            header('Location: ../pages/manager-dashboard.php');
                            break;
                        case 'Applicant':
                            header('Location: ../modals/applicant/dashboard.php');
                            break;
                        case 'Employee':
                            header('Location: ../modals/employee/dashboard.php');
                            break;
                        default:
                            header('Location: ../pages/dashboard.php');
                            break;
                    }
                    exit();
                }
            } else {
                incrementOTPAttempts($pending_email, 'login');
                $error_message = $result['message'];
                $show_otp_modal = true;
            }
        }
    }
}

// Handle resend OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_otp'])) {
    $pending_email = $_POST['pending_email'] ?? '';
    
    if (!empty($pending_email)) {
        $user = fetchSingle(
            "SELECT * FROM user_accounts WHERE (company_email = ? OR personal_email = ?) AND status = 'Active'",
            [$pending_email, $pending_email]
        );
        
        if ($user) {
            $otp_code = createOTP($pending_email, $user['phone_number'] ?? null, 'login', $user['id']);
            $user_name = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
            sendOTP($pending_email, $otp_code, $user_name, $user['phone_number'] ?? null);
            $success_message = 'A new verification code has been sent.';
        }
    }
    $show_otp_modal = true;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $username = strtolower(trim($_POST['username'] ?? ''));
        $password = trim($_POST['password'] ?? '');
    
        if (empty($username) || empty($password)) {
            $error_message = "Please enter both email and password.";
        } else {
            try {
                $user = fetchSingle(
                    "SELECT ua.*, r.role_name, r.role_type, r.access_level 
                     FROM user_accounts ua 
                     LEFT JOIN roles r ON ua.role_id = r.id 
                     WHERE (ua.company_email = ? OR ua.personal_email = ?) AND ua.status = 'Active'",
                    [$username, $username]
                );
                
                if (!$user) {
                    $user = fetchSingle(
                        "SELECT id, username, password, role, created_at FROM users WHERE username = ?",
                        [$username]
                    );
                    if ($user) {
                        $user['role_type'] = 'Employee';
                        $user['role_name'] = $user['role'];
                    }
                }

                if (!$user) {
                    $error_message = "Email not found. Please check your credentials.";
                } elseif (!password_verify($password, $user['password_hash'] ?? $user['password'])) {
                    $error_message = "Invalid password. Please try again.";
                } else {
                    // Generate and send OTP
                    $otp_code = createOTP($username, $user['phone_number'] ?? null, 'login', $user['id']);
                    $user_name = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
                    sendOTP($username, $otp_code, $user_name, $user['phone_number'] ?? null);
                    
                    $pending_email = $username;
                    $show_otp_modal = true;
                    $success_message = 'Verification code sent to your email' . (!empty($user['phone_number']) ? ' and phone' : '') . '.';
                }
            } catch (Exception $e) {
                $error_message = "Login failed: " . $e->getMessage();
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
    <title>Login - HR1 Management System</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            overflow: hidden;
        }

        .login-screen {
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0a1929;
        }

        .main-container {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            width: 100%;
        }

        .login-container {
            background: #1e2936;
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            width: 100%;
            max-width: 1000px;
            max-height: calc(100vh - 2rem);
            display: flex;
        }

        .welcome-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            background: linear-gradient(135deg, #2d5f7f 0%, #3a7a9e 50%, #4a9fd8 100%);
            position: relative;
        }

        .welcome-panel .system-label {
            position: absolute;
            top: 2rem;
            left: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #ffffff;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .welcome-panel .system-label::before {
            content: '🏢';
            font-size: 1.2rem;
        }

        .welcome-panel .illustration {
            width: 100%;
            max-width: 450px;
            height: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .welcome-panel .illustration-placeholder {
            width: 100%;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: rgba(255, 255, 255, 0.3);
        }

        .login-panel {
            width: 400px;
            padding: 3rem 2.5rem;
            background: #1a2332;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-box {
            width: 100%;
            text-align: center;
        }

        .login-box img {
            width: 6.25rem;
            height: auto;
            margin-bottom: 1.25rem;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
        }

        .login-box h2 {
            margin-bottom: 2rem;
            color: #ffffff;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .login-box form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .login-box input {
            width: 100%;
            padding: 0.875rem 1rem;
            background: #2a3544;
            border: 1px solid #3a4554;
            border-radius: 6px;
            color: #e2e8f0;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .login-box input:focus {
            outline: none;
            border-color: #0ea5e9;
            background: #2f3a4a;
        }

        .login-box input::placeholder {
            color: #8b92a0;
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
            cursor: pointer;
            user-select: none;
            transition: color 0.3s ease;
        }

        .password-icon:hover {
            color: #0284c7;
        }

        .login-box button {
            padding: 0.875rem;
            background: #0ea5e9;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: capitalize;
        }

        .login-box button:hover {
            background: #0284c7;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
        }

        .error-message {
            background: rgba(239, 68, 68, 0.15);
            color: #ff6b6b;
            padding: 0.75rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(239, 68, 68, 0.3);
            text-align: center;
            font-size: 0.9rem;
            backdrop-filter: blur(10px);
        }

        .success-message {
            background: rgba(16, 185, 129, 0.15);
            color: #4ade80;
            padding: 0.75rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(16, 185, 129, 0.3);
            text-align: center;
            font-size: 0.9rem;
            backdrop-filter: blur(10px);
        }

        .signup-link {
            margin-top: 1.5rem;
            text-align: center;
            color: #cbd5e1;
            font-size: 0.9rem;
        }

        .signup-link a {
            color: #0ea5e9;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .signup-link a:hover {
            color: #38bdf8;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            padding: 1rem;
            color: #64748b;
            font-size: 0.85rem;
            background: rgba(10, 25, 41, 0.8);
            backdrop-filter: blur(10px);
        }

        .footer a {
            color: #64748b;
            text-decoration: none;
            margin: 0 0.5rem;
            transition: color 0.3s;
        }

        .footer a:hover {
            color: #94a3b8;
        }

        @media (max-width: 48rem) {
            .login-container {
                flex-direction: column;
                height: auto;
            }
            
            .welcome-panel, .login-panel {
                width: 100%;
            }
            
            .welcome-panel {
                padding: 2rem 1.5rem;
            }
            
            .welcome-panel h1 {
                font-size: 1.75rem;
            }
            
            .login-panel {
                padding: 2.5rem 1.5rem;
            }
        }

        @media (max-width: 30rem) {
            .main-container {
                padding: 1rem;
            }
            
            .welcome-panel h1 {
                font-size: 1.5rem;
            }
            
            .login-box h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/loading-screen.php'; ?>
    
    <div class="login-screen">
        <div class="main-container">
            <div class="login-container">
                <div class="welcome-panel">
                    <div class="system-label">Freight Management System</div>
                    <div class="illustration">
                        <div class="illustration-placeholder">🏭</div>
                    </div>
                </div>
                <div class="login-panel">
                    <div class="login-box">
                        <img src="../assets/images/slate.png" alt="SLATE Logo">
                        <h2>Login</h2>
                        
                        <?php if (!empty($error_message)): ?>
                            <div class="error-message">
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success_message)): ?>
                            <div class="success-message">
                                <?php echo htmlspecialchars($success_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" class="show-loading">
                            <?php echo getCSRFTokenField(); ?>
                            <input type="hidden" name="login" value="1">
                            <input type="text" id="username" name="username" placeholder="Email Address" required 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                            <div class="password-field">
                                <input type="password" id="password" name="password" placeholder="Password" required>
                                <span class="material-symbols-outlined password-icon" id="togglePassword" onclick="togglePasswordVisibility()">visibility</span>
                            </div>
                            <button type="submit">Log In</button>
                        </form>

                        <div class="signup-link">
                            Don't have an account? <a href="terms.php">Become a Partner</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- OTP Verification Modal -->
    <div id="otpModal" class="otp-modal <?php echo $show_otp_modal ? 'active' : ''; ?>">
        <div class="otp-modal-content">
            <div class="otp-header">
                <img src="../assets/images/slate.png" alt="SLATE Logo" class="otp-logo">
                <h2>Verification Code</h2>
                <p>Enter the 6-digit code sent to your email<?php echo !empty($user['phone_number'] ?? '') ? ' and phone' : ''; ?></p>
            </div>

            <?php if (!empty($error_message) && $show_otp_modal): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if (!empty($success_message) && $show_otp_modal): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="otp-form">
                <?php echo getCSRFTokenField(); ?>
                <input type="hidden" name="verify_otp" value="1">
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
                
                <button type="submit" class="otp-submit-btn">Verify Code</button>
            </form>

            <div class="otp-footer">
                <p>Didn't receive the code?</p>
                <form method="POST" action="" style="display: inline;">
                    <?php echo getCSRFTokenField(); ?>
                    <input type="hidden" name="resend_otp" value="1">
                    <input type="hidden" name="pending_email" value="<?php echo htmlspecialchars($pending_email); ?>">
                    <button type="submit" class="resend-btn">Resend Code</button>
                </form>
                <button type="button" class="back-btn" onclick="closeOtpModal()">Back to Login</button>
            </div>

            <div class="otp-timer">
                <span class="material-symbols-outlined">timer</span>
                <span id="otpTimer">Code expires in 5:00</span>
            </div>
        </div>
    </div>
    
    <div class="footer">
        © 2025 SLATE Freight Management System. All rights reserved.
        <a href="#">Terms & Conditions</a> | <a href="#">Privacy Policy</a>
    </div>

    <style>
        /* OTP Modal Styles */
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

        .otp-modal.active {
            display: flex;
        }

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

        .otp-header {
            margin-bottom: 2rem;
        }

        .otp-logo {
            width: 80px;
            height: auto;
            margin-bottom: 1rem;
        }

        .otp-header h2 {
            color: #ffffff;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .otp-header p {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .otp-inputs {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .otp-digit {
            width: 50px;
            height: 60px;
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
            width: 100%;
            padding: 1rem;
            background: #0ea5e9;
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .otp-submit-btn:hover {
            background: #0284c7;
        }

        .otp-footer {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(58, 69, 84, 0.5);
        }

        .otp-footer p {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }

        .resend-btn, .back-btn {
            background: none;
            border: none;
            color: #0ea5e9;
            font-size: 0.9rem;
            cursor: pointer;
            padding: 0.5rem 1rem;
            transition: color 0.3s;
        }

        .resend-btn:hover, .back-btn:hover {
            color: #38bdf8;
        }

        .back-btn {
            color: #94a3b8;
            display: block;
            margin: 0.5rem auto 0;
        }

        .otp-timer {
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: #f59e0b;
            font-size: 0.85rem;
        }

        .otp-timer .material-symbols-outlined {
            font-size: 1.1rem;
        }
    </style>

    <script>
        function togglePasswordVisibility() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePassword');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.textContent = 'visibility_off';
            } else {
                passwordField.type = 'password';
                toggleIcon.textContent = 'visibility';
            }
        }

        // OTP Input handling
        const otpDigits = document.querySelectorAll('.otp-digit');
        const otpHidden = document.getElementById('otp_code_hidden');

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
                    if (otpDigits[i]) {
                        otpDigits[i].value = char;
                    }
                });
                updateHiddenOTP();
                if (pastedData.length === 6) {
                    otpDigits[5].focus();
                }
            });
        });

        function updateHiddenOTP() {
            let otp = '';
            otpDigits.forEach(digit => {
                otp += digit.value;
            });
            otpHidden.value = otp;
        }

        function closeOtpModal() {
            document.getElementById('otpModal').classList.remove('active');
            // Clear OTP inputs
            otpDigits.forEach(digit => digit.value = '');
            otpHidden.value = '';
        }

        // OTP Timer
        <?php if ($show_otp_modal): ?>
        let timeLeft = 300; // 5 minutes
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

        // Auto focus first OTP digit when modal opens
        document.querySelector('.otp-digit[data-index="0"]')?.focus();
        <?php endif; ?>
    </script>
</body>
</html>
