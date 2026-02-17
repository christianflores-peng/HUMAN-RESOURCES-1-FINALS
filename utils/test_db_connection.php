<?php
/**
 * Database Connection Test Script
 * 
 * Upload this file to your sub-domain to test if database connection works
 * after updating config.php
 * 
 * Access via: https://yoursubdomain.com/utils/test_db_connection.php
 */

require_once __DIR__ . '/../database/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .test-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #ccc;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
            color: #0c5460;
        }
        .icon {
            font-size: 20px;
            margin-right: 10px;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .details {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Database Connection Test</h1>
        
        <?php
        $all_passed = true;
        
        // Test 1: Check if constants are defined
        echo '<div class="test-item ' . (defined('DB_HOST') ? 'success' : 'error') . '">';
        echo '<span class="icon">' . (defined('DB_HOST') ? '✓' : '✗') . '</span>';
        echo '<strong>Configuration Constants:</strong> ';
        if (defined('DB_HOST')) {
            echo 'All database constants are defined';
        } else {
            echo 'Database constants are missing!';
            $all_passed = false;
        }
        echo '</div>';
        
        // Test 2: Try to connect
        try {
            $pdo = getDBConnection();
            echo '<div class="test-item success">';
            echo '<span class="icon">✓</span>';
            echo '<strong>Database Connection:</strong> Successfully connected to database';
            echo '</div>';
        } catch (Exception $e) {
            echo '<div class="test-item error">';
            echo '<span class="icon">✗</span>';
            echo '<strong>Database Connection:</strong> Failed to connect<br>';
            echo '<code>' . htmlspecialchars($e->getMessage()) . '</code>';
            echo '</div>';
            $all_passed = false;
            $pdo = null;
        }
        
        // Test 3: Check database exists
        if ($pdo) {
            try {
                $stmt = $pdo->query("SELECT DATABASE() as db_name");
                $result = $stmt->fetch();
                echo '<div class="test-item success">';
                echo '<span class="icon">✓</span>';
                echo '<strong>Database Selected:</strong> ' . htmlspecialchars($result['db_name']);
                echo '</div>';
            } catch (Exception $e) {
                echo '<div class="test-item error">';
                echo '<span class="icon">✗</span>';
                echo '<strong>Database Selection:</strong> Failed<br>';
                echo '<code>' . htmlspecialchars($e->getMessage()) . '</code>';
                echo '</div>';
                $all_passed = false;
            }
        }
        
        // Test 4: Check critical tables
        if ($pdo) {
            $critical_tables = ['user_accounts', 'roles', 'departments', 'job_postings'];
            $missing_tables = [];
            
            foreach ($critical_tables as $table) {
                try {
                    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                    if ($stmt->rowCount() == 0) {
                        $missing_tables[] = $table;
                    }
                } catch (Exception $e) {
                    $missing_tables[] = $table;
                }
            }
            
            if (empty($missing_tables)) {
                echo '<div class="test-item success">';
                echo '<span class="icon">✓</span>';
                echo '<strong>Database Tables:</strong> All critical tables exist';
                echo '</div>';
            } else {
                echo '<div class="test-item error">';
                echo '<span class="icon">✗</span>';
                echo '<strong>Database Tables:</strong> Missing tables: ' . implode(', ', $missing_tables);
                echo '</div>';
                $all_passed = false;
            }
        }
        
        // Test 5: Test a simple query
        if ($pdo) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_accounts");
                $result = $stmt->fetch();
                echo '<div class="test-item success">';
                echo '<span class="icon">✓</span>';
                echo '<strong>Query Test:</strong> Successfully queried database (' . $result['count'] . ' users found)';
                echo '</div>';
            } catch (Exception $e) {
                echo '<div class="test-item error">';
                echo '<span class="icon">✗</span>';
                echo '<strong>Query Test:</strong> Failed to execute query<br>';
                echo '<code>' . htmlspecialchars($e->getMessage()) . '</code>';
                echo '</div>';
                $all_passed = false;
            }
        }
        
        // Summary
        echo '<div class="details">';
        echo '<h3>Configuration Details</h3>';
        echo '<p><strong>Host:</strong> <code>' . DB_HOST . '</code></p>';
        echo '<p><strong>Database:</strong> <code>' . DB_NAME . '</code></p>';
        echo '<p><strong>Username:</strong> <code>' . DB_USER . '</code></p>';
        echo '<p><strong>Password:</strong> <code>' . str_repeat('*', strlen(DB_PASS)) . '</code> (' . strlen(DB_PASS) . ' characters)</p>';
        echo '<p><strong>Charset:</strong> <code>' . DB_CHARSET . '</code></p>';
        echo '</div>';
        
        // Final result
        if ($all_passed) {
            echo '<div class="test-item success" style="margin-top: 20px; font-size: 18px;">';
            echo '<span class="icon">🎉</span>';
            echo '<strong>ALL TESTS PASSED!</strong> Database connection is working correctly.';
            echo '</div>';
        } else {
            echo '<div class="test-item error" style="margin-top: 20px; font-size: 18px;">';
            echo '<span class="icon">⚠️</span>';
            echo '<strong>SOME TESTS FAILED!</strong> Please check the errors above and fix the configuration.';
            echo '</div>';
        }
        ?>
        
        <div class="test-item info" style="margin-top: 20px;">
            <span class="icon">ℹ️</span>
            <strong>Note:</strong> Delete this file after testing for security purposes.
        </div>
    </div>
</body>
</html>
