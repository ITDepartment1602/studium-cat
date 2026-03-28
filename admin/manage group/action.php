<?php 

	// in this file --> code for add a new video course ,update a video course and delete a video course by admin from manage_bundles.php

session_start();

include('../../config.php');

// =====================================================================================================================================
// ========================================================================================================================
     // in this section add videos ,update videos and delete videos is going on from manage_bundles.php

if (isset($_POST['submit'])) {
$groupname=$_POST['groupname'];

	$q="insert into grouplist(groupname) values('$groupname')";
	$r=mysqli_query($con,$q);

 if ($r==true)
  {

			 header("location:index.php");
    }
	
 }
// ============================================================================================

				// code to add a new video course by admin from manage_bundles.php

if ( isset($_GET['id']) ){
	$id = $_GET['id'];

	$sql = "DELETE from grouplist where id=$id";
	$con->query($sql);
}

header("location: index.php");
exit;