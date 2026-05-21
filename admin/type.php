<?php
include('../config.php');

$id = $_GET['id'];
$type = $_GET['type'];

$q="update login set type=$type where id=$id";
mysqli_query($con, $q);
header('location:../admin');
?> 