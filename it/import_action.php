<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Manila');
include_once '../config.php';
include_once 'tally_helper.php';
mysqli_query($con, "SET time_zone = '+08:00'");

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['csvFile'])) {
    header('Location: import_csv.php');
    exit;
}

function formatGroupStudent($value) {
    $value = trim($value);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return date('F Y', strtotime($value));
    if (preg_match('/^([A-Za-z]{3,})-(\d{2})$/', $value, $m)) {
        $ts = strtotime("01-{$m[1]}-20{$m[2]}");
        return $ts ? date('F Y', $ts) : $value;
    }
    if (preg_match('/^(\d{2})-([A-Za-z]{3,})$/', $value, $m)) {
        $ts = strtotime("01-{$m[2]}-20{$m[1]}");
        return $ts ? date('F Y', $ts) : $value;
    }
    return $value ?: "Unassigned";
}

// Index of a CSV header column by name (case-insensitive, ignores a UTF-8 BOM), or null.
function csvColumnIndex($header, $name) {
    if (!is_array($header)) return null;
    foreach ($header as $i => $col) {
        if (strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string)$col))) === $name) return $i;
    }
    return null;
}

// CSV date_enroll ("Y-m-d", optionally with a time part) → "Y-m-d", or null if empty/invalid.
function parseEnrollDate($value) {
    $day = substr(trim((string)$value), 0, 10);
    $d   = DateTime::createFromFormat('!Y-m-d', $day);
    return ($d && $d->format('Y-m-d') === $day && $day >= '2000-01-01') ? $day : null;
}

$file   = $_FILES['csvFile']['tmp_name'];
$handle = fopen($file, 'r');
$enrollCol = csvColumnIndex(fgetcsv($handle), 'date_enroll');
if ($enrollCol === null) {
    // Old CSV format: importing it would store the import time as the enrollment date.
    fclose($handle);
    $msg = 'Old CSV format: no date_enroll column, so nothing was imported. Re-download the CSV from the CSV Maker and import that file.';
    header('Location: import_csv.php?import=error&msg=' . urlencode($msg));
    exit;
}

// Ensure today's tally exists
if (!tally_read()) {
    $morningRes = mysqli_query($con, "SELECT COUNT(*) as cnt FROM login");
    $morningRow = mysqli_fetch_assoc($morningRes);
    tally_create((int)$morningRow['cnt']);
}

$sql = "INSERT INTO login (
    studentnumber, fullname, bundle_name, groupname,
    dateenrolled, access_date, dateexpired, subMonth, type,
    email, password, status, loginstatus, lastlogin, examTaken
) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $con->prepare($sql);
$rowCount      = 0;
$fallbackCount = 0; // imported rows whose date_enroll was empty/invalid

$batch = [
    'added_1month'       => 0,
    'added_2months'      => 0,
    'added_3months'      => 0,
    'added_6months'      => 0,
    'added_12months'     => 0,
    'added_1month_free'  => 0,
    'added_2months_free' => 0,
];

while (($data = fgetcsv($handle)) !== FALSE) {
    if (count($data) < 8) continue;

    $studentnumber = $data[1];
    $fullname      = $data[2];
    $bundle_name   = $data[3];
    $rawGroup      = trim($data[4]);
    $groupname     = formatGroupStudent($rawGroup);
    $dateenrolled  = parseEnrollDate($data[$enrollCol] ?? '');
    $usedFallback  = ($dateenrolled === null);
    if ($usedFallback) $dateenrolled = date('Y-m-d');
    $dateexpired   = null;
    $subMonth      = $data[5];
    $type          = null;
    $email         = $data[6];
    $password      = $data[7];
    $status        = 'user';
    $loginstatus   = null;
    $lastlogin     = null;
    $examTaken     = 0;

    $stmt->bind_param(
        "ssssssssssssss",
        $studentnumber, $fullname, $bundle_name, $groupname,
        $dateenrolled, $dateexpired, $subMonth, $type,
        $email, $password, $status, $loginstatus, $lastlogin, $examTaken
    );

    if ($stmt->execute()) {
        $rowCount++;
        if ($usedFallback) $fallbackCount++;
        $isFree = stripos($groupname, 'FREE') !== false;
        $months = (int)$subMonth;
        if ($isFree) {
            if ($months === 1)     $batch['added_1month_free']++;
            elseif ($months === 2) $batch['added_2months_free']++;
        } else {
            if ($months === 1)      $batch['added_1month']++;
            elseif ($months === 2)  $batch['added_2months']++;
            elseif ($months === 3)  $batch['added_3months']++;
            elseif ($months === 6)  $batch['added_6months']++;
            elseif ($months === 12) $batch['added_12months']++;
        }
    }
}

fclose($handle);
$stmt->close();

if ($rowCount > 0) tally_increment($batch);
mysqli_close($con);

header("Location: import_csv.php?import=success&rows=$rowCount&fallback=$fallbackCount");
exit;
?>
