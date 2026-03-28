<?php 

// =====================================================================================================================================

include '../../../../config.php';

if ( isset($_GET['id']) ){
    $id = $_GET['id'];
    $login_id = $_GET['login_id'];
    
    mysqli_select_db($con, DB_QUIZ_NAME);

    $sql = "DELETE from tbl_notes where tbl_notes_id=$id";
    $con->query($sql);
}

header("location: ../../note.php?id=$login_id");
exit;
 ?>