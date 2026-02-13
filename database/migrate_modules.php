<?php
/**
 * HR1 Module Restructure - Database Migration
 * Creates new tables for the 5-module system:
 * 1. Recruitment Management (job_requisitions)
 * 2. Applicant Management (updates to job_applications)
 * 3. New Hire Onboarding (already exists, minor updates)
 * 4. Performance Management (performance_goals)
 * 5. Social Recognition (social_recognitions)
 */

require_once __DIR__ . '/config.php';

$migrations = [];

// ─── 1. Job Requisitions (Hiring Manager requests new staff) ───
$migrations[] = "CREATE TABLE IF NOT EXISTS job_requisitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requested_by INT NOT NULL,
    department_id INT NOT NULL,
    job_title VARCHAR(255) NOT NULL,
    positions_needed INT DEFAULT 1,
    employment_type ENUM('Full-time','Part-time','Contract','Seasonal') DEFAULT 'Full-time',
    urgency ENUM('Low','Medium','High','Critical') DEFAULT 'Medium',
    justification TEXT,
    requirements TEXT,
    preferred_start_date DATE NULL,
    salary_range_min DECIMAL(10,2) NULL,
    salary_range_max DECIMAL(10,2) NULL,
    status ENUM('Draft','Pending','Approved','Rejected','Filled','Cancelled') DEFAULT 'Pending',
    approved_by INT NULL,
    approved_at DATETIME NULL,
    rejection_reason TEXT NULL,
    linked_posting_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requested_by) REFERENCES user_accounts(id),
    FOREIGN KEY (department_id) REFERENCES departments(id)
)";

// ─── 2. Interview Schedules (structured interviews) ───
$migrations[] = "CREATE TABLE IF NOT EXISTS interview_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    interviewer_id INT NOT NULL,
    interview_type ENUM('Phone','Video','In-Person','Panel','Technical') DEFAULT 'In-Person',
    scheduled_date DATE NOT NULL,
    scheduled_time TIME NOT NULL,
    duration_minutes INT DEFAULT 60,
    location VARCHAR(255) NULL,
    meeting_link VARCHAR(500) NULL,
    notes TEXT NULL,
    status ENUM('Scheduled','Confirmed','Completed','Cancelled','No-Show') DEFAULT 'Scheduled',
    interviewer_feedback TEXT NULL,
    rating INT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES job_applications(id),
    FOREIGN KEY (interviewer_id) REFERENCES user_accounts(id),
    FOREIGN KEY (created_by) REFERENCES user_accounts(id)
)";

// ─── 3. Performance Goals (probationary goal setting) ───
$migrations[] = "CREATE TABLE IF NOT EXISTS performance_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    set_by INT NOT NULL,
    goal_title VARCHAR(255) NOT NULL,
    goal_description TEXT,
    category ENUM('Safety','Punctuality','Quality','Teamwork','Compliance','Custom') DEFAULT 'Custom',
    target_value VARCHAR(255) NULL,
    current_value VARCHAR(255) NULL,
    weight INT DEFAULT 25,
    status ENUM('Active','Completed','Failed','Cancelled') DEFAULT 'Active',
    due_date DATE NULL,
    review_period ENUM('3-Month','5-Month','6-Month','Annual') DEFAULT '3-Month',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES user_accounts(id),
    FOREIGN KEY (set_by) REFERENCES user_accounts(id)
)";

// ─── 4. Social Recognitions (welcome posts, kudos) ───
$migrations[] = "CREATE TABLE IF NOT EXISTS social_recognitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_id INT NOT NULL,
    given_by INT NULL,
    recognition_type ENUM('Welcome','Kudos','Achievement','Milestone','Shoutout') DEFAULT 'Kudos',
    message TEXT NOT NULL,
    badge_icon VARCHAR(50) DEFAULT 'star',
    is_system_generated TINYINT(1) DEFAULT 0,
    is_public TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recipient_id) REFERENCES user_accounts(id)
)";

// ─── 5. Password reset tokens (for forgot password feature) ───
$migrations[] = "CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// ─── 6. Ensure performance_reviews table exists ───
$migrations[] = "CREATE TABLE IF NOT EXISTS performance_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    review_type ENUM('Probation','3-Month','5-Month','6-Month','Annual') DEFAULT 'Probation',
    review_period_start DATE NULL,
    review_period_end DATE NULL,
    safety_score INT DEFAULT 0,
    punctuality_score INT DEFAULT 0,
    quality_score INT DEFAULT 0,
    teamwork_score INT DEFAULT 0,
    overall_score DECIMAL(3,1) DEFAULT 0,
    recommendation ENUM('Regularize','Extend Probation','Terminate','Promote') DEFAULT 'Regularize',
    comments TEXT,
    status ENUM('Draft','Submitted','Acknowledged','Finalized') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES user_accounts(id),
    FOREIGN KEY (reviewer_id) REFERENCES user_accounts(id)
)";

// ─── 7. Ensure employee_requirements table exists ───
$migrations[] = "CREATE TABLE IF NOT EXISTS employee_requirements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT DEFAULT 0,
    status ENUM('Pending','Verified','Rejected') DEFAULT 'Pending',
    verified_by INT NULL,
    verified_at DATETIME NULL,
    rejection_reason TEXT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_accounts(id)
)";

// Run migrations
$success = 0;
$errors = [];

foreach ($migrations as $sql) {
    try {
        executeQuery($sql);
        $success++;
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

// Add columns safely - check existence first via INFORMATION_SCHEMA
$dbName = defined('DB_NAME') ? DB_NAME : 'hr1_hr1data';

function columnExists($dbName, $table, $column) {
    $row = fetchSingle(
        "SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?",
        [$dbName, $table, $column]
    );
    return ($row['c'] ?? 0) > 0;
}

$alterations = [
    ['job_applications', 'requisition_id',  "ALTER TABLE job_applications ADD COLUMN requisition_id INT NULL AFTER job_posting_id"],
    ['job_applications', 'screening_notes', "ALTER TABLE job_applications ADD COLUMN screening_notes TEXT NULL AFTER resume_path"],
    ['job_applications', 'screened_by',     "ALTER TABLE job_applications ADD COLUMN screened_by INT NULL AFTER screening_notes"],
    ['job_applications', 'screened_at',     "ALTER TABLE job_applications ADD COLUMN screened_at DATETIME NULL AFTER screened_by"],
    ['job_postings',     'requisition_id',  "ALTER TABLE job_postings ADD COLUMN requisition_id INT NULL AFTER id"],
];

foreach ($alterations as [$table, $col, $sql]) {
    try {
        if (!columnExists($dbName, $table, $col)) {
            executeQuery($sql);
            $success++;
        }
    } catch (Exception $e) {
        // Column may already exist
    }
}

if (php_sapi_name() === 'cli') {
    echo "Migration complete: {$success} operations successful.\n";
    if (!empty($errors)) {
        echo "Errors:\n";
        foreach ($errors as $err) echo "  - {$err}\n";
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'errors' => $errors]);
}
