<?php
// Add / delete groups (from manage group/index.php and grouplist.php).
session_start();
include('../../config.php');

// Add a group
if (isset($_POST['submit'])) {
    $stmt = mysqli_prepare($con, "INSERT INTO grouplist (groupname) VALUES (?)");
    mysqli_stmt_bind_param($stmt, 's', $_POST['groupname']);
    mysqli_stmt_execute($stmt);
    header("Location: index.php");
    exit;
}

// Delete a group / member
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = mysqli_prepare($con, "DELETE FROM grouplist WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
}

header("Location: index.php");
exit;
