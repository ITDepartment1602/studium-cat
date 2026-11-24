<?php
/**
 * Live Quiz — shared logic for the NCLEX Amplified live game (game/).
 *
 *  - LQ_TYPES / lq_validate_question(): the question formats a host can load
 *  - lq_parse_paste():   turns pasted ChatGPT SQL (or JSON) into question rows,
 *                        without ever executing the pasted SQL
 *  - lq_score():         0..1 credit per NCSBN type (+/- rules where NCSBN uses them)
 *  - lq_points():        speed-weighted game points
 *
 * Tables are created in config.php (section 6.10).
 */

const LQ_TYPES = [
    'mc'        => 'Multiple Choice',
    'sata'      => 'Select All That Apply',
    'mmr'       => 'Matrix Multiple Response',
    'mpr'       => 'Matrix Multiple Choice',
    'highlight' => 'Highlight',
    'dragndrop' => 'Drag & Drop',
    'dropdown'  => 'Drop-down',
    'bowtie'    => 'Bowtie',
];

const LQ_COLUMNS      = ['q_order', 'q_type', 'scenario', 'question', 'options', 'answer', 'rationale', 'time_limit'];
const LQ_MAX_POINTS   = 1000;
const LQ_GRACE_MS     = 1500;   // late answers in flight when the timer hits zero
const LQ_INTRO_MS     = 3500;   // "Get ready 3-2-1" before each question's clock starts
const LQ_MAX_QUESTION = 50;
// Bump on every front-end change: open host screens and phones reload themselves when it changes
const LQ_VERSION      = '2026.11.24.3';

function lq_now_ms(): int {
    return (int) round(microtime(true) * 1000);
}

function lq_json_out(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Who may host: anyone the admin panel lets in — an OTP-verified admin/IT login
 * (admin_login), or an admin_id session such as the IT "coffee" account — but
 * not someone who has typed the password and still owes the OTP (admin_code pending).
 */
function lq_is_host(): bool {
    if (!empty($_SESSION['admin_login'])) return true;
    if (!empty($_SESSION['admin_id']) && empty($_SESSION['admin_code'])) return true;
    return lq_host_key_ok();
}

/**
 * Host key — lets the NCLEX Amplified IT dashboard (another domain) host the game
 * in an iframe without sharing cookies. That dashboard, behind its own IT login,
 * stores sha256(key) in live_quiz_host_keys with a 12-hour expiry and opens
 * host.php?hk=<key>; host.js then sends the key as the X-LQ-Key header.
 * Only the hash is stored, so reading the table never reveals a usable key.
 */
function lq_host_key(): string {
    $k = $_SERVER['HTTP_X_LQ_KEY'] ?? ($_GET['hk'] ?? '');
    return (is_string($k) && preg_match('/^[a-f0-9]{64}$/', $k)) ? $k : '';
}

function lq_host_key_ok(): bool {
    static $ok = null;
    if ($ok !== null) return $ok;
    $key = lq_host_key();
    if ($key === '') return $ok = false;
    if (mt_rand(1, 200) === 1) { // occasional tidy-up of long-expired keys
        db()->execute('DELETE FROM live_quiz_host_keys WHERE expires_at < NOW() - INTERVAL 1 DAY');
    }
    $row = db()->fetchOne(
        'SELECT key_hash FROM live_quiz_host_keys WHERE key_hash = ? AND expires_at > NOW() LIMIT 1',
        [hash('sha256', $key)]
    );
    return $ok = !empty($row);
}

/* ───────────────────────────── Pasted SQL / JSON ───────────────────────────── */

/**
 * Parse what the host pasted. Accepts:
 *   INSERT INTO live_quiz_questions (cols...) VALUES (...), (...);   (one or more)
 *   optional DELETE FROM / TRUNCATE live_quiz_questions lines (ignored — we always replace)
 *   or a JSON array of question objects.
 * Returns ['rows' => [...], 'errors' => [...]] — rows are raw (not yet validated).
 */
function lq_parse_paste(string $text): array {
    $text = trim($text);
    if ($text === '') return ['rows' => [], 'errors' => ['Nothing was pasted.']];

    // JSON array fallback
    if ($text[0] === '[') {
        $arr = json_decode($text, true);
        if (!is_array($arr)) return ['rows' => [], 'errors' => ['The JSON could not be read: ' . json_last_error_msg()]];
        $rows = [];
        foreach ($arr as $i => $obj) {
            if (!is_array($obj)) return ['rows' => [], 'errors' => ['JSON item #' . ($i + 1) . ' is not an object.']];
            $row = [];
            foreach (LQ_COLUMNS as $c) {
                $v = $obj[$c] ?? null;
                $row[$c] = (is_array($v)) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v;
            }
            if ($row['q_order'] === null) $row['q_order'] = $i + 1;
            $rows[] = $row;
        }
        return ['rows' => $rows, 'errors' => []];
    }

    // ChatGPT often wraps its answer in a ```sql code fence — drop the fence lines
    $text = preg_replace('/^\s*```[a-zA-Z]*\s*$/m', '', $text);
    // …and adds a sentence before the SQL: start at the first statement
    if (preg_match('/\b(INSERT|DELETE|TRUNCATE)\b/i', $text, $m, PREG_OFFSET_CAPTURE)) $text = substr($text, $m[0][1]);
    // An apostrophe between two letters (client's) is always text, never a string boundary,
    // so write it the SQL way (client''s). Already-doubled ones ('') are left alone.
    $text = preg_replace("/(?<=\p{L})'(?=\p{L})/u", "''", $text);
    // Plural possessive (clients' families): a quote after "s" followed by a space and a word
    // can't be the end of a SQL value (that is always followed by a comma or ")").
    $text = preg_replace("/(?<=[sS])'(?=[ \t]+\p{L})/u", "''", $text);

    $tokens = lq_sql_tokens($text);
    if (isset($tokens['error'])) return ['rows' => [], 'errors' => [$tokens['error']]];
    $tokens = $tokens['tokens'];

    $rows = []; $errors = [];
    $n = count($tokens); $i = 0;
    $kw = function ($t, $word) { return $t['t'] === 'word' && strcasecmp($t['v'], $word) === 0; };
    // "line 12, near: …text…" so the host can find the spot in the pasted SQL
    $near = function ($idx) use ($tokens, $text) {
        $pos = $tokens[min($idx, count($tokens) - 1)]['p'] ?? strlen($text);
        $line = substr_count(substr($text, 0, $pos), "\n") + 1;
        $snip = trim(preg_replace('/\s+/', ' ', substr($text, max(0, $pos - 40), 90)));
        return ' (line ' . $line . ', near: …' . $snip . '…)';
    };

    while ($i < $n) {
        $t = $tokens[$i];
        if ($t['t'] === 'sym' && $t['v'] === ';') { $i++; continue; }

        // Statements we tolerate and ignore: DELETE FROM x ...; TRUNCATE [TABLE] x; SET ...;
        if ($kw($t, 'DELETE') || $kw($t, 'TRUNCATE') || $kw($t, 'SET') || $kw($t, 'START') || $kw($t, 'COMMIT') || $kw($t, 'BEGIN')) {
            while ($i < $n && !($tokens[$i]['t'] === 'sym' && $tokens[$i]['v'] === ';')) $i++;
            continue;
        }

        // Text after the SQL (ChatGPT's closing remarks) is ignored once rows were read
        if (!$kw($t, 'INSERT') && $rows) break;
        if (!$kw($t, 'INSERT')) {
            $errors[] = 'Only INSERT INTO live_quiz_questions statements are allowed (found "' . $t['v'] . '"' . $near($i) . "). Paste only the SQL, not ChatGPT's explanation.";
            break;
        }
        $i++;
        if ($i < $n && $kw($tokens[$i], 'IGNORE')) $i++;
        if ($i >= $n || !$kw($tokens[$i], 'INTO')) { $errors[] = 'Expected INSERT INTO.'; break; }
        $i++;
        $table = $tokens[$i]['v'] ?? '';
        if (strcasecmp(trim($table, '`'), 'live_quiz_questions') !== 0) {
            $errors[] = 'The SQL must insert into live_quiz_questions (found "' . $table . '").';
            break;
        }
        $i++;

        // Optional column list
        $cols = null;
        if ($i < $n && $tokens[$i]['t'] === 'sym' && $tokens[$i]['v'] === '(') {
            $cols = []; $i++;
            while ($i < $n && !($tokens[$i]['t'] === 'sym' && $tokens[$i]['v'] === ')')) {
                if ($tokens[$i]['t'] === 'word') $cols[] = strtolower(trim($tokens[$i]['v'], '`'));
                $i++;
            }
            $i++;
        }
        if ($i >= $n || !($kw($tokens[$i], 'VALUES') || $kw($tokens[$i], 'VALUE'))) { $errors[] = 'Expected VALUES after the column list.'; break; }
        $i++;

        // Tuples
        while ($i < $n) {
            if (!($tokens[$i]['t'] === 'sym' && $tokens[$i]['v'] === '(')) {
                $errors[] = 'Row ' . (count($rows) + 1) . ': expected "(" to start the row but found "' . $tokens[$i]['v'] . '"' . $near($i)
                    . ". Usually an apostrophe inside text that is not written as two quotes (''), or a missing comma between rows.";
                break 2;
            }
            $i++;
            $vals = [];
            while ($i < $n && !($tokens[$i]['t'] === 'sym' && $tokens[$i]['v'] === ')')) {
                $tk = $tokens[$i];
                if ($tk['t'] === 'str' || $tk['t'] === 'num') $vals[] = $tk['v'];
                elseif ($kw($tk, 'NULL')) $vals[] = null;
                elseif ($tk['t'] === 'sym' && $tk['v'] === ',') { /* separator */ }
                else {
                    $errors[] = 'Row ' . (count($rows) + 1) . ': unexpected "' . $tk['v'] . '"' . $near($i)
                        . ". Check that every apostrophe inside text is written as two quotes (''), e.g. client''s.";
                    break 3;
                }
                $i++;
            }
            $i++; // ')'

            $rowCols = $cols;
            if ($rowCols === null) {
                $rowCols = LQ_COLUMNS;
                if (count($vals) === count(LQ_COLUMNS) + 1) array_shift($vals); // leading id column
            }
            if (count($vals) !== count($rowCols)) {
                $errors[] = 'Row ' . (count($rows) + 1) . ' has ' . count($vals) . ' values but ' . count($rowCols) . ' columns.';
                break 2;
            }
            $row = array_fill_keys(LQ_COLUMNS, null);
            foreach ($rowCols as $k => $c) {
                if (in_array($c, LQ_COLUMNS, true)) $row[$c] = $vals[$k];
            }
            $rows[] = $row;

            if ($i < $n && $tokens[$i]['t'] === 'sym' && $tokens[$i]['v'] === ',') {
                $i++;
                // Tolerate a stray comma after the last row: "…), ;" or "…)," at the very end
                if ($i >= $n || ($tokens[$i]['t'] === 'sym' && $tokens[$i]['v'] === ';')) break;
                continue;
            }
            // Missing comma between two rows: "…30)\n(2, …" — just read the next row
            if ($i < $n && $tokens[$i]['t'] === 'sym' && $tokens[$i]['v'] === '(') continue;
            break;
        }
    }

    if (!$rows && !$errors) $errors[] = 'No INSERT rows were found in the pasted text.';
    return ['rows' => $rows, 'errors' => $errors];
}

/** Minimal MySQL tokenizer: strings (with '' and backslash escapes), numbers, words, symbols. */
function lq_sql_tokens(string $s): array {
    $out = []; $len = strlen($s); $i = 0;
    $esc = ['n' => "\n", 'r' => "\r", 't' => "\t", '0' => "\0", "'" => "'", '"' => '"', '\\' => '\\', 'Z' => "\x1a"];
    while ($i < $len) {
        $c = $s[$i];
        if (ctype_space($c)) { $i++; continue; }
        if ($c === '-' && ($s[$i + 1] ?? '') === '-') { while ($i < $len && $s[$i] !== "\n") $i++; continue; }
        if ($c === '#') { while ($i < $len && $s[$i] !== "\n") $i++; continue; }
        // Stray backslashes between values (line-continuation "\" or a copied literal "\n")
        // are copy artefacts, not SQL — treat them as whitespace
        if ($c === '\\') { $i += in_array($s[$i + 1] ?? '', ['n', 'r', 't'], true) ? 2 : 1; continue; }
        if ($c === '/' && ($s[$i + 1] ?? '') === '*') { $e = strpos($s, '*/', $i + 2); $i = $e === false ? $len : $e + 2; continue; }
        if ($c === "'" || $c === '"') {
            $start = $i; $q = $c; $i++; $buf = '';
            while (true) {
                if ($i >= $len) {
                    $line = substr_count(substr($s, 0, $start), "\n") + 1;
                    $snip = trim(preg_replace('/\s+/', ' ', substr($s, $start, 70)));
                    return ['error' => "A text value starting on line {$line} is never closed (near: {$snip}…). Check that apostrophes inside text are written as two quotes ('')."];
                }
                $ch = $s[$i];
                if ($ch === '\\' && $i + 1 < $len) { $nx = $s[$i + 1]; $buf .= $esc[$nx] ?? $nx; $i += 2; continue; }
                if ($ch === $q) {
                    if (($s[$i + 1] ?? '') === $q) { $buf .= $q; $i += 2; continue; }
                    $i++; break;
                }
                $buf .= $ch; $i++;
            }
            $out[] = ['t' => 'str', 'v' => $buf, 'p' => $start];
            continue;
        }
        if (ctype_digit($c) || ($c === '-' && ctype_digit($s[$i + 1] ?? ''))) {
            $j = $i + 1;
            while ($j < $len && (ctype_digit($s[$j]) || $s[$j] === '.')) $j++;
            $out[] = ['t' => 'num', 'v' => substr($s, $i, $j - $i), 'p' => $i];
            $i = $j; continue;
        }
        if (ctype_alpha($c) || $c === '_' || $c === '`') {
            $j = $i + 1;
            if ($c === '`') { $e = strpos($s, '`', $i + 1); $j = $e === false ? $len : $e + 1; }
            else while ($j < $len && (ctype_alnum($s[$j]) || $s[$j] === '_' || $s[$j] === '.')) $j++;
            $out[] = ['t' => 'word', 'v' => substr($s, $i, $j - $i), 'p' => $i];
            $i = $j; continue;
        }
        $out[] = ['t' => 'sym', 'v' => $c, 'p' => $i];
        $i++;
    }
    return ['tokens' => $out];
}

/* ───────────────────────────── Validation ───────────────────────────── */

function lq_decode($v) {
    if (is_array($v)) return $v;
    if ($v === null) return null;
    $d = json_decode((string) $v, true);
    if (json_last_error() === JSON_ERROR_NONE) return $d;
    return (string) $v; // bare value, e.g. an MC answer written as B instead of "B"
}

function lq_is_list($a): bool {
    return is_array($a) && array_keys($a) === range(0, count($a) - 1);
}

function lq_nonempty_strings($a): bool {
    if (!is_array($a) || !$a) return false;
    foreach ($a as $v) if (!is_string($v) && !is_numeric($v) || trim((string) $v) === '') return false;
    return true;
}

function lq_blanks_in(string $text): array {
    preg_match_all('/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/', $text, $m);
    return array_values(array_unique($m[1]));
}

/**
 * Validate one raw row. Returns [clean row, errors[]]; options/answer come back decoded.
 */
function lq_validate_question(array $row, int $n): array {
    $e = []; $label = 'Question ' . $n;
    $type = strtolower(trim((string) ($row['q_type'] ?? '')));
    $q = trim((string) ($row['question'] ?? ''));
    $opt = lq_decode($row['options'] ?? null);
    $ans = lq_decode($row['answer'] ?? null);
    $time = (int) ($row['time_limit'] ?? 30);

    if (!isset(LQ_TYPES[$type])) $e[] = "$label: q_type \"$type\" is not one of " . implode(', ', array_keys(LQ_TYPES)) . '.';
    if ($q === '') $e[] = "$label: question text is empty.";
    if ($time < 5 || $time > 300) $e[] = "$label: time_limit must be between 5 and 300 seconds (got $time).";
    if (!is_array($opt)) $e[] = "$label: options is not valid JSON.";

    if (!$e) {
        switch ($type) {
            case 'mc':
            case 'sata':
                $ch = $opt['choices'] ?? null;
                $min = $type === 'mc' ? 2 : 3; $max = $type === 'mc' ? 6 : 10;
                if (!is_array($ch) || lq_is_list($ch) || count($ch) < $min || count($ch) > $max || !lq_nonempty_strings($ch)) {
                    $e[] = "$label: options.choices must be an object like {\"A\":\"...\",\"B\":\"...\"} with $min–$max choices.";
                    break;
                }
                if ($type === 'mc') {
                    if (is_array($ans) || !array_key_exists((string) $ans, $ch)) $e[] = "$label: answer must be one choice letter, e.g. \"B\".";
                    else $ans = (string) $ans;
                } else {
                    if (!is_array($ans) || !$ans) { $e[] = "$label: answer must be a list of letters, e.g. [\"A\",\"C\"]."; break; }
                    $ans = array_values(array_unique(array_map('strval', $ans)));
                    foreach ($ans as $a) if (!array_key_exists($a, $ch)) $e[] = "$label: answer \"$a\" is not one of the choices.";
                }
                break;

            case 'mmr':
            case 'mpr':
                $cols = $opt['columns'] ?? null; $rows = $opt['rows'] ?? null;
                if (!lq_is_list($cols) || count($cols) < 2 || !lq_nonempty_strings($cols)) { $e[] = "$label: options.columns must be a list of at least 2 column names."; break; }
                if (!is_array($rows) || lq_is_list($rows) || count($rows) < 2 || !lq_nonempty_strings($rows)) { $e[] = "$label: options.rows must be an object like {\"r1\":\"...\",\"r2\":\"...\"}."; break; }
                if (!is_array($ans) || lq_is_list($ans)) { $e[] = "$label: answer must be an object keyed by row, e.g. {\"r1\":" . ($type === 'mmr' ? '["...", "..."]' : '"..."') . "}."; break; }
                foreach ($rows as $rk => $_) {
                    if (!array_key_exists($rk, $ans)) { $e[] = "$label: answer is missing row \"$rk\"."; continue; }
                    $v = $ans[$rk];
                    if ($type === 'mpr') {
                        if (!is_string($v) || !in_array($v, $cols, true)) $e[] = "$label: answer for row \"$rk\" must be exactly one of the columns.";
                    } else {
                        if (!is_array($v) || !$v) { $e[] = "$label: answer for row \"$rk\" must be a non-empty list of columns."; continue; }
                        foreach ($v as $cv) if (!in_array($cv, $cols, true)) $e[] = "$label: \"$cv\" in row \"$rk\" is not one of the columns.";
                    }
                }
                foreach ($ans as $rk => $_) if (!array_key_exists($rk, $rows)) $e[] = "$label: answer has unknown row \"$rk\".";
                break;

            case 'highlight':
                $items = $opt['items'] ?? null;
                if (!is_array($items) || lq_is_list($items) || count($items) < 3 || !lq_nonempty_strings($items)) { $e[] = "$label: options.items must be an object like {\"h1\":\"...\",\"h2\":\"...\"} with at least 3 phrases."; break; }
                if (!is_array($ans) || !$ans) { $e[] = "$label: answer must be a list of item keys, e.g. [\"h1\",\"h3\"]."; break; }
                $ans = array_values(array_unique(array_map('strval', $ans)));
                foreach ($ans as $a) if (!array_key_exists($a, $items)) $e[] = "$label: answer \"$a\" is not one of the items.";
                // Optional passage: every {{key}} in it must be one of the items
                if (isset($opt['text']) && trim((string) $opt['text']) !== '') {
                    foreach (lq_blanks_in((string) $opt['text']) as $k) if (!array_key_exists($k, $items)) $e[] = "$label: options.text uses {{" . $k . "}} but there is no item \"$k\".";
                }
                break;

            case 'dropdown':
            case 'dragndrop':
                $text = (string) ($opt['text'] ?? '');
                $blanks = lq_blanks_in($text);
                if (!$blanks) { $e[] = "$label: options.text must contain blanks like {{1}} and {{2}}."; break; }
                if (!is_array($ans) || lq_is_list($ans) && count($ans) !== count($blanks)) { $e[] = "$label: answer must be an object keyed by blank, e.g. {\"1\":\"...\"}."; break; }
                if ($type === 'dropdown') {
                    $lists = $opt['blanks'] ?? null;
                    if (!is_array($lists)) { $e[] = "$label: options.blanks must give the choices for each blank."; break; }
                    foreach ($blanks as $b) {
                        $l = $lists[$b] ?? null;
                        if (!lq_is_list($l) || count($l) < 2 || !lq_nonempty_strings($l)) { $e[] = "$label: options.blanks.\"$b\" must be a list of at least 2 choices."; continue; }
                        if (!isset($ans[$b]) || !in_array($ans[$b], $l, true)) $e[] = "$label: answer for blank $b must be one of its choices.";
                    }
                } else {
                    $tk = $opt['tokens'] ?? null;
                    if (!lq_is_list($tk) || count($tk) < count($blanks) || !lq_nonempty_strings($tk)) { $e[] = "$label: options.tokens must be a list with at least as many words as there are blanks."; break; }
                    if (count(array_unique($tk)) !== count($tk)) $e[] = "$label: options.tokens must not repeat a word.";
                    foreach ($blanks as $b) {
                        if (!isset($ans[$b]) || !in_array($ans[$b], $tk, true)) $e[] = "$label: answer for blank $b must be one of the tokens.";
                    }
                    $vals = array_map(fn($b) => $ans[$b] ?? null, $blanks);
                    if (count(array_unique($vals)) !== count($vals)) $e[] = "$label: each token can fill only one blank.";
                }
                break;

            case 'bowtie':
                foreach (['conditions' => 3, 'actions' => 4, 'parameters' => 4] as $k => $min) {
                    $l = $opt[$k] ?? null;
                    if (!is_array($l) || lq_is_list($l) || count($l) < $min || !lq_nonempty_strings($l)) $e[] = "$label: options.$k must be an object with at least $min items (keys like " . $k[0] . "1).";
                }
                if ($e) break;
                if (!is_array($ans)) { $e[] = "$label: answer must be {\"condition\":\"c1\",\"actions\":[\"a1\",\"a2\"],\"parameters\":[\"p1\",\"p2\"]}."; break; }
                if (!isset($ans['condition']) || !array_key_exists($ans['condition'], $opt['conditions'])) $e[] = "$label: answer.condition must be one of the condition keys.";
                foreach (['actions', 'parameters'] as $k) {
                    $l = $ans[$k] ?? null;
                    if (!lq_is_list($l) || count(array_unique($l)) !== 2) { $e[] = "$label: answer.$k must list exactly 2 different keys."; continue; }
                    foreach ($l as $v) if (!array_key_exists($v, $opt[$k])) $e[] = "$label: \"$v\" is not one of the $k.";
                }
                break;
        }
    }

    $clean = [
        'q_order'    => (int) ($row['q_order'] ?? $n),
        'q_type'     => $type,
        'scenario'   => trim((string) ($row['scenario'] ?? '')) ?: null,
        'question'   => $q,
        'options'    => $opt,
        'answer'     => $ans,
        'rationale'  => trim((string) ($row['rationale'] ?? '')) ?: null,
        'time_limit' => $time,
    ];
    return [$clean, $e];
}

/* ───────────────────────────── Scoring ───────────────────────────── */

/** Keep only well-formed parts of a player's answer for this question. */
function lq_clean_answer(string $type, array $opt, $a) {
    switch ($type) {
        case 'mc':
            return (is_string($a) && isset($opt['choices'][$a])) ? $a : null;
        case 'sata':
        case 'highlight':
            $pool = $type === 'sata' ? $opt['choices'] : $opt['items'];
            if (!is_array($a)) return [];
            return array_values(array_unique(array_filter(array_map('strval', $a), fn($k) => isset($pool[$k]))));
        case 'mmr':
        case 'mpr':
            $out = [];
            if (!is_array($a)) return $out;
            foreach ($opt['rows'] as $rk => $_) {
                $v = $a[$rk] ?? null;
                if ($type === 'mpr') { if (is_string($v) && in_array($v, $opt['columns'], true)) $out[$rk] = $v; }
                elseif (is_array($v)) { $out[$rk] = array_values(array_unique(array_filter($v, fn($c) => in_array($c, $opt['columns'], true)))); }
            }
            return $out;
        case 'dropdown':
        case 'dragndrop':
            $out = [];
            if (!is_array($a)) return $out;
            foreach (lq_blanks_in($opt['text']) as $b) {
                $v = $a[$b] ?? null;
                $pool = $type === 'dropdown' ? ($opt['blanks'][$b] ?? []) : $opt['tokens'];
                if (is_string($v) && in_array($v, $pool, true)) $out[$b] = $v;
            }
            return $out;
        case 'bowtie':
            $a = is_array($a) ? $a : [];
            $pick = fn($list, $pool, $max) => array_slice(array_values(array_unique(array_filter(is_array($list) ? $list : [], fn($k) => isset($pool[$k])))), 0, $max);
            return [
                'condition'  => (isset($a['condition']) && isset($opt['conditions'][$a['condition']])) ? $a['condition'] : null,
                'actions'    => $pick($a['actions'] ?? [], $opt['actions'], 2),
                'parameters' => $pick($a['parameters'] ?? [], $opt['parameters'], 2),
            ];
    }
    return null;
}

/**
 * Credit 0..1.
 *  mc, sata      — all-or-nothing (sata strict, matching Studium's ScoringEngine)
 *  mmr, highlight— NCSBN +/-: (right picks − wrong picks) / total right, floored at 0,
 *                  so ticking everything does not earn full points
 *  mpr, dropdown, dragndrop — share of rows/blanks right
 *  bowtie        — 5 slots (1 condition, 2 actions, 2 parameters) / 5
 */
function lq_score(string $type, $correct, $given): float {
    switch ($type) {
        case 'mc':
            return ($given !== null && (string) $given === (string) $correct) ? 1.0 : 0.0;
        case 'sata':
            $g = (array) $given; sort($g); $c = (array) $correct; sort($c);
            return ($g && $g === $c) ? 1.0 : 0.0;
        case 'highlight':
            $g = (array) $given; $c = (array) $correct;
            $right = count(array_intersect($g, $c)); $wrong = count(array_diff($g, $c));
            return max(0, ($right - $wrong) / max(1, count($c)));
        case 'mmr':
            $right = 0; $wrong = 0; $total = 0;
            foreach ($correct as $rk => $cols) {
                $total += count($cols);
                $g = (array) ($given[$rk] ?? []);
                $right += count(array_intersect($g, $cols));
                $wrong += count(array_diff($g, $cols));
            }
            return max(0, ($right - $wrong) / max(1, $total));
        case 'mpr':
        case 'dropdown':
        case 'dragndrop':
            $ok = 0;
            foreach ($correct as $k => $v) if (isset($given[$k]) && (string) $given[$k] === (string) $v) $ok++;
            return $ok / max(1, count($correct));
        case 'bowtie':
            $pts = ($given['condition'] ?? null) === $correct['condition'] ? 1 : 0;
            $pts += count(array_intersect((array) ($given['actions'] ?? []), $correct['actions']));
            $pts += count(array_intersect((array) ($given['parameters'] ?? []), $correct['parameters']));
            return min(5, $pts) / 5;
    }
    return 0.0;
}

/** Faster answers earn more: full credit at 0s, half credit at the buzzer. */
function lq_points(float $fraction, int $elapsedMs, int $limitSec): int {
    if ($fraction <= 0) return 0;
    $t = min(1, max(0, $elapsedMs / max(1, $limitSec * 1000)));
    return (int) round(LQ_MAX_POINTS * $fraction * (1 - $t / 2));
}

/** Streak bonus for consecutive fully-correct answers (2nd in a row +100 … capped at +300). */
function lq_streak_bonus(int $streak): int {
    return $streak >= 2 ? min(300, ($streak - 1) * 100) : 0;
}

/* ───────────────────────────── Data access ───────────────────────────── */

function lq_session(): ?array {
    return db()->fetchOne("SELECT * FROM live_quiz_session WHERE id = 1");
}

function lq_question_at(int $index): ?array {
    $q = db()->fetchOne("SELECT * FROM live_quiz_questions ORDER BY q_order, id LIMIT 1 OFFSET " . max(0, $index));
    if (!$q) return null;
    $q['options'] = lq_decode($q['options']);
    $q['answer']  = lq_decode($q['answer']);
    return $q;
}

/** Fields safe to send before the reveal — never the answer or rationale. */
function lq_public_question(array $q, int $index, int $total): array {
    return [
        'index'      => $index,
        'total'      => $total,
        'type'       => $q['q_type'],
        'type_label' => LQ_TYPES[$q['q_type']] ?? $q['q_type'],
        'scenario'   => $q['scenario'],
        'question'   => $q['question'],
        'options'    => $q['options'],
        'time_limit' => (int) $q['time_limit'],
    ];
}

function lq_wipe_game(): void {
    db()->execute("DELETE FROM live_quiz_answers");
    db()->execute("DELETE FROM live_quiz_players");
    db()->execute("DELETE FROM live_quiz_session");
}

/** Ranking order everywhere: higher score, then less total answering time, then earlier join. */
const LQ_RANK_ORDER = 'score DESC, time_ms ASC, id ASC';

function lq_rank_of(array $p): int {
    $r = db()->fetchOne(
        "SELECT COUNT(*) AS c FROM live_quiz_players
          WHERE score > ? OR (score = ? AND time_ms < ?) OR (score = ? AND time_ms = ? AND id < ?)",
        [(int) $p['score'], (int) $p['score'], (int) $p['time_ms'], (int) $p['score'], (int) $p['time_ms'], (int) $p['id']]
    );
    return (int) ($r['c'] ?? 0) + 1;
}
