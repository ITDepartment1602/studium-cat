<?php
include 'config.php';
session_start();

// Get schema from an existing question type table
$result = mysqli_query($con, "DESCRIBE mpr");
echo "Schema of MPR table:\n";
while($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
