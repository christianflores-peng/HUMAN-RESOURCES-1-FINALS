<?php
session_start();
require_once '../database/config.php';
require_once '../includes/rbac_helper.php';

// Check if user is logged in and is an Applicant
if (!isset($_SESSION['user_id'])) {
    header('Location: ../partials/login.php');
    exit();
}

$user = getUserWithRole($_SESSION['user_id']);
if (!$user || $user['role_type'] !== 'Applicant') {
    header('Location: ../partials/login.php');
    exit();
}

// Get applicant's applications
$applications = fetchAll(
    "SELECT ja.*, 
            COALESCE(jp.title, 'General Application') as job_title, 
            jp.department_id, 
            COALESCE(d.department_name, 'Not Assigned') as department_name, 
            COALESCE(jp.employment_type, 'To Be Determined') as employment_type, 
            COALESCE(jp.location, 'N/A') as location
     FROM job_applications ja
     LEFT JOIN job_postings jp ON ja.job_posting_id = jp.id
     LEFT JOIN departments d ON jp.department_id = d.id
     WHERE ja.email = ?
     ORDER BY ja.applied_date DESC",
    [$user['personal_email']]
);

// Get user profile info
$profile = fetchSingle(
    "SELECT * FROM user_accounts WHERE id = ?",
    [$user['id']]
);

// Status color mapping
$statusColors = [
    'new' => '#3b82f6',
    'screening' => '#f59e0b',
    'interview' => '#8b5cf6',
    'assessment' => '#ec4899',
    'offer' => '#10b981',
    'hired' => '#059669',
    'rejected' => '#ef4444',
    'withdrawn' => '#6b7280'
];

// Status labels
$statusLabels = [
    'new' => 'New Application',
    'screening' => 'Under Screening',
    'interview' => 'Interview Stage',
    'assessment' => 'Assessment',
    'offer' => 'Job Offer',
    'hired' => 'Hired',
    'rejected' => 'Not Selected',
    'withdrawn' => 'Withdrawn'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Portal - SLATE HR</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a1929 0%, #1a2942 100%);
            min-height: 100vh;
            color: #f8fafc;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            background: rgba(30, 41, 54, 0.8);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(10px);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-left img {
            width: 50px;
            height: auto;
        }

        .header-title h1 {
            font-size: 1.5rem;
            color: #0ea5e9;
        }

        .header-subtitle {
            font-size: 0.9rem;
            color: #94a3b8;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            color: #e2e8f0;
        }

        .user-role {
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: #0ea5e9;
            color: white;
        }

        .btn-primary:hover {
            background: #0284c7;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: rgba(71, 85, 105, 0.8);
            color: white;
        }

        .btn-secondary:hover {
            background: rgba(51, 65, 85, 0.9);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(30, 41, 54, 0.8);
            padding: 1.5rem;
            border-radius: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(58, 69, 84, 0.5);
        }

        .stat-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .stat-icon {
            font-size: 2rem;
            color: #0ea5e9;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #0ea5e9;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #94a3b8;
            margin-top: 0.5rem;
        }

        .content-section {
            background: rgba(30, 41, 54, 0.8);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(58, 69, 84, 0.5);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(58, 69, 84, 0.5);
        }

        .section-title {
            font-size: 1.25rem;
            color: #0ea5e9;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .applications-grid {
            display: grid;
            gap: 1.5rem;
        }

        .application-card {
            background: rgba(42, 53, 68, 0.5);
            border: 1px solid rgba(58, 69, 84, 0.5);
            border-radius: 8px;
            padding: 1.5rem;
            transition: all 0.3s;
        }

        .application-card:hover {
            transform: translateY(-2px);
            border-color: #0ea5e9;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.2);
        }

        .app-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .app-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #e2e8f0;
            margin-bottom: 0.5rem;
        }

        .app-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 1rem;
        }

        .app-meta-item {
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .app-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(58, 69, 84, 0.5);
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #94a3b8;
        }

        .empty-state-icon {
            font-size: 4rem;
            color: #475569;
            margin-bottom: 1rem;
        }

        .profile-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .profile-field {
            margin-bottom: 1rem;
        }

        .profile-label {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 0.35rem;
        }

        .profile-value {
            color: #e2e8f0;
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .profile-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php $logo_path = '../assets/images/slate.png'; include '../includes/loading-screen.php'; ?>

    <div class="dashboard-container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <img src="../assets/images/slate.png" alt="SLATE Logo">
                <div class="header-title">
                    <h1>Applicant Portal</h1>
                    <div class="header-subtitle">Track your applications and manage your profile</div>
                </div>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                    <div class="user-role">Applicant</div>
                </div>
                <a href="../partials/logout.php" class="btn btn-secondary">
                    <span class="material-symbols-outlined">logout</span>
                    Logout
                </a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="material-symbols-outlined stat-icon">description</span>
                    <div>
                        <div class="stat-value"><?php echo count($applications); ?></div>
                        <div class="stat-label">Total Applications</div>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="material-symbols-outlined stat-icon">pending</span>
                    <div>
                        <div class="stat-value">
                            <?php 
                            $pending = array_filter($applications, fn($app) => in_array($app['status'], ['new', 'screening', 'interview', 'assessment']));
                            echo count($pending);
                            ?>
                        </div>
                        <div class="stat-label">In Progress</div>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="material-symbols-outlined stat-icon">check_circle</span>
                    <div>
                        <div class="stat-value">
                            <?php 
                            $offers = array_filter($applications, fn($app) => in_array($app['status'], ['offer', 'hired']));
                            echo count($offers);
                            ?>
                        </div>
                        <div class="stat-label">Offers/Hired</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Applications -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="material-symbols-outlined">work</span>
                    My Applications
                </h2>
                <a href="../careers.php" class="btn btn-primary">
                    <span class="material-symbols-outlined">add</span>
                    Apply for New Job
                </a>
            </div>

            <?php if (empty($applications)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <span class="material-symbols-outlined" style="font-size: inherit;">inbox</span>
                    </div>
                    <h3>No Applications Yet</h3>
                    <p>You haven't applied for any positions yet. Browse available jobs and submit your application!</p>
                    <a href="../careers.php" class="btn btn-primary" style="margin-top: 1.5rem;">
                        <span class="material-symbols-outlined">search</span>
                        Browse Jobs
                    </a>
                </div>
            <?php else: ?>
                <div class="applications-grid">
                    <?php foreach ($applications as $app): ?>
                        <div class="application-card">
                            <div class="app-header">
                                <div>
                                    <div class="app-title"><?php echo htmlspecialchars($app['job_title']); ?></div>
                                    <div class="app-meta">
                                        <div class="app-meta-item">
                                            <span class="material-symbols-outlined" style="font-size: 1.1rem;">business</span>
                                            <?php echo htmlspecialchars($app['department_name'] ?? 'N/A'); ?>
                                        </div>
                                        <div class="app-meta-item">
                                            <span class="material-symbols-outlined" style="font-size: 1.1rem;">work</span>
                                            <?php echo htmlspecialchars($app['employment_type']); ?>
                                        </div>
                                        <div class="app-meta-item">
                                            <span class="material-symbols-outlined" style="font-size: 1.1rem;">location_on</span>
                                            <?php echo htmlspecialchars($app['location']); ?>
                                        </div>
                                        <div class="app-meta-item">
                                            <span class="material-symbols-outlined" style="font-size: 1.1rem;">calendar_today</span>
                                            Applied: <?php echo date('M d, Y', strtotime($app['applied_date'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="status-badge" style="background: <?php echo $statusColors[$app['status']] ?? '#6b7280'; ?>;">
                                    <?php echo $statusLabels[$app['status']] ?? ucfirst($app['status']); ?>
                                </div>
                            </div>

                            <div class="app-actions">
                                <a href="application-details.php?id=<?php echo $app['id']; ?>" class="btn btn-primary btn-small">
                                    <span class="material-symbols-outlined">visibility</span>
                                    View Details
                                </a>
                                <?php if ($app['status'] === 'screening'): ?>
                                    <a href="../screening.php?application_id=<?php echo $app['id']; ?>" class="btn btn-secondary btn-small">
                                        <span class="material-symbols-outlined">quiz</span>
                                        Take Assessment
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- My Profile -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="material-symbols-outlined">person</span>
                    My Profile
                </h2>
                <a href="edit-profile.php" class="btn btn-secondary">
                    <span class="material-symbols-outlined">edit</span>
                    Edit Profile
                </a>
            </div>

            <div class="profile-section">
                <div class="profile-field">
                    <div class="profile-label">Full Name</div>
                    <div class="profile-value"><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></div>
                </div>

                <div class="profile-field">
                    <div class="profile-label">Email Address</div>
                    <div class="profile-value"><?php echo htmlspecialchars($profile['personal_email']); ?></div>
                </div>

                <div class="profile-field">
                    <div class="profile-label">Phone Number</div>
                    <div class="profile-value"><?php echo htmlspecialchars($profile['phone'] ?? 'Not provided'); ?></div>
                </div>

                <div class="profile-field">
                    <div class="profile-label">Account Status</div>
                    <div class="profile-value">
                        <span class="status-badge" style="background: <?php echo $profile['status'] === 'Active' ? '#10b981' : '#f59e0b'; ?>;">
                            <?php echo htmlspecialchars($profile['status']); ?>
                        </span>
                    </div>
                </div>

                <div class="profile-field">
                    <div class="profile-label">Member Since</div>
                    <div class="profile-value"><?php echo date('F d, Y', strtotime($profile['created_at'])); ?></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
