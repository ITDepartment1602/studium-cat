<?php 
include('../../../config.php');

if(isset($_POST['send']))
{   
    $login_id = $_POST['login_id'];
    $name = $_POST['name'];
    $question = $_POST['question'];
    $message = $_POST['message'];

        // echo $rowhob;
        $query ="INSERT INTO feedback (login_id,topic,name,question,message) VALUES ('$login_id','$topic','$name','$question','$message')";
        $query_run = mysqli_query($con, $query);
}

?>

<a id="myBtn" class="sidebar-link" style="cursor: pointer;" title="Feedback"><i class="fas fa-pencil-square"></i></a>

<div id="myModal" class="modala">
  <div class="modal-contenta">
    <span class="close">&times;</span>
        <center><p style="color: black; font-size:18px;"><b>Feedback</b></p></center>
          <form method='POST' enctype="multipart/form-data">
              <input type="hidden" name="login_id" value="<?php echo $_GET['id'] ?>">
              <input type="hidden" name="topic" value="<?php echo $_GET['kilanlan'] ?>">
              <input type="hidden" name="name" value="<?php echo $_GET['topic'] ?>">
              <textarea spellcheck="false" name="message" placeholder="Enter your message"></textarea>
              <div class="button">
                  <button type="submit" class="send" name="send">Send</button>
              </div>
          </form>
  </div>
</div>