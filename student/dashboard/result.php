<?php
include '../../config.php';

$user_id = $_SESSION['user_id'] ?? (isset($_GET['id']) && ctype_digit($_GET['id']) ? intval($_GET['id']) : null);

$email    = isset($_GET['email'])    ? mysqli_real_escape_string($con, $_GET['email'])    : '';
$eid      = isset($_GET['eid'])      ? mysqli_real_escape_string($con, $_GET['eid'])      : '';
$topics1  = isset($_GET['topics1'])  ? mysqli_real_escape_string($con, $_GET['topics1'])  : '';
$kilanlan = isset($_GET['kilanlan']) ? mysqli_real_escape_string($con, $_GET['kilanlan']) : '';
$level    = isset($_GET['level'])    ? mysqli_real_escape_string($con, $_GET['level'])    : '';
$sahi     = isset($_GET['sahi'])     ? mysqli_real_escape_string($con, $_GET['sahi'])     : '';
$wrong    = isset($_GET['wrong'])    ? mysqli_real_escape_string($con, $_GET['wrong'])    : '';

// Fetch user info
$select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'") or die('query failed');
$fetch  = mysqli_fetch_assoc($select);

if (isset($_GET['examTakenResult']) && ctype_digit($_GET['examTakenResult'])) {
    $examTakenMinus = intval($_GET['examTakenResult']);
} else {
    $examTakenQuery = mysqli_query($con, "SELECT examTaken FROM `login` WHERE id = '$user_id'") or die('query failed');
    $examTaken      = mysqli_fetch_assoc($examTakenQuery)['examTaken'] ?? 0;
    $examTakenMinus = $examTaken - 1;
}

$correctAnswersQuery = mysqli_query($con, "SELECT COUNT(*) as correctCount FROM `review` WHERE studentId='$user_id' AND isCorrect=1 AND examTaken='$examTakenMinus'") or die('query failed');
$correctAnswersCount = mysqli_fetch_assoc($correctAnswersQuery)['correctCount'] ?? 0;

$wrongAnswersQuery = mysqli_query($con, "SELECT COUNT(*) as wrongCount FROM `review` WHERE studentId='$user_id' AND isCorrect=0 AND examTaken='$examTakenMinus'") or die('query failed');
$wrongAnswersCount = mysqli_fetch_assoc($wrongAnswersQuery)['wrongCount'] ?? 0;

$totalTimeQuery = mysqli_query($con, "SELECT totalTime FROM `review` WHERE studentId='$user_id' AND examTaken='$examTakenMinus' AND questionNumber=150") or die('query failed');
$totalTime = mysqli_fetch_assoc($totalTimeQuery)['totalTime'] ?? 0;

$score = ($correctAnswersCount / 150) * 100;

date_default_timezone_set('Asia/Manila');

$historyKey = 'history_inserted_' . $user_id . '_' . $examTakenMinus;
if (empty($_SESSION[$historyKey])) {
    $insertHistoryQuery = "INSERT INTO `history` (email, eid, kilanlan, score, level, sahi, wrong, date) VALUES ('$user_id', '$topics1', 'NARC Intermediate and Advance QBanks', '$score', '150', '$correctAnswersCount', '$wrongAnswersCount', NOW())";
    mysqli_query($con, $insertHistoryQuery) or die('query failed');
    $_SESSION[$historyKey] = true;
}

$passed = $score >= 75;

function fmtTime($s) {
    $h = floor($s/3600); $m = floor(($s%3600)/60); $sec = $s%60;
    return ($h>0 ? $h.'h ' : '') . ($m>0 ? $m.'m ' : '') . $sec.'s';
}

// System breakdown
$systemCounts = [];
$q = mysqli_query($con, "SELECT system, COUNT(*) as cnt FROM review WHERE studentId='$user_id' AND examTaken='$examTakenMinus' GROUP BY system");
while ($row = mysqli_fetch_assoc($q)) {
    $systemCounts[$row['system']] = $row['cnt'];
}
$hasSystem = array_sum($systemCounts) > 0;

$pageTitle = 'Test Results — Studium';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include '_layout/head.php'; ?>
</head>
<body>

<?php include '_layout/sidebar.php'; ?>

<main class="s-main">
  <div class="s-page-header">
    <h1>Test Results</h1>
    <p>Your exam performance breakdown for Exam #<?php echo $examTakenMinus + 1; ?></p>
  </div>

  <!-- Pass/Fail Banner -->
  <div class="s-card mb-4" style="border-left:4px solid <?php echo $passed ? 'var(--s-accent)' : '#ef4444'; ?>; background:<?php echo $passed ? 'rgba(13,148,136,.05)' : 'rgba(239,68,68,.05)'; ?>;">
    <div class="d-flex align-items-center gap-3">
      <div style="font-size:2.5rem; color:<?php echo $passed ? 'var(--s-accent)' : '#ef4444'; ?>;">
        <i class="bi bi-<?php echo $passed ? 'patch-check-fill' : 'x-circle-fill'; ?>"></i>
      </div>
      <div>
        <h3 class="fw-bold mb-1" style="color:<?php echo $passed ? 'var(--s-accent)' : '#ef4444'; ?>; font-size:1.3rem;">
          <?php echo $passed ? 'Passed!' : 'Keep Practicing!'; ?>
        </h3>
        <p class="mb-0" style="font-size:0.85rem; color:var(--s-muted);">
          You scored <strong><?php echo number_format($score, 1); ?>%</strong> — <?php echo $passed ? 'Great work! You passed the 75% benchmark.' : 'You need 75% to pass. Review and try again.'; ?>
        </p>
      </div>
      <div class="ms-auto">
        <a href="index.php" class="s-btn s-btn-primary">
          <i class="bi bi-house-fill me-1"></i> Dashboard
        </a>
      </div>
    </div>
  </div>

  <!-- Stats Row -->
  <div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
      <div class="s-card text-center">
        <div style="font-size:1.5rem; color:var(--s-accent);"><i class="bi bi-check-circle-fill"></i></div>
        <h3 class="fw-bold mb-0 mt-1" style="color:var(--s-accent);"><?php echo $correctAnswersCount; ?></h3>
        <p class="mb-0 text-muted" style="font-size:0.78rem;">Correct</p>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="s-card text-center">
        <div style="font-size:1.5rem; color:#ef4444;"><i class="bi bi-x-circle-fill"></i></div>
        <h3 class="fw-bold mb-0 mt-1" style="color:#ef4444;"><?php echo $wrongAnswersCount; ?></h3>
        <p class="mb-0 text-muted" style="font-size:0.78rem;">Incorrect</p>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="s-card text-center">
        <div style="font-size:1.5rem; color:var(--s-primary);"><i class="bi bi-percent"></i></div>
        <h3 class="fw-bold mb-0 mt-1" style="color:var(--s-primary);"><?php echo number_format($score, 1); ?>%</h3>
        <p class="mb-0 text-muted" style="font-size:0.78rem;">Score</p>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="s-card text-center">
        <div style="font-size:1.5rem; color:var(--s-muted);"><i class="bi bi-stopwatch"></i></div>
        <h5 class="fw-bold mb-0 mt-1" style="color:var(--s-primary); font-size:0.95rem;"><?php echo fmtTime($totalTime); ?></h5>
        <p class="mb-0 text-muted" style="font-size:0.78rem;">Total Time</p>
      </div>
    </div>
  </div>

  <!-- Score Chart + Progress -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="s-card h-100 text-center">
        <div class="s-card-title"><i class="bi bi-pie-chart-fill"></i> Score Breakdown</div>
        <div style="max-width:200px; margin:0 auto;">
          <canvas id="scoreChart"></canvas>
        </div>
        <div class="mt-3 d-flex justify-content-center gap-4" style="font-size:0.78rem;">
          <span style="color:var(--s-accent);"><i class="bi bi-circle-fill"></i> Correct</span>
          <span style="color:#ef4444;"><i class="bi bi-circle-fill"></i> Wrong</span>
        </div>
      </div>
    </div>
    <div class="col-md-8">
      <div class="s-card h-100">
        <div class="s-card-title"><i class="bi bi-bar-chart-fill"></i> Score Progress</div>
        <div class="s-progress" style="height:14px; margin-bottom:8px;">
          <div class="s-progress-bar" style="width:<?php echo number_format($score, 1); ?>%;"></div>
        </div>
        <div class="d-flex justify-content-between" style="font-size:0.75rem; color:var(--s-muted); margin-bottom:20px;">
          <span>0%</span><span><?php echo number_format($score, 1); ?>%</span><span>100%</span>
        </div>
        <table class="table" style="font-size:0.85rem;">
          <tr><td style="color:var(--s-muted);">Total Questions</td><td class="fw-semibold">150</td></tr>
          <tr><td style="color:var(--s-muted);">Correct Answers</td><td class="fw-semibold" style="color:var(--s-accent);"><?php echo $correctAnswersCount; ?></td></tr>
          <tr><td style="color:var(--s-muted);">Wrong Answers</td><td class="fw-semibold" style="color:#ef4444;"><?php echo $wrongAnswersCount; ?></td></tr>
          <tr><td style="color:var(--s-muted);">Time Taken</td><td class="fw-semibold"><?php echo fmtTime($totalTime); ?></td></tr>
        </table>
      </div>
    </div>
  </div>

  <!-- Review Table -->
  <div class="s-card s-table-wrap mb-4">
    <div class="s-card-title"><i class="bi bi-list-columns-reverse"></i> Question Review</div>
    <div class="table-responsive">
      <table id="reviewTable" class="table table-hover" style="font-size:0.82rem;">
        <thead>
          <tr>
            <th>#</th><th>ID</th><th>Concepts</th><th>Topics</th>
            <th>Client Needs</th><th>Time</th><th>Status</th><th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          if (isset($_GET['examTakenResult']) && ctype_digit($_GET['examTakenResult'])) {
              $examTakenMinus = intval($_GET['examTakenResult']);
          } else {
              $etq = mysqli_query($con, "SELECT examTaken FROM `login` WHERE id = '$user_id'") or die('query failed');
              $examTaken = mysqli_fetch_assoc($etq)['examTaken'] ?? 0;
              $examTakenMinus = $examTaken - 1;
          }
          $reviewQuery = mysqli_query($con, "SELECT isCorrect, questionNumber, questionId, topics1, system, cnc, timeTaken, ans, correctAns FROM `review` WHERE studentId='$user_id' AND examTaken='$examTakenMinus'") or die('query failed');
          while ($row = mysqli_fetch_assoc($reviewQuery)):
              $tm = (int)$row['timeTaken'];
              $fTime = ($tm >= 60 ? floor($tm/60).'m ' : '') . ($tm%60).'s';
              $correct = (bool)$row['isCorrect'];
          ?>
          <tr>
            <td class="fw-semibold"><?php echo $row['questionNumber']; ?></td>
            <td><?php echo str_pad($row['questionId'], 5, '0', STR_PAD_LEFT); ?></td>
            <td><?php echo htmlspecialchars($row['topics1']); ?></td>
            <td><?php echo htmlspecialchars($row['system']); ?></td>
            <td><?php echo htmlspecialchars($row['cnc']); ?></td>
            <td><?php echo $fTime; ?></td>
            <td>
              <?php if ($correct): ?>
                <span class="s-badge s-badge-teal"><i class="bi bi-check-lg me-1"></i>Correct</span>
              <?php else: ?>
                <span class="s-badge s-badge-red"><i class="bi bi-x-lg me-1"></i>Wrong</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="rationale/qpages.php?isCorrect=<?php echo $row['isCorrect']; ?>&questionId=<?php echo $row['questionId']; ?>&questionNumber=<?php echo $row['questionNumber']; ?>&topics1=<?php echo urlencode($row['topics1']); ?>&system=<?php echo urlencode($row['system']); ?>&cnc=<?php echo urlencode($row['cnc']); ?>&timeTaken=<?php echo $row['timeTaken']; ?>&ans=<?php echo urlencode($row['ans']); ?>&correctAns=<?php echo urlencode($row['correctAns']); ?>"
                 target="_blank" class="s-btn s-btn-outline" style="padding:4px 10px; font-size:0.72rem;">
                <i class="bi bi-eye"></i> View
              </a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Topics Breakdown -->
  <?php if ($hasSystem): ?>
  <div class="s-card mb-4">
    <div class="s-card-title"><i class="bi bi-diagram-3-fill"></i> Topics Breakdown</div>
    <div style="height:350px; margin-bottom:20px;">
      <canvas id="systemChart"></canvas>
    </div>
    <div class="table-responsive">
      <table class="table table-hover" style="font-size:0.82rem;">
        <thead>
          <tr><th>Topic</th><th>Questions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($systemCounts as $system => $count): ?>
          <tr>
            <td><?php echo htmlspecialchars($system); ?></td>
            <td><span class="s-badge" style="background:rgba(27,73,101,.1); color:var(--s-primary);"><?php echo $count; ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</main>


<script src="../ty/js/jquery-3.5.1.js"></script>
<script src="../ty/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function () {
  $('#reviewTable').DataTable({ pageLength: 10, autoWidth: false, responsive: true });
});

new Chart(document.getElementById('scoreChart'), {
  type: 'doughnut',
  data: {
    labels: ['Correct', 'Wrong'],
    datasets: [{
      data: [<?php echo $correctAnswersCount; ?>, <?php echo $wrongAnswersCount; ?>],
      backgroundColor: ['#0D9488', '#ef4444'],
      borderWidth: 0
    }]
  },
  options: { cutout: '72%', plugins: { legend: { display: false } } }
});

<?php if ($hasSystem): ?>
new Chart(document.getElementById('systemChart').getContext('2d'), {
  type: 'pie',
  data: {
    labels: <?php echo json_encode(array_keys($systemCounts)); ?>,
    datasets: [{
      data: <?php echo json_encode(array_values($systemCounts)); ?>,
      backgroundColor: [
        '#0D9488','#1B4965','#3B82F6','#F59E0B','#EF4444','#8B5CF6',
        '#EC4899','#10B981','#F97316','#06B6D4','#84CC16','#A855F7',
        '#14B8A6','#6366F1','#F43F5E','#22D3EE','#FBBF24','#4ADE80',
        '#FB923C','#C084FC'
      ]
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: true, position: 'right' } }
  }
});
<?php endif; ?>
</script>
</body>
</html>
