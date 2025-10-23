<?php
// Test script to verify the applicant portal database connection
require_once 'database/config.php';

echo "<h2>Database Connection Test</h2>";

try {
    // Test database connection
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test if tables exist
    $tables = ['users', 'departments', 'job_postings', 'job_applications', 'employees'];
    
    foreach ($tables as $table) {
        try {
            $result = fetchSingle("SHOW TABLES LIKE ?", [$table]);
            if ($result) {
                echo "<p style='color: green;'>✅ Table '$table' exists</p>";
            } else {
                echo "<p style='color: red;'>❌ Table '$table' does not exist</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error checking table '$table': " . $e->getMessage() . "</p>";
        }
    }
    
    // Test data counts
    echo "<h3>Data Counts:</h3>";
    
    try {
        $user_count = fetchSingle("SELECT COUNT(*) as count FROM users");
        echo "<p>Users: " . $user_count['count'] . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error counting users: " . $e->getMessage() . "</p>";
    }
    
    try {
        $dept_count = fetchSingle("SELECT COUNT(*) as count FROM departments");
        echo "<p>Departments: " . $dept_count['count'] . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error counting departments: " . $e->getMessage() . "</p>";
    }
    
    try {
        $job_count = fetchSingle("SELECT COUNT(*) as count FROM job_postings");
        echo "<p>Job Postings: " . $job_count['count'] . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error counting job postings: " . $e->getMessage() . "</p>";
    }
    
    try {
        $app_count = fetchSingle("SELECT COUNT(*) as count FROM job_applications");
        echo "<p>Job Applications: " . $app_count['count'] . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error counting applications: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='applicant-portal.php'>Go to Applicant Portal</a></p>";
echo "<p><a href='login.php'>Go to Login</a></p>";
?>
