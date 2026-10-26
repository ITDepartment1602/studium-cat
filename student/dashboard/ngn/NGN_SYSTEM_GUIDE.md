# Studium NGN — Scoring & Results Plan
### How Points Are Counted and How the Final Result Is Determined

---

> **Purpose of this document**
> This explains how the NGN exam scores each question type and how the final result is decided — including the "Theta" ability system. Written in plain language so anyone on the team can understand it without a technical background.

---

## Table of Contents

1. [Two Things That Make Up the Result](#1-two-things-that-make-up-the-result)
2. [How Each Question Type Is Scored](#2-how-each-question-type-is-scored)
3. [The Theta — Your Ability Meter](#3-the-theta--your-ability-meter)
4. [How the Final Score Percentage Is Calculated](#4-how-the-final-score-percentage-is-calculated)
5. [Score Levels to Display](#5-score-levels-to-display)
6. [Scoring at a Glance — Quick Reference Table](#6-scoring-at-a-glance--quick-reference-table)

---

## 1. Two Things That Make Up the Result

When a student finishes the exam, two separate things are calculated:

**Thing 1 — The Score Percentage**
This is the straightforward number: how many points did the student earn out of the total points available? This is shown as a percentage (e.g., 74%).

**Thing 2 — The Theta (Ability Estimate)**
This is a running measurement of the student's ability level that updates after every single question — not just at the end. It is the engine behind the adaptive nature of the exam. Theta decides whether a student is on track to pass or not, independent of the percentage score.

Both are explained in full below.

---

## 2. How Each Question Type Is Scored

The NGN uses a system called **partial credit** for most question types — meaning students earn points for every part they get right, not just when they get everything perfect.

Each question type has its own scoring rule. Here they are:

---

### Traditional (Multiple Choice) — All or Nothing

**Maximum: 1 point per question**

The student picks one answer from four choices.

| What happens | Points |
|---|---|
| Student picks the correct answer | 1 point |
| Student picks any wrong answer | 0 points |

There is no middle ground here. Right = full point. Wrong = nothing.

---

### SATA (Select All That Apply) — Strict: All or Nothing

**Maximum: 1 point per question**

The student checks every box they believe is correct. There are multiple correct answers.

| What happens | Points |
|---|---|
| Student checks ALL the correct boxes AND no wrong ones | 1 point (full credit) |
| Student checks even ONE wrong box | 0 points — no credit at all |
| Student misses even ONE correct box | 0 points — no credit at all |

**Important:** SATA is strict. It is not like the other types — there is no partial credit here. The student must get it completely right or they earn nothing. This matches how the real NCLEX scores SATA questions.

**Example:**
> 4 boxes are correct. Student checks all 4 correct ones but also checks 1 wrong one.
> Result: **0 points** — because a wrong box was included.

> 4 boxes are correct. Student checks only 3 of the 4 correct ones, zero wrong.
> Result: **0 points** — because one correct box was missed.

> 4 boxes are correct. Student checks exactly all 4, nothing wrong.
> Result: **1 point** — perfect.

---

### MMR (Matrix Multiple Response) — Partial Credit Per Correct Cell

**Maximum: 1 point per question (expressed as a fraction)**

The student checks boxes in a grid. Each row matches to a column.

The score is calculated as:

```
Points earned = Number of correct cells the student checked ÷ Total correct cells in the grid
```

No penalty for checking wrong cells — wrong selections are simply ignored and earn nothing.

| What happens | Effect |
|---|---|
| Student checks a cell that IS correct | Counts toward the score |
| Student checks a cell that is NOT correct | Ignored — no penalty, no points |
| Student leaves a correct cell unchecked | That cell is simply missed |

**Example:**
> The grid has 6 correct cells. Student checks 4 of those 6 correct cells, plus 2 wrong cells.
> Score: 4 correct out of 6 total = **0.67 out of 1 point** = 67% for that question.

---

### MPR (Matrix Priority Response) — Same as MMR

**Maximum: 1 point per question**

Scored exactly the same way as MMR — partial credit for each correct cell found, no penalty for wrong selections.

```
Points earned = Correct cells selected ÷ Total correct cells
```

---

### Bow-Tie — Points Per Correct Item in Each Section

**Maximum: 5 points per question**

The bow-tie has three sections. Each section has a different point cap:

| Section | What it represents | Maximum points for this section |
|---|---|---|
| Left — Conditions | The health problems causing the situation | 2 points |
| Center — Actions | What the nurse should do | 2 points |
| Right — Parameters | What to monitor | 1 point |

Each correct item the student places in a section earns 1 point, up to that section's cap.

**Example:**
> Conditions section: 2 correct items exist. Student gets 2 right → earns **2 points**
> Actions section: 2 correct items exist. Student gets 1 right → earns **1 point**
> Parameters section: 1 correct item exists. Student gets it wrong → earns **0 points**
> Total: **3 out of 5 points** = 60% for that question.

No penalty for wrong placements — only correct placements earn points.

---

### Dropdown — One Point Per Correct Blank

**Maximum: 1 point per question (expressed as a fraction)**

The question has blank spaces. Each blank has its own dropdown menu. The score is:

```
Points earned = Number of blanks answered correctly ÷ Total number of blanks
```

| What happens | Effect |
|---|---|
| Student picks the correct choice for a blank | Counts toward the score |
| Student picks the wrong choice for a blank | That blank earns nothing; no penalty |

**Example:**
> A question has 4 blanks. Student gets 3 right, 1 wrong.
> Score: 3 out of 4 = **0.75 out of 1 point** = 75% for that question.

---

### Drag and Drop — One Point Per Correct Position

**Maximum: 1 point per question (expressed as a fraction)**

The student drags words from a word bank into blank spaces in a sentence. Position matters — the right word must be in the right blank.

```
Points earned = Number of blanks with the correct word in the right position ÷ Total blanks
```

| What happens | Effect |
|---|---|
| Correct word in the correct blank | Counts toward the score |
| Wrong word in a blank (or blank left empty) | That position earns nothing; no penalty |

**Example:**
> A sentence has 3 blanks. Student places the right word in 2 of them.
> Score: 2 out of 3 = **0.67 out of 1 point** = 67% for that question.

---

### Highlight — Partial Credit Per Correctly Highlighted Phrase

**Maximum: 1 point per question (expressed as a fraction)**

A paragraph is shown. The student clicks to highlight phrases they believe are important. The score is:

```
Points earned = Number of correct phrases highlighted ÷ Total correct phrases in the passage
```

| What happens | Effect |
|---|---|
| Student highlights a phrase that SHOULD be highlighted | Counts toward the score |
| Student highlights a phrase that should NOT be highlighted | Ignored — no penalty, no points |
| Student misses a phrase that should be highlighted | That phrase earns nothing |

**Example:**
> 5 phrases should be highlighted. Student highlights 3 correct ones and 2 wrong ones.
> Score: 3 out of 5 = **0.60 out of 1 point** = 60% for that question.

---

## 3. The Theta — Your Ability Meter

### What is Theta?

Theta is a number that represents how strong a student's nursing knowledge is, based on how they are performing question by question. Think of it like a **skill gauge** or a **thermometer of ability.**

- The scale runs from **−4** (lowest) to **+4** (highest)
- Every student **starts at 0** — right in the middle — before any questions are answered
- The **passing line is also at 0** — meaning a student needs to stay above 0 to be on track to pass

---

### How Theta Moves After Each Question

Every time a student answers a question, the system adjusts their theta up or down. Here are the rules:

**When the student answers CORRECTLY:**
- Theta goes **up by 0.3**
- If the question was harder than the student's current level: theta goes **up by an extra 0.1** (harder questions are rewarded more)

**When the student answers INCORRECTLY:**
- Theta goes **down by 0.3**
- If the question was easier than the student's current level: theta goes **down by an extra 0.1** (missing easy questions is punished a bit more)

**The boundaries:**
- Theta can never go above **+4** no matter how well a student does
- Theta can never go below **−4** no matter how poorly a student does

---

### A Simple Analogy

Think of theta like a **elevator in a building with 8 floors.** Floor 0 is the ground floor — that is the passing level. The elevator starts at ground floor (0).

- Answer a question correctly → elevator goes up one level
- Answer a question correctly AND it was a hard question → elevator goes up a little higher
- Answer a question wrong → elevator goes down one level
- Answer a question wrong AND it was an easy question → elevator goes down a little more

The goal is to stay above the ground floor (0) by the end of the exam.

---

### How the System Gets More Confident Over Time

The system also tracks how confident it is in the theta estimate. Early in the exam, with only a few answers, the system is not very sure — the estimate could be off. As the student answers more questions, the system becomes more and more confident that the theta is accurate.

This confidence is measured by something called **Standard Error (SE):**
- At the start (0 questions answered): SE = 1.0 — not very confident
- As more questions are answered: SE gets smaller
- The formula: SE = 1 ÷ square root of (questions answered + 1)

In plain terms: the more questions answered, the tighter and more reliable the ability estimate becomes.

---

### The Stopping Rule — When Does the System Know Enough?

The system continuously checks whether it has gathered enough evidence to make a confident pass or fail determination. It does this by looking at a **confidence range** around the student's theta.

The confidence range is: **theta − (1.96 × SE)** to **theta + (1.96 × SE)**

This represents a 95% confidence window — the system is 95% sure the student's true ability falls somewhere in this range.

Three possible outcomes after each question:

| Outcome | What it means |
|---|---|
| **The entire confidence range is ABOVE 0** | The system is 95% confident the student is above the passing line → signals **PASS** |
| **The entire confidence range is BELOW 0** | The system is 95% confident the student is below the passing line → signals **FAIL** |
| **The range overlaps with 0** | Not confident enough yet → keep testing |

**Example:**
> After 10 questions, theta = 1.2, SE = 0.29
> Confidence range: 1.2 − (1.96 × 0.29) = **0.63** to 1.2 + (1.96 × 0.29) = **1.77**
> Entire range is above 0 → system signals **PASS**

> After 10 questions, theta = −1.5, SE = 0.29
> Confidence range: −1.5 − 0.57 = **−2.07** to −1.5 + 0.57 = **−0.93**
> Entire range is below 0 → system signals **FAIL**

> After 5 questions, theta = 0.2, SE = 0.41
> Confidence range: 0.2 − 0.80 = **−0.60** to 0.2 + 0.80 = **1.00**
> Range overlaps 0 → **keep testing**

---

## 4. How the Final Score Percentage Is Calculated

The percentage score is separate from theta. It is a straightforward sum calculated at the end of the exam.

### The Formula

```
Final Score % = (Total Points Earned across all questions ÷ Total Points Possible across all questions) × 100
```

### Step by Step

1. Each answered question has an **earned** value and a **maximum** value
2. Add up all the earned values
3. Add up all the maximum values
4. Divide earned by maximum
5. Multiply by 100 and round to the nearest whole number

### Example Calculation

| Question | Type | Earned | Max |
|---|---|---|---|
| Q1 | Traditional | 1.00 | 1 |
| Q2 | SATA | 0.00 | 1 |
| Q3 | MMR | 0.67 | 1 |
| Q4 | Bow-Tie | 3.00 | 5 |
| Q5 | Dropdown | 0.75 | 1 |
| Q6 | Drag and Drop | 0.67 | 1 |
| Q7 | Highlight | 0.60 | 1 |
| Q8 | Traditional | 0.00 | 1 |
| Q9 | MPR | 1.00 | 1 |
| Q10 | MMR | 0.50 | 1 |
| **Totals** | | **8.19** | **14** |

```
8.19 ÷ 14 × 100 = 58.5% → rounds to 59%
```

---

## 5. Score Levels to Display

Once the final percentage is calculated, show the student a label and color:

| Score Range | Label | Color |
|---|---|---|
| 90% and above | Excellent | Green |
| 75% to 89% | Good | Blue |
| 50% to 74% | Average | Yellow / Amber |
| Below 50% | Needs Improvement | Red |

---

## 6. Scoring at a Glance — Quick Reference Table

| Question Type | Max Points | How Partial Credit Works | Penalty for Wrong Answers? |
|---|---|---|---|
| Traditional (Multiple Choice) | 1 | None — all or nothing | No |
| SATA (Select All That Apply) | 1 | None — all or nothing, strict | No |
| MMR (Matrix Multiple Response) | 1 | Fraction of correct cells found | No |
| MPR (Matrix Priority Response) | 1 | Fraction of correct cells found | No |
| Bow-Tie | 5 | Points per correct item per section (Conditions: 0–2, Actions: 0–2, Parameters: 0–1) | No |
| Dropdown | 1 | Fraction of correct blanks filled | No |
| Drag and Drop | 1 | Fraction of correct words in correct positions | No |
| Highlight | 1 | Fraction of correct phrases selected | No |

**Note:** No question type in this system subtracts points for wrong answers. Wrong selections are simply ignored — they neither help nor hurt the score.

---

### Theta Summary

| What | Value / Rule |
|---|---|
| Starting theta | 0.0 (middle of the scale) |
| Passing threshold | 0.0 |
| Theta range | −4.0 to +4.0 |
| Correct answer adjustment | +0.3 |
| Wrong answer adjustment | −0.3 |
| Bonus for correct on hard question | +0.1 extra |
| Penalty for wrong on easy question | −0.1 extra |
| Pass signal | 95% confidence interval entirely above 0 |
| Fail signal | 95% confidence interval entirely below 0 |

---

*Studium NGN — Scoring & Results Plan*
*June 2026*
