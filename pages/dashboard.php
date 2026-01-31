<?php
session_start();

require_once '../database/config.php';
require_once '../includes/rbac_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$user = getUserWithRole($userId);
$roleType = $_SESSION['role_type'] ?? 'Employee';

// Get current user info
$current_user = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
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
<?php $logo_path = '../assets/images/slate.png'; include '../includes/loading-screen.php'; ?>

<?php 
$active_page = 'dashboard';
$page_title = 'Dashboard';
include '../partials/sidebar.php';
include '../partials/header.php';
?>
            <!-- Dashboard Module -->
            <div id="dashboard-module" class="module active">
                <!-- Role-Based Quick Access Cards -->
                <div style="margin-bottom: 2rem;">
                    <h2 style="color: #ffffff; margin-bottom: 1rem; font-size: 1.5rem;">Quick Access</h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                        
                        <?php if (isAdmin($userId)): ?>
                        <a href="admin-dashboard.php" style="text-decoration: none;">
                            <div style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3); transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
                                <span class="material-symbols-outlined" style="font-size: 3rem; color: white; display: block; margin-bottom: 1rem;">admin_panel_settings</span>
                                <h3 style="color: white; font-size: 1.25rem; margin-bottom: 0.5rem;">Admin Dashboard</h3>
                                <p style="color: rgba(255,255,255,0.9); font-size: 0.9rem;">User management, audit logs, system settings</p>
                            </div>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (in_array($user['role_id'] ?? 0, [7, 8])): ?>
                        <a href="employee-portal.php" style="text-decoration: none;">
                            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
                                <span class="material-symbols-outlined" style="font-size: 3rem; color: white; display: block; margin-bottom: 1rem;">badge</span>
                                <h3 style="color: white; font-size: 1.25rem; margin-bottom: 0.5rem;">Employee Portal</h3>
                                <p style="color: rgba(255,255,255,0.9); font-size: 0.9rem;">Onboarding tasks, handbook, personal info</p>
                            </div>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (isHRStaff($userId) || isAdmin($userId)): ?>
                        <a href="hr-recruitment-dashboard.php" style="text-decoration: none;">
                            <div style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3); transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
                                <span class="material-symbols-outlined" style="font-size: 3rem; color: white; display: block; margin-bottom: 1rem;">work</span>
                                <h3 style="color: white; font-size: 1.25rem; margin-bottom: 0.5rem;">HR Recruitment</h3>
                                <p style="color: rgba(255,255,255,0.9); font-size: 0.9rem;">Kanban board, applicant pipeline, hiring</p>
                            </div>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (isManager($userId) || isAdmin($userId)): ?>
                        <a href="manager-dashboard.php" style="text-decoration: none;">
                            <div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3); transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
                                <span class="material-symbols-outlined" style="font-size: 3rem; color: white; display: block; margin-bottom: 1rem;">supervisor_account</span>
                                <h3 style="color: white; font-size: 1.25rem; margin-bottom: 0.5rem;">Manager Portal</h3>
                                <p style="color: rgba(255,255,255,0.9); font-size: 0.9rem;">Team roster, assign tasks, upload handbooks</p>
                            </div>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!in_array($user['role_id'] ?? 0, [7, 8])): ?>
                        <a href="recruitment.php" style="text-decoration: none;">
                            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
                                <span class="material-symbols-outlined" style="font-size: 3rem; color: white; display: block; margin-bottom: 1rem;">description</span>
                                <h3 style="color: white; font-size: 1.25rem; margin-bottom: 0.5rem;">Job Postings</h3>
                                <p style="color: rgba(255,255,255,0.9); font-size: 0.9rem;">View and manage job requisitions</p>
                            </div>
                        </a>
                        <?php endif; ?>
                        
                    </div>
                </div>

                <?php if (in_array($user['role_id'] ?? 0, [7, 8])): ?>
                    <!-- EMPLOYEE VIEW -->
                    <?php
                    // Get employee onboarding progress
                    $onboardingProgress = 0;
                    $completedTasks = 0;
                    $totalTasks = 0;
                    try {
                        $tasks = fetchAll(
                            "SELECT COUNT(*) as total, 
                                    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed
                             FROM employee_onboarding_progress 
                             WHERE user_id = ?",
                            [$userId]
                        );
                        if (!empty($tasks)) {
                            $totalTasks = $tasks[0]['total'];
                            $completedTasks = $tasks[0]['completed'];
                            $onboardingProgress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
                        }
                    } catch (Exception $e) {
                        $onboardingProgress = 0;
                    }
                    
                    // Get days since hire
                    $daysSinceHire = 0;
                    if (!empty($user['hire_date'])) {
                        $hireDate = new DateTime($user['hire_date']);
                        $today = new DateTime();
                        $daysSinceHire = $today->diff($hireDate)->days;
                    }
                    ?>
                    
                    <h2 style="color: #ffffff; margin-bottom: 1rem; font-size: 1.5rem;">My Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon"><span class="material-symbols-outlined" style="font-size: 3rem; color: #10b981;">assignment</span></div>
                            <div class="stat-info">
                                <h3>Onboarding Progress</h3>
                                <p class="stat-number"><?php echo $onboardingProgress; ?>%</p>
                                <span class="stat-change positive"><?php echo $completedTasks; ?> of <?php echo $totalTasks; ?> tasks completed</span>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon"><span class="material-symbols-outlined" style="font-size: 3rem; color: #0ea5e9;">calendar_today</span></div>
                            <div class="stat-info">
                                <h3>Days with Company</h3>
                                <p class="stat-number"><?php echo $daysSinceHire; ?></p>
                                <span class="stat-change">Since <?php echo !empty($user['hire_date']) ? date('M d, Y', strtotime($user['hire_date'])) : 'N/A'; ?></span>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon"><span class="material-symbols-outlined" style="font-size: 3rem; color: #8b5cf6;">badge</span></div>
                            <div class="stat-info">
                                <h3>My Department</h3>
                                <p class="stat-number" style="font-size: 1.5rem;"><?php echo htmlspecialchars($user['department_name'] ?? 'N/A'); ?></p>
                                <span class="stat-change"><?php echo htmlspecialchars($user['job_title'] ?? 'Employee'); ?></span>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon"><span class="material-symbols-outlined" style="font-size: 3rem; color: #f59e0b;">pending_actions</span></div>
                            <div class="stat-info">
                                <h3>Pending Tasks</h3>
                                <p class="stat-number"><?php echo $totalTasks - $completedTasks; ?></p>
                                <span class="stat-change">Action required</span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- ADMIN/HR/MANAGER VIEW -->
                    <h2 style="color: #ffffff; margin-bottom: 1rem; font-size: 1.5rem;">System Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon"><span class="material-symbols-outlined" style="font-size: 3rem; color: #0ea5e9;">groups</span></div>
                            <div class="stat-info">
                                <h3>Total Employees</h3>
                                <p class="stat-number">1,247</p>
                                <span class="stat-change positive">+12 this month</span>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon"><span class="material-symbols-outlined" style="font-size: 3rem; color: #0ea5e9;">description</span></div>
                            <div class="stat-info">
                                <h3>Active Recruitments</h3>
                                <p class="stat-number">34</p>
                                <span class="stat-change positive">+5 this week</span>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon"><span class="material-symbols-outlined" style="font-size: 3rem; color: #0ea5e9;">trending_up</span></div>
                            <div class="stat-info">
                                <h3>Performance Score</h3>
                                <p class="stat-number">4.2/5</p>
                                <span class="stat-change positive">+0.3 this quarter</span>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon"><span class="material-symbols-outlined" style="font-size: 3rem; color: #0ea5e9;">emoji_events</span></div>
                            <div class="stat-info">
                                <h3>Recognition Awards</h3>
                                <p class="stat-number">156</p>
                                <span class="stat-change positive">+23 this month</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!in_array($user['role_id'] ?? 0, [7, 8])): ?>
                <!-- CHARTS FOR HR/MANAGER/ADMIN ONLY -->
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
                                <span class="activity-icon"><span class="material-symbols-outlined" style="color: #0ea5e9;">description</span></span>
                                <div class="activity-content">
                                    <p>New job requisition created</p>
                                    <small>2 hours ago</small>
                                </div>
                            </div>
                            <div class="activity-item">
                                <span class="activity-icon"><span class="material-symbols-outlined" style="color: #0ea5e9;">person</span></span>
                                <div class="activity-content">
                                    <p>Interview scheduled with John Doe</p>
                                    <small>4 hours ago</small>
                                </div>
                            </div>
                            <div class="activity-item">
                                <span class="activity-icon"><span class="material-symbols-outlined" style="color: #0ea5e9;">emoji_events</span></span>
                                <div class="activity-content">
                                    <p>Employee recognition awarded</p>
                                    <small>1 day ago</small>
                                </div>
                            </div>
                            <div class="activity-item">
                                <span class="activity-icon"><span class="material-symbols-outlined" style="color: #0ea5e9;">trending_up</span></span>
                                <div class="activity-content">
                                    <p>Performance review completed</p>
                                    <small>2 days ago</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
<?php include '../partials/footer.php'; ?>
