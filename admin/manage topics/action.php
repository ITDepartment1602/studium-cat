<?php
// Add / delete topics (from manage topics/index.php).
session_start();
include('../../config.php');

// Add a topic (with cover image upload)
if (isset($_POST['submit'])) {
    $title       = $_POST['title'];
    $description = $_POST['description'] ?? '';
    $image       = $_FILES['image'];

    $fileext  = explode('.', $image['name']);
    $filecheck = strtolower(end($fileext));
    $allowed  = ['png', 'jpg', 'jpeg'];

    if (in_array($filecheck, $allowed)) {
        $destinationfile = 'cover img/' . $image['name'];
        move_uploaded_file($image['tmp_name'], $destinationfile);

        $stmt = mysqli_prepare($con, "INSERT INTO topics (image, description, title) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sss', $destinationfile, $description, $title);
        mysqli_stmt_execute($stmt);
        header("Location: index.php");
        exit;
    }
}

// Delete a topic
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = mysqli_prepare($con, "DELETE FROM topics WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
}

header("Location: index.php");
exit;
