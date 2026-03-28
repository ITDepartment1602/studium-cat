<?php

session_start();

include '../../../config.php';

$user_id=$_GET['id'];
$qid=$_GET['qid'];
$kilanlan=$_GET['kilanlan'];

 $q="select * from exam_mode";

 $result=mysqli_query($con,$q);
 $res=mysqli_fetch_assoc($result);
 $num=mysqli_num_rows($result);

 if ($num==1)
  {

    if ($res['sahi']=='3')
      {
        header("location:pass.php");
      }

    if ($res['wrong']=='2')
      {
        header("location:failed.php");
      }

    if ($res['sahi']=='1')
      {
        header("location:question4.php?q=quiz&step=2n=4&id=$user_id&qid=$qid&kilanlan=$kilanlan");
      }


  }
   else
   {
    $_SESSION['error']="error";
    header('location:index');

   }
?>
