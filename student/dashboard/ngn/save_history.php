<?php
// save_history.php — Saves exam results with partial-credit scoring support
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
if (!$body) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid payload']);
    exit;
}

// Sanitize inputs using prepared statement
$examTaken      = isset($body['examTaken'])      ? intval($body['examTaken']) : 0;
$question_uid   = isset($body['question_uid'])   ? $body['question_uid'] : '';
$question_type  = isset($body['question_type'])  ? $body['question_type'] : '';
$question_id    = isset($body['question_id'])    ? intval($body['question_id']) : 0;
$topic          = isset($body['topic'])          ? $body['topic'] : '';
$system         = isset($body['system'])         ? $body['system'] : '';
$cnc            = isset($body['cnc'])            ? $body['cnc'] : '';
$dlevel         = isset($body['dlevel'])         ? $body['dlevel'] : '';
$user_answer    = isset($body['answer'])         ? json_encode($body['answer']) : '[]';
$correct_answer = isset($body['correct_answer']) ? json_encode($body['correct_answer']) : '[]';
$initial_answer = isset($body['initial_answer']) ? json_encode($body['initial_answer']) : null;  // New field
$changes        = isset($body['changes'])        ? json_encode($body['changes']) : null;        // New field
$isCorrect      = isset($body['isCorrect'])      ? intval($body['isCorrect']) : 0;

// Score now supports decimal (0.00–1.00) for partial credit
$score          = isset($body['score'])          ? floatval($body['score']) : ($isCorrect ? 1.00 : 0.00);
$max_points     = isset($body['max_points'])     ? intval($body['max_points']) : 1;
$earned_points  = isset($body['earned_points'])  ? intval($body['earned_points']) : ($isCorrect ? 1 : 0);
$rationale      = isset($body['rationale'])      ? $body['rationale'] : '';

$question_number = isset($body['question_number']) ? intval($body['question_number']) : 0;
$time_taken      = isset($body['time_taken'])      ? intval($body['time_taken']) : 0;
$totalTime       = isset($body['totalTime'])       ? intval($body['totalTime']) : 0;
$created_at      = date('Y-m-d H:i:s');

// Delete existing record for the same question in this attempt to support re-submissions/editing
$del_stmt = mysqli_prepare($con, "DELETE FROM temporary_exam_result WHERE student_id=? AND examTaken=? AND question_uid=?");
mysqli_stmt_bind_param($del_stmt, 'iis', $student_id, $examTaken, $question_uid);
mysqli_stmt_execute($del_stmt);
mysqli_stmt_close($del_stmt);

// Use prepared statement for security
$stmt = mysqli_prepare($con,
    "INSERT INTO temporary_exam_result
    (student_id, examTaken, question_uid, question_type, topic, system, cnc, dlevel, user_answer, correct_answer, initial_answer, changes, isCorrect, score, earned_points, max_points, rationale, question_number, time_taken, totalTime, timestamp)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'prepare_failed', 'mysql' => mysqli_error($con)]);
    exit;
}

mysqli_stmt_bind_param($stmt, 'iisssssssssssdiisiiis',
    $student_id,
    $examTaken,
    $question_uid,
    $question_type,
    $topic,
    $system,
    $cnc,
    $dlevel,
    $user_answer,
    $correct_answer,
    $initial_answer,
    $changes,
    $isCorrect,
    $score,
    $earned_points,
    $max_points,
    $rationale,
    $question_number,
    $time_taken,
    $totalTime,
    $created_at
);

$res = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($res) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'db_insert_failed', 'mysql' => mysqli_error($con)]);
}
