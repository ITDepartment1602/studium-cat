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
$qq        = intval($_GET['qq'] ?? 0);
$system    = isset($_GET['system']) ? $_GET['system'] : '';
$qnumsRaw  = isset($_GET['qnums']) ? $_GET['qnums'] : '';
$cc        = intval($_GET['cc'] ?? 0);
$wc        = intval($_GET['wc'] ?? 0);

$systemSafe = mysqli_real_escape_string($con, $system);

// Fetch question data for display
$stmt = $con->prepare("SELECT * FROM question WHERE id = ? AND system = ?");
$stmt->bind_param("is", $qq, $systemSafe);
$stmt->execute();
$fetch = $stmt->get_result()->fetch_assoc();

if (!$fetch) {
    // Fallback without system filter
    $stmt = $con->prepare("SELECT * FROM question WHERE id = ?");
    $stmt->bind_param("i", $qq);
    $stmt->execute();
    $fetch = $stmt->get_result()->fetch_assoc();
}

if (!$fetch) {
    die('Question not found.');
}

// ─── Process POST (answer submission) ─────────────────────────────────────────
$selectedAns = null;
$timeTaken   = 0;
$totalTime   = 0;
$isCorrect   = null;
$correctAns  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedAns = $_POST['ans'] ?? null;
    $timeTaken   = intval($_POST['time_taken'] ?? 0);
    $totalTime   = intval($_POST['totalTime'] ?? 0);

    // Fetch correct answer
    $stmt = $con->prepare("SELECT correctans FROM question WHERE id = ?");
    $stmt->bind_param("i", $qq);
    $stmt->execute();
    $correctAns = $stmt->get_result()->fetch_assoc()['correctans'] ?? '';

    $isCorrect = ($selectedAns == $correctAns) ? 1 : 0;

    // Fetch examTaken
    $stmt = $con->prepare("SELECT examTaken FROM login WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $examTaken = $stmt->get_result()->fetch_assoc()['examTaken'] ?? 0;

    // Check for existing record (prevent duplicate inserts on refresh)
    $stmt = $con->prepare("SELECT COUNT(*) as count FROM review WHERE questionId = ? AND examTaken = ? AND studentId = ?");
    $stmt->bind_param("isi", $qq, $examTaken, $user_id);
    $stmt->execute();
    $existingCount = intval($stmt->get_result()->fetch_assoc()['count'] ?? 0);

    if ($existingCount == 0) {
        if ($step === 150) {
            date_default_timezone_set('Asia/Manila');
            $timestamp = date('Y-m-d H:i:s');
            $stmt = $con->prepare("INSERT INTO review (questionId, isCorrect, topics1, system, cnc, timeTaken, studentId, examTaken, ans, correctAns, questionNumber, totalTime, timestamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissssisssiss",
                $qq, $isCorrect, $fetch['topics1'], $fetch['system'], $fetch['cnc'],
                $timeTaken, $user_id, $examTaken, $selectedAns, $correctAns,
                $step, $totalTime, $timestamp
            );
        } else {
            $stmt = $con->prepare("INSERT INTO review (questionId, isCorrect, topics1, system, cnc, timeTaken, studentId, examTaken, ans, correctAns, questionNumber) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissssisssi",
                $qq, $isCorrect, $fetch['topics1'], $fetch['system'], $fetch['cnc'],
                $timeTaken, $user_id, $examTaken, $selectedAns, $correctAns, $step
            );
        }
        $stmt->execute();
    } else {
        // Update if already exists (e.g., page reload)
        $stmt = $con->prepare("UPDATE review SET isCorrect = ?, timeTaken = ?, ans = ? WHERE questionId = ? AND examTaken = ? AND studentId = ?");
        $stmt->bind_param("iissis", $isCorrect, $timeTaken, $selectedAns, $qq, $examTaken, $user_id);
        $stmt->execute();
    }
} else {
    // Direct GET visit — fetch correctAns for display only
    $stmt = $con->prepare("SELECT correctans FROM question WHERE id = ?");
    $stmt->bind_param("i", $qq);
    $stmt->execute();
    $correctAns = $stmt->get_result()->fetch_assoc()['correctans'] ?? '';
}

// ─── Progress drawer data ─────────────────────────────────────────────────────
$_pd_s = $con->prepare("SELECT examTaken FROM login WHERE id = ?");
$_pd_s->bind_param("i", $user_id); $_pd_s->execute();
$_pd_et = $_pd_s->get_result()->fetch_assoc()['examTaken'] ?? 0;
$_pd_q = $con->prepare(
    "SELECT r.questionNumber, r.isCorrect, r.ans, r.correctAns,
            q.question, q.choiceA, q.choiceB, q.choiceC, q.choiceD,
            q.rationale, q.topics1, q.system
     FROM review r JOIN question q ON r.questionId = q.id
     WHERE r.studentId = ? AND r.examTaken = ?
     ORDER BY r.questionNumber ASC"
);
$_pd_q->bind_param("is", $user_id, $_pd_et); $_pd_q->execute();
$_pd_rows = []; $_pd_r2 = $_pd_q->get_result();
while ($_r = $_pd_r2->fetch_assoc()) {
    $_pd_rows[] = ['n'=>(int)$_r['questionNumber'],'ok'=>(int)$_r['isCorrect'],
        'ans'=>$_r['ans'],'cor'=>$_r['correctAns'],'t'=>$_r['topics1'],
        's'=>$_r['system'],'q'=>$_r['question'],'a'=>$_r['choiceA'],
        'b'=>$_r['choiceB'],'c'=>$_r['choiceC'],'d'=>$_r['choiceD'],
        'rat'=>$_r['rationale']];
}
$_pd_json = json_encode($_pd_rows, JSON_HEX_TAG|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE);

// ─── Build navigation URLs ─────────────────────────────────────────────────────
$updatedQnums = !empty($qnumsRaw) ? $qnumsRaw . '|' . $qq : (string)$qq;
$newCc = ($isCorrect === 1) ? $cc + 1 : $cc;
$newWc = ($isCorrect === 0) ? $wc + 1 : $wc;

if ($step < 150) {
    $nextUrl = '../question/index.php?' . http_build_query([
        'step'     => $step + 1,
        'topics1'  => $concept,
        'topics2'  => $topics2,
        'kilanlan' => $kilanlan,
        'id'       => $user_id,
        'qnums'    => $updatedQnums,
        'cc'       => $newCc,
        'wc'       => $newWc,
    ]);
    $nextLabel = 'Next Question';
    $nextIcon  = 'fa-arrow-right';
} else {
    $nextUrl = '../result-loader.php?' . http_build_query([
        'topics1'         => $concept,
        'topics2'         => $topics2,
        'kilanlan'        => $kilanlan,
        'id'              => $user_id,
        'kilanlanhistory' => $kilanlan,
    ]);
    $nextLabel = 'View Results';
    $nextIcon  = 'fa-trophy';
}

$progress = round(($step / 150) * 100);

// Choices map
$choices = [
    1 => ['text' => $fetch['choiceA'], 'letter' => 'A'],
    2 => ['text' => $fetch['choiceB'], 'letter' => 'B'],
    3 => ['text' => $fetch['choiceC'], 'letter' => 'C'],
    4 => ['text' => $fetch['choiceD'], 'letter' => 'D'],
];

$userRow  = mysqli_query($con, "SELECT bundle_name FROM login WHERE id = '$user_id'");
$userData = mysqli_fetch_assoc($userRow);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rationale — Question <?= $step ?> of 150 — Studium</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="shortcut icon" href="../../../img/logo1.svg">
  <style>
    :root {
      --primary: #0a1628;
      --primary-light: #1e3a5f;
      --accent: #0d9488;
      --success: #10b981;
      --success-bg: #ecfdf5;
      --danger: #ef4444;
      --danger-bg: #fef2f2;
      --warning: #f59e0b;
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
    .ra-nav {
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
    .ra-logo { font-size: 1.05rem; font-weight: 800; color: #fff; white-space: nowrap; letter-spacing: -0.5px; }
    .ra-logo span { color: var(--accent); }
    .ra-progress-wrap {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 5px;
      max-width: 380px;
      margin: 0 auto;
    }
    .ra-progress-label { font-size: 0.74rem; color: rgba(255,255,255,.7); display: flex; justify-content: space-between; }
    .ra-progress-bar { height: 5px; background: rgba(255,255,255,.15); border-radius: 3px; overflow: hidden; }
    .ra-progress-fill { height: 100%; background: var(--accent); border-radius: 3px; }
    .ra-badge {
      font-size: 0.74rem;
      font-weight: 700;
      padding: 4px 12px;
      border-radius: 20px;
      white-space: nowrap;
    }
    .ra-badge.correct { background: rgba(16,185,129,.15); color: #34d399; }
    .ra-badge.incorrect { background: rgba(239,68,68,.15); color: #fca5a5; }
    .ra-badge.neutral { background: rgba(255,255,255,.1); color: rgba(255,255,255,.6); }
    .ra-end-btn {
      background: transparent;
      border: 1.5px solid rgba(255,255,255,.3);
      color: rgba(255,255,255,.7);
      border-radius: 8px;
      padding: 6px 14px;
      font-size: 0.78rem;
      font-weight: 600;
      font-family: 'Inter', sans-serif;
      cursor: pointer;
      transition: border-color 0.2s, color 0.2s;
    }
    .ra-end-btn:hover { border-color: #ef4444; color: #ef4444; }

    /* ── Main layout ── */
    .ra-main { flex: 1; overflow-y: auto; padding: 28px 20px; }
    .ra-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      max-width: 1200px;
      margin: 0 auto;
    }
    @media (max-width: 900px) { .ra-grid { grid-template-columns: 1fr; } }

    /* ── Panel card ── */
    .ra-panel {
      background: var(--surface);
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0,0,0,.07);
      padding: 28px;
    }
    .ra-panel-title {
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      color: var(--text-muted);
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .ra-panel-title i { color: var(--accent); }

    /* ── Question text ── */
    .ra-question-text {
      font-size: 0.95rem;
      line-height: 1.75;
      color: var(--text);
      font-weight: 500;
      margin-bottom: 20px;
    }

    /* ── Answer choices ── */
    .ra-choices { display: flex; flex-direction: column; gap: 9px; }
    .ra-choice {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      padding: 12px 16px;
      border-radius: 10px;
      border: 2px solid transparent;
      transition: none;
    }
    .ra-choice.correct {
      background: var(--success-bg);
      border-color: var(--success);
    }
    .ra-choice.wrong {
      background: var(--danger-bg);
      border-color: var(--danger);
    }
    .ra-choice.neutral {
      background: #f8fafc;
      border-color: var(--border);
    }
    .ra-choice-icon {
      width: 26px; height: 26px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.78rem;
      font-weight: 700;
      flex-shrink: 0;
    }
    .ra-choice.correct .ra-choice-icon { background: var(--success); color: #fff; }
    .ra-choice.wrong .ra-choice-icon { background: var(--danger); color: #fff; }
    .ra-choice.neutral .ra-choice-icon { background: var(--border); color: var(--text-muted); }
    .ra-choice-letter { font-size: 0.78rem; font-weight: 700; }
    .ra-choice-text {
      font-size: 0.92rem;
      line-height: 1.5;
      color: var(--text);
    }
    .ra-choice.correct .ra-choice-text { color: #065f46; }
    .ra-choice.wrong .ra-choice-text { color: #991b1b; }

    /* ── Tags ── */
    .ra-tags {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-top: 20px;
      padding-top: 20px;
      border-top: 1px solid var(--border);
    }
    .ra-tag-item { font-size: 0.78rem; color: var(--text-muted); }
    .ra-tag-item strong { color: var(--text); font-weight: 600; }

    /* ── Rationale text ── */
    .ra-rationale-text {
      font-size: 0.92rem;
      line-height: 1.75;
      color: #1e293b;
    }
    .ra-narc-box {
      margin-top: 20px;
      padding: 16px;
      background: rgba(13,148,136,.06);
      border-radius: 10px;
      border-left: 3px solid var(--accent);
    }
    .ra-narc-title {
      font-size: 0.78rem;
      font-weight: 700;
      color: var(--accent);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 10px;
    }
    .ra-narc-text { font-size: 0.88rem; line-height: 1.7; color: var(--text); }

    /* ── Bottom bar ── */
    .ra-bottom {
      background: var(--surface);
      border-top: 1px solid var(--border);
      padding: 16px 24px;
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 12px;
      flex-shrink: 0;
    }
    .ra-score-label { font-size: 0.82rem; color: var(--text-muted); margin-right: auto; }
    .ra-score-label strong { color: var(--text); }
    .ra-next-btn {
      background: var(--primary);
      color: #fff;
      text-decoration: none;
      border-radius: 10px;
      padding: 12px 28px;
      font-size: 0.95rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: opacity 0.2s;
    }
    .ra-next-btn:hover { opacity: 0.85; }
    <?php if ($step === 150): ?>
    .ra-next-btn { background: var(--accent); }
    <?php endif; ?>

    @media (max-width: 640px) {
      .ra-nav { padding: 0 14px; gap: 10px; }
      .ra-logo { font-size: 0.9rem; }
      .ra-main { padding: 16px 10px; }
      .ra-panel { padding: 18px; }
      .ra-bottom { padding: 12px 16px; }
      .ra-tags { grid-template-columns: 1fr; }
    }
    .pd-view-btn{display:inline-flex;align-items:center;gap:6px;padding:0 13px;height:38px;border-radius:10px;border:1.5px solid #e2e8f0;background:#f8fafc;color:#64748b;font-size:.75rem;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;transition:border-color .15s,background .15s,color .15s;white-space:nowrap;flex-shrink:0;margin-right:auto;}
    .pd-view-btn:hover{border-color:#0d9488;background:#f0fdf9;color:#0d9488;}
    .pd-view-btn i{color:#0d9488;font-size:.8rem;}
  </style>
</head>
<body oncontextmenu="return false" onselectstart="return false" ondragstart="return false">

<nav class="ra-nav">
  <div class="ra-logo">Studium<span>.</span></div>

  <div class="ra-progress-wrap">
    <div class="ra-progress-label">
      <span>Rationale — Question <?= $step ?> of 150</span>
      <span style="display:flex;align-items:center;gap:5px;">
        <span id="ra-timer-paused" style="font-size:0.7rem;color:rgba(255,255,255,0.45);display:inline-flex;align-items:center;gap:3px;">
          <i class="fa-solid fa-pause" style="font-size:0.6rem;"></i> <span id="ra-timer-value">--:--</span> paused
        </span>
        <?= $progress ?>%
      </span>
    </div>
    <div class="ra-progress-bar">
      <div class="ra-progress-fill" style="width:<?= $progress ?>%"></div>
    </div>
  </div>

  <?php if ($isCorrect !== null): ?>
    <div class="ra-badge <?= $isCorrect ? 'correct' : 'incorrect' ?>">
      <?= $isCorrect ? '<i class="fa-solid fa-check me-1"></i> Correct' : '<i class="fa-solid fa-xmark me-1"></i> Incorrect' ?>
    </div>
  <?php else: ?>
    <div class="ra-badge neutral">Rationale</div>
  <?php endif; ?>

  <button class="ra-end-btn" onclick="confirmEnd('<?= htmlspecialchars($userData['bundle_name'] ?? '', ENT_QUOTES) ?>')">
    <i class="fa-solid fa-stop"></i> End
  </button>
</nav>

<div class="ra-main">
  <div class="ra-grid">

    <!-- LEFT: Question + Choices -->
    <div class="ra-panel">
      <div class="ra-panel-title"><i class="fa-solid fa-circle-question"></i> Question <?= $step ?></div>

      <div class="ra-question-text"><?= $fetch['question'] ?></div>

      <div class="ra-choices">
        <?php foreach ($choices as $val => $choice): ?>
          <?php
          $state = 'neutral';
          if ($correctAns == $val) {
              $state = 'correct';
          } elseif ($selectedAns !== null && $selectedAns == $val) {
              $state = 'wrong';
          }
          $icon = '';
          if ($state === 'correct') $icon = '<i class="fa-solid fa-check"></i>';
          elseif ($state === 'wrong') $icon = '<i class="fa-solid fa-xmark"></i>';
          else $icon = $choice['letter'];
          ?>
          <div class="ra-choice <?= $state ?>">
            <div class="ra-choice-icon">
              <?= $icon ?>
            </div>
            <div class="ra-choice-text"><?= $choice['text'] ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="ra-tags">
        <div class="ra-tag-item">Subject: <strong><?= htmlspecialchars($fetch['topics1'] ?? '') ?></strong></div>
        <div class="ra-tag-item">Difficulty: <strong><?= htmlspecialchars($fetch['dlevel'] ?? 'N/A') ?></strong></div>
        <div class="ra-tag-item">System: <strong><?= htmlspecialchars($fetch['system'] ?? '') ?></strong></div>
        <div class="ra-tag-item">CNC: <strong><?= htmlspecialchars($fetch['cnc'] ?? 'N/A') ?></strong></div>
        <div class="ra-tag-item">Question ID: <strong><?= str_pad($fetch['id'], 5, '0', STR_PAD_LEFT) ?></strong></div>
        <?php if ($timeTaken > 0): ?>
        <div class="ra-tag-item">Time Taken: <strong>
          <?php
          $tm = floor($timeTaken / 60);
          $ts = $timeTaken % 60;
          echo $tm > 0 ? $tm . 'm ' . $ts . 's' : $ts . 's';
          ?>
        </strong></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- RIGHT: Rationale -->
    <div class="ra-panel">
      <div class="ra-panel-title"><i class="fa-solid fa-book-open"></i> Rationale</div>

      <div class="ra-rationale-text"><?= nl2br(str_replace('\n', "\n", $fetch['rationale'] ?? 'No rationale available.')) ?></div>

      <?php if (!empty($fetch['narcan'])): ?>
      <div class="ra-narc-box">
        <div class="ra-narc-title"><i class="fa-solid fa-notes-medical"></i> NARC Additional Notes</div>
        <div class="ra-narc-text"><?= nl2br(str_replace('\n', "\n", $fetch['narcan'])) ?></div>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<!-- ── Progress Drawer ── -->
<script>const PD_DATA = <?= $_pd_json ?>;</script>
<style>
#pd-ov{position:fixed;inset:0;background:rgba(0,0,0,.18);z-index:600;opacity:0;pointer-events:none;transition:opacity .25s;}
#pd-ov.open{opacity:1;pointer-events:all;}
#pd-dr{position:fixed;bottom:76px;left:20px;width:360px;max-width:calc(100vw - 32px);max-height:72vh;background:#fff;border-radius:16px;z-index:601;display:flex;flex-direction:column;transform:translateY(60px);opacity:0;pointer-events:none;transition:transform .32s cubic-bezier(.4,0,.2,1),opacity .28s ease;box-shadow:0 20px 60px rgba(0,0,0,.18),0 6px 16px rgba(0,0,0,.08);border:1px solid rgba(0,0,0,.07);overflow:hidden;}
#pd-dr.open{transform:translateY(0);opacity:1;pointer-events:all;}
@media(max-width:640px){#pd-dr{left:12px;right:12px;width:auto;bottom:70px;max-height:72vh;}}
.pd-hnd{width:38px;height:4px;background:#e2e8f0;border-radius:2px;margin:12px auto 4px;cursor:pointer;flex-shrink:0;}
.pd-hd{display:flex;align-items:center;justify-content:space-between;padding:10px 20px 12px;border-bottom:1px solid #e8edf2;flex-shrink:0;}
.pd-hd-t{font-size:.98rem;font-weight:700;color:#0f172a;}
.pd-hd-s{font-size:.72rem;color:#64748b;margin-top:2px;}
.pd-hd-x{width:28px;height:28px;border-radius:50%;border:none;background:#f1f5f9;cursor:pointer;font-size:1.1rem;color:#64748b;display:flex;align-items:center;justify-content:center;font-family:inherit;flex-shrink:0;}
.pd-ls{flex:1;overflow-y:auto;padding:10px 14px;display:flex;flex-direction:column;gap:7px;}
.pd-it{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:11px;border:1.5px solid #e2e8f0;cursor:pointer;transition:border-color .15s,background .15s;background:#fff;text-align:left;width:100%;font-family:'Inter',sans-serif;}
.pd-it:hover{border-color:#0d9488;background:#f0fdf9;}
.pd-it.pdc{border-color:#bbf7d0;background:#f0fdf4;}
.pd-it.pdw{border-color:#fecaca;background:#fef2f2;}
.pd-it-n{font-size:.68rem;font-weight:700;color:#94a3b8;width:28px;flex-shrink:0;}
.pd-it-ic{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;flex-shrink:0;}
.pd-it.pdc .pd-it-ic{background:#10b981;color:#fff;}
.pd-it.pdw .pd-it-ic{background:#ef4444;color:#fff;}
.pd-it-inf{flex:1;min-width:0;}
.pd-it-tp{font-size:.8rem;font-weight:600;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.pd-it.pdw .pd-it-tp{text-decoration:line-through;color:#ef4444;}
.pd-it-sy{font-size:.68rem;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.pd-it-ar{font-size:.68rem;color:#cbd5e1;flex-shrink:0;}
.pd-ft{padding:10px 16px;border-top:1px solid #f1f5f9;flex-shrink:0;display:flex;align-items:center;justify-content:space-between;gap:8px;}
.pd-ft-info{font-size:.7rem;color:#94a3b8;white-space:nowrap;}
.pd-pg{display:flex;align-items:center;gap:4px;}
.pd-pg-btn{height:28px;min-width:28px;padding:0 8px;border-radius:7px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;font-size:.72rem;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;transition:border-color .15s,background .15s,color .15s;display:flex;align-items:center;justify-content:center;}
.pd-pg-btn:hover:not(:disabled){border-color:#0d9488;background:#f0fdf9;color:#0d9488;}
.pd-pg-btn:disabled{opacity:.35;cursor:default;}
.pd-pg-btn.active{background:#0d9488;border-color:#0d9488;color:#fff;}
.pd-pg-dots{font-size:.72rem;color:#94a3b8;padding:0 2px;}
#pd-pv{position:absolute;inset:0;background:#fff;display:flex;flex-direction:column;transform:translateX(100%);transition:transform .28s cubic-bezier(.4,0,.2,1);z-index:10;border-radius:20px 20px 0 0;}
#pd-pv.open{transform:translateX(0);}
.pd-pv-hd{display:flex;align-items:center;gap:10px;padding:13px 18px;border-bottom:1px solid #e8edf2;flex-shrink:0;}
.pd-pv-bk{border:none;background:#f1f5f9;border-radius:8px;padding:5px 12px;font-size:.78rem;font-weight:600;color:#475569;cursor:pointer;display:flex;align-items:center;gap:5px;font-family:'Inter',sans-serif;}
.pd-pv-t{font-size:.92rem;font-weight:700;color:#0f172a;flex:1;}
.pd-pv-b{font-size:.68rem;font-weight:700;padding:3px 9px;border-radius:20px;}
.pd-pv-b.c{background:#dcfce7;color:#16a34a;}
.pd-pv-b.w{background:#fee2e2;color:#dc2626;}
.pd-pv-bd{flex:1;overflow-y:auto;padding:18px;}
.pd-pq{font-size:.9rem;line-height:1.72;color:#1e293b;font-weight:500;margin-bottom:16px;}
.pd-chs{display:flex;flex-direction:column;gap:7px;margin-bottom:16px;}
.pd-ch{display:flex;align-items:flex-start;gap:9px;padding:9px 13px;border-radius:9px;border:2px solid transparent;font-size:.85rem;line-height:1.5;}
.pd-ch.c{background:#f0fdf4;border-color:#10b981;color:#065f46;}
.pd-ch.w{background:#fef2f2;border-color:#ef4444;color:#991b1b;}
.pd-ch.n{background:#f8fafc;border-color:#e2e8f0;color:#1e293b;}
.pd-ch-i{width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;flex-shrink:0;margin-top:1px;}
.pd-ch.c .pd-ch-i{background:#10b981;color:#fff;}
.pd-ch.w .pd-ch-i{background:#ef4444;color:#fff;}
.pd-ch.n .pd-ch-i{background:#e2e8f0;color:#64748b;}
.pd-rl{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#0d9488;margin-bottom:7px;display:flex;align-items:center;gap:5px;}
.pd-rt{font-size:.85rem;line-height:1.72;color:#334155;}
</style>
<div id="pd-ov" onclick="pdClose()"></div>
<div id="pd-dr">
  <div class="pd-hnd" onclick="pdClose()"></div>
  <div class="pd-hd">
    <div>
      <div class="pd-hd-t">Progress Overview</div>
      <div class="pd-hd-s" id="pd-sub"></div>
    </div>
    <button class="pd-hd-x" onclick="pdClose()">&times;</button>
  </div>
  <div class="pd-ls" id="pd-ls"></div>
  <div class="pd-ft" id="pd-ft">
    <span class="pd-ft-info" id="pd-pg-info"></span>
    <div class="pd-pg" id="pd-pg"></div>
  </div>
  <div id="pd-pv">
    <div class="pd-pv-hd">
      <button class="pd-pv-bk" onclick="pdClosePv()"><i class="fa-solid fa-arrow-left"></i> Back</button>
      <div class="pd-pv-t" id="pd-pv-t"></div>
      <div class="pd-pv-b" id="pd-pv-b"></div>
    </div>
    <div class="pd-pv-bd" id="pd-pv-bd"></div>
  </div>
</div>
<script>
function pdOpen() {
  _pdDone = false;
  _pdRender();
  document.getElementById('pd-ov').classList.add('open');
  document.getElementById('pd-dr').classList.add('open');
}
function pdClose() {
  document.getElementById('pd-ov').classList.remove('open');
  document.getElementById('pd-dr').classList.remove('open');
  pdClosePv();
}
function pdClosePv() { document.getElementById('pd-pv').classList.remove('open'); }
var _pdDone = false, _pdPage = 0;
var PD_PER_PAGE = 10;
function _pdRender() {
  if (_pdDone) return; _pdDone = true;
  var ans = PD_DATA.length, ok = 0;
  PD_DATA.forEach(function(q){ if (q.ok == 1) ok++; });
  document.getElementById('pd-sub').textContent = ans + ' of 150 answered';
  _pdPage = 0;
  _pdRenderPage();
}
function _pdRenderPage() {
  var ans = PD_DATA.length;
  var totalPages = Math.max(1, Math.ceil(ans / PD_PER_PAGE));
  if (_pdPage >= totalPages) _pdPage = totalPages - 1;
  var start = _pdPage * PD_PER_PAGE;
  var end = Math.min(start + PD_PER_PAGE, ans);
  var html = '';
  for (var i = start; i < end; i++) {
    var q = PD_DATA[i], isC = q.ok == 1;
    html += '<button class="pd-it ' + (isC ? 'pdc' : 'pdw') + '" onclick="pdOpenPv(' + i + ')">'
      + '<div class="pd-it-n">Q' + q.n + '</div>'
      + '<div class="pd-it-ic">' + (isC ? '&#10003;' : '&#10007;') + '</div>'
      + '<div class="pd-it-inf"><div class="pd-it-tp">' + _pdEsc(q.s) + '</div></div>'
      + '<div class="pd-it-ar"><i class="fa-solid fa-chevron-right"></i></div>'
      + '</button>';
  }
  if (!html) html = '<div style="text-align:center;padding:40px 20px;color:#94a3b8;font-size:.85rem;">No questions answered yet. Your progress will appear here.</div>';
  document.getElementById('pd-ls').innerHTML = html;
  document.getElementById('pd-ls').scrollTop = 0;
  // Footer info
  document.getElementById('pd-pg-info').textContent = ans > 0
    ? (start + 1) + '–' + end + ' of ' + ans + ' answered'
    : (150 - ans) + ' questions remaining';
  // Pagination buttons
  var pgHtml = '';
  if (totalPages > 1) {
    pgHtml += '<button class="pd-pg-btn" onclick="pdGoPage(' + (_pdPage - 1) + ')" ' + (_pdPage === 0 ? 'disabled' : '') + '><i class="fa-solid fa-chevron-left"></i></button>';
    var pages = _pdPageNums(totalPages, _pdPage);
    pages.forEach(function(p) {
      if (p === '...') {
        pgHtml += '<span class="pd-pg-dots">···</span>';
      } else {
        pgHtml += '<button class="pd-pg-btn' + (p === _pdPage ? ' active' : '') + '" onclick="pdGoPage(' + p + ')">' + (p + 1) + '</button>';
      }
    });
    pgHtml += '<button class="pd-pg-btn" onclick="pdGoPage(' + (_pdPage + 1) + ')" ' + (_pdPage >= totalPages - 1 ? 'disabled' : '') + '><i class="fa-solid fa-chevron-right"></i></button>';
  }
  document.getElementById('pd-pg').innerHTML = pgHtml;
}
function pdGoPage(p) {
  var totalPages = Math.ceil(PD_DATA.length / PD_PER_PAGE);
  if (p < 0 || p >= totalPages) return;
  _pdPage = p;
  _pdRenderPage();
}
function _pdPageNums(total, cur) {
  if (total <= 5) {
    var r = []; for (var i = 0; i < total; i++) r.push(i); return r;
  }
  var pages = [0];
  if (cur > 2) pages.push('...');
  for (var i = Math.max(1, cur - 1); i <= Math.min(total - 2, cur + 1); i++) pages.push(i);
  if (cur < total - 3) pages.push('...');
  pages.push(total - 1);
  return pages;
}
function _pdEsc(s) { return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function pdOpenPv(idx) {
  var q = PD_DATA[idx]; if (!q) return;
  var L = ['A','B','C','D'], T = [q.a, q.b, q.c, q.d];
  var ch = '';
  for (var i = 0; i < 4; i++) {
    var v = i + 1, cls = 'n', ico = L[i];
    if (q.cor == v) { cls = 'c'; ico = '&#10003;'; }
    else if (q.ans == v && q.ok == 0) { cls = 'w'; ico = '&#10007;'; }
    ch += '<div class="pd-ch ' + cls + '"><div class="pd-ch-i">' + ico + '</div><div>' + (T[i] || '') + '</div></div>';
  }
  var rat = (q.rat || 'No rationale available.').replace(/\\n/g, '<br>').replace(/\n/g, '<br>');
  document.getElementById('pd-pv-bd').innerHTML =
    '<div class="pd-pq">' + (q.q || '') + '</div>' +
    '<div class="pd-chs">' + ch + '</div>' +
    '<div class="pd-rl"><i class="fa-solid fa-book-open"></i>&nbsp;Rationale</div>' +
    '<div class="pd-rt">' + rat + '</div>';
  var isC = q.ok == 1;
  document.getElementById('pd-pv-t').textContent = 'Question ' + q.n;
  var bdg = document.getElementById('pd-pv-b');
  bdg.className = 'pd-pv-b ' + (isC ? 'c' : 'w');
  bdg.textContent = isC ? 'Correct' : 'Incorrect';
  document.getElementById('pd-pv').classList.add('open');
}
</script>

<div class="ra-bottom">
  <button onclick="pdOpen()" class="pd-view-btn" type="button">
    <i class="fa-solid fa-list-check"></i> View Progress
  </button>
  <a href="<?= htmlspecialchars($nextUrl) ?>" class="ra-next-btn" onclick="resumeTimer()">
    <?= $step === 150 ? '<i class="fa-solid fa-trophy"></i>' : '' ?>
    <?= $nextLabel ?>
    <?= $step < 150 ? '<i class="fa-solid fa-arrow-right"></i>' : '' ?>
  </a>
</div>

<script>
// Show the frozen session timer value that was captured when student submitted
(function () {
  const s = parseInt(localStorage.getItem('count')) || 0;
  const m = Math.floor(s / 60), ss = s % 60;
  const el = document.getElementById('ra-timer-value');
  if (el) el.textContent = m + ':' + (ss < 10 ? '0' + ss : ss);
})();

function resumeTimer() {
  localStorage.removeItem('timer_paused');
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
      window.location.href = '../index.php?bundle_name=' + encodeURIComponent(bundleName);
    }
  });
}
</script>
</body>
</html>
