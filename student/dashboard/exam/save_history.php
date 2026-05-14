<?php
// Exam Mode — Save answer to temporary_exam_result + IRT snapshot
require_once '../../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not authenticated']);
    exit;
}

$student_id = intval($_SESSION['user_id']);
$rawBody = file_get_contents('php://input');
$body = json_decode($rawBody, true);
if (!is_array($body)) {
    error_log('save_history invalid payload: ' . $rawBody);
    http_response_code(400);
    echo json_encode(['error' => 'invalid payload']);
    exit;
}

$examTaken    = isset($body['examTaken'])    ? intval($body['examTaken'])    : 0;
$question_uid = isset($body['question_uid']) ? trim($body['question_uid'])  : '';
$question_type   = isset($body['question_type'])   ? $body['question_type']           : '';
$question_id     = isset($body['question_id'])     ? intval($body['question_id'])     : 0;
$question_number = isset($body['question_number']) ? intval($body['question_number']) : 0;
$topic           = isset($body['topic'])           ? $body['topic']                   : '';
$system          = isset($body['system'])          ? $body['system']                  : '';
$cnc             = isset($body['cnc'])             ? $body['cnc']                     : '';
$dlevel          = isset($body['dlevel'])          ? $body['dlevel']                  : '';
$user_answer     = isset($body['answer'])          ? json_encode($body['answer'])     : '[]';
$correct_answer  = isset($body['correct_answer'])  ? json_encode($body['correct_answer']) : '[]';
$initial_answer  = isset($body['initial_answer'])  ? json_encode($body['initial_answer']) : null;
$changes         = isset($body['changes'])         ? json_encode($body['changes'])    : null;
$isCorrect       = isset($body['isCorrect'])       ? intval($body['isCorrect'])       : 0;
$score           = isset($body['score'])           ? floatval($body['score'])         : ($isCorrect ? 1.0 : 0.0);
$max_points      = isset($body['max_points'])      ? intval($body['max_points'])      : 1;
$earned_points   = isset($body['earned_points'])   ? floatval($body['earned_points']) : ($isCorrect ? 1 : 0);
$rationale       = isset($body['rationale'])       ? $body['rationale']               : '';
$omitted         = isset($body['omitted'])         ? intval($body['omitted'])         : 0;
$changes_count   = isset($body['changes_count'])   ? intval($body['changes_count'])   : 0;
$time_taken      = isset($body['time_taken'])      ? intval($body['time_taken'])      : 0;
$totalTime       = isset($body['totalTime'])       ? intval($body['totalTime'])       : 0;
$created_at      = date('Y-m-d H:i:s');

// IRT snapshot fields (exam mode only — stored for audit trail)
$theta_before    = isset($body['theta_before'])    ? floatval($body['theta_before'])    : 0.0;
$theta_after     = isset($body['theta_after'])     ? floatval($body['theta_after'])     : 0.0;
$sem_after       = isset($body['sem_after'])       ? floatval($body['sem_after'])       : 1.0;
$item_difficulty = isset($body['item_difficulty']) ? floatval($body['item_difficulty']) : 0.0;
$weighted_score  = isset($body['weighted_score'])  ? floatval($body['weighted_score'])  : $score;

if ($examTaken <= 0 || $question_uid === '') {
    error_log('save_history missing required fields: ' . json_encode(['examTaken' => $examTaken, 'question_uid' => $question_uid]));
    http_response_code(400);
    echo json_encode(['error' => 'missing required fields']);
    exit;
}

// Ownership check — the student must exist and this attempt must be valid
$ownership = db()->fetchOne(
    "SELECT id FROM login WHERE id = ? LIMIT 1",
    [$student_id]
);
if (!$ownership) {
    error_log('save_history forbidden student: ' . $student_id);
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

// Replace existing record for this question in this attempt
db()->execute(
    "DELETE FROM exammoderesults WHERE student_id = ? AND examTaken = ? AND question_uid = ?",
    [$student_id, $examTaken, $question_uid]
);

$ok = db()->execute(
    "INSERT INTO exammoderesults
        (student_id, examTaken, question_uid, question_type, question_id,
         topic, system, cnc, dlevel,
         user_answer, correct_answer, initial_answer, changes,
         isCorrect, score, earned_points, max_points,
         omitted, changes_count, rationale,
         question_number, time_taken, totalTime, timestamp,
         theta_before, theta_after, sem_after, item_difficulty, weighted_score)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
    [
        $student_id, $examTaken, $question_uid, $question_type, $question_id,
        $topic, $system, $cnc, $dlevel,
        $user_answer, $correct_answer, $initial_answer, $changes,
        $isCorrect, $score, $earned_points, $max_points,
        $omitted, $changes_count, $rationale,
        $question_number, $time_taken, $totalTime, $created_at,
        $theta_before, $theta_after, $sem_after, $item_difficulty, $weighted_score
    ]
);

if ($ok) {
    echo json_encode([
        'ok'      => true,
        'saved'   => 1,
        'theta'   => $theta_after,
        'sem'     => $sem_after,
    ]);
} else {
    error_log('save_history db_insert_failed for student ' . $student_id . ' examTaken ' . $examTaken . ' question_uid ' . $question_uid);
    http_response_code(500);
    echo json_encode(['error' => 'db_insert_failed']);
}
