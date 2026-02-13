<?php
/**
 * Admin Account Seeder
 * Run this once to create the default admin account in user_accounts table.
 * Access via: http://localhost/HR1/database/seed_admin.php
 * DELETE THIS FILE after use for security.
 */

require_once 'config.php';

$admin_email = 'christianvizmonte222@gmail.com';
$admin_password = 'Admin123!';
$first_name = 'System';
$last_name = 'Administrator';

echo "<h2>HR1 Admin Account Seeder</h2>";

try {
    // Check if admin already exists
    $existing = fetchSingle(
        "SELECT ua.id, ua.personal_email, ua.company_email, r.role_name, r.role_type 
         FROM user_accounts ua 
         LEFT JOIN roles r ON ua.role_id = r.id 
         WHERE r.role_type = 'Admin' LIMIT 1"
    );

    if ($existing) {
        echo "<p style='color: orange;'>⚠️ Admin account already exists:</p>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $existing['id'] . "</li>";
        echo "<li><strong>Email:</strong> " . ($existing['company_email'] ?? $existing['personal_email']) . "</li>";
        echo "<li><strong>Role:</strong> " . $existing['role_name'] . " (" . $existing['role_type'] . ")</li>";
        echo "</ul>";
        echo "<p>If you forgot the password, a new admin will be created below.</p>";
    }

    // Get the Admin role_id
    $admin_role = fetchSingle("SELECT id FROM roles WHERE role_type = 'Admin' LIMIT 1");

    if (!$admin_role) {
        echo "<p style='color: red;'>❌ No Admin role found in roles table. Make sure you've run hr1_rbac_schema.sql first.</p>";
        exit;
    }

    $role_id = $admin_role['id'];

    // Check if this specific email already exists
    $email_exists = fetchSingle(
        "SELECT id FROM user_accounts WHERE personal_email = ? OR company_email = ?",
        [$admin_email, $admin_email]
    );

    if ($email_exists) {
        // Update existing account password
        $hashed = password_hash($admin_password, PASSWORD_DEFAULT);
        executeQuery(
            "UPDATE user_accounts SET password_hash = ?, role_id = ?, status = 'Active' WHERE id = ?",
            [$hashed, $role_id, $email_exists['id']]
        );
        echo "<p style='color: green;'>✅ Admin account updated (password reset).</p>";
    } else {
        // Create new admin account
        $hashed = password_hash($admin_password, PASSWORD_DEFAULT);
        executeQuery(
            "INSERT INTO user_accounts (first_name, last_name, personal_email, company_email, password_hash, role_id, status, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, 'Active', NOW())",
            [$first_name, $last_name, $admin_email, $admin_email, $hashed, $role_id]
        );
        echo "<p style='color: green;'>✅ Admin account created successfully!</p>";
    }

    echo "<hr>";
    echo "<h3>Admin Login Credentials:</h3>";
    echo "<ul>";
    echo "<li><strong>Email:</strong> " . htmlspecialchars($admin_email) . "</li>";
    echo "<li><strong>Password:</strong> " . htmlspecialchars($admin_password) . "</li>";
    echo "</ul>";
    echo "<p style='color: red;'><strong>⚠️ DELETE this file after use!</strong> (database/seed_admin.php)</p>";
    echo "<p><a href='../auth/login.php'>→ Go to Login</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
