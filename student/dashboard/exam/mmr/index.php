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

$tabs_data = json_decode(($data['tabs'] ?? '') ?: '[]', true) ?: [];
if (empty($tabs_data)) {
    $nn = json_decode(($data['nurses_notes'] ?? '') ?: '[]', true) ?: [];
    $vs = json_decode(($data['vital_signs']  ?? '') ?: '[]', true) ?: [];
    $dx = json_decode(($data['diagnostics']  ?? '') ?: '[]', true) ?: [];
    if (!empty($nn))  $tabs_data[] = ['title' => 'Nurse Notes',  'content' => $nn];
    if (!empty($vs))  $tabs_data[] = ['title' => 'Vital Signs',  'content' => $vs];
    if (!empty($dx))  $tabs_data[] = ['title' => 'Diagnostics',  'content' => $dx];
}
$hasTabs = !empty($tabs_data);

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
body { font-family: 'Inter', sans-serif; background: var(--bg); display: flex; flex-direction: column; overflow: hidden; max-width: 100vw; }

.app-container { display: flex; flex-direction: column; height: 100%; max-width: 100%; overflow-x: hidden; }
.main-content { display: flex; flex: 1; overflow: hidden; max-width: 100%; }

.left-panel { width: 45%; background: var(--surface); border-right: 2px solid var(--border); display: flex; flex-direction: column; }
.panel-header { padding: 16px 20px; background: #f1f5f9; border-bottom: 1px solid var(--border); font-weight: 700; font-size: 13px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
.tabs-nav { display: flex; padding: 12px 12px 0; gap: 4px; border-bottom: 1px solid var(--border); }
.tab-btn { padding: 10px 16px; font-size: 13px; font-weight: 600; color: var(--text-muted); cursor: pointer; border-radius: 8px 8px 0 0; transition: all 0.2s; border: 1px solid transparent; }
.tab-btn:hover { background: #f8fafc; color: var(--text); }
.tab-btn.active { background: var(--surface); color: var(--accent); border: 1px solid var(--border); border-bottom-color: var(--surface); margin-bottom: -1px; }
.tab-content-area { flex: 1; overflow-y: auto; padding: 24px; }
.clinical-list { list-style: none; }
.clinical-list li { padding: 12px 16px; background: #f8fafc; border-radius: 8px; margin-bottom: 10px; font-size: 14px; line-height: 1.5; border: 1px solid #f1f5f9; }

.right-panel { width: 55%; background: white; overflow-y: auto; padding: 32px; }
.matrix-card { max-width: 100%; }
.question-title { font-size: 18px; font-weight: 700; line-height: 1.6; margin-bottom: 24px; color: var(--primary); }

.matrix-table { width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
.matrix-table th { background: #f8fafc; padding: 16px; font-size: 12px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); border-bottom: 1px solid var(--border); border-right: 1px solid var(--border); text-align: center; }
.matrix-table td { padding: 12px; border-bottom: 1px solid var(--border); border-right: 1px solid var(--border); text-align: center; }
.matrix-table td:first-child { text-align: left; font-size: 14px; color: var(--text); background: #fcfcfd; width: 40%; }
.matrix-table tr:last-child td { border-bottom: none; }
.matrix-table th:last-child, .matrix-table td:last-child { border-right: none; }
.matrix-checkbox { width: 22px; height: 22px; cursor: pointer; }

.footer { position: sticky; bottom: 0; background: white; border-top: 1px solid var(--border); padding: 16px 0 20px; display: flex; justify-content: center; z-index: 10; }
@media (max-width: 600px) { .footer { padding: 16px; } .footer .btn { min-width: 0; width: 100%; font-size: 15px; } }

.btn { padding: 14px 40px; border-radius: 12px; font-weight: 800; font-size: 15px; cursor: pointer; border: none; transition: all .2s ease; min-width: 220px; display: flex; align-items: center; justify-content: center; gap: 10px; }
.btn-primary { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; box-shadow: 0 4px 14px rgba(59,130,246,.4); }
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59,130,246,.5); }
.btn-primary:disabled { opacity: .45; cursor: not-allowed; transform: none !important; box-shadow: none; }

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

        <div class="right-panel" <?= !$hasTabs ? 'style="width:100%;"' : '' ?>>
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
            </div>
        </div>
    </div>

    <div class="footer">
        <button id="submitBtn" class="btn btn-primary"><i class="fas fa-check-circle"></i> Submit Matrix</button>
    </div>
</div>

<script>
$(document).ready(function(){
    const columns = <?= json_encode($columns) ?>;
    const correct = <?= json_encode($correct) ?>;
    const rationale = <?= json_encode($rationale) ?>;
    let locked = false;
    let initialAnswers = {};
    let hasInteracted = false;

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

    $('.tab-btn').click(function(){
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.tab-pane').hide();
        $('#' + $(this).data('tab')).show();
    });

    $('input.matrix-checkbox').click(function(){
        if(locked) { return false; }
        if(!hasInteracted) {
            hasInteracted = true;
            columns.forEach(col => {
                initialAnswers[col] = [];
                $(`input[name="${col}[]"]:checked`).each(function(){ initialAnswers[col].push($(this).val()); });
            });
        }
    });

    function setReadOnlyState() {
        $('input.matrix-checkbox').prop('disabled', true);
        $('#submitBtn').prop('disabled', true).hide();
        locked = true;
    }

    $('#submitBtn').click(function(){
        if(locked) return;

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

        if(Object.keys(initialAnswers).length === 0){
            initialAnswers = JSON.parse(JSON.stringify(userAns));
        }

        const getCorrectList = (colName) => {
            const key = Object.keys(correct).find(k => k.trim().toLowerCase() === colName.trim().toLowerCase());
            return key ? correct[key] : [];
        };

        let earned = 0, totalMax = 0;
        columns.forEach(col => {
            const list = getCorrectList(col);
            totalMax += list.length;
            $(`input[name="${col}[]"]`).each(function(){
                let val = $(this).val().trim().toLowerCase();
                let checked = $(this).is(':checked');
                let isCorr = list.map(s => s.toString().trim().toLowerCase()).includes(val);
                if(isCorr && checked) earned++;
                else if(!isCorr && checked) earned--;
            });
        });

        earned = Math.max(0, earned);
        let normalized = totalMax > 0 ? parseFloat((earned / totalMax).toFixed(2)) : 0;

        let changesData = null;
        if(JSON.stringify(initialAnswers) !== JSON.stringify(userAns)){
            changesData = { modified_count: 1, changed: true };
        }

        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting…');
        setReadOnlyState();

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
            topic:   <?= json_encode($data['topic']  ?? 'General') ?>,
            system:  <?= json_encode($data['system'] ?? 'N/A') ?>,
            cnc:     <?= json_encode($data['cnc']    ?? 'N/A') ?>,
            dlevel:  <?= json_encode($data['dlevel'] ?? 'N/A') ?>,
            question_id:   <?= json_encode($data['id']) ?>,
            question_type: 'mmr'
        }, '*');
    });
});
</script>
</body>
</html>
