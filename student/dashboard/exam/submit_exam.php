<?php
// Exam Mode — Transfer temp results → exammoderesults with terminal IRT row
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

if (!is_array($body) || !isset($body['examTaken'])) {
    error_log('submit_exam invalid payload: ' . $rawBody);
    http_response_code(400);
    echo json_encode(['error' => 'invalid payload']);
    exit;
}

$examTaken = intval($body['examTaken']);

// IRT / termination fields from the client
$termination_reason  = isset($body['termination_reason']) ? $body['termination_reason']       : 'manual_finish';
$final_result        = isset($body['final_result'])       ? strtoupper($body['final_result'])  : null;
$final_percent       = isset($body['final_percent'])      ? floatval($body['final_percent'])   : null;
$final_theta         = isset($body['final_theta'])        ? floatval($body['final_theta'])     : null;
$final_sem           = isset($body['final_sem'])          ? floatval($body['final_sem'])       : null;
$total_items         = isset($body['total_items'])        ? intval($body['total_items'])       : null;
$exam_duration       = isset($body['exam_duration'])      ? intval($body['exam_duration'])     : null;
$is_auto             = isset($body['is_auto_terminate'])  ? (bool)$body['is_auto_terminate']   : false;
$selected_concepts   = isset($body['selected_concepts']) && is_array($body['selected_concepts'])
                       ? json_encode(array_values(array_map('strval', $body['selected_concepts'])))
                       : null;

// Validate allowed termination reasons (whitelist)
$allowed_reasons = ['irt_pass','irt_fail','mercy_rule','completed_max','time_expired','time_expired_insufficient','pool_exhausted','manual_finish'];
if (!in_array($termination_reason, $allowed_reasons, true)) $termination_reason = 'manual_finish';

// Ownership: ensure this examTaken belongs to this student
$tempData = db()->fetchOne(
    "SELECT COUNT(*) AS cnt FROM exammoderesults WHERE student_id = ? AND examTaken = ?",
    [$student_id, $examTaken]
);

if (!$tempData || $tempData['cnt'] == 0) {
    db()->execute("DELETE FROM temporary_exam_state WHERE student_id = ? AND exam_mode = 'exam'", [$student_id]);
    unset($_SESSION['current_exam_examTaken']);
    echo json_encode(['ok' => true, 'message' => 'no data (already submitted?)']);
    exit;
}

$conn = db()->getConnection();
$conn->begin_transaction();

try {
    $lastRow = db()->fetchOne(
        "SELECT id FROM exammoderesults WHERE student_id = ? AND examTaken = ? ORDER BY question_number DESC LIMIT 1",
        [$student_id, $examTaken]
    );

    if ($lastRow) {
        $conn->query(sprintf(
            "UPDATE exammoderesults SET
                is_terminal = 1,
                termination_reason = %s,
                final_result = %s,
                final_percent = %s,
                final_theta = %s,
                final_sem = %s,
                total_items_answered = %s,
                exam_duration_sec = %s,
                selected_concepts = %s
             WHERE id = %d",
            $conn->real_escape_string("'" . $termination_reason . "'"),
            $final_result ? "'" . $conn->real_escape_string($final_result) . "'" : 'NULL',
            $final_percent !== null ? $final_percent : 'NULL',
            $final_theta !== null ? $final_theta : 'NULL',
            $final_sem !== null ? $final_sem : 'NULL',
            $total_items !== null ? $total_items : 'NULL',
            $exam_duration !== null ? $exam_duration : 'NULL',
            $selected_concepts ? "'" . $conn->real_escape_string($selected_concepts) . "'" : 'NULL',
            intval($lastRow['id'])
        ));
        if ($conn->error) throw new Exception("Update terminal row failed: " . $conn->error);
    }

    db()->execute(
        "DELETE FROM temporary_exam_state WHERE student_id = ? AND exam_mode = 'exam'",
        [$student_id]
    );

    $conn->commit();

    unset($_SESSION['current_exam_examTaken']);

    echo json_encode([
        'ok'          => true,
        'final_result'=> $final_result,
        'final_pct'   => $final_percent,
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log('submit_exam failed for student ' . $student_id . ' examTaken ' . $examTaken . ': ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Transfer failed: ' . $e->getMessage()]);
}
