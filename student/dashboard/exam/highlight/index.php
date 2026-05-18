<?php
require_once '../../../../config.php';
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id === 0) die("No ID provided.");

$stmt = mysqli_prepare($con, "SELECT * FROM highlight WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$data) die("Question not found.");

$topic               = $data['topic']    ?? '';
$question            = $data['question'] ?? '';
$passage             = $data['passage']  ?? '';
$options             = array_filter(array_map('trim', explode(',', $data['options']    ?? '')));
$highlightable_flags = array_map('intval', array_filter(array_map('trim', explode(',', $data['highlightable'] ?? ''))));
$correct_phrases     = array_filter(array_map('trim', explode(',', $data['correct_words'] ?? '')));
$rationale           = $data['rationale']  ?? '';
$systemTxt           = $data['system']     ?? '';
$cnc                 = $data['cnc']        ?? '';
$dlevel              = $data['dlevel']     ?? '';
$maxHighlights       = (int)($data['maxHighlights'] ?? 4);

// Build passage HTML with clickable spans
$option_map = [];
foreach ($options as $i => $opt) if ($opt !== '') $option_map[$i] = $opt;
uasort($option_map, function ($a, $b) { return mb_strlen($b) <=> mb_strlen($a); });

$passage_work = $passage;
$placeholders = [];
foreach ($option_map as $idx => $phrase) {
    if (trim($phrase) === '') continue;
    $token = "~~OPTION_{$idx}_" . md5($phrase) . "~~";
    $words = preg_split('/\s+/', trim($phrase));
    $escapedWords = array_map(function($w){ return preg_quote($w, '/'); }, $words);
    $fuzzyRegex = implode('\s+', $escapedWords);
    $passage_work = preg_replace_callback("/($fuzzyRegex)/iu", function ($m) use ($idx, $token, &$placeholders) {
        $placeholders[] = ['token' => $token, 'index' => $idx, 'text' => $m[0]];
        return $token;
    }, $passage_work);
}

$token_to_span = [];
foreach ($placeholders as $ph) {
    $idx     = $ph['index'];
    $display = htmlspecialchars($ph['text']);
    $is_highlightable = (isset($highlightable_flags[$idx]) && intval($highlightable_flags[$idx]) === 1);
    $cls = 'inline-token' . ($is_highlightable ? ' hint-highlightable' : '');
    $token_to_span[$ph['token']] = "<span class=\"{$cls}\" data-index=\"{$idx}\">{$display}</span>";
}
$passage_html = strtr($passage_work, $token_to_span);

$tabs_data = json_decode(($data['tabs'] ?? '') ?: '[]', true) ?: [];
$hasTabs   = !empty($tabs_data);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Highlight Question</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
:root {
  --primary: #0a1628; --accent: #3b82f6; --highlight: #fef08a;
  --success: #10b981; --danger: #ef4444;
  --bg: #f8fafc; --surface: #ffffff; --border: #e2e8f0; --text: #0f172a;
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
.right-panel { flex: 1; overflow-y: auto; padding: clamp(16px, 5vh, 48px) 16px; min-width: 0; display: flex; justify-content: center; align-items: flex-start; }
@media (max-width: 900px) {
  body { overflow: auto; }
  .two-panel { flex-direction: column; height: auto; overflow: visible; }
  .left-panel { width: 100%; min-width: 0; border-right: none; border-bottom: 2px solid var(--border); max-height: 35vh; overflow-y: auto; }
  .right-panel { width: 100% !important; overflow: visible; display: block; }
}

.card { background: var(--surface); border-radius: 20px; padding: clamp(20px, 4vw, 40px); width: 100%; max-width: 900px; box-shadow: 0 10px 30px -5px rgba(0,0,0,0.05); border: 1px solid var(--border); margin-bottom: 24px; }

@media (max-width: 640px) {
  .card { border-radius: 0; padding: 16px; }
  .question-text { font-size: 16px; line-height: 1.5; }
  .passage-box { padding: 16px; font-size: 14px; line-height: 1.6; }
  .btn { padding: 10px 24px; font-size: 13px; }
}

.instruction { font-size: 10px; font-weight: 800; text-transform: uppercase; color: var(--accent); letter-spacing: 1.5px; margin-bottom: 12px; display: block; }
.question-text { font-size: 18px; font-weight: 700; line-height: 1.6; margin-bottom: 24px; }
.passage-box { background: #fdfdfd; border: 1px solid var(--border); padding: 24px; border-radius: 12px; line-height: 1.8; font-size: 16px; color: #334155; white-space: pre-wrap; margin-bottom: 24px; }

.inline-token { padding: 2px 2px; border-radius: 4px; cursor: pointer; transition: all 0.2s; border-bottom: 1.5px solid transparent; }
.hint-highlightable:hover { background: #f1f5f9; border-bottom-color: var(--accent); }
.inline-token.highlighted { background: var(--highlight); border-bottom-color: #f59e0b; font-weight: 600; }
.inline-token.locked { cursor: default; }

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
    <div class="tab-btn <?= $i === 0 ? 'active' : '' ?>" data-tab="htab-<?= $i ?>"><?= htmlspecialchars($tab['title']) ?></div>
    <?php endforeach; ?>
  </div>
  <div class="tab-content-area">
    <?php foreach ($tabs_data as $i => $tab): ?>
    <div id="htab-<?= $i ?>" class="tab-pane" <?= $i > 0 ? 'style="display:none;"' : '' ?>>
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
    <span class="instruction">Highlight Question</span>
    <div class="question-text"><?= nl2br(htmlspecialchars($question)) ?></div>

    <div class="passage-box" id="passage"><?php echo $passage_html; ?></div>

    <div class="actions">
        <button id="submitBtn" class="btn btn-primary"><i class="fas fa-check-circle"></i> Submit Selections</button>
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
    const correctPhrases = <?php echo json_encode(array_values($correct_phrases)); ?>;
    const maxAllowed     = <?php echo $maxHighlights; ?>;
    const rationale      = <?php echo json_encode($rationale); ?>;
    let selectedText     = [];
    let locked           = false;
    let initialSelectedText = [];

    function normalize(str){
        return str.toLowerCase().replace(/[^a-z0-9\s]/g, '').replace(/\s+/g, ' ').trim();
    }
    const normCorrect = correctPhrases.map(normalize);

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

    $('.inline-token').click(function(){
        if(locked) return;

        const txt = $(this).text().trim();
        const nm  = normalize(txt);

        if($(this).hasClass('highlighted')) {
            $(this).removeClass('highlighted');
            selectedText = selectedText.filter(s => normalize(s) !== nm);
        } else {
            if(selectedText.length >= maxAllowed) {
                Swal.fire({ icon:'warning', title:'Limit Reached', text:'Maximum ' + maxAllowed + ' highlights allowed.' });
                return;
            }
            $(this).addClass('highlighted');
            selectedText.push(txt);
        }
    });

    function setReadOnlyState() {
        $('.inline-token').addClass('locked').off('click');
        $('#submitBtn').prop('disabled', true).hide();
        locked = true;
    }

    $('#submitBtn').click(function(){
        if(locked) return;

        if(selectedText.length === 0) {
            Swal.fire({ icon:'error', title:'Incomplete', text:'Please highlight at least one phrase.' });
            return;
        }

        if(initialSelectedText.length === 0) initialSelectedText = [...selectedText];

        let match = 0, wrong = 0;
        const selNorm = selectedText.map(normalize);
        selNorm.forEach(s => {
            if(normCorrect.includes(s)) match++;
            else wrong++;
        });

        const earned     = Math.max(0, match - wrong);
        const total      = normCorrect.length || 1;
        const normalized = parseFloat((earned / total).toFixed(2));

        let changesData = null;
        const initialNorm = initialSelectedText.map(normalize).sort().join('|');
        const currentNorm = selectedText.map(normalize).sort().join('|');
        if(initialNorm !== currentNorm){
            changesData = { modified_count: 1, changed: true };
        }

        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting…');
        setReadOnlyState();

        window.parent.postMessage({
            type:          'answered',
            answer:        selectedText,
            initial_answer: initialSelectedText.length > 0 ? initialSelectedText : null,
            correctAnswer:  correctPhrases,
            correct:        earned === total,
            score:          normalized,
            max_points:     total,
            earned_points:  earned,
            changes:        changesData,
            rationale:      rationale,
            topic:   <?= json_encode($topic) ?>,
            system:  <?= json_encode($systemTxt) ?>,
            cnc:     <?= json_encode($cnc) ?>,
            dlevel:  <?= json_encode($dlevel) ?>,
            question_id:   <?= json_encode($id) ?>,
            question_type: 'highlight'
        }, '*');
    });
});
</script>
</body>
</html>
