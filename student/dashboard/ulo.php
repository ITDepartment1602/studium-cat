<?php

include '../../../config.php';
session_start();
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
   <html lang="en">
   <html oncontextmenu="return false" onselectstart="return false" ondragstart="return false">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">

      <!--=============== REMIXICONS ===============-->
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.min.css">
      <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet" />

      <!--=============== CSS ===============-->
      <link rel="stylesheet" href="../css/start.css">
      <link rel="stylesheet" href="../css/feedback.css">
      <link rel="stylesheet" href="../css/sidebar1.css">

      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
      <title>NCLEX Amplified</title>
   </head>
   <style type="text/css">
      .question{
      background-color: #04AA6D; /* Green */
      border: none;
      color: white;
      padding: 7px 20px;
      text-align: center;
      text-decoration: none;
      display: inline-block;
      font-size: 16px;
      margin: 4px 2px;
      cursor: pointer;
      }
      .button1 {background-color: #008CBA;}
      .button2 {background-color: #5598C6;}
       
   </style>
   <body>

         <!--==================== HEADER 1====================-->    
<div class="containerab">
<div class="navbaraba">
&nbsp;&nbsp;&nbsp;&nbsp;
<?php 
include '../../../config.php';
$topic=$_GET['topic'];
$kilanlan=$_GET['kilanlan'];

$sql="select * from question where topics1='$topic' and topic='$kilanlan'";
$result=mysqli_query($con,$sql);

while ($row=mysqli_fetch_array($result))
{

?>     
<p style="font-size: 17px; width: 100%; margin-top: 15px"></p>
<?php } ?>
<!--==================== Timer ====================-->  
       

<?php require("timer.php"); ?>
&nbsp;
</div>
</div>


         <!--==================== HEADER 2====================-->
          <div class="containerab">

    <div class="sidebar">
        <div class="top-section">
        </div>

        <div class="sidebar-menu">
            <div class="top-menu">

             <?php include'feedback.php'; ?>

            <?php include'calculator.php'; ?>

             <a class="sidebar-link" style="cursor: pointer;">
                <i class="fa fa-arrows-alt" onclick="openFullscreen();" title="Enter Fullscreen"></i>
                <span><b>Full Screen</b></span>
             </a>

             <a class="sidebar-link" style="cursor: pointer;">
                <i class="fa fa-times-circle" onclick="closeFullscreen();" title="Exit Fullscreen"></i>
                <span><b>Exit Full Screen</b></span>  
             </a>

             <?php include'mynotes.php'; ?>

             <a href="../../../img/userguide.mp4" target="_blank" class="sidebar-link" style="cursor: pointer; text-decoration:none; color: white;">
                <i class="fa fa-question-circle"></i>
                <span><b>User Guide</b></span>
            </a>
            </div>
        </div>
    
    </div>
    <script type="text/javascript">

        document.querySelector(".sidebar-toggle-btn").addEventListener("click", () => {
            document.querySelector(".sidebar").classList.toggle("active");
        });

    </script>
    </div>

         <!--==================== Java Script ====================-->
      <script src="../assets/js/main.js"></script>
      <script src="../assets/js/script.js"></script>
      <script src="../assets/js/sweet.min.js"></script>

         <!--==================== Java Script Calculator====================-->
      <script type="text/javascript">
         var calculator_btn = document.querySelector(".calculator_btn");
         var wrappera = document.querySelector(".wrappera");
         var close_btns = document.querySelectorAll(".close_btn");

         calculator_btn.addEventListener("click", function () {
           wrappera.classList.add("active");
         });

         close_btns.forEach(function (btn) {
           btn.addEventListener("click", function () {
             wrappera.classList.remove("active");
           });
         });

      </script>
         <!--==================== Java Script Note====================-->

<script>
// Get the modal
var modal = document.getElementById("myModal");

// Get the button that opens the modal
var btn = document.getElementById("myBtn");

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks the button, open the modal 
btn.onclick = function() {
  modal.style.display = "block";
}

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
  modal.style.display = "none";
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
  }
}
</script>

         <!--==================== Java Script Back====================-->

<script>
    function submitForm(form) {
        swal({
            title: "Are you sure?",
            text: "Your data will be lost",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then(function (isOkay) {
            if (isOkay) {
                form.submit();
            }
        });
        return false;
    }
</script>
         <!--==================== Java Script Fullscreen====================-->

<script>
var elem = document.documentElement;
function openFullscreen() {
  if (elem.requestFullscreen) {
    elem.requestFullscreen();
  } else if (elem.webkitRequestFullscreen) { /* Safari */
    elem.webkitRequestFullscreen();
  } else if (elem.msRequestFullscreen) { /* IE11 */
    elem.msRequestFullscreen();
  }
}

function closeFullscreen() {
  if (document.exitFullscreen) {
    document.exitFullscreen();
  } else if (document.webkitExitFullscreen) { /* Safari */
    document.webkitExitFullscreen();
  } else if (document.msExitFullscreen) { /* IE11 */
    document.msExitFullscreen();
  }
}
</script>
   </body>
</html>
