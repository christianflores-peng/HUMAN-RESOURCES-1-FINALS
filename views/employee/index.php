<?php
require_once '../../includes/session_helper.php';
startSecureSession();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Employee') {
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
    <title>Employee Panel - HR1</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../../assets/css/spa.css">
</head>
<body data-role="employee">
    <?php $logo_path = '../../assets/images/slate.png'; include '../../includes/loading-screen.php'; ?>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-section">
                <img src="../../assets/images/slate.png" alt="SLATE Logo">
                <h2>Employee Panel</h2>
                <p><?php echo $user_name; ?></p>
                <span class="role-badge">EMPLOYEE</span>
            </div>

            <div class="nav-section-title">Main</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="dashboard">
                        <i data-lucide="layout-dashboard"></i>
                        Dashboard
                    </a>
                </li>
            </ul>

            <div class="nav-section-title">Onboarding</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="onboarding">
                        <i data-lucide="list-checks"></i>
                        Onboarding Checklist
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="requirements">
                        <i data-lucide="upload"></i>
                        Submit Requirements
                    </a>
                </li>
            </ul>

            <div class="nav-section-title">My Info</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="profile">
                        <i data-lucide="user"></i>
                        My Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="documents">
                        <i data-lucide="folder"></i>
                        My Documents
                    </a>
                </li>
            </ul>

            <div class="nav-section-title">Culture</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="recognition-wall">
                        <i data-lucide="trophy"></i>
                        Recognition Wall
                    </a>
                </li>
            </ul>

            <div class="nav-section-title">Resources</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="handbook">
                        <i data-lucide="book-open"></i>
                        Handbook
                    </a>
                </li>
            </ul>

            <div class="nav-section-title">Account</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="../../auth/logout.php" class="nav-link">
                        <i data-lucide="log-out"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </aside>

        <main class="main-content" id="spa-content">
            <div style="text-align:center;padding:4rem 2rem;">
                <div style="width:70px;height:70px;margin:0 auto 1.5rem;background:rgba(16,185,129,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <i data-lucide="user" style="width:36px;height:36px;color:#10b981;"></i>
                </div>
                <h2 style="color:#e2e8f0;margin-bottom:0.5rem;">Welcome, <?php echo $user_name; ?></h2>
                <p style="color:#94a3b8;margin-bottom:2rem;">Loading your <strong style="color:#10b981;">Employee</strong> dashboard...</p>
                <div style="display:flex;justify-content:center;gap:1rem;flex-wrap:wrap;margin-bottom:2rem;">
                    <span style="background:rgba(30,41,54,0.6);padding:0.5rem 1rem;border-radius:8px;color:#94a3b8;font-size:0.85rem;border:1px solid rgba(58,69,84,0.5);"><i data-lucide="list-checks" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:0.4rem;color:#10b981;"></i>Onboarding</span>
                    <span style="background:rgba(30,41,54,0.6);padding:0.5rem 1rem;border-radius:8px;color:#94a3b8;font-size:0.85rem;border:1px solid rgba(58,69,84,0.5);"><i data-lucide="upload" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:0.4rem;color:#10b981;"></i>Requirements</span>
                    <span style="background:rgba(30,41,54,0.6);padding:0.5rem 1rem;border-radius:8px;color:#94a3b8;font-size:0.85rem;border:1px solid rgba(58,69,84,0.5);"><i data-lucide="user" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:0.4rem;color:#10b981;"></i>Profile</span>
                    <span style="background:rgba(30,41,54,0.6);padding:0.5rem 1rem;border-radius:8px;color:#94a3b8;font-size:0.85rem;border:1px solid rgba(58,69,84,0.5);"><i data-lucide="folder" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:0.4rem;color:#10b981;"></i>Documents</span>
                    <span style="background:rgba(30,41,54,0.6);padding:0.5rem 1rem;border-radius:8px;color:#94a3b8;font-size:0.85rem;border:1px solid rgba(58,69,84,0.5);"><i data-lucide="trophy" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:0.4rem;color:#10b981;"></i>Recognition</span>
                </div>
                <div class="loading-spinner" style="margin:0 auto;width:30px;height:30px;border:3px solid rgba(16,185,129,0.2);border-top-color:#10b981;border-radius:50%;animation:spin 1s linear infinite;"></div>
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
