<?php
include '../../../config.php';
$q = mysqli_query($con, "SHOW TABLES");
while($r = mysqli_fetch_array($q)) {
    echo $r[0] . "\n";
}
?>
