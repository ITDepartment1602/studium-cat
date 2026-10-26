/**
 * question_preview.js — Shared question parsing + student-side preview engine.
 * Used by json_import.php (validate a pasted import) and pending.php (review the
 * approval queue), so both show a candidate question exactly as a student would see it.
 * Defines the global TYPE_CONFIG: one entry per question type with validate(),
 * buildPreview(), schemaHelp and example.
 */

/* ══════════════════════════════════════════════════════
   0. SHARED PARSING HELPERS (mirrored server-side in json_import_action.php)
   ══════════════════════════════════════════════════════ */
function escapeHtml(s) {
  return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
/** Safe to embed inside a nested script tag — neutralizes closing script/style tags, etc. */
function jsonForScript(value) {
  return JSON.stringify(value).replace(/</g, '\\u003c');
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
   1. STUDENT-SIDE PREVIEW SHELL — shared two-panel/tabs/card
      chrome reused by every question type's preview.
   ══════════════════════════════════════════════════════ */
const SHARED_PREVIEW_CSS = `
:root{--primary:#0a1628;--accent:#3b82f6;--success:#10b981;--danger:#ef4444;--surface:#fff;--border:#e2e8f0;--text:#0f172a;--text-muted:#64748b;}
*{box-sizing:border-box;margin:0;padding:0;}body{font-family:"Inter",sans-serif;color:var(--text);}
.preview-flag{position:fixed;top:8px;right:8px;background:#7C3AED;color:#fff;font-size:10px;font-weight:800;letter-spacing:.05em;text-transform:uppercase;padding:4px 10px;border-radius:100px;z-index:99;box-shadow:0 2px 8px rgba(124,58,237,.35);}
.two-panel{display:flex;min-height:100vh;}
.left-panel{width:40%;min-width:260px;background:#fff;border-right:2px solid var(--border);display:flex;flex-direction:column;flex-shrink:0;}
.panel-title{padding:14px 20px;background:#f1f5f9;font-weight:800;font-size:11px;text-transform:uppercase;color:var(--text-muted);letter-spacing:1px;border-bottom:1px solid var(--border);}
.tabs-row{display:flex;padding:8px 12px 0;gap:4px;border-bottom:1px solid var(--border);overflow-x:auto;}
.tab-btn{padding:9px 14px;font-size:13px;font-weight:600;cursor:pointer;border-radius:8px 8px 0 0;color:var(--text-muted);white-space:nowrap;}
.tab-btn.active{background:#f8fafc;color:var(--accent);border:1px solid var(--border);border-bottom-color:#f8fafc;margin-bottom:-1px;}
.tab-content-area{flex:1;overflow-y:auto;padding:16px;}
.clinical-record{background:#fdfdfd;border:1px solid #f1f5f9;padding:10px 14px;border-radius:8px;margin-bottom:8px;font-size:14px;line-height:1.5;}
.right-panel{flex:1;overflow-y:auto;padding:24px;}
.card{max-width:950px;margin:0 auto;background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:32px;box-shadow:0 4px 16px rgba(15,23,42,.05);}
.case-title-badge{font-size:13px;font-weight:800;color:#0369a1;text-transform:uppercase;letter-spacing:.4px;margin-bottom:10px;}
.instruction-badge{display:inline-block;background:#fef3c7;color:#92400e;font-size:11px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;border-radius:999px;padding:6px 14px;margin-bottom:16px;}
.question-header{font-size:18px;line-height:1.7;color:var(--primary);margin-bottom:22px;font-weight:600;}
.selection-count{font-size:12px;color:var(--text-muted);margin-bottom:14px;font-weight:600;text-transform:uppercase;letter-spacing:.3px;}
.items-container,.options-list{display:flex;flex-direction:column;gap:12px;margin-bottom:24px;}
.item-checkbox,.option-item{display:flex;align-items:center;gap:14px;padding:16px 18px;border:2px solid var(--border);border-radius:12px;background:#fff;cursor:pointer;transition:all .2s;}
.item-checkbox:hover,.option-item:hover{border-color:var(--accent);background:#f8fafc;}
.item-checkbox input,.option-item input{width:20px;height:20px;flex-shrink:0;accent-color:var(--accent);}
.item-text,.option-text{flex:1;font-size:15px;font-weight:500;color:var(--text);line-height:1.5;}
.item-checkbox.selected,.option-item.selected{border-color:var(--accent);background:#eff6ff;}
.item-checkbox.correct-reveal,.option-item.correct-reveal,.blank.correct-reveal,.dropzone.correct-reveal,.inline-select.correct-reveal,.cell-correct,.inline-token.correct-reveal{border-color:var(--success)!important;background:#f0fdf4!important;}
.item-checkbox.wrong-reveal,.option-item.wrong-reveal,.blank.wrong-reveal,.dropzone.wrong-reveal,.inline-select.wrong-reveal,.cell-wrong,.inline-token.wrong-reveal{border-color:var(--danger)!important;background:#fef2f2!important;}
.item-checkbox.disabled{opacity:.6;pointer-events:none;}
.result-section{display:none;margin-top:26px;padding:18px 22px;background:#f8fafc;border-left:4px solid var(--accent);border-radius:10px;}
.result-section.show{display:block;}
.result-title{font-size:14px;font-weight:800;text-transform:uppercase;margin-bottom:8px;letter-spacing:.5px;}
.result-title.correct{color:var(--success);}.result-title.incorrect{color:var(--danger);}
.result-score{font-size:13px;color:var(--text-muted);margin-top:4px;font-weight:600;}
.rationale-content{font-size:14px;line-height:1.7;color:var(--text-muted);margin-top:12px;}
.nclex-tips{margin-top:20px;padding:18px;background:#f0fdf4;border-radius:12px;border:1px solid #bbf7d0;}
.tips-title{font-weight:800;color:#166534;font-size:12px;text-transform:uppercase;margin-bottom:14px;display:flex;align-items:center;gap:8px;letter-spacing:.5px;}
.tips-list{list-style:none;}
.tips-list li{display:flex;align-items:flex-start;gap:10px;font-size:14px;color:#15803d;margin-bottom:9px;line-height:1.5;}
.tips-list li i{color:#22c55e;margin-top:3px;flex-shrink:0;}
.tip-highlight{background:#fef08a;padding:2px 4px;border-radius:4px;font-weight:700;color:#854d0e;border-bottom:1.5px solid #f59e0b;}
.button-group{display:flex;justify-content:flex-end;margin-top:26px;}
.btn{padding:12px 26px;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer;border:none;}
.btn-primary{background:#0d9488;color:#fff;}.btn-primary:hover:not(:disabled){background:#0b7e74;}.btn-primary:disabled{opacity:.65;cursor:not-allowed;}
`;
const MATRIX_CSS = `
.matrix-wrap{overflow-x:auto;margin-bottom:24px;}
.mx-tbl{border-collapse:separate;border-spacing:0;border:1px solid var(--border);border-radius:12px;overflow:hidden;width:100%;font-size:13px;}
.mx-tbl th{background:#f8fafc;padding:12px;font-size:11px;font-weight:800;text-transform:uppercase;color:var(--text-muted);border-bottom:1px solid var(--border);border-right:1px solid var(--border);text-align:center;}
.mx-tbl td{padding:10px;border-bottom:1px solid var(--border);border-right:1px solid var(--border);text-align:center;}
.mx-tbl td:first-child{text-align:left;font-weight:600;background:#fcfcfd;}
.mx-tbl th:last-child,.mx-tbl td:last-child{border-right:none;}
`;
const BOWTIE_CSS = `
.diagram-wrapper{display:flex;align-items:center;justify-content:center;gap:16px;margin-bottom:32px;}
.diagram-col{flex:1;display:flex;flex-direction:column;gap:10px;}
.col-label{font-size:10px;font-weight:800;text-transform:uppercase;color:var(--text-muted);text-align:center;margin-bottom:4px;}
.dropzone{min-height:64px;background:#eff6ff;border:2px dashed #bfdbfe;border-radius:12px;display:flex;align-items:center;justify-content:center;padding:8px;text-align:center;font-size:12px;color:#3b82f6;font-weight:500;cursor:pointer;}
.dropzone span{pointer-events:none;}
.choice-token{background:#fff;border:1px solid var(--border);border-radius:10px;padding:10px 14px;font-size:13px;font-weight:600;cursor:grab;box-shadow:0 2px 4px rgba(0,0,0,.05);}
.banks-container{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;background:#f8fafc;padding:20px;border-radius:16px;}
.bank-col{display:flex;flex-direction:column;gap:8px;}
.bank-header{font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;margin-bottom:2px;}
.bank-list{min-height:80px;display:flex;flex-direction:column;gap:8px;}
`;
const DRAGDROP_CSS = `
.choices-bank{display:flex;flex-wrap:wrap;gap:10px;padding:20px;background:#f8fafc;border-radius:12px;border:1px solid var(--border);}
.choice-item{background:#fff;border:1px solid var(--border);padding:10px 18px;border-radius:10px;font-size:14px;font-weight:600;cursor:grab;box-shadow:0 1px 3px rgba(0,0,0,.05);}
.ordered-slots{margin-bottom:20px;}
.slots-label{font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);margin-bottom:10px;letter-spacing:.5px;}
.slot-row{display:flex;align-items:center;gap:12px;margin-bottom:8px;}
.slot-num{width:26px;height:26px;background:var(--accent);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0;}
.blank{flex:1;min-height:36px;background:#f1f5f9;border:2px dashed #cbd5e1;border-radius:8px;display:flex;align-items:center;padding:0 12px;font-size:14px;font-weight:600;color:var(--accent);}
.blank.filled{border-style:solid;border-color:#3b82f6;background:#fff;color:var(--text);}
.cloze-blank{display:inline-flex;min-width:140px;height:36px;background:#f1f5f9;border:2px dashed #cbd5e1;border-radius:8px;margin:0 6px;vertical-align:middle;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:var(--accent);white-space:nowrap;padding:0 10px;cursor:pointer;}
.cloze-blank.filled{border-style:solid;border-color:#3b82f6;background:#fff;color:var(--text);}
`;
const DROPDOWN_CSS = `
.fallback-blanks{display:grid;gap:12px;margin-bottom:20px;}
.fallback-row{background:#f8fafc;border:1px solid var(--border);border-radius:12px;padding:10px 12px;}
.inline-select{width:100%;padding:8px 10px;border:2px solid var(--border);border-radius:10px;background:#fff;font-size:14px;font-weight:600;color:var(--text);}
.question-header .inline-select{width:auto;display:inline-block;margin:0 4px;vertical-align:middle;}
`;
const HIGHLIGHT_CSS = `
.passage-box{font-size:15px;line-height:2;color:var(--text);margin-bottom:16px;padding:20px;background:#f8fafc;border-radius:12px;border:1px solid var(--border);}
.inline-token{cursor:pointer;border-radius:4px;padding:1px 2px;transition:background .15s;}
.inline-token:hover{background:#fef9c3;}
.inline-token.selected{background:#fef08a;font-weight:600;}
`;
const TABS_SCRIPT = `
document.querySelectorAll(".tab-btn").forEach(function(btn){btn.addEventListener("click",function(){
document.querySelectorAll(".tab-btn").forEach(function(b){b.classList.remove("active");});this.classList.add("active");
document.querySelectorAll(".tab-pane").forEach(function(p){p.style.display="none";});document.getElementById(this.dataset.tab).style.display="";});});
`;

function previewShell(n, opts) {
  const hasTabs = Array.isArray(n.tabs) && n.tabs.length > 0;
  let tabsBtns = '', tabsPanes = '';
  if (hasTabs) {
    n.tabs.forEach((t, i) => {
      tabsBtns += '<div class="tab-btn' + (i === 0 ? ' active' : '') + '" data-tab="ptab-' + i + '">' + escapeHtml(t.title) + '</div>';
      const records = (t.content || []).map(c => '<div class="clinical-record">' + escapeHtml(c) + '</div>').join('');
      tabsPanes += '<div id="ptab-' + i + '" class="tab-pane"' + (i > 0 ? ' style="display:none;"' : '') + '>' + records + '</div>';
    });
  }
  const caseTitleHtml = (opts.caseTitle && n.case_title) ? ('<div class="case-title-badge">' + escapeHtml(n.case_title) + '</div>') : '';

  let tips = [];
  if (Array.isArray(n.furtherinfo)) tips = n.furtherinfo;
  else if (typeof n.furtherinfo === 'string' && n.furtherinfo.trim() !== '') tips = n.furtherinfo.split('\n').filter(l => l.trim() !== '');
  const tipsHtml = tips.length ? (
    '<div class="nclex-tips"><div class="tips-title"><i class="fas fa-lightbulb"></i> NCLEX Tips &amp; Further Information</div><ul class="tips-list">' +
    tips.map(t => {
      const clean = String(t).replace(/\s+/g, ' ').trim();
      const highlighted = escapeHtml(clean).replace(/%([^%]+)%/g, '<span class="tip-highlight">$1</span>');
      return '<li><i class="fas fa-check-circle"></i> <span>' + highlighted + '</span></li>';
    }).join('') + '</ul></div>'
  ) : '';
  const rationaleHtml = escapeHtml(n.rationale).replace(/\n/g, '<br>') + tipsHtml;

  return '<!DOCTYPE html><html><head><meta charset="UTF-8">' +
'<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">' +
'<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">' +
'<style>' + SHARED_PREVIEW_CSS + (opts.extraCss || '') + '</style></head><body>' +
'<div class="preview-flag"><i class="fas fa-eye"></i> Preview</div>' +
'<div class="two-panel">' +
(hasTabs ? ('<div class="left-panel"><div class="panel-title">Clinical Reference</div><div class="tabs-row">' + tabsBtns + '</div><div class="tab-content-area">' + tabsPanes + '</div></div>') : '') +
'<div class="right-panel"' + (!hasTabs ? ' style="width:100%;"' : '') + '>' +
'<div class="card">' + caseTitleHtml +
'<div class="instruction-badge">' + escapeHtml(opts.instructionLabel) + '</div>' +
'<div class="question-header"' + (opts.questionHeaderStyle ? ' style="' + opts.questionHeaderStyle + '"' : '') + '>' +
(opts.questionHtml !== undefined ? opts.questionHtml : escapeHtml(n.question).replace(/\n/g, '<br>')) + '</div>' +
opts.bodyHtml +
'<div class="result-section" id="resultSection"><div class="result-title" id="resultTitle"></div><div class="result-score" id="resultScore"></div>' +
'<div class="rationale-content" id="rationaleContent"></div></div>' +
'<div class="button-group"><button type="button" class="btn btn-primary" id="submitBtn"><i class="fas fa-check-circle"></i> Submit</button></div>' +
'</div></div></div>' +
'<script>' +
TABS_SCRIPT +
'const RATIONALE_HTML = ' + jsonForScript(rationaleHtml) + ';' +
(opts.bodyScript || '') +
'<\/script></body></html>';
}

/** Splits a passage into plain-text / clickable-phrase pieces (Highlight preview). */
function tokenizePassage(passage, options) {
  let pieces = [{ text: passage, token: null }];
  const sorted = options.map((o, idx) => ({ o, idx })).sort((a, b) => b.o.length - a.o.length);
  sorted.forEach(({ o, idx }) => {
    if (!o) return;
    const next = [];
    pieces.forEach(p => {
      if (p.token !== null) { next.push(p); return; }
      const lower = p.text.toLowerCase();
      const needle = o.toLowerCase();
      let cursor = 0, found = false, pos = lower.indexOf(needle, cursor);
      while (pos !== -1) {
        found = true;
        if (pos > cursor) next.push({ text: p.text.slice(cursor, pos), token: null });
        next.push({ text: p.text.slice(pos, pos + o.length), token: idx });
        cursor = pos + o.length;
        pos = lower.indexOf(needle, cursor);
      }
      if (!found) next.push(p);
      else if (cursor < p.text.length) next.push({ text: p.text.slice(cursor), token: null });
    });
    pieces = next.filter(p => p.text !== '');
  });
  return pieces;
}

/* ══════════════════════════════════════════════════════
   2. TYPE_CONFIG — validate() + buildPreview() + schemaHelp + example, per type
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
  buildPreview(n) {
    const optsHtml = n.options.map(opt =>
      '<label class="item-checkbox" data-value="' + escapeHtml(opt) + '"><input type="radio" name="answer" value="' + escapeHtml(opt) + '"><span class="item-text">' + escapeHtml(opt) + '</span></label>'
    ).join('');
    const bodyHtml = '<div class="items-container">' + optsHtml + '</div>';
    const bodyScript = `
var correctAnswer=${jsonForScript(n.correct)};
document.getElementById('submitBtn').addEventListener('click',function(){
  var selected=document.querySelector('input[name="answer"]:checked');
  if(!selected){alert('Please select an answer.'); return;}
  var val=selected.value;
  var isCorrect=val===correctAnswer;
  document.querySelectorAll('.item-checkbox').forEach(function(item){
    var v=item.getAttribute('data-value'); var input=item.querySelector('input'); input.disabled=true;
    if(v===correctAnswer){item.classList.add('correct-reveal');}
    else if(v===val){item.classList.add('wrong-reveal');}
  });
  var t=document.getElementById('resultTitle');
  t.className='result-title '+(isCorrect?'correct':'incorrect');
  t.innerHTML=(isCorrect?'<i class="fas fa-check-circle"></i> Correct!':'<i class="fas fa-times-circle"></i> Incorrect');
  document.getElementById('resultScore').textContent=isCorrect?'You selected the correct answer.':'Correct answer highlighted above.';
  document.getElementById('rationaleContent').innerHTML=RATIONALE_HTML;
  document.getElementById('resultSection').classList.add('show');
  this.disabled=true;
});`;
    return previewShell(n, { caseTitle: false, instructionLabel: 'MULTIPLE CHOICE', bodyHtml, bodyScript });
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
  buildPreview(n) {
    const itemsHtml = n.items.map(item =>
      '<label class="item-checkbox" data-value="' + escapeHtml(item) + '"><input type="checkbox" name="answers" value="' + escapeHtml(item) + '"><span class="item-text">' + escapeHtml(item) + '</span></label>'
    ).join('');
    const bodyHtml = '<div class="selection-count" id="selectionCount">Select correct answers</div><div class="items-container">' + itemsHtml + '</div>';
    const bodyScript = `
const correctAnswers = ${jsonForScript(n.correct)};
const totalCorrect = correctAnswers.length;
document.querySelectorAll('input[name="answers"]').forEach(function(cb){cb.addEventListener('change',function(){
  document.querySelectorAll('.item-checkbox').forEach(function(item){var i=item.querySelector('input');item.classList.toggle('selected',i.checked);});
  var count=document.querySelectorAll('input[name="answers"]:checked').length;
  document.getElementById('selectionCount').textContent=count===0?'Select correct answers':(count+' of '+totalCorrect+' selected');
});});
document.getElementById('submitBtn').addEventListener('click',function(){
  var userAnswers=Array.from(document.querySelectorAll('input[name="answers"]:checked')).map(function(c){return c.value;});
  if(userAnswers.length===0) return;
  var correctCount=0; userAnswers.forEach(function(a){if(correctAnswers.indexOf(a)!==-1)correctCount++;});
  var missed=correctAnswers.filter(function(c){return userAnswers.indexOf(c)===-1;}).length;
  var isPerfect=correctCount===totalCorrect&&missed===0&&userAnswers.length===correctCount;
  document.querySelectorAll('.item-checkbox').forEach(function(item){
    var v=item.getAttribute('data-value');var input=item.querySelector('input');input.disabled=true;
    item.classList.remove('correct-reveal','wrong-reveal','disabled');
    if(correctAnswers.indexOf(v)!==-1){item.classList.add('correct-reveal');}
    else if(userAnswers.indexOf(v)!==-1){item.classList.add('wrong-reveal');}else{item.classList.add('disabled');}
  });
  var t=document.getElementById('resultTitle');
  if(isPerfect){t.className='result-title correct';t.innerHTML='<i class="fas fa-check-circle"></i> Correct!';}
  else{t.className='result-title incorrect';t.innerHTML='<i class="fas fa-times-circle"></i> Incomplete or Incorrect';}
  document.getElementById('resultScore').textContent='Selected: '+userAnswers.length+' | Correct: '+correctCount+' / '+totalCorrect;
  document.getElementById('rationaleContent').innerHTML=RATIONALE_HTML;
  document.getElementById('resultSection').classList.add('show');
  this.disabled=true;
});`;
    return previewShell(n, { caseTitle: true, instructionLabel: 'SELECT ALL THAT APPLY', bodyHtml, bodyScript });
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
  buildPreview(n) {
    let required = n.items.length;
    const m = n.question.match(/Select\s+(\d+)/i);
    if (m) required = parseInt(m[1], 10);
    const itemsHtml = n.items.map(it =>
      '<label class="item-checkbox" data-value="' + escapeHtml(it.letter) + '"><input type="checkbox" name="answers" value="' + escapeHtml(it.letter) + '"><span class="item-text">' + escapeHtml(it.letter + '. ' + it.text) + '</span></label>'
    ).join('');
    const bodyHtml = '<div class="items-container">' + itemsHtml + '</div>';
    const bodyScript = `
var correctAnswers=${jsonForScript(n.correct)};
var required=${required};
var totalCorrect=correctAnswers.length;
document.querySelectorAll('input[name="answers"]').forEach(function(cb){cb.addEventListener('change',function(){
  var parent=this.closest('.item-checkbox'); parent.classList.toggle('selected',this.checked);
  var checkedCount=document.querySelectorAll('input[name="answers"]:checked').length;
  if(checkedCount>required){this.checked=false;parent.classList.remove('selected');}
});});
document.getElementById('submitBtn').addEventListener('click',function(){
  var selected=Array.from(document.querySelectorAll('input[name="answers"]:checked')).map(function(c){return c.value;});
  if(selected.length===0) return;
  var earned=0;
  document.querySelectorAll('input[name="answers"]').forEach(function(cb){
    var val=cb.value, checked=cb.checked, parent=cb.closest('.item-checkbox');
    if(correctAnswers.indexOf(val)!==-1){parent.classList.add('correct-reveal'); if(checked) earned++;}
    else if(checked){parent.classList.add('wrong-reveal'); earned--;}
    cb.disabled=true;
  });
  earned=Math.max(0,earned);
  var pct=totalCorrect>0?Math.round((earned/totalCorrect)*100):0;
  var t=document.getElementById('resultTitle');
  t.className='result-title '+(earned===totalCorrect?'correct':'incorrect');
  t.innerHTML=(earned===totalCorrect?'<i class="fas fa-check-circle"></i> Correct!':'<i class="fas fa-times-circle"></i> Partially Correct');
  document.getElementById('resultScore').textContent='Score: '+pct+'% ('+earned+'/'+totalCorrect+' pts)';
  document.getElementById('rationaleContent').innerHTML=RATIONALE_HTML;
  document.getElementById('resultSection').classList.add('show');
  this.disabled=true;
});`;
    const label = required < n.items.length ? ('MULTIPLE RESPONSE: SELECT ' + required) : 'MULTIPLE RESPONSE: SELECT ALL THAT APPLY';
    return previewShell(n, { caseTitle: true, instructionLabel: label, bodyHtml, bodyScript });
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
  buildPreview(n) {
    const theadHtml = '<th></th>' + n.columns.map(c => '<th>' + escapeHtml(c) + '</th>').join('');
    const tbodyHtml = n.rows.map(row => {
      const cells = n.columns.map(col => '<td><input type="checkbox" class="matrix-cb" data-col="' + escapeHtml(col) + '" data-row="' + escapeHtml(row) + '"></td>').join('');
      return '<tr><td>' + escapeHtml(row) + '</td>' + cells + '</tr>';
    }).join('');
    const bodyHtml = '<div class="matrix-wrap"><table class="mx-tbl"><thead><tr>' + theadHtml + '</tr></thead><tbody>' + tbodyHtml + '</tbody></table></div>';
    const bodyScript = `
var correct=${jsonForScript(n.correct)};
document.getElementById('submitBtn').addEventListener('click',function(){
  var earned=0,total=0;
  Object.keys(correct).forEach(function(col){total+=correct[col].length;});
  document.querySelectorAll('.matrix-cb').forEach(function(cb){
    var col=cb.dataset.col,row=cb.dataset.row,checked=cb.checked;
    var list=correct[col]||[];
    var isCorrect=list.indexOf(row)!==-1;
    var td=cb.closest('td');
    if(isCorrect){td.classList.add('cell-correct'); if(checked) earned++;}
    else if(checked){td.classList.add('cell-wrong'); earned--;}
    cb.disabled=true;
  });
  earned=Math.max(0,earned);
  var pct=total>0?Math.round((earned/total)*100):0;
  var t=document.getElementById('resultTitle');
  t.className='result-title '+(earned===total?'correct':'incorrect');
  t.innerHTML=(earned===total?'<i class="fas fa-check-circle"></i> Correct!':'<i class="fas fa-times-circle"></i> Partially Correct');
  document.getElementById('resultScore').textContent='Score: '+pct+'% ('+earned+'/'+total+' pts)';
  document.getElementById('rationaleContent').innerHTML=RATIONALE_HTML;
  document.getElementById('resultSection').classList.add('show');
  this.disabled=true;
});`;
    return previewShell(n, { caseTitle: true, instructionLabel: 'MATRIX MULTIPLE RESPONSE', bodyHtml, extraCss: MATRIX_CSS, bodyScript });
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
  buildPreview(n) {
    function bank(items, type) { return items.map(it => '<div class="choice-token" draggable="true" data-type="' + type + '">' + escapeHtml(it.text) + '</div>').join(''); }
    const bodyHtml =
      '<div class="diagram-wrapper">' +
      '<div class="diagram-col"><div class="col-label">Actions to Take</div><div class="dropzone" data-type="action"><span>Drop Action Here</span></div><div class="dropzone" data-type="action"><span>Drop Action Here</span></div></div>' +
      '<div class="diagram-col"><div class="col-label">Condition</div><div class="dropzone" data-type="condition"><span>Drop Condition Here</span></div></div>' +
      '<div class="diagram-col"><div class="col-label">Monitor Parameters</div><div class="dropzone" data-type="parameter"><span>Drop Parameter Here</span></div><div class="dropzone" data-type="parameter"><span>Drop Parameter Here</span></div></div>' +
      '</div>' +
      '<div class="banks-container">' +
      '<div class="bank-col"><div class="bank-header">Actions Bank</div><div class="bank-list" data-type="action">' + bank(n.actions, 'action') + '</div></div>' +
      '<div class="bank-col"><div class="bank-header">Conditions Bank</div><div class="bank-list" data-type="condition">' + bank(n.conditions, 'condition') + '</div></div>' +
      '<div class="bank-col"><div class="bank-header">Parameters Bank</div><div class="bank-list" data-type="parameter">' + bank(n.params, 'parameter') + '</div></div>' +
      '</div>';
    const bodyScript = `
var correctA=${jsonForScript(n.actions.filter(function(a){return a.correct;}).map(function(a){return a.text;}))};
var correctC=${jsonForScript(n.conditions.filter(function(a){return a.correct;}).map(function(a){return a.text;}))};
var correctP=${jsonForScript(n.params.filter(function(a){return a.correct;}).map(function(a){return a.text;}))};
var dragged=null;
document.querySelectorAll('.choice-token').forEach(function(tok){tok.addEventListener('dragstart',function(){dragged=this;});});
document.querySelectorAll('.dropzone').forEach(function(zone){
  zone.addEventListener('dragover',function(e){e.preventDefault();});
  zone.addEventListener('drop',function(e){
    e.preventDefault();
    if(!dragged||dragged.dataset.type!==this.dataset.type) return;
    var existing=this.querySelector('.choice-token');
    if(existing) document.querySelector('.bank-list[data-type="'+dragged.dataset.type+'"]').appendChild(existing);
    var span=this.querySelector('span'); if(span) span.style.display='none';
    this.appendChild(dragged);
  });
});
document.querySelectorAll('.bank-list').forEach(function(bankEl){
  bankEl.addEventListener('dragover',function(e){e.preventDefault();});
  bankEl.addEventListener('drop',function(e){
    e.preventDefault();
    if(dragged&&dragged.dataset.type===this.dataset.type){
      this.appendChild(dragged);
      document.querySelectorAll('.dropzone').forEach(function(z){if(!z.querySelector('.choice-token')){var s=z.querySelector('span'); if(s) s.style.display='';}});
    }
  });
});
document.getElementById('submitBtn').addEventListener('click',function(){
  var incomplete=false;
  document.querySelectorAll('.dropzone').forEach(function(z){if(!z.querySelector('.choice-token')) incomplete=true;});
  if(incomplete){alert('Please fill all slots in the diagram.'); return;}
  var earned=0;
  document.querySelectorAll('.dropzone').forEach(function(z){
    var tok=z.querySelector('.choice-token');
    var txt=tok?tok.textContent.trim():'';
    var type=z.dataset.type;
    var list=type==='action'?correctA:(type==='condition'?correctC:correctP);
    if(list.indexOf(txt)!==-1){z.classList.add('correct-reveal'); earned++;}
    else{z.classList.add('wrong-reveal');}
  });
  var total=5, pct=Math.round((earned/total)*100);
  var t=document.getElementById('resultTitle');
  t.className='result-title '+(earned===total?'correct':'incorrect');
  t.innerHTML=(earned===total?'<i class="fas fa-check-circle"></i> Correct!':'<i class="fas fa-times-circle"></i> Partially Correct')+' ('+earned+'/'+total+')';
  document.getElementById('resultScore').textContent='Score: '+pct+'%';
  document.getElementById('rationaleContent').innerHTML=RATIONALE_HTML;
  document.getElementById('resultSection').classList.add('show');
  document.querySelectorAll('.choice-token').forEach(function(t2){t2.setAttribute('draggable','false');});
  this.disabled=true;
});`;
    return previewShell(n, { caseTitle: false, instructionLabel: 'BOW-TIE', bodyHtml, extraCss: BOWTIE_CSS, bodyScript });
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
  buildPreview(n) {
    const bankHtml = n.items.map(it => '<div class="choice-item" draggable="true">' + escapeHtml(it) + '</div>').join('');
    const isCloze = /_{5,}/.test(n.question);
    let questionHtml, questionHeaderStyle, blanksHtml;
    if (isCloze) {
      let idx = 0;
      questionHtml = escapeHtml(n.question).replace(/_{5,}/g, function() {
        return '<span class="blank cloze-blank" data-idx="' + (idx++) + '">Drop Here</span>';
      });
      questionHeaderStyle = 'line-height:2.4;';
      blanksHtml = '';
    } else {
      questionHtml = undefined;
      questionHeaderStyle = '';
      const slotsHtml = n.correct.map((_, idx) => '<div class="slot-row"><div class="slot-num">' + (idx + 1) + '</div><div class="blank" data-idx="' + idx + '">Drop Here</div></div>').join('');
      blanksHtml = '<div class="ordered-slots"><div class="slots-label">Arrange in correct order:</div>' + slotsHtml + '</div>';
    }
    const bodyHtml = blanksHtml + '<div class="choices-bank">' + bankHtml + '</div>';
    const bodyScript = `
var correct=${jsonForScript(n.correct)};
var dragged=null;
document.querySelectorAll('.choice-item').forEach(function(tok){tok.addEventListener('dragstart',function(){dragged=this;});});
document.querySelectorAll('.blank').forEach(function(zone){
  zone.addEventListener('dragover',function(e){e.preventDefault();});
  zone.addEventListener('drop',function(e){
    e.preventDefault();
    if(!dragged) return;
    var existing=this.querySelector('.choice-item');
    if(existing) document.querySelector('.choices-bank').appendChild(existing);
    this.textContent=''; this.appendChild(dragged); this.classList.add('filled');
  });
});
var bankEl=document.querySelector('.choices-bank');
bankEl.addEventListener('dragover',function(e){e.preventDefault();});
bankEl.addEventListener('drop',function(e){e.preventDefault(); if(dragged) this.appendChild(dragged);});
document.getElementById('submitBtn').addEventListener('click',function(){
  var blanks=Array.from(document.querySelectorAll('.blank'));
  if(blanks.some(function(b){return !b.querySelector('.choice-item');})){alert('Please fill all blanks.'); return;}
  var earned=0;
  blanks.forEach(function(b,idx){
    var val=b.querySelector('.choice-item').textContent.trim();
    if(val.toLowerCase()===(correct[idx]||'').toLowerCase()){b.classList.add('correct-reveal'); earned++;}
    else{b.classList.add('wrong-reveal');}
  });
  var total=correct.length, pct=Math.round((earned/total)*100);
  var t=document.getElementById('resultTitle');
  t.className='result-title '+(earned===total?'correct':'incorrect');
  t.innerHTML=(earned===total?'<i class="fas fa-check-circle"></i> Correct!':'<i class="fas fa-times-circle"></i> Partially Correct');
  document.getElementById('resultScore').textContent='Score: '+pct+'% ('+earned+'/'+total+')';
  document.getElementById('rationaleContent').innerHTML=RATIONALE_HTML;
  document.getElementById('resultSection').classList.add('show');
  document.querySelectorAll('.choice-item').forEach(function(t2){t2.setAttribute('draggable','false');});
  this.disabled=true;
});`;
    return previewShell(n, { caseTitle: false, instructionLabel: 'DRAG & DROP', questionHtml, questionHeaderStyle, bodyHtml, extraCss: DRAGDROP_CSS, bodyScript });
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
  buildPreview(n) {
    const optionsHtml = '<option value="">Select answer</option>' + n.options.map(o => '<option value="' + escapeHtml(o) + '">' + escapeHtml(o) + '</option>').join('');
    const hasMarkers = /_{3,}|\[\[blank\]\]|\{\{blank\}\}|\[blank_?\d*\]/gi.test(n.question);
    let questionHtml, bodyHtml;
    if (hasMarkers) {
      let idx = 0;
      questionHtml = escapeHtml(n.question).replace(/_{3,}|\[\[blank\]\]|\{\{blank\}\}|\[blank_?\d*\]/gi, function() {
        if (idx >= n.correct_words.length) return '';
        return '<select class="inline-select dd-input" data-idx="' + (idx++) + '">' + optionsHtml + '</select>';
      });
      bodyHtml = '';
    } else {
      questionHtml = undefined;
      const rowsHtml = n.correct_words.map((_, idx) =>
        '<div class="fallback-row"><select class="inline-select dd-input" data-idx="' + idx + '">' + optionsHtml + '</select></div>'
      ).join('');
      bodyHtml = '<div class="fallback-blanks">' + rowsHtml + '</div>';
    }
    const bodyScript = `
var correct=${jsonForScript(n.correct_words)};
document.getElementById('submitBtn').addEventListener('click',function(){
  var selects=Array.from(document.querySelectorAll('.dd-input'));
  if(selects.some(function(s){return !s.value;})){alert('Please answer all blanks.'); return;}
  var earned=0;
  selects.forEach(function(s,idx){
    var ok=s.value.trim().toLowerCase()===(correct[idx]||'').trim().toLowerCase();
    s.classList.add(ok?'correct-reveal':'wrong-reveal'); s.disabled=true;
    if(ok) earned++;
  });
  var total=correct.length, pct=Math.round((earned/total)*100);
  var t=document.getElementById('resultTitle');
  t.className='result-title '+(earned===total?'correct':'incorrect');
  t.innerHTML=(earned===total?'<i class="fas fa-check-circle"></i> Correct!':'<i class="fas fa-times-circle"></i> Partially Correct');
  document.getElementById('resultScore').textContent='Score: '+pct+'% ('+earned+'/'+total+')';
  document.getElementById('rationaleContent').innerHTML=RATIONALE_HTML;
  document.getElementById('resultSection').classList.add('show');
  this.disabled=true;
});`;
    return previewShell(n, { caseTitle: false, instructionLabel: 'DROP-DOWN CLOZE', questionHtml, bodyHtml, extraCss: DROPDOWN_CSS, bodyScript });
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
  buildPreview(n) {
    const pieces = tokenizePassage(n.passage, n.options);
    const passageHtml = pieces.map(p => p.token === null ? escapeHtml(p.text) : ('<span class="inline-token" data-phrase="' + p.token + '">' + escapeHtml(p.text) + '</span>')).join('');
    const bodyHtml = '<div class="passage-box">' + passageHtml + '</div><div class="selection-count" id="selectionCount">Click phrases to highlight (max ' + n.maxHighlights + ')</div>';
    const correctFlags = n.options.map(o => n.correct_words.some(cv => cv.toLowerCase() === o.toLowerCase()));
    const bodyScript = `
var correctFlags=${jsonForScript(correctFlags)};
var maxHighlights=${n.maxHighlights};
var selected=new Set();
document.querySelectorAll('.inline-token').forEach(function(tok){tok.addEventListener('click',function(){
  var idx=parseInt(this.dataset.phrase,10);
  if(selected.has(idx)){selected.delete(idx); this.classList.remove('selected');}
  else{ if(selected.size>=maxHighlights) return; selected.add(idx); this.classList.add('selected'); }
  document.getElementById('selectionCount').textContent=selected.size+' of '+maxHighlights+' highlighted';
});});
document.getElementById('submitBtn').addEventListener('click',function(){
  var earned=0, total=correctFlags.filter(Boolean).length;
  document.querySelectorAll('.inline-token').forEach(function(tok){
    var idx=parseInt(tok.dataset.phrase,10);
    var isCorrect=correctFlags[idx];
    var isSelected=selected.has(idx);
    if(isCorrect){tok.classList.add('correct-reveal'); if(isSelected) earned++;}
    else if(isSelected){tok.classList.add('wrong-reveal');}
    tok.style.pointerEvents='none';
  });
  var pct=total>0?Math.round((earned/total)*100):0;
  var t=document.getElementById('resultTitle');
  t.className='result-title '+(earned===total?'correct':'incorrect');
  t.innerHTML=(earned===total?'<i class="fas fa-check-circle"></i> Correct!':'<i class="fas fa-times-circle"></i> Partially Correct');
  document.getElementById('resultScore').textContent='Score: '+pct+'% ('+earned+'/'+total+')';
  document.getElementById('rationaleContent').innerHTML=RATIONALE_HTML;
  document.getElementById('resultSection').classList.add('show');
  this.disabled=true;
});`;
    return previewShell(n, { caseTitle: false, instructionLabel: 'HIGHLIGHT', bodyHtml, extraCss: HIGHLIGHT_CSS, bodyScript });
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
