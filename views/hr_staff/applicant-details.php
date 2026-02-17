<?php
require_once '../../includes/session_helper.php';
require_once '../../includes/rbac_helper.php';
require_once '../../includes/spa_helper.php';

startSecureSession();
$is_ajax = is_spa_ajax();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit();
}

$userId = $_SESSION['user_id'];
if (!isHRStaff($userId) && !isAdmin($userId)) {
    header('Location: ../../index.php');
    exit();
}

// For direct access, route through the HR Staff panel SPA shell.
if (!$is_ajax) {
    $id = isset($_GET['id']) ? '&id=' . urlencode($_GET['id']) : '';
    header('Location: index.php?page=applicant-details' . $id);
    exit();
}

// Reuse the existing Applicant Details implementation.
// It already allows HR_Staff/Admin in its access guard.
require __DIR__ . '/../manager/applicant-details.php';
