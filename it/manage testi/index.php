<?php
$ap_base    = '../';
$active_nav = 'testi';
$ap_title   = 'Testimonials';
$ap_extra_head = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="../../table css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
$ap_topbar_actions = '
  <button class="ap-btn ap-btn-primary ap-btn-sm" onclick="openModal()">
    <i class="bi bi-plus-lg"></i> Add Testimonial
  </button>';
require_once '../partials/sidebar.php';
include('../../config.php');
?>

<!-- Add Testimonial Modal -->
<div class="ap-modal-overlay" id="addModal">
  <div class="ap-modal">
    <div class="ap-modal-header">
      <span class="ap-modal-title"><i class="bi bi-star-fill me-2" style="color:var(--ap-warning)"></i>Add Testimonial</span>
      <button class="ap-modal-close" onclick="closeModal()">×</button>
    </div>
    <form action="action.php" method="post" enctype="multipart/form-data">
      <div class="ap-modal-body">
        <div class="ap-form-group">
          <label class="ap-form-label">Message</label>
          <textarea class="ap-form-textarea" name="message" placeholder="Testimonial message..." required></textarea>
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">Name</label>
          <input type="text" class="ap-form-input" name="name" placeholder="e.g. Juan dela Cruz, RN, USRN">
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">Credential</label>
          <input type="text" class="ap-form-input" name="credinial" placeholder="e.g. Passed NCLEX 2024">
        </div>
      </div>
      <div class="ap-modal-footer">
        <button type="button" class="ap-btn ap-btn-ghost ap-btn-sm" onclick="closeModal()">Cancel</button>
        <button type="submit" name="submit" class="ap-btn ap-btn-primary ap-btn-sm">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Table -->
<div class="ap-card">
  <div class="ap-card-header">
    <div class="ap-card-title">
      <div class="ap-card-title-icon"><i class="bi bi-star-fill"></i></div>
      Testimonials
    </div>
  </div>

  <div style="padding:16px 20px; overflow-x:auto;">
    <table class="table data-table" style="width:100%;">
      <thead>
        <tr>
          <th>#</th>
          <th>Message</th>
          <th>Name</th>
          <th>Credential</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $data = mysqli_query($con, "SELECT * FROM testimonial");
        while ($rows = mysqli_fetch_array($data)):
        ?>
        <tr>
          <td><?= $rows['id'] ?></td>
          <td><?= htmlspecialchars(substr($rows['message'], 0, 60)) . (strlen($rows['message']) > 60 ? '...' : '') ?></td>
          <td style="font-weight:500;"><?= htmlspecialchars($rows['name'] ?? '') ?></td>
          <td><?= htmlspecialchars($rows['credinial'] ?? '') ?></td>
          <td>
            <a href="action.php?id=<?= $rows['id'] ?>" class="ap-btn ap-btn-danger ap-btn-xs"
               onclick="return confirm('Delete this testimonial?')">
              <i class="bi bi-trash"></i> Delete
            </a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function openModal()  { document.getElementById('addModal').classList.add('open'); }
function closeModal() { document.getElementById('addModal').classList.remove('open'); }
document.getElementById('addModal').addEventListener('click', e => { if (e.target === document.getElementById('addModal')) closeModal(); });
</script>

<?php
$ap_extra_js = '
<script src="../.././table js/jquery-3.5.1.js"></script>
<script src="../.././table js/jquery.dataTables.min.js"></script>
<script src="../.././table js/dataTables.bootstrap5.min.js"></script>
<script src="../.././table js/script.js"></script>';
require_once '../partials/footer.php';
?>
