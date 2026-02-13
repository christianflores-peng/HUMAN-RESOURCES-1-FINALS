<?php
/**
 * HR1 SPA Helper
 * Detects AJAX requests and manages shell vs content-only rendering.
 * 
 * Usage in each page file:
 *   require_once 'spa_helper.php';
 *   $is_ajax = is_spa_ajax();
 *   // ... PHP logic (queries, form handling) stays the same ...
 *   if (!$is_ajax) { spa_shell_start($role, $portal_name, $badge_label, $nav_items, $active_page); }
 *   // ... page HTML content ...
 *   if (!$is_ajax) { spa_shell_end(); }
 */

function is_spa_ajax() {
    return (
        isset($_GET['ajax']) && $_GET['ajax'] == '1'
    ) || (
        isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    );
}

function spa_shell_start($role, $portal_name, $badge_label, $nav_items, $active_page, $base_path = '../../') {
    $user_name = htmlspecialchars(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
    $logo_path = $base_path . 'assets/images/slate.png';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($portal_name); ?> - HR1</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/spa.css">
</head>
<body data-role="<?php echo htmlspecialchars($role); ?>">
    <?php include __DIR__ . '/loading-screen.php'; ?>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-section">
                <img src="<?php echo $logo_path; ?>" alt="SLATE Logo">
                <h2><?php echo htmlspecialchars($portal_name); ?></h2>
                <p><?php echo $user_name; ?></p>
                <span class="role-badge"><?php echo htmlspecialchars($badge_label); ?></span>
            </div>
            <?php foreach ($nav_items as $section): ?>
                <?php if (isset($section['title'])): ?>
                    <div class="nav-section-title"><?php echo htmlspecialchars($section['title']); ?></div>
                <?php endif; ?>
                <ul class="nav-menu">
                    <?php foreach ($section['links'] as $link): ?>
                    <li class="nav-item">
                        <?php if (isset($link['href'])): ?>
                            <a href="<?php echo $link['href']; ?>" class="nav-link">
                                <i data-lucide="<?php echo $link['icon']; ?>"></i>
                                <?php echo htmlspecialchars($link['label']); ?>
                            </a>
                        <?php else: ?>
                            <a href="#" class="nav-link<?php echo ($link['page'] === $active_page) ? ' active' : ''; ?>" data-page="<?php echo htmlspecialchars($link['page']); ?>">
                                <i data-lucide="<?php echo $link['icon']; ?>"></i>
                                <?php echo htmlspecialchars($link['label']); ?>
                            </a>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        </aside>

        <main class="main-content" id="spa-content">
    <?php
}

function spa_shell_end($base_path = '../../') {
    ?>
        </main>
    </div>

    <script src="<?php echo $base_path; ?>assets/js/spa.js"></script>
    <?php include __DIR__ . '/logout-modal.php'; ?>
    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
</body>
</html>
    <?php
}

/**
 * Navigation definitions for each portal role.
 */
function get_nav_items($role) {
    switch ($role) {
        case 'admin':
            return [
                [
                    'title' => 'Main',
                    'links' => [
                        ['page' => 'dashboard', 'icon' => 'layout-dashboard', 'label' => 'Dashboard & Analytics'],
                    ]
                ],
                [
                    'title' => 'Configuration',
                    'links' => [
                        ['page' => 'accounts', 'icon' => 'user-cog', 'label' => 'User Management'],
                        ['page' => 'roles-permissions', 'icon' => 'shield', 'label' => 'Roles & Permissions'],
                        ['page' => 'approval-workflows', 'icon' => 'git-branch', 'label' => 'Approval Workflows'],
                        ['page' => 'system-settings', 'icon' => 'settings', 'label' => 'System Settings'],
                    ]
                ],
                [
                    'title' => 'Recruitment Mgt',
                    'links' => [
                        ['page' => 'job-postings-admin', 'icon' => 'briefcase', 'label' => 'Job Postings Override'],
                    ]
                ],
                [
                    'title' => 'Applicant Mgt',
                    'links' => [
                        ['page' => 'applicant-database', 'icon' => 'database', 'label' => 'Applicant Database'],
                    ]
                ],
                [
                    'title' => 'Onboarding',
                    'links' => [
                        ['page' => 'requirements-masterlist', 'icon' => 'clipboard-list', 'label' => 'Requirements Masterlist'],
                    ]
                ],
                [
                    'title' => 'Performance',
                    'links' => [
                        ['page' => 'audit-logs', 'icon' => 'history', 'label' => 'Audit Logs'],
                        ['page' => 'regularization-approval', 'icon' => 'badge-check', 'label' => 'Regularization Approval'],
                    ]
                ],
                [
                    'title' => 'Recognition',
                    'links' => [
                        ['page' => 'recognition-moderation', 'icon' => 'message-square-warning', 'label' => 'Post Moderation'],
                        ['page' => 'rewards-config', 'icon' => 'gift', 'label' => 'Rewards Configuration'],
                    ]
                ],
                [
                    'title' => 'Account',
                    'links' => [
                        ['href' => '../../logout.php', 'icon' => 'log-out', 'label' => 'Logout'],
                    ]
                ]
            ];
        case 'employee':
            return [
                [
                    'title' => 'Main',
                    'links' => [
                        ['page' => 'dashboard', 'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
                    ]
                ],
                [
                    'title' => 'Onboarding',
                    'links' => [
                        ['page' => 'onboarding', 'icon' => 'list-checks', 'label' => 'Onboarding Checklist'],
                        ['page' => 'requirements', 'icon' => 'upload', 'label' => 'Submit Requirements'],
                    ]
                ],
                [
                    'title' => 'My Info',
                    'links' => [
                        ['page' => 'profile', 'icon' => 'user', 'label' => 'My Profile'],
                        ['page' => 'documents', 'icon' => 'folder', 'label' => 'My Documents'],
                    ]
                ],
                [
                    'title' => 'Culture',
                    'links' => [
                        ['page' => 'recognition-wall', 'icon' => 'trophy', 'label' => 'Recognition Wall'],
                    ]
                ],
                [
                    'title' => 'Account',
                    'links' => [
                        ['href' => '../../logout.php', 'icon' => 'log-out', 'label' => 'Logout'],
                    ]
                ]
            ];
        case 'manager':
            return [
                [
                    'title' => 'Main',
                    'links' => [
                        ['page' => 'dashboard', 'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
                    ]
                ],
                [
                    'title' => 'Recruitment',
                    'links' => [
                        ['page' => 'job-requisitions', 'icon' => 'file-plus', 'label' => 'Job Requisitions'],
                        ['page' => 'interview-panel', 'icon' => 'message-square', 'label' => 'Interview Panel'],
                    ]
                ],
                [
                    'title' => 'Team Management',
                    'links' => [
                        ['page' => 'my-team', 'icon' => 'users', 'label' => 'My Team'],
                        ['page' => 'performance-reviews', 'icon' => 'clipboard-check', 'label' => 'Performance Reviews'],
                        ['page' => 'goal-setting', 'icon' => 'target', 'label' => 'Goal Setting'],
                    ]
                ],
                [
                    'title' => 'Resources',
                    'links' => [
                        ['page' => 'handbook', 'icon' => 'book-open', 'label' => 'Handbook'],
                    ]
                ],
                [
                    'title' => 'Account',
                    'links' => [
                        ['href' => '../../logout.php', 'icon' => 'log-out', 'label' => 'Logout'],
                    ]
                ]
            ];
        case 'hr_staff':
            return [
                [
                    'title' => 'Main',
                    'links' => [
                        ['page' => 'dashboard', 'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
                    ]
                ],
                [
                    'title' => 'Recruitment Mgt',
                    'links' => [
                        ['page' => 'job-requisitions', 'icon' => 'file-plus', 'label' => 'Job Requisitions'],
                        ['page' => 'job-postings', 'icon' => 'briefcase', 'label' => 'Job Postings'],
                    ]
                ],
                [
                    'title' => 'Applicant Mgt',
                    'links' => [
                        ['page' => 'applicant-screening', 'icon' => 'filter', 'label' => 'Applicant Screening'],
                        ['page' => 'interview-management', 'icon' => 'calendar-check', 'label' => 'Interview Management'],
                        ['page' => 'recruitment-pipeline', 'icon' => 'kanban', 'label' => 'Recruitment Pipeline'],
                    ]
                ],
                [
                    'title' => 'Onboarding',
                    'links' => [
                        ['page' => 'onboarding-tracker', 'icon' => 'clipboard-check', 'label' => 'Onboarding Tracker'],
                        ['page' => 'documents', 'icon' => 'folder-check', 'label' => 'Document Verification'],
                    ]
                ],
                [
                    'title' => 'Account',
                    'links' => [
                        ['href' => '../../logout.php', 'icon' => 'log-out', 'label' => 'Logout'],
                    ]
                ]
            ];
        case 'applicant':
            return [
                [
                    'title' => 'Main',
                    'links' => [
                        ['page' => 'dashboard', 'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
                        ['href' => '../../public/careers.php', 'icon' => 'search', 'label' => 'Browse Jobs'],
                    ]
                ],
                [
                    'title' => 'My Applications',
                    'links' => [
                        ['page' => 'applications', 'icon' => 'briefcase', 'label' => 'Applications'],
                        ['page' => 'interview-schedule', 'icon' => 'calendar', 'label' => 'Interview Schedule'],
                        ['page' => 'road-test-info', 'icon' => 'car', 'label' => 'Road Test Info'],
                    ]
                ],
                [
                    'title' => 'Offers',
                    'links' => [
                        ['page' => 'offer-view', 'icon' => 'file-text', 'label' => 'Job Offers'],
                    ]
                ],
                [
                    'title' => 'My Info',
                    'links' => [
                        ['page' => 'profile', 'icon' => 'user', 'label' => 'My Profile'],
                        ['page' => 'notifications', 'icon' => 'bell', 'label' => 'Notifications'],
                    ]
                ],
                [
                    'title' => 'Account',
                    'links' => [
                        ['href' => '../../logout.php', 'icon' => 'log-out', 'label' => 'Logout'],
                    ]
                ]
            ];
        default:
            return [];
    }
}
