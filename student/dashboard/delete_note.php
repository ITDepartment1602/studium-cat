<?php
include '../../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}
$user_id = intval($_SESSION['user_id']);

if (isset($_GET['delete'])) {
    $noteId = intval($_GET['delete']);
    mysqli_query($con, "DELETE FROM tbl_notes WHERE tbl_notes_id = '$noteId' AND login_id = '$user_id'");
}

header('Location: mynotes.php');
exit;
