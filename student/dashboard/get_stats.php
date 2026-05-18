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

echo json_encode(['error' => 'Invalid type']);
