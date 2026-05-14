<?php
// Exam Mode — Cancel / terminate, drop all temp data
require_once '../../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not authenticated']);
    exit;
}

$student_id = intval($_SESSION['user_id']);
$body = json_decode(file_get_contents('php://input'), true);

if (!$body || !isset($body['examTaken'])) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid payload']);
    exit;
}

$examTaken = intval($body['examTaken']);

$conn = db()->getConnection();
$conn->begin_transaction();

try {
    db()->execute(
        "DELETE FROM exammoderesults WHERE student_id = ? AND examTaken = ?",
        [$student_id, $examTaken]
    );
    db()->execute(
        "DELETE FROM temporary_exam_state WHERE student_id = ? AND exam_mode = 'exam'",
        [$student_id]
    );

    $conn->commit();

    unset($_SESSION['current_exam_examTaken']);

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
