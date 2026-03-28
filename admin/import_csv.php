<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Manila');
include_once '../config.php';
mysqli_query($con, "SET time_zone = '+08:00'");

$message = "";
$messageClass = "";
$showHistoryButton = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvFile'])) {
    $file = $_FILES['csvFile']['tmp_name'];
    $handle = fopen($file, 'r');
    fgetcsv($handle); // Skip header row

    // SQL with fixed columns
    $sql = "INSERT INTO login (
        studentnumber, fullname, bundle_name, groupname, 
        dateenrolled, dateexpired, subMonth, type, 
        email, password, status, loginstatus, lastlogin, examTaken
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $con->prepare($sql);
    $rowCount = 0;

    while (($data = fgetcsv($handle)) !== FALSE) {
        // Expect at least 8 columns in CSV
        if (count($data) < 8) continue;

        $studentnumber = $data[1];
        $fullname      = $data[2];
        $bundle_name   = $data[3];

        // Format groupname into "Month YYYY"
        $rawGroup  = trim($data[4]);
        $groupname = formatGroupStudent($rawGroup);

        // Fixed values
        $dateenrolled = date('Y-m-d H:i:s'); // Manila current time
        $dateexpired  = null;                // Always NULL unless later computed
        $subMonth     = $data[5];
        $type         = null;                // Always NULL
        $email        = $data[6];
        $password     = $data[7];
        $status       = 'user';              // Fixed
        $loginstatus  = null;                // Always NULL
        $lastlogin    = null;                // Always NULL
        $examTaken    = 0;                   // Always 0

        $stmt->bind_param(
            "ssssssssssssss",
            $studentnumber, $fullname, $bundle_name, $groupname,
            $dateenrolled, $dateexpired, $subMonth, $type,
            $email, $password, $status, $loginstatus, $lastlogin, $examTaken
        );

        if ($stmt->execute()) {
            $rowCount++;
        }
    }

    fclose($handle);
    $stmt->close();
    mysqli_close($con);

    $message = "✅ Successfully imported $rowCount rows.";
    $messageClass = "alert-success";
    $showHistoryButton = true;
}

function formatGroupStudent($value) {
    $value = trim($value);

    // Case 1: YYYY-MM-DD → "Month YYYY"
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return date('F Y', strtotime($value));
    }

    // Case 2: Aug-25, Sept-25 → "Month YYYY"
    if (preg_match('/^([A-Za-z]{3,})-(\d{2})$/', $value, $matches)) {
        $month = $matches[1];
        $year  = "20" . $matches[2]; // force to 20xx
        $timestamp = strtotime("01-$month-$year");
        return $timestamp ? date('F Y', $timestamp) : $value;
    }

    // Case 3: 25-Aug, 25-Sept → "Month YYYY"
    if (preg_match('/^(\d{2})-([A-Za-z]{3,})$/', $value, $matches)) {
        $year  = "20" . $matches[1]; // force to 20xx
        $month = $matches[2];
        $timestamp = strtotime("01-$month-$year");
        return $timestamp ? date('F Y', $timestamp) : $value;
    }

    return $value ?: "Unassigned";
}


function formatDate($dateStr) {
    $timestamp = strtotime($dateStr);
    return ($timestamp && $timestamp > 0) ? date('Y-m-d', $timestamp) : null;
}

function formatDateTime($dateStr) {
    $timestamp = strtotime($dateStr);
    return ($timestamp && $timestamp > 0) ? date('Y-m-d H:i:s', $timestamp) : null;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CSV Import | Student Access</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
     <link rel="shortcut icon" type="text/css" href="../img/logo1.svg">
    <style>
    :root {
        --main-color: #1B4965;
        --main-hover: #15394f; /* Darker for hover */
    }
    body {
        background-color: #f5f7fa;
        font-family: 'Segoe UI', sans-serif;
    }
    .container {
        max-width: 600px;
        margin-top: 60px;
    }
    .card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
    }
    .card-header {
        background-color: var(--main-color);
        color: white;
        border-top-left-radius: 1rem;
        border-top-right-radius: 1rem;
        font-size: 1.2rem;
    }
    .btn-primary {
        background-color: var(--main-color);
        border-color: var(--main-color);
        transition: background-color 0.3s ease, transform 0.2s ease;
    }
    .btn-primary:hover {
        background-color: var(--main-hover);
        border-color: var(--main-hover);
        transform: translateY(-1px);
    }
    .btn-home, .btn-secondary {
        background-color: var(--main-color);
        color: white;
        border-radius: 50px;
        padding: 8px 20px;
        font-weight: 500;
        text-decoration: none;
        display: inline-block;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }
    .btn-home:hover, .btn-secondary:hover {
        background-color: var(--main-hover);
        color: white;
        transform: translateY(-2px);
    }
</style>

</head>
<body>
    <div class="container">
        <!-- Top Buttons -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <!-- Home Button -->
            <a href="index.php" class="btn btn-home d-flex align-items-center">
                <i class="bi bi-house-door-fill me-1"></i> Home
            </a>

            <!-- Access History Button -->
            <?php if ($showHistoryButton): ?>
                <a href="access_history.php" class="btn btn-secondary d-flex align-items-center">
                    <i class="bi bi-bar-chart-fill me-1"></i> View Access History
                </a>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header text-center">
                Insert CSV for the student to have access.
            </div>
            <div class="card-body">
                <?php if (!empty($message)): ?>
                    <div class="alert <?= $messageClass ?>"><?= $message ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="csvFile" class="form-label">CSV File</label>
                        <input class="form-control" type="file" id="csvFile" name="csvFile" accept=".csv" required>
                        <div class="form-text">
                            Leave <strong>dateenrolled</strong> blank in the CSV — it will be set automatically to current Manila time.<br>
                              Leave <strong>dateexpired</strong> NULL <br>
                                Make the <strong style="color: red;">subMonth</strong> 1 or 2. How many months of subscription. <br>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Upload & Import</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
