<?php
require_once '../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode([]); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$selectedConcepts = array_filter(array_map('trim', $input['concepts'] ?? []));
if (empty($selectedConcepts)) { echo json_encode([]); exit; }

$con = getQuizConnection();
$tables = ['mcq', 'mmr', 'dragndrop', 'dropdown_questions', 'mpr', 'btq', 'sata', 'highlight'];
$topics = [];

foreach ($tables as $table) {
    $check = mysqli_query($con, "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . mysqli_real_escape_string($con, $table) . "' LIMIT 1");
    if (!$check || !mysqli_num_rows($check)) continue;

    $colCheck = mysqli_query($con, "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . mysqli_real_escape_string($con, $table) . "' AND COLUMN_NAME IN ('concept','topic')");
    if (!$colCheck) continue;
    $cols = [];
    while ($c = mysqli_fetch_assoc($colCheck)) $cols[] = $c['COLUMN_NAME'];
    if (!in_array('concept', $cols) || !in_array('topic', $cols)) continue;

    $escaped = array_map(fn($v) => "'" . mysqli_real_escape_string($con, $v) . "'", $selectedConcepts);
    $inClause = implode(',', $escaped);

    $res = mysqli_query($con, "SELECT concept, topic, COUNT(*) as cnt FROM `$table` WHERE concept IN ($inClause) AND topic IS NOT NULL AND topic != '' GROUP BY concept, topic");
    if (!$res) continue;
    while ($row = mysqli_fetch_assoc($res)) {
        $c = trim($row['concept']);
        $t = trim($row['topic']);
        if ($c === '' || $t === '') continue;
        if (!isset($topics[$c])) $topics[$c] = [];
        $topics[$c][$t] = ($topics[$c][$t] ?? 0) + intval($row['cnt']);
    }
}

$result = [];
foreach ($topics as $concept => $topicList) {
    ksort($topicList);
    foreach ($topicList as $topic => $count) {
        $result[] = ['concept' => $concept, 'topic' => $topic, 'count' => $count];
    }
}
echo json_encode($result);
