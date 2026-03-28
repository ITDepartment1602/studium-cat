<!DOCTYPE html>
   <html lang="en">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">

      <!--=============== REMIXICONS ===============-->
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.min.css">
      <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet" />

      <!--=============== CSS ===============-->
      <link rel="stylesheet" href="../assets/css/styles.css">
      <link rel="stylesheet" href="../css/start.css">
      <link rel="stylesheet" href="../css/feedback.css">

    <title>NCLEX Amplified</title>
    <link rel="shortcut icon" type="text/css" href="../img/logo.png">
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
      .button2 {background-color: #0A2558;}
       
   </style>
   <body>

    <?php include "../header.php";?>

         <!--==================== HEADER ====================-->
          <div class="containerab">
         <!--==================== Content ====================-->
         <div class="subContainerab">
             <div class="main_containerab">
                <div class="contentab">
         <!--==================== Content Left ====================-->
                    <div class="content1ab">
                     <?php 
                     include '../../../config.php';
                     $topic=$_GET['topic'];
                     $qnumber=$_GET['qnumber'];
                     $kilanlan=$_GET['kilanlan'];

                         $sql="select * from question where topics1='$topic' and qnumber='$qnumber' and topic='$kilanlan'";
                         $result=mysqli_query($con,$sql);

                         while ($row=mysqli_fetch_array($result))
                          {
                             
                     ?> 
                     <form action="../rationale/ans20.php?q=quiz&step=2&eid=<?php echo $row['topics1']; ?>&n=20&id=<?php echo $_GET['id'] ?>&qid=<?php echo $row['correctans']; ?>&kilanlan=<?php echo $row['topic'] ?>" method='POST' enctype="multipart/form-data">
                      <br>
                     <b>Question <?php echo $row['qnumber'] ?></b><br><br>
                     
                     <?php echo $row['question'] ?><br><br>
                     
                     <input type="radio" name="ans" value="1"> <?php echo $row['choiceA'] ?><br><br>
                     <input type="radio" name="ans" value="2"> <?php echo $row['choiceB'] ?><br><br>
                     <input type="radio" name="ans" value="3"> <?php echo $row['choiceC'] ?><br><br>
                     <input type="radio" name="ans" value="4"> <?php echo $row['choiceD'] ?><br><br><br><br>
                     <button class="question button1" type="submit" name="question">Submit</button>
                     </form>
                   
                     </div>
         <!--==================== Content Right ====================-->
                    <div class="content2ab">

                     </div>
         <!--==================== Content Footer ====================-->
                    <div class="footerab">
                     
<form method="POST" action="../index.php" onsubmit="return submitForm(this);">
    <input class="question button2" type="submit" value="Back" />
</form>
  <?php } ?>

                  </div>
                </div>
            </div>
        </div>
    </div>




         <!--==================== Java Script ====================-->
      <script src="../assets/js/main.js"></script>
      <script src="../assets/js/script.js"></script>
      <script src="../assets/js/sweet.min.js"></script>
         <!--==================== Java Script Calculator====================-->
      <script type="text/javascript">
         var calculator_btn = document.querySelector(".calculator_btn");
         var wrapper = document.querySelector(".wrapper");
         var close_btns = document.querySelectorAll(".close_btn");

         calculator_btn.addEventListener("click", function () {
           wrapper.classList.add("active");
         });

         close_btns.forEach(function (btn) {
           btn.addEventListener("click", function () {
             wrapper.classList.remove("active");
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
   </body>
</html>
