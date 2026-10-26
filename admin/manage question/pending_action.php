<?php
/**
 * pending_action.php — Backend for the question approval queue.
 * Accepts a POST JSON body: { action, type, ids: [], ... } and answers with JSON.
 *
 * Approving re-validates the stored payload from scratch and inserts it into that type's
 * real question table; the pending row is kept (status = approved) so there is an audit
 * trail of what was let into the question bank and when.
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

$action = $body['action'] ?? '';
$type   = $body['type']   ?? '';

if (!array_key_exists($type, $QUESTION_TABLE_MAP)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unknown question type.']);
    exit;
}
$table     = $QUESTION_TABLE_MAP[$type];
$reviewer  = (string)($_SESSION['admin_name'] ?? $_SESSION['admin_email'] ?? $_SESSION['admin_id'] ?? 'admin');

/** Ids from the request, constrained to this type so one type can never act on another's rows. */
function requestedIds(array $body, $con, string $type): array {
    $ids = array_values(array_unique(array_filter(array_map('intval', (array)($body['ids'] ?? [])))));
    if (!$ids) return [];
    $in   = implode(',', $ids);
    $safe = mysqli_real_escape_string($con, $type);
    $res  = mysqli_query($con, "SELECT id FROM `pending_questions` WHERE id IN ($in) AND `type` = '$safe'");
    $out  = [];
    if ($res) while ($r = mysqli_fetch_assoc($res)) $out[] = (int)$r['id'];
    return $out;
}

/* ════════════════════════════════════════════════
   APPROVE — ids[], or every still-pending valid row when approve_all is set
   ════════════════════════════════════════════════ */
if ($action === 'approve') {
    if (!empty($body['approve_all'])) {
        $safe = mysqli_real_escape_string($con, $type);
        $res  = mysqli_query($con, "SELECT id FROM `pending_questions` WHERE `type` = '$safe' AND status = 'pending' ORDER BY id ASC");
        $ids  = [];
        if ($res) while ($r = mysqli_fetch_assoc($res)) $ids[] = (int)$r['id'];
    } else {
        $ids = requestedIds($body, $con, $type);
    }
    if (!$ids) {
        echo json_encode(['success' => false, 'error' => 'No questions selected.']);
        exit;
    }

    $approved  = 0;
    $skipped   = 0;
    $errors    = [];
    $conflicts = [];

    // How the admin chose to settle each duplicate, keyed by pending id:
    // 'replace' overwrites the row already in the bank, 'keep_both' inserts anyway, 'skip' leaves it queued.
    $resolutions = is_array($body['resolutions'] ?? null) ? $body['resolutions'] : [];

    // Built once and kept current as rows are approved, so two identical questions inside the
    // same batch still collide with each other and not just with what was already in the bank.
    $keyMap = loadQuestionKeyMap($con, $table);

    $fetch  = $con->prepare("SELECT payload FROM `pending_questions` WHERE id = ? AND status = 'pending'");
    $mark   = $con->prepare("UPDATE `pending_questions` SET status = 'approved', imported_id = ?, reviewed_at = NOW(), reviewed_by = ?, reject_reason = NULL WHERE id = ?");

    foreach ($ids as $id) {
        $fetch->bind_param('i', $id);
        $fetch->execute();
        $row = $fetch->get_result()->fetch_assoc();
        if (!$row) { $skipped++; continue; }   // already reviewed by someone else

        $q = json_decode($row['payload'], true);
        if (!is_array($q)) { $skipped++; $errors[] = "#$id: stored payload is not readable."; continue; }

        $check = validateForType($type, $q);
        if (!$check['ok']) {
            $skipped++;
            $errors[] = "#$id: " . implode('; ', $check['errors']);
            continue;
        }

        $key    = normalizeQuestionKey($q['question'] ?? '');
        $dupId  = ($key !== '' && isset($keyMap[$key])) ? $keyMap[$key] : 0;
        $choice = $dupId ? ($resolutions[$id] ?? null) : null;

        // A duplicate is never approved silently — the admin has to see both versions and decide.
        if ($dupId && $choice === null) {
            $existingRow = $con->query("SELECT * FROM `$table` WHERE id = " . (int)$dupId)->fetch_assoc();
            $conflicts[] = [
                'pending_id'  => $id,
                'existing_id' => $dupId,
                'question'    => (string)($q['question'] ?? ''),
                'incoming'    => $check['data'],
                'existing'    => $existingRow ?: [],
            ];
            continue;
        }
        if ($dupId && $choice === 'skip') { $skipped++; continue; }

        $res = ($dupId && $choice === 'replace')
            ? updateQuestionRow($con, $type, $table, $q, $dupId)
            : insertQuestionRow($con, $type, $table, $q);

        if (!$res['ok']) {
            $skipped++;
            $errors[] = "#$id: " . implode('; ', $res['errors']);
            continue;
        }

        if ($key !== '') $keyMap[$key] = $res['id'];
        $mark->bind_param('isi', $res['id'], $reviewer, $id);
        $mark->execute();
        $approved++;
    }

    echo json_encode([
        'success'   => $approved > 0 || !empty($conflicts),
        'approved'  => $approved,
        'skipped'   => $skipped,
        'conflicts' => $conflicts,
        'error'     => ($approved > 0 || $conflicts) ? null : (implode(' | ', array_slice($errors, 0, 3)) ?: 'Nothing could be approved.'),
        'details'   => $errors,
    ]);
    exit;
}

/* ════════════════════════════════════════════════
   REJECT — keeps the row, records why
   ════════════════════════════════════════════════ */
if ($action === 'reject') {
    $ids = requestedIds($body, $con, $type);
    if (!$ids) { echo json_encode(['success' => false, 'error' => 'No questions selected.']); exit; }

    $reason = trim((string)($body['reason'] ?? ''));
    $stmt   = $con->prepare("UPDATE `pending_questions` SET status = 'rejected', reject_reason = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ? AND status <> 'approved'");
    $n = 0;
    foreach ($ids as $id) {
        $stmt->bind_param('ssi', $reason, $reviewer, $id);
        if ($stmt->execute()) $n += $stmt->affected_rows;
    }
    echo json_encode(['success' => $n > 0, 'rejected' => $n, 'error' => $n > 0 ? null : 'Nothing was rejected.']);
    exit;
}

/* ════════════════════════════════════════════════
   RESTORE — put a rejected row back in the queue
   ════════════════════════════════════════════════ */
if ($action === 'restore') {
    $ids = requestedIds($body, $con, $type);
    if (!$ids) { echo json_encode(['success' => false, 'error' => 'No questions selected.']); exit; }

    $stmt = $con->prepare("UPDATE `pending_questions` SET status = 'pending', reject_reason = NULL, reviewed_at = NULL, reviewed_by = NULL WHERE id = ? AND status = 'rejected'");
    $n = 0;
    foreach ($ids as $id) {
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) $n += $stmt->affected_rows;
    }
    echo json_encode(['success' => $n > 0, 'restored' => $n, 'error' => $n > 0 ? null : 'Nothing was restored.']);
    exit;
}

/* ════════════════════════════════════════════════
   DELETE — drops the staging row only; an approved row's
   copy in the real question table is left untouched
   ════════════════════════════════════════════════ */
if ($action === 'delete') {
    $ids = requestedIds($body, $con, $type);
    if (!$ids) { echo json_encode(['success' => false, 'error' => 'No questions selected.']); exit; }

    $stmt = $con->prepare("DELETE FROM `pending_questions` WHERE id = ?");
    $n = 0;
    foreach ($ids as $id) {
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) $n += $stmt->affected_rows;
    }
    echo json_encode(['success' => $n > 0, 'deleted' => $n, 'error' => $n > 0 ? null : 'Nothing was deleted.']);
    exit;
}

/* ════════════════════════════════════════════════
   UPDATE — save an edited payload back to the queue
   ════════════════════════════════════════════════ */
if ($action === 'update') {
    $ids = requestedIds($body, $con, $type);
    $id  = $ids[0] ?? 0;
    if (!$id) { echo json_encode(['success' => false, 'error' => 'Question not found.']); exit; }

    $q = $body['payload'] ?? null;
    if (!is_array($q)) { echo json_encode(['success' => false, 'error' => 'Edited question must be a JSON object.']); exit; }

    $payload  = json_encode($q, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $question = trim((string)($q['question'] ?? ''));
    $topic    = trim((string)($q['topic']    ?? ''));
    $concept  = trim((string)($q['concept']  ?? ''));
    $dlevel   = normalizeDlevel($q['dlevel'] ?? '');

    $stmt = $con->prepare(
        "UPDATE `pending_questions`
         SET payload = ?, question = ?, topic = ?, concept = ?, dlevel = ?, status = 'pending', reject_reason = NULL
         WHERE id = ? AND status <> 'approved'"
    );
    $stmt->bind_param('sssssi', $payload, $question, $topic, $concept, $dlevel, $id);
    if (!$stmt->execute()) { echo json_encode(['success' => false, 'error' => $stmt->error]); exit; }
    if ($stmt->affected_rows === 0) { echo json_encode(['success' => false, 'error' => 'This question was already approved and can no longer be edited here.']); exit; }

    $check = validateForType($type, $q);
    echo json_encode([
        'success'  => true,
        'is_valid' => $check['ok'],
        'errors'   => $check['ok'] ? [] : $check['errors'],
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Unknown action.']);
