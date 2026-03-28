<?php
// multiplechoice/index.php

include '../../../../config.php';
session_start();

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

<script>

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