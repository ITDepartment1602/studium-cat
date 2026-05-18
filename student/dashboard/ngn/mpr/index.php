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

$items = explode("\n", $data['items']); 
$correct = explode(",", $data['correct']);
$rationale = $data['rationale'] ?? '';
$furtherinfo = $data['furtherinfo'] ?? '';
$image = $data['image'] ?? '';
$question = $data['question'] ?? '';

$required = count($items);
if (preg_match('/Select\s+(\d+)/i', $question, $match)) {
    $required = (int)$match[1];
} elseif (stripos($question, 'Select all that apply') !== false) {
    $required = count($items);
}

// Dynamic clinical reference tabs from `tabs` DB field (spec §1.2)
$tabs_data = json_decode(($data['tabs'] ?? '') ?: '[]', true) ?: [];
$hasTabs = !empty($tabs_data);

// Fetch Stats
$topic = $data['topic'] ?? 'General';
$system = $data['system'] ?? 'N/A';
$cnc = $data['cnc'] ?? 'N/A';
$dlevel = $data['dlevel'] ?? 'N/A';
$concept = $data['concept'] ?? 'General';
$narcan = $data['narcan'] ?? 'N/A';
$q_uid = 'mpr_' . $data['id'];
$peer_q = mysqli_query($con, "SELECT AVG(isCorrect) * 100 as avg_score FROM exam_results WHERE question_uid = '$q_uid'");
$peer_data = mysqli_fetch_assoc($peer_q);
$avg_peer_score = $peer_data['avg_score'] ? round($peer_data['avg_score'], 1) . '%' : 'N/A';
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
body {
  font-family: 'Inter', sans-serif;
  background: transparent;
  color: var(--text);
}

/* Two-panel layout */
.two-panel { display: flex; min-height: 100vh; overflow: hidden; }
.left-panel { width: 40%; min-width: 260px; background: #fff; border-right: 2px solid var(--border); display: flex; flex-direction: column; flex-shrink: 0; overflow: hidden; }
.panel-title { padding: 14px 20px; background: #f1f5f9; font-weight: 800; font-size: 11px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 1px; border-bottom: 1px solid var(--border); }
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
.detail-value { color: #0a1628; font-weight: 600; }

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
.stats-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
}
.stats-btn i { font-size: 14px; color: #3b82f6; }
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
  padding: 32px;
  max-width: 900px;
  margin: 0 auto;
}

.question-text {
  font-size: 18px;
  font-weight: 600;
  line-height: 1.6;
  margin-bottom: 24px;
  color: var(--primary);
}

.instruction-badge {
  display: inline-block;
  background: #eff6ff;
  color: #1e40af;
  font-size: 12px;
  font-weight: 700;
  padding: 4px 12px;
  border-radius: 100px;
  margin-bottom: 20px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.options-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.option-item {
  position: relative;
  display: flex;
  align-items: center;
  padding: 16px 20px;
  border: 2px solid var(--border);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.option-item:hover {
  border-color: #cbd5e1;
  background: #fcfcfd;
}

.option-item.selected {
  border-color: var(--accent);
  background: #f0f7ff;
}

.option-item input {
  position: absolute;
  opacity: 0;
  cursor: pointer;
}

.custom-checkbox {
  width: 20px;
  height: 20px;
  border: 2px solid #cbd5e1;
  border-radius: 6px;
  margin-right: 16px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  background: white;
}

.option-item.selected .custom-checkbox {
  background: var(--accent);
  border-color: var(--accent);
}

.custom-checkbox::after {
  content: "✓";
  color: white;
  font-size: 14px;
  font-weight: bold;
  display: none;
}

.option-item.selected .custom-checkbox::after {
  display: block;
}

.option-text {
  font-size: 15px;
  font-weight: 500;
  color: var(--text);
  line-height: 1.4;
}

/* Feedback Styles */
.option-item.correct-reveal {
  border-color: var(--success);
  background: #ecfdf5;
}
.option-item.correct-reveal .custom-checkbox {
  background: var(--success);
  border-color: var(--success);
}

.option-item.wrong-reveal {
  border-color: var(--danger);
  background: #fef2f2;
}
.option-item.wrong-reveal .custom-checkbox {
  background: var(--danger);
  border-color: var(--danger);
}

.actions {
  margin-top: 32px;
  display: flex;
  align-items: center;
  gap: 16px;
}

.btn {
  padding: 12px 32px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
}

.btn-primary {
  background: var(--primary);
  color: white;
}

.btn-primary:hover {
  background: #1e293b;
  transform: translateY(-1px);
}

.btn-outline {
  background: transparent;
  border: 2px solid var(--border);
  color: var(--text-muted);
}

.btn-outline:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

#result {
  margin-top: 24px;
  padding: 24px;
  border-radius: 12px;
  background: #f8fafc;
  border-left: 4px solid var(--accent);
  display: none;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

.rationale-title {
  font-size: 14px;
  font-weight: 800;
  text-transform: uppercase;
  color: var(--text-muted);
  margin-bottom: 8px;
  letter-spacing: 0.5px;
}

.rationale-text {
  font-size: 15px;
  line-height: 1.6;
  color: var(--text);
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
    <div class="previous-badge" id="prevBadge">
        <i class="fas fa-lock"></i> This question has been submitted and is now read-only.
    </div>

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
        <button id="submitBtn" class="btn btn-primary">Submit Answer</button>
    </div>

    <div id="result">
        <div class="rationale-title" id="resType">Rationale</div>
        <div class="rationale-text" id="rationaleText"></div>
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
    let image = <?= json_encode($image) ?>;

    /* Stats Data */
    const _qStartTime = Date.now();
    const questionStats = {
        difficulty: <?= json_encode($dlevel) ?>,
        peerScore: <?= json_encode($avg_peer_score) ?>,
        concept: <?= json_encode($concept) ?>,
        topic: <?= json_encode($topic) ?>,
        system: <?= json_encode($system ?? 'N/A') ?>,
        cnc: <?= json_encode($cnc) ?>,
        type: 'Multiple Response (MPR)'
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

    let isEditing = false;
    let isReviewMode = false;
    let hasInteracted = false;      // Track first user interaction
    let initialAnswers = [];
    let currentAnswers = [];
    let changes = null;

    // ===== PREFILL MESSAGE HANDLER (for review/resume) =====
    window.addEventListener('message', (event) => {
        if(event.data.type === 'prefill' || event.data.type === 'previous'){
            isReviewMode = event.data.isReview ?? false;
            const previousAnswers = event.data.answer || [];
            const prevInitial = event.data.initial_answer || [];
            
            if(previousAnswers.length > 0){
                initialAnswers = prevInitial.length > 0 ? prevInitial : previousAnswers;
                currentAnswers = previousAnswers;
                
                $('#prevBadge').show();
                $('input[name="answers[]"]').each(function(){
                    let checked = previousAnswers.includes($(this).val());
                    $(this).prop('checked', checked);
                    if(checked) $(this).closest('.option-item').addClass('selected');
                });
                
                if(event.data.showRationale) {
                    let score = event.data.score || 0;
                    let earned = event.data.earned_points || 0;
                    let max = event.data.max_points || 0;
                    showResult(Math.round(score*100) + "% ("+earned+"/"+max+" pts)", true, prevInitial);
                }
            }
        }
    });

    // Toggle selection styles
    $(document).on('change', 'input[name="answers[]"]', function(){
        if(isReviewMode) return; // Prevent changes in review mode
        
        // Capture initial answers on first interaction (only if not prefilled)
        if(!hasInteracted && initialAnswers.length === 0) {
            hasInteracted = true;
            $('input[name="answers[]"]').each(function(){
                if($(this).is(':checked')) {
                    initialAnswers.push($(this).val());
                }
            });
        } else if(!hasInteracted) {
            hasInteracted = true; // Mark as interacted even if prefilled
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
            Swal.fire({
                icon:'warning',
                title:'Limit Reached',
                text:'You can only select ' + required + ' items.'
            });
        }
    });

    function showResult(scoreText, showCorrectLines = true, prevInitial = null) {
        $('.option-item').removeClass('correct-reveal wrong-reveal omitted-reveal');

        if(showCorrectLines) {
            $('input[name="answers[]"]').each(function(){
                let val = $(this).val();
                let parent = $(this).closest('.option-item');

                if(correct.includes(val)){
                    parent.addClass('correct-reveal');
                } else if($(this).is(':checked')) {
                    parent.addClass('wrong-reveal');
                }
            });
        }

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
        $('#resType').html(`
            <div style="display:flex; align-items:center; justify-content:space-between; width:100%;">
                <span>Score: ${scoreText} — Rationale</span>
                <button class="stats-btn" onclick="showStatsModal()">
                    <i class="fas fa-info-circle"></i> Question Info
                </button>
            </div>
        `);
        $('#result').fadeIn();
        
        $('input[name="answers[]"]').prop('disabled', true);
        $('.option-item').css('cursor', 'default').off('click');
        $('#submitBtn').hide();
    }

    $('#submitBtn').click(function(){
        if(isReviewMode) return; // Prevent resubmission in review mode
        
        let selected = [];
        $('input[name="answers[]"]:checked').each(function(){
            selected.push($(this).val());
        });

        if(selected.length === 0){
            Swal.fire({ icon:'error', title:'Incomplete', text:'Please select at least one answer.' });
            return;
        }

        // Track initial answers if not set
        if(initialAnswers.length === 0) {
            initialAnswers = [...selected];
        }
        
        currentAnswers = selected;

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

        showResult(Math.round(normalizedScore * 100) + "% ("+earned+"/"+maxPoints+" pts)");

        // Calculate changes
        let changesData = null;
        if(initialAnswers.length > 0 && initialAnswers.length !== selected.length) {
            const added = selected.filter(a => !initialAnswers.includes(a));
            const removed = initialAnswers.filter(a => !selected.includes(a));
            
            if(added.length > 0 || removed.length > 0) {
                changesData = {
                    added: added,
                    removed: removed,
                    modified_count: added.length + removed.length,
                    changed: true
                };
            }
        }

        window.parent.postMessage({
            type:'answered',
            answer:selected,
            initial_answer: initialAnswers.length > 0 ? initialAnswers : null,
            correctAnswer:correct,
            correct: earned === maxPoints && selected.length === maxPoints,
            score: normalizedScore,
            max_points: maxPoints,
            earned_points: earned,
            changes: changesData,
            rationale: rationale,
            topic: <?= json_encode($data['topic'] ?? 'General') ?>,
            system: <?= json_encode($data['system'] ?? 'N/A') ?>,
            cnc: <?= json_encode($data['cnc'] ?? 'N/A') ?>,
            dlevel: <?= json_encode($data['dlevel'] ?? 'N/A') ?>,
            question_id: <?= json_encode($data['id']) ?>,
            question_type:'mpr'
        },'*');
    });

    // Signal parent that this iframe is ready to receive prefill data
    if (window.parent !== window) window.parent.postMessage({ type: 'ready' }, '*');
});
</script>
</body>
</html>