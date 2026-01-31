<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check if user has admin access (block Applicants from admin panel)
$admin_roles = ['Administrator', 'HR Manager', 'Recruiter', 'Manager', 'Supervisor', 'Employee'];
if (!in_array($_SESSION['role'] ?? '', $admin_roles)) {
    header('Location: ../careers.php');
    exit();
}

// Get current user info
$current_user = $_SESSION['username'] ?? 'User';
$current_role = $_SESSION['role'] ?? 'Employee';
// Database
require_once '../database/config.php';

// Ensure onboarding_tasks table exists
$pdo = getDBConnection();
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `onboarding_tasks` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `employee_id` int(11) NOT NULL,
        `task_name` varchar(200) NOT NULL,
        `description` text,
        `assigned_to` int(11),
        `due_date` date,
        `status` enum('pending', 'in_progress', 'completed', 'overdue') DEFAULT 'pending',
        `completed_date` timestamp NULL,
        `notes` text,
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    
    // Create orientation_schedules table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `orientation_schedules` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `employee_id` int(11) NOT NULL,
        `session_title` varchar(200) NOT NULL,
        `session_description` text,
        `session_date` date NOT NULL,
        `session_time` time NOT NULL,
        `duration_minutes` int(11) DEFAULT 60,
        `location` varchar(200),
        `facilitator` varchar(100),
        `status` enum('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    
    // Create task_templates table for position-based task suggestions
    $pdo->exec("CREATE TABLE IF NOT EXISTS `task_templates` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `position_keyword` varchar(100) NOT NULL,
        `task_name` varchar(200) NOT NULL,
        `task_description` text,
        `default_days_to_complete` int(11) DEFAULT 7,
        `task_order` int(11) DEFAULT 0,
        `is_active` tinyint(1) DEFAULT 1,
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    
    // Insert default task templates if table is empty
    $count = $pdo->query("SELECT COUNT(*) FROM task_templates")->fetchColumn();
    if ($count == 0) {
        $templates = [
            // Intern tasks
            ['INTERN', 'Complete internship agreement', 'Sign and submit internship agreement form', 3, 1],
            ['INTERN', 'Submit academic requirements', 'Provide proof of enrollment and academic standing', 5, 2],
            ['INTERN', 'Attend intern orientation', 'Participate in intern-specific orientation program', 7, 3],
            ['INTERN', 'Complete training modules', 'Finish all required online training courses', 14, 4],
            ['INTERN', 'Meet with supervisor', 'Initial meeting with assigned supervisor', 3, 5],
            
            // Full-time employee tasks
            ['FULL', 'Complete employment contract', 'Review and sign employment contract', 5, 1],
            ['FULL', 'Submit tax documents', 'Provide W-4 and state tax forms', 7, 2],
            ['FULL', 'Enroll in benefits', 'Complete health insurance and benefits enrollment', 14, 3],
            ['FULL', 'Set up direct deposit', 'Provide banking information for payroll', 7, 4],
            ['FULL', 'Complete background check', 'Submit information for background verification', 10, 5],
            ['FULL', 'IT equipment setup', 'Receive and configure laptop and phone', 3, 6],
            
            // Manager tasks
            ['MANAGER', 'Complete leadership training', 'Attend management fundamentals course', 30, 1],
            ['MANAGER', 'Review team structure', 'Meet with HR to understand team composition', 7, 2],
            ['MANAGER', 'Set up 1-on-1s', 'Schedule initial meetings with direct reports', 14, 3],
            
            // General tasks (apply to all)
            ['ALL', 'Complete I-9 verification', 'Provide employment eligibility documents', 3, 1],
            ['ALL', 'Sign confidentiality agreement', 'Review and sign NDA and company policies', 5, 2],
            ['ALL', 'Complete emergency contact form', 'Provide emergency contact information', 3, 3],
            ['ALL', 'Photo for ID badge', 'Submit photo for employee ID badge', 7, 4],
        ];
        
        $stmt = $pdo->prepare("INSERT INTO task_templates (position_keyword, task_name, task_description, default_days_to_complete, task_order) VALUES (?, ?, ?, ?, ?)");
        foreach ($templates as $template) {
            $stmt->execute($template);
        }
    }
} catch (Exception $e) {
    // Table might already exist or other issue - continue anyway
}

// Safe output
function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Add new onboarding task
    if ($action === 'add_task') {
        $employee_id = (int)($_POST['employee_id'] ?? 0);
        $task_name = trim($_POST['task_name'] ?? '');
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $status = $_POST['status'] ?? 'pending';
        
        if ($employee_id && $task_name) {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("INSERT INTO onboarding_tasks (employee_id, task_name, status, due_date, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$employee_id, $task_name, $status, $due_date]);
                $success_message = "Onboarding task added successfully!";
            } catch (Exception $e) {
                $error_message = "Error adding task: " . $e->getMessage();
            }
        } else {
            $error_message = "Please fill in all required fields.";
        }
    }
    
    // Generate tasks from templates based on position
    if ($action === 'generate_tasks') {
        $employee_id = (int)($_POST['employee_id'] ?? 0);
        
        if ($employee_id) {
            try {
                $pdo = getDBConnection();
                
                // Get employee position
                $employee = fetchSingle("SELECT position FROM employees WHERE id = ?", [$employee_id]);
                $position = strtoupper($employee['position'] ?? '');
                
                // Find matching templates
                $keywords = ['ALL']; // Always include general tasks
                if (strpos($position, 'INTERN') !== false) {
                    $keywords[] = 'INTERN';
                }
                if (strpos($position, 'MANAGER') !== false || strpos($position, 'SUPERVISOR') !== false) {
                    $keywords[] = 'MANAGER';
                }
                if (strpos($position, 'FULL') !== false || (strpos($position, 'INTERN') === false && strpos($position, 'MANAGER') === false)) {
                    $keywords[] = 'FULL';
                }
                
                $placeholders = implode(',', array_fill(0, count($keywords), '?'));
                $templates = fetchAll(
                    "SELECT task_name, default_days_to_complete FROM task_templates 
                     WHERE position_keyword IN ($placeholders) AND is_active = 1 
                     ORDER BY task_order ASC",
                    $keywords
                );
                
                $count = 0;
                $stmt = $pdo->prepare("INSERT INTO onboarding_tasks (employee_id, task_name, status, due_date, created_at) VALUES (?, ?, 'pending', ?, NOW())");
                foreach ($templates as $template) {
                    $due_date = date('Y-m-d', strtotime('+' . $template['default_days_to_complete'] . ' days'));
                    $stmt->execute([$employee_id, $template['task_name'], $due_date]);
                    $count++;
                }
                
                $success_message = "Generated {$count} tasks based on position!";
            } catch (Exception $e) {
                $error_message = "Error generating tasks: " . $e->getMessage();
            }
        }
    }
    
    // Update task status
    if ($action === 'update_task') {
        $task_id = (int)($_POST['task_id'] ?? 0);
        $status = $_POST['status'] ?? 'pending';
        $completed_date = ($status === 'completed') ? date('Y-m-d H:i:s') : null;
        
        if ($task_id) {
            try {
                executeQuery(
                    "UPDATE onboarding_tasks SET status = ?, completed_date = ?, updated_at = NOW() WHERE id = ?",
                    [$status, $completed_date, $task_id]
                );
                $success_message = "Task status updated successfully!";
            } catch (Exception $e) {
                $error_message = "Error updating task: " . $e->getMessage();
            }
        }
    }
    
    // Delete task
    if ($action === 'delete_task') {
        $task_id = (int)($_POST['task_id'] ?? 0);
        
        if ($task_id) {
            try {
                executeQuery("DELETE FROM onboarding_tasks WHERE id = ?", [$task_id]);
                $success_message = "Task deleted successfully!";
            } catch (Exception $e) {
                $error_message = "Error deleting task: " . $e->getMessage();
            }
        }
    }
    
    // Add orientation schedule
    if ($action === 'add_orientation') {
        $employee_id = (int)($_POST['employee_id'] ?? 0);
        $session_title = trim($_POST['session_title'] ?? '');
        $session_description = trim($_POST['session_description'] ?? '');
        $session_date = $_POST['session_date'] ?? '';
        $session_time = $_POST['session_time'] ?? '';
        $duration = (int)($_POST['duration_minutes'] ?? 60);
        $location = trim($_POST['location'] ?? '');
        $facilitator = trim($_POST['facilitator'] ?? '');
        
        if ($employee_id && $session_title && $session_date && $session_time) {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare(
                    "INSERT INTO orientation_schedules (employee_id, session_title, session_description, session_date, session_time, duration_minutes, location, facilitator, status, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', NOW())"
                );
                $stmt->execute([$employee_id, $session_title, $session_description, $session_date, $session_time, $duration, $location, $facilitator]);
                $success_message = "Orientation session scheduled successfully!";
            } catch (Exception $e) {
                $error_message = "Error scheduling orientation: " . $e->getMessage();
            }
        } else {
            $error_message = "Please fill in all required fields.";
        }
    }
    
    // Delete orientation schedule
    if ($action === 'delete_orientation') {
        $orientation_id = (int)($_POST['orientation_id'] ?? 0);
        
        if ($orientation_id) {
            try {
                executeQuery("DELETE FROM orientation_schedules WHERE id = ?", [$orientation_id]);
                $success_message = "Orientation session deleted successfully!";
            } catch (Exception $e) {
                $error_message = "Error deleting orientation: " . $e->getMessage();
            }
        }
    }
    
    // Redirect to avoid form resubmission
    if ($success_message || $error_message) {
        $_SESSION['success_message'] = $success_message;
        $_SESSION['error_message'] = $error_message;
        header('Location: onboarding.php');
        exit();
    }
}

// Get messages from session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Load all employees for dropdown
try {
    $allEmployees = fetchAll("SELECT id, CONCAT(first_name, ' ', last_name) AS name, position FROM employees ORDER BY first_name ASC");
} catch (Exception $e) {
    $allEmployees = [];
}

// New hires (recently hired within last 30 days or upcoming)
try {
    $upcomingHires = fetchAll(
        "SELECT e.id, e.first_name, e.last_name, e.email, e.position, e.department_id, e.hire_date,
                d.name AS department_name,
                (SELECT COUNT(*) FROM onboarding_tasks t WHERE t.employee_id = e.id) AS total_tasks,
                (SELECT COUNT(*) FROM onboarding_tasks t WHERE t.employee_id = e.id AND t.status = 'completed') AS completed_tasks
         FROM employees e
         LEFT JOIN departments d ON d.id = e.department_id
         WHERE e.hire_date IS NOT NULL AND e.hire_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
         ORDER BY e.hire_date DESC
         LIMIT 10"
    );
} catch (Exception $e) {
    $upcomingHires = [];
}

// Pick primary employee to show checklist (first upcoming if available, else latest recent hire)
$primaryEmployee = $upcomingHires[0] ?? null;
$primaryEmployeeId = $primaryEmployee['id'] ?? null;

if (!$primaryEmployeeId) {
    try {
        $primaryEmployee = fetchSingle(
            "SELECT e.id, e.first_name, e.last_name, e.email, e.position, e.department_id, e.hire_date,
                    d.name AS department_name
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             ORDER BY e.hire_date DESC, e.created_at DESC LIMIT 1"
        );
        $primaryEmployeeId = $primaryEmployee['id'] ?? null;
    } catch (Exception $e) {
        $primaryEmployee = null;
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
    
    // Load orientation schedules for primary employee
    try {
        $orientationSchedules = fetchAll(
            "SELECT id, session_title, session_description, session_date, session_time, duration_minutes, location, facilitator, status
             FROM orientation_schedules
             WHERE employee_id = ?
             ORDER BY session_date ASC, session_time ASC",
            [$primaryEmployeeId]
        );
    } catch (Exception $e) {
        $orientationSchedules = [];
    }
} else {
    $checklistTasks = [];
    $orientationSchedules = [];
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
                    <button class="btn btn-primary" onclick="document.getElementById('addTaskModal').style.display='block'">Add Task</button>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo h($success_message); ?></div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-error"><?php echo h($error_message); ?></div>
                <?php endif; ?>

                <div class="submodule-nav">
                    <button class="submodule-btn active" data-submodule="pre-boarding">Pre-boarding</button>
                    <button class="submodule-btn" data-submodule="orientation">Orientation</button>
                    <button class="submodule-btn" data-submodule="integration">Role Integration</button>
                </div>

                <!-- Pre-boarding Submodule -->
                <div id="pre-boarding" class="submodule active">
                    <?php if ($primaryEmployee): ?>
                    <div class="employee-onboarding-card" style="background: var(--card-bg, #1e293b); padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #3b82f6;">
                        <h3 style="margin: 0 0 0.5rem 0; color: #e2e8f0;">Currently Onboarding</h3>
                        <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                            <div>
                                <p style="margin: 0; font-size: 1.25rem; font-weight: 600; color: #f8fafc;"><?php echo h($primaryEmployee['first_name'] . ' ' . $primaryEmployee['last_name']); ?></p>
                                <p style="margin: 0.25rem 0; color: #94a3b8;">Employee ID: #<?php echo $primaryEmployee['id']; ?></p>
                            </div>
                            <div>
                                <p style="margin: 0; color: #94a3b8;">Position: <strong style="color: #e2e8f0;"><?php echo h($primaryEmployee['position']); ?></strong></p>
                                <p style="margin: 0.25rem 0; color: #94a3b8;">Department: <strong style="color: #e2e8f0;"><?php echo h($primaryEmployee['department_name'] ?? 'Not assigned'); ?></strong></p>
                            </div>
                            <div>
                                <p style="margin: 0; color: #94a3b8;">Email: <strong style="color: #e2e8f0;"><?php echo h($primaryEmployee['email']); ?></strong></p>
                                <p style="margin: 0.25rem 0; color: #94a3b8;">Hire Date: <strong style="color: #e2e8f0;"><?php echo $primaryEmployee['hire_date'] ? date('M d, Y', strtotime($primaryEmployee['hire_date'])) : 'Today'; ?></strong></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
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
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_task">
                                            <input type="hidden" name="task_id" value="<?php echo $t['id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $isCompleted ? 'pending' : 'completed'; ?>">
                                            <input type="checkbox" <?php echo $isCompleted ? 'checked' : ''; ?> onchange="this.form.submit()">
                                        </form>
                                        <span><?php echo h($t['task_name']); ?></span>
                                        <?php if ($dateNote) : ?><small><?php echo h($dateNote); ?></small><?php endif; ?>
                                        <form method="POST" style="display: inline; float: right;" onsubmit="return confirm('Delete this task?');">
                                            <input type="hidden" name="action" value="delete_task">
                                            <input type="hidden" name="task_id" value="<?php echo $t['id']; ?>">
                                            <button type="submit" class="btn-icon" title="Delete">🗑️</button>
                                        </form>
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
                    <?php if ($primaryEmployee): ?>
                    <div class="employee-onboarding-card" style="background: var(--card-bg, #1e293b); padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #10b981;">
                        <h3 style="margin: 0 0 0.5rem 0; color: #e2e8f0;">Orientation For</h3>
                        <div style="display: flex; gap: 2rem; flex-wrap: wrap; align-items: center;">
                            <div>
                                <p style="margin: 0; font-size: 1.25rem; font-weight: 600; color: #f8fafc;"><?php echo h($primaryEmployee['first_name'] . ' ' . $primaryEmployee['last_name']); ?></p>
                                <p style="margin: 0.25rem 0; color: #94a3b8;"><?php echo h($primaryEmployee['position']); ?> • <?php echo h($primaryEmployee['department_name'] ?? 'Unassigned'); ?></p>
                            </div>
                            <div style="margin-left: auto;">
                                <span style="background: #10b981; color: white; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.875rem;">New Hire</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="orientation-program">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h3 style="margin: 0;">Orientation Program</h3>
                            <?php if ($primaryEmployeeId): ?>
                            <button class="btn btn-primary" onclick="document.getElementById('addOrientationModal').style.display='block'">Schedule Session</button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="orientation-schedule">
                            <?php if (!empty($orientationSchedules)): ?>
                                <?php foreach ($orientationSchedules as $session): ?>
                                    <div class="schedule-item" style="position: relative; border-left: 3px solid <?php echo $session['status'] === 'completed' ? '#10b981' : ($session['status'] === 'cancelled' ? '#ef4444' : '#3b82f6'); ?>;">
                                        <div class="schedule-time" style="min-width: 120px;">
                                            <div style="font-weight: 600; color: #3b82f6;"><?php echo date('g:i A', strtotime($session['session_time'])); ?></div>
                                            <div style="font-size: 0.75rem; color: #94a3b8;"><?php echo date('M d, Y', strtotime($session['session_date'])); ?></div>
                                            <?php if ($session['duration_minutes']): ?>
                                            <div style="font-size: 0.7rem; color: #64748b;"><?php echo $session['duration_minutes']; ?> min</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="schedule-content" style="flex: 1;">
                                            <h4 style="margin: 0 0 0.5rem 0; color: #f8fafc;"><?php echo h($session['session_title']); ?></h4>
                                            <?php if ($session['session_description']): ?>
                                            <p style="margin: 0 0 0.5rem 0; color: #cbd5e1;"><?php echo h($session['session_description']); ?></p>
                                            <?php endif; ?>
                                            <div style="display: flex; gap: 1rem; flex-wrap: wrap; font-size: 0.875rem; color: #94a3b8;">
                                                <?php if ($session['location']): ?>
                                                <span>📍 <?php echo h($session['location']); ?></span>
                                                <?php endif; ?>
                                                <?php if ($session['facilitator']): ?>
                                                <span><span class="material-symbols-outlined" style="font-size: 1rem; vertical-align: middle;">person</span> <?php echo h($session['facilitator']); ?></span>
                                                <?php endif; ?>
                                                <span style="background: <?php echo $session['status'] === 'completed' ? '#10b981' : ($session['status'] === 'cancelled' ? '#ef4444' : '#3b82f6'); ?>; color: white; padding: 0.125rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">
                                                    <?php echo ucfirst($session['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <form method="POST" style="position: absolute; top: 0.5rem; right: 0.5rem;" onsubmit="return confirm('Delete this orientation session?');">
                                            <input type="hidden" name="action" value="delete_orientation">
                                            <input type="hidden" name="orientation_id" value="<?php echo $session['id']; ?>">
                                            <button type="submit" class="btn-icon" title="Delete" style="background: transparent; border: none; cursor: pointer; font-size: 1.2rem;">🗑️</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="schedule-item" style="text-align: center; padding: 2rem;">
                                    <p style="color: #94a3b8; margin: 0;">No orientation sessions scheduled yet.</p>
                                    <?php if ($primaryEmployeeId): ?>
                                    <p style="color: #64748b; font-size: 0.875rem; margin: 0.5rem 0 0 0;">Click "Schedule Session" to add orientation sessions for this employee.</p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Role Integration Submodule -->
                <div id="integration" class="submodule">
                    <?php if ($primaryEmployee): ?>
                    <div class="employee-onboarding-card" style="background: var(--card-bg, #1e293b); padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #f59e0b;">
                        <h3 style="margin: 0 0 0.5rem 0; color: #e2e8f0;">Role Integration Plan For</h3>
                        <div style="display: flex; gap: 2rem; flex-wrap: wrap; align-items: center;">
                            <div>
                                <p style="margin: 0; font-size: 1.25rem; font-weight: 600; color: #f8fafc;"><?php echo h($primaryEmployee['first_name'] . ' ' . $primaryEmployee['last_name']); ?></p>
                                <p style="margin: 0.25rem 0; color: #94a3b8;"><?php echo h($primaryEmployee['position']); ?> • <?php echo h($primaryEmployee['department_name'] ?? 'Unassigned'); ?></p>
                            </div>
                            <div style="margin-left: auto;">
                                <span style="background: #f59e0b; color: white; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.875rem;">In Progress</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
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

<?php include '../modals/components/onboarding-modals.php'; ?>

<?php include '../partials/footer.php'; ?>
