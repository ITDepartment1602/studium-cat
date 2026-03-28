<?php 

	// in this file --> code for add a new video course ,update a video course and delete a video course by admin from manage_bundles.php

session_start();

include('../../config.php');

// =====================================================================================================================================
// ========================================================================================================================
     // in this section add videos ,update videos and delete videos is going on from manage_bundles.php

if (isset($_POST['submit'])) {
$title=$_POST['title'];
$description=$_POST['description'];
$image=$_FILES['image'];

$filename=$image['name'];
print_r($image);		
$fileerror=$image['error'];   
$filetmp=$image['tmp_name'];


$fileext=explode('.', $filename);
$filecheck=strtolower(end($fileext));

$fileextstored= array('png','jpg','jpeg' );

if (in_array($filecheck,$fileextstored)) {
	$destinationfile='cover img/'.$filename;
	move_uploaded_file($filetmp,$destinationfile);

	$q="insert into topics(image,description,title) values('$destinationfile','$description','$title')";
	$r=mysqli_query($con,$q);

 if ($r==true)
  {

			 header("location:index.php");
    }
	
 }
}
// ============================================================================================

				// code to add a new video course by admin from manage_bundles.php

if ( isset($_GET['id']) ){
	$id = $_GET['id'];

	$sql = "DELETE from topics where id=$id";
	$con->query($sql);
}

header("location: index.php");
exit;