<?php
/**
 * Database Configuration
 * Simple version matching production
 */

// Detect environment
$isProduction = !in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']);

if ($isProduction) {
    // Hostinger Production
    $con = mysqli_connect('127.0.0.1', 'u436962267_studium', 'Nclexamplified2023', 'u436962267_studium') 
        or die('connection failed');
} else {
    // Local Development
    $con = mysqli_connect('localhost', 'root', '', 'u436962267_studium') 
        or die('connection failed');
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
