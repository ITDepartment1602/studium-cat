<?php
include '../../../config.php';
$r = mysqli_query($con, 'DESCRIBE exam_results');
if(!$r) die("Error: " . mysqli_error($con));
while($f = mysqli_fetch_assoc($r)) {
    echo $f['Field'] . " (" . $f['Type'] . ")\n";
}
?>
