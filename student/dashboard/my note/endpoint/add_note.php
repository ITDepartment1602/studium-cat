<?php
include('../conn/conn.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_GET["id"];
    $noteTitle = $_POST["note_title"];
    $noteContent = $_POST["note_content"];
    $dateTime = date("Y-m-d H:i:s");

    try {
        $stmt = $conn->prepare("INSERT INTO tbl_notes (login_id,note_title, note, date_time) VALUES (:id,:note_title, :note, :date_time)");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':note_title', $noteTitle);
        $stmt->bindParam(':note', $noteContent);
        $stmt->bindParam(':date_time', $dateTime);
        $stmt->execute();
        echo "Note added successfully!";
        header("location: ../index.php?id=$id");
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        header("location: ../index.php?id=$id");
    }
}

?>