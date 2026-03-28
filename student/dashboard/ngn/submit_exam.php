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

if (!$body || !isset($body['examTaken'])) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid payload']);
    exit;
}

$examTaken = intval($body['examTaken']);

mysqli_begin_transaction($con);

try {
    // Transfer data from temporary_exam_result to exam_results
    $transfer_stmt = mysqli_prepare($con, "
        INSERT INTO exam_results (student_id, examTaken, question_uid, question_type, topic, system, cnc, dlevel, user_answer, correct_answer, initial_answer, changes, isCorrect, score, earned_points, max_points, rationale, question_number, time_taken, totalTime, timestamp)
        SELECT student_id, examTaken, question_uid, question_type, topic, system, cnc, dlevel, user_answer, correct_answer, initial_answer, changes, isCorrect, score, earned_points, max_points, rationale, question_number, time_taken, totalTime, timestamp
        FROM temporary_exam_result
        WHERE student_id = ? AND examTaken = ?
    ");
    
    if (!$transfer_stmt) throw new Exception("Prepare transfer failed: " . mysqli_error($con));
    
    mysqli_stmt_bind_param($transfer_stmt, 'ii', $student_id, $examTaken);
    mysqli_stmt_execute($transfer_stmt);
    mysqli_stmt_close($transfer_stmt);

    // Delete from temporary_exam_result
    $del_stmt = mysqli_prepare($con, "DELETE FROM temporary_exam_result WHERE student_id = ? AND examTaken = ?");
    mysqli_stmt_bind_param($del_stmt, 'ii', $student_id, $examTaken);
    mysqli_stmt_execute($del_stmt);
    mysqli_stmt_close($del_stmt);

    // Delete from temporary_exam_state
    $del_state = mysqli_prepare($con, "DELETE FROM temporary_exam_state WHERE student_id = ? AND examTaken = ?");
    mysqli_stmt_bind_param($del_state, 'ii', $student_id, $examTaken);
    mysqli_stmt_execute($del_state);
    mysqli_stmt_close($del_state);

    // Commit Transaction
    mysqli_commit($con);
    
    // Unset Session Data for this attempt
    if (isset($_SESSION['current_ngn_examTaken'])) {
        unset($_SESSION['current_ngn_examTaken']);
    }
    if (isset($_SESSION['ngn_exam_set'])) {
        unset($_SESSION['ngn_exam_set']);
    }

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    mysqli_rollback($con);
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
