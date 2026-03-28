<?php
include 'config.php'; // Include your database configuration
session_start(); // Start the session

$user_id = $_SESSION['user_id'] ?? null; // Get user_id from session

if ($user_id) {
    // Update login status to 'Offline'
    $updateStatus = "UPDATE login SET loginstatus = 'Offline' WHERE id = $user_id";
    mysqli_query($con, $updateStatus);
}

// Clear all session variables and destroy the session
session_unset(); // Clear all session variables
session_destroy(); // Destroy the session

header('Location: index.php'); // Redirect to the home page or login page
exit();
?>