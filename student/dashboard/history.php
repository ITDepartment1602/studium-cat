<?php
include '../../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Expiry check
$select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'");
$fetch  = mysqli_fetch_assoc($select);
date_default_timezone_set('Asia/Manila');
$daysLeft = floor((strtotime($fetch['dateexpired']) - time()) / 86400);
if ($daysLeft < 0) { header('Location: ../../logout.php'); exit; }

// History query — review table lives in the quiz DB
$quizCon = getQuizConnection();
$query = "SELECT examTaken,
             MAX(CASE WHEN questionNumber = 150 THEN totalTime ELSE NULL END) AS totalTime,
             MAX(CASE WHEN questionNumber = 150 THEN timestamp ELSE NULL END) AS timestamp,
             topics1,
             SUM(CASE WHEN isCorrect = 1 THEN 1 ELSE 0 END) AS correctAnswers,
             COUNT(*) AS totalQuestions
      FROM review
      WHERE studentId = '$user_id'
      GROUP BY examTaken, topics1
      HAVING COUNT(*) >= 145
      ORDER BY examTaken ASC";
$result = mysqli_query($quizCon, $query);

$totalExams = 0;
$totalTimeAll = 0;
$topicCounts = [];
$correctAnswersByTopic = [];

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $totalExams++;
        $totalTimeAll += $row['totalTime'];
        $topic = $row['topics1'];
        $correctAnswersByTopic[$topic] = ($correctAnswersByTopic[$topic] ?? 0) + $row['correctAnswers'];
        $topicCounts[$topic] = ($topicCounts[$topic] ?? 0) + 1;
    }
    $bestTopic = !empty($correctAnswersByTopic) ? array_keys($correctAnswersByTopic, max($correctAnswersByTopic))[0] : 'N/A';
} else {
    $bestTopic = 'N/A';
}

function formatTime($secs) {
    $h = floor($secs / 3600); $m = floor(($secs % 3600) / 60); $s = $secs % 60;
    $out = '';
    if ($h > 0) $out .= $h . 'h ';
    if ($m > 0) $out .= $m . 'm ';
    $out .= $s . 's';
    return trim($out);
}

$pageTitle = 'Exam History — Studium';
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
    <h1>Exam History</h1>
    <p>Review your past exam performance and track your progress</p>
  </div>

  <!-- Summary Stats -->
  <div class="row g-3 mb-4">
    <div class="col-md-4 col-12">
      <div class="s-kpi-card" style="--kc:#0D9488;">
        <div class="s-kpi-icon-row"><i class="bi bi-journal-check" style="color:#0D9488;"></i></div>
        <div class="s-kpi-num"><?php echo $totalExams; ?></div>
        <div class="s-kpi-label">Total Exams Taken</div>
        <div class="s-kpi-bar"><div style="width:<?php echo min(100, $totalExams * 5); ?>%;"></div></div>
        <div class="s-kpi-sub"><?php echo $totalExams === 1 ? '1 exam completed' : $totalExams . ' exams completed'; ?></div>
      </div>
    </div>
    <div class="col-md-4 col-12">
      <div class="s-kpi-card" style="--kc:#1e6091;">
        <div class="s-kpi-icon-row"><i class="bi bi-clock-history" style="color:#1e6091;"></i></div>
        <div class="s-kpi-num" style="font-size:1.5rem;"><?php echo formatTime($totalTimeAll); ?></div>
        <div class="s-kpi-label">Total Time Spent</div>
        <div class="s-kpi-bar"><div style="width:<?php echo min(100, $totalExams * 5); ?>%; background:#1e6091;"></div></div>
        <div class="s-kpi-sub">Cumulative practice time</div>
      </div>
    </div>
    <div class="col-md-4 col-12">
      <div class="s-kpi-card" style="--kc:#f59e0b;">
        <div class="s-kpi-icon-row"><i class="bi bi-trophy-fill" style="color:#f59e0b;"></i></div>
        <div class="s-kpi-num" style="font-size:1rem; padding-top:8px;"><?php echo htmlspecialchars($bestTopic); ?></div>
        <div class="s-kpi-label">Best Topic</div>
        <div class="s-kpi-bar"><div style="width:80%; background:#f59e0b;"></div></div>
        <div class="s-kpi-sub">Highest correct answers</div>
      </div>
    </div>
  </div>

  <!-- History Table -->
  <div class="s-card s-table-wrap">
    <div class="s-card-title"><i class="bi bi-table"></i> Exam Records</div>
    <div class="table-responsive">
      <table id="reviewTable" class="table table-hover" style="font-size:0.85rem;">
        <thead>
          <tr>
            <th>Exam #</th>
            <th>Date &amp; Time</th>
            <th>Topic</th>
            <th>Score</th>
            <th>Time Taken</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          mysqli_data_seek($result, 0);
          while ($row = mysqli_fetch_assoc($result)):
            $score    = $row['correctAnswers'];
            $scorePercent = round(($score / 150) * 100);
            $badgeClass = $scorePercent >= 75 ? 's-score-pass' : ($scorePercent >= 60 ? 's-score-warn' : 's-score-fail');
          ?>
          <tr>
            <td class="fw-semibold">Exam #<?php echo ($row['examTaken'] + 1); ?></td>
            <td><?php echo $row['timestamp'] ? date('M d, Y h:i A', strtotime($row['timestamp'])) : '—'; ?></td>
            <td><?php echo htmlspecialchars($row['topics1']); ?></td>
            <td>
              <span class="s-badge <?php echo $badgeClass; ?>">
                <?php echo $score; ?>/150
              </span>
            </td>
            <td><?php echo formatTime($row['totalTime']); ?></td>
            <td>
              <a href="history_details.php?examTaken=<?php echo $row['examTaken']; ?>&userId=<?php echo $user_id; ?>"
                 class="s-btn s-btn-primary" style="padding:5px 12px; font-size:0.75rem;">
                <i class="bi bi-eye"></i> View
              </a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>

<div class="s-footer"><span>© Studium 2025, All Right Reserved.</span></div>

<script src="../ty/js/bootstrap.bundle.min.js"></script>
<script src="../ty/js/jquery-3.5.1.js"></script>
<script src="../ty/js/jquery.dataTables.min.js"></script>
<script src="../ty/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
  $('#reviewTable').DataTable({
    paging: true,
    lengthChange: true,
    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
    pageLength: 10,
    searching: true,
    ordering: true,
    order: [[0, 'desc']],
    info: true,
    autoWidth: false,
    responsive: true,
    language: {
      search: '<i class="bi bi-search"></i>',
      searchPlaceholder: 'Search exams...',
      lengthMenu: 'Show _MENU_ entries',
      info: 'Showing _START_ to _END_ of _TOTAL_ exams',
      emptyTable: 'No exam records found',
      zeroRecords: 'No matching exams found'
    }
  });
});
</script>
</body>
</html>
