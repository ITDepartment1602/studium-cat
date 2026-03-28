<?php
include('../../../config.php');

if (isset($_POST['send'])) {
    $login_id = $_POST['login_id'];
    $name = $_POST['name'];
    $message = $_POST['message'];
    $question_id = $_POST['question']; // Get the question ID from the POST data

    // Sanitize inputs to prevent SQL injection
    $login_id = mysqli_real_escape_string($con, $login_id);
    $name = mysqli_real_escape_string($con, $name);
    $message = mysqli_real_escape_string($con, $message);
    $question = isset($_SESSION['question_id']) ? $_SESSION['question_id'] : $_POST['question'];

    $query = "INSERT INTO feedback (login_id, name, question, message) VALUES ('$login_id', '$name', '$question', '$message')";
    $query_run = mysqli_query($con, $query);

    if ($query_run) {
        echo "<script src='https://unpkg.com/sweetalert/dist/sweetalert.min.js'></script>";
        echo "<script>
        swal({
            title: 'Success!',
            text: 'Feedback sent successfully!',
            icon: 'success',
            button: 'OK',
        }).then(function() {
            
        });
        </script>";
    } else {
        echo "<script src='https://unpkg.com/sweetalert/dist/sweetalert.min.js'></script>";
        echo "<script>
        swal({
            title: 'Error!',
            text: 'Failed to send feedback: " . mysqli_error($con) . "',
            icon: 'error',
            button: 'OK',
        });
        </script>";
    }
}
?>

<a id="myBtn" class="sidebar-link" style="cursor: pointer;" title="Feedback"><i class="fas fa-pencil-square"></i></a>

<div id="myModal" class="modala">
    <div class="modal-contenta">
        <span class="close">&times;</span>

        <center>
            <p style="color: black; font-size:18px;"><b>Feedback</b></p>
        </center>

        <form method='POST' enctype="multipart/form-data">
            <input type="hidden" name="login_id" value="<?php echo $_GET['id']; ?>">
            <input type="hidden" name="name" value="<?php echo $_GET['topics1']; ?>">
            <input type="hidden" name="question" id="question_id"
                value="<?php echo isset($_GET['qq']) ? $_GET['qq'] : ''; ?>">
            <!-- Hidden input for question ID -->
            <textarea spellcheck="false" name="message" placeholder="Enter your message"></textarea>
            <div class="button">
                <button type="submit" class="send" name="send" style="background-color: #1B4965;">Send</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Function to set the question ID when opening the modal
    function setQuestionId(questionId) {
        document.getElementById('question_id').value = questionId;
    }

    // Example of how to call this function when opening the modal
    document.getElementById('myBtn').onclick = function () {
        // Replace with the actual question ID you want to set
        var questionId = /* Logic to get the question ID from the context */;
        setQuestionId(questionId);
        document.getElementById("myModal").style.display = "block";
    };
</script>