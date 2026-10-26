<?php
// Reusable student list, filtered by ?filter=. Used by the dashboard stat cards.
include('../config.php');
date_default_timezone_set('Asia/Manila');
$current_date = date('Y-m-d H:i:s');

// Filter registry: label + WHERE clause. Keys are whitelisted, so no injection.
$base = "status = 'user' AND groupname != 'Admin'";
$filters = [
    'all'          => ['Total Students',          $base],
    'activated'    => ['Activated Students',       "$base AND dateexpired IS NOT NULL AND dateexpired > ?"],
    'expired'      => ['Expired Subscriptions',    "$base AND dateexpired IS NOT NULL AND dateexpired != '' AND dateexpired < ?"],
    'notactivated' => ['Not Activated Students',   "$base AND (dateexpired IS NULL OR dateexpired = '')"],
    'active'       => ['Active Now',               "$base AND lastlogin >= ?"],
];

$filter = $_GET['filter'] ?? 'all';
if (!isset($filters[$filter])) $filter = 'all';
[$pageLabel, $where] = $filters[$filter];

// Build query with the right bound parameter (if any).
$sql = "SELECT * FROM `login` WHERE $where ORDER BY id DESC";
$stmt = mysqli_prepare($con, $sql);
if ($filter === 'active') {
    $threshold = date('Y-m-d H:i:s', time() - 600);
    mysqli_stmt_bind_param($stmt, 's', $threshold);
} elseif (in_array($filter, ['activated', 'expired'])) {
    mysqli_stmt_bind_param($stmt, 's', $current_date);
}
mysqli_stmt_execute($stmt);
$data = mysqli_stmt_get_result($stmt);

$ap_base    = '';
$active_nav = 'dashboard';
$ap_title   = $pageLabel;
$ap_extra_head = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
require_once 'partials/sidebar.php';
?>

<div class="ap-card">
  <div class="ap-card-header">
    <div class="ap-card-title">
      <div class="ap-card-title-icon"><i class="bi bi-person-lines-fill"></i></div>
      <?= htmlspecialchars($pageLabel) ?>
    </div>
    <a href="index.php" class="ap-btn ap-btn-ghost ap-btn-sm">
      <i class="bi bi-arrow-left"></i> Dashboard
    </a>
  </div>

  <div style="padding:16px 20px; overflow-x:auto;">
    <table id="studentsTable" class="table" style="width:100%;">
      <thead>
        <tr>
          <th>Student No.</th>
          <th>Full Name</th>
          <th>Bundle</th>
          <th>Group</th>
          <th>Date Expired</th>
          <th>Email</th>
          <th>Password</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($rows = mysqli_fetch_assoc($data)): ?>
        <tr>
          <td><?= htmlspecialchars($rows['studentnumber']) ?></td>
          <td style="font-weight:500;"><?= htmlspecialchars($rows['fullname']) ?></td>
          <td><?= htmlspecialchars(str_replace('Packege', 'Package', $rows['bundle_name'])) ?></td>
          <td><?= htmlspecialchars($rows['groupname']) ?></td>
          <td style="white-space:nowrap;"><?= !empty($rows['dateexpired']) ? htmlspecialchars($rows['dateexpired']) : '<span style="color:#94a3b8;">—</span>' ?></td>
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
    icon: 'warning', showCancelButton: true,
    confirmButtonColor: '#0d9488', confirmButtonText: 'Yes', cancelButtonText: 'Cancel',
  }).then(r => {
    if (!r.isConfirmed) return;
    if (action === 'delete') window.location.href = `admin_delete.php?id=${id}`;
    else if (action === 'update') window.location.href = `admin_update.php?id=${id}`;
  });
}
</script>

<?php
$ap_extra_js = '
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>$("#studentsTable").DataTable({
  order: [], pageLength: 25, dom: "tip",
  language: { info: "Showing _START_–_END_ of _TOTAL_ students", infoEmpty: "No students found", paginate: { previous: "← Prev", next: "Next →" } }
});</script>';
require_once 'partials/footer.php';
?>
