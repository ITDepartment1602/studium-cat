<?php
include '../../../config.php';
$q = mysqli_query($con, 'DESCRIBE login');
while($row=mysqli_fetch_assoc($q)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
