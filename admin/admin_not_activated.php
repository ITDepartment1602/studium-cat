<?php
include("count.php");
$count = new count;
include('../config.php');
date_default_timezone_set('Asia/Manila');

$ap_base    = '';
$active_nav = 'dashboard';
$ap_title   = 'Not Activated Subscriptions';
$ap_extra_head = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
require_once 'partials/sidebar.php';
?>

<div class="ap-card">
  <div class="ap-card-header">
    <div class="ap-card-title">
      <div class="ap-card-title-icon"><i class="bi bi-clock-history"></i></div>
      Not Activated Subscriptions
    </div>
    <a href="index.php" class="ap-btn ap-btn-ghost ap-btn-sm">
      <i class="bi bi-arrow-left"></i> Dashboard
    </a>
  </div>

  <div style="padding:16px 20px; overflow-x:auto;">
    <table id="notActivatedTable" class="table table-bordered" style="width:100%;">
      <thead>
        <tr>
          <th>Student No.</th>
          <th>Full Name</th>
          <th>Bundle</th>
          <th>Group</th>
          <th>Months Avail</th>
          <th>Email</th>
          <th>Password</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $query = "SELECT * FROM `login` WHERE status = 'user' AND groupname != 'Admin' AND dateexpired IS NULL";
        $data = mysqli_query($con, $query);
        while ($rows = mysqli_fetch_array($data)):
        ?>
        <tr>
          <td><?= htmlspecialchars($rows['studentnumber']) ?></td>
          <td style="font-weight:500;"><?= htmlspecialchars($rows['fullname']) ?></td>
          <td><?= htmlspecialchars(str_replace('Packege', 'Package', $rows['bundle_name'])) ?></td>
          <td><?= htmlspecialchars($rows['groupname']) ?></td>
          <td><?= htmlspecialchars($rows['subMonth']) ?> Month/s</td>
          <td><?= htmlspecialchars($rows['email']) ?></td>
          <td><?= htmlspecialchars($rows['password']) ?></td>
          <td style="white-space:nowrap;">
            <a href="#" onclick="confirmAction('update','<?= addslashes($rows['fullname']) ?>','<?= $rows['id'] ?>')"
               class="ap-btn ap-btn-accent ap-btn-xs"><i class="bi bi-pencil"></i> Update</a>
            <a href="#" onclick="confirmAction('delete','<?= addslashes($rows['fullname']) ?>','<?= $rows['id'] ?>')"
               class="ap-btn ap-btn-danger ap-btn-xs"><i class="bi bi-trash"></i> Delete</a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function confirmAction(action, name, id) {
  Swal.fire({
    title: action.charAt(0).toUpperCase() + action.slice(1) + ' Confirmation',
    text: `Are you sure you want to ${action} ${name}?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#0d9488',
    confirmButtonText: 'Yes',
    cancelButtonText: 'Cancel',
  }).then(result => {
    if (result.isConfirmed) {
      if (action === 'delete') window.location.href = `admin_delete.php?id=${id}`;
      else if (action === 'update') window.location.href = `admin_update.php?id=${id}`;
    }
  });
}
</script>

<?php
$ap_extra_js = '
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>$(\'#notActivatedTable\').DataTable();</script>';
require_once 'partials/footer.php';
?>
