<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection
require_once 'database/config.php';

// Safe output helper
function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// Get current user info
$current_user = $_SESSION['username'] ?? 'User';
$current_role = $_SESSION['role'] ?? 'Employee';

// Fetch applicant statistics
$stats = [];
try {
    // Total applications
    $total_apps = fetchSingle("SELECT COUNT(*) as count FROM job_applications");
    $stats['total_applications'] = (int)($total_apps['count'] ?? 0);
    
    // Applications by status
    $status_counts = fetchAll("
        SELECT status, COUNT(*) as count 
        FROM job_applications 
        GROUP BY status
    ");
    
    $stats['by_status'] = [];
    foreach ($status_counts as $row) {
        $stats['by_status'][$row['status']] = (int)$row['count'];
    }
    
    // Recent applications (last 7 days)
    $recent_apps = fetchSingle("
        SELECT COUNT(*) as count 
        FROM job_applications 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stats['recent_applications'] = (int)($recent_apps['count'] ?? 0);
    
    // Active job postings
    $active_jobs = fetchSingle("
        SELECT COUNT(*) as count 
        FROM job_postings 
        WHERE status = 'active'
    ");
    $stats['active_jobs'] = (int)($active_jobs['count'] ?? 0);
    
} catch (Exception $e) {
    $stats = [
        'total_applications' => 0,
        'by_status' => [],
        'recent_applications' => 0,
        'active_jobs' => 0
    ];
}

// Fetch recent applications for the dashboard
$recent_applications = [];
try {
    $recent_applications = fetchAll("
        SELECT ja.id, ja.first_name, ja.last_name, ja.email, ja.status, ja.created_at,
               jp.title as job_title, jp.location
        FROM job_applications ja
        JOIN job_postings jp ON jp.id = ja.job_posting_id
        ORDER BY ja.created_at DESC
        LIMIT 10
    ");
} catch (Exception $e) {
    $recent_applications = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Management Portal - HR System</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Portal-specific styles */
        .portal-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            display: flex;
            flex-direction: column;
        }
        
        .portal-header {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #334155;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .portal-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .portal-logo h1 {
            color: #3b82f6;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .portal-nav {
            display: flex;
            gap: 1rem;
        }
        
        .portal-nav a {
            color: #cbd5e1;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .portal-nav a:hover {
            background: #334155;
            color: #f8fafc;
        }
        
        .portal-main {
            flex: 1;
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }
        
        .portal-welcome {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .portal-welcome h2 {
            font-size: 2.5rem;
            color: #f8fafc;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .portal-welcome p {
            color: #cbd5e1;
            font-size: 1.1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #cbd5e1;
            font-size: 1rem;
        }
        
        .portal-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .action-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
            border-color: #3b82f6;
        }
        
        .action-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .action-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #f8fafc;
            margin-bottom: 0.5rem;
        }
        
        .action-description {
            color: #cbd5e1;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        
        .btn-portal {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-portal:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }
        
        .recent-applications {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 2rem;
        }
        
        .recent-applications h3 {
            color: #f8fafc;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }
        
        .application-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #334155;
            transition: all 0.3s ease;
        }
        
        .application-item:hover {
            background: #334155;
        }
        
        .application-item:last-child {
            border-bottom: none;
        }
        
        .app-info h4 {
            color: #f8fafc;
            margin-bottom: 0.25rem;
        }
        
        .app-info p {
            color: #cbd5e1;
            font-size: 0.9rem;
        }
        
        .app-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-new { background: #3b82f6; color: white; }
        .status-reviewed { background: #f59e0b; color: white; }
        .status-screening { background: #8b5cf6; color: white; }
        .status-interview { background: #06b6d4; color: white; }
        .status-offer { background: #10b981; color: white; }
        .status-hired { background: #059669; color: white; }
        .status-rejected { background: #ef4444; color: white; }
        
        .portal-footer {
            background: #0f172a;
            border-top: 1px solid #334155;
            padding: 2rem;
            text-align: center;
            color: #64748b;
        }
        
        @media (max-width: 768px) {
            .portal-main {
                padding: 1rem;
            }
            
            .portal-welcome h2 {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .portal-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="portal-container">
        <!-- Portal Header -->
        <header class="portal-header">
            <div class="portal-logo">
                <h1>SLATE</h1>
                <span style="color: #cbd5e1;">Applicant Management Portal</span>
            </div>
            <nav class="portal-nav">
                <a href="pages/dashboard.php">Dashboard</a>
                <a href="pages/applicant-management.php">Manage Applications</a>
                <a href="careers.php">Job Postings</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="portal-main">
            <!-- Welcome Section -->
            <div class="portal-welcome">
                <h2>Welcome to Applicant Management</h2>
                <p>Manage job applications, track candidates, and streamline your hiring process</p>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo h($stats['total_applications']); ?></div>
                    <div class="stat-label">Total Applications</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo h($stats['recent_applications']); ?></div>
                    <div class="stat-label">New This Week</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo h($stats['active_jobs']); ?></div>
                    <div class="stat-label">Active Job Postings</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo h($stats['by_status']['hired'] ?? 0); ?></div>
                    <div class="stat-label">Successful Hires</div>
                </div>
            </div>

            <!-- Action Cards -->
            <div class="portal-actions">
                <div class="action-card">
                    <div class="action-icon">📋</div>
                    <div class="action-title">View All Applications</div>
                    <div class="action-description">Browse and manage all job applications in your system</div>
                    <a href="pages/applicant-management.php" class="btn-portal">View Applications</a>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">📊</div>
                    <div class="action-title">Analytics Dashboard</div>
                    <div class="action-description">Track hiring metrics and performance analytics</div>
                    <a href="pages/dashboard.php" class="btn-portal">View Analytics</a>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">💼</div>
                    <div class="action-title">Job Postings</div>
                    <div class="action-description">Create and manage job postings and requirements</div>
                    <a href="careers.php" class="btn-portal">Manage Jobs</a>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">👥</div>
                    <div class="action-title">Team Collaboration</div>
                    <div class="action-description">Coordinate with hiring teams and stakeholders</div>
                    <a href="pages/applicant-management.php#collaboration" class="btn-portal">Collaborate</a>
                </div>
            </div>

            <!-- Recent Applications -->
            <div class="recent-applications">
                <h3>Recent Applications</h3>
                <?php if (!empty($recent_applications)): ?>
                    <?php foreach ($recent_applications as $app): ?>
                        <div class="application-item">
                            <div class="app-info">
                                <h4><?php echo h($app['first_name'] . ' ' . $app['last_name']); ?></h4>
                                <p><?php echo h($app['job_title']); ?> • <?php echo h($app['location']); ?></p>
                                <small>Applied: <?php echo date('M j, Y', strtotime($app['created_at'])); ?></small>
                            </div>
                            <div class="app-status status-<?php echo h($app['status']); ?>">
                                <?php echo h(ucfirst($app['status'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; color: #64748b;">
                        <p>No applications found. Start by creating job postings!</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <!-- Portal Footer -->
        <footer class="portal-footer">
            <p>&copy; 2025 HR Management System. All rights reserved.</p>
        </footer>
    </div>

    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stat cards on load
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.6s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });
        });
    </script>
</body>
</html>
