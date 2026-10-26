<?php
// Toggle a student's activation type (from the dashboard).
include('../config.php');

$id   = intval($_GET['id'] ?? 0);
$type = $_GET['type'] ?? '';

$stmt = mysqli_prepare($con, "UPDATE login SET type = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'si', $type, $id);
mysqli_stmt_execute($stmt);

header('Location: ../it');
exit;
