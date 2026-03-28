<?php
session_start();
include('../../config.php');

// Handle form submission for adding questions
if (isset($_POST['submit'])) {
	$topic = $_POST['topic'];
	$concept = mysqli_real_escape_string($con, $_POST['concept']);
	$qnumber = mysqli_real_escape_string($con, $_POST['qnumber']);
	$question = mysqli_real_escape_string($con, $_POST['question']);
	$options = $_POST['options']; // This will be an array of options
	$wrong = mysqli_real_escape_string($con, $_POST['wrong']);
	$rationale = mysqli_real_escape_string($con, $_POST['rationale']);

	// New fields
	$narcan = mysqli_real_escape_string($con, $_POST['narcan']);
	$dlevel = mysqli_real_escape_string($con, $_POST['dlevel']);
	$cnc = mysqli_real_escape_string($con, $_POST['cnc']);
	$system = mysqli_real_escape_string($con, $_POST['system']);

	// Convert options array to JSON for storage
	$options_json = json_encode($options);

	// Handle correct answers
	$correctans_input = mysqli_real_escape_string($con, $_POST['correctans']); // Input as a string
	$correctans = array_map('trim', explode(',', $correctans_input)); // Split and trim whitespace
	$correctans_json = json_encode(array_map('intval', $correctans)); // Convert to integers and encode to JSON

	// Question type
	$type = 'SATA';

	// Prepare the query
	foreach ($topic as $rowhob) {
		$query = "INSERT INTO question (topic, topics1, qnumber, question, options, correctans, wrong, rationale, narcan, dlevel, cnc, system, type) 
                  VALUES ('$rowhob', '$concept', '$qnumber', '$question', '$options_json', '$correctans_json', '$wrong', '$rationale', '$narcan', '$dlevel', '$cnc', '$system', '$type')";

		// Execute the query
		if (!mysqli_query($con, $query)) {
			// Output error message
			echo "Error: " . mysqli_error($con);
			exit; // Stop execution on error
		}
	}

	// Redirect on success
	header("Location: questionlistsata.php");
	exit;
}

// Handle deletion of questions
if (isset($_GET['id'])) {
	$id = intval($_GET['id']); // Ensure $id is an integer
	$sql = "DELETE FROM question WHERE id = $id";
	if ($con->query($sql) === TRUE) {
		header("Location: questionlistsata.php");
		exit;
	}
}
?>