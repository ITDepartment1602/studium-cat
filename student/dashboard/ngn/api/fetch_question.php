<?php
include '../../../../config.php';
session_start();

// For now, get random question from HIGHLIGHT table
$q = mysqli_query($con, "SELECT * FROM highlight ORDER BY RAND() LIMIT 1");
$d = mysqli_fetch_assoc($q);

echo json_encode([
  'id' => $d['id'],
  'topic' => $d['topic'],
  'question' => $d['question'],
  'passage' => $d['passage'],
  'rationale' => $d['rationale'] ?? 'No rationale provided.',
  'concept' => $d['targets'] ?? 'N/A',
  'system' => $d['system'] ?? 'N/A',
  'cnc' => $d['cnc'] ?? 'N/A',
  'dlevel' => $d['dlevel'] ?? 'N/A'
]);
