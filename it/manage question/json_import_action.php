<?php
/**
 * json_import_action.php — Bulk JSON question importer.
 * Accepts POST JSON body: { type: "traditional"|"sata"|..., questions: [ {...}, ... ] }
 *
 * Questions are staged in `pending_questions` rather than written straight to the live
 * question bank — an admin approves them from pending.php, which re-validates and copies
 * each row into the real table.  Rows that fail validation are still staged so they can be
 * repaired in the pending editor instead of forcing a re-paste of the whole import.
 */
include('../../config.php');
require_once __DIR__ . '/question_validators.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin_login'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Not authorized.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request body.']);
    exit;
}

$type      = $body['type'] ?? '';
$questions = $body['questions'] ?? [];

if (!array_key_exists($type, $QUESTION_TABLE_MAP) || !is_array($questions) || count($questions) === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid type or no questions supplied.']);
    exit;
}

$batchId  = bin2hex(random_bytes(8));
$queued   = 0;
$failed   = 0;
$errorsOut = [];

$stmt = $con->prepare(
    "INSERT INTO `pending_questions` (`type`, `payload`, `question`, `topic`, `concept`, `dlevel`, `batch_id`)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not open the pending queue: ' . $con->error]);
    exit;
}

foreach ($questions as $i => $q) {
    if (!is_array($q)) { $failed++; $errorsOut[] = "Row $i: not a valid object."; continue; }

    $payload  = json_encode($q, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $question = trim((string)($q['question'] ?? ''));
    $topic    = trim((string)($q['topic']    ?? ''));
    $concept  = trim((string)($q['concept']  ?? ''));
    $dlevel   = normalizeDlevel($q['dlevel'] ?? '');

    $stmt->bind_param('sssssss', $type, $payload, $question, $topic, $concept, $dlevel, $batchId);
    if ($stmt->execute()) $queued++;
    else { $failed++; $errorsOut[] = "Row $i: " . $stmt->error; }
}

$success = $queued > 0;
echo json_encode([
    'success'  => $success,
    'imported' => $queued,
    'queued'   => $queued,
    'failed'   => $failed,
    'batch_id' => $batchId,
    'error'    => $success ? null : (implode(' | ', array_slice($errorsOut, 0, 5)) ?: 'No rows could be queued.'),
    'details'  => $errorsOut,
]);
