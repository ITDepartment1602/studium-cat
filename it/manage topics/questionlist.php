<?php
$ap_base    = '../';
$active_nav = 'topics';
$ap_title   = 'Question List';
$ap_extra_head = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">';
require_once '../partials/sidebar.php';
include('../../config.php');

$concept = $_GET['concept'] ?? '';
$title   = $_GET['title'] ?? '';

$stmt = mysqli_prepare(
    $con,
    "SELECT * FROM `question` WHERE topics1 = ? AND topic = ? ORDER BY `qnumber` ASC"
);
mysqli_stmt_bind_param($stmt, 'ss', $concept, $title);
mysqli_stmt_execute($stmt);
$data = mysqli_stmt_get_result($stmt);
?>

<div class="ap-card">
  <div class="ap-card-header">
    <div class="ap-card-title">
      <div class="ap-card-title-icon"><i class="bi bi-patch-question-fill"></i></div>
      <?= htmlspecialchars($concept ?: 'Question List') ?>
    </div>
    <a href="title.php?title=<?= urlencode($title) ?>" class="ap-btn ap-btn-ghost ap-btn-sm">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>

  <div style="padding:16px 20px; overflow-x:auto;">
    <table class="table data-table" style="width:100%;">
      <thead>
        <tr>
          <th>Question Number</th>
          <th>Question</th>
          <th>Choice A</th>
          <th>Choice B</th>
          <th>Choice C</th>
          <th>Choice D</th>
          <th>Correct Answer</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($rows = mysqli_fetch_assoc($data)): ?>
        <tr>
          <td><?= htmlspecialchars($rows['qnumber']) ?></td>
          <td><?= htmlspecialchars($rows['question']) ?></td>
          <td><?= htmlspecialchars($rows['choiceA']) ?></td>
          <td><?= htmlspecialchars($rows['choiceB']) ?></td>
          <td><?= htmlspecialchars($rows['choiceC']) ?></td>
          <td><?= htmlspecialchars($rows['choiceD']) ?></td>
          <td><span class="ap-badge ap-badge-success"><?= htmlspecialchars($rows['correctans']) ?></span></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$ap_extra_js = '
<script src="../.././table js/jquery-3.5.1.js"></script>
<script src="../.././table js/jquery.dataTables.min.js"></script>
<script src="../.././table js/script.js"></script>';
require_once '../partials/footer.php';
?>
