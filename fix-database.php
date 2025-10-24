<?php
/**
 * Database Fix Script
 * This script will add the missing columns to the users table
 */

require_once 'database/config.php';

echo "<h2>🔧 Database Fix Script</h2>";
echo "<p>This script will add the missing columns to the users table for registration.</p>";

try {
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Check current columns
    $columns = fetchAll("SHOW COLUMNS FROM users");
    echo "<h3>Current Users Table Columns:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>" . $column['Field'] . " (" . $column['Type'] . ")</li>";
    }
    echo "</ul>";
    
    // Add missing columns
    echo "<h3>Adding Missing Columns...</h3>";
    
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
    
    // Verify the fix
    echo "<h3>Verification:</h3>";
    $new_columns = fetchAll("SHOW COLUMNS FROM users");
    $required_columns = ['full_name', 'email', 'company', 'phone'];
    $existing_columns = array_column($new_columns, 'Field');
    
    $all_good = true;
    foreach ($required_columns as $col) {
        if (in_array($col, $existing_columns)) {
            echo "<p style='color: green;'>✅ Column '$col' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Column '$col' missing</p>";
            $all_good = false;
        }
    }
    
    if ($all_good) {
        echo "<h3 style='color: green;'>🎉 Database fix completed successfully!</h3>";
        echo "<p><a href='register.php'>Test Registration</a> | <a href='test-registration.php'>Test Registration System</a></p>";
    } else {
        echo "<h3 style='color: red;'>❌ Database fix incomplete. Please check the errors above.</h3>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>
