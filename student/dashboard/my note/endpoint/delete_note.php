<?php 

// =====================================================================================================================================

if ( isset($_GET['id']) ){
    $id = $_GET['id'];
    $login_id = $_GET['login_id'];
    
    $servername = "127.0.0.1";
    $username = "u940051167_quiz";
    $password = "Nclexamplified2023";
    $database = "u940051167_quiz";

    $connect = new mysqli($servername, $username, $password, $database);

    $sql = "DELETE from tbl_notes where tbl_notes_id=$id";
    $connect->query($sql);
}

header("location: ../../note.php?id=$login_id");
exit;
 ?>