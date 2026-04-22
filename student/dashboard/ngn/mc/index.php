<?php
// multiplechoice/index.php

require_once '../../../../config.php';
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

/* =====================================================
   FETCH RANDOM QUESTION
===================================================== */

$q = mysqli_query($con, "SELECT * FROM mcq ORDER BY RAND() LIMIT 1");
$data = mysqli_fetch_assoc($q);

if (!$data) {
    die('<div style="font-family: Arial; padding: 20px;">
        No MCQ question found. Please add questions to the database.
    </div>');
}

/* =====================================================
   PARSE DATA
===================================================== */

$choices   = json_decode($data['choices'], true) ?? [];
$correct   = $data['correct'] ?? '';
$rationale = $data['rationale'] ?? '';

// Dynamic clinical reference tabs from `tabs` DB field (spec §1.2)
$tabs_data = json_decode(($data['tabs'] ?? '') ?: '[]', true) ?: [];
$hasTabs = !empty($tabs_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Multiple Choice Question</title>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
body{
    font-family: Arial, sans-serif;
    background:#f4f6f9;
    margin:0;
}

/* Two-panel layout */
.two-panel { display: flex; min-height: 100vh; overflow: hidden; }
.left-panel { width: 40%; min-width: 260px; background: #fff; border-right: 2px solid #e2e8f0; display: flex; flex-direction: column; flex-shrink: 0; overflow: hidden; }
.panel-title { padding: 14px 20px; background: #f1f5f9; font-weight: 800; font-size: 11px; text-transform: uppercase; color: #64748b; letter-spacing: 1px; border-bottom: 1px solid #e2e8f0; }
.tabs-row { display: flex; padding: 8px 12px 0; gap: 4px; border-bottom: 1px solid #e2e8f0; overflow-x: auto; flex-shrink: 0; }
.tab-btn { padding: 9px 14px; font-size: 13px; font-weight: 600; cursor: pointer; border-radius: 8px 8px 0 0; color: #64748b; white-space: nowrap; }
.tab-btn.active { background: #f8fafc; color: #3b82f6; border: 1px solid #e2e8f0; border-bottom-color: #f8fafc; margin-bottom: -1px; }
.tab-content-area { flex: 1; overflow-y: auto; padding: 16px; }
.clinical-record { background: #fdfdfd; border: 1px solid #f1f5f9; padding: 10px 14px; border-radius: 8px; margin-bottom: 8px; font-size: 14px; line-height: 1.5; }
.right-panel { flex: 1; overflow-y: auto; min-width: 0; }
@media (max-width: 900px) {
  .two-panel { flex-direction: column; height: auto; overflow: visible; }
  .left-panel { width: 100%; min-width: 0; border-right: none; border-bottom: 2px solid #e2e8f0; max-height: 35vh; overflow-y: auto; }
  .right-panel { width: 100% !important; overflow: visible; }
}

.container{
    max-width:800px;
    margin:40px auto;
    background:white;
    padding:25px;
    border-radius:6px;
}

.option{
    padding:12px;
    border:1px solid #ddd;
    border-radius:4px;
    margin-bottom:10px;
    cursor:pointer;
}

.option:hover{
    background:#f1f3f5;
}

.footer{
    margin-top:20px;
    text-align:right;
}

.btn{
    background:#003057;
    color:white;
    border:none;
    padding:10px 25px;
    cursor:pointer;
    border-radius:4px;
    font-weight:bold;
}

#result{
    display:none;
    margin-top:20px;
    padding:15px;
    background:#f8f9fa;
    border-left:4px solid #087f39;
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
    <div class="tab-btn <?= $i === 0 ? 'active' : '' ?>" data-tab="mctab-<?= $i ?>"><?= htmlspecialchars($tab['title']) ?></div>
    <?php endforeach; ?>
  </div>
  <div class="tab-content-area">
    <?php foreach ($tabs_data as $i => $tab): ?>
    <div id="mctab-<?= $i ?>" class="tab-pane" <?= $i > 0 ? 'style="display:none;"' : '' ?>>
      <?php foreach ((array)($tab['content'] ?? []) as $item): ?>
      <div class="clinical-record"><?= htmlspecialchars($item) ?></div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
<div class="right-panel" <?= !$hasTabs ? 'style="width:100%;"' : '' ?>>

<div class="container">

<h3><?= nl2br(htmlspecialchars($data['question'])) ?></h3>

<form id="mcqForm">

<?php foreach($choices as $choice): ?>
<div class="option">
    <label>
        <input type="radio" name="answer" value="<?= htmlspecialchars($choice) ?>">
        <?= htmlspecialchars($choice) ?>
    </label>
</div>
<?php endforeach; ?>

</form>

<div id="result"></div>

<div class="footer">
<button class="btn" id="submitBtn">Submit</button>
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

let correct = <?= json_encode($correct) ?>;
let rationale = <?= json_encode($rationale) ?>;

/* ================= SUBMIT ================= */
$('#submitBtn').click(function(){

    let selected = $('input[name="answer"]:checked').val();

    if(!selected){
        Swal.fire({
            icon: 'error',
            title: 'Select an answer'
        });
        return;
    }

    let isCorrect = (selected === correct);

/* ================= HIGHLIGHT ================= */
$('input[name="answer"]').each(function(){

    let val = $(this).val();
    let parent = $(this).closest('.option');

    if(val === correct){
        parent.css("background","#c8e6c9");
    }
    else if($(this).is(':checked') && val !== correct){
        parent.css("background","#ffcdd2");
    }

});

/* ================= RESULT ================= */
$('#result').html(`
    <div style="margin-bottom:10px;">
        <b>Result:</b> 
        <span style="color:${isCorrect ? 'green' : 'red'}; font-weight:bold;">
            ${isCorrect ? 'CORRECT' : 'INCORRECT'}
        </span>
    </div>

    <div style="margin-top:10px;">
        <b>Rationale:</b><br>
        ${rationale ? rationale : "No rationale provided."}
    </div>
`).fadeIn();

$('input').prop('disabled', true);
$('#submitBtn').hide();

/* ================= SEND TO PARENT ================= */
window.parent.postMessage({
    type: 'answered',
    answer: selected,
    correctAnswer: correct,
    correct: isCorrect,
    score: isCorrect ? 1 : 0,
    max_score: 1,
    question_id: <?= json_encode($data['id']) ?>,
    question_type: 'mcq'
}, '*');

});


// Signal parent that this iframe is ready to receive prefill data
if (window.parent !== window) window.parent.postMessage({ type: 'ready' }, '*');

/* ================= PREFILL ================= */
window.addEventListener('message', (event) => {

    if(event.data.type === 'prefill' || event.data.type === 'previous'){

        const previous = event.data.answer;

        if(previous){

            $(`input[value="${previous}"]`).prop('checked', true);

            $('input').prop('disabled', true);
            $('#submitBtn').hide();

            $('input').each(function(){

                let val = $(this).val();
                let parent = $(this).closest('.option');

                if(val === correct){
                    parent.css("background","#c8e6c9");
                }
                else if($(this).is(':checked') && val !== correct){
                    parent.css("background","#ffcdd2");
                }

            });

            $('#result').html(`
                <div>
                    <b>Rationale:</b><br>
                    ${rationale ? rationale : "No rationale provided."}
                </div>
            `).show();

        }

    }

});

});
</script>

</body>
</html>