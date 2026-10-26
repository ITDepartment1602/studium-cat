<?php
// Handle status update before any output
include('../../config.php');
if (isset($_GET['id']) && isset($_GET['status'])) {
    $id     = (int)$_GET['id'];
    $status = (int)$_GET['status'];
    mysqli_query($con, "UPDATE signup SET status='$status' WHERE id='$id'");
    header("location:index.php");
    exit;
}

$ap_base    = '../';
$active_nav = 'inquiries';
$ap_title   = 'Inquiries';
$ap_extra_head = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="../../table css/dataTables.bootstrap5.min.css">';
require_once '../partials/sidebar.php';
?>

<div class="ap-card">
  <div class="ap-card-header">
    <div class="ap-card-title">
      <div class="ap-card-title-icon"><i class="bi bi-envelope-fill"></i></div>
      Sign-up Inquiries
    </div>
  </div>

  <div style="padding:16px 20px; overflow-x:auto;">
    <table class="table data-table" style="width:100%;">
      <thead>
        <tr>
          <th>Email</th>
          <th>Full Name</th>
          <th>Facebook</th>
          <th>Contact</th>
          <th>Address</th>
          <th>Review Center</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $data = mysqli_query($con, "SELECT * FROM signup");
        while ($rows = mysqli_fetch_array($data)):
          if ($rows['status'] == 1) $badge = '<span class="ap-badge ap-badge-warning">Pending</span>';
          elseif ($rows['status'] == 2) $badge = '<span class="ap-badge ap-badge-danger">Not Proceed</span>';
          else $badge = '<span class="ap-badge ap-badge-success">Proceed</span>';
        ?>
        <tr>
          <td><?= htmlspecialchars($rows['email']) ?></td>
          <td style="font-weight:500;"><?= htmlspecialchars($rows['fullname']) ?></td>
          <td><?= htmlspecialchars($rows['facebookname'] ?? '') ?></td>
          <td><?= htmlspecialchars($rows['contactnumber'] ?? '') ?></td>
          <td><?= htmlspecialchars($rows['address'] ?? '') ?></td>
          <td><?= htmlspecialchars($rows['rcenter'] ?? '') ?></td>
          <td><?= $badge ?></td>
          <td>
            <select class="ap-form-select" style="width:auto;padding:4px 8px;font-size:0.72rem;"
              onchange="statusUpdate(this.value,'<?= $rows['id'] ?>')">
              <option value="" disabled selected>Change</option>
              <option value="1">Pending</option>
              <option value="2">Not Proceed</option>
              <option value="3">Proceed</option>
            </select>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function statusUpdate(value, id) {
  window.location.href = 'index.php?id=' + id + '&status=' + value;
}
</script>

<?php
$ap_extra_js = '
<script src="../.././table js/jquery-3.5.1.js"></script>
<script src="../.././table js/jquery.dataTables.min.js"></script>
<script src="../.././table js/dataTables.bootstrap5.min.js"></script>
<script src="../.././table js/script.js"></script>';
require_once '../partials/footer.php';
?>
