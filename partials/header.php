<?php
// Header partial - accepts $page_title, $current_user, $current_role
$page_title = $page_title ?? 'HR Management';
$current_user = $current_user ?? ($_SESSION['username'] ?? 'User');
$current_role = $current_role ?? ($_SESSION['role'] ?? 'Employee');
?>
<!-- Main Content -->
<main class="main-content" id="mainContent">
    <header class="top-header">
        <div class="header-left">
            <h1 id="pageTitle"><?php echo htmlspecialchars($page_title); ?></h1>
        </div>
        <div class="header-right">
            <span id="userInfo">Welcome, <span id="currentUser"><?php echo htmlspecialchars($current_user); ?></span></span>
            <span id="currentRole" class="role-badge"><?php echo htmlspecialchars($current_role); ?></span>
            <button id="themeToggle" class="theme-toggle" aria-label="Toggle theme"><span class="material-symbols-outlined">dark_mode</span></button>
            <a href="../index.php" class="home-btn">Home</a>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <!-- Content Area -->
    <div class="content-area" id="contentArea">
