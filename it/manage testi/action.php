<?php
// Add / delete testimonials.
session_start();
include('../../config.php');

// Add a testimonial
if (isset($_POST['submit'])) {
    $stmt = mysqli_prepare($con, "INSERT INTO testimonial (message, name, credinial) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'sss', $_POST['message'], $_POST['name'], $_POST['credinial']);
    mysqli_stmt_execute($stmt);
    header("Location: index.php");
    exit;
}

// Delete a testimonial
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = mysqli_prepare($con, "DELETE FROM testimonial WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
}

header("Location: index.php");
exit;
