<?php
include('../../config.php');
date_default_timezone_set('Asia/Manila');
if (session_status() === PHP_SESSION_NONE) session_start();

/* ── Type routing ─────────────────────────────────── */
$tableMap = [
    'traditional' => 'mcq',
    'sata'        => 'sata',
    'mpr'         => 'mpr',
    'mmr'         => 'mmr',
    'btq'         => 'btq',
    'dragndrop'   => 'dragndrop',
    'dropdown'    => 'dropdown_questions',
    'highlight'   => 'highlight',
];
$typeLabels = [
    'traditional' => 'Multiple Choice (MCQ)',
    'sata'        => 'SATA — Select All That Apply',
    'mpr'         => 'Matrix / Priority Response',
    'mmr'         => 'Matrix Multiple Response',
    'btq'         => 'Bow-Tie',
    'dragndrop'   => 'Drag & Drop',
    'dropdown'    => 'Dropdown Cloze',
    'highlight'   => 'Highlight',
];

$type = $_GET['type'] ?? '';
if (!array_key_exists($type, $tableMap)) { header('Location: index.php'); exit; }
$table     = $tableMap[$type];
$typeLabel = $typeLabels[$type];

$jsonImportTypes = ['traditional', 'sata', 'mpr', 'mmr', 'btq', 'dragndrop', 'dropdown', 'highlight'];
$hasJsonImport   = in_array($type, $jsonImportTypes, true);

/* ── Flash message ───────────────────────────────── */
$flash = $_SESSION['qflash'] ?? null;
unset($_SESSION['qflash']);

/* ── Question list ───────────────────────────────── */
$questions = [];
$res = mysqli_query($con, "SELECT id, question, dlevel, topic, concept FROM `$table` ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($res)) $questions[] = $row;

/* ── How many imports are still waiting for approval ── */
$pendingCount = 0;
$pstmt = $con->prepare("SELECT COUNT(*) c FROM `pending_questions` WHERE `type` = ? AND status = 'pending'");
if ($pstmt) {
    $pstmt->bind_param('s', $type);
    $pstmt->execute();
    $pendingCount = (int)($pstmt->get_result()->fetch_assoc()['c'] ?? 0);
}

/* ── Page setup ──────────────────────────────────── */
$ap_base    = '../';
$active_nav = 'questions';
$ap_title   = $typeLabel . ' Questions';
$ap_extra_head = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">';
$ap_topbar_actions = '
<a href="index.php" class="ap-btn ap-btn-ghost ap-btn-sm">
  <i class="bi bi-arrow-left"></i> Back
</a>
<button type="button" class="ap-btn ap-btn-primary ap-btn-sm" onclick="openAddModal()">
  <i class="bi bi-plus-lg"></i> Add Question
</button>';
require_once '../partials/sidebar.php';
?>

<!-- Add Question — choose method modal -->
<div class="ap-modal-overlay" id="addChoiceModal">
  <div class="ap-modal" style="max-width:640px;">
    <div class="ap-modal-header">
      <span class="ap-modal-title"><i class="bi bi-plus-circle" style="color:var(--ap-primary);margin-right:6px;"></i>Add <?= htmlspecialchars($typeLabel) ?> Question</span>
      <button class="ap-modal-close" onclick="closeAddModal()">×</button>
    </div>
    <div class="ap-modal-body">
      <p style="font-size:0.82rem;color:var(--ap-text-3);margin:0 0 16px;">Choose how you want to add this question.</p>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;">

        <a href="form.php?type=<?= urlencode($type) ?>" style="text-decoration:none;color:inherit;display:block;border:1.5px solid var(--ap-border);border-radius:12px;padding:18px 16px;transition:all .15s;" onmouseover="this.style.borderColor='var(--ap-primary)'" onmouseout="this.style.borderColor='var(--ap-border)'">
          <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#007CBF,#0090db);display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="bi bi-pencil-square" style="color:#fff;font-size:1.1rem;"></i>
          </div>
          <div style="font-weight:700;font-size:0.86rem;color:var(--ap-text-1);margin-bottom:4px;">Manual Form</div>
          <div style="font-size:0.75rem;color:var(--ap-text-3);line-height:1.45;">Fill in each field yourself using the guided form. Best for a single question.</div>
        </a>

        <?php if ($hasJsonImport): ?>
        <a href="json_import.php?type=<?= urlencode($type) ?>" style="text-decoration:none;color:inherit;display:block;border:1.5px solid var(--ap-border);border-radius:12px;padding:18px 16px;transition:all .15s;" onmouseover="this.style.borderColor='var(--ap-primary)'" onmouseout="this.style.borderColor='var(--ap-border)'">
          <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#7C3AED,#8b5cf6);display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="bi bi-database" style="color:#fff;font-size:1.1rem;"></i>
          </div>
          <div style="font-weight:700;font-size:0.86rem;color:var(--ap-text-1);margin-bottom:4px;">Import from SQL / JSON</div>
          <div style="font-size:0.75rem;color:var(--ap-text-3);line-height:1.45;">Paste or upload a SQL INSERT statement (or JSON) matching the required schema. Validates and previews before saving — supports multiple questions at once.</div>
        </a>
        <?php else: ?>
        <div style="border:1.5px dashed var(--ap-border);border-radius:12px;padding:18px 16px;opacity:0.55;cursor:not-allowed;">
          <div style="width:40px;height:40px;border-radius:10px;background:#e2e8f0;display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="bi bi-database" style="color:#94a3b8;font-size:1.1rem;"></i>
          </div>
          <div style="font-weight:700;font-size:0.86rem;color:var(--ap-text-2);margin-bottom:4px;">Import from SQL / JSON</div>
          <div style="font-size:0.75rem;color:var(--ap-text-3);line-height:1.45;">Not available for Multiple Choice — use the manual form instead.</div>
        </div>
        <?php endif; ?>

        <a href="pending.php?type=<?= urlencode($type) ?>" style="text-decoration:none;color:inherit;display:block;border:1.5px solid <?= $pendingCount ? '#f59e0b' : 'var(--ap-border)' ?>;border-radius:12px;padding:18px 16px;transition:all .15s;position:relative;" onmouseover="this.style.borderColor='var(--ap-primary)'" onmouseout="this.style.borderColor='<?= $pendingCount ? '#f59e0b' : 'var(--ap-border)' ?>'">
          <?php if ($pendingCount): ?>
          <span style="position:absolute;top:12px;right:12px;background:#f59e0b;color:#fff;font-size:0.68rem;font-weight:800;border-radius:100px;padding:2px 9px;"><?= number_format($pendingCount) ?></span>
          <?php endif; ?>
          <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#D97706,#f59e0b);display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="bi bi-hourglass-split" style="color:#fff;font-size:1.1rem;"></i>
          </div>
          <div style="font-weight:700;font-size:0.86rem;color:var(--ap-text-1);margin-bottom:4px;">Waiting for Approval</div>
          <div style="font-size:0.75rem;color:var(--ap-text-3);line-height:1.45;">
            <?php if ($pendingCount): ?>
              <?= number_format($pendingCount) ?> imported question(s) are waiting. Review, edit and approve them into the question bank — come back anytime.
            <?php else: ?>
              Imported questions land here first. Review, edit and approve them before they go live — come back anytime.
            <?php endif; ?>
          </div>
        </a>

      </div>
    </div>
  </div>
</div>
<script>
function openAddModal()  { document.getElementById('addChoiceModal').classList.add('open'); }
function closeAddModal() { document.getElementById('addChoiceModal').classList.remove('open'); }
document.getElementById('addChoiceModal').addEventListener('click', function(e) {
  if (e.target === this) closeAddModal();
});
</script>

<?php if ($flash): ?>
<div style="margin:10px 20px 0;padding:10px 16px;background:<?= $flash['ok'] ? '#dcfce7' : '#fee2e2' ?>;color:<?= $flash['ok'] ? '#16a34a' : '#dc2626' ?>;border-radius:8px;font-size:0.83rem;display:flex;align-items:center;gap:8px;">
  <i class="bi bi-<?= $flash['ok'] ? 'check-circle' : 'exclamation-circle' ?>"></i>
  <?= htmlspecialchars($flash['msg']) ?>
</div>
<?php endif; ?>

<div class="ap-card">
  <div class="ap-card-header">
    <div class="ap-card-title">
      <div class="ap-card-title-icon"><i class="bi bi-patch-question-fill"></i></div>
      <?= htmlspecialchars($typeLabel) ?>
    </div>
    <span style="font-size:0.8rem;color:var(--ap-text-3);"><?= count($questions) ?> questions</span>
  </div>

  <div style="padding:16px 20px;overflow-x:auto;">
    <table id="qTable" class="table table-bordered" style="width:100%;">
      <thead>
        <tr>
          <th style="width:50px;">#</th>
          <th>Question</th>
          <th style="width:100px;">Difficulty</th>
          <th style="width:170px;">Concept</th>
          <th style="width:120px;white-space:nowrap;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($questions as $q): ?>
        <tr>
          <td><?= $q['id'] ?></td>
          <td style="max-width:480px;">
            <span style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;font-size:0.82rem;">
              <?= htmlspecialchars(strip_tags($q['question'])) ?>
            </span>
          </td>
          <td>
            <?php
            $cls = ['Easy'=>'ap-badge-success','Medium'=>'ap-badge-warning','Hard'=>'ap-badge-danger'][$q['dlevel']??''] ?? 'ap-badge-gray';
            ?>
            <span class="ap-badge <?= $cls ?>"><?= htmlspecialchars($q['dlevel'] ?: '—') ?></span>
          </td>
          <td style="font-size:0.78rem;"><?= htmlspecialchars($q['concept'] ?: '—') ?></td>
          <td style="white-space:nowrap;">
            <a href="form.php?type=<?= urlencode($type) ?>&id=<?= $q['id'] ?>"
               class="ap-btn ap-btn-accent ap-btn-xs">
              <i class="bi bi-pencil"></i> Edit
            </a>
            <button onclick="confirmDelete(<?= $q['id'] ?>,'<?= htmlspecialchars(addslashes(mb_substr(strip_tags($q['question']),0,60))) ?>')"
                    class="ap-btn ap-btn-danger ap-btn-xs">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function confirmDelete(id, preview) {
  Swal.fire({
    title: 'Delete Question?',
    text: preview + '…',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Delete',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#ef4444',
  }).then(r => {
    if (r.isConfirmed) {
      const f = document.createElement('form');
      f.method = 'post'; f.action = 'qaction.php';
      f.innerHTML = `<input name="action" value="delete">
                     <input name="type"   value="<?= $type ?>">
                     <input name="id"     value="${id}">`;
      document.body.appendChild(f);
      f.submit();
    }
  });
}
</script>

<?php
$ap_extra_js = '
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(\'#qTable\').DataTable({
  order: [[0,"desc"]],
  columnDefs: [{"targets":0,"visible":false}],
  dom: \'frtip\',
  language: { paginate: { previous: \'← Prev\', next: \'Next →\' } }
});
</script>';
require_once '../partials/footer.php';
?>
