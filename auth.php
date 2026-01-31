<?php
require_once __DIR__ . '/includes/session_helper.php';

// Start secure session
startSecureSession();

// Require authentication
requireAuth('../partials/login.php');
?>
