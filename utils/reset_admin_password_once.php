<?php
// One-time admin password reset
// Delete this file after successful run.

require_once __DIR__ . '/../database/config.php';

$targetEmail = 'admin@slatefreight.com';
$fallbackEmail = 'christianvizmonte222@gmail.com';
$newPassword = 'admin123!';
$newHash = password_hash($newPassword, PASSWORD_DEFAULT);

header('Content-Type: text/plain; charset=utf-8');

try {
    $user = fetchSingle(
        "SELECT id, company_email, personal_email, username, status
         FROM user_accounts
         WHERE company_email = ? OR personal_email = ? OR username = ?
            OR company_email = ? OR personal_email = ? OR username = ?
         LIMIT 1",
        [$targetEmail, $targetEmail, $targetEmail, $fallbackEmail, $fallbackEmail, $fallbackEmail]
    );

    if (!$user) {
        echo "ERROR: User not found for {$targetEmail}\n";
        exit;
    }

    executeQuery(
        "UPDATE user_accounts
         SET password_hash = ?, status = 'Active', updated_at = NOW()
         WHERE id = ?",
        [$newHash, $user['id']]
    );

    echo "SUCCESS: Password updated.\n";
    echo "User ID: {$user['id']}\n";
    echo "Login identifiers:\n";
    echo "- company_email: " . ($user['company_email'] ?? '') . "\n";
    echo "- personal_email: " . ($user['personal_email'] ?? '') . "\n";
    echo "- username: " . ($user['username'] ?? '') . "\n";
    echo "\nNew password: {$newPassword}\n";
    echo "\nDELETE this file now: /utils/reset_admin_password_once.php\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
