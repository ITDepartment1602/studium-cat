<?php
// Exam Mode — Result Page (Phase 4)
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

// ─── OWNERSHIP CHECK ──────────────────────────────────────────────────────────
$owns = db()->fetchOne(
    "SELECT COUNT(*) AS c FROM exammoderesults WHERE student_id = ? AND examTaken = ? LIMIT 1",
    [$student_id, $examTaken]
);
if (!$owns || $owns['c'] == 0) { header('Location: ../index.php'); exit; }

// ─── LOAD ALL ROWS (ordered by question_number) ───────────────────────────────
$rows = db()->fetchAll(
    "SELECT * FROM exammoderesults WHERE student_id = ? AND examTaken = ? ORDER BY question_number ASC",
    [$student_id, $examTaken]
);

// ─── TERMINAL ROW ─────────────────────────────────────────────────────────────
$terminal = null;
foreach ($rows as $r) { if ($r['is_terminal']) { $terminal = $r; break; } }
if (!$terminal) $terminal = end($rows); // fallback to last row

$finalResult  = $terminal['final_result']         ?? null;
$finalPct     = $terminal['final_percent']         ?? null;
$finalTheta   = $terminal['final_theta']           ?? null;
$finalSem     = $terminal['final_sem']             ?? null;
$termReason   = $terminal['termination_reason']    ?? 'manual_finish';
$totalItems   = $terminal['total_items_answered']  ?? count($rows);
$durationSec  = $terminal['exam_duration_sec']     ?? 0;
$examTime     = $terminal['timestamp']             ?? date('Y-m-d H:i:s');

// If final_result/percent wasn't stored, derive from running totals
if ($finalPct === null) {
    $lastRow  = end($rows);
    $finalPct = floatval($lastRow['running_percent'] ?? 0);
}
if ($finalResult === null) {
    $finalResult = $finalPct >= 85 ? 'PASSED' : 'FAILED';
}

$isPassed = ($finalResult === 'PASSED');

// ─── THETA PROGRESSION (use stored theta_after values from exam) ──────────────
$thetaProgression = [];
foreach ($rows as $row) {
    // Use the stored theta_after snapshot from the exam; fall back to 0
    $thetaProgression[] = round(floatval($row['theta_after'] ?? 0.0), 3);
}

// ─── AGGREGATIONS ─────────────────────────────────────────────────────────────
// Difficulty distribution
$diffCounts = ['easy' => 0, 'moderate' => 0, 'hard' => 0];
foreach ($rows as $r) {
    $dl = strtolower(trim($r['dlevel'] ?? 'moderate'));
    if ($dl === 'medium') $dl = 'moderate';
    if (isset($diffCounts[$dl])) $diffCounts[$dl]++;
    else $diffCounts['moderate']++;
}

// Topic & Concept performance
$topicMap = [];
$conceptMap = [];
foreach ($rows as $r) {
    // Topic
    $t = htmlspecialchars($r['topic'] ?? 'General', ENT_QUOTES, 'UTF-8') ?: 'General';
    if (!isset($topicMap[$t])) $topicMap[$t] = ['earned' => 0, 'possible' => 0, 'count' => 0];
    
    // Concept (stored in the `cnc` column)
    $c = htmlspecialchars($r['cnc'] ?? 'General Concept', ENT_QUOTES, 'UTF-8') ?: 'General Concept';
    if (!isset($conceptMap[$c])) $conceptMap[$c] = ['earned' => 0, 'possible' => 0, 'count' => 0];

    $dl = strtolower(trim($r['dlevel'] ?? 'moderate'));
    $w  = ['easy'=>1.0,'moderate'=>1.2,'medium'=>1.2,'hard'=>1.5][$dl] ?? 1.2;
    $sc = floatval($r['score']);
    
    $topicMap[$t]['earned']   += $sc * $w;
    $topicMap[$t]['possible'] += 1.0 * $w;
    $topicMap[$t]['count']++;

    $conceptMap[$c]['earned']   += $sc * $w;
    $conceptMap[$c]['possible'] += 1.0 * $w;
    $conceptMap[$c]['count']++;
}
arsort($topicMap);
arsort($conceptMap);
$topTopics = array_slice($topicMap, 0, 10, true);

// Question type performance
$typeMap = [];
foreach ($rows as $r) {
    $tp = $r['question_type'] ?? 'mc';
    if (!isset($typeMap[$tp])) $typeMap[$tp] = ['correct' => 0, 'total' => 0];
    $typeMap[$tp]['total']++;
    if (floatval($r['score']) >= 0.5) $typeMap[$tp]['correct']++;
}

// Summary stats
$totalCorrect = 0;
$totalOmitted = 0;
$totalWeightedEarned = 0.0;
$totalWeightedPossible = 0.0;
$totalTimeSec = 0;
$longestStreak = 0; $curStreak = 0;
foreach ($rows as $r) {
    $dl = strtolower(trim($r['dlevel'] ?? 'moderate'));
    $w  = ['easy'=>1.0,'moderate'=>1.2,'medium'=>1.2,'hard'=>1.5][$dl] ?? 1.2;
    $sc = floatval($r['score']);
    if (intval($r['omitted'] ?? 0)) { $totalOmitted++; }
    $totalWeightedEarned   += $sc * $w;
    $totalWeightedPossible += 1.0 * $w;
    $totalTimeSec += intval($r['time_taken']);
    if ($sc >= 0.5) {
        $totalCorrect++;
        $curStreak++;
        $longestStreak = max($longestStreak, $curStreak);
    } else {
        $curStreak = 0;
    }
}
$avgTimeSec  = count($rows) > 0 ? round($totalTimeSec / count($rows)) : 0;
$weightedPct = $totalWeightedPossible > 0
    ? round(($totalWeightedEarned / $totalWeightedPossible) * 100, 1)
    : 0;

// Duration format
$durH = floor($durationSec / 3600);
$durM = floor(($durationSec % 3600) / 60);
$durS = $durationSec % 60;
$durationStr = ($durH > 0 ? "{$durH}h " : '') . "{$durM}m {$durS}s";

$termMessages = [
    'pass_85'        => 'Auto-terminated: Achieved 85% Competency',
    'fail_impossible'=> 'Auto-terminated: Mathematically Impossible to Reach 85%',
    'completed_max'  => 'All ' . $totalItems . ' questions completed',
    'irt_pass'       => 'IRT 95% CI Confirmed Competency',
    'irt_fail'       => 'IRT 95% CI Confirmed Insufficient Competency',
    'manual_finish'  => 'Manually submitted by student',
    'pool_exhausted' => 'Question pool exhausted',
    'time_expired'   => '2-hour time limit reached — exam auto-submitted',
];
$termLabel = $termMessages[$termReason] ?? ucfirst(str_replace('_',' ',$termReason));

// Chart JSON
$thetaJs        = json_encode($thetaProgression);
$thetaLabelsJs  = json_encode(range(1, count($thetaProgression)));
$diffLabelsJs   = json_encode(array_keys($diffCounts));
$diffValuesJs   = json_encode(array_values($diffCounts));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CAT Exam Results — Studium</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root {
      --primary: #0a1628; --primary-light: #1e3a5f;
      --accent: #3b82f6; --success: #10b981; --danger: #ef4444; --warning: #f59e0b;
      --surface: #ffffff; --surface-alt: #f8fafc;
      --text: #0f172a; --text-muted: #64748b; --border: #e2e8f0; --radius: 16px;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', sans-serif; background: var(--surface-alt); color: var(--text); padding: 32px 20px; line-height: 1.5; }
    .container { max-width: 1200px; margin: 0 auto; }

    /* ── TOPBAR ── */
    .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
    .topbar h1 { font-size: 26px; font-weight: 900; letter-spacing: -.5px; }
    .topbar p  { color: var(--text-muted); font-size: 13px; margin-top: 2px; }
    .btn-back { background: #fff; border: 1px solid var(--border); color: var(--text); padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 7px; transition: all .2s; }
    .btn-back:hover { border-color: var(--accent); color: var(--accent); transform: translateY(-1px); }
    .btn-retry { background: linear-gradient(135deg, var(--accent), #2563eb); color: #fff; border: none; padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 7px; cursor: pointer; transition: all .2s; }
    .btn-retry:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(59,130,246,.3); }

    /* ── HERO BANNER ── */
    .hero {
      border-radius: 24px; padding: 40px 48px; margin-bottom: 28px;
      display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 28px;
      position: relative; overflow: hidden;
    }
    .hero.passed { background: linear-gradient(135deg, #064e3b 0%, #065f46 50%, #047857 100%); }
    .hero.failed { background: linear-gradient(135deg, #450a0a 0%, #7f1d1d 50%, #991b1b 100%); }
    .hero::before {
      content: ''; position: absolute; right: -60px; top: -60px;
      width: 280px; height: 280px; border-radius: 50%;
      background: rgba(255,255,255,.04);
    }
    .hero::after {
      content: ''; position: absolute; left: 40%; bottom: -80px;
      width: 220px; height: 220px; border-radius: 50%;
      background: rgba(255,255,255,.03);
    }
    .hero-left { position: relative; z-index: 1; }
    .hero-verdict { font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: rgba(255,255,255,.6); margin-bottom: 6px; }
    .hero-score  { font-size: 72px; font-weight: 900; color: #fff; line-height: 1; margin-bottom: 4px; }
    .hero-label  { font-size: 15px; color: rgba(255,255,255,.7); margin-bottom: 14px; }
    .hero-term   { display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15); padding: 7px 16px; border-radius: 100px; font-size: 12px; color: rgba(255,255,255,.85); font-weight: 600; }
    .hero-right  { position: relative; z-index: 1; display: flex; gap: 28px; flex-wrap: wrap; }
    .hero-stat   { text-align: center; }
    .hero-stat-val { font-size: 28px; font-weight: 900; color: #fff; line-height: 1; }
    .hero-stat-lbl { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: rgba(255,255,255,.55); margin-top: 4px; }

    /* ── STAT CARDS ROW ── */
    .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
    .stat-card { background: #fff; border: 1px solid var(--border); border-radius: var(--radius); padding: 20px 24px; }
    .stat-card-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 12px; }
    .stat-card-val  { font-size: 24px; font-weight: 800; color: var(--text); line-height: 1; margin-bottom: 4px; }
    .stat-card-lbl  { font-size: 12px; font-weight: 600; color: var(--text-muted); }

    /* ── CHART CARDS ── */
    .chart-row { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }
    .chart-row-3 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 28px; }
    .chart-card { background: #fff; border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; }
    .chart-card h3 { font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: .6px; color: var(--text-muted); margin-bottom: 18px; }
    .chart-wrap { position: relative; }

    /* ── BREAKDOWN TABLE ── */
    .table-section { background: #fff; border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
    .table-section h3 { font-size: 15px; font-weight: 800; padding: 20px 24px 16px; border-bottom: 1px solid var(--border); }
    .breakdown-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .breakdown-table th { background: var(--surface-alt); padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; color: var(--text-muted); border-bottom: 1px solid var(--border); white-space: nowrap; }
    .breakdown-table td { padding: 11px 14px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
    .breakdown-table tr:last-child td { border-bottom: none; }
    .breakdown-table tr.correct-row { background: #f0fdf4; }
    .breakdown-table tr.partial-row { background: #fffbeb; }
    .breakdown-table tr.wrong-row   { background: #fff1f2; }
    .score-pill { display: inline-block; padding: 3px 10px; border-radius: 100px; font-size: 11px; font-weight: 800; }
    .score-pill.correct { background: #dcfce7; color: #166534; }
    .score-pill.partial { background: #fef3c7; color: #92400e; }
    .score-pill.wrong   { background: #fee2e2; color: #991b1b; }
    .type-chip { background: #f1f5f9; color: #475569; padding: 3px 9px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .diff-chip { padding: 3px 9px; border-radius: 6px; font-size: 11px; font-weight: 700; }
    .diff-easy     { background: #dcfce7; color: #166534; }
    .diff-moderate,
    .diff-medium   { background: #fef3c7; color: #92400e; }
    .diff-hard     { background: #fee2e2; color: #991b1b; }
    .rationale-toggle { cursor: pointer; color: var(--accent); font-size: 11px; font-weight: 700; white-space: nowrap; }
    .rationale-row td { padding: 12px 14px; background: #f8fafc; border-bottom: 1px solid var(--border); font-size: 12px; color: var(--text-muted); line-height: 1.6; }

    /* ── THETA METER ── */
    .theta-meter { display: flex; align-items: center; gap: 10px; margin-top: 16px; }
    .theta-track { flex: 1; height: 8px; background: linear-gradient(90deg, #ef4444, #f59e0b, #10b981); border-radius: 100px; position: relative; }
    .theta-marker { position: absolute; top: 50%; transform: translate(-50%, -50%); width: 16px; height: 16px; background: #fff; border: 3px solid var(--primary); border-radius: 50%; box-shadow: 0 2px 6px rgba(0,0,0,.2); transition: left .5s; }
    .theta-label { font-size: 11px; font-weight: 700; color: var(--text-muted); }

    /* ── RESPONSIVE ── */
    @media (max-width: 900px) {
      .stats-row  { grid-template-columns: 1fr 1fr; }
      .chart-row  { grid-template-columns: 1fr; }
      .chart-row-3{ grid-template-columns: 1fr; }
    }
    @media (max-width: 600px) {
      .stats-row  { grid-template-columns: 1fr 1fr; }
      .hero { padding: 28px 24px; }
      .hero-score { font-size: 52px; }
    }
  </style>
</head>
<body>
<div class="container">

  <!-- TOPBAR -->
  <div class="topbar">
    <div>
      <h1>CAT Exam Results</h1>
      <p>Attempt #<?php echo $examTaken; ?> &nbsp;·&nbsp; <?php echo date('M d, Y \a\t h:i A', strtotime($examTime)); ?></p>
    </div>
    <div style="display:flex;gap:10px;">
      <a href="../index.php" class="btn-back"><i class="fas fa-home"></i> Dashboard</a>
      <a href="index.php" class="btn-retry"><i class="fas fa-rotate-right"></i> Retry Exam</a>
    </div>
  </div>

  <!-- HERO -->
  <div class="hero <?php echo $isPassed ? 'passed' : 'failed'; ?>">
    <div class="hero-left">
      <div class="hero-verdict"><?php echo $isPassed ? '✅ Examination Passed' : '❌ Examination Failed'; ?></div>
      <div class="hero-score"><?php echo round($finalPct ?? $weightedPct, 1); ?>%</div>
      <div class="hero-label">Weighted Competency Score</div>
      <div class="hero-term">
        <i class="fas fa-flag-checkered"></i>
        <?php echo htmlspecialchars($termLabel, ENT_QUOTES, 'UTF-8'); ?>
      </div>

      <!-- Theta meter -->
      <?php if ($finalTheta !== null): ?>
      <div class="theta-meter" style="margin-top:20px; max-width:360px;">
        <span class="theta-label" style="color:rgba(255,255,255,.5);">−4</span>
        <div class="theta-track" style="flex:1;">
          <?php $thetaPct = (($finalTheta + 4) / 8) * 100; $thetaPct = max(2, min(98, $thetaPct)); ?>
          <div class="theta-marker" style="left:<?php echo $thetaPct; ?>%;"></div>
        </div>
        <span class="theta-label" style="color:rgba(255,255,255,.5);">+4</span>
        <span style="font-size:12px; font-weight:800; color:rgba(255,255,255,.9); white-space:nowrap;">θ = <?php echo number_format($finalTheta, 3); ?></span>
      </div>
      <?php endif; ?>
    </div>
    <div class="hero-right">
      <div class="hero-stat">
        <div class="hero-stat-val"><?php echo $totalItems; ?></div>
        <div class="hero-stat-lbl">Items Answered</div>
      </div>
      <div class="hero-stat">
        <div class="hero-stat-val"><?php echo $totalCorrect; ?></div>
        <div class="hero-stat-lbl">Correct</div>
      </div>
      <div class="hero-stat">
        <div class="hero-stat-val"><?php echo $durationStr; ?></div>
        <div class="hero-stat-lbl">Duration</div>
      </div>
      <?php if ($finalSem !== null): ?>
      <div class="hero-stat">
        <div class="hero-stat-val"><?php echo number_format($finalSem, 3); ?></div>
        <div class="hero-stat-lbl">SEM</div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- STAT CARDS -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-card-icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-percent"></i></div>
      <div class="stat-card-val"><?php echo $weightedPct; ?>%</div>
      <div class="stat-card-lbl">Weighted Competency</div>
    </div>
    <div class="stat-card">
      <div class="stat-card-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-check"></i></div>
      <div class="stat-card-val"><?php echo $totalCorrect; ?> / <?php echo count($rows); ?></div>
      <div class="stat-card-lbl">Correct Answers</div>
    </div>
    <div class="stat-card">
      <div class="stat-card-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-stopwatch"></i></div>
      <div class="stat-card-val"><?php echo $avgTimeSec; ?>s</div>
      <div class="stat-card-lbl">Avg Time / Question</div>
    </div>
    <?php if ($totalOmitted > 0): ?>
    <div class="stat-card">
      <div class="stat-card-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-clock"></i></div>
      <div class="stat-card-val"><?php echo $totalOmitted; ?></div>
      <div class="stat-card-lbl">Timed Out (Omitted)</div>
    </div>
    <?php else: ?>
    <div class="stat-card">
      <div class="stat-card-icon" style="background:#f3e8ff;color:#7c3aed;"><i class="fas fa-fire"></i></div>
      <div class="stat-card-val"><?php echo $longestStreak; ?></div>
      <div class="stat-card-lbl">Longest Streak</div>
    </div>
    <?php endif; ?></div>

  <!-- CHARTS ROW 1: Theta Progression + Difficulty Pie -->
  <div class="chart-row">
    <div class="chart-card">
      <h3><i class="fas fa-chart-line" style="margin-right:6px;color:var(--accent);"></i>IRT Ability Estimate (θ) Progression</h3>
      <div class="chart-wrap" style="height:220px;">
        <canvas id="thetaChart"></canvas>
      </div>
      <div style="margin-top:12px; display:flex; align-items:center; gap:16px; font-size:11px; color:var(--text-muted);">
        <span><strong style="color:var(--text);">Passing logit (θ=0)</strong> — line above passing = strong candidate</span>
        <?php if ($finalTheta !== null): ?>
        <span style="margin-left:auto; font-weight:700; color:<?php echo $finalTheta >= 0 ? '#10b981':'#ef4444'; ?>;">
          Final θ = <?php echo number_format($finalTheta, 3); ?>
        </span>
        <?php endif; ?>
      </div>
    </div>
    <div class="chart-card">
      <h3><i class="fas fa-layer-group" style="margin-right:6px;color:var(--warning);"></i>Difficulty Distribution</h3>
      <div class="chart-wrap" style="height:220px; display:flex; align-items:center; justify-content:center;">
        <canvas id="diffChart" style="max-width:200px;"></canvas>
      </div>
    </div>
  </div>

  <!-- TOPIC AND CONCEPT PERFORMANCE BARS -->
  <style>
    .perf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 40px; }
    .perf-section { background: #fff; border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; }
    .perf-section h3 { font-size: 16px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
    
    .perf-item { margin-bottom: 20px; }
    .perf-item:last-child { margin-bottom: 0; }
    
    .perf-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 8px; }
    .perf-name { font-size: 14px; font-weight: 700; color: var(--text); flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 12px; }
    .perf-value { font-size: 15px; font-weight: 900; }
    
    .perf-track { height: 8px; background: var(--surface-alt); border-radius: 100px; overflow: hidden; position: relative; }
    .perf-fill { height: 100%; border-radius: 100px; transition: width 0.8s ease; }
    
    /* Performance Colors */
    .perf-strong .perf-fill { background: linear-gradient(90deg, #10b981, #059669); }
    .perf-strong .perf-value { color: #10b981; }
    
    .perf-average .perf-fill { background: linear-gradient(90deg, #f59e0b, #d97706); }
    .perf-average .perf-value { color: #f59e0b; }
    
    .perf-weak .perf-fill { background: linear-gradient(90deg, #ef4444, #dc2626); }
    .perf-weak .perf-value { color: #ef4444; }
    
    @media (max-width: 900px) { .perf-grid { grid-template-columns: 1fr; } }
  </style>

  <div class="perf-grid">
    <!-- Topics -->
    <div class="perf-section">
      <h3><i class="fas fa-book-open" style="color: var(--accent);"></i> Topic Performance</h3>
      <?php foreach ($topicMap as $name => $data):
        $pct = $data['possible'] > 0 ? round(($data['earned'] / $data['possible']) * 100) : 0;
        $class = $pct >= 85 ? 'perf-strong' : ($pct >= 60 ? 'perf-average' : 'perf-weak');
      ?>
      <div class="perf-item <?php echo $class; ?>">
        <div class="perf-header">
          <div class="perf-name" title="<?php echo $name; ?>"><?php echo $name; ?> <span style="color:var(--text-muted);font-weight:500;font-size:11px;"> (<?php echo $data['count']; ?> items)</span></div>
          <div class="perf-value"><?php echo $pct; ?>%</div>
        </div>
        <div class="perf-track">
          <div class="perf-fill" style="width: <?php echo $pct; ?>%;"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Concepts -->
    <div class="perf-section">
      <h3><i class="fas fa-lightbulb" style="color: var(--warning);"></i> Concept Performance</h3>
      <?php foreach ($conceptMap as $name => $data):
        $pct = $data['possible'] > 0 ? round(($data['earned'] / $data['possible']) * 100) : 0;
        $class = $pct >= 85 ? 'perf-strong' : ($pct >= 60 ? 'perf-average' : 'perf-weak');
      ?>
      <div class="perf-item <?php echo $class; ?>">
        <div class="perf-header">
          <div class="perf-name" title="<?php echo $name; ?>"><?php echo $name; ?> <span style="color:var(--text-muted);font-weight:500;font-size:11px;"> (<?php echo $data['count']; ?> items)</span></div>
          <div class="perf-value"><?php echo $pct; ?>%</div>
        </div>
        <div class="perf-track">
          <div class="perf-fill" style="width: <?php echo $pct; ?>%;"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- QUESTION-BY-QUESTION BREAKDOWN TABLE -->
  <div class="table-section" style="margin-bottom:40px;">
    <h3><i class="fas fa-list-ol" style="margin-right:8px;color:var(--accent);"></i>Question Breakdown</h3>
    <div style="overflow-x:auto;">
      <table class="breakdown-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Type</th>
            <th>Difficulty</th>
            <th>Score</th>
            <th>Result</th>
            <th>Time</th>
            <th>Topic</th>
            <th>θ After</th>
            <th>Rationale</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $i => $r):
            $sc   = floatval($r['score']);
            $omit = intval($r['omitted'] ?? 0);
            $rowClass = $omit ? 'wrong-row' : ($sc >= 1.0 ? 'correct-row' : ($sc >= 0.5 ? 'partial-row' : 'wrong-row'));
            $pillClass = $omit ? 'wrong' : ($sc >= 1.0 ? 'correct' : ($sc >= 0.5 ? 'partial' : 'wrong'));
            $pillLabel = $omit ? 'Omitted' : ($sc >= 1.0 ? 'Correct' : ($sc >= 0.5 ? 'Partial' : 'Wrong'));
            $dl = strtolower(trim($r['dlevel'] ?? 'moderate'));
            if ($dl === 'medium') $dl = 'moderate';
            $thetaAfter = isset($r['theta_after']) ? number_format(floatval($r['theta_after']), 3) : '—';
            $timeSec = intval($r['time_taken'] ?? 0);
            $timeStr = $timeSec >= 60 ? floor($timeSec/60).'m '.($timeSec%60).'s' : $timeSec.'s';
            $topicLabel = htmlspecialchars($r['topic'] ?? '—', ENT_QUOTES, 'UTF-8') ?: '—';
            $ratText    = htmlspecialchars($r['rationale'] ?? '', ENT_QUOTES, 'UTF-8');
            $ratId = 'rat-' . $i;
          ?>
          <tr class="<?php echo $rowClass; ?>">
            <td style="font-weight:700;color:var(--text-muted);"><?php echo intval($r['question_number'] ?? $i+1); ?></td>
            <td><span class="type-chip"><?php echo htmlspecialchars(strtoupper($r['question_type'] ?? 'MC'), ENT_QUOTES, 'UTF-8'); ?></span></td>
            <td><span class="diff-chip diff-<?php echo $dl; ?>"><?php echo ucfirst($dl); ?></span></td>
            <td style="font-weight:700;"><?php echo round($sc * 100); ?>%</td>
            <td><span class="score-pill <?php echo $pillClass; ?>"><?php echo $pillLabel; ?></span></td>
            <td style="color:var(--text-muted);"><?php echo $timeStr; ?></td>
            <td style="max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo $topicLabel; ?>"><?php echo $topicLabel; ?></td>
            <td style="font-family:monospace;font-size:12px;color:<?php echo floatval($r['theta_after'] ?? 0) >= 0 ? '#10b981' : '#ef4444'; ?>;"><?php echo $thetaAfter; ?></td>
            <td><?php if ($ratText): ?><span class="rationale-toggle" onclick="toggleRationale('<?php echo $ratId; ?>')"><i class="fas fa-book-open" style="margin-right:3px;"></i>Show</span><?php else: ?>—<?php endif; ?></td>
          </tr>
          <?php if ($ratText): ?>
          <tr id="<?php echo $ratId; ?>" style="display:none;" class="rationale-row">
            <td colspan="9"><?php echo $ratText; ?></td>
          </tr>
          <?php endif; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /container -->

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ── RATIONALE TOGGLE ──────────────────────────────────────────────────────────
function toggleRationale(id) {
  const row = document.getElementById(id);
  if (!row) return;
  const isHidden = row.style.display === 'none';
  row.style.display = isHidden ? 'table-row' : 'none';
  const btn = row.previousElementSibling?.querySelector('.rationale-toggle');
  if (btn) btn.innerHTML = isHidden
    ? '<i class="fas fa-chevron-up" style="margin-right:3px;"></i>Hide'
    : '<i class="fas fa-book-open" style="margin-right:3px;"></i>Show';
}

// ── CHART DATA ────────────────────────────────────────────────────────────────
const thetaData    = <?= $thetaJs ?>;
const thetaLabels  = <?= $thetaLabelsJs ?>;
const diffLabels   = <?= $diffLabelsJs ?>;
const diffValues   = <?= $diffValuesJs ?>;
// ── THETA PROGRESSION CHART ───────────────────────────────────────────────────
new Chart(document.getElementById('thetaChart'), {
  type: 'line',
  data: {
    labels: thetaLabels,
    datasets: [
      {
        label: 'θ (Ability)',
        data: thetaData,
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59,130,246,.08)',
        tension: 0.3,
        fill: true,
        pointRadius: thetaData.length <= 30 ? 4 : 2,
        pointBackgroundColor: thetaData.map(v => v >= 0 ? '#10b981' : '#ef4444'),
        borderWidth: 2,
      },
      {
        label: 'Passing (θ=0)',
        data: thetaLabels.map(() => 0),
        borderColor: 'rgba(100,116,139,.4)',
        borderDash: [5, 5],
        pointRadius: 0,
        borderWidth: 1.5,
        fill: false,
      }
    ]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend: { position: 'top', labels: { font: { size: 11 }, boxWidth: 14 } },
      tooltip: {
        callbacks: {
          label: ctx => ` θ = ${ctx.parsed.y.toFixed(3)}`,
        }
      }
    },
    scales: {
      x: { title: { display: true, text: 'Question Number', font: { size: 11 } },
           ticks: { font: { size: 10 } } },
      y: { min: -4, max: 4,
           title: { display: true, text: 'Ability Estimate (θ)', font: { size: 11 } },
           ticks: { font: { size: 10 } },
           grid: { color: 'rgba(0,0,0,.04)' } }
    }
  }
});

// ── DIFFICULTY PIE ────────────────────────────────────────────────────────────
new Chart(document.getElementById('diffChart'), {
  type: 'doughnut',
  data: {
    labels: diffLabels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
    datasets: [{
      data: diffValues,
      backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
      borderWidth: 0, cutout: '72%', borderRadius: 6,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: true,
    plugins: {
      legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 14, boxWidth: 12 } },
    }
  }
});


// ── AUTO-ANNOUNCE RESULT (once on page load) ──────────────────────────────────
<?php if (isset($_GET['finish']) && $_GET['finish'] == '1'): ?>
const isPassed = <?= $isPassed ? 'true' : 'false' ?>;
const finalPct = <?= round($finalPct ?? $weightedPct, 1) ?>;
setTimeout(() => {
  Swal.fire({
    icon:   isPassed ? 'success' : 'error',
    title:  isPassed ? '🎉 PASSED!' : '❌ FAILED',
    html:   `<strong style="font-size:22px;">${finalPct}%</strong><br><small style="color:#64748b;"><?= htmlspecialchars(addslashes($termLabel), ENT_QUOTES, 'UTF-8') ?></small>`,
    confirmButtonColor: isPassed ? '#10b981' : '#ef4444',
    confirmButtonText: 'View Details',
    timer: 6000,
    timerProgressBar: true,
  });
}, 400);
<?php endif; ?>
</script>
</body>
</html>
