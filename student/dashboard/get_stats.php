<?php
include '../../config.php';
session_start();
$user_id = $_SESSION['user_id'] ?? 0;

header('Content-Type: application/json');

if (!$user_id) {
  echo json_encode(['error' => 'User not logged in']);
  exit;
}

$type = $_GET['type'] ?? '';
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

echo json_encode(['error' => 'Invalid type']);
