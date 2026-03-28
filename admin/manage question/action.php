<?php
session_start();
include('../../config.php');

// =====================================================================================================================================
// Add, update, and delete questions from manage_bundles.php

// Handle form submission for adding questions
if (isset($_POST['submit'])) {
	$topic = $_POST['topic'];
	$concept = $_POST['concept'];
	$qnumber = mysqli_real_escape_string($con, $_POST['qnumber']);
	$question = mysqli_real_escape_string($con, $_POST['question']);
	$choiceA = mysqli_real_escape_string($con, $_POST['choiceA']);
	$choiceB = mysqli_real_escape_string($con, $_POST['choiceB']);
	$choiceC = mysqli_real_escape_string($con, $_POST['choiceC']);
	$choiceD = mysqli_real_escape_string($con, $_POST['choiceD']);
	$correctans = mysqli_real_escape_string($con, $_POST['correctans']);
	$wrong = mysqli_real_escape_string($con, $_POST['wrong']);
	$rationale = mysqli_real_escape_string($con, $_POST['rationale']);

	// New fields
	$narcan = mysqli_real_escape_string($con, $_POST['narcan']);
	$dlevel = mysqli_real_escape_string($con, $_POST['dlevel']);
	$cnc = mysqli_real_escape_string($con, $_POST['cnc']);
	$system = mysqli_real_escape_string($con, $_POST['system']);

	foreach ($topic as $rowhob) {
		$query = "INSERT INTO question (topic, topics1, qnumber, question, choiceA, choiceB, choiceC, choiceD, correctans, wrong, rationale, narcan, dlevel, cnc, system) 
                  VALUES ('$rowhob', '$concept', '$qnumber', '$question', '$choiceA', '$choiceB', '$choiceC', '$choiceD', '$correctans', '$wrong', '$rationale', '$narcan', '$dlevel', '$cnc', '$system')";
		$query_run = mysqli_query($con, $query);
	}

	if ($query_run) {
		header("Location: questionlist.php");
		exit;
	}
}

// Handle deletion of questions
if (isset($_GET['id'])) {
	$id = intval($_GET['id']); // Ensure $id is an integer
	$sql = "DELETE FROM question WHERE id = $id";
	if ($con->query($sql) === TRUE) {
		header("Location: questionlist.php");
		exit;
	}
}
?>