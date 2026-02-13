<?php
/**
 * Database Update Script
 * Run this file once to update the database schema for the Applicant Tracking System
 */

require_once '../database/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Update - HR1 ATS</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #0ea5e9; }
        .success { color: #10b981; padding: 10px; background: #d1fae5; border-radius: 4px; margin: 10px 0; }
        .error { color: #ef4444; padding: 10px; background: #fee2e2; border-radius: 4px; margin: 10px 0; }
        .info { color: #0ea5e9; padding: 10px; background: #dbeafe; border-radius: 4px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>HR1 Applicant Tracking System - Database Update</h1>
        <p>This script will update your database schema to support the complete applicant workflow.</p>
";

try {
    $pdo = getDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='info'>Starting database update...</div>";
    
    // Read the SQL file
    $sql_file = '../database/update_application_workflow.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: $sql_file");
    }
    
    $sql = file_get_contents($sql_file);
    
    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $pdo->exec($statement);
            $success_count++;
            
            // Extract table/action info for display
            if (preg_match('/ALTER TABLE `?(\w+)`?/i', $statement, $matches)) {
                echo "<div class='success'>✓ Updated table: {$matches[1]}</div>";
            } elseif (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "<div class='success'>✓ Created table: {$matches[1]}</div>";
            } else {
                echo "<div class='success'>✓ Executed statement successfully</div>";
            }
        } catch (PDOException $e) {
            // Check if error is about column already existing
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "<div class='info'>ℹ Column already exists (skipped)</div>";
            } elseif (strpos($e->getMessage(), 'already exists') !== false) {
                echo "<div class='info'>ℹ Table already exists (skipped)</div>";
            } else {
                $error_count++;
                echo "<div class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
                echo "<pre>" . htmlspecialchars(substr($statement, 0, 200)) . "...</pre>";
            }
        }
    }
    
    echo "<hr>";
    echo "<div class='success'><strong>Database update completed!</strong></div>";
    echo "<div class='info'>Successful operations: $success_count</div>";
    if ($error_count > 0) {
        echo "<div class='error'>Errors encountered: $error_count</div>";
    }
    
    echo "<h2>New Tables Created:</h2>";
    echo "<ul>";
    echo "<li>application_status_history - Track all status changes</li>";
    echo "<li>applicant_notifications - Notification system</li>";
    echo "<li>interview_schedules - Interview management</li>";
    echo "<li>road_test_schedules - Road test management</li>";
    echo "<li>job_offers - Job offer management</li>";
    echo "</ul>";
    
    echo "<h2>Updated Tables:</h2>";
    echo "<ul>";
    echo "<li>job_applications - Added workflow columns for complete tracking</li>";
    echo "</ul>";
    
    echo "<div class='success' style='margin-top: 20px;'>";
    echo "<strong>✓ Your database is now ready for the Applicant Tracking System!</strong>";
    echo "</div>";
    
    echo "<p><a href='../modals/applicant/index.php' style='color: #0ea5e9;'>Go to Applicant Portal</a></p>";
    echo "<p><a href='../modals/manager/index.php' style='color: #0ea5e9;'>Go to Manager Portal</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'><strong>Fatal Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</div></body></html>";
?>
