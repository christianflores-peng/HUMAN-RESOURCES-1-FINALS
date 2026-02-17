<?php
/**
 * FTP Directory Structure Checker
 * 
 * This script connects to your sub-domain via FTP and lists the directory structure
 * to help identify the correct paths for deployment.
 * 
 * Usage: php utils/ftp_check_structure.php
 */

// FTP Configuration
$ftp_server = 'ftp.yourdomain.com'; // UPDATE THIS with your actual FTP server
$ftp_username = 'hr1slateXCZ_domain_ftp';
$ftp_password = 'HihMzLPiO%vacL8b';
$ftp_port = 21;

echo "=== FTP Directory Structure Checker ===\n\n";

// Connect to FTP server
echo "Connecting to FTP server: $ftp_server...\n";
$ftp_conn = ftp_connect($ftp_server, $ftp_port, 30);

if (!$ftp_conn) {
    die("ERROR: Could not connect to FTP server $ftp_server\n");
}

echo "✓ Connected successfully\n\n";

// Login to FTP
echo "Logging in as: $ftp_username...\n";
$login = ftp_login($ftp_conn, $ftp_username, $ftp_password);

if (!$login) {
    ftp_close($ftp_conn);
    die("ERROR: FTP login failed. Check username and password.\n");
}

echo "✓ Logged in successfully\n\n";

// Enable passive mode
ftp_pasv($ftp_conn, true);

// Function to list directory recursively
function listFtpDirectory($ftp_conn, $dir, $depth = 0, $max_depth = 2) {
    if ($depth > $max_depth) return;
    
    $indent = str_repeat("  ", $depth);
    $files = ftp_nlist($ftp_conn, $dir);
    
    if ($files === false) {
        echo $indent . "✗ Cannot access: $dir\n";
        return;
    }
    
    foreach ($files as $file) {
        $basename = basename($file);
        
        // Skip . and ..
        if ($basename == '.' || $basename == '..') continue;
        
        // Check if it's a directory
        $is_dir = false;
        $size = ftp_size($ftp_conn, $file);
        
        if ($size == -1) {
            // Likely a directory
            $is_dir = true;
            echo $indent . "📁 $basename/\n";
            
            // Recursively list subdirectory
            if ($depth < $max_depth) {
                listFtpDirectory($ftp_conn, $file, $depth + 1, $max_depth);
            }
        } else {
            // It's a file
            $size_kb = round($size / 1024, 2);
            echo $indent . "📄 $basename ($size_kb KB)\n";
        }
    }
}

// Check common root directories
$common_paths = ['/', '/public_html', '/www', '/httpdocs', '/html'];

echo "Checking common root directories...\n\n";

foreach ($common_paths as $path) {
    echo "=== Checking: $path ===\n";
    
    if (@ftp_chdir($ftp_conn, $path)) {
        echo "✓ Directory exists and accessible\n";
        listFtpDirectory($ftp_conn, $path, 0, 2);
    } else {
        echo "✗ Directory not found or not accessible\n";
    }
    
    echo "\n";
}

// Look for config.php files
echo "=== Searching for config.php files ===\n";
$search_paths = [
    '/public_html/database/config.php',
    '/www/database/config.php',
    '/httpdocs/database/config.php',
    '/database/config.php',
    '/public_html/includes/smtp_config.php',
    '/www/includes/smtp_config.php'
];

foreach ($search_paths as $search_path) {
    $size = @ftp_size($ftp_conn, $search_path);
    if ($size != -1) {
        $size_kb = round($size / 1024, 2);
        echo "✓ FOUND: $search_path ($size_kb KB)\n";
    }
}

echo "\n";

// Close FTP connection
ftp_close($ftp_conn);

echo "=== Check Complete ===\n";
echo "\nNext steps:\n";
echo "1. Note the correct base path (e.g., /public_html/)\n";
echo "2. Update ftp_deploy_config.php with the correct paths\n";
echo "3. Run the deployment script\n";
?>
