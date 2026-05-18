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

$topic = $data['topic'] ?? '';
$question = $data['question'] ?? '';
$passage = $data['passage'] ?? '';
$options = array_filter(array_map('trim', explode(',', $data['options'] ?? '')));
$highlightable_flags = array_map('intval', array_filter(array_map('trim', explode(',', $data['highlightable'] ?? ''))));
$correct_phrases = array_filter(array_map('trim', explode(',', $data['correct_words'] ?? '')));
$rationale = $data['rationale'] ?? '';
$furtherinfo = $data['furtherinfo'] ?? '';
$image = $data['image'] ?? '';
$systemTxt = $data['system'] ?? '';
$cnc = $data['cnc'] ?? '';
$dlevel = $data['dlevel'] ?? '';
$concept = $data['concept'] ?? 'General';
$narcan = $data['narcan'] ?? 'N/A';
$maxHighlights = (int)($data['maxHighlights'] ?? 4);
$q_uid = 'highlight_' . ($data['id'] ?? 0);
$peer_q = mysqli_query($con, "SELECT AVG(isCorrect) * 100 as avg_score FROM exam_results WHERE question_uid = '$q_uid'");
$peer_data = mysqli_fetch_assoc($peer_q);
$avg_peer_score = ($peer_data && $peer_data['avg_score']) ? round($peer_data['avg_score'], 1) . '%' : 'N/A';

// Build passage HTML with spans
$option_map = [];
foreach ($options as $i => $opt) if ($opt !== '') $option_map[$i] = $opt;
uasort($option_map, function ($a, $b) { return mb_strlen($b) <=> mb_strlen($a); });

$passage_work = $passage;
$placeholders = [];
foreach ($option_map as $idx => $phrase) {
    if (trim($phrase) === '') continue;
    $token = "~~OPTION_{$idx}_" . md5($phrase) . "~~";
    
    // Create a regex that allows any sequence of spaces/newlines between words in the phrase
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
    $idx = $ph['index'];
    $display = htmlspecialchars($ph['text']);
    $is_highlightable = (isset($highlightable_flags[$idx]) && intval($highlightable_flags[$idx]) === 1);
    $cls = 'inline-token' . ($is_highlightable ? ' hint-highlightable' : '');
    $token_to_span[$ph['token']] = "<span class=\"{$cls}\" data-index=\"{$idx}\">{$display}</span>";
}
$passage_html = strtr($passage_work, $token_to_span);

// Dynamic clinical reference tabs from `tabs` DB field (spec §1.2)
$tabs_data = json_decode(($data['tabs'] ?? '') ?: '[]', true) ?: [];
$hasTabs = !empty($tabs_data);
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
  --primary: #0a1628;
  --accent: #3b82f6;
  --highlight: #fef08a;
  --highlight-hover: #fde68a;
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
.right-panel { flex: 1; overflow-y: auto; padding: clamp(16px, 5vh, 48px) 16px; min-width: 0; display: flex; justify-content: center; align-items: flex-start; }
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
  border-radius: 20px;
  padding: clamp(20px, 4vw, 40px);
  width: 100%;
  max-width: 900px;
  box-shadow: 0 10px 30px -5px rgba(0,0,0,0.05);
  border: 1px solid var(--border);
  margin-bottom: 24px;
}

/* RESPONSIVE */
@media (max-width: 640px) {
  .card { border-radius: 0; padding: 16px; }
  .question-text { font-size: 16px; line-height: 1.5; }
  .passage-box { padding: 16px; font-size: 14px; line-height: 1.6; }
  .btn { padding: 10px 24px; font-size: 13px; }
}

.instruction {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--accent);
    letter-spacing: 1.5px;
    margin-bottom: 12px;
    display: block;
}

.question-text {
  font-size: 18px;
  font-weight: 700;
  line-height: 1.6;
  margin-bottom: 24px;
}

.passage-box {
  background: #fdfdfd;
  border: 1px solid var(--border);
  padding: 24px;
  border-radius: 12px;
  line-height: 1.8;
  font-size: 16px;
  color: #334155;
  white-space: pre-wrap;
  margin-bottom: 24px;
}

.inline-token {
  padding: 2px 2px;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
  border-bottom: 1.5px solid transparent;
}

.hint-highlightable:hover {
  background: #f1f5f9;
  border-bottom-color: var(--accent);
}

.inline-token.highlighted {
  background: var(--highlight);
  border-bottom-color: #f59e0b;
  font-weight: 600;
}

.inline-token.correct-reveal { background: #dcfce7 !important; border-bottom-color: var(--success); }
.inline-token.wrong-reveal { background: #fee2e2 !important; border-bottom-color: var(--danger); }

.actions { display: flex; gap: 12px; margin-top: 24px; }
.btn {
  padding: 12px 32px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}
.btn-primary { background: var(--primary); color: white; }
.btn-outline { background: transparent; border: 2px solid var(--border); color: #64748b; }

#result {
  margin-top: 24px;
  padding: 24px;
  background: #f8fafc;
  border-radius: 12px;
  border-left: 4px solid var(--accent);
  display: list-item; /* none by default, JS handles */
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
    <div class="previous-badge" id="prevBadge">
        <i class="fas fa-lock"></i> Your highlights have been finalized and are now read-only.
    </div>

    <span class="instruction">Highlight Question</span>
    <div class="question-text"><?= nl2br(htmlspecialchars($question)) ?></div>

    <div class="passage-box" id="passage"><?php echo $passage_html; ?></div>

    <div class="actions">
        <button id="submitBtn" class="btn btn-primary">Submit Selections</button>
    </div>

    <div id="result">
        <div style="font-weight:800; color:#64748b; font-size:12px; margin-bottom:8px; text-transform:uppercase;">Performance & Rationale</div>
        <div id="resSummary" style="font-weight:700; margin-bottom:12px;"></div>
        <div id="rationaleContent" style="font-size:14px; line-height:1.6;"></div>
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
    const maxAllowed = <?php echo $maxHighlights; ?>;
    const rationale = <?php echo json_encode($rationale); ?>;
    const furtherinfo = <?php echo json_encode($furtherinfo); ?>;
    const image = <?php echo json_encode($image); ?>;
    let selectedText = [];
    let isReviewMode = false;
    let initialSelectedText = [];
    let hasInteracted = false;

    /* Stats Data */
    const _qStartTime = Date.now();
    const questionStats = {
        difficulty: <?= json_encode($dlevel) ?>,
        peerScore: <?= json_encode($avg_peer_score) ?>,
        concept: <?= json_encode($concept) ?>,
        topic: <?= json_encode($topic) ?>,
        system: <?= json_encode($systemTxt) ?>,
        cnc: <?= json_encode($cnc) ?>,
        type: 'Highlight Table (Highlight)'
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

    function normalize(str){
        return str.toLowerCase().replace(/[^a-z0-9\s]/g, '').replace(/\s+/g, ' ').trim();
    }
    const normCorrect = correctPhrases.map(normalize);

    $('.inline-token').click(function(){
        if(isReviewMode) return; // review mode
        
        hasInteracted = true;  // Mark that user has interacted
        
        const txt = $(this).text().trim();
        const nm = normalize(txt);

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

    function reveal(scoreHeader, prevSelected = []) {
        const displayInitial = prevSelected.length > 0 ? prevSelected : initialSelectedText;
        const displayInitialNorm = displayInitial.map(normalize);
        
        $('.inline-token').removeClass('correct-reveal wrong-reveal omitted-reveal');
        $('.inline-token').each(function(){
            const nm = normalize($(this).text().trim());
            if(normCorrect.includes(nm)) {
                $(this).addClass('correct-reveal');
            } else if($(this).hasClass('highlighted')) {
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

        $('#rationaleContent').html(resultHtml);
        $('#result').fadeIn();
        $('.inline-token').css('cursor', 'default');
        $('#submitBtn').hide();
    }



    window.addEventListener('message', (e) => {
        if(e.data.type === 'prefill' || e.data.type === 'previous') {
            isReviewMode = e.data.isReview ?? false;
            const prev = e.data.answer || [];
            let prevInitial = e.data.initial_answer || [];
            
            // Handle JSON-encoded initial_answer from database
            if(typeof prevInitial === 'string') {
                try { prevInitial = JSON.parse(prevInitial); } catch(er) { prevInitial = []; }
            }
            
            if(prev.length > 0) {
                initialSelectedText = prevInitial.length > 0 ? prevInitial : prev;
                $('#prevBadge').show();
                selectedText = [...prev];
                $('.inline-token').each(function(){
                    const nm = normalize($(this).text().trim());
                    if(prev.map(normalize).includes(nm)) $(this).addClass('highlighted');
                });
                
                if(e.data.showRationale) {
                    let s = e.data.score || 0;
                    reveal("Score: " + Math.round(s*100) + "%", prevInitial);
                }
            }
        }
    });
    
    // Capture initial state on page load for fresh exams
    if(initialSelectedText.length === 0) {
        $('.inline-token.highlighted').each(function(){
            initialSelectedText.push($(this).text().trim());
        });
    }

    $('#submitBtn').click(function(){
        if(isReviewMode) return; // Prevent resubmission in review mode
        
        if(selectedText.length === 0) {
            Swal.fire({ icon:'error', title:'Incomplete', text:'Please highlight at least one phrase.' });
            return;
        }
        
        // Capture initial if not done yet (safety net)
        if(initialSelectedText.length === 0){
            initialSelectedText = [...selectedText];
        }

        let match = 0, wrong = 0;
        const selNorm = selectedText.map(normalize);
        selNorm.forEach(s => {
            if(normCorrect.includes(s)) match++;
            else wrong++;
        });

        const earned = Math.max(0, match - wrong);
        const total = normCorrect.length || 1;
        const normalized = parseFloat((earned / total).toFixed(2));

        reveal("Score: " + Math.round(normalized*100) + "% ("+earned+"/"+total+" pts)");
        
        // Calculate changes
        let changesData = null;
        const initialNorm = initialSelectedText.map(normalize).sort().join('|');
        const currentNorm = selectedText.map(normalize).sort().join('|');
        if(initialNorm !== currentNorm){
            changesData = {
                modified_count: 1,
                changed: true
            };
        }

        window.parent.postMessage({
            type:'answered',
            answer: selectedText,
            initial_answer: initialSelectedText.length > 0 ? initialSelectedText : null,
            correctAnswer: correctPhrases,
            correct: earned === total,
            score: normalized,
            max_points: total,
            earned_points: earned,
            changes: changesData,
            rationale: rationale,
            topic: <?= json_encode($topic) ?>,
            question_id: <?= json_encode($id) ?>,
            question_type: 'highlight'
        }, '*');
    });

    // Signal parent that this iframe is ready to receive prefill data
    if (window.parent !== window) window.parent.postMessage({ type: 'ready' }, '*');
});
</script>
</body>
</html>