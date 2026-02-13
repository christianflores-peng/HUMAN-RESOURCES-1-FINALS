<?php
require_once '../../includes/session_helper.php';
startSecureSession();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Admin') {
    header('Location: ../../index.php');
    exit();
}

$current_page = $_GET['page'] ?? 'dashboard';
$user_name = htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - HR1</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../../assets/css/spa.css">
</head>
<body data-role="admin">
    <?php $logo_path = '../../assets/images/slate.png'; include '../../includes/loading-screen.php'; ?>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-section">
                <img src="../../assets/images/slate.png" alt="SLATE Logo">
                <h2>Admin Portal</h2>
                <p><?php echo $user_name; ?></p>
                <span class="role-badge">ADMINISTRATOR</span>
            </div>

            <div class="nav-section-title">Main</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="dashboard">
                        <i data-lucide="layout-dashboard"></i>
                        Dashboard & Analytics
                    </a>
                </li>
            </ul>

            <div class="nav-section-title">Configuration</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="accounts">
                        <i data-lucide="user-cog"></i>
                        User Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="roles-permissions">
                        <i data-lucide="shield"></i>
                        Roles & Permissions
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="approval-workflows">
                        <i data-lucide="git-branch"></i>
                        Approval Workflows
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="system-settings">
                        <i data-lucide="settings"></i>
                        System Settings
                    </a>
                </li>
            </ul>

            <div class="nav-section-title">Recruitment Mgt</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="job-postings-admin">
                        <i data-lucide="briefcase"></i>
                        Job Postings Override
                    </a>
                </li>
            </ul>

            <div class="nav-section-title">Applicant Mgt</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="applicant-database">
                        <i data-lucide="database"></i>
                        Applicant Database
                    </a>
                </li>
            </ul>

            <div class="nav-section-title">Onboarding</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="requirements-masterlist">
                        <i data-lucide="clipboard-list"></i>
                        Requirements Masterlist
                    </a>
                </li>
            </ul>

            <div class="nav-section-title">Performance</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="audit-logs">
                        <i data-lucide="history"></i>
                        Audit Logs
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="regularization-approval">
                        <i data-lucide="badge-check"></i>
                        Regularization Approval
                    </a>
                </li>
            </ul>

            <div class="nav-section-title">Recognition</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="recognition-moderation">
                        <i data-lucide="message-square-warning"></i>
                        Post Moderation
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="rewards-config">
                        <i data-lucide="gift"></i>
                        Rewards Configuration
                    </a>
                </li>
            </ul>

            <div class="nav-section-title">Account</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="../../logout.php" class="nav-link">
                        <i data-lucide="log-out"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </aside>

        <main class="main-content" id="spa-content">
            <div style="text-align:center;padding:3rem 2rem;">
                <div style="width:70px;height:70px;margin:0 auto 1.5rem;background:rgba(239,68,68,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <i data-lucide="shield" style="width:36px;height:36px;color:#ef4444;"></i>
                </div>
                <h2 style="color:#e2e8f0;margin-bottom:0.5rem;">Welcome, <?php echo $user_name; ?></h2>
                <p style="color:#94a3b8;margin-bottom:2rem;">Loading your <strong style="color:#ef4444;">Administrator</strong> dashboard...</p>
                <div style="display:flex;justify-content:center;gap:0.75rem;flex-wrap:wrap;margin-bottom:2rem;">
                    <span style="background:rgba(30,41,54,0.6);padding:0.4rem 0.85rem;border-radius:8px;color:#94a3b8;font-size:0.8rem;border:1px solid rgba(58,69,84,0.5);"><i data-lucide="git-branch" style="width:13px;height:13px;display:inline-block;vertical-align:middle;margin-right:0.3rem;color:#ef4444;"></i>Workflows</span>
                    <span style="background:rgba(30,41,54,0.6);padding:0.4rem 0.85rem;border-radius:8px;color:#94a3b8;font-size:0.8rem;border:1px solid rgba(58,69,84,0.5);"><i data-lucide="briefcase" style="width:13px;height:13px;display:inline-block;vertical-align:middle;margin-right:0.3rem;color:#ef4444;"></i>Recruitment</span>
                    <span style="background:rgba(30,41,54,0.6);padding:0.4rem 0.85rem;border-radius:8px;color:#94a3b8;font-size:0.8rem;border:1px solid rgba(58,69,84,0.5);"><i data-lucide="database" style="width:13px;height:13px;display:inline-block;vertical-align:middle;margin-right:0.3rem;color:#ef4444;"></i>Applicants</span>
                    <span style="background:rgba(30,41,54,0.6);padding:0.4rem 0.85rem;border-radius:8px;color:#94a3b8;font-size:0.8rem;border:1px solid rgba(58,69,84,0.5);"><i data-lucide="clipboard-list" style="width:13px;height:13px;display:inline-block;vertical-align:middle;margin-right:0.3rem;color:#ef4444;"></i>Onboarding</span>
                    <span style="background:rgba(30,41,54,0.6);padding:0.4rem 0.85rem;border-radius:8px;color:#94a3b8;font-size:0.8rem;border:1px solid rgba(58,69,84,0.5);"><i data-lucide="badge-check" style="width:13px;height:13px;display:inline-block;vertical-align:middle;margin-right:0.3rem;color:#ef4444;"></i>Performance</span>
                    <span style="background:rgba(30,41,54,0.6);padding:0.4rem 0.85rem;border-radius:8px;color:#94a3b8;font-size:0.8rem;border:1px solid rgba(58,69,84,0.5);"><i data-lucide="gift" style="width:13px;height:13px;display:inline-block;vertical-align:middle;margin-right:0.3rem;color:#ef4444;"></i>Recognition</span>
                    <span style="background:rgba(30,41,54,0.6);padding:0.4rem 0.85rem;border-radius:8px;color:#94a3b8;font-size:0.8rem;border:1px solid rgba(58,69,84,0.5);"><i data-lucide="bar-chart-3" style="width:13px;height:13px;display:inline-block;vertical-align:middle;margin-right:0.3rem;color:#ef4444;"></i>Analytics</span>
                </div>
                <div class="loading-spinner" style="margin:0 auto;width:30px;height:30px;border:3px solid rgba(239,68,68,0.2);border-top-color:#ef4444;border-radius:50%;animation:spin 1s linear infinite;"></div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/spa.js"></script>
    <?php include '../../includes/logout-modal.php'; ?>
    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</body>
</html>
