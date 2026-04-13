# AGENTS.md — NGN Dashboard Fix Execution Guide

This is the authoritative implementation guide for all AI agents working on the NGN module.
Work through priorities **P0 → P1 → P2 → P3** in order. Do not proceed to a lower priority
until every item in the current priority is complete and verified.

Reference roadmap: [NGN-UPGRADE-ROADMAP.md](NGN-UPGRADE-ROADMAP.md)

---

## Codebase Contracts (Must Not Be Violated)

- `config.php` is the single bootstrap. It calls `session_start()`, connects the DB, and loads
  `Database.php` and `ScoringEngine.php`. Individual files must **never** call `session_start()`.
- `db()` is the DB singleton. Use `db()->fetchOne()`, `db()->fetchAll()`, `db()->execute()`,
  `db()->query()`. Never call raw `mysqli_*` functions in new or modified code.
- `core/Security.php` is **not** auto-loaded. Require it explicitly in any file that uses
  `generateCsrfToken()` / `validateCsrfToken()`.
- All query parameters must be bound via prepared statements (`?` placeholders). No raw
  string interpolation into SQL.
- All scoring must go through `ScoringEngine::score()`. `includes/scoring.php` is deprecated.

---

## Priority Table

| ID | Priority | File | Issue | Lines |
|----|----------|------|-------|-------|
| 1  | P0 | history_details.php | No exam ownership check — student can view any exam | 11, 20, 47 |
| 2  | P0 | history_details.php + index.php | SQL injection via raw `mysqli_query()` interpolation | Multiple |
| 3  | P0 | history_details.php | XSS: `nl2br($displayRationale)` without `htmlspecialchars()` | 347 |
| 4  | P0 | index.php | `eval()` in calculator input | 1130 |
| 5  | P1 | core/ScoringEngine.php | Bowtie per-section cap (2/2/1) not enforced | 132–157 |
| 6  | P1 | api/submit_answer.php | `updateTheta()` called without `$questionDifficulty` | 208 |
| 7  | P1 | includes/scoring.php | Dual scoring engines with contradictory SATA logic | entire file |
| 8  | P2 | history_details.php | `mysqli_real_escape_string()` deprecated throughout | 11, 65, 276–277 |
| 9  | P2 | api/submit_answer.php | No exam ownership check before processing submission | 59 |
| 10 | P2 | history_details.php | Partial scores (0.0–0.99) miscategorized as "wrong" | 29–30 |
| 11 | P2 | config.php + submit_answer.php | `changes` JSON column defined but never written | 156 |
| 12 | P2 | fetch_question.php + submit_answer.php | `json_decode(...) ?: []` swallows falsy JSON | 26, 30 |
| 13 | P2 | api/fetch_question.php | Arbitrary table fallback: `$tableMap[$type] ?? $type` | 64 |
| 14 | P2 | api/submit_answer.php | Multi-step DB writes not wrapped in a transaction | 124–222 |
| 15 | P3 | submit_answer.php, save_history.php, state_manager.php | No CSRF validation on POST endpoints | — |
| 16 | P3 | index.php + save_history.php | Duplicate `CREATE TABLE` blocks (already in config.php) | 10–54 |
| 17 | P3 | history_details.php | JSON in `onclick` attribute — needs `data-*` refactor | 352–363 |
| 18 | P3 | state_manager.php | Uses `mysqli_*` functions; not using `db()` singleton | 29–53 |
| 19 | **P0** | result.php | SQL injection via raw `$student_id` and `$id` interpolation | 35–37, 42, 383 |
| 20 | P2 | save_history.php | `bind_param` type mismatch (22 chars / 24 params) + raw mysqli | 86–134 |
| 21 | P2 | index.php | 6 raw `mysqli_query()` calls need `db()` migration | Multiple |
| 22 | P2 | submit_exam.php | Duplicate `CREATE TABLE` + raw `mysqli_prepare` | 27–58, 76–104 |
| 23 | P2 | result.php | Full raw mysqli migration needed | Multiple |
| 24 | **P1** | core/ScoringEngine.php | `highlight` scored as binary (should be per-word partial) | 49–51 |
| 25 | **P1** | core/ScoringEngine.php | `mpr`/`msr` aliased to SATA strict scoring (should be partial) | 43–45 |
| 26 | **P1** | core/ScoringEngine.php | `dragndrop` scored as binary (should be positional partial) | 49–51 |
| 27 | P2 | All question tables | Missing `difficulty_logit` column disables CAT adaptive selection | — |
| 28 | P2 | btq, mmr tables | Missing `topic`/`system`/`cnc`/`dlevel` metadata columns | — |
| 29 | P3 | All tables | Missing `narcan` and `concept` columns per NGN PDF spec | — |
| 30 | P2 | save_history.php | Duplicate `CREATE TABLE` block (lines 27–54) | 27–54 |

---

## P0 — Critical Security (Fix These First)

### Fix 1 & 2 & 8 — history_details.php: Exam Ownership + All SQL Injections

**File:** `student/dashboard/ngn/history_details.php`

All three SELECT queries that touch `$user_id` / `$examTaken` use raw `mysqli_query()` with
string interpolation and the deprecated `mysqli_real_escape_string()`. Replace them all with
`db()` prepared statements, and add the ownership check on every query.

**Line 11 — GET param intake:**
```php
// BEFORE
$examTaken = isset($_GET['examTaken']) ? mysqli_real_escape_string($con, $_GET['examTaken']) : die("No exam selected!");

// AFTER
$examTaken = isset($_GET['examTaken']) ? intval($_GET['examTaken']) : null;
if (!$examTaken) { die("No exam selected."); }
```

**Line 14 — Student info query:**
```php
// BEFORE
$user_query = mysqli_query($con, "SELECT studentnumber, fullname FROM login WHERE id = '$user_id'");
$user_data  = mysqli_fetch_assoc($user_query);

// AFTER
$user_data = db()->fetchOne(
    "SELECT studentnumber, fullname FROM login WHERE id = ? LIMIT 1",
    [$user_id]
);
```

**Lines 20–30 — Stats query (ownership enforced via AND student_id = ?):**
```php
// BEFORE
$stats_query = mysqli_query($con, "SELECT isCorrect, score, topic, omitted, changes_count FROM exam_results WHERE student_id='$user_id' AND examTaken='$examTaken'");
// and later: if ($score >= 1.00) $stats['correct']++; else $stats['wrong']++;

// AFTER
$statsRows = db()->fetchAll(
    "SELECT isCorrect, score, topic, omitted, changes_count
     FROM exam_results
     WHERE student_id = ? AND examTaken = ?",
    [$user_id, $examTaken]
);
// Three-way categorization (also fixes Issue 10):
foreach ($statsRows as $sRow) {
    $stats['total']++;
    $score = floatval($sRow['score']);
    if ($sRow['omitted']) { $stats['omitted']++; continue; }
    if ($score >= 1.00)        $stats['correct']++;
    elseif ($score > 0.00)     $stats['partial']++;
    else                        $stats['wrong']++;
    if ($sRow['changes_count'] > 0) $stats['changed']++;
    // topic accumulation continues unchanged
}
```
Add `'partial' => 0` to the `$stats` initialization. Update all downstream HTML references
that compute "wrong" as `$stats['total'] - $stats['correct']` to use `$stats['partial']`
and `$stats['wrong']` directly.

**Line 47 — Results list query (ownership enforced):**
```php
// BEFORE
$results_query = mysqli_query($con, "SELECT * FROM exam_results WHERE student_id='$user_id' AND examTaken='$examTaken' ORDER BY question_number ASC");

// AFTER
$results = db()->fetchAll(
    "SELECT * FROM exam_results
     WHERE student_id = ? AND examTaken = ?
     ORDER BY question_number ASC",
    [$user_id, $examTaken]
);
```
Replace all `mysqli_fetch_assoc($results_query)` / `mysqli_num_rows($results_query)` with
`foreach ($results as $row)` / `count($results)`.

**Lines 64–68 — `table_exists()` helper (uses deprecated escape):**
```php
// BEFORE (inside table_exists function)
$safe = mysqli_real_escape_string($con, $table);
$res  = mysqli_query($con, "SHOW TABLES LIKE '$safe'");
return $res && mysqli_num_rows($res) > 0;

// AFTER
$result = db()->fetchOne("SHOW TABLES LIKE ?", [$table]);
return $result !== null;
```

**Lines 276–278 — Dynamic per-row question lookup:**
The `$qTypeTable` variable is produced by `resolveQuestionTable()` which maps against a
whitelist (`$candidatesByType`). It is safe to use as a backtick-quoted identifier.
Only `$actualId` is user-controlled and must be bound as a parameter.

```php
// BEFORE
$safeTable = mysqli_real_escape_string($con, $qTypeTable);
$safeId    = mysqli_real_escape_string($con, $actualId);
$q_lookup  = mysqli_query($con, "SELECT * FROM `$safeTable` WHERE id = '$safeId' LIMIT 1");
if ($q_lookup && $q_data = mysqli_fetch_assoc($q_lookup)) { ... }

// AFTER
// $qTypeTable is already whitelist-validated. Only bind the user-controlled id.
$q_data = db()->fetchOne(
    "SELECT * FROM `{$qTypeTable}` WHERE id = ? LIMIT 1",
    [intval($actualId)]
);
if ($q_data) { ... }
```

---

### Fix 3 — history_details.php: XSS in Rationale Output

**File:** `student/dashboard/ngn/history_details.php` — **Line 347**

```php
// BEFORE
<?php echo nl2br($displayRationale); ?>

// AFTER
<?php echo nl2br(htmlspecialchars($displayRationale, ENT_QUOTES, 'UTF-8')); ?>
```

Apply the same pattern wherever `$row['rationale']` or any database string is echoed into HTML
without escaping.

---

### Fix 4 — index.php: Remove eval() from Calculator

**File:** `student/dashboard/ngn/index.php` — **Lines 1127–1132**

Replace the `calcInput` function. The regex whitelist runs before any evaluation, making
the `Function()` constructor call safe (it sees only digits, operators, parens, and dots).

```javascript
// BEFORE
function calcInput(val) {
  const d = document.getElementById('calcDisplay');
  if (val === 'C') d.value = '';
  else if (val === '=') { try { d.value = eval(d.value); } catch (e) { d.value = 'Total Error'; } }
  else d.value += val;
}

// AFTER
function calcInput(val) {
  const d = document.getElementById('calcDisplay');
  if (val === 'C') {
    d.value = '';
  } else if (val === '=') {
    try {
      const expr = d.value;
      if (!/^[0-9+\-*/.() ]+$/.test(expr)) { d.value = 'Error'; return; }
      d.value = Function('"use strict"; return (' + expr + ')')();
    } catch (e) {
      d.value = 'Error';
    }
  } else {
    d.value += val;
  }
}
```

---

## P1 — High Bug Fixes

### Fix 5 — core/ScoringEngine.php: Enforce Bowtie Per-Section Caps

**File:** `core/ScoringEngine.php` — **Lines 132–157**

The `scoreBowtie()` method applies a global `min($earned, 5)` but not per-section limits.
A question with 3 correct conditions lets a student score 3 on a section capped at 2.

```php
// BEFORE
foreach ($correctConditions as $c) {
    if (in_array($c, $userConditions, true)) { $earned++; }
}
foreach ($correctActions as $c) {
    if (in_array($c, $userActions, true)) { $earned++; }
}
foreach ($correctParams as $c) {
    if (in_array($c, $userParams, true)) { $earned++; }
}
$earned = min($earned, $maxPoints);

// AFTER
$conditionScore = 0;
foreach ($correctConditions as $c) {
    if (in_array($c, $userConditions, true)) { $conditionScore++; }
}
$earned += min($conditionScore, 2);

$actionScore = 0;
foreach ($correctActions as $c) {
    if (in_array($c, $userActions, true)) { $actionScore++; }
}
$earned += min($actionScore, 2);

$paramScore = 0;
foreach ($correctParams as $c) {
    if (in_array($c, $userParams, true)) { $paramScore++; }
}
$earned += min($paramScore, 1);

$earned = min($earned, $maxPoints); // defensive global cap
```

---

### Fix 6 — api/submit_answer.php: Pass Question Difficulty to updateTheta

**File:** `student/dashboard/ngn/api/submit_answer.php` — **Lines 92–117 and 208**

The `updateTheta()` method accepts an optional `$questionDifficulty` parameter that drives
IRT bonus/penalty logic (ScoringEngine.php lines 296–301), but the call at line 208 never
passes it.

**Step 1** — Initialize before the metadata block and fetch `difficulty_logit`:
```php
$questionDifficulty = 0.0; // default if column absent or metadata not fetched
```
Inside the `if ($qData)` block that already reads metadata, add:
```php
$questionDifficulty = isset($qData['difficulty_logit']) ? floatval($qData['difficulty_logit']) : 0.0;
```
Also add `difficulty_logit` to the SELECT in the metadata query:
```php
// BEFORE
$qData = db()->fetchOne("SELECT rationale, topic, system, cnc, dlevel FROM {$table} WHERE id = ? LIMIT 1", [$question_id]);

// AFTER
$qData = db()->fetchOne("SELECT rationale, topic, system, cnc, dlevel, difficulty_logit FROM {$table} WHERE id = ? LIMIT 1", [$question_id]);
```

**Step 2** — Pass it at line 208:
```php
// BEFORE
$newTheta = ScoringEngine::updateTheta($currentTheta, $isCorrect);

// AFTER
$newTheta = ScoringEngine::updateTheta($currentTheta, $isCorrect, $questionDifficulty);
```

---

### Fix 7 — includes/scoring.php: Deprecate Duplicate Scoring Engine

**File:** `student/dashboard/ngn/includes/scoring.php`

This file implements penalty-based SATA scoring, directly contradicting `ScoringEngine::scoreSATA()`
which uses strict all-or-nothing scoring. The contradiction means which file is included determines
whether wrong choices are penalized.

**Action:** Replace the entire file with stub redirects and a deprecation notice:

```php
<?php
/**
 * DEPRECATED — Do not add new scoring logic here.
 *
 * All scoring is handled exclusively by /core/ScoringEngine.php.
 * Stubs below exist only to prevent fatal errors in legacy callers.
 *
 * To remove: grep -rn "includes/scoring.php" and delete all includes, then delete this file.
 */

function calculateHighlightScore($u, $c)  { return ScoringEngine::score('highlight',   $u, $c); }
function calculateMMRScore($u, $c)         { return ScoringEngine::score('mmr',         $u, $c); }
function calculateMPRScore($u, $c)         { return ScoringEngine::score('sata',        $u, $c); }
function calculateDragDropScore($u, $c)    { return ScoringEngine::score('dragndrop',   $u, $c); }
function calculateDropdownScore($u, $c)    { return ScoringEngine::score('dropdown',    $u, $c); }
function calculateSATAScore($u, $c)        { return ScoringEngine::score('sata',        $u, $c); }
function calculateColumnScore($u, $c)      { return ScoringEngine::score('column',      $u, $c); }
function calculateTraditionalScore($u, $c) { return ScoringEngine::score('traditional', $u, $c); }

function calculateBowtieScore($uA, $uC, $uP, $cA, $cC, $cP) {
    $user    = ['actions' => $uA, 'conditions' => $uC, 'parameters' => $uP];
    $correct = ['actions' => $cA, 'conditions' => $cC, 'parameters' => $cP];
    return ScoringEngine::score('bowtie', $user, $correct);
}
```

---

## P2 — Medium Fixes

### Fix 9 — api/submit_answer.php: Exam Ownership Check

**File:** `student/dashboard/ngn/api/submit_answer.php`

Add ownership verification after the `$examTaken` / `$question_uid` validation block
and **before** any DB reads or writes:

```php
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
```

---

### Fix 10 — history_details.php: Three-Way Score Categorization

Already co-located with Fix 2 (the stats loop replacement above). Ensure `$stats['partial']`
is initialized to `0` and that the HTML output uses `$stats['partial']` explicitly rather
than computing it as `total - correct - omitted`.

---

### Fix 11 — submit_answer.php: Write the `changes` JSON Column

**File:** `student/dashboard/ngn/api/submit_answer.php`

The `changes` column (`json DEFAULT NULL`) is defined in `config.php` but never written.
Either write it or remove the column. Recommended: write it as a lightweight audit trail.

Add before the UPSERT block:
```php
$changes_json = null;
if ($existing && ScoringEngine::hasChanged($existing['prev_answer'] ?? '', $user_answer)) {
    $changes_json = json_encode([
        'previous' => $existing['prev_answer'] ?? null,
        'count'    => $changes_count,
    ]);
}
```
Then add `changes = ?` to the UPDATE column list and `changes, ?` to the INSERT column/values lists.

---

### Fix 12 — fetch_question.php + submit_answer.php: JSON Fallback Ambiguity

**Files:**
- `student/dashboard/ngn/api/fetch_question.php` — line 26
- `student/dashboard/ngn/api/submit_answer.php` — line 30

```php
// BEFORE (both files)
$input = json_decode($json, true) ?: [];

// AFTER (both files)
$decoded = json_decode($json, true);
$input   = is_array($decoded) ? $decoded : [];
```

---

### Fix 13 — fetch_question.php: Reject Unknown Question Types

**File:** `student/dashboard/ngn/api/fetch_question.php` — **Line 64**

```php
// BEFORE
$table = $tableMap[$questionType] ?? $questionType;

// AFTER
if (!isset($tableMap[$questionType])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid question type']);
    exit;
}
$table = $tableMap[$questionType];
```

Apply the same pattern in `api/submit_answer.php` at line ~108 where metadata is fetched:
if `$question_type` is not in `$tableMap`, set `$qData = null` and skip the metadata fetch
rather than falling through to an unvalidated table name.

---

### Fix 14 — submit_answer.php: Wrap DB Writes in a Transaction

**File:** `student/dashboard/ngn/api/submit_answer.php` — **Lines 124–222**

```php
// Add before the UPSERT block:
$conn = db()->getConnection();
$conn->begin_transaction();
try {

    // ... existing UPSERT (temporary_exam_result) ...
    $ok = db()->execute(/* upsert query */, [/* params */]);
    if (!$ok) throw new Exception('Result upsert failed');

    // ... existing CAT state update (temporary_exam_state) ...
    $ok2 = db()->execute(/* state update query */, [/* params */]);
    if (!$ok2) throw new Exception('State update failed');

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Transaction failed', 'detail' => IS_PRODUCTION ? null : $e->getMessage()]);
    exit;
}
```

---

## P3 — Lower Priority

### Fix 15 — CSRF Protection on POST Endpoints

**Files:** `api/submit_answer.php`, `save_history.php`, `state_manager.php`

`core/Security.php` already provides `generateCsrfToken()` and `validateCsrfToken()`.

**Step 1 — Expose token to JavaScript (index.php):**
```php
require_once '../../../core/Security.php';
$csrfToken = generateCsrfToken();
```
```javascript
const CSRF_TOKEN = <?php echo json_encode($csrfToken); ?>;
```

**Step 2 — Include header in every JS fetch call:**
```javascript
headers: { 'X-CSRF-Token': CSRF_TOKEN, 'Content-Type': 'application/json' }
```

**Step 3 — Validate in each POST endpoint (add after auth check):**
```php
require_once '../../../../core/Security.php'; // adjust relative depth per file
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!validateCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}
```

---

### Fix 16 — Remove Duplicate CREATE TABLE Blocks

**Files:** `index.php` (lines 10–54), `save_history.php` (lines 23–55)

`config.php` already creates `temporary_exam_result`, `temporary_exam_state`, and `exam_results`
on every request. The identical blocks in `index.php` and `save_history.php` are dead code and
will cause schema drift if one copy is updated without the other.

**Action:** Delete the entire `CREATE TABLE IF NOT EXISTS` blocks from both files. The
`require_once '../../../config.php'` already guarantees the tables exist before either file runs.

---

### Fix 17 — Refactor onclick JSON to data-* Attributes (history_details.php)

**File:** `student/dashboard/ngn/history_details.php` — **Lines 352–363**

```php
// BEFORE
<button class="btn-view ..." onclick='viewQuestion(<?php echo json_encode([...]); ?>)'>

// AFTER
<button class="btn-view ..."
        data-payload='<?php echo htmlspecialchars(json_encode([...]), ENT_QUOTES, "UTF-8"); ?>'>
```

Add a delegated listener (in the `<script>` block, after DOM ready):
```javascript
document.querySelectorAll('.btn-view').forEach(function(btn) {
    btn.addEventListener('click', function() {
        viewQuestion(JSON.parse(this.getAttribute('data-payload')));
    });
});
```

---

### Fix 18 — state_manager.php: Migrate to db() Singleton

**File:** `student/dashboard/ngn/state_manager.php` — **Lines 29–53**

Replace the `mysqli_prepare` / `bind_param` / `execute` block with:
```php
$ok = db()->execute(
    "INSERT INTO temporary_exam_state
     (student_id, examTaken, question_set, current_question, timer, updated_at)
     VALUES (?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
     question_set      = VALUES(question_set),
     current_question  = VALUES(current_question),
     timer             = VALUES(timer),
     updated_at        = VALUES(updated_at)",
    [$student_id, $examTaken, $question_set, $current_question, $timer, $updated_at]
);

if ($ok) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'db_insert_failed']);
}
```

---

## Coding Standards

All code in the NGN module must conform to these rules. Any change that violates a rule
must be corrected before moving to the next fix.

| Rule | Requirement |
|------|-------------|
| **DB-001** | No raw SQL string interpolation. All user/external values must be `?` bound parameters. |
| **DB-002** | Use `db()` singleton. Never call `mysqli_query()`, `mysqli_prepare()`, or `mysqli_real_escape_string()` in new or modified code. |
| **DB-003** | Verify ownership before data access. Any endpoint accepting an external identifier (`examTaken`, `question_uid`) must check it belongs to `$_SESSION['user_id']`. |
| **DB-004** | Wrap multi-step writes in a transaction with `begin_transaction()` / `commit()` / `rollback()`. |
| **OUTPUT-001** | Wrap all PHP values rendered into HTML in `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`. |
| **OUTPUT-002** | No JSON in HTML attributes. Use `data-*` with `htmlspecialchars(json_encode(...))` and parse in JS. |
| **JS-001** | No `eval()`. Use `JSON.parse()` for data. Use a regex-whitelisted `Function()` for arithmetic only. |
| **INCLUDE-001** | Every PHP file begins with `require_once '<path>/config.php'`. Never call `session_start()` manually. |
| **INCLUDE-002** | Use `require_once`, never `include`. `session_start()` is handled exclusively by `config.php`. |
| **SCORE-001** | All scoring goes through `ScoringEngine::score()`. `includes/scoring.php` is deprecated — do not add logic there. |
| **CSRF-001** | All state-mutating POST endpoints must validate `X-CSRF-Token` via `validateCsrfToken()` from `core/Security.php`. |

---

## Verification Checklist

Run each check after completing the corresponding fix group.

### P0 Checks
- [ ] Log in as Student A, note their `examTaken` ID. Log in as Student B. Request
  `history_details.php?examTaken=<A's ID>`. Expected: 0 results shown, no data leak.
- [ ] Request `history_details.php?examTaken=abc`. Expected: `intval()` reduces to `0`,
  page dies with "No exam selected." — no SQL error.
- [ ] Insert `<script>alert(1)</script>` as a rationale in dev DB. Load history page.
  Expected: script tag renders as literal text; no alert fires.
- [ ] Open NGN exam calculator. Enter `1+2=`. Expected: `3`. Enter `alert(1)=`.
  Expected: `Error` (regex rejects it before evaluation).

### P1 Checks
- [ ] Call `ScoringEngine::score('bowtie', $user, $correct)` with `$correct` having
  3 conditions, 3 actions, 2 parameters (all answered correctly by user).
  Expected: `earned = 5`, not 8.
- [ ] Submit a correct answer for a question with `difficulty_logit = 2.0`, student theta = 0.0.
  Expected: returned `cat.theta_ability >= 0.4` (base 0.3 + IRT bonus 0.1).
- [ ] `grep -rn "includes/scoring.php"` across the project.
  Expected: zero active includes (only the deprecated file itself references it internally).

### P2 Checks
- [ ] POST to `api/submit_answer.php` with a valid session for Student A but `examTaken`
  belonging to Student B. Expected: HTTP 403.
- [ ] Load a history page for an exam with at least one partially-correct answer (score = 0.5).
  Expected: stats panel shows non-zero "Partial" count, not under "Wrong".
- [ ] Send a request body of `false` (valid JSON, not an object) to `api/fetch_question.php`.
  Expected: treated as `[]`, returns HTTP 400 "missing required fields".
- [ ] POST `question_type=../../../etc/passwd` to `api/fetch_question.php`.
  Expected: HTTP 400, "Invalid question type".
- [ ] In dev, temporarily break the state UPDATE query. Submit an answer. Confirm the
  `temporary_exam_result` row is also rolled back (no orphaned result row).

### P3 Checks
- [ ] POST to `api/submit_answer.php` with a valid session cookie but no `X-CSRF-Token` header.
  Expected: HTTP 403, "Invalid CSRF token".
- [ ] Confirm `CREATE TABLE IF NOT EXISTS temporary_exam_result` appears only in `config.php`
  across the entire codebase: `grep -rn "CREATE TABLE.*temporary_exam_result"`.
- [ ] Inspect DOM of `history_details.php` for a question row. Expected: `<button data-payload='...'>`,
  no `onclick='viewQuestion(...)'`. Clicking the button still opens the modal correctly.
- [ ] Trigger a state save mid-exam. Expected: no `mysqli_*` deprecation warnings in PHP error log.

### Scoring Engine Checks (Phase 5)
- [ ] `ScoringEngine::score('highlight', ['word1','word2'], ['word1','word2','word3'])` → `earned = 0.67`, not `0` or `1`.
- [ ] `ScoringEngine::score('mpr', ['a','c'], ['a','b','c','d'])` → `earned = 0.5`, not `0`.
- [ ] `ScoringEngine::score('dragndrop', ['x','y','z'], ['x','y','w'])` → `earned = 0.67` (2/3 correct positions).
- [ ] `ScoringEngine::score('sata', ['a','b'], ['a','b','c'])` → `earned = 0` (strict: missed `c`).
- [ ] `ScoringEngine::score('msr', ['a','b'], ['a','b','c'])` → `earned = 0.67` (partial: MSR/MPR not strict).

### Schema Checks (Phase 6)
- [ ] `SHOW COLUMNS FROM btq LIKE 'difficulty_logit'` → returns row.
- [ ] `SHOW COLUMNS FROM mmr LIKE 'topic'` → returns row.
- [ ] `SHOW COLUMNS FROM traditional LIKE 'narcan'` → returns row.
- [ ] `SHOW COLUMNS FROM exam_results LIKE 'concept'` → returns row.
- [ ] Submit an answer for a question with `difficulty_logit = 2.0`. Confirm `cat.theta_ability` updated correctly.
- [ ] Submit answer where question has `narcan` value. Confirm `exam_results.narcan` is populated after `submit_exam.php`.
