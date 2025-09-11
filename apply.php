<?php
require_once 'database/config.php';

$job_id = intval($_GET['job_id'] ?? 0);
$success_message = '';
$error_message = '';

// Get job details
try {
    $sql = "SELECT jp.*, d.name as department_name
            FROM job_postings jp
            LEFT JOIN departments d ON jp.department_id = d.id
            WHERE jp.id = ? AND jp.status = 'active'";
    
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application']) && $job) {
    try {
        // Validate required fields
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $cover_letter = trim($_POST['cover_letter'] ?? '');
        
        if (empty($first_name) || empty($last_name) || empty($email) || empty($cover_letter)) {
            throw new Exception("Please fill in all required fields.");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }
        
        // Check if already applied
        $existing_application = fetchSingle(
            "SELECT id FROM job_applications WHERE job_posting_id = ? AND email = ?",
            [$job_id, $email]
        );
        
        if ($existing_application) {
            throw new Exception("You have already applied for this position with this email address.");
        }
        
        // Handle file upload (resume)
        $resume_path = null;
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/resumes/';
            
            // Create upload directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['pdf', 'doc', 'docx'];
            
            if (!in_array($file_extension, $allowed_extensions)) {
                throw new Exception("Please upload a PDF, DOC, or DOCX file for your resume.");
            }
            
            if ($_FILES['resume']['size'] > 5 * 1024 * 1024) { // 5MB limit
                throw new Exception("Resume file size must be less than 5MB.");
            }
            
            $filename = 'resume_' . $job_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
            $resume_path = $upload_dir . $filename;
            
            if (!move_uploaded_file($_FILES['resume']['tmp_name'], $resume_path)) {
                throw new Exception("Failed to upload resume. Please try again.");
            }
        }
        
        // Insert application into database
        $sql = "INSERT INTO job_applications (job_posting_id, first_name, last_name, email, phone, 
                resume_path, cover_letter, status, applied_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'new', NOW())";
        
        $params = [$job_id, $first_name, $last_name, $email, $phone, $resume_path, $cover_letter];
        
        $application_id = insertRecord($sql, $params);
        
        if ($application_id) {
            $success_message = "Your application has been submitted successfully! We'll review your application and get back to you soon.";
            // Clear form data
            $_POST = [];
        } else {
            throw new Exception("Failed to submit application. Please try again.");
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        
        // Clean up uploaded file if application failed
        if (isset($resume_path) && file_exists($resume_path)) {
            unlink($resume_path);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $job ? 'Apply for ' . htmlspecialchars($job['title']) : 'Job Application'; ?> | HR1 Management</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        :root {
            --primary-color: #3b82f6;
            --primary-hover: #2563eb;
            --secondary-color: #6b7280;
            --success-color: #10b981;
            --error-color: #ef4444;
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
            max-width: 800px;
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
            font-size: 2rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .job-meta {
            display: flex;
            gap: 2rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
        }

        .application-form {
            background: var(--bg-card);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-header h2 {
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .form-header p {
            color: var(--text-secondary);
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section h3 {
            color: var(--text-primary);
            margin-bottom: 1rem;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }

        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .required {
            color: var(--error-color);
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-display {
            padding: 0.75rem;
            border: 2px dashed var(--border-color);
            border-radius: var(--border-radius);
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }

        .file-input-display:hover {
            border-color: var(--primary-color);
            background: var(--bg-secondary);
        }

        .file-info {
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
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
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
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

        .success-message {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            border: 1px solid rgba(16, 185, 129, 0.2);
            text-align: center;
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            border: 1px solid rgba(239, 68, 68, 0.2);
            text-align: center;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            
            .job-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .form-actions {
                flex-direction: column;
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
            <span style="color: var(--text-secondary);">Job Application</span>
        </div>
    </header>

    <div class="container">
        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <h3>🎉 Application Submitted Successfully!</h3>
                <p><?php echo htmlspecialchars($success_message); ?></p>
                <div style="margin-top: 1rem;">
                    <a href="careers.php" class="btn btn-outline">Browse More Jobs</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($job && empty($success_message)): ?>
            <!-- Job Details -->
            <div class="job-header">
                <h1 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h1>
                <div class="job-meta">
                    <div class="job-meta-item">
                        <span>🏢</span>
                        <span><?php echo htmlspecialchars($job['department_name']); ?></span>
                    </div>
                    <div class="job-meta-item">
                        <span>📍</span>
                        <span><?php echo htmlspecialchars($job['location']); ?></span>
                    </div>
                    <div class="job-meta-item">
                        <span>💼</span>
                        <span><?php echo htmlspecialchars($job['employment_type']); ?></span>
                    </div>
                    <div class="job-meta-item">
                        <span>💰</span>
                        <span><?php echo htmlspecialchars($job['salary_display']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Application Form -->
            <div class="application-form">
                <div class="form-header">
                    <h2>Apply for this Position</h2>
                    <p>We're excited to learn more about you! Please fill out the form below.</p>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <!-- Personal Information -->
                    <div class="form-section">
                        <h3>Personal Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name <span class="required">*</span></label>
                                <input type="text" id="first_name" name="first_name" required 
                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name <span class="required">*</span></label>
                                <input type="text" id="last_name" name="last_name" required 
                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address <span class="required">*</span></label>
                                <input type="email" id="email" name="email" required 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Resume Upload -->
                    <div class="form-section">
                        <h3>Resume</h3>
                        <div class="form-group">
                            <label for="resume">Upload Resume</label>
                            <div class="file-input-wrapper">
                                <input type="file" id="resume" name="resume" class="file-input" 
                                       accept=".pdf,.doc,.docx" onchange="updateFileDisplay()">
                                <div class="file-input-display" id="file-display">
                                    <span>📄 Click to upload your resume</span>
                                    <div class="file-info">Accepted formats: PDF, DOC, DOCX (Max 5MB)</div>
                                </div>
                            </div>
                            <div id="file-selected" style="display: none; margin-top: 0.5rem; color: var(--success-color);"></div>
                        </div>
                    </div>

                    <!-- Cover Letter -->
                    <div class="form-section">
                        <h3>Cover Letter</h3>
                        <div class="form-group">
                            <label for="cover_letter">Tell us why you're perfect for this role <span class="required">*</span></label>
                            <textarea id="cover_letter" name="cover_letter" rows="6" required 
                                      placeholder="Share your motivation, relevant experience, and what makes you a great fit for this position..."><?php echo htmlspecialchars($_POST['cover_letter'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <a href="careers.php" class="btn btn-outline">Cancel</a>
                        <button type="submit" name="submit_application" class="btn btn-primary">
                            Submit Application
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function updateFileDisplay() {
            const fileInput = document.getElementById('resume');
            const fileDisplay = document.getElementById('file-display');
            const fileSelected = document.getElementById('file-selected');
            
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                fileSelected.textContent = `✓ Selected: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                fileSelected.style.display = 'block';
                fileDisplay.style.borderColor = 'var(--success-color)';
                fileDisplay.style.background = 'rgba(16, 185, 129, 0.05)';
            } else {
                fileSelected.style.display = 'none';
                fileDisplay.style.borderColor = 'var(--border-color)';
                fileDisplay.style.background = 'transparent';
            }
        }

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const requiredFields = form.querySelectorAll('input[required], textarea[required]');
                    let isValid = true;
                    
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            field.style.borderColor = 'var(--error-color)';
                            isValid = false;
                        } else {
                            field.style.borderColor = 'var(--border-color)';
                        }
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                        alert('Please fill in all required fields.');
                    }
                });
            }
        });
    </script>
</body>
</html>
