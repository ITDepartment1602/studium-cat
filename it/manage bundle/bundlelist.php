<?php
$ap_base    = '../';
$active_nav = 'bundle';
$ap_title   = 'Bundle Contents';
$ap_extra_head = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">';
$ap_topbar_actions = '
  <button class="ap-btn ap-btn-primary ap-btn-sm" onclick="openModal()">
    <i class="bi bi-plus-lg"></i> Add Topics
  </button>';
require_once '../partials/sidebar.php';
include('../../config.php');

$bundle_name = $_GET['bundle_name'] ?? '';

// Topic options for the multi-select.
$topics = mysqli_query($con, "SELECT title FROM topics");

// Current contents of this bundle.
$stmt = mysqli_prepare($con, "SELECT * FROM bundlelist WHERE bundle_name = ?");
mysqli_stmt_bind_param($stmt, 's', $bundle_name);
mysqli_stmt_execute($stmt);
$list = mysqli_stmt_get_result($stmt);
?>

<!-- Add Topics Modal -->
<div class="ap-modal-overlay" id="addModal">
  <div class="ap-modal">
    <div class="ap-modal-header">
      <span class="ap-modal-title"><i class="bi bi-collection-fill me-2" style="color:var(--ap-primary)"></i>Select Topics</span>
      <button class="ap-modal-close" onclick="closeModal()">×</button>
    </div>
    <form action="action.php" method="post" enctype="multipart/form-data">
      <div class="ap-modal-body">
        <input type="hidden" name="bundle_name" value="<?= htmlspecialchars($bundle_name) ?>">
        <div class="ap-form-group">
          <label class="ap-form-label">Topics</label>
          <select name="bundlelist_name[]" class="multiple-select" style="width:100%;" multiple>
            <?php while ($t = mysqli_fetch_assoc($topics)): ?>
            <option value="<?= htmlspecialchars($t['title']) ?>"><?= htmlspecialchars($t['title']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
      </div>
      <div class="ap-modal-footer">
        <button type="button" class="ap-btn ap-btn-ghost ap-btn-sm" onclick="closeModal()">Cancel</button>
        <button type="submit" name="createpackage" class="ap-btn ap-btn-primary ap-btn-sm">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Bundle Contents -->
<div class="ap-card">
  <div class="ap-card-header">
    <div class="ap-card-title">
      <div class="ap-card-title-icon"><i class="bi bi-collection-fill"></i></div>
      <?= htmlspecialchars(str_replace('Packege', 'Package', $bundle_name) ?: 'Bundle Contents') ?>
    </div>
    <a href="index.php" class="ap-btn ap-btn-ghost ap-btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
  </div>

  <div class="ap-box-grid">
    <?php while ($row = mysqli_fetch_assoc($list)): ?>
    <div class="ap-box-item">
      <div class="ap-box-item-icon"><i class="bi bi-journal-text"></i></div>
      <h3><?= htmlspecialchars($row['bundlelist_name']) ?></h3>
      <div class="ap-box-actions">
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
    title: 'Delete Topic?',
    text: 'Remove this topic from the bundle?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Delete',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#ef4444',
  }).then(r => { if (r.isConfirmed) window.location.href = `action.php?id=${id}&scope=item&bundle_name=<?= urlencode($bundle_name) ?>`; });
}
</script>

<?php
$ap_extra_js = '
<script src="../.././table js/jquery-3.5.1.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>$(".multiple-select").select2({ dropdownParent: $("#addModal .ap-modal") });</script>';
require_once '../partials/footer.php';
?>
