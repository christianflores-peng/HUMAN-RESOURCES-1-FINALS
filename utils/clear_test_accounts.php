<?php
/**
 * Clear Test Accounts Tool
 * Quickly delete test applicant accounts to test registration
 */
require_once 'database/config.php';

$message = '';
$accounts = [];

// Handle delete request
if (isset($_GET['delete_id'])) {
    try {
        $delete_id = (int)$_GET['delete_id'];
        
        // Delete from applicant_profiles first (if exists)
        executeQuery("DELETE FROM applicant_profiles WHERE user_id = ?", [$delete_id]);
        
        // Delete from user_accounts
        executeQuery("DELETE FROM user_accounts WHERE id = ?", [$delete_id]);
        
        $message = "✅ Account deleted successfully!";
    } catch (Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
    }
}

// Get all applicant accounts
try {
    $accounts = fetchAll("SELECT * FROM user_accounts WHERE role_id = 9 ORDER BY created_at DESC");
} catch (Exception $e) {
    $message = "❌ Error fetching accounts: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Clear Test Accounts - HR1</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2196f3; margin-bottom: 20px; }
        .message { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .success { background: #e8f5e9; border: 1px solid #4caf50; color: #2e7d32; }
        .error { background: #ffebee; border: 1px solid #f44336; color: #c62828; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #2196f3; color: white; }
        tr:hover { background: #f5f5f5; }
        .btn { padding: 8px 16px; background: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #d32f2f; }
        .btn-primary { background: #2196f3; }
        .btn-primary:hover { background: #1976d2; }
        .empty { text-align: center; padding: 40px; color: #999; }
        .actions { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗑️ Clear Test Applicant Accounts</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="actions">
            <a href="partials/register-portal.php" class="btn btn-primary">← Back to Registration</a>
        </div>
        
        <?php if (empty($accounts)): ?>
            <div class="empty">
                <h3>No applicant accounts found</h3>
                <p>You can proceed with registration!</p>
            </div>
        <?php else: ?>
            <h2>Applicant Accounts (Role ID: 9)</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($accounts as $account): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($account['id']); ?></td>
                        <td><?php echo htmlspecialchars($account['first_name'] . ' ' . $account['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($account['personal_email']); ?></td>
                        <td><?php echo htmlspecialchars($account['phone'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($account['status']); ?></td>
                        <td><?php echo htmlspecialchars($account['created_at']); ?></td>
                        <td>
                            <a href="?delete_id=<?php echo $account['id']; ?>" 
                               class="btn" 
                               onclick="return confirm('Delete this account?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
