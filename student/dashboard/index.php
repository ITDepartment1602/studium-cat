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
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

  <!-- ── Hero ── -->
  <div class="s-hero-banner mb-4">
    <div class="s-hero-left">
      <div class="s-hero-greeting">Good <?php
        $hr = (int)date('H');
        echo $hr < 12 ? 'Morning' : ($hr < 18 ? 'Afternoon' : 'Evening');
      ?>, <span><?php echo htmlspecialchars($firstname); ?>!</span></div>
      <p class="s-hero-sub">Here's your performance snapshot and practice options.</p>
    </div>
    <div class="s-hero-donut">
      <div style="position:relative; width:100px; height:100px;">
        <canvas id="passingChart"></canvas>
        <div id="passingValue" class="s-donut-label large" style="font-size:1.2rem; color:#fff;"></div>
      </div>
      <div style="font-size:0.7rem; color:rgba(255,255,255,.65); text-align:center; margin-top:6px;">Chance of Passing</div>
    </div>
  </div>

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
    <div class="col-lg-4 col-md-6 col-12">
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
    $tq = mysqli_query($con, "SELECT * FROM bundlelist ORDER BY id ASC");
    while ($brow = mysqli_fetch_array($tq)) {
      if ($brow['name'] == "NARC NGN QBanks (Soon)") continue; // skip NGN, handled above
      $cleanName = str_replace('(Soon)', '', $brow['name']);
    ?>
    <div class="col-lg-4 col-md-6 col-12">
      <div class="s-mode-hero-card" style="--mhc-color:#1B4965; --mhc-bg:rgba(27,73,101,.05);">
        <div class="s-mhc-icon" style="background:rgba(27,73,101,.1);">
          <i class="bi bi-journals" style="color:#1B4965;"></i>
        </div>
        <div class="s-mhc-body">
          <div class="s-mhc-title"><?php echo htmlspecialchars($cleanName); ?></div>
          <div class="s-mhc-desc">Traditional multiple-choice questions with in-depth rationale and full topic coverage.</div>
          <div class="s-mhc-tag" style="background:rgba(27,73,101,.1);color:#1B4965;">Traditional NCLEX</div>
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
    <div class="col-lg-4 col-md-6 col-12">
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
  <h5 class="s-section-title mb-4 mt-2"><i class="bi bi-bar-chart-line-fill me-2"></i>Statistics</h5>

  <!-- KPI Row -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="s-kpi-card" style="--kc:#0D9488;">
        <div class="s-kpi-icon-row"><i class="bi bi-check-circle-fill" style="color:#0D9488;"></i></div>
        <div class="s-kpi-num"><?= $correct ?></div>
        <div class="s-kpi-label">Correct Answers</div>
        <div class="s-kpi-bar"><div style="width:<?= $correctPercent ?>%;"></div></div>
        <div class="s-kpi-sub"><?= $correctPercent ?>% accuracy</div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="s-kpi-card" style="--kc:#EF4444;">
        <div class="s-kpi-icon-row"><i class="bi bi-x-circle-fill" style="color:#EF4444;"></i></div>
        <div class="s-kpi-num"><?= $wrong ?></div>
        <div class="s-kpi-label">Incorrect Answers</div>
        <div class="s-kpi-bar"><div style="width:<?= ($correct+$wrong>0)?100-$correctPercent:0 ?>%;"></div></div>
        <div class="s-kpi-sub"><?= ($correct+$wrong>0)?100-$correctPercent:0 ?>% of attempts</div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="s-kpi-card" style="--kc:#1e6091;">
        <div class="s-kpi-icon-row"><i class="bi bi-journals" style="color:#1e6091;"></i></div>
        <div class="s-kpi-num"><?= $usedQ ?></div>
        <div class="s-kpi-label">Questions Used</div>
        <div class="s-kpi-bar"><div style="width:<?= $usedPercent ?>%;"></div></div>
        <div class="s-kpi-sub"><?= $usedPercent ?>% of <?= $totalQ ?> total</div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="s-kpi-card" style="--kc:#64748B;">
        <div class="s-kpi-icon-row"><i class="bi bi-question-circle-fill" style="color:#64748B;"></i></div>
        <div class="s-kpi-num"><?= $unusedQ ?></div>
        <div class="s-kpi-label">Unused Questions</div>
        <div class="s-kpi-bar"><div style="width:<?= 100-$usedPercent ?>%;"></div></div>
        <div class="s-kpi-sub"><?= 100-$usedPercent ?>% remaining</div>
      </div>
    </div>
  </div>

  <!-- Usage & Performance Charts -->
  <div class="row g-4 mb-4">
    <div class="col-lg-6 col-12">
      <div class="s-chart-card">
        <div class="s-chart-card-header">
          <span class="s-chart-card-title"><i class="bi bi-question-circle-fill"></i> Questions Usage</span>
          <span class="s-badge s-badge-blue"><?= $totalQ ?> Total</span>
        </div>
        <div class="s-chart-body">
          <div class="s-donut-wrap flex-shrink-0">
            <canvas id="questionsCircle"></canvas>
            <div class="s-donut-label">Usage</div>
          </div>
          <div class="s-chart-legend">
            <div class="s-legend-item">
              <span class="s-legend-dot" style="background:#0D9488;"></span>
              Used &nbsp;<strong><?= $usedQ ?></strong>
              <span class="s-badge s-badge-teal ms-1"><?= $usedPercent ?>%</span>
            </div>
            <div class="s-legend-item">
              <span class="s-legend-dot" style="background:#e2e8f0;"></span>
              Unused &nbsp;<strong><?= $unusedQ ?></strong>
              <span class="s-badge s-badge-gray ms-1"><?= 100 - $usedPercent ?>%</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-6 col-12">
      <div class="s-chart-card">
        <div class="s-chart-card-header">
          <span class="s-chart-card-title"><i class="bi bi-bar-chart-fill"></i> Performance</span>
          <span class="s-badge s-badge-teal"><?= $correctPercent ?>% Accuracy</span>
        </div>
        <div class="s-chart-body">
          <div class="s-donut-wrap flex-shrink-0">
            <canvas id="performanceCircle"></canvas>
            <div class="s-donut-label">Score</div>
          </div>
          <div class="s-chart-legend">
            <div class="s-legend-item">
              <span class="s-legend-dot" style="background:#0D9488;"></span>
              Correct &nbsp;<strong><?= $correct ?></strong>
              <span class="s-badge s-badge-teal ms-1"><?= $correctPercent ?>%</span>
            </div>
            <div class="s-legend-item">
              <span class="s-legend-dot" style="background:#d72638;"></span>
              Incorrect &nbsp;<strong><?= $wrong ?></strong>
              <span class="s-badge s-badge-red ms-1"><?= ($correct + $wrong > 0) ? 100 - $correctPercent : 0 ?>%</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>


  <!-- ── Topics & Concepts ── -->
  <h5 class="s-section-title mb-4 mt-2"><i class="bi bi-grid-fill me-2"></i>Topics & Concepts Statistics</h5>
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
            <option value="Leadership And Management">Leadership And Management</option>
            <option value="Mental Health">Mental Health</option>
            <option value="Pharmacology">Pharmacology</option>
            <option value="Maternal And Newborn Health">Maternal And Newborn Health</option>
          </select>
        </div>
        <div class="s-chart-body">
          <div class="s-donut-wrap flex-shrink-0" style="position:relative;">
            <canvas id="topicChart"></canvas>
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
          <select id="conceptSelect" class="s-input" style="max-width:180px; font-size:0.78rem; padding:5px 10px;">
            <option value="Pain Meds">Pain Meds</option>
            <option value="Antepartum">Antepartum</option>
            <option value="Assignment/Delegation">Assignment/Delegation</option>
            <option value="Cardiovascular">Cardiovascular</option>
            <option value="Oncology">Oncology</option>
            <option value="Emergency Care">Emergency Care</option>
            <option value="Endocrine">Endocrine</option>
            <option value="Nursing Legalities">Nursing Legalities</option>
            <option value="Fluid and Electrolyte">Fluid and Electrolyte</option>
            <option value="Gastrointestinal/Nutrition">Gastrointestinal/Nutrition</option>
            <option value="Growth and Development">Growth and Development</option>
            <option value="Hematology">Hematology</option>
            <option value="Immunology">Immunology</option>
            <option value="Communicable Disease">Communicable Disease</option>
            <option value="Integumentary">Integumentary</option>
            <option value="Management Concepts">Management Concepts</option>
            <option value="Psychiatry">Psychiatry</option>
            <option value="Musculoskeletal">Musculoskeletal</option>
            <option value="Neurology">Neurology</option>
            <option value="Prioritization">Prioritization</option>
            <option value="Psych Meds">Psych Meds</option>
            <option value="Respiratory">Respiratory</option>
            <option value="Skills/Procedures">Skills/Procedures</option>
            <option value="Genitourinary">Genitourinary</option>
            <option value="Eyes/Ears/Nose/Throat">Eyes/Ears/Nose/Throat</option>
            <option value="Intrapartum">Intrapartum</option>
            <option value="Postpartum">Postpartum</option>
            <option value="Labor and Delivery">Labor and Delivery</option>
            <option value="Drug Computations">Drug Computations</option>
            <option value="Culture and Religion">Culture and Religion</option>
            <option value="Neonatology">Neonatology</option>
            <option value="End of Life Care">End of Life Care</option>
            <option value="Communication">Communication</option>
          </select>
        </div>
        <div class="s-chart-body">
          <div class="s-donut-wrap flex-shrink-0" style="position:relative;">
            <canvas id="conceptChart"></canvas>
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
    <canvas id="scoresChart" style="max-height:260px;"></canvas>
  </div>


  <!-- ── Concept Score Cards ── -->
  <h5 class="s-section-title mb-4 mt-2"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Concept Breakdown</h5>
  <div class="row g-3 mb-4">
    <?php
    $conceptIcons = [
      'Adult Health'              => 'bi bi-person-fill',
      'Child Health'              => 'bi bi-emoji-smile-fill',
      'Critical Care'             => 'bi bi-heart-pulse-fill',
      'Fundamentals'              => 'bi bi-gear-fill',
      'Leadership And Management' => 'bi bi-people-fill',
      'Mental Health'             => 'bi bi-lightbulb-fill',
      'Pharmacology'              => 'bi bi-capsule',
      'Maternal And Newborn Health' => 'bi bi-gender-female'
    ];
    $concepts = array_keys($conceptIcons);
    foreach ($concepts as $concept) {
      $query = "SELECT * FROM `history` WHERE email = '$user_id' AND eid = '$concept' AND kilanlan = 'NARC Intermediate and Advance QBanks'";
      $data  = mysqli_query($quizCon, $query);
      $totalScore = 0; $count = 0;
      while ($rows = mysqli_fetch_array($data)) { $totalScore += $rows['score']; $count++; }
      $scoreDisplay = ($count > 0) ? round($totalScore / $count) : 0;
      $scoreColor = $scoreDisplay >= 75 ? '#0D9488' : ($scoreDisplay >= 60 ? '#b45309' : '#ef4444');
      $scoreBg    = $scoreDisplay >= 75 ? 'rgba(13,148,136,.08)' : ($scoreDisplay >= 60 ? 'rgba(180,83,9,.08)' : 'rgba(239,68,68,.08)');
    ?>
    <div class="col-xl-3 col-md-6 col-12">
      <div class="s-concept-card2 s-concept-card" data-score="<?php echo $scoreDisplay; ?>">
        <div class="s-cc2-ring" style="background:conic-gradient(<?php echo $scoreColor; ?> <?php echo $scoreDisplay; ?>%, #e9ecef 0);">
          <div class="s-cc2-inner">
            <i class="<?php echo $conceptIcons[$concept]; ?>" style="color:<?php echo $scoreColor; ?>;"></i>
          </div>
        </div>
        <div class="s-cc2-score" style="color:<?php echo $scoreColor; ?>;"><?php echo $scoreDisplay; ?><span>%</span></div>
        <div class="s-cc2-name"><?php echo $concept; ?></div>
        <div class="s-cc2-meta"><?php echo $count > 0 ? $count . ' exam' . ($count > 1 ? 's' : '') . ' taken' : 'No exams yet'; ?></div>
      </div>
    </div>
    <?php } ?>
  </div>

</main><!-- /s-main -->

<!-- Footer -->
<div class="s-footer">
  <span>© Studium 2025, All Right Reserved.</span>
</div>


<!-- ── Scripts ── -->
<script src="../ty/js/bootstrap.bundle.min.js"></script>
<script src="../ty/js/jquery-3.5.1.js"></script>
<script src="../ty/js/jquery.dataTables.min.js"></script>
<script src="../ty/js/dataTables.bootstrap5.min.js"></script>

<script>
  // Apply Poppins to all Chart.js instances
  Chart.defaults.font.family = "'Poppins', sans-serif";

  // ── Passing Donut ──
  const passingValue = <?php echo $passingRounded; ?>;
  document.getElementById("passingValue").innerText = passingValue + "%";
  new Chart(document.getElementById('passingChart'), {
    type: 'doughnut',
    data: {
      labels: ['Achieved', 'Remaining'],
      datasets: [{ data: [passingValue, 100 - passingValue], backgroundColor: ['#0D9488','#e9ecef'], borderWidth: 0 }]
    },
    options: {
      cutout: '78%',
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } }
    }
  });

  // ── Statistics Donuts ──
  new Chart(document.getElementById('questionsCircle'), {
    type: 'doughnut',
    data: { labels: ['Used','Unused'], datasets: [{ data: [<?= $usedQ ?>, <?= $unusedQ ?>], backgroundColor: ['#0D9488','#ddd'], borderWidth: 0 }] },
    options: { responsive: true, maintainAspectRatio: false, cutout: '80%', plugins: { legend: { display: false } } }
  });

  new Chart(document.getElementById('performanceCircle'), {
    type: 'doughnut',
    data: { labels: ['Correct','Wrong'], datasets: [{ data: [<?= $correct ?>, <?= $wrong ?>], backgroundColor: ['#0D9488','#d72638'], borderWidth: 0 }] },
    options: { responsive: true, maintainAspectRatio: false, cutout: '80%', plugins: { legend: { display: false } } }
  });

  // ── Line Chart: Average Scores ──
  const labels = [];
  const dataScores = [];
  <?php
  foreach ($concepts as $concept) {
    $query = "SELECT * FROM `history` WHERE email = '$user_id' AND eid = '$concept' AND kilanlan = 'NARC Intermediate and Advance QBanks'";
    $data  = mysqli_query($quizCon, $query);
    $totalScore = 0; $count = 0;
    while ($rows = mysqli_fetch_array($data)) { $totalScore += $rows['score']; $count++; }
    $avg = ($count > 0) ? $totalScore / $count : 0;
    echo "labels.push('$concept');";
    echo "dataScores.push(Math.round($avg));";
  }
  ?>
  new Chart(document.getElementById('scoresChart'), {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{ label: 'Average %', data: dataScores, fill: true, tension: 0.4, borderColor: '#1B4965', backgroundColor: 'rgba(13,148,136,0.15)', pointBackgroundColor: '#0D9488' }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true, max: 100 } } }
  });
</script>

<!-- Concept + Topic chart JS -->
<script>
let conceptChart, topicChart;

function updateConceptChart(correct, wrong) {
  if (conceptChart) conceptChart.destroy();
  const data   = (correct + wrong === 0) ? [100] : [correct, wrong];
  const colors = (correct + wrong === 0) ? ['#ddd'] : ['#0D9488', '#d72638'];
  conceptChart = new Chart(document.getElementById('conceptChart'), {
    type: 'doughnut',
    data: { datasets: [{ data, backgroundColor: colors, borderWidth: 0 }] },
    options: { cutout: '80%', plugins: { legend: { display: false } }, responsive: true, maintainAspectRatio: false }
  });
}

function updateTopicChart(correct, wrong) {
  if (topicChart) topicChart.destroy();
  const data   = (correct + wrong === 0) ? [100] : [correct, wrong];
  const colors = (correct + wrong === 0) ? ['#ddd'] : ['#0D9488', '#d72638'];
  topicChart = new Chart(document.getElementById('topicChart'), {
    type: 'doughnut',
    data: { datasets: [{ data, backgroundColor: colors, borderWidth: 0 }] },
    options: { cutout: '80%', plugins: { legend: { display: false } }, responsive: true, maintainAspectRatio: false }
  });
}

function fetchStats(type, value) {
  fetch(`get_stats.php?type=${type}&value=${encodeURIComponent(value)}`)
    .then(res => res.json())
    .then(data => {
      const prefix = (type === 'concept') ? 'concept' : 'topic';
      document.getElementById(prefix + 'Total').innerText   = data.total;
      document.getElementById(prefix + 'Used').innerText    = data.used;
      document.getElementById(prefix + 'Correct').innerText = data.correct;
      document.getElementById(prefix + 'Wrong').innerText   = data.wrong;
      let cp = (data.correct + data.wrong > 0) ? Math.round((data.correct / (data.correct + data.wrong)) * 100) : 0;
      document.getElementById(prefix + 'CorrectPercent').innerText = cp + '%';
      document.getElementById(prefix + 'WrongPercent').innerText   = (data.correct + data.wrong > 0 ? 100 - cp : 0) + '%';
      if (type === 'concept') updateConceptChart(data.correct, data.wrong);
      else                    updateTopicChart(data.correct, data.wrong);
    });
}

document.getElementById('conceptSelect').addEventListener('change', function() { fetchStats('concept', this.value); });
document.getElementById('topicSelect').addEventListener('change',   function() { fetchStats('topic',   this.value); });
fetchStats('concept', document.getElementById('conceptSelect').value);
fetchStats('topic',   document.getElementById('topicSelect').value);
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
#ngnModal .modal-content { border-radius: 20px; overflow: hidden; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.18); }
#ngnModal .ngn-modal-header { background: linear-gradient(135deg, #004AAD 0%, #02968A 100%); padding: 22px 28px 18px; }
#ngnModal .ngn-modal-header h5 { color: #fff; font-weight: 700; font-size: 1.15rem; margin: 0; }
#ngnModal .ngn-modal-header .step-badge { background: rgba(255,255,255,0.2); color: #fff; font-size: 0.7rem; font-weight: 600; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; }
#ngnModal .modal-body { padding: 22px 24px 12px; max-height: 55vh; overflow-y: auto; }
#ngnModal .modal-footer { padding: 14px 24px; border-top: 1px solid #f0f0f0; background: #fafafa; }
.ngn-alert { background: #fff5f5; border-left: 4px solid #e53e3e; border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; display: flex; align-items: flex-start; gap: 10px; }
.ngn-alert i { color: #e53e3e; margin-top: 2px; }
.ngn-alert p { margin: 0; color: #c53030; font-size: 0.82rem; line-height: 1.5; }
.ngn-selectall-bar { display: flex; align-items: center; justify-content: space-between; background: #f8f9ff; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 10px 16px; margin-bottom: 14px; cursor: pointer; transition: background 0.2s; }
.ngn-selectall-bar:hover { background: #eef2ff; }
.ngn-selectall-bar label { cursor: pointer; font-weight: 600; font-size: 0.9rem; color: #1B4965; margin: 0; }
.ngn-item-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
@media (max-width: 576px) { .ngn-item-grid { grid-template-columns: 1fr; } }
.ngn-item-card { display: flex; align-items: center; gap: 12px; background: #fff; border: 2px solid #e9edf5; border-radius: 14px; padding: 12px 14px; cursor: pointer; transition: all 0.18s; user-select: none; }
.ngn-item-card:hover { border-color: #004AAD; background: #f0f5ff; transform: translateY(-1px); box-shadow: 0 4px 14px rgba(0,74,173,0.1); }
.ngn-item-card.selected { border-color: #02968A; background: #f0fdf9; }
.ngn-item-card .ngn-cb { width: 18px; height: 18px; accent-color: #02968A; flex-shrink: 0; pointer-events: none; }
.ngn-item-card .ngn-label { font-size: 0.88rem; font-weight: 600; color: #2d3748; flex-grow: 1; }
.ngn-item-card .ngn-count { background: linear-gradient(135deg,#004AAD,#02968A); color:#fff; font-size:0.72rem; font-weight:700; padding:3px 9px; border-radius:20px; white-space:nowrap; }
.ngn-topic-list { display: grid; grid-template-rows: repeat(5, auto); grid-auto-flow: column; grid-auto-columns: 1fr; gap: 8px; }
.ngn-topic-card { display: flex; align-items: center; gap: 10px; background: #fff; border: 2px solid #e9edf5; border-radius: 12px; padding: 10px 13px; cursor: pointer; transition: all 0.15s; user-select: none; }
.ngn-topic-card:hover { border-color: #004AAD; background: #f0f5ff; }
.ngn-topic-card.selected { border-color: #02968A; background: #f0fdf9; }
.ngn-topic-card .ngn-cb { width: 17px; height: 17px; accent-color: #02968A; pointer-events: none; }
.ngn-topic-card .ngn-label { font-size: 0.82rem; color: #2d3748; font-weight: 500; flex-grow: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ngn-topic-card .ngn-count { background: linear-gradient(135deg,#004AAD,#02968A); color:#fff; font-size:0.68rem; font-weight:700; padding:2px 8px; border-radius:20px; white-space:nowrap; }
.ngn-steps { display:flex; align-items:center; gap:6px; }
.ngn-dot { width:8px; height:8px; border-radius:50%; background:rgba(255,255,255,0.35); transition:background 0.2s; }
.ngn-dot.active { background:#fff; width:22px; border-radius:4px; }
.ngn-btn-next { background:linear-gradient(135deg,#004AAD,#02968A); color:#fff; border:none; border-radius:10px; padding:9px 22px; font-weight:600; font-size:0.9rem; transition:opacity 0.2s,transform 0.15s; }
.ngn-btn-next:disabled { opacity:.45; cursor:not-allowed; }
.ngn-btn-next:not(:disabled):hover { opacity:.9; transform:translateY(-1px); }
.ngn-btn-back { background:transparent; border:2px solid #cbd5e0; border-radius:10px; padding:8px 18px; font-weight:600; font-size:0.9rem; color:#4a5568; }
.ngn-btn-back:hover { background:#f7fafc; }
.ngn-btn-cancel { background:transparent; border:none; color:#94a3b8; font-size:0.88rem; padding:8px 12px; }
.ngn-loading { text-align:center; padding:36px 0; color:#94a3b8; font-size:0.9rem; }
.ngn-loading i { font-size:1.5rem; margin-bottom:8px; display:block; }
</style>

<!-- NGN Modal HTML -->
<div class="modal fade" id="ngnModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" style="margin-top:60px;">
    <div class="modal-content">
      <!-- STEP 1: Concepts -->
      <div id="ngnStep1">
        <div class="ngn-modal-header">
          <div class="d-flex align-items-center gap-2 mb-1"><span class="step-badge">Step 1 of 2</span></div>
          <div class="d-flex align-items-center justify-content-between">
            <h5><i class="bi bi-lightbulb-fill me-2"></i>Choose Subjects</h5>
            <div class="d-flex align-items-center gap-2">
              <div class="ngn-steps"><div class="ngn-dot active"></div><div class="ngn-dot"></div></div>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
          </div>
        </div>
        <div class="modal-body">
          <div class="ngn-alert"><i class="bi bi-exclamation-circle-fill"></i>
            <p><strong>Important:</strong> You must select at least one subject to proceed. Your exam will only include questions from the subjects and topics you choose.</p>
          </div>
          <div class="ngn-selectall-bar" onclick="toggleAllConcepts()">
            <label for="selectAllConcepts"><i class="bi bi-check2-all me-2" style="color:#004AAD;"></i>Select All Subjects</label>
            <input class="ngn-cb" type="checkbox" id="selectAllConcepts" onclick="event.stopPropagation();toggleAllConcepts()">
          </div>
          <div id="conceptLoadingMsg" class="ngn-loading"><i class="bi bi-arrow-repeat" style="display:inline-block;animation:spin .8s linear infinite;"></i>Loading subjects...</div>
          <div id="conceptGrid" class="ngn-item-grid d-none"></div>
        </div>
        <div class="modal-footer justify-content-between">
          <button class="ngn-btn-cancel" data-bs-dismiss="modal">Cancel</button>
          <button class="ngn-btn-next" id="nextToTopicBtn" disabled>Next <i class="bi bi-arrow-right ms-1"></i></button>
        </div>
      </div>
      <!-- STEP 2: Topics -->
      <div id="ngnStep2" class="d-none">
        <div class="ngn-modal-header">
          <div class="d-flex align-items-center gap-2 mb-1"><span class="step-badge">Step 2 of 2</span></div>
          <div class="d-flex align-items-center justify-content-between">
            <h5><i class="bi bi-list-check me-2"></i>Choose Topics</h5>
            <div class="d-flex align-items-center gap-2">
              <div class="ngn-steps"><div class="ngn-dot"></div><div class="ngn-dot active"></div></div>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
          </div>
        </div>
        <div class="modal-body">
          <div class="ngn-alert"><i class="bi bi-exclamation-circle-fill"></i>
            <p><strong>Important:</strong> Only topics belonging to your selected subjects are shown. You must select at least one topic.</p>
          </div>
          <div class="ngn-selectall-bar" onclick="toggleAllTopics()">
            <label for="selectAllTopics"><i class="bi bi-check2-all me-2" style="color:#004AAD;"></i>Select All Topics</label>
            <input class="ngn-cb" type="checkbox" id="selectAllTopics" onclick="event.stopPropagation();toggleAllTopics()">
          </div>
          <div id="topicLoadingMsg" class="ngn-loading d-none"><i class="bi bi-arrow-repeat" style="display:inline-block;animation:spin .8s linear infinite;"></i>Loading topics...</div>
          <div id="topicList" class="ngn-topic-list"></div>
        </div>
        <div class="modal-footer justify-content-between">
          <button class="ngn-btn-back" id="backToConceptBtn"><i class="bi bi-arrow-left me-1"></i>Back</button>
          <form id="ngnStartForm" method="POST" action="ngn/start_filtered_exam.php" class="d-inline">
            <input type="hidden" name="concepts" id="hiddenConcepts">
            <input type="hidden" name="topics" id="hiddenTopics">
            <button type="submit" class="ngn-btn-next" id="startExamBtn" disabled><i class="bi bi-play-fill me-1"></i>Start Exam</button>
          </form>
        </div>
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
  new bootstrap.Modal(document.getElementById('ngnModal')).show();
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

</body>
</html>
