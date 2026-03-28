<?php 
include '../config.php';

if ( isset($_GET['id']) ){
	$id = $_GET['id'];

	$sql = "DELETE from login where id=$id";
	$con->query($sql);
}

header("location: ../admin");
exit;

 ?>