<?php
// Centralized Database Configuration - Auto switches between Local and Production
include __DIR__ . '/../../../config.php';

// Provide PDO $conn for mynotes.php (uses PDO while the rest of the app uses MySQLi $con)
if (!isset($conn)) {
    try {
        $conn = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
            DB_USER,
            DB_PASS
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        error_log('PDO conn error: ' . $e->getMessage());
        $conn = null;
    }
}
?>