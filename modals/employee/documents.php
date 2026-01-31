<?php
require_once '../../includes/session_helper.php';
startSecureSession();

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'Employee') {
    header('Location: ../../index.php');
    exit();
}

require_once '../../database/config.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

$employee = fetchSingle("SELECT * FROM employees WHERE user_id = ?", [$user_id]);

$documents = fetchAll("
    SELECT * FROM employee_documents 
    WHERE employee_id = ? 
    ORDER BY uploaded_at DESC
", [$employee['id'] ?? 0]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents - HR1</title>
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
        .header { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
        .header h1 { font-size: 1.5rem; color: #e2e8f0; }
        .documents-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
        .doc-card { background: rgba(30, 41, 54, 0.6); border-radius: 12px; padding: 1.5rem; border: 1px solid rgba(58, 69, 84, 0.5); transition: all 0.3s; }
        .doc-card:hover { border-color: #0ea5e9; }
        .doc-icon { width: 60px; height: 60px; border-radius: 12px; background: rgba(14, 165, 233, 0.2); display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; }
        .doc-icon .material-symbols-outlined { font-size: 2rem; color: #0ea5e9; }
        .doc-name { color: #e2e8f0; font-weight: 600; margin-bottom: 0.5rem; word-break: break-word; }
        .doc-meta { color: #94a3b8; font-size: 0.8rem; margin-bottom: 1rem; }
        .doc-actions { display: flex; gap: 0.75rem; }
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 6px; font-size: 0.85rem; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem; }
        .btn-primary { background: #0ea5e9; color: white; }
        .btn-primary:hover { background: #0284c7; }
        .empty-state { text-align: center; padding: 4rem 2rem; color: #94a3b8; }
        .empty-state .material-symbols-outlined { font-size: 5rem; color: #475569; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-section">
                <img src="../../assets/images/slate.png" alt="SLATE Logo">
                <h2>Employee Portal</h2>
                <p><?php echo htmlspecialchars($user_name); ?></p>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><span class="material-symbols-outlined">dashboard</span>Dashboard</a></li>
                <li class="nav-item"><a href="onboarding.php" class="nav-link"><span class="material-symbols-outlined">checklist</span>Onboarding</a></li>
                <li class="nav-item"><a href="requirements.php" class="nav-link"><span class="material-symbols-outlined">upload_file</span>Submit Requirements</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link"><span class="material-symbols-outlined">person</span>My Profile</a></li>
                <li class="nav-item"><a href="documents.php" class="nav-link active"><span class="material-symbols-outlined">folder</span>Documents</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link"><span class="material-symbols-outlined">logout</span>Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header"><h1>My Documents</h1></div>

            <?php if (empty($documents)): ?>
            <div class="empty-state">
                <span class="material-symbols-outlined">folder_off</span>
                <h3>No Documents</h3>
                <p>Your HR documents will appear here once uploaded.</p>
            </div>
            <?php else: ?>
            <div class="documents-grid">
                <?php foreach ($documents as $doc): ?>
                <div class="doc-card">
                    <div class="doc-icon"><span class="material-symbols-outlined">description</span></div>
                    <div class="doc-name"><?php echo htmlspecialchars($doc['document_name']); ?></div>
                    <div class="doc-meta">
                        <div>Type: <?php echo htmlspecialchars($doc['document_type'] ?? 'Document'); ?></div>
                        <div>Uploaded: <?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></div>
                    </div>
                    <div class="doc-actions">
                        <a href="../../<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn btn-primary">
                            <span class="material-symbols-outlined" style="font-size: 1rem;">download</span>Download
                        </a>
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
