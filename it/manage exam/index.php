<?php
$ap_base    = '../';
$active_nav = 'exam_mode';
$ap_title   = 'Exam Mode — Omission Report';
$ap_extra_head = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="../../table css/dataTables.bootstrap5.min.css">';
require_once '../partials/sidebar.php';
include('../../config.php');
date_default_timezone_set('Asia/Manila');

$rows = db()->fetchAll(
    "SELECT er.examTaken, er.student_id, l.fullname, l.studentnumber,
            COUNT(*) AS totalItems,
            SUM(er.omitted) AS omittedCount,
            MAX(er.final_result) AS finalResult,
            MAX(er.timestamp) AS timestamp
     FROM exammoderesults er
     LEFT JOIN login l ON l.id = er.student_id
     GROUP BY er.examTaken, er.student_id
     ORDER BY MAX(er.timestamp) DESC"
);
?>

<div class="ap-card">
  <div class="ap-card-header">
    <div class="ap-card-title">
      <div class="ap-card-title-icon"><i class="bi bi-clipboard-data-fill"></i></div>
      Adaptive Exam — Omission Report
    </div>
    <a href="feedback.php" class="ap-btn ap-btn-ghost ap-btn-xs"><i class="bi bi-chat-heart-fill"></i> Feedback Inbox</a>
  </div>

  <div style="padding:16px 20px; overflow-x:auto;">
    <table class="table data-table" style="width:100%;">
      <thead>
        <tr>
          <th>Student</th>
          <th>Student No.</th>
          <th>Attempt</th>
          <th>Items Delivered</th>
          <th>Omitted</th>
          <th>Omission Rate</th>
          <th>Result</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row):
            $total   = intval($row['totalItems']);
            $omitted = intval($row['omittedCount']);
            $rate    = $total > 0 ? round(($omitted / $total) * 100, 1) : 0.0;
            $result  = $row['finalResult'] ?? null;
            $resultLabel = $result === 'PASSED' ? 'PASSED' : ($result === 'FAILED' ? 'FAILED' : 'Incomplete');
            $resultBadge = $result === 'PASSED' ? 'ap-badge-success' : ($result === 'FAILED' ? 'ap-badge-danger' : 'ap-badge-warning');
        ?>
        <tr>
          <td style="font-weight:500;"><?= htmlspecialchars($row['fullname'] ?? 'Unknown') ?></td>
          <td><?= htmlspecialchars($row['studentnumber'] ?? '') ?></td>
          <td>#<?= htmlspecialchars($row['examTaken']) ?></td>
          <td><?= $total ?></td>
          <td><?= $omitted ?></td>
          <td><?= $rate ?>%</td>
          <td><span class="ap-badge <?= $resultBadge ?>"><?= htmlspecialchars($resultLabel) ?></span></td>
          <td data-order="<?= $row['timestamp'] ? strtotime($row['timestamp']) : 0 ?>"><?= $row['timestamp'] ? date('M j, Y g:i A', strtotime($row['timestamp'])) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$ap_extra_js = '
<script src="../.././table js/jquery-3.5.1.js"></script>
<script src="../.././table js/jquery.dataTables.min.js"></script>
<script src="../.././table js/script.js"></script>
<script>
$(document).ready(function () {
  // script.js initializes .data-table with no explicit order, which defaults to
  // sorting by column 0 (Student) — override so the latest attempt always shows first.
  $(".data-table").DataTable().order([[7, "desc"]]).draw();
});
</script>';
require_once '../partials/footer.php';
?>
