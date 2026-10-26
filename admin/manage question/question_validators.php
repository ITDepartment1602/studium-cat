<?php
/**
 * question_validators.php — Server-side source of truth for question shape.
 * Shared by json_import_action.php (paste -> pending) and pending_action.php (pending -> real table),
 * so a question is validated by identical rules no matter which door it came through.
 */

$QUESTION_TABLE_MAP = [
    'traditional' => 'mcq',
    'sata'      => 'sata',
    'mpr'       => 'mpr',
    'mmr'       => 'mmr',
    'btq'       => 'btq',
    'dragndrop' => 'dragndrop',
    'dropdown'  => 'dropdown_questions',
    'highlight' => 'highlight',
];

$QUESTION_TYPE_LABELS = [
    'traditional' => 'Multiple Choice (MCQ)',
    'sata'      => 'SATA — Select All That Apply',
    'mpr'       => 'Matrix / Priority Response',
    'mmr'       => 'Matrix Multiple Response',
    'btq'       => 'Bow-Tie',
    'dragndrop' => 'Drag & Drop',
    'dropdown'  => 'Dropdown Cloze',
    'highlight' => 'Highlight',
];

/* ════════════════════════════════════════════════
   Shared helpers
   ════════════════════════════════════════════════ */

function normalizeDlevel($raw): string {
    $map = ['easy' => 'Easy', 'moderate' => 'Medium', 'medium' => 'Medium', 'med' => 'Medium', 'hard' => 'Hard'];
    $norm = $map[strtolower(trim((string)$raw))] ?? '';
    return $norm !== '' ? $norm : 'Medium';
}

function normalizeFurtherinfo($raw): string {
    if (is_array($raw)) {
        return json_encode(array_values(array_map(fn($v) => (string)$v, $raw)), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    return trim((string)$raw);
}

function normalizeTabs($raw): string {
    $tabsRaw = is_array($raw) ? $raw : [];
    $out = [];
    foreach ($tabsRaw as $t) {
        if (!is_array($t) || empty($t['title'])) continue;
        $content = is_array($t['content'] ?? null) ? array_values(array_map(fn($v) => (string)$v, $t['content'])) : [];
        $out[] = ['title' => (string)$t['title'], 'content' => $content];
    }
    return json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

/** Fields common to every NGN question type. */
function commonFields(array $q): array {
    return [
        'question'         => trim((string)($q['question']  ?? '')),
        'rationale'        => trim((string)($q['rationale'] ?? '')),
        'topic'            => trim((string)($q['topic']     ?? '')),
        'concept'          => trim((string)($q['concept']   ?? '')),
        'dlevel'           => normalizeDlevel($q['dlevel'] ?? ''),
        'system'           => trim((string)($q['system']    ?? '')),
        'cnc'              => trim((string)($q['cnc']       ?? '')),
        'narcan'           => trim((string)($q['narcan']    ?? '')),
        'difficulty_logit' => is_numeric($q['difficulty_logit'] ?? null) ? (float)$q['difficulty_logit'] : 0.0,
        'furtherinfo'      => normalizeFurtherinfo($q['furtherinfo'] ?? ''),
        'image'            => trim((string)($q['image']     ?? '')),
        'tabs'             => normalizeTabs($q['tabs'] ?? []),
    ];
}

/** Comma / newline / JSON-array string or array -> flat array of trimmed non-empty strings. */
function parseFlexList($raw): array {
    if (is_array($raw)) {
        return array_values(array_filter(array_map(fn($v) => trim((string)$v), $raw), fn($v) => $v !== ''));
    }
    $raw = trim((string)$raw);
    if ($raw === '') return [];
    $parts = strpos($raw, "\n") !== false ? preg_split('/\r\n|\r|\n/', $raw) : explode(',', $raw);
    return array_values(array_filter(array_map('trim', $parts), fn($v) => $v !== ''));
}

/** "A. text\nB. text" (or array of such lines) -> [['letter'=>'A','text'=>'text'], ...]. letter is null if the line didn't match. */
function parseLetteredLines($raw): array {
    $raw = is_array($raw) ? implode("\n", $raw) : (string)$raw;
    $raw = preg_replace('/\r\n?/', "\n", trim($raw));
    $lines = array_values(array_filter(array_map('trim', explode("\n", $raw)), fn($l) => $l !== ''));
    $out = [];
    foreach ($lines as $line) {
        if (preg_match('/^([A-Za-z])[.)]\s*(.+)$/', $line, $m)) $out[] = ['letter' => strtoupper($m[1]), 'text' => trim($m[2])];
        else $out[] = ['letter' => null, 'text' => $line];
    }
    return $out;
}

/** "A,C,E" (or array) -> ['A','C','E'] (uppercased, trimmed, deduped-order-preserved not required). */
function parseLetters($raw): array {
    $raw = is_array($raw) ? implode(',', $raw) : (string)$raw;
    return array_values(array_filter(array_map(fn($v) => strtoupper(trim($v)), explode(',', $raw)), fn($v) => $v !== ''));
}

/** Real column names of a table, cached per request so bulk imports only query once per table. */
function getTableColumns($con, string $table): array {
    static $cache = [];
    if (isset($cache[$table])) return $cache[$table];
    $cols = [];
    $safe = mysqli_real_escape_string($con, $table);
    $res = mysqli_query($con, "SHOW COLUMNS FROM `$safe`");
    if ($res) while ($row = mysqli_fetch_assoc($res)) $cols[] = $row['Field'];
    $cache[$table] = $cols;
    return $cols;
}

/**
 * Fills in any column that exists in the table but isn't produced by the type validator
 * (e.g. a source SQL/JSON dump that carries extra fields like `item`/`targets`), so nothing
 * the admin supplies is silently dropped just because this importer doesn't otherwise use it.
 */
function addPassthroughColumns($con, string $table, array $q, array $d): array {
    foreach (getTableColumns($con, $table) as $col) {
        if ($col === 'id') continue;               // never let admin input set the primary key
        if (array_key_exists($col, $d)) continue;   // already produced by the type validator
        if (!array_key_exists($col, $q)) continue;  // admin didn't supply this field at all
        $val = $q[$col];
        if ($val === null) continue;                // leave column at its DB default
        if (is_array($val)) $d[$col] = json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        elseif (is_bool($val)) $d[$col] = $val ? 1 : 0;
        else $d[$col] = (string)$val;
    }
    return $d;
}

/**
 * Resolves a "pick the correct ones" section from any of three accepted shapes:
 *   1) an array of objects: [{"text": "...", "correct": true}, ...] — no letters needed, correctness is per-item.
 *   2) a plain array of option text with no letters: items = ["text1","text2",...], correct = the matching
 *      subset of that same text (array, or comma/newline string) — letters are auto-assigned A, B, C...
 *   3) the lettered-string convention: items = "A. text\nB. text", correct = "A,C" (separate field).
 * Returns ['entries' => [['letter','text','correct'], ...], 'errors' => string[]].
 */
function resolveLetteredSection($itemsRaw, $correctRaw): array {
    $errors = [];
    $entries = [];
    $isObjectArray = is_array($itemsRaw) && !empty($itemsRaw) && is_array($itemsRaw[0] ?? null) && array_key_exists('text', $itemsRaw[0]);

    $isPlainTextArray = false;
    if (!$isObjectArray && is_array($itemsRaw) && !empty($itemsRaw)) {
        $allScalar = true;
        $anyLettered = false;
        foreach ($itemsRaw as $it) {
            if (is_array($it)) { $allScalar = false; break; }
            if (preg_match('/^([A-Za-z])[.)]\s*(.+)$/', trim((string)$it))) $anyLettered = true;
        }
        $isPlainTextArray = $allScalar && !$anyLettered;
    }

    if ($isObjectArray) {
        $letters = range('A', 'Z');
        foreach ($itemsRaw as $idx => $it) {
            $text = trim((string)($it['text'] ?? ''));
            if ($text === '') { $errors[] = 'every item needs non-empty "text"'; break; }
            $entries[] = ['letter' => $letters[$idx] ?? ('X' . $idx), 'text' => $text, 'correct' => !empty($it['correct'])];
        }
    } elseif ($isPlainTextArray) {
        $letters = range('A', 'Z');
        $correctList = array_map(fn($v) => strtolower(trim((string)$v)), is_array($correctRaw) ? $correctRaw : parseFlexList($correctRaw ?? ''));
        foreach ($itemsRaw as $idx => $it) {
            $text = trim((string)$it);
            if ($text === '') { $errors[] = 'every item needs non-empty text'; break; }
            $entries[] = ['letter' => $letters[$idx] ?? ('X' . $idx), 'text' => $text, 'correct' => in_array(strtolower($text), $correctList, true)];
        }
    } else {
        $lines = parseLetteredLines($itemsRaw ?? '');
        $badLine = false;
        foreach ($lines as $l) if ($l['letter'] === null) { $badLine = true; break; }
        if ($badLine) $errors[] = 'every item line must start with a letter followed by "." or ")"';

        $letterMap = [];
        foreach ($lines as $l) if ($l['letter'] !== null) $letterMap[$l['letter']] = $l['text'];
        $correctLetters = parseLetters($correctRaw ?? '');
        $badLetters = array_diff($correctLetters, array_keys($letterMap));
        if ($badLetters) $errors[] = 'correct references letter(s) not found in items: ' . implode(', ', $badLetters);

        foreach ($lines as $l) {
            $entries[] = ['letter' => $l['letter'], 'text' => $l['text'], 'correct' => $l['letter'] !== null && in_array($l['letter'], $correctLetters, true)];
        }
    }

    return ['entries' => $entries, 'errors' => $errors];
}

/* ════════════════════════════════════════════════
   Per-type validators
   Each returns ['ok'=>bool,'errors'=>string[]] or ['ok'=>true,'data'=>[dbColumn=>value,...]]
   ════════════════════════════════════════════════ */

/** Traditional single-answer Multiple Choice. Stores `choices` as a JSON array and `correct` as
 *  the literal matching option text (not a letter) — that's what the student-side renderer
 *  (ngn/mc/index.php) compares the selected radio value against. */
function validateMcq(array $q): array {
    $errors = [];
    $c = commonFields($q);
    if ($c['question']  === '') $errors[] = 'missing question';
    if ($c['rationale'] === '') $errors[] = 'missing rationale';

    $res = resolveLetteredSection($q['choices'] ?? $q['options'] ?? null, $q['correct'] ?? '');
    $errors = array_merge($errors, $res['errors']);
    $entries = $res['entries'];
    if (count($entries) < 2) $errors[] = 'choices must have at least 2 entries';
    $correctEntries = array_values(array_filter($entries, fn($e) => $e['correct']));
    if (count($correctEntries) !== 1) $errors[] = 'exactly one option must be marked correct (found ' . count($correctEntries) . ')';

    if ($errors) return ['ok' => false, 'errors' => $errors];
    return ['ok' => true, 'data' => array_merge($c, [
        'choices' => json_encode(array_map(fn($e) => $e['text'], $entries), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'correct' => $correctEntries[0]['text'],
    ])];
}

function validateSata(array $q): array {
    $errors = [];
    $c = commonFields($q);
    if ($c['question']  === '') $errors[] = 'missing question';
    if ($c['rationale'] === '') $errors[] = 'missing rationale';

    $res = resolveLetteredSection($q['items'] ?? null, $q['correct'] ?? '');
    $errors = array_merge($errors, $res['errors']);
    $entries = $res['entries'];
    if (count($entries) < 2) $errors[] = 'items must have at least 2 entries';
    $correctEntries = array_values(array_filter($entries, fn($e) => $e['correct']));
    if (count($correctEntries) < 1) $errors[] = 'at least one item must be marked correct';

    if ($errors) return ['ok' => false, 'errors' => $errors];
    return ['ok' => true, 'data' => array_merge($c, [
        'items'      => json_encode(array_map(fn($e) => $e['text'], $entries),         JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'correct'    => json_encode(array_map(fn($e) => $e['text'], $correctEntries),  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'case_title' => trim((string)($q['case_title'] ?? '')),
    ])];
}

function validateMpr(array $q): array {
    $errors = [];
    $c = commonFields($q);
    if ($c['question']  === '') $errors[] = 'missing question';
    if ($c['rationale'] === '') $errors[] = 'missing rationale';

    $res = resolveLetteredSection($q['items'] ?? null, $q['correct'] ?? '');
    $errors = array_merge($errors, $res['errors']);
    $entries = $res['entries'];
    if (count($entries) < 2) $errors[] = 'items must have at least 2 entries';
    $correctEntries = array_values(array_filter($entries, fn($e) => $e['correct']));
    if (count($correctEntries) < 1) $errors[] = 'at least one item must be marked correct';

    if ($errors) return ['ok' => false, 'errors' => $errors];
    return ['ok' => true, 'data' => array_merge($c, [
        'items'      => implode("\n", array_map(fn($e) => $e['letter'] . '. ' . $e['text'], $entries)),
        'correct'    => implode(',', array_map(fn($e) => $e['letter'], $correctEntries)),
        'case_title' => trim((string)($q['case_title'] ?? '')),
    ])];
}

function validateMmr(array $q): array {
    $errors = [];
    $c = commonFields($q);
    if ($c['question']  === '') $errors[] = 'missing question';
    if ($c['rationale'] === '') $errors[] = 'missing rationale';

    $columns = parseFlexList($q['columns'] ?? null);
    $rows    = parseFlexList($q['rows']    ?? null);
    if (count($columns) < 1) $errors[] = 'columns must have at least 1 entry';
    if (count($rows)    < 2) $errors[] = 'rows must have at least 2 entries';

    $correctRaw = is_array($q['correct'] ?? null) ? $q['correct'] : null;
    if (!is_array($correctRaw) || count($correctRaw) < 1) {
        $errors[] = 'correct must be an object mapping a column name to an array of correct row names';
    }

    $correctNorm = [];
    if (is_array($correctRaw)) {
        foreach ($correctRaw as $colKey => $rowList) {
            $matchCol = null;
            foreach ($columns as $col) if (strcasecmp(trim($col), trim((string)$colKey)) === 0) { $matchCol = $col; break; }
            if ($matchCol === null) { $errors[] = "correct references column \"$colKey\" not found in columns"; continue; }
            $rowListArr = is_array($rowList) ? $rowList : [$rowList];
            $validRows = [];
            foreach ($rowListArr as $r) {
                $r = trim((string)$r);
                $matchRow = null;
                foreach ($rows as $row) if (strcasecmp(trim($row), $r) === 0) { $matchRow = $row; break; }
                if ($matchRow === null) { $errors[] = "correct[\"$colKey\"] references row \"$r\" not found in rows"; continue; }
                $validRows[] = $matchRow;
            }
            if ($validRows) $correctNorm[$matchCol] = $validRows;
        }
    }
    if (!$errors && empty($correctNorm)) $errors[] = 'correct did not resolve to any valid column/row pair';

    if ($errors) return ['ok' => false, 'errors' => $errors];
    return ['ok' => true, 'data' => array_merge($c, [
        'columns'    => json_encode($columns,     JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'rows'       => json_encode($rows,        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'correct'    => json_encode($correctNorm, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'case_title' => trim((string)($q['case_title'] ?? '')),
    ])];
}

/** One of the 3 Bow-Tie banks: items (either shape) + a required exact count of correct entries. */
function validateBtqSection(array $q, string $itemsKey, string $correctKey, int $requiredCorrect, string $label): array {
    $res = resolveLetteredSection($q[$itemsKey] ?? null, $q[$correctKey] ?? '');
    $errors = array_map(fn($e) => "$label: $e", $res['errors']);
    $entries = $res['entries'];

    if (count($entries) < 2) $errors[] = "$label must have at least 2 items";
    $correctCount = count(array_filter($entries, fn($e) => $e['correct']));
    if ($correctCount !== $requiredCorrect) {
        $errors[] = "$label needs exactly $requiredCorrect correct answer(s) (found $correctCount) to match the bow-tie diagram";
    }
    if ($errors) return ['ok' => false, 'errors' => $errors];

    $arr = array_map(fn($e) => ['text' => $e['text'], 'correct' => $e['correct']], $entries);
    return ['ok' => true, 'json' => json_encode($arr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)];
}

function validateBtq(array $q): array {
    $errors = [];
    $c = commonFields($q);
    if ($c['question']  === '') $errors[] = 'missing question';
    if ($c['rationale'] === '') $errors[] = 'missing rationale';

    $actions    = validateBtqSection($q, 'actionToTake',        'actionToTake_correct',        2, 'Actions to Take');
    $conditions = validateBtqSection($q, 'potentialConditions', 'potentialConditions_correct', 1, 'Potential Conditions');
    $params     = validateBtqSection($q, 'parameterToMonitor',  'parameterToMonitor_correct',  2, 'Parameters to Monitor');
    foreach ([$actions, $conditions, $params] as $sec) if (!$sec['ok']) $errors = array_merge($errors, $sec['errors']);

    if ($errors) return ['ok' => false, 'errors' => $errors];
    return ['ok' => true, 'data' => array_merge($c, [
        'actionToTake'        => $actions['json'],
        'potentialConditions' => $conditions['json'],
        'parameterToMonitor'  => $params['json'],
    ])];
}

function validateDragndrop(array $q): array {
    $errors = [];
    $c = commonFields($q);
    if ($c['question']  === '') $errors[] = 'missing question';
    if ($c['rationale'] === '') $errors[] = 'missing rationale';

    $items   = parseFlexList($q['items']   ?? null);
    $correct = parseFlexList($q['correct'] ?? null);
    if (count($items)   < 2) $errors[] = 'items must have at least 2 entries';
    if (count($correct) < 1) $errors[] = 'correct must have at least 1 entry (one per blank, in order)';
    foreach ($correct as $cv) {
        $found = false;
        foreach ($items as $it) if (strcasecmp($it, $cv) === 0) { $found = true; break; }
        if (!$found) { $errors[] = "correct value \"$cv\" does not match any item"; break; }
    }
    $blankMarkers = preg_match_all('/_{5,}/', $c['question']);
    if ($blankMarkers > 0 && $blankMarkers !== count($correct)) {
        $errors[] = "question has $blankMarkers blank marker(s) (5+ underscores) but correct has " . count($correct) . " entries — they must match exactly";
    }

    if ($errors) return ['ok' => false, 'errors' => $errors];
    return ['ok' => true, 'data' => array_merge($c, [
        'items'   => json_encode($items,   JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'correct' => json_encode($correct, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ])];
}

function validateDropdown(array $q): array {
    $errors = [];
    $c = commonFields($q);
    if ($c['question']  === '') $errors[] = 'missing question';
    if ($c['rationale'] === '') $errors[] = 'missing rationale';

    $options      = parseFlexList($q['options']       ?? null);
    $correctWords = parseFlexList($q['correct_words'] ?? null);
    if (count($options)      < 2) $errors[] = 'options must have at least 2 entries';
    if (count($correctWords) < 1) $errors[] = 'correct_words must have at least 1 entry (one per blank, in order)';
    foreach ($correctWords as $cv) {
        $found = false;
        foreach ($options as $o) if (strcasecmp($o, $cv) === 0) { $found = true; break; }
        if (!$found) { $errors[] = "correct_words value \"$cv\" is not present in options"; break; }
    }
    $blankMarkers = preg_match_all('/_{3,}|\[\[blank\]\]|\{\{blank\}\}|\[blank_?\d*\]/i', $c['question']);
    if ($blankMarkers > 0 && $blankMarkers !== count($correctWords)) {
        $errors[] = "question has $blankMarkers blank marker(s) but correct_words has " . count($correctWords) . " entries — they must match exactly";
    }

    if ($errors) return ['ok' => false, 'errors' => $errors];
    return ['ok' => true, 'data' => array_merge($c, [
        'options'       => implode(',', $options),
        'correct_words' => implode(',', $correctWords),
    ])];
}

function validateHighlight(array $q): array {
    $errors = [];
    $c = commonFields($q);
    if ($c['question']  === '') $errors[] = 'missing question';
    if ($c['rationale'] === '') $errors[] = 'missing rationale';

    $passage = trim((string)($q['passage'] ?? ''));
    if ($passage === '') $errors[] = 'missing passage';

    $options      = parseFlexList($q['options']       ?? null);
    $correctWords = parseFlexList($q['correct_words'] ?? null);
    if (count($options)      < 2) $errors[] = 'options must have at least 2 phrases';
    if (count($correctWords) < 1) $errors[] = 'correct_words must have at least 1 phrase';
    foreach ($correctWords as $cv) {
        $found = false;
        foreach ($options as $o) if (strcasecmp($o, $cv) === 0) { $found = true; break; }
        if (!$found) { $errors[] = "correct_words value \"$cv\" is not in options"; break; }
    }
    if ($passage !== '') {
        foreach ($options as $o) {
            if (stripos($passage, $o) === false) { $errors[] = "option phrase \"$o\" was not found in the passage text"; break; }
        }
    }
    $maxHighlights = is_numeric($q['maxHighlights'] ?? null) ? max(1, (int)$q['maxHighlights']) : max(1, count($correctWords));

    if ($errors) return ['ok' => false, 'errors' => $errors];
    return ['ok' => true, 'data' => array_merge($c, [
        'passage'       => $passage,
        'options'       => implode(',', $options),
        'correct_words' => implode(',', $correctWords),
        'maxHighlights' => $maxHighlights,
    ])];
}

function validateForType(string $type, array $q): array {
    switch ($type) {
        case 'traditional': return validateMcq($q);
        case 'sata':      return validateSata($q);
        case 'mpr':       return validateMpr($q);
        case 'mmr':       return validateMmr($q);
        case 'btq':       return validateBtq($q);
        case 'dragndrop': return validateDragndrop($q);
        case 'dropdown':  return validateDropdown($q);
        case 'highlight': return validateHighlight($q);
        default:          return ['ok' => false, 'errors' => ['unsupported type']];
    }
}

/**
 * The form of a question's text used for duplicate matching: tags stripped, runs of
 * whitespace collapsed, case folded. Two questions that differ only in markup or spacing
 * are the same question as far as the approval queue is concerned.
 */
function normalizeQuestionKey($raw): string {
    return strtolower(preg_replace('/\s+/', ' ', trim(strip_tags((string)$raw))));
}

/** [normalized question text => row id] for one real question table. */
function loadQuestionKeyMap($con, string $table): array {
    $map  = [];
    $safe = mysqli_real_escape_string($con, $table);
    $res  = mysqli_query($con, "SELECT id, question FROM `$safe`");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $key = normalizeQuestionKey($row['question']);
            if ($key !== '' && !isset($map[$key])) $map[$key] = (int)$row['id'];
        }
    }
    return $map;
}

/**
 * Validates one raw question and overwrites an existing row in its real question table.
 * Used when an admin resolves a duplicate by replacing what is already in the bank.
 * Returns ['ok'=>true,'id'=>int] or ['ok'=>false,'errors'=>string[]].
 */
function updateQuestionRow($con, string $type, string $table, array $q, int $existingId): array {
    $res = validateForType($type, $q);
    if (!$res['ok']) return ['ok' => false, 'errors' => $res['errors']];

    $floatCols = ['difficulty_logit' => true];
    $intCols   = ['maxHighlights' => true];

    $d = addPassthroughColumns($con, $table, $q, $res['data']);
    unset($d['id']);
    if (!$d) return ['ok' => false, 'errors' => ['nothing to update']];

    $cols  = array_keys($d);
    $types = array_map(function ($c) use ($floatCols, $intCols) {
        if (isset($floatCols[$c])) return 'd';
        if (isset($intCols[$c]))   return 'i';
        return 's';
    }, $cols);

    $setSql = implode(', ', array_map(fn($c) => "`$c` = ?", $cols));
    $stmt = $con->prepare("UPDATE `$table` SET $setSql WHERE id = ?");
    if (!$stmt) return ['ok' => false, 'errors' => ['prepare failed — ' . $con->error]];

    $params = array_values($d);
    $params[] = $existingId;
    $stmt->bind_param(implode('', $types) . 'i', ...$params);
    if (!$stmt->execute()) return ['ok' => false, 'errors' => [$stmt->error]];

    return ['ok' => true, 'id' => $existingId];
}

/**
 * Validates one raw question and writes it into its real question table.
 * Column list is derived from whatever keys the type's validator returned, so no
 * separate per-type column-mapping table is needed.
 * Returns ['ok'=>true,'id'=>int] or ['ok'=>false,'errors'=>string[]].
 */
function insertQuestionRow($con, string $type, string $table, array $q): array {
    $res = validateForType($type, $q);
    if (!$res['ok']) return ['ok' => false, 'errors' => $res['errors']];

    $floatCols = ['difficulty_logit' => true];
    $intCols   = ['maxHighlights' => true];

    $d      = addPassthroughColumns($con, $table, $q, $res['data']);
    $cols   = array_keys($d);
    $types  = array_map(function ($c) use ($floatCols, $intCols) {
        if (isset($floatCols[$c])) return 'd';
        if (isset($intCols[$c]))   return 'i';
        return 's';
    }, $cols);
    $params = array_values($d);

    $colSql       = implode(',', array_map(fn($c) => "`$c`", $cols));
    $placeholders = implode(',', array_fill(0, count($cols), '?'));
    $stmt = $con->prepare("INSERT INTO `$table` ($colSql) VALUES ($placeholders)");
    if (!$stmt) return ['ok' => false, 'errors' => ['prepare failed — ' . $con->error]];

    $stmt->bind_param(implode('', $types), ...$params);
    if (!$stmt->execute()) return ['ok' => false, 'errors' => [$stmt->error]];

    return ['ok' => true, 'id' => (int)$con->insert_id];
}
