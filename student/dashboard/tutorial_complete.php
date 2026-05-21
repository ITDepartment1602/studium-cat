<?php
include '../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Not authenticated']);
    exit;
}

$uid = intval($_SESSION['user_id']);
mysqli_query($con, "UPDATE login SET tutorial = 1 WHERE id = $uid");

echo json_encode(['ok' => true]);
