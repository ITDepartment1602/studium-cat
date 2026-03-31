<?php
require_once '../../../../config.php';
// session_start handled by config.php

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not authenticated']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$score = isset($_POST['score']) ? floatval($_POST['score']) : 0.00;
$time_spent = isset($_POST['time_spent']) ? intval($_POST['time_spent']) : 0;

// Security: Use prepared statement
$stmt = mysqli_prepare($con, "SELECT * FROM highlight WHERE id=?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$d = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$d) {
    http_response_code(404);
    echo json_encode(['error' => 'question not found']);
    exit;
}

echo json_encode([
  'id' => $id,
  'score' => $score,
  'isCorrect' => ($score >= 1.00),
  'rationale' => $d['rationale'] ?? 'No rationale provided.',
  'topic' => $d['topic'] ?? 'N/A',
  'concept' => $d['targets'] ?? 'N/A',
  'system' => $d['system'] ?? 'N/A',
  'cnc' => $d['cnc'] ?? 'N/A',
  'dlevel' => $d['dlevel'] ?? 'N/A',
  'time_spent' => $time_spent
]);
