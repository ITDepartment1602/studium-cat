<?php
require_once '../../../../config.php';
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id === 0) die("No question ID provided!");

$q = mysqli_query($con, "SELECT * FROM dragndrop WHERE id='$id'");
$data = mysqli_fetch_assoc($q);
if (!$data) die("Question not found!");

$topic     = $data['topic'];
$question  = $data['question'];
$items     = json_decode($data['items'], true);
shuffle($items);
$rationale = $data['rationale'];
$system    = $data['system'] ?? 'N/A';
$cnc       = $data['cnc']    ?? 'N/A';
$dlevel    = $data['dlevel'] ?? 'N/A';
$correct   = json_decode($data['correct'], true);

$tabs_data = json_decode(($data['tabs'] ?? '') ?: '[]', true) ?: [];
$hasTabs   = !empty($tabs_data);

$BLANK_MARKER = '/_{5,}/';
$isCloze = (bool) preg_match($BLANK_MARKER, $question);

function buildClozeHtml(string $question, int $blankCount): string {
    $idx = 0;
    return preg_replace_callback('/_{5,}/', function() use (&$idx) {
        $html = '<span class="blank cloze-blank" data-idx="' . $idx . '">Drop Here</span>';
        $idx++;
        return $html;
    }, htmlspecialchars($question));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Drag & Drop Question</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://bernardo-castilho.github.io/DragDropTouch/DragDropTouch.js"></script>
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
}

html, body { height: 100%; margin: 0; padding: 0; overflow: hidden; }
body { font-family: 'Inter', sans-serif; color: var(--text); background: white; overflow: hidden; }

.two-panel { display: flex; height: 100%; overflow: hidden; }
.left-panel { width: 40%; min-width: 260px; background: #fff; border-right: 2px solid var(--border); display: flex; flex-direction: column; flex-shrink: 0; overflow: hidden; }
.panel-title { padding: 14px 20px; background: #f1f5f9; font-weight: 800; font-size: 11px; text-transform: uppercase; color: #64748b; letter-spacing: 1px; border-bottom: 1px solid var(--border); }
.tabs-row { display: flex; padding: 8px 12px 0; gap: 4px; border-bottom: 1px solid var(--border); overflow-x: auto; overflow-y: hidden; flex-shrink: 0; scrollbar-width: none; }
.tabs-row::-webkit-scrollbar { height: 3px; }
.tabs-row::-webkit-scrollbar-thumb { background: transparent; border-radius: 10px; }
.tabs-row:hover::-webkit-scrollbar-thumb { background: #cbd5e1; }
.tab-btn { padding: 9px 14px; font-size: 13px; font-weight: 600; cursor: pointer; border-radius: 8px 8px 0 0; color: #64748b; white-space: nowrap; }
.tab-btn.active { background: #f8fafc; color: var(--accent); border: 1px solid var(--border); border-bottom-color: #f8fafc; margin-bottom: -1px; }
.tab-content-area { flex: 1; overflow-y: auto; padding: 16px; }
.clinical-record { background: #fdfdfd; border: 1px solid #f1f5f9; padding: 10px 14px; border-radius: 8px; margin-bottom: 8px; font-size: 14px; line-height: 1.5; }
.right-panel { flex: 1; overflow-y: auto; padding: clamp(8px, 3vh, 32px) 12px; min-width: 0; display: flex; justify-content: center; align-items: flex-start; }
@media (max-width: 900px) {
  body { overflow: auto; }
  .two-panel { flex-direction: column; height: auto; overflow: visible; }
  .left-panel { width: 100%; min-width: 0; border-right: none; border-bottom: 2px solid var(--border); max-height: 35vh; overflow-y: auto; }
  .right-panel { width: 100% !important; overflow: visible; display: block; }
}

.card { background: var(--surface); border-radius: 12px; padding: 16px; width: 100%; max-width: 900px; box-shadow: 0 4px 20px -5px rgba(0,0,0,0.05); border: 1px solid var(--border); margin-bottom: 24px; }

@media (max-width: 600px) {
  .card { padding: 12px; border-radius: 8px; }
  .question-container { font-size: 14px; line-height: 1.6; margin-bottom: 16px; }
  .blank { min-width: 80px; height: 30px; font-size: 12px; margin: 2px; }
  .instruction { font-size: 9px; margin-bottom: 8px; }
  .choices-bank { padding: 12px; gap: 6px; }
  .choice-item { padding: 5px 10px; font-size: 12px; }
  .btn { padding: 8px 16px; font-size: 12px; width: 100%; }
}

.instruction { font-size: 10px; font-weight: 800; text-transform: uppercase; color: var(--accent); letter-spacing: 1px; margin-bottom: 12px; display: block; }
.question-container { font-size: 18px; font-weight: 600; line-height: 2; margin-bottom: 32px; color: var(--primary); }

.blank { display: inline-flex; min-width: 120px; height: 36px; background: #f1f5f9; border: 2px dashed #cbd5e1; border-radius: 8px; margin: 0 4px; vertical-align: middle; align-items: center; justify-content: center; font-size: 15px; font-weight: 700; color: var(--accent); transition: all 0.2s; cursor: pointer; }
.blank.active { border-color: var(--accent); background: #eff6ff; }
.blank.filled { border-style: solid; border-color: #3b82f6; background: white; box-shadow: 0 2px 6px rgba(59,130,246,0.1); }

.choices-bank { display: flex; flex-wrap: wrap; gap: 12px; padding: 24px; background: #f8fafc; border-radius: 12px; border: 1px solid var(--border); }
.choice-item { background: white; border: 1px solid var(--border); padding: 10px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: grab; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: all 0.2s; }
.choice-item:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

.ordered-slots { margin-bottom: 24px; }
.slots-label { font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 10px; }
.slot-row { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
.slot-num { width: 28px; height: 28px; background: var(--accent); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; flex-shrink: 0; }
.slot-row .blank { flex: 1; min-width: 0; display: flex; }

.cloze-blank { display: inline-flex; min-width: 160px; height: 38px; background: #f1f5f9; border: 2px dashed #cbd5e1; border-radius: 8px; margin: 0 6px; vertical-align: middle; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; color: var(--accent); transition: all 0.2s; cursor: pointer; white-space: nowrap; padding: 0 10px; }
.cloze-blank.active { border-color: var(--accent); background: #eff6ff; }
.cloze-blank.filled { border-style: solid; border-color: #3b82f6; background: white; color: var(--text); box-shadow: 0 2px 6px rgba(59,130,246,0.1); }

.actions { position: sticky; bottom: 0; background: #fff; padding: 16px 0 20px; margin-top: 24px; border-top: 1px solid var(--border); display: flex; justify-content: center; z-index: 10; }
.btn { padding: 14px 40px; border-radius: 12px; font-weight: 800; font-size: 15px; cursor: pointer; border: none; transition: all .2s ease; min-width: 220px; display: flex; align-items: center; justify-content: center; gap: 10px; }
.btn-primary { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; box-shadow: 0 4px 14px rgba(59,130,246,.4); }
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59,130,246,.5); }
.btn-primary:disabled { opacity: .45; cursor: not-allowed; transform: none !important; box-shadow: none; }
</style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="two-panel">
<?php if ($hasTabs): ?>
<div class="left-panel">
  <div class="panel-title">Clinical Reference</div>
  <div class="tabs-row">
    <?php foreach ($tabs_data as $i => $tab): ?>
    <div class="tab-btn <?= $i === 0 ? 'active' : '' ?>" data-tab="dtab-<?= $i ?>"><?= htmlspecialchars($tab['title']) ?></div>
    <?php endforeach; ?>
  </div>
  <div class="tab-content-area">
    <?php foreach ($tabs_data as $i => $tab): ?>
    <div id="dtab-<?= $i ?>" class="tab-pane" <?= $i > 0 ? 'style="display:none;"' : '' ?>>
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
    <div style="margin-bottom: 24px;">
        <div style="font-size: 13px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 8px;"><?php echo htmlspecialchars($topic); ?></div>
    </div>

    <?php if ($isCloze): ?>
    <div class="question-container" id="questionBox" style="line-height:2.2;">
        <?php echo buildClozeHtml($question, count($correct)); ?>
    </div>
    <?php else: ?>
    <div class="question-container" id="questionBox">
        <?php echo nl2br(htmlspecialchars($question)); ?>
    </div>
    <div class="ordered-slots" id="orderedSlots">
        <div class="slots-label">Arrange in correct order:</div>
        <?php for ($i = 0; $i < count($correct); $i++): ?>
            <div class="slot-row">
                <div class="slot-num"><?= $i + 1 ?></div>
                <div class="blank" data-idx="<?= $i ?>">Drop Here</div>
            </div>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <div class="choices-bank" id="choicesBank">
        <?php foreach ($items as $item): ?>
            <div class="choice-item" draggable="true"><?php echo htmlspecialchars($item); ?></div>
        <?php endforeach; ?>
    </div>

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
    const isCloze   = <?= $isCloze ? 'true' : 'false' ?>;
    let dragged = null;
    let locked  = false;
    let initialAnswers = [];
    let hasInteracted  = false;

    const $blanks = () => isCloze ? $('.cloze-blank') : $('.blank');

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

    $(document).on('dragstart', '.choice-item', function(e){ dragged = this; $(this).css('opacity', '0.5'); });
    $(document).on('dragend',   '.choice-item', function(){  $(this).css('opacity', '1'); });

    $(document).on('dragover',  '.blank, .cloze-blank', function(e){ e.preventDefault(); $(this).addClass('active'); });
    $(document).on('dragleave', '.blank, .cloze-blank', function(){ $(this).removeClass('active'); });
    $(document).on('drop',      '.blank, .cloze-blank', function(e){
        e.preventDefault();
        if(locked) return;
        $(this).removeClass('active');
        if(!dragged) return;
        if(!hasInteracted) {
            hasInteracted = true;
            $blanks().each(function(i){ initialAnswers[i] = $(this).text().trim(); });
        }
        if($(this).hasClass('filled')) {
            $('#choicesBank').append(`<div class="choice-item" draggable="true">${$(this).text()}</div>`);
        }
        $(this).text($(dragged).text().trim()).addClass('filled');
        $(dragged).remove();
        dragged = null;
    });

    $(document).on('click', '.blank, .cloze-blank', function(){
        if(locked) return;
        if(!$(this).hasClass('filled')) return;
        if(!hasInteracted) {
            hasInteracted = true;
            $blanks().each(function(i){ initialAnswers[i] = $(this).text().trim(); });
        }
        $('#choicesBank').append(`<div class="choice-item" draggable="true">${$(this).text()}</div>`);
        $(this).text('Drop Here').removeClass('filled');
    });

    function setReadOnlyState() {
        $blanks().css('cursor', 'default');
        $('.choice-item').attr('draggable', false).css('cursor', 'default');
        $('#submitBtn').prop('disabled', true).hide();
        locked = true;
    }

    $('#submitBtn').click(function(){
        if(locked) return;

        let userAnswers = [];
        let incomplete  = false;

        $blanks().each(function(){
            let txt = $(this).text().trim();
            if(!$(this).hasClass('filled')) incomplete = true;
            userAnswers.push(txt);
        });

        if(incomplete) {
            Swal.fire({ icon:'warning', title:'Incomplete', text:'Please fill all blanks.' });
            return;
        }

        if(initialAnswers.length === 0) initialAnswers = [...userAnswers];

        let earned = 0;
        userAnswers.forEach((v, i) => {
            if(v.toString().trim().toLowerCase() === correct[i].toString().trim().toLowerCase()) earned++;
        });

        const total      = correct.length;
        const normalized = parseFloat((earned / total).toFixed(2));

        let changesData = null;
        if(JSON.stringify(initialAnswers) !== JSON.stringify(userAnswers)){
            changesData = {
                modified_count: userAnswers.filter((ans, i) => ans !== initialAnswers[i]).length,
                changed: true
            };
        }

        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting…');
        setReadOnlyState();

        window.parent.postMessage({
            type: 'answered',
            question_id:   <?= json_encode($id) ?>,
            answer:        userAnswers,
            initial_answer: initialAnswers.length > 0 ? initialAnswers : null,
            correctAnswer:  correct,
            correct:        earned === total,
            score:          normalized,
            max_points:     total,
            earned_points:  earned,
            changes:        changesData,
            rationale:      rationale,
            topic:   <?= json_encode($topic) ?>,
            system:  <?= json_encode($system) ?>,
            cnc:     <?= json_encode($cnc) ?>,
            dlevel:  <?= json_encode($dlevel) ?>,
            question_type: 'dragndrop'
        }, '*');
    });

    // Auto-scroll for drag & drop on mobile
    let isDragging = false;
    let autoScrollInterval = null;

    $(document).on('dragstart', '.choice-item', function(){ isDragging = true; });
    $(document).on('dragend',   '.choice-item', function(){
        isDragging = false;
        if (autoScrollInterval) clearInterval(autoScrollInterval);
    });

    $(document).on('dragover', function(e){
        if (!isDragging) return;
        const mouseY = e.clientY;
        const scrollThreshold = 100;
        if (mouseY < scrollThreshold) window.scrollBy(0, -10);
        else if (mouseY > window.innerHeight - scrollThreshold) window.scrollBy(0, 10);
    });

    document.addEventListener('dragover', function(e){
        if (!isDragging) return;
        let clientY = e.clientY;
        if (clientY === 0 && e.touches && e.touches[0]) clientY = e.touches[0].clientY;
        if (clientY < 100) window.scrollBy(0, -10);
        else if (clientY > window.innerHeight - 100) window.scrollBy(0, 10);
    }, true);

    $(document).on('touchend', function(){
        isDragging = false;
        if (autoScrollInterval) clearInterval(autoScrollInterval);
    });
});
</script>
</body>
</html>
