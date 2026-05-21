<?php
require_once '../../../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$student_id = intval($_SESSION['user_id']);
$examTaken  = isset($_GET['examTaken']) ? intval($_GET['examTaken']) : 0;

if (isset($_GET['finish']) && $_GET['finish'] == '1') {
    unset($_SESSION['current_exam_examTaken']);
    if (!$examTaken) { header('Location: ../index.php'); exit; }
}
if (!$examTaken) { header('Location: ../index.php'); exit; }

$owns = db()->fetchOne(
    "SELECT COUNT(*) AS c FROM exammoderesults WHERE student_id = ? AND examTaken = ? LIMIT 1",
    [$student_id, $examTaken]
);
if (!$owns || $owns['c'] == 0) { header('Location: ../index.php'); exit; }

$terminal = db()->fetchOne(
    "SELECT * FROM exammoderesults WHERE student_id = ? AND examTaken = ? AND is_terminal = 1 LIMIT 1",
    [$student_id, $examTaken]
);
if (!$terminal) {
    $terminal = db()->fetchOne(
        "SELECT * FROM exammoderesults WHERE student_id = ? AND examTaken = ? ORDER BY question_number DESC LIMIT 1",
        [$student_id, $examTaken]
    );
}

// Defensive pass/fail — handles NULL, empty string, whitespace
$rawResult   = isset($terminal['final_result']) ? strtoupper(trim((string)$terminal['final_result'])) : '';
$finalResult = ($rawResult === 'PASSED' || $rawResult === 'FAILED') ? $rawResult : null;
$finalTheta  = isset($terminal['final_theta']) && $terminal['final_theta'] !== null
               ? floatval($terminal['final_theta'])
               : (isset($terminal['theta_after']) ? floatval($terminal['theta_after']) : null);
$termReason  = isset($terminal['termination_reason']) ? trim((string)$terminal['termination_reason']) : 'manual_finish';

$totalItems = intval($terminal['total_items_answered'] ?? 0);
if ($totalItems === 0) {
    $cnt = db()->fetchOne(
        "SELECT COUNT(*) AS c FROM exammoderesults WHERE student_id = ? AND examTaken = ?",
        [$student_id, $examTaken]
    );
    $totalItems = intval($cnt['c'] ?? $terminal['question_number'] ?? 0);
}

$durationSec = intval($terminal['exam_duration_sec'] ?? $terminal['totalTime'] ?? 0);
$examTime    = $terminal['timestamp'] ?? date('Y-m-d H:i:s');

if ($finalResult === null) {
    if (in_array($termReason, ['irt_pass'], true))
        $finalResult = 'PASSED';
    elseif (in_array($termReason, ['irt_fail','time_expired_insufficient'], true))
        $finalResult = 'FAILED';
    else
        $finalResult = ($finalTheta !== null && $finalTheta > 0.0) ? 'PASSED' : 'FAILED';
}
$isPassed = ($finalResult === 'PASSED');

$durationStr = '';
if ($durationSec > 0) {
    $h = floor($durationSec / 3600);
    $m = floor(($durationSec % 3600) / 60);
    $s = $durationSec % 60;
    $durationStr = ($h > 0 ? "{$h}h " : '') . "{$m}m {$s}s";
}

// CPR — failed only; aggregate by concept (system) only
$cprRows = []; $selectedConcepts = [];
if (!$isPassed) {
    if (!empty($terminal['selected_concepts']))
        $selectedConcepts = json_decode($terminal['selected_concepts'], true) ?: [];
    $cprRows = db()->fetchAll(
        "SELECT system AS concept,
                SUM(earned_points) AS earned, SUM(max_points) AS max_pts, COUNT(*) AS items
         FROM exammoderesults WHERE student_id = ? AND examTaken = ? AND system IS NOT NULL AND system <> ''
         GROUP BY system ORDER BY system ASC",
        [$student_id, $examTaken]
    );
    if (!empty($selectedConcepts))
        $cprRows = array_values(array_filter($cprRows, fn($r) => in_array($r['concept'], $selectedConcepts)));
    if (empty($cprRows)) {
        $cprRows = db()->fetchAll(
            "SELECT cnc AS concept,
                    SUM(earned_points) AS earned, SUM(max_points) AS max_pts, COUNT(*) AS items
             FROM exammoderesults WHERE student_id = ? AND examTaken = ? AND cnc IS NOT NULL AND cnc <> ''
             GROUP BY cnc ORDER BY cnc ASC",
            [$student_id, $examTaken]
        );
    }
}

function getCprLabel(float $ratio): array {
    if ($ratio >= 0.65) return ['label' => 'Above Passing Standard', 'class' => 'above'];
    if ($ratio >= 0.35) return ['label' => 'Near Passing Standard',  'class' => 'near'];
    return                     ['label' => 'Below Passing Standard', 'class' => 'below'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Exam Results — Studium</title>
  <link rel="shortcut icon" type="image/svg+xml" href="../../../assets/LOGO.svg">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Inter', sans-serif;
      background: #f1f5f9;
      color: #0f172a;
      min-height: 100vh;
      -webkit-font-smoothing: antialiased;
    }

    /* ── NAVBAR ── */
    .navbar {
      height: 52px;
      background: #fff;
      border-bottom: 1px solid #e2e8f0;
      display: flex; align-items: center;
      padding: 0 40px; gap: 10px;
      position: static;
    }
    .nb-brand { display: flex; align-items: center; gap: 8px; text-decoration: none; flex: 1; }
    .nb-logo  { height: 24px; width: 24px; object-fit: contain; }
    .nb-name  { font-size: 13px; font-weight: 700; color: #0f172a; letter-spacing: -.2px; }
    .nb-sep   { width: 1px; height: 14px; background: #e2e8f0; flex-shrink: 0; }
    .nb-ctx   { font-size: 11px; font-weight: 500; color: #94a3b8; }
    .nb-btn {
      display: inline-flex; align-items: center; gap: 5px;
      font-size: 11.5px; font-weight: 500; color: #64748b;
      text-decoration: none; padding: 5px 12px;
      border: 1px solid #e2e8f0; border-radius: 6px;
      background: #f8fafc; transition: all .15s; white-space: nowrap;
    }
    .nb-btn:hover { color: #0078b8; border-color: #bfdbfe; background: #eff6ff; }
    .nb-btn i { font-size: 10px; }

    /* ── RESULT HEADER — full-bleed white panel ── */
    .result-header {
      background: #fff;
      border-bottom: 1px solid #e2e8f0;
      padding: 36px 40px 0;
      border-top: 3px solid var(--accent-color);
      animation: fadeUp .3s ease both;
    }
    .result-header.is-passed { --accent-color: #007CBF; }
    .result-header.is-failed { --accent-color: #ef4444; }

    .rh-top { display: flex; align-items: flex-start; gap: 32px; padding-bottom: 28px; }
    .rh-left { flex: 1; min-width: 0; }

    .eyebrow {
      font-size: 10px; font-weight: 600; letter-spacing: 1.2px;
      text-transform: uppercase; color: #94a3b8; margin-bottom: 10px;
    }
    .verdict {
      font-size: 68px; font-weight: 800;
      letter-spacing: -3.5px; line-height: .95;
      margin-bottom: 10px;
    }
    .verdict.passed { color: #007CBF; }
    .verdict.failed { color: #ef4444; }
    .verdict-sub {
      font-size: 13.5px; font-weight: 300;
      color: #64748b; line-height: 1.6;
    }

    /* stat strip — right side of header top */
    .stat-strip {
      flex-shrink: 0;
      display: flex; align-items: stretch;
      border: 1px solid #e2e8f0; border-radius: 10px;
      overflow: hidden; background: #f8fafc;
      align-self: center;
    }
    .stat-item {
      padding: 16px 24px;
      display: flex; flex-direction: column; gap: 4px;
      min-width: 110px;
    }
    .stat-item + .stat-item { border-left: 1px solid #e2e8f0; }
    .stat-val {
      font-size: 24px; font-weight: 700;
      letter-spacing: -.5px; color: #0f172a; line-height: 1;
    }
    .stat-val.pass { color: #007CBF; }
    .stat-val.fail { color: #ef4444; }
    .stat-lbl {
      font-size: 10px; font-weight: 600;
      color: #94a3b8; text-transform: uppercase; letter-spacing: .8px;
    }

    /* tab bar below header top */
    .rh-tabs {
      display: flex; align-items: center; gap: 0;
      border-top: 1px solid #f1f5f9;
    }
    .rh-tab {
      font-size: 11.5px; font-weight: 500; color: #94a3b8;
      padding: 10px 18px; border-bottom: 2px solid transparent;
      cursor: default; white-space: nowrap;
    }
    .rh-tab.active { color: #007CBF; border-bottom-color: #007CBF; font-weight: 600; }
    .rh-tab.active.fail-tab { color: #ef4444; border-bottom-color: #ef4444; }

    /* ── BODY ── */
    .result-body {
      padding: 28px 40px 56px;
      animation: fadeUp .35s ease .1s both;
    }

    /* flex two-col */
    .body-wrap { display: flex; align-items: flex-start; gap: 20px; }
    .main-col  { flex: 1; min-width: 0; }
    .side-col  {
      flex: 0 0 310px;
      display: flex; flex-direction: column; gap: 12px;
      position: sticky; top: 20px;
    }

    /* white content card */
    .main-card {
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      overflow: hidden;
    }
    .main-card-head {
      padding: 14px 22px;
      border-bottom: 1px solid #f1f5f9;
      display: flex; align-items: center; justify-content: space-between;
    }
    .main-card-lbl {
      font-size: 10px; font-weight: 700;
      text-transform: uppercase; letter-spacing: 1.1px; color: #94a3b8;
    }
    .main-card-body { padding: 24px 22px; }

    /* intro */
    .intro {
      font-size: 13.5px; font-weight: 300;
      color: #475569; line-height: 1.9;
      padding-bottom: 20px; margin-bottom: 20px;
      border-bottom: 1px solid #f1f5f9;
    }
    .intro strong { font-weight: 600; color: #0f172a; }

    /* steps */
    .steps-lbl {
      font-size: 10px; font-weight: 700;
      letter-spacing: 1px; text-transform: uppercase;
      color: #94a3b8; margin-bottom: 14px;
    }
    .step {
      display: flex; gap: 16px;
      padding: 15px 0;
      border-bottom: 1px solid #f8fafc;
    }
    .step:first-child { padding-top: 0; }
    .step:last-child  { border-bottom: none; padding-bottom: 0; }
    .step-n {
      font-size: 10px; font-weight: 700; color: #cbd5e1;
      letter-spacing: .4px; padding-top: 2px; flex-shrink: 0; width: 18px;
    }
    .step-body { flex: 1; min-width: 0; }
    .step-title { font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 4px; }
    .step-desc  { font-size: 12.5px; font-weight: 300; color: #64748b; line-height: 1.75; }
    .step-tag {
      display: inline-block; margin-top: 7px;
      font-size: 10px; font-weight: 500;
      color: #0078b8; background: #eff6ff;
      border: 1px solid #bfdbfe; border-radius: 4px; padding: 2px 8px;
    }

    /* side cards */
    .side-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
    .side-card-head { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; }
    .side-card-lbl  { font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; }
    .tip {
      display: flex; align-items: flex-start; gap: 11px;
      padding: 11px 16px; border-bottom: 1px solid #f8fafc;
      transition: background .1s;
    }
    .tip:last-child  { border-bottom: none; }
    .tip:hover       { background: #f8fafc; }
    .tip-ic {
      width: 28px; height: 28px; border-radius: 7px;
      background: #f1f5f9; color: #64748b; font-size: 11px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; margin-top: 1px;
    }
    .tip-title { font-size: 12px; font-weight: 600; color: #0f172a; margin-bottom: 2px; }
    .tip-body  { font-size: 11px; font-weight: 300; color: #64748b; line-height: 1.55; }

    /* CPR */
    .cpr-intro {
      font-size: 13px; font-weight: 300;
      color: #64748b; line-height: 1.8;
      padding-bottom: 16px; margin-bottom: 4px;
      border-bottom: 1px solid #f1f5f9;
    }
.cpr-row { display: flex; flex-direction: column; gap: 7px; padding: 13px 0; border-bottom: 1px solid #f8fafc; }
    .cpr-row:last-child { border-bottom: none; }
    .cpr-top  { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .cpr-area { font-size: 13px; font-weight: 500; color: #0f172a; flex: 1; }
    .cpr-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
    .cpr-pct  { font-size: 12px; font-weight: 600; color: #334155; }
    .cpr-badge { font-size: 10px; font-weight: 600; padding: 2px 9px; border-radius: 4px; white-space: nowrap; }
    .cpr-badge.above { background: #dcfce7; color: #15803d; }
    .cpr-badge.near  { background: #fef9c3; color: #854d0e; }
    .cpr-badge.below { background: #fee2e2; color: #b91c1c; }
    .cpr-track { height: 4px; background: #f1f5f9; border-radius: 2px; overflow: hidden; }
    .cpr-fill  { height: 100%; border-radius: 2px; width: 0%; transition: width .75s cubic-bezier(.4,0,.2,1); }
    .cpr-fill.above { background: #10b981; }
    .cpr-fill.near  { background: #f59e0b; }
    .cpr-fill.below { background: #ef4444; }
    .cpr-count { font-size: 10.5px; color: #94a3b8; }

    /* notice */
    .notice {
      margin-top: 32px; padding-top: 18px;
      border-top: 1px solid #e2e8f0;
      font-size: 11px; font-weight: 300;
      color: #94a3b8; line-height: 1.7; text-align: center;
    }

    /* animation */
    @keyframes fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

    /* responsive */
    @media (max-width: 1100px) {
      .stat-strip { flex-wrap: wrap; max-width: 300px; }
      .stat-item  { flex: 1 1 auto; min-width: 90px; }
      .side-col   { flex: 0 0 280px; }
    }
    @media (max-width: 900px) {
      .rh-top { flex-direction: column; gap: 20px; }
      .stat-strip { flex-wrap: nowrap; max-width: 100%; align-self: stretch; }
      .stat-item  { flex: 1; min-width: 0; }
    }
    @media (max-width: 768px) {
      .navbar        { padding: 0 20px; }
      .result-header { padding: 24px 20px 0; }
      .result-body   { padding: 20px 20px 48px; }
      .verdict       { font-size: 52px; letter-spacing: -2.5px; }
      .body-wrap     { flex-direction: column; }
      .side-col      { flex: none; width: 100%; position: static; flex-direction: row; flex-wrap: wrap; gap: 10px; }
      .side-card     { flex: 1 1 260px; }
    }
    @media (max-width: 480px) {
      .verdict    { font-size: 42px; letter-spacing: -2px; }
      .stat-strip { flex-wrap: wrap; }
      .stat-item  { flex: 1 1 50%; }
      .side-col   { flex-direction: column; }
      .side-card  { flex: none; }
    }
  </style>
</head>
<body>

<nav class="navbar">
  <a href="../index.php" class="nb-brand">
    <img src="../../../assets/LOGO.svg" alt="Studium" class="nb-logo">
    <span class="nb-name">Studium</span>
  </a>
  <div class="nb-sep"></div>
  <span class="nb-ctx">Practice Exam Results</span>
  <a href="../index.php" class="nb-btn"><i class="bi bi-arrow-left"></i> Dashboard</a>
</nav>

<!-- FULL-BLEED HEADER -->
<div class="result-header <?= $isPassed ? 'is-passed' : 'is-failed' ?>">
  <div class="rh-top">
    <div class="rh-left">
      <div class="eyebrow">Adaptive Exam NCLEX Simulation &mdash; <?= date('F j, Y', strtotime($examTime)) ?></div>
      <div class="verdict <?= $isPassed ? 'passed' : 'failed' ?>"><?= $isPassed ? 'Passed.' : 'Failed.' ?></div>
      <div class="verdict-sub">
        <?= $isPassed
          ? 'You met the adaptive passing threshold for this simulation.'
          : 'You did not meet the passing threshold for this simulation.' ?>
      </div>
    </div>
    <div class="stat-strip">
      <div class="stat-item">
        <div class="stat-val"><?= $totalItems ?></div>
        <div class="stat-lbl">Items</div>
      </div>
      <?php if ($durationStr): ?>
      <div class="stat-item">
        <div class="stat-val"><?= $durationStr ?></div>
        <div class="stat-lbl">Duration</div>
      </div>
      <?php endif; ?>
      <div class="stat-item">
        <div class="stat-val <?= $isPassed ? 'pass' : 'fail' ?>"><?= $isPassed ? 'Pass' : 'Fail' ?></div>
        <div class="stat-lbl">Result</div>
      </div>
    </div>
  </div>
  <div class="rh-tabs">
    <?php if ($isPassed): ?>
      <div class="rh-tab active">What to Do Next</div>
      <div class="rh-tab">Tips for Test Day</div>
    <?php else: ?>
      <div class="rh-tab active fail-tab">Performance Report</div>
      <div class="rh-tab">Study Plan</div>
    <?php endif; ?>
  </div>
</div>

<!-- BODY -->
<div class="result-body">
  <div class="body-wrap">

    <?php if ($isPassed): ?>

    <div class="main-col">
      <div class="main-card">
        <div class="main-card-head">
          <span class="main-card-lbl">What This Means</span>
        </div>
        <div class="main-card-body">
          <p class="intro">
            Your performance met the adaptive passing threshold — a strong indicator you are
            <strong>building the clinical reasoning</strong> the NCLEX demands.
            Use this as momentum, not a finish line. Stay consistent and keep drilling weak areas.
          </p>
          <div class="steps-lbl">What to Do Next</div>
          <div class="step">
            <div class="step-n">01</div>
            <div class="step-body">
              <div class="step-title">Review Your Weak Areas</div>
              <div class="step-desc">Even with a passing result, certain content areas may still need attention. Go through session rationales and identify topics where you struggled consistently. Targeted review outperforms broad re-reading.</div>
              <span class="step-tag">Review rationales &amp; session notes</span>
            </div>
          </div>
          <div class="step">
            <div class="step-n">02</div>
            <div class="step-body">
              <div class="step-title">Schedule Your Actual NCLEX Exam</div>
              <div class="step-desc">Complete your Authorization to Test (ATT) through your State Board of Nursing, then schedule through Pearson VUE. Give yourself a focused final-review window without burning out.</div>
              <span class="step-tag">pearsonvue.com/nclex</span>
            </div>
          </div>
          <div class="step">
            <div class="step-n">03</div>
            <div class="step-body">
              <div class="step-title">Build Testing Stamina</div>
              <div class="step-desc">The actual NCLEX can run up to 150 items. Continue full-length timed simulations to keep your endurance sharp and reduce test-day anxiety.</div>
              <span class="step-tag">Full-length timed simulations recommended</span>
            </div>
          </div>
          <div class="step">
            <div class="step-n">04</div>
            <div class="step-body">
              <div class="step-title">Prepare for Exam Day</div>
              <div class="step-desc">Confirm your test center, accepted ID, and arrival time. Rest well the night before. Trust your preparation — avoid last-minute cramming and stay calm.</div>
              <span class="step-tag">Check the NCSBN candidate bulletin</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="side-col">
      <div class="side-card">
        <div class="side-card-head"><div class="side-card-lbl">Tips for Test Day</div></div>
        <div class="tip"><div class="tip-ic"><i class="bi bi-eye"></i></div><div><div class="tip-title">Read Every Word</div><div class="tip-body">Identify the key client, action, and priority before selecting an answer.</div></div></div>
        <div class="tip"><div class="tip-ic"><i class="bi bi-stopwatch"></i></div><div><div class="tip-title">Pace Yourself</div><div class="tip-body">Aim for ~1 minute per item. Commit and move forward.</div></div></div>
        <div class="tip"><div class="tip-ic"><i class="bi bi-sort-down"></i></div><div><div class="tip-title">Prioritize Safely</div><div class="tip-body">Apply ABC, Maslow, and safety principles for multi-action questions.</div></div></div>
        <div class="tip"><div class="tip-ic"><i class="bi bi-shield-check"></i></div><div><div class="tip-title">Trust Your First Instinct</div><div class="tip-body">Avoid changing answers unless you have a clear, specific reason.</div></div></div>
        <div class="tip"><div class="tip-ic"><i class="bi bi-heart-pulse"></i></div><div><div class="tip-title">Think Like a Nurse</div><div class="tip-body">Ask: what is the safest, most appropriate nursing action here?</div></div></div>
        <div class="tip"><div class="tip-ic"><i class="bi bi-moon-stars"></i></div><div><div class="tip-title">Rest Before Your Exam</div><div class="tip-body">Sleep, hydrate, and arrive early. Fatigue directly undermines recall.</div></div></div>
      </div>
      <div class="side-card">
        <div class="side-card-head"><div class="side-card-lbl">After You Pass the Real NCLEX</div></div>
        <div class="tip"><div class="tip-ic"><i class="bi bi-file-text"></i></div><div><div class="tip-title">Quick Results</div><div class="tip-body">Via Pearson VUE within 2 business days (fee applies).</div></div></div>
        <div class="tip"><div class="tip-ic"><i class="bi bi-bank"></i></div><div><div class="tip-title">Apply for Licensure</div><div class="tip-body">Submit your application to your State Board of Nursing immediately.</div></div></div>
        <div class="tip"><div class="tip-ic"><i class="bi bi-card-checklist"></i></div><div><div class="tip-title">Wait for Your License</div><div class="tip-body">4–6 weeks. Do not practice as RN until the license is officially issued.</div></div></div>
      </div>
    </div>

    <?php else: ?>

    <div class="main-col">
      <div class="main-card">
        <div class="main-card-head">
          <span class="main-card-lbl">Candidate Performance Report</span>
          <?php if (!empty($selectedConcepts)): ?>
          <span style="font-size:10.5px;font-weight:400;color:#94a3b8;"><?= implode(' &middot; ', array_map('htmlspecialchars', $selectedConcepts)) ?></span>
          <?php endif; ?>
        </div>
        <div class="main-card-body">
          <p class="cpr-intro">
            Performance breakdown by content area. Prioritize topics rated
            <strong style="font-weight:600;color:#b91c1c;">Below Passing Standard</strong> in your next study sessions.
          </p>
          <?php if (!empty($cprRows)): ?>
            <?php $ci = 0; foreach ($cprRows as $row):
              $earned = floatval($row['earned']); $max = floatval($row['max_pts']);
              $ratio  = $max > 0 ? $earned / $max : 0.0;
              $cpr    = getCprLabel($ratio); $pct = round($ratio * 100);
              $items  = intval($row['items']); $delay = $ci * 80; $ci++;
            ?>
            <div class="cpr-row">
              <div class="cpr-top">
                <div class="cpr-area"><?= htmlspecialchars($row['concept'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="cpr-right">
                  <span class="cpr-pct"><?= $pct ?>%</span>
                  <span class="cpr-badge <?= $cpr['class'] ?>"><?= $cpr['label'] ?></span>
                </div>
              </div>
              <div class="cpr-track" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="cpr-fill <?= $cpr['class'] ?>" data-w="<?= $pct ?>" style="transition-delay:<?= $delay ?>ms;"></div>
              </div>
              <div class="cpr-count"><?= $items ?> item<?= $items !== 1 ? 's' : '' ?></div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="font-size:13px;color:#94a3b8;font-style:italic;">Content area data is not available for this attempt.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="side-col">
      <div class="side-card">
        <div class="side-card-head"><div class="side-card-lbl">Study Recommendations</div></div>
        <div class="tip"><div class="tip-ic"><i class="bi bi-crosshair"></i></div><div><div class="tip-title">Target Weak Areas First</div><div class="tip-body">Prioritize Below Passing Standard content before reviewing stronger topics.</div></div></div>
        <div class="tip"><div class="tip-ic"><i class="bi bi-book"></i></div><div><div class="tip-title">Read Every Rationale</div><div class="tip-body">Wrong-answer rationales reveal knowledge gaps as effectively as correct ones.</div></div></div>
        <div class="tip"><div class="tip-ic"><i class="bi bi-arrow-repeat"></i></div><div><div class="tip-title">Retake by Topic</div><div class="tip-body">Use topic-filtered sessions to drill specific content until you are consistent.</div></div></div>
        <div class="tip"><div class="tip-ic"><i class="bi bi-graph-up"></i></div><div><div class="tip-title">Track Your Progress</div><div class="tip-body">Retake full simulations periodically to measure improvement across all areas.</div></div></div>
      </div>
      <div class="side-card">
        <div class="side-card-head"><div class="side-card-lbl">Before Your Next Attempt</div></div>
        <div class="tip"><div class="tip-ic"><i class="bi bi-calendar3"></i></div><div><div class="tip-title">45-Day Rule</div><div class="tip-body">NCLEX allows retesting after 45 days. Use that window for a structured plan.</div></div></div>
        <div class="tip"><div class="tip-ic"><i class="bi bi-person-video3"></i></div><div><div class="tip-title">Consult Your Instructor</div><div class="tip-body">Discuss your CPR with your review instructor for a personalized strategy.</div></div></div>
        <div class="tip"><div class="tip-ic"><i class="bi bi-wind"></i></div><div><div class="tip-title">Manage Test Anxiety</div><div class="tip-body">Practice breathing and timed simulations to build confidence before retaking.</div></div></div>
      </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
      requestAnimationFrame(function () {
        document.querySelectorAll('.cpr-fill[data-w]').forEach(function (el) {
          var d = parseInt(el.dataset.w > 0 ? el.style.transitionDelay : 0) || 0;
          setTimeout(function () { el.style.width = el.dataset.w + '%'; }, d + 120);
        });
      });
    });
    </script>

    <?php endif; ?>

  </div><!-- /body-wrap -->

  <p class="notice">
    This is a practice simulation by your review center and is not affiliated with NCSBN or Pearson VUE.
    Item content, correct answers, and scoring data are not disclosed after examination completion.
  </p>
</div><!-- /result-body -->

<script>
window.history.pushState(null, '', window.location.href);
window.addEventListener('popstate', function () {
  window.history.pushState(null, '', window.location.href);
});
</script>
</body>
</html>
