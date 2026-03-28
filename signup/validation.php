<?php 

  // in this file --> code for add a new video course ,update a video course and delete a video course by admin from manage_bundles.php

session_start();

include('../config.php');

// =====================================================================================================================================
// ========================================================================================================================
     // in this section add videos ,update videos and delete videos is going on from manage_bundles.php

if (isset($_POST['submit'])) {
$email=$_POST['email'];
$fullname=addslashes($_POST['fullname']);
$facebookname=addslashes($_POST['facebookname']);
$contactnumber=$_POST['contactnumber'];
$address=addslashes($_POST['address']);
$rcenter=addslashes($_POST['rcenter']);
$agree=$_POST['agree'];



  $q="insert into signup(email,fullname,facebookname,contactnumber,address,rcenter,agree,status) values ('$email','$fullname','$facebookname','$contactnumber','$address','$rcenter','$agree','1')";
  $r=mysqli_query($con,$q);

 if ($r==true)
  {

       header("location:success.php");
    }
  
 }