<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success {
            color: #16a085;
            background: #d5f4e6;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
            border-left: 4px solid #16a085;
        }
        .error {
            color: #e74c3c;
            background: #fadbd8;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
            border-left: 4px solid #e74c3c;
        }
        .info {
            color: #2980b9;
            background: #ebf3fd;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
            border-left: 4px solid #2980b9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>HR Management System - Database Connection Test</h1>
        
        <?php
        require_once 'config.php';
        
        echo '<div class="info">Testing database connection to: <strong>' . DB_NAME . '</strong> on <strong>' . DB_HOST . '</strong></div>';
        
        try {
            // Test basic connection
            $pdo = getDBConnection();
            echo '<div class="success">✅ Database connection successful!</div>';
            
            // Test tables existence
            echo '<h2>Database Schema Verification</h2>';
            
            $tables = [
                'users' => 'User authentication and roles',
                'departments' => 'Company departments',
                'employees' => 'Employee information',
                'job_postings' => 'Job posting management',
                'job_applications' => 'Application tracking',
                'interviews' => 'Interview scheduling',
                'onboarding_tasks' => 'Employee onboarding',
                'performance_goals' => 'Goal management',
                'performance_reviews' => 'Performance reviews',
                'recognition_awards' => 'Employee recognition',
                'rewards_catalog' => 'Rewards system',
                'reward_redemptions' => 'Reward redemptions'
            ];
            
            echo '<table>';
            echo '<tr><th>Table Name</th><th>Description</th><th>Status</th><th>Record Count</th></tr>';
            
            foreach ($tables as $table => $description) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                    $result = $stmt->fetch();
                    $count = $result['count'];
                    echo "<tr><td><strong>$table</strong></td><td>$description</td><td><span style='color: green;'>✅ Exists</span></td><td>$count records</td></tr>";
                } catch (PDOException $e) {
                    echo "<tr><td><strong>$table</strong></td><td>$description</td><td><span style='color: red;'>❌ Missing</span></td><td>-</td></tr>";
                }
            }
            echo '</table>';
            
            // Test sample data
            echo '<h2>Sample Data Verification</h2>';
            
            try {
                $departments = fetchAll("SELECT * FROM departments LIMIT 5");
                if (!empty($departments)) {
                    echo '<div class="success">✅ Departments table has sample data</div>';
                    echo '<table>';
                    echo '<tr><th>ID</th><th>Department Name</th><th>Description</th></tr>';
                    foreach ($departments as $dept) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($dept['id']) . '</td>';
                        echo '<td>' . htmlspecialchars($dept['name']) . '</td>';
                        echo '<td>' . htmlspecialchars($dept['description']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<div class="error">❌ No sample departments found</div>';
                }
            } catch (Exception $e) {
                echo '<div class="error">❌ Error checking departments: ' . $e->getMessage() . '</div>';
            }
            
            try {
                $rewards = fetchAll("SELECT * FROM rewards_catalog LIMIT 5");
                if (!empty($rewards)) {
                    echo '<div class="success">✅ Rewards catalog has sample data</div>';
                    echo '<table>';
                    echo '<tr><th>ID</th><th>Reward Name</th><th>Points Required</th><th>Category</th></tr>';
                    foreach ($rewards as $reward) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($reward['id']) . '</td>';
                        echo '<td>' . htmlspecialchars($reward['name']) . '</td>';
                        echo '<td>' . htmlspecialchars($reward['points_required']) . '</td>';
                        echo '<td>' . htmlspecialchars($reward['category']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<div class="error">❌ No sample rewards found</div>';
                }
            } catch (Exception $e) {
                echo '<div class="error">❌ Error checking rewards: ' . $e->getMessage() . '</div>';
            }
            
            // Test users table
            try {
                $users = fetchAll("SELECT id, username, role, created_at FROM users LIMIT 5");
                if (!empty($users)) {
                    echo '<h2>Existing Users</h2>';
                    echo '<table>';
                    echo '<tr><th>ID</th><th>Username</th><th>Role</th><th>Created</th></tr>';
                    foreach ($users as $user) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($user['id']) . '</td>';
                        echo '<td>' . htmlspecialchars($user['username']) . '</td>';
                        echo '<td>' . htmlspecialchars($user['role']) . '</td>';
                        echo '<td>' . htmlspecialchars($user['created_at']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
            } catch (Exception $e) {
                echo '<div class="error">❌ Error checking users: ' . $e->getMessage() . '</div>';
            }
            
            echo '<div class="success">🎉 Database setup is complete and ready for use!</div>';
            
        } catch (PDOException $e) {
            echo '<div class="error">❌ Database connection failed: ' . $e->getMessage() . '</div>';
            
            echo '<div class="info">';
            echo '<strong>Troubleshooting Steps:</strong><br>';
            echo '1. Make sure MySQL/MariaDB is running in Laragon<br>';
            echo '2. Verify database name: <code>' . DB_NAME . '</code><br>';
            echo '3. Check if the database exists in phpMyAdmin<br>';
            echo '4. Import the hr_management_schema.sql file<br>';
            echo '5. Update credentials in database/config.php if needed';
            echo '</div>';
        }
        ?>
        
        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #ddd;">
            <h2>Next Steps</h2>
            <a href="../pages/job_posting.php" class="btn">🚀 Go to Job Posting System</a>
            <a href="../index.html" class="btn">🏠 Go to Main Application</a>
            <a href="http://localhost/phpmyadmin" class="btn" target="_blank">🗄️ Open phpMyAdmin</a>
        </div>
    </div>
</body>
</html>
