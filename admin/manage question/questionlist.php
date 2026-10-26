<?php
$ap_base    = '../';
$active_nav = 'questions';
$ap_title   = 'Question List';
$ap_extra_head = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
$ap_topbar_actions = '
  <button class="ap-btn ap-btn-primary ap-btn-sm" onclick="openModal()">
    <i class="bi bi-plus-lg"></i> Add Question
  </button>';
require_once '../partials/sidebar.php';
include('../../config.php');

$concepts = ['Adult Health','Child Health','Critical Care','Fundamentals',
             'Leadership & Management','Maternal & Newborn Health','Mental Health','Pharmacology'];

$topics = mysqli_query($con, "SELECT title FROM topics");
$data   = mysqli_query($con, "SELECT * FROM `question`");
?>

<!-- Add Question Modal -->
<div class="ap-modal-overlay" id="addModal">
  <div class="ap-modal" style="max-width:560px;">
    <div class="ap-modal-header">
      <span class="ap-modal-title"><i class="bi bi-patch-question-fill me-2" style="color:var(--ap-primary)"></i>Add Question</span>
      <button class="ap-modal-close" onclick="closeModal()">×</button>
    </div>
    <form action="action.php" method="post" enctype="multipart/form-data">
      <div class="ap-modal-body" style="max-height:65vh;overflow-y:auto;">
        <div class="ap-form-group">
          <label class="ap-form-label">Topic</label>
          <select name="topic[]" class="ap-form-select" required>
            <option disabled selected>Select topic..</option>
            <?php while ($t = mysqli_fetch_assoc($topics)): ?>
            <option value="<?= htmlspecialchars($t['title']) ?>"><?= htmlspecialchars($t['title']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
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
          <label class="ap-form-label">Question Number</label>
          <input type="number" class="ap-form-input" placeholder="Input Question Number" name="qnumber" required>
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">Question</label>
          <input type="text" class="ap-form-input" placeholder="Input Question" name="question" required>
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">Choice A</label>
          <input type="text" class="ap-form-input" placeholder="Input choice A" name="choiceA" required>
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">Choice B</label>
          <input type="text" class="ap-form-input" placeholder="Input choice B" name="choiceB" required>
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">Choice C</label>
          <input type="text" class="ap-form-input" placeholder="Input choice C" name="choiceC" required>
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">Choice D</label>
          <input type="text" class="ap-form-input" placeholder="Input choice D" name="choiceD" required>
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">Correct Answer</label>
          <input type="text" class="ap-form-input" placeholder="Input correct answer" name="correctans" required>
        </div>
        <input type="hidden" name="wrong" value="1">
        <div class="ap-form-group">
          <label class="ap-form-label">Rationale</label>
          <textarea name="rationale" class="ap-form-textarea" required></textarea>
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">NARC Additional Notes</label>
          <textarea name="narcan" class="ap-form-textarea" required></textarea>
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">D Level</label>
          <input type="text" class="ap-form-input" placeholder="Input D Level" name="dlevel" required>
        </div>
        <div class="ap-form-group">
          <label class="ap-form-label">CNC</label>
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
      <div class="ap-card-title-icon"><i class="bi bi-patch-question-fill"></i></div>
      Question List
    </div>
  </div>

  <div style="padding:16px 20px; overflow-x:auto;">
    <table class="table data-table" style="width:100%;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Concept</th>
          <th>Question</th>
          <th>Choice A</th>
          <th>Choice B</th>
          <th>Choice C</th>
          <th>Choice D</th>
          <th>D Level</th>
          <th>Rationale</th>
          <th>Correct Answer</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($rows = mysqli_fetch_assoc($data)): ?>
        <tr>
          <td><?= htmlspecialchars($rows['id']) ?></td>
          <td><?= htmlspecialchars($rows['topics1']) ?></td>
          <td><?= htmlspecialchars($rows['question']) ?></td>
          <td><?= htmlspecialchars($rows['choiceA']) ?></td>
          <td><?= htmlspecialchars($rows['choiceB']) ?></td>
          <td><?= htmlspecialchars($rows['choiceC']) ?></td>
          <td><?= htmlspecialchars($rows['choiceD']) ?></td>
          <td><?= htmlspecialchars($rows['dlevel']) ?></td>
          <td><?= htmlspecialchars($rows['rationale']) ?></td>
          <td><span class="ap-badge ap-badge-success"><?= htmlspecialchars($rows['correctans']) ?></span></td>
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
function confirmDelete(id) {
  Swal.fire({
    title: 'Delete Question?',
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
