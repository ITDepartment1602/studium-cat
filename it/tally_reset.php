<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include 'tally_helper.php';

header('Content-Type: application/json');

$f = tally_file_path();
if (!file_exists($f)) {
    echo json_encode(['ok' => false, 'error' => 'No tally file for today']);
    exit;
}

$fp = fopen($f, 'r+');
if (!$fp) {
    echo json_encode(['ok' => false, 'error' => 'Cannot open tally file']);
    exit;
}

flock($fp, LOCK_EX);

$raw = '';
while (!feof($fp)) $raw .= fread($fp, 8192);
$data = ($raw !== '') ? json_decode($raw, true) : null;

if (is_array($data)) {
    $data['added_1month']       = 0;
    $data['added_2months']      = 0;
    $data['added_3months']      = 0;
    $data['added_6months']      = 0;
    $data['added_12months']     = 0;
    $data['added_1month_free']  = 0;
    $data['added_2months_free'] = 0;
    $data['deleted_today']      = 0;

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data));
    fflush($fp);
}

flock($fp, LOCK_UN);
fclose($fp);

echo json_encode(['ok' => true]);
