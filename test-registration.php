<?php
// Test registration system
require_once 'database/config.php';

echo "<h2>Registration System Test</h2>";

try {
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Check if users table has the new columns
    $columns = fetchAll("SHOW COLUMNS FROM users");
    
    echo "<h3>Users Table Columns:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>" . $column['Field'] . " (" . $column['Type'] . ")</li>";
    }
    echo "</ul>";
    
    // Check if we have the required columns for registration
    $required_columns = ['full_name', 'email', 'company', 'phone'];
    $existing_columns = array_column($columns, 'Field');
    
    echo "<h3>Registration Requirements Check:</h3>";
    foreach ($required_columns as $col) {
        if (in_array($col, $existing_columns)) {
            echo "<p style='color: green;'>✅ Column '$col' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Column '$col' missing - run database/update_users_table.sql</p>";
        }
    }
    
    // Test user count
    $user_count = fetchSingle("SELECT COUNT(*) as count FROM users");
    echo "<p>Total users: " . $user_count['count'] . "</p>";
    
    echo "<hr>";
    echo "<p><a href='register.php'>Go to Registration Page</a></p>";
    echo "<p><a href='login.php'>Go to Login Page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>
