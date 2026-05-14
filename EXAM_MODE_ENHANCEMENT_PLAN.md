# Studium — Exam Mode Enhancement Plan

**Document Type:** Feature Implementation Plan for Developer / AI Agent Handoff  
**Repository:** `studium-cat`  
**Date:** April 22, 2026  
**Status:** ✅ Ready for Implementation

---

## ⚠️ Verified: Three Systems, One to Focus On

After a full codebase audit, Studium has **three distinct exam/question systems**. Understanding which is which is critical before touching anything:

| System | Location | Used By | Status |
|--------|----------|---------|--------|
| **Study Mode** | `student/dashboard/question/question1.php` → `question150.php` + `rationale/ans1.php` → `ans150.php` | `topic.php` → linked from study topic selection | ✅ **ACTIVE — DO NOT TOUCH** |
| **Legacy Exam** | `student/dashboard/actual/question1.php` → `question150.php` | No active PHP links found — appears unused/orphaned | ⚠️ Orphaned — no active entry point found |
| **NGN Practice** (Practice Mode) | `student/dashboard/ngn/index.php` | Student dashboard exam button | ✅ **ACTIVE — DO NOT TOUCH** |
| **Exam Mode** (NEW) | `student/dashboard/exam/index.php` | **NEW — Build this** | 🔨 **THIS IS THE NEW EXAM MODE** |

### ✅ Verified: Study Mode uses `question/` and `rationale/` folders

The `student/dashboard/question/` files (question1.php → question150.php) are **Study Mode** — confirmed by:
- `topic.php` line 332: `<form action="question/question1.php" method='GET'>` — launched from topic study selection
- `action.php` lines 24, 29: `header("location:question/question1.php?topic=$topic&qnumber=$question")`
- All 150 `rationale/ans*.php` files link back to `../question/question{N+1}.php` — they form the Study Mode answer-reveal chain

> **DO NOT modify `student/dashboard/question/` or `student/dashboard/rationale/` — these are actively used by Study Mode.**

### ⚠️ Legacy Exam (`actual/`) — Orphaned, Not Study Mode

The `student/dashboard/actual/` files are a completely separate old exam system that:
- Uses the `exam_mode` database table (separate from the NGN's `temporary_exam_result`)
- Has NO active inbound PHP links found anywhere in the codebase
- Is effectively dead code — it can be left as-is or archived later

> **The `actual/` folder is NOT the Study Mode. It is a legacy exam system with no active entry point.**

---

## 🏗️ Architecture Decision: New Standalone `exam/` Folder

**Should we modify the NGN Practice Mode?**

**Answer: NO.** The NGN practice mode (`ngn/`) remains completely untouched. We build a **separate, isolated Exam Mode** under `student/dashboard/exam/` that clones the NGN architecture but implements an entirely new adaptive engine.

| Aspect | NGN (Practice — `ngn/`) | Exam Mode (New — `exam/`) |
|--------|--------------------------|---------------------------|
| Purpose | Practice with immediate feedback | Realistic NCLEX CAT exam |
| Total items | ~16 | **150 fixed** |
| Adaptive engine | None (random selection) | **IRT Rasch (1PL) with Newton-Raphson theta** |
| Scoring | Binary/partial per type | **Partial credit weighted by difficulty** |
| Question source | 8 question tables | **9 tables** (mmr, mpr, sata, btq, dragndrop, dropdown, highlight, mcq, cat) |
| Stopping rule | Manual finish | **85% competency OR mathematically impossible + minimum 75 items** |
| Difficulty selection | Random | **Maximum Fisher Information targeting** |
| Result storage | `exam_results` | **New `exammoderesults` table** |

### Why a separate folder instead of modifying `ngn/`?

1. **Zero risk** — No chance of breaking the existing practice mode
2. **Clean separation** — Exam mode has fundamentally different logic (IRT, stopping rules, 150 items)
3. **Independent deployment** — Can be tested and rolled out without affecting current users
4. **Codebase clarity** — `ngn/` = practice, `exam/` = real exam. No confusion.

---

## 📋 Table of Contents

1. [Mathematical Foundation — Item Response Theory](#1-mathematical-foundation--item-response-theory)
2. [Stopping Rules (NCLEX-Inspired)](#2-stopping-rules-nclex-inspired)
3. [Folder Structure](#3-folder-structure)
4. [Database Schema](#4-database-schema)
5. [Question Pool Assembly](#5-question-pool-assembly)
6. [JavaScript IRT Engine](#6-javascript-irt-engine)
7. [Scoring Models Per Question Type](#7-scoring-models-per-question-type)
8. [Main Controller — exam/index.php](#8-main-controller--examindexphp)
9. [API Endpoints](#9-api-endpoints)
10. [Result Page Analytics](#10-result-page-analytics)
11. [UI/UX Design](#11-uiux-design)
12. [Complete Task Checklist](#12-complete-task-checklist)
13. [Verification Plan](#13-verification-plan)
14. [Appendix A — Data Readiness SQL](#appendix-a--data-readiness-sql)
15. [Appendix B — Exam Flow Diagram](#appendix-b--exam-flow-diagram)
16. [Appendix C — Auto-Termination Edge Cases](#appendix-c--auto-termination-edge-cases)

---

## 1. Mathematical Foundation — Item Response Theory

### 1.1 Why IRT, Not Streaks

The real NCLEX uses the **Rasch (1PL) Item Response Theory model** — not streak-based difficulty adjustment. A streak-based system (e.g., "3 correct → level up") is pedagogically simplistic and does not accurately model student ability.

We implement a proper IRT engine that mirrors the NCLEX CAT algorithm:

### 1.2 The Rasch (1PL) Model

The probability that a student with ability **θ** (theta) answers correctly an item with difficulty **b** is:

```
P(correct | θ, b) = e^(θ - b) / (1 + e^(θ - b))
```

Where:
- **θ (theta)** = student ability estimate (logit scale, range: -4.0 to +4.0)
- **b** = item difficulty parameter (logit scale)

### 1.3 Mapping `dlevel` to Difficulty Logits

Since the database uses string `dlevel` values, we map them to logit scale:

| `dlevel` value | Difficulty logit `b` |
|----------------|---------------------|
| `easy` | **-1.5** |
| `moderate` / `medium` | **0.0** |
| `hard` | **+1.5** |

If a question has a `difficulty_logit` column populated with a non-zero value, we use that instead (finer-grained).

### 1.4 Theta Update — Newton-Raphson Maximum Likelihood Estimation

After each response, we re-estimate θ using **all responses so far** via Newton-Raphson iteration:

```
θ_new = θ_old - Σ(x_j - P_j(θ_old)) / Σ(P_j(θ_old) × (1 - P_j(θ_old)))
```

Where:
- `x_j` = response to item j (1.0 for correct, 0.0 for wrong, or partial credit score 0.0–1.0)
- `P_j(θ)` = probability of correct response on item j given current θ
- The numerator is the first derivative of the log-likelihood
- The denominator is the negative second derivative (Fisher Information)

We run **up to 15 iterations** of Newton-Raphson per update, clamped to [-4.0, +4.0].

**Bayesian MAP prior:** To handle edge cases (all correct or all wrong early in the exam), we use a MAP prior of N(0, 1) which adds `-θ` to the numerator and `+1` to the denominator, ensuring convergence.

### 1.5 Standard Error of Measurement (SEM)

```
SEM = 1 / √(Σ I_j(θ) + 1)
```

Where `I_j(θ) = P_j(θ) × (1 - P_j(θ))` is the Fisher Information for item j. The `+1` in the denominator is the contribution from the MAP prior.

SEM decreases with more items answered. It tells us how confident we are in θ.

### 1.6 Adaptive Item Selection — Maximum Fisher Information

When selecting the next item, we pick the one whose difficulty `b_j` maximizes the information at the current `θ̂`:

```
Information = P(θ̂, b_j) × (1 - P(θ̂, b_j))
```

This is maximized when `b_j ≈ θ̂` (i.e., 50% chance of getting it right). So the algorithm preferentially selects items whose difficulty matches the student's current estimated ability.

### 1.7 Scoring as a Function of Difficulty

Partial scores are **weighted by difficulty** to reward harder questions:

| `dlevel` | Correct Weight | Wrong Penalty Weight |
|----------|---------------|---------------------|
| `easy` | 1.0× base | 1.0× base |
| `moderate` | 1.2× base | 1.0× base |
| `hard` | 1.5× base | 0.8× base |

This means getting a hard question right contributes more to your competency score than an easy one, which is exactly how NCLEX works — answering above-passing-level questions correctly is the path to passing.

---

## 2. Stopping Rules (NCLEX-Inspired)

The exam terminates under these conditions (checked after every answer):

### Rule 1: Competency Achieved (Pass)
```
IF answered >= 75 AND competency_score >= 85%:
    → PASS — Auto-terminate with congratulations
```

### Rule 2: Mathematically Impossible to Reach 85% (Fail)
```
remaining = 150 - answered
max_possible_score = current_weighted_earned + (remaining × 1.5)
max_possible_total = current_weighted_possible + (remaining × 1.5)
max_possible_percent = (max_possible_score / max_possible_total) × 100

IF answered >= 75 AND max_possible_percent < 85%:
    → FAIL — Cannot mathematically achieve 85%. Stop exam.
```

### Rule 3: Maximum Items Reached
```
IF answered >= 150:
    → END — Show final score (pass if >= 85%, fail otherwise)
```

### Rule 4: IRT Confidence (Supplementary — True NCLEX Rule)
```
IF answered >= 75 AND SEM < 0.30:
    IF θ > passing_logit + 1.96 × SEM → PASS (95% CI above passing)
    IF θ < passing_logit - 1.96 × SEM → FAIL (95% CI below passing)
```

> **Note:** Rules 1-3 are the primary rules. Rule 4 is the true NCLEX stopping rule — we implement it alongside the simpler percentage-based rules for a practice exam context.

---

## 3. Folder Structure

```
student/dashboard/exam/
├── index.php                 ← Main exam controller (IRT engine, adaptive pool, 150 items)
├── save_history.php          ← Save answer to temporary_exam_result (exam mode variant)
├── submit_exam.php           ← Transfer temp → exammoderesults on finish
├── state_manager.php         ← Pause/resume with IRT state
├── cancel_exam.php           ← Clear temp data on termination
├── result.php                ← Exam results page with IRT analytics
├── security_violation.php    ← Security violation handler
├── bowtie/
│   └── index.php             ← Clone from ngn/bowtie/index.php
├── dragndrop/
│   └── index.php             ← Clone from ngn/dragndrop/index.php
├── dropdown/
│   └── index.php             ← Clone from ngn/dropdown/index.php
├── highlight/
│   └── index.php             ← Clone from ngn/highlight/index.php
├── mmr/
│   └── index.php             ← Clone from ngn/mmr/index.php
├── mc/
│   └── index.php             ← Clone from ngn/traditional/index.php (renamed folder to mc/)
├── mpr/
│   └── index.php             ← Clone from ngn/mpr/index.php
└── sata/
    └── index.php             ← Clone from ngn/sata/index.php
```

> **Note:** Each question type folder contains a single `index.php` that renders the question in an iframe — identical to the NGN pattern. The `postMessage` protocol is the same. We adjust `require_once` paths to `../../../../config.php` for all subfolder files.

---

## 4. Database Schema

### 4.1 New Table: `exammoderesults`

```sql
CREATE TABLE IF NOT EXISTS `exammoderesults` (
  `id`                  INT(11)       NOT NULL AUTO_INCREMENT,
  `student_id`          INT(11)       NOT NULL,
  `examTaken`           INT(11)       NOT NULL,
  
  -- Question identification
  `question_uid`        VARCHAR(100)  NOT NULL,       -- format: {type}-{id}
  `question_type`       VARCHAR(50)   DEFAULT NULL,
  `question_id`         INT(11)       DEFAULT NULL,
  `question_number`     INT(11)       DEFAULT NULL,
  `source_table`        VARCHAR(50)   DEFAULT NULL,   -- actual table name queried
  
  -- Answer data
  `user_answer`         TEXT,
  `correct_answer`      TEXT,
  `initial_answer`      TEXT          DEFAULT NULL,
  `changes`             JSON          DEFAULT NULL,
  
  -- Scoring
  `isCorrect`           TINYINT(1)    DEFAULT 0,
  `score`               FLOAT         DEFAULT 0,       -- 0.00–1.00 partial credit
  `earned_points`       FLOAT         DEFAULT 0,
  `max_points`          INT(11)       DEFAULT 1,
  `weighted_score`      FLOAT         DEFAULT 0,       -- score × difficulty weight
  
  -- IRT State captured at answer time
  `theta_before`        FLOAT         DEFAULT 0.0,     -- θ before this answer
  `theta_after`         FLOAT         DEFAULT 0.0,     -- θ after this answer
  `sem_after`           FLOAT         DEFAULT 1.0,     -- SEM after this answer
  `item_difficulty`     FLOAT         DEFAULT 0.0,     -- b parameter used
  `item_information`    FLOAT         DEFAULT 0.0,     -- Fisher info at answer time
  
  -- Metadata
  `topic`               VARCHAR(255)  DEFAULT NULL,
  `system`              VARCHAR(255)  DEFAULT NULL,
  `cnc`                 VARCHAR(255)  DEFAULT NULL,
  `dlevel`              VARCHAR(100)  DEFAULT NULL,
  `narcan`              VARCHAR(255)  DEFAULT NULL,
  `concept`             VARCHAR(255)  DEFAULT NULL,
  `rationale`           TEXT,
  
  -- Tracking
  `omitted`             TINYINT(1)    DEFAULT 0,
  `changes_count`       INT(11)       DEFAULT 0,
  `time_taken`          INT(11)       DEFAULT 0,
  `totalTime`           INT(11)       DEFAULT 0,
  `timestamp`           DATETIME      DEFAULT CURRENT_TIMESTAMP,
  
  -- Exam-level running totals (denormalized for quick lookup)
  `running_correct`     INT(11)       DEFAULT 0,       -- cumulative correct at this point
  `running_total`       INT(11)       DEFAULT 0,       -- cumulative answered at this point
  `running_percent`     FLOAT         DEFAULT 0,       -- running competency %
  
  -- Termination metadata (only on last row per exam)
  `is_terminal`         TINYINT(1)    DEFAULT 0,       -- 1 if this was the last question
  `termination_reason`  VARCHAR(100)  DEFAULT NULL,    -- 'pass_85', 'fail_impossible', 'completed_150', 'irt_pass', 'irt_fail', 'pool_exhausted'
  `final_result`        VARCHAR(20)   DEFAULT NULL,    -- 'PASSED' or 'FAILED'
  `final_percent`       FLOAT         DEFAULT NULL,
  `final_theta`         FLOAT         DEFAULT NULL,
  `final_sem`           FLOAT         DEFAULT NULL,
  `total_items_answered` INT(11)      DEFAULT NULL,
  `exam_duration_sec`   INT(11)       DEFAULT NULL,
  
  PRIMARY KEY (`id`),
  KEY `student_exam` (`student_id`, `examTaken`),
  KEY `student_id` (`student_id`),
  KEY `exam_terminal` (`student_id`, `examTaken`, `is_terminal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### 4.2 Modifications to `temporary_exam_state`

We add IRT columns for pause/resume:

```sql
ALTER TABLE `temporary_exam_state`
  ADD COLUMN IF NOT EXISTS `exam_mode`           VARCHAR(20)  DEFAULT 'ngn',
  ADD COLUMN IF NOT EXISTS `adaptive_difficulty`  VARCHAR(10)  DEFAULT 'medium',
  ADD COLUMN IF NOT EXISTS `correct_streak`       INT(11)      DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `wrong_streak`         INT(11)      DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `irt_theta`            FLOAT        DEFAULT 0.0,
  ADD COLUMN IF NOT EXISTS `irt_sem`              FLOAT        DEFAULT 1.0,
  ADD COLUMN IF NOT EXISTS `irt_history`          LONGTEXT     DEFAULT NULL;
```

The `exam_mode` column differentiates NGN practice (`ngn`) from exam mode (`exam`) state rows.

---

## 5. Question Pool Assembly

### 5.1 Source Tables & Column Mapping

| Table Name | Question Type | Display Type | Notes |
|------------|---------------|-------------|-------|
| `mcq` | mc | Multiple Choice | Traditional MCQ — aliased as `traditional` view |
| `cat` | mc | Multiple Choice | **Only where `type IS NULL OR type = ''`** |
| `sata` | sata | SATA | Select all that apply |
| `mmr` | mmr | MMR | Matrix multiple response |
| `mpr` | mpr | MPR | Multiple priority response |
| `btq` | bowtie | Bow-Tie | Bowtie questions |
| `dragndrop` | dragndrop | Drag & Drop | |
| `dropdown` | dropdown | Drop-Down | May also be `dropdown_questions` |
| `highlight` | highlight | Highlight | |

### 5.2 Columns to SELECT from Each Table

```
id, concept, topic, question, choiceA, choiceB, choiceC, choiceD, correct,
rationale, narcan, dlevel, timeadd, cnc, system, image, difficulty_logit
```

### 5.3 Target Distribution — 150 Items

| Source Table | Type | Quota | % |
|-------------|------|-------|---|
| `mcq` | mc | 50 | 33% |
| `cat` (where type IS NULL/empty) | mc | 25 | 17% |
| `sata` | sata | 20 | 13% |
| `mmr` | mmr | 15 | 10% |
| `highlight` | highlight | 10 | 7% |
| `btq` | bowtie | 8 | 5% |
| `dragndrop` | dragndrop | 8 | 5% |
| `dropdown` | dropdown | 7 | 5% |
| `mpr` | mpr | 7 | 5% |
| **TOTAL** | | **150** | **100%** |

### 5.4 Pool Construction PHP Logic

```php
$TARGET_TOTAL = 150;

$examDistribution = [
    ['table' => 'mcq',       'type' => 'mc',        'quota' => 50, 'filter' => ''],
    ['table' => 'cat',       'type' => 'mc',        'quota' => 25, 'filter' => "(type IS NULL OR type = '')"],
    ['table' => 'sata',      'type' => 'sata',      'quota' => 20, 'filter' => ''],
    ['table' => 'mmr',       'type' => 'mmr',       'quota' => 15, 'filter' => ''],
    ['table' => 'highlight', 'type' => 'highlight',  'quota' => 10, 'filter' => ''],
    ['table' => 'btq',       'type' => 'bowtie',    'quota' => 8,  'filter' => ''],
    ['table' => 'dragndrop', 'type' => 'dragndrop',  'quota' => 8,  'filter' => ''],
    ['table' => 'dropdown',  'type' => 'dropdown',   'quota' => 7,  'filter' => ''],
    ['table' => 'mpr',       'type' => 'mpr',        'quota' => 7,  'filter' => ''],
];

// For each entry: SELECT id, dlevel, difficulty_logit FROM `{table}` 
// WHERE {filter} ORDER BY RAND() LIMIT {quota * 3}
// (fetch 3× quota to give the adaptive engine room to select by difficulty)

// Pool structure: array of {id, type, table, dlevel, b}
// where b = difficulty_logit if non-zero, else DIFFICULTY_MAP[dlevel]
```

### 5.5 Fallback Logic

If any table has fewer questions than its quota:
1. Take all available from that table
2. Redistribute the shortfall proportionally to `mcq` and `sata` (largest pools)
3. If still under 150, accept whatever we have (**minimum 50 to start exam**)

---

## 6. JavaScript IRT Engine

This is the heart of the exam. Implemented in `exam/index.php` `<script>` section:

```javascript
// ===== IRT ENGINE =====
class IRTEngine {
    constructor() {
        this.theta = 0.0;           // Current ability estimate
        this.sem = 1.0;             // Standard error of measurement
        this.history = [];          // [{b: difficulty, x: score}, ...]
        this.passingLogit = 0.0;    // Passing standard on logit scale
        this.THETA_MIN = -4.0;
        this.THETA_MAX = 4.0;
    }

    // Rasch probability: P(correct | θ, b)
    probability(theta, b) {
        const exp = Math.exp(theta - b);
        return exp / (1 + exp);
    }

    // Fisher Information for item with difficulty b at ability θ
    information(theta, b) {
        const p = this.probability(theta, b);
        return p * (1 - p);
    }

    // Newton-Raphson MAP theta update using ALL response history
    updateTheta(score, itemDifficulty) {
        this.history.push({ b: itemDifficulty, x: score });

        let theta = this.theta;
        const maxIter = 15;
        const tolerance = 0.001;

        for (let iter = 0; iter < maxIter; iter++) {
            let numerator = 0;
            let denominator = 0;

            // MAP prior contribution: Normal(0, 1)
            numerator -= theta;       // d/dθ of -θ²/2
            denominator += 1;         // second derivative of prior

            for (const item of this.history) {
                const p = this.probability(theta, item.b);
                numerator += (item.x - p);
                denominator += p * (1 - p);
            }

            if (denominator === 0) break;
            const delta = numerator / denominator;
            theta += delta;
            theta = Math.max(this.THETA_MIN, Math.min(this.THETA_MAX, theta));
            if (Math.abs(delta) < tolerance) break;
        }

        this.theta = theta;
        this.sem = this.calculateSEM();
        return this.theta;
    }

    // Standard Error = 1 / sqrt(total Fisher Information + prior)
    calculateSEM() {
        let totalInfo = 1; // Prior contribution
        for (const item of this.history) {
            totalInfo += this.information(this.theta, item.b);
        }
        return 1.0 / Math.sqrt(totalInfo);
    }

    // Select next item: find item with difficulty closest to θ (max info)
    selectNextItem(availablePool) {
        let bestItem = null;
        let bestInfo = -Infinity;

        for (const item of availablePool) {
            const info = this.information(this.theta, item.b);
            if (info > bestInfo) {
                bestInfo = info;
                bestItem = item;
            }
        }
        return bestItem;
    }

    // Check NCLEX-style stopping rule (95% CI)
    checkIRTStopping() {
        if (this.history.length < 75) return null;

        const ciLower = this.theta - 1.96 * this.sem;
        const ciUpper = this.theta + 1.96 * this.sem;

        if (ciLower > this.passingLogit) return 'irt_pass';
        if (ciUpper < this.passingLogit) return 'irt_fail';
        return null;
    }

    // Serialize for pause/resume
    serialize() {
        return { theta: this.theta, sem: this.sem, history: this.history };
    }

    // Restore from serialized state
    restore(state) {
        this.theta = state.theta;
        this.sem = state.sem;
        this.history = state.history;
    }
}
```

### 6.1 Difficulty Mapping

```javascript
const DIFFICULTY_MAP = {
    'easy':     -1.5,
    'moderate':  0.0,
    'medium':    0.0,
    'hard':      1.5
};

function getDifficultyLogit(item) {
    if (item.difficulty_logit && parseFloat(item.difficulty_logit) !== 0) {
        return parseFloat(item.difficulty_logit);
    }
    return DIFFICULTY_MAP[item.dlevel?.toLowerCase()] ?? 0.0;
}
```

### 6.2 Difficulty Weight for Competency Score

```javascript
const DIFFICULTY_WEIGHTS = {
    'easy':     { correct: 1.0, wrong: 1.0 },
    'moderate': { correct: 1.2, wrong: 1.0 },
    'medium':   { correct: 1.2, wrong: 1.0 },
    'hard':     { correct: 1.5, wrong: 0.8 }
};

function getWeightedScore(score, dlevel) {
    const w = DIFFICULTY_WEIGHTS[dlevel?.toLowerCase()] ?? { correct: 1.2, wrong: 1.0 };
    return score * w.correct;
}
```

### 6.3 Competency Score Calculation

```javascript
function calculateCompetencyPercent() {
    let totalWeightedEarned = 0;
    let totalWeightedPossible = 0;

    for (const uid of Object.keys(userAnswers)) {
        const ans = userAnswers[uid];
        const dlevel = ans.dlevel || 'moderate';
        const w = DIFFICULTY_WEIGHTS[dlevel.toLowerCase()] ?? { correct: 1.2 };
        
        totalWeightedEarned += ans.score * w.correct;
        totalWeightedPossible += 1.0 * w.correct;  // max possible for this item
    }

    return totalWeightedPossible > 0
        ? (totalWeightedEarned / totalWeightedPossible) * 100
        : 0;
}
```

### 6.4 Stopping Rule Implementation

```javascript
const PASS_THRESHOLD = 85;         // Percent
const MIN_ITEMS_BEFORE_CHECK = 75; // Don't stop before this (half of 150)
const MAX_ITEMS = 150;

function checkExamTermination() {
    const answered = Object.keys(userAnswers).length;
    if (answered < MIN_ITEMS_BEFORE_CHECK) return null;

    const competency = calculateCompetencyPercent();

    // Rule 1: Pass — 85% achieved
    if (competency >= PASS_THRESHOLD) {
        return { result: 'PASSED', reason: 'pass_85', percent: competency };
    }

    // Rule 2: Fail — Mathematically impossible to reach 85%
    const remaining = MAX_ITEMS - answered;
    const maxFutureWeighted = remaining * 1.5; // best case: all hard, all correct
    const currentWE = getTotalWeightedEarned();
    const currentWP = getTotalWeightedPossible();
    const maxPercent = ((currentWE + maxFutureWeighted) / (currentWP + maxFutureWeighted)) * 100;
    
    if (maxPercent < PASS_THRESHOLD) {
        return { result: 'FAILED', reason: 'fail_impossible', percent: competency };
    }

    // Rule 3: Max items
    if (answered >= MAX_ITEMS) {
        return { 
            result: competency >= PASS_THRESHOLD ? 'PASSED' : 'FAILED',
            reason: 'completed_150', 
            percent: competency 
        };
    }

    // Rule 4: IRT confidence (supplementary)
    const irtResult = irt.checkIRTStopping();
    if (irtResult === 'irt_pass') return { result: 'PASSED', reason: 'irt_pass', percent: competency };
    if (irtResult === 'irt_fail') return { result: 'FAILED', reason: 'irt_fail', percent: competency };

    return null; // Continue exam
}
```

---

## 7. Scoring Models Per Question Type

For items with partial credit (NGN types), the observed score `x_j` is no longer binary:

| Question Type | Scoring Model | `x_j` value for IRT |
|---------------|---------------|---------------------|
| `mc` (traditional MCQ) | Binary 0/1 | 0.0 or 1.0 |
| `sata` | +/- model (per NCLEX NGN spec) | `max(0, correct_selected - wrong_selected) / total_correct` |
| `mmr` | Partial: correct/total | `correct_responses / total_correct_options` |
| `mpr` | Partial: correct/total | `correct_responses / total_correct_options` |
| `highlight` | Per-word partial | `correct_words / total_correct_words` |
| `dragndrop` | Positional partial | `correct_positions / total_positions` |
| `dropdown` | Per-blank partial | `correct_blanks / total_blanks` |
| `bowtie` | Section-capped (2/2/1) | `earned / 5` (normalized to 0.0–1.0) |

The IRT theta update treats `x_j` as the observed response value. This is a key advantage of IRT — it naturally handles partial credit via the Generalized Partial Credit Model approximation.

---

## 8. Main Controller — `exam/index.php`

Cloned from `ngn/index.php` with these major changes:

### 8.1 PHP Section Changes

1. **Pool Construction** — Queries all 9 tables, buckets by difficulty, builds the 150-item pool
2. **IRT State Initialization** — Checks for saved IRT state on resume via `temporary_exam_state` where `exam_mode = 'exam'`
3. **Pool JSON** — Passes full pool JSON to JavaScript (not just question IDs — includes difficulty data)

### 8.2 JavaScript Section Changes

1. **IRT Engine Class** — Full Newton-Raphson theta estimation (see Section 6)
2. **Adaptive `getNextAdaptiveQuestion()`** — Replaces static `questionIds[currentQuestion]` navigation
3. **Stopping Rule Checks** — `checkExamTermination()` called after every answer
4. **Auto-termination UI** — SweetAlert2 modals for PASS (green confetti) and FAIL (red notification)
5. **IRT State Variables** — `theta`, `sem`, `itemHistory[]`, `difficultyMap{}`
6. **Competency Progress Bar** — Real-time weighted score display alongside question progress
7. **Difficulty Badge** — Shows current adaptive difficulty level (color-coded)
8. **Theta Meter** — Shows current ability estimate as a gauge

### 8.3 Pre-Exam Modal

Shows "Exam Mode" branding with exam parameters:
```
📋 Total Items:     150
⏱️ Time Limit:      None (student-paced)
🎯 Passing Rate:    85%
🧠 Exam Mode:       Adaptive CAT (IRT)
📊 Scoring:         Partial credit, difficulty-weighted
🏁 Stopping Rule:   Auto-terminate on pass/fail at ≥75 items
```

### 8.4 Navigation Change — Dynamic Instead of Static

In NGN practice mode, all questions are loaded into `questionIds[]` upfront and navigation is linear. In Exam Mode:

1. First question is selected from moderate difficulty
2. After each answer → IRT updates θ → `selectNextItem()` picks the next question from the remaining pool
3. Questions are appended to `questionIds[]` dynamically (so Previous still works for review)
4. The question navigator sidebar grows as questions are answered

---

## 9. API Endpoints

### 9.1 `exam/save_history.php`

Clone of `ngn/save_history.php`. Saves to `temporary_exam_result`. Additionally sends:
- `theta_before` — θ value before this answer
- `theta_after` — θ value after this answer  
- `sem_after` — SEM after this answer
- `item_difficulty` — b parameter of this item
- `weighted_score` — score × difficulty weight

### 9.2 `exam/submit_exam.php`

Clone of `ngn/submit_exam.php`. Modified to:
- Transfer temp results → `exammoderesults` (NOT `exam_results`)
- Write terminal row with `is_terminal = 1`, `final_result`, `final_theta`, `final_percent`, `termination_reason`, `total_items_answered`, `exam_duration_sec`
- Accept `termination_reason` and `final_stats` from the client

### 9.3 `exam/state_manager.php`

Clone of `ngn/state_manager.php`. Extended to save/restore:
- `irt_theta`, `irt_sem`
- `irt_history` (JSON array of all `{b, x}` pairs for theta recalculation on resume)
- `exam_mode = 'exam'` marker
- Remaining question pool

### 9.4 `exam/cancel_exam.php`

Clone of `ngn/cancel_exam.php`. Clears exam mode temp data where `exam_mode = 'exam'`.

### 9.5 `exam/security_violation.php`

Clone of `ngn/security_violation.php`. Same security enforcement.

---

## 10. Result Page Analytics

The `exam/result.php` page will display:

### 10.1 Hero Section
- **PASSED / FAILED** banner with large percentage (animated entrance)
- Theta ability score with confidence interval visualization
- Number of items answered (out of 150)
- Time taken
- Termination reason explanation:
  - `pass_85` → "Auto-terminated: 85% competency achieved"
  - `fail_impossible` → "Auto-terminated: Mathematically impossible to reach 85%"
  - `completed_150` → "All 150 questions completed"
  - `irt_pass` / `irt_fail` → "IRT 95% confidence interval determination"

### 10.2 Charts (Chart.js)
1. **Theta Progression Line Chart** — θ value over question number (shows adaptive journey)
2. **Difficulty Distribution Pie Chart** — Easy vs Moderate vs Hard items served
3. **Topic Performance Horizontal Bar Chart** — Accuracy per topic
4. **Question Type Radar Chart** — Performance across 8 question types
5. **Competency Score Gauge** — Animated SVG ring showing final weighted %

### 10.3 Summary Statistics
- Total items answered
- Total weighted score
- Average time per question
- Longest correct streak
- Most/least proficient topics
- Most/least proficient question types

### 10.4 Detailed Breakdown Table
- Per-question: number, type, difficulty, your score, weighted score, θ before/after, time spent
- Color-coded rows: correct (green), partial (amber), wrong (red)
- Expandable rationale viewer per row

---

## 11. UI/UX Design

### 11.1 Pre-Exam Start Modal
- Animated gradient background (deep navy → indigo)
- Step badges with exam parameters
- "Start Examination" full-width pill button with shimmer animation
- Scale-in entrance animation

### 11.2 Exam Navigation Bar
- `📖 Studium CAT Exam` branding
- Center panel:
  - Question type badge (color-coded by type)
  - `Q [N] / 150` counter
  - Live streak indicator: `🔥 3 Streak` (correct) or `❌ 2 Miss` (wrong)
  - **Difficulty badge**: pill-shaped, color coded:
    - `EASY` = green (`#10b981`)
    - `MEDIUM` = amber (`#f59e0b`)
    - `HARD` = red (`#ef4444`)
- Right side: user badge, timer, competency meter

### 11.3 Question Navigator Sidebar
- Nav dots that grow dynamically as questions are answered
- Color-coded: correct (green border), partial (amber border), wrong (red border)
- Flag for Review toggle per dot

### 11.4 Bottom Controls Bar
- Previous / Flag for Review / Next buttons
- On last question: "Finish" → green pill button with 🏁 icon

---

## 12. Complete Task Checklist

### Phase 0 — Database & Data Preparation
- [ ] **T0.1** Add `CREATE TABLE exammoderesults` to `config.php`
- [ ] **T0.2** Add new `temporary_exam_state` columns via `_addColIfMissing()` in `config.php`
- [ ] **T0.3** Run the `dlevel` verification SQL query (Appendix A) against all 9 question tables

### Phase 1 — Clone Folder Structure
- [ ] **T1.1** Create `student/dashboard/exam/` directory
- [ ] **T1.2** Clone all 8 question type subfolders from `ngn/` → `exam/` (bowtie, dragndrop, dropdown, highlight, mmr, mpr, sata, mc)
- [ ] **T1.3** Adjust `require_once` paths in all cloned question type `index.php` files to `../../../../config.php`
- [ ] **T1.4** Clone `cancel_exam.php` and `security_violation.php` from `ngn/`

### Phase 2 — Main Controller (`exam/index.php`)
- [ ] **T2.1** Clone `ngn/index.php` as base
- [ ] **T2.2** Rewrite PHP pool construction: query all 9 tables, bucket by difficulty, build 150-item pool with difficulty logit data
- [ ] **T2.3** Implement IRT Engine class in JavaScript (Newton-Raphson, Fisher Information, SEM)
- [ ] **T2.4** Implement `getNextAdaptiveQuestion()` using Maximum Fisher Information selection
- [ ] **T2.5** Replace static `questionIds` navigation with dynamic adaptive selection (questions appended after each answer)
- [ ] **T2.6** Add stopping rule checks after each answer (`checkExamTermination()`)
- [ ] **T2.7** Add auto-termination UI (SweetAlert2 modals for pass/fail with animated transitions)
- [ ] **T2.8** Add IRT state variables and difficulty badge UI to navbar
- [ ] **T2.9** Add competency progress bar alongside question progress ring
- [ ] **T2.10** Redesign pre-exam modal with Exam Mode branding and IRT parameters display

### Phase 3 — API Endpoints
- [ ] **T3.1** Create `exam/save_history.php` with IRT data fields (theta_before/after, sem, item_difficulty, weighted_score)
- [ ] **T3.2** Create `exam/submit_exam.php` → writes to `exammoderesults` with terminal row
- [ ] **T3.3** Create `exam/state_manager.php` with IRT state persistence (irt_theta, irt_sem, irt_history JSON)
- [ ] **T3.4** Create `exam/cancel_exam.php` (clears exam mode temp data)

### Phase 4 — Result Page
- [ ] **T4.1** Create `exam/result.php` with hero pass/fail section and competency score
- [ ] **T4.2** Add theta progression line chart (Chart.js)
- [ ] **T4.3** Add difficulty distribution pie chart and topic performance bar chart
- [ ] **T4.4** Add question type radar chart for cross-type performance analysis
- [ ] **T4.5** Add detailed question-by-question review table with expandable rationale

### Phase 5 — Integration & Polish
- [ ] **T5.1** Add "Exam Mode" button to student dashboard (`student/dashboard/index.php`)
- [ ] **T5.2** Full security audit: right-click disable, tab-switch detection, blur detection (clone from `ngn/`)
- [ ] **T5.3** Mobile responsiveness audit for all new UI components
- [ ] **T5.4** Pause/resume testing with IRT state restoration (verify θ, SEM, pool, timer are all restored correctly)
- [ ] **T5.5** Edge case testing: pool exhaustion, all-correct sequences, all-wrong sequences, partial credit edge cases

---

## 13. Verification Plan

### Automated Tests
1. **IRT Math Verification**: Create a test script that runs Newton-Raphson theta estimate against known datasets and verifies convergence
2. **Stopping Rules**: Simulate 150-question sequences with known outcomes, verify correct termination point
3. **Pool Construction**: Verify all 9 tables are queried correctly, questions are bucketed by difficulty, and fallback logic works

### Manual Verification
- [ ] **V1** Start exam → Verify 150 items are loaded, first question is moderate difficulty
- [ ] **V2** Answer 5 correct → Verify θ increases, next items become harder
- [ ] **V3** Answer 5 wrong → Verify θ decreases, next items become easier
- [ ] **V4** Achieve 85% at question 75 → Verify auto-termination fires (Pass)
- [ ] **V5** Score terribly → Verify "impossible to reach 85%" fires (Fail)
- [ ] **V6** Pause at question 50, resume → Verify θ, SEM, pool, and timer are restored
- [ ] **V7** Complete all 150 → Verify full results with all charts
- [ ] **V8** Tab-switch during exam → Verify security violation fires
- [ ] **V9** Open calculator → Verify it works (no eval, regex-whitelisted)
- [ ] **V10** Right-click during exam → Verify it's disabled

---

## Appendix A — Data Readiness SQL

Run this in phpMyAdmin **before** starting implementation to confirm `dlevel` data quality:

```sql
SELECT 'mcq' AS tbl, dlevel, COUNT(*) AS cnt FROM mcq GROUP BY dlevel
UNION ALL SELECT 'cat', dlevel, COUNT(*) FROM cat WHERE (type IS NULL OR type = '') GROUP BY dlevel
UNION ALL SELECT 'sata', dlevel, COUNT(*) FROM sata GROUP BY dlevel
UNION ALL SELECT 'mmr', dlevel, COUNT(*) FROM mmr GROUP BY dlevel
UNION ALL SELECT 'mpr', dlevel, COUNT(*) FROM mpr GROUP BY dlevel
UNION ALL SELECT 'btq', dlevel, COUNT(*) FROM btq GROUP BY dlevel
UNION ALL SELECT 'dragndrop', dlevel, COUNT(*) FROM dragndrop GROUP BY dlevel
UNION ALL SELECT 'dropdown', dlevel, COUNT(*) FROM dropdown GROUP BY dlevel
UNION ALL SELECT 'highlight', dlevel, COUNT(*) FROM highlight GROUP BY dlevel
ORDER BY tbl, dlevel;
```

**Minimum recommended counts per table per level:**

| Type | Easy | Medium | Hard | Total Minimum |
|------|------|--------|------|---------------|
| `mcq` | 20+ | 20+ | 20+ | 60+ |
| `cat` (filtered) | 10+ | 10+ | 10+ | 30+ |
| `sata` | 10+ | 10+ | 10+ | 30+ |
| `mmr` | 7+ | 7+ | 7+ | 21+ |
| `highlight` | 5+ | 5+ | 5+ | 15+ |
| All others | 3+ | 3+ | 3+ | 9+ each |

**Fix if missing:**
```sql
-- Add dlevel column if missing from any table
ALTER TABLE tablename ADD COLUMN dlevel VARCHAR(50) DEFAULT 'medium';

-- Tag all untagged questions as medium
UPDATE tablename SET dlevel = 'medium' WHERE dlevel IS NULL OR dlevel = '';
```

---

## Appendix B — Exam Flow Diagram

```
Student Dashboard
    ↓ clicks "Start Exam Mode"
    
exam/index.php (Pre-Exam Modal)
    ↓ agrees to rules, clicks Start
    
PHP: Query 9 tables
    ↓ bucket {id, type, table, dlevel, b} by difficulty
    ↓ build 150-item pool → pass as JSON to JS
    
JS: Initialize IRT Engine (θ=0.0, SEM=1.0)
    ↓
    
┌─── EXAM LOOP ──────────────────────────────────────────────┐
│                                                              │
│  IRT: Select item with b ≈ θ (Max Fisher Information)       │
│    ↓                                                         │
│  Load question iframe (bowtie/, mc/, sata/, etc.)           │
│    ↓                                                         │
│  Student answers → iframe postMessage('answered') to parent │
│    ↓                                                         │
│  Score answer (partial credit per type)                      │
│    ↓                                                         │
│  IRT: Update θ via Newton-Raphson MLE                       │
│    ↓                                                         │
│  Calculate weighted competency score                         │
│    ↓                                                         │
│  Check stopping rules:                                       │
│    • ≥85% & ≥75 items → PASSED (auto-terminate)            │
│    • Impossible to reach 85% → FAILED (auto-terminate)      │
│    • 150 items reached → Final determination                │
│    • IRT 95% CI passed/failed → Auto-terminate              │
│    • None → Continue loop                                    │
│                                                              │
└──────────────────────────────────────────────────────────────┘
    ↓
Submit to exammoderesults (with terminal row)
    ↓
exam/result.php
    → Pass/Fail hero banner
    → Theta progression chart
    → Topic/type/difficulty breakdowns
    → Question-by-question review
```

---

## Appendix C — Auto-Termination Edge Cases

| Scenario | Expected Behavior |
|----------|-------------------|
| Student reaches 85% exactly at question 75 | ✅ Auto-terminate immediately (PASS) |
| Student reaches 85% at question 74 | ❌ Do NOT terminate (below minimum of 75) |
| Score was 85%+ then drops below after question 76 | Do NOT retroactively terminate — check is per-question, only fires when threshold is met |
| Student answers all 150 without ever hitting 85% | Normal completion flow → FAILED |
| Student pauses when they had 85%+ at last check | On resume, do NOT auto-terminate retroactively — wait for next answer |
| All question pools exhausted before question 150 | Submit with available questions; `termination_reason = 'pool_exhausted'` |
| Student is auto-terminated and tries to go back | `isExiting = true` blocks all navigation; only result.php redirect works |
| All answers correct from start | θ increases rapidly → harder questions → if 85% maintained at Q75, pass early |
| All answers wrong from start | θ decreases → easier questions → at some point impossible to reach 85%, auto fail |

---

## Coding Standards (Same as AGENTS.md)

All code must conform to these rules:

| Rule | Requirement |
|------|-------------|
| **DB-001** | No raw SQL string interpolation. All user/external values must be `?` bound parameters. |
| **DB-002** | Use `db()` singleton. Never call `mysqli_query()`, `mysqli_prepare()`, or `mysqli_real_escape_string()` in new code. |
| **DB-003** | Verify ownership before data access. Any endpoint accepting an external identifier must check it belongs to `$_SESSION['user_id']`. |
| **OUTPUT-001** | Wrap all PHP values rendered into HTML in `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`. |
| **JS-001** | No `eval()`. Use `JSON.parse()` for data. Use regex-whitelisted `Function()` for calculator arithmetic only. |
| **INCLUDE-001** | Every PHP file begins with `require_once '<path>/config.php'`. Never call `session_start()` manually. |
| **SCORE-001** | All scoring goes through `ScoringEngine::score()`. |

---

*Plan authored: April 22, 2026*  
*Based on full codebase audit + IRT research (NCSBN/NCLEX specifications)*  
*Repository: `ITDepartment1602/studium-cat`*
