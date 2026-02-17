<?php
require_once '../../includes/session_helper.php';
require_once '../../includes/rbac_helper.php';
startSecureSession();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit();
}

$userId = $_SESSION['user_id'];
$user = getUserWithRole($userId);

if (!$user || !in_array($user['role_type'] ?? '', ['Manager', 'Admin', 'HR_Staff'])) {
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
    <title>Manager Panel - HR1</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../../assets/css/spa.css">
</head>
<body data-role="manager">
    <?php $logo_path = '../../assets/images/slate.png'; include '../../includes/loading-screen.php'; ?>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-section">
                <img src="../../assets/images/slate.png" alt="SLATE Logo">
                <h2>Manager Panel</h2>
                <p><?php echo $user_name; ?></p>
                <span class="role-badge">MANAGER</span>
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

            <div class="nav-section-title">Recruitment</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="job-requisitions">
                        <i data-lucide="file-plus"></i>
                        Job Requisitions
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="interview-panel">
                        <i data-lucide="message-square"></i>
                        Interview Panel
                    </a>
                </li>
            </ul>

            <div class="nav-section-title">Team Management</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="my-team">
                        <i data-lucide="users"></i>
                        My Team
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="performance-reviews">
                        <i data-lucide="clipboard-check"></i>
                        Performance Reviews
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="goal-setting">
                        <i data-lucide="target"></i>
                        Goal Setting
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
                <div style="width:70px;height:70px;margin:0 auto 1.5rem;background:rgba(245,158,11,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <i data-lucide="briefcase" style="width:36px;height:36px;color:#f59e0b;"></i>
                </div>
                <h2 style="color:#e2e8f0;margin-bottom:0.5rem;">Welcome, <?php echo $user_name; ?></h2>
                <p style="color:#94a3b8;margin-bottom:2rem;">Loading your <strong style="color:#f59e0b;">Manager</strong> dashboard...</p>
                <div style="display:flex;justify-content:center;gap:1rem;flex-wrap:wrap;margin-bottom:2rem;">
                    <span style="background:rgba(30,41,54,0.6);padding:0.5rem 1rem;border-radius:8px;color:#94a3b8;font-size:0.85rem;border:1px solid rgba(58,69,84,0.5);"><i data-lucide="file-plus" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:0.4rem;color:#f59e0b;"></i>Requisitions</span>
                    <span style="background:rgba(30,41,54,0.6);padding:0.5rem 1rem;border-radius:8px;color:#94a3b8;font-size:0.85rem;border:1px solid rgba(58,69,84,0.5);"><i data-lucide="message-square" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:0.4rem;color:#f59e0b;"></i>Interviews</span>
                    <span style="background:rgba(30,41,54,0.6);padding:0.5rem 1rem;border-radius:8px;color:#94a3b8;font-size:0.85rem;border:1px solid rgba(58,69,84,0.5);"><i data-lucide="users" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:0.4rem;color:#f59e0b;"></i>My Team</span>
                    <span style="background:rgba(30,41,54,0.6);padding:0.5rem 1rem;border-radius:8px;color:#94a3b8;font-size:0.85rem;border:1px solid rgba(58,69,84,0.5);"><i data-lucide="clipboard-check" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:0.4rem;color:#f59e0b;"></i>Reviews</span>
                    <span style="background:rgba(30,41,54,0.6);padding:0.5rem 1rem;border-radius:8px;color:#94a3b8;font-size:0.85rem;border:1px solid rgba(58,69,84,0.5);"><i data-lucide="target" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:0.4rem;color:#f59e0b;"></i>Goals</span>
                </div>
                <div class="loading-spinner" style="margin:0 auto;width:30px;height:30px;border:3px solid rgba(245,158,11,0.2);border-top-color:#f59e0b;border-radius:50%;animation:spin 1s linear infinite;"></div>
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
