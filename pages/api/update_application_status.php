<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../../database/config.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

$allowed = ['new','reviewed','screening','interview','offer','hired','rejected'];
if (!$id || !in_array($status, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

try {
    $rows = updateRecord("UPDATE job_applications SET status = ?, updated_at = NOW(), reviewed_by = ? WHERE id = ?", [$status, $_SESSION['user_id'], $id]);
    echo json_encode(['success' => $rows > 0]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>





