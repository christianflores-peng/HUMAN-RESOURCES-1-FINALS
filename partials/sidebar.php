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
<!-- Material Symbols Font -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block" />

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
                        <span class="material-symbols-outlined icon">dashboard</span>Dashboard
                    </a></li>
                    <li><a href="employee-portal.php" class="menu-item <?php echo $active_page === 'employee-portal' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined icon">badge</span>Employee Portal
                    </a></li>
                </ul>
            </div>
            
            <div class="menu-section">
                <h3>Public Access</h3>
                <ul>
                    <li><a href="../careers.php" class="menu-item" target="_blank">
                        <span class="material-symbols-outlined icon">public</span>Careers Page
                    </a></li>
                    <li><a href="../logout.php" class="menu-item">
                        <span class="material-symbols-outlined icon">logout</span>Logout
                    </a></li>
                </ul>
            </div>
        <?php else: ?>
            <!-- FULL MENU FOR HR/MANAGER/ADMIN -->
            <div class="menu-section">
                <h3>Main</h3>
                <ul>
                    <li><a href="../index.php" class="menu-item">
                        <span class="material-symbols-outlined icon">home</span>Home
                    </a></li>
                    <li><a href="dashboard.php" class="menu-item <?php echo $active_page === 'dashboard' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined icon">dashboard</span>Dashboard
                    </a></li>
                    <li><a href="../applicant-portal.php" class="menu-item <?php echo $active_page === 'applicant-portal' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined icon">person_search</span>Applicant Portal
                    </a></li>
                </ul>
            </div>
            
            <div class="menu-section">
                <h3>Role Dashboards</h3>
                <ul>
                    <?php if ($isHR || $isAdmin): ?>
                    <li><a href="hr-recruitment-dashboard.php" class="menu-item <?php echo $active_page === 'hr-recruitment' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined icon">work</span>HR Recruitment
                    </a></li>
                    <?php endif; ?>
                    
                    <?php if ($isManager || $isAdmin): ?>
                    <li><a href="manager-dashboard.php" class="menu-item <?php echo $active_page === 'manager-dashboard' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined icon">supervisor_account</span>Manager Portal
                    </a></li>
                    <?php endif; ?>
                    
                    <?php if ($isAdmin): ?>
                    <li><a href="admin-dashboard.php" class="menu-item <?php echo $active_page === 'admin-dashboard' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined icon">admin_panel_settings</span>Admin Dashboard
                    </a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <?php if ($isHR || $isAdmin): ?>
            <div class="menu-section">
                <h3>HR Modules</h3>
                <ul>
                    <li><a href="hr-recruitment-dashboard.php" class="menu-item <?php echo $active_page === 'recruitment' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined icon">work</span>Recruitment
                    </a></li>
                    <li><a href="applications.php" class="menu-item <?php echo $active_page === 'applications' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined icon">description</span>Job Applications
                    </a></li>
                    <li><a href="applicant-management.php" class="menu-item <?php echo $active_page === 'applicant-management' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined icon">groups</span>Applicant Management
                    </a></li>
                    <li><a href="onboarding.php" class="menu-item <?php echo $active_page === 'onboarding' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined icon">how_to_reg</span>Onboarding
                    </a></li>
                    <li><a href="performance.php" class="menu-item <?php echo $active_page === 'performance' ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined icon">trending_up</span>Performance Management
                    </a></li>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="menu-section">
                <h3>Public Access</h3>
                <ul>
                    <li><a href="../careers.php" class="menu-item" target="_blank">
                        <span class="material-symbols-outlined icon">public</span>Careers Page
                    </a></li>
                    <li><a href="../logout.php" class="menu-item">
                        <span class="material-symbols-outlined icon">logout</span>Logout
                    </a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</nav>
