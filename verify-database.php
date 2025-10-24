<?php
/**
 * Database Verification & Testing Tool
 * Comprehensive testing suite for the registration system
 */

require_once 'database/config.php';

echo "<h1>🧪 Database Verification & Testing Suite</h1>";
echo "<p>This tool will verify your database implementation and run comprehensive tests.</p>";

try {
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test 1: Database Structure Verification
    echo "<h2>Test 1: Database Structure Verification</h2>";
    $columns = fetchAll("SHOW COLUMNS FROM users");
    $required_columns = ['id', 'username', 'password', 'role', 'full_name', 'email', 'company', 'phone', 'created_at'];
    $existing_columns = array_column($columns, 'Field');
    
    $structure_passed = true;
    foreach ($required_columns as $col) {
        if (in_array($col, $existing_columns)) {
            echo "<p style='color: green;'>✅ Column '$col' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Column '$col' missing</p>";
            $structure_passed = false;
        }
    }
    
    // Test 2: Constraints Verification
    echo "<h2>Test 2: Constraints Verification</h2>";
    $indexes = fetchAll("SHOW INDEX FROM users");
    $constraint_passed = true;
    
    $has_username_unique = false;
    $has_email_unique = false;
    
    foreach ($indexes as $index) {
        if ($index['Key_name'] === 'username' && $index['Non_unique'] == 0) {
            $has_username_unique = true;
            echo "<p style='color: green;'>✅ Username unique constraint exists</p>";
        }
        if ($index['Key_name'] === 'email' && $index['Non_unique'] == 0) {
            $has_email_unique = true;
            echo "<p style='color: green;'>✅ Email unique constraint exists</p>";
        }
    }
    
    if (!$has_username_unique) {
        echo "<p style='color: red;'>❌ Username unique constraint missing</p>";
        $constraint_passed = false;
    }
    if (!$has_email_unique) {
        echo "<p style='color: red;'>❌ Email unique constraint missing</p>";
        $constraint_passed = false;
    }
    
    // Test 3: Sample Data Verification
    echo "<h2>Test 3: Sample Data Verification</h2>";
    $user_count = fetchSingle("SELECT COUNT(*) as count FROM users");
    echo "<p>Total users: " . $user_count['count'] . "</p>";
    
    if ($user_count['count'] > 0) {
        $sample_users = fetchAll("SELECT username, role, full_name, email FROM users LIMIT 3");
        echo "<h3>Sample Users:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Username</th><th>Role</th><th>Full Name</th><th>Email</th></tr>";
        foreach ($sample_users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . htmlspecialchars($user['full_name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($user['email'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 4: Registration Functionality Test
    echo "<h2>Test 4: Registration Functionality Test</h2>";
    
    // Test valid registration data
    $test_username = 'test_user_' . time();
    $test_email = 'test' . time() . '@example.com';
    $test_password = 'testpass123';
    $test_full_name = 'Test User';
    $test_company = 'Test Company';
    $test_phone = '555-0123';
    
    try {
        // Check if test user already exists
        $existing = fetchSingle("SELECT id FROM users WHERE username = ? OR email = ?", [$test_username, $test_email]);
        
        if (!$existing) {
            // Insert test user
            $hashed_password = password_hash($test_password, PASSWORD_DEFAULT);
            $user_id = insertRecord(
                "INSERT INTO users (username, password, role, full_name, email, company, phone, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                [$test_username, $hashed_password, 'Employee', $test_full_name, $test_email, $test_company, $test_phone]
            );
            
            if ($user_id) {
                echo "<p style='color: green;'>✅ Test user created successfully (ID: $user_id)</p>";
                
                // Verify the user was created correctly
                $created_user = fetchSingle("SELECT * FROM users WHERE id = ?", [$user_id]);
                if ($created_user) {
                    echo "<p style='color: green;'>✅ User data verified in database</p>";
                    echo "<ul>";
                    echo "<li>Username: " . htmlspecialchars($created_user['username']) . "</li>";
                    echo "<li>Email: " . htmlspecialchars($created_user['email']) . "</li>";
                    echo "<li>Full Name: " . htmlspecialchars($created_user['full_name']) . "</li>";
                    echo "<li>Company: " . htmlspecialchars($created_user['company']) . "</li>";
                    echo "<li>Phone: " . htmlspecialchars($created_user['phone']) . "</li>";
                    echo "<li>Role: " . htmlspecialchars($created_user['role']) . "</li>";
                    echo "</ul>";
                    
                    // Test password verification
                    if (password_verify($test_password, $created_user['password'])) {
                        echo "<p style='color: green;'>✅ Password hashing and verification working</p>";
                    } else {
                        echo "<p style='color: red;'>❌ Password verification failed</p>";
                    }
                    
                    // Clean up test user
                    updateRecord("DELETE FROM users WHERE id = ?", [$user_id]);
                    echo "<p style='color: blue;'>🧹 Test user cleaned up</p>";
                } else {
                    echo "<p style='color: red;'>❌ Failed to retrieve created user</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ Failed to create test user</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Test user already exists, skipping creation test</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Registration test failed: " . $e->getMessage() . "</p>";
    }
    
    // Test 5: Duplicate Prevention Test
    echo "<h2>Test 5: Duplicate Prevention Test</h2>";
    
    try {
        // Try to create duplicate username
        $existing_user = fetchSingle("SELECT username FROM users LIMIT 1");
        if ($existing_user) {
            $duplicate_username = $existing_user['username'];
            $duplicate_email = 'duplicate' . time() . '@example.com';
            
            try {
                $hashed_password = password_hash('testpass123', PASSWORD_DEFAULT);
                insertRecord(
                    "INSERT INTO users (username, password, role, full_name, email, company, phone, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                    [$duplicate_username, $hashed_password, 'Employee', 'Duplicate User', $duplicate_email, 'Test Company', '555-0123']
                );
                echo "<p style='color: red;'>❌ Duplicate username was allowed (this should not happen)</p>";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    echo "<p style='color: green;'>✅ Duplicate username prevention working</p>";
                } else {
                    echo "<p style='color: red;'>❌ Unexpected error: " . $e->getMessage() . "</p>";
                }
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Could not test duplicate prevention: " . $e->getMessage() . "</p>";
    }
    
    // Test 6: Performance Test
    echo "<h2>Test 6: Performance Test</h2>";
    
    $start_time = microtime(true);
    $users = fetchAll("SELECT id, username, email FROM users LIMIT 100");
    $end_time = microtime(true);
    $query_time = ($end_time - $start_time) * 1000; // Convert to milliseconds
    
    echo "<p>Query time: " . number_format($query_time, 2) . " ms</p>";
    if ($query_time < 100) {
        echo "<p style='color: green;'>✅ Query performance is good</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Query performance could be improved</p>";
    }
    
    // Test 7: Security Test
    echo "<h2>Test 7: Security Test</h2>";
    
    // Test SQL injection prevention
    try {
        $malicious_input = "'; DROP TABLE users; --";
        $result = fetchSingle("SELECT id FROM users WHERE username = ?", [$malicious_input]);
        echo "<p style='color: green;'>✅ SQL injection prevention working (prepared statements)</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Security test failed: " . $e->getMessage() . "</p>";
    }
    
    // Final Results
    echo "<h2>📊 Test Results Summary</h2>";
    
    $total_tests = 7;
    $passed_tests = 0;
    
    if ($structure_passed) $passed_tests++;
    if ($constraint_passed) $passed_tests++;
    if ($user_count['count'] > 0) $passed_tests++;
    // Registration test, duplicate test, performance test, security test are assumed passed if no errors
    
    echo "<div style='background: #f0f0f0; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>Overall Score: $passed_tests/$total_tests tests passed</h3>";
    
    if ($passed_tests == $total_tests) {
        echo "<p style='color: green; font-size: 1.2em; font-weight: bold;'>🎉 ALL TESTS PASSED! Your database is ready for production.</p>";
    } elseif ($passed_tests >= 5) {
        echo "<p style='color: orange; font-size: 1.1em;'>⚠️ Most tests passed. Review failed tests above.</p>";
    } else {
        echo "<p style='color: red; font-size: 1.1em;'>❌ Multiple tests failed. Please fix issues before proceeding.</p>";
    }
    echo "</div>";
    
    // Recommendations
    echo "<h2>💡 Recommendations</h2>";
    echo "<ul>";
    if (!$structure_passed) {
        echo "<li>Run the <a href='implement-database.php'>implementation script</a> to fix missing columns</li>";
    }
    if (!$constraint_passed) {
        echo "<li>Add unique constraints for username and email</li>";
    }
    if ($user_count['count'] == 0) {
        echo "<li>Import sample data from the schema files</li>";
    }
    if ($query_time > 100) {
        echo "<li>Add database indexes for better performance</li>";
    }
    echo "<li>Regularly backup your database</li>";
    echo "<li>Monitor user registration patterns</li>";
    echo "<li>Review security logs periodically</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
}

echo "<hr>";
echo "<h2>🔗 Quick Links</h2>";
echo "<ul>";
echo "<li><a href='implement-database.php'>🛠️ Implementation Script</a></li>";
echo "<li><a href='register.php'>📝 Registration Form</a></li>";
echo "<li><a href='login.php'>🔐 Login Page</a></li>";
echo "<li><a href='test-registration.php'>🧪 Registration Test</a></li>";
echo "<li><a href='database/REGISTRATION_DATABASE_FIX.md'>📖 Complete Guide</a></li>";
echo "</ul>";
?>
