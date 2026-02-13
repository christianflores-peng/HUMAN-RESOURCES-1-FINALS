<?php
require_once '../includes/session_helper.php';

// Start secure session
startSecureSession();

// Securely destroy session
destroySecureSession();

// Redirect to the landing page
header("Location: ../index.php");
exit();
?>