<?php
include '../../../config.php';
$q = mysqli_query($con, "SELECT id, correctans FROM sata LIMIT 1");
$r = mysqli_fetch_assoc($q);
echo "SATA ID: " . $r['id'] . "\n";
echo "CorrectAns RAW: " . $r['correctans'] . "\n";
?>
