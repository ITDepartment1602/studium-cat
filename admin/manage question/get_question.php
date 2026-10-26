<?php
/**
 * get_question.php — Returns a single question row as JSON for the edit modal.
 * GET params: type, id
 */
include('../../config.php');
header('Content-Type: application/json');

$tableMap = [
    'traditional' => 'mcq',
    'sata'        => 'sata',
    'mpr'         => 'mpr',
    'mmr'         => 'mmr',
    'btq'         => 'btq',
    'dragndrop'   => 'dragndrop',
    'dropdown'    => 'dropdown_questions',
    'highlight'   => 'highlight',
];

$type = $_GET['type'] ?? '';
$id   = (int)($_GET['id'] ?? 0);

if (!array_key_exists($type, $tableMap) || !$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid type or id']);
    exit;
}

$table = $tableMap[$type];
$stmt  = $con->prepare("SELECT * FROM `$table` WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res  = $stmt->get_result();
$row  = $res->fetch_assoc();

if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

// Decode JSON fields so the frontend receives proper arrays/objects
$jsonFields = ['choices', 'items', 'correct', 'columns', 'rows', 'tabs',
               'actionToTake', 'potentialConditions', 'parameterToMonitor'];

foreach ($jsonFields as $f) {
    if (isset($row[$f]) && is_string($row[$f]) && $row[$f] !== '') {
        $decoded = json_decode($row[$f], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $row[$f] = $decoded;
        }
    }
}

echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
