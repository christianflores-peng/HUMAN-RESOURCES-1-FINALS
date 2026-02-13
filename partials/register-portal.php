<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check if coming from terms page
$from_terms = isset($_GET['terms_accepted']) && $_GET['terms_accepted'] === 'true';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration - HR1 Management System</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a1929 0%, #1a2942 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            color: #f8fafc;
            overflow: hidden;
        }

        .registration-container {
            width: 100%;
            max-width: 900px;
            max-height: calc(100vh - 2rem);
            display: flex;
            background: #1e2936;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        }

        /* Left Panel - Illustration */
        .illustration-panel {
            flex: 0.8;
            background: linear-gradient(135deg, #2d5f7f 0%, #3a7a9e 50%, #4a9fd8 100%);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .system-label {
            position: absolute;
            top: 1rem;
            left: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #ffffff;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .warehouse-illustration {
            width: 100%;
            max-width: 180px;
            height: 180px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            margin-bottom: 0.5rem;
        }

        /* Right Panel - Registration Form */
        .form-panel {
            flex: 1;
            padding: 1.5rem 2rem;
            background: #1a2332;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .logo-section img {
            width: 32px;
            height: auto;
            margin-bottom: 0.3rem;
        }

        .logo-section h1 {
            font-size: 1.1rem;
            color: #ffffff;
            margin-bottom: 0.2rem;
        }

        /* Progress Steps */
        .progress-steps {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
        }

        .step-number {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #2a3544;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .step.active .step-number {
            background: #0ea5e9;
            color: #ffffff;
        }

        .step.completed .step-number {
            background: #10b981;
            color: #ffffff;
        }

        .step-label {
            font-size: 0.75rem;
            color: #64748b;
        }

        .step.active .step-label {
            color: #0ea5e9;
            font-weight: 500;
        }

        .step-connector {
            width: 40px;
            height: 2px;
            background: #2a3544;
            margin-bottom: 1rem;
        }

        .step.active ~ .step-connector {
            background: #2a3544;
        }

        /* Content Section */
        .content-section {
            margin-bottom: 0.75rem;
        }

        .section-title {
            font-size: 1.1rem;
            color: #ffffff;
            margin-bottom: 0.25rem;
            text-align: center;
        }

        .section-subtitle {
            color: #94a3b8;
            font-size: 0.8rem;
            text-align: center;
            margin-bottom: 0.75rem;
        }

        /* Category Cards */
        .category-cards {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .category-card {
            background: #2a3544;
            border: 2px solid #3a4554;
            border-radius: 8px;
            padding: 0.75rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .category-card:hover {
            border-color: #0ea5e9;
            background: #2f3a4a;
            transform: translateX(5px);
        }

        .category-card.selected {
            border-color: #0ea5e9;
            background: rgba(14, 165, 233, 0.1);
        }

        .category-card.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .category-card.disabled:hover {
            transform: none;
            border-color: #3a4554;
            background: #2a3544;
        }

        .category-icon {
            width: 38px;
            height: 38px;
            background: rgba(14, 165, 233, 0.2);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .category-card.selected .category-icon {
            background: #0ea5e9;
        }

        .category-info {
            flex: 1;
        }

        .category-title {
            font-size: 1rem;
            color: #ffffff;
            font-weight: 600;
            margin-bottom: 0.2rem;
        }

        .category-description {
            font-size: 0.8rem;
            color: #94a3b8;
        }

        .category-badge {
            background: #475569;
            color: #cbd5e1;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* Navigation Buttons */
        .button-group {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.25rem;
        }

        .btn {
            padding: 0.65rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .btn-secondary {
            background: #475569;
            color: #ffffff;
            flex: 1;
        }

        .btn-secondary:hover {
            background: #334155;
        }

        .btn-primary {
            background: #0ea5e9;
            color: #ffffff;
            flex: 2;
        }

        .btn-primary:hover {
            background: #0284c7;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
        }

        .btn-primary:disabled {
            background: #475569;
            cursor: not-allowed;
            opacity: 0.5;
            transform: none;
        }

        .login-link {
            text-align: center;
            margin-top: 1rem;
            color: #cbd5e1;
            font-size: 0.8rem;
        }

        .login-link a {
            color: #0ea5e9;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 968px) {
            .registration-container {
                flex-direction: column;
            }

            .illustration-panel {
                min-height: 250px;
            }

            .warehouse-illustration {
                height: 200px;
                font-size: 4rem;
            }

            .form-panel {
                padding: 2rem;
            }
        }

        @media (max-width: 640px) {
            .progress-steps {
                gap: 0.5rem;
            }

            .step-connector {
                width: 40px;
            }

            .step-label {
                font-size: 0.75rem;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php $logo_path = '../assets/images/slate.png'; include '../includes/loading-screen.php'; ?>
    
    <div class="registration-container">
        <!-- Left Panel - Illustration -->
        <div class="illustration-panel">
            <div class="system-label">
                <span>🏢</span> Freight Management System
            </div>
            <div class="warehouse-illustration">
                🏭
            </div>
        </div>

        <!-- Right Panel - Registration Form -->
        <div class="form-panel">
            <div class="logo-section">
                <img src="../assets/images/slate.png" alt="SLATE Logo">
                <h1>Registration</h1>
            </div>

            <!-- Progress Steps -->
            <div class="progress-steps">
                <div class="step active">
                    <div class="step-number">1</div>
                    <div class="step-label">Type</div>
                </div>
                <div class="step-connector"></div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-label">Registration Details</div>
                </div>
            </div>

            <!-- Content -->
            <div class="content-section">
                <h2 class="section-title">Choose Your Registration Type</h2>
                <p class="section-subtitle">Select the category that best describes you</p>

                <div class="category-cards">
                    <!-- Applicant Card -->
                    <div class="category-card" onclick="selectCategory('applicant')" id="applicant-card">
                        <div class="category-icon">📝</div>
                        <div class="category-info">
                            <div class="category-title">Applicant</div>
                            <div class="category-description">Apply for job positions</div>
                        </div>
                    </div>

                    <!-- Employee Card -->
                    <div class="category-card" onclick="selectCategory('employee')" id="employee-card">
                        <div class="category-icon">👤</div>
                        <div class="category-info">
                            <div class="category-title">Employee</div>
                            <div class="category-description">Register as an employee</div>
                        </div>
                    </div>

                    <!-- Both Card -->
                    <div class="category-card disabled" id="both-card">
                        <div class="category-icon">📋</div>
                        <div class="category-info">
                            <div class="category-title">Both</div>
                            <div class="category-description">Coming soon - Apply and register as employee</div>
                        </div>
                        <div class="category-badge">Coming Soon</div>
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="button-group">
                <button class="btn btn-secondary" onclick="window.location.href='terms.php'">
                    <i data-lucide="arrow-left"></i>
                    Previous
                </button>
                <button class="btn btn-primary" id="next-btn" disabled onclick="proceedToRegistration()">
                    Next
                    <i data-lucide="arrow-right"></i>
                </button>
            </div>

            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>

    <script>
        let selectedCategory = null;

        function selectCategory(category) {
            // Don't allow selection of disabled categories
            if (category === 'both') return;

            selectedCategory = category;

            // Remove selected class from all cards
            document.querySelectorAll('.category-card').forEach(card => {
                card.classList.remove('selected');
            });

            // Add selected class to clicked card
            document.getElementById(category + '-card').classList.add('selected');

            // Enable next button
            document.getElementById('next-btn').disabled = false;
        }

        function proceedToRegistration() {
            if (!selectedCategory) return;

            // Redirect to appropriate registration form
            if (selectedCategory === 'applicant') {
                window.location.href = 'register-applicant.php?terms_accepted=true';
            } else if (selectedCategory === 'employee') {
                window.location.href = 'register-employee.php?terms_accepted=true';
            }
        }
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
