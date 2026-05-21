<?php
$ap_base    = '../';
$active_nav = 'result';
$ap_title   = 'Manage Result';
$ap_extra_head = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="../../table css/dataTables.bootstrap5.min.css">';
require_once '../partials/sidebar.php';
include('../../config.php');
?>

<div class="ap-card">
  <div class="ap-card-header">
    <div class="ap-card-title">
      <div class="ap-card-title-icon"><i class="bi bi-bar-chart-fill"></i></div>
      Student Results
    </div>
  </div>

  <div style="padding:16px 20px; overflow-x:auto;">
    <table class="table data-table" style="width:100%;">
      <thead>
        <tr>
          <th>Full Name</th>
          <th>Number of History</th>
          <th>Average Score</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $query = "
          SELECT login.id AS login_id, login.fullname, COUNT(history.email) AS history_count,
                 AVG(history.score) AS average_score
          FROM login
          INNER JOIN history ON login.id = history.email
          WHERE login.groupname != 'Admin'
          GROUP BY login.id
          HAVING AVG(history.score) IS NOT NULL";
        $data = mysqli_query($con, $query);
        while ($rows = mysqli_fetch_array($data)):
          $avg = round($rows['average_score'], 2);
          if ($avg < 40) $badge = 'ap-badge-danger';
          elseif ($avg <= 70) $badge = 'ap-badge-warning';
          else $badge = 'ap-badge-success';
        ?>
        <tr>
          <td style="font-weight:500;"><?= htmlspecialchars($rows['fullname']) ?></td>
          <td><?= $rows['history_count'] ?></td>
          <td><span class="ap-badge <?= $badge ?>"><?= $avg ?>%</span></td>
          <td>
            <a href="open_history.php?student_id=<?= $rows['login_id'] ?>" class="ap-btn ap-btn-primary ap-btn-xs">
              <i class="bi bi-eye"></i> View
            </a>
          </td>
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
<script src="../.././table js/dataTables.bootstrap5.min.js"></script>
<script src="../.././table js/script.js"></script>';
require_once '../partials/footer.php';
?>
