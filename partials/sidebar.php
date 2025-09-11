<?php
// Sidebar partial - accepts $active_page parameter to highlight current page
$active_page = $active_page ?? 'dashboard';
?>
<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="app-brand">
            <img src="../assets/images/slate.png" alt="SLATE Logo" class="app-logo">
            <h2 class="dept-name">HR1</h2>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">☰</button>
    </div>
    
    <div class="sidebar-menu">
        <div class="menu-section">
            <h3>Main</h3>
            <ul>
                <li><a href="dashboard.php" class="menu-item <?php echo $active_page === 'dashboard' ? 'active' : ''; ?>">
                    <span class="icon">🏠︎</span>Dashboard
                </a></li>
            </ul>
        </div>
        
        <div class="menu-section">
            <h3>HR Modules</h3>
            <ul>
                <li><a href="recruitment.php" class="menu-item <?php echo $active_page === 'recruitment' ? 'active' : ''; ?>">
                    <span class="icon">📋</span>Recruitment
                </a></li>
                <li><a href="applicant-management.php" class="menu-item <?php echo $active_page === 'applicant-management' ? 'active' : ''; ?>">
                    <span class="icon">👥</span>Applicant Management
                </a></li>
                <li><a href="onboarding.php" class="menu-item <?php echo $active_page === 'onboarding' ? 'active' : ''; ?>">
                    <span class="icon">🎯</span>Onboarding
                </a></li>
                <li><a href="performance.php" class="menu-item <?php echo $active_page === 'performance' ? 'active' : ''; ?>">
                    <span class="icon">📈</span>Performance Management
                </a></li>
                <li><a href="recognition.php" class="menu-item <?php echo $active_page === 'recognition' ? 'active' : ''; ?>">
                    <span class="icon">🏆</span>Social Recognition
                </a></li>
            </ul>
        </div>
        
        <div class="menu-section">
            <h3>Public Access</h3>
            <ul>
                <li><a href="../careers.php" class="menu-item" target="_blank">
                    <span class="icon">🌐</span>Careers Page
                </a></li>
                <li><a href="../logout.php" class="menu-item">
                    <span class="icon">🚪</span>Logout
                </a></li>
            </ul>
        </div>
    </div>
</nav>
