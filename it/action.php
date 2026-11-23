<?php
// Add / delete students (from the dashboard add-student form).
session_start();
include('../config.php');

// Add a student
if (isset($_POST['submit'])) {
    // Empty date inputs are stored as NULL; access_date is when the account is created (Manila time).
    $dateenrolled = trim($_POST['dateenrolled'] ?? '') !== '' ? $_POST['dateenrolled'] : null;
    $dateexpired  = trim($_POST['dateexpired'] ?? '') !== '' ? $_POST['dateexpired'] : null;
    $access_date  = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d H:i:s');

    $stmt = mysqli_prepare(
        $con,
        "INSERT INTO login
           (studentnumber, fullname, bundle_name, groupname, dateenrolled, access_date, dateexpired, email, password, status)
         VALUES (?,?,?,?,?,?,?,?,?,?)"
    );
    mysqli_stmt_bind_param(
        $stmt, 'ssssssssss',
        $_POST['studentnumber'], $_POST['fullname'], $_POST['bundle_name'], $_POST['groupname'],
        $dateenrolled, $access_date, $dateexpired, $_POST['email'], $_POST['password'], $_POST['status']
    );
    mysqli_stmt_execute($stmt);
    header("Location: index.php");
    exit;
}

// Delete a student
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = mysqli_prepare($con, "DELETE FROM login WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
}

header("Location: index.php");
exit;
