<?php
/**
 * Manager View Employee Personal Information
 * Allows managers to view employee personal information updates
 */

session_start();
require_once '../database/config.php';
require_once '../includes/rbac_helper.php';

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

$employeeId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($employeeId <= 0) {
    header('Location: manager-dashboard.php');
    exit();
}

// Get employee information
$employee = null;
try {
    $employee = fetchSingle(
        "SELECT ua.*, d.name as department_name, r.role_name
         FROM user_accounts ua
         LEFT JOIN departments d ON ua.department_id = d.id
         LEFT JOIN roles r ON ua.role_id = r.id
         WHERE ua.id = ?",
        [$employeeId]
    );
} catch (Exception $e) {
    $employee = null;
}

if (!$employee) {
    header('Location: manager-dashboard.php');
    exit();
}

// Get emergency contact info (if stored separately)
$emergencyContact = null;
try {
    $emergencyContact = fetchSingle(
        "SELECT * FROM employee_emergency_contacts WHERE user_id = ? ORDER BY created_at DESC LIMIT 1",
        [$employeeId]
    );
} catch (Exception $e) {
    $emergencyContact = null;
}

// Get onboarding progress
$onboardingTasks = [];
try {
    $onboardingTasks = fetchAll(
        "SELECT eop.*, ot.task_name, ot.category
         FROM employee_onboarding_progress eop
         JOIN onboarding_tasks ot ON eop.task_id = ot.id
         WHERE eop.user_id = ?
         ORDER BY eop.status ASC, ot.category",
        [$employeeId]
    );
} catch (Exception $e) {
    $onboardingTasks = [];
}

$totalTasks = count($onboardingTasks);
$completedTasks = count(array_filter($onboardingTasks, fn($t) => $t['status'] === 'Completed'));
$progressPercent = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Information - Manager Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
<?php 
$active_page = 'manager-dashboard';
include '../partials/sidebar.php';
include '../partials/header.php';
?>

<div style="padding: 2rem; max-width: 1200px; margin: 0 auto;">
    <!-- Header -->
    <div style="background: #1e2936; border-radius: 12px; padding: 2rem; margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="color: #ffffff; font-size: 1.75rem; margin-bottom: 0.5rem;">
                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                </h1>
                <p style="color: #94a3b8;">
                    <?php echo htmlspecialchars($employee['job_title'] ?? 'Employee'); ?> • 
                    <?php echo htmlspecialchars($employee['department_name'] ?? 'N/A'); ?>
                </p>
            </div>
            <a href="manager-dashboard.php" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.25rem; background: #334155; color: #e2e8f0; text-decoration: none; border-radius: 8px;">
                <i data-lucide="arrow-left"></i>
                Back to Team
            </a>
        </div>
    </div>

    <!-- Onboarding Progress -->
    <div style="background: #1e2936; border-radius: 12px; padding: 2rem; margin-bottom: 2rem;">
        <h2 style="color: #ffffff; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <i data-lucide="clipboard-check"></i>
            Onboarding Progress
        </h2>
        <div style="display: flex; align-items: center; gap: 2rem; margin-bottom: 1rem;">
            <div style="flex: 1;">
                <div style="background: #2a3544; border-radius: 8px; height: 12px; overflow: hidden;">
                    <div style="height: 100%; background: linear-gradient(90deg, #10b981, #0ea5e9); width: <?php echo $progressPercent; ?>%; transition: width 0.3s;"></div>
                </div>
            </div>
            <div style="font-size: 2rem; font-weight: 700; color: #0ea5e9;">
                <?php echo $progressPercent; ?>%
            </div>
        </div>
        <p style="color: #94a3b8; margin-bottom: 1.5rem;">
            <?php echo $completedTasks; ?> of <?php echo $totalTasks; ?> tasks completed
        </p>

        <?php if (!empty($onboardingTasks)): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem;">
                <?php foreach ($onboardingTasks as $task): ?>
                    <div style="background: #2a3544; border-radius: 8px; padding: 1rem; border-left: 4px solid <?php echo $task['status'] === 'Completed' ? '#10b981' : '#f59e0b'; ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                            <h3 style="color: #ffffff; font-size: 0.95rem;"><?php echo htmlspecialchars($task['task_name']); ?></h3>
                            <span style="padding: 0.25rem 0.5rem; background: <?php echo $task['status'] === 'Completed' ? 'rgba(16, 185, 129, 0.2)' : 'rgba(245, 158, 11, 0.2)'; ?>; border-radius: 12px; font-size: 0.75rem; color: <?php echo $task['status'] === 'Completed' ? '#10b981' : '#f59e0b'; ?>;">
                                <?php echo htmlspecialchars($task['status']); ?>
                            </span>
                        </div>
                        <p style="color: #64748b; font-size: 0.8rem;"><?php echo htmlspecialchars($task['category']); ?></p>
                        <?php if ($task['completed_at']): ?>
                            <p style="color: #10b981; font-size: 0.8rem; margin-top: 0.5rem;">✓ Completed <?php echo date('M d, Y', strtotime($task['completed_at'])); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: #64748b; text-align: center; padding: 2rem;">No onboarding tasks assigned yet.</p>
        <?php endif; ?>
    </div>

    <!-- Personal Information -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Contact Information -->
        <div style="background: #1e2936; border-radius: 12px; padding: 2rem;">
            <h2 style="color: #ffffff; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i data-lucide="mail"></i>
                Contact Information
            </h2>
            
            <div style="display: grid; gap: 1rem;">
                <div>
                    <label style="display: block; color: #94a3b8; font-size: 0.85rem; margin-bottom: 0.25rem;">Email Address</label>
                    <p style="color: #ffffff; font-size: 1rem;"><?php echo htmlspecialchars($employee['company_email'] ?? $employee['personal_email'] ?? 'N/A'); ?></p>
                </div>

                <div>
                    <label style="display: block; color: #94a3b8; font-size: 0.85rem; margin-bottom: 0.25rem;">Phone Number</label>
                    <p style="color: #ffffff; font-size: 1rem;"><?php echo htmlspecialchars($employee['phone'] ?? 'Not provided'); ?></p>
                </div>

                <div>
                    <label style="display: block; color: #94a3b8; font-size: 0.85rem; margin-bottom: 0.25rem;">Complete Address</label>
                    <p style="color: #ffffff; font-size: 1rem;"><?php echo htmlspecialchars($employee['address'] ?? 'Not provided'); ?></p>
                </div>

                <div>
                    <label style="display: block; color: #94a3b8; font-size: 0.85rem; margin-bottom: 0.25rem;">Date of Birth</label>
                    <p style="color: #ffffff; font-size: 1rem;"><?php echo $employee['date_of_birth'] ? date('M d, Y', strtotime($employee['date_of_birth'])) : 'Not provided'; ?></p>
                </div>
            </div>
        </div>

        <!-- Emergency Contact -->
        <div style="background: #1e2936; border-radius: 12px; padding: 2rem;">
            <h2 style="color: #ffffff; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i data-lucide="phone-call"></i>
                Emergency Contact
            </h2>
            
            <?php if ($emergencyContact): ?>
                <div style="display: grid; gap: 1rem;">
                    <div>
                        <label style="display: block; color: #94a3b8; font-size: 0.85rem; margin-bottom: 0.25rem;">Contact Name</label>
                        <p style="color: #ffffff; font-size: 1rem;"><?php echo htmlspecialchars($emergencyContact['contact_name']); ?></p>
                    </div>

                    <div>
                        <label style="display: block; color: #94a3b8; font-size: 0.85rem; margin-bottom: 0.25rem;">Relationship</label>
                        <p style="color: #ffffff; font-size: 1rem;"><?php echo htmlspecialchars($emergencyContact['relationship']); ?></p>
                    </div>

                    <div>
                        <label style="display: block; color: #94a3b8; font-size: 0.85rem; margin-bottom: 0.25rem;">Contact Phone</label>
                        <p style="color: #ffffff; font-size: 1rem;"><?php echo htmlspecialchars($emergencyContact['contact_phone']); ?></p>
                    </div>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem; color: #64748b;">
                    <i data-lucide="user-x" style="width: 3rem; height: 3rem; display: block; margin: 0 auto 0.5rem;"></i>
                    <p>No emergency contact information provided yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Employment Details -->
    <div style="background: #1e2936; border-radius: 12px; padding: 2rem; margin-top: 2rem;">
        <h2 style="color: #ffffff; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
            <i data-lucide="badge-check"></i>
            Employment Details
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 2rem;">
            <div>
                <label style="display: block; color: #94a3b8; font-size: 0.85rem; margin-bottom: 0.25rem;">Employee ID</label>
                <p style="color: #ffffff; font-size: 1rem;"><?php echo htmlspecialchars($employee['employee_id'] ?? 'N/A'); ?></p>
            </div>

            <div>
                <label style="display: block; color: #94a3b8; font-size: 0.85rem; margin-bottom: 0.25rem;">Hire Date</label>
                <p style="color: #ffffff; font-size: 1rem;"><?php echo $employee['hire_date'] ? date('M d, Y', strtotime($employee['hire_date'])) : 'N/A'; ?></p>
            </div>

            <div>
                <label style="display: block; color: #94a3b8; font-size: 0.85rem; margin-bottom: 0.25rem;">Employment Status</label>
                <p style="color: #ffffff; font-size: 1rem;">
                    <span style="padding: 0.25rem 0.75rem; background: <?php echo $employee['employment_status'] === 'Active' ? 'rgba(16, 185, 129, 0.2)' : 'rgba(245, 158, 11, 0.2)'; ?>; border-radius: 12px; color: <?php echo $employee['employment_status'] === 'Active' ? '#10b981' : '#f59e0b'; ?>;">
                        <?php echo htmlspecialchars($employee['employment_status'] ?? 'Active'); ?>
                    </span>
                </p>
            </div>

            <div>
                <label style="display: block; color: #94a3b8; font-size: 0.85rem; margin-bottom: 0.25rem;">Role</label>
                <p style="color: #ffffff; font-size: 1rem;"><?php echo htmlspecialchars($employee['role_name'] ?? 'Employee'); ?></p>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>

<?php include '../partials/footer.php'; ?>
</body>
</html>
