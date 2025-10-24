<?php
/**
 * Database Implementation Script
 * Run this to set up your registration database
 */

require_once 'database/config.php';

echo "<h1>🛠️ Database Implementation Script</h1>";
echo "<p>This script will help you implement the registration database fixes.</p>";

try {
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Step 1: Check current structure
    echo "<h2>Step 1: Current Database Structure</h2>";
    $columns = fetchAll("SHOW COLUMNS FROM users");
    
    echo "<h3>Current Users Table Columns:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>" . $column['Field'] . " (" . $column['Type'] . ")</li>";
    }
    echo "</ul>";
    
    // Check if we have the required columns
    $required_columns = ['full_name', 'email', 'company', 'phone'];
    $existing_columns = array_column($columns, 'Field');
    
    echo "<h3>Registration Requirements Check:</h3>";
    $missing_columns = [];
    foreach ($required_columns as $col) {
        if (in_array($col, $existing_columns)) {
            echo "<p style='color: green;'>✅ Column '$col' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Column '$col' missing</p>";
            $missing_columns[] = $col;
        }
    }
    
    // Step 2: Fix missing columns if needed
    if (!empty($missing_columns)) {
        echo "<h2>Step 2: Adding Missing Columns</h2>";
        
        $alter_queries = [
            "ALTER TABLE `users` ADD COLUMN `full_name` varchar(255) DEFAULT NULL AFTER `role`",
            "ALTER TABLE `users` ADD COLUMN `email` varchar(255) DEFAULT NULL AFTER `full_name`",
            "ALTER TABLE `users` ADD COLUMN `company` varchar(255) DEFAULT NULL AFTER `email`",
            "ALTER TABLE `users` ADD COLUMN `phone` varchar(20) DEFAULT NULL AFTER `company`"
        ];
        
        foreach ($alter_queries as $query) {
            try {
                $pdo->exec($query);
                echo "<p style='color: green;'>✅ " . $query . "</p>";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                    echo "<p style='color: orange;'>⚠️ Column already exists: " . $query . "</p>";
                } else {
                    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
                }
            }
        }
        
        // Add unique constraint for email
        try {
            $pdo->exec("ALTER TABLE `users` ADD UNIQUE KEY `email` (`email`)");
            echo "<p style='color: green;'>✅ Added unique constraint for email</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<p style='color: orange;'>⚠️ Email unique constraint already exists</p>";
            } else {
                echo "<p style='color: red;'>❌ Error adding email constraint: " . $e->getMessage() . "</p>";
            }
        }
        
        // Update existing users with default values
        try {
            $pdo->exec("UPDATE `users` SET 
                `full_name` = CONCAT('User ', `id`),
                `email` = CONCAT(`username`, '@example.com'),
                `company` = 'SLATE Freight Management',
                `phone` = '555-0000'
                WHERE `full_name` IS NULL");
            echo "<p style='color: green;'>✅ Updated existing users with default values</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Error updating users: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<h2>Step 2: Database Already Fixed!</h2>";
        echo "<p style='color: green;'>✅ All required columns already exist. No changes needed.</p>";
    }
    
    // Step 3: Verify final structure
    echo "<h2>Step 3: Final Verification</h2>";
    $final_columns = fetchAll("SHOW COLUMNS FROM users");
    $final_column_names = array_column($final_columns, 'Field');
    
    $all_good = true;
    foreach ($required_columns as $col) {
        if (in_array($col, $final_column_names)) {
            echo "<p style='color: green;'>✅ Column '$col' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Column '$col' still missing</p>";
            $all_good = false;
        }
    }
    
    // Step 4: Test data
    echo "<h2>Step 4: Sample Data Check</h2>";
    $user_count = fetchSingle("SELECT COUNT(*) as count FROM users");
    echo "<p>Total users: " . $user_count['count'] . "</p>";
    
    if ($user_count['count'] > 0) {
        $sample_users = fetchAll("SELECT id, username, role, full_name, email, company, phone FROM users LIMIT 3");
        echo "<h3>Sample Users:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Full Name</th><th>Email</th><th>Company</th><th>Phone</th></tr>";
        foreach ($sample_users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . htmlspecialchars($user['full_name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($user['email'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($user['company'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($user['phone'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Step 5: Performance indexes
    echo "<h2>Step 5: Adding Performance Indexes</h2>";
    $index_queries = [
        "CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)",
        "CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)",
        "CREATE INDEX IF NOT EXISTS idx_users_role ON users(role)"
    ];
    
    foreach ($index_queries as $query) {
        try {
            $pdo->exec($query);
            echo "<p style='color: green;'>✅ Index created successfully</p>";
        } catch (PDOException $e) {
            echo "<p style='color: orange;'>⚠️ Index may already exist: " . $e->getMessage() . "</p>";
        }
    }
    
    // Final result
    if ($all_good) {
        echo "<h2 style='color: green;'>🎉 IMPLEMENTATION COMPLETE!</h2>";
        echo "<p style='color: green; font-size: 1.2em;'>Your registration database is now fully functional!</p>";
        
        echo "<h3>Next Steps:</h3>";
        echo "<ol>";
        echo "<li><a href='register.php' target='_blank'>Test Registration Form</a></li>";
        echo "<li><a href='login.php' target='_blank'>Test Login</a></li>";
        echo "<li><a href='test-registration.php' target='_blank'>Run Registration Tests</a></li>";
        echo "</ol>";
        
        echo "<h3>Test Credentials:</h3>";
        echo "<ul>";
        echo "<li><strong>Admin:</strong> admin / admin123</li>";
        echo "<li><strong>HR Manager:</strong> hr_manager / hr123</li>";
        echo "<li><strong>Recruiter:</strong> recruiter / recruit123</li>";
        echo "</ul>";
        
    } else {
        echo "<h2 style='color: red;'>❌ IMPLEMENTATION INCOMPLETE</h2>";
        echo "<p style='color: red;'>Some columns are still missing. Please check the errors above.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in <code>database/config.php</code></p>";
}

echo "<hr>";
echo "<p><strong>Implementation Guide:</strong> <a href='database/REGISTRATION_DATABASE_FIX.md' target='_blank'>View Complete Guide</a></p>";
echo "<p><strong>Database Schema:</strong> <a href='database/hr_management_schema_clean.sql' target='_blank'>Download Clean Schema</a></p>";
?>
