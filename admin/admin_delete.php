<?php 
if ( isset($_GET['id']) ){
	$id = $_GET['id'];

$servername = "127.0.0.1";
$username = "u436962267_studium";
$password = "Nclexamplified2023";
$database = "u436962267_studium";

	$connection = new mysqli($servername, $username, $password, $database);

	$sql = "DELETE from login where id=$id";
	$connection->query($sql);
}

header("location: ../admin");
exit;

 ?>