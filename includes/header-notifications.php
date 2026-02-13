<?php
// Fetch notifications for current user (using unique variable names to avoid conflicts)
$_header_user_id = $_SESSION['user_id'] ?? 0;
$_header_role = $_SESSION['role_type'] ?? '';
$_header_unread = [];
$_header_unread_count = 0;
$_header_recent = [];
$_header_is_applicant = ($_header_role === 'Applicant');

if ($_header_is_applicant) {
    // Applicants: use applicant_notifications table
    try {
        $_header_unread = fetchAll("
            SELECT * FROM applicant_notifications 
            WHERE user_id = ? AND is_read = 0 
            ORDER BY created_at DESC 
            LIMIT 5
        ", [$_header_user_id]);
        $_header_unread_count = count($_header_unread);
    } catch (Exception $e) {
        $_header_unread = [];
    }

    try {
        $_header_recent = fetchAll("
            SELECT * FROM applicant_notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 10
        ", [$_header_user_id]);
    } catch (Exception $e) {
        $_header_recent = [];
    }
} else {
    // Other roles: use audit_logs as activity feed
    try {
        if (in_array($_header_role, ['Admin', 'HR_Staff'])) {
            // Admin/HR see all recent system activity
            $_header_recent = fetchAll("
                SELECT id, user_email, action, module, detail, created_at 
                FROM audit_logs 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $_header_unread = fetchAll("
                SELECT id FROM audit_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
        } else {
            // Manager/Employee see only their own activity
            $_header_recent = fetchAll("
                SELECT id, user_email, action, module, detail, created_at 
                FROM audit_logs 
                WHERE user_id = ?
                ORDER BY created_at DESC 
                LIMIT 10
            ", [$_header_user_id]);
            $_header_unread = fetchAll("
                SELECT id FROM audit_logs 
                WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ", [$_header_user_id]);
        }
        $_header_unread_count = count($_header_unread);
    } catch (Exception $e) {
        $_header_recent = [];
        $_header_unread = [];
    }
}
?>

<!-- Notification Bell Styles -->
<style>
    .header-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .notification-bell {
        position: relative;
        cursor: pointer;
    }
    
    .notification-bell .bell-icon {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: rgba(100, 116, 139, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        border: 1px solid rgba(100, 116, 139, 0.3);
    }
    
    .notification-bell .bell-icon:hover {
        background: rgba(14, 165, 233, 0.2);
        border-color: rgba(14, 165, 233, 0.4);
    }
    
    .notification-bell .bell-icon i {
        width: 1.4rem;
        height: 1.4rem;
        color: #cbd5e1;
    }
    
    .notification-bell .badge {
        position: absolute;
        top: -2px;
        right: -2px;
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        font-size: 0.7rem;
        font-weight: 700;
        min-width: 18px;
        height: 18px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 4px;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
        animation: pulse-badge 2s infinite;
    }
    
    @keyframes pulse-badge {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    
    .notification-dropdown {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        width: 360px;
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border: 1px solid rgba(14, 165, 233, 0.2);
        border-radius: 12px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        z-index: 1000;
        overflow: hidden;
    }
    
    .notification-dropdown.active {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    .notification-dropdown-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid rgba(58, 69, 84, 0.5);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .notification-dropdown-header h4 {
        color: #e2e8f0;
        font-size: 0.95rem;
        font-weight: 600;
    }
    
    .notification-dropdown-header a {
        color: #0ea5e9;
        font-size: 0.8rem;
        text-decoration: none;
    }
    
    .notification-dropdown-header a:hover {
        text-decoration: underline;
    }
    
    .notification-list {
        max-height: 350px;
        overflow-y: auto;
    }
    
    .notification-item {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid rgba(58, 69, 84, 0.3);
        display: flex;
        gap: 0.75rem;
        transition: background 0.2s;
        cursor: pointer;
    }
    
    .notification-item:hover {
        background: rgba(14, 165, 233, 0.05);
    }
    
    .notification-item.unread {
        background: rgba(14, 165, 233, 0.08);
        border-left: 3px solid #0ea5e9;
    }
    
    .notification-item:last-child {
        border-bottom: none;
    }
    
    .notification-icon {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .notification-icon.status { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
    .notification-icon.interview { background: rgba(139, 92, 246, 0.2); color: #8b5cf6; }
    .notification-icon.offer { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
    .notification-icon.rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
    
    .notification-icon i {
        width: 1.2rem;
        height: 1.2rem;
    }
    
    .notification-content {
        flex: 1;
        min-width: 0;
    }
    
    .notification-content p {
        color: #e2e8f0;
        font-size: 0.85rem;
        line-height: 1.4;
        margin-bottom: 0.25rem;
    }
    
    .notification-content .time {
        color: #64748b;
        font-size: 0.75rem;
    }
    
    .notification-empty {
        padding: 2rem;
        text-align: center;
        color: #64748b;
    }
    
    .notification-empty i {
        width: 2.5rem;
        height: 2.5rem;
        opacity: 0.5;
        margin-bottom: 0.5rem;
        display: block;
    }
    
    .notification-dropdown-footer {
        padding: 0.75rem 1.25rem;
        border-top: 1px solid rgba(58, 69, 84, 0.5);
        text-align: center;
    }
    
    .notification-dropdown-footer a {
        color: #0ea5e9;
        font-size: 0.85rem;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .notification-dropdown-footer a:hover {
        text-decoration: underline;
    }
</style>

<!-- Notification Bell HTML -->
<div class="notification-bell" id="notificationBell">
    <div class="bell-icon">
        <i data-lucide="bell"></i>
    </div>
    <?php if ($_header_unread_count > 0): ?>
    <span class="badge"><?php echo $_header_unread_count > 9 ? '9+' : $_header_unread_count; ?></span>
    <?php endif; ?>
    
    <div class="notification-dropdown" id="notificationDropdown">
        <div class="notification-dropdown-header">
            <h4>Notifications</h4>
            <?php if ($_header_unread_count > 0): ?>
            <a href="notifications.php?mark_all_read=1">Mark all read</a>
            <?php endif; ?>
        </div>
        
        <div class="notification-list">
            <?php if (empty($_header_recent)): ?>
            <div class="notification-empty">
                <i data-lucide="bell-off"></i>
                <p>No notifications yet</p>
            </div>
            <?php else: ?>
            <?php foreach ($_header_recent as $notif): 
                // Get message text - applicant_notifications uses 'message', audit_logs uses 'detail'
                $_notif_msg = $notif['message'] ?? $notif['detail'] ?? ($notif['action'] ?? 'Activity') . ' on ' . ($notif['module'] ?? 'system');
                $_notif_is_read = isset($notif['is_read']) ? $notif['is_read'] : 1;
                
                $icon_class = 'status';
                $icon_name = 'info';
                $_msg_lower = strtolower($_notif_msg);
                $_action_lower = strtolower($notif['action'] ?? '');
                
                if (strpos($_msg_lower, 'interview') !== false || strpos($_msg_lower, 'schedule') !== false) {
                    $icon_class = 'interview'; $icon_name = 'calendar';
                } elseif (strpos($_msg_lower, 'offer') !== false || $_action_lower === 'approve') {
                    $icon_class = 'offer'; $icon_name = 'gift';
                } elseif (strpos($_msg_lower, 'rejected') !== false || $_action_lower === 'reject') {
                    $icon_class = 'rejected'; $icon_name = 'x-circle';
                } elseif (strpos($_msg_lower, 'screening') !== false || strpos($_msg_lower, 'review') !== false) {
                    $icon_class = 'status'; $icon_name = 'clipboard-check';
                } elseif ($_action_lower === 'login' || $_action_lower === 'logout') {
                    $icon_class = 'status'; $icon_name = 'log-in';
                } elseif ($_action_lower === 'create' || $_action_lower === 'hire') {
                    $icon_class = 'offer'; $icon_name = 'plus-circle';
                } elseif ($_action_lower === 'edit') {
                    $icon_class = 'interview'; $icon_name = 'edit-3';
                } elseif ($_action_lower === 'delete') {
                    $icon_class = 'rejected'; $icon_name = 'trash-2';
                }
                
                $time_ago = time() - strtotime($notif['created_at']);
                if ($time_ago < 60) $time_str = 'Just now';
                elseif ($time_ago < 3600) $time_str = floor($time_ago / 60) . 'm ago';
                elseif ($time_ago < 86400) $time_str = floor($time_ago / 3600) . 'h ago';
                else $time_str = date('M d', strtotime($notif['created_at']));
            ?>
            <div class="notification-item <?php echo $_notif_is_read ? '' : 'unread'; ?>">
                <div class="notification-icon <?php echo $icon_class; ?>">
                    <i data-lucide="<?php echo $icon_name; ?>"></i>
                </div>
                <div class="notification-content">
                    <p><?php echo htmlspecialchars($_notif_msg); ?></p>
                    <span class="time"><?php echo $time_str; ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($_header_is_applicant): ?>
        <div class="notification-dropdown-footer">
            <a href="notifications.php"><i data-lucide="eye" style="width: 1rem; height: 1rem;"></i>View All Notifications</a>
        </div>
        <?php else: ?>
        <div class="notification-dropdown-footer">
            <a href="dashboard.php"><i data-lucide="eye" style="width: 1rem; height: 1rem;"></i>View Dashboard</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Toggle notification dropdown
    document.getElementById('notificationBell').addEventListener('click', function(e) {
        e.stopPropagation();
        const dropdown = document.getElementById('notificationDropdown');
        dropdown.classList.toggle('active');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('notificationDropdown');
        const bell = document.getElementById('notificationBell');
        if (!bell.contains(e.target)) {
            dropdown.classList.remove('active');
        }
    });
    
    // Prevent dropdown from closing when clicking inside
    document.getElementById('notificationDropdown').addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
