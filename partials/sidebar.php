<?php
// Sidebar partial - accepts $active_page parameter to highlight current page
$active_page = $active_page ?? 'dashboard';

// Get user role for conditional menu display
$userRoleId = $_SESSION['role_id'] ?? 0;
$isEmployee = in_array($userRoleId, [7, 8]); // Employee or New Hire
$isHR = in_array($userRoleId, [2, 3]); // HR Staff or HR Manager
$isManager = in_array($userRoleId, [4, 5, 6]); // Manager roles
$isAdmin = ($userRoleId == 1); // Admin
?>
<!-- Lucide Icons -->
<script src="https://unpkg.com/lucide@latest"></script>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="app-brand">
            <img src="../assets/images/slate.png" alt="HR1 Logo" class="app-logo">
            <h2 class="dept-name">HR1</h2>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">☰</button>
    </div>
    
    <div class="sidebar-menu">
        <?php if ($isEmployee): ?>
            <!-- EMPLOYEE SIMPLIFIED MENU -->
            <div class="menu-section">
                <h3>Main</h3>
                <ul>
                    <li><a href="dashboard.php" class="menu-item <?php echo $active_page === 'dashboard' ? 'active' : ''; ?>">
                        <i data-lucide="layout-dashboard" class="icon"></i>Dashboard
                    </a></li>
                    <li><a href="employee-portal.php" class="menu-item <?php echo $active_page === 'employee-portal' ? 'active' : ''; ?>">
                        <i data-lucide="id-card" class="icon"></i>Employee Portal
                    </a></li>
                </ul>
            </div>
            
            <div class="menu-section">
                <h3>Public Access</h3>
                <ul>
                    <li><a href="../careers.php" class="menu-item" target="_blank">
                        <i data-lucide="globe" class="icon"></i>Careers Page
                    </a></li>
                    <li><a href="../logout.php" class="menu-item">
                        <i data-lucide="log-out" class="icon"></i>Logout
                    </a></li>
                </ul>
            </div>
        <?php else: ?>
            <!-- FULL MENU FOR HR/MANAGER/ADMIN -->
            <div class="menu-section">
                <h3>Main</h3>
                <ul>
                    <li><a href="../index.php" class="menu-item">
                        <i data-lucide="home" class="icon"></i>Home
                    </a></li>
                    <li><a href="dashboard.php" class="menu-item <?php echo $active_page === 'dashboard' ? 'active' : ''; ?>">
                        <i data-lucide="layout-dashboard" class="icon"></i>Dashboard
                    </a></li>
                    <li><a href="../applicant-portal.php" class="menu-item <?php echo $active_page === 'applicant-portal' ? 'active' : ''; ?>">
                        <i data-lucide="user-search" class="icon"></i>Applicant Portal
                    </a></li>
                </ul>
            </div>
            
            <div class="menu-section">
                <h3>Role Dashboards</h3>
                <ul>
                    <?php if ($isHR || $isAdmin): ?>
                    <li><a href="hr-recruitment-dashboard.php" class="menu-item <?php echo $active_page === 'hr-recruitment' ? 'active' : ''; ?>">
                        <i data-lucide="briefcase" class="icon"></i>HR Recruitment
                    </a></li>
                    <?php endif; ?>
                    
                    <?php if ($isManager || $isAdmin): ?>
                    <li><a href="manager-dashboard.php" class="menu-item <?php echo $active_page === 'manager-dashboard' ? 'active' : ''; ?>">
                        <i data-lucide="user-check" class="icon"></i>Manager Portal
                    </a></li>
                    <?php endif; ?>
                    
                    <?php if ($isAdmin): ?>
                    <li><a href="admin-dashboard.php" class="menu-item <?php echo $active_page === 'admin-dashboard' ? 'active' : ''; ?>">
                        <i data-lucide="shield" class="icon"></i>Admin Dashboard
                    </a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <?php if ($isHR || $isAdmin): ?>
            <div class="menu-section">
                <h3>HR Modules</h3>
                <ul>
                    <li><a href="hr-recruitment-dashboard.php" class="menu-item <?php echo $active_page === 'recruitment' ? 'active' : ''; ?>">
                        <i data-lucide="briefcase" class="icon"></i>Recruitment
                    </a></li>
                    <li><a href="applications.php" class="menu-item <?php echo $active_page === 'applications' ? 'active' : ''; ?>">
                        <i data-lucide="file-text" class="icon"></i>Job Applications
                    </a></li>
                    <li><a href="applicant-management.php" class="menu-item <?php echo $active_page === 'applicant-management' ? 'active' : ''; ?>">
                        <i data-lucide="users" class="icon"></i>Applicant Management
                    </a></li>
                    <li><a href="onboarding.php" class="menu-item <?php echo $active_page === 'onboarding' ? 'active' : ''; ?>">
                        <i data-lucide="user-check" class="icon"></i>Onboarding
                    </a></li>
                    <li><a href="performance.php" class="menu-item <?php echo $active_page === 'performance' ? 'active' : ''; ?>">
                        <i data-lucide="trending-up" class="icon"></i>Performance Management
                    </a></li>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="menu-section">
                <h3>Public Access</h3>
                <ul>
                    <li><a href="../careers.php" class="menu-item" target="_blank">
                        <i data-lucide="globe" class="icon"></i>Careers Page
                    </a></li>
                    <li><a href="../logout.php" class="menu-item">
                        <i data-lucide="log-out" class="icon"></i>Logout
                    </a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</nav>

<script>
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
