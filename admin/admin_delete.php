<?php
include '../config.php';
include 'tally_helper.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $con->query("DELETE FROM login WHERE id=$id");

    // Track deletion in today's tally file (only if file exists for today)
    if (tally_read()) {
        tally_increment(['deleted_today' => 1]);
    }
}

header("location: ../admin");
exit;
