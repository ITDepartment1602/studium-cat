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

$file   = $_FILES['csvFile']['tmp_name'];
$handle = fopen($file, 'r');
fgetcsv($handle); // skip header

// Ensure today's tally exists
if (!tally_read()) {
    $morningRes = mysqli_query($con, "SELECT COUNT(*) as cnt FROM login");
    $morningRow = mysqli_fetch_assoc($morningRes);
    tally_create((int)$morningRow['cnt']);
}

$sql = "INSERT INTO login (
    studentnumber, fullname, bundle_name, groupname,
    dateenrolled, dateexpired, subMonth, type,
    email, password, status, loginstatus, lastlogin, examTaken
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $con->prepare($sql);
$rowCount = 0;

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
    $dateenrolled  = date('Y-m-d H:i:s');
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

header("Location: import_csv.php?import=success&rows=$rowCount");
exit;
?>
