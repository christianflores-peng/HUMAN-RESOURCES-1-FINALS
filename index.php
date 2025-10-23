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
    <title>HR1 - Human Resources Management System</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        /* Header Styles */
        .top-header {
            background: #1e3a8a;
            color: white;
            padding: 8px 0;
            font-size: 14px;
        }
        
        .top-header .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .contact-info {
            display: flex;
            gap: 30px;
        }
        
        .contact-info span {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
        }
        
        .social-links a {
            color: white;
            font-size: 16px;
            transition: color 0.3s ease;
        }
        
        .social-links a:hover {
            color: #60a5fa;
        }
        
        /* Navigation */
        .main-nav {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo h1 {
            color: #3b82f6;
            font-size: 24px;
            font-weight: 700;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 30px;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #374151;
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
        }
        
        .nav-links a:hover,
        .nav-links a.active {
            color: #3b82f6;
        }
        
        .nav-links a.active::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            right: 0;
            height: 2px;
            background: #3b82f6;
        }
        
        /* Hero Section */
        .hero-section {
            position: relative;
            height: 100vh;
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), 
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><rect fill="%23f8fafc" width="1200" height="600"/><circle cx="200" cy="150" r="50" fill="%23e2e8f0"/><circle cx="800" cy="300" r="80" fill="%23cbd5e1"/><rect x="400" y="200" width="200" height="100" fill="%23e2e8f0"/></svg>');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
        }
        
        .hero-content {
            max-width: 800px;
            padding: 0 20px;
        }
        
        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 40px;
            opacity: 0.9;
        }
        
        .cta-button {
            background: #3b82f6;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .cta-button:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }
        
        .hero-dots {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
        }
        
        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .dot.active {
            background: white;
            transform: scale(1.2);
        }
        
        /* Features Section */
        .features-section {
            padding: 80px 0;
            background: #f8fafc;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0;
            margin-top: 40px;
        }
        
        .feature-card {
            padding: 60px 40px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .feature-card:nth-child(1) {
            background: white;
        }
        
        .feature-card:nth-child(2) {
            background: #3b82f6;
            color: white;
        }
        
        .feature-card:nth-child(3) {
            background: white;
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        
        .feature-card:nth-child(2) .feature-icon {
            color: white;
        }
        
        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .feature-card p {
            opacity: 0.8;
            line-height: 1.6;
        }
        
        .about-section {
            padding: 80px 0;
            background: white;
        }
        
        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }
        
        .about-text h4 {
            color: #64748b;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        
        .about-text h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .about-text p {
            color: #6b7280;
            font-size: 1.1rem;
            line-height: 1.7;
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
    <!-- Top Header -->
    <div id="top" class="top-header">
        <div class="container">
            <div class="contact-info">
                <span><i class="fas fa-phone"></i> Call Us: +1 234 567 8900</span>
                <span><i class="fas fa-map-marker-alt"></i> Location: New York, NY, USA</span>
            </div>
            <div class="social-links">
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-linkedin"></i></a>
            </div>
        </div>
        </div>
        
    <!-- Main Navigation -->
    <nav class="main-nav">
        <div class="nav-container">
            <div class="logo">
                <h1>HUMAN RESOURCES MANAGEMENT</h1>
            </div>
            <ul class="nav-links">
                <li><a href="#top" class="active">Home</a></li>
                <li><a href="#about">About</a></li>
                <?php if ($is_logged_in): ?>
                    <li><a href="pages/dashboard.php">Dashboard</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1>Build Your HR Strategy With Our Specialists</h1>
            <p>Streamline your human resources management with our comprehensive HR1 system. From recruitment to performance tracking, we've got you covered.</p>
            <?php if ($is_logged_in): ?>
                <a href="pages/dashboard.php" class="cta-button">
                    Access Dashboard <i class="fas fa-arrow-right"></i>
                </a>
            <?php else: ?>
                <a href="login.php" class="cta-button">
                    Get Started <i class="fas fa-arrow-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <div class="hero-dots">
            <div class="dot"></div>
            <div class="dot active"></div>
            <div class="dot"></div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">👥</div>
                    <h3>Professional Consultants</h3>
                    <p>Our experienced HR professionals provide expert guidance and support for all your human resources needs.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Comprehensive Services</h3>
                    <p>From recruitment and onboarding to performance management and analytics, we offer complete HR solutions.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🏢</div>
                    <h3>About HR1 System</h3>
                    <p>More than 10,000+ companies trust our HR management platform for their human resources operations.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section" id="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h4>ABOUT HR1 SYSTEM</h4>
                    <h2>More than 10,000+ Companies Trust Our HR Management Platform</h2>
                    <p>Our comprehensive HR management system provides everything you need to manage your workforce effectively. From recruitment and onboarding to performance tracking and analytics, HR1 helps you streamline your human resources operations and make data-driven decisions.</p>
                </div>
                <div class="about-stats">
                    <div style="background: #f8fafc; padding: 40px; border-radius: 10px; text-align: center;">
                        <h3 style="font-size: 3rem; color: #3b82f6; margin-bottom: 10px;">10,000+</h3>
                        <p style="color: #6b7280; font-size: 1.1rem;">Companies Using HR1</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Access Section -->
    <section style="padding: 80px 0; background: #f8fafc;">
        <div class="container">
            <div style="text-align: center; margin-bottom: 50px;">
                <h2 style="font-size: 2.5rem; color: #3b82f6; margin-bottom: 20px;">Quick Access</h2>
                <p style="color: #6b7280; font-size: 1.1rem;">Access your HR tools and manage your workforce efficiently</p>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
                <?php if ($is_logged_in): ?>
                    <div style="background: white; padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <div style="font-size: 2.5rem; margin-bottom: 20px;">📊</div>
                        <h3 style="margin-bottom: 15px; color: #1e293b;">Dashboard</h3>
                        <p style="color: #64748b; margin-bottom: 20px;">Access your HR dashboard and analytics</p>
                        <a href="pages/dashboard.php" style="background: #3b82f6; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-block;">Access Dashboard</a>
                    </div>
                    <div style="background: white; padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <div style="font-size: 2.5rem; margin-bottom: 20px;">👥</div>
                        <h3 style="margin-bottom: 15px; color: #1e293b;">Applicant Portal</h3>
                        <p style="color: #64748b; margin-bottom: 20px;">Manage job applications and candidates</p>
                        <a href="apply.php" style="background: #3b82f6; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-block;">View Portal</a>
                    </div>
                    <div style="background: white; padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <div style="font-size: 2.5rem; margin-bottom: 20px;">💼</div>
                        <h3 style="margin-bottom: 15px; color: #1e293b;">Job Postings</h3>
                        <p style="color: #64748b; margin-bottom: 20px;">Browse and apply for available positions</p>
                        <a href="apply.php" style="background: #3b82f6; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-block;">View Jobs</a>
                    </div>
                <?php else: ?>
                    <div style="background: white; padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <div style="font-size: 2.5rem; margin-bottom: 20px;">🔐</div>
                        <h3 style="margin-bottom: 15px; color: #1e293b;">Login</h3>
                        <p style="color: #64748b; margin-bottom: 20px;">Access your HR management system</p>
                        <a href="login.php" style="background: #3b82f6; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-block;">Login</a>
                    </div>
                    <div style="background: white; padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <div style="font-size: 2.5rem; margin-bottom: 20px;">📝</div>
                        <h3 style="margin-bottom: 15px; color: #1e293b;">Register</h3>
                        <p style="color: #64748b; margin-bottom: 20px;">Create your HR1 account</p>
                        <a href="register.php" style="background: #3b82f6; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-block;">Register</a>
                    </div>
                    <div style="background: white; padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <div style="font-size: 2.5rem; margin-bottom: 20px;">💼</div>
                        <h3 style="margin-bottom: 15px; color: #1e293b;">Apply for Jobs</h3>
                        <p style="color: #64748b; margin-bottom: 20px;">Browse and apply for available positions</p>
                        <a href="apply.php" style="background: #3b82f6; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-block;">View Jobs</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer style="background: #1e293b; color: white; padding: 40px 0; text-align: center;">
        <div class="container">
            <p>&copy; 2025 HR1 Human Resources Management System. All rights reserved.</p>
            <div style="margin-top: 20px;">
                <a href="#" style="color: #94a3b8; text-decoration: none; margin: 0 15px;">Terms & Conditions</a>
                <a href="#" style="color: #94a3b8; text-decoration: none; margin: 0 15px;">Privacy Policy</a>
                <a href="#" style="color: #94a3b8; text-decoration: none; margin: 0 15px;">Contact Us</a>
            </div>
        </div>
    </footer>
    
    <script>
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
                nav.style.background = 'rgba(255, 255, 255, 0.95)';
                nav.style.backdropFilter = 'blur(10px)';
            } else {
                nav.style.background = 'white';
                nav.style.backdropFilter = 'none';
            }
        });
    </script>
</body>
</html>

