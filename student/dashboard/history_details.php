<?php
include '../../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}
$user_id   = $_SESSION['user_id'];
$examTaken = intval($_GET['examTaken'] ?? 0);

// Correct / wrong / time for this exam
$correctRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as n FROM `review` WHERE studentId='$user_id' AND isCorrect=1 AND examTaken='$examTaken'"));
$wrongRow   = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as n FROM `review` WHERE studentId='$user_id' AND isCorrect=0 AND examTaken='$examTaken'"));
$timeRow    = mysqli_fetch_assoc(mysqli_query($con, "SELECT totalTime FROM `review` WHERE studentId='$user_id' AND examTaken='$examTaken' AND questionNumber=150"));

$correctCount = (int)($correctRow['n'] ?? 0);
$wrongCount   = (int)($wrongRow['n']   ?? 0);
$totalTime    = (int)($timeRow['totalTime'] ?? 0);
$totalQ       = $correctCount + $wrongCount;
$scorePercent = $totalQ > 0 ? round(($correctCount / 150) * 100, 1) : 0;

function fmtTime($s) {
    $h = floor($s/3600); $m = floor(($s%3600)/60); $sec = $s%60;
    return ($h>0 ? $h.'h ' : '') . ($m>0 ? $m.'m ' : '') . $sec.'s';
}

$pageTitle = 'Exam Details — Studium';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include '_layout/head.php'; ?>
</head>
<body>

<?php include '_layout/sidebar.php'; ?>

<main class="s-main">

  <!-- Back button + header -->
  <div class="d-flex align-items-center gap-3 mb-4">
    <a href="history.php" class="s-btn s-btn-outline" style="padding:7px 14px;">
      <i class="bi bi-arrow-left"></i> Back
    </a>
    <div>
      <h1 class="s-page-header mb-0" style="font-size:1.4rem;">Exam #<?php echo $examTaken + 1; ?> Details</h1>
      <p class="text-muted mb-0" style="font-size:0.82rem;">Question-by-question breakdown</p>
    </div>
  </div>

  <!-- Score Summary -->
  <div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
      <div class="s-card text-center">
        <div style="font-size:1.6rem; color:var(--s-accent);"><i class="bi bi-check-circle-fill"></i></div>
        <h3 class="fw-bold mb-0" style="color:var(--s-accent);"><?php echo $correctCount; ?></h3>
        <p class="mb-0 text-muted" style="font-size:0.78rem;">Correct</p>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="s-card text-center">
        <div style="font-size:1.6rem; color:#ef4444;"><i class="bi bi-x-circle-fill"></i></div>
        <h3 class="fw-bold mb-0" style="color:#ef4444;"><?php echo $wrongCount; ?></h3>
        <p class="mb-0 text-muted" style="font-size:0.78rem;">Incorrect</p>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="s-card text-center">
        <div style="font-size:1.6rem; color:var(--s-primary);"><i class="bi bi-percent"></i></div>
        <h3 class="fw-bold mb-0" style="color:var(--s-primary);"><?php echo $scorePercent; ?>%</h3>
        <p class="mb-0 text-muted" style="font-size:0.78rem;">Score</p>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="s-card text-center">
        <div style="font-size:1.6rem; color:var(--s-muted);"><i class="bi bi-stopwatch"></i></div>
        <h5 class="fw-bold mb-0" style="color:var(--s-primary); font-size:0.95rem;"><?php echo fmtTime($totalTime); ?></h5>
        <p class="mb-0 text-muted" style="font-size:0.78rem;">Total Time</p>
      </div>
    </div>
  </div>

  <!-- Score bar -->
  <div class="s-card mb-4">
    <div class="s-card-title"><i class="bi bi-bar-chart-fill"></i> Score: <?php echo $correctCount; ?>/150</div>
    <div class="s-progress" style="height:14px;">
      <div class="s-progress-bar" style="width:<?php echo $scorePercent; ?>%; transition:width 1s ease;"></div>
    </div>
    <div class="d-flex justify-content-between mt-2" style="font-size:0.75rem; color:var(--s-muted);">
      <span>0%</span><span><?php echo $scorePercent; ?>%</span><span>100%</span>
    </div>
  </div>

  <!-- Questions Table -->
  <div class="s-card s-table-wrap">
    <div class="s-card-title"><i class="bi bi-list-columns-reverse"></i> Question Breakdown</div>
    <div class="table-responsive">
      <table id="reviewTable" class="table table-hover" style="font-size:0.82rem;">
        <thead>
          <tr>
            <th>#</th>
            <th>ID</th>
            <th>Topic</th>
            <th>System</th>
            <th>Client Needs</th>
            <th>Time</th>
            <th>Result</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $reviewQuery = mysqli_query($con, "SELECT isCorrect, ans, questionNumber, questionId, topics1, system, cnc, timeTaken, correctAns FROM `review` WHERE studentId='$user_id' AND examTaken='$examTaken'");
          while ($row = mysqli_fetch_assoc($reviewQuery)):
            $tm  = (int)$row['timeTaken'];
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
              <a href="rationale/qpages.php?isCorrect=<?php echo $row['isCorrect']; ?>&questionId=<?php echo $row['questionId']; ?>&topics1=<?php echo urlencode($row['topics1']); ?>&system=<?php echo urlencode($row['system']); ?>&cnc=<?php echo urlencode($row['cnc']); ?>&timeTaken=<?php echo $row['timeTaken']; ?>&ans=<?php echo urlencode($row['ans']); ?>&correctAns=<?php echo urlencode($row['correctAns']); ?>&questionNumber=<?php echo $row['questionNumber']; ?>"
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

</main>


<script src="../ty/js/jquery-3.5.1.js"></script>
<script src="../ty/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
  $('#reviewTable').DataTable({ paging:true, lengthChange:true, searching:true, ordering:true, info:true, autoWidth:false, responsive:true });
  // Animate progress bar on load
  document.querySelector('.s-progress-bar').style.width = '<?php echo $scorePercent; ?>%';
});
</script>
</body>
</html>
