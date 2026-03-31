<?php
require_once '../../../../config.php';
// session_start handled by config.php

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id > 0) {
    $q = mysqli_query($con, "SELECT * FROM mmr WHERE id = $id LIMIT 1");
} else {
    $q = mysqli_query($con, "SELECT * FROM mmr ORDER BY RAND() LIMIT 1");
}
$data = mysqli_fetch_assoc($q);
if (!$data) {
    die('<div style="font-family: Arial; padding: 20px;">No MMR question found.</div>');
}

$columns = json_decode($data['columns'], true) ?? [];
$rows    = json_decode($data['rows'], true) ?? [];
$nurses_notes = json_decode($data['nurses_notes'], true) ?? [];
$vital_signs  = json_decode($data['vital_signs'], true) ?? [];
$diagnostics   = json_decode($data['diagnostics'], true) ?? [];
$correct       = json_decode($data['correct'], true) ?? [];
$rationale     = $data['rationale'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MMR Question</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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

html, body { height: 100%; margin: 0; padding: 0; overflow-x: hidden; }
body {
  font-family: 'Inter', sans-serif;
  background: var(--bg);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  max-width: 100vw;
}

.app-container {
  display: flex;
  flex-direction: column;
  height: 100%;
  max-width: 100%;
  overflow-x: hidden;
}

.main-content {
  display: flex;
  flex: 1;
  overflow: hidden;
  max-width: 100%;
}


/* RESPONSIVE */
/* Moved responsive block to bottom of cascade */

/* LEFT PANEL - CLINICAL DATA */
.left-panel {
  width: 45%;
  background: var(--surface);
  border-right: 2px solid var(--border);
  display: flex;
  flex-direction: column;
}

.panel-header {
  padding: 16px 20px;
  background: #f1f5f9;
  border-bottom: 1px solid var(--border);
  font-weight: 700;
  font-size: 13px;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.tabs-nav {
  display: flex;
  padding: 12px 12px 0;
  gap: 4px;
  border-bottom: 1px solid var(--border);
}

.tab-btn {
  padding: 10px 16px;
  font-size: 13px;
  font-weight: 600;
  color: var(--text-muted);
  cursor: pointer;
  border-radius: 8px 8px 0 0;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.tab-btn:hover { background: #f8fafc; color: var(--text); }
.tab-btn.active {
  background: var(--surface);
  color: var(--accent);
  border: 1px solid var(--border);
  border-bottom-color: var(--surface);
  margin-bottom: -1px;
}

.tab-content-area {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
}

.clinical-list {
  list-style: none;
}

.clinical-list li {
  padding: 12px 16px;
  background: #f8fafc;
  border-radius: 8px;
  margin-bottom: 10px;
  font-size: 14px;
  line-height: 1.5;
  border: 1px solid #f1f5f9;
}

/* RIGHT PANEL - MATRIX */
.right-panel {
  width: 55%;
  background: white;
  overflow-y: auto;
  padding: 32px;
}

.matrix-card {
  max-width: 100%;
}

.question-title {
  font-size: 18px;
  font-weight: 700;
  line-height: 1.6;
  margin-bottom: 24px;
  color: var(--primary);
}

.matrix-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  border: 1px solid var(--border);
  border-radius: 12px;
  overflow: hidden;
}

.matrix-table th {
  background: #f8fafc;
  padding: 16px;
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
  color: var(--text-muted);
  border-bottom: 1px solid var(--border);
  border-right: 1px solid var(--border);
  text-align: center;
}

.matrix-table td {
  padding: 12px;
  border-bottom: 1px solid var(--border);
  border-right: 1px solid var(--border);
  text-align: center;
}

.matrix-table td:first-child {
  text-align: left;
  font-weight: 600;
  font-size: 14px;
  color: var(--text);
  background: #fcfcfd;
  width: 40%;
}

.matrix-table tr:last-child td { border-bottom: none; }
.matrix-table th:last-child, .matrix-table td:last-child { border-right: none; }

.matrix-checkbox {
  width: 22px;
  height: 22px;
  cursor: pointer;
}

/* Reveal Colors */
.cell-correct { background-color: #ecfdf5 !important; }
.cell-wrong { background-color: #fef2f2 !important; }
.cell-omitted { background-color: #fffbeb !important; text-decoration: line-through; opacity: 0.75; }

/* FOOTER */
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

.btn {
  padding: 12px 28px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary { background: var(--primary); color: white; }
.btn-primary:hover { background: #1e293b; }
.btn-outline { background: transparent; border: 2px solid var(--border); color: var(--text-muted); }

#result {
  margin-top: 24px;
  padding: 20px;
  background: #fafafa;
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

/* RESPONSIVE CSS MUST BE LAST IN CASCADE */
@media (max-width: 900px) {
  .main-content { flex-direction: column; overflow: visible; display: block; height: auto; }
  .left-panel, .right-panel { width: 100%; height: auto; flex: none; border-right: none; overflow: visible; }
  .left-panel { border-bottom: 2px solid var(--border); min-height: auto; max-height: 35vh; overflow-y: auto; }
  .right-panel { padding: 12px; min-height: auto; }
  .matrix-table { display: block; overflow-x: auto; -webkit-overflow-scrolling: touch; font-size: 13px; }
  .matrix-table th { padding: 10px 8px; font-size: 10px; }
  .matrix-table td { padding: 10px 8px; }
  .matrix-table td:first-child { width: auto; min-width: 140px; font-size: 13px; }
  .question-title { font-size: 14px; margin-bottom: 16px; line-height: 1.5; }
  .tabs-nav { flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 4px; }
  .tab-btn { padding: 8px 10px; font-size: 11px; white-space: nowrap; flex-shrink: 0; }
  .clinical-list li { padding: 10px 12px; font-size: 13px; }
  body { overflow: auto; height: auto; min-height: 100%; display: block; }
  .app-container { overflow-y: visible; height: auto; min-height: 100%; display: block; }
}
</style>
</head>
<body>

<div class="app-container">
    <div class="main-content">
        <!-- Clinical Panel -->
        <div class="left-panel">
            <div class="panel-header">Client Records</div>
            <div class="tabs-nav">
                <div class="tab-btn active" data-tab="notes">Nurse Notes</div>
                <div class="tab-btn" data-tab="vitals">Vital Signs</div>
                <div class="tab-btn" data-tab="diagnostics">Diagnostics</div>
            </div>
            <div class="tab-content-area">
                <div id="notes" class="tab-pane">
                    <ul class="clinical-list">
                        <?php foreach($nurses_notes as $n) echo "<li>" . htmlspecialchars($n) . "</li>"; ?>
                    </ul>
                </div>
                <div id="vitals" class="tab-pane" style="display:none;">
                    <ul class="clinical-list">
                        <?php foreach($vital_signs as $v) echo "<li>" . htmlspecialchars($v) . "</li>"; ?>
                    </ul>
                </div>
                <div id="diagnostics" class="tab-pane" style="display:none;">
                    <ul class="clinical-list">
                        <?php foreach($diagnostics as $d) echo "<li>" . htmlspecialchars($d) . "</li>"; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Question Panel -->
        <div class="right-panel">
            <div class="previous-badge" id="prevBadge">
                <i class="fas fa-lock"></i> This matrix has been submitted and is now read-only.
            </div>

            <div class="matrix-card">
                <h2 class="question-title"><?= nl2br(htmlspecialchars($data['question'])) ?></h2>
                
                <form id="mmrForm">
                    <table class="matrix-table">
                        <thead>
                            <tr>
                                <th>Parameter / Finding</th>
                                <?php foreach($columns as $col) echo "<th>" . htmlspecialchars($col) . "</th>"; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rows as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row) ?></td>
                                <?php foreach($columns as $col): ?>
                                <td>
                                    <input type="checkbox" class="matrix-checkbox" name="<?= htmlspecialchars($col) ?>[]" value="<?= htmlspecialchars($row) ?>">
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>

                <div id="result">
                    <div style="font-weight:800; color:var(--text-muted); font-size:12px; margin-bottom:8px; text-transform:uppercase;">Analysis & Rationale</div>
                    <div id="resSummary" style="font-weight:700; color:var(--text); margin-bottom:12px;"></div>
                    <div id="rationaleText" style="line-height:1.6; color:var(--text); font-size:14px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <button id="submitBtn" class="btn btn-primary">Submit Matrix</button>
    </div>
</div>

<script>
$(document).ready(function(){
    const columns = <?= json_encode($columns) ?>;
    const correct = <?= json_encode($correct) ?>;
    const rationale = <?= json_encode($rationale) ?>;
    let isReviewMode = false;
    let initialAnswers = {};
    let hasInteracted = false;
    
    // Capture initial state on page load (for fresh exams)
    function captureInitialState() {
        if(Object.keys(initialAnswers).length === 0) {
            let init = {};
            columns.forEach(col => {
                init[col] = [];
                $(`input[name="${col}[]"]:checked`).each(function(){
                    init[col].push($(this).val());
                });
            });
            initialAnswers = init;
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
    
    // Prevent checkbox changes in review mode
    $('input.matrix-checkbox').click(function(e){
        if(isReviewMode) {
            e.preventDefault();
            return false;
        }
        
        hasInteracted = true;  // Mark that user has interacted
    });

    function showResult(scoreHeader, prevAnswers = {}) {
        $('.matrix-table td').removeClass('cell-correct cell-wrong cell-omitted');
        
        const displayInitial = Object.keys(prevAnswers).length > 0 ? prevAnswers : initialAnswers;
        
        // Use the same robust key matching as in the scoring logic
        const getCorrectList = (colName) => {
            const key = Object.keys(correct).find(k => k.trim().toLowerCase() === colName.trim().toLowerCase());
            return key ? correct[key] : [];
        };

        columns.forEach(col => {
            const list = getCorrectList(col);
            const listLower = list.map(s => s.toString().trim().toLowerCase());
            const initialList = displayInitial[col] || [];
            const initialLower = initialList.map(s => s.toString().trim().toLowerCase());

            $(`input[name="${col}[]"]`).each(function(){
                let val = $(this).val().trim().toLowerCase();
                let parent = $(this).parent();
                let isCorrect = listLower.includes(val);
                let wasInitial = initialLower.includes(val);
                let isNowChecked = $(this).is(':checked');
                
                // Show omitted if was checked initially but not now
                if(wasInitial && !isNowChecked) {
                    parent.addClass('cell-omitted');
                } else if(isCorrect) {
                    parent.addClass('cell-correct');
                } else if(isNowChecked) {
                    parent.addClass('cell-wrong');
                }
            });
        });

        $('#resSummary').html(scoreHeader);
        $('#rationaleText').html(rationale || "No rationale provided.");
        $('#result').fadeIn();
        
        $('input.matrix-checkbox').prop('disabled', true);
        $('#submitBtn').hide();
    }



    window.addEventListener('message', (event) => {
        if(event.data.type === 'prefill' || event.data.type === 'previous'){
            isReviewMode = event.data.isReview ?? false;
            const prev = event.data.answer || {};
            const prevInitial = event.data.initial_answer || {};
            
            let hasAnswers = false;
            Object.keys(prev).forEach(col => {
                if(prev[col].length > 0) hasAnswers = true;
                prev[col].forEach(val => {
                    $(`input[name="${col}[]"][value="${val}"]`).prop('checked', true);
                });
            });
            
            // Track initial answers
            initialAnswers = Object.keys(prevInitial).length > 0 ? prevInitial : prev;

            if(hasAnswers) {
                $('#prevBadge').show();
                if(event.data.showRationale) {
                    let score = event.data.score || 0;
                    let earned = event.data.earned_points || 0;
                    let max = event.data.max_points || 0;
                    showResult("Score: " + Math.round(score*100) + "% ("+earned+"/"+max+" pts)", prevInitial);
                }
            }
        }
    });

    $('#submitBtn').click(function(){
        if(isReviewMode) return; // Prevent resubmission in review mode
        
        let valid = true;
        columns.forEach(col => {
            if($(`input[name="${col}[]"]:checked`).length === 0) valid = false;
        });

        if(!valid) {
            Swal.fire({ icon:'error', title:'Incomplete', text:'Please select at least one row per column.' });
            return;
        }

        let userAns = {};
        columns.forEach(col => {
            userAns[col] = [];
            $(`input[name="${col}[]"]:checked`).each(function(){ userAns[col].push($(this).val()); });
        });
        
        // Capture initial if not done yet (safety net)
        if(Object.keys(initialAnswers).length === 0){
            initialAnswers = JSON.parse(JSON.stringify(userAns));
        }

        let earned = 0;
        let totalMax = 0;
        
        // Use a more robust case-insensitive key matching for columns
        const getCorrectList = (colName) => {
            const key = Object.keys(correct).find(k => k.trim().toLowerCase() === colName.trim().toLowerCase());
            return key ? correct[key] : [];
        };

        columns.forEach(col => {
            const list = getCorrectList(col);
            totalMax += list.length;
            
            $(`input[name="${col}[]"]`).each(function(){
                let val = $(this).val().trim().toLowerCase();
                let checked = $(this).is(':checked');
                let isCorrect = list.map(s => s.toString().trim().toLowerCase()).includes(val);
                
                if(isCorrect && checked) earned++;
                else if(!isCorrect && checked) earned--;
            });
        });

        earned = Math.max(0, earned);
        let normalized = totalMax > 0 ? parseFloat((earned / totalMax).toFixed(2)) : 0;

        showResult("Score: " + Math.round(normalized*100) + "% ("+earned+"/"+totalMax+" pts)");
        
        // Calculate changes
        let changesData = null;
        if(JSON.stringify(initialAnswers) !== JSON.stringify(userAns)){
            changesData = {
                modified_count: 1,
                changed: true
            };
        }

        window.parent.postMessage({
            type: 'answered',
            answer: userAns,
            initial_answer: Object.keys(initialAnswers).length > 0 ? initialAnswers : null,
            correctAnswer: correct,
            correct: earned === totalMax,
            score: normalized,
            max_points: totalMax,
            earned_points: earned,
            changes: changesData,
            rationale: rationale,
            topic: <?= json_encode($data['topic'] ?? 'General') ?>,
            system: <?= json_encode($data['system'] ?? 'N/A') ?>,
            cnc: <?= json_encode($data['cnc'] ?? 'N/A') ?>,
            dlevel: <?= json_encode($data['dlevel'] ?? 'N/A') ?>,
            question_id: <?= json_encode($data['id']) ?>,
            question_type: 'mmr'
        }, '*');
    });
});
</script>
</body>
</html>