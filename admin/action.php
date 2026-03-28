<?php 

	// in this file --> code for add a new video course ,update a video course and delete a video course by admin from manage_bundles.php

session_start();

include('../config.php');

// =====================================================================================================================================
// ========================================================================================================================
     // in this section add videos ,update videos and delete videos is going on from manage_bundles.php

if (isset($_POST['submit'])) {
$studentnumber=$_POST['studentnumber'];
$fullname=$_POST['fullname'];
$bundle_name=$_POST['bundle_name'];
$groupname=$_POST['groupname'];
$dateenrolled=$_POST['dateenrolled'];
$dateexpired=$_POST['dateexpired'];
$email=$_POST['email'];
$password=$_POST['password'];
$status=$_POST['status'];



	$q="insert into login(studentnumber,fullname,bundle_name,groupname,dateenrolled,dateexpired,email,password,status) values ('$studentnumber','$fullname','$bundle_name','$groupname','$dateenrolled','$dateexpired','$email','$password','$status')";
	$r=mysqli_query($con,$q);

 if ($r==true)
  {

			 header("location:index.php");
    }
	
 }

// =====================================================================================================================================
if ( isset($_GET['id']) ){
	$id = $_GET['id'];

	$sql = "DELETE from login where id=$id";
	$con->query($sql);
}

header("location: index.php");




?>