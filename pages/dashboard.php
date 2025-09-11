<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get current user info
$current_user = $_SESSION['username'] ?? 'User';
$current_role = $_SESSION['role'] ?? 'Employee';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Management System - Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<?php 
$active_page = 'dashboard';
$page_title = 'Dashboard';
include '../partials/sidebar.php';
include '../partials/header.php';
?>
            <!-- Dashboard Module -->
            <div id="dashboard-module" class="module active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-info">
                            <h3>Total Employees</h3>
                            <p class="stat-number">1,247</p>
                            <span class="stat-change positive">+12 this month</span>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📋</div>
                        <div class="stat-info">
                            <h3>Active Recruitments</h3>
                            <p class="stat-number">34</p>
                            <span class="stat-change positive">+5 this week</span>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📈</div>
                        <div class="stat-info">
                            <h3>Performance Score</h3>
                            <p class="stat-number">4.2/5</p>
                            <span class="stat-change positive">+0.3 this quarter</span>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🏆</div>
                        <div class="stat-info">
                            <h3>Recognition Awards</h3>
                            <p class="stat-number">156</p>
                            <span class="stat-change positive">+23 this month</span>
                        </div>
                    </div>
                </div>

                <div class="dashboard-charts">
                    <div class="chart-container">
                        <h3>Recruitment Pipeline</h3>
                        <div class="progress-chart">
                            <div class="progress-item">
                                <span>Applications</span>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 85%"></div>
                                </div>
                                <span>340</span>
                            </div>
                            <div class="progress-item">
                                <span>Screening</span>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 65%"></div>
                                </div>
                                <span>156</span>
                            </div>
                            <div class="progress-item">
                                <span>Interviews</span>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 45%"></div>
                                </div>
                                <span>89</span>
                            </div>
                            <div class="progress-item">
                                <span>Offers</span>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 25%"></div>
                                </div>
                                <span>23</span>
                            </div>
                        </div>
                    </div>

                    <div class="chart-container">
                        <h3>Recent Activities</h3>
                        <div class="activity-list">
                            <div class="activity-item">
                                <span class="activity-icon">📋</span>
                                <div class="activity-content">
                                    <p>New job requisition created</p>
                                    <small>2 hours ago</small>
                                </div>
                            </div>
                            <div class="activity-item">
                                <span class="activity-icon">👤</span>
                                <div class="activity-content">
                                    <p>Interview scheduled with John Doe</p>
                                    <small>4 hours ago</small>
                                </div>
                            </div>
                            <div class="activity-item">
                                <span class="activity-icon">🏆</span>
                                <div class="activity-content">
                                    <p>Employee recognition awarded</p>
                                    <small>1 day ago</small>
                                </div>
                            </div>
                            <div class="activity-item">
                                <span class="activity-icon">📈</span>
                                <div class="activity-content">
                                    <p>Performance review completed</p>
                                    <small>2 days ago</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<?php include '../partials/footer.php'; ?>
