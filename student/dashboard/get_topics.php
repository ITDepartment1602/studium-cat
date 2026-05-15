<?php
include '../../config.php';

header('Content-Type: application/json');

if (!isset($_GET['topics1'])) {
    echo json_encode(['error' => 'No concept provided']);
    exit;
}

$selectedConcept = mysqli_real_escape_string($con, $_GET['topics1']);
$topics = [];
$seen   = [];

$stmt = $con->prepare("SELECT DISTINCT id, system, topics1 FROM question ORDER BY system ASC");
if (!$stmt) {
    echo json_encode(['error' => 'Query failed']);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $key = strtolower(trim($row['system']));
    if (in_array($key, $seen)) continue;
    $seen[] = $key;

    $countStmt = $con->prepare("SELECT COUNT(*) AS count FROM question WHERE system = ? AND topics1 = ?");
    $countStmt->bind_param("ss", $row['system'], $selectedConcept);
    $countStmt->execute();
    $count = $countStmt->get_result()->fetch_assoc()['count'];
    $countStmt->close();

    $topics[] = [
        'system' => $row['system'],
        'value'  => $row['id'] . '|' . urlencode($row['system']),
        'count'  => (int) $count,
    ];
}

$stmt->close();

echo json_encode(['topics' => $topics]);
