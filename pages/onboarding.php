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
// Database
require_once '../database/config.php';

// Safe output
function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// Upcoming hires (next 5)
try {
    $upcomingHires = fetchAll(
        "SELECT e.id, e.first_name, e.last_name, e.position, e.hire_date,
                (SELECT COUNT(*) FROM onboarding_tasks t WHERE t.employee_id = e.id) AS total_tasks,
                (SELECT COUNT(*) FROM onboarding_tasks t WHERE t.employee_id = e.id AND t.status = 'completed') AS completed_tasks
         FROM employees e
         WHERE e.hire_date IS NOT NULL AND e.hire_date >= CURDATE()
         ORDER BY e.hire_date ASC
         LIMIT 5"
    );
} catch (Exception $e) {
    $upcomingHires = [];
}

// Pick primary employee to show checklist (first upcoming if available, else latest recent hire)
$primaryEmployeeId = $upcomingHires[0]['id'] ?? null;
if (!$primaryEmployeeId) {
    try {
        $fallback = fetchSingle("SELECT id FROM employees ORDER BY hire_date DESC LIMIT 1");
        $primaryEmployeeId = $fallback['id'] ?? null;
    } catch (Exception $e) {
        $primaryEmployeeId = null;
    }
}

// Load checklist tasks for primary employee
if ($primaryEmployeeId) {
    try {
        $checklistTasks = fetchAll(
            "SELECT id, task_name, status, due_date, completed_date
             FROM onboarding_tasks
             WHERE employee_id = ?
             ORDER BY FIELD(status,'pending','in_progress','completed'), due_date ASC NULLS LAST",
            [$primaryEmployeeId]
        );
    } catch (Exception $e) {
        $checklistTasks = [];
    }
} else {
    $checklistTasks = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Management System - Onboarding</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<?php 
$active_page = 'onboarding';
$page_title = 'Employee Onboarding';
include '../partials/sidebar.php';
include '../partials/header.php';
?>
            <!-- Onboarding Module -->
            <div id="onboarding-module" class="module active">
                <div class="module-header">
                    <h2>Employee Onboarding</h2>
                    <button class="btn btn-primary" onclick="showSubModule('pre-boarding')">New Hire</button>
                </div>

                <div class="submodule-nav">
                    <button class="submodule-btn active" data-submodule="pre-boarding">Pre-boarding</button>
                    <button class="submodule-btn" data-submodule="orientation">Orientation</button>
                    <button class="submodule-btn" data-submodule="integration">Role Integration</button>
                </div>

                <!-- Pre-boarding Submodule -->
                <div id="pre-boarding" class="submodule active">
                    <div class="onboarding-checklist">
                        <h3>Pre-boarding Checklist</h3>
                        <div class="checklist-container">
                            <?php if (!empty($checklistTasks)) : ?>
                                <?php foreach ($checklistTasks as $t) : ?>
                                    <?php
                                        $isCompleted = strtolower((string)$t['status']) === 'completed';
                                        $isInProgress = strtolower((string)$t['status']) === 'in_progress';
                                        $dateNote = $isCompleted && $t['completed_date'] ? ('Completed ' . date('M d, Y', strtotime($t['completed_date']))) :
                                                    ($t['due_date'] ? ('Due ' . date('M d, Y', strtotime($t['due_date']))) : '');
                                    ?>
                                    <div class="checklist-item<?php echo $isCompleted ? ' completed' : ''; ?>">
                                        <input type="checkbox" <?php echo $isCompleted ? 'checked disabled' : ($isInProgress ? 'checked' : ''); ?>>
                                        <span><?php echo h($t['task_name']); ?></span>
                                        <?php if ($dateNote) : ?><small><?php echo h($dateNote); ?></small><?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <div class="checklist-item">
                                    <input type="checkbox" disabled>
                                    <span>No tasks yet for this hire</span>
                                    <small>Create tasks in the admin</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="new-hires-list">
                        <h3>Upcoming New Hires</h3>
        					<div class="hire-cards">
                            <?php if (!empty($upcomingHires)) : ?>
                                <?php foreach ($upcomingHires as $h) : ?>
                                    <?php 
                                        $total = (int)$h['total_tasks'];
                                        $done = (int)$h['completed_tasks'];
                                        $pct = $total > 0 ? round(($done / $total) * 100) : 0;
                                    ?>
                                    <div class="hire-card">
                                        <div class="hire-info">
                                            <h4><?php echo h($h['first_name'] . ' ' . $h['last_name']); ?></h4>
                                            <p><?php echo h($h['position']); ?></p>
                                            <p>Start Date: <?php echo h(date('M d, Y', strtotime($h['hire_date']))); ?></p>
                                        </div>
                                        <div class="hire-progress">
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $pct; ?>%"></div>
                                            </div>
                                            <span><?php echo $pct; ?>% Complete</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <div class="hire-card">
                                    <div class="hire-info">
                                        <h4>No upcoming hires</h4>
                                        <p>New hires with future start dates will appear here.</p>
                                    </div>
                                    <div class="hire-progress">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: 0%"></div>
                                        </div>
                                        <span>0% Complete</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Orientation Submodule -->
                <div id="orientation" class="submodule">
                    <div class="orientation-program">
                        <h3>Orientation Program</h3>
                        <div class="orientation-schedule">
                            <div class="schedule-item">
                                <div class="schedule-time">9:00 AM</div>
                                <div class="schedule-content">
                                    <h4>Welcome & Company Overview</h4>
                                    <p>Introduction to company culture and values</p>
                                </div>
                            </div>
                            <div class="schedule-item">
                                <div class="schedule-time">10:30 AM</div>
                                <div class="schedule-content">
                                    <h4>HR Policies & Benefits</h4>
                                    <p>Review of employee handbook and benefits</p>
                                </div>
                            </div>
                            <div class="schedule-item">
                                <div class="schedule-time">1:00 PM</div>
                                <div class="schedule-content">
                                    <h4>Department Introduction</h4>
                                    <p>Meet the team and understand department goals</p>
                                </div>
                            </div>
                            <div class="schedule-item">
                                <div class="schedule-time">3:00 PM</div>
                                <div class="schedule-content">
                                    <h4>IT Setup & Training</h4>
                                    <p>System access and tool training</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Role Integration Submodule -->
                <div id="integration" class="submodule">
                    <div class="integration-plan">
                        <h3>30-60-90 Day Plan</h3>
                        <div class="plan-tabs">
                            <button class="plan-tab active" data-period="30">30 Days</button>
                            <button class="plan-tab" data-period="60">60 Days</button>
                            <button class="plan-tab" data-period="90">90 Days</button>
                        </div>
                        
                        <div class="plan-content">
                            <div id="plan-30" class="plan-period active">
                                <h4>First 30 Days - Learning & Adaptation</h4>
                                <ul class="plan-goals">
                                    <li>Complete all required training modules</li>
                                    <li>Shadow team members and understand workflows</li>
                                    <li>Set up development environment</li>
                                    <li>Complete first small project</li>
                                    <li>Weekly check-ins with manager</li>
                                </ul>
                            </div>
                            
                            <div id="plan-60" class="plan-period">
                                <h4>60 Days - Contributing & Growing</h4>
                                <ul class="plan-goals">
                                    <li>Take ownership of medium-sized projects</li>
                                    <li>Participate in team meetings and planning</li>
                                    <li>Provide feedback on processes</li>
                                    <li>Build relationships across departments</li>
                                    <li>Complete performance review</li>
                                </ul>
                            </div>
                            
                            <div id="plan-90" class="plan-period">
                                <h4>90 Days - Full Integration</h4>
                                <ul class="plan-goals">
                                    <li>Lead projects independently</li>
                                    <li>Mentor newer team members</li>
                                    <li>Contribute to strategic planning</li>
                                    <li>Set long-term career goals</li>
                                    <li>Complete comprehensive review</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<?php include '../partials/footer.php'; ?>
