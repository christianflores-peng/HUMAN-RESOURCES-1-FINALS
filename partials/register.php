<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../pages/dashboard.php');
    exit();
}

// Check if user accepted terms (from sessionStorage via JavaScript or direct access)
// Note: This is a basic check. For production, consider server-side session tracking
$coming_from_terms = isset($_GET['terms_accepted']) && $_GET['terms_accepted'] === 'true';

$error_message = '';
$success_message = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error_message = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // Check if username or email already exists
        require_once '../database/config.php';
        
        try {
            $existing_user = fetchSingle("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
            
            if ($existing_user) {
                $error_message = 'Username or email already exists.';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user (default role: Applicant for public registration)
                $user_id = insertRecord(
                    "INSERT INTO users (username, password, role, full_name, email, company, phone, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                    [$username, $hashed_password, 'Applicant', $full_name, $email, $company, $phone]
                );
                
                if ($user_id) {
                    $success_message = 'Registration successful! You can now login.';
                    // Redirect to login after 3 seconds
                    header('refresh:3;url=login.php');
                } else {
                    $error_message = 'Registration failed. Please try again.';
                }
            }
        } catch (Exception $e) {
            $error_message = 'Registration failed. Please try again.';
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
</body>
</html>
