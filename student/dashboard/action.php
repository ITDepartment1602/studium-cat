<?php 

	// feedback -------------------------------------------------------------------------------------------

 session_start();

include('../../config.php');


if(isset($_POST['send']))
{	
	$topic = $_POST['topic'];
	$question = $_POST['question'];
	$studentnumber = $_POST['studentnumber'];
	$message = $_POST['message'];

		// echo $rowhob;
		$query ="INSERT INTO feedback (topic,question,studentnumber,message) VALUES ('$topic','$question','$studentnumber','$message')";
		$query_run = mysqli_query($con, $query);
	
	if($query_run)
	{
		$_SESSION['status'] = " Courses Inserted";
		header("location:question/question1.php?topic=$topic&qnumber=$question");
	}
	else
	{
		$_SESSION['status'] = " Courses Not Inserted";
		header("location:question/question1.php?topic=$topic&qnumber=$question");
	}
}

?>