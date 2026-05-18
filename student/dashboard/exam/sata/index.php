<?php
require_once '../../../../config.php';
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $q = mysqli_query($con, "SELECT * FROM sata WHERE id = {$id} LIMIT 1");
} else {
    $q = mysqli_query($con, "SELECT * FROM sata ORDER BY RAND() LIMIT 1");
}
if (!$q) die('<div style="font-family:Inter,sans-serif;padding:24px;">Database error: ' . htmlspecialchars(mysqli_error($con)) . '</div>');

$data = mysqli_fetch_assoc($q);
if (!$data) die('<div style="font-family:Inter,sans-serif;padding:24px;">No SATA question found.</div>');

$questionText   = $data['question'] ?? '';
$items          = json_decode($data['items'],   true) ?? [];
$correctAnswers = json_decode($data['correct'], true) ?? [];
$rationale      = $data['rationale'] ?? '';
$topic          = $data['topic']     ?? 'General';
$system         = $data['system']    ?? 'N/A';
$cnc            = $data['cnc']       ?? 'N/A';
$dlevel         = $data['dlevel']    ?? 'N/A';

$tabs_data = json_decode(($data['tabs'] ?? '') ?: '[]', true) ?: [];
$hasTabs   = !empty($tabs_data);

$items          = array_map(fn($i) => is_array($i) ? ($i['text'] ?? $i) : $i, $items);
$correctAnswers = array_map('strval', (array)$correctAnswers);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SATA Question</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
:root {
  --primary: #0a1628; --accent: #3b82f6; --success: #10b981; --danger: #ef4444;
  --surface: #ffffff; --border: #e2e8f0; --text: #0f172a; --text-muted: #64748b; --bg-soft: #f8fafc;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', sans-serif; background: transparent; color: var(--text); }

.two-panel { display: flex; min-height: 100vh; overflow: hidden; }
.left-panel { width: 40%; min-width: 260px; background: #fff; border-right: 2px solid var(--border); display: flex; flex-direction: column; flex-shrink: 0; overflow: hidden; }
.panel-title { padding: 14px 20px; background: #f1f5f9; font-weight: 800; font-size: 11px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 1px; border-bottom: 1px solid var(--border); }
.tabs-row { display: flex; padding: 8px 12px 0; gap: 4px; border-bottom: 1px solid var(--border); overflow-x: auto; overflow-y: hidden; flex-shrink: 0; scrollbar-width: none; }
.tabs-row::-webkit-scrollbar { height: 3px; }
.tabs-row::-webkit-scrollbar-thumb { background: transparent; border-radius: 10px; }
.tabs-row:hover::-webkit-scrollbar-thumb { background: #cbd5e1; }
.tab-btn { padding: 9px 14px; font-size: 13px; font-weight: 600; cursor: pointer; border-radius: 8px 8px 0 0; color: var(--text-muted); white-space: nowrap; }
.tab-btn.active { background: #f8fafc; color: var(--accent); border: 1px solid var(--border); border-bottom-color: #f8fafc; margin-bottom: -1px; }
.tab-content-area { flex: 1; overflow-y: auto; padding: 16px; }
.clinical-record { background: #fdfdfd; border: 1px solid #f1f5f9; padding: 10px 14px; border-radius: 8px; margin-bottom: 8px; font-size: 14px; line-height: 1.5; }
.right-panel { flex: 1; overflow-y: auto; padding: 24px; min-width: 0; }
@media (max-width: 900px) {
  .two-panel { flex-direction: column; height: auto; overflow: visible; }
  .left-panel { width: 100%; min-width: 0; border-right: none; border-bottom: 2px solid var(--border); max-height: 35vh; overflow-y: auto; }
  .right-panel { width: 100% !important; overflow: visible; }
}

.card { max-width: 950px; margin: 0 auto; background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 32px; box-shadow: 0 4px 16px rgba(15,23,42,.05); }
.instruction-badge { display: inline-block; background: #fef3c7; color: #92400e; font-size: 11px; font-weight: 800; letter-spacing: .6px; text-transform: uppercase; border-radius: 999px; padding: 6px 14px; margin-bottom: 16px; }
.question-header { font-size: 18px; line-height: 1.7; color: var(--primary); margin-bottom: 28px; font-weight: 600; }
.selection-count { font-size: 12px; color: var(--text-muted); margin-bottom: 16px; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
.items-container { display: flex; flex-direction: column; gap: 12px; margin-bottom: 28px; }
.item-checkbox { display: flex; align-items: flex-start; gap: 14px; padding: 16px 18px; border: 2px solid var(--border); border-radius: 12px; background: white; cursor: pointer; transition: all .3s ease; }
.item-checkbox:hover { border-color: var(--accent); background: #f8fafc; }
.item-checkbox input[type="checkbox"] { margin-top: 4px; cursor: pointer; width: 20px; height: 20px; flex-shrink: 0; accent-color: var(--accent); }
.item-checkbox input[type="checkbox"]:disabled { opacity: .6; cursor: not-allowed; }
.item-text { flex: 1; font-size: 15px; font-weight: 500; color: var(--text); line-height: 1.6; }
.item-checkbox input[type="checkbox"]:checked ~ .item-text { color: var(--accent); font-weight: 600; }
.item-checkbox.selected { border-color: var(--accent); background: #eff6ff; }
.item-checkbox.locked { opacity: .7; pointer-events: none; }
.button-group { position: sticky; bottom: 0; background: #fff; padding: 16px 0 20px; margin-top: 24px; border-top: 1px solid var(--border); display: flex; justify-content: center; z-index: 10; }
.btn { padding: 14px 40px; border-radius: 12px; font-weight: 800; font-size: 15px; cursor: pointer; border: none; transition: all .2s ease; min-width: 220px; display: flex; align-items: center; justify-content: center; gap: 10px; }
.btn-primary { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; box-shadow: 0 4px 14px rgba(59,130,246,.4); }
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59,130,246,.5); }
.btn-primary:disabled { opacity: .45; cursor: not-allowed; transform: none !important; box-shadow: none; }
@media (max-width: 768px) { .card { padding: 20px; } .question-header { font-size: 16px; } }
@media (max-width: 480px) { .card { padding: 16px; } .item-text { font-size: 14px; } }
</style>
</head>
<body>
<div class="two-panel">
<?php if ($hasTabs): ?>
<div class="left-panel">
  <div class="panel-title">Clinical Reference</div>
  <div class="tabs-row">
    <?php foreach ($tabs_data as $i => $tab): ?>
    <div class="tab-btn <?= $i === 0 ? 'active' : '' ?>" data-tab="stab-<?= $i ?>"><?= htmlspecialchars($tab['title']) ?></div>
    <?php endforeach; ?>
  </div>
  <div class="tab-content-area">
    <?php foreach ($tabs_data as $i => $tab): ?>
    <div id="stab-<?= $i ?>" class="tab-pane" <?= $i > 0 ? 'style="display:none;"' : '' ?>>
      <?php foreach ((array)($tab['content'] ?? []) as $item): ?>
      <div class="clinical-record"><?= htmlspecialchars($item) ?></div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
<div class="right-panel" <?= !$hasTabs ? 'style="width:100%;"' : '' ?>>
<div class="card">
  <div class="instruction-badge"><i class="fas fa-asterisk"></i> SELECT ALL THAT APPLY</div>
  <div class="question-header"><?= nl2br(htmlspecialchars($questionText)) ?></div>
  <div class="selection-count" id="selectionCount">Select correct answers</div>
  <form id="sataForm">
    <div class="items-container">
      <?php foreach ($items as $idx => $item): ?>
      <label class="item-checkbox" data-index="<?= $idx ?>" data-value="<?= htmlspecialchars($item) ?>">
        <input type="checkbox" name="answers" value="<?= htmlspecialchars($item) ?>">
        <span class="item-text"><?= nl2br(htmlspecialchars($item)) ?></span>
      </label>
      <?php endforeach; ?>
    </div>
  </form>
  <div class="button-group">
    <button type="button" class="btn btn-primary" id="submitBtn">
      <i class="fas fa-check-circle"></i> Submit Answers
    </button>
  </div>
</div>
</div>
</div>

<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
    document.getElementById(this.dataset.tab).style.display = '';
  });
});

const correctAnswers = <?= json_encode($correctAnswers) ?>;
const rationale      = <?= json_encode($rationale) ?>;
const totalCorrect   = correctAnswers.length;
let locked = false;
let userAnswers = [];
let initialAnswers = [];
let hasInteracted = false;

// Timeout from parent (Pressure Mode)
window.addEventListener('message', e => {
  if (e.data?.type === 'timeout') {
    setReadOnlyState();
    const ov = document.createElement('div');
    ov.style.cssText = 'position:fixed;inset:0;background:rgba(239,68,68,.08);display:flex;align-items:flex-start;justify-content:center;padding-top:20px;z-index:9999;pointer-events:none;';
    ov.innerHTML = '<div style="background:#ef4444;color:#fff;padding:8px 22px;border-radius:100px;font-weight:800;font-size:13px;box-shadow:0 4px 16px rgba(239,68,68,.4);">⏰ Time Expired</div>';
    document.body.appendChild(ov);
  }
});

if (window.parent !== window) window.parent.postMessage({ type: 'ready' }, '*');

document.querySelectorAll('input[name="answers"]').forEach(checkbox => {
  checkbox.addEventListener('change', function() {
    if (locked) return;
    if (!hasInteracted) {
      hasInteracted = true;
      initialAnswers = Array.from(document.querySelectorAll('input[name="answers"]:checked')).map(c => c.value);
    }
    userAnswers = Array.from(document.querySelectorAll('input[name="answers"]:checked')).map(c => c.value);
    document.querySelectorAll('.item-checkbox').forEach(item => {
      item.classList.toggle('selected', item.querySelector('input').checked);
    });
    updateSelectionCount();
  });
});

function updateSelectionCount() {
  const count = document.querySelectorAll('input[name="answers"]:checked').length;
  document.getElementById('selectionCount').textContent =
    count === 0 ? 'Select correct answers' : `${count} of ${totalCorrect} selected`;
}

document.getElementById('submitBtn').addEventListener('click', function() {
  if (locked) return;
  userAnswers = Array.from(document.querySelectorAll('input[name="answers"]:checked')).map(c => c.value);
  if (userAnswers.length === 0) {
    Swal.fire({ icon: 'warning', title: 'Please Select Answers', text: 'Select at least one answer before submitting.', confirmButtonColor: '#3b82f6' });
    return;
  }
  this.disabled = true;
  this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting…';
  setReadOnlyState();
  sendAnswerToParent();
});

function setReadOnlyState() {
  document.querySelectorAll('input[name="answers"]').forEach(i => i.disabled = true);
  document.getElementById('submitBtn').style.display = 'none';
  locked = true;
}

function sendAnswerToParent() {
  let correctCount = 0, incorrectCount = 0;
  userAnswers.forEach(a => correctAnswers.includes(a) ? correctCount++ : incorrectCount++);
  const missedCount = correctAnswers.filter(c => !userAnswers.includes(c)).length;
  const isCorrect   = correctCount === totalCorrect && incorrectCount === 0;
  const score       = isCorrect ? 1 : parseFloat((correctCount / totalCorrect).toFixed(2));

  let changesData = null;
  if (initialAnswers.length > 0) {
    const added   = userAnswers.filter(a => !initialAnswers.includes(a));
    const removed = initialAnswers.filter(a => !userAnswers.includes(a));
    if (added.length > 0 || removed.length > 0) changesData = { added, removed, modified_count: added.length + removed.length, changed: true };
  }

  if (window.parent !== window) {
    window.parent.postMessage({
      type: 'answered',
      answer:         userAnswers,
      initial_answer: initialAnswers.length > 0 ? initialAnswers : null,
      correctAnswer:  correctAnswers,
      correct:        isCorrect,
      score,
      max_points:     totalCorrect,
      earned_points:  correctCount,
      changes:        changesData,
      rationale,
      topic:   <?= json_encode($topic) ?>,
      system:  <?= json_encode($system) ?>,
      cnc:     <?= json_encode($cnc) ?>,
      dlevel:  <?= json_encode($dlevel) ?>,
      question_id:   <?= json_encode($data['id'] ?? 0) ?>,
      question_type: 'sata'
    }, '*');
  }
}
</script>
</body>
</html>
