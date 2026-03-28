<?php
include '../../../config.php';
$queries = [
    "ALTER TABLE exam_results ADD COLUMN earned_points INT(11) DEFAULT 0",
    "ALTER TABLE exam_results ADD COLUMN max_points INT(11) DEFAULT 1"
];
foreach ($queries as $q) {
    if (mysqli_query($con, $q)) {
        echo "Sucess: $q\n";
    } else {
        echo "Failed: $q (" . mysqli_error($con) . ")\n";
    }
}
?>