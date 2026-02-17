<?php
/**
 * FTP Deployment Script - Upload Updated Config Files to Sub-domain
 * 
 * This script uploads critical configuration files to the production sub-domain
 * to fix database connection issues.
 * 
 * Usage: Run this script from command line or browser
 * php utils/ftp_deploy_config.php
 */

// FTP Configuration
$ftp_server = 'ftp.yourdomain.com'; // UPDATE THIS with your actual FTP server
$ftp_username = 'hr1slateXCZ_domain_ftp';
$ftp_password = 'HihMzLPiO%vacL8b';
$ftp_port = 21;

// Remote paths (UPDATE these based on your server structure)
$remote_base_path = '/public_html/'; // Common paths: /public_html/, /www/, /httpdocs/
$remote_config_path = $remote_base_path . 'database/config.php';
$remote_smtp_path = $remote_base_path . 'includes/smtp_config.php';

// Local files to upload
$local_files = [
    'database/config.php' => $remote_config_path,
    'includes/smtp_config.php' => $remote_smtp_path
];

echo "=== FTP Deployment Script ===\n\n";

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

// Enable passive mode (helps with firewalls)
ftp_pasv($ftp_conn, true);

// Upload files
$success_count = 0;
$error_count = 0;

foreach ($local_files as $local_path => $remote_path) {
    $full_local_path = __DIR__ . '/../' . $local_path;
    
    echo "Uploading: $local_path\n";
    echo "  Local:  $full_local_path\n";
    echo "  Remote: $remote_path\n";
    
    if (!file_exists($full_local_path)) {
        echo "  ✗ ERROR: Local file not found!\n\n";
        $error_count++;
        continue;
    }
    
    // Create remote directory if needed
    $remote_dir = dirname($remote_path);
    @ftp_mkdir($ftp_conn, $remote_dir);
    
    // Upload file
    $upload = ftp_put($ftp_conn, $remote_path, $full_local_path, FTP_BINARY);
    
    if ($upload) {
        echo "  ✓ Upload successful\n\n";
        $success_count++;
    } else {
        echo "  ✗ Upload failed\n\n";
        $error_count++;
    }
}

// Close FTP connection
ftp_close($ftp_conn);

// Summary
echo "=== Deployment Summary ===\n";
echo "Successful uploads: $success_count\n";
echo "Failed uploads: $error_count\n";
echo "\n";

if ($error_count == 0) {
    echo "✓ All files deployed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Test login on your sub-domain\n";
    echo "2. Check if database connection works\n";
    echo "3. Verify all features are functional\n";
} else {
    echo "⚠ Some files failed to upload.\n";
    echo "Please check:\n";
    echo "1. FTP credentials are correct\n";
    echo "2. Remote paths exist on server\n";
    echo "3. You have write permissions\n";
}

echo "\n=== Deployment Complete ===\n";
?>
