<?php
$ap_base    = '../';
$active_nav = 'group';
$ap_title   = 'Manage Group';
$ap_extra_head = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
$ap_topbar_actions = '
  <button class="ap-btn ap-btn-primary ap-btn-sm" onclick="openModal()">
    <i class="bi bi-plus-lg"></i> Add Group
  </button>';
require_once '../partials/sidebar.php';
include("../../config.php");
?>

<!-- Add Group Modal -->
<div class="ap-modal-overlay" id="addModal">
  <div class="ap-modal">
    <div class="ap-modal-header">
      <span class="ap-modal-title"><i class="bi bi-people-fill me-2" style="color:var(--ap-primary)"></i>Add Group</span>
      <button class="ap-modal-close" onclick="closeModal()">×</button>
    </div>
    <form action="action.php" method="post">
      <div class="ap-modal-body">
        <div class="ap-form-group">
          <label class="ap-form-label">Group Name</label>
          <input type="text" class="ap-form-input" placeholder="e.g. June 2025" name="groupname" required>
        </div>
      </div>
      <div class="ap-modal-footer">
        <button type="button" class="ap-btn ap-btn-ghost ap-btn-sm" onclick="closeModal()">Cancel</button>
        <button type="submit" name="submit" class="ap-btn ap-btn-primary ap-btn-sm">Save Group</button>
      </div>
    </form>
  </div>
</div>

<!-- Group Grid -->
<div class="ap-card">
  <div class="ap-card-header">
    <div class="ap-card-title">
      <div class="ap-card-title-icon"><i class="bi bi-people-fill"></i></div>
      Groups
    </div>
    <span class="text-muted fs-sm">Manage student cohort groups</span>
  </div>

  <div class="ap-box-grid">
    <?php
    $q = "SELECT * FROM grouplist";
    $query = mysqli_query($con, $q);
    while ($row = mysqli_fetch_array($query)):
      if ($row['groupname'] === 'Admin') continue;
    ?>
    <div class="ap-box-item">
      <div class="ap-box-item-icon"><i class="bi bi-people"></i></div>
      <h3><?= htmlspecialchars($row['groupname']) ?></h3>
      <div class="ap-box-actions">
        <a href="grouplist.php?groupname=<?= urlencode($row['groupname']) ?>" class="ap-btn ap-btn-accent ap-btn-sm">
          <i class="bi bi-folder2-open"></i> Open
        </a>
        <button class="ap-btn ap-btn-danger ap-btn-sm" onclick="confirmDelete('<?= $row['id'] ?>')">
          <i class="bi bi-trash"></i> Delete
        </button>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
</div>

<script>
function openModal()  { document.getElementById('addModal').classList.add('open'); }
function closeModal() { document.getElementById('addModal').classList.remove('open'); }
document.getElementById('addModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
function confirmDelete(id) {
  Swal.fire({
    title: 'Delete Group?',
    text: 'This action cannot be undone.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Delete',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#ef4444',
  }).then(r => { if (r.isConfirmed) window.location.href = `action.php?id=${id}&action=delete`; });
}
</script>

<?php require_once '../partials/footer.php'; ?>
