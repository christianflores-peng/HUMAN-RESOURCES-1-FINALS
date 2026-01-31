<?php
require_once '../../includes/session_helper.php';
startSecureSession();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Applicant') {
    header('Location: ../../index.php');
    exit();
}

require_once '../../database/config.php';

$user_id = $_SESSION['user_id'];

try {
    if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
        executeQuery("UPDATE applicant_notifications SET is_read = 1 WHERE id = ? AND user_id = ?", [$_GET['mark_read'], $user_id]);
        header('Location: notifications.php');
        exit();
    }

    if (isset($_GET['mark_all_read'])) {
        executeQuery("UPDATE applicant_notifications SET is_read = 1 WHERE user_id = ?", [$user_id]);
        header('Location: notifications.php');
        exit();
    }

    $notifications = fetchAll("SELECT * FROM applicant_notifications WHERE user_id = ? ORDER BY created_at DESC", [$user_id]);
} catch (Exception $e) {
    $notifications = [];
}
$unread_count = count(array_filter($notifications, fn($n) => !$n['is_read']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - HR1</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #0a1929 0%, #1a2942 100%); min-height: 100vh; color: #f8fafc; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: rgba(15, 23, 42, 0.95); border-right: 1px solid rgba(58, 69, 84, 0.5); padding: 1.5rem 0; position: fixed; height: 100vh; overflow-y: auto; }
        .logo-section { padding: 0 1.5rem 1.5rem; border-bottom: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1.5rem; }
        .logo-section img { width: 60px; margin-bottom: 0.5rem; }
        .logo-section h2 { font-size: 1.1rem; color: #0ea5e9; }
        .logo-section p { font-size: 0.75rem; color: #94a3b8; }
        .nav-menu { list-style: none; }
        .nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; color: #cbd5e1; text-decoration: none; transition: all 0.3s; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(14, 165, 233, 0.1); color: #0ea5e9; border-left: 3px solid #0ea5e9; }
        .main-content { flex: 1; margin-left: 260px; padding: 2rem; }
        .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5rem; color: #e2e8f0; display: flex; align-items: center; gap: 0.5rem; }
        .unread-badge { background: #ef4444; color: white; padding: 0.25rem 0.6rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .btn { padding: 0.65rem 1.25rem; border: none; border-radius: 6px; font-size: 0.9rem; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-secondary { background: rgba(100, 116, 139, 0.3); color: #cbd5e1; }
        .btn-secondary:hover { background: rgba(100, 116, 139, 0.5); }
        .notifications-list { display: grid; gap: 1rem; }
        .notification-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.25rem; border: 1px solid rgba(58, 69, 84, 0.5); transition: all 0.3s; display: flex; gap: 1rem; }
        .notification-card.unread { background: rgba(14, 165, 233, 0.05); border-color: rgba(14, 165, 233, 0.3); }
        .notification-icon { width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .notification-icon.status_change { background: rgba(99, 102, 241, 0.2); color: #6366f1; }
        .notification-icon.interview { background: rgba(139, 92, 246, 0.2); color: #8b5cf6; }
        .notification-icon.road_test { background: rgba(236, 72, 153, 0.2); color: #ec4899; }
        .notification-icon.offer { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .notification-icon.hired { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .notification-icon.rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .notification-content { flex: 1; }
        .notification-title { font-size: 1rem; color: #e2e8f0; font-weight: 600; margin-bottom: 0.35rem; }
        .notification-message { color: #94a3b8; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .notification-time { color: #64748b; font-size: 0.8rem; }
        .notification-actions { display: flex; gap: 0.5rem; align-items: center; }
        .mark-read-btn { padding: 0.4rem 0.8rem; background: rgba(14, 165, 233, 0.2); color: #0ea5e9; border: none; border-radius: 6px; font-size: 0.8rem; cursor: pointer; text-decoration: none; }
        .mark-read-btn:hover { background: rgba(14, 165, 233, 0.3); }
        .empty-state { text-align: center; padding: 4rem 2rem; color: #94a3b8; }
        .empty-state .material-symbols-outlined { font-size: 5rem; color: #475569; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <?php $logo_path = '../../assets/images/slate.png'; include '../../includes/loading-screen.php'; ?>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-section">
                <img src="../../assets/images/slate.png" alt="SLATE Logo">
                <h2>Applicant Portal</h2>
                <p><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></p>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><span class="material-symbols-outlined">dashboard</span>Dashboard</a></li>
                <li class="nav-item"><a href="applications.php" class="nav-link"><span class="material-symbols-outlined">work</span>My Applications</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link"><span class="material-symbols-outlined">person</span>My Profile</a></li>
                <li class="nav-item"><a href="notifications.php" class="nav-link active"><span class="material-symbols-outlined">notifications</span>Notifications</a></li>
                <li class="nav-item"><a href="interview-schedule.php" class="nav-link"><span class="material-symbols-outlined">event</span>Interview Schedule</a></li>
                <li class="nav-item"><a href="road-test-info.php" class="nav-link"><span class="material-symbols-outlined">directions_car</span>Road Test Info</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link"><span class="material-symbols-outlined">logout</span>Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Notifications<?php if ($unread_count > 0): ?><span class="unread-badge"><?php echo $unread_count; ?> New</span><?php endif; ?></h1>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                    <?php if ($unread_count > 0): ?><a href="?mark_all_read=1" class="btn btn-secondary"><span class="material-symbols-outlined">done_all</span>Mark All as Read</a><?php endif; ?>
                </div>
            </div>

            <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <span class="material-symbols-outlined">notifications_off</span>
                <h3>No Notifications</h3>
                <p>You don't have any notifications yet.</p>
            </div>
            <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notif): ?>
                <div class="notification-card <?php echo !$notif['is_read'] ? 'unread' : ''; ?>">
                    <div class="notification-icon <?php echo $notif['type']; ?>">
                        <span class="material-symbols-outlined"><?php $icons = ['status_change' => 'sync', 'interview' => 'event', 'road_test' => 'directions_car', 'offer' => 'mail', 'hired' => 'celebration', 'rejected' => 'cancel']; echo $icons[$notif['type']] ?? 'notifications'; ?></span>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                        <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                        <div class="notification-time"><?php $time_diff = time() - strtotime($notif['created_at']); if ($time_diff < 60) { echo 'Just now'; } elseif ($time_diff < 3600) { echo floor($time_diff / 60) . ' minutes ago'; } elseif ($time_diff < 86400) { echo floor($time_diff / 3600) . ' hours ago'; } else { echo date('M d, Y h:i A', strtotime($notif['created_at'])); } ?></div>
                    </div>
                    <div class="notification-actions">
                        <?php if (!$notif['is_read']): ?><a href="?mark_read=<?php echo $notif['id']; ?>" class="mark-read-btn">Mark as Read</a><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
    <?php include '../../includes/logout-modal.php'; ?>
</body>
</html>
