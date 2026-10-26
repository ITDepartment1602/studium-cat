<?php
/**
 * import_precheck.php — Database-aware checks run by the Validate Query button.
 *
 * The client-side validators only know whether a question is well-formed on its own. This
 * endpoint answers the two questions that need the database: has this question already been
 * stored (or already queued), and is any column the type's table cares about left empty.
 * Catching both here means nothing reaches the approval queue that would duplicate or
 * under-populate the live question bank.
 *
 * POST JSON: { type, questions: [ {...}, ... ] }
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

if (!array_key_exists($type, $QUESTION_TABLE_MAP) || !is_array($questions)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid type or questions.']);
    exit;
}
$table = $QUESTION_TABLE_MAP[$type];

/**
 * Metadata the platform actually reads back: topic/concept drive filtering and reporting,
 * system/cnc/narcan are the NGN classification, and difficulty_logit feeds adaptive selection.
 * Anything left empty here still imports, but degrades those features later.
 */
$IMPORTANT_FIELDS = [
    'topic'            => 'Topic',
    'concept'          => 'Concept',
    'system'           => 'System',
    'cnc'              => 'CNC',
    'narcan'           => 'NARC / additional notes',
    'difficulty_logit' => 'Difficulty logit (adaptive exam uses this)',
];

$tableCols = getTableColumns($con, $table);

/* ── What is already in the live bank, and what is already queued ── */
$bankMap = loadQuestionKeyMap($con, $table);

$queuedMap = [];
$stmt = $con->prepare("SELECT id, question FROM `pending_questions` WHERE `type` = ? AND status = 'pending'");
$stmt->bind_param('s', $type);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $key = normalizeQuestionKey($r['question']);
    if ($key !== '' && !isset($queuedMap[$key])) $queuedMap[$key] = (int)$r['id'];
}

/* ── Per-question verdict ── */
$out       = [];
$seenInRun = [];   // normalized key => the row index that first used it

foreach ($questions as $i => $q) {
    $row = [
        'index'          => $i,
        'duplicate_of'   => 0,    // id in the live question table
        'queued_as'      => 0,    // id already sitting in the approval queue
        'repeat_of_row'  => -1,   // an earlier row in this same paste
        'missing'        => [],
    ];

    if (!is_array($q)) { $out[] = $row; continue; }

    $key = normalizeQuestionKey($q['question'] ?? '');
    if ($key !== '') {
        if (isset($bankMap[$key]))   $row['duplicate_of'] = $bankMap[$key];
        if (isset($queuedMap[$key])) $row['queued_as']    = $queuedMap[$key];
        if (isset($seenInRun[$key])) $row['repeat_of_row'] = $seenInRun[$key];
        else $seenInRun[$key] = $i;
    }

    foreach ($IMPORTANT_FIELDS as $col => $label) {
        if (!in_array($col, $tableCols, true)) continue;   // this type's table has no such column
        $v = $q[$col] ?? null;
        // 0 is a real difficulty_logit value, so only a truly absent field counts as missing.
        $empty = $v === null || (is_string($v) && trim($v) === '') || (is_array($v) && !$v);
        if ($empty) $row['missing'][] = $label;
    }

    $out[] = $row;
}

echo json_encode(['success' => true, 'rows' => $out]);
