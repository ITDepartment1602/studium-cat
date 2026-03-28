<?php 
if ( isset($_GET['id']) ){
	$id = $_GET['id'];

$servername = "127.0.0.1";
$username = "u940051167_quiz";
$password = "Nclexamplified2023";
$database = "u940051167_quiz";

	$connection = new mysqli($servername, $username, $password, $database);

	$sql = "DELETE from login where id=$id";
	$connection->query($sql);
}

header("location: ../admin");
exit;

 ?>