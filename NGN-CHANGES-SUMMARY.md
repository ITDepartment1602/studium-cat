# NGN Module — What Was Fixed, Improved, and Prepared

> **Date:** April 11, 2026
> **Audience:** Non-technical stakeholders, administrators, and content managers
> **Branch:** `fix/refactoring`

---

## Overview

The NGN (Next Generation NCLEX) exam module — the part of Studium-CAT where students take their adaptive nursing board exams — underwent a major round of fixes and improvements. This document explains **what was wrong**, **what was done about it**, and **what it means for students and administrators**, without getting into code specifics.

The work was organized into 7 phases, from most urgent (security) to least urgent (preparing for a bigger question bank).

---

## What Is the NGN Module?

The NGN module is where students take **Computerized Adaptive Testing (CAT)** exams that simulate the Next Generation NCLEX. It supports 8 different question formats:

- Traditional multiple choice
- Select All That Apply (SATA)
- Highlight text questions
- Drag-and-drop ordering
- Dropdown fill-in-the-blank
- Matrix/Multiple Response (MMR)
- Multiple Priority Response (MPR)
- Bowtie (clinical judgment questions)

---

## Phase 1 — Critical Security Fixes

> These were the highest priority. They were addressed first because they posed a direct risk to student data.

### 1. Students could see each other's exam history

**What was wrong:** If Student A knew Student B's exam ID number (a simple integer like `42`), they could type it into the URL and see Student B's full exam results — answers, scores, and rationale.

**What was fixed:** Every page that shows exam results now verifies that the exam being requested actually belongs to the logged-in student. If it doesn't match, access is denied immediately.

**What it means:** Student exam data is now private. No student can view another student's results by guessing a number.

---

### 2. Attackers could manipulate the database through URLs

**What was wrong:** Several pages were building database queries by directly pasting values from the web address (URL) into the query. This is a well-known vulnerability called **SQL injection** — it allows a malicious user to trick the system into reading, modifying, or deleting data it shouldn't have access to.

Affected pages: exam history details, exam results page.

**What was fixed:** All database queries now use **prepared statements** — a technique where the query structure is locked in place before any user-provided value is inserted. The database treats those values as plain data, not as instructions.

**What it means:** It is no longer possible to manipulate the database through URLs. Student and exam data is protected.

---

### 3. Harmful scripts could be injected into exam reviews

**What was wrong:** When a question's rationale (the explanation shown after answering) contained special characters like `<` or `>`, those characters could be interpreted as web code. A malicious rationale entry in the database could cause a script to run in a student's browser when they viewed their exam history — this is called **Cross-Site Scripting (XSS)**.

**What was fixed:** All rationale text is now **escaped** before being shown on screen. Special characters are converted to their display-only equivalents, so they appear as text rather than being executed as code.

**What it means:** Even if someone inserts unusual characters into a question's rationale, students will see it as plain readable text. No harmful scripts can run.

---

### 4. The exam calculator could run arbitrary code

**What was wrong:** The built-in calculator on the exam page was using a programming function called `eval()` to process whatever the student typed. While intended only for math, `eval()` can execute any code — including malicious code if a student entered it deliberately.

**What was fixed:** The calculator now validates input with a strict rule: **only numbers and basic math symbols are allowed** (`0–9`, `+`, `-`, `*`, `/`, `.`, `(`, `)`). Anything else is rejected with an "Error" message before any calculation happens.

**What it means:** The calculator is safe for math and nothing else. Attempting to type in anything other than a math expression now produces an error.

---

## Phase 2 — Scoring Bug Fixes

> These fixes ensure that exam scores are calculated correctly.

### 5. Bowtie questions were over-awarding points

**What was wrong:** Bowtie questions are clinical judgment scenarios with three sections: Conditions (up to 2 points), Actions (up to 2 points), and Parameters (up to 1 point) — a maximum of 5 points total. The scoring logic was not enforcing the per-section limits. A student who correctly answered 3 Conditions (instead of the maximum 2) could receive 3 points for that section, breaking the intended 5-point cap.

**What was fixed:** Each section now has its own independent maximum enforced before totaling. Conditions max out at 2, Actions max out at 2, Parameters max out at 1 — regardless of how many items a student selects correctly.

**What it means:** Bowtie scores are now fair and consistent with NCLEX scoring standards.

---

### 6. The adaptive difficulty system was not using question difficulty

**What was wrong:** The exam uses a system called **Computerized Adaptive Testing (CAT)** — it adjusts which question comes next based on how well the student is doing (their "ability estimate," called theta). Part of how theta is updated depends on the **difficulty** of the question just answered. However, the difficulty value was not being passed correctly, so theta was being updated as if every question had the same difficulty (zero).

**What was fixed:** When a student answers a question, the system now correctly reads that question's difficulty rating from the database and uses it to update the student's ability estimate. Getting a hard question right boosts theta more than getting an easy one right.

**What it means:** The adaptive system now works as designed. Questions should feel appropriately challenging as the exam progresses.

---

### 7. Two conflicting scoring systems existed at the same time

**What was wrong:** There were two separate files responsible for calculating scores, and they had different (sometimes contradictory) rules for the same question types.

**What was fixed:** The older, redundant scoring file was replaced with a set of "forwarding stubs" — it no longer contains any real logic and simply passes everything to the one authoritative scoring engine. There is now a single source of truth for all scoring.

**What it means:** Scores are consistent regardless of which part of the system triggers the calculation.

---

## Phase 3 — Data Integrity & Reliability Fixes

> These changes prevent data loss and make the system more robust.

### 8. Submitting an answer for someone else's exam was possible

**What was wrong:** The API endpoint that records a student's answer did not verify that the exam being submitted to belonged to the student currently logged in. A student who knew another student's exam ID could submit answers on their behalf.

**What was fixed:** Before recording any answer, the system now checks that the exam ID belongs to the currently logged-in student. If it doesn't match, the request is rejected.

**What it means:** Students can only submit answers to their own active exams.

---

### 9. Partial exam saves could leave corrupted data

**What was wrong:** When recording an answer, the system made two separate database writes (saving the answer, then updating the exam state). If the connection dropped or an error occurred between the two writes, the system could end up in an inconsistent state — for example, an answer recorded but the exam position not updated.

**What was fixed:** Both writes now happen inside a **database transaction** — an all-or-nothing operation. If anything goes wrong, both writes are cancelled and the database stays in its previous clean state.

**What it means:** Exam data is either saved completely or not at all. No half-saved states.

---

### 10. Invalid question types could access unintended data

**What was wrong:** If a request for a question came in with an unrecognized question type, the system would fall back to using whatever string was provided as a database table name. A crafted request could potentially use this to probe tables that should be off-limits.

**What was fixed:** The system now checks the question type against a strict list of known types. Any unrecognized type immediately returns an error — the request goes no further.

**What it means:** Only valid, recognized question types can be fetched. Unexpected inputs are safely rejected.

---

### 11. Answer change history was tracked but never saved

**What was wrong:** The database had a column for storing a history of when a student changed their answer during an exam (useful for reviewing student decision-making). However, this data was never actually written to the column — it was computed and then discarded.

**What was fixed:** The change history is now properly saved alongside each answer submission.

**What it means:** Administrators and reviewers can now see if a student changed their answer and how many times, providing richer insight into exam behavior.

---

### 12. Duplicate database setup code was scattered across files

**What was wrong:** Several files each contained their own copy of the instructions for creating the database tables needed for exams. This was redundant and a maintenance risk — if the table structure needed to change, it would need updating in multiple places.

**What was fixed:** All table creation logic now lives in one place (`config.php`). Every other file relies on that single definition.

**What it means:** The database structure is easier to maintain and less likely to get out of sync.

---

## Phase 4 — Code Quality & Cleanup

> These changes improve maintainability and close smaller gaps.

### 13. State-saving during exams was using an outdated database approach

**What was wrong:** The file responsible for saving the student's progress mid-exam (which question they're on, how much time is left, etc.) was using an older, less safe way of interacting with the database. It also had code that would expose internal database error details to the browser — a security concern.

**What was fixed:** The file now uses the same modern, secure database approach as the rest of the system. Error messages no longer reveal internal details.

**What it means:** Mid-exam progress saves are now consistent with the rest of the system and do not leak internal information.

---

### 14. Question review buttons used an unsafe coding pattern

**What was wrong:** Each "View" button in the exam history page was embedding a block of question data directly inside an HTML button attribute using an `onclick` handler. This pattern is considered poor practice — it can cause display bugs with certain characters, it's harder to maintain, and it bypasses content security policies.

**What was fixed:** The question data is now stored in a dedicated HTML data attribute (`data-payload`) and properly escaped. A single event listener handles all button clicks and reads from that attribute.

**What it means:** The review buttons work the same way for students, but the underlying code is cleaner, more reliable, and safer.

---

## Phase 5 — Scoring Engine Completeness

> These fixes ensure that all 8 question types score correctly, especially for partial credit.

### 15. Highlight questions were graded as all-or-nothing

**What was wrong:** Highlight questions ask students to select specific words or phrases in a passage. Previously, a student who highlighted 3 out of 4 correct words received a score of **0** — the same as someone who got all 4 wrong.

**What was fixed:** Highlight questions now award **partial credit proportional to how many correct words were selected**. Selecting 3 of 4 correct words now earns 75% of the points.

**What it means:** Students are rewarded for partial knowledge on highlight questions, which is consistent with how NCLEX scores them.

---

### 16. MPR questions were using the wrong scoring rule

**What was wrong:** Multiple Priority Response (MPR) questions allow partial credit — a student who identifies 3 out of 5 correct priorities should earn 60% of the points. Instead, the system was applying SATA's "strict" rule (all-or-nothing), meaning any missed item resulted in a score of zero.

**What was fixed:** MPR questions now use their own partial-credit scoring, separate from SATA. SATA (Select All That Apply) remains strict as required by NCLEX rules.

**What it means:** MPR scores now correctly reflect partial understanding. SATA scoring is unchanged.

---

### 17. Drag-and-drop questions were graded as all-or-nothing

**What was wrong:** Drag-and-drop questions ask students to order or place items in the correct positions. A student who placed 4 out of 5 items correctly received 0 points.

**What was fixed:** Drag-and-drop questions now use **positional partial scoring** — each item placed in the correct position earns a proportional share of the points.

**What it means:** Students receive credit for each correctly placed item, not just for getting the entire ordering perfect.

---

## Phase 6 — Preparing for the 4,000-Question Bank

> These changes prepare the database to support the full NGN Question Bank as described in the NCSBN Instructions PDF.

### 18. Questions had no difficulty rating column

**What was wrong:** The CAT system selects each question based on how closely its difficulty matches the student's current ability level. For this to work, every question needs a stored **difficulty rating** (`difficulty_logit`). This column was missing from all 9 question tables.

**What was fixed:** The system now automatically adds the difficulty rating column to all question tables the first time it runs if the column isn't already there. Once questions are imported with their difficulty values, the adaptive selection will work fully.

**What it means:** The adaptive system can now work as intended once the question bank is loaded with difficulty data.

---

### 19. Bowtie and Matrix questions were missing metadata fields

**What was wrong:** Bowtie (`btq`) and Matrix (`mmr`) question tables were missing several descriptive fields that all other question types already had: Topic, Body System, Clinical/Nursing Concept (CNC), and Difficulty Level label. Without these, those question types couldn't be filtered or reported on the same way as others.

**What was fixed:** These four fields are now automatically added to the Bowtie and Matrix tables if they don't already exist.

**What it means:** All 8 question types now have a consistent set of metadata fields, enabling uniform reporting and filtering across question types.

---

### 20. Two new classification fields added across all tables

**What was wrong:** The NGN Question Bank Instructions PDF specifies two additional classification fields for every question: **Narcan** (a clinical tag used for opioid-related questions) and **Concept** (the underlying nursing concept being tested). Neither field existed anywhere in the database.

**What was fixed:** Both fields are now automatically added to:
- All 9 question type tables
- The active exam result table (temporary storage during an exam)
- The permanent exam results table

The system also now reads and saves these values when a student submits an answer.

**What it means:** The database is ready to store the full question metadata required by the NCSBN specification. When the 4,000-question bank is imported, these fields will be populated and available for filtering and analytics.

---

## Summary Table

| # | Category | What Changed | Impact on Students | Impact on Admins |
|---|----------|-------------|-------------------|-----------------|
| 1 | Security | Exam ownership check on history page | Exam history is now private | Reduced data breach risk |
| 2 | Security | SQL injection fixed on multiple pages | No visible change | Database is protected from attacks |
| 3 | Security | XSS fix on rationale display | No visible change | Malicious content can't run |
| 4 | Security | Calculator no longer uses eval() | "Error" on non-math input | Code execution risk removed |
| 5 | Scoring | Bowtie per-section caps enforced | Scores now reflect true performance | Scores match NCLEX standards |
| 6 | CAT | Adaptive system uses question difficulty | Better question calibration | Theta updates are more accurate |
| 7 | Scoring | Single scoring engine enforced | Consistent scores | Easier to maintain scoring rules |
| 8 | Security | Answer ownership check on submission | Can't submit to others' exams | Exam integrity improved |
| 9 | Reliability | DB transaction wrap on answer save | No half-saved answers | Cleaner data, no corruption |
| 10 | Security | Invalid question types rejected | No visible change | Attack surface reduced |
| 11 | Data | Answer change history now saved | — | Reviewers can see answer changes |
| 12 | Cleanup | Duplicate DB table setup removed | No visible change | Easier maintenance |
| 13 | Cleanup | State-save file modernized | No visible change | No internal errors shown to users |
| 14 | Cleanup | Review buttons use safe data pattern | No visible change | More reliable HTML |
| 15 | Scoring | Highlight: partial credit per word | Higher scores for partial knowledge | Fairer grading |
| 16 | Scoring | MPR: partial credit (not strict) | Higher scores for partial knowledge | Matches NCLEX MPR rules |
| 17 | Scoring | Drag-and-drop: partial per position | Higher scores for partial ordering | Fairer grading |
| 18 | CAT | Difficulty column added to all tables | Better adaptive question selection | Ready for question bank import |
| 19 | Schema | Metadata fields added to BTQ & MMR | — | Uniform reporting across all types |
| 20 | Schema | Narcan & Concept fields added everywhere | — | Ready for NCSBN question bank spec |

---

## What Has Not Changed

- The look and feel of the exam for students is **unchanged**
- Login, registration, and dashboard pages are **not affected**
- Quiz and other non-NGN modules are **not affected**
- No student data was deleted or altered in any of these changes

---

## What Comes Next

The following items were identified but are **not yet implemented**:

- **CSRF Protection** — An additional security layer that prevents certain types of forged requests to the exam submission endpoints. Currently deferred; the API is only accessible to authenticated sessions.
- **Question Bank Import** — The database is now ready to receive the 4,000-question NCSBN bank. The actual import process (uploading, mapping, and validating questions) is a separate step.
- **Difficulty Data Population** — The difficulty rating column now exists on all question tables, but existing questions still have a rating of `0.0` (the default). Meaningful adaptive selection requires these values to be populated per question.
