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
    'Adult Health'               => 'fa-solid fa-user',
    'Child Health'               => 'fa-solid fa-baby',
    'Critical Care'              => 'fa-solid fa-heart-pulse',
    'Fundamentals'               => 'fa-solid fa-book-medical',
    'Leadership and Management'  => 'fa-solid fa-user-tie',
    'Mental Health'              => 'fa-solid fa-brain',
    'Pharmacology'               => 'fa-solid fa-pills',
    'Maternal and Newborn Health'=> 'fa-solid fa-baby-carriage',
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
    'Critical Care'          => 'fa-solid fa-heart-pulse',
    'Endocrine'              => 'fa-solid fa-syringe',
    'Fluid and Electrolytes' => 'fa-solid fa-droplet',
    'Fundamentals'           => 'fa-solid fa-book-medical',
    'Heart Failure'          => 'fa-solid fa-heart-crack',
    'Pharmacology'           => 'fa-solid fa-pills',
    'Respiratory'            => 'fa-solid fa-lungs',
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

    <!-- Traditional QBanks -->
    <?php
    $tq = mysqli_query($con, "SELECT * FROM bundlelist GROUP BY name ORDER BY id ASC");
    $_tut_trad_first = true;
    while ($brow = mysqli_fetch_array($tq)) {
      if (stripos($brow['name'], 'NGN') !== false) continue; // skip all NGN rows, handled above
      $cleanName = str_replace(['NARC ', '(Soon)'], '', $brow['name']);
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

    <!-- NGN QBanks -->
    <div class="col-lg-4 col-md-6 col-12" id="tut-ngn-card">
      <div class="s-mode-hero-card" style="--mhc-color:#0D9488; --mhc-bg:rgba(13,148,136,.06);">
        <div class="s-mhc-icon" style="background:rgba(13,148,136,.12);">
          <i class="bi bi-lightbulb-fill" style="color:#0D9488;"></i>
        </div>
        <div class="s-mhc-body">
          <div class="s-mhc-title">NGN QBanks</div>
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

    <!-- CAT Exam Mode -->
    <div class="col-lg-4 col-md-6 col-12" id="tut-cat-card">
      <div class="s-mode-hero-card" style="--mhc-color:#6366f1; --mhc-bg:rgba(99,102,241,.05);">
        <div class="s-mhc-icon" style="background:rgba(99,102,241,.1);">
          <i class="bi bi-activity" style="color:#6366f1;"></i>
        </div>
        <div class="s-mhc-body">
          <div class="s-mhc-title">Exam Mode</div>
          <div class="s-mhc-desc">A simulation of the NCLEX exam with a mix of traditional and NGN questions. IRT engine adjusts difficulty in real time. Auto-terminates on pass/fail.</div>
          <div class="s-mhc-tag" style="background:rgba(99,102,241,.1);color:#6366f1;">IRT Adaptive</div>
        </div>
        <div class="s-mhc-footer">
          <?php if ($isPackege2): ?>
            <a href="exam/index.php" class="s-btn w-100" style="background:#6366f1;color:#fff;justify-content:center;">
              <i class="bi bi-lightning-charge-fill"></i> Start Exam
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

  <!-- Performance Over Time (replaces Usage/Performance donuts) -->
  <div class="s-chart-card mb-4">
    <div class="s-chart-card-header s-tl-header">
      <span class="s-chart-card-title"><i class="bi bi-graph-up-arrow"></i> Performance Over Time</span>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="s-badge <?= $overallAccuracy >= 75 ? 's-badge-teal' : 's-badge-red' ?>" id="badge-timeline-pct">
          <i class="bi <?= $overallAccuracy >= 75 ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill' ?> me-1"></i><?= $overallAccuracy ?>% Accuracy
        </span>
        <div class="s-stat-filter-bar" role="group" aria-label="Time period" id="timeline-period-bar">
          <button class="s-stat-tab active" data-period="day" onclick="setTimelinePeriod('day')">Daily</button>
          <button class="s-stat-tab" data-period="week" onclick="setTimelinePeriod('week')">Weekly</button>
          <button class="s-stat-tab" data-period="month" onclick="setTimelinePeriod('month')">Monthly</button>
        </div>
      </div>
    </div>
    <div class="s-chart-body" style="flex-direction:column; align-items:stretch; gap:0;">
      <div class="s-tl-summary" id="timelineSummary">
        <div class="s-tl-kpi">
          <span class="s-tl-kpi-label">Questions answered</span>
          <span class="s-tl-kpi-value" id="tlTotal">—</span>
        </div>
        <div class="s-tl-kpi">
          <span class="s-tl-kpi-label" id="tlBestLabel">Best day</span>
          <span class="s-tl-kpi-value" id="tlBest">—</span>
        </div>
        <div class="s-tl-kpi">
          <span class="s-tl-kpi-label">At the 75% pass mark</span>
          <span class="s-tl-kpi-value" id="tlPassing">—</span>
        </div>
      </div>
      <div class="s-tl-legend" id="timelineLegend" aria-label="Chart legend">
        <span class="s-tl-legend-item"><span class="s-tl-key s-tl-key-line"></span>Your accuracy</span>
        <span class="s-tl-legend-item"><span class="s-tl-key s-tl-key-pass"></span>Pass mark (75%)</span>
        <span class="s-tl-legend-item"><span class="s-tl-key s-tl-key-zone"></span>Passing zone (75–100%)</span>
        <span class="s-tl-legend-item"><span class="s-tl-key s-tl-key-bar"></span>Questions answered</span>
      </div>
      <div id="timelineChart" style="width:100%;height:330px;"></div>
      <div id="timelineEmpty" class="s-timeline-empty d-none">
        <i class="bi bi-bar-chart-line"></i>
        <p>No exam activity yet for this view. Complete a practice session or exam to see your trend here.</p>
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
  <div class="s-chart-card mb-4 d-none d-md-block">
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

  // Plain-language guidance shown on each card — tells a non-technical
  // student what the score means and what to do next.
  $rcGuide = [
    'ready'   => "You're at passing level. Keep it up!",
    'ontrack' => "Almost there — a little more practice to reach 75%.",
    'needs'   => "Keep practicing to reach the 75% passing goal.",
    'none'    => "Take an exam to start tracking your progress.",
  ];

  // Helper macro to render readiness cards.
  // Cards with no exam data are hidden from the main grid.
  // A collapsible strip below the grid lists the untaken concepts by name.
  function renderReadinessCards(array $conceptMap, array $conceptReadiness, array $rcColors, array $rcGuide, string $uid = ''): void {
    $hidden = [];
    foreach ($conceptReadiness as $concept => $data) {
      if ($data['attempts'] === 0) $hidden[] = $concept;
    }

    foreach ($conceptReadiness as $concept => $data):
      if ($data['attempts'] === 0) continue;
      $icon = $conceptMap[$concept] ?? 'bi bi-circle-fill';
      $rc   = $rcColors[$data['status']];
  ?>
    <div class="col-xl-3 col-md-6 col-12">
      <div class="s-rc" data-status="<?= $data['status'] ?>">

        <div class="s-rc-top">
          <span class="s-rc-icon"><i class="<?= $icon ?>"></i></span>
          <span class="s-rc-badge"><span class="s-rc-dot"></span><?= $rc['label'] ?></span>
        </div>

        <div class="s-rc-name"><?= htmlspecialchars($concept) ?></div>

        <div class="s-rc-metric">
          <span class="s-rc-avg"><?= $data['avg'] ?><span>%</span></span>
          <span class="s-rc-metric-lbl">average score</span>
        </div>

        <div class="s-rc-bar">
          <div class="s-rc-bar-fill" data-w="<?= $data['avg'] ?>"></div>
          <span class="s-rc-bar-goal" title="Passing goal: 75%"></span>
        </div>
        <div class="s-rc-scale">
          <span>Your score</span>
          <span class="s-rc-goal-lbl">Goal&nbsp;75%</span>
        </div>

        <p class="s-rc-guide"><?= $rcGuide[$data['status']] ?></p>

        <div class="s-rc-recent">
          <span class="s-rc-recent-lbl">Last <?= $data['attempts'] ?> exam<?= $data['attempts'] > 1 ? 's' : '' ?></span>
          <span class="s-rc-recent-scores">
            <?php
              $ordered = array_reverse($data['scores']);
              $lastIdx = count($ordered) - 1;
              foreach ($ordered as $i => $sc):
            ?>
            <?php if ($i > 0): ?><i class="bi bi-arrow-right s-rc-arrow"></i><?php endif; ?>
            <span class="s-rc-score<?= $i === $lastIdx ? ' is-latest' : '' ?>"><?= round($sc) ?>%</span>
            <?php endforeach; ?>
          </span>
        </div>

      </div>
    </div>
  <?php endforeach;

    if (!empty($hidden)):
      $panelId = 'rc-untaken-' . ($uid ?: 'overall');
      $count   = count($hidden);
  ?>
    <div class="col-12">
      <div class="s-rc-untaken">
        <button class="s-rc-untaken-toggle" onclick="rcToggleUntaken('<?= $panelId ?>', this)" aria-expanded="false">
          <span class="s-rc-untaken-icon"><i class="bi bi-hourglass-split"></i></span>
          <span class="s-rc-untaken-label"><?= $count ?> concept<?= $count > 1 ? 's' : '' ?> not started yet</span>
          <i class="bi bi-chevron-down s-rc-untaken-chevron"></i>
        </button>
        <div class="s-rc-untaken-panel" id="<?= $panelId ?>">
          <?php foreach ($hidden as $name):
            $chipIcon = $conceptMap[$name] ?? 'bi bi-circle';
          ?>
          <span class="s-rc-untaken-chip">
            <i class="<?= $chipIcon ?>"></i><?= htmlspecialchars($name) ?>
          </span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif;
  }
  ?>

  <!-- Overall readiness cards — date-aware (daily reset + backtracking) -->
  <div id="readiness-overall-wrap">
    <div class="s-rd-nav" id="readiness-overall-nav">
      <div class="s-rd-period s-stat-filter-bar" role="group" aria-label="Progress period">
        <button class="s-stat-tab active" data-rperiod="day"   onclick="setOverallPeriod('day')">Day</button>
        <button class="s-stat-tab"        data-rperiod="week"  onclick="setOverallPeriod('week')">Week</button>
        <button class="s-stat-tab"        data-rperiod="month" onclick="setOverallPeriod('month')">Month</button>
      </div>
      <div class="s-rd-datenav">
        <button class="s-rd-arrow" id="rd-prev" onclick="shiftOverall(-1)" title="Previous"><i class="bi bi-chevron-left"></i></button>
        <label class="s-rd-daterange" title="Pick a date to jump to">
          <i class="bi bi-calendar3"></i>
          <span id="rd-range-label">Today</span>
          <input type="date" id="rd-date-input" class="s-rd-date-input">
        </label>
        <button class="s-rd-arrow" id="rd-next" onclick="shiftOverall(1)" title="Next"><i class="bi bi-chevron-right"></i></button>
        <button class="s-rd-today" id="rd-today" onclick="goOverallToday()"><i class="bi bi-arrow-counterclockwise"></i> Today</button>
      </div>
    </div>
    <div class="row g-3 mb-4" id="readiness-cards-overall">
      <div class="col-12 text-center py-4" id="readiness-overall-loading" style="color:var(--s-muted);">
        <i class="bi bi-arrow-repeat" style="font-size:1.4rem;"></i>
      </div>
    </div>
  </div>

  <style>
  .s-rd-nav {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 10px; margin-bottom: 16px;
    background: #fff; border: 1px solid var(--s-border, #e2e8f0);
    border-radius: 12px; padding: 8px 12px;
  }
  .s-rd-datenav { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
  .s-rd-arrow {
    width: 32px; height: 32px; flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center;
    border: 1px solid var(--s-border, #e2e8f0); border-radius: 8px;
    background: #fff; color: var(--s-primary, #007CBF); cursor: pointer;
    transition: background .15s, opacity .15s;
  }
  .s-rd-arrow:hover:not(:disabled) { background: rgba(0,124,191,.08); }
  .s-rd-arrow:disabled { opacity: .35; cursor: not-allowed; }
  .s-rd-daterange {
    position: relative; display: inline-flex; align-items: center; gap: 7px;
    border: 1px solid var(--s-border, #e2e8f0); border-radius: 8px;
    padding: 6px 12px; min-width: 150px; justify-content: center;
    font-size: 0.82rem; font-weight: 600; color: #334155; cursor: pointer;
    background: #f8fafc;
  }
  .s-rd-daterange:hover { background: #f1f5f9; }
  .s-rd-daterange i { color: var(--s-primary, #007CBF); }
  /* Native date input overlays the label so the whole pill opens the picker */
  .s-rd-date-input {
    position: absolute; inset: 0; width: 100%; height: 100%;
    opacity: 0; cursor: pointer; border: 0; padding: 0;
  }
  .s-rd-today {
    display: inline-flex; align-items: center; gap: 5px;
    border: 1px solid var(--s-border, #e2e8f0); border-radius: 8px;
    background: #fff; color: #64748b; cursor: pointer;
    font-size: 0.78rem; font-weight: 600; padding: 6px 12px;
    transition: background .15s, color .15s;
  }
  .s-rd-today:hover { background: rgba(0,124,191,.08); color: var(--s-primary, #007CBF); }
  .s-rd-today.is-hidden { display: none; }
  @media (max-width: 576px) {
    .s-rd-nav { padding: 8px; }
    .s-rd-period { width: 100%; }
    .s-rd-datenav { width: 100%; justify-content: space-between; }
  }
  </style>

  <!-- Traditional readiness cards (hidden by default) -->
  <div class="row g-3 mb-4" id="readiness-cards-trad" style="display:none;">
    <?php renderReadinessCards($readinessConceptMap, $conceptReadiness, $rcColors, $rcGuide, 'trad'); ?>
  </div>

  <!-- NGN readiness cards (hidden by default) -->
  <div class="row g-3 mb-4" id="readiness-cards-ngn" style="display:none;">
    <?php renderReadinessCards($ngnConceptMap, $ngnConceptReadiness, $rcColors, $rcGuide, 'ngn'); ?>
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

// ── Timeline chart — Performance Over Time ──
// Two stacked panels on one shared time axis: Accuracy % on top (the headline,
// with the 75% pass mark), Questions answered as a quiet bar strip below.
// Kept as separate panels on purpose — one y-scale per panel, no dual axis.
let timelineChart  = echarts.init(document.getElementById('timelineChart'));
let timelinePeriod  = 'day';
let lastTimeline    = [];

const TL_PASS = 75;
const TL = {
  accent:   '#0D9488',
  bar:      '#CBD5E1',
  barHover: '#94A3B8',
  pass:     '#64748B',
  below:    '#D72638',
  text:     '#0F172A',
  muted:    '#64748B',
  faint:    '#94A3B8',
  grid:     'rgba(15,23,42,0.06)',
  border:   '#E2E8F0'
};
const TL_BUCKET_MS = { day: 864e5, week: 7 * 864e5, month: 30 * 864e5 };

// Narrow cards get a tighter layout (smaller gutters, fewer date labels)
function timelineCompact() {
  const el = document.getElementById('timelineChart');
  return (el ? el.clientWidth : window.innerWidth) < 576;
}

window.addEventListener('resize', () => {
  if (!timelineChart) return;
  // Rebuild so the layout switches when crossing the mobile breakpoint
  if (lastTimeline.length) timelineChart.setOption(buildTimelineOption(lastTimeline, timelinePeriod), true);
  timelineChart.resize();
});

const MONTH_NAMES = ['January','February','March','April','May','June','July','August','September','October','November','December'];

// Converts an ISO year-week bucket (e.g. "2026-W26") into that week's Monday date
function isoWeekToMonday(isoWeek) {
  const [yearStr, weekStr] = isoWeek.split('-W');
  const year = parseInt(yearStr, 10);
  const week = parseInt(weekStr, 10);
  const simple = new Date(year, 0, 1 + (week - 1) * 7);
  const dayOfWeek = simple.getDay() || 7; // Monday=1 .. Sunday=7
  simple.setDate(simple.getDate() - dayOfWeek + 1);
  return simple;
}

// Converts a raw bucket label ("2026-06-24" / "2026-W26" / "2026-06") into a Date
function bucketToDate(rawLabel, period) {
  if (period === 'month') {
    const [y, m] = rawLabel.split('-').map(Number);
    return new Date(y, m - 1, 1);
  }
  if (period === 'week') return isoWeekToMonday(rawLabel);
  const [y, m, d] = rawLabel.split('-').map(Number);
  return new Date(y, m - 1, d);
}

// Full, human-readable bucket name for the tooltip
function formatBucketLabel(rawLabel, period) {
  const dt = bucketToDate(rawLabel, period);
  if (period === 'month') return `${MONTH_NAMES[dt.getMonth()]} ${dt.getFullYear()}`;
  const day = `${MONTH_NAMES[dt.getMonth()]} ${dt.getDate()}, ${dt.getFullYear()}`;
  return period === 'week' ? `Week of ${day}` : day;
}

// Short axis tick label: "Mar 24" (day/week) or "Mar 2026" (month)
function formatAxisDate(ms, period) {
  const dt = new Date(ms);
  const mon = MONTH_NAMES[dt.getMonth()].slice(0, 3);
  return period === 'month' ? `${mon} ${dt.getFullYear()}` : `${mon} ${dt.getDate()}`;
}

function tlStatusPill(pass) {
  const color = pass ? TL.accent : TL.below;
  const bg    = pass ? 'rgba(13,148,136,0.12)' : 'rgba(215,38,56,0.10)';
  const label = pass ? '&#10003; At pass mark' : '&#9888; Below pass mark';
  return `<span style="display:inline-block;padding:2px 8px;border-radius:20px;background:${bg};color:${color};font-size:11px;font-weight:600;">${label}</span>`;
}

function buildTimelineOption(timeline, period) {
  const compact = timelineCompact();
  const bucket  = TL_BUCKET_MS[period];
  const times   = timeline.map(t => bucketToDate(t.label, period).getTime());
  const barPts  = timeline.map((t, i) => [times[i], t.answered]);
  const maxAns  = Math.max(1, ...timeline.map(t => t.answered));

  // Break the accuracy line across long idle stretches (2+ weeks for daily,
  // 4+ weeks for weekly, 3+ months for monthly) instead of drawing a straight
  // line through time with no data. accIdx maps line points back to timeline.
  const maxGap = { day: 14, week: 4, month: 3 }[period] * bucket;
  const accPts = [];
  const accIdx = [];
  timeline.forEach((t, i) => {
    if (i > 0 && times[i] - times[i - 1] > maxGap) {
      accPts.push([(times[i] + times[i - 1]) / 2, null]);
      accIdx.push(-1);
    }
    accPts.push([times[i], t.accuracy]);
    accIdx.push(i);
  });

  // Pad half a bucket on each side so the first/last bars aren't clipped
  const xMin   = times[0] - bucket / 2;
  const xMax   = times[times.length - 1] + bucket / 2;

  // Panel geometry (px) — accuracy panel on top, volume strip below
  const left   = compact ? 36 : 46;
  const right  = compact ? 10 : 16;
  const accTop = 30, accH = compact ? 168 : 182;
  const volTop = accTop + accH + 40, volH = 52;

  // Bar width from the real pixel size of one bucket (time axes otherwise
  // squeeze daily bars down to hairlines)
  const plotW    = (document.getElementById('timelineChart').clientWidth || 600) - left - right;
  const bucketPx = plotW * bucket / (xMax - xMin);
  const barWidth = Math.max(6, Math.min(16, Math.round(bucketPx * 0.6)));

  const sharedX = {
    type: 'time',
    min: xMin, max: xMax,
    axisLine: { show: false },
    axisTick: { show: false },
    splitLine: { show: false },
    axisPointer: { show: true, type: 'line', lineStyle: { color: TL.border, width: 1 }, label: { show: false } }
  };
  const panelTitle = (text, top) => ({
    text, left: left - 2, top,
    textStyle: { fontSize: 11, fontWeight: 600, color: TL.muted, fontFamily: ECHART_FONT }
  });

  return {
    animation: true,
    animationDuration: 600,
    animationEasing: 'cubicOut',
    textStyle: { fontFamily: ECHART_FONT },
    title: [
      panelTitle('Accuracy', 0),
      panelTitle('Questions answered', volTop - 24)
    ],
    axisPointer: { link: [{ xAxisIndex: 'all' }] },
    tooltip: {
      trigger: 'axis',
      backgroundColor: '#fff',
      borderColor: TL.border,
      borderWidth: 1,
      padding: [10, 12],
      extraCssText: 'border-radius:10px;box-shadow:0 8px 24px rgba(15,23,42,.10);min-width:180px;',
      textStyle: { color: TL.text, fontSize: 12, fontFamily: ECHART_FONT },
      formatter: params => {
        // Bars carry the plain timeline index; line points go through accIdx
        const p = params.find(x => x.seriesType === 'bar') || params[0];
        const t = timeline[p.seriesType === 'bar' ? p.dataIndex : accIdx[p.dataIndex]];
        if (!t) return '';
        const row = (k, v) =>
          `<div style="display:flex;justify-content:space-between;gap:18px;line-height:1.7;">` +
          `<span style="color:${TL.muted}">${k}</span><b style="font-variant-numeric:tabular-nums">${v}</b></div>`;
        const hasAcc = t.accuracy !== null;
        return `<div style="font-size:11px;color:${TL.faint};margin-bottom:4px;">${formatBucketLabel(t.label, period)}</div>` +
               row('Accuracy', hasAcc ? t.accuracy + '%' : '—') +
               row('Correct', `${t.correct} of ${t.answered}`) +
               (hasAcc ? `<div style="margin-top:8px;">${tlStatusPill(t.accuracy >= TL_PASS)}</div>` : '');
      }
    },
    grid: [
      { left, right, top: accTop, height: accH },
      { left, right, top: volTop, height: volH }
    ],
    xAxis: [
      { ...sharedX, gridIndex: 0, axisLabel: { show: false } },
      {
        ...sharedX, gridIndex: 1,
        splitNumber: compact ? 3 : 7,
        axisLine: { show: true, lineStyle: { color: TL.border } },
        axisLabel: {
          color: TL.faint, fontSize: 10, margin: 10, hideOverlap: true,
          formatter: v => formatAxisDate(v, period)
        }
      }
    ],
    yAxis: [
      {
        gridIndex: 0, type: 'value', min: 0, max: 100, interval: 25,
        axisLine: { show: false }, axisTick: { show: false },
        splitLine: { lineStyle: { color: TL.grid } },
        axisLabel: { color: TL.faint, fontSize: 10, formatter: '{value}%' }
      },
      {
        gridIndex: 1, type: 'value', min: 0, max: maxAns, interval: maxAns,
        axisLine: { show: false }, axisTick: { show: false },
        splitLine: { show: false },
        axisLabel: { color: TL.faint, fontSize: 10, showMinLabel: false }
      }
    ],
    series: [
      {
        name: 'Accuracy',
        type: 'line',
        xAxisIndex: 0, yAxisIndex: 0,
        data: accPts,
        connectNulls: false,
        smooth: false,
        symbol: 'circle',
        symbolSize: 8,
        lineStyle: { width: 2, color: TL.accent },
        itemStyle: { color: TL.accent, borderColor: '#fff', borderWidth: 2 },
        emphasis: { scale: 1.4, itemStyle: { borderWidth: 2 } },
        areaStyle: {
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: 'rgba(13,148,136,0.18)' },
            { offset: 1, color: 'rgba(13,148,136,0)' }
          ])
        },
        markArea: {
          silent: true,
          itemStyle: { color: 'rgba(13,148,136,0.07)' },
          data: [[{ yAxis: TL_PASS }, { yAxis: 100 }]]
        },
        markLine: {
          silent: true,
          symbol: 'none',
          lineStyle: { color: TL.pass, type: [4, 4], width: 1 },
          label: {
            position: 'insideStartTop',
            formatter: `Pass mark ${TL_PASS}%`,
            color: TL.pass, fontSize: 10, fontWeight: 600
          },
          data: [{ yAxis: TL_PASS }]
        }
      },
      {
        name: 'Questions answered',
        type: 'bar',
        xAxisIndex: 1, yAxisIndex: 1,
        data: barPts,
        barWidth,
        barMinHeight: 2,
        itemStyle: { color: TL.bar, borderRadius: [4, 4, 0, 0] },
        emphasis: { itemStyle: { color: TL.barHover } }
      }
    ]
  };
}

// KPI row above the chart — summarises exactly what the chart is showing
function updateTimelineSummary(timeline) {
  const total   = timeline.reduce((s, t) => s + t.answered, 0);
  const scored  = timeline.filter(t => t.accuracy !== null);
  const passing = scored.filter(t => t.accuracy >= TL_PASS).length;
  const unit    = { day: 'day', week: 'week', month: 'month' }[timelinePeriod];
  // Best bucket; ties go to the one with more questions answered
  const best    = scored.reduce((b, t) =>
    !b || t.accuracy > b.accuracy || (t.accuracy === b.accuracy && t.answered > b.answered) ? t : b, null);

  document.getElementById('tlTotal').textContent = total.toLocaleString();
  document.getElementById('tlBestLabel').textContent = `Best ${unit}`;
  document.getElementById('tlBest').innerHTML = !best ? '—'
    : `${best.accuracy}%<span class="s-tl-kpi-sub">${formatAxisDate(bucketToDate(best.label, timelinePeriod).getTime(), timelinePeriod)}</span>`;
  document.getElementById('tlPassing').innerHTML =
    `${passing}<span class="s-tl-kpi-sub">of ${scored.length} ${unit}s</span>`;
}

function renderTimelineChart(timeline) {
  const emptyEl   = document.getElementById('timelineEmpty');
  const chartEl   = document.getElementById('timelineChart');
  const summaryEl = document.getElementById('timelineSummary');
  const legendEl  = document.getElementById('timelineLegend');
  lastTimeline = timeline || [];
  if (!timeline || timeline.length === 0) {
    [chartEl, summaryEl, legendEl].forEach(el => el.classList.add('d-none'));
    emptyEl.classList.remove('d-none');
    return;
  }
  [chartEl, summaryEl, legendEl].forEach(el => el.classList.remove('d-none'));
  emptyEl.classList.add('d-none');
  updateTimelineSummary(timeline);
  timelineChart.setOption(buildTimelineOption(timeline, timelinePeriod), true);
  timelineChart.resize();
}

function loadTimelineStats(mode, period) {
  fetch(`get_timeline_stats.php?mode=${mode}&period=${period}`)
    .then(res => res.json())
    .then(data => renderTimelineChart(data.timeline || []))
    .catch(() => renderTimelineChart([]));
}

function setTimelinePeriod(period) {
  timelinePeriod = period;
  document.querySelectorAll('#timeline-period-bar .s-stat-tab').forEach(t => t.classList.toggle('active', t.dataset.period === period));
  loadTimelineStats(currentStatsMode, period);
}

let currentStatsMode = 'overall';
loadTimelineStats(currentStatsMode, timelinePeriod);

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
      axisLabel: { color: '#94a3b8', fontSize: 9, rotate: 0, interval: 0, fontFamily: ECHART_FONT }
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

  // ── Performance Over Time chart badge ──
  const tlBadge = document.getElementById('badge-timeline-pct');
  const tlPass  = d.accuracy >= TL_PASS;
  tlBadge.className = 's-badge ' + (tlPass ? 's-badge-teal' : 's-badge-red');
  tlBadge.innerHTML = `<i class="bi ${tlPass ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'} me-1"></i>${d.accuracy}% Accuracy`;

  // ── Average Scores line chart ──
  scoresChart.setOption(buildScoresOption(d.chartLabels, d.chartData));

  // ── Performance Over Time (re-fetch for new mode) ──
  currentStatsMode = mode;
  loadTimelineStats(mode, timelinePeriod);

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

// ── Untaken concepts toggle ──
function rcToggleUntaken(panelId, btn) {
  const panel    = document.getElementById(panelId);
  const chevron  = btn.querySelector('.s-rc-untaken-chevron');
  const expanded = btn.getAttribute('aria-expanded') === 'true';
  btn.setAttribute('aria-expanded', !expanded);
  panel.classList.toggle('is-open', !expanded);
  chevron.style.transform = expanded ? '' : 'rotate(180deg)';
}
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

<!-- Date-aware Overall NCLEX Readiness (daily reset + day/week/month backtracking) -->
<script>
const RD_ICON_MAP = <?= json_encode($overallConceptIconMap, JSON_UNESCAPED_UNICODE) ?>;
const RD_LABELS   = <?= json_encode(array_map(fn($c) => $c['label'], $rcColors)) ?>;
const RD_GUIDE    = <?= json_encode($rcGuide, JSON_UNESCAPED_UNICODE) ?>;
const RD_TODAY    = '<?= (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d') ?>';

let rdPeriod = 'day';
let rdAnchor = RD_TODAY; // 'YYYY-MM-DD'

function rdEsc(s) {
  return String(s).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
}
function rdYmd(dt) {
  return dt.getUTCFullYear() + '-' +
         String(dt.getUTCMonth() + 1).padStart(2, '0') + '-' +
         String(dt.getUTCDate()).padStart(2, '0');
}
function rdShift(dateStr, period, dir) {
  const [y, m, d] = dateStr.split('-').map(Number);
  let dt;
  if (period === 'month') {
    dt = new Date(Date.UTC(y, m - 1, 1));
    dt.setUTCMonth(dt.getUTCMonth() + dir);
  } else {
    dt = new Date(Date.UTC(y, m - 1, d));
    dt.setUTCDate(dt.getUTCDate() + dir * (period === 'week' ? 7 : 1));
  }
  const next = rdYmd(dt);
  return next > RD_TODAY ? dateStr : next; // never step into the future
}

// Build one concept readiness card (mirrors the PHP renderReadinessCards markup)
function rdBuildCard(c) {
  const label = RD_LABELS[c.status] || '';
  const guide = RD_GUIDE[c.status] || '';
  let scoresHtml = '';
  const last = c.scores.length - 1;
  c.scores.forEach((sc, i) => {
    if (i > 0) scoresHtml += '<i class="bi bi-arrow-right s-rc-arrow"></i>';
    scoresHtml += `<span class="s-rc-score${i === last ? ' is-latest' : ''}">${sc}%</span>`;
  });
  return `
    <div class="col-xl-3 col-md-6 col-12">
      <div class="s-rc" data-status="${c.status}">
        <div class="s-rc-top">
          <span class="s-rc-icon"><i class="${c.icon}"></i></span>
          <span class="s-rc-badge"><span class="s-rc-dot"></span>${label}</span>
        </div>
        <div class="s-rc-name">${rdEsc(c.name)}</div>
        <div class="s-rc-metric">
          <span class="s-rc-avg">${c.avg}<span>%</span></span>
          <span class="s-rc-metric-lbl">average score</span>
        </div>
        <div class="s-rc-bar">
          <div class="s-rc-bar-fill" data-w="${c.avg}"></div>
          <span class="s-rc-bar-goal" title="Passing goal: 75%"></span>
        </div>
        <div class="s-rc-scale">
          <span>Your score</span>
          <span class="s-rc-goal-lbl">Goal&nbsp;75%</span>
        </div>
        <p class="s-rc-guide">${guide}</p>
        <div class="s-rc-recent">
          <span class="s-rc-recent-lbl">Last ${c.attempts} exam${c.attempts > 1 ? 's' : ''}</span>
          <span class="s-rc-recent-scores">${scoresHtml}</span>
        </div>
      </div>
    </div>`;
}

function rdBuildUntaken(hidden, period) {
  if (!hidden.length) return '';
  const scope = period === 'week' ? 'this week' : (period === 'month' ? 'this month' : 'this day');
  const chips = hidden.map(c =>
    `<span class="s-rc-untaken-chip"><i class="${c.icon}"></i>${rdEsc(c.name)}</span>`
  ).join('');
  return `
    <div class="col-12">
      <div class="s-rc-untaken">
        <button class="s-rc-untaken-toggle" onclick="rcToggleUntaken('rc-untaken-overall', this)" aria-expanded="false">
          <span class="s-rc-untaken-icon"><i class="bi bi-hourglass-split"></i></span>
          <span class="s-rc-untaken-label">${hidden.length} concept${hidden.length > 1 ? 's' : ''} with no exams ${scope}</span>
          <i class="bi bi-chevron-down s-rc-untaken-chevron"></i>
        </button>
        <div class="s-rc-untaken-panel" id="rc-untaken-overall">${chips}</div>
      </div>
    </div>`;
}

function rdUpdateBanner(summary, rangeLabel) {
  const banner = document.getElementById('readiness-banner-overall');
  const note   = document.getElementById('readiness-note-overall');
  if (banner) {
    banner.className = 's-readiness-banner s-rb-' + summary.status;
    const r = summary.ready, t = summary.total;
    let html;
    if (summary.status === 'ready') {
      html = `<i class="bi bi-patch-check-fill"></i>&nbsp; <strong>NCLEX Ready!</strong>&nbsp; All ${t} concepts passing — ${rdEsc(rangeLabel)}.`;
    } else if (summary.status === 'ontrack') {
      html = `<i class="bi bi-arrow-up-circle-fill"></i>&nbsp; <strong>On Track</strong>&nbsp; — ${r}/${t} concepts passing (${rdEsc(rangeLabel)}).`;
    } else if (summary.status === 'needs') {
      html = `<i class="bi bi-exclamation-triangle-fill"></i>&nbsp; <strong>Needs Work</strong>&nbsp; — ${r}/${t} concepts passing (${rdEsc(rangeLabel)}).`;
    } else {
      html = `<i class="bi bi-hourglass-split"></i>&nbsp; <strong>No Exams ${rdEsc(rangeLabel)}</strong>&nbsp; — Take an exam to track your progress.`;
    }
    banner.innerHTML = html;
  }
  if (note) {
    note.innerHTML = `Showing your combined <strong>Traditional + NGN</strong> progress for <strong>${rdEsc(rangeLabel)}</strong>. ` +
      `Each concept's score is the average of every exam taken in this period. A concept is ` +
      `<span style="color:#0D9488;font-weight:600;">Ready</span> at ≥ 75%.`;
  }
}

function renderOverallReadiness(data) {
  const grid = document.getElementById('readiness-cards-overall');
  if (!grid) return;

  // Range label + date controls
  document.getElementById('rd-range-label').textContent = data.rangeLabel;
  const dInput = document.getElementById('rd-date-input');
  dInput.value = data.anchor;
  dInput.max   = data.today;
  document.getElementById('rd-next').disabled = data.isCurrent;
  document.getElementById('rd-today').classList.toggle('is-hidden', data.isCurrent);

  const taken  = data.concepts.filter(c => c.attempts > 0);
  const hidden = data.concepts.filter(c => c.attempts === 0);

  let html = taken.map(rdBuildCard).join('');
  if (!taken.length) {
    const scope = data.period === 'week' ? 'this week' : (data.period === 'month' ? 'this month' : 'today');
    html = `
      <div class="col-12">
        <div class="s-rc-untaken" style="text-align:center;">
          <div style="padding:18px 12px;color:var(--s-muted);">
            <i class="bi bi-clipboard-data" style="font-size:1.6rem;display:block;margin-bottom:8px;"></i>
            No exams completed ${scope}. Finish a Traditional or NGN exam to see your progress here.
          </div>
        </div>
      </div>`;
  }
  html += rdBuildUntaken(hidden, data.period);
  grid.innerHTML = html;

  // Animate the progress bars (same easing pattern as the rest of the page)
  grid.querySelectorAll('.s-rc-bar-fill[data-w]').forEach((bar, i) => {
    bar.style.width = '0%';
    requestAnimationFrame(() => { setTimeout(() => { bar.style.width = bar.dataset.w + '%'; }, 80 + i * 30); });
  });

  rdUpdateBanner(data.summary, data.rangeLabel);
}

function loadOverallReadiness() {
  const grid = document.getElementById('readiness-cards-overall');
  if (grid) grid.style.opacity = '0.5';
  fetch(`get_readiness.php?period=${rdPeriod}&date=${rdAnchor}`)
    .then(r => r.json())
    .then(data => {
      if (data.error) return;
      rdAnchor = data.anchor;
      renderOverallReadiness(data);
    })
    .catch(() => {})
    .finally(() => { if (grid) grid.style.opacity = '1'; });
}

function setOverallPeriod(p) {
  rdPeriod = p;
  document.querySelectorAll('#readiness-overall-nav .s-stat-tab').forEach(t =>
    t.classList.toggle('active', t.dataset.rperiod === p));
  loadOverallReadiness();
}
function shiftOverall(dir) {
  rdAnchor = rdShift(rdAnchor, rdPeriod, dir);
  loadOverallReadiness();
}
function goOverallToday() {
  rdAnchor = RD_TODAY;
  loadOverallReadiness();
}
document.getElementById('rd-date-input').addEventListener('change', function () {
  if (this.value) { rdAnchor = this.value; loadOverallReadiness(); }
});
// Open the native calendar when the whole date pill is clicked (Chrome/Edge need showPicker)
document.querySelector('.s-rd-daterange').addEventListener('click', function (e) {
  e.preventDefault();
  const input = document.getElementById('rd-date-input');
  if (typeof input.showPicker === 'function') {
    try { input.showPicker(); return; } catch (_) {}
  }
  input.focus(); // fallback for older browsers
});

// Show the date-nav only on the Overall tab; (re)load when entering it.
const _rdPrevSetStatsFilter = setStatsFilter;
setStatsFilter = function (mode) {
  _rdPrevSetStatsFilter(mode);
  const nav = document.getElementById('readiness-overall-nav');
  if (nav) nav.style.display = (mode === 'overall') ? '' : 'none';
  if (mode === 'overall') loadOverallReadiness();
};

// Initial load — Overall is the default active tab
loadOverallReadiness();
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
@media (min-width: 577px) {
  #ngnModalBackdrop.s-modal-backdrop .s-modal--lg { max-width: 920px; }
}
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
.ngn-topic-list { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
@media (max-width: 576px) {
  .ngn-topic-list { grid-template-columns: 1fr; }
  .ngn-topic-card .ngn-label { white-space: normal; overflow: visible; text-overflow: unset; }
}
.ngn-pagination { display: flex; align-items: center; justify-content: center; gap: 16px; margin-top: 14px; }
.ngn-page-btn { background: #fff; border: 2px solid #e2e8f0; border-radius: 8px; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #4a5568; transition: all 0.15s; }
.ngn-page-btn:hover:not(:disabled) { border-color: #007CBF; color: #007CBF; }
.ngn-page-btn:disabled { opacity: 0.35; cursor: not-allowed; }
.ngn-page-info { font-size: 0.82rem; font-weight: 600; color: #4a5568; min-width: 100px; text-align: center; }
.ngn-topic-card { display: flex; align-items: center; gap: 10px; background: #fff; border: 2px solid #e9edf5; border-radius: 12px; padding: 10px 13px; cursor: pointer; transition: all 0.15s; user-select: none; min-width: 0; }
.ngn-topic-card:hover { border-color: #007CBF; background: #f0f5ff; }
.ngn-topic-card.selected { border-color: #02968A; background: #f0fdf9; }
.ngn-topic-card .ngn-cb { width: 17px; height: 17px; accent-color: #02968A; pointer-events: none; flex-shrink: 0; }
.ngn-topic-card .ngn-label { font-size: 0.82rem; color: #2d3748; font-weight: 500; flex-grow: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
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
        <div id="topicPagination" class="ngn-pagination d-none">
          <button type="button" class="ngn-page-btn" id="topicPrevBtn" aria-label="Previous page"><i class="bi bi-chevron-left"></i></button>
          <span class="ngn-page-info" id="topicPageInfo">Page 1 of 1</span>
          <button type="button" class="ngn-page-btn" id="topicNextBtn" aria-label="Next page"><i class="bi bi-chevron-right"></i></button>
        </div>
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
// Auto-open NGN modal when arriving from result.php "New Exam" button
if (new URLSearchParams(window.location.search).get('start_ngn') === '1') {
  document.addEventListener('DOMContentLoaded', function() { openNGNModal(); });
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
const TOPIC_PAGE_SIZE = 10;
let allTopicsData = [];
let selectedTopicValues = new Set();
let currentTopicPage = 0;

document.getElementById('nextToTopicBtn').addEventListener('click', function() {
  const selectedConcepts = Array.from(document.querySelectorAll('.concept-cb:checked')).map(c => c.value);
  document.getElementById('ngnStep1').classList.add('d-none');
  document.getElementById('ngnStep2').classList.remove('d-none');
  const list = document.getElementById('topicList');
  const loading = document.getElementById('topicLoadingMsg');
  const pagination = document.getElementById('topicPagination');
  list.innerHTML = ''; list.style.display = 'none'; pagination.classList.add('d-none'); loading.classList.remove('d-none');
  allTopicsData = []; selectedTopicValues = new Set(); currentTopicPage = 0;
  fetch('get_ngn_topics.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ concepts: selectedConcepts }) })
    .then(r => r.json()).then(data => {
      loading.classList.add('d-none');
      list.style.display = '';
      allTopicsData = data;
      if (!data.length) { list.innerHTML = '<p class="text-center text-muted py-3">No topics found.</p>'; updateStartBtn(); return; }
      renderTopicPage(0);
    }).catch(() => { loading.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-circle-fill me-1"></i>Failed to load.</span>'; });
});
function renderTopicPage(page) {
  const list = document.getElementById('topicList');
  const pagination = document.getElementById('topicPagination');
  const totalPages = Math.max(1, Math.ceil(allTopicsData.length / TOPIC_PAGE_SIZE));
  currentTopicPage = Math.min(Math.max(page, 0), totalPages - 1);
  const start = currentTopicPage * TOPIC_PAGE_SIZE;
  list.innerHTML = '';
  allTopicsData.slice(start, start + TOPIC_PAGE_SIZE).forEach(item => {
    const isSelected = selectedTopicValues.has(item.topic);
    const card = document.createElement('div');
    card.className = 'ngn-topic-card' + (isSelected ? ' selected' : '');
    card.innerHTML = `<input class="ngn-cb topic-cb" type="checkbox" value="${escHtml(item.topic)}" data-concept="${escHtml(item.concept)}"${isSelected ? ' checked' : ''}><span class="ngn-label">${escHtml(item.topic)}</span><span class="ngn-count">${item.count} Qs</span>`;
    card.addEventListener('click', function() {
      const cb = this.querySelector('.topic-cb');
      cb.checked = !cb.checked;
      this.classList.toggle('selected', cb.checked);
      if (cb.checked) selectedTopicValues.add(cb.value); else selectedTopicValues.delete(cb.value);
      updateStartBtn();
    });
    list.appendChild(card);
  });
  if (allTopicsData.length > TOPIC_PAGE_SIZE) {
    pagination.classList.remove('d-none');
    document.getElementById('topicPageInfo').textContent = `Page ${currentTopicPage + 1} of ${totalPages}`;
    document.getElementById('topicPrevBtn').disabled = currentTopicPage === 0;
    document.getElementById('topicNextBtn').disabled = currentTopicPage >= totalPages - 1;
  } else {
    pagination.classList.add('d-none');
  }
  updateStartBtn();
}
document.getElementById('topicPrevBtn').addEventListener('click', function() { renderTopicPage(currentTopicPage - 1); });
document.getElementById('topicNextBtn').addEventListener('click', function() { renderTopicPage(currentTopicPage + 1); });
function toggleAllTopics() {
  const newState = selectedTopicValues.size < allTopicsData.length;
  if (newState) allTopicsData.forEach(item => selectedTopicValues.add(item.topic));
  else selectedTopicValues.clear();
  renderTopicPage(currentTopicPage);
}
function updateStartBtn() {
  const checked = selectedTopicValues.size;
  const total   = allTopicsData.length;
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
  document.getElementById('hiddenTopics').value   = JSON.stringify(Array.from(selectedTopicValues));
});
</script>

<?php if ($fetch['tutorial'] === null): ?>
<?php include '_layout/tutorial.php'; ?>
<?php endif; ?>

</body>
</html>
