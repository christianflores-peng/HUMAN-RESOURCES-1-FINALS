<?php
/**
 * Sample Data Insertion Script for HR Management System
 * 
 * This script populates the database with sample data for testing the applicant portal.
 * Run this script after setting up the database schema.
 */

require_once 'config.php';

echo "<h2>Inserting Sample Data for Applicant Portal</h2>";

try {
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Insert sample job postings
    echo "<h3>Inserting Sample Job Postings...</h3>";
    
    $job_postings = [
        [
            'title' => 'Senior Software Engineer',
            'department_id' => 1, // Engineering
            'location' => 'Remote',
            'employment_type' => 'Full-time',
            'salary_min' => 80000,
            'salary_max' => 120000,
            'description' => 'We are looking for a Senior Software Engineer to join our development team. You will be responsible for designing, developing, and maintaining our web applications.',
            'requirements' => 'Bachelor\'s degree in Computer Science, 5+ years of experience with PHP, JavaScript, and MySQL. Experience with modern frameworks preferred.',
            'status' => 'active',
            'posted_by' => 1,
            'closing_date' => date('Y-m-d', strtotime('+30 days'))
        ],
        [
            'title' => 'Marketing Manager',
            'department_id' => 2, // Marketing
            'location' => 'New York, NY',
            'employment_type' => 'Full-time',
            'salary_min' => 60000,
            'salary_max' => 90000,
            'description' => 'Join our marketing team as a Marketing Manager. You will develop and execute marketing strategies to promote our products and services.',
            'requirements' => 'Bachelor\'s degree in Marketing or related field, 3+ years of marketing experience, strong communication skills.',
            'status' => 'active',
            'posted_by' => 1,
            'closing_date' => date('Y-m-d', strtotime('+25 days'))
        ],
        [
            'title' => 'Sales Representative',
            'department_id' => 3, // Sales
            'location' => 'Chicago, IL',
            'employment_type' => 'Full-time',
            'salary_min' => 45000,
            'salary_max' => 70000,
            'description' => 'We are seeking a motivated Sales Representative to join our sales team. You will be responsible for generating new business and maintaining client relationships.',
            'requirements' => 'High school diploma required, Bachelor\'s preferred, 2+ years of sales experience, excellent interpersonal skills.',
            'status' => 'active',
            'posted_by' => 1,
            'closing_date' => date('Y-m-d', strtotime('+20 days'))
        ]
    ];
    
    foreach ($job_postings as $job) {
        try {
            $sql = "INSERT INTO job_postings (title, department_id, location, employment_type, salary_min, salary_max, description, requirements, status, posted_by, closing_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $job_id = insertRecord($sql, [
                $job['title'],
                $job['department_id'],
                $job['location'],
                $job['employment_type'],
                $job['salary_min'],
                $job['salary_max'],
                $job['description'],
                $job['requirements'],
                $job['status'],
                $job['posted_by'],
                $job['closing_date']
            ]);
            echo "<p style='color: green;'>✅ Inserted job: " . $job['title'] . " (ID: $job_id)</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error inserting job '" . $job['title'] . "': " . $e->getMessage() . "</p>";
        }
    }
    
    // Insert sample job applications
    echo "<h3>Inserting Sample Job Applications...</h3>";
    
    $applications = [
        [
            'job_posting_id' => 1,
            'first_name' => 'John',
            'last_name' => 'Smith',
            'email' => 'john.smith@email.com',
            'phone' => '555-0101',
            'resume_path' => 'uploads/resumes/john_smith_resume.pdf',
            'cover_letter' => 'I am very interested in the Senior Software Engineer position. With my 6 years of experience in web development, I believe I would be a great fit for your team.',
            'status' => 'new'
        ],
        [
            'job_posting_id' => 1,
            'first_name' => 'Sarah',
            'last_name' => 'Johnson',
            'email' => 'sarah.johnson@email.com',
            'phone' => '555-0102',
            'resume_path' => 'uploads/resumes/sarah_johnson_resume.pdf',
            'cover_letter' => 'I have extensive experience with PHP and JavaScript frameworks. I am excited about the opportunity to contribute to your development team.',
            'status' => 'reviewed'
        ],
        [
            'job_posting_id' => 2,
            'first_name' => 'Michael',
            'last_name' => 'Brown',
            'email' => 'michael.brown@email.com',
            'phone' => '555-0103',
            'resume_path' => 'uploads/resumes/michael_brown_resume.pdf',
            'cover_letter' => 'I am passionate about marketing and have successfully managed several campaigns. I would love to bring my expertise to your team.',
            'status' => 'screening'
        ],
        [
            'job_posting_id' => 2,
            'first_name' => 'Emily',
            'last_name' => 'Davis',
            'email' => 'emily.davis@email.com',
            'phone' => '555-0104',
            'resume_path' => 'uploads/resumes/emily_davis_resume.pdf',
            'cover_letter' => 'With my background in digital marketing and strong analytical skills, I am confident I can drive results for your marketing initiatives.',
            'status' => 'interview'
        ],
        [
            'job_posting_id' => 3,
            'first_name' => 'David',
            'last_name' => 'Wilson',
            'email' => 'david.wilson@email.com',
            'phone' => '555-0105',
            'resume_path' => 'uploads/resumes/david_wilson_resume.pdf',
            'cover_letter' => 'I have a proven track record in sales and am excited about the opportunity to grow with your company.',
            'status' => 'offer'
        ],
        [
            'job_posting_id' => 1,
            'first_name' => 'Lisa',
            'last_name' => 'Anderson',
            'email' => 'lisa.anderson@email.com',
            'phone' => '555-0106',
            'resume_path' => 'uploads/resumes/lisa_anderson_resume.pdf',
            'cover_letter' => 'I am a full-stack developer with experience in both frontend and backend technologies. I am eager to contribute to your engineering team.',
            'status' => 'hired'
        ]
    ];
    
    foreach ($applications as $app) {
        try {
            $sql = "INSERT INTO job_applications (job_posting_id, first_name, last_name, email, phone, resume_path, cover_letter, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $app_id = insertRecord($sql, [
                $app['job_posting_id'],
                $app['first_name'],
                $app['last_name'],
                $app['email'],
                $app['phone'],
                $app['resume_path'],
                $app['cover_letter'],
                $app['status']
            ]);
            echo "<p style='color: green;'>✅ Inserted application: " . $app['first_name'] . " " . $app['last_name'] . " (ID: $app_id)</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error inserting application for " . $app['first_name'] . " " . $app['last_name'] . ": " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>Sample Data Insertion Complete!</h3>";
    echo "<p><a href='../applicant-portal.php'>View Applicant Portal</a></p>";
    echo "<p><a href='../test-portal.php'>Test Database Connection</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>
