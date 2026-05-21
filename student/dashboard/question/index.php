<?php
include '../../../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$step      = max(1, min(150, intval($_GET['step'] ?? 1)));
$concept   = isset($_GET['topics1']) ? $_GET['topics1'] : '';
$topics2   = isset($_GET['topics2']) ? $_GET['topics2'] : '';
$kilanlan  = isset($_GET['kilanlan']) ? $_GET['kilanlan'] : '';
$user_id   = intval($_GET['id'] ?? $_SESSION['user_id']);
$qnumsRaw  = isset($_GET['qnums']) ? $_GET['qnums'] : '';
$cc        = intval($_GET['cc'] ?? 0);
$wc        = intval($_GET['wc'] ?? 0);

$conceptSafe = mysqli_real_escape_string($con, $concept);

// Parse system list from topics2
$selectedTopics = $topics2 ? explode(',', $topics2) : [];
$systems = [];
foreach ($selectedTopics as $topic) {
    $decoded = urldecode($topic);
    $parts   = explode('|', $decoded, 2);
    $sys     = count($parts) > 1 ? $parts[1] : $parts[0];
    if ($sys !== '') $systems[] = mysqli_real_escape_string($con, $sys);
}

if (empty($systems)) {
    die('No topics selected. Please go back and select a concept.');
}

// Build exclusion list from already-seen question IDs
$excludeIds = [];
if (!empty($qnumsRaw)) {
    foreach (explode('|', $qnumsRaw) as $qid) {
        $qid = intval(trim($qid));
        if ($qid > 0) $excludeIds[] = $qid;
    }
}

$systemList = "'" . implode("','", $systems) . "'";
$excludeSql = !empty($excludeIds) ? "AND id NOT IN (" . implode(',', $excludeIds) . ")" : "";

$sql    = "SELECT * FROM question WHERE topics1='$conceptSafe' AND system IN ($systemList) AND (type IS NULL OR type != 'SATA') $excludeSql ORDER BY RAND() LIMIT 1";
$result = mysqli_query($con, $sql);
$row    = $result ? mysqli_fetch_assoc($result) : null;

if (!$row) {
    die('No question available for this step. All questions in this concept may have been exhausted.');
}

$progress   = round(($step / 150) * 100);
$answerBase = '../rationale/answer.php';
$answerUrl  = $answerBase . '?' . http_build_query([
    'step'     => $step,
    'topics1'  => $concept,
    'topics2'  => $topics2,
    'kilanlan' => $kilanlan,
    'id'       => $user_id,
    'qq'       => $row['id'],
    'system'   => $row['system'],
    'qnums'    => $qnumsRaw,
    'cc'       => $cc,
    'wc'       => $wc,
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Question <?= $step ?> of 150 — Studium</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="shortcut icon" href="../../../img/logo1.svg">
  <style>
    :root {
      --primary: #0a1628;
      --primary-light: #1e3a5f;
      --accent: #0d9488;
      --surface: #ffffff;
      --surface-alt: #f0f4f8;
      --text: #0f172a;
      --text-muted: #64748b;
      --border: #e2e8f0;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; }
    body { font-family: 'Inter', sans-serif; background: var(--surface-alt); color: var(--text); display: flex; flex-direction: column; }

    /* ── Navbar ── */
    .eq-nav {
      background: var(--primary);
      color: #fff;
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 0 24px;
      height: 58px;
      flex-shrink: 0;
      position: sticky;
      top: 0;
      z-index: 50;
      box-shadow: 0 2px 12px rgba(0,0,0,.3);
    }
    .eq-logo { font-size: 1.05rem; font-weight: 800; color: #fff; white-space: nowrap; letter-spacing: -0.5px; }
    .eq-logo span { color: var(--accent); }
    .eq-progress-wrap {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 5px;
      max-width: 380px;
      margin: 0 auto;
    }
    .eq-progress-label {
      font-size: 0.74rem;
      color: rgba(255,255,255,.7);
      display: flex;
      justify-content: space-between;
    }
    .eq-progress-bar { height: 5px; background: rgba(255,255,255,.15); border-radius: 3px; overflow: hidden; }
    .eq-progress-fill { height: 100%; background: var(--accent); border-radius: 3px; }
    .eq-timer {
      font-size: 0.83rem;
      color: rgba(255,255,255,.85);
      white-space: nowrap;
      display: flex;
      align-items: center;
      gap: 6px;
      font-variant-numeric: tabular-nums;
    }
    .eq-timer i { color: var(--accent); font-size: 0.8rem; }

    /* ── Main ── */
    .eq-main { flex: 1; overflow-y: auto; padding: 32px 20px; display: flex; flex-direction: column; align-items: center; }

    /* ── Card ── */
    .eq-card {
      background: var(--surface);
      border-radius: 18px;
      box-shadow: 0 4px 24px rgba(0,0,0,.07);
      padding: 36px;
      width: 100%;
      max-width: 780px;
    }
    .eq-qnum {
      font-size: 0.75rem;
      font-weight: 700;
      color: var(--accent);
      text-transform: uppercase;
      letter-spacing: 0.6px;
      margin-bottom: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .eq-qnum span.eq-concept {
      font-weight: 500;
      color: var(--text-muted);
      text-transform: none;
      letter-spacing: 0;
    }
    .eq-question {
      font-size: 1rem;
      line-height: 1.75;
      color: var(--text);
      margin-bottom: 28px;
      font-weight: 500;
    }
    .eq-divider { height: 1px; background: var(--border); margin-bottom: 24px; }

    /* ── Choices ── */
    .eq-choices { display: flex; flex-direction: column; gap: 10px; margin-bottom: 28px; }
    .eq-choice-label {
      display: flex;
      align-items: flex-start;
      gap: 14px;
      padding: 14px 18px;
      border: 2px solid var(--border);
      border-radius: 12px;
      cursor: pointer;
      transition: border-color 0.15s, background 0.15s;
      background: var(--surface);
      user-select: none;
    }
    .eq-choice-label:hover { border-color: var(--accent); background: rgba(13,148,136,.04); }
    .eq-choice-label.selected { border-color: var(--accent); background: rgba(13,148,136,.07); }
    .eq-choice-label input[type="radio"] { display: none; }
    .eq-choice-dot {
      width: 20px; height: 20px;
      border-radius: 50%;
      border: 2px solid var(--border);
      flex-shrink: 0;
      margin-top: 2px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: border-color 0.15s, background 0.15s;
    }
    .eq-choice-label.selected .eq-choice-dot { border-color: var(--accent); background: var(--accent); }
    .eq-choice-dot::after {
      content: '';
      width: 7px; height: 7px;
      border-radius: 50%;
      background: #fff;
      display: none;
    }
    .eq-choice-label.selected .eq-choice-dot::after { display: block; }
    .eq-choice-letter {
      font-size: 0.78rem;
      font-weight: 700;
      color: var(--text-muted);
      width: 18px;
      flex-shrink: 0;
      margin-top: 2px;
    }
    .eq-choice-label.selected .eq-choice-letter { color: var(--accent); }
    .eq-choice-text { font-size: 0.95rem; line-height: 1.55; color: var(--text); }

    /* ── Submit ── */
    .eq-submit-row { display: flex; justify-content: flex-end; align-items: center; gap: 12px; }
    .eq-submit-btn {
      background: var(--accent);
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 12px 32px;
      font-size: 0.95rem;
      font-weight: 700;
      font-family: 'Inter', sans-serif;
      cursor: pointer;
      transition: opacity 0.2s, transform 0.1s;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .eq-submit-btn:hover { opacity: 0.88; }
    .eq-submit-btn:active { transform: scale(0.97); }
    .eq-end-btn {
      background: transparent;
      border: 1.5px solid rgba(255,255,255,.3);
      color: rgba(255,255,255,.7);
      border-radius: 8px;
      padding: 6px 14px;
      font-size: 0.78rem;
      font-weight: 600;
      font-family: 'Inter', sans-serif;
      cursor: pointer;
      margin-left: auto;
      transition: border-color 0.2s, color 0.2s;
    }
    .eq-end-btn:hover { border-color: #ef4444; color: #ef4444; }

    @media (max-width: 640px) {
      .eq-nav { padding: 0 14px; gap: 10px; }
      .eq-logo { font-size: 0.9rem; }
      .eq-main { padding: 16px 10px; }
      .eq-card { padding: 20px 16px; border-radius: 14px; }
      .eq-question { font-size: 0.93rem; }
      .eq-choice-text { font-size: 0.88rem; }
    }
  </style>
</head>
<body oncontextmenu="return false" onselectstart="return false" ondragstart="return false">

<nav class="eq-nav">
  <div class="eq-logo">Studium<span>.</span></div>

  <div class="eq-progress-wrap">
    <div class="eq-progress-label">
      <span>Question <?= $step ?> of 150</span>
      <span><?= $progress ?>%</span>
    </div>
    <div class="eq-progress-bar">
      <div class="eq-progress-fill" style="width:<?= $progress ?>%"></div>
    </div>
  </div>

  <div class="eq-timer">
    <i class="fa-regular fa-clock"></i>
    <span id="perTimer">0:00</span>
  </div>

  <?php
  $userRow = mysqli_query($con, "SELECT bundle_name FROM login WHERE id = '$user_id'");
  $userData = mysqli_fetch_assoc($userRow);
  ?>
  <button class="eq-end-btn" onclick="confirmEnd('<?= htmlspecialchars($userData['bundle_name'] ?? '', ENT_QUOTES) ?>')">
    <i class="fa-solid fa-stop"></i> End
  </button>
</nav>

<main class="eq-main">
  <div class="eq-card">
    <div class="eq-qnum">
      Question <?= $step ?>
      <span class="eq-concept">&bull; <?= htmlspecialchars($concept) ?></span>
    </div>

    <form method="POST" action="<?= htmlspecialchars($answerUrl) ?>" id="questionForm">
      <input type="hidden" name="time_taken" id="time_taken" value="0">
      <?php if ($step === 150): ?>
      <input type="hidden" name="totalTime" id="totalTime" value="0">
      <?php endif; ?>

      <div class="eq-question">
        <?= $row['question'] ?>
      </div>

      <div class="eq-divider"></div>

      <div class="eq-choices">
        <?php
        $choices = [1 => $row['choiceA'], 2 => $row['choiceB'], 3 => $row['choiceC'], 4 => $row['choiceD']];
        $letters = [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D'];
        foreach ($choices as $val => $text):
        ?>
        <label class="eq-choice-label" id="choice-<?= $val ?>">
          <input type="radio" name="ans" value="<?= $val ?>" required onchange="selectChoice(<?= $val ?>)">
          <div class="eq-choice-dot"></div>
          <div class="eq-choice-letter"><?= $letters[$val] ?>.</div>
          <div class="eq-choice-text"><?= $text ?></div>
        </label>
        <?php endforeach; ?>
      </div>

      <div class="eq-submit-row">
        <button type="submit" class="eq-submit-btn" onclick="captureTime()">
          <i class="fa-regular fa-paper-plane"></i> Submit Answer
        </button>
      </div>
    </form>
  </div>
</main>

<!-- ── Floating Tool Dock ── -->
<div class="eq-tool-dock" id="toolDock">
  <button class="eq-tool-btn" id="calcBtn" onclick="toggleTool('calc')" title="Calculator">
    <i class="fa-solid fa-calculator"></i><span>Calc</span>
  </button>
  <button class="eq-tool-btn" id="noteBtn" onclick="toggleNoteModal()" title="Notes">
    <i class="fa-solid fa-note-sticky"></i><span>Notes</span>
  </button>
  <button class="eq-tool-btn" id="fsBtn" onclick="toggleFullscreen()" title="Fullscreen">
    <i class="fa-solid fa-expand" id="fsIcon"></i><span>Full</span>
  </button>
</div>

<!-- ── Calculator Panel ── -->
<div class="eq-tool-panel" id="calcPanel">
  <div class="eq-tp-header"><span><i class="fa-solid fa-calculator me-2"></i>Calculator</span><button onclick="toggleTool('calc')">&times;</button></div>
  <input class="eq-calc-display" id="calcDisplay" readonly value="0">
  <div class="eq-calc-grid">
    <button class="eq-calc-btn eq-cb-fn" onclick="calcClear()">C</button>
    <button class="eq-calc-btn eq-cb-fn" onclick="calcDel()">⌫</button>
    <button class="eq-calc-btn eq-cb-fn" onclick="calcInput('%')">%</button>
    <button class="eq-calc-btn eq-cb-op" onclick="calcInput('/')">÷</button>
    <button class="eq-calc-btn" onclick="calcInput('7')">7</button>
    <button class="eq-calc-btn" onclick="calcInput('8')">8</button>
    <button class="eq-calc-btn" onclick="calcInput('9')">9</button>
    <button class="eq-calc-btn eq-cb-op" onclick="calcInput('*')">×</button>
    <button class="eq-calc-btn" onclick="calcInput('4')">4</button>
    <button class="eq-calc-btn" onclick="calcInput('5')">5</button>
    <button class="eq-calc-btn" onclick="calcInput('6')">6</button>
    <button class="eq-calc-btn eq-cb-op" onclick="calcInput('-')">−</button>
    <button class="eq-calc-btn" onclick="calcInput('1')">1</button>
    <button class="eq-calc-btn" onclick="calcInput('2')">2</button>
    <button class="eq-calc-btn" onclick="calcInput('3')">3</button>
    <button class="eq-calc-btn eq-cb-op" onclick="calcInput('+')">+</button>
    <button class="eq-calc-btn" style="grid-column:span 2;" onclick="calcInput('0')">0</button>
    <button class="eq-calc-btn" onclick="calcInput('.')">.</button>
    <button class="eq-calc-btn eq-cb-eq" onclick="calcEval()">=</button>
  </div>
</div>

<!-- ── Notes Modal ── -->
<div class="modal-overlay" id="notePanel" style="background:transparent;pointer-events:none;z-index:1300;display:none;">
  <div class="modal-box" style="pointer-events:auto;width:370px;padding:20px;border:1px solid var(--border);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
      <span style="font-weight:800;font-size:12px;text-transform:uppercase;color:var(--text-muted);">
        <i class="fa-solid fa-note-sticky me-1"></i> Quick Note
      </span>
      <i class="fas fa-times" style="cursor:pointer;" onclick="toggleNoteModal()"></i>
    </div>
    <input id="examNoteTitle" type="text"
      style="width:100%;border:1px solid var(--border);border-radius:10px;padding:10px 12px;font-size:13px;font-weight:600;margin-bottom:10px;background:var(--card-bg,#fff);color:var(--text);"
      placeholder="Note title (required to save)..." />
    <textarea id="examNote"
      style="width:100%;height:180px;border:1px solid var(--border);border-radius:10px;padding:12px;font-size:13px;resize:none;background:var(--card-bg,#fff);color:var(--text);"
      placeholder="Type your clinical notes here..."></textarea>
    <div style="margin-top:10px;font-size:10px;color:var(--text-muted);">
      <i class="fas fa-info-circle me-1"></i> This note will automatically save to <strong>My Notes</strong> after the exam ends.
    </div>
  </div>
</div>

<style>
/* ── Floating tool dock ── */
.eq-tool-dock {
  position: fixed;
  right: 16px;
  top: 50%;
  transform: translateY(-50%);
  display: flex;
  flex-direction: column;
  gap: 6px;
  z-index: 200;
}
.eq-tool-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  width: 52px;
  padding: 10px 4px;
  background: #fff;
  border: 1.5px solid var(--border);
  border-radius: 12px;
  cursor: pointer;
  font-size: 1.05rem;
  color: var(--text-muted);
  box-shadow: 0 2px 8px rgba(0,0,0,.08);
  transition: background 0.15s, color 0.15s, border-color 0.15s;
  font-family: 'Inter', sans-serif;
}
.eq-tool-btn span { font-size: 0.6rem; font-weight: 600; }
.eq-tool-btn:hover, .eq-tool-btn.active { background: var(--accent); color: #fff; border-color: var(--accent); }

/* ── Tool panels ── */
.eq-tool-panel {
  position: fixed;
  right: 78px;
  top: 50%;
  transform: translateY(-50%);
  width: 280px;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 8px 40px rgba(0,0,0,.15);
  border: 1px solid var(--border);
  z-index: 199;
  display: none;
  flex-direction: column;
  overflow: hidden;
}
.eq-tool-panel.is-open { display: flex; }
.eq-tp-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: var(--primary);
  color: #fff;
  font-size: 0.85rem;
  font-weight: 700;
}
.eq-tp-header button {
  background: rgba(255,255,255,.15);
  border: none;
  color: #fff;
  border-radius: 6px;
  width: 24px; height: 24px;
  cursor: pointer;
  font-size: 1rem;
  line-height: 1;
}
/* Calculator */
.eq-calc-display {
  width: 100%;
  background: #0a1628;
  color: #5eead4;
  font-size: 1.4rem;
  font-weight: 700;
  text-align: right;
  padding: 14px 16px;
  border: none;
  font-family: 'Inter', sans-serif;
  letter-spacing: 1px;
}
.eq-calc-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1px;
  background: var(--border);
}
.eq-calc-btn {
  padding: 14px 0;
  background: #fff;
  border: none;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  font-family: 'Inter', sans-serif;
  color: var(--text);
  transition: background 0.1s;
}
.eq-calc-btn:hover { background: #f0fdf9; }
.eq-cb-op  { color: var(--accent); }
.eq-cb-fn  { color: #64748b; background: #f8fafc; }
.eq-cb-eq  { background: var(--accent); color: #fff; }
.eq-cb-eq:hover { background: #0b7e74; }
/* Note modal — same as NGN */
.modal-overlay {
  position: fixed; top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.45); backdrop-filter: blur(3px);
  display: none; align-items: center; justify-content: center;
  z-index: 1300;
}
.modal-box {
  background: var(--card-bg, #fff);
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.18);
  animation: slideUp 0.25s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes slideUp {
  from { transform: translateY(20px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}
@media (max-width: 640px) {
  .eq-tool-dock { right: 10px; top: auto; bottom: 80px; transform: none; }
  .eq-tool-panel { right: 0; left: 0; top: auto; bottom: 0; transform: none; width: 100%; border-radius: 16px 16px 0 0; }
  .eq-note-area { min-height: 160px; }
}
</style>

<script>
// ── Timer setup — totalSeconds is the continuous session timer (shown in navbar) ──
// qSeconds counts only this question's elapsed time, used only for time_taken DB field
let totalSeconds = parseInt(localStorage.getItem('count')) || 0;
let questionStartSeconds = totalSeconds; // snapshot at page load
const perTimerEl = document.getElementById('perTimer');

function fmtTime(s) { const m = Math.floor(s / 60), ss = s % 60; return m + ':' + (ss < 10 ? '0' + ss : ss); }

// Show current session total immediately (no flash of 0:00)
perTimerEl.textContent = fmtTime(totalSeconds);

const totalTimerInterval = setInterval(function () {
  totalSeconds++;
  perTimerEl.textContent = fmtTime(totalSeconds);
  localStorage.setItem('count', totalSeconds);
}, 1000);

function captureTime() {
  clearInterval(totalTimerInterval);
  const timeTaken = totalSeconds - questionStartSeconds;
  document.getElementById('time_taken').value = timeTaken;
  <?php if ($step === 150): ?>
  document.getElementById('totalTime').value = totalSeconds;
  <?php endif; ?>
  localStorage.setItem('timer_paused', 'true');
  localStorage.setItem('count', totalSeconds);
}

function selectChoice(val) {
  document.querySelectorAll('.eq-choice-label').forEach(function (l) { l.classList.remove('selected'); });
  document.getElementById('choice-' + val).classList.add('selected');
}

function confirmEnd(bundleName) {
  Swal.fire({
    title: 'End Study Session?',
    text: 'Your progress for this session will be lost.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, End Session',
    cancelButtonText: 'Continue Studying'
  }).then(function (result) {
    if (result.isConfirmed) {
      clearInterval(qTimerInterval);
      clearInterval(totalTimerInterval);
      window.location.href = '../index.php?bundle_name=' + encodeURIComponent(bundleName);
    }
  });
}

// ── Tools ──
let openPanel = null;
function toggleTool(name) {
  const panelId = name + 'Panel', btnId = name + 'Btn';
  const panel = document.getElementById(panelId), btn = document.getElementById(btnId);
  if (!panel) return;
  if (panel.classList.contains('is-open')) {
    panel.classList.remove('is-open'); btn.classList.remove('active');
    openPanel = null;
  } else {
    if (openPanel) {
      document.getElementById(openPanel + 'Panel')?.classList.remove('is-open');
      document.getElementById(openPanel + 'Btn')?.classList.remove('active');
    }
    panel.classList.add('is-open'); btn.classList.add('active');
    openPanel = name;
  }
}

// Calculator logic
let calcExpr = '', calcFresh = true;
const calcDisplay = document.getElementById('calcDisplay');
function calcInput(v) {
  const ops = ['+','-','*','/','%'];
  if (calcFresh && !ops.includes(v)) { calcExpr = ''; calcFresh = false; }
  calcExpr += v;
  calcDisplay.value = calcExpr;
}
function calcClear() { calcExpr = ''; calcDisplay.value = '0'; calcFresh = true; }
function calcDel() {
  calcExpr = calcExpr.slice(0, -1);
  calcDisplay.value = calcExpr || '0';
}
function calcEval() {
  try {
    if (!/^[0-9+\-*/.() %]+$/.test(calcExpr)) { calcDisplay.value = 'Error'; return; }
    const result = Function('"use strict"; return (' + calcExpr.replace(/%/g, '/100') + ')')();
    calcDisplay.value = parseFloat(result.toFixed(10)).toString();
    calcExpr = calcDisplay.value;
    calcFresh = true;
  } catch(e) { calcDisplay.value = 'Error'; calcExpr = ''; }
}
document.addEventListener('keydown', function(e) {
  if (!document.getElementById('calcPanel').classList.contains('is-open')) return;
  if (document.activeElement === document.getElementById('noteArea')) return;
  if ('0123456789+-*/.'.includes(e.key)) { e.preventDefault(); calcInput(e.key); }
  else if (e.key === 'Enter') { e.preventDefault(); calcEval(); }
  else if (e.key === 'Backspace') { e.preventDefault(); calcDel(); }
  else if (e.key === 'Escape') toggleTool('calc');
});

// Note modal toggle — matches NGN style
function toggleNoteModal() {
  const modal = document.getElementById('notePanel');
  const isHidden = modal.style.display === 'none' || modal.style.display === '';
  modal.style.display = isHidden ? 'flex' : 'none';
  modal.style.pointerEvents = isHidden ? 'auto' : 'none';
  if (isHidden) document.getElementById('examNoteTitle').focus();
}

// Save note to My Notes on form submit
document.getElementById('questionForm').addEventListener('submit', async function(e) {
  const title   = (document.getElementById('examNoteTitle')?.value || '').trim();
  const content = (document.getElementById('examNote')?.value || '').trim();
  if (title && content) {
    e.preventDefault();
    const fd = new FormData();
    fd.append('note_title',   title);
    fd.append('note_content', content);
    await fetch('../mynotes.php', { method: 'POST', body: fd }).catch(() => {});
    this.submit();
  }
});

// Fullscreen
function toggleFullscreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen().catch(() => {});
    document.getElementById('fsIcon').className = 'fa-solid fa-compress';
  } else {
    document.exitFullscreen();
    document.getElementById('fsIcon').className = 'fa-solid fa-expand';
  }
}
document.addEventListener('fullscreenchange', function() {
  document.getElementById('fsIcon').className = document.fullscreenElement ? 'fa-solid fa-compress' : 'fa-solid fa-expand';
});
</script>
</body>
</html>
