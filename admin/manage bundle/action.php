<?php 

	// in this file --> code for add a new video course ,update a video course and delete a video course by admin from manage_bundles.php

session_start();

include('../../config.php');

// =====================================================================================================================================
// ========================================================================================================================
     // in this section add videos ,update videos and delete videos is going on from manage_bundles.php

if (isset($_POST['submit'])) {
$bundle_name=$_POST['bundle_name'];


	$q="insert into bundle(bundle_name) values('$bundle_name')";
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

	$sql = "DELETE from bundle where id=$id";
	$con->query($sql);
}

header("location: index.php");

// ============================================================================================


if(isset($_POST['createpackage']))
{	
	$bundle_name = $_POST['bundle_name'];
	$bundlelist_name = $_POST['bundlelist_name'];

	foreach($bundlelist_name as $rowhob)
	{
		// echo $rowhob;
		$query ="INSERT INTO bundlelist (bundle_name,bundlelist_name) VALUES ('$bundle_name','$rowhob')";
		$query_run = mysqli_query($con, $query);
	}

	if($query_run)
	{
		$_SESSION['status'] = " Courses Inserted";
		header("location:bundlelist.php?bundle_name=$bundle_name&status=added");
	}
	else
	{
		$_SESSION['status'] = " Courses Not Inserted";
		header("location:bundlelist.php?bundle_name=$bundle_name&status=added");
	}
}


// ============================================================================================

if ( isset($_GET['id']) ){
	$id = $_GET['id'];

	$sql = "DELETE from bundlelist where id=$id";
	$con->query($sql);
}

header("location: index.php");