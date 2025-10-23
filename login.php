<?php
session_start();

// Hardcoded credentials - Change these as needed
$valid_users = [
    'admin' => [
        'password' => 'admin123',
        'role' => 'Administrator',
        'full_name' => 'System Administrator',
        'user_id' => 1
    ],
    'hr_manager' => [
        'password' => 'hr123',
        'role' => 'HR Manager',
        'full_name' => 'HR Manager',
        'user_id' => 2
    ],
    'recruiter' => [
        'password' => 'recruit123',
        'role' => 'Recruiter',
        'full_name' => 'Senior Recruiter',
        'user_id' => 3
    ],
    'employee' => [
        'password' => 'emp123',
        'role' => 'Employee',
        'full_name' => 'Employee User',
        'user_id' => 4
    ],
    'john_doe' => [
        'password' => 'john123',
        'role' => 'Employee',
        'full_name' => 'John Doe',
        'user_id' => 5
    ],
    'jane_smith' => [
        'password' => 'jane123',
        'role' => 'HR Manager',
        'full_name' => 'Jane Smith',
        'user_id' => 6
    ]
];

$error_message = '';
$success_message = '';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php');
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        // Check credentials
        if (array_key_exists($username, $valid_users)) {
            $user_data = $valid_users[$username];
            
            if ($password === $user_data['password']) {
                // Login successful - Set session variables
                $_SESSION['user_id'] = $user_data['user_id'];
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $user_data['full_name'];
                $_SESSION['role'] = $user_data['role'];
                $_SESSION['login_time'] = time();
                
                // Redirect to dashboard
                header('Location: pages/dashboard.php');
                exit();
            } else {
                $error_message = "Invalid password. Please try again.";
            }
        } else {
            $error_message = "Username not found. Please check your credentials.";
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f8fafc;
            overflow: hidden;
        }

        .login-screen {
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
        }

        .main-container {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-container {
            width: 100%;
            max-width: 75rem;
            display: flex;
            background: rgba(31, 42, 56, 0.8);
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 0.625rem 1.875rem rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .welcome-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem;
            background: linear-gradient(135deg, rgba(0, 114, 255, 0.2), rgba(0, 198, 255, 0.2));
            position: relative;
        }

        .welcome-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .welcome-panel h1 {
            font-size: 2.25rem;
            font-weight: 700;
            color: #ffffff;
            text-shadow: 0.125rem 0.125rem 0.5rem rgba(0, 0, 0, 0.6);
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .login-panel {
            width: 25rem;
            padding: 3.75rem 2.5rem;
            background: rgba(22, 33, 49, 0.95);
            backdrop-filter: blur(10px);
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
            margin-bottom: 1.5625rem;
            color: #ffffff;
            font-size: 1.75rem;
            font-weight: 600;
        }

        .login-box form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .login-box input {
            width: 100%;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.375rem;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .login-box input:focus {
            outline: none;
            border-color: #00c6ff;
            box-shadow: 0 0 0 0.125rem rgba(0, 198, 255, 0.2);
            background: rgba(255, 255, 255, 0.15);
        }

        .login-box input::placeholder {
            color: rgba(160, 160, 160, 0.8);
        }

        .login-box button {
            padding: 0.75rem;
            background: linear-gradient(to right, #0072ff, #00c6ff);
            border: none;
            border-radius: 0.375rem;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .login-box button:hover {
            background: linear-gradient(to right, #0052cc, #009ee3);
            transform: translateY(-0.125rem);
            box-shadow: 0 0.3125rem 0.9375rem rgba(0, 0, 0, 0.2);
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

        .demo-credentials {
            background: rgba(59, 130, 246, 0.1);
            padding: 1rem;
            border-radius: 0.375rem;
            margin-top: 1.5rem;
            border: 1px solid rgba(59, 130, 246, 0.2);
            backdrop-filter: blur(10px);
        }

        .demo-credentials h4 {
            color: #60a5fa;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .demo-list {
            font-size: 0.8rem;
            color: rgba(203, 213, 225, 0.8);
            line-height: 1.4;
        }

        .demo-item {
            margin-bottom: 0.3rem;
        }

        .demo-item strong {
            color: #e2e8f0;
        }

        .public-links {
            margin-top: 1.5rem;
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .public-links h4 {
            color: #e2e8f0;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .public-links a {
            display: inline-block;
            color: #60a5fa;
            text-decoration: none;
            font-size: 0.9rem;
            margin: 0.2rem 0.5rem;
            transition: color 0.3s;
        }

        .public-links a:hover {
            color: #93c5fd;
            text-decoration: underline;
        }

        @media (max-width: 48rem) {
            .login-container {
                flex-direction: column;
            }
            
            .welcome-panel, .login-panel {
                width: 100%;
            }
            
            .welcome-panel {
                padding: 1.875rem 1.25rem;
            }
            
            .welcome-panel h1 {
                font-size: 1.75rem;
            }
            
            .login-panel {
                padding: 2.5rem 1.25rem;
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
    <div class="login-screen">
        <div class="main-container">
            <div class="login-container">
                <div class="welcome-panel">
                    <h1>HR MANAGEMENT SYSTEM</h1>
                </div>
                <div class="login-panel">
                    <div class="login-box">
                        <img src="assets/images/slate.png" alt="HR1 Logo">
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
                        
                        <form method="POST" action="">
                            <input type="text" id="username" name="username" placeholder="Username" required 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                            <input type="password" id="password" name="password" placeholder="Password" required>
                            <button type="submit">Log In</button>
                            
                            <div style="margin-top: 1rem; text-align: center;">
                                <small style="color: rgba(160, 160, 160, 0.8); font-size: 0.8rem;">
                                    Demo: admin/admin123 | hr_manager/hr123
                                </small>
                            </div>
                            
                            <div style="margin-top: 1.5rem; text-align: center;">
                                <p style="color: #cbd5e1; font-size: 0.9rem;">
                                    Don't have an account? <a href="register.php" style="color: #3b82f6; text-decoration: none; font-weight: 500;">Create Account</a>
                                </p>
                            </div>
                        </form>

                        <!-- Demo Credentials -->
                        <div class="demo-credentials">
                            <h4>🔐 Demo Credentials</h4>
                            <div class="demo-list">
                                <div class="demo-item"><strong>admin</strong> / admin123 (Administrator)</div>
                                <div class="demo-item"><strong>hr_manager</strong> / hr123 (HR Manager)</div>
                                <div class="demo-item"><strong>recruiter</strong> / recruit123 (Recruiter)</div>
                                <div class="demo-item"><strong>employee</strong> / emp123 (Employee)</div>
                                <div class="demo-item"><strong>john_doe</strong> / john123 (Employee)</div>
                                <div class="demo-item"><strong>jane_smith</strong> / jane123 (HR Manager)</div>
                            </div>
                        </div>

                        <!-- Public Access Links -->
                        <div class="public-links">
                            <h4>🌐 Public Access</h4>
                            <a href="careers.php" target="_blank">Browse Jobs</a>
                            <a href="careers.php" target="_blank">Apply for Positions</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
