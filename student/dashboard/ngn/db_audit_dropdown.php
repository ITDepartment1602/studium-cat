<?php
include '../../../config.php';
$q = mysqli_query($con, "SELECT correct_words, options FROM dropdown_questions LIMIT 1");
$r = mysqli_fetch_assoc($q);
print_r($r);
?>
