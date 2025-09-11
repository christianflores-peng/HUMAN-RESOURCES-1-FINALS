<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }

require_once '../../database/config.php';

$employeeId = (int)($_POST['employeeId'] ?? 0);
$reviewType = trim($_POST['reviewType'] ?? 'annual');
$dueDate = $_POST['dueDate'] ?? null;

if (!$employeeId || !$dueDate) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Missing required fields']); exit; }

try {
    $id = insertRecord(
        "INSERT INTO performance_reviews (employee_id, reviewer_id, review_period_start, review_period_end, review_type, status, due_date)
         VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 MONTH), ?, 'draft', ?)",
        [$employeeId, $_SESSION['user_id'], $reviewType, $dueDate]
    );
    echo json_encode(['success'=>true,'id'=>$id]);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['success'=>false,'message'=>'Server error']);
}
?>





