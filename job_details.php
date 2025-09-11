<?php
require_once 'database/config.php';

$job_id = intval($_GET['id'] ?? 0);
$error_message = '';

// Get job details
try {
    $sql = "SELECT jp.*, d.name as department_name, u.username as posted_by_username,
                   COUNT(ja.id) as application_count
            FROM job_postings jp
            LEFT JOIN departments d ON jp.department_id = d.id
            LEFT JOIN users u ON jp.posted_by = u.id
            LEFT JOIN job_applications ja ON jp.id = ja.job_posting_id
            WHERE jp.id = ? AND jp.status = 'active'
            GROUP BY jp.id";
    
    $job = fetchSingle($sql, [$job_id]);
    
    if (!$job) {
        throw new Exception("Job posting not found or no longer available.");
    }
    
    // Format salary display
    $salary_range = '';
    if ($job['salary_min'] || $job['salary_max']) {
        if ($job['salary_min'] && $job['salary_max']) {
            $salary_range = '$' . number_format($job['salary_min']) . ' - $' . number_format($job['salary_max']);
        } elseif ($job['salary_min']) {
            $salary_range = 'From $' . number_format($job['salary_min']);
        } elseif ($job['salary_max']) {
            $salary_range = 'Up to $' . number_format($job['salary_max']);
        }
    } else {
        $salary_range = 'Competitive';
    }
    $job['salary_display'] = $salary_range;
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    $job = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $job ? htmlspecialchars($job['title']) : 'Job Details'; ?> | HR1 Management</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        :root {
            --primary-color: #3b82f6;
            --primary-hover: #2563eb;
            --secondary-color: #6b7280;
            --success-color: #10b981;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-card: #ffffff;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --border-radius: 8px;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .header {
            background: var(--bg-primary);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            box-shadow: var(--shadow);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: var(--primary-color);
            font-weight: 500;
            transition: color 0.3s;
        }

        .back-btn:hover {
            color: var(--primary-hover);
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
        }

        .job-header {
            background: var(--bg-card);
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .job-title {
            font-size: 2.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .job-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .apply-section {
            background: linear-gradient(135deg, var(--primary-color), #6366f1);
            color: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            text-align: center;
        }

        .job-content {
            background: var(--bg-card);
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .content-section {
            margin-bottom: 2rem;
        }

        .content-section h2 {
            color: var(--text-primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-color);
        }

        .content-section p, .content-section ul {
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        .content-section ul {
            padding-left: 1.5rem;
        }

        .content-section li {
            margin-bottom: 0.5rem;
        }

        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            font-size: 1rem;
        }

        .btn-primary {
            background: white;
            color: var(--primary-color);
        }

        .btn-primary:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-1px);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            padding: 2rem;
            border-radius: var(--border-radius);
            text-align: center;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .job-stats {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
            padding: 1rem 0;
            border-top: 1px solid var(--border-color);
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        @media (max-width: 768px) {
            .job-title {
                font-size: 2rem;
            }
            
            .job-meta {
                grid-template-columns: 1fr;
            }
            
            .job-stats {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="careers.php" class="back-btn">
                <span>←</span>
                <span>Back to Careers</span>
            </a>
            <span style="color: var(--text-secondary);">Job Details</span>
        </div>
    </header>

    <div class="container">
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <h2>Oops! Something went wrong</h2>
                <p><?php echo htmlspecialchars($error_message); ?></p>
                <div style="margin-top: 1rem;">
                    <a href="careers.php" class="btn btn-outline">Browse Available Jobs</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Job Header -->
            <div class="job-header">
                <h1 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h1>
                
                <div class="job-meta">
                    <div class="job-meta-item">
                        <span style="font-size: 1.2em;">🏢</span>
                        <span><?php echo htmlspecialchars($job['department_name']); ?> Department</span>
                    </div>
                    <div class="job-meta-item">
                        <span style="font-size: 1.2em;">📍</span>
                        <span><?php echo htmlspecialchars($job['location']); ?></span>
                    </div>
                    <div class="job-meta-item">
                        <span style="font-size: 1.2em;">💼</span>
                        <span><?php echo htmlspecialchars($job['employment_type']); ?></span>
                    </div>
                    <div class="job-meta-item">
                        <span style="font-size: 1.2em;">💰</span>
                        <span><?php echo htmlspecialchars($job['salary_display']); ?></span>
                    </div>
                </div>

                <div class="apply-section">
                    <h3 style="margin-bottom: 0.5rem;">Ready to join our team?</h3>
                    <p style="margin-bottom: 1rem; opacity: 0.9;">We'd love to hear from you!</p>
                    <a href="apply.php?job_id=<?php echo $job['id']; ?>" class="btn btn-primary">
                        Apply Now
                    </a>
                </div>

                <div class="job-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo intval($job['application_count']); ?></div>
                        <div class="stat-label">Applications Received</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo date('M j', strtotime($job['posted_date'])); ?></div>
                        <div class="stat-label">Posted Date</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo ucfirst($job['status']); ?></div>
                        <div class="stat-label">Status</div>
                    </div>
                </div>
            </div>

            <!-- Job Content -->
            <div class="job-content">
                <div class="content-section">
                    <h2>Job Description</h2>
                    <div style="white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($job['description'])); ?></div>
                </div>

                <div class="content-section">
                    <h2>Requirements & Qualifications</h2>
                    <div style="white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($job['requirements'])); ?></div>
                </div>

                <?php if ($job['closing_date']): ?>
                <div class="content-section">
                    <h2>Application Deadline</h2>
                    <p><strong><?php echo date('F j, Y', strtotime($job['closing_date'])); ?></strong></p>
                    <p style="color: var(--text-secondary);">Don't wait - apply today!</p>
                </div>
                <?php endif; ?>

                <div class="content-section">
                    <h2>About This Opportunity</h2>
                    <p>Join our dynamic team at HR1 Management and be part of a company that values innovation, collaboration, and professional growth. We offer competitive compensation, comprehensive benefits, and opportunities for career advancement.</p>
                    
                    <h3 style="margin: 1.5rem 0 1rem 0; color: var(--text-primary);">What We Offer:</h3>
                    <ul>
                        <li>Competitive salary and performance bonuses</li>
                        <li>Comprehensive health and dental insurance</li>
                        <li>Flexible working arrangements</li>
                        <li>Professional development opportunities</li>
                        <li>Collaborative and inclusive work environment</li>
                        <li>Career growth and advancement paths</li>
                    </ul>
                </div>

                <div style="text-align: center; padding: 2rem 0; border-top: 1px solid var(--border-color);">
                    <h3 style="color: var(--text-primary); margin-bottom: 1rem;">Interested in this position?</h3>
                    <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">Submit your application and let us know why you'd be a great fit for our team.</p>
                    <a href="apply.php?job_id=<?php echo $job['id']; ?>" class="btn btn-primary" 
                       style="background: var(--primary-color); color: white; margin-right: 1rem;">
                        Apply Now
                    </a>
                    <a href="careers.php" class="btn btn-outline">
                        View All Jobs
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
