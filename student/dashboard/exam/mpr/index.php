<?php
require_once '../../../../config.php';
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$question_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($question_id > 0){
    $q = mysqli_query($con, "SELECT * FROM mpr WHERE id = $question_id LIMIT 1");
} else {
    $q = mysqli_query($con, "SELECT * FROM mpr ORDER BY RAND() LIMIT 1");
}

$data = mysqli_fetch_assoc($q);
if (!$data) {
    die('<div style="font-family: Arial; padding: 20px;">No MPR question found.</div>');
}

$items    = explode("\n", $data['items']);
$correct  = explode(",", $data['correct']);
$rationale = $data['rationale'] ?? '';
$question  = $data['question']  ?? '';

$required = count($items);
if (preg_match('/Select\s+(\d+)/i', $question, $match)) {
    $required = (int)$match[1];
} elseif (stripos($question, 'Select all that apply') !== false) {
    $required = count($items);
}

$tabs_data = json_decode(($data['tabs'] ?? '') ?: '[]', true) ?: [];
$hasTabs   = !empty($tabs_data);

$topic  = $data['topic']  ?? 'General';
$system = $data['system'] ?? 'N/A';
$cnc    = $data['cnc']    ?? 'N/A';
$dlevel = $data['dlevel'] ?? 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MPR Question</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
:root {
  --primary: #0a1628;
  --accent: #3b82f6;
  --success: #10b981;
  --danger: #ef4444;
  --bg: #f8fafc;
  --surface: #ffffff;
  --border: #e2e8f0;
  --text: #0f172a;
  --text-muted: #64748b;
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
.right-panel { flex: 1; overflow-y: auto; padding: 20px; min-width: 0; }
@media (max-width: 900px) {
  .two-panel { flex-direction: column; height: auto; overflow: visible; }
  .left-panel { width: 100%; min-width: 0; border-right: none; border-bottom: 2px solid var(--border); max-height: 35vh; overflow-y: auto; }
  .right-panel { width: 100% !important; overflow: visible; }
}

.card { background: var(--surface); border-radius: 12px; padding: 32px; max-width: 900px; margin: 0 auto; }
.question-text { font-size: 18px; font-weight: 600; line-height: 1.6; margin-bottom: 24px; color: var(--primary); }
.instruction-badge { display: inline-block; background: #eff6ff; color: #1e40af; font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 100px; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.5px; }

.options-list { display: flex; flex-direction: column; gap: 12px; }
.option-item { position: relative; display: flex; align-items: center; padding: 16px 20px; border: 2px solid var(--border); border-radius: 12px; cursor: pointer; transition: all 0.2s ease; }
.option-item:hover { border-color: #cbd5e1; background: #fcfcfd; }
.option-item.selected { border-color: var(--accent); background: #f0f7ff; }
.option-item.locked { opacity: .7; pointer-events: none; }
.option-item input { position: absolute; opacity: 0; cursor: pointer; }
.custom-checkbox { width: 20px; height: 20px; border: 2px solid #cbd5e1; border-radius: 6px; margin-right: 16px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; background: white; }
.option-item.selected .custom-checkbox { background: var(--accent); border-color: var(--accent); }
.custom-checkbox::after { content: "✓"; color: white; font-size: 14px; font-weight: bold; display: none; }
.option-item.selected .custom-checkbox::after { display: block; }
.option-text { font-size: 15px; font-weight: 500; color: var(--text); line-height: 1.4; }

.actions { position: sticky; bottom: 0; background: #fff; padding: 16px 0 20px; margin-top: 24px; border-top: 1px solid var(--border); display: flex; justify-content: center; z-index: 10; }
.btn { padding: 14px 40px; border-radius: 12px; font-weight: 800; font-size: 15px; cursor: pointer; border: none; transition: all .2s ease; min-width: 220px; display: flex; align-items: center; justify-content: center; gap: 10px; }
.btn-primary { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; box-shadow: 0 4px 14px rgba(59,130,246,.4); }
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59,130,246,.5); }
.btn-primary:disabled { opacity: .45; cursor: not-allowed; transform: none !important; box-shadow: none; }
</style>
</head>
<body>
<div class="two-panel">
<?php if ($hasTabs): ?>
<div class="left-panel">
  <div class="panel-title">Clinical Reference</div>
  <div class="tabs-row">
    <?php foreach ($tabs_data as $i => $tab): ?>
    <div class="tab-btn <?= $i === 0 ? 'active' : '' ?>" data-tab="mtab-<?= $i ?>"><?= htmlspecialchars($tab['title']) ?></div>
    <?php endforeach; ?>
  </div>
  <div class="tab-content-area">
    <?php foreach ($tabs_data as $i => $tab): ?>
    <div id="mtab-<?= $i ?>" class="tab-pane" <?= $i > 0 ? 'style="display:none;"' : '' ?>>
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
    <div class="instruction-badge">
        <i class="fas fa-list-check"></i>
        <?php if ($required < count($items)): ?>
            Multiple Response: Select <?= $required ?>
        <?php else: ?>
            Multiple Response: Select All That Apply
        <?php endif; ?>
    </div>

    <div class="question-text">
        <?= nl2br(htmlspecialchars($question)) ?>
    </div>

    <form id="mprForm" class="options-list">
        <?php foreach($items as $item):
            $letter = trim(substr($item, 0, 1));
        ?>
        <label class="option-item" data-value="<?= $letter ?>">
            <input type="checkbox" name="answers[]" value="<?= $letter ?>">
            <div class="custom-checkbox"></div>
            <div class="option-text"><?= htmlspecialchars($item) ?></div>
        </label>
        <?php endforeach; ?>
    </form>

    <div class="actions">
        <button id="submitBtn" class="btn btn-primary"><i class="fas fa-check-circle"></i> Submit Answer</button>
    </div>
</div>
</div><!-- /.right-panel -->
</div><!-- /.two-panel -->

<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
    document.getElementById(this.dataset.tab).style.display = '';
  });
});

$(document).ready(function(){
    const correct   = <?= json_encode($correct) ?>;
    const rationale = <?= json_encode($rationale) ?>;
    const required  = <?= json_encode($required) ?>;
    let locked = false;
    let initialAnswers = [];
    let hasInteracted  = false;

    // Timeout from parent (Pressure Mode)
    window.addEventListener('message', function(e) {
        if (e.data && e.data.type === 'timeout') {
            setReadOnlyState();
            const ov = document.createElement('div');
            ov.style.cssText = 'position:fixed;inset:0;background:rgba(239,68,68,.08);display:flex;align-items:flex-start;justify-content:center;padding-top:20px;z-index:9999;pointer-events:none;';
            ov.innerHTML = '<div style="background:#ef4444;color:#fff;padding:8px 22px;border-radius:100px;font-weight:800;font-size:13px;box-shadow:0 4px 16px rgba(239,68,68,.4);">⏰ Time Expired</div>';
            document.body.appendChild(ov);
        }
    });

    if (window.parent !== window) window.parent.postMessage({ type: 'ready' }, '*');

    $(document).on('change', 'input[name="answers[]"]', function(){
        if(locked) return;

        if(!hasInteracted && initialAnswers.length === 0) {
            hasInteracted = true;
            $('input[name="answers[]"]').each(function(){
                if($(this).is(':checked')) initialAnswers.push($(this).val());
            });
        } else {
            hasInteracted = true;
        }

        let parent = $(this).closest('.option-item');
        if(this.checked) {
            parent.addClass('selected');
        } else {
            parent.removeClass('selected');
        }

        let checkedCount = $('input[name="answers[]"]:checked').length;
        if(checkedCount > required){
            this.checked = false;
            parent.removeClass('selected');
            Swal.fire({ icon:'warning', title:'Limit Reached', text:'You can only select ' + required + ' items.' });
        }
    });

    function setReadOnlyState() {
        $('input[name="answers[]"]').prop('disabled', true);
        $('.option-item').addClass('locked');
        $('#submitBtn').prop('disabled', true).hide();
        locked = true;
    }

    $('#submitBtn').click(function(){
        if(locked) return;

        let selected = [];
        $('input[name="answers[]"]:checked').each(function(){ selected.push($(this).val()); });

        if(selected.length === 0){
            Swal.fire({ icon:'error', title:'Incomplete', text:'Please select at least one answer.' });
            return;
        }

        if(initialAnswers.length === 0) initialAnswers = [...selected];

        let earned = 0;
        let maxPoints = correct.length;
        const normCorrect = correct.map(s => s.toString().trim().toLowerCase());

        $('input[name="answers[]"]').each(function(){
            let val = $(this).val().trim().toLowerCase();
            let checked = $(this).is(':checked');
            if(normCorrect.includes(val) && checked) earned++;
            else if(!normCorrect.includes(val) && checked) earned--;
        });

        earned = Math.max(0, earned);
        let normalizedScore = maxPoints > 0 ? parseFloat((earned / maxPoints).toFixed(2)) : 0;

        let changesData = null;
        if(initialAnswers.length > 0 && initialAnswers.length !== selected.length) {
            const added   = selected.filter(a => !initialAnswers.includes(a));
            const removed = initialAnswers.filter(a => !selected.includes(a));
            if(added.length > 0 || removed.length > 0) {
                changesData = { added, removed, modified_count: added.length + removed.length, changed: true };
            }
        }

        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting…');
        setReadOnlyState();

        window.parent.postMessage({
            type:'answered',
            answer: selected,
            initial_answer: initialAnswers.length > 0 ? initialAnswers : null,
            correctAnswer: correct,
            correct: earned === maxPoints && selected.length === maxPoints,
            score: normalizedScore,
            max_points: maxPoints,
            earned_points: earned,
            changes: changesData,
            rationale: rationale,
            topic:  <?= json_encode($data['topic']  ?? 'General') ?>,
            system: <?= json_encode($data['system'] ?? 'N/A') ?>,
            cnc:    <?= json_encode($data['cnc']    ?? 'N/A') ?>,
            dlevel: <?= json_encode($data['dlevel'] ?? 'N/A') ?>,
            question_id:   <?= json_encode($data['id']) ?>,
            question_type: 'mpr'
        }, '*');
    });
});
</script>
</body>
</html>
