<?php
// Config handles DB connection + session start
include '../../config.php';

// Only show errors locally; hide on production
$isProduction = !in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']);
if (!$isProduction) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Auto-create temporary_exam_state table if missing
mysqli_query($con, "
    CREATE TABLE IF NOT EXISTS `temporary_exam_state` (
        `student_id` int(11) NOT NULL,
        `examTaken` int(11) NOT NULL,
        `question_set` text NOT NULL,
        `current_question` int(11) NOT NULL DEFAULT 0,
        `timer` int(11) NOT NULL DEFAULT 0,
        `updated_at` datetime NOT NULL,
        PRIMARY KEY (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

// Clear stale NGN exam sessions when returning to dashboard
if (isset($_SESSION['current_ngn_examTaken'])) unset($_SESSION['current_ngn_examTaken']);
if (isset($_SESSION['ngn_exam_set']))           unset($_SESSION['ngn_exam_set']);

// Stats queries
$quizCon = getQuizConnection();
mysqli_query($quizCon, "SET time_zone = '+08:00'");

$r = mysqli_query($quizCon, "SELECT COUNT(*) as total FROM question");
$totalQ = ($r && $row = mysqli_fetch_assoc($r)) ? (int)$row['total'] : 0;

$r = mysqli_query($quizCon, "SELECT COUNT(DISTINCT questionId) as used FROM review WHERE studentId = '$user_id'");
$usedQ = ($r && $row = mysqli_fetch_assoc($r)) ? (int)$row['used'] : 0;
$unusedQ = $totalQ - $usedQ;

$r = mysqli_query($quizCon, "SELECT COUNT(*) as correct FROM review WHERE studentId = '$user_id' AND ans = correctAns");
$correct = ($r && $row = mysqli_fetch_assoc($r)) ? (int)$row['correct'] : 0;

$r = mysqli_query($quizCon, "SELECT COUNT(*) as wrong FROM review WHERE studentId = '$user_id' AND ans != correctAns");
$wrong = ($r && $row = mysqli_fetch_assoc($r)) ? (int)$row['wrong'] : 0;

$usedPercent    = ($totalQ > 0)             ? round(($usedQ / $totalQ) * 100) : 0;
$correctPercent = ($correct + $wrong > 0)   ? round(($correct / ($correct + $wrong)) * 100) : 0;

// Traditional exam stats (from history table)
$tradQ = mysqli_query($quizCon, "SELECT COUNT(*) as exams, AVG(score) as avg_score, MAX(score) as best, SUM(sahi) as total_correct, SUM(wrong) as total_wrong FROM `history` WHERE email = '$user_id' AND kilanlan = 'NARC Intermediate and Advance QBanks'");
$tradRow      = $tradQ ? mysqli_fetch_assoc($tradQ) : [];
$tradExams    = (int)($tradRow['exams'] ?? 0);
$tradAvgScore = $tradExams > 0 ? round($tradRow['avg_score']) : 0;
$tradBest     = $tradExams > 0 ? round($tradRow['best']) : 0;
$tradCorrect  = (int)($tradRow['total_correct'] ?? 0);
$tradWrong    = (int)($tradRow['total_wrong'] ?? 0);
$tradAccuracy = ($tradCorrect + $tradWrong > 0) ? round($tradCorrect / ($tradCorrect + $tradWrong) * 100) : 0;

// NGN exam stats (from exam_results table — main DB via db())
$ngnStats   = db()->fetchOne("SELECT COUNT(*) as total, SUM(isCorrect) as correct, COUNT(DISTINCT examTaken) as exams FROM exam_results WHERE student_id = ?", [$user_id]);
$ngnTotal   = (int)($ngnStats['total'] ?? 0);
$ngnCorrect = (int)($ngnStats['correct'] ?? 0);
$ngnExams   = (int)($ngnStats['exams'] ?? 0);
$ngnWrong   = $ngnTotal - $ngnCorrect;
$ngnAccuracy = ($ngnTotal > 0) ? round(($ngnCorrect / $ngnTotal) * 100) : 0;

// ── Overall combined accuracy (questions only — usage computed after ngnUsedQ below) ──
$overallCorrect  = $correct + $ngnCorrect;
$overallWrong    = $wrong   + $ngnWrong;
$overallAccuracy = ($overallCorrect + $overallWrong > 0)
    ? round($overallCorrect / ($overallCorrect + $overallWrong) * 100) : 0;

// ── NCLEX Readiness: last 3 exam scores per concept ──
$readinessConceptMap = [
    'Adult Health'               => 'bi bi-person-fill',
    'Child Health'               => 'bi bi-emoji-smile-fill',
    'Critical Care'              => 'bi bi-heart-pulse-fill',
    'Fundamentals'               => 'bi bi-gear-fill',
    'Leadership and Management'  => 'bi bi-people-fill',
    'Mental Health'              => 'bi bi-lightbulb-fill',
    'Pharmacology'               => 'bi bi-capsule',
    'Maternal and Newborn Health'=> 'bi bi-gender-female',
];
$conceptReadiness = [];
foreach (array_keys($readinessConceptMap) as $concept) {
    $rq = mysqli_query($quizCon, "SELECT score FROM `history` WHERE email = '$user_id' AND eid = '$concept' AND kilanlan = 'NARC Intermediate and Advance QBanks' ORDER BY date DESC LIMIT 3");
    $scores = [];
    while ($rr = mysqli_fetch_assoc($rq)) { $scores[] = (float)$rr['score']; }
    $avg    = count($scores) > 0 ? round(array_sum($scores) / count($scores)) : 0;
    $status = count($scores) === 0 ? 'none' : ($avg >= 75 ? 'ready' : ($avg >= 60 ? 'ontrack' : 'needs'));
    $conceptReadiness[$concept] = ['scores' => $scores, 'avg' => $avg, 'status' => $status, 'attempts' => count($scores)];
}
$readyCount     = count(array_filter($conceptReadiness, fn($c) => $c['status'] === 'ready'));
$attemptedCount = count(array_filter($conceptReadiness, fn($c) => $c['attempts'] > 0));
$totalConceptCount = count($conceptReadiness);
if ($attemptedCount === 0) {
    $overallReadiness = 'none';
} elseif ($readyCount === $totalConceptCount) {
    $overallReadiness = 'ready';
} elseif ($readyCount >= 5) {
    $overallReadiness = 'ontrack';
} else {
    $overallReadiness = 'needs';
}

// ── NGN question bank constants ──
$ngnBankTotal = 32;
$ngnBankConcept = [
    'Critical Care'          => 6,
    'Endocrine'              => 1,
    'Fluid and Electrolytes' => 2,
    'Fundamentals'           => 3,
    'Heart Failure'          => 6,
    'Pharmacology'           => 9,
    'Respiratory'            => 5,
];
$ngnBankTopic = [
    'Septic Shock'                          => 6,
    'Diabetic Ketoacidosis'                 => 1,
    'Fluid and Electrolyte Imbalances'      => 2,
    'Postoperative Care'                    => 2,
    'Skills/Procedures'                     => 1,
    'Acute Decompensated Heart Failure'     => 6,
    'Contrast-Induced Nephropathy Prevention' => 4,
    'Cardiac Medications'                   => 5,
    'Pulmonary Embolism'                    => 5,
];

// NGN questions used by this student
$ngnUsedRow  = db()->fetchOne("SELECT COUNT(DISTINCT question_uid) as used FROM exam_results WHERE student_id = ?", [$user_id]);
$ngnUsedQ    = (int)($ngnUsedRow['used'] ?? 0);
$ngnUnusedQ  = $ngnBankTotal - $ngnUsedQ;
$ngnUsedPct  = $ngnBankTotal > 0 ? round($ngnUsedQ / $ngnBankTotal * 100) : 0;

// ── Overall Questions Usage = Traditional bank + NGN bank combined ──
// Formula: (trad used + NGN used) / (trad total bank + NGN total bank) × 100
$overallUsedQ   = $usedQ + $ngnUsedQ;
$overallTotalQ  = $totalQ + $ngnBankTotal;
$overallUnusedQ = $overallTotalQ - $overallUsedQ;
$overallUsedPct = $overallTotalQ > 0 ? round($overallUsedQ / $overallTotalQ * 100) : 0;

// ── NGN Readiness per concept ──
$ngnConceptMap = [
    'Critical Care'          => 'bi bi-heart-pulse-fill',
    'Endocrine'              => 'bi bi-capsule',
    'Fluid and Electrolytes' => 'bi bi-droplet-fill',
    'Fundamentals'           => 'bi bi-gear-fill',
    'Heart Failure'          => 'bi bi-heartbreak-fill',
    'Pharmacology'           => 'bi bi-prescription2',
    'Respiratory'            => 'bi bi-wind',
];
$ngnConceptReadiness = [];
foreach (array_keys($ngnConceptMap) as $concept) {
    $rows = db()->fetchAll(
        "SELECT examTaken, SUM(isCorrect) as correct, COUNT(*) as total
         FROM exam_results WHERE student_id = ? AND concept = ?
         GROUP BY examTaken ORDER BY MIN(timestamp) DESC LIMIT 3",
        [$user_id, $concept]
    );
    $scores = [];
    foreach ($rows as $row) {
        $scores[] = $row['total'] > 0 ? round(($row['correct'] / $row['total']) * 100) : 0;
    }
    $avg    = count($scores) > 0 ? round(array_sum($scores) / count($scores)) : 0;
    $status = count($scores) === 0 ? 'none' : ($avg >= 75 ? 'ready' : ($avg >= 60 ? 'ontrack' : 'needs'));
    $ngnConceptReadiness[$concept] = ['scores' => $scores, 'avg' => $avg, 'status' => $status, 'attempts' => count($scores)];
}
$ngnReadyCount        = count(array_filter($ngnConceptReadiness, fn($c) => $c['status'] === 'ready'));
$ngnAttemptedCount    = count(array_filter($ngnConceptReadiness, fn($c) => $c['attempts'] > 0));
$ngnTotalConceptCount = count($ngnConceptReadiness);
$ngnOverallReadiness  = $ngnAttemptedCount === 0 ? 'none'
    : ($ngnReadyCount === $ngnTotalConceptCount ? 'ready'
    : ($ngnReadyCount >= 4 ? 'ontrack' : 'needs'));

// NGN concept → topics mapping (for Topics dropdown filtering)
$ngnConceptTopics = [];
$_ngn_tables = ['btq','dragndrop','dropdown','highlight','mmr','mpr','sata','traditional'];
$_ngn_db = db()->getConnection();
foreach ($_ngn_tables as $_t) {
    $_r = $_ngn_db->query("SELECT DISTINCT concept, topic FROM `$_t` WHERE concept IS NOT NULL AND concept != '' AND topic IS NOT NULL AND topic != ''");
    if (!$_r) continue;
    while ($_row = $_r->fetch_assoc()) {
        $ngnConceptTopics[$_row['concept']][] = $_row['topic'];
    }
}
foreach ($ngnConceptTopics as $_c => $_ts) {
    $ngnConceptTopics[$_c] = array_values(array_unique($_ts));
    sort($ngnConceptTopics[$_c]);
}

// Traditional concept → topics mapping (for Topics dropdown filtering)
$tradConceptTopics = [];
foreach (array_keys($readinessConceptMap) as $_tc) {
    $_tce = mysqli_real_escape_string($quizCon, $_tc);
    $_tq  = mysqli_query($quizCon, "SELECT DISTINCT system FROM question WHERE topics1 = '$_tce' AND system IS NOT NULL AND system != '' ORDER BY system ASC");
    $_ts  = [];
    while ($_tr = mysqli_fetch_assoc($_tq)) { $_ts[] = $_tr['system']; }
    $tradConceptTopics[$_tc] = $_ts;
}

// NGN all-time avg per concept (for line chart)
$ngnConceptAvgs = [];
foreach (array_keys($ngnConceptMap) as $concept) {
    $r2 = db()->fetchOne(
        "SELECT SUM(isCorrect) as correct, COUNT(*) as total FROM exam_results WHERE student_id = ? AND concept = ?",
        [$user_id, $concept]
    );
    $ngnConceptAvgs[$concept] = ($r2 && $r2['total'] > 0) ? round(($r2['correct'] / $r2['total']) * 100) : 0;
}

// Traditional all-time avg per concept (for line chart, pre-computed to avoid inline JS queries)
$tradChartAvgs = [];
foreach (array_keys($readinessConceptMap) as $concept) {
    $qt = mysqli_query($quizCon, "SELECT AVG(score) as avg FROM `history` WHERE email = '$user_id' AND eid = '" . mysqli_real_escape_string($quizCon, $concept) . "' AND kilanlan = 'NARC Intermediate and Advance QBanks'");
    $rt = mysqli_fetch_assoc($qt);
    $tradChartAvgs[$concept] = $rt && $rt['avg'] !== null ? round($rt['avg']) : 0;
}

// ── Overall merged readiness (Trad + NGN combined) ──
$overallConceptIconMap = array_merge($ngnConceptMap, $readinessConceptMap); // trad wins overlap

// Raw correct/total from history per trad concept
$tradRaw = [];
foreach (array_keys($readinessConceptMap) as $concept) {
    $_ce = mysqli_real_escape_string($quizCon, $concept);
    $rr  = mysqli_fetch_assoc(mysqli_query($quizCon,
        "SELECT SUM(sahi) as correct, SUM(sahi+wrong) as total FROM history
         WHERE email='$user_id' AND eid='$_ce' AND kilanlan='NARC Intermediate and Advance QBanks'"
    ));
    $tradRaw[$concept] = ['correct' => (int)($rr['correct'] ?? 0), 'total' => (int)($rr['total'] ?? 0)];
}

// Build overall concept readiness — trad concepts first (weighted with NGN if shared)
$overallConceptReadiness = [];
foreach ($readinessConceptMap as $concept => $icon) {
    $tC = $tradRaw[$concept]['correct']; $tT = $tradRaw[$concept]['total'];
    $nC = 0; $nT = 0;
    if (isset($ngnConceptReadiness[$concept])) {
        $nRow = db()->fetchOne("SELECT SUM(isCorrect) as correct, COUNT(*) as total FROM exam_results WHERE student_id = ? AND concept = ?", [$user_id, $concept]);
        $nC = (int)($nRow['correct'] ?? 0); $nT = (int)($nRow['total'] ?? 0);
    }
    $cTotal = $tT + $nT; $cCorrect = $tC + $nC;
    $avg    = $cTotal > 0 ? round($cCorrect / $cTotal * 100) : 0;
    $status = $cTotal > 0 ? ($avg >= 75 ? 'ready' : ($avg >= 60 ? 'ontrack' : 'needs')) : 'none';
    $scores = count($conceptReadiness[$concept]['scores']) > 0
        ? $conceptReadiness[$concept]['scores']
        : (isset($ngnConceptReadiness[$concept]) ? $ngnConceptReadiness[$concept]['scores'] : []);
    $overallConceptReadiness[$concept] = [
        'scores'   => $scores,
        'avg'      => $avg,
        'status'   => $status,
        'attempts' => $conceptReadiness[$concept]['attempts'] + (isset($ngnConceptReadiness[$concept]) ? $ngnConceptReadiness[$concept]['attempts'] : 0),
    ];
}
// Append NGN-only concepts
foreach ($ngnConceptReadiness as $concept => $data) {
    if (!isset($overallConceptReadiness[$concept])) $overallConceptReadiness[$concept] = $data;
}

$overallReadyCount        = count(array_filter($overallConceptReadiness, fn($c) => $c['status'] === 'ready'));
$overallTotalCount        = count($overallConceptReadiness);
$overallAttempted         = count(array_filter($overallConceptReadiness, fn($c) => $c['attempts'] > 0));
$overallCombinedReadiness = $overallAttempted === 0 ? 'none'
    : ($overallReadyCount === $overallTotalCount ? 'ready'
    : ($overallReadyCount >= 5 ? 'ontrack' : 'needs'));

// ── Overall chart averages: trad concepts weighted with NGN, then NGN-only appended ──
$overallChartAvgs = [];
foreach (array_keys($readinessConceptMap) as $concept) {
    $tC2  = $tradRaw[$concept]['correct']; $tT2 = $tradRaw[$concept]['total'];
    $nRow2 = db()->fetchOne("SELECT SUM(isCorrect) as correct, COUNT(*) as total FROM exam_results WHERE student_id = ? AND concept = ?", [$user_id, $concept]);
    $nC2  = (int)($nRow2['correct'] ?? 0); $nT2 = (int)($nRow2['total'] ?? 0);
    $ct2  = $tT2 + $nT2;
    $overallChartAvgs[$concept] = $ct2 > 0 ? round(($tC2 + $nC2) / $ct2 * 100) : 0;
}
foreach (array_keys($ngnConceptMap) as $concept) {
    if (!isset($overallChartAvgs[$concept])) $overallChartAvgs[$concept] = $ngnConceptAvgs[$concept] ?? 0;
}

// Update last login
$result = mysqli_query($con, "UPDATE login SET lastlogin = NOW(), loginstatus = 'Active now' WHERE id = " . intval($user_id));

// Fetch user for bundle check (used in practice mode section)
$select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'");
$fetch  = mysqli_fetch_assoc($select);

$pageTitle = 'Dashboard — Studium';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include '_layout/head.php'; ?>
  <!-- ECharts — single charting library for donuts + line chart -->
  <script src="https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js"></script>
</head>
<body>

<?php include '_layout/sidebar.php'; ?>

<!-- ══ Main Content ══ -->
<main class="s-main">

  <?php
  // Paused exam checks (moved here for use in practice cards)
  $ngnStateCheck    = mysqli_query($quizCon, "SELECT * FROM temporary_exam_state WHERE student_id = '" . intval($user_id) . "' AND (exam_mode IS NULL OR exam_mode = 'ngn')");
  $hasPausedNGN     = mysqli_num_rows($ngnStateCheck) > 0;
  $examModeCheck    = mysqli_query($quizCon, "SELECT * FROM temporary_exam_state WHERE student_id = '" . intval($user_id) . "' AND exam_mode = 'exam'");
  $hasPausedExamMode = mysqli_num_rows($examModeCheck) > 0;
  if ($hasPausedExamMode) {
      $emRow = mysqli_fetch_assoc($examModeCheck);
      $emSavedTimeStr = date('M d, Y h:i A', strtotime($emRow['updated_at']));
  }
  if ($hasPausedNGN) {
      $stRow = mysqli_fetch_assoc($ngnStateCheck);
      $savedTimeStr = date('M d, Y h:i A', strtotime($stRow['updated_at']));
  }
  $userBundle = $fetch['bundle_name'];
  $isPackege2 = ($userBundle == 'Packege 2');
  $firstname  = explode(' ', trim($fetch['fullname']))[0];

  // Passing score
  $selP = mysqli_query($quizCon, "SELECT sum(score) FROM `history` WHERE email = '$user_id'");
  $passingRounded = 0;
  while ($rp = mysqli_fetch_array($selP)) {
      $tot = mysqli_num_rows(mysqli_query($quizCon, "SELECT * FROM `history` WHERE email = '$user_id'"));
      $passingRounded = ($tot == 0) ? 0 : round($rp['sum(score)'] / $tot);
  }
  ?>

  <!-- ── Hero Banner ── -->
  <?php
  $noData = ($overallCorrect + $overallWrong) === 0;
  if ($noData) {
    $heroTier = 'none';
    $heroIcon = 'bi-rocket-takeoff-fill';
  } elseif ($overallAccuracy >= 85) {
    $heroTier = 'perfect';
    $heroIcon = 'bi-trophy-fill';
  } elseif ($overallAccuracy >= 75) {
    $heroTier = 'high';
    $heroIcon = 'bi-patch-check-fill';
  } elseif ($overallAccuracy >= 55) {
    $heroTier = 'mid';
    $heroIcon = 'bi-arrow-up-circle-fill';
  } else {
    $heroTier = 'low';
    $heroIcon = 'bi-fire';
  }

  $heroQuotesByTier = [
    'low'     => [
      "Every expert was once a beginner. Keep going.",
      "The NCLEX is hard. Quitting is harder to live with. Push through.",
      "Every wrong answer is a lesson. You're building the nurse in you.",
      "Small progress is still progress. Keep grinding.",
      "Struggle is the sign you're growing. Don't stop now.",
      "Champions are made in the moments they want to quit.",
      "You didn't come this far to only come this far.",
    ],
    'mid'     => [
      "The finish line is closer than you think. Stay the course.",
      "Consistency is your superpower. Keep showing up every day.",
      "You've come too far to stop. Stay focused and finish strong.",
      "Progress isn't always linear — but you're trending up.",
      "Trust the process. Your hard work is compounding.",
      "Every session is one step closer to passing day.",
    ],
    'high'    => [
      "You're in the passing zone. Stay sharp and don't let up!",
      "NCLEX-ready and proving it. Keep that momentum going.",
      "You're ahead of the curve. One more push and you'll be unstoppable.",
      "Channel this confidence into exam day. You've earned it.",
      "Discipline got you here. Let it carry you to the finish.",
    ],
    'perfect' => [
      "Top performance. Your dedication is clearly showing.",
      "You're performing at the highest level. Own it.",
      "Elite scores. NCLEX doesn't stand a chance.",
      "Mastery takes repetition — and you're proving that every session.",
    ],
    'none'    => [
      "Your NCLEX journey starts today. Every great nurse began right here.",
      "The first step is the hardest. Choose a mode below and begin.",
      "Thousands of nurses passed the NCLEX one question at a time. Start yours.",
      "Your future patients are counting on you. Begin your review today.",
    ],
  ];

  $allHeroQuotes = array_merge(...array_values($heroQuotesByTier));
  $tierQuotes    = $heroQuotesByTier[$heroTier];
  ?>
  <div class="s-hero-banner mb-4">
    <div class="s-hero-orb s-hero-orb--1"></div>
    <div class="s-hero-orb s-hero-orb--2"></div>
    <div class="s-hero-inner">
      <div class="s-hero-icon-col">
        <div class="s-hero-icon-wrap">
          <svg class="s-hero-logo" viewBox="0 0 2858 1997" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M2217.78 1971.36C951.926 1971.36 1497.93 1538.44 594.344 810.147C594.344 1009.31 680.34 1457.02 1262.06 1854.67C692.08 1605.45 524.148 1228.06 511.429 1070.51C314.195 1083.34 88.2955 941.476 0 868.94C476.062 2001.42 1531.69 2051.53 2217.78 1971.36Z" fill="white"/>
            <path d="M594.953 825.34C594.952 455.709 747.925 111.114 844.693 0C909.111 155.279 1099.55 500.098 1345.97 637.149C1075.08 1043.91 1216.57 1572.48 1345.97 1765.45C1034.57 1427.04 1122.45 1283.58 594.953 825.34Z" fill="white"/>
            <path d="M1330.84 1747.73C1413.82 1441.95 1846.8 995.848 2857.46 1567.68C2602.61 2139.3 1564.6 2038.03 1330.84 1747.73Z" fill="white"/>
            <path d="M2747.04 227.463C1208.43 147.798 1163.03 1106.81 1337.72 1591.21C1417.89 1408.37 1638.86 1003.4 2557.16 1241.84C2473.45 838.113 2471.8 484.402 2747.04 227.463Z" fill="white"/>
            <path d="M598.328 1314.8C281.019 1235.14 89.4524 1020 3.37402 873.441C65.2604 931.39 261.947 1046.1 516.469 1071.76C520.407 1124.64 535.035 1187.37 598.328 1314.8Z" fill="white"/>
            <path d="M595.798 826.183C708.881 1405.1 927.453 1253.2 1332.52 1751.95C929.978 1221.13 1023.66 1167.12 595.798 826.183Z" fill="white"/>
            <path d="M1307.18 613.681C998.303 424.393 866.514 237.214 773.861 107.176C786.52 86.0779 815.213 28.6923 844.749 2.53125C912.866 148.414 1101.55 478.036 1307.18 613.681C1319.02 620.939 1331.13 628.2 1343.5 635.46C1331.35 628.918 1319.23 621.633 1307.18 613.681Z" fill="white"/>
            <path d="M2744.39 227.852C2031.28 324.903 1277.67 562.04 1338.44 1590.76C975.558 525.754 1962.92 122.367 2744.39 227.852Z" fill="white"/>
            <path d="M1329.15 1751.11C1421.14 1411.86 1880.22 1013.53 2853.24 1566.29C2048.16 1566.29 1551.1 1438.02 1329.15 1751.11Z" fill="white"/>
          </svg>
        </div>
      </div>
      <div class="s-hero-body">
        <div class="s-hero-label">Daily Motivation</div>
        <div class="s-hero-quote-wrap">
          <i class="bi bi-quote s-hero-quote-mark s-hero-quote-mark--open"></i>
          <div class="s-hero-quote" id="heroQuoteText"></div>
        </div>
      </div>
    </div>
  </div>

  <style>
  .s-hero-banner {
    position: relative; overflow: hidden; border-radius: 16px;
    background: linear-gradient(135deg, var(--s-primary, #007CBF) 0%, #0D9488 100%);
    padding: 34px 28px;
    box-shadow: 0 4px 20px rgba(0,124,191,0.18);
  }
  .s-hero-orb {
    position: absolute; border-radius: 50%; pointer-events: none;
    background: rgba(255,255,255,0.07);
  }
  .s-hero-orb--1 { width: 180px; height: 180px; top: -60px; right: -30px; }
  .s-hero-orb--2 { width: 100px; height: 100px; bottom: -30px; right: 120px; }
  .s-hero-inner {
    position: relative; display: flex; align-items: center; gap: 22px;
  }
  .s-hero-icon-col { flex-shrink: 0; }
  .s-hero-icon-wrap {
    width: 64px; height: 64px; border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
  }
  .s-hero-logo {
    width: 58px; height: 58px; opacity: 0.22;
    filter: brightness(0) invert(1);
  }
  .s-hero-body { flex: 1; min-width: 0; }
  .s-hero-label {
    font-size: 0.68rem; font-weight: 700; letter-spacing: 1px;
    text-transform: uppercase; color: rgba(255,255,255,0.65); margin-bottom: 6px;
  }
  .s-hero-quote-wrap { position: relative; padding-left: 4px; min-height: 2.4em; }
  .s-hero-quote-mark {
    padding-top: 10px;
    font-size: 1.6rem; color: rgba(255,255,255,0.25); line-height: 1;
  }
  .s-hero-quote-mark--open {
    position: absolute; top: -6px; left: -4px;
  }
  .s-hero-quote-mark--close {
    display: inline; transform: scaleX(-1);
    vertical-align: -4px; margin-left: 1px;
    font-style: normal;
  }
  .s-hero-quote {
    font-size: 1.5rem; font-weight: 700; color: #fff; line-height: 1.45;
    padding-left: 20px;
    transition: opacity 0.4s ease, transform 0.4s ease;
  }
  .s-hero-quote.fade-out { opacity: 0; transform: translateY(6px); }
  @media (max-width: 576px) {
    .s-hero-banner { padding: 22px 18px; }
    .s-hero-icon-col { display: none; }
    .s-hero-quote { font-size: 1.05rem; }
    .s-hero-label { font-size: 0.72rem; }
  }
  </style>

  <script>
  (function() {
    const quotes = <?= json_encode(array_values($tierQuotes), JSON_UNESCAPED_UNICODE) ?>;
    let current = 0;

    const closeIcon = '<i class="bi bi-quote s-hero-quote-mark s-hero-quote-mark--close"></i>';

    function heroGoTo(idx) {
      const el = document.getElementById('heroQuoteText');
      if (!el) return;
      el.classList.add('fade-out');
      setTimeout(function() {
        current = idx;
        el.innerHTML = quotes[current] + closeIcon;
        el.classList.remove('fade-out');
      }, 380);
    }

    document.addEventListener('DOMContentLoaded', function() {
      const el = document.getElementById('heroQuoteText');
      if (!el || !quotes.length) return;
      el.innerHTML = quotes[0] + closeIcon;
      setInterval(function() {
        heroGoTo((current + 1) % quotes.length);
      }, 10000);
    });
  })();
  </script>

  <!-- ── Practice Mode Cards ── -->
  <h5 class="s-section-title mb-3"><i class="bi bi-lightning-charge-fill me-2"></i>Choose Your Practice Mode</h5>

  <?php if ($hasPausedNGN): ?>
  <div class="s-alert-banner mb-3" style="border-color:#f59e0b; background:linear-gradient(135deg,#fffbeb,#fef3c7);">
    <i class="bi bi-pause-circle-fill" style="color:#d97706; font-size:1.3rem;"></i>
    <div class="flex-grow-1">
      <div class="fw-semibold" style="color:#92400e;">Paused NGN Exam — Resume where you left off</div>
      <div style="font-size:0.72rem; color:#a16207;">Saved: <?php echo $savedTimeStr; ?></div>
    </div>
    <a href="ngn/index.php" class="s-btn s-btn-teal" style="padding:6px 14px;font-size:0.78rem;flex-shrink:0;"><i class="bi bi-play-fill"></i> Resume</a>
    <button onclick="discardExam(<?php echo $stRow['examTaken']; ?>)" class="s-btn s-btn-outline" style="padding:6px 10px;font-size:0.78rem;color:#ef4444;border-color:#fca5a5;flex-shrink:0;"><i class="bi bi-trash3"></i></button>
  </div>
  <?php endif; ?>

  <?php if ($hasPausedExamMode): ?>
  <div class="s-alert-banner mb-3" style="border-color:#6366f1; background:linear-gradient(135deg,#eef2ff,#e0e7ff);">
    <i class="bi bi-pause-circle-fill" style="color:#6366f1; font-size:1.3rem;"></i>
    <div class="flex-grow-1">
      <div class="fw-semibold" style="color:#4338ca;">Paused CAT Exam — Adaptive exam in progress</div>
      <div style="font-size:0.72rem; color:#6366f1;">Saved: <?php echo htmlspecialchars($emSavedTimeStr, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
    <a href="exam/index.php" class="s-btn" style="background:#6366f1;color:#fff;padding:6px 14px;font-size:0.78rem;flex-shrink:0;"><i class="bi bi-play-fill"></i> Resume</a>
    <button onclick="discardExamMode(<?php echo intval($emRow['examTaken']); ?>)" class="s-btn s-btn-outline" style="padding:6px 10px;font-size:0.78rem;color:#ef4444;border-color:#fca5a5;flex-shrink:0;"><i class="bi bi-trash3"></i></button>
  </div>
  <?php endif; ?>

  <div class="row g-3 mb-4">

    <!-- NGN QBanks -->
    <div class="col-lg-4 col-md-6 col-12" id="tut-ngn-card">
      <div class="s-mode-hero-card" style="--mhc-color:#0D9488; --mhc-bg:rgba(13,148,136,.06);">
        <div class="s-mhc-icon" style="background:rgba(13,148,136,.12);">
          <i class="bi bi-lightbulb-fill" style="color:#0D9488;"></i>
        </div>
        <div class="s-mhc-body">
          <div class="s-mhc-title">NARC NGN QBanks</div>
          <div class="s-mhc-desc">Next Generation NCLEX clinical judgment questions with NGN-style scenarios.</div>
          <div class="s-mhc-tag" style="background:rgba(13,148,136,.12);color:#0D9488;">Next Gen NCLEX</div>
        </div>
        <div class="s-mhc-footer">
          <?php if ($isPackege2): ?>
            <?php if ($hasPausedNGN): ?>
              <a href="ngn/index.php" class="s-btn s-btn-teal w-100" style="justify-content:center;">
                <i class="bi bi-play-fill"></i> Resume NGN
              </a>
            <?php else: ?>
              <button type="button" class="s-btn s-btn-teal w-100" style="justify-content:center;" onclick="openNGNModal()">
                <i class="bi bi-lightning-charge-fill"></i> Start NGN Exam
              </button>
            <?php endif; ?>
          <?php else: ?>
            <a href="subscription.php" class="s-btn w-100" style="background:#94a3b8;color:#fff;justify-content:center;cursor:not-allowed;opacity:.75;">
              <i class="bi bi-lock-fill"></i> Package 2 Required
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Traditional QBanks -->
    <?php
    $tq = mysqli_query($con, "SELECT * FROM bundlelist GROUP BY name ORDER BY id ASC");
    $_tut_trad_first = true;
    while ($brow = mysqli_fetch_array($tq)) {
      if (stripos($brow['name'], 'NGN') !== false) continue; // skip all NGN rows, handled above
      $cleanName = str_replace('(Soon)', '', $brow['name']);
    ?>
    <div class="col-lg-4 col-md-6 col-12"<?= $_tut_trad_first ? ' id="tut-trad-card"' : '' ?><?php $_tut_trad_first = false; ?>>
      <div class="s-mode-hero-card" style="--mhc-color:var(--s-primary); --mhc-bg:rgba(0,124,191,.05);">
        <div class="s-mhc-icon" style="background:rgba(0,124,191,.1);">
          <i class="bi bi-journals" style="color:var(--s-primary);"></i>
        </div>
        <div class="s-mhc-body">
          <div class="s-mhc-title"><?php echo htmlspecialchars($cleanName); ?></div>
          <div class="s-mhc-desc">Traditional multiple-choice questions with in-depth rationale and full topic coverage.</div>
          <div class="s-mhc-tag" style="background:rgba(0,124,191,.1);color:var(--s-primary);">Traditional NCLEX</div>
        </div>
        <div class="s-mhc-footer">
          <a href="topic.php?kilanlan=<?php echo urlencode($brow['bundlelist_name']); ?>"
             class="s-btn s-btn-primary w-100" style="justify-content:center;">
            <i class="bi bi-book-fill"></i> Start Practice
          </a>
        </div>
      </div>
    </div>
    <?php } ?>

    <!-- CAT Exam Mode -->
    <div class="col-lg-4 col-md-6 col-12" id="tut-cat-card">
      <div class="s-mode-hero-card" style="--mhc-color:#6366f1; --mhc-bg:rgba(99,102,241,.05);">
        <div class="s-mhc-icon" style="background:rgba(99,102,241,.1);">
          <i class="bi bi-activity" style="color:#6366f1;"></i>
        </div>
        <div class="s-mhc-body">
          <div class="s-mhc-title">CAT Exam Mode</div>
          <div class="s-mhc-desc">NCLEX-style adaptive exam. IRT engine adjusts difficulty in real time. Auto-terminates on pass/fail.</div>
          <div class="s-mhc-tag" style="background:rgba(99,102,241,.1);color:#6366f1;">IRT Adaptive</div>
        </div>
        <div class="s-mhc-footer">
          <?php if ($isPackege2): ?>
            <a href="exam/index.php" class="s-btn w-100" style="background:#6366f1;color:#fff;justify-content:center;">
              <i class="bi bi-lightning-charge-fill"></i> Start CAT Exam
            </a>
          <?php else: ?>
            <a href="subscription.php" class="s-btn w-100" style="background:#94a3b8;color:#fff;justify-content:center;cursor:not-allowed;opacity:.75;">
              <i class="bi bi-lock-fill"></i> Package 2 Required
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div><!-- /practice row -->


  <!-- ── Statistics ── -->
  <div class="s-section-header mt-2 mb-3" id="tut-stats-hdr">
    <div class="d-flex align-items-center gap-2">
      <h5 class="s-section-title mb-0"><i class="bi bi-bar-chart-line-fill me-2"></i>Statistics</h5>
      <button class="s-info-btn" data-tip="Accuracy = correct answers ÷ total attempted × 100. Switch filters to view Overall, Traditional, or NGN performance separately.">i</button>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <div class="s-stat-filter-bar" role="group" aria-label="Statistics filter">
        <button class="s-stat-tab active" data-filter="overall" onclick="setStatsFilter('overall')">
          <i class="bi bi-grid-3x3-gap-fill"></i> Overall
        </button>
        <button class="s-stat-tab" data-filter="trad" onclick="setStatsFilter('trad')">
          <i class="bi bi-journals"></i> Traditional
        </button>
        <button class="s-stat-tab<?= !$isPackege2 ? ' s-stat-tab-locked' : '' ?>"
                data-filter="ngn"
                <?= $isPackege2 ? "onclick=\"setStatsFilter('ngn')\"" : 'disabled' ?>
                <?= !$isPackege2 ? 'data-tip="Upgrade to Package 2 to unlock NGN exam statistics."' : '' ?>>
          <i class="bi bi-lightbulb-fill"></i> NGN<?= !$isPackege2 ? '&nbsp;<i class="bi bi-lock-fill" style="font-size:0.6rem;"></i>' : '' ?>
        </button>
      </div>
      <button class="s-toggle-btn" id="stats-toggle-btn" onclick="toggleSection('stats-section-body','stats-toggle-btn')">
        <i class="bi bi-chevron-down s-chevron"></i><span class="s-toggle-lbl">Hide</span>
      </button>
    </div>
  </div>

  <div class="s-collapsible" id="stats-section-body">

  <!-- Overall Accuracy Formula Card -->
  <div class="s-accuracy-overview mb-4">
    <div class="s-acc-info">
      <div class="s-acc-pct" id="acc-pct"><?= $overallAccuracy ?>%</div>
      <div class="s-acc-title" id="acc-title">Overall Accuracy (Traditional + NGN)</div>
      <div class="s-acc-formula">
        <span class="s-fpill" id="acc-correct"><?= $overallCorrect ?> correct</span>
        <span class="s-fop">÷</span>
        <span class="s-fpill" id="acc-total"><?= ($overallCorrect + $overallWrong) ?> answered</span>
        <span class="s-fop">× 100</span>
      </div>
    </div>
    <div class="s-acc-bar-wrap">
      <div class="s-acc-bar-track">
        <div class="s-acc-bar-fill" id="acc-bar" data-w="<?= $overallAccuracy ?>"></div>
        <div class="s-acc-pass-line" style="left:75%">
          <span class="s-acc-pass-lbl">75% Pass Mark</span>
        </div>
      </div>
      <div class="s-acc-bar-ends">
        <span>0%</span>
        <span style="color:var(--s-primary); font-weight:700;" id="acc-bar-lbl">50%</span>
        <span>100%</span>
      </div>
    </div>
  </div>

  <!-- Usage & Performance Charts -->
  <div class="row g-4 mb-4">
    <div class="col-lg-6 col-12">
      <div class="s-chart-card">
        <div class="s-chart-card-header">
          <span class="s-chart-card-title"><i class="bi bi-question-circle-fill"></i> Questions Usage</span>
          <span class="s-badge s-badge-blue" id="badge-q-total"><?= $overallTotalQ ?> Total</span>
        </div>
        <div class="s-chart-body">
          <div class="s-donut-wrap flex-shrink-0">
            <div id="questionsCircle" style="width:100%;height:100%;"></div>
            <div class="s-donut-label">Usage</div>
          </div>
          <div class="s-chart-legend">
            <div class="s-legend-item">
              <span class="s-legend-dot" id="dot-used" style="background:#007CBF;"></span>
              Used &nbsp;<strong id="legend-used-count"><?= $overallUsedQ ?></strong>
              <span id="legend-used-pct" class="s-badge s-badge-blue ms-1"><?= $overallUsedPct ?>%</span>
            </div>
            <div class="s-legend-item">
              <span class="s-legend-dot" style="background:#e2e8f0;"></span>
              Unused &nbsp;<strong id="legend-unused-count"><?= $overallUnusedQ ?></strong>
              <span id="legend-unused-pct" class="s-badge s-badge-gray ms-1"><?= 100 - $overallUsedPct ?>%</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-6 col-12">
      <div class="s-chart-card">
        <div class="s-chart-card-header">
          <span class="s-chart-card-title"><i class="bi bi-bar-chart-fill"></i> Performance</span>
          <span class="s-badge s-badge-teal" id="badge-perf-pct"><?= $overallAccuracy ?>% Accuracy</span>
        </div>
        <div class="s-chart-body">
          <div class="s-donut-wrap flex-shrink-0">
            <div id="performanceCircle" style="width:100%;height:100%;"></div>
            <div class="s-donut-label">Score</div>
          </div>
          <div class="s-chart-legend">
            <div class="s-legend-item">
              <span class="s-legend-dot" style="background:#0D9488;"></span>
              Correct &nbsp;<strong id="legend-perf-correct"><?= $overallCorrect ?></strong>
              <span id="legend-perf-correct-pct" class="s-badge s-badge-teal ms-1"><?= $overallAccuracy ?>%</span>
            </div>
            <div class="s-legend-item">
              <span class="s-legend-dot" style="background:#d72638;"></span>
              Incorrect &nbsp;<strong id="legend-perf-wrong"><?= $overallWrong ?></strong>
              <span id="legend-perf-wrong-pct" class="s-badge s-badge-red ms-1"><?= ($overallCorrect + $overallWrong > 0) ? 100 - $overallAccuracy : 0 ?>%</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  </div><!-- /stats-section-body -->


  <!-- ── Topics & Concepts ── -->
  <div class="s-section-header mt-2 mb-4" id="tut-topics-hdr">
    <div class="d-flex align-items-center gap-2">
      <h5 class="s-section-title mb-0"><i class="bi bi-grid-fill me-2"></i>Topics & Concepts Statistics</h5>
      <button class="s-info-btn" data-tip="See how many questions you've attempted and your accuracy per concept and topic. Select from the dropdowns to explore any area.">i</button>
    </div>
    <button class="s-toggle-btn" id="topics-toggle-btn" onclick="toggleSection('topics-section-body','topics-toggle-btn')">
      <i class="bi bi-chevron-down s-chevron"></i><span class="s-toggle-lbl">Hide</span>
    </button>
  </div>
  <div class="s-collapsible" id="topics-section-body">
  <div class="row g-4 mb-4">

    <!-- Concepts Statistics -->
    <div class="col-lg-6 col-12">
      <div class="s-chart-card">
        <div class="s-chart-card-header">
          <span class="s-chart-card-title"><i class="bi bi-grid-fill"></i> Concepts</span>
          <select id="topicSelect" class="s-input" style="max-width:180px; font-size:0.78rem; padding:5px 10px;">
            <option value="Adult Health">Adult Health</option>
            <option value="Child Health">Child Health</option>
            <option value="Critical Care">Critical Care</option>
            <option value="Fundamentals">Fundamentals</option>
            <option value="Leadership and Management">Leadership and Management</option>
            <option value="Mental Health">Mental Health</option>
            <option value="Pharmacology">Pharmacology</option>
            <option value="Maternal and Newborn Health">Maternal and Newborn Health</option>
          </select>
        </div>
        <div class="s-chart-body">
          <div class="s-donut-wrap flex-shrink-0" style="position:relative;">
            <div id="topicChart" style="width:100%;height:100%;"></div>
            <div class="s-donut-label">Score</div>
          </div>
          <div class="s-chart-legend">
            <div class="s-legend-item"><span class="s-legend-dot" style="background:#0D9488;"></span> Correct <strong><span id="topicCorrect">0</span></strong> <span id="topicCorrectPercent" class="s-badge s-badge-teal ms-1">0%</span></div>
            <div class="s-legend-item"><span class="s-legend-dot" style="background:#d72638;"></span> Incorrect <strong><span id="topicWrong">0</span></strong> <span id="topicWrongPercent" class="s-badge s-badge-red ms-1">0%</span></div>
            <div class="s-legend-item mt-1" style="font-size:0.78rem; color:var(--s-muted);">Used: <strong><span id="topicUsed">0</span></strong> &nbsp;/ Total: <strong><span id="topicTotal">0</span></strong></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Topics Statistics -->
    <div class="col-lg-6 col-12">
      <div class="s-chart-card">
        <div class="s-chart-card-header">
          <span class="s-chart-card-title"><i class="bi bi-list-ul"></i> Topics</span>
          <?php
          // Render only the first concept's topics — JS will swap on concept change
          $_firstConcept = array_key_first($readinessConceptMap);
          $_initTopics   = $tradConceptTopics[$_firstConcept] ?? [];
          ?>
          <select id="conceptSelect" class="s-input" style="max-width:180px; font-size:0.78rem; padding:5px 10px;">
            <?php foreach ($_initTopics as $_t): ?>
            <option value="<?= htmlspecialchars($_t) ?>"><?= htmlspecialchars($_t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="s-chart-body">
          <div class="s-donut-wrap flex-shrink-0" style="position:relative;">
            <div id="conceptChart" style="width:100%;height:100%;"></div>
            <div class="s-donut-label">Score</div>
          </div>
          <div class="s-chart-legend">
            <div class="s-legend-item"><span class="s-legend-dot" style="background:#0D9488;"></span> Correct <strong><span id="conceptCorrect">0</span></strong> <span id="conceptCorrectPercent" class="s-badge s-badge-teal ms-1">0%</span></div>
            <div class="s-legend-item"><span class="s-legend-dot" style="background:#d72638;"></span> Incorrect <strong><span id="conceptWrong">0</span></strong> <span id="conceptWrongPercent" class="s-badge s-badge-red ms-1">0%</span></div>
            <div class="s-legend-item mt-1" style="font-size:0.78rem; color:var(--s-muted);">Used: <strong><span id="conceptUsed">0</span></strong> &nbsp;/ Total: <strong><span id="conceptTotal">0</span></strong></div>
          </div>
        </div>
      </div>
    </div>
  </div>


  <!-- ── Average Scores Chart ── -->
  <div class="s-chart-card mb-4">
    <div class="s-chart-card-header">
      <span class="s-chart-card-title"><i class="bi bi-graph-up-arrow"></i> Average Scores per Concept</span>
    </div>
    <div id="scoresChart" style="height:260px; width:100%;"></div>
  </div>

  </div><!-- /topics-section-body -->


  <!-- ── NCLEX Readiness ── -->
  <div class="s-readiness-header mb-3 mt-2" id="tut-readiness-hdr">
    <div class="d-flex align-items-center gap-2">
      <h5 class="s-section-title mb-0"><i class="bi bi-patch-check-fill me-2"></i>NCLEX Readiness</h5>
      <button class="s-info-btn" data-tip="Based on your last 3 exam scores per concept. ≥75% = Ready, ≥60% = Almost, <60% = Needs Work. Switch the filter above to see Traditional or NGN readiness.">i</button>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <div id="readiness-banner-wrap">
        <div class="s-readiness-banner s-rb-<?= $overallCombinedReadiness ?>" id="readiness-banner-overall">
          <?php if ($overallCombinedReadiness === 'ready'): ?>
            <i class="bi bi-patch-check-fill"></i>&nbsp; <strong>NCLEX Ready!</strong>&nbsp; All <?= $overallTotalCount ?> concepts passing.
          <?php elseif ($overallCombinedReadiness === 'ontrack'): ?>
            <i class="bi bi-arrow-up-circle-fill"></i>&nbsp; <strong>On Track</strong>&nbsp; — <?= $overallReadyCount ?>/<?= $overallTotalCount ?> concepts passing.
          <?php elseif ($overallCombinedReadiness === 'needs'): ?>
            <i class="bi bi-exclamation-triangle-fill"></i>&nbsp; <strong>Needs Work</strong>&nbsp; — <?= $overallReadyCount ?>/<?= $overallTotalCount ?> concepts passing. Focus on the red concepts below.
          <?php else: ?>
            <i class="bi bi-hourglass-split"></i>&nbsp; <strong>No Data Yet</strong>&nbsp; — Complete at least one exam to see readiness.
          <?php endif; ?>
        </div>
        <div class="s-readiness-banner s-rb-<?= $tradAccuracy >= 75 ? 'ready' : ($tradAccuracy >= 60 ? 'ontrack' : ($tradExams > 0 ? 'needs' : 'none')) ?>" id="readiness-banner-trad" style="display:none;">
          <?php $tradReadyStatus = $tradAccuracy >= 75 ? 'ready' : ($tradAccuracy >= 60 ? 'ontrack' : ($tradExams > 0 ? 'needs' : 'none')); ?>
          <?php if ($tradReadyStatus === 'ready'): ?>
            <i class="bi bi-patch-check-fill"></i>&nbsp; <strong>Traditional Ready!</strong>&nbsp; <?= $readyCount ?>/<?= $totalConceptCount ?> concepts at passing standard.
          <?php elseif ($tradReadyStatus === 'ontrack'): ?>
            <i class="bi bi-arrow-up-circle-fill"></i>&nbsp; <strong>On Track</strong>&nbsp; — <?= $readyCount ?>/<?= $totalConceptCount ?> concepts passing.
          <?php elseif ($tradReadyStatus === 'needs'): ?>
            <i class="bi bi-exclamation-triangle-fill"></i>&nbsp; <strong>Needs Work</strong>&nbsp; — <?= $readyCount ?>/<?= $totalConceptCount ?> concepts passing.
          <?php else: ?>
            <i class="bi bi-hourglass-split"></i>&nbsp; <strong>No Data Yet</strong>&nbsp; — Take a Traditional exam to start.
          <?php endif; ?>
        </div>
        <div class="s-readiness-banner s-rb-<?= $ngnOverallReadiness ?>" id="readiness-banner-ngn" style="display:none;">
          <?php if ($ngnOverallReadiness === 'ready'): ?>
            <i class="bi bi-patch-check-fill"></i>&nbsp; <strong>NGN Ready!</strong>&nbsp; All <?= $ngnTotalConceptCount ?> NGN concepts passing.
          <?php elseif ($ngnOverallReadiness === 'ontrack'): ?>
            <i class="bi bi-arrow-up-circle-fill"></i>&nbsp; <strong>NGN On Track</strong>&nbsp; — <?= $ngnReadyCount ?>/<?= $ngnTotalConceptCount ?> concepts passing.
          <?php elseif ($ngnOverallReadiness === 'needs'): ?>
            <i class="bi bi-exclamation-triangle-fill"></i>&nbsp; <strong>NGN Needs Work</strong>&nbsp; — <?= $ngnReadyCount ?>/<?= $ngnTotalConceptCount ?> concepts passing.
          <?php else: ?>
            <i class="bi bi-hourglass-split"></i>&nbsp; <strong>No NGN Data</strong>&nbsp; — Take an NGN exam to start.
          <?php endif; ?>
        </div>
      </div>
      <button class="s-toggle-btn" id="readiness-toggle-btn" onclick="toggleSection('readiness-section-body','readiness-toggle-btn')">
        <i class="bi bi-chevron-down s-chevron"></i><span class="s-toggle-lbl">Hide</span>
      </button>
    </div>
  </div>

  <div class="s-collapsible" id="readiness-section-body">

  <p class="s-readiness-note mb-3" id="readiness-note-overall">Based on your combined <strong>Traditional + NGN</strong> performance. A concept is <span style="color:#0D9488;font-weight:600;">Ready</span> when its weighted accuracy is ≥ 75%.</p>
  <p class="s-readiness-note mb-3" id="readiness-note-trad" style="display:none;">Based on your <strong>last 3 exam attempts</strong> per concept (150 questions each). A concept is <span style="color:#0D9488;font-weight:600;">Ready</span> when its recent average is ≥ 75%.</p>
  <p class="s-readiness-note mb-3" id="readiness-note-ngn" style="display:none;">Based on your <strong>last 3 NGN exam attempts</strong> per concept. A concept is <span style="color:#0D9488;font-weight:600;">Ready</span> when its recent accuracy is ≥ 75%.</p>

  <?php
  $rcColors = [
    'ready'   => ['color' => '#0D9488', 'bg' => 'rgba(13,148,136,.09)',  'label' => 'Ready',      'icon' => 'bi-check-circle-fill'],
    'ontrack' => ['color' => '#d97706', 'bg' => 'rgba(217,119,6,.09)',   'label' => 'Almost',     'icon' => 'bi-arrow-up-circle-fill'],
    'needs'   => ['color' => '#ef4444', 'bg' => 'rgba(239,68,68,.09)',   'label' => 'Needs Work', 'icon' => 'bi-x-circle-fill'],
    'none'    => ['color' => '#94a3b8', 'bg' => 'rgba(148,163,184,.09)', 'label' => 'No Data',    'icon' => 'bi-dash-circle'],
  ];

  // Helper macro to render readiness cards
  function renderReadinessCards(array $conceptMap, array $conceptReadiness, array $rcColors): void {
    foreach ($conceptReadiness as $concept => $data):
      $icon = $conceptMap[$concept] ?? 'bi bi-circle-fill';
      $rc   = $rcColors[$data['status']];
  ?>
    <div class="col-xl-3 col-md-6 col-12">
      <div class="s-readiness-card<?= $data['attempts'] === 0 ? ' s-rc--empty' : '' ?>" style="--rc:<?= $rc['color'] ?>;">
        <div class="s-rc-header">
          <div class="s-rc-icon" style="background:<?= $rc['bg'] ?>; color:<?= $rc['color'] ?>;"><i class="<?= $icon ?>"></i></div>
          <span class="s-rc-status" style="background:<?= $rc['bg'] ?>; color:<?= $rc['color'] ?>;"><i class="bi <?= $rc['icon'] ?> me-1"></i><?= $rc['label'] ?></span>
        </div>
        <div class="s-rc-name"><?= htmlspecialchars($concept) ?></div>
        <div class="s-rc-avg" style="color:<?= $rc['color'] ?>;"><?= $data['avg'] ?><span>%</span></div>
        <div class="s-rc-sub"><?= $data['attempts'] > 0 ? 'Last '.$data['attempts'].' exam'.($data['attempts']>1?'s':'').' average' : 'No exams taken yet' ?></div>
        <?php if ($data['attempts'] > 0): ?>
        <div class="s-rc-pills">
          <?php foreach ($data['scores'] as $i => $sc):
            $pc = $sc >= 75 ? '#0D9488' : ($sc >= 60 ? '#d97706' : '#ef4444');
          ?>
          <span class="s-rc-pill" style="background:<?= $pc ?>; opacity:<?= round(1-$i*0.18,2) ?>;"><?= round($sc) ?>%</span>
          <?php endforeach; ?>
          <span class="s-rc-pill-lbl">← latest</span>
        </div>
        <?php else: ?>
        <div class="s-rc-pills"><span style="font-size:0.7rem;color:var(--s-muted);">Take an exam to track progress</span></div>
        <?php endif; ?>
        <div class="s-rc-bar">
          <div class="s-rc-bar-fill" data-w="<?= $data['avg'] ?>" style="background:<?= $rc['color'] ?>; width:0;"></div>
          <div class="s-rc-bar-marker"></div>
        </div>
        <div class="s-rc-bar-labels">
          <span>0%</span><span style="color:<?= $rc['color'] ?>;font-weight:600;"><?= $data['avg'] ?>%</span><span>100%</span>
        </div>
      </div>
    </div>
  <?php endforeach; }
  ?>

  <!-- Overall readiness cards (default visible) -->
  <div class="row g-3 mb-4" id="readiness-cards-overall">
    <?php renderReadinessCards($overallConceptIconMap, $overallConceptReadiness, $rcColors); ?>
  </div>

  <!-- Traditional readiness cards (hidden by default) -->
  <div class="row g-3 mb-4" id="readiness-cards-trad" style="display:none;">
    <?php renderReadinessCards($readinessConceptMap, $conceptReadiness, $rcColors); ?>
  </div>

  <!-- NGN readiness cards (hidden by default) -->
  <div class="row g-3 mb-4" id="readiness-cards-ngn" style="display:none;">
    <?php renderReadinessCards($ngnConceptMap, $ngnConceptReadiness, $rcColors); ?>
  </div>

  </div><!-- /readiness-section-body -->

</main><!-- /s-main -->

<!-- Footer -->
<div class="s-footer">
  <span>© Studium 2025, All Right Reserved.</span>
</div>


<!-- ── Scripts ── -->
<script src="../ty/js/jquery-3.5.1.js"></script>

<script>
// ── ECharts global defaults (clean Inter font everywhere) ──
const ECHART_FONT = "'Inter', sans-serif";

// ── All stats data pre-encoded from PHP ──
const allStats = {
  overall: {
    // Combined Traditional (review table) + NGN (exam_results) stats
    // Accuracy = (trad_correct + ngn_correct) / (trad_total + ngn_total) × 100
    // Usage    = (trad_used + ngn_used) / (trad_bank + ngn_bank_32) × 100
    qUsed: <?= $overallUsedQ ?>, qUnused: <?= $overallUnusedQ ?>, qTotal: <?= $overallTotalQ ?>, qUsedPct: <?= $overallUsedPct ?>,
    correct: <?= $overallCorrect ?>, wrong: <?= $overallWrong ?>, accuracy: <?= $overallAccuracy ?>,
    accLabel: 'Overall Accuracy (Traditional + NGN)',
    chartLabels: <?= json_encode(array_keys($overallChartAvgs)) ?>,
    chartData:   <?= json_encode(array_values($overallChartAvgs)) ?>
  },
  trad: {
    qUsed: <?= $usedQ ?>, qUnused: <?= $unusedQ ?>, qTotal: <?= $totalQ ?>, qUsedPct: <?= $usedPercent ?>,
    correct: <?= $tradCorrect ?>, wrong: <?= $tradWrong ?>, accuracy: <?= $tradAccuracy ?>,
    accLabel: 'Traditional Accuracy',
    chartLabels: <?= json_encode(array_keys($tradChartAvgs)) ?>,
    chartData:   <?= json_encode(array_values($tradChartAvgs)) ?>
  },
  ngn: {
    qUsed: <?= $ngnUsedQ ?>, qUnused: <?= $ngnUnusedQ ?>, qTotal: <?= $ngnBankTotal ?>, qUsedPct: <?= $ngnUsedPct ?>,
    correct: <?= $ngnCorrect ?>, wrong: <?= $ngnWrong ?>, accuracy: <?= $ngnAccuracy ?>,
    accLabel: 'NGN Accuracy',
    chartLabels: <?= json_encode(array_keys($ngnConceptAvgs)) ?>,
    chartData:   <?= json_encode(array_values($ngnConceptAvgs)) ?>
  }
};

// ── Premium ECharts donut factory ──
//  - 72→92% radius for an elegant thin ring
//  - Rounded segment ends with white separator for a polished look
//  - Smooth hover lift with soft shadow
//  - Empty state (all zeros) renders a neutral gray ring
function makeDonut(elId, data, colors) {
  const el = document.getElementById(elId);
  if (!el) return null;
  const chart = echarts.init(el);
  chart.setOption(buildDonutOption(data, colors));
  return chart;
}

function buildDonutOption(data, colors) {
  const keys   = Object.keys(data);
  const values = Object.values(data);
  const total  = values.reduce((a, b) => a + b, 0);

  // Empty state → flat gray ring
  const pieData = (total === 0)
    ? [{ value: 1, name: 'No data', itemStyle: { color: '#e2e8f0' }, tooltip: { show: false } }]
    : keys.map((k, i) => ({ value: values[i], name: k, itemStyle: { color: colors[i] } }));

  return {
    animation: true,
    animationDuration: 900,
    animationEasing: 'cubicOut',
    textStyle: { fontFamily: ECHART_FONT },
    tooltip: {
      trigger: 'item',
      backgroundColor: '#0f172a',
      borderColor: '#0f172a',
      borderWidth: 0,
      padding: [8, 12],
      textStyle: { color: '#fff', fontSize: 12, fontFamily: ECHART_FONT },
      formatter: p => p.name === 'No data'
        ? `<span style="color:#94a3b8;font-size:11px">No data yet</span>`
        : `<span style="color:#94a3b8;font-size:10px">${p.name}</span><br/><b style="font-size:14px">${p.value}</b> <span style="color:#94a3b8;font-size:10px">(${p.percent}%)</span>`
    },
    series: [{
      type: 'pie',
      radius: ['72%', '92%'],
      avoidLabelOverlap: false,
      itemStyle: {
        borderRadius: 6,
        borderColor: '#fff',
        borderWidth: total === 0 ? 0 : 2
      },
      label: { show: false },
      labelLine: { show: false },
      emphasis: {
        scale: true,
        scaleSize: 4,
        itemStyle: { shadowBlur: 12, shadowColor: 'rgba(15,23,42,0.18)' }
      },
      data: pieData
    }]
  };
}

// ── Global chart instances (initialised with Overall combined data) ──
let qChart    = makeDonut('questionsCircle',  { Used: <?= $overallUsedQ ?>,  Unused: <?= $overallUnusedQ ?>  }, ['#007CBF','#e2e8f0']);
let perfChart = makeDonut('performanceCircle', { Correct: <?= $overallCorrect ?>, Wrong: <?= $overallWrong ?>  }, ['#0D9488','#d72638']);
window.addEventListener('resize', () => { qChart && qChart.resize(); perfChart && perfChart.resize(); });

// ── Line Chart: Average Scores per Concept ──
const scoresChart = echarts.init(document.getElementById('scoresChart'));

function buildScoresOption(labels, data) {
  const abbrevMap = {
    'Leadership and Management': 'Leadership & Mgmt',
    'Maternal and Newborn Health': 'Maternal & Newborn',
    'Fluid and Electrolytes': 'Fluid & Electrolytes',
  };
  const shortLabels = labels.map(l => abbrevMap[l] || l);
  return {
    animation: true,
    animationDuration: 900,
    animationEasing: 'cubicOut',
    textStyle: { fontFamily: ECHART_FONT },
    tooltip: {
      trigger: 'axis',
      backgroundColor: '#0f172a',
      borderColor: '#0f172a',
      borderWidth: 0,
      padding: [8, 12],
      textStyle: { color: '#fff', fontSize: 12, fontFamily: ECHART_FONT },
      axisPointer: {
        type: 'line',
        lineStyle: { color: 'rgba(100,116,139,0.45)', type: 'dashed', width: 1 }
      },
      formatter: p => `<span style="color:#94a3b8;font-size:10px">${labels[p[0].dataIndex]}</span><br/><b style="font-size:14px">${p[0].value}%</b>`
    },
    grid: { top: 18, right: 18, bottom: 84, left: 48 },
    xAxis: {
      type: 'category',
      data: shortLabels,
      boundaryGap: false,
      axisLine: { show: false },
      axisTick: { show: false },
      axisLabel: { color: '#94a3b8', fontSize: 10, rotate: 40, interval: 0, fontFamily: ECHART_FONT }
    },
    yAxis: {
      type: 'value',
      min: 0, max: 100,
      splitLine: { lineStyle: { color: 'rgba(15,23,42,0.06)' } },
      axisLine: { show: false },
      axisTick: { show: false },
      axisLabel: { color: '#94a3b8', fontSize: 11, formatter: '{value}%', fontFamily: ECHART_FONT }
    },
    series: [{
      type: 'line',
      showSymbol: false,
      symbol: 'circle',
      symbolSize: 8,
      smooth: true,
      data: data,
      lineStyle: {
        width: 3,
        color: {
          type: 'linear', x: 0, y: 0, x2: 1, y2: 0,
          colorStops: [
            { offset: 0, color: '#007CBF' },
            { offset: 1, color: '#0D9488' }
          ]
        }
      },
      itemStyle: {
        color: '#0D9488',
        borderColor: '#fff',
        borderWidth: 2,
        shadowBlur: 8,
        shadowColor: 'rgba(13,148,136,0.35)'
      },
      areaStyle: {
        opacity: 0.18,
        color: {
          type: 'linear', x: 0, y: 0, x2: 0, y2: 1,
          colorStops: [
            { offset: 0, color: 'rgba(13,148,136,0.55)' },
            { offset: 1, color: 'rgba(0,124,191,0)' }
          ]
        }
      },
      emphasis: {
        focus: 'series',
        scale: true
      }
    }]
  };
}

scoresChart.setOption(buildScoresOption(
  <?= json_encode(array_keys($overallChartAvgs)) ?>,
  <?= json_encode(array_values($overallChartAvgs)) ?>
));

window.addEventListener('resize', () => scoresChart.resize());

// ── setStatsFilter — wires ALL sections to the filter tabs ──
function setStatsFilter(mode) {
  // Tab highlight
  document.querySelectorAll('.s-stat-tab').forEach(t => t.classList.toggle('active', t.dataset.filter === mode));

  const d = allStats[mode];
  const perfTotal = d.correct + d.wrong;
  const wrongPct  = perfTotal > 0 ? 100 - d.accuracy : 0;

  // ── Accuracy card ──
  document.getElementById('acc-pct').textContent     = d.accuracy + '%';
  document.getElementById('acc-title').textContent   = d.accLabel;
  document.getElementById('acc-correct').textContent = d.correct + ' correct';
  document.getElementById('acc-total').textContent   = perfTotal + ' answered';
  document.getElementById('acc-bar-lbl').textContent = '50%';
  const bar = document.getElementById('acc-bar');
  if (bar) {
    bar.style.transition = 'none'; bar.style.width = '0%';
    requestAnimationFrame(() => { bar.style.transition = ''; setTimeout(() => { bar.style.width = d.accuracy + '%'; }, 30); });
  }

  // ── Questions Usage donut ──
  qChart.setOption(buildDonutOption({ Used: d.qUsed, Unused: d.qUnused }, ['#007CBF', '#e2e8f0']));
  document.getElementById('badge-q-total').textContent    = d.qTotal + ' Total';
  document.getElementById('legend-used-count').textContent   = d.qUsed;
  document.getElementById('legend-unused-count').textContent = d.qUnused;
  document.getElementById('legend-used-pct').textContent     = d.qUsedPct + '%';
  document.getElementById('legend-unused-pct').textContent   = (100 - d.qUsedPct) + '%';

  // ── Performance donut ──
  perfChart.setOption(buildDonutOption({ Correct: d.correct, Wrong: d.wrong }, ['#0D9488', '#d72638']));
  document.getElementById('badge-perf-pct').textContent          = d.accuracy + '% Accuracy';
  document.getElementById('legend-perf-correct').textContent     = d.correct;
  document.getElementById('legend-perf-wrong').textContent       = d.wrong;
  document.getElementById('legend-perf-correct-pct').textContent = d.accuracy + '%';
  document.getElementById('legend-perf-wrong-pct').textContent   = wrongPct + '%';

  // ── Average Scores line chart ──
  scoresChart.setOption(buildScoresOption(d.chartLabels, d.chartData));

  // ── Readiness banners ──
  const rb = id => document.getElementById(id);
  const show = (el, vis) => { if (el) el.style.display = vis ? '' : 'none'; };
  show(rb('readiness-banner-overall'), mode === 'overall');
  show(rb('readiness-banner-trad'),    mode === 'trad');
  show(rb('readiness-banner-ngn'),     mode === 'ngn');

  // ── Readiness cards + notes ──
  show(rb('readiness-cards-overall'), mode === 'overall');
  show(rb('readiness-cards-trad'),    mode === 'trad');
  show(rb('readiness-cards-ngn'),     mode === 'ngn');
  show(rb('readiness-note-overall'),  mode === 'overall');
  show(rb('readiness-note-trad'),     mode === 'trad');
  show(rb('readiness-note-ngn'),      mode === 'ngn');

  // Re-animate readiness bars for newly visible cards
  const cardGroupId = mode === 'ngn' ? '#readiness-cards-ngn'
                    : mode === 'trad' ? '#readiness-cards-trad'
                    : '#readiness-cards-overall';
  document.querySelectorAll(cardGroupId + ' .s-rc-bar-fill[data-w]').forEach((b, i) => {
    b.style.width = '0%';
    requestAnimationFrame(() => { setTimeout(() => { b.style.width = b.dataset.w + '%'; }, 80 + i * 30); });
  });
}

// ── Section collapse/expand with localStorage ──
function toggleSection(sectionId, btnId) {
  const section = document.getElementById(sectionId);
  const btn     = document.getElementById(btnId);
  if (!section || !btn) return;
  const collapsed = section.classList.toggle('is-collapsed');
  btn.classList.toggle('collapsed', collapsed);
  btn.querySelector('.s-toggle-lbl').textContent = collapsed ? 'Show' : 'Hide';
  try { localStorage.setItem('s-section-' + sectionId, collapsed ? '1' : '0'); } catch(e) {}
}

// Restore section collapse states
(function restoreSectionStates() {
  ['stats-section-body', 'topics-section-body', 'readiness-section-body'].forEach(id => {
    try {
      if (localStorage.getItem('s-section-' + id) !== '1') return;
      const section = document.getElementById(id);
      if (!section) return;
      section.classList.add('is-collapsed');
      const btn = document.querySelector('[onclick*="' + id + '"]');
      if (btn) { btn.classList.add('collapsed'); const lbl = btn.querySelector('.s-toggle-lbl'); if (lbl) lbl.textContent = 'Show'; }
    } catch(e) {}
  });
})();

// ── Animate accuracy bar + readiness bars on load ──
(function initAnimations() {
  const accBar = document.getElementById('acc-bar');
  if (accBar) { requestAnimationFrame(() => { setTimeout(() => { accBar.style.width = accBar.dataset.w + '%'; }, 150); }); }
  document.querySelectorAll('#readiness-cards-overall .s-rc-bar-fill[data-w]').forEach((bar, i) => {
    bar.style.width = '0%';
    requestAnimationFrame(() => { setTimeout(() => { bar.style.width = bar.dataset.w + '%'; }, 200 + i * 40); });
  });
})();
</script>

<!-- Concept + Topic chart JS -->
<script>
// ECharts donut instances (created lazily on first use)
let conceptChart = null, topicChart = null;
let currentMode = 'overall';

// Dropdown option lists
const TRAD_CONCEPTS       = <?= json_encode(array_keys($readinessConceptMap)) ?>;
const NGN_CONCEPTS        = <?= json_encode(array_keys($ngnConceptMap)) ?>;
const NGN_TOPICS          = <?= json_encode(array_keys($ngnBankTopic)) ?>;
const NGN_CONCEPT_TOPICS  = <?= json_encode($ngnConceptTopics, JSON_UNESCAPED_UNICODE) ?>;
const TRAD_CONCEPT_TOPICS = <?= json_encode($tradConceptTopics, JSON_UNESCAPED_UNICODE) ?>;

// Overall = union of trad + ngn concepts (no duplicates, trad order first)
const OVERALL_CONCEPTS = [...new Set([...TRAD_CONCEPTS, ...NGN_CONCEPTS])];
const OVERALL_CONCEPT_TOPICS = (() => {
  const map = {};
  OVERALL_CONCEPTS.forEach(c => {
    const t = [...(TRAD_CONCEPT_TOPICS[c] || []), ...(NGN_CONCEPT_TOPICS[c] || [])];
    map[c] = [...new Set(t)].sort();
  });
  return map;
})();

const _TRAD_TOPICS = [
  'Pain Meds','Antepartum','Assignment/Delegation','Cardiovascular','Oncology',
  'Emergency Care','Endocrine','Nursing Legalities','Fluid and Electrolyte',
  'Gastrointestinal/Nutrition','Growth and Development','Hematology','Immunology',
  'Communicable Disease','Integumentary','Management Concepts','Psychiatry',
  'Musculoskeletal','Neurology','Prioritization','Psych Meds','Respiratory',
  'Skills/Procedures','Genitourinary','Eyes/Ears/Nose/Throat','Intrapartum',
  'Postpartum','Labor and Delivery','Drug Computations','Culture and Religion',
  'Neonatology','End of Life Care','Communication'
];

function swapDropdown(selectEl, options) {
  const prev = selectEl.value;
  selectEl.innerHTML = options.map(o => `<option value="${o}">${o}</option>`).join('');
  if (options.includes(prev)) selectEl.value = prev;
}

function updateConceptChart(correct, wrong) {
  if (!conceptChart) conceptChart = echarts.init(document.getElementById('conceptChart'));
  conceptChart.setOption(buildDonutOption({ Correct: correct, Wrong: wrong }, ['#0D9488', '#d72638']));
}

function updateTopicChart(correct, wrong) {
  if (!topicChart) topicChart = echarts.init(document.getElementById('topicChart'));
  topicChart.setOption(buildDonutOption({ Correct: correct, Wrong: wrong }, ['#0D9488', '#d72638']));
}

window.addEventListener('resize', () => {
  if (conceptChart) conceptChart.resize();
  if (topicChart)   topicChart.resize();
});

function fetchStats(type, value) {
  fetch(`get_stats.php?type=${type}&value=${encodeURIComponent(value)}`)
    .then(res => res.json())
    .then(data => {
      const isConcept = (type === 'concept' || type === 'ngn_topic' || type === 'overall_topic');
      const prefix    = isConcept ? 'concept' : 'topic';
      document.getElementById(prefix + 'Total').innerText   = data.total ?? 0;
      document.getElementById(prefix + 'Used').innerText    = data.used  ?? 0;
      document.getElementById(prefix + 'Correct').innerText = data.correct ?? 0;
      document.getElementById(prefix + 'Wrong').innerText   = data.wrong ?? 0;
      const tot = (data.correct ?? 0) + (data.wrong ?? 0);
      const cp  = tot > 0 ? Math.round((data.correct / tot) * 100) : 0;
      document.getElementById(prefix + 'CorrectPercent').innerText = cp + '%';
      document.getElementById(prefix + 'WrongPercent').innerText   = (tot > 0 ? 100 - cp : 0) + '%';
      if (isConcept) updateConceptChart(data.correct ?? 0, data.wrong ?? 0);
      else           updateTopicChart(data.correct ?? 0, data.wrong ?? 0);
    });
}

// ── Filter Topics dropdown to show only topics for the selected concept ──
function syncNGNTopics(conceptName) {
  const topicSel = document.getElementById('conceptSelect');
  const topics = NGN_CONCEPT_TOPICS[conceptName] || NGN_TOPICS;
  swapDropdown(topicSel, topics);
  fetchStats('ngn_topic', topicSel.value);
}
function syncTradTopics(conceptName) {
  const topicSel = document.getElementById('conceptSelect');
  const topics = TRAD_CONCEPT_TOPICS[conceptName] || _TRAD_TOPICS;
  swapDropdown(topicSel, topics);
  fetchStats('concept', topicSel.value);
}
function syncOverallTopics(conceptName) {
  const topicSel = document.getElementById('conceptSelect');
  const topics = OVERALL_CONCEPT_TOPICS[conceptName] || [...new Set([..._TRAD_TOPICS, ...NGN_TOPICS])].sort();
  swapDropdown(topicSel, topics);
  fetchStats('overall_topic', topicSel.value);
}

// ── Swap dropdowns when filter mode changes ──
function syncTopicConceptDropdowns(mode) {
  const conceptSel = document.getElementById('topicSelect');
  const topicSel   = document.getElementById('conceptSelect');
  if (mode === 'ngn') {
    swapDropdown(conceptSel, NGN_CONCEPTS);
    syncNGNTopics(conceptSel.value);
    fetchStats('ngn_concept', conceptSel.value);
  } else if (mode === 'overall') {
    swapDropdown(conceptSel, OVERALL_CONCEPTS);
    syncOverallTopics(conceptSel.value);
    fetchStats('overall_concept', conceptSel.value);
  } else {
    swapDropdown(conceptSel, TRAD_CONCEPTS);
    syncTradTopics(conceptSel.value);
    fetchStats('topic', conceptSel.value);
  }
}

// Patch setStatsFilter to also sync dropdowns
const _origSetStatsFilter = setStatsFilter;
setStatsFilter = function(mode) {
  currentMode = mode;
  _origSetStatsFilter(mode);
  syncTopicConceptDropdowns(mode);
};

// Event listeners — use currentMode to pick correct fetch type
document.getElementById('topicSelect').addEventListener('change', function() {
  if (currentMode === 'ngn') {
    fetchStats('ngn_concept', this.value);
    syncNGNTopics(this.value);
  } else if (currentMode === 'overall') {
    fetchStats('overall_concept', this.value);
    syncOverallTopics(this.value);
  } else {
    fetchStats('topic', this.value);
    syncTradTopics(this.value);
  }
});
document.getElementById('conceptSelect').addEventListener('change', function() {
  if (currentMode === 'ngn')          fetchStats('ngn_topic',     this.value);
  else if (currentMode === 'overall') fetchStats('overall_topic', this.value);
  else                                fetchStats('concept',       this.value);
});

// Initial load — currentMode is 'overall' on page load
(function () {
  syncTopicConceptDropdowns(currentMode);
})();
</script>

<!-- Discard functions -->
<script>
function discardExam(examTaken) {
  Swal.fire({
    title: 'Discard Progress?',
    text: 'This will PERMANENTLY DELETE all answers and progress for this paused attempt.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, Discard',
    cancelButtonText: 'Keep it'
  }).then(async result => {
    if (result.isConfirmed) {
      try {
        const res = await (await fetch('ngn/cancel_exam.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ examTaken })
        })).json();
        if (res.ok) {
          Swal.fire({ title: 'Discarded', text: 'Temporary data cleared.', icon: 'success', timer: 1500, showConfirmButton: false });
          setTimeout(() => location.reload(), 1500);
        } else throw new Error(res.error || 'Server Error');
      } catch (err) { Swal.fire('Error', 'Failed to clear data.', 'error'); }
    }
  });
}

function discardExamMode(examTaken) {
  Swal.fire({
    title: 'Discard CAT Exam?',
    text: 'This will permanently delete all progress for this paused Exam Mode attempt.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#3b82f6',
    confirmButtonText: 'Yes, Discard',
    cancelButtonText: 'Keep it'
  }).then(async result => {
    if (result.isConfirmed) {
      try {
        const res = await (await fetch('exam/cancel_exam.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ examTaken })
        })).json();
        if (res.ok) {
          Swal.fire({ title: 'Discarded', text: 'Exam data cleared.', icon: 'success', timer: 1500, showConfirmButton: false });
          setTimeout(() => location.reload(), 1500);
        } else throw new Error(res.error || 'Server Error');
      } catch (err) { Swal.fire('Error', 'Failed to clear exam data.', 'error'); }
    }
  });
}
</script>

<!-- Concept cards: show immediately -->
<script>
document.querySelectorAll('.s-concept-card').forEach(c => c.classList.add('visible'));
</script>

<!-- NGN Modal Styles -->
<style>
.ngn-alert { background: #fff5f5; border-left: 4px solid #e53e3e; border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; display: flex; align-items: flex-start; gap: 10px; }
.ngn-alert i { color: #e53e3e; margin-top: 2px; }
.ngn-alert p { margin: 0; color: #c53030; font-size: 0.82rem; line-height: 1.5; }
.ngn-selectall-bar { display: flex; align-items: center; justify-content: space-between; background: #f8f9ff; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 10px 16px; margin-bottom: 14px; cursor: pointer; transition: background 0.2s; }
.ngn-selectall-bar:hover { background: #eef2ff; }
.ngn-selectall-bar label { cursor: pointer; font-weight: 600; font-size: 0.9rem; color: var(--s-primary); margin: 0; }
.ngn-item-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
@media (max-width: 576px) { .ngn-item-grid { grid-template-columns: 1fr; } }
.ngn-item-card { display: flex; align-items: center; gap: 12px; background: #fff; border: 2px solid #e9edf5; border-radius: 14px; padding: 12px 14px; cursor: pointer; transition: all 0.18s; user-select: none; }
.ngn-item-card:hover { border-color: #007CBF; background: #f0f5ff; transform: translateY(-1px); box-shadow: 0 4px 14px rgba(0,74,173,0.1); }
.ngn-item-card.selected { border-color: #02968A; background: #f0fdf9; }
.ngn-item-card .ngn-cb { width: 18px; height: 18px; accent-color: #02968A; flex-shrink: 0; pointer-events: none; }
.ngn-item-card .ngn-label { font-size: 0.88rem; font-weight: 600; color: #2d3748; flex-grow: 1; }
.ngn-item-card .ngn-count { background: linear-gradient(135deg,#007CBF,#02968A); color:#fff; font-size:0.72rem; font-weight:700; padding:3px 9px; border-radius:20px; white-space:nowrap; }
.ngn-topic-list { display: grid; grid-template-rows: repeat(5, auto); grid-auto-flow: column; grid-auto-columns: 1fr; gap: 8px; }
.ngn-topic-card { display: flex; align-items: center; gap: 10px; background: #fff; border: 2px solid #e9edf5; border-radius: 12px; padding: 10px 13px; cursor: pointer; transition: all 0.15s; user-select: none; }
.ngn-topic-card:hover { border-color: #007CBF; background: #f0f5ff; }
.ngn-topic-card.selected { border-color: #02968A; background: #f0fdf9; }
.ngn-topic-card .ngn-cb { width: 17px; height: 17px; accent-color: #02968A; pointer-events: none; }
.ngn-topic-card .ngn-label { font-size: 0.82rem; color: #2d3748; font-weight: 500; flex-grow: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ngn-topic-card .ngn-count { background: linear-gradient(135deg,#007CBF,#02968A); color:#fff; font-size:0.68rem; font-weight:700; padding:2px 8px; border-radius:20px; white-space:nowrap; }
.ngn-steps { display:flex; align-items:center; gap:6px; }
.ngn-dot { width:8px; height:8px; border-radius:50%; background:rgba(255,255,255,0.35); transition:background 0.2s; }
.ngn-dot.active { background:#fff; width:22px; border-radius:4px; }
.step-badge { background: rgba(255,255,255,0.2); color: #fff; font-size: 0.68rem; font-weight: 600; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.4px; display: inline-block; margin-bottom: 6px; }
.ngn-btn-next { background:linear-gradient(135deg,#007CBF,#02968A); color:#fff; border:none; border-radius:10px; padding:9px 22px; font-weight:600; font-size:0.9rem; transition:opacity 0.2s,transform 0.15s; cursor:pointer; }
.ngn-btn-next:disabled { opacity:.45; cursor:not-allowed; }
.ngn-btn-next:not(:disabled):hover { opacity:.9; transform:translateY(-1px); }
.ngn-btn-back { background:transparent; border:2px solid #cbd5e0; border-radius:10px; padding:8px 18px; font-weight:600; font-size:0.9rem; color:#4a5568; cursor:pointer; }
.ngn-btn-back:hover { background:#f7fafc; }
.ngn-btn-cancel { background:transparent; border:none; color:#94a3b8; font-size:0.88rem; padding:8px 12px; cursor:pointer; }
.ngn-loading { text-align:center; padding:36px 0; color:#94a3b8; font-size:0.9rem; }
.ngn-loading i { font-size:1.5rem; margin-bottom:8px; display:block; }
</style>

<!-- NGN Modal HTML — custom modal system (Bootstrap-free) -->
<div class="s-modal-backdrop" id="ngnModalBackdrop" role="dialog" aria-modal="true" aria-hidden="true">
  <div class="s-modal s-modal--lg">

    <!-- STEP 1: Concepts -->
    <div id="ngnStep1">
      <div class="s-modal-header s-modal-header--gradient">
        <div style="flex:1;">
          <span class="step-badge">Step 1 of 2</span>
          <h5 class="s-modal-title"><i class="bi bi-lightbulb-fill me-2"></i>Choose Subjects</h5>
        </div>
        <div class="d-flex align-items-center gap-2">
          <div class="ngn-steps"><div class="ngn-dot active"></div><div class="ngn-dot"></div></div>
          <button type="button" class="s-modal-close" data-s-dismiss="modal" aria-label="Close">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
      </div>
      <div class="s-modal-body">
        <div class="ngn-alert"><i class="bi bi-exclamation-circle-fill"></i>
          <p><strong>Important:</strong> You must select at least one subject to proceed. Your exam will only include questions from the subjects and topics you choose.</p>
        </div>
        <div class="ngn-selectall-bar" onclick="toggleAllConcepts()">
          <label for="selectAllConcepts"><i class="bi bi-check2-all me-2" style="color:#007CBF;"></i>Select All Subjects</label>
          <input class="ngn-cb" type="checkbox" id="selectAllConcepts" onclick="event.stopPropagation();toggleAllConcepts()">
        </div>
        <div id="conceptLoadingMsg" class="ngn-loading"><i class="bi bi-arrow-repeat" style="display:inline-block;animation:spin .8s linear infinite;"></i>Loading subjects...</div>
        <div id="conceptGrid" class="ngn-item-grid d-none"></div>
      </div>
      <div class="s-modal-footer s-modal-footer--between">
        <button class="ngn-btn-cancel" data-s-dismiss="modal">Cancel</button>
        <button class="ngn-btn-next" id="nextToTopicBtn" disabled>Next <i class="bi bi-arrow-right ms-1"></i></button>
      </div>
    </div>

    <!-- STEP 2: Topics -->
    <div id="ngnStep2" class="d-none">
      <div class="s-modal-header s-modal-header--gradient">
        <div style="flex:1;">
          <span class="step-badge">Step 2 of 2</span>
          <h5 class="s-modal-title"><i class="bi bi-list-check me-2"></i>Choose Topics</h5>
        </div>
        <div class="d-flex align-items-center gap-2">
          <div class="ngn-steps"><div class="ngn-dot"></div><div class="ngn-dot active"></div></div>
          <button type="button" class="s-modal-close" data-s-dismiss="modal" aria-label="Close">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
      </div>
      <div class="s-modal-body">
        <div class="ngn-alert"><i class="bi bi-exclamation-circle-fill"></i>
          <p><strong>Important:</strong> Only topics belonging to your selected subjects are shown. You must select at least one topic.</p>
        </div>
        <div class="ngn-selectall-bar" onclick="toggleAllTopics()">
          <label for="selectAllTopics"><i class="bi bi-check2-all me-2" style="color:#007CBF;"></i>Select All Topics</label>
          <input class="ngn-cb" type="checkbox" id="selectAllTopics" onclick="event.stopPropagation();toggleAllTopics()">
        </div>
        <div id="topicLoadingMsg" class="ngn-loading d-none"><i class="bi bi-arrow-repeat" style="display:inline-block;animation:spin .8s linear infinite;"></i>Loading topics...</div>
        <div id="topicList" class="ngn-topic-list"></div>
      </div>
      <div class="s-modal-footer s-modal-footer--between">
        <button class="ngn-btn-back" id="backToConceptBtn"><i class="bi bi-arrow-left me-1"></i>Back</button>
        <form id="ngnStartForm" method="POST" action="ngn/start_filtered_exam.php" style="display:inline;">
          <input type="hidden" name="concepts" id="hiddenConcepts">
          <input type="hidden" name="topics" id="hiddenTopics">
          <button type="submit" class="ngn-btn-next" id="startExamBtn" disabled><i class="bi bi-play-fill me-1"></i>Start Exam</button>
        </form>
      </div>
    </div>

  </div>
</div>

<!-- NGN Modal JS -->
<script>
function escHtml(str) { return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function openNGNModal() {
  document.getElementById('ngnStep1').classList.remove('d-none');
  document.getElementById('ngnStep2').classList.add('d-none');
  SModal.open('ngnModalBackdrop');
  loadConcepts();
}
function loadConcepts() {
  const grid = document.getElementById('conceptGrid');
  const loading = document.getElementById('conceptLoadingMsg');
  grid.classList.add('d-none'); grid.innerHTML = ''; loading.classList.remove('d-none');
  fetch('get_ngn_concepts.php').then(r => r.json()).then(data => {
    loading.classList.add('d-none'); grid.classList.remove('d-none');
    data.forEach(item => {
      const card = document.createElement('div');
      card.className = 'ngn-item-card';
      card.innerHTML = `<input class="ngn-cb concept-cb" type="checkbox" value="${escHtml(item.concept)}"><span class="ngn-label">${escHtml(item.concept)}</span><span class="ngn-count">${item.count} Qs</span>`;
      card.addEventListener('click', function() { const cb = this.querySelector('.concept-cb'); cb.checked = !cb.checked; this.classList.toggle('selected', cb.checked); updateNextBtn(); });
      grid.appendChild(card);
    });
    updateNextBtn();
  }).catch(() => { loading.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-circle-fill me-1"></i>Failed to load.</span>'; });
}
function toggleAllConcepts() {
  const allCb = document.getElementById('selectAllConcepts');
  const cbs = document.querySelectorAll('.concept-cb');
  const newState = Array.from(cbs).some(c => !c.checked);
  allCb.checked = newState;
  cbs.forEach(cb => { cb.checked = newState; cb.closest('.ngn-item-card').classList.toggle('selected', newState); });
  updateNextBtn();
}
function updateNextBtn() {
  const checked = document.querySelectorAll('.concept-cb:checked').length;
  const total   = document.querySelectorAll('.concept-cb').length;
  document.getElementById('nextToTopicBtn').disabled = checked === 0;
  const sa = document.getElementById('selectAllConcepts');
  sa.checked = total > 0 && checked === total;
  sa.indeterminate = checked > 0 && checked < total;
}
document.getElementById('nextToTopicBtn').addEventListener('click', function() {
  const selectedConcepts = Array.from(document.querySelectorAll('.concept-cb:checked')).map(c => c.value);
  document.getElementById('ngnStep1').classList.add('d-none');
  document.getElementById('ngnStep2').classList.remove('d-none');
  const list = document.getElementById('topicList');
  const loading = document.getElementById('topicLoadingMsg');
  list.innerHTML = ''; loading.classList.remove('d-none');
  fetch('get_ngn_topics.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ concepts: selectedConcepts }) })
    .then(r => r.json()).then(data => {
      loading.classList.add('d-none');
      if (!data.length) { list.style.display = 'block'; list.innerHTML = '<p class="text-center text-muted py-3">No topics found.</p>'; return; }
      const numCols = Math.min(Math.max(Math.ceil(data.length / 5), 1), 4);
      list.style.gridTemplateColumns = `repeat(${numCols}, 1fr)`;
      list.style.gridTemplateRows = `repeat(${Math.ceil(data.length / numCols)}, auto)`;
      data.forEach(item => {
        const card = document.createElement('div');
        card.className = 'ngn-topic-card';
        card.innerHTML = `<input class="ngn-cb topic-cb" type="checkbox" value="${escHtml(item.topic)}" data-concept="${escHtml(item.concept)}"><span class="ngn-label">${escHtml(item.topic)}</span><span class="ngn-count">${item.count} Qs</span>`;
        card.addEventListener('click', function() { const cb = this.querySelector('.topic-cb'); cb.checked = !cb.checked; this.classList.toggle('selected', cb.checked); updateStartBtn(); });
        list.appendChild(card);
      });
      updateStartBtn();
    }).catch(() => { loading.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-circle-fill me-1"></i>Failed to load.</span>'; });
});
function toggleAllTopics() {
  const allCb = document.getElementById('selectAllTopics');
  const cbs = document.querySelectorAll('.topic-cb');
  const newState = Array.from(cbs).some(c => !c.checked);
  allCb.checked = newState;
  cbs.forEach(cb => { cb.checked = newState; cb.closest('.ngn-topic-card').classList.toggle('selected', newState); });
  updateStartBtn();
}
function updateStartBtn() {
  const checked = document.querySelectorAll('.topic-cb:checked').length;
  const total   = document.querySelectorAll('.topic-cb').length;
  document.getElementById('startExamBtn').disabled = checked === 0;
  const sa = document.getElementById('selectAllTopics');
  sa.checked = total > 0 && checked === total;
  sa.indeterminate = checked > 0 && checked < total;
}
document.getElementById('backToConceptBtn').addEventListener('click', function() {
  document.getElementById('ngnStep2').classList.add('d-none');
  document.getElementById('ngnStep1').classList.remove('d-none');
});
document.getElementById('ngnStartForm').addEventListener('submit', function() {
  document.getElementById('hiddenConcepts').value = JSON.stringify(Array.from(document.querySelectorAll('.concept-cb:checked')).map(c => c.value));
  document.getElementById('hiddenTopics').value   = JSON.stringify(Array.from(document.querySelectorAll('.topic-cb:checked')).map(c => c.value));
});
</script>

<?php if ($fetch['tutorial'] === null): ?>
<?php include '_layout/tutorial.php'; ?>
<?php endif; ?>

</body>
</html>
