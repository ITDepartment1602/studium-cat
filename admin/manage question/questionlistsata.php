<?php
$ap_base    = '../';
$active_nav = 'questions';
$ap_title   = 'SATA Question List';
$ap_extra_head = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
$ap_topbar_actions = '
  <button class="ap-btn ap-btn-primary ap-btn-sm" onclick="openModal()">
    <i class="bi bi-plus-lg"></i> Add SATA Question
  </button>';
require_once '../partials/sidebar.php';
include('../../config.php');

$concepts = ['Adult Health','Child Health','Critical Care','Fundamentals',
             'Leadership & Management','Maternal & Newborn Health','Mental Health','Pharmacology'];

$data = mysqli_query($con, "SELECT * FROM `question` WHERE `type` IS NOT NULL AND `type` = 'SATA'");
?>

<!-- Add SATA Question Modal -->
<div class="ap-modal-overlay" id="addModal">
  <div class="ap-modal" style="max-width:560px;">
    <div class="ap-modal-header">
      <span class="ap-modal-title"><i class="bi bi-list-check me-2" style="color:var(--ap-primary)"></i>Add SATA Question</span>
      <button class="ap-modal-close" onclick="closeModal()">×</button>
    </div>
    <form action="actionsata.php" method="post" enctype="multipart/form-data">
      <div class="ap-modal-body" style="max-height:65vh;overflow-y:auto;">
        <input type="hidden" name="topic[]" value="NARC Intermediate and Advance QBanks">
        <input type="hidden" name="qnumber" value="1">
        <div class="ap-form-group">
          <label class="ap-form-label">Concept</label>
          <select name="concept" class="ap-form-select" required>
            <option disabled selected>Select concept..</option>
            <?php foreach ($concepts as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">Question</label>
          <input type="text" class="ap-form-input" placeholder="Input Question" name="question" required>
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">Choices</label>
          <div id="options-container">
            <div class="option-input" style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">
              <input type="text" class="ap-form-input" style="margin:0;" placeholder="Input Choice" name="options[]" required>
              <button type="button" class="ap-btn ap-btn-danger ap-btn-xs" onclick="removeOption(this)">×</button>
            </div>
          </div>
          <button type="button" class="ap-btn ap-btn-ghost ap-btn-sm" onclick="addOption()">
            <i class="bi bi-plus-lg"></i> Add Choice
          </button>
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">Correct Answer (e.g. [0, 2, 3])</label>
          <div style="font-size:0.72rem;color:var(--ap-muted);margin-bottom:6px;">A=0 · B=1 · C=2 · D=3 · E=4 · F=5</div>
          <input type="text" class="ap-form-input" placeholder="Input indices of correct answers" value="[]" name="correctans" required>
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">Rationale</label>
          <textarea name="rationale" class="ap-form-textarea" required></textarea>
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">NARC Additional Notes</label>
          <textarea name="narcan" class="ap-form-textarea" required></textarea>
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">D Level (e.g. Hard level)</label>
          <input type="text" class="ap-form-input" placeholder="Input D Level" name="dlevel" required>
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">Client Needs</label>
          <input type="text" class="ap-form-input" placeholder="Input CNC" name="cnc" required>
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">System</label>
          <input type="text" class="ap-form-input" placeholder="Input System" name="system" required>
        </div>
      </div>
      <div class="ap-modal-footer">
        <button type="button" class="ap-btn ap-btn-ghost ap-btn-sm" onclick="closeModal()">Cancel</button>
        <button type="submit" name="submit" class="ap-btn ap-btn-primary ap-btn-sm">Submit</button>
      </div>
    </form>
  </div>
</div>

<div class="ap-card">
  <div class="ap-card-header">
    <div class="ap-card-title">
      <div class="ap-card-title-icon"><i class="bi bi-list-check"></i></div>
      SATA Question List
    </div>
  </div>

  <div style="padding:16px 20px; overflow-x:auto;">
    <table class="table data-table" style="width:100%;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Concept</th>
          <th>Question</th>
          <th>Options</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($rows = mysqli_fetch_assoc($data)):
          $options        = json_decode($rows['options']) ?: [];
          $correctAnswers = json_decode($rows['correctans']) ?: [];
        ?>
        <tr>
          <td><?= htmlspecialchars($rows['id']) ?></td>
          <td><?= htmlspecialchars($rows['topics1']) ?></td>
          <td><?= htmlspecialchars($rows['question']) ?></td>
          <td>
            <?php foreach ($options as $index => $option): ?>
            <div>
              <input disabled type="checkbox" <?= in_array($index, $correctAnswers) ? 'checked' : '' ?>>
              <?= htmlspecialchars($option) ?>
            </div>
            <?php endforeach; ?>
          </td>
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
function openModal()  { document.getElementById('addModal').classList.add('open'); }
function closeModal() { document.getElementById('addModal').classList.remove('open'); }
document.getElementById('addModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
function addOption() {
  const container = document.getElementById('options-container');
  const div = document.createElement('div');
  div.classList.add('option-input');
  div.style.cssText = 'display:flex;align-items:center;gap:6px;margin-bottom:6px;';
  div.innerHTML = `
    <input type="text" class="ap-form-input" style="margin:0;" placeholder="Input Choice" name="options[]" required>
    <button type="button" class="ap-btn ap-btn-danger ap-btn-xs" onclick="removeOption(this)">×</button>`;
  container.appendChild(div);
}
function removeOption(button) {
  button.parentNode.remove();
}
function confirmDelete(id) {
  Swal.fire({
    title: 'Delete Question?',
    text: 'This action cannot be undone.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Delete',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#ef4444',
  }).then(r => { if (r.isConfirmed) window.location.href = `actionsata.php?id=${id}&action=delete`; });
}
</script>

<?php
$ap_extra_js = '
<script src="../.././table js/jquery-3.5.1.js"></script>
<script src="../.././table js/jquery.dataTables.min.js"></script>
<script src="../.././table js/script.js"></script>';
require_once '../partials/footer.php';
?>
