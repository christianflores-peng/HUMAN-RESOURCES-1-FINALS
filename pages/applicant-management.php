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

// Safe output helper
function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// Prepare data buckets
$pipeline = [
    'applied'   => ['label' => 'Applied',   'statuses' => ['new'],                    'count' => 0, 'items' => []],
    'screening' => ['label' => 'Screening', 'statuses' => ['screening','reviewed'],  'count' => 0, 'items' => []],
    'interview' => ['label' => 'Interview', 'statuses' => ['interview'],             'count' => 0, 'items' => []],
    'offer'     => ['label' => 'Offer',     'statuses' => ['offer'],                 'count' => 0, 'items' => []],
];

// Load counts and samples for each column
foreach ($pipeline as $key => $cfg) {
    $placeholders = implode(',', array_fill(0, count($cfg['statuses']), '?'));
    try {
        $row = fetchSingle("SELECT COUNT(*) as c FROM job_applications WHERE status IN ($placeholders)", $cfg['statuses']);
        $pipeline[$key]['count'] = (int)($row['c'] ?? 0);
    } catch (Exception $e) {
        $pipeline[$key]['count'] = 0;
    }
    try {
        $pipeline[$key]['items'] = fetchAll(
            "SELECT ja.id, ja.first_name, ja.last_name, ja.status, jp.title AS job_title, ja.created_at
             FROM job_applications ja
             JOIN job_postings jp ON jp.id = ja.job_posting_id
             WHERE ja.status IN ($placeholders)
             ORDER BY ja.created_at DESC
             LIMIT 6",
            $cfg['statuses']
        );
    } catch (Exception $e) {
        $pipeline[$key]['items'] = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Management System - Applicant Management</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<?php 
$active_page = 'applicant-management';
$page_title = 'Applicant Management';
include '../partials/sidebar.php';
include '../partials/header.php';
?>
            <!-- Applicant Management Module -->
            <div id="applicant-management-module" class="module active">
                <div class="module-header">
                    <h2>Applicant Management</h2>
                    <button class="btn btn-primary" onclick="showSubModule('tracking')">View Pipeline</button>
                </div>

                <div class="submodule-nav">
                    <button class="submodule-btn active" data-submodule="tracking">Tracking</button>
                    <button class="submodule-btn" data-submodule="documents">Document Management</button>
                    <button class="submodule-btn" data-submodule="collaboration">Collaboration</button>
                </div>

                <!-- Tracking Submodule -->
                <div id="tracking" class="submodule active">
                    <div class="kanban-board">
                        <div class="kanban-column" data-status="new">
                            <h4>Applied <span class="count"><?php echo (int)$pipeline['applied']['count']; ?></span></h4>
                            <div class="kanban-cards">
                                <?php if (!empty($pipeline['applied']['items'])): ?>
                                    <?php foreach ($pipeline['applied']['items'] as $item): ?>
                                        <div class="kanban-card" draggable="true" data-app-id="<?php echo (int)$item['id']; ?>">
                                            <h5><?php echo h($item['first_name'] . ' ' . $item['last_name']); ?></h5>
                                            <p><?php echo h($item['job_title']); ?></p>
                                            <small>New application</small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="kanban-card"><h5>No applicants yet</h5><p>—</p></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="kanban-column" data-status="screening">
                            <h4>Screening <span class="count"><?php echo (int)$pipeline['screening']['count']; ?></span></h4>
                            <div class="kanban-cards">
                                <?php if (!empty($pipeline['screening']['items'])): ?>
                                    <?php foreach ($pipeline['screening']['items'] as $item): ?>
                                        <div class="kanban-card" draggable="true" data-app-id="<?php echo (int)$item['id']; ?>">
                                            <h5><?php echo h($item['first_name'] . ' ' . $item['last_name']); ?></h5>
                                            <p><?php echo h($item['job_title']); ?></p>
                                            <small>In screening</small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="kanban-card"><h5>Nothing in screening</h5><p>—</p></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="kanban-column" data-status="interview">
                            <h4>Interview <span class="count"><?php echo (int)$pipeline['interview']['count']; ?></span></h4>
                            <div class="kanban-cards">
                                <?php if (!empty($pipeline['interview']['items'])): ?>
                                    <?php foreach ($pipeline['interview']['items'] as $item): ?>
                                        <div class="kanban-card" draggable="true" data-app-id="<?php echo (int)$item['id']; ?>">
                                            <h5><?php echo h($item['first_name'] . ' ' . $item['last_name']); ?></h5>
                                            <p><?php echo h($item['job_title']); ?></p>
                                            <small>Interview stage</small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="kanban-card"><h5>No interviews scheduled</h5><p>—</p></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="kanban-column" data-status="offer">
                            <h4>Offer <span class="count"><?php echo (int)$pipeline['offer']['count']; ?></span></h4>
                            <div class="kanban-cards">
                                <?php if (!empty($pipeline['offer']['items'])): ?>
                                    <?php foreach ($pipeline['offer']['items'] as $item): ?>
                                        <div class="kanban-card" draggable="true" data-app-id="<?php echo (int)$item['id']; ?>">
                                            <h5><?php echo h($item['first_name'] . ' ' . $item['last_name']); ?></h5>
                                            <p><?php echo h($item['job_title']); ?></p>
                                            <small>Offer pending</small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="kanban-card"><h5>No offers yet</h5><p>—</p></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Document Management Submodule -->
                <div id="documents" class="submodule">
                    <div class="document-manager">
                        <h3>Document Management</h3>
                        <div class="document-grid">
                            <div class="document-card">
                                <div class="document-icon">📄</div>
                                <h4>Resumes</h4>
                                <p>156 files</p>
                                <button class="btn btn-sm">Manage</button>
                            </div>
                            <div class="document-card">
                                <div class="document-icon">📋</div>
                                <h4>Cover Letters</h4>
                                <p>89 files</p>
                                <button class="btn btn-sm">Manage</button>
                            </div>
                            <div class="document-card">
                                <div class="document-icon">🎓</div>
                                <h4>Certificates</h4>
                                <p>67 files</p>
                                <button class="btn btn-sm">Manage</button>
                            </div>
                            <div class="document-card">
                                <div class="document-icon">✅</div>
                                <h4>Background Checks</h4>
                                <p>23 files</p>
                                <button class="btn btn-sm">Manage</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Collaboration Submodule -->
                <div id="collaboration" class="submodule">
                    <div class="collaboration-tools">
                        <h3>Team Collaboration</h3>
                        <div class="collaboration-grid">
                            <div class="collab-card">
                                <h4>Interview Feedback</h4>
                                <p>Collect and review feedback from interview panels</p>
                                <button class="btn btn-primary">View Feedback</button>
                            </div>
                            <div class="collab-card">
                                <h4>Hiring Committee</h4>
                                <p>Coordinate with hiring committee members</p>
                                <button class="btn btn-primary">Committee Notes</button>
                            </div>
                            <div class="collab-card">
                                <h4>Reference Checks</h4>
                                <p>Manage reference check process</p>
                                <button class="btn btn-primary">Check References</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<?php include '../partials/footer.php'; ?>
