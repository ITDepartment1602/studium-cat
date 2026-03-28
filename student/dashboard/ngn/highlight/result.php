<?php
session_start();
if (!isset($_SESSION['highlight_exam'])) die("No exam data found.");

$exam = $_SESSION['highlight_exam'];
$answers = $exam['answers'];
$totalQuestions = count($answers);
$avgScore = $totalQuestions ? round(array_sum(array_column($answers, 'score')) / $totalQuestions, 2) : 0;
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Exam Result</title>
<style>
body { font-family: 'Poppins', sans-serif; text-align:center; background:#f4f6f9; margin-top:50px; }
.box { background:white; padding:40px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.1); display:inline-block; max-width:700px; text-align:left; }
h2 { color:#087f39; }
.correct { color:green; font-weight:bold; }
.wrong { color:red; font-weight:bold; }
ul { list-style:none; padding-left:0; }
li { margin-bottom:8px; }
</style>
</head>
<body>
<div class="box">
    <h2>Exam Completed!</h2>
    <p>You answered <b><?php echo $totalQuestions; ?></b> questions.</p>
    <p>Your average score: <b><?php echo $avgScore; ?>%</b></p>
    <hr>
    <h3>Detailed Results:</h3>
    <ul>
    <?php foreach($answers as $qid => $ans): ?>
        <li>
            <b>Question ID <?php echo $qid; ?>:</b>
            <span class="<?php echo $ans['isCorrect'] ? 'correct' : 'wrong'; ?>">
                <?php echo $ans['isCorrect'] ? '✅ Correct' : '❌ Incorrect'; ?>
            </span><br>
            Matched: <?php echo $ans['match']; ?>/<?php echo $ans['total']; ?><br>
            Score: <?php echo $ans['score']; ?>%<br>
            Your highlight: <?php echo implode(', ', $ans['highlighted']); ?><br>
            Correct highlight: <?php echo implode(', ', $ans['correct_answer']); ?>
        </li>
    <?php endforeach; ?>
    </ul>
    <button onclick="window.location.href='index.php'">Retake Exam</button>
</div>
</body>
</html>