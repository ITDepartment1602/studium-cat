<?php
include 'config.php'; // handles session_start safely
// Note: DO NOT call session_start() again — config.php already does it

$user_id = $_SESSION['user_id'] ?? null; // Get user_id from session

if ($user_id) {
    // Update login status to 'Offline'
    $updateStatus = "UPDATE login SET loginstatus = 'Offline' WHERE id = $user_id";
    mysqli_query($con, $updateStatus);
}

// Clear all session variables and destroy the session
session_unset(); // Clear all session variables
session_destroy(); // Destroy the session

header('Location: ' . BASE_URL . 'index.php'); // Redirect to login page
exit();
?>