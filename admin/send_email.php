<?php


error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// CHECK IF ID IS PROVIDED
if (!isset($_GET['id'])) {
    die("Missing student ID.");
}

$id = $_GET['id'];

// CONNECT TO DATABASE
include '../config.php';

// GET STUDENT DATA
$stmt = $con->prepare("
    SELECT 
        fullname,
        email,
        password,
        subMonth,
        studentnumber,
        groupname,
        bundle_name
    FROM login 
    WHERE id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Student not found.");
}

$row = $result->fetch_assoc();

// ASSIGN VARIABLES
$name            = $row['fullname'];
$subMonth        = $row['subMonth'];
$email           = $row['email'];
$password        = $row['password'];
$student_number  = $row['studentnumber'];
$group_number    = $row['groupname'];
$bundle_name     = $row['bundle_name']; // if you need it for email

$mail = new PHPMailer(true);

try {

    // SMTP CONFIG
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'nclexamplified@gmail.com';
    $mail->Password   = 'xpcw mohk pzyj mlux'; 
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    // SENDER
    $mail->setFrom('nclexamplified@gmail.com', 'NCLEX Amplified Review Center');

    // RECEIVER
    $mail->addAddress($email, $name);

    // EMBED SIGNATURE IMAGE (CHECK PATH!)
    $mail->addEmbeddedImage('img/sigimg.png', 'sigimg');

    // EMAIL CONTENT
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

<p>
Open a web browser (Google Chrome, Microsoft Edge, Safari, or any browser you prefer)<br>
Go to the following URL:<br>
<a href='https://studium.cat' target='_blank' rel='noopener'>https://studium.cat</a>
</p>

<p><strong>Log in using the credentials below:</strong></p>
<ul>
    <li>Email: <strong>{$email}</strong></li>
    <li>Password: <strong>{$password}</strong></li>
</ul>

<hr>

<p>
Studium CAT is designed to simulate the real NCLEX experience, helping you strengthen 
your test-taking strategies, improve clinical judgment, and monitor your progress through 
adaptive, exam-style questions.
</p>

<p>
If you encounter any issues accessing the platform or need assistance, our Technical 
Support Team is always ready to help.<br>
<a href='https://www.facebook.com/NCLEXAmplifiedTechSupport/'>https://www.facebook.com/NCLEXAmplifiedTechSupport/</a></p>
</p>

<p>
We’re excited to be part of your NCLEX journey and are committed to supporting you every 
step of the way.
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


   // SEND EMAIL
$mail->send();
$insert = $con->prepare("INSERT INTO email_sent_status (student_id) VALUES (?)");
$insert->bind_param("i", $id);
$insert->execute();

$status = "success";
header("Location: access_history.php?email=success");
exit();

} catch (Exception $e) {
   $status = "error";
header("Location: access_history.php?email=failed");
exit();

}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Status</title>
    <style>
        body {
            font-family: Segoe UI, sans-serif;
            background: #f2f2f2;
            padding: 40px;
        }
        .modal-box {
            max-width: 450px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.15);
        }
        .success {
            color: green;
            font-size: 22px;
            font-weight: bold;
        }
        .error {
            color: red;
            font-size: 22px;
            font-weight: bold;
        }
        a.btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 18px;
            background: #333;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
        }
    </style>
</head>
<body>

<div class="modal-box">
    <?php if ($status === "success") { ?>
        <div class="success">Email Sent ✔</div>
    <?php } else { ?>
        <div class="error">Email Failed ✖</div>
    <?php } ?>

    <p><?php echo $message; ?></p>

    <a href="bookhistory.php" class="btn">Back</a>
</div>

</body>
</html>
