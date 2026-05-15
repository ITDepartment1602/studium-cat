<?php
include '../../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}
$user_id = intval($_SESSION['user_id']);

// Handle add note
$addSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note_title'], $_POST['note_content'])) {
    $noteTitle   = mysqli_real_escape_string($con, trim($_POST['note_title']));
    $noteContent = mysqli_real_escape_string($con, trim($_POST['note_content']));
    $dateTime    = date('Y-m-d H:i:s');
    if ($noteTitle !== '') {
        $ins = mysqli_query($con, "INSERT INTO tbl_notes (login_id, note_title, note, date_time) VALUES ('$user_id', '$noteTitle', '$noteContent', '$dateTime')");
        $addSuccess = ($ins !== false);
    }
}

// Handle delete note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delId = intval($_POST['delete_id']);
    mysqli_query($con, "DELETE FROM tbl_notes WHERE tbl_notes_id = '$delId' AND login_id = '$user_id'");
    header('Location: mynotes.php');
    exit;
}

// Fetch notes
$result = mysqli_query($con, "SELECT * FROM tbl_notes WHERE login_id = '$user_id' ORDER BY date_time DESC");
$notes  = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notes[] = $row;
}

$pageTitle = 'My Notes — Studium';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include '_layout/head.php'; ?>
</head>
<body>

<?php include '_layout/sidebar.php'; ?>

<main class="s-main">

  <div class="s-page-header-row">
    <div>
      <h1>My Notes</h1>
      <p>Keep your study notes organized in one place.</p>
    </div>
    <button class="s-btn s-btn-teal" onclick="openAddModal()">
      <i class="bi bi-plus-lg me-1"></i>Add Note
    </button>
  </div>

  <?php if ($addSuccess): ?>
  <div class="s-alert-banner mb-4" style="border-color:#0D9488; background:linear-gradient(135deg,#f0fdf4,#dcfce7);">
    <i class="bi bi-check-circle-fill" style="color:#0D9488; font-size:1.2rem;"></i>
    <div>Note added successfully!</div>
  </div>
  <?php endif; ?>

  <!-- Notes Grid -->
  <?php if (empty($notes)): ?>
  <div class="s-empty">
    <i class="bi bi-journal-x"></i>
    <p>No notes yet. Click the <strong>Add Note</strong> button to get started.</p>
  </div>
  <?php else: ?>
  <div class="row g-3 mb-5">
    <?php foreach ($notes as $note):
      $fmtDate = date('M d, Y · h:i A', strtotime($note['date_time']));
      $safeTitle   = htmlspecialchars($note['note_title'], ENT_QUOTES, 'UTF-8');
      $safeContent = htmlspecialchars($note['note'],       ENT_QUOTES, 'UTF-8');
    ?>
    <div class="col-xl-3 col-lg-4 col-md-6 col-12">
      <div class="s-note-card">
        <div class="s-note-title"><?= $safeTitle ?></div>
        <div class="s-note-preview"><?= nl2br($safeContent) ?></div>
        <div class="d-flex justify-content-between align-items-center mt-3 pt-2" style="border-top:1px solid var(--s-border);">
          <span style="font-size:0.68rem; color:var(--s-muted);"><?= $fmtDate ?></span>
          <div class="d-flex gap-2">
            <button class="s-btn s-btn-outline" style="padding:4px 10px; font-size:0.72rem;"
              onclick="viewNote(<?= htmlspecialchars(json_encode($note['note_title']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($note['note']), ENT_QUOTES) ?>)">
              <i class="bi bi-eye"></i>
            </button>
            <form method="POST" style="display:inline;" onsubmit="return confirmDelete(event, this)">
              <input type="hidden" name="delete_id" value="<?= intval($note['tbl_notes_id']) ?>">
              <button type="submit" class="s-btn s-btn-danger" style="padding:4px 10px; font-size:0.72rem;">
                <i class="bi bi-trash3"></i>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</main>

<!-- Footer -->
<div class="s-footer">
  <span>© Studium 2025, All Right Reserved.</span>
</div>

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
    <div class="modal-content" style="border-radius:18px; border:none; box-shadow:0 20px 60px rgba(0,0,0,.15);">
      <div class="modal-header" style="background:linear-gradient(135deg,#1B4965,#0D9488); border-radius:18px 18px 0 0; padding:18px 24px;">
        <h5 class="modal-title" style="color:#fff; font-weight:700; margin:0;"><i class="bi bi-journal-plus me-2"></i>Add New Note</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body" style="padding:24px;">
          <div class="mb-3">
            <label class="s-label">Title</label>
            <input type="text" name="note_title" class="s-input" placeholder="Note title..." required maxlength="255">
          </div>
          <div class="mb-0">
            <label class="s-label">Content</label>
            <textarea name="note_content" class="s-input" rows="6" placeholder="Write your note here..." style="resize:vertical;"></textarea>
          </div>
        </div>
        <div class="modal-footer" style="padding:14px 24px; border-top:1px solid var(--s-border); background:#fafafa; border-radius:0 0 18px 18px;">
          <button type="button" class="s-btn s-btn-outline" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="s-btn s-btn-teal"><i class="bi bi-check2 me-1"></i>Save Note</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../ty/js/bootstrap.bundle.min.js"></script>
<script>
function openAddModal() {
  new bootstrap.Modal(document.getElementById('addNoteModal')).show();
}

function viewNote(title, content) {
  Swal.fire({
    title: title,
    html: `<p style="text-align:left; white-space:pre-wrap; font-size:0.9rem; color:#1e293b;">${content.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')}</p>`,
    showConfirmButton: false,
    showCloseButton: true,
    width: '560px',
    customClass: { popup: 'text-start' }
  });
}

function confirmDelete(e, form) {
  e.preventDefault();
  Swal.fire({
    title: 'Delete Note?',
    text: 'This note will be permanently deleted.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Delete',
    cancelButtonText: 'Cancel'
  }).then(result => {
    if (result.isConfirmed) form.submit();
  });
  return false;
}
</script>
</body>
</html>
