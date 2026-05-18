<?php
/**
 * NGN Exam Submission - Transfer temporary results to permanent storage
 */
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

// Tables are created by config.php — no duplicate CREATE TABLE here

// Check if there's data to transfer
$tempData = db()->fetchOne(
    "SELECT COUNT(*) as cnt FROM temporary_exam_result WHERE student_id = ? AND examTaken = ?",
    [$student_id, $examTaken]
);

if (!$tempData || $tempData['cnt'] == 0) {
    echo json_encode(['ok' => true, 'message' => 'No data to transfer']);
    exit;
}

$conn = db()->getConnection();
$conn->begin_transaction();

try {
    // Transfer data from temporary_exam_result to exam_results
    $ok = db()->execute(
        "INSERT INTO exam_results (student_id, examTaken, question_uid, question_type, topic, system, cnc, dlevel, user_answer, correct_answer, initial_answer, changes, isCorrect, score, earned_points, max_points, omitted, changes_count, rationale, concept, question_number, time_taken, totalTime, timestamp)
         SELECT student_id, examTaken, question_uid, question_type, topic, system, cnc, dlevel, user_answer, correct_answer, initial_answer, changes, isCorrect, score, earned_points, max_points, omitted, changes_count, rationale, concept, question_number, time_taken, totalTime, timestamp
         FROM temporary_exam_result
         WHERE student_id = ? AND examTaken = ?",
        [$student_id, $examTaken]
    );
    if (!$ok) throw new Exception("Transfer failed");

    // Delete from temporary_exam_result
    db()->execute(
        "DELETE FROM temporary_exam_result WHERE student_id = ? AND examTaken = ?",
        [$student_id, $examTaken]
    );

    // Delete from temporary_exam_state
    db()->execute(
        "DELETE FROM temporary_exam_state WHERE student_id = ? AND examTaken = ?",
        [$student_id, $examTaken]
    );

    $conn->commit();

    // Unset Session Data for this attempt
    unset($_SESSION['current_ngn_examTaken']);
    unset($_SESSION['ngn_exam_set']);

    echo json_encode(['ok' => true, 'transferred' => $tempData['cnt']]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Transfer failed']);
}
