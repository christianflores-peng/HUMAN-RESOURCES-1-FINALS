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

function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// Load employees for selectors (limit to active)
try {
    $employees = fetchAll("SELECT id, CONCAT(first_name,' ',last_name) AS name FROM employees WHERE status='active' ORDER BY first_name ASC LIMIT 200");
} catch (Exception $e) { $employees = []; }

// Load active goals (limit 8)
try {
    $activeGoals = fetchAll(
        "SELECT pg.id, pg.title, pg.description, pg.priority, pg.target_date,
                COALESCE(pg.progress_percentage, 0) AS progress_percentage,
                CONCAT(e.first_name, ' ', e.last_name) AS employee_name
         FROM performance_goals pg
         JOIN employees e ON e.id = pg.employee_id
         WHERE pg.status = 'active'
         ORDER BY pg.priority DESC, pg.target_date ASC
         LIMIT 8"
    );
} catch (Exception $e) {
    $activeGoals = [];
}

// Load upcoming reviews (next 6)
try {
    $upcomingReviews = fetchAll(
        "SELECT pr.id,
                CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                e.position,
                pr.review_type,
                pr.due_date
         FROM performance_reviews pr
         JOIN employees e ON e.id = pr.employee_id
         WHERE pr.status IN ('draft','submitted','approved') AND pr.due_date IS NOT NULL AND pr.due_date >= CURDATE()
         ORDER BY pr.due_date ASC
         LIMIT 6"
    );
} catch (Exception $e) {
    $upcomingReviews = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Management System - Performance Management</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<?php 
$active_page = 'performance';
$page_title = 'Performance Management';
include '../partials/sidebar.php';
include '../partials/header.php';
?>
            <!-- Performance Management Module -->
            <div id="performance-module" class="module active">
                <div class="module-header">
                    <h2>Performance Management</h2>
                    <button class="btn btn-primary" onclick="showSubModule('goal-setting')">Set Goals</button>
                </div>

                <div class="submodule-nav">
                    <button class="submodule-btn active" data-submodule="goal-setting">Goal Setting</button>
                    <button class="submodule-btn" data-submodule="reviews">Reviews</button>
                    <button class="submodule-btn" data-submodule="feedback">Feedback</button>
                </div>

                <!-- Goal Setting Submodule -->
                <div id="goal-setting" class="submodule active">
                    <div class="goals-container">
                        <h3>Goal Management</h3>
                        <div class="goal-form">
                            <h4>Create New Goal</h4>
                            <form class="goal-creation-form">
                                <div class="form-group">
                                    <label>Goal Title</label>
                                    <input type="text" name="goalTitle" placeholder="e.g., Increase team productivity by 20%" required>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Employee</label>
                                        <select name="employeeId" required>
                                            <?php foreach ($employees as $emp): ?>
                                                <option value="<?php echo (int)$emp['id']; ?>"><?php echo h($emp['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Category</label>
                                        <select name="category" required>
                                            <option value="performance">Performance</option>
                                            <option value="development">Development</option>
                                            <option value="leadership">Leadership</option>
                                            <option value="innovation">Innovation</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Priority</label>
                                        <select name="priority" required>
                                            <option value="high">High</option>
                                            <option value="medium">Medium</option>
                                            <option value="low">Low</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea rows="3" name="description" placeholder="Describe the goal and success criteria..." required></textarea>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Start Date</label>
                                        <input type="date" name="startDate" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Target Date</label>
                                        <input type="date" name="targetDate" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Create Goal</button>
                            </form>
                        </div>

                        <div class="goals-list">
                            <h4>Active Goals</h4>
                            <div class="goal-cards">
                                <?php if (!empty($activeGoals)) : ?>
                                    <?php foreach ($activeGoals as $g) : ?>
                                        <?php 
                                            $priorityClass = strtolower((string)$g['priority']);
                                            $pct = max(0, min(100, (int)$g['progress_percentage']));
                                        ?>
                                        <div class="goal-card">
                                            <div class="goal-header">
                                                <h5><?php echo h($g['title']); ?></h5>
                                                <span class="priority-badge <?php echo h($priorityClass); ?>"><?php echo ucfirst($priorityClass); ?> Priority</span>
                                            </div>
                                            <p><?php echo h($g['description']); ?></p>
                                            <div class="goal-progress">
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo $pct; ?>%"></div>
                                                </div>
                                                <span><?php echo $pct; ?>% Complete</span>
                                            </div>
                                            <div class="goal-dates">
                                                <small>Owner: <?php echo h($g['employee_name']); ?></small>
                                                <small> &nbsp; Due: <?php echo h(date('M d, Y', strtotime($g['target_date']))); ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <div class="goal-card">
                                        <div class="goal-header">
                                            <h5>No active goals</h5>
                                        </div>
                                        <p>Create a goal to get started.</p>
                                        <div class="goal-progress">
                                            <div class="progress-bar"><div class="progress-fill" style="width: 0%"></div></div>
                                            <span>0% Complete</span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reviews Submodule -->
                <div id="reviews" class="submodule">
                    <div class="reviews-container">
                        <h3>Performance Reviews</h3>
                        <div class="goal-form" style="margin-bottom:1rem;">
                            <h4>Schedule Review</h4>
                            <form class="review-scheduling-form">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Employee</label>
                                        <select name="employeeId" required>
                                            <?php foreach ($employees as $emp): ?>
                                                <option value="<?php echo (int)$emp['id']; ?>"><?php echo h($emp['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Review Type</label>
                                        <select name="reviewType" required>
                                            <option value="annual">Annual</option>
                                            <option value="mid-year">Mid-Year</option>
                                            <option value="90-day">90-Day</option>
                                            <option value="project-based">Project-Based</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Due Date</label>
                                        <input type="date" name="dueDate" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Schedule</button>
                            </form>
                        </div>
                        <div class="review-schedule">
                            <h4>Upcoming Reviews</h4>
                            <div class="review-list">
                                <?php if (!empty($upcomingReviews)) : ?>
                                    <?php foreach ($upcomingReviews as $r) : ?>
                                        <div class="review-item">
                                            <div class="review-employee">
                                                <h5><?php echo h($r['employee_name']); ?></h5>
                                                <p><?php echo h($r['position']); ?></p>
                                            </div>
                                            <div class="review-details">
                                                <span class="review-type"><?php echo h(ucwords(str_replace('-', ' ', $r['review_type']))); ?></span>
                                                <span class="review-date">Due: <?php echo h(date('M d, Y', strtotime($r['due_date']))); ?></span>
                                            </div>
                                            <div class="review-actions">
                                                <button class="btn btn-sm btn-primary">Start Review</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <div class="review-item">
                                        <div class="review-employee">
                                            <h5>No upcoming reviews</h5>
                                            <p>When scheduled, they will appear here.</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="review-templates">
                            <h4>Review Templates</h4>
                            <div class="template-grid">
                                <div class="template-card">
                                    <h5>Annual Performance Review</h5>
                                    <p>Comprehensive yearly evaluation</p>
                                    <button class="btn btn-sm">Use Template</button>
                                </div>
                                <div class="template-card">
                                    <h5>90-Day Review</h5>
                                    <p>New employee evaluation</p>
                                    <button class="btn btn-sm">Use Template</button>
                                </div>
                                <div class="template-card">
                                    <h5>Project Review</h5>
                                    <p>Project-specific performance</p>
                                    <button class="btn btn-sm">Use Template</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feedback Submodule -->
                <div id="feedback" class="submodule">
                    <div class="feedback-container">
                        <h3>Continuous Feedback</h3>
                        <div class="feedback-tools">
                            <div class="feedback-card">
                                <h4>360-Degree Feedback</h4>
                                <p>Collect feedback from peers, managers, and direct reports</p>
                                <button class="btn btn-primary">Start 360 Review</button>
                            </div>
                            
                            <div class="feedback-card">
                                <h4>Peer Feedback</h4>
                                <p>Request feedback from team members</p>
                                <button class="btn btn-primary">Request Feedback</button>
                            </div>
                            
                            <div class="feedback-card">
                                <h4>Self Assessment</h4>
                                <p>Employee self-evaluation tools</p>
                                <button class="btn btn-primary">Self Assess</button>
                            </div>
                        </div>

                        <div class="recent-feedback">
                            <h4>Recent Feedback</h4>
                            <div class="feedback-list">
                                <div class="feedback-item">
                                    <div class="feedback-header">
                                        <strong>From: Manager</strong>
                                        <span class="feedback-date">2 days ago</span>
                                    </div>
                                    <p>"Excellent work on the recent project. Great attention to detail and team collaboration."</p>
                                    <div class="feedback-rating">
                                        <span>Rating: ⭐⭐⭐⭐⭐</span>
                                    </div>
                                </div>
                                
                                <div class="feedback-item">
                                    <div class="feedback-header">
                                        <strong>From: Peer</strong>
                                        <span class="feedback-date">1 week ago</span>
                                    </div>
                                    <p>"Always willing to help and share knowledge. A valuable team member."</p>
                                    <div class="feedback-rating">
                                        <span>Rating: ⭐⭐⭐⭐⭐</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<?php include '../partials/footer.php'; ?>
