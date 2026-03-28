<?php
include '../../config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $rating = $_POST['rating'];
    $review = $_POST['review'];

    $stmt = $con->prepare("INSERT INTO studentReviews (studentId, rating, review) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $rating, $review);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }

    $stmt->close();
}
?>