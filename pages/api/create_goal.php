<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }

require_once '../../database/config.php';

$title = trim($_POST['goalTitle'] ?? '');
$employeeId = (int)($_POST['employeeId'] ?? 0);
$category = trim($_POST['category'] ?? 'performance');
$priority = trim($_POST['priority'] ?? 'medium');
$description = trim($_POST['description'] ?? '');
$startDate = $_POST['startDate'] ?? null;
$targetDate = $_POST['targetDate'] ?? null;

if ($title === '' || !$employeeId || $description === '' || !$startDate || !$targetDate) {
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Missing required fields']); exit;
}

try {
    $id = insertRecord(
        "INSERT INTO performance_goals (employee_id, title, description, category, priority, start_date, target_date, progress_percentage, status, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'active', ?)",
        [$employeeId, $title, $description, $category, $priority, $startDate, $targetDate, $_SESSION['user_id']]
    );
    echo json_encode(['success'=>true,'id'=>$id]);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['success'=>false,'message'=>'Server error']);
}
?>





