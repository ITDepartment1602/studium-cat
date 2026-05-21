<?php
// Exam Mode — Main Controller | Phase 2
// IRT Rasch (1PL) Adaptive CAT Engine — sample pool (dev mode)
require_once '../../../config.php';

$isProduction = !in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']);
if ($isProduction) { error_reporting(0); ini_set('display_errors', 0); }

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$user_id  = intval($_SESSION['user_id']);
$user     = db()->fetchOne("SELECT fullname FROM login WHERE id = ? LIMIT 1", [$user_id]);
$fullname = $user ? $user['fullname'] : 'Student';

// ─── DIFFICULTY MAP ────────────────────────────────────────────────────────
$DIFF_MAP_PHP = ['easy' => -1.5, 'moderate' => 0.0, 'medium' => 0.0, 'hard' => 1.5];

// ─── SAMPLE POOL DISTRIBUTION (dev: 30 items; change TARGET_TOTAL → 150 for prod) ─
$TARGET_TOTAL = 30;
$examDistribution = [
    ['tables' => ['mcq'],                              'type' => 'mc',        'quota' => 10, 'filter' => ''],
    ['tables' => ['cat'],                            'type' => 'mc',        'quota' => 5,  'filter' => "(type IS NULL OR type = '')"],
    ['tables' => ['sata'],                           'type' => 'sata',      'quota' => 4,  'filter' => ''],
    ['tables' => ['mmr'],                            'type' => 'mmr',       'quota' => 3,  'filter' => ''],
    ['tables' => ['highlight'],                      'type' => 'highlight', 'quota' => 2,  'filter' => ''],
    ['tables' => ['btq'],                            'type' => 'bowtie',    'quota' => 2,  'filter' => ''],
    ['tables' => ['dragndrop'],                      'type' => 'dragndrop', 'quota' => 2,  'filter' => ''],
    ['tables' => ['dropdown', 'dropdown_questions'], 'type' => 'dropdown',  'quota' => 2,  'filter' => ''],
    ['tables' => ['mpr'],                            'type' => 'mpr',       'quota' => 2,  'filter' => ''],
];

function exam_table_exists(string $t): bool {
    return db()->fetchOne(
        "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
        [$t]
    ) !== null;
}

// Build candidate pool — 3× quota per spec for IRT selection room
$examPool = [];
foreach ($examDistribution as $spec) {
    $actualTable = null;
    foreach ($spec['tables'] as $t) {
        if (exam_table_exists($t)) { $actualTable = $t; break; }
    }
    if (!$actualTable) continue;

    $where = $spec['filter'] !== '' ? "WHERE {$spec['filter']}" : '';
    $rows  = db()->fetchAll(
        "SELECT id,
                COALESCE(dlevel,'moderate') AS dlevel,
                COALESCE(difficulty_logit, 0) AS difficulty_logit
         FROM `{$actualTable}` {$where} ORDER BY RAND() LIMIT ?",
        [$spec['quota'] * 3]
    );
    foreach ($rows as $row) {
        $dl    = strtolower(trim($row['dlevel'] ?: 'moderate'));
        $logit = floatval($row['difficulty_logit']);
        // Use calibrated logit when available; fall back to dlevel bucket only if 0/null
        $b     = ($logit !== 0.0) ? $logit : ($DIFF_MAP_PHP[$dl] ?? 0.0);
        $examPool[] = [
            'id'    => intval($row['id']),
            'type'  => $spec['type'],
            'table' => $actualTable,
            'dlevel'=> $dl,
            'b'     => $b,
        ];
    }
}

if (count($examPool) < 5) {
    die('Insufficient exam pool. Visit <a href="db_audit_dlevel.php">db_audit_dlevel.php</a> to diagnose.');
}
shuffle($examPool);

// ─── ALWAYS FRESH — NO RESUME ────────────────────────────────────────────────
// Cancel any existing incomplete exam at page load (no resume allowed)
$existingState = db()->fetchOne(
    "SELECT examTaken FROM temporary_exam_state WHERE student_id = ? AND exam_mode = 'exam'",
    [$user_id]
);
if ($existingState) {
    $existingTaken = intval($existingState['examTaken']);
    db()->execute("DELETE FROM temporary_exam_result WHERE student_id = ? AND examTaken = ?", [$user_id, $existingTaken]);
    db()->execute("DELETE FROM temporary_exam_state WHERE student_id = ? AND exam_mode = 'exam'", [$user_id]);
    unset($_SESSION['current_exam_examTaken']);
}

// Generate new attempt number
if (!isset($_SESSION['current_exam_examTaken'])) {
    $userRow = db()->fetchOne("SELECT examTaken FROM login WHERE id = ? LIMIT 1", [$user_id]);
    $base = isset($userRow['examTaken']) ? intval($userRow['examTaken']) : 0;
    $_SESSION['current_exam_examTaken'] = $base + 1;
    db()->execute("UPDATE login SET examTaken = examTaken + 1 WHERE id = ?", [$user_id]);
}
$examTaken = $_SESSION['current_exam_examTaken'];

// ─── JS VARIABLES ────────────────────────────────────────────────────────────
$examPoolJs    = json_encode($examPool, JSON_THROW_ON_ERROR);
$examTakenJs   = json_encode($examTaken);
$userIdJs      = json_encode($user_id);
$fullnameJs    = json_encode($fullname);
$targetTotalJs = json_encode($TARGET_TOTAL);

// Build per-type quota map (sum quotas when multiple specs share a type)
$typeQuotaMap = [];
foreach ($examDistribution as $spec) {
    $t = $spec['type'];
    $typeQuotaMap[$t] = ($typeQuotaMap[$t] ?? 0) + $spec['quota'];
}
$typeQuotaMapJs = json_encode($typeQuotaMap);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Studium CAT Exam</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    :root {
      --primary: #0a1628;
      --primary-light: #1e3a5f;
      --accent: #3b82f6;
      --accent-glow: rgba(59,130,246,.3);
      --success: #10b981;
      --danger: #ef4444;
      --warning: #f59e0b;
      --surface: #ffffff;
      --surface-alt: #f8fafc;
      --text: #0f172a;
      --text-muted: #64748b;
      --border: #e2e8f0;
      --radius: 14px;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; overflow: hidden; }
    body { font-family: 'Inter', sans-serif; background: var(--surface-alt); color: var(--text); display: flex; flex-direction: column; }

    /* ── NAVBAR ── */
    .navbar {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
      color: #fff; padding: 0 12px; height: 56px;
      display: flex; align-items: center; justify-content: space-between;
      box-shadow: 0 4px 20px rgba(0,0,0,.15); position: relative; z-index: 100; flex-shrink: 0;
    }
    .nav-left, .nav-right { display: flex; align-items: center; gap: 10px; }
    .nav-center { display: flex; align-items: center; gap: 16px; }
    .nav-brand { font-size: 16px; font-weight: 800; letter-spacing: -.5px; display: flex; align-items: center; gap: 8px; }
    .nav-brand i { color: var(--accent); font-size: 18px; }
    .nav-divider { width: 1px; height: 26px; background: rgba(255,255,255,.15); }

    .question-type-badge {
      background: rgba(59,130,246,.2); border: 1px solid rgba(59,130,246,.3);
      color: #93c5fd; padding: 3px 12px; border-radius: 100px;
      font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; transition: all .3s;
    }

    /* Difficulty pill */
    .diff-badge {
      padding: 3px 10px; border-radius: 100px; font-size: 10px; font-weight: 800;
      text-transform: uppercase; letter-spacing: .5px; transition: all .4s;
    }
    .diff-easy     { background: rgba(16,185,129,.2); color: #6ee7b7; border: 1px solid rgba(16,185,129,.3); }
    .diff-moderate,
    .diff-medium   { background: rgba(245,158,11,.2); color: #fcd34d; border: 1px solid rgba(245,158,11,.3); }
    .diff-hard     { background: rgba(239,68,68,.2);  color: #fca5a5; border: 1px solid rgba(239,68,68,.3); }

    /* Streak badge */
    .streak-badge { font-size: 11px; font-weight: 700; color: rgba(255,255,255,.8); white-space: nowrap; }

    /* Progress ring */
    .progress-ring-container { position: relative; width: 38px; height: 38px; }
    .progress-ring { transform: rotate(-90deg); }
    .progress-ring-bg { fill: none; stroke: rgba(255,255,255,.1); stroke-width: 3; }
    .progress-ring-fill {
      fill: none; stroke: var(--accent); stroke-width: 3; stroke-linecap: round;
      transition: stroke-dashoffset .6s cubic-bezier(.4,0,.2,1);
      filter: drop-shadow(0 0 4px var(--accent-glow));
    }
    .progress-ring-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); font-size: 9px; font-weight: 800; color: #fff; }
    .progress-label { font-size: 12px; font-weight: 600; color: rgba(255,255,255,.9); }
    .progress-label span { color: rgba(255,255,255,.5); font-weight: 400; }

    /* Competency meter */
    .comp-meter-wrap { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; }
    .comp-meter-label { font-size: 9px; font-weight: 700; color: rgba(255,255,255,.6); text-transform: uppercase; letter-spacing: .5px; }
    .comp-meter-bar { width: 60px; height: 6px; background: rgba(255,255,255,.1); border-radius: 100px; overflow: hidden; }
    .comp-meter-fill { height: 100%; border-radius: 100px; background: var(--success); transition: width .5s ease, background .5s ease; }
    .comp-meter-pct { font-size: 11px; font-weight: 700; color: rgba(255,255,255,.9); }

    .user-badge {
      display: flex; align-items: center; gap: 6px;
      background: rgba(255,255,255,.08); padding: 5px 12px; border-radius: 100px;
      font-size: 11px; font-weight: 500; border: 1px solid rgba(255,255,255,.08);
    }
    .user-badge i { color: var(--accent); font-size: 12px; }

    .timer-box {
      display: flex; align-items: center; gap: 6px;
      background: rgba(255,255,255,.06); padding: 7px 14px;
      border-radius: 10px; border: 1px solid rgba(255,255,255,.08);
    }
    .timer-icon { color: var(--warning); font-size: 13px; animation: pulse-timer 2s infinite; }
    @keyframes pulse-timer { 0%,100%{opacity:1} 50%{opacity:.5} }
    .timer-text { font-family: 'Inter',monospace; font-size: 14px; font-weight: 700; }

    /* ── LAYOUT ── */
    .main-content { flex: 1; display: flex; overflow: hidden; }

    /* ── TOOLS SIDEBAR ── */
    .tools-nav-wrapper { width: 64px; background: #fff; border-right: 1px solid var(--border); flex-shrink: 0; }
    .tools-nav { display: flex; flex-direction: column; align-items: center; padding: 14px 0; gap: 10px; }
    .tool-btn {
      width: 42px; height: 42px; border-radius: 12px; display: flex; flex-direction: column;
      align-items: center; justify-content: center; cursor: pointer; transition: all .2s;
      color: var(--text-muted); border: 1px solid var(--border); background: #fff;
    }
    .tool-btn:hover { background: var(--surface-alt); color: var(--accent); }
    .tool-btn i { font-size: 16px; }
    .tool-btn span { font-size: 7px; font-weight: 800; text-transform: uppercase; margin-top: 3px; }

    /* ── IFRAME AREA ── */
    .iframe-container { flex: 1; display: flex; flex-direction: column; background: var(--surface-alt); overflow: hidden; }
    .iframe-wrapper { flex: 1; padding: clamp(8px,2vw,20px); display: flex; align-items: stretch; overflow: hidden; }
    iframe { width: 100%; height: 100%; border: none; border-radius: var(--radius); background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,.07); }

    .btn {
      padding: 9px 22px; border: none; border-radius: 10px;
      font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 600;
      cursor: pointer; transition: all .2s; display: flex; align-items: center; gap: 7px;
    }
    .btn:disabled { opacity: .4; cursor: not-allowed; transform: none !important; }
    .btn-secondary { background: var(--surface-alt); color: var(--text); border: 1px solid var(--border); }
    .btn-secondary:hover:not(:disabled) { background: #e2e8f0; transform: translateY(-1px); }
    .btn-primary   { background: linear-gradient(135deg,var(--accent),#2563eb); color: #fff; box-shadow: 0 2px 8px var(--accent-glow); }
    .btn-primary:hover:not(:disabled)   { transform: translateY(-1px); box-shadow: 0 4px 16px var(--accent-glow); }
    .btn-success   { background: linear-gradient(135deg,#10b981,#059669); color: #fff; box-shadow: 0 2px 8px rgba(16,185,129,.3); }
    .btn-success:hover:not(:disabled)   { transform: translateY(-1px); }
    .btn-danger    { background: linear-gradient(135deg,var(--danger),#dc2626); color: #fff; box-shadow: 0 2px 8px rgba(239,68,68,.3); }
    .btn-danger:hover:not(:disabled)    { transform: translateY(-1px); }

    /* Progress thin bar */
    .progress-bar-bottom { height: 3px; background: var(--border); flex-shrink: 0; }
    .progress-bar-fill { height: 100%; background: linear-gradient(90deg,var(--accent),#8b5cf6); border-radius: 0 3px 3px 0; transition: width .6s cubic-bezier(.4,0,.2,1); }

    /* Pressure per-question timer bar */
    .question-timer-bar { height: 4px; background: var(--border); flex-shrink: 0; position: relative; }
    .question-timer-fill {
      height: 100%; border-radius: 0 2px 2px 0;
      background: linear-gradient(90deg, #10b981, #3b82f6);
      transition: width .5s linear, background .5s ease;
    }
    .question-timer-fill.warn  { background: linear-gradient(90deg, #f59e0b, #ef4444); }
    .question-timer-fill.urgent{ background: #ef4444; animation: pulse-bar .5s infinite; }
    @keyframes pulse-bar { 0%,100%{opacity:1} 50%{opacity:.6} }

    /* Timer danger state */
    .timer-box.danger { background: rgba(239,68,68,.15); border-color: rgba(239,68,68,.3); animation: pulse-danger 1s infinite; }
    @keyframes pulse-danger { 0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,.3)} 50%{box-shadow:0 0 0 4px rgba(239,68,68,.15)} }
    .timer-box.danger .timer-icon { color: #ef4444; }
    .timer-box.danger .timer-text { color: #fca5a5; }

    /* Mode badge */
    .mode-badge {
      padding: 3px 10px; border-radius: 100px; font-size: 9px; font-weight: 800;
      text-transform: uppercase; letter-spacing: .5px;
    }
    .mode-badge.normal   { background: rgba(59,130,246,.2); color: #93c5fd; border: 1px solid rgba(59,130,246,.3); }
    .mode-badge.pressure { background: rgba(239,68,68,.2);  color: #fca5a5; border: 1px solid rgba(239,68,68,.3); }

    /* Mode selection cards */
    .mode-card {
      border: 2px solid #e2e8f0; border-radius: 16px; padding: 20px;
      cursor: pointer; transition: all .2s; background: #fff; position: relative;
    }
    .mode-card:hover { border-color: #93c5fd; background: #f0f7ff; transform: translateY(-2px); }
    .mode-card.selected-normal   { border-color: #3b82f6; background: #eff6ff; box-shadow: 0 0 0 4px rgba(59,130,246,.12); }
    .mode-card.selected-pressure { border-color: #ef4444; background: #fff5f5; box-shadow: 0 0 0 4px rgba(239,68,68,.12); }
    .mode-card-checkmark {
      position: absolute; top: 12px; right: 12px;
      width: 22px; height: 22px; border-radius: 50%;
      display: none; align-items: center; justify-content: center;
      font-size: 10px; color: #fff;
    }
    .mode-card.selected-normal   .mode-card-checkmark { display: flex; background: #3b82f6; }
    .mode-card.selected-pressure .mode-card-checkmark { display: flex; background: #ef4444; }
    .mode-card-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 12px; }
    .mode-card-title { font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 2px; }
    .mode-card-sub   { font-size: 11px; color: #64748b; font-weight: 600; margin-bottom: 10px; }
    .mode-card-features { list-style: none; padding: 0; margin: 0; }
    .mode-card-features li { font-size: 12px; color: #475569; padding: 3px 0 3px 14px; position: relative; }
    .mode-card-features li::before { content: '›'; position: absolute; left: 2px; color: #94a3b8; font-weight: 700; }

    /* ── MODALS ── */
    .modal-overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,.5);
      backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 1300;
    }
    .modal-overlay.active { display: flex; animation: fadeIn .2s ease; }
    @keyframes fadeIn { from{opacity:0} to{opacity:1} }
    .modal-box {
      background: #fff; border-radius: 20px; padding: 28px;
      max-width: 420px; width: 90%; text-align: center;
      box-shadow: 0 20px 60px rgba(0,0,0,.2);
      animation: slideUp .3s cubic-bezier(.34,1.56,.64,1);
    }
    @keyframes slideUp { from{transform:translateY(20px);opacity:0} to{transform:translateY(0);opacity:1} }

    /* ── RESPONSIVE HIDE ── */
    @media (max-width: 768px) {
      .question-nav { display: none; }
      .nav-center { display: none; }
      .comp-meter-wrap { display: none; }
      .user-badge { display: none; }
    }
    @media (max-width: 640px) {
      .nav-brand span { display: none; }
      .diff-badge { display: none; }
    }
    @media (max-width: 480px) {
      .btn span { display: none; }
    }
  </style>
</head>

<body>

<!-- ── NAVBAR ── -->
<div class="navbar">
  <div class="nav-left">
    <div class="nav-brand">
      <i class="fas fa-brain"></i>
      <span>Studium CAT Exam</span>
    </div>
    <div class="nav-divider"></div>
    <span class="question-type-badge" id="questionTag">—</span>
    <span class="diff-badge diff-moderate" id="diffBadge">MEDIUM</span>
    <span id="srcTableBadge" style="padding:3px 8px;border-radius:100px;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;background:rgba(255,255,255,.12);color:#94a3b8;border:1px solid rgba(255,255,255,.15);">—</span>
    <span id="debugBadge" style="display:none;padding:3px 10px;border-radius:6px;font-size:9px;font-weight:700;font-family:monospace;background:rgba(239,68,68,.2);color:#fca5a5;border:1px solid rgba(239,68,68,.3);">θ=0.000</span>
    <span id="debugAnswerBadge" onclick="showAnswerModal()" style="padding:3px 10px;border-radius:6px;font-size:9px;font-weight:700;font-family:monospace;background:rgba(16,185,129,.2);color:#6ee7b7;border:1px solid rgba(16,185,129,.3);cursor:pointer;user-select:none;" title="Click to see full answer">Ans: —</span>
  </div>

  <div class="nav-center">
    <div style="display:flex;align-items:center;gap:12px;">
      <div class="progress-ring-container">
        <svg class="progress-ring" width="38" height="38">
          <circle class="progress-ring-bg" cx="19" cy="19" r="15"/>
          <circle class="progress-ring-fill" id="progressRing" cx="19" cy="19" r="15"
                  stroke-dasharray="94.25" stroke-dashoffset="94.25"/>
        </svg>
        <span class="progress-ring-text" id="progressPercent">0%</span>
      </div>
      <div class="progress-label" id="questionProgress">
        Q <strong>—</strong> <span>/ <?php echo $TARGET_TOTAL; ?></span>
      </div>
    </div>
    <span class="streak-badge" id="streakBadge"></span>
  </div>

  <div class="nav-right">
    <div class="comp-meter-wrap">
      <span class="comp-meter-label">Competency</span>
      <div class="comp-meter-bar">
        <div class="comp-meter-fill" id="compFill" style="width:0%"></div>
      </div>
      <span class="comp-meter-pct" id="compPct">0%</span>
    </div>
    <div class="user-badge">
      <i class="fas fa-user"></i>
      <?php echo htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <span class="mode-badge normal" id="modeBadge" style="display:none;">Normal</span>
    <div class="timer-box" id="timerBox">
      <i class="fas fa-clock timer-icon" id="timerIcon"></i>
      <span class="timer-text" id="timer">—</span>
    </div>
  </div>
</div>
<div class="progress-bar-bottom">
  <div class="progress-bar-fill" id="progressBarFill" style="width:0%"></div>
</div>

<!-- ── PRE-EXAM MODAL ── -->
<div class="modal-overlay active" id="startModal" style="background:rgba(7,14,28,.97);z-index:9999;">
  <div style="width:100%;max-width:620px;padding:clamp(24px,5vw,40px);
              background:#fff;border-radius:min(28px,5vw);box-shadow:0 30px 60px rgba(0,0,0,.5);
              max-height:95vh;overflow-y:auto;">

    <!-- ── STEP 1: MODE SELECTION ── -->
    <div id="modeStep">
      <div style="text-align:center;margin-bottom:28px;">
        <div style="width:56px;height:56px;background:linear-gradient(135deg,#1e3a5f,#3b82f6);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto 14px;">🧠</div>
        <div style="font-size:22px;font-weight:900;color:#0f172a;">Choose Your Exam Mode</div>
        <div style="font-size:13px;color:#64748b;margin-top:4px;">Select how you want to take this adaptive CAT examination</div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:24px;">
        <div class="mode-card" id="normalCard" onclick="selectExamMode('normal')">
          <div class="mode-card-checkmark"><i class="fas fa-check"></i></div>
          <div class="mode-card-icon" style="background:#dbeafe;color:#2563eb;">⏱️</div>
          <div class="mode-card-title">Normal Mode</div>
          <div class="mode-card-sub">2-hour global countdown</div>
          <ul class="mode-card-features">
            <li>120 minutes total time</li>
            <li>Answer at your own pace</li>
            <li>Timer runs continuously</li>
            <li>Best for exam readiness</li>
          </ul>
        </div>
        <div class="mode-card" id="pressureCard" onclick="selectExamMode('pressure')">
          <div class="mode-card-checkmark"><i class="fas fa-check"></i></div>
          <div class="mode-card-icon" style="background:#fee2e2;color:#dc2626;">⚡</div>
          <div class="mode-card-title">Pressure Mode</div>
          <div class="mode-card-sub">Per-question countdown</div>
          <ul class="mode-card-features">
            <li>MC: 1 min per question</li>
            <li>Complex types: 2–3 min</li>
            <li>Timeout = auto-skip (omitted)</li>
            <li>High-stakes simulation</li>
          </ul>
        </div>
      </div>

      <button id="modeNextBtn" disabled onclick="showRulesStep()"
        style="width:100%;padding:14px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:#fff;
               border:none;border-radius:12px;font-size:15px;font-weight:800;cursor:pointer;
               opacity:.4;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px;">
        <i class="fas fa-arrow-right"></i> Continue to Rules
      </button>
      <p style="text-align:center;font-size:10px;color:#94a3b8;margin-top:10px;">Select a mode to continue</p>
    </div>

    <!-- ── STEP 2: RULES + BEGIN ── -->
    <div id="rulesStep" style="display:none;">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
        <button onclick="showModeStep()" style="width:32px;height:32px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;cursor:pointer;color:#64748b;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <i class="fas fa-chevron-left"></i>
        </button>
        <div style="flex:1;">
          <div style="font-size:17px;font-weight:900;color:#0f172a;" id="selectedModeTitle">—</div>
          <div style="font-size:11px;color:#64748b;" id="selectedModeDesc">—</div>
        </div>
        <span id="selectedModeBadge"></span>
      </div>

      <!-- Dynamic exam params -->
      <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:16px;margin-bottom:16px;">
        <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;margin-bottom:10px;">Exam Parameters</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;" id="examParams"></div>
      </div>

      <!-- Security warning -->
      <div style="background:#fef2f2;border:1px solid #fee2e2;border-radius:12px;padding:14px;margin-bottom:16px;display:flex;gap:12px;align-items:flex-start;">
        <div style="width:28px;height:28px;background:#ef4444;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;flex-shrink:0;margin-top:1px;">!</div>
        <div style="font-size:12px;color:#991b1b;line-height:1.6;">
          <strong>ZERO TOLERANCE:</strong> Leaving this tab will result in <span style="font-weight:700;text-decoration:underline;">Instant Exam Termination</span>. No exceptions.
        </div>
      </div>

      <!-- Agreements -->
      <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:20px;">
        <label style="display:flex;align-items:center;gap:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px 14px;cursor:pointer;">
          <input type="checkbox" id="agreeRules" style="width:18px;height:18px;accent-color:#3b82f6;cursor:pointer;">
          <span style="font-size:13px;font-weight:600;color:#334155;">I agree to the NCLEX Security Guidelines.</span>
        </label>
        <label style="display:flex;align-items:center;gap:12px;background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:12px 14px;cursor:pointer;">
          <input type="checkbox" id="agreeFocus" style="width:18px;height:18px;accent-color:#ef4444;cursor:pointer;">
          <span style="font-size:13px;font-weight:600;color:#991b1b;">I understand leaving this tab will immediately fail my exam.</span>
        </label>
      </div>

      <button id="startExamBtn" disabled
        style="width:100%;padding:16px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:#fff;
               border:none;border-radius:14px;font-size:16px;font-weight:900;cursor:pointer;
               display:flex;align-items:center;justify-content:center;gap:10px;
               transition:all .2s;opacity:.4;letter-spacing:.3px;">
        <i class="fas fa-brain" style="font-size:18px;"></i> Begin Adaptive Examination
      </button>
      <p style="text-align:center;font-size:9px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.8px;margin-top:12px;">IRT Adaptive CAT — Active</p>
    </div>
  </div>
</div>

<!-- ── MAIN CONTENT ── -->
<div class="main-content">

  <!-- Tools Sidebar -->
  <div class="tools-nav-wrapper h-full flex flex-col">
    <div class="tools-nav" id="toolsNav">
      <div class="tool-btn" id="calcBtn" title="Calculator"><i class="fas fa-calculator"></i><span>Calc</span></div>
      <div class="tool-btn" id="noteBtn" title="Notes"><i class="fas fa-sticky-note"></i><span>Notes</span></div>
      <div class="tool-btn" id="fullBtn" title="Fullscreen"><i class="fas fa-expand"></i><span>Size</span></div>
      <div class="tool-btn" id="feedBtn" title="Feedback"><i class="fas fa-comment-dots"></i><span>Feed</span></div>
    </div>
  </div>

  <!-- Iframe Area -->
  <div class="iframe-container">
    <div class="question-timer-bar" id="questionTimerBar" style="display:none;">
      <div class="question-timer-fill" id="questionTimerFill" style="width:100%;"></div>
    </div>
    <div class="iframe-wrapper">
      <iframe id="questionFrame" src=""></iframe>
    </div>
  </div>
</div>

<!-- ── TOOL MODALS ── -->
<div class="modal-overlay" id="calcModal" style="background:transparent;pointer-events:none;z-index:1300;">
  <div class="modal-box" style="pointer-events:auto;width:280px;padding:20px;border:1px solid var(--border);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
      <span style="font-weight:800;font-size:11px;text-transform:uppercase;color:var(--text-muted);">Calculator</span>
      <i class="fas fa-times" style="cursor:pointer;" onclick="document.getElementById('calcModal').style.display='none'"></i>
    </div>
    <input type="text" id="calcDisplay" readonly
      style="width:100%;border:1px solid var(--border);padding:10px;text-align:right;font-family:monospace;
             font-size:20px;border-radius:8px;margin-bottom:10px;background:#f8fafc;">
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;">
      <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('C')">C</button>
      <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('/')">/</button>
      <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('*')">×</button>
      <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('-')">−</button>
      <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('7')">7</button>
      <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('8')">8</button>
      <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('9')">9</button>
      <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('+')">+</button>
      <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('4')">4</button>
      <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('5')">5</button>
      <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('6')">6</button>
      <button class="btn btn-primary" style="padding:10px;grid-row:span 2;" onclick="calcInput('=')">=</button>
      <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('1')">1</button>
      <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('2')">2</button>
      <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('3')">3</button>
      <button class="btn btn-secondary" style="padding:10px;grid-column:span 2;" onclick="calcInput('0')">0</button>
      <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('.')">.</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="noteModal" style="background:transparent;pointer-events:none;z-index:1300;">
  <div class="modal-box" style="pointer-events:auto;width:370px;padding:20px;border:1px solid var(--border);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
      <span style="font-weight:800;font-size:12px;text-transform:uppercase;color:var(--text-muted);">
        <i class="fas fa-sticky-note me-1"></i> Quick Note
      </span>
      <i class="fas fa-times" style="cursor:pointer;" onclick="document.getElementById('noteModal').style.display='none'"></i>
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

<div class="modal-overlay" id="feedModal" style="background:transparent;pointer-events:none;z-index:1300;">
  <div class="modal-box" style="pointer-events:auto;width:300px;padding:22px;border:1px solid var(--border);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
      <span style="font-weight:800;font-size:11px;text-transform:uppercase;color:var(--text-muted);">Flash Feedback</span>
      <i class="fas fa-times" style="cursor:pointer;" onclick="document.getElementById('feedModal').style.display='none'"></i>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px;">
      <button class="btn btn-secondary" style="justify-content:flex-start;font-size:12px;" onclick="logFeedback('Technical Issue')"><i class="fas fa-bug"></i> Bug / Technical Issue</button>
      <button class="btn btn-secondary" style="justify-content:flex-start;font-size:12px;" onclick="logFeedback('Calculation Help')"><i class="fas fa-calculator"></i> Calculation Help</button>
      <button class="btn btn-secondary" style="justify-content:flex-start;font-size:12px;" onclick="logFeedback('Vague Question')"><i class="fas fa-question"></i> Vague Question</button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<script>
// ── SECURITY ────────────────────────────────────────────────────────────────
document.addEventListener('contextmenu', e => {
  e.preventDefault();
  Swal.fire({ icon:'warning', title:'Security', text:'Right-click is disabled during the examination.', timer:2000, showConfirmButton:false });
});
document.addEventListener('visibilitychange', () => {
  if (!document.hidden || isExiting) return;
  if (document.getElementById('startModal').classList.contains('active')) return;
  if (currentQuestion === 0 && Object.keys(userAnswers).length === 0) return;
  window.location.href = 'security_violation.php?reason=tab_switch';
});
window.addEventListener('blur', () => {
  setTimeout(() => {
    if (isExiting) return;
    if (document.getElementById('startModal').classList.contains('active')) return;
    if (currentQuestion === 0 && Object.keys(userAnswers).length === 0) return;
    if (document.activeElement && document.activeElement.tagName === 'IFRAME') return;
    window.location.href = 'security_violation.php?reason=window_focus_lost';
  }, 200);
});
document.onkeydown = e => {
  if (e.keyCode === 123) return false;                                                // F12
  if (e.keyCode === 44)  return false;                                                // PrintScreen
  if (e.ctrlKey && e.shiftKey && ['I','C','J','K'].includes(String.fromCharCode(e.keyCode))) return false;
  if (e.ctrlKey && ['U','S','P','A'].includes(String.fromCharCode(e.keyCode))) return false;
};
(function () {
  window.history.pushState(null, '', window.location.href);
  window.onpopstate = function () {
    // Trap the user on the same page — back button is disabled during exam
    window.history.pushState(null, '', window.location.href);
    Swal.fire({
      title: 'Back Navigation Blocked',
      html:  '<div style="font-size:13px;color:#64748b;">Backward navigation is disabled during the examination.<br>Return to your active exam.</div>',
      icon:  'warning',
      confirmButtonColor: '#3b82f6',
      confirmButtonText:  'Continue Exam',
      allowOutsideClick:  false,
      allowEscapeKey:     false,
    });
  };
})();

// ── TOOL HELPERS ────────────────────────────────────────────────────────────
function calcInput(v) {
  const d = document.getElementById('calcDisplay');
  if (v === 'C') { d.value = ''; return; }
  if (v === '=') {
    try {
      const expr = d.value;
      if (!/^[0-9+\-*/.() ]+$/.test(expr)) { d.value = 'Error'; return; }
      d.value = Function('"use strict"; return (' + expr + ')')();
    } catch(e) { d.value = 'Error'; }
    return;
  }
  d.value += v;
}
function logFeedback(t) {
  Swal.fire({ icon:'success', title:'Logged', text:`Feedback on "${t}" recorded.` });
  document.getElementById('feedModal').style.display = 'none';
}
function toggleTool(id) {
  const el = document.getElementById(id);
  el.style.display = (el.style.display === 'flex') ? 'none' : 'flex';
}
function toggleFullScreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen();
    document.getElementById('fullBtn').innerHTML = '<i class="fas fa-compress"></i><span>Size</span>';
  } else {
    document.exitFullscreen && document.exitFullscreen();
    document.getElementById('fullBtn').innerHTML = '<i class="fas fa-expand"></i><span>Size</span>';
  }
}
document.getElementById('calcBtn').addEventListener('click', () => toggleTool('calcModal'));
document.getElementById('noteBtn').addEventListener('click', () => toggleTool('noteModal'));
document.getElementById('feedBtn').addEventListener('click', () => toggleTool('feedModal'));
document.getElementById('fullBtn').addEventListener('click', toggleFullScreen);
// Dev-mode debug badge
const isDevMode = ['localhost','127.0.0.1'].includes(location.hostname);
if (isDevMode) document.getElementById('debugBadge').style.display = 'inline';
</script>
<script>
// ═══════════════════════════════════════════════════════════════════════════
// IRT ENGINE — Rasch 1PL with Newton-Raphson MAP Estimation
// ═══════════════════════════════════════════════════════════════════════════
class IRTEngine {
  constructor() {
    this.theta      = 0.0;
    this.sem        = 1.0;
    this.history    = [];       // [{b, x}, ...]
    this.passingLogit = 0.0;
    this.THETA_MIN  = -4.0;
    this.THETA_MAX  =  4.0;
  }

  // Rasch ICC: P(correct | θ, b)
  probability(theta, b) {
    const e = Math.exp(theta - b);
    return e / (1 + e);
  }

  // Fisher Information at θ for item b
  information(theta, b) {
    const p = this.probability(theta, b);
    return p * (1 - p);
  }

  // Newton-Raphson MAP theta update (all history)
  updateTheta(score, b) {
    this.history.push({ b, x: score });
    let theta = this.theta;
    for (let i = 0; i < 15; i++) {
      let num = -theta;   // MAP prior: N(0,1) → d/dθ = -θ
      let den =  1;       // MAP prior: second derivative = 1
      for (const item of this.history) {
        const p = this.probability(theta, item.b);
        num += (item.x - p);
        den += p * (1 - p);
      }
      if (den === 0) break;
      const delta = num / den;
      theta = Math.max(this.THETA_MIN, Math.min(this.THETA_MAX, theta + delta));
      if (Math.abs(delta) < 0.001) break;
    }
    this.theta = theta;
    this.sem   = this.calculateSEM();
    return this.theta;
  }

  // SEM = 1 / √(ΣI_j + prior)
  calculateSEM() {
    let info = 1; // prior contribution
    for (const item of this.history) info += this.information(this.theta, item.b);
    return 1.0 / Math.sqrt(info);
  }

  // Select from top-K items by Fisher Information (K=5 random tie-break prevents same Q1 every exam)
  selectNextItem(pool) {
    if (!pool.length) return null;
    const K = Math.min(5, pool.length);
    const ranked = pool
      .map(item => ({ item, fi: this.information(this.theta, item.b) }))
      .sort((a, b) => b.fi - a.fi)
      .slice(0, K);
    return ranked[Math.floor(Math.random() * ranked.length)].item;
  }

  // NCLEX-style 95% CI stopping rule
  checkIRTStopping(minItems) {
    if (this.history.length < minItems) return null;
    const ci  = IRT_CONFIDENCE * this.sem;
    const low  = this.theta - ci;
    const high = this.theta + ci;
    if (low  > PASSING_LOGIT) return 'irt_pass';
    if (high < PASSING_LOGIT) return 'irt_fail';
    return null;
  }

  serialize() { return { theta: this.theta, sem: this.sem, history: this.history }; }
  restore(s)  { this.theta = s.theta; this.sem = s.sem; this.history = s.history; }
}

// Selected concepts filter (populated by concept selection UI — empty = all concepts)
let selectedConceptFilter = [];

// ── CONSTANTS ────────────────────────────────────────────────────────────────
const DIFF_MAP = { easy: -1.5, moderate: 0.0, medium: 0.0, hard: 1.5 };
const DIFF_WEIGHTS = {
  easy:     { correct: 1.0 }, moderate: { correct: 1.2 },
  medium:   { correct: 1.2 }, hard:     { correct: 1.5 },
};
const IS_DEV_MODE    = true;                        // flip to false in production
const MIN_ITEMS      = IS_DEV_MODE ? 10  : 85;      // minimum before CI check (NCSBN: 85)
const TARGET_TOTAL   = <?= $targetTotalJs ?>;

async function saveExamNote() {
  const title   = (document.getElementById('examNoteTitle')?.value || '').trim();
  const content = (document.getElementById('examNote')?.value || '').trim();
  if (title && content) {
    const fd = new FormData();
    fd.append('note_title',   title);
    fd.append('note_content', content);
    await fetch('../mynotes.php', { method: 'POST', body: fd }).catch(() => {});
  }
}
const MAX_ITEMS      = TARGET_TOTAL;                 // max items (NCSBN: 150 in prod)
const PASSING_LOGIT  = 0.0;                          // NCSBN logit passing standard (real NCLEX-RN 2023 = -0.37)
const IRT_CONFIDENCE = 1.96;                         // 95% CI z-score
const NORMAL_TIME_LIMIT      = 7200; // 2 hours in seconds
const PRESSURE_TIMERS = {
  mc: 60, sata: 120, mmr: 180, mpr: 180,
  bowtie: 180, dragndrop: 180, dropdown: 120, highlight: 120,
};

// ── PHP → JS BOOTSTRAP ───────────────────────────────────────────────────────
const examPool      = <?= $examPoolJs ?>;
const typeQuota     = <?= $typeQuotaMapJs ?>;   // max items per type (enforced in selection)
let   questionIds   = [];
const examTaken     = <?= $examTakenJs ?>;
const authUserId    = <?= $userIdJs ?>;
const answerCacheKey= `exam_ans_${authUserId}_${examTaken}`;

// ── IRT INIT ─────────────────────────────────────────────────────────────────
const irt = new IRTEngine();

// ── REMAINING POOL ───────────────────────────────────────────────────────────
let remainingPool = [...examPool];

// ── ANSWERS / STATE ──────────────────────────────────────────────────────────
localStorage.removeItem(answerCacheKey);
let userAnswers = {};

function saveAnswersToCache() {
  localStorage.setItem(answerCacheKey, JSON.stringify(userAnswers));
}

let examMode             = 'normal'; // set by modal
let pressureTimerStart   = 0;
let pressureTimerLimit   = 60;
let pressureTimerInterval= null;

let isExiting        = false;
let pendingSave      = Promise.resolve();
let questionStartTime= Date.now();
let totalSeconds     = 0;
let currentQuestion  = 0;
let correctStreak    = 0;
let wrongStreak      = 0;
let lastAnswerFull   = '';

// ── TIMER ─────────────────────────────────────────────────────────────────────
let startTime = Date.now();
function fmtTime(sec) {
  const h = Math.floor(sec / 3600);
  const m = Math.floor((sec % 3600) / 60);
  const s = sec % 60;
  return h > 0
    ? `${h}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`
    : `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
}

function updateTimer() {
  const elapsed = Math.floor((Date.now() - startTime) / 1000);
  totalSeconds  = elapsed;

  if (examMode === 'normal') {
    const remaining = Math.max(0, NORMAL_TIME_LIMIT - elapsed);
    const timerEl   = document.getElementById('timer');
    const timerBox  = document.getElementById('timerBox');
    timerEl.textContent = fmtTime(remaining);
    if (remaining <= 600 && remaining > 0) {       // < 10 min
      timerBox.classList.add('danger');
      document.getElementById('timerIcon').className = 'fas fa-hourglass-half timer-icon';
    }
    if (remaining === 0 && !isExiting) {
      if (globalTimerInterval) clearInterval(globalTimerInterval);
      handleTimeExpired();
    }
  }
  // Pressure mode timer is handled by startPressureTimer interval separately
}
let globalTimerInterval = null; // started when exam begins, not on page load

// ── COMPETENCY HELPERS ────────────────────────────────────────────────────────
function getWeightCorrect(dlevel) {
  return (DIFF_WEIGHTS[(dlevel||'moderate').toLowerCase()] ?? { correct: 1.2 }).correct;
}
function getTotalWeightedEarned() {
  let t = 0;
  for (const ans of Object.values(userAnswers)) t += ans.score * getWeightCorrect(ans.dlevel);
  return t;
}
function getTotalWeightedPossible() {
  let t = 0;
  for (const ans of Object.values(userAnswers)) t += 1.0 * getWeightCorrect(ans.dlevel);
  return t;
}
function calculateCompetencyPercent() {
  const wp = getTotalWeightedPossible();
  return wp > 0 ? (getTotalWeightedEarned() / wp) * 100 : 0;
}

// ── NORMAL MODE: TIME EXPIRED ────────────────────────────────────────────────
async function handleTimeExpired() {
  if (isExiting) return;
  isExiting = true;
  clearInterval(pressureTimerInterval);

  const answered = Object.keys(userAnswers).length;
  const termReason  = answered < MIN_ITEMS ? 'time_expired_insufficient' : 'time_expired';
  // NCSBN ROT rule: < MIN_ITEMS = auto-FAIL; else final theta decision
  const termResult  = (answered < MIN_ITEMS) ? 'FAILED' : (irt.theta > PASSING_LOGIT ? 'PASSED' : 'FAILED');

  try {
    await pendingSave;
    const pct = calculateCompetencyPercent();
    const res = await fetch('submit_exam.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        examTaken,
        termination_reason: termReason,
        final_result:  termResult,
        final_percent: pct,
        final_theta:   irt.theta,
        final_sem:     irt.sem,
        total_items:   answered,
        exam_duration: totalSeconds,
        is_auto_terminate: true,
      })
    });
    const data = await res.json();
    if (data.ok) localStorage.removeItem(answerCacheKey);
  } catch(err) { console.error('time_expired submit:', err); }

  await Swal.fire({
    icon: 'warning', title: '⏰ Time Expired',
    html: `<div style="font-size:13px;color:#64748b;">Your 2-hour exam time has elapsed. Exam auto-submitted.</div>`,
    confirmButtonText: 'View Results',
    confirmButtonColor: '#64748b',
    allowOutsideClick: false, allowEscapeKey: false,
  });
  await saveExamNote();
  window.location.href = `result.php?examTaken=${examTaken}&finish=1`;
}

// ── PRESSURE MODE: PER-QUESTION TIMER ────────────────────────────────────────
function startPressureTimer(type) {
  clearInterval(pressureTimerInterval);
  pressureTimerLimit = PRESSURE_TIMERS[type] ?? 60;
  pressureTimerStart = Date.now();

  const bar      = document.getElementById('questionTimerBar');
  const fill     = document.getElementById('questionTimerFill');
  if (bar)  bar.style.display = 'block';
  if (fill) { fill.style.width = '100%'; fill.className = 'question-timer-fill'; }

  pressureTimerInterval = setInterval(() => {
    const elapsed   = Math.floor((Date.now() - pressureTimerStart) / 1000);
    const remaining = Math.max(0, pressureTimerLimit - elapsed);
    const pct       = (remaining / pressureTimerLimit) * 100;

    // Update navbar timer
    document.getElementById('timer').textContent = fmtTime(remaining);

    // Update progress bar
    if (fill) {
      fill.style.width = pct + '%';
      fill.classList.remove('warn','urgent');
      if (remaining <= 10)                        fill.classList.add('urgent');
      else if (remaining <= pressureTimerLimit * 0.3) fill.classList.add('warn');
    }

    // Danger state on timer box
    const box = document.getElementById('timerBox');
    if (remaining <= 10) box.classList.add('danger'); else box.classList.remove('danger');

    if (remaining === 0) {
      clearInterval(pressureTimerInterval);
      handleQuestionTimeout();
    }
  }, 500);
}

async function handleQuestionTimeout() {
  if (isExiting) return;

  // Lock the iframe visually
  document.getElementById('questionFrame')?.contentWindow?.postMessage({ type: 'timeout' }, '*');

  const q = questionIds[currentQuestion];
  if (q) {
    const uid = `${q.type}-${q.id}`;
    if (!userAnswers[uid]) {
      userAnswers[uid] = {
        question_uid: uid, question_type: q.type, question_id: q.id,
        answer: null, correct_answer: null, initial_answer: null, changes: null,
        isCorrect: 0, score: 0, max_points: 1, earned_points: 0,
        rationale: null, topic: null, system: null, cnc: null, dlevel: q.dlevel ?? 'moderate',
        time_taken: pressureTimerLimit, totalTime: totalSeconds,
        examTaken, question_number: currentQuestion + 1,
        timestamp: new Date().toISOString(),
        theta_before: irt.theta, item_difficulty: q.b ?? 0.0,
        theta_after: irt.theta, sem_after: irt.sem,
        omitted: 1,
      };
      saveAnswersToCache();
      updateProgress();
      updateCompetencyMeter();

      pendingSave = fetch('save_history.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(Object.assign({}, userAnswers[uid], { exam_mode: 'exam', weighted_score: 0 }))
      }).catch(err => console.error('save omitted:', err));
    }
  }

  // Check stopping rules before advancing
  const term = checkExamTermination();
  if (term) {
    setTimeout(() => handleAutoTermination(term), 400);
    return;
  }
  setTimeout(advanceQuestion, 600);
}

// ── STOPPING RULES (NCSBN NCLEX CAT) ─────────────────────────────────────────
function checkExamTermination() {
  const answered = Object.keys(userAnswers).length;

  // Gate: minimum items not yet met
  if (answered < MIN_ITEMS) return null;

  const pct = calculateCompetencyPercent();

  // Rule 1 (PRIMARY) — IRT 95% Confidence Interval
  const ciHalf    = IRT_CONFIDENCE * irt.sem;
  const thetaLow  = irt.theta - ciHalf;
  const thetaHigh = irt.theta + ciHalf;

  if (thetaLow > PASSING_LOGIT) {
    return { result: 'PASSED', reason: 'irt_pass', percent: pct };
  }
  if (thetaHigh < PASSING_LOGIT) {
    return { result: 'FAILED', reason: 'irt_fail', percent: pct };
  }

  // Rule 2 — Mercy rule: if even perfect performance on remaining items can't recover
  if (answered >= 20) {
    const remaining = MAX_ITEMS - answered;
    const maxThetaGain = remaining * 0.15; // conservative per-item gain estimate
    if (irt.theta + maxThetaGain < PASSING_LOGIT - 0.5) {
      return { result: 'FAILED', reason: 'mercy_rule', percent: pct };
    }
  }

  // Rule 3 (SECONDARY) — Maximum items exhausted, use ROTC rule
  if (answered >= MAX_ITEMS) {
    const recentCount   = Math.ceil(answered * 0.6);
    const recentItems   = irt.history.slice(-recentCount);
    const recentCorrect = recentItems.filter(h => h.x >= 0.5).length;
    const recentRatio   = recentCorrect / Math.max(recentCount, 1);
    // If CI still straddles the passing standard, use recent-item ratio as tiebreaker
    const result = (irt.theta > PASSING_LOGIT || recentRatio >= 0.5) ? 'PASSED' : 'FAILED';
    return { result, reason: 'completed_max', percent: pct };
  }

  return null; // CI unresolved — continue
}

// ── AUTO-TERMINATION ──────────────────────────────────────────────────────────
const TERM_REASONS = {
  irt_pass:                  'IRT 95% CI confirms ability above the passing standard.',
  irt_fail:                  'IRT 95% CI confirms ability below the passing standard.',
  mercy_rule:                'Performance trajectory indicates inability to reach passing standard.',
  completed_max:             `All ${MAX_ITEMS} questions answered.`,
  time_expired:              'Examination time limit reached — exam auto-submitted.',
  time_expired_insufficient: 'Time limit reached before minimum items — exam auto-failed.',
  pool_exhausted:            'Question pool exhausted.',
  manual_finish:             'Exam manually submitted.',
};
async function handleAutoTermination(term) {
  isExiting = true;
  try {
    await pendingSave;
    const res = await fetch('submit_exam.php', {
      method: 'POST', credentials:'same-origin', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        examTaken,
        termination_reason: term.reason,
        final_result:   term.result,
        final_percent:  term.percent,
        final_theta:    irt.theta,
        final_sem:      irt.sem,
        total_items:    Object.keys(userAnswers).length,
        exam_duration:  totalSeconds,
        is_auto_terminate: true,
        selected_concepts: typeof selectedConceptFilter !== 'undefined' ? selectedConceptFilter : [],
      })
    });
    const data = await res.json();
    if (!data.ok) throw new Error(data.error || 'submit failed');
    localStorage.removeItem(answerCacheKey);
  } catch(err) {
    console.error('Auto-terminate submit error:', err);
  }

  const isPassed = term.result === 'PASSED';
  await Swal.fire({
    icon:  isPassed ? 'success' : 'error',
    title: isPassed ? '🎉 PASSED!' : '❌ FAILED',
    html:  `
      <div style="font-size:28px;font-weight:900;color:${isPassed?'#10b981':'#ef4444'};margin-bottom:6px;">
        ${Math.round(term.percent)}%
      </div>
      <div style="font-size:13px;color:#64748b;margin-bottom:10px;">Competency Score</div>
      <div style="font-size:12px;color:#475569;background:#f8fafc;border-radius:10px;padding:10px;">
        ${TERM_REASONS[term.reason] || 'Exam complete.'}
      </div>
      <div style="margin-top:12px;font-size:11px;color:#94a3b8;">
        Items answered: ${Object.keys(userAnswers).length} &nbsp;|&nbsp;
        θ = ${irt.theta.toFixed(3)} &nbsp;|&nbsp; SEM = ${irt.sem.toFixed(3)}
      </div>`,
    confirmButtonText:  'View Results',
    confirmButtonColor: isPassed ? '#10b981' : '#ef4444',
    allowOutsideClick: false, allowEscapeKey: false,
  });
  await saveExamNote();
  window.location.href = `result.php?examTaken=${examTaken}&finish=1`;
}

// ── ADAPTIVE SELECTION (content-balanced) ────────────────────────────────────
function getNextAdaptiveQuestion() {
  if (remainingPool.length === 0) return null;

  const answeredCount  = Object.keys(userAnswers).length;
  const remainingSlots = MAX_ITEMS - answeredCount;

  // Tally how many times each type has been answered
  const typeSeen = {};
  for (const uid in userAnswers) {
    const t = userAnswers[uid].question_type;
    typeSeen[t] = (typeSeen[t] || 0) + 1;
  }

  // Enforce quota: exclude types that have already hit their max count
  const quotaPool = remainingPool.filter(i => (typeSeen[i.type] || 0) < (typeQuota[i.type] ?? MAX_ITEMS));
  // If quota enforcement empties the pool (shouldn't happen), fall back to full pool
  const activePool = quotaPool.length > 0 ? quotaPool : remainingPool;

  // Types still available after quota enforcement
  const poolTypeSet = new Set(activePool.map(i => i.type));

  // Types that have never appeared (and still have quota-eligible pool items)
  const allUnseen = [...poolTypeSet].filter(t => !typeSeen[t]);

  // Interleave types evenly: introduce a new type every (MAX_ITEMS / totalTypes) questions.
  const uniqueTypes   = new Set([...poolTypeSet, ...Object.keys(typeSeen)]).size;
  const introInterval = Math.max(2, Math.floor(MAX_ITEMS / uniqueTypes)); // e.g. 30/9 ≈ 3
  const seenTypeCount  = Object.keys(typeSeen).length;
  const expectedSeen   = Math.min(uniqueTypes, Math.floor(answeredCount / introInterval) + 1);
  const overdue = allUnseen.filter(() => seenTypeCount < expectedSeen);

  let selectionPool = activePool;

  if (allUnseen.length > 0 && remainingSlots <= allUnseen.length + 2) {
    // Crunch time — must fill remaining slots with unseen types
    const filtered = activePool.filter(i => allUnseen.includes(i.type));
    if (filtered.length > 0) selectionPool = filtered;
  } else if (overdue.length > 0) {
    // Scheduled introduction — interleave unseen types throughout the exam
    const filtered = activePool.filter(i => overdue.includes(i.type));
    if (filtered.length > 0) selectionPool = filtered;
  }

  const selected = irt.selectNextItem(selectionPool);
  if (!selected) return null;
  remainingPool = remainingPool.filter(
    i => !(i.id === selected.id && i.type === selected.type)
  );
  questionIds.push(selected);
  return selected;
}

// ── UI UPDATES ────────────────────────────────────────────────────────────────
const typeLabels = {
  mc:       { label:'Multiple Choice', icon:'fa-circle-dot',      color:'#64748b', path:'mc'       },
  sata:     { label:'SATA',            icon:'fa-check-double',     color:'#14b8a6', path:'sata'     },
  mmr:      { label:'MMR',             icon:'fa-table-cells',      color:'#06b6d4', path:'mmr'      },
  mpr:      { label:'Multi-Response',  icon:'fa-list-check',       color:'#10b981', path:'mpr'      },
  bowtie:   { label:'Bow-Tie',         icon:'fa-diagram-project',  color:'#8b5cf6', path:'bowtie'   },
  dragndrop:{ label:'Drag & Drop',     icon:'fa-hand',             color:'#ec4899', path:'dragndrop'},
  dropdown: { label:'Drop-Down',       icon:'fa-caret-down',       color:'#6366f1', path:'dropdown' },
  highlight:{ label:'Highlight',       icon:'fa-highlighter',      color:'#f59e0b', path:'highlight'},
};

function updateDifficultyBadge() {
  const badge = document.getElementById('diffBadge');
  const srcBadge = document.getElementById('srcTableBadge');
  if (!badge || !questionIds[currentQuestion]) return;
  const q  = questionIds[currentQuestion];
  const dl = (q.dlevel || 'moderate').toLowerCase();
  badge.className = `diff-badge diff-${dl}`;
  badge.textContent = dl.charAt(0).toUpperCase() + dl.slice(1);
  if (srcBadge) srcBadge.textContent = q.table || '—';
}

function updateCompetencyMeter() {
  const pct = calculateCompetencyPercent();
  const fill  = document.getElementById('compFill');
  const pctEl = document.getElementById('compPct');
  if (fill)  { fill.style.width = Math.min(100, pct) + '%'; fill.style.background = pct >= 85 ? '#10b981' : pct >= 60 ? '#f59e0b' : '#ef4444'; }
  if (pctEl) pctEl.textContent = Math.round(pct) + '%';
}

function updateStreakBadge() {
  const el = document.getElementById('streakBadge');
  if (!el) return;
  if (correctStreak >= 2) el.textContent = `🔥 ${correctStreak} Streak`;
  else if (wrongStreak >= 2) el.textContent = `❌ ${wrongStreak} Miss`;
  else el.textContent = '';
}

function updateProgress() {
  const answered = Object.keys(userAnswers).length;
  const pct      = Math.round((answered / Math.max(MAX_ITEMS, 1)) * 100);
  const ring     = document.getElementById('progressRing');
  const circ     = 2 * Math.PI * 15;
  ring.style.strokeDashoffset = circ - (pct / 100) * circ;
  document.getElementById('progressPercent').textContent = pct + '%';
  document.getElementById('progressBarFill').style.width = pct + '%';
}

function updateDebugBadge() {
  const el = document.getElementById('debugBadge');
  if (!el) return;
  el.textContent = `θ=${irt.theta.toFixed(3)} | SEM=${irt.sem.toFixed(3)} | ${Object.keys(userAnswers).length}/${remainingPool.length+questionIds.length}`;
}

function showAnswerModal() {
  if (!lastAnswerFull) return;
  Swal.fire({
    title: 'Correct Answer',
    html: '<div style="text-align:left;font-size:14px;line-height:1.7;white-space:pre-wrap;word-break:break-word;max-height:60vh;overflow-y:auto;">' + lastAnswerFull.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</div>',
    icon: 'info',
    confirmButtonText: 'Close',
    confirmButtonColor: '#3b82f6',
    width: '480px',
  });
}

// ── LOAD QUESTION ─────────────────────────────────────────────────────────────
function loadQuestion(index) {
  if (index >= questionIds.length) return;
  const q  = questionIds[index];
  const tl = typeLabels[q.type] || { label: q.type, icon: 'fa-question', color: '#64748b', path: q.type };

  document.getElementById('questionTag').innerHTML =
    `<i class="fas ${tl.icon}" style="margin-right:4px;"></i> ${tl.label}`;
  document.getElementById('questionProgress').innerHTML =
    `Q <strong>${index + 1}</strong> <span>/ ${MAX_ITEMS}</span>`;

  updateDifficultyBadge();

  fetch('debug_answer.php?id=' + q.id + '&table=' + q.table)
    .then(r => r.text())
    .then(t => {
      lastAnswerFull = t;
      const b = document.getElementById('debugAnswerBadge');
      if (b) b.textContent = 'Ans: ' + (t.length > 40 ? t.substring(0,40)+'…' : t);
    }).catch(e => console.error(e));

  const iframe   = document.getElementById('questionFrame');
  const srcParam = q.table ? `&src=${encodeURIComponent(q.table)}` : '';
  const url      = `${tl.path}/index.php?id=${q.id}${srcParam}&t=${Date.now()}`;
  if (iframe.contentWindow) iframe.contentWindow.location.replace(url);
  else iframe.src = url;

  questionStartTime = Date.now();
  updateProgress();

  if (examMode === 'pressure') {
    startPressureTimer(q.type);
  }
}

// ── IFRAME SECURITY FORWARDING ────────────────────────────────────────────────
document.getElementById('questionFrame').addEventListener('load', () => {
  const iframe   = document.getElementById('questionFrame');
  const innerDoc = iframe.contentDocument || iframe.contentWindow?.document;
  if (innerDoc) {
    innerDoc.addEventListener('contextmenu', e => {
      e.preventDefault();
      Swal.fire({ icon:'warning', title:'Security', text:'Right-click is disabled.', timer:1500, showConfirmButton:false });
    });
    innerDoc.onkeydown = document.onkeydown;
  }
});

// ── MESSAGE HANDLER ───────────────────────────────────────────────────────────
window.addEventListener('message', async event => {
  if (!event.data || typeof event.data !== 'object') return;
  if (event.data.type === 'ready') return; // No prefill in exam mode

  if (event.data.type !== 'answered') return;
  if (isExiting) return;

  const q   = questionIds[currentQuestion];
  if (!q) return;
  const uid = `${q.type}-${q.id}`;

  const timeSpent   = Math.max(0, Math.round((Date.now() - questionStartTime) / 1000));
  const answer      = event.data.answer ?? event.data.highlighted ?? event.data.user_answer ?? [];
  const correctAns  = event.data.correctAnswer ?? event.data.correct ?? [];
  const isCorrect   = !!event.data.correct;
  const initialAns  = event.data.initial_answer ?? null;
  const changes     = event.data.changes ?? null;

  let score = typeof event.data.score !== 'undefined' ? parseFloat(event.data.score) : (isCorrect ? 1 : 0);
  if (score > 1) score = score / 100;
  score = Math.max(0, Math.min(1, score));

  const maxPts     = event.data.max_points  ?? 1;
  const earnedPts  = event.data.earned_points ?? (isCorrect ? 1 : 0);
  const topic      = event.data.topic  ?? null;
  const system     = event.data.system ?? null;
  const cnc        = event.data.cnc    ?? null;
  const dlevel     = event.data.dlevel ?? q.dlevel ?? 'moderate';

  userAnswers[uid] = {
    question_uid: uid, question_type: q.type, question_id: q.id,
    answer, correct_answer: correctAns, initial_answer: initialAns, changes,
    isCorrect: isCorrect ? 1 : 0, score, max_points: maxPts, earned_points: earnedPts,
    rationale: event.data.rationale ?? null,
    topic, system, cnc, dlevel,
    time_taken: timeSpent, totalTime: totalSeconds,
    examTaken, question_number: currentQuestion + 1,
    timestamp: new Date().toISOString(),
    // IRT state snapshot
    theta_before:     irt.theta,
    item_difficulty:  q.b ?? 0.0,
  };

  // ── IRT UPDATE ────────────────────────────────────────────────────────────
  const itemB = q.b ?? 0.0;
  irt.updateTheta(score, itemB);
  userAnswers[uid].theta_after = irt.theta;
  userAnswers[uid].sem_after   = irt.sem;

  // Update streaks
  if (score >= 0.5) { correctStreak++; wrongStreak  = 0; }
  else              { wrongStreak++;   correctStreak = 0; }

  saveAnswersToCache();
  updateProgress();
  updateDifficultyBadge();
  updateCompetencyMeter();
  updateStreakBadge();
  updateDebugBadge();

  // ── SERVER SAVE (silent) ──────────────────────────────────────────────────
  const savePayload = Object.assign({}, userAnswers[uid], {
    exam_mode: 'exam', weighted_score: score * getWeightCorrect(dlevel),
  });
  pendingSave = fetch('save_history.php', {
    method: 'POST', credentials:'same-origin', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(savePayload)
  }).then(r => r.json()).then(res => {
    if (!res.ok) throw new Error(res.error || res.mysql || 'Server error');
  }).catch(err => {
    console.error('save_history failed:', err);
  });

  // ── STOPPING RULES ────────────────────────────────────────────────────────
  const term = checkExamTermination();
  if (term) {
    setTimeout(() => handleAutoTermination(term), 600);
    return;
  }

  // Auto-advance — no feedback, no button needed
  setTimeout(advanceQuestion, 500);
});

// ── NAVIGATION ────────────────────────────────────────────────────────────────
function advanceQuestion() {
  if (isExiting) return;
  clearInterval(pressureTimerInterval);

  if (remainingPool.length > 0 && Object.keys(userAnswers).length < MAX_ITEMS) {
    const next = getNextAdaptiveQuestion();
    if (next) {
      currentQuestion = questionIds.length - 1;
      loadQuestion(currentQuestion);
    } else {
      // Pool returned null unexpectedly — treat as exhausted
      const _answered = Object.keys(userAnswers).length;
      const _result   = (_answered >= MIN_ITEMS && irt.theta > PASSING_LOGIT) ? 'PASSED' : 'FAILED';
      handleAutoTermination({ result: _result, reason: 'pool_exhausted', percent: calculateCompetencyPercent() });
    }
  } else if (remainingPool.length === 0) {
    // Pool exhausted before MAX_ITEMS — check stopping rules first
    const term = checkExamTermination();
    if (term) {
      handleAutoTermination(term);
    } else {
      const _answered = Object.keys(userAnswers).length;
      const _result   = (_answered >= MIN_ITEMS && irt.theta > PASSING_LOGIT) ? 'PASSED' : 'FAILED';
      handleAutoTermination({ result: _result, reason: 'pool_exhausted', percent: calculateCompetencyPercent() });
    }
  } else {
    handleManualFinish();
  }
}

async function handleManualFinish() {
  if (isExiting) return;
  isExiting = true;
  try {
    await pendingSave;
    const pct = calculateCompetencyPercent();
    const res = await fetch('submit_exam.php', {
      method: 'POST', credentials:'same-origin', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        examTaken,
        termination_reason: 'manual_finish',
        final_result:  irt.theta > PASSING_LOGIT ? 'PASSED' : 'FAILED',
        final_percent: pct,
        final_theta:   irt.theta,
        final_sem:     irt.sem,
        total_items:   Object.keys(userAnswers).length,
        exam_duration: totalSeconds,
        is_auto_terminate: false,
        selected_concepts: typeof selectedConceptFilter !== 'undefined' ? selectedConceptFilter : [],
      })
    });
    const result = await res.json();
    if (!result.ok) throw new Error(result.error || 'Submit failed');
    localStorage.removeItem(answerCacheKey);
    await saveExamNote();
  window.location.href = `result.php?examTaken=${examTaken}&finish=1`;
  } catch(err) {
    console.error('Submit error:', err);
    Swal.fire('Error', 'Failed to submit: ' + err.message, 'error');
    isExiting = false;
  }
}

// ── START MODAL — MODE SELECTION ─────────────────────────────────────────────
function selectExamMode(mode) {
  examMode = mode;
  document.getElementById('normalCard').className   = 'mode-card' + (mode === 'normal'   ? ' selected-normal'   : '');
  document.getElementById('pressureCard').className = 'mode-card' + (mode === 'pressure' ? ' selected-pressure' : '');
  const nextBtn = document.getElementById('modeNextBtn');
  nextBtn.disabled = false;
  nextBtn.style.opacity = '1';
}

function showRulesStep() {
  document.getElementById('modeStep').style.display  = 'none';
  document.getElementById('rulesStep').style.display = 'block';

  const isNormal = examMode === 'normal';
  document.getElementById('selectedModeTitle').textContent = isNormal ? 'Normal Mode' : 'Pressure Mode';
  document.getElementById('selectedModeDesc').textContent  = isNormal ? '2-hour global countdown timer' : 'Per-question countdown timers';
  document.getElementById('selectedModeBadge').innerHTML   = isNormal
    ? '<span style="padding:4px 12px;border-radius:100px;background:#dbeafe;color:#2563eb;font-size:11px;font-weight:800;">⏱️ Normal</span>'
    : '<span style="padding:4px 12px;border-radius:100px;background:#fee2e2;color:#dc2626;font-size:11px;font-weight:800;">⚡ Pressure</span>';

  const params = isNormal
    ? [['📋','Total Items',`<?= $TARGET_TOTAL ?> items`],['⏱️','Time Limit','2 hours (120 min)'],
       ['🎯','Stop Rule','IRT 95% Confidence Interval'],['🏁','Min Items',`${MIN_ITEMS} items`],
       ['📊','Scoring','Partial credit (NCSBN NGN)'],['🔢','Difficulty','Adaptive (IRT)']]
    : [['📋','Total Items',`<?= $TARGET_TOTAL ?> items`],['⚡','MC Timer','60 sec / question'],
       ['🎯','Stop Rule','IRT 95% Confidence Interval'],['⏰','Complex Types','2–3 min / question'],
       ['📊','Timeout Rule','Auto-skip → Omitted'],['🔢','Difficulty','Adaptive (IRT)']];

  document.getElementById('examParams').innerHTML = params.map(([icon, label, value]) =>
    `<div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;">
       <div style="font-size:10px;color:#94a3b8;margin-bottom:2px;">${icon} ${label}</div>
       <div style="font-size:12px;font-weight:700;color:#0f172a;">${value}</div>
     </div>`).join('');

  // Reset checkboxes
  document.getElementById('agreeRules').checked = false;
  document.getElementById('agreeFocus').checked = false;
  validateStart();
}

function showModeStep() {
  document.getElementById('rulesStep').style.display = 'none';
  document.getElementById('modeStep').style.display  = 'block';
}

const startModal = document.getElementById('startModal');

function validateStart() {
  const ok = document.getElementById('agreeRules').checked && document.getElementById('agreeFocus').checked;
  const btn = document.getElementById('startExamBtn');
  btn.disabled = !ok;
  btn.style.opacity = ok ? '1' : '.4';
}
document.addEventListener('change', e => {
  if (e.target.id === 'agreeRules' || e.target.id === 'agreeFocus') validateStart();
});

document.getElementById('startExamBtn').addEventListener('click', () => {
  // Update mode badge in navbar
  const badge = document.getElementById('modeBadge');
  badge.style.display = 'inline';
  if (examMode === 'pressure') {
    badge.className = 'mode-badge pressure';
    badge.textContent = '⚡ Pressure';
    document.getElementById('timerIcon').className = 'fas fa-bolt timer-icon';
    document.getElementById('questionTimerBar').style.display = 'block';
  } else {
    badge.className = 'mode-badge normal';
    badge.textContent = '⏱ Normal';
  }

  toggleFullScreen();
  startModal.classList.remove('active');
  startTime = Date.now();

  // Start global timer now that exam has begun
  globalTimerInterval = setInterval(updateTimer, 1000);
  updateTimer();

  const first = getNextAdaptiveQuestion();
  if (!first) { Swal.fire('Error','No questions available in pool.','error'); return; }
  currentQuestion = 0;
  loadQuestion(currentQuestion);
});

// ── RELOAD GUARD ─────────────────────────────────────────────────────────────
window.addEventListener('beforeunload', e => {
  if (!isExiting && Object.keys(userAnswers).length > 0) {
    e.preventDefault();
    e.returnValue = '';
    return '';
  }
});
</script>
</body>
</html>
