<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

require_once 'database/config.php';

// Get user info from user_accounts table (primary) with fallback to users table (legacy)
$user_id = $_SESSION['user_id'];
$user = fetchSingle("SELECT ua.*, r.role_name, r.role_type, d.department_name 
    FROM user_accounts ua 
    LEFT JOIN roles r ON ua.role_id = r.id 
    LEFT JOIN departments d ON ua.department_id = d.id 
    WHERE ua.id = ?", [$user_id]);

// Fallback to legacy users table
if (!$user) {
    $user = fetchSingle("SELECT * FROM users WHERE id = ?", [$user_id]);
    if ($user) {
        // Map legacy fields to new structure
        $user['personal_email'] = $user['email'] ?? null;
        $user['first_name'] = explode(' ', $user['full_name'] ?? '')[0] ?? '';
        $user['last_name'] = explode(' ', $user['full_name'] ?? '', 2)[1] ?? '';
    }
} else {
    // For user_accounts, construct full_name from first_name and last_name
    $user['full_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    $user['email'] = $user['personal_email'] ?? $user['company_email'] ?? null;
    $user['role'] = $user['role_name'] ?? 'Applicant';
}

if (!$user) {
    header('Location: auth/login.php');
    exit();
}

// Fetch user's job applications
$applications = [];
$has_hired_application = false;

try {
    // Get user email for finding applications
    $search_email = $user['personal_email'] ?? $user['email'] ?? '';
    
    // First try to get applications by email
    $applications = fetchAll("
        SELECT ja.*, jp.title as job_title, jp.location, jp.department_id,
               d.name as department_name
        FROM job_applications ja
        LEFT JOIN job_postings jp ON jp.id = ja.job_posting_id
        LEFT JOIN departments d ON d.id = jp.department_id
        WHERE ja.email = ?
        ORDER BY ja.created_at DESC
    ", [$search_email]);
    
    // If no applications found by email, try to get all applications for testing
    if (empty($applications)) {
        // For admin/testing: show all applications
        $applications = fetchAll("
            SELECT ja.*, jp.title as job_title, jp.location, jp.department_id,
                   d.name as department_name
            FROM job_applications ja
            LEFT JOIN job_postings jp ON jp.id = ja.job_posting_id
            LEFT JOIN departments d ON d.id = jp.department_id
            ORDER BY ja.created_at DESC
            LIMIT 50
        ");
    }
    
    // Check if any application has been hired
    foreach ($applications as $app) {
        if ($app['status'] === 'hired') {
            $has_hired_application = true;
            break;
        }
    }
} catch (Exception $e) {
    $applications = [];
    error_log("Error fetching applications: " . $e->getMessage());
}

// If user has been hired and hasn't completed onboarding, redirect to employee onboarding
$user_role = $user['role_name'] ?? $user['role'] ?? '';
if ($has_hired_application && $user_role !== 'Employee' && !isset($_SESSION['onboarding_complete'])) {
    $_SESSION['approved'] = true;
    header('Location: employee-onboarding.php');
    exit();
}

// Determine current application and its stage
$current_application = null;
$current_stage = 'applied';
$stage_day = 0;
$days_remaining = 0;

if (!empty($applications)) {
    // Get the most recent application
    $current_application = $applications[0];
    
    // Determine stage based on status
    switch ($current_application['status']) {
        case 'new':
        case 'reviewed':
            $current_stage = 'applied';
            $stage_day = 0;
            break;
        case 'interview':
            $current_stage = 'interview';
            $stage_day = 1;
            break;
        case 'screening':
            $current_stage = 'screening';
            $stage_day = 2;
            break;
        case 'offer':
            $current_stage = 'offer';
            $stage_day = 3;
            break;
        case 'hired':
            $current_stage = 'waiting';
            // Calculate days remaining (3 days from last update)
            $last_update = strtotime($current_application['updated_at']);
            $days_passed = floor((time() - $last_update) / 86400);
            $days_remaining = max(0, 3 - $days_passed);
            break;
    }
}

// Group applications by status (for backward compatibility)
$applied = [];
$screening = [];
$interview = [];
$offer = [];

foreach ($applications as $app) {
    switch ($app['status']) {
        case 'new':
        case 'reviewed':
            $applied[] = $app;
            break;
        case 'screening':
            $screening[] = $app;
            break;
        case 'interview':
            $interview[] = $app;
            break;
        case 'offer':
        case 'hired':
            $offer[] = $app;
            break;
    }
}

// Safe output helper
function h($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - HR1</title>
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
            min-height: 100vh;
            color: #f8fafc;
        }

        .portal-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .portal-header {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #334155;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .portal-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .portal-logo img {
            height: 40px;
        }

        .portal-logo h1 {
            color: #0ea5e9;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .portal-nav {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .portal-nav a {
            color: #cbd5e1;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .portal-nav a:hover {
            background: #334155;
            color: #f8fafc;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #cbd5e1;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #0ea5e9, #3b82f6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
        }

        .portal-main {
            flex: 1;
            padding: 3rem 2rem;
            max-width: 900px;
            margin: 0 auto;
            width: 100%;
        }

        .welcome-section {
            text-align: center;
            margin-bottom: 3rem;
        }

        .welcome-section h2 {
            font-size: 2rem;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .welcome-section p {
            color: #94a3b8;
            font-size: 1.1rem;
        }

        .status-card {
            background: #1e2936;
            border-radius: 16px;
            padding: 3rem;
            text-align: center;
            margin-bottom: 2rem;
            border: 2px solid #334155;
        }

        .status-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 3rem;
        }

        .status-pending .status-icon {
            background: rgba(245, 158, 11, 0.2);
            border: 3px solid #f59e0b;
        }

        .status-review .status-icon {
            background: rgba(59, 130, 246, 0.2);
            border: 3px solid #3b82f6;
        }

        .status-approved .status-icon {
            background: rgba(16, 185, 129, 0.2);
            border: 3px solid #10b981;
        }

        .status-rejected .status-icon {
            background: rgba(239, 68, 68, 0.2);
            border: 3px solid #ef4444;
        }

        .status-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .status-pending .status-title { color: #f59e0b; }
        .status-review .status-title { color: #3b82f6; }
        .status-approved .status-title { color: #10b981; }
        .status-rejected .status-title { color: #ef4444; }

        .status-message {
            color: #cbd5e1;
            font-size: 1.1rem;
            line-height: 1.6;
            max-width: 500px;
            margin: 0 auto 2rem;
        }

        .progress-bar {
            background: #334155;
            border-radius: 10px;
            height: 10px;
            max-width: 400px;
            margin: 0 auto 1rem;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 1s ease;
        }

        .status-pending .progress-fill {
            width: 33%;
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
        }

        .status-review .progress-fill {
            width: 66%;
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
        }

        .status-approved .progress-fill {
            width: 100%;
            background: linear-gradient(90deg, #10b981, #34d399);
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            max-width: 400px;
            margin: 0 auto;
            font-size: 0.85rem;
            color: #64748b;
        }

        .progress-step.active {
            color: #0ea5e9;
            font-weight: 600;
        }

        .progress-step.completed {
            color: #10b981;
        }

        .info-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .info-card {
            background: #1e2936;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1.5rem;
        }

        .info-card h4 {
            color: #0ea5e9;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-card p {
            color: #94a3b8;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .info-card .value {
            color: #f8fafc;
            font-weight: 500;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #0ea5e9;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #0284c7;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #334155;
            color: #ffffff;
        }

        .btn-secondary:hover {
            background: #475569;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .portal-footer {
            background: #0f172a;
            border-top: 1px solid #334155;
            padding: 1.5rem;
            text-align: center;
            color: #64748b;
        }

        /* Quick Access Cards */
        .quick-access-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .access-card {
            background: #1e2936;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s;
        }

        .access-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            border-color: #0ea5e9;
        }

        .access-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .access-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #f8fafc;
            margin-bottom: 0.75rem;
        }

        .access-description {
            color: #94a3b8;
            margin-bottom: 1.5rem;
            line-height: 1.5;
            font-size: 0.95rem;
        }

        /* Application Management Styles */
        .app-management {
            margin-top: 2rem;
        }

        .app-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .app-header h2 {
            font-size: 1.75rem;
            color: #ffffff;
        }

        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #334155;
        }

        .tab {
            padding: 0.875rem 1.5rem;
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab:hover {
            color: #cbd5e1;
        }

        .tab.active {
            color: #0ea5e9;
            border-bottom-color: #0ea5e9;
        }

        .pipeline {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }

        .pipeline-column {
            background: #1e2936;
            border-radius: 12px;
            padding: 1.5rem;
            min-height: 400px;
        }

        .column-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .column-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #f8fafc;
        }

        .column-count {
            background: #0ea5e9;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .application-card {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }

        .application-card:hover {
            border-color: #0ea5e9;
            transform: translateY(-2px);
        }

        .app-card-title {
            font-size: 1rem;
            font-weight: 600;
            color: #f8fafc;
            margin-bottom: 0.5rem;
        }

        .app-card-meta {
            color: #94a3b8;
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }

        .app-card-date {
            color: #64748b;
            font-size: 0.8rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #64748b;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 200px;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.4;
            filter: grayscale(0.5);
        }

        .empty-state p {
            font-size: 0.95rem;
            color: #64748b;
            margin: 0;
        }

        /* Step-by-Step Process Styles */
        .process-steps {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            background: #1e2936;
            border-radius: 12px;
            margin-bottom: 2rem;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            position: relative;
        }

        .step-number {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #334155;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.25rem;
            transition: all 0.3s;
        }

        .step.completed .step-number {
            background: #10b981;
            color: white;
        }

        .step.current .step-number {
            background: #0ea5e9;
            color: white;
            box-shadow: 0 0 20px rgba(14, 165, 233, 0.5);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .step-label {
            color: #94a3b8;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .step.completed .step-label,
        .step.current .step-label {
            color: #f8fafc;
        }

        .step-line {
            width: 80px;
            height: 3px;
            background: #334155;
            margin: 0 1rem;
        }

        .step-line.active {
            background: #10b981;
        }

        .stage-content {
            display: flex;
            justify-content: center;
            padding: 2rem 0;
        }

        .stage-card {
            background: #1e2936;
            border-radius: 12px;
            padding: 3rem;
            max-width: 700px;
            width: 100%;
        }

        .stage-icon {
            font-size: 4rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .stage-card h3 {
            font-size: 1.75rem;
            color: #f8fafc;
            text-align: center;
            margin-bottom: 1rem;
        }

        .stage-card > p {
            color: #cbd5e1;
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.05rem;
        }

        .stage-details {
            background: #0f172a;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #334155;
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #94a3b8;
            font-weight: 500;
        }

        .detail-value {
            color: #f8fafc;
            font-weight: 600;
        }

        .stage-message {
            background: #334155;
            border-left: 4px solid #64748b;
            padding: 1rem 1.5rem;
            border-radius: 6px;
            color: #cbd5e1;
        }

        .stage-message.info {
            background: rgba(14, 165, 233, 0.1);
            border-left-color: #0ea5e9;
        }

        .stage-message.success {
            background: rgba(16, 185, 129, 0.1);
            border-left-color: #10b981;
        }

        .stage-message p {
            margin: 0.5rem 0;
        }

        .stage-message ul {
            margin: 0.5rem 0;
            padding-left: 1.5rem;
        }

        .stage-message li {
            margin: 0.25rem 0;
        }

        .countdown-timer {
            text-align: center;
            padding: 2rem;
            margin: 2rem 0;
        }

        .countdown-number {
            font-size: 5rem;
            font-weight: 700;
            color: #0ea5e9;
            line-height: 1;
        }

        .countdown-label {
            font-size: 1.25rem;
            color: #94a3b8;
            margin-top: 0.5rem;
        }

        @media (max-width: 1200px) {
            .pipeline {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 968px) {
            .quick-access-cards {
                grid-template-columns: 1fr;
            }

            .process-steps {
                flex-direction: column;
                gap: 1rem;
            }

            .step-line {
                width: 3px;
                height: 40px;
                margin: 0;
            }

            .stage-card {
                padding: 2rem 1.5rem;
            }

            .countdown-number {
                font-size: 3.5rem;
            }
        }

        @media (max-width: 768px) {
            .portal-main {
                padding: 1.5rem;
            }

            .welcome-section h2 {
                font-size: 1.5rem;
            }

            .status-card {
                padding: 2rem 1.5rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .pipeline {
                grid-template-columns: 1fr;
            }

            .app-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="portal-container">
        <!-- Header -->
        <header class="portal-header">
            <div class="portal-logo">
                <img src="assets/images/slate.png" alt="SLATE Logo">
                <h1>SLATE</h1>
            </div>
            <nav class="portal-nav">
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($user['full_name'] ?? $user['username'], 0, 1)); ?></div>
                    <span><?php echo h($user['full_name'] ?? $user['username']); ?></span>
                </div>
                <a href="auth/logout.php">Logout</a>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="portal-main">
            <!-- Application Management -->
            <div class="app-management">
                <div class="app-header">
                    <h2>Applicant Management</h2>
                    <a href="public/careers.php" class="btn btn-primary">View Pipeline</a>
                </div>

                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab active" onclick="switchTab('tracking')">Tracking</button>
                    <button class="tab" onclick="switchTab('documents')">Document Management</button>
                    <button class="tab" onclick="switchTab('collaboration')">Collaboration</button>
                </div>

                <!-- Step-by-Step Process View -->
                <div id="tracking-content" class="tab-content">
                    <?php if ($current_application): ?>
                        <!-- Progress Steps -->
                        <div class="process-steps">
                            <div class="step <?php echo $stage_day >= 0 ? 'completed' : ''; ?>">
                                <div class="step-number">1</div>
                                <div class="step-label">Applied</div>
                            </div>
                            <div class="step-line <?php echo $stage_day >= 1 ? 'active' : ''; ?>"></div>
                            <div class="step <?php echo $stage_day >= 1 ? 'completed' : ''; ?> <?php echo $current_stage == 'interview' ? 'current' : ''; ?>">
                                <div class="step-number">2</div>
                                <div class="step-label">Interview</div>
                            </div>
                            <div class="step-line <?php echo $stage_day >= 2 ? 'active' : ''; ?>"></div>
                            <div class="step <?php echo $stage_day >= 2 ? 'completed' : ''; ?> <?php echo $current_stage == 'screening' ? 'current' : ''; ?>">
                                <div class="step-number">3</div>
                                <div class="step-label">Screening</div>
                            </div>
                            <div class="step-line <?php echo $stage_day >= 3 ? 'active' : ''; ?>"></div>
                            <div class="step <?php echo $stage_day >= 3 ? 'completed' : ''; ?> <?php echo $current_stage == 'offer' ? 'current' : ''; ?>">
                                <div class="step-number">4</div>
                                <div class="step-label">Offer</div>
                            </div>
                        </div>

                        <!-- Current Stage Content -->
                        <div class="stage-content">
                            <?php if ($current_stage == 'applied'): ?>
                                <!-- Applied Stage -->
                                <div class="stage-card">
                                    <div class="stage-icon">📋</div>
                                    <h3>Application Submitted</h3>
                                    <p>Your application for <strong><?php echo h($current_application['job_title']); ?></strong> has been received.</p>
                                    <div class="stage-details">
                                        <div class="detail-item">
                                            <span class="detail-label">Position:</span>
                                            <span class="detail-value"><?php echo h($current_application['job_title']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Department:</span>
                                            <span class="detail-value"><?php echo h($current_application['department_name'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Applied Date:</span>
                                            <span class="detail-value"><?php echo date('F j, Y', strtotime($current_application['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="stage-message">
                                        <p>⏳ Waiting for HR to review your application...</p>
                                    </div>
                                </div>

                            <?php elseif ($current_stage == 'interview'): ?>
                                <!-- Interview Stage (Day 1) -->
                                <div class="stage-card">
                                    <div class="stage-icon">💬</div>
                                    <h3>Interview Scheduled - Day 1</h3>
                                    <p>Congratulations! You've been selected for an interview.</p>
                                    <div class="stage-details">
                                        <div class="detail-item">
                                            <span class="detail-label">📍 Location:</span>
                                            <span class="detail-value"><?php echo h($current_application['location']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">📅 Interview Date:</span>
                                            <span class="detail-value"><?php echo date('F j, Y', strtotime('+1 day', strtotime($current_application['updated_at']))); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">⏰ Time:</span>
                                            <span class="detail-value">10:00 AM</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">💼 Position:</span>
                                            <span class="detail-value"><?php echo h($current_application['job_title']); ?></span>
                                        </div>
                                    </div>
                                    <div class="stage-message info">
                                        <p><strong>What to bring:</strong></p>
                                        <ul>
                                            <li>Valid ID</li>
                                            <li>Resume (printed copy)</li>
                                            <li>Portfolio (if applicable)</li>
                                        </ul>
                                    </div>
                                </div>

                            <?php elseif ($current_stage == 'screening'): ?>
                                <!-- Screening Stage (Day 2) -->
                                <div class="stage-card">
                                    <div class="stage-icon">🔍</div>
                                    <h3>Talent Acquisition Screening - Day 2</h3>
                                    <p>Your interview was successful! Now it's time for the screening assessment.</p>
                                    <div class="stage-details">
                                        <div class="detail-item">
                                            <span class="detail-label">Assessment Type:</span>
                                            <span class="detail-value">Skills & Aptitude Test</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Duration:</span>
                                            <span class="detail-value">60 minutes</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Format:</span>
                                            <span class="detail-value">Online Assessment</span>
                                        </div>
                                    </div>
                                    <div class="stage-message info">
                                        <p><strong>Assessment Areas:</strong></p>
                                        <ul>
                                            <li>Technical Skills</li>
                                            <li>Problem Solving</li>
                                            <li>Communication</li>
                                            <li>Cultural Fit</li>
                                        </ul>
                                    </div>
                                    <button class="btn btn-primary" style="margin-top: 1rem;" onclick="window.location.href='screening.php?application_id=<?php echo $current_application['id']; ?>'">Start Assessment</button>
                                </div>

                            <?php elseif ($current_stage == 'offer'): ?>
                                <!-- Offer Stage (Day 3) -->
                                <div class="stage-card">
                                    <div class="stage-icon">🎉</div>
                                    <h3>Job Offer - Day 3</h3>
                                    <p>Congratulations! We're pleased to offer you a position.</p>
                                    <div class="stage-details">
                                        <div class="detail-item">
                                            <span class="detail-label">Position Offered:</span>
                                            <span class="detail-value"><?php echo h($current_application['job_title']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Department:</span>
                                            <span class="detail-value"><?php echo h($current_application['department_name'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Start Date:</span>
                                            <span class="detail-value"><?php echo date('F j, Y', strtotime('+7 days')); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Employment Type:</span>
                                            <span class="detail-value">Full-time</span>
                                        </div>
                                    </div>
                                    <div class="stage-message success">
                                        <p><strong>Next Steps:</strong></p>
                                        <ul>
                                            <li>Review the offer details</li>
                                            <li>Accept or decline the offer</li>
                                            <li>Complete onboarding documents</li>
                                        </ul>
                                    </div>
                                    <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                                        <button class="btn btn-primary">Accept Offer</button>
                                        <button class="btn btn-secondary">Download Offer Letter</button>
                                    </div>
                                </div>

                            <?php elseif ($current_stage == 'waiting'): ?>
                                <!-- Waiting for Results -->
                                <div class="stage-card">
                                    <div class="stage-icon">⏳</div>
                                    <h3>Awaiting Final Results</h3>
                                    <p>You've completed all stages! We're reviewing your application.</p>
                                    <div class="countdown-timer">
                                        <div class="countdown-number"><?php echo $days_remaining; ?></div>
                                        <div class="countdown-label">Days Remaining</div>
                                    </div>
                                    <div class="stage-message">
                                        <p>We'll notify you of the final decision within <strong>3 business days</strong>.</p>
                                        <p>Thank you for your patience!</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php else: ?>
                        <!-- No Applications -->
                        <div class="empty-state" style="padding: 4rem;">
                            <div class="empty-state-icon">📋</div>
                            <h3>No Applications Yet</h3>
                            <p>You haven't applied for any positions yet.</p>
                            <a href="public/careers.php" class="btn btn-primary" style="margin-top: 1rem;">Browse Job Openings</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Document Management Tab (Hidden by default) -->
                <div id="documents-content" class="tab-content" style="display: none;">
                    <div class="empty-state" style="padding: 4rem;">
                        <div class="empty-state-icon">📄</div>
                        <p>Document management coming soon</p>
                    </div>
                </div>

                <!-- Collaboration Tab (Hidden by default) -->
                <div id="collaboration-content" class="tab-content" style="display: none;">
                    <div class="empty-state" style="padding: 4rem;">
                        <div class="empty-state-icon">👥</div>
                        <p>Collaboration features coming soon</p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="portal-footer">
            <p>&copy; <?php echo date('Y'); ?> HR1 Management System. All rights reserved.</p>
        </footer>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.style.display = 'none';
            });

            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab content
            const contentId = tabName + '-content';
            const content = document.getElementById(contentId);
            if (content) {
                content.style.display = 'block';
            }

            // Add active class to clicked tab
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
