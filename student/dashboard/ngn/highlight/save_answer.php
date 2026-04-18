<?php
require_once '../../../../config.php';
// session_start() is handled by config.php

header('Content-Type: application/json');

if (!isset($_SESSION['highlight_exam'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No exam session']);
    exit;
}

// Validate required POST fields
$required = ['id', 'highlighted', 'correct_answer', 'match', 'total', 'score', 'isCorrect'];
foreach ($required as $field) {
    if (!isset($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing field: {$field}"]);
        exit;
    }
}

$id             = $_POST['id'];
$highlighted    = json_decode($_POST['highlighted'], true);
$correct_answer = json_decode($_POST['correct_answer'], true);
$match          = (int)$_POST['match'];
$total          = (int)$_POST['total'];
$score          = (int)$_POST['score'];
$isCorrect      = filter_var($_POST['isCorrect'], FILTER_VALIDATE_BOOLEAN);
$action         = $_POST['action'] ?? null;

// Save detailed answer info
$_SESSION['highlight_exam']['answers'][$id] = [
    'highlighted'    => $highlighted,
    'correct_answer' => $correct_answer,
    'match'          => $match,
    'total'          => $total,
    'score'          => $score,
    'isCorrect'      => $isCorrect
];

// Navigation
if ($action === 'next') {
    $_SESSION['highlight_exam']['current']++;
} elseif ($action === 'prev') {
    $_SESSION['highlight_exam']['current'] = max(0, $_SESSION['highlight_exam']['current'] - 1);
}

echo json_encode([
    'status'    => 'success',
    'score'     => $score,
    'match'     => $match,
    'total'     => $total,
    'isCorrect' => $isCorrect
]);
