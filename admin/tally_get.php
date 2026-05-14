<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include '../config.php';
include 'tally_helper.php';

header('Content-Type: application/json');

// Read today's tally file; if it doesn't exist, create it with current student count as morning
$tally = tally_read();
if (!$tally) {
    $res = mysqli_query($con, "SELECT COUNT(*) as cnt FROM login");
    $row = mysqli_fetch_assoc($res);
    $tally = tally_create((int)$row['cnt']);
}

// Real-time expired count
$now = date('Y-m-d H:i:s');
$res = mysqli_query($con,
    "SELECT COUNT(*) as cnt FROM login
     WHERE dateexpired IS NOT NULL AND dateexpired != '' AND dateexpired < '$now'"
);
$row = mysqli_fetch_assoc($res);
$expiredCount = (int)$row['cnt'];

$totalAccess    = $tally['added_1month'] + $tally['added_2months'] + $tally['added_3months']
                + $tally['added_6months'] + $tally['added_12months']
                + $tally['added_1month_free'] + $tally['added_2months_free'];
$totalAfternoon = $tally['morning_count'] + $totalAccess;

echo json_encode([
    'morning_count'      => $tally['morning_count'],
    'added_1month'       => $tally['added_1month'],
    'added_2months'      => $tally['added_2months'] ?? 0,
    'added_3months'      => $tally['added_3months'],
    'added_6months'      => $tally['added_6months'],
    'added_12months'     => $tally['added_12months'],
    'added_1month_free'  => $tally['added_1month_free'],
    'added_2months_free' => $tally['added_2months_free'],
    'deleted_today'      => $tally['deleted_today'],
    'expired_count'      => $expiredCount,
    'total_access'       => $totalAccess,
    'total_afternoon'    => $totalAfternoon,
]);
