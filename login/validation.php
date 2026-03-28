<?php
session_start([
    'cookie_lifetime' => 0, // session until browser closes
]);
include '../config.php';
mysqli_select_db($con, 'u436962267_studium'); 

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
    // Check if already logged in
    if ($res['loginstatus'] !== 'Offline') {
        if ($res['lastlogin'] !== NULL) {
            $lastLogin = $res['lastlogin'];
            $lastLoginTime = new DateTime($lastLogin);
            $currentDateTime = new DateTime($currentDate);
            $interval = $lastLoginTime->diff($currentDateTime);

            if ($interval->i < 2 && $interval->h == 0) {
                $_SESSION['error'] = "You can't log in right now because you're already logged in on another device or browser. If you closed the browser without logging out, please wait 2 minutes before trying again.";
                header('location: ../');
                exit;
            }
        }
    }

    // Update last login time and login status
    $updateLoginTime = "UPDATE login SET lastlogin = '$currentDate', loginstatus = 'Active now' WHERE id = {$res['id']}";
    mysqli_query($con, $updateLoginTime);

    // Admin login with OTP
    if ($res['status'] == 'admin') {
        $code = rand(100000, 999999);
        $hour = date('G');
        $Greetings = ($hour >= 5 && $hour < 12) ? "Good Morning" : (($hour >= 12 && $hour < 18) ? "Good Afternoon" : "Good Evening");

        $_SESSION['admin_id'] = $res['id'];
        $_SESSION['admin_code'] = $code;

        $mail = new PHPMailer(true);
        $mail->SMTPDebug = 0;

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'nclexotp@gmail.com';
            $mail->Password = 'fpzfivmvuurazste';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->setFrom('nclexotp@gmail.com', 'NCLEX Amplified');
            $mail->addAddress('maverickdelacruzleocadio@gmail.com');

            $mail->isHTML(true);
            $mail->Subject = 'NCLEX Amplified Studium: Admin Login Code';
            $mail->Body = "Hello $Greetings!,<br>Your 6-digit verification code is: 
            <b style='font-size: 20px;'>$code</b>
            <br><br>This is an automated message, please do not reply. 
            <br><br><a href='https://m.me/NCLEX.Amplified.Official'>Contact us on Facebook</a>";

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

    } else if (!is_null($res['dateexpired']) && $currentDate > $res['dateexpired']) {
        // Account expired
        $dateExpired = date('F j, Y', strtotime($res['dateexpired']));
        $_SESSION['error'] = "Your account has expired as of <b>$dateExpired</b>. Please contact support.";
        header('location: ../');
        exit;

    } else if ($res['status'] == 'user') {
        // If dateexpired is NULL → redirect to activation modal
        if (is_null($res['dateexpired'])) {
            $_SESSION['pending_user_id']   = $res['id'];
            $_SESSION['pending_fullname']  = $res['fullname'];
            $_SESSION['pending_subMonth']  = $res['subMonth'];
            header('location: ../student/dashboard/activate.php');
            exit;
        }

        // Otherwise proceed normally
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
