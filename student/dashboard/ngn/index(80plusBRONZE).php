<?php
session_start();
include '../../../config.php';

// Verify login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../login.php");
    exit;
}

// Get fullname
$user_id = $_SESSION['user_id'];
$user_query = mysqli_query($con, "SELECT fullname, examTaken FROM login WHERE id='$user_id' LIMIT 1");
$user = mysqli_fetch_assoc($user_query);
$fullname = $user ? $user['fullname'] : 'Student';

// --- examTaken handling: increment once when starting the exam
$currentExamTaken = isset($user['examTaken']) ? intval($user['examTaken']) : 0;
$examTaken = $currentExamTaken + 1;

// update login table to set the new examTaken value
mysqli_query($con, "UPDATE login SET examTaken = examTaken + 1 WHERE id='$user_id'");

// Fetch questions from multiple tables
$questionIds = [];

// helper to safely query table existence
function table_exists($con, $table) {
    $table = mysqli_real_escape_string($con, $table);
    $res = mysqli_query($con, "SHOW TABLES LIKE '$table'");
    return $res && mysqli_num_rows($res) > 0;
}

// highlight
if (table_exists($con, 'highlight')) {
    $qHighlight = mysqli_query($con, "SELECT id, 'highlight' AS type FROM highlight ORDER BY RAND() LIMIT 3");
    while ($row = mysqli_fetch_assoc($qHighlight)) $questionIds[] = $row;
}
// bowtie
if (table_exists($con, 'btq')) {
    $q = mysqli_query($con, "SELECT id, 'bowtie' AS type FROM btq ORDER BY RAND() LIMIT 1");
    while ($r = mysqli_fetch_assoc($q)) $questionIds[] = $r;
}
// mmr
if (table_exists($con, 'mmr')) {
    $q = mysqli_query($con, "SELECT id, 'mmr' AS type FROM mmr ORDER BY RAND() LIMIT 2");
    while ($r = mysqli_fetch_assoc($q)) {
        $questionIds[] = $r;
    }
}

// dragndrop
if (table_exists($con, 'dragndrop')) {
    $qDrag = mysqli_query($con, "SELECT id, 'dragndrop' AS type FROM dragndrop ORDER BY RAND() LIMIT 2");
    while ($row = mysqli_fetch_assoc($qDrag)) $questionIds[] = $row;
}

// dropdown
if (table_exists($con, 'dropdown')) {
    $q = mysqli_query($con, "SELECT id, 'dropdown' AS type FROM dropdown ORDER BY RAND() LIMIT 2");
    while ($r = mysqli_fetch_assoc($q)) $questionIds[] = $r;
}

// sata
if (table_exists($con, 'sata')) {
    $q = mysqli_query($con, "SELECT id, 'sata' AS type FROM sata ORDER BY RAND() LIMIT 2");
    while ($r = mysqli_fetch_assoc($q)) $questionIds[] = $r;
}

// column
if (table_exists($con, 'column')) {
    $q = mysqli_query($con, "SELECT id, 'column' AS type FROM column ORDER BY RAND() LIMIT 2");
    while ($r = mysqli_fetch_assoc($q)) $questionIds[] = $r;
}

// traditional
if (table_exists($con, 'traditional')) {
    $q = mysqli_query($con, "SELECT id, 'traditional' AS type FROM traditional ORDER BY RAND() LIMIT 2");
    while ($r = mysqli_fetch_assoc($q)) $questionIds[] = $r;
}

// If no questions available, stop gracefully
if (count($questionIds) === 0) {
    die("No questions available.");
}

// Shuffle combined questions
shuffle($questionIds);

// Encode for JS
$questionIdsJs = json_encode($questionIds);
$examTakenJs = json_encode($examTaken);
$userIdJs = json_encode($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>NCLEX NGN Exam</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
body { margin:0; font-family:'Poppins',sans-serif; background:#f7f8fa; overflow:hidden; }
.navbar { background:#087f39; color:white; padding:15px 30px; display:flex; justify-content:space-between; align-items:center; }
.navbar div { display:flex; gap:20px; align-items:center; }
.timer { font-weight:bold; font-size:18px; }
.container { width:100%; height:calc(100vh - 70px); display:flex; flex-direction:column; align-items:center; justify-content:center; background:#fff; }
iframe { width:90%; height:70vh; border:none; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.1); background:white; pointer-events:auto; }
.controls { margin-top:15px; }
button { padding:10px 25px; border:none; border-radius:6px; background:#087f39; color:white; cursor:pointer; margin:0 5px; }
button:hover { background:#065f2b; }
button:disabled { opacity:0.6; cursor:not-allowed; }
</style>
</head>
<body>

<div class="navbar">
  <div><b>NCLEX NGN Exam</b></div>
  <div>
    <span id="questionTag">Question Type</span>
    <span>| <?php echo htmlspecialchars($fullname); ?></span>
    <span class="timer" id="timer">00:00</span>
  </div>
</div>

<div class="container">
    <iframe id="questionFrame" src=""></iframe>
    <div class="controls">
        <button id="prevBtn" disabled>Previous</button>
        <button id="nextBtn" disabled>Next</button>
        <button id="endBtn" style="background-color:red;display:none;">End Exam</button>
    </div>
</div>

<script>
    
    
const questionIds = <?php echo $questionIdsJs; ?>;
const examTaken = <?php echo $examTakenJs; ?>;
const authUserId = <?php echo $userIdJs; ?>;

let totalSeconds = 0;
let currentQuestion = 0;
let userAnswers = {}; // keyed by question_uid
let questionStartTime = Date.now();

// Timer
setInterval(() => {
  totalSeconds++;
  const m = String(Math.floor(totalSeconds/60)).padStart(2,'0');
  const s = String(totalSeconds%60).padStart(2,'0');
  document.getElementById('timer').textContent = `${m}:${s}`;
}, 1000);

// Load question
function loadQuestion(index) {
  const q = questionIds[index];
  const iframe = document.getElementById('questionFrame');

  document.getElementById('questionTag').textContent =
    "Question Type: " + q.type.toUpperCase();

  iframe.src = `${q.type}/index.php?id=${q.id}&t=${Date.now()}`;

  document.getElementById('prevBtn').disabled = index === 0;

  const uid = `${q.type}-${q.id}`;
  document.getElementById('nextBtn').disabled =
    !(userAnswers[uid] !== undefined);

  questionStartTime = Date.now();
}

document.getElementById('questionFrame').addEventListener('load', () => {
  const iframeWindow = document.getElementById('questionFrame').contentWindow;
  const q = questionIds[currentQuestion];
  const uid = `${q.type}-${q.id}`;
  const prevResult = userAnswers[uid] || null;

  if (prevResult) {
    iframeWindow.postMessage({
      type: 'prefill',
      answer: prevResult.answer ?? [],
      correct_answer: prevResult.correct_answer ?? [],
      isCorrect: prevResult.isCorrect === 1,
      score: prevResult.score ?? 0,
      rationale: prevResult.rationale ?? '',
      topic: prevResult.topic ?? '',
      system: prevResult.system ?? '',
      cnc: prevResult.cnc ?? '',
      dlevel: prevResult.dlevel ?? '',
      question_id: prevResult.question_id ?? q.id,
      showRationale: true
    }, '*');

    document.getElementById('nextBtn').disabled = false;
  } else {
    iframeWindow.postMessage({ type: 'prefill', answer: [], showRationale: false }, '*');
    document.getElementById('nextBtn').disabled = true;
  }
});





// Listen for answered messages
window.addEventListener('message', async (event) => {
  if (!event.data || typeof event.data !== 'object') return;
  if (event.data.type !== 'answered') return;

  const q = questionIds[currentQuestion];
  const uid = `${q.type}-${q.id}`;
  const timeSpent = Math.max(0, Math.round((Date.now() - questionStartTime)/1000));
  const totalTime = totalSeconds;

  const answer = event.data.answer ?? event.data.highlighted ?? event.data.user_answer ?? [];
  const correctAnswer = event.data.correctAnswer ?? event.data.correct ?? [];
  const isCorrect = !!event.data.correct;
  const score = typeof event.data.score !== 'undefined' ? parseInt(event.data.score,10) : (isCorrect ? 1 : 0);
  const topic = event.data.topic ?? null;
  const system = event.data.system ?? null;
  const cnc = event.data.cnc ?? null;
  const dlevel = event.data.dlevel ?? null;

  userAnswers[uid] = {
    question_uid: uid,
    question_type: q.type,
    question_id: q.id,
     answer: event.data.answer ?? event.data.user_answer ?? [],
    correct_answer: event.data.correctAnswer ?? event.data.correct ?? [],
    isCorrect: isCorrect ? 1 : 0,
    score: score,
    topic: topic,
    system: system,
    cnc: cnc,
    dlevel: dlevel,
    time_taken: timeSpent,
    totalTime: totalTime,
    examTaken: examTaken,
    question_number: currentQuestion + 1,
    timestamp: new Date().toISOString()
  };

  document.getElementById('nextBtn').disabled = false;

  // Save history to server
  try {
    await fetch('save_history.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(userAnswers[uid])
    });
  } catch(err) { console.warn('Failed to save history:', err); }
});

// Navigation
document.getElementById('nextBtn').addEventListener('click', () => {
  if (currentQuestion < questionIds.length - 1) {
    currentQuestion++;
    loadQuestion(currentQuestion);
  } else {
    window.location.href = 'result.php?examTaken=' + examTaken;
  }
});

document.getElementById('prevBtn').addEventListener('click', () => {
  if (currentQuestion > 0) {
    currentQuestion--;
    loadQuestion(currentQuestion);
  }
});

// End Exam button (direct to result)
document.getElementById('endBtn').addEventListener('click', () => {
  window.location.href = 'result.php?examTaken=' + examTaken;
});


// Initial load
loadQuestion(currentQuestion);

// === Warn on accidental reload ===
window.addEventListener('beforeunload', function (e) {
    // Only warn if the user has started answering
    if (Object.keys(userAnswers).length > 0) {
        e.preventDefault();
        e.returnValue = '';
        return '';
    }
});

</script>
</body>
</html>
