<?php
/**
 * Manager Task Assignment Page
 * Allows managers to assign onboarding tasks to their team members
 */

session_start();
require_once '../database/config.php';
require_once '../includes/rbac_helper.php';
require_once '../includes/email_generator.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../partials/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$user = getUserWithRole($userId);

// Verify manager access
if (!$user || !in_array($user['role_type'], ['Manager', 'Admin', 'HR_Staff'])) {
    if (!isAdmin($userId) && !isHRStaff($userId)) {
        header('Location: ../pages/dashboard.php');
        exit();
    }
}

$message = null;
$messageType = null;

// Handle task assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_task'])) {
    $employeeId = intval($_POST['employee_id']);
    $taskName = trim($_POST['task_name']);
    $taskDescription = trim($_POST['task_description']);
    $category = $_POST['category'] ?? 'General';
    $daysToComplete = intval($_POST['days_to_complete']);
    
    try {
        // First, create the task in onboarding_tasks if it doesn't exist
        $existingTask = fetchSingle(
            "SELECT id FROM onboarding_tasks WHERE task_name = ? AND category = ?",
            [$taskName, $category]
        );
        
        if ($existingTask) {
            $taskId = $existingTask['id'];
        } else {
            $taskId = insertRecord(
                "INSERT INTO onboarding_tasks (task_name, task_description, category, is_required, days_to_complete, created_at)
                 VALUES (?, ?, ?, 1, ?, NOW())",
                [$taskName, $taskDescription, $category, $daysToComplete]
            );
        }
        
        // Assign task to employee
        $existing = fetchSingle(
            "SELECT id FROM employee_onboarding_progress WHERE user_id = ? AND task_id = ?",
            [$employeeId, $taskId]
        );
        
        if ($existing) {
            $message = "Task already assigned to this employee.";
            $messageType = "warning";
        } else {
            insertRecord(
                "INSERT INTO employee_onboarding_progress (user_id, task_id, status)
                 VALUES (?, ?, 'Pending')",
                [$employeeId, $taskId]
            );
            
            logAuditAction($userId, 'CREATE', 'employee_onboarding_progress', null, null, [
                'employee_id' => $employeeId,
                'task_name' => $taskName
            ], "Assigned onboarding task to employee");
            
            $message = "Task assigned successfully!";
            $messageType = "success";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "error";
    }
}

// Get team members
$teamMembers = getAccessibleEmployees($userId, ['status' => null]);

// Get existing tasks
$existingTasks = [];
try {
    $existingTasks = fetchAll("SELECT * FROM onboarding_tasks ORDER BY category, task_name");
} catch (Exception $e) {
    $existingTasks = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Tasks - Manager Portal</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block" />
</head>
<body>
<?php 
$active_page = 'manager-dashboard';
include '../partials/sidebar.php';
include '../partials/header.php';
?>

<div class="main-container">
    <div style="background: #1e2936; border-radius: 12px; padding: 2rem; margin-bottom: 2rem;">
        <div style="margin-bottom: 1.5rem;">
            <h1 style="color: #ffffff; font-size: 1.75rem;">Assign Onboarding Tasks</h1>
            <p style="color: #94a3b8; margin-top: 0.5rem;">Assign tasks to your team members for onboarding completion</p>
        </div>

        <?php if ($message): ?>
            <div style="padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; <?php 
                echo $messageType === 'success' ? 'background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #6ee7b7;' : 
                    ($messageType === 'error' ? 'background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5;' : 
                    'background: rgba(245, 158, 11, 0.2); border: 1px solid #f59e0b; color: #fbbf24;');
            ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" style="display: grid; gap: 1.5rem;">
            <input type="hidden" name="assign_task" value="1">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-weight: 500;">Select Employee</label>
                    <select name="employee_id" required style="width: 100%; padding: 0.75rem 1rem; background: #2a3544; border: 1px solid #3a4554; border-radius: 6px; color: #e2e8f0; font-size: 0.95rem;">
                        <option value="">-- Select Employee --</option>
                        <?php foreach ($teamMembers as $member): ?>
                            <option value="<?php echo $member['id']; ?>">
                                <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?> - <?php echo htmlspecialchars($member['job_title'] ?? 'Employee'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-weight: 500;">Category</label>
                    <select name="category" required style="width: 100%; padding: 0.75rem 1rem; background: #2a3544; border: 1px solid #3a4554; border-radius: 6px; color: #e2e8f0; font-size: 0.95rem;">
                        <option value="Documents">Documents</option>
                        <option value="Training">Training</option>
                        <option value="IT Setup">IT Setup</option>
                        <option value="Orientation">Orientation</option>
                        <option value="Compliance">Compliance</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div>
                <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-weight: 500;">Task Name</label>
                <input type="text" name="task_name" required placeholder="e.g., Submit Driver's License Copy" style="width: 100%; padding: 0.75rem 1rem; background: #2a3544; border: 1px solid #3a4554; border-radius: 6px; color: #e2e8f0; font-size: 0.95rem;">
            </div>

            <div>
                <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-weight: 500;">Task Description</label>
                <textarea name="task_description" rows="4" required placeholder="Describe what the employee needs to do..." style="width: 100%; padding: 0.75rem 1rem; background: #2a3544; border: 1px solid #3a4554; border-radius: 6px; color: #e2e8f0; font-size: 0.95rem; resize: vertical;"></textarea>
            </div>

            <div>
                <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-weight: 500;">Days to Complete</label>
                <input type="number" name="days_to_complete" value="7" min="1" max="90" required style="width: 200px; padding: 0.75rem 1rem; background: #2a3544; border: 1px solid #3a4554; border-radius: 6px; color: #e2e8f0; font-size: 0.95rem;">
            </div>

            <button type="submit" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.875rem 2rem; background: #0ea5e9; color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer;">
                <span class="material-symbols-outlined">add_task</span>
                Assign Task to Employee
            </button>
        </form>
    </div>

    <!-- Existing Tasks Reference -->
    <div style="background: #1e2936; border-radius: 12px; padding: 2rem;">
        <h2 style="color: #ffffff; margin-bottom: 1rem;">Existing Task Templates</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem;">
            <?php foreach ($existingTasks as $task): ?>
                <div style="background: #2a3544; border-radius: 8px; padding: 1rem; border-left: 4px solid #0ea5e9;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                        <h3 style="color: #ffffff; font-size: 0.95rem;"><?php echo htmlspecialchars($task['task_name']); ?></h3>
                        <span style="padding: 0.25rem 0.5rem; background: #334155; border-radius: 4px; font-size: 0.75rem; color: #0ea5e9;">
                            <?php echo htmlspecialchars($task['category']); ?>
                        </span>
                    </div>
                    <p style="color: #94a3b8; font-size: 0.85rem;"><?php echo htmlspecialchars($task['task_description']); ?></p>
                    <p style="color: #64748b; font-size: 0.8rem; margin-top: 0.5rem;">⏱️ <?php echo $task['days_to_complete']; ?> days</p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include '../partials/footer.php'; ?>
</body>
</html>
