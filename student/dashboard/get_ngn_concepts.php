<?php
require_once '../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode([]); exit; }

$con = getQuizConnection();
$tables = ['mcq', 'mmr', 'dragndrop', 'dropdown_questions', 'mpr', 'btq', 'sata', 'highlight'];
$concepts = [];

foreach ($tables as $table) {
    $check = mysqli_query($con, "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . mysqli_real_escape_string($con, $table) . "' LIMIT 1");
    if (!$check || !mysqli_num_rows($check)) continue;

    $colCheck = mysqli_query($con, "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . mysqli_real_escape_string($con, $table) . "' AND COLUMN_NAME = 'concept' LIMIT 1");
    if (!$colCheck || !mysqli_num_rows($colCheck)) continue;

    $res = mysqli_query($con, "SELECT concept, COUNT(*) as cnt FROM `$table` WHERE concept IS NOT NULL AND concept != '' GROUP BY concept");
    if (!$res) continue;
    while ($row = mysqli_fetch_assoc($res)) {
        $c = trim($row['concept']);
        if ($c === '') continue;
        $concepts[$c] = ($concepts[$c] ?? 0) + intval($row['cnt']);
    }
}

ksort($concepts);
$result = [];
foreach ($concepts as $name => $count) {
    $result[] = ['concept' => $name, 'count' => $count];
}
echo json_encode($result);
