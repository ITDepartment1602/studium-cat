<?php

$servername = "127.0.0.1";
$username = "u436962267_studium";
$password = "Nclexamplified2023";
$db = "u436962267_studium";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$db", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "";
} catch (PDOException $e) {
    echo "Failed " . $e->getMessage();
}

?>