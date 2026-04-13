<?php
// bowtie/index.php
require_once '../../../../config.php';
// session_start handled by config.php

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id > 0) {
    $q = mysqli_query($con, "SELECT * FROM btq WHERE id = $id LIMIT 1");
} else {
    $q = mysqli_query($con, "SELECT * FROM btq ORDER BY RAND() LIMIT 1");
}
$data = mysqli_fetch_assoc($q);
if (!$data) die('Question not found.');

$actions    = json_decode($data['actionToTake'], true) ?? [];
$conditions = json_decode($data['potentialConditions'], true) ?? [];
$parameters = json_decode($data['parameterToMonitor'], true) ?? [];
$nursesNotes = json_decode($data['nursesNotes'], true) ?? [];
$vitalSigns  = json_decode($data['vitalSigns'], true) ?? [];
$diagnostics = json_decode($data['diagnostics'], true) ?? [];
$rationale = $data['rationale'] ?? '';

$correctActions = []; $correctConditions = []; $correctParameters = [];
foreach ($actions as $a) if (!empty($a['correct'])) $correctActions[] = $a['text'];
foreach ($conditions as $c) if (!empty($c['correct'])) $correctConditions[] = $c['text'];
foreach ($parameters as $p) if (!empty($p['correct'])) $correctParameters[] = $p['text'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NCLEX NGN Bow-Tie</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
  --text-muted: #64748b;
  --drop-bg: #eff6ff;
}

* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; margin: 0; padding: 0; }
body {
  font-family: 'Inter', sans-serif;
  background: var(--bg);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.app-container {
  display: flex;
  flex-direction: column;
  height: 100%;
  width: 100%;
}

.main-container {
  display: flex;
  flex: 1;
  overflow: hidden;
}

/* Moved responsive block to bottom of cascade */

/* EXHIBITS */
.left-panel {
  width: 40%;
  background: white;
  border-right: 2px solid var(--border);
  display: flex;
  flex-direction: column;
}

.panel-title {
  padding: 16px 20px;
  background: #f1f5f9;
  font-weight: 800;
  font-size: 11px;
  text-transform: uppercase;
  color: var(--text-muted);
  letter-spacing: 1px;
  border-bottom: 1px solid var(--border);
}

.tabs-row {
  display: flex;
  padding: 8px 12px 0;
  gap: 4px;
  border-bottom: 1px solid var(--border);
}

.tab-btn {
  padding: 10px 16px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  border-radius: 8px 8px 0 0;
  color: var(--text-muted);
}

.tab-btn.active {
  background: var(--bg);
  color: var(--accent);
  border: 1px solid var(--border);
  border-bottom-color: var(--bg);
  margin-bottom: -1px;
}

.tab-content-area {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
}

.clinical-record {
    background: #fdfdfd;
    border: 1px solid #f1f5f9;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 8px;
    font-size: 14px;
    line-height: 1.5;
}

/* BOWTIE INTERFACE */
.right-panel {
  width: 60%;
  background: white;
  overflow-y: auto;
  padding: 32px;
}

.question-header {
  font-size: 17px;
  font-weight: 700;
  line-height: 1.6;
  margin-bottom: 32px;
}

/* THE DIAGRAM */
.diagram-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 20px;
  margin-bottom: 40px;
  position: relative;
}

.col-label {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 12px;
    text-align: center;
}

.diagram-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.dropzone {
  min-height: 70px;
  background: var(--drop-bg);
  border: 2px dashed #bfdbfe;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
  text-align: center;
  font-size: 12px;
  color: #3b82f6;
  font-weight: 500;
  transition: all 0.2s;
  cursor: pointer;
  position: relative;
}

.dropzone:hover { background: #dbeafe; border-color: #60a5fa; }
.dropzone.center-slot {
  min-height: 100px;
  border: 3px solid #3b82f6;
  background: white;
  color: var(--primary);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
}

.choice-token {
  background: white;
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 10px 14px;
  font-size: 13px;
  font-weight: 600;
  cursor: grab;
  width: 100%;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  transition: transform 0.1s;
}
.choice-token:active { cursor: grabbing; transform: scale(0.98); }

/* Feedback states */
.dropzone.correct-reveal { border-color: var(--success); background: #f0fdf4; }
.dropzone.wrong-reveal { border-color: var(--danger); background: #fef2f2; }
.dropzone.omitted-reveal { 
  border-color: #f59e0b; 
  background: #fffbeb;
  text-decoration: line-through;
  opacity: 0.75;
}

/* CHOICE BANKS */
.banks-container {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 20px;
  background: #f8fafc;
  padding: 24px;
  border-radius: 16px;
}

.bank-col { display: flex; flex-direction: column; gap: 8px; }
.bank-header { font-size: 11px; font-weight: 800; color: #64748b; margin-bottom: 4px; text-transform: uppercase; }
.bank-list { min-height: 100px; display: flex; flex-direction: column; gap: 8px; }

.footer {
  padding: 16px 32px;
  background: white;
  border-top: 1px solid var(--border);
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}
@media (max-width: 600px) {
  .footer { padding: 16px; justify-content: stretch; }
  .footer .btn { width: 100%; font-size: 15px; padding: 14px; }
}

.btn { padding: 12px 28px; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; border: none; }
.btn-primary { background: var(--primary); color: white; }
.btn-outline { background: transparent; border: 2px solid var(--border); color: var(--text-muted); }

#result {
  margin-top: 24px;
  padding: 24px;
  border-radius: 12px;
  background: #f8fafc;
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

/* RESPONSIVE CSS MUST BE LAST IN CASCADE */
@media (max-width: 900px) {
  .main-container { flex-direction: column; overflow: visible; display: block; height: auto; }
  .left-panel, .right-panel { width: 100%; height: auto; flex: none; border-right: none; overflow: visible; }
  .left-panel { border-bottom: 2px solid var(--border); min-height: auto; max-height: 35vh; overflow-y: auto; }
  .right-panel { padding: 12px; min-height: auto; }
  .tabs-row { flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 4px; }
  
  /* Vertical "Hourglass" Layout for Ribbon Tie */
  .diagram-wrapper {
      flex-direction: column;
      overflow-x: hidden;
      justify-content: flex-start;
      gap: 20px;
      padding-bottom: 12px;
      width: 100%;
  }
  .diagram-col, .diagram-col[style*="flex: 1.2"] {
      width: 100%;
      min-width: unset;
      flex: 0 0 auto;
      flex-direction: row;
      flex-wrap: wrap;
      justify-content: center;
      gap: 12px;
  }
  .diagram-col .col-label {
      width: 100%;
      text-align: center;
      margin-bottom: 2px;
  }
  .diagram-col .dropzone {
      flex: 1 1 42%;
      max-width: 48%;
      min-height: 60px;
      padding: 6px;
      font-size: 11px;
      border-radius: 8px;
  }
  .diagram-col[style*="flex: 1.2"] .dropzone {
      flex: 1 1 80%;
      max-width: 80%;
      min-height: 70px;
  }
  
  .banks-container { grid-template-columns: 1fr; gap: 12px; padding: 16px; }
  .app-container { overflow-y: visible; height: auto; min-height: 100%; display: block; }
  body { overflow: auto; height: auto; min-height: 100%; display: block; }
}

@media (max-width: 480px) {
  .diagram-wrapper { gap: 16px; padding-bottom: 8px; }
  .diagram-col .dropzone { min-height: 50px; font-size: 10px; }
  .diagram-col[style*="flex: 1.2"] .dropzone { min-height: 60px; font-size: 11px; }
  .choice-token { padding: 8px 10px; font-size: 11.5px; border-radius: 8px; }
  .col-label { font-size: 10px; }
  .bank-header { font-size: 10px; }
  .banks-container { padding: 12px; border-radius: 12px; }
  .question-header { font-size: 14px; margin-bottom: 16px; line-height: 1.5; }
  .tab-btn { padding: 8px 10px; font-size: 11px; white-space: nowrap; flex-shrink: 0; }
  .clinical-record { padding: 10px 12px; font-size: 13px; line-height: 1.4; }
}
</style>
</head>
<body>

<div class="app-container">
    <div class="main-container">
        <!-- Exhibit -->
        <div class="left-panel">
            <div class="panel-title">Clinical History</div>
            <div class="tabs-row">
                <div class="tab-btn active" data-tab="nnotes">Nurses' Notes</div>
                <div class="tab-btn" data-tab="vsigns">Vital Signs</div>
                <div class="tab-btn" data-tab="diags">Diagnostics</div>
            </div>
            <div class="tab-content-area">
                <div id="nnotes" class="tab-pane">
                    <?php foreach($nursesNotes as $n) echo "<div class='clinical-record'>".htmlspecialchars($n)."</div>"; ?>
                </div>
                <div id="vsigns" class="tab-pane" style="display:none;">
                    <?php foreach($vitalSigns as $v) echo "<div class='clinical-record'>".htmlspecialchars($v)."</div>"; ?>
                </div>
                <div id="diags" class="tab-pane" style="display:none;">
                    <?php foreach($diagnostics as $d) echo "<div class='clinical-record'>".htmlspecialchars($d)."</div>"; ?>
                </div>
            </div>
        </div>

        <!-- Question Area -->
        <div class="right-panel">
            <div class="previous-badge" id="prevBadge">
                <i class="fas fa-lock"></i> This Bow-Tie has been submitted and is now read-only.
            </div>

            <div class="question-header">
                <?= nl2br(htmlspecialchars($data['question'])) ?>
            </div>

            <div class="diagram-wrapper">
                <div class="diagram-col">
                    <div class="col-label">Actions to Take</div>
                    <div class="dropzone" data-type="action"><span>Drop Action Here</span></div>
                    <div class="dropzone" data-type="action"><span>Drop Action Here</span></div>
                </div>
                <div class="diagram-col" style="flex: 1.2;">
                    <div class="col-label">Condition</div>
                    <div class="dropzone center-slot" data-type="condition"><span>Drop Condition Here</span></div>
                </div>
                <div class="diagram-col">
                    <div class="col-label">Monitor Parameters</div>
                    <div class="dropzone" data-type="parameter"><span>Drop Parameter Here</span></div>
                    <div class="dropzone" data-type="parameter"><span>Drop Parameter Here</span></div>
                </div>
            </div>

            <div class="banks-container">
                <div class="bank-col">
                    <div class="bank-header">Actions Bank</div>
                    <div class="bank-list" data-type="action">
                        <?php foreach($actions as $a) echo "<div class='choice-token' draggable='true' data-type='action'>".htmlspecialchars($a['text'])."</div>"; ?>
                    </div>
                </div>
                <div class="bank-col">
                    <div class="bank-header">Conditions Bank</div>
                    <div class="bank-list" data-type="condition">
                        <?php foreach($conditions as $c) echo "<div class='choice-token' draggable='true' data-type='condition'>".htmlspecialchars($c['text'])."</div>"; ?>
                    </div>
                </div>
                <div class="bank-col">
                    <div class="bank-header">Parameters Bank</div>
                    <div class="bank-list" data-type="parameter">
                        <?php foreach($parameters as $p) echo "<div class='choice-token' draggable='true' data-type='parameter'>".htmlspecialchars($p['text'])."</div>"; ?>
                    </div>
                </div>
            </div>

            <div id="result">
                <div id="scoreSummary" style="font-weight:800; font-size:18px; margin-bottom:12px;"></div>
                <div style="font-weight:800; color:var(--text-muted); font-size:11px; text-transform:uppercase; margin-bottom:8px;">Rationale</div>
                <div id="rationaleContent" style="font-size:14px; line-height:1.6; color:#475569;"></div>
            </div>
        </div>
    </div>

    <div class="footer">
        <button id="submitBtn" class="btn btn-primary">Submit Bow-Tie</button>
    </div>
</div>

<script>
$(document).ready(function(){
    const correctA = <?= json_encode($correctActions) ?>;
    const correctC = <?= json_encode($correctConditions) ?>;
    const correctP = <?= json_encode($correctParameters) ?>;
    const rationale = <?= json_encode($rationale) ?>;
    let isReviewMode = false;
    let initialAnswers = {};
    let hasInteracted = false;
    
    // Capture initial state on page load (for fresh exams)
    function captureInitialState() {
        if(Object.keys(initialAnswers).length === 0) {
            let a = {}, c = {}, p = {};
            let idx = 0;
            $('.dropzone[data-type="action"]').each(function(){ a[idx++] = $(this).find('.choice-token').text().trim(); });
            idx = 0;
            $('.dropzone[data-type="condition"]').each(function(){ c[idx++] = $(this).find('.choice-token').text().trim(); });
            idx = 0;
            $('.dropzone[data-type="parameter"]').each(function(){ p[idx++] = $(this).find('.choice-token').text().trim(); });
            initialAnswers = {actions: a, conditions: c, parameters: p};
        }
    }
    setTimeout(captureInitialState, 50);
    
    // Tabs
    $('.tab-btn').click(function(){
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.tab-pane').hide();
        $('#' + $(this).data('tab')).show();
    });

    // Drag Handle
    let dragged = null;
    $(document).on('dragstart', '.choice-token', function(e){
        dragged = this;
        e.originalEvent.dataTransfer.setData('text/plain', ''); 
    });

    $('.dropzone').on('dragover', function(e){ e.preventDefault(); });
    $('.dropzone').on('drop', function(e){
        e.preventDefault();
        if(isReviewMode) return; // review mode
        if(!dragged) return;
        
        hasInteracted = true;  // Mark that user has interacted
        
        if(dragged.dataset.type !== this.dataset.type) {
            Swal.fire({ icon:'error', title:'Wrong Section', text:'This item belongs in the ' + dragged.dataset.type + ' section.' });
            return;
        }
        
        let existing = $(this).find('.choice-token');
        if(existing.length > 0) $(`.bank-list[data-type="${dragged.dataset.type}"]`).append(existing);
        
        $(this).find('span').hide();
        $(this).append(dragged);
    });

    $('.bank-list').on('dragover', function(e){ e.preventDefault(); });
    $('.bank-list').on('drop', function(e){
        e.preventDefault();
        if(dragged && dragged.dataset.type === this.dataset.type) {
            $(this).append(dragged);
            $('.dropzone').each(function(){
                if($(this).find('.choice-token').length === 0) $(this).find('span').show();
            });
        }
    });

    function reveal(summary, prevInitial = null) {
        $('.dropzone').removeClass('correct-reveal wrong-reveal omitted-reveal');
        const displayInitial = (prevInitial && Object.keys(prevInitial).length > 0) ? prevInitial : initialAnswers;
        
        $('.dropzone').each(function(){
            let token = $(this).find('.choice-token');
            if(token.length === 0) return;
            let txt = token.text().trim();
            let type = this.dataset.type;
            let checkList = (type === 'action') ? correctA : (type === 'condition' ? correctC : correctP);
            
            // Check if this was in initial but not final
            if(displayInitial && displayInitial[type] && displayInitial[type] !== txt && displayInitial[type]) {
                $(this).addClass('omitted-reveal');
            } else if(checkList.includes(txt)) {
                $(this).addClass('correct-reveal');
            } else {
                $(this).addClass('wrong-reveal');
            }
        });

        $('#scoreSummary').html(summary);
        $('#rationaleContent').html(rationale || "No rationale provided.");
        $('#result').fadeIn();
        $('.choice-token').attr('draggable', false);
        $('#submitBtn').hide();
    }



    window.addEventListener('message', (e) => {
        if(e.data.type === 'prefill' || e.data.type === 'previous') {
            isReviewMode = e.data.isReview ?? false;
            const ans = e.data.answer || {};
            let prevInitial = e.data.initial_answer || {};
            
            // Handle JSON-encoded initial_answer from database
            if(typeof prevInitial === 'string') {
                try { prevInitial = JSON.parse(prevInitial); } catch(er) { prevInitial = {}; }
            }
            
            let filled = false;
            
            // Track initial answers
            initialAnswers = Object.keys(prevInitial).length > 0 ? prevInitial : ans;
            
            // Restoring answers globally with improved matching
            const types = ['action','condition','parameter'];
            types.forEach(type => {
                let vals = ans[type+'s'] || ans[type] || [];
                if (!Array.isArray(vals)) vals = [vals]; // Handle single values

                vals.forEach((v, i) => {
                    if(!v) return;
                    filled = true;
                    // Normalized match: trim and lowercase
                    let matchVal = v.toString().trim().toLowerCase();
                    
                    let targetZone = $(`.dropzone[data-type="${type}"]:eq(${i})`);
                    let token = $(`.choice-token[data-type="${type}"]`).filter(function(){ 
                        return $(this).text().trim().toLowerCase() === matchVal; 
                    }).first();
                    
                    if(token.length && targetZone.length) {
                        targetZone.find('span').hide();
                        targetZone.empty().append(token); // Ensure slot is clean
                    }
                });
            });

            if(filled) {
                $('#prevBadge').show();
                if(e.data.showRationale) {
                    let s_val = parseFloat(e.data.score || 0);
                    let e_pts = e.data.earned_points || Math.round(s_val * 5);
                    reveal("Score: " + Math.round(s_val*100) + "% ("+e_pts+"/5 pts)", prevInitial);
                }
            }
        }
    });

    $('#submitBtn').click(function(){
        if(isReviewMode) return; // Prevent resubmission in review mode
        
        let incomplete = false;
        $('.dropzone').each(function(){ if($(this).find('.choice-token').length === 0) incomplete = true; });
        if(incomplete) {
            Swal.fire({ icon:'warning', title:'Incomplete', text:'Please fill all slots in the diagram.' });
            return;
        }
        
        // Capture initial if not done yet (safety net)
        if(!hasInteracted){
            hasInteracted = true;
            let a=[], c=[], p=[];
            $('.dropzone[data-type="action"]').each(function(){ a.push($(this).find('.choice-token').text().trim()); });
            $('.dropzone[data-type="condition"]').each(function(){ c.push($(this).find('.choice-token').text().trim()); });
            $('.dropzone[data-type="parameter"]').each(function(){ p.push($(this).find('.choice-token').text().trim()); });
            initialAnswers = {actions: a, conditions: c, parameters: p};
        }

        let earned = 0;
        $('.dropzone').each(function(){
            let txt = $(this).find('.choice-token').text().trim().toLowerCase();
            let type = this.dataset.type;
            let list = (type === 'action') ? correctA : (type === 'condition' ? correctC : correctP);
            
            // Normalize correct list as well
            if(list.map(s => s.toString().trim().toLowerCase()).includes(txt)) earned++;
        });

        let total = 5;
        let normalized = parseFloat((earned / total).toFixed(2));
        
        let userA = [], userC = [], userP = [];
        $('.dropzone[data-type="action"]').each(function(){ userA.push($(this).find('.choice-token').text().trim()); });
        $('.dropzone[data-type="condition"]').each(function(){ userC.push($(this).find('.choice-token').text().trim()); });
        $('.dropzone[data-type="parameter"]').each(function(){ userP.push($(this).find('.choice-token').text().trim()); });
        
        let selected = {actions: userA, conditions: userC, parameters: userP};
        
        reveal("Score: " + Math.round(normalized*100) + "% ("+earned+"/"+total+" pts)");
        
        // Calculate changes
        let changesData = null;
        if(JSON.stringify(initialAnswers) !== JSON.stringify(selected)){
            changesData = {
                modified_count: 1,
                changed: true
            };
        }

        window.parent.postMessage({
            type: 'answered',
            answer: selected,
            initial_answer: Object.keys(initialAnswers).length > 0 ? initialAnswers : null,
            correctAnswer: {actions: correctA, conditions: correctC, parameters: correctP},
            correct: earned === total,
            score: normalized,
            max_points: total,
            earned_points: earned,
            changes: changesData,
            rationale: rationale,
            question_id: <?= json_encode($data['id']) ?>,
            question_type: 'bowtie'
        }, '*');
    });

    // ===== AUTO-SCROLL FOR DRAG & DROP ON MOBILE =====
    let isDragging = false;
    let autoScrollInterval = null;

    // Detect when dragging starts
    $(document).on('dragstart', '.choice-token', function(e){
        isDragging = true;
    });

    // Detect when dragging ends
    $(document).on('dragend', '.choice-token', function(e){
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
});
</script>
</body>
</html>