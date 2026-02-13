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

// Ensure username column exists in user_accounts (check first to avoid ALTER on every load)
try {
    $colCheck = fetchSingle("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_accounts' AND COLUMN_NAME = 'username'");
    if (($colCheck['c'] ?? 0) == 0) {
        executeQuery("ALTER TABLE user_accounts ADD COLUMN username VARCHAR(50) UNIQUE AFTER employee_id");
    }
} catch (Exception $e) {
    // Column may already exist or table not ready, continue
}

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
                     WHERE (ua.company_email = ? OR ua.personal_email = ? OR ua.username = ?) AND ua.status = 'Active'",
                    [$pending_email, $pending_email, $pending_email]
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
                            header('Location: ../modals/admin/index.php');
                            break;
                        case 'HR_Staff':
                            header('Location: ../modals/hr_staff/index.php');
                            break;
                        case 'Manager':
                            header('Location: ../modals/manager/index.php');
                            break;
                        case 'Applicant':
                            header('Location: ../modals/applicant/index.php');
                            break;
                        case 'Employee':
                            header('Location: ../modals/employee/index.php');
                            break;
                        default:
                            header('Location: ../index.php');
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
            "SELECT * FROM user_accounts WHERE (company_email = ? OR personal_email = ? OR username = ?) AND status = 'Active'",
            [$pending_email, $pending_email, $pending_email]
        );
        
        if ($user) {
            $otp_email = $user['personal_email'] ?? $user['company_email'] ?? $pending_email;
            $otp_code = createOTP($otp_email, $user['phone_number'] ?? null, 'login', $user['id']);
            $user_name = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
            sendOTP($otp_email, $otp_code, $user_name, $user['phone_number'] ?? null);
            $pending_email = $otp_email;
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
                     WHERE (ua.company_email = ? OR ua.personal_email = ? OR ua.username = ?) AND ua.status = 'Active'",
                    [$username, $username, $username]
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
                    $error_message = "Account not found. Please check your credentials.";
                } elseif (!password_verify($password, $user['password_hash'] ?? $user['password'])) {
                    $error_message = "Invalid password. Please try again.";
                } else {
                    // Use the user's actual email from DB, not the login input
                    $otp_email = $user['personal_email'] ?? $user['company_email'] ?? $username;
                    
                    // Generate and send OTP
                    $otp_code = createOTP($otp_email, $user['phone_number'] ?? null, 'login', $user['id']);
                    $user_name = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
                    sendOTP($otp_email, $otp_code, $user_name, $user['phone_number'] ?? null);
                    
                    $pending_email = $otp_email;
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

            .login-screen {
            width: 100%;
            max-width: 1100px;
            display: flex;
            flex-direction: column;
        }

        .system-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #ffffff;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .system-label img {
            width: 24px;
            height: 24px;
        }

        .login-container {
            display: flex;
            background: linear-gradient(135deg, #1e3a52 0%, #2d5a7b 100%);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            min-height: 500px;
        }

        .welcome-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            background: linear-gradient(135deg, #1e3a52 0%, #2d5a7b 100%);
        }

        .welcome-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
        }

        .welcome-logo {
            width: 280px;
            height: auto;
        }

        .welcome-text {
            color: #ffffff;
            font-size: 1.1rem;
            font-weight: 500;
            text-align: center;
        }

        .login-panel {
            width: 400px;
            min-width: 400px;
            padding: 3rem 2.5rem;
            background: #1e2936;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-box {
            width: 100%;
            text-align: center;
        }

        .login-box img {
            width: 150px;
            height: auto;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .login-box h2 {
            margin-bottom: 1.75rem;
            color: #ffffff;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .login-box form {
            display: flex;
            flex-direction: column;
            gap: 0.875rem;
        }

        .login-box input {
            width: 100%;
            padding: 0.875rem 1rem;
            background: #2a3544;
            border: 2px solid #3a4554;
            border-radius: 6px;
            color: #e2e8f0;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .login-box input:focus {
            outline: none;
            border-color: #0ea5e9;
            background: #2a3544;
            box-shadow: 0 0 0 1px #0ea5e9;
        }

        .login-box input::placeholder {
            color: #8b92a0;
        }

        .password-field {
            position: relative;
        }

        .password-field input {
            padding-right: 2.5rem;
        }

        .password-icon {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 18px;
            cursor: pointer;
            user-select: none;
            transition: color 0.3s ease;
        }

        .password-icon:hover {
            color: #0ea5e9;
        }

        .login-box button {
            padding: 0.875rem;
            background: #0ea5e9;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.95rem;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .login-box button:hover {
            background: #0284c7;
        }

        .error-message {
            background: rgba(239, 68, 68, 0.15);
            color: #ff6b6b;
            padding: 0.625rem;
            border-radius: 0.375rem;
            margin-bottom: 0.75rem;
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
            font-size: 0.85rem;
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
            text-align: center;
            padding: 2rem 1.0rem;
            margin-top: 3rem;
            color: #eaecf0ff;
            font-size: 0.9rem;
        }

        .footer a {
            color: #eaecf0ff;
            text-decoration: none;
            margin: 0 0.5rem;
            transition: color 0.3s;
        }

        .footer a:hover {
            color: #739bd3ff;
        }

        @media (max-width: 990px) {
            body {
                padding: 1rem;
            }

            .login-container {
                flex-direction: column;
            }
            
            .welcome-panel {
                min-height: 300px;
                padding: 2rem;
            }
            
            .login-panel {
                width: 100%;
                min-width: unset;
                padding: 2rem 1.5rem;
            }
        }

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

        .otp-timer i {
            width: 1.1rem;
            height: 1.1rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/loading-screen.php'; ?>
    
    <div class="login-screen">
        <div class="login-container">
            <div class="welcome-panel">
                <div class="welcome-content">
                    <img src="../assets/images/slate.png" alt="SLATE Logo" class="welcome-logo">
                    <p class="welcome-text">Freight Management System</p>
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
                            <i data-lucide="eye" class="password-icon" id="togglePassword"></i>
                        </div>
                        <button type="submit">Log In</button>
                    </form>

                    <div class="signup-link">
                        Don't have an account? <a href="terms.php">Become a Partner</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            © 2025 SLATE Freight Management System. All rights reserved. &nbsp;|&nbsp;
            <a href="terms.php">Terms & Conditions</a> &nbsp;|&nbsp;
            <a href="#">Privacy Policy</a>
        </div>
    </div>

    <!-- OTP Verification Modal -->
    <div id="otpModal" class="otp-modal <?php echo $show_otp_modal ? 'active' : ''; ?>">
        <div class="otp-modal-content">
            <div class="otp-header">
                <img src="../assets/images/slate.png" alt="SLATE Logo" class="otp-logo">
                <h2>Verification Code</h2>
                <p>Enter the 6-digit code sent to <strong style="color:#0ea5e9;"><?php echo htmlspecialchars(maskEmail($pending_email)); ?></strong></p>
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
                <i data-lucide="timer"></i>
                <span id="otpTimer">Code expires in 5:00</span>
            </div>
        </div>
    </div>

    <script>
        // Password toggle using event delegation
        document.querySelectorAll('.password-field').forEach(field => {
            field.addEventListener('click', function(e) {
                if (e.target.closest('.password-icon') || e.target.closest('svg.lucide')) {
                    const input = field.querySelector('input');
                    const isPassword = input.getAttribute('type') === 'password';
                    input.setAttribute('type', isPassword ? 'text' : 'password');
                    const oldIcon = field.querySelector('svg.lucide') || field.querySelector('.password-icon');
                    const newIcon = document.createElement('i');
                    newIcon.setAttribute('data-lucide', isPassword ? 'eye-off' : 'eye');
                    newIcon.className = 'password-icon';
                    newIcon.id = 'togglePassword';
                    oldIcon.replaceWith(newIcon);
                    lucide.createIcons();
                }
            });
        });

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
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
