<?php
include('../../config.php');
require_once __DIR__ . '/question_validators.php';
date_default_timezone_set('Asia/Manila');
if (session_status() === PHP_SESSION_NONE) session_start();

$type = $_GET['type'] ?? '';
if (!array_key_exists($type, $QUESTION_TABLE_MAP)) { header('Location: index.php'); exit; }
$typeLabel = $QUESTION_TYPE_LABELS[$type];

/* ── Page setup ──────────────────────────────────── */
$ap_base    = '../';
$active_nav = 'questions';
$ap_title   = 'Import Questions — ' . $typeLabel;
$ap_extra_head = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
  .ji-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;padding:0 20px 30px;}
  @media(max-width:1050px){.ji-grid{grid-template-columns:1fr;}}
  .ji-card{background:var(--ap-card);border-radius:var(--ap-radius-lg);border:1px solid var(--ap-border);overflow:hidden;margin-bottom:16px;}
  .ji-card-hd{padding:14px 18px;border-bottom:1px solid var(--ap-border);font-size:0.78rem;font-weight:700;color:var(--ap-text-2);letter-spacing:.03em;display:flex;align-items:center;justify-content:space-between;gap:8px;}
  .ji-card-bd{padding:18px;}
  #jsonInput{width:100%;box-sizing:border-box;font-family:"SFMono-Regular",Consolas,"Liberation Mono",Menlo,monospace;font-size:0.78rem;line-height:1.5;resize:vertical;min-height:360px;background:var(--ap-bg-2);}
  .ji-schema{font-size:0.72rem;color:var(--ap-text-3);line-height:1.65;background:var(--ap-bg-2);border-radius:8px;padding:12px 14px;margin-top:10px;}
  .ji-schema code{background:rgba(124,58,237,0.1);color:#7C3AED;padding:1px 5px;border-radius:4px;font-size:0.71rem;}
  .ji-schema .req{color:#dc2626;font-weight:700;}
  .ji-qcard{border:1.5px solid var(--ap-border);border-radius:10px;padding:14px 16px;margin-bottom:10px;}
  .ji-qcard.valid{border-color:#bbf7d0;background:#f0fdf4;}
  .ji-qcard.invalid{border-color:#fecaca;background:#fef2f2;}
  .ji-qcard.dupe{border-color:#fcd34d;background:#fffbeb;}
  .ji-flag{display:flex;gap:7px;align-items:flex-start;font-size:0.74rem;line-height:1.55;border-radius:8px;padding:8px 11px;margin-top:8px;}
  .ji-flag i{flex-shrink:0;margin-top:2px;}
  .ji-flag.dupe{background:#fef3c7;color:#92400e;}
  .ji-flag.missing{background:#fff7ed;color:#9a3412;}
  .ji-selectbar{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:10px 13px;margin-bottom:12px;background:var(--ap-bg-2);border:1px solid var(--ap-border);border-radius:9px;font-size:0.78rem;}
  .ji-selectbar input[type=checkbox]{width:17px;height:17px;accent-color:#0d9488;cursor:pointer;}
  #jiSelectHint{font-size:0.74rem;color:var(--ap-text-3);}
  #jiSelectHint b{color:#b45309;}
  .ji-flag.checking{background:#eff6ff;color:#1d4ed8;margin:0 0 12px;}
  .ji-flag.failed{background:#fef2f2;color:#b91c1c;margin:0 0 12px;}
  .ji-flag code{background:rgba(154,52,18,.12);padding:1px 5px;border-radius:4px;font-weight:700;}
  .ji-qcard-top{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;}
  .ji-qcard-title{font-size:0.8rem;font-weight:700;color:var(--ap-text-1);}
  .ji-qcard-snippet{font-size:0.76rem;color:var(--ap-text-3);margin-bottom:8px;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
  .ji-issue-list{margin:6px 0 0;padding-left:18px;font-size:0.74rem;line-height:1.6;}
  .ji-issue-list.err{color:#dc2626;}
  .ji-issue-list.warn{color:#b45309;}
  .ji-empty{font-size:0.8rem;color:var(--ap-text-3);text-align:center;padding:40px 20px;}
  .ji-mismatch{border-color:#fcd34d;background:#fffbeb;}
  .ji-mismatch code{background:rgba(180,83,9,.12);color:#92400e;padding:1px 6px;border-radius:4px;font-weight:700;}
  #previewFrame{width:100%;height:78vh;border:none;background:#fff;}
</style>';
$ap_topbar_actions = '
<a href="list.php?type='.urlencode($type).'" class="ap-btn ap-btn-ghost ap-btn-sm">
  <i class="bi bi-arrow-left"></i> Back to list
</a>';
require_once '../partials/sidebar.php';
?>

<div class="ji-grid">

  <!-- ════ LEFT: JSON input ════ -->
  <div>
    <div class="ji-card">
      <div class="ji-card-hd">
        <span><i class="bi bi-database" style="color:#7C3AED;margin-right:6px;"></i>Paste or Upload SQL Query — <?= htmlspecialchars($typeLabel) ?></span>
        <label class="ap-btn ap-btn-ghost ap-btn-xs" style="cursor:pointer;margin:0;">
          <i class="bi bi-upload"></i> Choose File
          <input type="file" id="jsonFile" accept=".sql,.json,application/json,text/plain" style="display:none;">
        </label>
      </div>
      <div class="ji-card-bd">
        <textarea id="jsonInput" placeholder="Paste a SQL INSERT statement (or a JSON array) of questions here…"></textarea>
        <div style="display:flex;gap:8px;margin-top:12px;">
          <button type="button" class="ap-btn ap-btn-primary ap-btn-sm" onclick="validateJson()">
            <i class="bi bi-check2-circle"></i> Validate Query
          </button>
          <button type="button" class="ap-btn ap-btn-ghost ap-btn-sm" onclick="loadExample()">
            <i class="bi bi-magic"></i> Load Example
          </button>
          <button type="button" class="ap-btn ap-btn-ghost ap-btn-sm" onclick="document.getElementById('jsonInput').value='';document.getElementById('resultsCard').style.display='none';document.getElementById('emptyState').style.display='';">
            <i class="bi bi-x-circle"></i> Clear
          </button>
        </div>

        <div class="ji-schema" id="schemaHelp"></div>
      </div>
    </div>
  </div>

  <!-- ════ RIGHT: Validation results ════ -->
  <div>
    <div class="ji-card ji-mismatch" id="mismatchCard" style="display:none;">
      <div class="ji-card-bd" style="text-align:center;">
        <i class="bi bi-signpost-split-fill" style="font-size:1.8rem;color:#b45309;display:block;margin-bottom:10px;"></i>
        <div style="font-size:0.95rem;font-weight:800;color:#92400e;margin-bottom:8px;">Wrong import page</div>
        <div style="font-size:0.82rem;color:#92400e;line-height:1.6;margin-bottom:16px;" id="mismatchMsg"></div>
        <button type="button" class="ap-btn ap-btn-primary ap-btn-sm" id="mismatchGo">
          <i class="bi bi-arrow-right-circle"></i> <span id="mismatchGoLabel"></span>
        </button>
        <div style="margin-top:12px;">
          <button type="button" class="ap-btn ap-btn-ghost ap-btn-xs" onclick="validateAnyway()">
            Validate here anyway
          </button>
        </div>
      </div>
    </div>

    <div class="ji-card" id="resultsCard" style="display:none;">
      <div class="ji-card-hd">
        <span><i class="bi bi-list-check" style="color:var(--ap-primary);margin-right:6px;"></i>Validation Results — <?= htmlspecialchars($typeLabel) ?> rules</span>
        <span id="resultsSummary" style="font-weight:600;color:var(--ap-text-3);"></span>
      </div>
      <div class="ji-card-bd">
        <div class="ji-selectbar" id="jiSelectBar" style="display:none;">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:700;color:var(--ap-text-2);">
            <input type="checkbox" id="jiSelectAll" onchange="jiToggleAll(this.checked)"> Select all importable
          </label>
          <span id="jiSelectHint"></span>
        </div>
        <div id="resultsList"></div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:6px;">
          <button type="button" class="ap-btn ap-btn-primary ap-btn-sm" id="importBtn" onclick="importValid()" disabled>
            <i class="bi bi-inbox"></i> Send <span id="importCount">0</span> to Approval Queue
          </button>
        </div>
      </div>
    </div>
    <div class="ji-card" id="emptyState">
      <div class="ji-empty">
        <i class="bi bi-arrow-left-circle" style="font-size:1.6rem;display:block;margin-bottom:8px;color:var(--ap-text-3);"></i>
        Paste a SQL query (or JSON) on the left, then click <strong>Validate Query</strong> to see results here.
      </div>
    </div>
  </div>

</div>

<!-- ════ Student-side preview modal ════ -->
<div class="ap-modal-overlay" id="previewModal">
  <div class="ap-modal" style="max-width:1000px;width:95%;">
    <div class="ap-modal-header">
      <span class="ap-modal-title"><i class="bi bi-eye" style="color:var(--ap-primary);margin-right:6px;"></i>Student-Side Preview</span>
      <button class="ap-modal-close" onclick="closePreview()">×</button>
    </div>
    <div style="padding:0;">
      <iframe id="previewFrame"></iframe>
    </div>
  </div>
</div>

<script src="question_preview.js"></script>
<script>
const TYPE = <?= json_encode($type) ?>;
const IMPORT_ACTION_URL = 'json_import_action.php';

/** Maps a SQL table name back to the import page that handles it, so a paste meant for
 *  another question type can be recognised instead of being judged by this page's rules. */
const TABLE_TO_TYPE = <?= json_encode(array_flip($QUESTION_TABLE_MAP)) ?>;
const TYPE_LABELS   = <?= json_encode($QUESTION_TYPE_LABELS) ?>;
const MY_TABLE      = <?= json_encode($QUESTION_TABLE_MAP[$type]) ?>;

const CFG = TYPE_CONFIG[TYPE];
document.getElementById('schemaHelp').innerHTML = '<strong style="color:var(--ap-text-2);">Required fields</strong> — paste a SQL <code>INSERT INTO</code> statement (columns matching the field names below), or a JSON array of question objects.<br>' + CFG.schemaHelp;

/* ══════════════════════════════════════════════════════
   3. SQL INSERT FALLBACK — some content-generation tools output
      `INSERT INTO table (\`col\`,...) VALUES ('v', 123, ...);`
      instead of JSON. Detected automatically when JSON.parse fails.
   ══════════════════════════════════════════════════════ */
/** Parses one SQL VALUES tuple's inner text (between the parens) into an array of JS values. */
function parseSqlValueTuple(str) {
  const values = [];
  let i = 0;
  const n = str.length;
  while (i < n) {
    while (i < n && /[\s,]/.test(str[i])) i++;
    if (i >= n) break;
    if (str[i] === "'") {
      let j = i + 1, buf = '';
      while (j < n) {
        if (str[j] === "'") {
          if (str[j + 1] === "'") { buf += "'"; j += 2; continue; } // '' escapes a literal apostrophe
          j++; break;
        }
        buf += str[j]; j++;
      }
      values.push(buf);
      i = j;
    } else {
      let j = i;
      while (j < n && str[j] !== ',') j++;
      const rawTok = str.slice(i, j).trim();
      if (/^null$/i.test(rawTok)) values.push(null);
      else if (rawTok !== '' && !isNaN(Number(rawTok))) values.push(Number(rawTok));
      else values.push(rawTok);
      i = j;
    }
  }
  return values;
}

/**
 * Scans arbitrary text for `INSERT INTO table (cols) VALUES (...), (...);` statements.
 * Returns { rows: [one plain object per row], tables: [distinct table names seen] } — the
 * table names are what lets the page tell "wrong import page" apart from "bad data".
 */
function extractInsertStatements(sql) {
  const results = [];
  const tables  = [];
  const insertRe = /INSERT\s+INTO\s+`?(\w+)`?\s*\(([\s\S]*?)\)\s*VALUES/gi;
  let m;
  while ((m = insertRe.exec(sql)) !== null) {
    const tableName = String(m[1] || '').toLowerCase();
    if (tableName && !tables.includes(tableName)) tables.push(tableName);
    const columns = m[2].split(',').map(c => c.trim().replace(/^`|`$/g, ''));
    let idx = insertRe.lastIndex;
    while (idx < sql.length && /\s/.test(sql[idx])) idx++;
    while (idx < sql.length && sql[idx] === '(') {
      let depth = 0, inStr = false, start = idx, j = idx;
      for (; j < sql.length; j++) {
        const ch = sql[j];
        if (inStr) {
          if (ch === "'") {
            if (sql[j + 1] === "'") { j++; continue; }
            inStr = false;
          }
        } else {
          if (ch === "'") inStr = true;
          else if (ch === '(') depth++;
          else if (ch === ')') { depth--; if (depth === 0) { j++; break; } }
        }
      }
      const row = parseSqlValueTuple(sql.slice(start + 1, j - 1));
      const obj = {};
      columns.forEach((c, ci) => { obj[c] = row[ci]; });
      // Fields whose SQL value is itself a JSON blob (e.g. actionToTake, tabs) get auto-decoded.
      Object.keys(obj).forEach(k => {
        if (typeof obj[k] === 'string') {
          const t = obj[k].trim();
          if (t.startsWith('[') || t.startsWith('{')) {
            try { const p = JSON.parse(t); if (p && typeof p === 'object') obj[k] = p; } catch (e) { /* plain text, leave as-is */ }
          }
        }
      });
      results.push(obj);
      idx = j;
      while (idx < sql.length && /[\s,]/.test(sql[idx])) idx++;
      if (sql[idx] === ';') { idx++; break; }
      if (sql[idx] !== '(') break;
    }
    insertRe.lastIndex = Math.max(insertRe.lastIndex, idx);
  }
  return { rows: dedupeSqlRows(results), tables };
}

/**
 * Some SQL batch exports (e.g. bow-tie "one row per action/condition/parameter option" test
 * batches) repeat the full question record once per bank option, varying only a throwaway
 * per-row pivot column (e.g. `correct`/`type`) that the validators never read — the real
 * per-option correctness already lives inside the actionToTake/potentialConditions/
 * parameterToMonitor JSON blobs. Collapse those repeats down to one row per question, grouped
 * by the `item` column when present (falls back to question+topic+concept so normal
 * one-row-per-question exports are left untouched).
 */
function dedupeSqlRows(rows) {
  const seen = new Set();
  const out = [];
  rows.forEach(row => {
    const item = row.item !== undefined && row.item !== null ? String(row.item).trim() : '';
    const key = item !== ''
      ? 'item:' + item
      : 'q:' + String(row.question ?? '') + '|' + String(row.topic ?? '') + '|' + String(row.concept ?? '');
    if (!seen.has(key)) { seen.add(key); out.push(row); }
  });
  return out;
}

/**
 * Guesses a question type from the fields present on a parsed object. Only used for JSON
 * pastes, which carry no table name — the distinguishing field of each type is enough.
 * Returns null when nothing matches confidently.
 */
function detectTypeFromShape(q) {
  if (!q || typeof q !== 'object' || Array.isArray(q)) return null;
  const has = k => q[k] !== undefined && q[k] !== null && q[k] !== '';

  if (has('passage') && has('correct_words'))       return 'highlight';
  if (has('actionToTake') || has('potentialConditions') || has('parameterToMonitor')) return 'btq';
  if (has('columns') && has('rows'))                return 'mmr';
  if (has('correct_words'))                         return 'dropdown';
  if (has('choices'))                               return 'traditional';
  return null;
}

/** The type a paste looks like, or null when it matches this page (or can't be told apart). */
function detectForeignType(rows, sqlTables) {
  if (sqlTables && sqlTables.length) {
    const known = sqlTables.filter(t => TABLE_TO_TYPE[t]);
    if (known.length === 1 && known[0] !== MY_TABLE) return TABLE_TO_TYPE[known[0]];
    return null;
  }
  const guesses = rows.map(detectTypeFromShape).filter(Boolean);
  if (!guesses.length) return null;
  const allSame = guesses.every(g => g === guesses[0]);
  return (allSame && guesses[0] !== TYPE) ? guesses[0] : null;
}

/* ══════════════════════════════════════════════════════
   4. VALIDATE BUTTON
   ══════════════════════════════════════════════════════ */
let lastResults = [];
let lastRaw = []; // the untouched parsed question objects — submitted to the server as-is, so the
                   // server's own parser (which expects the original raw field shapes, e.g. lettered
                   // "A. text" strings) is always the single source of truth for the final transform.
let lastFromSql = false;

function validateJson(force) {
  const raw = document.getElementById('jsonInput').value.trim();
  const resultsCard = document.getElementById('resultsCard');
  const emptyState  = document.getElementById('emptyState');
  const list        = document.getElementById('resultsList');

  if (!raw) {
    Swal.fire({ icon: 'warning', title: 'Nothing to validate', text: 'Paste or upload JSON first.', confirmButtonColor: '#0d9488' });
    return;
  }

  let parsed;
  let sqlTables = [];
  lastFromSql = false;
  try {
    parsed = JSON.parse(raw);
  } catch (e) {
    if (/INSERT\s+INTO/i.test(raw)) {
      const extracted = extractInsertStatements(raw);
      if (extracted.rows.length > 0) { parsed = extracted.rows; lastFromSql = true; sqlTables = extracted.tables; }
    }
    if (parsed === undefined) {
      emptyState.style.display = 'none';
      resultsCard.style.display = '';
      list.innerHTML = '<div class="ji-qcard invalid"><div class="ji-qcard-title" style="color:#dc2626;"><i class="bi bi-x-octagon"></i> Invalid JSON</div>' +
        '<div class="ji-issue-list err" style="margin-top:8px;list-style:none;padding-left:0;">' + escapeHtml(e.message) + '</div></div>';
      document.getElementById('resultsSummary').textContent = '';
      document.getElementById('importBtn').disabled = true;
      document.getElementById('importCount').textContent = '0';
      document.getElementById('jiSelectBar').style.display = 'none';
      lastResults = [];
      lastRaw = [];
      return;
    }
  }

  const arr = Array.isArray(parsed) ? parsed : [parsed];

  // A paste meant for another type would otherwise be judged by this page's rules and come
  // back as a wall of errors that say nothing about the real problem.
  const foreign = force ? null : detectForeignType(arr, sqlTables);
  if (foreign) { showMismatch(foreign, raw, sqlTables, arr.length); return; }
  document.getElementById('mismatchCard').style.display = 'none';

  lastRaw = arr;
  lastResults = arr.map((q, i) => CFG.validate(q, i));
  lastPrecheck = {};

  renderResults();
  emptyState.style.display = 'none';
  resultsCard.style.display = '';

  runPrecheck(arr);
}

/* ══════════════════════════════════════════════════════
   4a. DATABASE PRE-CHECK — shape alone cannot tell whether a
       question is already stored or missing metadata the
       question bank relies on, so ask the server.
   ══════════════════════════════════════════════════════ */
let lastPrecheck   = {};        // row index -> { duplicate_of, queued_as, repeat_of_row, missing[] }
let precheckState  = 'idle';    // 'running' | 'ok' | 'failed'
let precheckError  = '';

function runPrecheck(arr) {
  precheckState = 'running';
  precheckError = '';
  renderResults();

  fetch('import_precheck.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ type: TYPE, questions: arr })
  })
  .then(r => r.ok ? r.json() : Promise.reject(new Error('server replied HTTP ' + r.status)))
  .then(data => {
    if (!data.success) throw new Error(data.error || 'the database check was rejected');
    lastPrecheck = {};
    data.rows.forEach(row => { lastPrecheck[row.index] = row; });
    precheckState = 'ok';
    renderResults();
  })
  .catch(err => {
    // Importing without this answer could silently duplicate questions, so the import stays locked.
    precheckState = 'failed';
    precheckError = String(err && err.message ? err.message : err);
    renderResults();
  });
}

function retryPrecheck() {
  if (lastRaw.length) runPrecheck(lastRaw);
}

/** True when a row must not be imported as-is because the question is already stored or queued. */
function isBlocked(index) {
  const p = lastPrecheck[index];
  return !!p && (p.duplicate_of > 0 || p.queued_as > 0 || p.repeat_of_row >= 0);
}

function renderResults() {
  const list = document.getElementById('resultsList');

  let banner = '';
  if (precheckState === 'running') {
    banner = '<div class="ji-flag checking"><i class="bi bi-hourglass-split"></i> Checking the database for duplicates and missing information…</div>';
  } else if (precheckState === 'failed') {
    banner = '<div class="ji-flag failed"><i class="bi bi-x-octagon-fill"></i> <span><strong>Database check failed</strong> — ' +
             escapeHtml(precheckError) + '.<br>Importing is locked because duplicates cannot be ruled out. ' +
             '<button type="button" class="ap-btn ap-btn-ghost ap-btn-xs" style="margin-top:6px;" onclick="retryPrecheck()">' +
             '<i class="bi bi-arrow-clockwise"></i> Try again</button></span></div>';
  }
  list.innerHTML = banner + lastResults.map(r => renderResultCard(r)).join('');

  const validCount   = lastResults.filter(r => r.valid).length;
  const blockedCount = lastResults.filter(r => r.valid && isBlocked(r.index)).length;
  const missingCount = lastResults.filter(r => (lastPrecheck[r.index]?.missing || []).length > 0).length;

  let summary = (lastFromSql ? '(parsed from SQL) ' : '') + validCount + ' / ' + lastResults.length + ' valid';
  if (precheckState === 'running')      summary += ' · checking database…';
  else if (precheckState === 'failed')  summary += ' · database check failed';
  else {
    if (blockedCount) summary += ' · ' + blockedCount + ' duplicate';
    if (missingCount) summary += ' · ' + missingCount + ' incomplete';
  }
  document.getElementById('resultsSummary').textContent = summary;

  recountImport();
}

/* ══════════════════════════════════════════════════════
   4b. WRONG-PAGE HANDOFF — carry the paste to the right
       import page instead of making the admin paste twice.
   ══════════════════════════════════════════════════════ */
const HANDOFF_KEY = 'ji_handoff';

function showMismatch(foreignType, rawText, sqlTables, count) {
  const foreignLabel = TYPE_LABELS[foreignType] || foreignType;
  const source = sqlTables && sqlTables.length
    ? 'Your SQL is for the <code>' + escapeHtml(sqlTables.join('</code>, <code>')) + '</code> table'
    : 'Your JSON looks like <strong>' + escapeHtml(foreignLabel) + '</strong>';

  document.getElementById('mismatchMsg').innerHTML =
    source + ' (' + count + ' question' + (count === 1 ? '' : 's') + '), so these are <strong>' +
    escapeHtml(foreignLabel) + '</strong> questions.<br>You are on the <strong>' + escapeHtml(TYPE_LABELS[TYPE]) +
    '</strong> import page, so the wrong rules would be used.';

  document.getElementById('mismatchGoLabel').textContent = 'Take me to ' + foreignLabel + ' import';
  document.getElementById('mismatchGo').onclick = function () {
    try { sessionStorage.setItem(HANDOFF_KEY, JSON.stringify({ type: foreignType, text: rawText })); } catch (e) { /* private mode — user re-pastes */ }
    window.location.href = 'json_import.php?type=' + encodeURIComponent(foreignType) + '&handoff=1';
  };

  document.getElementById('mismatchCard').style.display = '';
  document.getElementById('resultsCard').style.display  = 'none';
  document.getElementById('emptyState').style.display   = 'none';
  document.getElementById('importBtn').disabled = true;
  lastResults = [];
  lastRaw = [];
}

/** Escape hatch: validate with this page's rules even though the paste looks like another type. */
function validateAnyway() {
  document.getElementById('mismatchCard').style.display = 'none';
  validateJson(true);
}

/** Picks up a paste handed over from another import page. */
(function receiveHandoff() {
  if (!/[?&]handoff=1/.test(window.location.search)) return;
  let data = null;
  try {
    const stored = sessionStorage.getItem(HANDOFF_KEY);
    if (stored) data = JSON.parse(stored);
    sessionStorage.removeItem(HANDOFF_KEY);
  } catch (e) { return; }
  if (!data || data.type !== TYPE || !data.text) return;

  document.getElementById('jsonInput').value = data.text;
  validateJson();
})();

function renderResultCard(r) {
  const badge = r.valid
    ? '<span class="ap-badge ap-badge-success"><i class="bi bi-check-circle"></i> Valid</span>'
    : '<span class="ap-badge ap-badge-danger"><i class="bi bi-x-circle"></i> Invalid</span>';

  const p       = lastPrecheck[r.index];
  const blocked = isBlocked(r.index);

  let extraBadge = '';
  if (p && p.duplicate_of > 0)       extraBadge = '<span class="ap-badge ap-badge-warning"><i class="bi bi-files"></i> Already in database</span>';
  else if (p && p.queued_as > 0)     extraBadge = '<span class="ap-badge ap-badge-warning"><i class="bi bi-hourglass-split"></i> Already in approval queue</span>';
  else if (p && p.repeat_of_row >= 0) extraBadge = '<span class="ap-badge ap-badge-warning"><i class="bi bi-arrow-repeat"></i> Repeated in this paste</span>';

  let html = '<div class="ji-qcard ' + (r.valid ? (blocked ? 'dupe' : 'valid') : 'invalid') + '">';
  html += '<div class="ji-qcard-top"><span class="ji-qcard-title">#' + (r.index + 1) + '</span><span style="display:flex;gap:6px;">' + extraBadge + badge + '</span></div>';
  html += '<div class="ji-qcard-snippet">' + escapeHtml(r.question || '(no question text)') + '</div>';

  if (r.errors && r.errors.length) {
    html += '<ul class="ji-issue-list err">' + r.errors.map(e => '<li>' + escapeHtml(e) + '</li>').join('') + '</ul>';
  }

  if (p && p.duplicate_of > 0) {
    html += '<div class="ji-flag dupe"><i class="bi bi-exclamation-octagon-fill"></i> This question already exists in the <code>' +
            escapeHtml(MY_TABLE) + '</code> table as <strong>#' + p.duplicate_of +
            '</strong>. Importing it again would store the same question twice.</div>';
  } else if (p && p.queued_as > 0) {
    html += '<div class="ji-flag dupe"><i class="bi bi-exclamation-octagon-fill"></i> This question is already waiting for approval (queue #' +
            p.queued_as + '). Approve or delete that one instead of queueing a second copy.</div>';
  } else if (p && p.repeat_of_row >= 0) {
    html += '<div class="ji-flag dupe"><i class="bi bi-exclamation-octagon-fill"></i> Same question as <strong>#' +
            (p.repeat_of_row + 1) + '</strong> in this paste. Only one copy should be imported.</div>';
  }

  if (p && p.missing && p.missing.length) {
    html += '<div class="ji-flag missing"><i class="bi bi-exclamation-triangle-fill"></i> Missing information: <strong>' +
            p.missing.map(escapeHtml).join(', ') + '</strong>. These columns would be stored empty.</div>';
  }

  if (r.warnings && r.warnings.length) {
    html += '<ul class="ji-issue-list warn">' + r.warnings.map(w => '<li>' + escapeHtml(w) + '</li>').join('') + '</ul>';
  }

  if (r.valid) {
    html += '<div style="margin-top:10px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">' +
      '<label style="display:flex;align-items:center;gap:6px;font-size:0.75rem;color:' + (blocked ? '#b45309' : 'var(--ap-text-3)') + ';cursor:pointer;">' +
      '<input type="checkbox" class="ji-include" data-index="' + r.index + '"' + (blocked ? '' : ' checked') + ' onchange="recountImport()"> ' +
      (blocked ? 'Import anyway' : 'Include in import') + '</label>' +
      '<button type="button" class="ap-btn ap-btn-ghost ap-btn-xs" onclick="previewQuestion(' + r.index + ')"><i class="bi bi-eye"></i> Preview as Student</button>' +
      '</div>';
  }
  html += '</div>';
  return html;
}

/** Rows that are safe to import in bulk: valid, and not already stored, queued or repeated. */
function jiImportableIndexes() {
  return lastResults.filter(r => r.valid && !isBlocked(r.index)).map(r => r.index);
}

/** Select all deliberately leaves duplicates alone — those stay a manual, per-row decision. */
function jiToggleAll(on) {
  const allow = new Set(jiImportableIndexes());
  document.querySelectorAll('.ji-include').forEach(cb => {
    if (allow.has(parseInt(cb.dataset.index, 10))) cb.checked = on;
  });
  recountImport();
}

function syncSelectAll() {
  const bar = document.getElementById('jiSelectBar');
  const box = document.getElementById('jiSelectAll');
  const hint = document.getElementById('jiSelectHint');

  const allow   = jiImportableIndexes();
  const blocked = lastResults.filter(r => r.valid && isBlocked(r.index)).length;

  bar.style.display = lastResults.length ? '' : 'none';

  const checkedClean = allow.filter(i => document.querySelector('.ji-include[data-index="' + i + '"]:checked')).length;
  box.checked       = allow.length > 0 && checkedClean === allow.length;
  box.indeterminate = checkedClean > 0 && checkedClean < allow.length;
  box.disabled      = allow.length === 0 || precheckState !== 'ok';

  hint.innerHTML = allow.length + ' of ' + lastResults.length + ' can be imported' +
    (blocked ? ' · <b>' + blocked + ' duplicate' + (blocked === 1 ? '' : 's') + ' never selected</b>' : '');
}

function recountImport() {
  const checked = document.querySelectorAll('.ji-include:checked').length;
  document.getElementById('importCount').textContent = checked;
  // Never let anything through while the duplicate check is unresolved.
  document.getElementById('importBtn').disabled = checked === 0 || precheckState !== 'ok';
  syncSelectAll();
}

/* ══════════════════════════════════════════════════════
   4. FILE UPLOAD
   ══════════════════════════════════════════════════════ */
document.getElementById('jsonFile').addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = evt => { document.getElementById('jsonInput').value = evt.target.result; };
  reader.readAsText(file);
  e.target.value = '';
});

/* ══════════════════════════════════════════════════════
   5. STUDENT-SIDE PREVIEW MODAL
   ══════════════════════════════════════════════════════ */
function previewQuestion(index) {
  const r = lastResults.find(x => x.index === index);
  if (!r || !r.valid) return;
  const html = CFG.buildPreview(r.normalized);
  const frame = document.getElementById('previewFrame');
  frame.srcdoc = html;
  document.getElementById('previewModal').classList.add('open');
}
function closePreview() {
  document.getElementById('previewModal').classList.remove('open');
  document.getElementById('previewFrame').srcdoc = 'about:blank';
}
document.getElementById('previewModal').addEventListener('click', function(e) {
  if (e.target === this) closePreview();
});

/* ══════════════════════════════════════════════════════
   6. IMPORT — send normalized + selected questions to server
   ══════════════════════════════════════════════════════ */
function importValid() {
  const selectedIdx = Array.from(document.querySelectorAll('.ji-include:checked')).map(cb => parseInt(cb.dataset.index, 10));
  const payload = lastResults.filter(r => r.valid && selectedIdx.includes(r.index)).map(r => lastRaw[r.index]);

  if (payload.length === 0) return;

  const btn = document.getElementById('importBtn');
  const originalHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="ngn-spinner" style="display:inline-block;width:12px;height:12px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:ji-spin .7s linear infinite;margin-right:6px;"></span> Sending…';

  fetch(IMPORT_ACTION_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ type: TYPE, questions: payload })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      Swal.fire({
        icon: 'success',
        title: 'Sent for Approval',
        text: data.queued + ' question(s) are now waiting for approval' + (data.failed ? ', ' + data.failed + ' failed.' : '.'),
        confirmButtonText: 'Review them now',
        confirmButtonColor: '#0d9488'
      }).then(() => { window.location.href = 'pending.php?type=' + encodeURIComponent(TYPE); });
    } else {
      Swal.fire({ icon: 'error', title: 'Import Failed', text: data.error || 'Unknown error.', confirmButtonColor: '#0d9488' });
      btn.disabled = false; btn.innerHTML = originalHtml;
    }
  })
  .catch(err => {
    Swal.fire({ icon: 'error', title: 'Request Failed', text: String(err), confirmButtonColor: '#0d9488' });
    btn.disabled = false; btn.innerHTML = originalHtml;
  });
}

/* ══════════════════════════════════════════════════════
   7. "Load Example"
   ══════════════════════════════════════════════════════ */
function loadExample() {
  document.getElementById('jsonInput').value = JSON.stringify(CFG.example, null, 2);
}
</script>
<style>@keyframes ji-spin{to{transform:rotate(360deg);}}</style>

<?php require_once '../partials/footer.php'; ?>
