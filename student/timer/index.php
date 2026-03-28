<?php 
$start = microtime (true);
include 'db.inc.php';
$stmt = $db->query("SELECT * FROM invlist"); 
echo $stmt->rowCount()." records<br>";
$end = microtime (true);
echo round ($end - $start,2)." seconds";
?>