<?php
include '../../../../config.php';
$r = mysqli_fetch_assoc(mysqli_query($con, "SELECT correct_words FROM highlight WHERE id=3"));
echo "CORRECT RAW: " . str_replace(',', '|', $r['correct_words']) . "\n";
?>
