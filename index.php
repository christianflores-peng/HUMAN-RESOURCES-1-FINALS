<?php
session_start();

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$current_user = $_SESSION['username'] ?? 'Guest';
$current_role = $_SESSION['role'] ?? 'Guest';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SLATE - HR Management System</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Main index page styles */
        .hero-section {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-content {
            text-align: center;
            z-index: 2;
            max-width: 1200px;
            padding: 2rem;
        }
        
        .hero-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .hero-logo h1 {
            font-size: 4rem;
            font-weight: 700;
            color: #3b82f6;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        
        .truck-icon-large {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 2rem;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
            color: #cbd5e1;
            margin-bottom: 3rem;
            font-weight: 300;
        }
        
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .module-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .module-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            border-color: #3b82f6;
        }
        
        .module-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .module-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #f8fafc;
            margin-bottom: 0.5rem;
        }
        
        .module-description {
            color: #cbd5e1;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .module-btn {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .module-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }
        
        .auth-section {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 2rem;
            margin-top: 2rem;
            backdrop-filter: blur(10px);
        }
        
        .auth-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .auth-btn {
            padding: 0.875rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .auth-btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }
        
        .auth-btn-secondary {
            background: transparent;
            color: #3b82f6;
            border: 2px solid #3b82f6;
        }
        
        .auth-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }
        
        .user-info {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
            color: #6ee7b7;
        }
        
        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        
        .floating-element {
            position: absolute;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-element:nth-child(1) {
            width: 100px;
            height: 100px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .floating-element:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }
        
        .floating-element:nth-child(3) {
            width: 80px;
            height: 80px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .footer {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            color: #64748b;
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
            .hero-logo h1 {
                font-size: 2.5rem;
            }
            
            .modules-grid {
                grid-template-columns: 1fr;
            }
            
            .auth-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="hero-section">
        <!-- Floating Background Elements -->
        <div class="floating-elements">
            <div class="floating-element"></div>
            <div class="floating-element"></div>
            <div class="floating-element"></div>
        </div>
        
        <div class="hero-content">
            <!-- Logo and Title -->
            <div class="hero-logo">
                <div class="truck-icon-large">S</div>
                <h1>SLATE</h1>
            </div>
            <p class="hero-subtitle">Freight Management System</p>
            
            <?php if ($is_logged_in): ?>
                <!-- Logged in user section -->
                <div class="user-info">
                    <h3>Welcome back, <?php echo htmlspecialchars($current_user); ?>!</h3>
                    <p>Role: <?php echo htmlspecialchars($current_role); ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Main Modules Grid -->
            <div class="modules-grid">
                <!-- HR Management Module -->
                <div class="module-card">
                    <div class="module-icon">👥</div>
                    <h3 class="module-title">HR Management</h3>
                    <p class="module-description">Complete human resources management including recruitment, onboarding, and performance tracking.</p>
                    <?php if ($is_logged_in): ?>
                        <a href="pages/dashboard.php" class="module-btn">Access Dashboard</a>
                    <?php else: ?>
                        <a href="login.php" class="module-btn">Login Required</a>
                    <?php endif; ?>
                </div>
                
                <!-- Applicant Portal Module -->
                <div class="module-card">
                    <div class="module-icon">📋</div>
                    <h3 class="module-title">Applicant Portal</h3>
                    <p class="module-description">Manage job applications, track candidates, and streamline your hiring process.</p>
                    <?php if ($is_logged_in): ?>
                        <a href="applicant-portal.php" class="module-btn">View Portal</a>
                    <?php else: ?>
                        <a href="login.php" class="module-btn">Login Required</a>
                    <?php endif; ?>
                </div>
                
                <!-- Job Postings Module -->
                <div class="module-card">
                    <div class="module-icon">💼</div>
                    <h3 class="module-title">Job Postings</h3>
                    <p class="module-description">Browse and apply for available positions. Public access for job seekers.</p>
                    <a href="careers.php" class="module-btn">View Jobs</a>
                </div>
                
                <!-- Analytics Module -->
                <div class="module-card">
                    <div class="module-icon">📊</div>
                    <h3 class="module-title">Analytics</h3>
                    <p class="module-description">Track hiring metrics, performance analytics, and business intelligence.</p>
                    <?php if ($is_logged_in): ?>
                        <a href="pages/dashboard.php" class="module-btn">View Analytics</a>
                    <?php else: ?>
                        <a href="login.php" class="module-btn">Login Required</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Authentication Section -->
            <div class="auth-section">
                <?php if ($is_logged_in): ?>
                    <h3 style="color: #f8fafc; margin-bottom: 1rem;">Quick Actions</h3>
                    <div class="auth-buttons">
                        <a href="pages/dashboard.php" class="auth-btn auth-btn-primary">Dashboard</a>
                        <a href="applicant-portal.php" class="auth-btn auth-btn-primary">Applicant Portal</a>
                        <a href="careers.php" class="auth-btn auth-btn-secondary">Browse Jobs</a>
                        <a href="logout.php" class="auth-btn auth-btn-secondary">Logout</a>
                    </div>
                <?php else: ?>
                    <h3 style="color: #f8fafc; margin-bottom: 1rem;">Get Started</h3>
                    <div class="auth-buttons">
                        <a href="login.php" class="auth-btn auth-btn-primary">Login</a>
                        <a href="register.php" class="auth-btn auth-btn-secondary">Create Account</a>
                        <a href="careers.php" class="auth-btn auth-btn-secondary">Browse Jobs</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="footer">
            © 2025 SLATE Freight Management System. All rights reserved. | 
            <a href="#">Terms & Conditions</a> | 
            <a href="#">Privacy Policy</a>
        </div>
    </div>
    
    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Animate module cards on load
            const cards = document.querySelectorAll('.module-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = 'all 0.6s ease';
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 150);
            });
        });
    </script>
</body>
</html>
