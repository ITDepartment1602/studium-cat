<?php
$ap_base    = '../';
$active_nav = 'topics';
$ap_title   = 'Manage Topics';
$ap_extra_head = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
$ap_topbar_actions = '
  <button class="ap-btn ap-btn-primary ap-btn-sm" onclick="openModal()">
    <i class="bi bi-plus-lg"></i> Add Topic
  </button>';
require_once '../partials/sidebar.php';
include("../../config.php");
?>

<!-- Add Topic Modal -->
<div class="ap-modal-overlay" id="addModal">
  <div class="ap-modal">
    <div class="ap-modal-header">
      <span class="ap-modal-title"><i class="bi bi-book-fill me-2" style="color:var(--ap-primary)"></i>Add Topic</span>
      <button class="ap-modal-close" onclick="closeModal()">×</button>
    </div>
    <form action="action.php" method="post" enctype="multipart/form-data">
      <div class="ap-modal-body">
        <div class="ap-form-group">
          <label class="ap-form-label">Title</label>
          <input type="text" class="ap-form-input" placeholder="Topic title" name="title" required>
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">Description</label>
          <input type="text" class="ap-form-input" placeholder="Short description" name="description">
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">Cover Image</label>
          <input type="file" class="ap-form-input" name="image" accept="image/*">
        </div>
      </div>
      <div class="ap-modal-footer">
        <button type="button" class="ap-btn ap-btn-ghost ap-btn-sm" onclick="closeModal()">Cancel</button>
        <button type="submit" name="submit" class="ap-btn ap-btn-primary ap-btn-sm">Save Topic</button>
      </div>
    </form>
  </div>
</div>

<!-- Topics Grid -->
<div class="ap-card">
  <div class="ap-card-header">
    <div class="ap-card-title">
      <div class="ap-card-title-icon"><i class="bi bi-book-fill"></i></div>
      Topics
    </div>
  </div>

  <div class="ap-topic-grid">
    <?php
    $q = "SELECT * FROM topics";
    $query = mysqli_query($con, $q);
    while ($row = mysqli_fetch_array($query)):
      $isSoon = ($row['name'] === 'NARC NGN Qbanks');
    ?>
    <div class="ap-topic-item">
      <img src="<?= htmlspecialchars($row['image']) ?>" alt="" class="ap-topic-img">
      <div class="ap-topic-body">
        <div class="ap-topic-name"><?= htmlspecialchars($row['name'] ?? $row['title']) ?></div>
        <?php if (!empty($row['description'])): ?>
        <div class="ap-topic-desc"><?= htmlspecialchars($row['description']) ?></div>
        <?php endif; ?>
        <div class="ap-topic-actions">
          <?php if ($isSoon): ?>
            <span class="ap-btn ap-btn-ghost ap-btn-sm" style="opacity:0.45;cursor:not-allowed;">
              <i class="bi bi-clock"></i> Coming Soon
            </span>
          <?php else: ?>
            <a href="title.php?title=<?= urlencode($row['title']) ?>" class="ap-btn ap-btn-accent ap-btn-sm">
              <i class="bi bi-folder2-open"></i> Open
            </a>
            <button class="ap-btn ap-btn-danger ap-btn-sm" onclick="confirmDelete('<?= htmlspecialchars(addslashes($row['name'] ?? $row['title'])) ?>','<?= $row['id'] ?>')">
              <i class="bi bi-trash"></i>
            </button>
          <?php endif; ?>
        </div>
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
function confirmDelete(name, id) {
  Swal.fire({
    title: 'Delete Topic?',
    text: `Are you sure you want to delete "${name}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Delete',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#ef4444',
  }).then(r => { if (r.isConfirmed) window.location.href = `action.php?id=${id}&action=delete`; });
}
</script>

<?php require_once '../partials/footer.php'; ?>
