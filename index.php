<?php
session_start();

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$current_user = $_SESSION['username'] ?? 'Guest';
$current_role = $_SESSION['role'] ?? 'Guest';
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SLATE - Freight Management System</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #f8fafc;
            background: linear-gradient(135deg, #0a1929 0%, #1a2942 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .material-symbols-outlined {
            vertical-align: middle;
        }

        /* Loading Screen */
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0a1929 0%, #1a2942 50%, #0f3a4a 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        .loading-screen.hidden {
            opacity: 0;
            visibility: hidden;
        }

        .loading-logo {
            width: 120px;
            height: auto;
            margin-bottom: 2rem;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.8;
                transform: scale(1.05);
            }
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(14, 165, 233, 0.2);
            border-top-color: #0ea5e9;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1.5rem;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        .loading-text {
            color: #cbd5e1;
            font-size: 1.1rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-weight: 500;
            letter-spacing: 0.5px;
            animation: fadeInOut 2s ease-in-out infinite;
        }

        @keyframes fadeInOut {
            0%, 100% {
                opacity: 0.6;
            }
            50% {
                opacity: 1;
            }
        }

        .loading-dots::after {
            content: '';
            animation: dots 1.5s steps(4, end) infinite;
        }

        @keyframes dots {
            0%, 20% {
                content: '';
            }
            40% {
                content: '.';
            }
            60% {
                content: '..';
            }
            80%, 100% {
                content: '...';
            }
        }
        
        /* Navigation */
        .main-nav {
            background: rgba(30, 41, 54, 0.8);
            backdrop-filter: blur(20px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(58, 69, 84, 0.5);
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo img {
            width: 50px;
            height: auto;
        }
        
        .logo h1 {
            color: #ffffff;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 0.5rem;
            align-items: center;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #cbd5e1;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            position: relative;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav-links a:hover {
            color: #ffffff;
            background: rgba(14, 165, 233, 0.15);
            transform: translateY(-1px);
        }

        .nav-links a.btn-primary {
            background: #0ea5e9;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
        }

        .nav-links a.btn-primary:hover {
            background: #0284c7;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(14, 165, 233, 0.4);
        }
        
        /* Hero Section */
        .hero-section {
            position: relative;
            min-height: 90vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            overflow: hidden;
            padding: 4rem 2rem;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(14, 165, 233, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(59, 130, 246, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .hero-content {
            max-width: 900px;
            padding: 0 2rem;
            position: relative;
            z-index: 1;
        }

        .hero-badge {
            display: inline-block;
            background: rgba(14, 165, 233, 0.2);
            border: 1px solid rgba(14, 165, 233, 0.3);
            color: #0ea5e9;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
        }
        
        .hero-content h1 {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.1;
            background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-content p {
            font-size: 1.25rem;
            margin-bottom: 2.5rem;
            color: #cbd5e1;
            line-height: 1.6;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .cta-button {
            background: #0ea5e9;
            color: white;
            padding: 1rem 2.5rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.3);
        }
        
        .cta-button:hover {
            background: #0284c7;
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(14, 165, 233, 0.4);
        }

        .cta-button.secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            box-shadow: none;
        }

        .cta-button.secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        
        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-top: 4rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .stat-item {
            text-align: center;
            padding: 1.5rem;
            background: rgba(30, 41, 54, 0.4);
            border-radius: 12px;
            border: 1px solid rgba(58, 69, 84, 0.5);
            backdrop-filter: blur(10px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #0ea5e9;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #94a3b8;
        }
        
        /* Features Section */
        .features-section {
            padding: 6rem 0;
            background: transparent;
            position: relative;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-badge {
            display: inline-block;
            background: rgba(14, 165, 233, 0.1);
            border: 1px solid rgba(14, 165, 233, 0.2);
            color: #0ea5e9;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 1rem;
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: #94a3b8;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            padding: 2.5rem;
            text-align: center;
            transition: all 0.3s ease;
            background: rgba(30, 41, 54, 0.6);
            border: 1px solid rgba(58, 69, 84, 0.5);
            border-radius: 16px;
            backdrop-filter: blur(10px);
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            border-color: rgba(14, 165, 233, 0.5);
            background: rgba(30, 41, 54, 0.8);
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 1.5rem;
            background: rgba(14, 165, 233, 0.1);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            background: rgba(14, 165, 233, 0.2);
            transform: scale(1.1);
        }

        .feature-icon .material-symbols-outlined {
            font-size: 2.5rem;
            color: #0ea5e9;
        }
        
        .feature-card h3 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            font-weight: 600;
            color: #ffffff;
        }
        
        .feature-card p {
            color: #94a3b8;
            line-height: 1.7;
            font-size: 0.95rem;
        }
        
        .about-section {
            padding: 6rem 0;
            background: rgba(30, 41, 54, 0.3);
        }
        
        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }
        
        .about-text h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }
        
        .about-text p {
            color: #cbd5e1;
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 2rem;
        }

        .about-features {
            display: grid;
            gap: 1rem;
        }

        .about-feature-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(14, 165, 233, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(14, 165, 233, 0.1);
        }

        .about-feature-item .material-symbols-outlined {
            color: #0ea5e9;
            font-size: 1.5rem;
        }

        .about-feature-item span {
            color: #cbd5e1;
            font-size: 0.95rem;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .top-header {
                display: none;
            }
            
            .nav-container {
                flex-direction: column;
                height: auto;
                padding: 20px;
            }
            
            .nav-links {
                margin-top: 20px;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .about-content {
                grid-template-columns: 1fr;
                gap: 40px;
            }
        }
        
        @media (max-width: 480px) {
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .hero-content p {
                font-size: 1rem;
            }
            
            .feature-card {
                padding: 40px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading-screen" id="loadingScreen">
        <img src="assets/images/slate.png" alt="SLATE Logo" class="loading-logo">
        <div class="loading-spinner"></div>
        <div class="loading-text">
            Loading SLATE Freight System<span class="loading-dots"></span>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="main-nav">
        <div class="nav-container">
            <div class="logo">
                <img src="assets/images/slate.png" alt="SLATE Logo">
                <h1>SLATE</h1>
            </div>
            <ul class="nav-links">
                <li><a href="#features">Features</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="careers.php">Careers</a></li>
                <?php if ($is_logged_in): ?>
                    <li><a href="pages/dashboard.php" class="btn-primary">
                        <span class="material-symbols-outlined">dashboard</span>
                        Dashboard
                    </a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="partials/login.php">Login</a></li>
                    <li><a href="partials/register-portal.php" class="btn-primary">
                        <span class="material-symbols-outlined">person_add</span>
                        Register
                    </a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <div class="hero-badge">🚀 Freight Management Excellence</div>
            <h1>Streamline Your HR & Logistics Operations</h1>
            <p>Comprehensive human resources management integrated with freight logistics. From recruitment to performance tracking, manage your entire workforce efficiently.</p>
            <div class="cta-buttons">
                <?php if ($is_logged_in): ?>
                    <a href="pages/dashboard.php" class="cta-button">
                        <span class="material-symbols-outlined">dashboard</span>
                        Access Dashboard
                    </a>
                    <a href="careers.php" class="cta-button secondary">
                        <span class="material-symbols-outlined">work</span>
                        View Careers
                    </a>
                <?php else: ?>
                    <a href="partials/register-portal.php" class="cta-button">
                        <span class="material-symbols-outlined">person_add</span>
                        Get Started
                    </a>
                    <a href="partials/login.php" class="cta-button secondary">
                        <span class="material-symbols-outlined">login</span>
                        Login
                    </a>
                <?php endif; ?>
            </div>
            <div class="hero-stats">
                <div class="stat-item">
                    <div class="stat-number">10K+</div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">500+</div>
                    <div class="stat-label">Companies</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">99%</div>
                    <div class="stat-label">Satisfaction</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">✨ Our Features</div>
                <h2 class="section-title">Everything You Need to Manage Your Workforce</h2>
                <p class="section-subtitle">Powerful tools and features designed to streamline your HR operations and boost productivity</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">groups</span>
                    </div>
                    <h3>Recruitment Management</h3>
                    <p>Streamline your hiring process with applicant tracking, job postings, and candidate management tools.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">analytics</span>
                    </div>
                    <h3>Performance Tracking</h3>
                    <p>Monitor employee performance with comprehensive analytics, KPIs, and real-time reporting dashboards.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">schedule</span>
                    </div>
                    <h3>Time & Attendance</h3>
                    <p>Automated time tracking, shift scheduling, and leave management for efficient workforce planning.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">local_shipping</span>
                    </div>
                    <h3>Freight Integration</h3>
                    <p>Seamlessly integrate HR operations with freight management and logistics workflows.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">security</span>
                    </div>
                    <h3>Secure & Compliant</h3>
                    <p>Enterprise-grade security with role-based access control and compliance management.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">support_agent</span>
                    </div>
                    <h3>24/7 Support</h3>
                    <p>Round-the-clock customer support and dedicated account management for your success.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section" id="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>Trusted by 10,000+ Companies Worldwide</h2>
                    <p>SLATE combines powerful HR management with freight logistics expertise. Our comprehensive platform provides everything you need to manage your workforce effectively, from recruitment and onboarding to performance tracking and analytics.</p>
                    <div class="about-features">
                        <div class="about-feature-item">
                            <span class="material-symbols-outlined">check_circle</span>
                            <span>Integrated HR & Logistics Management</span>
                        </div>
                        <div class="about-feature-item">
                            <span class="material-symbols-outlined">check_circle</span>
                            <span>Real-time Analytics & Reporting</span>
                        </div>
                        <div class="about-feature-item">
                            <span class="material-symbols-outlined">check_circle</span>
                            <span>Automated Workflows & Processes</span>
                        </div>
                        <div class="about-feature-item">
                            <span class="material-symbols-outlined">check_circle</span>
                            <span>Enterprise-grade Security</span>
                        </div>
                    </div>
                </div>
                <div class="about-stats">
                    <div style="background: rgba(30, 41, 54, 0.6); padding: 3rem; border-radius: 16px; text-align: center; border: 1px solid rgba(58, 69, 84, 0.5); backdrop-filter: blur(10px);">
                        <h3 style="font-size: 4rem; color: #0ea5e9; margin-bottom: 1rem; font-weight: 800;">10K+</h3>
                        <p style="color: #cbd5e1; font-size: 1.2rem; margin-bottom: 2rem;">Companies Trust SLATE</p>
                        <div style="display: grid; gap: 1rem; margin-top: 2rem;">
                            <div style="background: rgba(14, 165, 233, 0.1); padding: 1rem; border-radius: 10px;">
                                <div style="font-size: 1.8rem; color: #0ea5e9; font-weight: 700;">500+</div>
                                <div style="color: #94a3b8; font-size: 0.9rem;">Active Clients</div>
                            </div>
                            <div style="background: rgba(14, 165, 233, 0.1); padding: 1rem; border-radius: 10px;">
                                <div style="font-size: 1.8rem; color: #0ea5e9; font-weight: 700;">99%</div>
                                <div style="color: #94a3b8; font-size: 0.9rem;">Satisfaction Rate</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Access Section -->
    <section style="padding: 6rem 0; background: transparent;">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">🎯 Quick Access</div>
                <h2 class="section-title">Get Started Today</h2>
                <p class="section-subtitle">Access your HR tools and manage your workforce efficiently</p>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; margin-top: 3rem;">
                <?php if ($is_logged_in): ?>
                    <div style="background: rgba(30, 41, 54, 0.6); padding: 2.5rem; border-radius: 16px; text-align: center; border: 1px solid rgba(58, 69, 84, 0.5); backdrop-filter: blur(10px); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-8px)'; this.style.borderColor='rgba(14, 165, 233, 0.5)'" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='rgba(58, 69, 84, 0.5)'">
                        <div style="width: 70px; height: 70px; margin: 0 auto 1.5rem; background: rgba(14, 165, 233, 0.1); border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                            <span class="material-symbols-outlined" style="font-size: 2.5rem; color: #0ea5e9;">dashboard</span>
                        </div>
                        <h3 style="margin-bottom: 1rem; color: #ffffff; font-size: 1.3rem;">Dashboard</h3>
                        <p style="color: #94a3b8; margin-bottom: 1.5rem; line-height: 1.6;">Access your HR dashboard and analytics</p>
                        <a href="pages/dashboard.php" style="background: #0ea5e9; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; display: inline-block; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.background='#0284c7'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#0ea5e9'; this.style.transform='translateY(0)'">Access Dashboard</a>
                    </div>
                    <div style="background: rgba(30, 41, 54, 0.6); padding: 2.5rem; border-radius: 16px; text-align: center; border: 1px solid rgba(58, 69, 84, 0.5); backdrop-filter: blur(10px); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-8px)'; this.style.borderColor='rgba(14, 165, 233, 0.5)'" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='rgba(58, 69, 84, 0.5)'">
                        <div style="width: 70px; height: 70px; margin: 0 auto 1.5rem; background: rgba(14, 165, 233, 0.1); border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                            <span class="material-symbols-outlined" style="font-size: 2.5rem; color: #0ea5e9;">groups</span>
                        </div>
                        <h3 style="margin-bottom: 1rem; color: #ffffff; font-size: 1.3rem;">Applicant Portal</h3>
                        <p style="color: #94a3b8; margin-bottom: 1.5rem; line-height: 1.6;">Manage job applications and candidates</p>
                        <a href="applicant-portal.php" style="background: #0ea5e9; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; display: inline-block; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.background='#0284c7'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#0ea5e9'; this.style.transform='translateY(0)'">View Portal</a>
                    </div>
                    <div style="background: rgba(30, 41, 54, 0.6); padding: 2.5rem; border-radius: 16px; text-align: center; border: 1px solid rgba(58, 69, 84, 0.5); backdrop-filter: blur(10px); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-8px)'; this.style.borderColor='rgba(14, 165, 233, 0.5)'" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='rgba(58, 69, 84, 0.5)'">
                        <div style="width: 70px; height: 70px; margin: 0 auto 1.5rem; background: rgba(14, 165, 233, 0.1); border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                            <span class="material-symbols-outlined" style="font-size: 2.5rem; color: #0ea5e9;">work</span>
                        </div>
                        <h3 style="margin-bottom: 1rem; color: #ffffff; font-size: 1.3rem;">Job Postings</h3>
                        <p style="color: #94a3b8; margin-bottom: 1.5rem; line-height: 1.6;">Browse and apply for available positions</p>
                        <a href="public/careers.php" style="background: #0ea5e9; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; display: inline-block; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.background='#0284c7'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#0ea5e9'; this.style.transform='translateY(0)'">View Jobs</a>
                    </div>
                <?php else: ?>
                    <div style="background: rgba(30, 41, 54, 0.6); padding: 2.5rem; border-radius: 16px; text-align: center; border: 1px solid rgba(58, 69, 84, 0.5); backdrop-filter: blur(10px); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-8px)'; this.style.borderColor='rgba(14, 165, 233, 0.5)'" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='rgba(58, 69, 84, 0.5)'">
                        <div style="width: 70px; height: 70px; margin: 0 auto 1.5rem; background: rgba(14, 165, 233, 0.1); border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                            <span class="material-symbols-outlined" style="font-size: 2.5rem; color: #0ea5e9;">login</span>
                        </div>
                        <h3 style="margin-bottom: 1rem; color: #ffffff; font-size: 1.3rem;">Login</h3>
                        <p style="color: #94a3b8; margin-bottom: 1.5rem; line-height: 1.6;">Access your HR management system</p>
                        <a href="partials/login.php" style="background: #0ea5e9; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; display: inline-block; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.background='#0284c7'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#0ea5e9'; this.style.transform='translateY(0)'">Login</a>
                    </div>
                    <div style="background: rgba(30, 41, 54, 0.6); padding: 2.5rem; border-radius: 16px; text-align: center; border: 1px solid rgba(58, 69, 84, 0.5); backdrop-filter: blur(10px); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-8px)'; this.style.borderColor='rgba(14, 165, 233, 0.5)'" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='rgba(58, 69, 84, 0.5)'">
                        <div style="width: 70px; height: 70px; margin: 0 auto 1.5rem; background: rgba(14, 165, 233, 0.1); border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                            <span class="material-symbols-outlined" style="font-size: 2.5rem; color: #0ea5e9;">person_add</span>
                        </div>
                        <h3 style="margin-bottom: 1rem; color: #ffffff; font-size: 1.3rem;">Register</h3>
                        <p style="color: #94a3b8; margin-bottom: 1.5rem; line-height: 1.6;">Create your SLATE account</p>
                        <a href="partials/register-portal.php" style="background: #0ea5e9; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; display: inline-block; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.background='#0284c7'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#0ea5e9'; this.style.transform='translateY(0)'">Register</a>
                    </div>
                    <div style="background: rgba(30, 41, 54, 0.6); padding: 2.5rem; border-radius: 16px; text-align: center; border: 1px solid rgba(58, 69, 84, 0.5); backdrop-filter: blur(10px); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-8px)'; this.style.borderColor='rgba(14, 165, 233, 0.5)'" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='rgba(58, 69, 84, 0.5)'">
                        <div style="width: 70px; height: 70px; margin: 0 auto 1.5rem; background: rgba(14, 165, 233, 0.1); border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                            <span class="material-symbols-outlined" style="font-size: 2.5rem; color: #0ea5e9;">work</span>
                        </div>
                        <h3 style="margin-bottom: 1rem; color: #ffffff; font-size: 1.3rem;">Apply for Jobs</h3>
                        <p style="color: #94a3b8; margin-bottom: 1.5rem; line-height: 1.6;">Browse and apply for available positions</p>
                        <a href="public/careers.php" style="background: #0ea5e9; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; display: inline-block; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.background='#0284c7'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#0ea5e9'; this.style.transform='translateY(0)'">View Jobs</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer style="background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px); color: white; padding: 3rem 0; text-align: center; border-top: 1px solid rgba(58, 69, 84, 0.5);">
        <div class="container">
            <p style="color: #94a3b8; font-size: 0.95rem; margin-bottom: 1rem;">&copy; 2025 SLATE Freight Management System. All rights reserved.</p>
            <?php include 'partials/legal_links.php'; ?>
        </div>
    </footer>
    
    <script>
        // Hide loading screen when page is fully loaded
        window.addEventListener('load', function() {
            setTimeout(function() {
                const loadingScreen = document.getElementById('loadingScreen');
                loadingScreen.classList.add('hidden');
                
                // Remove from DOM after transition
                setTimeout(function() {
                    loadingScreen.remove();
                }, 500);
            }, 800); // Show loading screen for at least 800ms
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add scroll effect to navigation
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('.main-nav');
            if (window.scrollY > 100) {
                nav.style.background = 'rgba(30, 41, 54, 0.95)';
                nav.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.4)';
            } else {
                nav.style.background = 'rgba(30, 41, 54, 0.8)';
                nav.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.3)';
            }
        });
    </script>
</body>
</html>

