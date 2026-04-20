# NGN Module Hardening & Question Bank Readiness Plan

> **Status:** PENDING APPROVAL  
> **Created:** 2026-04-11  
> **Scope:** `student/dashboard/ngn/` + `core/ScoringEngine.php` + `config.php`  
> **Goal:** Zero-defect NGN module ready for 4,000-question bank import  

---

## Context

The NGN (Next Generation NCLEX) module at `student/dashboard/ngn/` is a Computerized Adaptive Testing (CAT) exam system supporting 8 question types. The codebase has:

1. **Critical security vulnerabilities** (SQL injection, XSS, eval()) documented in AGENTS.md
2. **Architectural debt** (dual scoring engines, raw mysqli_* calls, duplicate schema blocks)
3. **Incomplete CAT implementation** (difficulty_logit missing, theta updates broken)
4. **Schema gaps** vs the NGN Question Bank Instructions PDF (missing narcan tag, concept field, per-table metadata columns)
5. **New issues** discovered during scan that AGENTS.md doesn't cover (result.php SQL injection, save_history.php bind_param bugs, scoring engine missing MPR/highlight partial scoring)

This plan aligns the existing AGENTS.md P0-P3 fixes with additional issues found, then prepares the schema and scoring for the 4,000-question bank.

---

## Files In Scope

| File | Lines | Key Issues |
|------|-------|------------|
| `config.php` | 265 | Authoritative schema, bootstrap (OK) |
| `core/ScoringEngine.php` | 357 | Bowtie caps, MPR/highlight scoring, updateTheta |
| `core/Database.php` | 214 | db() singleton (OK) |
| `core/Security.php` | 243 | CSRF helpers (OK, not yet wired) |
| `ngn/index.php` | ~1678 | Raw mysqli, eval(), duplicate CREATE TABLE, session |
| `ngn/history_details.php` | 445 | SQL injection, XSS, no ownership, raw mysqli |
| `ngn/result.php` | 512 | SQL injection (NEW), raw mysqli |
| `ngn/save_history.php` | 141 | Duplicate CREATE TABLE, raw mysqli, bind_param mismatch |
| `ngn/submit_exam.php` | 119 | Duplicate CREATE TABLE, raw mysqli |
| `ngn/state_manager.php` | 55 | session_start(), include, raw mysqli |
| `ngn/api/fetch_question.php` | 143 | Arbitrary table fallback, json_decode |
| `ngn/api/submit_answer.php` | 252 | No ownership, no transaction, no difficulty pass-through, json_decode |
| `ngn/includes/scoring.php` | 264 | Deprecated, contradicts ScoringEngine |

---

## Phase 1: P0 Critical Security (4 tasks)

> **BLOCKING** — Nothing else proceeds until all P0 issues are resolved.

### Task 1.1: history_details.php — SQL injection + ownership + raw mysqli

**File:** `student/dashboard/ngn/history_details.php`

| Line(s) | Current Problem | Fix |
|---------|----------------|-----|
| 2 | `session_start()` redundant | Remove (config.php handles it) |
| 3 | `include` | Change to `require_once` |
| 11 | `mysqli_real_escape_string` on GET param | `intval($_GET['examTaken'])` |
| 14 | Raw `mysqli_query` with `$user_id` interpolation | `db()->fetchOne("...WHERE id = ?", [$user_id])` |
| 20-42 | Raw `mysqli_query` stats loop, no ownership | `db()->fetchAll()` with `AND student_id = ?` bound param |
| 21 | No `partial` stat | Add `'partial' => 0` to `$stats` init |
| 29-30 | Score >= 1.00 or wrong (binary) | Three-way: correct / partial / wrong |
| 47 | Raw `mysqli_query` results list | `db()->fetchAll()` with bound params |
| 64-68 | `table_exists()` uses `mysqli_real_escape_string` | `db()->fetchOne("SHOW TABLES LIKE ?", [$table])` |
| 167 | "Partial" computed as `total - correct` | Use `$stats['partial']` directly |
| 232-233 | `mysqli_num_rows` / `mysqli_fetch_assoc` loops | `count($results)` / `foreach ($results as $row)` |
| 276-278 | `mysqli_real_escape_string` + raw query | `db()->fetchOne("SELECT * FROM \`{$qTypeTable}\` WHERE id = ? LIMIT 1", [intval($actualId)])` |

### Task 1.2: history_details.php — XSS in rationale output

**File:** `student/dashboard/ngn/history_details.php` — Line 347

```php
// BEFORE
<?php echo nl2br($displayRationale); ?>

// AFTER
<?php echo nl2br(htmlspecialchars($displayRationale, ENT_QUOTES, 'UTF-8')); ?>
```

### Task 1.3: index.php — Remove eval() from calculator

**File:** `student/dashboard/ngn/index.php` — Line 1130

```javascript
// BEFORE
else if (val === '=') { try { d.value = eval(d.value); } catch (e) { d.value = 'Total Error'; } }

// AFTER
else if (val === '=') {
  try {
    const expr = d.value;
    if (!/^[0-9+\-*/.() ]+$/.test(expr)) { d.value = 'Error'; return; }
    d.value = Function('"use strict"; return (' + expr + ')')();
  } catch (e) { d.value = 'Error'; }
}
```

### Task 1.4: result.php — SQL injection (NEW — not in AGENTS.md)

**File:** `student/dashboard/ngn/result.php`

| Line(s) | Current Problem | Fix |
|---------|----------------|-----|
| 35-37 | `$whereClause = " WHERE student_id='$student_id'"` | Build parameterized queries |
| 42 | `mysqli_query($con, $countSql)` with interpolation | `db()->fetchOne()` with bound params |
| 383 | `"...WHERE student_id='$student_id' AND examTaken='$id'..."` | `db()->fetchAll()` with bound params |
| All | Every `mysqli_query` / `mysqli_fetch_assoc` | Convert to `db()->fetchOne/fetchAll` |

**Add to AGENTS.md as Issue 19, Priority P0.**

---

## Phase 2: P1 High Bug Fixes (3 tasks)

### Task 2.1: ScoringEngine.php — Enforce bowtie per-section caps (2/2/1)

**File:** `core/ScoringEngine.php` — Lines 132-157

```php
// BEFORE — counts all correct without per-section limits
foreach ($correctConditions as $c) {
    if (in_array($c, $userConditions, true)) { $earned++; }
}
// ... same for actions and params ...
$earned = min($earned, $maxPoints);

// AFTER — cap each section independently
$conditionScore = 0;
foreach ($correctConditions as $c) {
    if (in_array($c, $userConditions, true)) $conditionScore++;
}
$earned += min($conditionScore, 2);

$actionScore = 0;
foreach ($correctActions as $c) {
    if (in_array($c, $userActions, true)) $actionScore++;
}
$earned += min($actionScore, 2);

$paramScore = 0;
foreach ($correctParams as $c) {
    if (in_array($c, $userParams, true)) $paramScore++;
}
$earned += min($paramScore, 1);

$earned = min($earned, $maxPoints); // defensive global cap
```

### Task 2.2: submit_answer.php — Pass questionDifficulty to updateTheta

**File:** `student/dashboard/ngn/api/submit_answer.php`

| Step | Change |
|------|--------|
| Init | Add `$questionDifficulty = 0.0;` before metadata block |
| Line 109 | Add `difficulty_logit` to SELECT: `"SELECT rationale, topic, system, cnc, dlevel, difficulty_logit FROM..."` |
| Inside `if ($qData)` | Add `$questionDifficulty = floatval($qData['difficulty_logit'] ?? 0.0);` |
| Line 208 | `ScoringEngine::updateTheta($currentTheta, $isCorrect, $questionDifficulty)` |

### Task 2.3: includes/scoring.php — Deprecate with stubs

**File:** `student/dashboard/ngn/includes/scoring.php`

Replace entire file with stub functions that delegate to `ScoringEngine::score()`:

```php
<?php
/**
 * DEPRECATED — All scoring handled by /core/ScoringEngine.php.
 * Stubs prevent fatal errors in legacy callers.
 */
function calculateHighlightScore($u, $c)  { return ScoringEngine::score('highlight', $u, $c); }
function calculateMMRScore($u, $c)         { return ScoringEngine::score('mmr', $u, $c); }
function calculateMPRScore($u, $c)         { return ScoringEngine::score('mpr', $u, $c); }
// ... etc per AGENTS.md Fix 7 ...
```

---

## Phase 3: P2 Medium Fixes (8 tasks)

### Task 3.1: submit_answer.php — Exam ownership check

**File:** `student/dashboard/ngn/api/submit_answer.php` — After line 47

```php
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

### Task 3.2: submit_answer.php — Write `changes` JSON column

**File:** `student/dashboard/ngn/api/submit_answer.php`

Build `$changes_json` from `hasChanged()` check, add `changes = ?` to both UPDATE and INSERT queries.

### Task 3.3: fetch_question.php + submit_answer.php — JSON decode fix

**Files:** `api/fetch_question.php:26`, `api/submit_answer.php:30`

```php
// BEFORE
$input = json_decode($json, true) ?: [];

// AFTER
$decoded = json_decode($json, true);
$input = is_array($decoded) ? $decoded : [];
```

### Task 3.4: fetch_question.php — Reject unknown question types

**File:** `api/fetch_question.php` — Line 64

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

Apply same in `submit_answer.php` line 108.

### Task 3.5: submit_answer.php — Wrap DB writes in transaction

**File:** `student/dashboard/ngn/api/submit_answer.php` — Lines 124-222

Wrap UPSERT + CAT state update in `begin_transaction()` / `commit()` / `rollback()`.

### Task 3.6: save_history.php — Migrate to db() + fix bind_param mismatch (NEW)

**File:** `student/dashboard/ngn/save_history.php`

**Issues:**
- `bind_param` format string `'iississsssssssdiisiiis'` has **22 chars for 24 params** — type mismatch bug
- Lines 27-54: Duplicate CREATE TABLE block
- Lines 86-134: Raw `mysqli_prepare/bind_param` instead of `db()->execute()`
- Lines 100, 140: `mysqli_error($con)` leaks DB error details

**Fix:** Remove CREATE TABLE block, replace DELETE+INSERT with `db()->execute()` UPSERT, remove error detail leaks.

### Task 3.7: index.php — Migrate all raw mysqli to db() (NEW)

**File:** `student/dashboard/ngn/index.php`

| Line | Raw Call | Replacement |
|------|----------|-------------|
| 9-54 | Duplicate CREATE TABLE blocks | Delete entirely |
| 64-69 | `mysqli_prepare($con, ...)` | `db()->fetchOne()` |
| 80 | `mysqli_query($con, "...WHERE student_id = '$user_id'")` | `db()->fetchOne("...WHERE student_id = ?", [$user_id])` |
| 99 | `mysqli_query($con, "...WHERE student_id='$user_id'...")` | `db()->fetchAll()` |
| 137 | `mysqli_query($con, "...WHERE id = '$user_id'")` | `db()->fetchOne()` |
| 143 | `mysqli_query($con, "UPDATE login SET...")` | `db()->execute()` |
| 152-155 | `table_exists()` with `mysqli_real_escape_string` | `db()->fetchOne("SHOW TABLES LIKE ?", [$table])` |
| 174 | `mysqli_query($con, "SELECT...LIMIT $limit")` | `db()->fetchAll()` with bound params |

### Task 3.8: submit_exam.php — Remove duplicate CREATE TABLE + migrate (NEW)

**File:** `student/dashboard/ngn/submit_exam.php`

- Remove CREATE TABLE block (lines 27-58)
- Lines 76-104: Replace `mysqli_prepare/bind_param/execute` with `db()->execute()`

---

## Phase 4: P3 Lower Priority (4 tasks)

### Task 4.1: CSRF protection on POST endpoints

**Files:** `api/submit_answer.php`, `save_history.php`, `state_manager.php`, `submit_exam.php`

1. Expose token in `index.php`: `$csrfToken = generateCsrfToken();`
2. Add `X-CSRF-Token` header to all JS fetch calls
3. Validate in each POST endpoint via `validateCsrfToken()`

### Task 4.2: state_manager.php — Migrate to db()

**File:** `student/dashboard/ngn/state_manager.php`

- Line 2: Remove `session_start()`
- Line 3: `include` → `require_once`
- Lines 29-53: Replace `mysqli_prepare/bind_param` with `db()->execute()`
- Lines 41, 53: Remove `mysqli_error($con)` from error responses

### Task 4.3: history_details.php — Refactor onclick JSON to data-* attributes

**File:** `student/dashboard/ngn/history_details.php` — Lines 352-363

Replace `onclick='viewQuestion(<?php echo json_encode(...); ?>)'` with:
```html
<button data-payload='<?php echo htmlspecialchars(json_encode([...]), ENT_QUOTES, "UTF-8"); ?>'>
```
Add delegated JS listener.

### Task 4.4: result.php — Full migration to db() (NEW)

**File:** `student/dashboard/ngn/result.php`

Convert ALL remaining `mysqli_query/mysqli_fetch_assoc` calls to `db()->fetchOne/fetchAll/execute`.

---

## Phase 5: Scoring Engine Completeness (3 tasks)

> Required before loading the 4,000-question bank. Ensures correct partial scoring for all 8 NGN types.

### Task 5.1: Add proper highlight scoring

**File:** `core/ScoringEngine.php`

**Problem:** Highlight currently falls through to `scoreTraditional()` (binary 0/1). Should have partial scoring per word.

**Fix:** Add `case 'highlight':` → new `scoreHighlight()` method with per-word matching and partial credit.

### Task 5.2: Add proper MPR + dragndrop scoring

**File:** `core/ScoringEngine.php`

**Problems:**
- `mpr` aliased to `sata` → strict all-or-nothing. Should be partial credit.
- `dragndrop` falls through to `scoreTraditional()` → binary. Should be positional partial.

**Fix:**
- New `scoreMPR()`: partial credit = correct_selected / total_correct
- New `scoreDragDrop()`: positional partial = correct_positions / total_positions

### Task 5.3: Separate SATA from MPR routing

**File:** `core/ScoringEngine.php` — Lines 43-44

```php
// BEFORE
case 'sata':
case 'msr':
    return self::scoreSATA($user, $correct);

// AFTER
case 'sata':
    return self::scoreSATA($user, $correct);    // Strict: all-or-nothing per NCLEX
case 'mpr':
case 'msr':
    return self::scoreMPR($user, $correct);     // Partial credit
```

---

## Phase 6: Schema Alignment for Question Bank (3 tasks)

> Prepares the DB for the 4,000-question bank per NGN_NCSBN_Question_Bank_Instructions.pdf

### Task 6.1: Add difficulty_logit to all question tables

**Action:** ALTER TABLE migration for all 9 tables:

```sql
ALTER TABLE traditional ADD COLUMN difficulty_logit DECIMAL(5,2) DEFAULT 0.0;
ALTER TABLE sata ADD COLUMN difficulty_logit DECIMAL(5,2) DEFAULT 0.0;
ALTER TABLE mpr ADD COLUMN difficulty_logit DECIMAL(5,2) DEFAULT 0.0;
ALTER TABLE mmr ADD COLUMN difficulty_logit DECIMAL(5,2) DEFAULT 0.0;
ALTER TABLE btq ADD COLUMN difficulty_logit DECIMAL(5,2) DEFAULT 0.0;
ALTER TABLE dragndrop ADD COLUMN difficulty_logit DECIMAL(5,2) DEFAULT 0.0;
ALTER TABLE dropdown ADD COLUMN difficulty_logit DECIMAL(5,2) DEFAULT 0.0;
ALTER TABLE highlight ADD COLUMN difficulty_logit DECIMAL(5,2) DEFAULT 0.0;
ALTER TABLE `column` ADD COLUMN difficulty_logit DECIMAL(5,2) DEFAULT 0.0;
```

This enables CAT adaptive question selection (`fetch_question.php` already checks for this column dynamically).

### Task 6.2: Add missing metadata columns to question tables

Ensure ALL question tables have the mandatory tag columns from the PDF:

| Column | Type | Tables Missing It |
|--------|------|-------------------|
| `topic` | varchar(255) | btq, mmr |
| `system` | varchar(255) | btq, mmr |
| `cnc` | varchar(255) | btq, mmr |
| `dlevel` | varchar(100) | btq, mmr |
| `narcan` | varchar(255) | ALL tables (new column per PDF) |
| `concept` | varchar(255) | ALL tables (new column per PDF) |

### Task 6.3: Add narcan and concept to result tables

**Files:** `config.php` (lines 132-195)

Add `narcan varchar(255) DEFAULT NULL` and `concept varchar(255) DEFAULT NULL` to:
- `temporary_exam_result` schema
- `exam_results` schema

Update `submit_answer.php` to read and store these fields from question metadata.

---

## Phase 7: Update AGENTS.md (1 task)

### Task 7.1: Add new findings to AGENTS.md priority table

| ID | Priority | File | Issue |
|----|----------|------|-------|
| 19 | **P0** | result.php | SQL injection via raw `$student_id` and `$id` interpolation |
| 20 | P2 | save_history.php | bind_param type mismatch (22 chars / 24 params) + raw mysqli |
| 21 | P2 | index.php | 6 raw `mysqli_query` calls need `db()` migration |
| 22 | P2 | submit_exam.php | Duplicate CREATE TABLE + raw `mysqli_prepare` |
| 23 | P2 | result.php | Full raw mysqli migration needed |
| 24 | **P1** | ScoringEngine.php | Highlight scored as binary (should be partial) |
| 25 | **P1** | ScoringEngine.php | MPR aliased to SATA strict scoring (should be partial) |
| 26 | **P1** | ScoringEngine.php | Dragndrop scored as binary (should be partial) |
| 27 | P2 | All question tables | Missing `difficulty_logit` column disables CAT |
| 28 | P2 | btq, mmr tables | Missing topic/system/cnc/dlevel metadata columns |
| 29 | P3 | All tables | Missing `narcan` and `concept` columns per PDF spec |
| 30 | P2 | save_history.php | Duplicate CREATE TABLE block (lines 27-54) |

Add new coding standard:
```
INCLUDE-002: Use `require_once`, never `include`. session_start() handled exclusively by config.php.
```

---

## Verification Checklist

### After Phase 1 (P0 Security):
- [ ] `history_details.php?examTaken=abc` → dies "No exam selected", no SQL error
- [ ] Student B cannot view Student A's exam via examTaken parameter manipulation
- [ ] XSS payload `<script>alert(1)</script>` in rationale renders as literal text
- [ ] Calculator: `alert(1)=` → "Error"; `1+2=` → `3`
- [ ] result.php: `grep` confirms zero raw `$variable` in any SQL string

### After Phase 2 (P1 Scoring):
- [ ] Bowtie: 3 correct conditions → scores max 2 for conditions section (not 3)
- [ ] `updateTheta()` receives actual `difficulty_logit` from question metadata
- [ ] `grep -rn "includes/scoring.php"` → zero active includes

### After Phase 3 (P2 Medium):
- [ ] POST to `submit_answer.php` with wrong student's examTaken → HTTP 403
- [ ] Partial score (0.5) shows under "Partial" count, not "Wrong"
- [ ] `json_decode(false)` → treated as `[]`, returns HTTP 400
- [ ] `question_type=../../../etc/passwd` → HTTP 400 "Invalid question type"
- [ ] DB failure mid-transaction → both writes rolled back
- [ ] `grep -rn "mysqli_query\|mysqli_prepare\|mysqli_real_escape" student/dashboard/ngn/` → zero matches (except deprecated stubs)

### After Phase 4 (P3 Cleanup):
- [ ] POST without `X-CSRF-Token` header → HTTP 403
- [ ] `grep -rn "CREATE TABLE" student/dashboard/ngn/` → zero matches
- [ ] No `onclick` with inline JSON in history_details.php DOM

### After Phase 5 (Scoring Engine):
- [ ] Highlight: 3/4 correct words → score = 0.75 (not 0 or 1)
- [ ] MPR: 3/5 correct selections → score = 0.60 (not 0)
- [ ] Drag-and-drop: 2/4 correct positions → score = 0.50 (not 0)
- [ ] SATA: any wrong selection → score = 0 (strict, unchanged)

### After Phase 6 (Schema):
- [ ] `SHOW COLUMNS FROM btq LIKE 'difficulty_logit'` → returns row
- [ ] `SHOW COLUMNS FROM mmr LIKE 'topic'` → returns row
- [ ] `SHOW COLUMNS FROM traditional LIKE 'narcan'` → returns row
- [ ] fetch_question.php selects by difficulty distance when column present
- [ ] submit_answer.php stores `narcan` and `concept` in results

---

## Execution Order

```
Phase 1 (P0 Security)       ← BLOCKER: nothing else until done
  |
Phase 2 (P1 Scoring Bugs)   ← Can run in parallel with Phase 3
  |
Phase 3 (P2 Medium Fixes)   ← Bulk migration work
  |
Phase 4 (P3 Cleanup)        ← Can run in parallel with Phase 5
  |
Phase 5 (Scoring Engine)    ← Required before question bank import
  |
Phase 6 (Schema Alignment)  ← Required before question bank import
  |
Phase 7 (AGENTS.md Update)  ← Done incrementally as fixes land
```

**Total: 7 phases, 26 tasks, ~15 files modified**
