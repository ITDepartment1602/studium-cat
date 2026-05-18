<?php
/**
 * NGN Save History - Saves exam answer to temporary table
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
if (!$body) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid payload']);
    exit;
}

// Tables are created by config.php — no duplicate CREATE TABLE here

// Sanitize inputs
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
$initial_answer = isset($body['initial_answer']) ? json_encode($body['initial_answer']) : null;
$changes        = isset($body['changes'])        ? json_encode($body['changes']) : null;
$isCorrect      = isset($body['isCorrect'])      ? intval($body['isCorrect']) : 0;

// Score supports decimal (0.00-1.00) for partial credit
$score          = isset($body['score'])          ? floatval($body['score']) : ($isCorrect ? 1.00 : 0.00);
$max_points     = isset($body['max_points'])     ? intval($body['max_points']) : 1;
$earned_points  = isset($body['earned_points'])  ? floatval($body['earned_points']) : ($isCorrect ? 1 : 0);
$rationale      = isset($body['rationale'])      ? $body['rationale'] : '';
$omitted        = isset($body['omitted'])        ? intval($body['omitted']) : 0;
$changes_count  = isset($body['changes_count'])  ? intval($body['changes_count']) : 0;

$question_number = isset($body['question_number']) ? intval($body['question_number']) : 0;
$time_taken      = isset($body['time_taken'])      ? intval($body['time_taken']) : 0;
$totalTime       = isset($body['totalTime'])       ? intval($body['totalTime']) : 0;
$concept         = isset($body['concept'])         ? $body['concept'] : null;
$created_at      = date('Y-m-d H:i:s');

// Always resolve concept from question table — critical for NCLEX Readiness dashboard
if ($concept === null && $question_uid !== '') {
    static $qTableMap = [
        'highlight' => 'highlight', 'traditional' => 'traditional', 'mc' => 'traditional',
        'bowtie' => 'btq', 'bt' => 'btq', 'mmr' => 'mmr', 'mpr' => 'mpr',
        'sata' => 'sata', 'dropdown' => 'dropdown', 'ddl' => 'dropdown',
        'dragndrop' => 'dragndrop',
    ];
    // Parse numeric ID from question_uid format "type-NNN"
    $resolvedId = $question_id > 0 ? $question_id : 0;
    if ($resolvedId === 0 && preg_match('/-(\d+)$/', $question_uid, $m)) {
        $resolvedId = (int)$m[1];
    }
    if ($resolvedId > 0 && isset($qTableMap[$question_type])) {
        $cRow = db()->fetchOne("SELECT concept FROM `{$qTableMap[$question_type]}` WHERE id = ? LIMIT 1", [$resolvedId]);
        if ($cRow) $concept = $cRow['concept'] ?? null;
    }
}

// Delete existing record for the same question in this attempt to support re-submissions
db()->execute(
    "DELETE FROM temporary_exam_result WHERE student_id = ? AND examTaken = ? AND question_uid = ?",
    [$student_id, $examTaken, $question_uid]
);

// Insert using db() singleton with prepared statement
$ok = db()->execute(
    "INSERT INTO temporary_exam_result
    (student_id, examTaken, question_uid, question_type, question_id,
     topic, system, cnc, dlevel,
     user_answer, correct_answer, initial_answer, changes,
     isCorrect, score, earned_points, max_points,
     omitted, changes_count, rationale, concept,
     question_number, time_taken, totalTime, timestamp)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
    [
        $student_id, $examTaken, $question_uid, $question_type, $question_id,
        $topic, $system, $cnc, $dlevel,
        $user_answer, $correct_answer, $initial_answer, $changes,
        $isCorrect, $score, $earned_points, $max_points,
        $omitted, $changes_count, $rationale, $concept,
        $question_number, $time_taken, $totalTime, $created_at
    ]
);

if ($ok) {
    echo json_encode(['ok' => true, 'saved' => 1]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'db_insert_failed']);
}
