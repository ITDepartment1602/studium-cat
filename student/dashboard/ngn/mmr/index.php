<?php
require_once '../../../../config.php';
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

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

$columns   = json_decode($data['columns'] ?: '[]', true) ?: [];
$rows      = json_decode($data['rows']    ?: '[]', true) ?: [];
$correct   = json_decode($data['correct'] ?: '{}', true) ?: [];
$rationale = $data['rationale'] ?? '';
$furtherinfo = $data['furtherinfo'] ?? '';
$image = $data['image'] ?? '';

// Dynamic clinical reference tabs from `tabs` DB field (spec §1.2)
// Fall back to the legacy individual columns if `tabs` field is absent/empty.
$tabs_data = json_decode(($data['tabs'] ?? '') ?: '[]', true) ?: [];
if (empty($tabs_data)) {
    // Legacy fallback: build tabs from old separate columns
    $nn = json_decode(($data['nurses_notes'] ?? '') ?: '[]', true) ?: [];
    $vs = json_decode(($data['vital_signs']  ?? '') ?: '[]', true) ?: [];
    $dx = json_decode(($data['diagnostics']  ?? '') ?: '[]', true) ?: [];
    if (!empty($nn))  $tabs_data[] = ['title' => 'Nurse Notes',  'content' => $nn];
    if (!empty($vs))  $tabs_data[] = ['title' => 'Vital Signs',  'content' => $vs];
    if (!empty($dx))  $tabs_data[] = ['title' => 'Diagnostics',  'content' => $dx];
}
$hasTabs = !empty($tabs_data);

// Fetch Stats
$topic = $data['topic'] ?? 'General';
$system = $data['system'] ?? 'N/A';
$cnc = $data['cnc'] ?? 'N/A';
$dlevel = $data['dlevel'] ?? 'N/A';
$concept = $data['concept'] ?? 'General';
$narcan = $data['narcan'] ?? 'N/A';
$q_uid = 'mmr_' . $data['id'];
$peer_q = mysqli_query($con, "SELECT AVG(isCorrect) * 100 as avg_score FROM exam_results WHERE question_uid = '$q_uid'");
$peer_data = mysqli_fetch_assoc($peer_q);
$avg_peer_score = $peer_data['avg_score'] ? round($peer_data['avg_score'], 1) . '%' : 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MMR Question</title>
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
  font-size: 14px;
  color: var(--text);
  background: #fcfcfd;
  width: 40%;
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
.stats-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
}
.stats-btn i { font-size: 14px; color: #3b82f6; }

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
        <!-- Clinical Panel — shown only when the question has tabs data -->
        <?php if ($hasTabs): ?>
        <div class="left-panel">
            <div class="panel-header">Client Records</div>
            <div class="tabs-nav">
                <?php foreach ($tabs_data as $i => $tab): ?>
                <div class="tab-btn <?= $i === 0 ? 'active' : '' ?>" data-tab="tab-<?= $i ?>"><?= htmlspecialchars($tab['title']) ?></div>
                <?php endforeach; ?>
            </div>
            <div class="tab-content-area">
                <?php foreach ($tabs_data as $i => $tab): ?>
                <div id="tab-<?= $i ?>" class="tab-pane" <?= $i > 0 ? 'style="display:none;"' : '' ?>>
                    <ul class="clinical-list">
                        <?php foreach ((array)($tab['content'] ?? []) as $item): ?>
                            <li><?= htmlspecialchars($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Question Panel — expands to full width when no tabs -->
        <div class="right-panel" <?= !$hasTabs ? 'style="width:100%;"' : '' ?>>
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
    let rationale = <?= json_encode($rationale) ?>;
    let furtherinfo = <?= json_encode($furtherinfo) ?>;
    let image = <?= json_encode($image) ?>;

    /* Stats Data */
    const _qStartTime = Date.now();
    const questionStats = {
        difficulty: <?= json_encode($dlevel) ?>,
        peerScore: <?= json_encode($avg_peer_score) ?>,
        concept: <?= json_encode($concept) ?>,
        topic: <?= json_encode($topic) ?>,
        system: <?= json_encode($system) ?>,
        cnc: <?= json_encode($data['cnc'] ?? 'N/A') ?>,
        type: 'Matrix Multiple Response (MMR)'
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

    function showResult(scoreHeader, prevAnswers = {}, earnedPoints = 0, maxPoints = 0, isCorrect = false) {
        $('.matrix-table td').removeClass('cell-correct cell-wrong cell-omitted');

        // Use the same robust key matching as in the scoring logic
        const getCorrectList = (colName) => {
            const key = Object.keys(correct).find(k => k.trim().toLowerCase() === colName.trim().toLowerCase());
            return key ? correct[key] : [];
        };

        columns.forEach(col => {
            const list = getCorrectList(col);
            const listLower = list.map(s => s.toString().trim().toLowerCase());

            $(`input[name="${col}[]"]`).each(function(){
                let val = $(this).val().trim().toLowerCase();
                let parent = $(this).parent();
                let isCorrect = listLower.includes(val);
                let isNowChecked = $(this).is(':checked');

                if(isCorrect) {
                    parent.addClass('cell-correct');
                } else if(isNowChecked) {
                    parent.addClass('cell-wrong');
                }
            });
        });

        let summaryHtml = `
            <div style="display:flex; align-items:center; justify-content: space-between; width: 100%;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <i class="fas ${isCorrect ? 'fa-check-circle' : 'fa-times-circle'}" style="color:${isCorrect ? '#10b981' : '#ef4444'}; font-size:18px;"></i>
                    <span style="color:${isCorrect ? '#10b981' : '#ef4444'}; font-size:16px;">${scoreHeader}</span>
                </div>
                <button class="stats-btn" onclick="showStatsModal()">
                    <i class="fas fa-info-circle"></i> Question Info
                </button>
            </div>
        `;
        $('#resSummary').html(summaryHtml);
        
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

    // Signal parent that this iframe is ready to receive prefill data
    if (window.parent !== window) window.parent.postMessage({ type: 'ready' }, '*');

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