<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check if user has admin access
$admin_roles = ['Administrator', 'HR Manager', 'Recruiter', 'Manager', 'Supervisor', 'Employee'];
if (!in_array($_SESSION['role'] ?? '', $admin_roles)) {
    header('Location: ../careers.php');
    exit();
}

// Get file path from query parameter
$file_path = $_GET['file'] ?? '';

if (empty($file_path)) {
    http_response_code(400);
    die('<h2>Error: No file specified</h2>');
}

// Sanitize the file path to prevent directory traversal
$file_path = str_replace(['../', '..\\', '..', '%2e%2e', '%252e%252e'], '', $file_path);
$file_path = preg_replace('/\.\.+/', '', $file_path); // Remove any remaining dots sequences

// Build full file path
$base_dir = realpath(__DIR__ . '/../');
$attempted_path = $base_dir . '/' . ltrim($file_path, '/\\');
$full_path = realpath($attempted_path);

// Security check: Ensure file is within the allowed directory
if ($full_path === false || strpos($full_path, $base_dir) !== 0) {
    http_response_code(403);
    die('<h2>Error: Access denied</h2>');
}

// Check file exists
if (!file_exists($full_path)) {
    http_response_code(404);
    die('<h2>Error: File not found</h2>' . 
        '<p>Looking for: ' . htmlspecialchars($file_path) . '</p>' .
        '<p>Attempted path: ' . htmlspecialchars($attempted_path) . '</p>' .
        '<p>Resolved path: ' . htmlspecialchars($full_path) . '</p>' .
        '<p>File exists: ' . (file_exists($attempted_path) ? 'Yes at attempted path' : 'No') . '</p>');
}

// Get file extension
$extension = strtolower(pathinfo($full_path, PATHINFO_EXTENSION));

// For Word documents, show a message that they need to be downloaded
if (in_array($extension, ['doc', 'docx'])) {
    // Check if this is being loaded in an iframe (for preview) or direct download
    if (isset($_GET['download'])) {
        // Clear any previous output
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . basename($full_path) . '"');
        header('Content-Length: ' . filesize($full_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: public');
        
        // Output file
        readfile($full_path);
        exit();
    } else {
        // Show message in iframe
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Document Preview</title>
            <style>
                body { 
                    margin: 0; 
                    padding: 2rem; 
                    background: #0f172a; 
                    color: #e2e8f0;
                    font-family: system-ui, -apple-system, sans-serif;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                }
                .message {
                    text-align: center;
                    max-width: 500px;
                }
                .btn {
                    display: inline-block;
                    padding: 0.75rem 1.5rem;
                    background: #3b82f6;
                    color: white;
                    text-decoration: none;
                    border-radius: 6px;
                    margin-top: 1rem;
                }
                .btn:hover { background: #2563eb; }
            </style>
        </head>
        <body>
            <div class="message">
                <h2>📄 Word Document</h2>
                <p>Word documents cannot be previewed in the browser.</p>
                <p>Click the button below to download and open in Microsoft Word.</p>
                <a href="view_file.php?file=<?= urlencode($file_path) ?>&download=1" class="btn">Download Document</a>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
}

// Set content type based on file extension
$content_types = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'txt' => 'text/plain'
];

$content_type = $content_types[$extension] ?? 'application/octet-stream';

// Clear all output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Set headers to display file in browser instead of downloading
header('Content-Type: ' . $content_type);
header('Content-Disposition: inline; filename="' . basename($full_path) . '"');
header('Content-Length: ' . filesize($full_path));
header('Accept-Ranges: bytes');

// Output file content directly
readfile($full_path);
exit();
