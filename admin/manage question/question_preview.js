/**
 * question_preview.js — Client-side question parsing and validation.
 * Used by json_import.php (validate a pasted import) and pending.php (review the approval
 * queue). Defines the global TYPE_CONFIG: one entry per question type with validate(),
 * schemaHelp and example. Previews are the real student pages, see device_preview.js.
 */

/* ══════════════════════════════════════════════════════
   0. SHARED PARSING HELPERS (mirrored server-side in json_import_action.php)
   ══════════════════════════════════════════════════════ */
function escapeHtml(s) {
  return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
function parseLetteredLines(raw) {
  const text = Array.isArray(raw) ? raw.join('\n') : String(raw ?? '');
  const lines = text.split(/\r?\n/).map(l => l.trim()).filter(l => l !== '');
  const letterRe = /^([A-Za-z])[.)]\s*(.+)$/;
  return lines.map(line => {
    const m = line.match(letterRe);
    return m ? { letter: m[1].toUpperCase(), text: m[2].trim() } : { letter: null, text: line };
  });
}
function parseLetters(raw) {
  const text = Array.isArray(raw) ? raw.join(',') : String(raw ?? '');
  return text.split(',').map(s => s.trim().toUpperCase()).filter(s => s !== '');
}
/**
 * Resolves a "pick the correct ones" section from either of two accepted shapes:
 *   1) an array of objects: [{"text": "...", "correct": true}, ...] — no letters needed, correctness is per-item.
 *   2) the lettered-string convention: items = "A. text\nB. text", correct = "A,C" (separate field).
 * Returns { entries: [{letter, text, correct}], errors: string[] }.
 */
function resolveLetteredSection(itemsRaw, correctRaw) {
  const errors = [];
  let entries = [];
  const isObjectArray = Array.isArray(itemsRaw) && itemsRaw.length > 0 &&
    itemsRaw.every(it => it && typeof it === 'object' && !Array.isArray(it) && 'text' in it);

  const letterLineRe = /^([A-Za-z])[.)]\s*(.+)$/;
  const isPlainTextArray = !isObjectArray && Array.isArray(itemsRaw) && itemsRaw.length > 0 &&
    itemsRaw.every(it => typeof it === 'string' || typeof it === 'number') &&
    !itemsRaw.some(it => letterLineRe.test(String(it).trim()));

  if (isObjectArray) {
    const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    let badText = false;
    entries = itemsRaw.map((it, idx) => {
      const text = String(it.text ?? '').trim();
      if (!text) badText = true;
      return { letter: letters[idx] || ('X' + idx), text, correct: !!it.correct };
    });
    if (badText) errors.push('every item needs non-empty "text".');
  } else if (isPlainTextArray) {
    const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const correctList = (Array.isArray(correctRaw) ? correctRaw : parseFlexList(correctRaw)).map(v => String(v).trim().toLowerCase());
    let badText = false;
    entries = itemsRaw.map((it, idx) => {
      const text = String(it).trim();
      if (!text) badText = true;
      return { letter: letters[idx] || ('X' + idx), text, correct: correctList.includes(text.toLowerCase()) };
    });
    if (badText) errors.push('every item needs non-empty text.');
  } else {
    const lines = parseLetteredLines(itemsRaw);
    const badLine = lines.some(l => l.letter === null);
    if (badLine) errors.push('every item line must start with a letter followed by "." or ")".');
    const letterMap = {};
    lines.forEach(l => { if (l.letter !== null) letterMap[l.letter] = l.text; });
    const correctLetters = parseLetters(correctRaw);
    const badLetters = correctLetters.filter(l => !(l in letterMap));
    if (badLetters.length) errors.push('correct references letter(s) not found in items: ' + badLetters.join(', ') + '.');
    entries = lines.map(l => ({ letter: l.letter, text: l.text, correct: l.letter !== null && correctLetters.includes(l.letter) }));
  }

  return { entries, errors };
}
function parseFlexList(raw) {
  if (Array.isArray(raw)) return raw.map(v => String(v).trim()).filter(v => v !== '');
  const text = String(raw ?? '').trim();
  if (!text) return [];
  const parts = text.includes('\n') ? text.split(/\r?\n/) : text.split(',');
  return parts.map(s => s.trim()).filter(s => s !== '');
}
const DLEVEL_MAP = { easy: 'Easy', moderate: 'Medium', medium: 'Medium', med: 'Medium', hard: 'Hard' };
function checkDlevel(raw, errors, warnings) {
  const dlevelRaw = String(raw ?? '').trim();
  const norm = DLEVEL_MAP[dlevelRaw.toLowerCase()] || '';
  if (!dlevelRaw) { warnings.push('No "dlevel" provided — defaulting to Medium.'); return 'Medium'; }
  if (!norm) { errors.push('"dlevel" value "' + dlevelRaw + '" is not recognized (expected Easy, Medium/Moderate, or Hard).'); return dlevelRaw; }
  if (norm.toLowerCase() !== dlevelRaw.toLowerCase()) warnings.push('"dlevel" "' + dlevelRaw + '" will be normalized to "' + norm + '".');
  return norm;
}
/** Validates + extracts the fields common to every NGN question type. */
function checkCommon(q, errors, warnings) {
  const question = String(q.question ?? '').trim();
  if (!question) errors.push('Missing "question" text.');
  const rationale = String(q.rationale ?? '').trim();
  if (!rationale) errors.push('Missing "rationale".');
  const topic = String(q.topic ?? '').trim();
  if (!topic) warnings.push('No "topic" provided.');
  const concept = String(q.concept ?? '').trim();
  if (!concept) warnings.push('No "concept" provided.');
  const dlevel = checkDlevel(q.dlevel, errors, warnings);

  let difficultyLogit = 0;
  if (q.difficulty_logit !== undefined && q.difficulty_logit !== null && q.difficulty_logit !== '') {
    const n = Number(q.difficulty_logit);
    if (isNaN(n)) errors.push('"difficulty_logit" must be a number.');
    else { difficultyLogit = n; if (n < -4 || n > 4) warnings.push('"difficulty_logit" ' + n + ' is outside the typical -4..+4 range.'); }
  }

  let furtherinfo = q.furtherinfo;
  if (furtherinfo !== undefined && !Array.isArray(furtherinfo) && typeof furtherinfo !== 'string') {
    errors.push('"furtherinfo" must be a string or an array of strings.');
  }

  let tabs = [];
  if (q.tabs !== undefined) {
    if (!Array.isArray(q.tabs)) {
      errors.push('"tabs" must be an array of {title, content}.');
    } else {
      q.tabs.forEach((t, ti) => {
        if (!t || typeof t !== 'object' || !t.title) { errors.push('tabs[' + ti + '] is missing a "title".'); return; }
        const content = Array.isArray(t.content) ? t.content : (t.content !== undefined ? [String(t.content)] : []);
        tabs.push({ title: String(t.title), content: content.map(String) });
      });
    }
  }

  return {
    question, rationale, topic, concept, dlevel, difficultyLogit,
    furtherinfo: furtherinfo === undefined ? '' : furtherinfo, tabs,
    system: String(q.system ?? '').trim(), cnc: String(q.cnc ?? '').trim(), narcan: String(q.narcan ?? '').trim(),
    image: String(q.image ?? '').trim()
  };
}
function mkResult(i, valid, errors, warnings, question, normalized) {
  return { index: i, valid, errors, warnings, question, normalized: valid ? normalized : null };
}
function notObjectResult(q, i) {
  return { index: i, valid: false, errors: ['Not a valid JSON object.'], warnings: [], question: '', normalized: null };
}

/* ══════════════════════════════════════════════════════
   1. TYPE_CONFIG — validate() + schemaHelp + example, per type
   ══════════════════════════════════════════════════════ */
const TYPE_CONFIG = {

/* ── Multiple Choice (MCQ) ────────────────────────── */
traditional: {
  schemaHelp: '<strong>Required:</strong> <code>question</code>, <code>choices</code> (alias: <code>options</code>), <code>correct</code>, <code>rationale</code>, <code>topic</code>, <code>concept</code>, <code>dlevel</code> (Easy/Medium/Hard).<br><code>choices</code> accepts <strong>either</strong> a plain array of option text (e.g. <code>["Obtain ECG","Administer oxygen",...]</code>) with <code>correct</code> as the matching option text, <strong>or</strong> a lettered string (one <code>A. text</code> per line — min 2) with <code>correct</code> as a single letter (e.g. <code>"C"</code>), <strong>or</strong> an array of <code>{"text": "...", "correct": true}</code> objects — in that case <code>correct</code> is not needed. Exactly one option must be marked correct.<br><strong style="color:var(--ap-text-3);font-weight:400;">Optional:</strong> <code>system</code>, <code>cnc</code>, <code>narcan</code>, <code>difficulty_logit</code>, <code>furtherinfo</code> (array of tips, wrap key terms in <code>%like this%</code>), <code>tabs</code> (array of <code>{title, content:[]}</code>), <code>image</code>. (No <code>case_title</code> for this type.)',
  validate(q, i) {
    if (typeof q !== 'object' || q === null || Array.isArray(q)) return notObjectResult(q, i);
    const errors = [], warnings = [];
    const c = checkCommon(q, errors, warnings);
    const { entries, errors: secErrors } = resolveLetteredSection(q.choices ?? q.options, q.correct);
    errors.push(...secErrors);
    if (entries.length < 2) errors.push('"choices" must contain at least 2 entries.');
    const correctEntries = entries.filter(e => e.correct);
    if (correctEntries.length !== 1) errors.push('Exactly one option must be marked correct (via "correct" letter, or "correct": true on the item) — found ' + correctEntries.length + '.');
    const valid = errors.length === 0;
    return mkResult(i, valid, errors, warnings, c.question, valid ? { ...c, options: entries.map(e => e.text), correct: correctEntries[0] ? correctEntries[0].text : '' } : null);
  },
  example: [{
    topic: "Medication Administration Safety", concept: "Fundamentals",
    question: "A nurse is preparing to administer digoxin to a client. Which assessment finding would require the nurse to withhold the dose and notify the provider?",
    choices: "A. Apical heart rate of 68 bpm\nB. Apical heart rate of 52 bpm\nC. Blood pressure of 122/78 mmHg\nD. Respiratory rate of 16 breaths/min",
    correct: "B",
    rationale: "B (CORRECT): An apical heart rate below %60 bpm% is a sign of %digoxin toxicity% or excessive cardiac depression, and the dose should be withheld with the provider notified.\nA (INCORRECT): A heart rate of 68 bpm is within normal limits for digoxin administration.\nC (INCORRECT): This blood pressure is within normal limits and is not a reason to withhold digoxin.\nD (INCORRECT): This respiratory rate is normal and unrelated to digoxin safety parameters.",
    system: "Cardiovascular", cnc: "Physiological Integrity - Pharmacological and Parenteral Therapies", narcan: "Digoxin Administration Safety", dlevel: "Medium",
    furtherinfo: ["Always assess the %apical pulse% for one full minute before administering %digoxin% or other cardiac glycosides."],
    difficulty_logit: -0.2
  }]
},

/* ── SATA ─────────────────────────────────────────── */
sata: {
  schemaHelp: '<strong>Required:</strong> <code>question</code>, <code>items</code>, <code>correct</code>, <code>rationale</code>, <code>topic</code>, <code>concept</code>, <code>dlevel</code> (Easy/Medium/Hard).<br><code>items</code> accepts <strong>either</strong> a lettered string (one <code>A. text</code> per line — min 2) with <code>correct</code> as letters (e.g. <code>"A,C,E"</code>), <strong>or</strong> an array of <code>{"text": "...", "correct": true}</code> objects — in that case <code>correct</code> is not needed.<br><strong style="color:var(--ap-text-3);font-weight:400;">Optional:</strong> <code>case_title</code>, <code>system</code>, <code>cnc</code>, <code>narcan</code>, <code>difficulty_logit</code>, <code>furtherinfo</code> (array of tips, wrap key terms in <code>%like this%</code>), <code>tabs</code> (array of <code>{title, content:[]}</code>), <code>image</code>.',
  validate(q, i) {
    if (typeof q !== 'object' || q === null || Array.isArray(q)) return notObjectResult(q, i);
    const errors = [], warnings = [];
    const c = checkCommon(q, errors, warnings);
    const { entries, errors: secErrors } = resolveLetteredSection(q.items, q.correct);
    errors.push(...secErrors);
    if (entries.length < 2) errors.push('"items" must contain at least 2 entries.');
    const correctEntries = entries.filter(e => e.correct);
    if (correctEntries.length === 0) errors.push('At least one item must be marked correct (via "correct" letters, or "correct": true on the item).');
    const valid = errors.length === 0;
    return mkResult(i, valid, errors, warnings, c.question, valid ? { ...c, items: entries.map(e => e.text), correct: correctEntries.map(e => e.text), case_title: String(q.case_title ?? '').trim() } : null);
  },
  example: [{
    topic: "Postoperative Neurovascular Assessment", case_title: "Postoperative Day 1 - Total Knee Arthroplasty", concept: "Adult Health",
    question: "A nurse is assessing a client on postoperative day 1 following a left total knee arthroplasty. Which findings require the nurse to notify the surgeon immediately? Select 4 answers.",
    items: "A. Left foot temperature cooler than the right foot\nB. Surgical dressing with moderate serosanguineous drainage\nC. Absent dorsalis pedis pulse on the operative leg\nD. Pain rated 4/10, controlled with prescribed analgesia\nE. Increased calf swelling and warmth in the operative leg\nF. Client reports numbness and tingling in the operative foot",
    correct: "A,C,E,F",
    rationale: "A (CORRECT): A left foot that is cooler than the right foot suggests decreased %arterial perfusion% and may indicate %compartment syndrome%.\nB (INCORRECT): Moderate %serosanguineous drainage% is expected on postoperative day 1.\nC (CORRECT): An absent %dorsalis pedis pulse% is a critical sign of %vascular compromise%.\nD (INCORRECT): Pain controlled with prescribed %analgesia% is expected.\nE (CORRECT): Increased calf swelling and warmth are classic signs of %deep vein thrombosis (DVT)%.\nF (CORRECT): New numbness and tingling suggest %neurovascular compromise%.",
    system: "Musculoskeletal / Neurovascular", cnc: "Physiological Integrity - Reduction of Risk Potential", narcan: "Post-operative Neurovascular Complications", dlevel: "Easy",
    furtherinfo: ["%Compartment syndrome% is a surgical emergency caused by increased pressure within a closed fascial space.", "%Deep vein thrombosis (DVT)% is a major risk after %total knee arthroplasty%."],
    difficulty_logit: -0.55,
    tabs: [{ title: "Nurse Notes", content: ["Client is postoperative day 1 following left total knee arthroplasty.", "Pain tolerable at rest, worsens with passive range of motion."] }, { title: "Vital Signs", content: ["BP: 128/78 mmHg", "HR: 82 bpm", "Temp: 37.1 C (98.8 F)", "SpO2: 96% on room air"] }],
    image: ""
  }]
},

/* ── MPR ──────────────────────────────────────────── */
mpr: {
  schemaHelp: '<strong>Required:</strong> <code>question</code> (phrase "Select N" to cap selections, or omit for select-all), <code>items</code>, <code>correct</code>, <code>rationale</code>, <code>topic</code>, <code>concept</code>, <code>dlevel</code>.<br><code>items</code> accepts <strong>either</strong> a lettered string (one <code>A. text</code> per line — min 2) with <code>correct</code> as letters (e.g. <code>"A,C"</code>), <strong>or</strong> an array of <code>{"text": "...", "correct": true}</code> objects.<br><strong style="color:var(--ap-text-3);font-weight:400;">Optional:</strong> <code>case_title</code>, <code>system</code>, <code>cnc</code>, <code>narcan</code>, <code>difficulty_logit</code>, <code>furtherinfo</code>, <code>tabs</code>, <code>image</code>.',
  validate(q, i) {
    if (typeof q !== 'object' || q === null || Array.isArray(q)) return notObjectResult(q, i);
    const errors = [], warnings = [];
    const c = checkCommon(q, errors, warnings);
    const { entries, errors: secErrors } = resolveLetteredSection(q.items, q.correct);
    errors.push(...secErrors);
    if (entries.length < 2) errors.push('"items" must contain at least 2 entries.');
    const correctEntries = entries.filter(e => e.correct);
    if (correctEntries.length === 0) errors.push('At least one item must be marked correct (via "correct" letters, or "correct": true on the item).');
    const valid = errors.length === 0;
    return mkResult(i, valid, errors, warnings, c.question, valid ? { ...c, items: entries.map(e => ({ letter: e.letter, text: e.text })), correct: correctEntries.map(e => e.letter), case_title: String(q.case_title ?? '').trim() } : null);
  },
  example: [{
    topic: "Post-Op Priority Actions", case_title: "Immediate Postoperative Care", concept: "Adult Health",
    question: "A nurse is caring for a client 2 hours after abdominal surgery. Select 3 priority actions.",
    items: "A. Assess surgical dressing for bleeding\nB. Offer a full solid meal immediately\nC. Monitor vital signs every 15 minutes\nD. Encourage ambulation to the cafeteria unassisted\nE. Auscultate bowel sounds",
    correct: "A,C,E",
    rationale: "A (CORRECT): Assessing the dressing detects early %hemorrhage%.\nB (INCORRECT): Oral intake is advanced gradually, not a full meal immediately.\nC (CORRECT): Frequent %vital sign% monitoring detects early complications.\nD (INCORRECT): Unassisted ambulation this soon risks %falls%.\nE (CORRECT): Auscultating bowel sounds assesses return of %peristalsis%.",
    system: "Perioperative", cnc: "Physiological Integrity", narcan: "Post-Op Monitoring", dlevel: "Medium",
    furtherinfo: ["Early recognition of %postoperative hemorrhage% relies on frequent dressing and vital sign checks."],
    difficulty_logit: 0
  }]
},

/* ── MMR ──────────────────────────────────────────── */
mmr: {
  schemaHelp: '<strong>Required:</strong> <code>question</code>, <code>columns</code> (array, ≥1), <code>rows</code> (array, ≥2), <code>correct</code> (object — <code>{"ColumnName": ["Row Name", ...]}</code>), <code>rationale</code>, <code>topic</code>, <code>concept</code>, <code>dlevel</code>.<br><strong style="color:var(--ap-text-3);font-weight:400;">Optional:</strong> <code>case_title</code>, <code>system</code>, <code>cnc</code>, <code>narcan</code>, <code>difficulty_logit</code>, <code>furtherinfo</code>, <code>tabs</code>, <code>image</code>.',
  validate(q, i) {
    if (typeof q !== 'object' || q === null || Array.isArray(q)) return notObjectResult(q, i);
    const errors = [], warnings = [];
    const c = checkCommon(q, errors, warnings);
    const columns = parseFlexList(q.columns);
    const rows = parseFlexList(q.rows);
    if (columns.length < 1) errors.push('"columns" must have at least 1 entry.');
    if (rows.length < 2) errors.push('"rows" must have at least 2 entries.');
    const correctRaw = (q.correct && typeof q.correct === 'object' && !Array.isArray(q.correct)) ? q.correct : null;
    if (!correctRaw || Object.keys(correctRaw).length < 1) errors.push('"correct" must be an object mapping a column name to an array of correct row names.');
    const correctNorm = {};
    if (correctRaw) {
      Object.keys(correctRaw).forEach(colKey => {
        const matchCol = columns.find(c2 => c2.trim().toLowerCase() === String(colKey).trim().toLowerCase());
        if (!matchCol) { errors.push('"correct" references column "' + colKey + '" not found in columns.'); return; }
        const rowListArr = Array.isArray(correctRaw[colKey]) ? correctRaw[colKey] : [correctRaw[colKey]];
        const validRows = [];
        rowListArr.forEach(r => {
          const rTrim = String(r).trim();
          const matchRow = rows.find(rr => rr.trim().toLowerCase() === rTrim.toLowerCase());
          if (!matchRow) { errors.push('correct["' + colKey + '"] references row "' + rTrim + '" not found in rows.'); return; }
          validRows.push(matchRow);
        });
        if (validRows.length) correctNorm[matchCol] = validRows;
      });
    }
    if (!errors.length && Object.keys(correctNorm).length === 0) errors.push('"correct" did not resolve to any valid column/row pair.');
    const valid = errors.length === 0;
    return mkResult(i, valid, errors, warnings, c.question, valid ? { ...c, columns, rows, correct: correctNorm, case_title: String(q.case_title ?? '').trim() } : null);
  },
  example: [{
    topic: "Electrolyte Imbalance Findings", case_title: "Electrolyte Imbalance Assessment", concept: "Adult Health",
    question: "For each finding, check the column(s) it is associated with.",
    columns: ["Hyperkalemia", "Hyponatremia"],
    rows: ["Peaked T waves", "Muscle cramps", "Confusion", "Diarrhea"],
    correct: { "Hyperkalemia": ["Peaked T waves", "Muscle cramps"], "Hyponatremia": ["Confusion", "Diarrhea"] },
    rationale: "Peaked T waves and muscle cramps are classic %hyperkalemia% findings. Confusion and diarrhea are associated with %hyponatremia% due to cerebral edema and fluid shifts.",
    system: "Renal / Electrolyte", cnc: "Physiological Integrity", narcan: "Electrolyte Imbalances", dlevel: "Hard",
    furtherinfo: ["Always correlate %ECG changes% with serum electrolyte trends before intervening."]
  }]
},

/* ── Bow-Tie ──────────────────────────────────────── */
btq: {
  schemaHelp: 'Three banks, each with its own required correct-answer count (matches the fixed diagram: 2 actions, 1 condition, 2 parameters — 5 pts total). Each bank accepts <strong>either</strong> a lettered string (<code>actionToTake: "A. text\\nB. text"</code>) with a separate <code>actionToTake_correct: "A,C"</code> letters field, <strong>or</strong> directly an array of <code>{"text": "...", "correct": true}</code> objects (no separate <code>_correct</code> field needed) — same for <code>potentialConditions</code> and <code>parameterToMonitor</code>.<br><strong>Required:</strong> <code>question</code>, <code>potentialConditions</code> (exactly 1 correct), <code>actionToTake</code> (exactly 2 correct), <code>parameterToMonitor</code> (exactly 2 correct), <code>rationale</code>, <code>topic</code>, <code>concept</code>, <code>dlevel</code>.<br><strong style="color:var(--ap-text-3);font-weight:400;">Optional:</strong> <code>system</code>, <code>cnc</code>, <code>narcan</code>, <code>difficulty_logit</code>, <code>furtherinfo</code>, <code>tabs</code>, <code>image</code>. (No <code>case_title</code> for this type.)',
  validate(q, i) {
    if (typeof q !== 'object' || q === null || Array.isArray(q)) return notObjectResult(q, i);
    const errors = [], warnings = [];
    const c = checkCommon(q, errors, warnings);
    function section(itemsKey, correctKey, requiredCount, label) {
      const { entries, errors: secErrors } = resolveLetteredSection(q[itemsKey], q[correctKey]);
      secErrors.forEach(e => errors.push(label + ': ' + e));
      if (entries.length < 2) errors.push(label + ' must have at least 2 items.');
      const correctCount = entries.filter(e => e.correct).length;
      if (correctCount !== requiredCount) errors.push(label + ' needs exactly ' + requiredCount + ' correct answer(s) (found ' + correctCount + ').');
      return entries;
    }
    const actions = section('actionToTake', 'actionToTake_correct', 2, 'Actions to Take');
    const conditions = section('potentialConditions', 'potentialConditions_correct', 1, 'Potential Conditions');
    const params = section('parameterToMonitor', 'parameterToMonitor_correct', 2, 'Parameters to Monitor');
    const valid = errors.length === 0;
    return mkResult(i, valid, errors, warnings, c.question, valid ? { ...c, actions, conditions, params } : null);
  },
  example: [{
    topic: "Sepsis Recognition", concept: "Critical Care",
    question: "Complete the bow-tie for a client with suspected sepsis.",
    potentialConditions: "A. Septic shock\nB. Simple urinary tract infection\nC. Anxiety disorder",
    potentialConditions_correct: "A",
    actionToTake: "A. Obtain blood cultures before antibiotics\nB. Administer broad-spectrum antibiotics\nC. Apply ice packs to lower fever\nD. Give oral fluids only",
    actionToTake_correct: "A,B",
    parameterToMonitor: "A. Serum lactate level\nB. Urine output\nC. Hair color",
    parameterToMonitor_correct: "A,B",
    rationale: "The client's presentation is most consistent with %septic shock%. Blood cultures must be drawn %before% antibiotics to avoid false negatives, and broad-spectrum antibiotics should begin within 1 hour. %Serum lactate% and %urine output% are key perfusion markers to trend.",
    system: "Critical Care", cnc: "Physiological Integrity", narcan: "Sepsis Management", dlevel: "Hard",
    furtherinfo: ["The %Surviving Sepsis Campaign% recommends the 1-hour bundle: cultures, lactate, broad-spectrum antibiotics, and fluid resuscitation."]
  }]
},

/* ── Drag & Drop ──────────────────────────────────── */
dragndrop: {
  schemaHelp: '<strong>Required:</strong> <code>question</code>, <code>items</code> (array or comma/newline string — the word bank, ≥2), <code>correct</code> (array or comma/newline string — one per blank, in order), <code>rationale</code>, <code>topic</code>, <code>concept</code>, <code>dlevel</code>. If your question text contains 5+ underscore blanks (<code>_____</code>), the count must exactly match <code>correct</code>.<br><strong style="color:var(--ap-text-3);font-weight:400;">Optional:</strong> <code>system</code>, <code>cnc</code>, <code>narcan</code>, <code>difficulty_logit</code>, <code>furtherinfo</code>, <code>tabs</code>, <code>image</code>. (No <code>case_title</code>.) If the question has 5+ underscore blanks, the preview shows them as inline drop zones inside the sentence; otherwise it falls back to a numbered ordering list below the question.',
  validate(q, i) {
    if (typeof q !== 'object' || q === null || Array.isArray(q)) return notObjectResult(q, i);
    const errors = [], warnings = [];
    const c = checkCommon(q, errors, warnings);
    const items = parseFlexList(q.items);
    const correct = parseFlexList(q.correct);
    if (items.length < 2) errors.push('"items" must have at least 2 entries.');
    if (correct.length < 1) errors.push('"correct" must have at least 1 entry (one per blank, in order).');
    const badCorrect = correct.filter(cv => !items.some(it => it.toLowerCase() === cv.toLowerCase()));
    if (badCorrect.length) errors.push('"correct" value(s) not found among items: ' + badCorrect.join(', ') + '.');
    const blankMarkers = (c.question.match(/_{5,}/g) || []).length;
    if (blankMarkers > 0 && blankMarkers !== correct.length) errors.push('question has ' + blankMarkers + ' blank marker(s) (5+ underscores) but correct has ' + correct.length + ' entries — they must match exactly.');
    const valid = errors.length === 0;
    return mkResult(i, valid, errors, warnings, c.question, valid ? { ...c, items, correct } : null);
  },
  example: [{
    topic: "Steps of Wound Irrigation", concept: "Fundamentals",
    question: "Arrange the steps of wound irrigation in the correct order.",
    items: ["Don sterile gloves", "Position irrigation catheter 1 inch from wound", "Irrigate with prescribed solution", "Pat dry with sterile gauze", "Perform hand hygiene"],
    correct: ["Perform hand hygiene", "Don sterile gloves", "Position irrigation catheter 1 inch from wound", "Irrigate with prescribed solution", "Pat dry with sterile gauze"],
    rationale: "%Hand hygiene% always comes first to reduce infection risk, followed by donning sterile gloves to maintain %sterile technique%. The catheter is positioned close enough to irrigate effectively without contaminating the tip, then the solution is instilled, and the wound is dried last to prepare for a fresh dressing.",
    system: "Wound Care", cnc: "Safe and Effective Care Environment", narcan: "Wound Irrigation Procedure", dlevel: "Medium"
  }]
},

/* ── Dropdown Cloze ───────────────────────────────── */
dropdown: {
  schemaHelp: '<strong>Required:</strong> <code>question</code>, <code>options</code> (array or comma/newline string, ≥2 — the full choice pool shown for every blank), <code>correct_words</code> (array or comma/newline string — one per blank, in order, each must be present in <code>options</code>), <code>rationale</code>, <code>topic</code>, <code>concept</code>, <code>dlevel</code>. If your question text contains blank markers (<code>___</code>, <code>[[blank]]</code>, <code>{{blank}}</code>, or <code>[BLANK_1]</code>/<code>[BLANK_2]</code>/…), the count must exactly match <code>correct_words</code>.<br><strong style="color:var(--ap-text-3);font-weight:400;">Optional:</strong> <code>system</code>, <code>cnc</code>, <code>narcan</code>, <code>difficulty_logit</code>, <code>furtherinfo</code>, <code>tabs</code>, <code>image</code>.',
  validate(q, i) {
    if (typeof q !== 'object' || q === null || Array.isArray(q)) return notObjectResult(q, i);
    const errors = [], warnings = [];
    const c = checkCommon(q, errors, warnings);
    const options = parseFlexList(q.options);
    const correctWords = parseFlexList(q.correct_words);
    if (options.length < 2) errors.push('"options" must have at least 2 entries.');
    if (correctWords.length < 1) errors.push('"correct_words" must have at least 1 entry (one per blank, in order).');
    const bad = correctWords.filter(cv => !options.some(o => o.toLowerCase() === cv.toLowerCase()));
    if (bad.length) errors.push('"correct_words" value(s) not present in options: ' + bad.join(', ') + '.');
    const blankMarkers = (c.question.match(/_{3,}|\[\[blank\]\]|\{\{blank\}\}|\[blank_?\d*\]/gi) || []).length;
    if (blankMarkers > 0 && blankMarkers !== correctWords.length) errors.push('question has ' + blankMarkers + ' blank marker(s) but correct_words has ' + correctWords.length + ' entries — they must match exactly.');
    const valid = errors.length === 0;
    return mkResult(i, valid, errors, warnings, c.question, valid ? { ...c, options, correct_words: correctWords } : null);
  },
  example: [{
    topic: "DKA Manifestations", concept: "Adult Health",
    question: "A client in diabetic ketoacidosis is expected to have a blood glucose _____ and a blood pH _____.",
    options: ["above 250 mg/dL", "below 70 mg/dL", "above 7.35", "below 7.35"],
    correct_words: ["above 250 mg/dL", "below 7.35"],
    rationale: "%Diabetic ketoacidosis% presents with %hyperglycemia% (glucose typically above 250 mg/dL) and %metabolic acidosis% (pH below 7.35) from ketone accumulation.",
    system: "Endocrine", cnc: "Physiological Adaptation", narcan: "Diabetic Ketoacidosis", dlevel: "Medium"
  }]
},

/* ── Highlight ────────────────────────────────────── */
highlight: {
  schemaHelp: '<strong>Required:</strong> <code>question</code> (the instruction shown above the passage), <code>passage</code> (the text body), <code>options</code> (array or comma/newline string, ≥2 phrases — each must appear verbatim in <code>passage</code>), <code>correct_words</code> (subset of <code>options</code>), <code>rationale</code>, <code>topic</code>, <code>concept</code>, <code>dlevel</code>.<br><strong style="color:var(--ap-text-3);font-weight:400;">Optional:</strong> <code>maxHighlights</code> (defaults to the number of correct phrases), <code>system</code>, <code>cnc</code>, <code>narcan</code>, <code>difficulty_logit</code>, <code>furtherinfo</code>, <code>tabs</code>, <code>image</code>.',
  validate(q, i) {
    if (typeof q !== 'object' || q === null || Array.isArray(q)) return notObjectResult(q, i);
    const errors = [], warnings = [];
    const c = checkCommon(q, errors, warnings);
    const passage = String(q.passage ?? '').trim();
    if (!passage) errors.push('Missing "passage".');
    const options = parseFlexList(q.options);
    const correctWords = parseFlexList(q.correct_words);
    if (options.length < 2) errors.push('"options" must have at least 2 phrases.');
    if (correctWords.length < 1) errors.push('"correct_words" must have at least 1 phrase.');
    const bad = correctWords.filter(cv => !options.some(o => o.toLowerCase() === cv.toLowerCase()));
    if (bad.length) errors.push('"correct_words" value(s) not in options: ' + bad.join(', ') + '.');
    if (passage) {
      const missing = options.filter(o => passage.toLowerCase().indexOf(o.toLowerCase()) === -1);
      if (missing.length) errors.push('option phrase(s) not found in passage: ' + missing.join(', ') + '.');
    }
    const maxHighlights = (Number.isFinite(Number(q.maxHighlights)) && Number(q.maxHighlights) > 0) ? Math.floor(Number(q.maxHighlights)) : Math.max(1, correctWords.length);
    const valid = errors.length === 0;
    return mkResult(i, valid, errors, warnings, c.question, valid ? { ...c, passage, options, correct_words: correctWords, maxHighlights } : null);
  },
  example: [{
    topic: "Signs of Compartment Syndrome", concept: "Adult Health",
    question: "Highlight the findings in the nurse's note below that indicate compartment syndrome.",
    passage: "The client reports severe pain unrelieved by medication. The affected limb is pale and cool to touch. Capillary refill is 4 seconds. The client is able to move all fingers without difficulty.",
    options: ["severe pain unrelieved by medication", "pale and cool to touch", "Capillary refill is 4 seconds", "move all fingers without difficulty"],
    correct_words: ["severe pain unrelieved by medication", "pale and cool to touch", "Capillary refill is 4 seconds"],
    maxHighlights: 3,
    rationale: "%Compartment syndrome% classically presents with severe, unrelenting pain, pallor, coolness, and delayed %capillary refill% due to compromised %perfusion%. Intact finger movement is reassuring and not a warning sign at this stage.",
    system: "Musculoskeletal", cnc: "Reduction of Risk Potential", narcan: "Compartment Syndrome", dlevel: "Medium"
  }]
}

};
