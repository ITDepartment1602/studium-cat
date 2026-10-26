<?php
$ap_base    = '../';
$active_nav = 'result';
$ap_title   = 'Student Result';
$ap_extra_head = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">';

require_once '../partials/sidebar.php';
include('../../config.php');

// student_id is required; bail back to the list if it is missing.
if (!isset($_GET['student_id'])) {
    header('Location: index.php');
    exit();
}
$student_id = intval($_GET['student_id']);

// Categories shown as score cards.
$categories = [
    'Adult Health',
    'Child Health',
    'Critical Care',
    'Fundamentals',
    'Leadership and Management',
    'Maternal and Newborn Health',
    'Mental Health',
    'Pharmacology',
];

// Average score for one category. Guards against NULL (no attempts) so
// round() never receives null (PHP 8.1+ deprecation / fatal).
function getAverageScore($con, $eid, $student_id)
{
    $stmt = mysqli_prepare($con, "SELECT AVG(score) AS average FROM history WHERE eid = ? AND email = ?");
    mysqli_stmt_bind_param($stmt, 'si', $eid, $student_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return ($row && $row['average'] !== null) ? round((float) $row['average'], 2) : 0;
}

// Fetch the student's history (also used for the name + the table below).
$stmt = mysqli_prepare(
    $con,
    "SELECT history.*, login.fullname
     FROM history
     LEFT JOIN login ON history.email = login.id
     WHERE login.id = ?
     ORDER BY history.date DESC"
);
mysqli_stmt_bind_param($stmt, 'i', $student_id);
mysqli_stmt_execute($stmt);
$history = mysqli_stmt_get_result($stmt);

$rows = mysqli_fetch_all($history, MYSQLI_ASSOC);
$student_name = !empty($rows) ? $rows[0]['fullname'] : 'Student';
?>

<div class="ap-card">
  <div class="ap-card-header">
    <div class="ap-card-title">
      <div class="ap-card-title-icon"><i class="bi bi-person-lines-fill"></i></div>
      <?= htmlspecialchars($student_name) ?>'s Results
    </div>
    <a href="index.php" class="ap-btn ap-btn-ghost ap-btn-sm">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>

  <div class="ap-card-body">
    <div class="row g-3">
      <?php foreach ($categories as $category):
        $averageScore = getAverageScore($con, $category, $student_id);
        if ($averageScore < 40)      $bar = '#dc2626';
        elseif ($averageScore <= 70) $bar = '#d97706';
        else                         $bar = '#16a34a';
      ?>
      <div class="col-6 col-lg-3">
        <div class="ap-card" style="margin:0;height:100%;">
          <div class="ap-card-header" style="justify-content:center;">
            <div class="ap-card-title" style="font-size:0.82rem;text-align:center;">
              <?= htmlspecialchars($category) ?>
            </div>
          </div>
          <div class="ap-card-body" style="text-align:center;">
            <div style="font-size:1.9rem;font-weight:700;color:var(--ap-text);margin-bottom:10px;">
              <?= $averageScore ?>%
            </div>
            <div class="progress" style="height:8px;border-radius:6px;">
              <div class="progress-bar" role="progressbar"
                   style="width:<?= $averageScore ?>%;background-color:<?= $bar ?>;"
                   aria-valuenow="<?= $averageScore ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div style="margin-top:22px; overflow-x:auto;">
      <table class="table data-table" style="width:100%;">
        <thead>
          <tr>
            <th>Score</th>
            <th>Correct Answer</th>
            <th>Wrong Answer</th>
            <th>Date Test</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td><span class="ap-badge ap-badge-navy"><?= htmlspecialchars($r['score']) ?>%</span></td>
            <td><?= htmlspecialchars($r['sahi']) ?></td>
            <td><?= htmlspecialchars($r['wrong']) ?></td>
            <td><?= $r['date'] ? date('F j, Y - h:i A', strtotime($r['date'])) : '—' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
$ap_extra_js = '
<script src="../.././table js/jquery-3.5.1.js"></script>
<script src="../.././table js/jquery.dataTables.min.js"></script>
<script src="../.././table js/script.js"></script>';
require_once '../partials/footer.php';
?>
