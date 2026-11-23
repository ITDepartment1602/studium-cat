<?php
/**
 * preview_action.php — Prepares one raw question (import paste or queue row) for the device
 * preview. Runs the same validator and column mapping as insertQuestionRow(), so the preview
 * shows the row that approving this question would store.
 */
include('../../config.php');
require_once __DIR__ . '/question_validators.php';
require_once __DIR__ . '/../../includes/question_preview.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin_login'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Please log in again.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$type = $body['type'] ?? '';
$q    = $body['question'] ?? null;

if (!array_key_exists($type, $QUESTION_TABLE_MAP) || !is_array($q)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid preview request.']);
    exit;
}

$res = validateForType($type, $q);
if (!$res['ok']) {
    echo json_encode(['success' => false, 'error' => implode('; ', $res['errors'])]);
    exit;
}

$row = addPassthroughColumns($con, $QUESTION_TABLE_MAP[$type], $q, $res['data']);
echo json_encode(['success' => true, 'url' => storeQuestionPreview($type, $row)]);
