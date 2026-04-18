<?php
/**
 * ============================================================
 *  CAT ADAPTIVE QUESTION SELECTION API
 * ============================================================
 *  Selects next question based on student's theta (ability)
 *  Returns question with difficulty closest to current theta
 * ============================================================
 */

require_once '../../../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$student_id = intval($_SESSION['user_id']);

// Parse input
$input = $_POST;
if (empty($input)) {
    $json = file_get_contents('php://input');
    $decoded = json_decode($json, true);
    $input = is_array($decoded) ? $decoded : [];
}

$examTaken = isset($input['examTaken']) ? intval($input['examTaken']) : 0;
$questionType = isset($input['question_type']) ? strtolower(trim($input['question_type'])) : '';

if (!$examTaken || !$questionType) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: examTaken, question_type']);
    exit;
}

// Get current CAT state
$examState = db()->fetchOne(
    "SELECT theta_ability, question_count 
     FROM temporary_exam_state 
     WHERE student_id = ? AND examTaken = ?",
    [$student_id, $examTaken]
);

$theta = $examState ? floatval($examState['theta_ability']) : 0.0;

// Map question types to tables
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

if (!isset($tableMap[$questionType])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid question type']);
    exit;
}
$table = $tableMap[$questionType];

// Check if table has difficulty_logit column
$hasDifficulty = false;
$checkCol = db()->query("SHOW COLUMNS FROM `{$table}` LIKE 'difficulty_logit'");
$hasDifficulty = $checkCol && $checkCol->num_rows > 0;

// Get already answered questions
$answered = db()->fetchAll(
    "SELECT question_id FROM temporary_exam_result 
     WHERE student_id = ? AND examTaken = ?",
    [$student_id, $examTaken]
);

$answeredIds = array_column($answered, 'question_id');

// Build query
$params = [];
$whereClause = '';

if (!empty($answeredIds)) {
    $placeholders = implode(',', array_fill(0, count($answeredIds), '?'));
    $whereClause = "WHERE id NOT IN ({$placeholders})";
    $params = $answeredIds;
}

// Adaptive selection
if ($hasDifficulty) {
    $sql = "SELECT *, ABS(COALESCE(difficulty_logit, 0) - ?) as diff_distance
            FROM `{$table}`
            {$whereClause}
            ORDER BY diff_distance ASC, RAND()
            LIMIT 1";
    array_unshift($params, $theta);
    $question = db()->fetchOne($sql, $params);
} else {
    $sql = "SELECT * FROM `{$table}` {$whereClause} ORDER BY RAND() LIMIT 1";
    $question = db()->fetchOne($sql, $params);
}

if (!$question) {
    echo json_encode([
        'success' => false,
        'message' => 'No more questions available for this type',
        'cat' => [
            'theta_ability' => round($theta, 3),
            'question_count' => $examState ? intval($examState['question_count']) : 0
        ]
    ]);
    exit;
}

// Build response
$response = [
    'success' => true,
    'id' => $question['id'],
    'topic' => $question['topic'] ?? 'General',
    'question' => $question['question'] ?? '',
    'difficulty_logit' => isset($question['difficulty_logit']) ? floatval($question['difficulty_logit']) : 0.0,
    'cat' => [
        'theta_ability' => round($theta, 3),
        'question_count' => $examState ? intval($examState['question_count']) : 0
    ]
];

// Add type-specific fields
if ($questionType === 'highlight') {
    $response['passage'] = $question['passage'] ?? '';
}

$response['rationale'] = $question['rationale'] ?? 'No rationale provided.';
$response['system'] = $question['system'] ?? 'N/A';
$response['cnc'] = $question['cnc'] ?? 'N/A';
$response['dlevel'] = $question['dlevel'] ?? 'N/A';

echo json_encode($response);
