<?php
require_once '../../includes/session_helper.php';
require_once '../../includes/spa_helper.php';
startSecureSession();
$is_ajax = is_spa_ajax();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Admin') {
    header('Location: ../../index.php');
    exit();
}

if (!$is_ajax) {
    header('Location: index.php?page=system-settings');
    exit();
}

require_once '../../database/config.php';

$success_message = '';
$error_message = '';

// Ensure system_settings table exists
try {
    executeQuery("CREATE TABLE IF NOT EXISTS `system_settings` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `setting_key` VARCHAR(100) NOT NULL UNIQUE,
        `setting_value` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    // Table may already exist, continue
}

// Fetch current settings
try {
    $settings = fetchAll("SELECT * FROM system_settings ORDER BY setting_key");
    $settings_map = [];
    foreach ($settings as $s) {
        $settings_map[$s['setting_key']] = $s['setting_value'];
    }
} catch (Exception $e) {
    $settings = [];
    $settings_map = [];
}

// Default values if settings table doesn't exist or is empty
$defaults = [
    'company_name' => 'SLATE Freight Management',
    'company_email' => 'admin@slate.com',
    'company_phone' => '555-0001',
    'company_address' => '',
    'session_timeout' => '30',
    'max_login_attempts' => '5',
    'otp_expiry_minutes' => '1',
    'password_min_length' => '8',
    'maintenance_mode' => '0',
    'allow_registration' => '1',
    'smtp_host' => '',
    'smtp_port' => '587',
    'smtp_username' => '',
    'smtp_encryption' => 'tls',
    'timezone' => 'Asia/Manila',
    'date_format' => 'M d, Y',
    'records_per_page' => '25',
];

foreach ($defaults as $key => $val) {
    if (!isset($settings_map[$key])) {
        $settings_map[$key] = $val;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'] ?? '';
    
    try {
        $updates = [];
        
        if ($section === 'general') {
            $updates = [
                'company_name' => trim($_POST['company_name'] ?? ''),
                'company_email' => trim($_POST['company_email'] ?? ''),
                'company_phone' => trim($_POST['company_phone'] ?? ''),
                'company_address' => trim($_POST['company_address'] ?? ''),
                'timezone' => $_POST['timezone'] ?? 'Asia/Manila',
                'date_format' => $_POST['date_format'] ?? 'M d, Y',
                'records_per_page' => $_POST['records_per_page'] ?? '25',
            ];
        } elseif ($section === 'security') {
            $updates = [
                'session_timeout' => $_POST['session_timeout'] ?? '30',
                'max_login_attempts' => $_POST['max_login_attempts'] ?? '5',
                'otp_expiry_minutes' => $_POST['otp_expiry_minutes'] ?? '1',
                'password_min_length' => $_POST['password_min_length'] ?? '8',
                'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
                'allow_registration' => isset($_POST['allow_registration']) ? '1' : '0',
            ];
        } elseif ($section === 'email') {
            $updates = [
                'smtp_host' => trim($_POST['smtp_host'] ?? ''),
                'smtp_port' => $_POST['smtp_port'] ?? '587',
                'smtp_username' => trim($_POST['smtp_username'] ?? ''),
                'smtp_encryption' => $_POST['smtp_encryption'] ?? 'tls',
            ];
        }
        
        foreach ($updates as $key => $value) {
            executeQuery(
                "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
                [$key, $value]
            );
            $settings_map[$key] = $value;
        }
        
        $success_message = ucfirst($section) . " settings saved successfully.";
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get system info
$php_version = phpversion();
$db_version = '';
try {
    $v = fetchSingle("SELECT VERSION() as v");
    $db_version = $v['v'] ?? 'Unknown';
} catch (Exception $e) {
    $db_version = 'Unknown';
}

$total_users = 0;
$total_roles = 0;
try {
    $total_users = fetchSingle("SELECT COUNT(*) as c FROM user_accounts")['c'] ?? 0;
    $total_roles = fetchSingle("SELECT COUNT(*) as c FROM roles")['c'] ?? 0;
} catch (Exception $e) {}
?>
<div data-page-title="System Settings">
<style>
        .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5rem; color: #e2e8f0; margin-bottom: 0.25rem; }
        .header p { color: #94a3b8; font-size: 0.9rem; }

        .success-msg { background: rgba(16, 185, 129, 0.15); color: #4ade80; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid rgba(16, 185, 129, 0.3); }
        .error-msg { background: rgba(239, 68, 68, 0.15); color: #ff6b6b; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid rgba(239, 68, 68, 0.3); }

        .settings-grid { display: grid; grid-template-columns: 220px 1fr; gap: 1.5rem; }
        .settings-nav { list-style: none; }
        .settings-nav li { margin-bottom: 0.25rem; }
        .settings-nav a { display: flex; align-items: center; gap: 0.6rem; padding: 0.7rem 1rem; color: #94a3b8; text-decoration: none; border-radius: 8px; font-size: 0.9rem; transition: all 0.3s; }
        .settings-nav a:hover, .settings-nav a.active { background: rgba(14, 165, 233, 0.1); color: #0ea5e9; }

        .card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); margin-bottom: 1.5rem; }
        .card h2 { font-size: 1.15rem; color: #e2e8f0; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 0.5rem; }
        .card .subtitle { color: #94a3b8; font-size: 0.85rem; margin-bottom: 1.25rem; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; color: #94a3b8; font-size: 0.85rem; margin-bottom: 0.4rem; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.65rem 0.75rem; background: #2a3544; border: 1px solid #3a4554; border-radius: 8px; color: #e2e8f0; font-size: 0.9rem; transition: border-color 0.3s; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #0ea5e9; }
        .form-group .hint { color: #64748b; font-size: 0.75rem; margin-top: 0.25rem; }

        .toggle-group { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; background: rgba(15, 23, 42, 0.6); border-radius: 8px; margin-bottom: 0.75rem; }
        .toggle-group .toggle-label { color: #e2e8f0; font-size: 0.9rem; }
        .toggle-group .toggle-desc { color: #94a3b8; font-size: 0.8rem; }
        .toggle { position: relative; width: 44px; height: 24px; }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #3a4554; border-radius: 24px; transition: 0.3s; }
        .toggle-slider:before { content: ""; position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.3s; }
        .toggle input:checked + .toggle-slider { background: #0ea5e9; }
        .toggle input:checked + .toggle-slider:before { transform: translateX(20px); }

        .btn { padding: 0.6rem 1.25rem; border: none; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 500; transition: all 0.3s; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: #0ea5e9; color: white; }
        .btn-primary:hover { background: #0284c7; }

        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .info-item { background: rgba(15, 23, 42, 0.6); border-radius: 8px; padding: 1rem; }
        .info-item .label { color: #94a3b8; font-size: 0.8rem; margin-bottom: 0.25rem; }
        .info-item .value { color: #e2e8f0; font-size: 1rem; font-weight: 500; }

        .settings-section { display: none; }
        .settings-section.active { display: block; }

        @media (max-width: 1024px) {
            .settings-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
            <div class="header">
                <div>
                    <h1>System Settings</h1>
                    <p>Configure system preferences and parameters</p>
                </div>
                <div class="header-actions">
                    <?php include '../../includes/header-notifications.php'; ?>
                </div>
            </div>

            <?php if ($success_message): ?>
                <div class="success-msg"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="settings-grid">
                <div class="card" style="height: fit-content;">
                    <ul class="settings-nav">
                        <li><a href="#" class="active" onclick="showSection('general', this)"><i data-lucide="building-2" style="width:18px;height:18px;"></i> General</a></li>
                        <li><a href="#" onclick="showSection('security', this)"><i data-lucide="shield-check" style="width:18px;height:18px;"></i> Security</a></li>
                        <li><a href="#" onclick="showSection('email', this)"><i data-lucide="mail" style="width:18px;height:18px;"></i> Email / SMTP</a></li>
                        <li><a href="#" onclick="showSection('system', this)"><i data-lucide="server" style="width:18px;height:18px;"></i> System Info</a></li>
                    </ul>
                </div>

                <div>
                    <!-- General Settings -->
                    <div id="section-general" class="settings-section active">
                        <form method="POST">
                            <input type="hidden" name="section" value="general">
                            <div class="card">
                                <h2><i data-lucide="building-2"></i> General Settings</h2>
                                <p class="subtitle">Company information and display preferences</p>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Company Name</label>
                                        <input type="text" name="company_name" value="<?php echo htmlspecialchars($settings_map['company_name']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Company Email</label>
                                        <input type="email" name="company_email" value="<?php echo htmlspecialchars($settings_map['company_email']); ?>">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Company Phone</label>
                                        <input type="text" name="company_phone" value="<?php echo htmlspecialchars($settings_map['company_phone']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Timezone</label>
                                        <select name="timezone">
                                            <option value="Asia/Manila" <?php echo $settings_map['timezone'] === 'Asia/Manila' ? 'selected' : ''; ?>>Asia/Manila (UTC+8)</option>
                                            <option value="UTC" <?php echo $settings_map['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                            <option value="America/New_York" <?php echo $settings_map['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>America/New_York (EST)</option>
                                            <option value="Europe/London" <?php echo $settings_map['timezone'] === 'Europe/London' ? 'selected' : ''; ?>>Europe/London (GMT)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Company Address</label>
                                    <input type="text" name="company_address" value="<?php echo htmlspecialchars($settings_map['company_address']); ?>">
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Date Format</label>
                                        <select name="date_format">
                                            <option value="M d, Y" <?php echo $settings_map['date_format'] === 'M d, Y' ? 'selected' : ''; ?>>Jan 01, 2025</option>
                                            <option value="d/m/Y" <?php echo $settings_map['date_format'] === 'd/m/Y' ? 'selected' : ''; ?>>01/01/2025</option>
                                            <option value="Y-m-d" <?php echo $settings_map['date_format'] === 'Y-m-d' ? 'selected' : ''; ?>>2025-01-01</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Records Per Page</label>
                                        <select name="records_per_page">
                                            <option value="10" <?php echo $settings_map['records_per_page'] === '10' ? 'selected' : ''; ?>>10</option>
                                            <option value="25" <?php echo $settings_map['records_per_page'] === '25' ? 'selected' : ''; ?>>25</option>
                                            <option value="50" <?php echo $settings_map['records_per_page'] === '50' ? 'selected' : ''; ?>>50</option>
                                            <option value="100" <?php echo $settings_map['records_per_page'] === '100' ? 'selected' : ''; ?>>100</option>
                                        </select>
                                    </div>
                                </div>
                                <div style="text-align: right; margin-top: 0.5rem;">
                                    <button type="submit" class="btn btn-primary"><i data-lucide="save" style="width:16px;height:16px;"></i> Save General Settings</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Security Settings -->
                    <div id="section-security" class="settings-section">
                        <form method="POST">
                            <input type="hidden" name="section" value="security">
                            <div class="card">
                                <h2><i data-lucide="shield-check"></i> Security Settings</h2>
                                <p class="subtitle">Authentication and access control parameters</p>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Session Timeout (minutes)</label>
                                        <input type="number" name="session_timeout" value="<?php echo htmlspecialchars($settings_map['session_timeout']); ?>" min="5" max="120">
                                        <p class="hint">Auto-logout after inactivity</p>
                                    </div>
                                    <div class="form-group">
                                        <label>Max Login Attempts</label>
                                        <input type="number" name="max_login_attempts" value="<?php echo htmlspecialchars($settings_map['max_login_attempts']); ?>" min="3" max="10">
                                        <p class="hint">Before account lockout</p>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>OTP Expiry (minutes)</label>
                                        <input type="number" name="otp_expiry_minutes" value="<?php echo htmlspecialchars($settings_map['otp_expiry_minutes']); ?>" min="1" max="15">
                                    </div>
                                    <div class="form-group">
                                        <label>Minimum Password Length</label>
                                        <input type="number" name="password_min_length" value="<?php echo htmlspecialchars($settings_map['password_min_length']); ?>" min="6" max="20">
                                    </div>
                                </div>

                                <div style="margin-top: 0.5rem;">
                                    <div class="toggle-group">
                                        <div>
                                            <div class="toggle-label">Maintenance Mode</div>
                                            <div class="toggle-desc">Disable access for non-admin users</div>
                                        </div>
                                        <label class="toggle">
                                            <input type="checkbox" name="maintenance_mode" <?php echo $settings_map['maintenance_mode'] === '1' ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="toggle-group">
                                        <div>
                                            <div class="toggle-label">Allow Registration</div>
                                            <div class="toggle-desc">Allow new users to register / apply</div>
                                        </div>
                                        <label class="toggle">
                                            <input type="checkbox" name="allow_registration" <?php echo $settings_map['allow_registration'] === '1' ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div style="text-align: right; margin-top: 1rem;">
                                    <button type="submit" class="btn btn-primary"><i data-lucide="save" style="width:16px;height:16px;"></i> Save Security Settings</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Email Settings -->
                    <div id="section-email" class="settings-section">
                        <form method="POST">
                            <input type="hidden" name="section" value="email">
                            <div class="card">
                                <h2><i data-lucide="mail"></i> Email / SMTP Settings</h2>
                                <p class="subtitle">Configure outgoing email for OTP and notifications</p>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>SMTP Host</label>
                                        <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($settings_map['smtp_host']); ?>" placeholder="smtp.gmail.com">
                                    </div>
                                    <div class="form-group">
                                        <label>SMTP Port</label>
                                        <input type="number" name="smtp_port" value="<?php echo htmlspecialchars($settings_map['smtp_port']); ?>" placeholder="587">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>SMTP Username / Email</label>
                                        <input type="text" name="smtp_username" value="<?php echo htmlspecialchars($settings_map['smtp_username']); ?>" placeholder="your@gmail.com">
                                    </div>
                                    <div class="form-group">
                                        <label>Encryption</label>
                                        <select name="smtp_encryption">
                                            <option value="tls" <?php echo $settings_map['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                            <option value="ssl" <?php echo $settings_map['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                            <option value="none" <?php echo $settings_map['smtp_encryption'] === 'none' ? 'selected' : ''; ?>>None</option>
                                        </select>
                                    </div>
                                </div>
                                <p class="hint" style="margin-bottom: 1rem;">Note: SMTP password should be configured in the server environment variables for security.</p>
                                <div style="text-align: right;">
                                    <button type="submit" class="btn btn-primary"><i data-lucide="save" style="width:16px;height:16px;"></i> Save Email Settings</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- System Info -->
                    <div id="section-system" class="settings-section">
                        <div class="card">
                            <h2><i data-lucide="server"></i> System Information</h2>
                            <p class="subtitle">Server and application details</p>
                            
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="label">PHP Version</div>
                                    <div class="value"><?php echo $php_version; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="label">Database Version</div>
                                    <div class="value"><?php echo htmlspecialchars($db_version); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="label">Server Software</div>
                                    <div class="value"><?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="label">Document Root</div>
                                    <div class="value" style="font-size: 0.8rem; word-break: break-all;"><?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? ''); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="label">Total Users</div>
                                    <div class="value"><?php echo number_format($total_users); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="label">Total Roles</div>
                                    <div class="value"><?php echo number_format($total_roles); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="label">Server Time</div>
                                    <div class="value"><?php echo date('M d, Y g:i A'); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="label">Memory Usage</div>
                                    <div class="value"><?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function showSection(section, el) {
            event.preventDefault();
            document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.settings-nav a').forEach(a => a.classList.remove('active'));
            document.getElementById('section-' + section).classList.add('active');
            el.classList.add('active');
        }

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</div>
