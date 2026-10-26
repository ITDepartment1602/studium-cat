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
  body { overflow-x: hidden; }
  .two-panel { flex-direction: column; height: auto; overflow: visible; }
  .left-panel { width: 100%; min-width: 0; border-right: none; border-bottom: 2px solid #e2e8f0; max-height: 35vh; overflow-y: auto; }
  .right-panel { width: 100% !important; overflow: visible; }
}
@media (max-width: 640px) {
  .container { padding: 14px; margin: 12px auto; border-radius: 0; }
  .option { padding: 10px; font-size: 13px; }
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
    background:#0d9488;
    color:white;
    border:none;
    padding:10px 25px;
    cursor:pointer;
    border-radius:4px;
    font-weight:bold;
    transition: background 0.2s;
}
.btn:hover { background: #0b7e74; }
@keyframes ngn-spin { to { transform: rotate(360deg); } }
.ngn-spinner { display: inline-block; width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: ngn-spin 0.7s linear infinite; vertical-align: middle; margin-right: 4px; flex-shrink: 0; }

#result{
    display:none;
    margin-top:20px;
    padding:15px;
    background:#f8f9fa;
    border-left:4px solid #087f39;
}

/* ── Mobile Clinical Reference — Senior-Friendly Design ── */
@media (max-width: 768px) {
  /* Hide panel from normal flow */
  .left-panel { display: none !important; }

  /* Compact banner button above the question */
  .mobile-clinref-btn {
    display: flex !important;
    width: 100%;
    align-items: center;
    justify-content: center;
    gap: 6px;
    background: #e8f4fd;
    color: #0369a1;
    border: 1.5px solid #bae6fd;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    margin-bottom: 10px;
    text-align: center;
    letter-spacing: 0.1px;
    -webkit-tap-highlight-color: transparent;
  }
  .mobile-clinref-btn i { font-size: 14px; }

  /* Full-screen overlay */
  .left-panel.mobile-open {
    display: flex !important;
    position: fixed !important;
    inset: 0 !important;
    z-index: 9998 !important;
    width: 100% !important;
    min-width: unset !important;
    max-width: 100% !important;
    height: 100dvh !important;
    max-height: 100% !important;
    border-right: none !important;
    border-bottom: none !important;
    flex-direction: column !important;
    overflow: hidden !important;
    border-radius: 0 !important;
    background: #fff !important;
  }

  /* Header inside modal */
  .left-panel.mobile-open .panel-title {
    padding: 16px 18px !important;
    font-size: 13px !important;
    display: flex !important;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
    background: #f1f5f9;
    border-bottom: 1px solid #e2e8f0;
  }

  /* Close button — large, with text */
  .mobile-clinref-close {
    display: flex !important;
    align-items: center;
    gap: 6px;
    background: #ef4444;
    color: #fff !important;
    border: none;
    border-radius: 10px;
    padding: 8px 16px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    min-width: 80px;
    justify-content: center;
  }

  /* Tabs — large tap targets */
  .left-panel.mobile-open .tabs-row {
    flex-shrink: 0;
    padding: 6px 8px 0 !important;
    gap: 3px !important;
    overflow-x: hidden;
    display: flex;
    flex-wrap: nowrap;
  }
  .left-panel.mobile-open .tab-btn {
    font-size: 11px !important;
    padding: 8px 10px !important;
    font-weight: 600;
    white-space: nowrap;
    flex: 1 1 0;
    min-width: 0;
    text-align: center;
    border-radius: 8px 8px 0 0;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* Content area — scrollable, comfortable font */
  .left-panel.mobile-open .tab-content-area {
    flex: 1;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 16px !important;
  }
  .left-panel.mobile-open .clinical-record {
    font-size: 14.5px !important;
    line-height: 1.7 !important;
    padding: 12px 14px !important;
    margin-bottom: 10px !important;
    border-radius: 8px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
  }
}

/* ── Mobile UI: Compact & Readable (≤768px) ── */
@media (max-width: 768px) {
  html, body { overflow-x: hidden; max-width: 100vw; }
  .right-panel { width: 100% !important; }
  .container { padding: 12px !important; margin: 10px auto !important; border-radius: 10px !important; box-shadow: none !important; border: 1px solid #e2e8f0; }
  h2, .question-title { font-size: 14px !important; line-height: 1.55 !important; margin-bottom: 12px !important; }
  .option { padding: 10px 12px !important; font-size: 13px !important; margin-bottom: 8px !important; border-radius: 8px !important; }
  .footer { padding: 10px 0 !important; gap: 8px !important; }
  .btn, button[type=submit], input[type=submit] { padding: 10px 14px !important; font-size: 13px !important; border-radius: 8px !important; }
  #result, .rationale-box { padding: 12px !important; margin-top: 12px !important; font-size: 13px !important; border-radius: 8px !important; }
}
@media (min-width: 769px) {
  .mobile-clinref-btn { display: none !important; }

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

    $(this).prop('disabled', true).html('<span class="ngn-spinner"></span> Submitting...');

    const _mc_selected = selected; // capture for setTimeout closure
    setTimeout(function() {
        let isCorrect = (_mc_selected === correct);

        /* ================= HIGHLIGHT ================= */
        $('input[name="answer"]').each(function(){
            let val = $(this).val();
            let parent = $(this).closest('.option');
            if(val === correct){
                parent.css("background","#c8e6c9");
            } else if($(this).is(':checked') && val !== correct){
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
            answer: _mc_selected,
            correctAnswer: correct,
            correct: isCorrect,
            score: isCorrect ? 1 : 0,
            max_score: 1,
            question_id: <?= json_encode($data['id']) ?>,
            question_type: 'mcq'
        }, '*');
    }, 60);

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


<script>
(function () {
  if (window.innerWidth > 768) return;
  var lp = document.querySelector(".left-panel");
  if (!lp) return;

  /* Close button inside modal header — text label, not just × */
  var pt = lp.querySelector(".panel-title");
  if (pt) {
    var cb = document.createElement("button");
    cb.className = "mobile-clinref-close";
    cb.innerHTML = "&#10005; Close";
    cb.onclick = function () { lp.classList.remove("mobile-open"); };
    pt.appendChild(cb);
  }

  /* Banner button injected at the TOP of right-panel content (before question) */
  var rp = document.querySelector(".right-panel");
  var btn = document.createElement("button");
  btn.className = "mobile-clinref-btn";
  btn.innerHTML = "<i class='fas fa-folder-open'></i> View Patient Chart &amp; Lab Results";
  btn.onclick = function () { lp.classList.add("mobile-open"); };

  if (rp) {
    rp.insertBefore(btn, rp.firstChild);
  } else {
    document.body.insertBefore(btn, document.body.firstChild);
  }

})();
</script>
</body>
</html>