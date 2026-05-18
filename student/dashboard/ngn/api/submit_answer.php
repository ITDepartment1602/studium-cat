<?php
/**
 * ============================================================
 *  NGN SUBMIT ANSWER API
 * ============================================================
 *  Handles answer submission with:
 *  - Initial vs Final response tracking
 *  - Partial scoring via ScoringEngine
 *  - CAT theta ability updates
 *  - Omitted answer detection
 * ============================================================
 */

require_once '../../../../config.php';
header('Content-Type: application/json');

// Verify authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$student_id = intval($_SESSION['user_id']);

// Parse input (support both POST and JSON body)
$input = $_POST;
if (empty($input)) {
    $json = file_get_contents('php://input');
    $decoded = json_decode($json, true);
    $input = is_array($decoded) ? $decoded : [];
}

// Required fields
$examTaken = isset($input['examTaken']) ? intval($input['examTaken']) : 0;
$question_uid = isset($input['question_uid']) ? trim($input['question_uid']) : '';
$question_type = isset($input['question_type']) ? strtolower(trim($input['question_type'])) : '';
$question_id = isset($input['question_id']) ? intval($input['question_id']) : 0;
$user_answer = $input['user_answer'] ?? null;
$correct_answer = $input['correct_answer'] ?? null;
$time_taken = isset($input['time_taken']) ? intval($input['time_taken']) : 0;
$question_number = isset($input['question_number']) ? intval($input['question_number']) : 0;

if (!$examTaken || !$question_uid || !$question_type) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: examTaken, question_uid, question_type']);
    exit;
}

// Verify this examTaken belongs to this student
$ownerCheck = db()->fetchOne(
    "SELECT student_id FROM temporary_exam_state WHERE student_id = ? AND examTaken = ?",
    [$student_id, $examTaken]
);
if (!$ownerCheck) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied: exam not found for this student']);
    exit;
}

// Check for omitted answer
$isOmitted = ScoringEngine::isOmitted($user_answer);

// Score the answer using centralized engine
$scoreResult = ScoringEngine::score($question_type, $user_answer, $correct_answer);
$earned_points = $scoreResult['earned'];
$max_points = $scoreResult['max'];
$isCorrect = $scoreResult['isCorrect'];

// Check for existing record to handle initial vs final tracking
$existing = db()->fetchOne(
    "SELECT id, initial_answer, changes_count, user_answer as prev_answer 
     FROM temporary_exam_result 
     WHERE student_id = ? AND examTaken = ? AND question_uid = ?",
    [$student_id, $examTaken, $question_uid]
);

// Determine initial_answer and changes_count
$initial_answer = null;
$changes_count = 0;

if ($existing) {
    // Existing record - keep original initial_answer
    $initial_answer = $existing['initial_answer'];
    $changes_count = intval($existing['changes_count'] ?? 0);
    
    // Check if answer changed from previous
    if (ScoringEngine::hasChanged($existing['prev_answer'], $user_answer)) {
        $changes_count++;
    }
} else {
    // First submission - this IS the initial answer
    $initial_answer = is_string($user_answer) ? $user_answer : json_encode($user_answer);
}

// Fetch question metadata if not provided
$rationale = $input['rationale'] ?? 'No rationale provided.';
$topic = $input['topic'] ?? 'N/A';
$system = $input['system'] ?? 'N/A';
$cnc = $input['cnc'] ?? 'N/A';
$dlevel = $input['dlevel'] ?? 'N/A';
$narcan = $input['narcan'] ?? null;
$concept = $input['concept'] ?? null;
$questionDifficulty = 0.0; // default if column absent or metadata not fetched

$tableMap = [
    'highlight' => 'highlight',
    'traditional' => 'traditional',
    'mc' => 'traditional',
    'bowtie' => 'btq',
    'bt' => 'btq',
    'mmr' => 'mmr',
    'mpr' => 'mpr',
    'sata' => 'sata',
    'dropdown' => 'dropdown',
    'ddl' => 'dropdown',
    'dragndrop' => 'dragndrop',
    'column' => 'column'
];

// If metadata not provided, try to fetch from appropriate table
if (!isset($input['rationale']) && $question_id > 0) {
    if (isset($tableMap[$question_type])) {
        $table = $tableMap[$question_type];
        $qData = db()->fetchOne("SELECT rationale, topic, system, cnc, dlevel, difficulty_logit, narcan, concept FROM `{$table}` WHERE id = ? LIMIT 1", [$question_id]);
    } else {
        $qData = null;
    }
    if ($qData) {
        $rationale = $qData['rationale'] ?? $rationale;
        $topic = $qData['topic'] ?? $topic;
        $system = $qData['system'] ?? $system;
        $cnc = $qData['cnc'] ?? $cnc;
        $dlevel = $qData['dlevel'] ?? $dlevel;
        $narcan = $qData['narcan'] ?? $narcan;
        $concept = $qData['concept'] ?? $concept;
        $questionDifficulty = isset($qData['difficulty_logit']) ? floatval($qData['difficulty_logit']) : 0.0;
    }
}

// Always ensure concept is populated — fetch from question table when not in client payload
if ($concept === null && isset($tableMap[$question_type])) {
    // Resolve numeric ID: prefer explicit question_id, fall back to parsing question_uid (format: "type-NNN")
    $resolvedId = $question_id > 0 ? $question_id : 0;
    if ($resolvedId === 0 && preg_match('/-(\d+)$/', $question_uid, $m)) {
        $resolvedId = (int)$m[1];
    }
    if ($resolvedId > 0) {
        $cRow = db()->fetchOne("SELECT concept FROM `{$tableMap[$question_type]}` WHERE id = ? LIMIT 1", [$resolvedId]);
        if ($cRow) $concept = $cRow['concept'] ?? null;
    }
}

// Convert answers to JSON strings for storage
$user_answer_json = is_string($user_answer) ? $user_answer : json_encode($user_answer);
$correct_answer_json = is_string($correct_answer) ? $correct_answer : json_encode($correct_answer);

// Build changes JSON for audit trail
$changes_json = null;
if ($existing && ScoringEngine::hasChanged($existing['prev_answer'] ?? '', $user_answer)) {
    $changes_json = json_encode([
        'previous' => $existing['prev_answer'] ?? null,
        'count'    => $changes_count,
    ]);
}

// Wrap all DB writes in a transaction
$conn = db()->getConnection();
$conn->begin_transaction();

try {
    // UPSERT the answer
    if ($existing) {
        // Update existing record
        $ok = db()->execute(
            "UPDATE temporary_exam_result
             SET user_answer = ?,
                 correct_answer = ?,
                 isCorrect = ?,
                 score = ?,
                 earned_points = ?,
                 max_points = ?,
                 omitted = ?,
                 changes_count = ?,
                 changes = ?,
                 time_taken = ?,
                 totalTime = totalTime + ?,
                 timestamp = NOW()
             WHERE id = ?",
            [
                $user_answer_json,
                $correct_answer_json,
                $isCorrect ? 1 : 0,
                $earned_points / max($max_points, 1), // normalized score 0-1
                $earned_points,
                $max_points,
                $isOmitted ? 1 : 0,
                $changes_count,
                $changes_json,
                $time_taken,
                $time_taken,
                $existing['id']
            ]
        );
        if (!$ok) throw new Exception('Result update failed');
    } else {
        // Insert new record
        $ok = db()->execute(
            "INSERT INTO temporary_exam_result
             (student_id, examTaken, question_uid, question_type, question_id,
              user_answer, correct_answer, initial_answer, changes, isCorrect, score,
              earned_points, max_points, omitted, changes_count,
              rationale, topic, system, cnc, dlevel, narcan, concept,
              time_taken, totalTime, question_number)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $student_id,
                $examTaken,
                $question_uid,
                $question_type,
                $question_id,
                $user_answer_json,
                $correct_answer_json,
                $initial_answer,
                $changes_json,
                $isCorrect ? 1 : 0,
                $earned_points / max($max_points, 1),
                $earned_points,
                $max_points,
                $isOmitted ? 1 : 0,
                $changes_count,
                $rationale,
                $topic,
                $system,
                $cnc,
                $dlevel,
                $narcan,
                $concept,
                $time_taken,
                $time_taken,
                $question_number
            ]
        );
        if (!$ok) throw new Exception('Result insert failed');
    }

    // CAT: Update theta ability and standard error
    $examState = db()->fetchOne(
        "SELECT theta_ability, standard_error, question_count
         FROM temporary_exam_state
         WHERE student_id = ? AND examTaken = ?",
        [$student_id, $examTaken]
    );

    $newTheta = null;
    $newSE = null;
    $questionCount = null;
    $stoppingResult = null;

    if ($examState) {
        $currentTheta = floatval($examState['theta_ability']);
        $questionCount = intval($examState['question_count']) + 1;

        // Update theta based on correctness and question difficulty
        $newTheta = ScoringEngine::updateTheta($currentTheta, $isCorrect, $questionDifficulty);
        $newSE = ScoringEngine::calculateStandardError($questionCount);

        // Check stopping rule
        $stoppingResult = ScoringEngine::checkStoppingRule($newTheta, $newSE, 0.0);

        $ok2 = db()->execute(
            "UPDATE temporary_exam_state
             SET theta_ability = ?,
                 standard_error = ?,
                 question_count = ?,
                 updated_at = NOW()
             WHERE student_id = ? AND examTaken = ?",
            [$newTheta, $newSE, $questionCount, $student_id, $examTaken]
        );
        if (!$ok2) throw new Exception('State update failed');
    }

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Transaction failed']);
    exit;
}

// Prepare response
$response = [
    'success' => true,
    'question_uid' => $question_uid,
    'isCorrect' => $isCorrect,
    'earned_points' => $earned_points,
    'max_points' => $max_points,
    'isOmitted' => $isOmitted,
    'changes_count' => $changes_count,
    'rationale' => $rationale,
    'topic' => $topic,
    'system' => $system,
    'cnc' => $cnc,
    'dlevel' => $dlevel,
    'time_taken' => $time_taken
];

// Include CAT info if available
if ($newTheta !== null) {
    $response['cat'] = [
        'theta_ability' => round($newTheta, 3),
        'standard_error' => round($newSE, 3),
        'question_count' => $questionCount,
        'stopping_rule' => $stoppingResult
    ];
}

echo json_encode($response);
