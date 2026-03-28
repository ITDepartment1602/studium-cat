<?php 

	// in this file --> code for add a new video course ,update a video course and delete a video course by admin from manage_bundles.php

session_start();

include('../../config.php');

// =====================================================================================================================================
// ========================================================================================================================
     // in this section add videos ,update videos and delete videos is going on from manage_bundles.php

if (isset($_POST['submit'])) {
$message=addslashes($_POST['message']);
$name=$_POST['name'];
$credinial=addslashes($_POST['credinial']);



	$q="insert into testimonial(message,name,credinial) values ('$message','$name','$credinial')";
	$r=mysqli_query($con,$q);

 if ($r==true)
  {

			 header("location:index.php");
    }
	
 }

// =====================================================================================================================================
if ( isset($_GET['id']) ){
	$id = $_GET['id'];

	$sql = "DELETE from testimonial where id=$id";
	$con->query($sql);
}

header("location: index.php");




?>