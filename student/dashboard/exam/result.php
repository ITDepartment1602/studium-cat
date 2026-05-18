<?php
// Exam Mode — Result Page (NCSBN-compliant: PASSED/FAILED + CPR for failed only)
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

// ─── TERMINAL ROW ONLY — no full question list needed ─────────────────────────
$terminal = db()->fetchOne(
    "SELECT * FROM exammoderesults WHERE student_id = ? AND examTaken = ? AND is_terminal = 1 LIMIT 1",
    [$student_id, $examTaken]
);
if (!$terminal) {
    // Fallback: last row
    $terminal = db()->fetchOne(
        "SELECT * FROM exammoderesults WHERE student_id = ? AND examTaken = ? ORDER BY question_number DESC LIMIT 1",
        [$student_id, $examTaken]
    );
}

$finalResult  = $terminal['final_result']        ?? null;
// Use final_theta; fall back to theta_after (from save_history.php rows)
$finalTheta   = isset($terminal['final_theta'])  && $terminal['final_theta'] !== null
                ? floatval($terminal['final_theta'])
                : (isset($terminal['theta_after']) ? floatval($terminal['theta_after']) : null);
$termReason   = $terminal['termination_reason']   ?? 'manual_finish';
// total_items_answered from terminal row, else count rows for this exam
$totalItems   = intval($terminal['total_items_answered'] ?? 0);
if ($totalItems === 0) {
    $cnt = db()->fetchOne(
        "SELECT COUNT(*) AS c FROM exammoderesults WHERE student_id = ? AND examTaken = ?",
        [$student_id, $examTaken]
    );
    $totalItems = intval($cnt['c'] ?? $terminal['question_number'] ?? 0);
}
// exam_duration_sec from terminal row, else totalTime (elapsed seconds stored by save_history.php)
$durationSec  = intval($terminal['exam_duration_sec'] ?? $terminal['totalTime'] ?? 0);
$examTime     = $terminal['timestamp']            ?? date('Y-m-d H:i:s');

// Derive result: prefer stored final_result, then termination_reason, then theta comparison
if ($finalResult === null) {
    $termPass = ['irt_pass'];
    $termFail = ['irt_fail', 'time_expired_insufficient'];
    if (in_array($termReason, $termPass, true)) {
        $finalResult = 'PASSED';
    } elseif (in_array($termReason, $termFail, true)) {
        $finalResult = 'FAILED';
    } else {
        // Last resort: use theta vs PASSING_LOGIT = 0.0
        $finalResult = ($finalTheta !== null && $finalTheta > 0.0) ? 'PASSED' : 'FAILED';
    }
}
$isPassed = ($finalResult === 'PASSED');

// Duration format — hide if zero
$durationStr = '';
if ($durationSec > 0) {
    $durH = floor($durationSec / 3600);
    $durM = floor(($durationSec % 3600) / 60);
    $durS = $durationSec % 60;
    $durationStr = ($durH > 0 ? "{$durH}h " : '') . "{$durM}m {$durS}s";
}

// ─── CPR (Candidate Performance Report) — FAILED candidates only ──────────────
$cprGrouped = [];   // concept → [topics]
$selectedConcepts = [];

if (!$isPassed) {
    // Fetch selected concepts from terminal row (may be null for legacy exams)
    if (!empty($terminal['selected_concepts'])) {
        $selectedConcepts = json_decode($terminal['selected_concepts'], true) ?: [];
    }

    // Topic-level CPR: group by concept (system) → topic (cnc)
    $cprRows = db()->fetchAll(
        "SELECT system AS concept, cnc AS topic,
                SUM(earned_points) AS earned,
                SUM(max_points)    AS max_pts,
                COUNT(*) AS items
         FROM   exammoderesults
         WHERE  student_id = ? AND examTaken = ?
           AND  cnc IS NOT NULL AND cnc <> ''
         GROUP BY system, cnc
         ORDER BY system ASC, cnc ASC",
        [$student_id, $examTaken]
    );

    foreach ($cprRows as $row) {
        $concept = !empty($row['concept']) ? $row['concept'] : 'General';
        $cprGrouped[$concept][] = $row;
    }

    // Filter to selected concepts if available
    if (!empty($selectedConcepts)) {
        $cprGrouped = array_intersect_key($cprGrouped, array_flip($selectedConcepts));
    }

    // Fallback: use cnc-only grouping if no system-level data
    if (empty($cprGrouped)) {
        $cprFallback = db()->fetchAll(
            "SELECT cnc AS topic, SUM(earned_points) AS earned, SUM(max_points) AS max_pts, COUNT(*) AS items
             FROM exammoderesults WHERE student_id = ? AND examTaken = ? AND cnc IS NOT NULL AND cnc <> ''
             GROUP BY cnc ORDER BY cnc ASC",
            [$student_id, $examTaken]
        );
        foreach ($cprFallback as $row) {
            $cprGrouped['Content Areas'][] = $row;
        }
    }
}

function getCprLabel(float $ratio): array {
    if ($ratio >= 0.65) return ['label' => 'Above Passing Standard', 'class' => 'above', 'color' => '#10b981'];
    if ($ratio >= 0.35) return ['label' => 'Near Passing Standard',  'class' => 'near',  'color' => '#f59e0b'];
    return                      ['label' => 'Below Passing Standard', 'class' => 'below', 'color' => '#ef4444'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NCLEX Examination Results — Studium</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root {
      --success: #10b981; --danger: #ef4444; --warning: #f59e0b;
      --surface: #ffffff; --surface-alt: #f8fafc;
      --text: #0f172a; --text-muted: #64748b; --border: #e2e8f0;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', sans-serif; background: var(--surface-alt); color: var(--text); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 32px 20px; }
    .page { max-width: 680px; width: 100%; }

    /* TOPBAR */
    .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
    .topbar-title { font-size: 14px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }
    .btn-back { background: #fff; border: 1px solid var(--border); color: var(--text); padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 7px; transition: all .2s; }
    .btn-back:hover { border-color: #3b82f6; color: #3b82f6; transform: translateY(-1px); }

    /* HERO BANNER */
    .hero { border-radius: 24px; padding: 48px 40px; margin-bottom: 24px; text-align: center; position: relative; overflow: hidden; }
    .hero.passed { background: linear-gradient(135deg, #064e3b 0%, #065f46 50%, #047857 100%); }
    .hero.failed { background: linear-gradient(135deg, #450a0a 0%, #7f1d1d 50%, #991b1b 100%); }
    .hero::before { content: ''; position: absolute; right: -80px; top: -80px; width: 300px; height: 300px; border-radius: 50%; background: rgba(255,255,255,.04); }
    .hero::after  { content: ''; position: absolute; left: -60px; bottom: -60px; width: 240px; height: 240px; border-radius: 50%; background: rgba(255,255,255,.03); }
    .hero-icon { font-size: 64px; margin-bottom: 16px; position: relative; z-index: 1; line-height: 1; }
    .hero-verdict { font-size: 36px; font-weight: 900; color: #fff; letter-spacing: -0.5px; position: relative; z-index: 1; margin-bottom: 8px; }
    .hero-sub { font-size: 15px; color: rgba(255,255,255,.65); position: relative; z-index: 1; font-weight: 500; }

    /* STATS ROW */
    .stats-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
    .stat-card { background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 24px; text-align: center; }
    .stat-val { font-size: 28px; font-weight: 900; color: var(--text); line-height: 1; margin-bottom: 6px; }
    .stat-lbl { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--text-muted); }

    /* CPR SECTION */
    .cpr-section { background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 28px; margin-bottom: 24px; animation: fadeSlide 0.5s ease 0.2s both; }
    @keyframes fadeSlide { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
    .cpr-title { font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: .6px; color: var(--text-muted); margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
    .cpr-desc { font-size: 13px; color: var(--text-muted); margin-bottom: 24px; line-height: 1.6; }
    .cpr-concept-header { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-muted); padding: 14px 0 8px; border-bottom: 1px solid var(--surface-alt); margin-bottom: 4px; }
    .cpr-row { padding: 14px 0; border-bottom: 1px solid var(--surface-alt); }
    .cpr-row:last-child { border-bottom: none; }
    .cpr-row-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 8px; }
    .cpr-area { font-size: 13.5px; font-weight: 600; color: var(--text); flex: 1; line-height: 1.3; }
    .cpr-pct { font-size: 12px; font-weight: 700; color: var(--text-muted); min-width: 38px; text-align: right; }
    .cpr-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 100px; font-size: 11px; font-weight: 800; white-space: nowrap; }
    .cpr-badge.above { background: #dcfce7; color: #166534; }
    .cpr-badge.near  { background: #fef3c7; color: #92400e; }
    .cpr-badge.below { background: #fee2e2; color: #991b1b; }
    .cpr-bar-track { height: 6px; background: #f1f5f9; border-radius: 4px; overflow: hidden; }
    .cpr-bar-fill { height: 100%; border-radius: 4px; width: 0%; transition: width 0.8s ease-out; }
    .cpr-bar-fill.above { background: linear-gradient(90deg, #10b981, #059669); }
    .cpr-bar-fill.near  { background: linear-gradient(90deg, #f59e0b, #d97706); }
    .cpr-bar-fill.below { background: linear-gradient(90deg, #ef4444, #dc2626); }
    .cpr-no-data { color: var(--text-muted); font-size: 13px; font-style: italic; }
    .cpr-items { font-size: 11px; color: var(--text-muted); margin-top: 4px; }

    /* FOOTER NOTE */
    .notice { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 16px 20px; font-size: 12px; color: var(--text-muted); line-height: 1.6; text-align: center; margin-bottom: 24px; }
    .notice i { color: #64748b; margin-right: 6px; }

    @media (max-width: 480px) {
      .hero { padding: 36px 24px; }
      .hero-verdict { font-size: 28px; }
      .stats-row { grid-template-columns: 1fr 1fr; }
    }
  </style>
</head>
<body>
<div class="page">

  <!-- TOPBAR -->
  <div class="topbar">
    <div class="topbar-title">NCLEX Examination Results</div>
    <a href="../index.php" class="btn-back"><i class="fas fa-home"></i> Dashboard</a>
  </div>

  <!-- HERO BANNER -->
  <div class="hero <?= $isPassed ? 'passed' : 'failed' ?>">
    <div class="hero-icon"><?= $isPassed ? '✅' : '❌' ?></div>
    <div class="hero-verdict"><?= $isPassed ? 'PASSED' : 'FAILED' ?></div>
    <div class="hero-sub">
      <?= $isPassed
        ? 'Congratulations — You have passed the NCLEX examination.'
        : 'You have not passed the NCLEX examination at this time.' ?>
    </div>
  </div>

  <!-- STATS -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-val"><?= $totalItems ?></div>
      <div class="stat-lbl">Items Answered</div>
    </div>
    <div class="stat-card">
      <div class="stat-val"><?= $durationStr ?: '—' ?></div>
      <div class="stat-lbl">Exam Duration</div>
    </div>
  </div>

  <?php if (!$isPassed): ?>
  <!-- CPR — FAILED candidates only -->
  <div class="cpr-section">
    <div class="cpr-title"><i class="fas fa-chart-bar"></i> Candidate Performance Report</div>
    <div class="cpr-desc">
      The following report shows your performance broken down by topic area.
      Use this to identify where to focus your study before retaking the examination.
      <?php if (!empty($selectedConcepts)): ?>
        <br><strong>Concepts selected:</strong> <?= implode(' · ', array_map('htmlspecialchars', $selectedConcepts)) ?>
      <?php endif; ?>
    </div>

    <?php if (!empty($cprGrouped)): ?>
      <?php $conceptCount = count($cprGrouped); $cIndex = 0; ?>
      <?php foreach ($cprGrouped as $conceptName => $topicRows): ?>
        <?php if ($conceptCount > 1): ?>
        <div class="cpr-concept-header"><?= htmlspecialchars($conceptName) ?></div>
        <?php endif; ?>
        <?php foreach ($topicRows as $i => $row):
          $earned = floatval($row['earned']);
          $max    = floatval($row['max_pts']);
          $ratio  = $max > 0 ? $earned / $max : 0.0;
          $cpr    = getCprLabel($ratio);
          $pct    = round($ratio * 100);
          $area   = htmlspecialchars($row['topic'], ENT_QUOTES, 'UTF-8');
          $items  = intval($row['items']);
          $delay  = ($cIndex * 80); $cIndex++;
        ?>
        <div class="cpr-row" role="listitem">
          <div class="cpr-row-top">
            <div class="cpr-area"><?= $area ?></div>
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
              <span class="cpr-pct"><?= $pct ?>%</span>
              <span class="cpr-badge <?= $cpr['class'] ?>">
                <?php if ($cpr['class'] === 'above'): ?><i class="fas fa-arrow-up"></i>
                <?php elseif ($cpr['class'] === 'near'): ?><i class="fas fa-minus"></i>
                <?php else: ?><i class="fas fa-arrow-down"></i><?php endif; ?>
                <?= $cpr['label'] ?>
              </span>
            </div>
          </div>
          <div class="cpr-bar-track" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="cpr-bar-fill <?= $cpr['class'] ?>" data-w="<?= $pct ?>" style="transition-delay:<?= $delay ?>ms;"></div>
          </div>
          <div class="cpr-items"><?= $items ?> item<?= $items !== 1 ? 's' : '' ?> answered</div>
        </div>
        <?php endforeach; ?>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="cpr-no-data">Content area data is not available for this attempt.</p>
    <?php endif; ?>
  </div>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    requestAnimationFrame(function() {
      document.querySelectorAll('.cpr-bar-fill[data-w]').forEach(function(bar) {
        setTimeout(function() { bar.style.width = bar.dataset.w + '%'; }, parseInt(bar.style.transitionDelay) || 0);
      });
    });
  });
  </script>
  <?php endif; ?>

  <!-- NOTICE -->
  <div class="notice">
    <i class="fas fa-lock"></i>
    In accordance with NCSBN policy, specific exam content, correct answers, and scoring details
    are not disclosed after examination completion.
  </div>

</div><!-- /page -->

<script>
// Prevent back-navigation to the exam
window.history.pushState(null, '', window.location.href);
window.addEventListener('popstate', function() {
  window.history.pushState(null, '', window.location.href);
});
</script>
</body>
</html>
