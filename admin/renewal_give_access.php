<?php
session_start();
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin_login'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized. Please log in to the admin panel again.']);
    exit;
}

header('Content-Type: application/json');

include '../config.php';
include 'sheets_helper.php';
include 'tally_helper.php';

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$input = json_decode(file_get_contents('php://input'), true);

$rowIndex = (int)($input['row_index'] ?? -1);
$studentNumber = trim($input['student_number'] ?? '');
$months = (int)($input['months'] ?? 0);

if ($rowIndex < 0 || $studentNumber === '' || $months <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid request.']);
    exit;
}

try {
    // Re-check the sheet row hasn't already been processed or changed
    $status = sheets_get_renewal_row_status($rowIndex);
    if ($status['student_number'] !== $studentNumber || $status['is_given']) {
        echo json_encode(['ok' => false, 'error' => 'This entry was already processed or changed. Please refresh.']);
        exit;
    }

    // Look up the student
    $stmt = mysqli_prepare($con, "SELECT id, fullname, email, dateexpired FROM login WHERE studentnumber = ?");
    mysqli_stmt_bind_param($stmt, "s", $studentNumber);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $student = mysqli_fetch_assoc($result);

    if (!$student) {
        echo json_encode(['ok' => false, 'error' => "Student number {$studentNumber} not found in the system."]);
        exit;
    }

    // Compute new expiration: extend from current expiry if still active, else from now
    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $current = null;
    if (!empty($student['dateexpired']) && $student['dateexpired'] !== '0000-00-00 00:00:00') {
        $current = new DateTime($student['dateexpired'], new DateTimeZone('Asia/Manila'));
    }
    $base = ($current && $current > $now) ? $current : $now;
    $newExpiry = (clone $base)->modify("+{$months} months");
    $newExpiryStr = $newExpiry->format('Y-m-d H:i:s');

    // Update DB
    $update = mysqli_prepare($con, "UPDATE login SET dateexpired = ?, subMonth = ? WHERE studentnumber = ?");
    mysqli_stmt_bind_param($update, "sis", $newExpiryStr, $months, $studentNumber);
    mysqli_stmt_execute($update);

    // Update daily tally
    $tallyKey = sheets_subscription_tally_key(['months' => $months, 'free' => (bool)($input['free'] ?? false)]);
    if ($tallyKey !== null) {
        tally_increment([$tallyKey => 1]);
    }

    // Send renewal notification email (non-fatal on failure)
    $emailSent = false;
    if (!empty($student['email'])) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'nclexamplified@gmail.com';
            $mail->Password   = 'xpcw mohk pzyj mlux';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('nclexamplified@gmail.com', 'NCLEX Amplified Review Center');
            $mail->addAddress($student['email'], $student['fullname']);
            $mail->addEmbeddedImage(__DIR__ . '/img/sigimg.png', 'sigimg');

            $mail->isHTML(true);
            $mail->Subject = 'Your Studium CAT Access Has Been Renewed!';

            $newExpiryDisplay = $newExpiry->format('F j, Y');
            $name = $student['fullname'];

            $mail->Body = "
<div style='font-family:Segoe UI, sans-serif; line-height:1.5; color:#333;'>

<p>Great day <strong>{$name}</strong>, USRN!</p>

<p>Good news! Your access to <strong>Studium CAT &ndash; Computer Adaptive Test</strong>,
exclusively owned and developed by <strong>NCLEX Amplified Review Center</strong>,
has been renewed for <strong>{$months} month/s</strong>.</p>

<hr>

<p><strong>Your access is now valid until: {$newExpiryDisplay}</strong></p>

<p>
You can continue using Studium CAT at:<br>
<a href='https://studium.cat' target='_blank' rel='noopener'>https://studium.cat</a>
</p>

<hr>

<p>
If you encounter any issues accessing the platform or need assistance, our Technical
Support Team is always ready to help.<br>
<a href='https://www.facebook.com/NCLEXAmplifiedTechSupport/'>https://www.facebook.com/NCLEXAmplifiedTechSupport/</a>
</p>

<p>
Thank you for continuing your NCLEX journey with us!
</p>

<br>
--<br>
Warmest regards,<br>
<strong>
<span style='color:#3A53A7;'>NCLEX </span>
<span style='color:#DB2523;'>Amplified </span>Review Center
</strong><br>
Bacoor, Cavite, Philippines<br>
Phone: +639 16 529 7237 / +639 26 102 1491<br>
Facebook:
<a href='https://www.facebook.com/NCLEXAmplifiedReviewCenter'>
https://www.facebook.com/NCLEXAmplifiedReviewCenter
</a><br>

<img src='cid:sigimg' style='max-width:500px; width:100%;'>
</div>
";

            $mail->send();
            $emailSent = true;
        } catch (Exception $e) {
            $emailSent = false;
        }
    }

    // Mark the sheet row as given.  Access has already been granted at this
    // point, so a failure here is reported as a warning rather than an error:
    // telling the admin it failed outright would make them grant it twice.
    $sheetMarked = true;
    $sheetError  = null;
    try {
        sheets_mark_renewal_given($rowIndex);
    } catch (Throwable $e) {
        $sheetMarked = false;
        $sheetError  = $e->getMessage();
        error_log('[renewal_give_access] access granted but sheet not marked for row ' . $rowIndex . ': ' . $sheetError);
    }

    echo json_encode([
        'ok'             => true,
        'new_expiration' => $newExpiry->format('F j, Y'),
        'email_sent'     => $emailSent,
        'sheet_marked'   => $sheetMarked,
        'sheet_error'    => $sheetError,
    ]);
} catch (Throwable $e) {
    // Throwable, not Exception: the PHPMailer Exception import at the top of
    // this file makes an unqualified "Exception" mean PHPMailer's own
    // exception class, so Sheets/DB errors used to escape this catch as fatals
    // and the endpoint returned a PHP error page instead of JSON.
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
