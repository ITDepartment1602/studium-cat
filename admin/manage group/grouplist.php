<?php
$ap_base    = '../';
$active_nav = 'group';
$ap_title   = 'Group Members';
$ap_extra_head = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
require_once '../partials/sidebar.php';
include('../../config.php');

$groupname = $_GET['groupname'] ?? '';

$stmt = mysqli_prepare($con, "SELECT * FROM `login` WHERE groupname = ?");
mysqli_stmt_bind_param($stmt, 's', $groupname);
mysqli_stmt_execute($stmt);
$data = mysqli_stmt_get_result($stmt);
?>

<div class="ap-card">
  <div class="ap-card-header">
    <div class="ap-card-title">
      <div class="ap-card-title-icon"><i class="bi bi-people-fill"></i></div>
      <?= htmlspecialchars($groupname ?: 'Group Members') ?>
    </div>
    <a href="index.php" class="ap-btn ap-btn-ghost ap-btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
  </div>

  <div style="padding:16px 20px; overflow-x:auto;">
    <table class="table data-table" style="width:100%;">
      <thead>
        <tr>
          <th>Student Number</th>
          <th>Full Name</th>
          <th>Bundle Name</th>
          <th>Date Enrolled</th>
          <th>Date Expired</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($rows = mysqli_fetch_assoc($data)): ?>
        <tr>
          <td><?= htmlspecialchars($rows['studentnumber']) ?></td>
          <td style="font-weight:500;"><?= htmlspecialchars($rows['fullname']) ?></td>
          <td><?= htmlspecialchars(str_replace('Packege', 'Package', $rows['bundle_name'])) ?></td>
          <td><?= htmlspecialchars($rows['dateenrolled']) ?></td>
          <td><?= htmlspecialchars($rows['dateexpired']) ?></td>
          <td>
            <button class="ap-btn ap-btn-danger ap-btn-xs" onclick="confirmDelete('<?= $rows['id'] ?>')">
              <i class="bi bi-trash"></i> Delete
            </button>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function confirmDelete(id) {
  Swal.fire({
    title: 'Delete Record?',
    text: 'This action cannot be undone.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Delete',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#ef4444',
  }).then(r => { if (r.isConfirmed) window.location.href = `action.php?id=${id}&action=delete`; });
}
</script>

<?php
$ap_extra_js = '
<script src="../.././table js/jquery-3.5.1.js"></script>
<script src="../.././table js/jquery.dataTables.min.js"></script>
<script src="../.././table js/script.js"></script>';
require_once '../partials/footer.php';
?>
