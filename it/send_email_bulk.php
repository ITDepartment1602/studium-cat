<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include '../config.php';
date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['ids'])) {
    header('Location: it_admin.php');
    exit;
}

$ids = array_map('intval', (array)$_POST['ids']);
$sent = 0;
$failed = 0;

foreach ($ids as $id) {
    if ($id <= 0) continue;

    // Check already sent
    $check = $con->prepare("SELECT id FROM email_sent_status WHERE student_id = ? LIMIT 1");
    $check->bind_param("i", $id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) continue;

    $stmt = $con->prepare("SELECT fullname, email, password, subMonth, studentnumber, groupname, bundle_name FROM login WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) { $failed++; continue; }

    $row      = $result->fetch_assoc();
    $name     = $row['fullname'];
    $email    = $row['email'];
    $password = $row['password'];
    $subMonth = $row['subMonth'];

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
        $mail->addAddress($email, $name);
        $mail->addEmbeddedImage('img/sigimg.png', 'sigimg');
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to NCLEX Amplified Review Center!';
        $mail->Body = "
<div style='font-family:Segoe UI, sans-serif; line-height:1.5; color:#333;'>
<p>Great day <strong>{$name}</strong>, USRN!</p>
<p>Good news! You can now enjoy <strong>{$subMonth} month/s of full access</strong> to
<strong>Studium CAT – Computer Adaptive Test</strong>, exclusively owned and developed by
<strong>NCLEX Amplified Review Center</strong>.</p>
<hr>
<p><strong>How to Access Studium CAT</strong></p>
<p>Open a web browser and go to: <a href='https://studium.cat'>https://studium.cat</a></p>
<p><strong>Log in using the credentials below:</strong></p>
<ul>
    <li>Email: <strong>{$email}</strong></li>
    <li>Password: <strong>{$password}</strong></li>
</ul>
<hr>
<p>Studium CAT is designed to simulate the real NCLEX experience.</p>
<p>If you need help, contact our Technical Support:<br>
<a href='https://www.facebook.com/NCLEXAmplifiedTechSupport/'>Facebook Support Page</a></p>
<br>--<br>
Warmest regards,<br>
<strong><span style='color:#3A53A7;'>NCLEX </span><span style='color:#DB2523;'>Amplified </span>Review Center</strong><br>
Bacoor, Cavite, Philippines<br>
<img src='cid:sigimg' style='max-width:500px;width:100%;'>
</div>";
        $mail->send();

        $insert = $con->prepare("INSERT IGNORE INTO email_sent_status (student_id) VALUES (?)");
        $insert->bind_param("i", $id);
        $insert->execute();
        $sent++;
    } catch (Exception $e) {
        $failed++;
    }
}

header("Location: import_csv.php?sent=$sent&failed=$failed");
exit;
?>
