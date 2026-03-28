<?php
include 'config.php';
session_start();

$tables = ['traditional', 'mcq', 'mpr', 'mmr', 'sata', 'bowtie', 'dragndrop', 'dropdown', 'highlight'];

foreach ($tables as $table) {
    $r = mysqli_query($con, "SELECT COUNT(*) as cnt FROM $table");
    $d = mysqli_fetch_assoc($r);
    echo $table . ": " . $d['cnt'] . " questions\n";
}
?>
