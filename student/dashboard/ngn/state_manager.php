<?php
session_start();
include '../../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not authenticated']);
    exit;
}

$student_id = intval($_SESSION['user_id']);
$body = json_decode(file_get_contents('php://input'), true);

if (!$body || !isset($body['examTaken']) || !isset($body['question_set']) || !isset($body['timer'])) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid payload']);
    exit;
}

$examTaken = intval($body['examTaken']);
$question_set = json_encode($body['question_set']);
$current_question = isset($body['current_question']) ? intval($body['current_question']) : 0;
$timer = intval($body['timer']);
$updated_at = date('Y-m-d H:i:s');

// We use INSERT ... ON DUPLICATE KEY UPDATE to create or update the state
$stmt = mysqli_prepare($con, "
    INSERT INTO temporary_exam_state (student_id, examTaken, question_set, current_question, timer, updated_at) 
    VALUES (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
    question_set = VALUES(question_set), 
    current_question = VALUES(current_question), 
    timer = VALUES(timer), 
    updated_at = VALUES(updated_at)
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => mysqli_error($con)]);
    exit;
}

mysqli_stmt_bind_param($stmt, 'iisiis', $student_id, $examTaken, $question_set, $current_question, $timer, $updated_at);
$res = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($res) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'db_insert_failed', 'mysql' => mysqli_error($con)]);
}
?>
