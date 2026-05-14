<?php
// dragndrop/index.php
require_once '../../../../config.php';
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id === 0) die("No question ID provided!");

$q = mysqli_query($con, "SELECT * FROM dragndrop WHERE id='$id'");
$data = mysqli_fetch_assoc($q);
if (!$data) die("Question not found!");

$topic = $data['topic'];
$question = $data['question'];
$items = json_decode($data['items'], true);
shuffle($items);
$rationale = $data['rationale'];
$system = $data['system'] ?? 'N/A';
$cnc = $data['cnc'] ?? 'N/A';
$dlevel = $data['dlevel'] ?? 'N/A';
$concept = $data['concept'] ?? 'General';
$narcan = $data['narcan'] ?? 'N/A';
$q_uid = 'dragndrop_' . $id;
$peer_q = mysqli_query($con, "SELECT AVG(isCorrect) * 100 as avg_score FROM exam_results WHERE question_uid = '$q_uid'");
$peer_data = mysqli_fetch_assoc($peer_q);
$avg_peer_score = ($peer_data && $peer_data['avg_score']) ? round($peer_data['avg_score'], 1) . '%' : 'N/A';
$correct = json_decode($data['correct'], true);
$furtherinfo = $data['furtherinfo'] ?? '';
$image = $data['image'] ?? '';

// Dynamic clinical reference tabs from `tabs` DB field (spec §1.2)
$tabs_data = json_decode(($data['tabs'] ?? '') ?: '[]', true) ?: [];
$hasTabs = !empty($tabs_data);

// Detect cloze mode: question contains __________ (10+ underscores) inline blanks
$BLANK_MARKER = '/_{5,}/';
$isCloze = (bool) preg_match($BLANK_MARKER, $question);

// Build inline cloze HTML — replace each blank marker with a drop-zone span
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
<!-- Mobile Drag & Drop Polyfill -->
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
body {
  font-family: 'Inter', sans-serif;
  color: var(--text);
  background: white;
  overflow: hidden;
}

/* Two-panel layout */
.two-panel { display: flex; height: 100%; overflow: hidden; }
.left-panel { width: 40%; min-width: 260px; background: #fff; border-right: 2px solid var(--border); display: flex; flex-direction: column; flex-shrink: 0; overflow: hidden; }
.panel-title { padding: 14px 20px; background: #f1f5f9; font-weight: 800; font-size: 11px; text-transform: uppercase; color: #64748b; letter-spacing: 1px; border-bottom: 1px solid var(--border); }
.tabs-row {
  display: flex;
  padding: 8px 12px 0;
  gap: 4px;
  border-bottom: 1px solid var(--border);
  overflow-x: auto;
  overflow-y: hidden;
  flex-shrink: 0;
  scrollbar-width: none;
}
.tabs-row::-webkit-scrollbar {
  height: 3px;
}
.tabs-row::-webkit-scrollbar-track {
  background: transparent;
}
.tabs-row::-webkit-scrollbar-thumb {
  background: transparent;
  border-radius: 10px;
}
.tabs-row:hover::-webkit-scrollbar-thumb {
  background: #cbd5e1;
}
.tabs-row:hover {
  scrollbar-width: thin;
}
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

.nclex-tips {
    margin-top: 24px;
    padding: 20px;
    background: #f0fdf4;
    border-radius: 12px;
    border: 1px solid #bbf7d0;
}
.tips-title {
    font-weight: 800;
    color: #166534;
    font-size: 13px;
    text-transform: uppercase;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    letter-spacing: 0.5px;
}
.tips-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.tips-list li {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    font-size: 14.5px;
    color: #15803d;
    margin-bottom: 10px;
    line-height: 1.5;
}
.tips-list li i {
    color: #22c55e;
    margin-top: 3px;
    flex-shrink: 0;
    font-size: 16px;
}
.rationale-image-wrapper {
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid var(--border);
}
.image-title {
    font-weight: 800;
    color: var(--text-muted);
    font-size: 11px;
    text-transform: uppercase;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.tip-highlight {
    background: #fef08a;
    padding: 2px 4px;
    border-radius: 4px;
    font-weight: 700;
    color: #854d0e;
    border-bottom: 1.5px solid #f59e0b;
    display: inline;
    line-height: 1;
    white-space: normal;
}

.card {
  background: var(--surface);
  border-radius: 12px;
  padding: 16px;
  width: 100%;
  max-width: 900px;
  box-shadow: 0 4px 20px -5px rgba(0,0,0,0.05);
  border: 1px solid var(--border);
  margin-bottom: 24px;
}

/* MOBILE SQUEEZE */
@media (max-width: 600px) {
  .card { padding: 12px; border-radius: 8px; }
  .question-container { font-size: 14px; line-height: 1.6; margin-bottom: 16px; }
  .blank { min-width: 80px; height: 30px; font-size: 12px; margin: 2px; }
  .instruction { font-size: 9px; margin-bottom: 8px; }
  .choices-bank { padding: 12px; gap: 6px; }
  .choice-item { padding: 5px 10px; font-size: 12px; }
  .btn { padding: 8px 16px; font-size: 12px; width: 100%; }
}

/* RESPONSIVE */
@media (max-width: 640px) {
  .card { border-radius: 0; padding: 16px; }
  .question-container { font-size: 16px; line-height: 1.8; }
  .blank { min-width: 100px; font-size: 13px; height: 32px; }
  .choices-bank { padding: 16px; gap: 8px; }
  .choice-item { padding: 6px 12px; font-size: 13px; }
  body { overflow-y: auto; }
}

.instruction {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--accent);
    letter-spacing: 1px;
    margin-bottom: 12px;
    display: block;
}

.question-container {
  font-size: 18px;
  font-weight: 600;
  line-height: 2;
  margin-bottom: 32px;
  color: var(--primary);
}

.blank {
  display: inline-flex;
  min-width: 120px;
  height: 36px;
  background: #f1f5f9;
  border: 2px dashed #cbd5e1;
  border-radius: 8px;
  margin: 0 4px;
  vertical-align: middle;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  font-weight: 700;
  color: var(--accent);
  transition: all 0.2s;
  cursor: pointer;
}

.blank.active { border-color: var(--accent); background: #eff6ff; }
.blank.filled { border-style: solid; border-color: #3b82f6; background: white; box-shadow: 0 2px 6px rgba(59, 130, 246, 0.1); }

/* Reveal Feedback */
.blank.correct-reveal { border-color: var(--success); background: #f0fdf4; color: #15803d; }
.blank.wrong-reveal { border-color: var(--danger); background: #fef2f2; color: #b91c1c; }

.choices-bank {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  padding: 24px;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid var(--border);
}

.choice-item {
  background: white;
  border: 1px solid var(--border);
  padding: 10px 20px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: grab;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  transition: all 0.2s;
}
.choice-item:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

.actions { display: flex; gap: 12px; margin-top: 32px; }
.btn { padding: 12px 32px; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; border: none; transition: all 0.2s; }
.btn-primary { background: var(--primary); color: white; }
.btn-outline { background: transparent; border: 2px solid var(--border); color: #64748b; }

#result {
  margin-top: 24px;
  padding: 24px;
  background: #f8fafc;
  border-radius: 12px;
  border-left: 4px solid var(--accent);
  display: none;
}

.previous-badge {
    display: none;
    background: #f1f5f9;
    color: #475569;
    font-size: 12px;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #cbd5e1;
}

.ordered-slots {
  margin-bottom: 24px;
}
.slots-label {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--text-muted);
  letter-spacing: 0.5px;
  margin-bottom: 10px;
}
.slot-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}
.slot-num {
  width: 28px;
  height: 28px;
  background: var(--accent);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 800;
  flex-shrink: 0;
}
.slot-row .blank {
  flex: 1;
  min-width: 0;
  display: flex;
}

/* Inline cloze blanks that sit inside question text */
.cloze-blank {
  display: inline-flex;
  min-width: 160px;
  height: 38px;
  background: #f1f5f9;
  border: 2px dashed #cbd5e1;
  border-radius: 8px;
  margin: 0 6px;
  vertical-align: middle;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
  color: var(--accent);
  transition: all 0.2s;
  cursor: pointer;
  white-space: nowrap;
  padding: 0 10px;
}
.cloze-blank.active  { border-color: var(--accent); background: #eff6ff; }
.cloze-blank.filled  { border-style: solid; border-color: #3b82f6; background: white; color: var(--text); box-shadow: 0 2px 6px rgba(59,130,246,0.1); }
.cloze-blank.correct-reveal { border-color: var(--success); background: #f0fdf4; color: #15803d; }
.cloze-blank.wrong-reveal   { border-color: var(--danger);  background: #fef2f2; color: #b91c1c; }

.cloze-select {
  display: inline-block;
  padding: 8px 12px;
  border: 2px solid #cbd5e1;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  margin: 0 4px;
  background: white;
  cursor: pointer;
  color: var(--primary);
  transition: all 0.2s;
}

.cloze-select:hover {
  border-color: var(--accent);
  background: #eff6ff;
}

.cloze-select:focus {
  outline: none;
  border-color: var(--accent);
  background: #eff6ff;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.cloze-select option {
  color: var(--primary);
  padding: 8px;
}
.stats-btn {
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.stats-btn:hover { background: #e2e8f0; color: #0f172a; }
.stats-btn i { font-size: 14px; color: #3b82f6; }
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
    <div class="previous-badge" id="prevBadge">
        <i class="fas fa-lock"></i> Your ordering has been submitted and is now read-only.
    </div>

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
        <button id="submitBtn" class="btn btn-primary">Submit Answer</button>
    </div>

    <div id="result">
        <div style="font-weight:800; color:#64748b; font-size:12px; margin-bottom:8px; text-transform:uppercase;">Performance & Rationale</div>
        <div id="resSummary" style="font-weight:700; margin-bottom:12px;"></div>
        <div id="rationaleText" style="font-size:14px; line-height:1.6;"></div>
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
    const correct = <?= json_encode($correct) ?>;
    const rationale = <?= json_encode($rationale) ?>;
    const furtherinfo = <?= json_encode($furtherinfo) ?>;
    const image = <?= json_encode($image) ?>;
    const isCloze = <?= $isCloze ? 'true' : 'false' ?>;
    let dragged = null;
    let isReviewMode = false;
    let initialAnswers = [];
    let hasInteracted = false;

    /* Stats Data */
    const _qStartTime = Date.now();
    const questionStats = {
        difficulty: <?= json_encode($dlevel) ?>,
        peerScore: <?= json_encode($avg_peer_score) ?>,
        concept: <?= json_encode($concept) ?>,
        topic: <?= json_encode($topic) ?>,
        system: <?= json_encode($system) ?>,
        cnc: <?= json_encode($cnc) ?>,
        type: 'Drag & Drop'
    };

    window.showStatsModal = function() {
        const secs = Math.round((Date.now() - _qStartTime) / 1000);
        const timeTaken = secs < 60 ? secs + ' s' : Math.floor(secs/60) + ' m ' + (secs%60) + ' s';
        Swal.fire({
            title: '<i class="fas fa-chart-bar" style="color:#3b82f6; margin-right:6px;"></i> Statistics',
            html: `
                <div style="text-align:left; padding:4px 0;">
                    <div style="display:flex; gap:10px; margin-bottom:16px;">
                        <div style="flex:1; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px; text-align:center;">
                            <div style="font-size:18px; margin-bottom:4px;"><i class="fas fa-gauge-high" style="color:#f59e0b;"></i></div>
                            <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Difficulty level</div>
                            <div style="font-size:13px; font-weight:800; color:#0f172a;">${questionStats.difficulty}</div>
                        </div>
                        <div style="flex:1; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px; text-align:center;">
                            <div style="font-size:18px; margin-bottom:4px;"><i class="fas fa-users" style="color:#8b5cf6;"></i></div>
                            <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Avg. Peers Score</div>
                            <div style="font-size:13px; font-weight:800; color:#10b981;">${questionStats.peerScore}</div>
                        </div>
                        <div style="flex:1; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px; text-align:center;">
                            <div style="font-size:18px; margin-bottom:4px;"><i class="fas fa-hourglass-half" style="color:#3b82f6;"></i></div>
                            <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Time taken</div>
                            <div style="font-size:13px; font-weight:800; color:#0f172a;">${timeTaken}</div>
                        </div>
                    </div>
                    <div style="border-top:1px solid #e2e8f0; padding-top:14px; display:flex; flex-direction:column; gap:10px;">
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; min-width:130px;">Subject</span>
                            <span style="background:#eff6ff; color:#3b82f6; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">${questionStats.concept}</span>
                            <span style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">Lesson</span>
                            <span style="background:#eff6ff; color:#3b82f6; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">${questionStats.topic}</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; min-width:130px;">Client Need Area</span>
                            <span style="background:#f0fdf4; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">${questionStats.cnc}</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; min-width:130px;">Client Need Topic</span>
                            <span style="background:#f0fdf4; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">${questionStats.system}</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; min-width:130px;">Question Type</span>
                            <span style="background:#fef3c7; color:#d97706; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">${questionStats.type}</span>
                        </div>
                    </div>
                </div>
            `,
            confirmButtonText: 'Got it',
            confirmButtonColor: '#3b82f6',
            width: '500px'
        });
    };

    // All blanks — covers both .blank (ordering slots) and .cloze-blank (inline)
    const $blanks = () => isCloze ? $('.cloze-blank') : $('.blank');

    // Drag start / end
    $(document).on('dragstart', '.choice-item', function(e){
        dragged = this;
        $(this).css('opacity', '0.5');
    });
    $(document).on('dragend', '.choice-item', function(){
        $(this).css('opacity', '1');
    });

    // Dragover / dragleave / drop — delegated so cloze blanks added via PHP also work
    $(document).on('dragover', '.blank, .cloze-blank', function(e){
        e.preventDefault(); $(this).addClass('active');
    });
    $(document).on('dragleave', '.blank, .cloze-blank', function(){
        $(this).removeClass('active');
    });
    $(document).on('drop', '.blank, .cloze-blank', function(e){
        e.preventDefault();
        if(isReviewMode) return;
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

    // Click blank to return item to bank
    $(document).on('click', '.blank, .cloze-blank', function(){
        if(isReviewMode) return;
        if(!$(this).hasClass('filled')) return;

        if(!hasInteracted) {
            hasInteracted = true;
            $blanks().each(function(i){ initialAnswers[i] = $(this).text().trim(); });
        }
        $('#choicesBank').append(`<div class="choice-item" draggable="true">${$(this).text()}</div>`);
        $(this).text('Drop Here').removeClass('filled');
    });

    function showResult(scoreHeader, userAnswers = [], prevInitial = []) {
        $blanks().removeClass('correct-reveal wrong-reveal omitted-reveal');

        $blanks().each(function(i){
            let txt = $(this).text().trim();
            if(txt === correct[i]) {
                $(this).addClass('correct-reveal');
            } else {
                $(this).addClass('wrong-reveal');
            }
        });

        $('#resSummary').html(`
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:4px;">
                <span style="font-weight:700;">${scoreHeader}</span>
                <button class="stats-btn" onclick="showStatsModal()">
                    <i class="fas fa-info-circle"></i> Question Info
                </button>
            </div>
        `);

        let resultHtml = rationale || "No rationale provided.";
        
        if (furtherinfo) {
            let tips = [];
            try {
                let decoded = JSON.parse(furtherinfo);
                if (Array.isArray(decoded)) tips = decoded;
                else tips = [furtherinfo];
            } catch (e) {
                tips = furtherinfo.split('\n').filter(l => l.trim() !== '');
            }

            resultHtml += '<div class="nclex-tips">';
            resultHtml += '<div class="tips-title"><i class="fas fa-lightbulb"></i> NCLEX Tips & Further Information</div>';
            resultHtml += '<ul class="tips-list">';
            tips.forEach(t => {
                // Collapse newlines and extra spaces for a continuous sentence
                let cleanTip = t.replace(/\s+/g, ' ').trim();
                // Highlight words wrapped in %
                let highlighted = cleanTip.replace(/%([^%]+)%/g, '<span class="tip-highlight">$1</span>');
                resultHtml += '<li><i class="fas fa-check-circle"></i> <span>' + highlighted + '</span></li>';
            });
            resultHtml += '</ul></div>';
        }
        
        if (image) {
            resultHtml += '<div class="rationale-image-wrapper">';
            resultHtml += '<div class="image-title"><i class="fas fa-image"></i> Related Illustration</div>';
            resultHtml += '<img src="../../../../admin/dashboard/pages/uploads/' + image + '" alt="NCLEX Illustration" style="max-width:100%; border-radius:12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">';
            resultHtml += '</div>';
        }

        $('#rationaleText').html(resultHtml);
        $('#result').fadeIn();
        $blanks().css('cursor', 'default');
        $('.choice-item').attr('draggable', false);
        $('#submitBtn').hide();
    }



    window.addEventListener('message', (e) => {
        if(e.data.type === 'prefill' || e.data.type === 'previous') {
            isReviewMode = e.data.isReview ?? false;
            const ans = e.data.answer || [];
            const prevInitial = e.data.initial_answer || [];

            if(ans.length > 0) {
                initialAnswers = prevInitial.length > 0 ? prevInitial : ans;
                $('#prevBadge').show();

                // Handle both dropdown and drag-and-drop formats
                if($('.cloze-select').length > 0) {
                    // Cloze format
                    ans.forEach((val, i) => {
                        $(`.cloze-select:eq(${i})`).val(val || '').prop('disabled', isReviewMode);
                    });
                } else {
                    ans.forEach((val, i) => {
                        if(val && val !== 'Drop Here') {
                            $blanks().eq(i).text(val).addClass('filled');
                            $('.choice-item').each(function(){
                                if($(this).text().trim() === val) $(this).remove();
                            });
                        }
                    });
                }

                if(isReviewMode) {
                    // Lock everything in review mode
                    $('#submitBtn').hide();
                    $('#choicesBank').hide();
                    $blanks().css('cursor', 'default');
                    $('.choice-item').attr('draggable', false);
                    $('#prevBadge').show();
                }

                if(e.data.showRationale) {
                    let s = e.data.score || 0;
                    showResult("Score: " + Math.round(s*100) + "%", ans, prevInitial);
                }
            } else if(isReviewMode) {
                // Review mode with no answer — still lock
                $('#submitBtn').hide();
                $('#prevBadge').show();
            }
        }
    });

    $('#submitBtn').click(function(){
        if(isReviewMode) return; // Prevent resubmission in review mode

        let userAnswers = [];
        let incomplete = false;

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

        const total = correct.length;
        const normalized = parseFloat((earned / total).toFixed(2));
        showResult("Score: " + Math.round(normalized*100) + "% ("+earned+"/"+total+" pts)", userAnswers);
        
        // Calculate changes
        let changesData = null;
        if(JSON.stringify(initialAnswers) !== JSON.stringify(userAnswers)){
            changesData = {
                modified_count: userAnswers.filter((ans, i) => ans !== initialAnswers[i]).length,
                changed: true
            };
        }

        window.parent.postMessage({
            type:'answered',
            question_id: <?= json_encode($id) ?>,
            answer: userAnswers,
            initial_answer: initialAnswers.length > 0 ? initialAnswers : null,
            correctAnswer: correct,
            isCorrect: earned === total,
            score: normalized,
            max_points: total,
            earned_points: earned,
            changes: changesData,
            rationale: rationale,
            topic: <?= json_encode($topic) ?>,
            question_type: 'dragndrop'
        }, '*');
    });

    // ===== AUTO-SCROLL FOR DRAG & DROP ON MOBILE =====
    let isDragging = false;
    let autoScrollInterval = null;

    // Detect when dragging starts
    $(document).on('dragstart', '.choice-item', function(e){
        isDragging = true;
    });

    // Detect when dragging ends
    $(document).on('dragend', '.choice-item', function(e){
        isDragging = false;
        if (autoScrollInterval) clearInterval(autoScrollInterval);
    });

    // Auto-scroll while dragging on desktop/mouse
    $(document).on('dragover', function(e){
        if (!isDragging) return;

        const mouseY = e.clientY;
        const scrollThreshold = 100; // pixels from top/bottom
        let scrollAmount = 0;

        // Check if mouse is near top of viewport
        if (mouseY < scrollThreshold) {
            scrollAmount = -10; // scroll up
        }
        // Check if mouse is near bottom of viewport
        else if (mouseY > window.innerHeight - scrollThreshold) {
            scrollAmount = 10; // scroll down
        }

        if (scrollAmount !== 0) {
            window.scrollBy(0, scrollAmount);
        }
    });

    // For touch/mobile drag - also monitor drag events on drop zones
    document.addEventListener('dragover', function(e){
        if (!isDragging) return;

        // Get touch position if available (from DragDropTouch polyfill)
        let clientY = e.clientY;
        if (clientY === 0 && e.touches && e.touches[0]) {
            clientY = e.touches[0].clientY;
        }

        const scrollThreshold = 100;
        if (clientY < scrollThreshold) {
            window.scrollBy(0, -10);
        } else if (clientY > window.innerHeight - scrollThreshold) {
            window.scrollBy(0, 10);
        }
    }, true); // Use capture phase for better responsiveness

    // Final fallback for touch end
    $(document).on('touchend', function(){
        isDragging = false;
        if (autoScrollInterval) clearInterval(autoScrollInterval);
    });

    // Signal parent that this iframe is ready to receive prefill data
    if (window.parent !== window) window.parent.postMessage({ type: 'ready' }, '*');
});
</script>
</body>
</html>
