<?php
// Exam Mode — Pause / Resume state persistence (IRT-aware)
require_once '../../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not authenticated']);
    exit;
}

$student_id = intval($_SESSION['user_id']);
$body = json_decode(file_get_contents('php://input'), true);

if (!$body || !isset($body['examTaken'], $body['question_set'], $body['timer'])) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid payload']);
    exit;
}

$examTaken        = intval($body['examTaken']);
$question_set     = json_encode($body['question_set']);
$current_question = isset($body['current_question']) ? intval($body['current_question']) : 0;
$timer            = intval($body['timer']);
$updated_at       = date('Y-m-d H:i:s');

// IRT state
$exam_mode   = 'exam';
$irt_theta   = isset($body['irt_theta'])   ? floatval($body['irt_theta'])  : 0.0;
$irt_sem     = isset($body['irt_sem'])     ? floatval($body['irt_sem'])    : 1.0;
$irt_history = isset($body['irt_history']) ? $body['irt_history']          : null;
// Ensure irt_history is stored as a JSON string
if (is_array($irt_history)) $irt_history = json_encode($irt_history);

$ok = db()->execute(
    "INSERT INTO temporary_exam_state
        (student_id, examTaken, question_set, current_question, timer, updated_at,
         exam_mode, irt_theta, irt_sem, irt_history)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
        examTaken        = VALUES(examTaken),
        question_set     = VALUES(question_set),
        current_question = VALUES(current_question),
        timer            = VALUES(timer),
        updated_at       = VALUES(updated_at),
        exam_mode        = VALUES(exam_mode),
        irt_theta        = VALUES(irt_theta),
        irt_sem          = VALUES(irt_sem),
        irt_history      = VALUES(irt_history)",
    [
        $student_id, $examTaken, $question_set, $current_question, $timer, $updated_at,
        $exam_mode, $irt_theta, $irt_sem, $irt_history,
    ]
);

if ($ok) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'db_insert_failed']);
}
