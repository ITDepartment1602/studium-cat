<?php

include '../../config.php';
session_start();
$user_id = $_SESSION['user_id'];
?>

 <!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../ty/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css"/>
    <link rel="stylesheet" href="../ty/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="../ty/css/style.css" />
    <link rel="stylesheet" href="css/style.css">
     <link rel="stylesheet" href="../pchart/pchart.css">
     <link rel="stylesheet" href="../pricing/style.css">
     <link rel="stylesheet" href="../pricing/exam.css">
    <title>studium</title>
    <link rel="shortcut icon" type="text/css" href="../../img/logo1.svg">
  </head>
  <style>
.offcanvas{
  border-radius: 0 10px 10px 0;
  background-color: #AAD8F8;
  width: 200px;
  margin-top: 10px;
}
#table tr p{
  color: white;
}
#table{
  border-radius: 0 10px 10px 0;
  background-color: #5598C6;
}
#table tr p:hover {
   background-color: #AAD8F8;
   border-radius: 5px;
   color: black;
   
}
.nurse img{
   width:70px;
   margin-left: 5px;
}
.nurse a{
   font-size: 15px;
   margin-left: -15px;
   color: black;
   text-decoration: none;
}
.product{
  margin-left: 5px;
  margin-top: 20%;
}
.product a{
  text-decoration: none;
  color: red;
}
  </style>
  <body>
    <!-- top navigation bar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background-color:#5598C6;">
      <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="offcanvasExample">
          <span class="navbar-toggler-icon" data-bs-target="#sidebar"></span>
        </button>
        <a class="navbar-brand me-auto ms-lg-0 ms-3 text-uppercase fw-bold" style="font-size: 20px"></a>
        <a class="nav-link text-white" href="../../index.php"> <i class="fa fa-sign-out fa-2x" aria-hidden="true" style="color: #fff"></i></a>
      </div>
    </nav>
    <!-- top navigation bar -->
   
    <!-- offcanvas -->
    <div class="offcanvas offcanvas-start sidebar-nav ml-6" tabindex="-1" id="sidebar">
      <div class="offcanvas-body p-0">
        <nav class="navbar-dark" style="width: 100%;">
          <ul class="navbar-nav">

<!---<form action="" method="POST"> 
          <div class="col-md-auto">
            <input type="text" name="search" class='form-control' placeholder="Search By Name" value="" > 


        </form>--->
    <?php
         $select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'") or die('query failed');
         if(mysqli_num_rows($select) > 0){
            $fetch = mysqli_fetch_assoc($select);
         }
      ?>
            <li style="width: 100%">
              <table id="table" style="margin-top: -20px; width: 100%;">
                <tr>
                  <td class="nav-link px-1">
                  </td>
                      <img src="../../img/logo2.svg" style="width:100px; margin-left: 40px;">
                  
                </tr>
                <tr>
                  <td class="nav-link px-1">
                  </td>
                      <div class="nurse">
                      <img src="../../img/nurse.svg"><a href="" data-bs-toggle="modal" id="myBtn">Set exam date ></a>
                      </div>
                </tr>
                <!-- Modal -->
                  <div id="myModal" class="modala">
                    <div class="modal-contenta">
                      <div class="wrappera">
                        <span class="close">&times;</span>
                        <header>Pick Your Exam Date</header>
                        <div class="content">
                          <p>Providing us your exam date helps us make the product experience more personal for you.</p>
                          <form>
                            <input class="form-control" type="date" name="">
                            <br>
                            <button class="btn btn-primary">submit</button>
                          </form>
                        </div>
                      </div>
                  </div>
                </div>
                <tr>
                  <td class="nav-link px-1">
                  </td>
                  <td>
                      <a href="index.php" id="myVideo" class="nav-link">
                      <p style="font-size: 15px;"><i class="fa fa-home fa-2x" aria-hidden="true"></i> Home ></p></a>
                  </td>
                </tr>
                <tr>
                  <td class="nav-link px-1">
                  </td>
                  <td>
                      <a href="quiz.php?bundle_name=<?php echo $fetch['bundle_name']; ?>" id="myVideo" class="nav-link">
                      <p style="font-size: 15px;"><i class="fa fa-book fa-2x" aria-hidden="true"></i> Dashboard ></p></a>
                  </td>
                </tr>
                <tr>
                  <td class="nav-link px-1">
                  </td>
                  <td>
                      <a href="profile.php" id="myVideo" class="nav-link">
                      <p style="font-size: 15px;"><i class="fa fa-user fa-2x" aria-hidden="true"></i> View Profile ></p></a>
                  </td>
                </tr>
                <tr>
                  <td class="nav-link px-1">
                  </td>
                      <?php
                         $select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'") or die('query failed');
                         if(mysqli_num_rows($select) > 0){
                            $fetch = mysqli_fetch_assoc($select);
                         }
                      ?>
                  <td>
                      <a href="note.php?id=<?php echo $fetch['id'] ?>" id="myVideo" class="nav-link">
                      <p style="font-size: 15px;"><i class="fa fa-pencil fa-2x" aria-hidden="true"></i> My Notes ></p></a>
                  </td>
                </tr>
                <tr>
                  <td class="nav-link px-1">
                  </td>
                  <td>
                      <a href="" id="myVideo" class="nav-link">
                      <p style="font-size: 15px;"><i class="fa fa-question-circle fa-2x" aria-hidden="true"></i> User Guide ></p></a>
                  </td>
                </tr>
                <tr>
                  <td class="nav-link px-1">
                  </td>
                  <td>
                      <a href="https://www.facebook.com/NCLEX.Amplified.Technical" target="_blank" id="myVideo" class="nav-link">
                      <p style="font-size: 15px;"><i class="fa fa-phone-square fa-2x" aria-hidden="true"></i> Contact Us ></p></a>
                  </td>
                </tr>
              </table>
              
            </li>
            <tr>
                  <td class="nav-link px-1">
                  </td>
                      <div class="product"><a href="pricing.php">Explore more products ></a></div>
              </tr>
            <li class="my-4"><hr class="dropdown-divider bg-dark"  style="margin-top: 50%;"/></li>
    <?php
         $select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'") or die('query failed');
         if(mysqli_num_rows($select) > 0){
            $fetch = mysqli_fetch_assoc($select);
         }
      ?>
            <p style="color: black; font-size: 15px; text-align: center; margin-top: -20px;">Expiration Date</p>
            <p style="color: black; font-size: 15px; text-align: center; margin-top: -20px;"><?php echo $fetch['dateexpired'] ?></p>
          </ul>
        </nav>
      </div>
    </div>
    <!-- offcanvas -->

     <main class="mt-1 pt-1">
      <div class="container-fluid" style="margin-top: 60px">
        <div class="row">
          <div class="col-md-12 mb-3">
              <div class="card-body" >
                  <div class="container">
                      <div class="box-container">
                          <div class="box">
                          <div class="pricing-card">
                            <h3 class="pricing-card-header">Package 1</h3>
                            <div class="price"><sup>₱</sup>15<span>/ 1 MONTH</span></div>
                            <ul>
                              <li><strong>3</strong> Websites</li>
                              <li><strong>20 GB</strong> SSD</li>
                              <li><strong>1</strong> Domain Name</li>
                              <li><strong>5</strong> Email</li>
                              <li><strong>1x</strong> CPU & RAM</li>
                            </ul>
                            <a href="#" class="order-btn">Order Now</a>
                          </div>
                          </div>  

                          <div class="box">
                          <div class="pricing-card">
                            <h3 class="pricing-card-header">Package 2</h3>
                            <div class="price"><sup>₱</sup>15<span>/3 MONTHS</span></div>
                            <ul>
                              <li><strong>3</strong> Websites</li>
                              <li><strong>20 GB</strong> SSD</li>
                              <li><strong>1</strong> Domain Name</li>
                              <li><strong>5</strong> Email</li>
                              <li><strong>1x</strong> CPU & RAM</li>
                            </ul>
                            <a href="#" class="order-btn">Order Now</a>
                          </div>
                          </div>

                          <div class="box">
                          <div class="pricing-card">
                            <h3 class="pricing-card-header">Package 3</h3>
                            <div class="price"><sup>₱</sup>15<span>/6 MONTHS</span></div>
                            <ul>
                              <li><strong>3</strong> Websites</li>
                              <li><strong>20 GB</strong> SSD</li>
                              <li><strong>1</strong> Domain Name</li>
                              <li><strong>5</strong> Email</li>
                              <li><strong>1x</strong> CPU & RAM</li>
                            </ul>
                            <a href="#" class="order-btn">Order Now</a>
                          </div>
                          </div>

                          <div class="box">
                          <div class="pricing-card">
                            <h3 class="pricing-card-header">Package 4</h3>
                            <div class="price"><sup>₱</sup>15<span>/1 YEAR</span></div>
                            <ul>
                              <li><strong>3</strong> Websites</li>
                              <li><strong>20 GB</strong> SSD</li>
                              <li><strong>1</strong> Domain Name</li>
                              <li><strong>5</strong> Email</li>
                              <li><strong>1x</strong> CPU & RAM</li>
                            </ul>
                            <a href="#" class="order-btn">Order Now</a>
                          </div>
                          </div>

                      </div>
                  </div>
              </div>
          </div>
        </div>
      </div>
    </main>   



<br><br><br>
<div class="copy" style="background-color: #5598C6; height: 30px;">
<center><span style="color:white;">© NCLEX Amplified, All Right Reserved.</span></center>
</div>

    <script src="../ty/./js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.0.2/dist/chart.min.js"></script>
    <script src="../ty/./js/jquery-3.5.1.js"></script>
    <script src="../ty/./js/jquery.dataTables.min.js"></script>
    <script src="../ty/./js/dataTables.bootstrap5.min.js"></script>
    <script src="../ty/./js/script.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js" charset="utf-8"></script>
      <!--=============== MAIN JS ===============-->
      <script src="assets/js/main.js"></script>

    <!--=============== GRAPH ===============-->  
  <script>
    $(function () { 
  $('[data-toggle="tooltip"]').tooltip({trigger: 'manual'}).tooltip('show');
});  

// $( window ).scroll(function() {   
 // if($( window ).scrollTop() > 10){  // scroll down abit and get the action   
  $(".progress-bar").each(function(){
    each_bar_width = $(this).attr('aria-valuenow');
    $(this).width(each_bar_width + 'px');
  });
       
 //  }  
// });
  </script>

    <!--=============== Pchart ===============-->  
 <script src="../pchart/plugins/jquery-2.2.4.min.js"></script>
 <script src="../pchart/plugins/jquery.appear.min.js"></script>
 <script src="../pchart/plugins/jquery.easypiechart.min.js"></script> 
 <script>
    'use strict';

var $window = $(window);

function run()
{
  var fName = arguments[0],
    aArgs = Array.prototype.slice.call(arguments, 1);
  try {
    fName.apply(window, aArgs);
  } catch(err) {
     
  }
};
 
/* ===================== chart ============================= */
function _chart ()
{
  $('.b-skills').appear(function() {
    setTimeout(function() {
      $('.chart').easyPieChart({
        easing: 'easeOutElastic',
        delay: 3000,
        barColor: '#369670',
        trackColor: '#E5E6E6',
        scaleColor: false,
        lineWidth: 11,
        trackWidth: 11,
        size: 250,
        lineCap: 'round',
        onStep: function(from, to, percent) {
          this.el.children[0].innerHTML = Math.round(percent);
        }
      });
    }, 150);
  });
};
 

$(document).ready(function() {
  run(_chart);
});
</script>
  </body>
</html>
