<?php
session_start([
    'cookie_lifetime' => 0, // Set session to last for 2 hours
]);
include '../config.php';
mysqli_select_db($con, 'u436962267_studium'); // Make sure to update the database name

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$pass = $_POST['password'];
$email = $_POST['email'];

$q = "SELECT * FROM login WHERE email='$email' AND password='$pass'";
$result = mysqli_query($con, $q);
$res = mysqli_fetch_assoc($result);
$num = mysqli_num_rows($result);
date_default_timezone_set('Asia/Manila');
$currentDate = date('Y-m-d H:i:s');

if ($num == 1) {
    // Check if the user is logged in
       if ($res['loginstatus'] !== 'Offline') {
        // Check if lastlogin is NULL
        if ($res['lastlogin'] !== NULL) {
            $lastLogin = $res['lastlogin'];
            $lastLoginTime = new DateTime($lastLogin);
            $currentDateTime = new DateTime($currentDate);
            $interval = $lastLoginTime->diff($currentDateTime);

            // If the difference in minutes is less than 2, restrict login
            if ($interval->i < 2 && $interval->h == 0) {
                $_SESSION['error'] = "You can't log in right now because you're already logged in on another device or browser. If you closed the browser without logging out, please wait 2 minutes before trying again for security reasons.";
                header('location: ../');
                exit;
            }
        }
    }

    // Update last login time and login status to 'Active now'
    $updateLoginTime = "UPDATE login SET lastlogin = '$currentDate', loginstatus = 'Active now' WHERE id = {$res['id']}";
    mysqli_query($con, $updateLoginTime);

    if ($res['status'] == 'admin') {
        // Generate 6-digit code
        $code = rand(100000, 999999);

        $hour = date('G');
        $Greetings = ($hour >= 5 && $hour < 12) ? "Good Morning" : (($hour >= 12 && $hour < 18) ? "Good Afternoon" : "Good Evening");

        $_SESSION['admin_id'] = $res['id'];
        $_SESSION['admin_code'] = $code;

        $mail = new PHPMailer(true);
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = 'html';

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'studiumail2025@gmail.com';
            $mail->Password = 'lgwzwmwipawsmjeb';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->setFrom('studiumail2025@gmail.com', 'OTP for Studium Admin');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'NCLEX Amplified Studium: Admin Login Code';
            $mail->Body = "Hello $Greetings!,<br>Your 6-digit verification code is: <br> 
            <b style='font-size: 20px;'>$code</b>
              <br><br>
              This is an automated message, please do not reply. If you did not request this one-time password, please disregard this email and contact us at <a href='mailto:nclexamplified@gmail.com'>nclexamplified@gmail.com</a>. 
              <br><br>
              <a href='https://m.me/NCLEX.Amplified.Official'>Contact us on Facebook</a>";

            $mail->send();
            header('Location: verify_admin.php');
            exit;

        } catch (Exception $e) {
            echo "Email could not be sent. Error: {$mail->ErrorInfo}";
            exit;
        }

    } else if ($res['type'] == 1) {
        $_SESSION['error'] = "Your account is disabled. Please contact support.";
        header('location: ../');
        exit;

    } else if ($currentDate > $res['dateexpired']) {
        $dateExpired = date('F j, Y', strtotime($res['dateexpired']));
        $_SESSION['error'] = "Your account has expired as of <b>$dateExpired</b>. Please contact technical support.";
        header('location: ../');
        exit;

    } else if ($res['status'] == 'user') {
        $_SESSION['user_id'] = $res['id'];
        $select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$res[id]'") or die('query failed');
        if (mysqli_num_rows($select) > 0) {
            $fetch = mysqli_fetch_assoc($select);
        }
        header('location:../student/dashboard/index.php?bundle_name=' . $fetch['bundle_name']);
        exit;
    }

} else {
    $_SESSION['error'] = "Email or Password is incorrect";
    header('location: ../');
    exit;
}

?>