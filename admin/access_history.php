<?php
date_default_timezone_set('Asia/Manila');
include_once '../config.php';

$query = "
    SELECT id, studentnumber, fullname, dateenrolled, subMonth, dateexpired
    FROM login
    WHERE dateenrolled >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    ORDER BY id DESC
";
$result = $con->query($query);

$ap_base    = '';
$active_nav = 'history';
$ap_title   = 'Access History';
$ap_subtitle = 'Students enrolled in the last 12 months';
$ap_extra_head = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">';
require_once 'partials/sidebar.php';
?>

<div class="ap-card">
  <div class="ap-card-header">
    <div class="ap-card-title">
      <div class="ap-card-title-icon"><i class="bi bi-clock-history"></i></div>
      Access History
    </div>
    <a href="index.php" class="ap-btn ap-btn-ghost ap-btn-sm">
      <i class="bi bi-arrow-left"></i> Dashboard
    </a>
  </div>

  <div style="padding:16px 20px; overflow-x:auto;">
    <table id="accessTable" class="table table-bordered" style="width:100%;">
      <thead>
        <tr>
          <th>Sort</th>
          <th>Student No.</th>
          <th>Full Name</th>
          <th>Date Enrolled</th>
          <th>Subscription</th>
          <th>Email</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()):
          $subMonth   = $row['subMonth']   ?? null;
          $dateExpired = $row['dateexpired'] ?? null;
          $now = new DateTime('now', new DateTimeZone('Asia/Manila'));

          // Email check
          $check = $con->prepare("SELECT id FROM email_sent_status WHERE student_id = ? LIMIT 1");
          $check->bind_param("i", $row['id']);
          $check->execute();
          $email_sent = $check->get_result()->num_rows > 0;
        ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td><?= htmlspecialchars($row['studentnumber']) ?></td>
          <td style="font-weight:500;"><?= htmlspecialchars($row['fullname']) ?></td>
          <td><?= date('F d, Y | h:i A', strtotime($row['dateenrolled'])) ?></td>
          <td>
            <?php
            if (!empty($subMonth)) {
                echo '<span class="ap-badge ap-badge-info">' . htmlspecialchars($subMonth) . ' Month/s</span>';
            } elseif (!empty($dateExpired) && $dateExpired !== '0000-00-00 00:00:00') {
                $exp = new DateTime($dateExpired, new DateTimeZone('Asia/Manila'));
                if ($exp < $now) {
                    echo '<span class="ap-badge ap-badge-danger">Expired</span>';
                } else {
                    $diff = $now->diff($exp);
                    $left = ($diff->y * 12 + $diff->m) . 'm ' . $diff->d . 'd left';
                    echo '<span class="ap-badge ap-badge-success">Active — ' . $left . '</span>';
                }
            } else {
                echo '<span class="ap-badge ap-badge-gray">No Data</span>';
            }
            ?>
          </td>
          <td>
            <?php if ($email_sent): ?>
              <span class="ap-badge ap-badge-gray"><i class="bi bi-check2"></i> Sent</span>
            <?php else: ?>
              <a href="send_email.php?id=<?= $row['id'] ?>" class="ap-btn ap-btn-accent ap-btn-xs">
                <i class="bi bi-envelope"></i> Send
              </a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$ap_extra_js = '
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(\'#accessTable\').DataTable({ "order": [[0,"desc"]], "columnDefs":[{"targets":0,"visible":false}] });
</script>';
require_once 'partials/footer.php';
?>
