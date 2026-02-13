<?php
session_start();
require_once '../database/config.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $_SESSION['role'] ?? null;

// Get all active job postings for public view
try {
    $sql = "SELECT jp.*, d.department_name,
                   COUNT(ja.id) as application_count
            FROM job_postings jp
            LEFT JOIN departments d ON jp.department_id = d.id
            LEFT JOIN job_applications ja ON jp.id = ja.job_posting_id
            WHERE jp.status = 'Open'
            GROUP BY jp.id
            ORDER BY jp.posted_date DESC";
    
    $job_postings = fetchAll($sql);
    
    // Format salary display
    foreach ($job_postings as &$job) {
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
    }
    
} catch (Exception $e) {
    $job_postings = [];
    $error_message = "Error loading job postings. Please try again later.";
}

// Get departments for filter
try {
    $departments = fetchAll("SELECT DISTINCT d.id, d.department_name 
                           FROM departments d 
                           JOIN job_postings jp ON d.id = jp.department_id 
                           WHERE jp.status = 'Open' 
                           ORDER BY d.department_name");
} catch (Exception $e) {
    $departments = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Careers - Join Our Team | HR1 Management</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --primary-color: #0ea5e9;
            --primary-hover: #0284c7;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-card: #1e2936;
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --border-radius: 12px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.4);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a1929 0%, #1a2942 100%);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
        }

        .header {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 0;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo img {
            width: 40px;
            height: 40px;
        }

        .logo h1 {
            font-size: 1.5rem;
            color: var(--primary-color);
            font-weight: 700;
        }

        .header-nav {
            display: flex;
            gap: 2rem;
        }

        .header-nav a {
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 500;
            transition: all 0.3s;
            padding: 0.5rem 1rem;
            border-radius: 6px;
        }

        .header-nav a:hover {
            color: var(--primary-color);
            background: rgba(14, 165, 233, 0.1);
        }

        .header-nav a.active {
            color: var(--primary-color);
            background: rgba(14, 165, 233, 0.15);
        }

        .theme-toggle {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--text-primary);
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        .theme-toggle:active {
            transform: scale(0.95);
        }

        .hero {
            background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 50%, #6366f1 100%);
            color: white;
            padding: 5rem 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.05"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');
            opacity: 0.3;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            font-weight: 800;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .hero p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.95;
            position: relative;
            z-index: 1;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 2rem;
            position: relative;
            z-index: 1;
        }

        .hero-stat {
            text-align: center;
        }

        .hero-stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            display: block;
        }

        .hero-stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .content {
            padding: 3rem 0;
        }

        .filters {
            background: var(--bg-card);
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .filters h3 {
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .filter-row {
            display: flex;
            gap: 1rem;
            align-items: end;
        }

        .filter-group {
            flex: 1;
        }

        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .filter-group select, .filter-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: all 0.3s;
        }

        .filter-group select:focus, .filter-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }

        .filter-group input::placeholder {
            color: var(--text-muted);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(14, 165, 233, 0.4);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .jobs-grid {
            display: grid;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .job-card {
            background: var(--bg-card);
            border-radius: var(--border-radius);
            padding: 2.5rem;
            box-shadow: var(--shadow);
            transition: all 0.3s;
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .job-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-color);
            transform: scaleY(0);
            transition: transform 0.3s;
        }

        .job-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
            border-color: var(--primary-color);
        }

        .job-card:hover::before {
            transform: scaleY(1);
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .job-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .job-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .job-meta-item i {
            width: 18px;
            height: 18px;
            color: var(--primary-color);
        }

        .job-description {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .job-tags {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .job-tag {
            background: rgba(14, 165, 233, 0.1);
            color: var(--primary-color);
            padding: 0.375rem 0.875rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid rgba(14, 165, 233, 0.2);
        }

        .job-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .job-posted {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .no-jobs {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .no-jobs h3 {
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .footer {
            background: var(--bg-primary);
            color: var(--text-muted);
            padding: 2.5rem 0;
            text-align: center;
            margin-top: 4rem;
            border-top: 1px solid var(--border-color);
        }

        .job-count {
            background: var(--bg-card);
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }

        .job-count i {
            color: var(--primary-color);
            width: 24px;
            height: 24px;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .filter-row {
                flex-direction: column;
            }
            
            .job-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .job-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .job-actions {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <?php $logo_path = '../assets/images/slate.png'; include '../includes/loading-screen.php'; ?>
    
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <img src="../assets/images/slate.png" alt="HR1 Logo">
                <h1>Human Resources 1</h1>
            </div>
            <nav class="header-nav">
                <a href="careers.php" class="active">Careers</a>
                <a href="../index.php">Home</a>
                <?php if ($is_logged_in && $user_role === 'Applicant'): ?>
                    <a href="../my-account.php">My Applications</a>
                    <a href="../logout.php">Logout</a>
                <?php elseif ($is_logged_in): ?>
                    <a href="../pages/dashboard.php">Dashboard</a>
                    <a href="../logout.php">Logout</a>
                <?php else: ?>
                    <a href="../partials/login.php">Login</a>
                    <a href="../partials/terms.php">Register</a>
                <?php endif; ?>
                <button id="themeToggle" class="theme-toggle" aria-label="Toggle theme">🌓</button>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Join Our Team</h1>
            <p>Discover exciting career opportunities and help us build the future of HR management. We're looking for passionate, talented individuals to join our growing team.</p>
            <div class="hero-stats">
                <div class="hero-stat">
                    <span class="hero-stat-number"><?php echo count($job_postings); ?></span>
                    <span class="hero-stat-label">Open Positions</span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-number"><?php echo count($departments); ?></span>
                    <span class="hero-stat-label">Departments</span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-number">100+</span>
                    <span class="hero-stat-label">Team Members</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="content">
        <div class="container">
            <!-- Filters -->
            <div class="filters">
                <h3>Find Your Perfect Role</h3>
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="department_filter">Department</label>
                        <select id="department_filter" onchange="filterJobs()">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>">
                                    <?php echo htmlspecialchars($dept['department_name'] ?? ''); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="type_filter">Employment Type</label>
                        <select id="type_filter" onchange="filterJobs()">
                            <option value="">All Types</option>
                            <option value="Full-time">Full-time</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Contract">Contract</option>
                            <option value="Internship">Internship</option>
                            <option value="Remote">Remote</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="search_filter">Search Keywords</label>
                        <input type="text" id="search_filter" placeholder="Job title, skills..." onkeyup="filterJobs()">
                    </div>
                    <div class="filter-group">
                        <button type="button" class="btn btn-outline" onclick="clearFilters()">Clear</button>
                    </div>
                </div>
            </div>

            <!-- Job Count -->
            <?php if (!empty($job_postings)): ?>
            <div style="text-align: center;">
                <div class="job-count">
                    <i data-lucide="briefcase"></i>
                    <span><strong><?php echo count($job_postings); ?></strong> open position<?php echo count($job_postings) != 1 ? 's' : ''; ?> available</span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Job Listings -->
            <div class="jobs-grid" id="jobs-container">
                <?php if (empty($job_postings)): ?>
                    <div class="no-jobs">
                        <h3>No Open Positions</h3>
                        <p>We don't have any open positions at the moment, but we're always looking for talented individuals. Please check back soon!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($job_postings as $job): ?>
                        <div class="job-card" 
                             data-department="<?php echo $job['department_id']; ?>"
                             data-type="<?php echo strtolower($job['employment_type']); ?>"
                             data-keywords="<?php echo strtolower($job['title'] . ' ' . $job['department_name'] . ' ' . $job['description']); ?>">
                            <div class="job-header">
                                <div>
                                    <h2 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h2>
                                    <div class="job-meta">
                                        <div class="job-meta-item">
                                            <i data-lucide="building"></i>
                                            <span><?php echo htmlspecialchars($job['department_name']); ?></span>
                                        </div>
                                        <div class="job-meta-item">
                                            <i data-lucide="map-pin"></i>
                                            <span><?php echo htmlspecialchars($job['location']); ?></span>
                                        </div>
                                        <div class="job-meta-item">
                                            <i data-lucide="briefcase"></i>
                                            <span><?php echo htmlspecialchars($job['employment_type']); ?></span>
                                        </div>
                                        <div class="job-meta-item">
                                            <i data-lucide="dollar-sign"></i>
                                            <span><?php echo htmlspecialchars($job['salary_display']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="job-description">
                                <?php echo nl2br(htmlspecialchars(substr($job['description'], 0, 200))); ?>
                                <?php if (strlen($job['description']) > 200): ?>...<?php endif; ?>
                            </div>
                            
                            <div class="job-tags">
                                <span class="job-tag"><?php echo htmlspecialchars($job['department_name']); ?></span>
                                <span class="job-tag"><?php echo htmlspecialchars($job['employment_type']); ?></span>
                                <?php if ($job['application_count'] > 0): ?>
                                    <span class="job-tag"><?php echo $job['application_count']; ?> applications</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="job-actions">
                                <div class="job-posted">
                                    Posted <?php echo date('M j, Y', strtotime($job['posted_date'])); ?>
                                </div>
                                <div>
                                    <a href="job_details.php?id=<?php echo $job['id']; ?>" class="btn btn-outline">View Details</a>
                                    <a href="../partials/terms.php?job_id=<?php echo $job['id']; ?>&type=applicant" class="btn btn-primary">Apply Now</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> HR1 Management System. All rights reserved. | Equal Opportunity Employer</p>
        </div>
    </footer>

    <script>
        function filterJobs() {
            const departmentFilter = document.getElementById('department_filter').value;
            const typeFilter = document.getElementById('type_filter').value.toLowerCase();
            const searchFilter = document.getElementById('search_filter').value.toLowerCase();
            
            const jobCards = document.querySelectorAll('.job-card');
            let visibleCount = 0;
            
            jobCards.forEach(card => {
                const department = card.dataset.department;
                const type = card.dataset.type;
                const keywords = card.dataset.keywords;
                
                let show = true;
                
                if (departmentFilter && department !== departmentFilter) {
                    show = false;
                }
                
                if (typeFilter && type !== typeFilter) {
                    show = false;
                }
                
                if (searchFilter && !keywords.includes(searchFilter)) {
                    show = false;
                }
                
                if (show) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show no results message if no jobs visible
            const noJobsMessage = document.querySelector('.no-jobs');
            if (visibleCount === 0 && jobCards.length > 0) {
                if (!noJobsMessage) {
                    const container = document.getElementById('jobs-container');
                    const noResultsDiv = document.createElement('div');
                    noResultsDiv.className = 'no-jobs';
                    noResultsDiv.innerHTML = '<h3>No Matching Positions</h3><p>Try adjusting your search criteria to find more opportunities.</p>';
                    container.appendChild(noResultsDiv);
                }
            } else if (noJobsMessage && visibleCount > 0) {
                noJobsMessage.remove();
            }
        }
        
        function clearFilters() {
            document.getElementById('department_filter').value = '';
            document.getElementById('type_filter').value = '';
            document.getElementById('search_filter').value = '';
            filterJobs();
        }

        // Theme Toggle Functionality
        function initializeTheme() {
            const storedTheme = localStorage.getItem('hrTheme') || 'theme-dark';
            document.body.classList.remove('theme-light', 'theme-dark');
            document.body.classList.add(storedTheme);
            
            const themeToggle = document.getElementById('themeToggle');
            if (themeToggle) {
                themeToggle.textContent = storedTheme === 'theme-light' ? '🌙' : '☀️';
                themeToggle.setAttribute('aria-label', storedTheme === 'theme-light' ? 'Switch to dark mode' : 'Switch to light mode');
            }
        }

        function toggleTheme() {
            const isDark = document.body.classList.contains('theme-dark');
            const next = isDark ? 'theme-light' : 'theme-dark';
            document.body.classList.remove('theme-light', 'theme-dark');
            document.body.classList.add(next);
            localStorage.setItem('hrTheme', next);
            
            const themeToggle = document.getElementById('themeToggle');
            if (themeToggle) {
                themeToggle.textContent = next === 'theme-light' ? '🌙' : '☀️';
                themeToggle.setAttribute('aria-label', next === 'theme-light' ? 'Switch to dark mode' : 'Switch to light mode');
            }
        }

        // Initialize theme on page load
        initializeTheme();

        // Add event listener to theme toggle button
        document.getElementById('themeToggle').addEventListener('click', toggleTheme);
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
