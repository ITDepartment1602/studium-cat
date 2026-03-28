<?php
include('conn/conn.php');

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['delete'])) {
    $noteID = $_GET['delete'];

    // Delete the note from the database
    $stmt = $conn->prepare("DELETE FROM `tbl_notes` WHERE tbl_notes_id = :note_id");
    $stmt->bindParam(':note_id', $noteID);

    if ($stmt->execute()) {
        // Redirect back to mynotes.php with a success message
        header("Location: https://nclexamplifiedreviewcenter.com/studium/student/dashboard/mynotes.php");
        exit();
    } else {
        // Redirect back to mynotes.php with an error message
        header("Location: https://nclexamplifiedreviewcenter.com/studium/student/dashboard/mynotes.php");
        exit();
    }
} else {
    // Redirect if accessed directly or without a valid note ID
    header("Location: https://nclexamplifiedreviewcenter.com/studium/student/dashboard/mynotes.php");
    exit();
}
?>
