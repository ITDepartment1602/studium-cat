<?php
include '../../config.php';
$user_id = $_SESSION['user_id'] ?? 0;

header('Content-Type: application/json');

if (!$user_id) {
  echo json_encode(['error' => 'User not logged in']);
  exit;
}

$con = getQuizConnection();
$type  = $_GET['type']  ?? '';
$value = $_GET['value'] ?? '';

if ($type === 'concept') {
    // ✅ Concepts = system
    $total = mysqli_fetch_assoc(mysqli_query(
        $con,
        "SELECT COUNT(DISTINCT questionId) as total 
         FROM review 
         WHERE studentId='$user_id' AND system='$value'"
    ))['total'];

    $used = mysqli_fetch_assoc(mysqli_query(
        $con,
        "SELECT COUNT(DISTINCT questionId) as used 
         FROM review 
         WHERE studentId='$user_id' AND system='$value'"
    ))['used'];

    $correct = mysqli_fetch_assoc(mysqli_query(
        $con,
        "SELECT COUNT(*) as correct 
         FROM review 
         WHERE studentId='$user_id' AND system='$value' AND ans = correctAns"
    ))['correct'];

    $wrong = mysqli_fetch_assoc(mysqli_query(
        $con,
        "SELECT COUNT(*) as wrong 
         FROM review 
         WHERE studentId='$user_id' AND system='$value' AND ans != correctAns"
    ))['wrong'];

    echo json_encode([
        'total' => intval($total),
        'used' => intval($used),
        'correct' => intval($correct),
        'wrong' => intval($wrong)
    ]);
    exit;
}

if ($type === 'topic') {
    // ✅ Topics = topics1
    $total = mysqli_fetch_assoc(mysqli_query(
        $con,
        "SELECT COUNT(DISTINCT questionId) as total 
         FROM review 
         WHERE studentId='$user_id' AND topics1='$value'"
    ))['total'];

    $used = mysqli_fetch_assoc(mysqli_query(
        $con,
        "SELECT COUNT(DISTINCT questionId) as used 
         FROM review 
         WHERE studentId='$user_id' AND topics1='$value'"
    ))['used'];

    $correct = mysqli_fetch_assoc(mysqli_query(
        $con,
        "SELECT COUNT(*) as correct 
         FROM review 
         WHERE studentId='$user_id' AND topics1='$value' AND ans = correctAns"
    ))['correct'];

    $wrong = mysqli_fetch_assoc(mysqli_query(
        $con,
        "SELECT COUNT(*) as wrong 
         FROM review 
         WHERE studentId='$user_id' AND topics1='$value' AND ans != correctAns"
    ))['wrong'];

    echo json_encode([
        'total' => intval($total),
        'used' => intval($used),
        'correct' => intval($correct),
        'wrong' => intval($wrong)
    ]);
    exit;
}

// ── NGN stats from exam_results ──
if ($type === 'ngn_concept') {
    $db  = db()->getConnection();
    $val = $db->real_escape_string($value);
    $uid = intval($user_id);
    $row = $db->query("SELECT COUNT(DISTINCT question_uid) as used, SUM(isCorrect) as correct, COUNT(*) - SUM(isCorrect) as wrong FROM exam_results WHERE student_id = $uid AND concept = '$val'")->fetch_assoc();
    echo json_encode(['total' => intval($row['used']), 'used' => intval($row['used']), 'correct' => intval($row['correct']), 'wrong' => intval($row['wrong'])]);
    exit;
}

if ($type === 'ngn_topic') {
    $db  = db()->getConnection();
    $val = $db->real_escape_string($value);
    $uid = intval($user_id);
    $row = $db->query("SELECT COUNT(DISTINCT question_uid) as used, SUM(isCorrect) as correct, COUNT(*) - SUM(isCorrect) as wrong FROM exam_results WHERE student_id = $uid AND topic = '$val'")->fetch_assoc();
    echo json_encode(['total' => intval($row['used']), 'used' => intval($row['used']), 'correct' => intval($row['correct']), 'wrong' => intval($row['wrong'])]);
    exit;
}

// ── Overall = Trad (review table) + NGN (exam_results) combined ──
// overall_concept: concept card — Concepts card uses eid (trad) + concept (ngn)
if ($type === 'overall_concept') {
    $val = mysqli_real_escape_string($con, $value);
    $uid = intval($user_id);

    // Trad: eid matches concept name (e.g. "Adult Health", "Pharmacology")
    $tRow = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT
            COUNT(DISTINCT r.questionId) as used,
            SUM(r.ans = q.correctAns)    as correct,
            SUM(r.ans != q.correctAns)   as wrong
         FROM review r
         JOIN question q ON r.questionId = q.id
         WHERE r.studentId = '$uid' AND q.topics1 = '$val'"
    ));
    $tCorrect = (int)($tRow['correct'] ?? 0);
    $tWrong   = (int)($tRow['wrong']   ?? 0);
    $tUsed    = (int)($tRow['used']    ?? 0);

    // NGN: concept column
    $db  = db()->getConnection();
    $nRow = $db->query(
        "SELECT COUNT(DISTINCT question_uid) as used,
                SUM(isCorrect)               as correct,
                COUNT(*) - SUM(isCorrect)    as wrong
         FROM exam_results
         WHERE student_id = $uid AND concept = '{$db->real_escape_string($value)}'"
    )->fetch_assoc();
    $nCorrect = (int)($nRow['correct'] ?? 0);
    $nWrong   = (int)($nRow['wrong']   ?? 0);
    $nUsed    = (int)($nRow['used']    ?? 0);

    echo json_encode([
        'total'   => $tUsed + $nUsed,
        'used'    => $tUsed + $nUsed,
        'correct' => $tCorrect + $nCorrect,
        'wrong'   => $tWrong   + $nWrong,
    ]);
    exit;
}

// overall_topic: Topics card — system (trad) + topic (ngn)
if ($type === 'overall_topic') {
    $uid = intval($user_id);

    // Trad: system column in review table
    $val = mysqli_real_escape_string($con, $value);
    $tRow = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT
            COUNT(DISTINCT questionId)         as used,
            SUM(ans = correctAns)              as correct,
            SUM(ans != correctAns)             as wrong
         FROM review
         WHERE studentId = '$uid' AND system = '$val'"
    ));
    $tCorrect = (int)($tRow['correct'] ?? 0);
    $tWrong   = (int)($tRow['wrong']   ?? 0);
    $tUsed    = (int)($tRow['used']    ?? 0);

    // NGN: topic column
    $db  = db()->getConnection();
    $nRow = $db->query(
        "SELECT COUNT(DISTINCT question_uid) as used,
                SUM(isCorrect)               as correct,
                COUNT(*) - SUM(isCorrect)    as wrong
         FROM exam_results
         WHERE student_id = $uid AND topic = '{$db->real_escape_string($value)}'"
    )->fetch_assoc();
    $nCorrect = (int)($nRow['correct'] ?? 0);
    $nWrong   = (int)($nRow['wrong']   ?? 0);
    $nUsed    = (int)($nRow['used']    ?? 0);

    echo json_encode([
        'total'   => $tUsed + $nUsed,
        'used'    => $tUsed + $nUsed,
        'correct' => $tCorrect + $nCorrect,
        'wrong'   => $tWrong   + $nWrong,
    ]);
    exit;
}

echo json_encode(['error' => 'Invalid type']);
