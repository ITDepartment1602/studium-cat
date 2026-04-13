<?php
/**
 * Login Validation
 * 
 * Handles user authentication with secure architecture.
 */

// config.php handles session, environment settings, and database initialization
require_once __DIR__ . '/../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Get sanitized inputs via global config helper
$email = post('email');
$password = post('password');

if (!$email || !$password) {
    redirect(BASE_URL . 'index.php', 'Please enter email and password', 'error');
}

// Authenticate user (supports both hashed and legacy plain-text passwords)
$user = authenticateUser($email, $password);

if (!$user) {
    redirect(BASE_URL . 'index.php', 'Email or Password is incorrect', 'error');
}

date_default_timezone_set('Asia/Manila');
$currentDate = date('Y-m-d H:i:s');

// Check if already logged in on another device (within a 2-minute window)
if ($user['loginstatus'] !== 'Offline' && !is_null($user['lastlogin'])) {
    $lastLogin = new DateTime($user['lastlogin']);
    $currentDateTime = new DateTime($currentDate);
    $interval = $lastLogin->diff($currentDateTime);

    if ($interval->i < 2 && $interval->h == 0) {
        redirect(BASE_URL . 'index.php', "You can't log in right now because you're already logged in on another device or browser. If you closed the browser without logging out, please wait 2 minutes before trying again.", 'error');
    }
}

// Update login time and status using modern db() helper
db()->execute(
    "UPDATE login SET lastlogin = ?, loginstatus = 'Active now' WHERE id = ?",
    [$currentDate, $user['id']]
);

// Admin login logic
if ($user['status'] == 'admin') {
    $code = rand(100000, 999999);
    $hour = date('G');
    $Greetings = ($hour >= 5 && $hour < 12) ? "Good Morning" : (($hour >= 12 && $hour < 18) ? "Good Afternoon" : "Good Evening");

    $_SESSION['admin_id'] = $user['id'];
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
        error_log("Email send failed: " . $mail->ErrorInfo);
        redirect(BASE_URL . 'index.php', 'Unable to send verification code. Please try again.', 'error');
    }

// Account lifecycle checks
} else if (isset($user['type']) && $user['type'] == 1) {
    redirect(BASE_URL . 'index.php', 'Your account is disabled. Please contact support.', 'error');

} else if (!is_null($user['dateexpired']) && $currentDate > $user['dateexpired']) {
    $dateExpired = date('F j, Y', strtotime($user['dateexpired']));
    redirect(BASE_URL . 'index.php', "Your account has expired as of <b>$dateExpired</b>. Please contact support.", 'error');

// Normal user login success redirection
} else if ($user['status'] == 'user') {
    // If dateexpired is NULL → user needs system activation
    if (is_null($user['dateexpired'])) {
        $_SESSION['pending_user_id']   = $user['id'];
        $_SESSION['pending_fullname']  = $user['fullname'];
        $_SESSION['pending_subMonth']  = $user['subMonth'];
        header('Location: ../student/dashboard/activate.php');
        exit;
    }

    // Set authenticated session
    loginUser($user);
    
    // Support legacy bundle_name redirection
    $bundleNameParam = isset($user['bundle_name']) ? ('?bundle_name=' . urlencode($user['bundle_name'])) : '';
    header('Location: ../student/dashboard/index.php' . $bundleNameParam);
    exit;
}

// Global fallback if something went wrong
redirect(BASE_URL . 'index.php', 'Authentication failed', 'error');
?>
