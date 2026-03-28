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
     <link rel="stylesheet" href="../pricing/moda.css">
      <link rel="stylesheet" href="../pricing/exam.css">
    <title>studium</title>
    <link rel="shortcut icon" type="text/css" href="../../img/logo1.svg">
  </head>
  
  <style>
/* Create three equal columns that floats next to each other */
.column, .column1, .column2 {
  position: absolute;
  float: left;
  margin-top: -100px; /* Should be removed. Only for demonstration */
  text-align: center;
  color: #FFF;
  width: 23%;
}
.column{
  margin-left: 9%;
  border-radius: 50px;
}
.column1{
  margin-left: 31%;
}
.column2{
  margin-left: 53%;
  border-radius: 0 50px 50px 0;
}
/* Clear floats after the columns */
.row2:after {
  content: "";
  display: table;
  clear: both;
}

/* Clear floats after the columns */

.tooltip{ 
  position:relative;
  float:right;
  margin-top: -10px;
}

.tooltip > .tooltip-inner {
  background-color: #0A2558; 
  color:white; 
  border-radius: 5px;
  width: 55px;
}

.popOver + .tooltip > .tooltip-arrow {  
  border-top:10px solid #0A2558;
  border-left: 10px solid transparent;
  border-right: 10px solid transparent;
  margin-bottom: -80%;
  margin-left: 9px;
  width: 10px;
  border-radius: 0 0 10px 10px; 
}
.progress-bar{
  display: block;
  background: white;
  padding: 100px;
  margin-left: 70px;
  margin-top: -50px;
  box-shadow: 0 0 10px white;
}
@media (max-width:768px){
    .container1{
      display: none;
    }
    .row1{
      display: none;
    }
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
                      <a href="subscription.php" id="myVideo" class="nav-link">
                      <p style="font-size: 15px;"><i class="fa fa-question-circle fa-2x" aria-hidden="true"></i> Subscription ></p></a>
                  </td>
                </tr>
                
                <tr>
                  <td class="nav-link px-1">
                  </td>
                  <td>
                      <a href="package.php" id="myVideo" class="nav-link">
                      <p style="font-size: 15px;"><i class="fa fa-question-circle fa-2x" aria-hidden="true"></i> Package ></p></a>
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

<main class="mt-5 pt-5">
<div class="container1" style="margin-top: -10px">
    <div class="box-container">
        <div class="box">

<center><h2>Chance of passing</h2></center>

      <?php
          $select = mysqli_query($con, "SELECT sum(sahi) FROM `history` WHERE email = '$user_id'") or die('query failed');
          while ($rows = mysqli_fetch_array($select)) {
      ?>
    <div class="row1" style="margin-top: -70px;">
        <div class="progress-bar" role="progressbar" aria-valuenow="<?php echo $rows['sum(sahi)']; ?>" aria-valuemin="0" aria-valuemax="1870">   
        <span class="popOver" data-toggle="tooltip" data-placement="top" title="<?php echo $rows['sum(sahi)']; ?>%"></span>  
        </div>
    </div>
  <?php } ?>
    <div class="row2">
      <div class="column" style="background-color:#DA3134;">
        <h5>Low</h5>
      </div>
      <div class="column1" style="background-color:#004AAD;">
        <h5>Mid</h5>
      </div>
      <div class="column2" style="background-color:#02968A;">
        <h5>High</h5>
      </div>
    </div>

        </div>
    </div>
</div>
 </main> 

     <main class="mt-1 pt-1">
      <div class="container-fluid" style="margin-top: -60px">
        <div class="row">
          <div class="col-md-12 mb-3">
              <div class="card-body" >
                  <div class="container">
                      <div class="box-container">
                           <?php 
                               $bundle_name=$_GET['bundle_name'];

                                 $q="select * from topics LEFT JOIN bundlelist on topics.title=bundlelist.bundlelist_name where bundle_name='$bundle_name'";
                                 //echo $course_name;
                                 $query=mysqli_query($con,$q);
                                 while ($row=mysqli_fetch_array($query))
                                {

                                 ?> 
                          <div class="box">
                              <img src="../../admin/manage topics/<?php echo $row['image']; ?>" style="width: 240px; height: 300px;">
                              <h3><?php echo $row['name'] ?></h3>

                              <p><?php echo $row['description'] ?></p>
                              <a href="topic.php?kilanlan=NARC%20Intermediate%20and%20Advance%20QBanks" class="btn" style="background: #3D52A0;">Open</a>
                              <a href="index.php" class="btn" style="background: #DC2222;">Back</a>
                          </div>
                        <?php } ?>       
                      </div>
                  </div>
              </div>
          </div>
        </div>
      </div>
    </main>   

                            


    <main class="mt-1 pt-5">
<div class="container" style="margin-top: -50px">
    <div class="box-container">
        <div class="box">
          <h4 style="color:#004AAD">NARC Basic QBanks</h4>
          <div class="b-skills">
            <div class="col-xs-12">
              <br>
              <div class="details">
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Adult Health' AND kilanlan = 'NARC Intermediate QBanks'"; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Adult Health: <b style="color:#004AAD"><?php echo $rows['sahi'] ?>%</b></p>

                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Child Health' AND kilanlan = 'NARC Intermediate QBanks'"; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Child Health: <b style="color:#004AAD"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Fundamentals' AND kilanlan = 'NARC Intermediate QBanks'"; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Fundamentals: <b style="color:#004AAD"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Leadership and Management' AND kilanlan = 'NARC Intermediate QBanks'"; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Leadership & Management: <b style="color:#004AAD"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Maternal and Newborn Health' AND kilanlan = 'NARC Intermediate QBanks'"; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Maternal & Newborn Health: <b style="color:#004AAD"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Mental Health' AND kilanlan = 'NARC Intermediate QBanks'"; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Mental Health: <b style="color:#004AAD"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Pharmacology' AND kilanlan = 'NARC Intermediate QBanks'"; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Pharmacology: <b style="color:#004AAD"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>
              </div>

              <div class="skill-item center-block">
                <div class="chart-container" style="color:#004AAD">
                  <?php
                    $select = mysqli_query($con, "SELECT sum(sahi) FROM `history` WHERE email = '$user_id' AND kilanlan = 'NARC Intermediate QBanks'") or die('query failed');
                    while ($rows = mysqli_fetch_array($select)) {
                  ?>
                  <div class="chart" data-percent="<?php echo $rows['sum(sahi)']; ?>" data-bar-color="#004AAD" data-color="black">
                    <span class="percent" data-after="%"></span>
                  </div>
                  <?php } ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      
        <div class="box">
          <h4 style="color:#DA3134">NARC Intermediate QBanks</h4>
          <div class="b-skills">
            <div class="col-xs-12">
              <br>
              <div class="details">
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Adult Health' AND kilanlan = 'NARC Advance QBanks'"; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Adult Health: <b style="color:#DA3134"><?php echo $rows['sahi'] ?>%</b></p>

                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Child Health' AND kilanlan = 'NARC Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Child Health: <b style="color:#DA3134"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Fundamentals' AND kilanlan = 'NARC Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Fundamentals: <b style="color:#DA3134"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Leadership and Management' AND kilanlan = 'NARC Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Leadership & Management: <b style="color:#DA3134"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Maternal and Newborn Health' AND kilanlan = 'NARC Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Maternal & Newborn Health: <b style="color:#DA3134"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Mental Health' AND kilanlan = 'NARC Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Mental Health: <b style="color:#DA3134"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Pharmacology' AND kilanlan = 'NARC Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Pharmacology: <b style="color:#DA3134"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>
              </div>

              <div class="skill-item center-block">
                <div class="chart-container" style="color: #DA3134">
                  <?php
                    $select = mysqli_query($con, "SELECT sum(sahi) FROM `history` WHERE email = '$user_id' AND kilanlan = 'NARC Advance QBanks'") or die('query failed');
                    while ($rows = mysqli_fetch_array($select)) {
                  ?>
                  <div class="chart" data-percent="<?php echo $rows['sum(sahi)']; ?>" data-bar-color="#DA3134">
                    <span class="percent" data-after="%"></span>
                  </div>
                  <?php } ?>
                </div>
              </div>
            </div>
          </div>
        </div>
    </div>
</div>
 </main> 

     <main class="mt-1 pt-5">
<div class="container" style="margin-top: -40px">
    <div class="box-container">
        <div class="box">
          <h4 style="color:#02968A">NARC Advance QBanks</h4>
          <div class="b-skills">
            <div class="col-xs-12">
              <br>
              <div class="details">
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Adult Health' AND kilanlan = 'NARC Intermediate and Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Adult Health: <b style="color:#02968A"><?php echo $rows['sahi'] ?>%</b></p>

                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Child Health' AND kilanlan = 'NARC Intermediate and Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Child Health: <b style="color:#02968A"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Critical Care' AND kilanlan = 'NARC Intermediate and Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Critical Care: <b style="color:#02968A"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Fundamentals' AND kilanlan = 'NARC Intermediate and Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Fundamentals: <b style="color:#02968A"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Leadership and Management' AND kilanlan = 'NARC Intermediate and Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Leadership & Management: <b style="color:#02968A"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Maternal and Newborn Health' AND kilanlan = 'NARC Intermediate and Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Maternal & Newborn Health: <b style="color:#02968A"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Mental Health' AND kilanlan = 'NARC Intermediate and Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Mental Health: <b style="color:#02968A"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Pharmacology' AND kilanlan = 'NARC Intermediate and Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Pharmacology: <b style="color:#02968A"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>
              </div>

              <div class="skill-item center-block">
                <div class="chart-container" style="color:#02968A">
                  <?php
                    $select = mysqli_query($con, "SELECT sum(sahi) FROM `history` WHERE email = '$user_id' AND kilanlan = 'NARC Intermediate and Advance QBanks'") or die('query failed');
                    while ($rows = mysqli_fetch_array($select)) {
                  ?>
                  <div class="chart" data-percent="<?php echo $rows['sum(sahi)']; ?>" data-bar-color="#02968A">
                    <span class="percent" data-after="%"></span>
                  </div>
                  <?php } ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      
        <div class="box">
          <h4 style="color:#FF9A00">NARC Extreme Qbanks</h4>
          <div class="b-skills">
            <div class="col-xs-12">
              <br>
              <div class="details">
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Adult Health' AND kilanlan = 'NARC Extreme QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Adult Health: <b style="color:#FF9A00"><?php echo $rows['sahi'] ?>%</b></p>

                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Child Health' AND kilanlan = 'NARC Extreme QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Child Health: <b style="color:#FF9A00"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Critical Care' AND kilanlan = 'NARC Extreme QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Critical Care: <b style="color:#FF9A00"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Fundamentals' AND kilanlan = 'NARC Extreme QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Fundamentals: <b style="color:#FF9A00"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Leadership and Management' AND kilanlan = 'NARC Extreme QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Leadership & Management: <b style="color:#FF9A00"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Maternal and Newborn Health' AND kilanlan = 'NARC Extreme QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Maternal & Newborn Health: <b style="color:#FF9A00"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Mental Health' AND kilanlan = 'NARC Extreme QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Mental Health: <b style="color:#FF9A00"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>

                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Pharmacology' AND kilanlan = 'NARC Extreme QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>

                <p>Pharmacology: <b style="color:#FF9A00"><?php echo $rows['sahi'] ?>%</b></p>
                
                <?php } ?>
              </div>

              <div class="skill-item center-block">
                <div class="chart-container" style="color:#FF9A00">
                  <?php
                    $select = mysqli_query($con, "SELECT sum(sahi) FROM `history` WHERE email = '$user_id' AND kilanlan = 'NARC Extreme QBanks'") or die('query failed');
                    while ($rows = mysqli_fetch_array($select)) {
                  ?>
                  <div class="chart" data-percent="<?php echo $rows['sum(sahi)']; ?>" data-bar-color="#FF9A00">
                    <span class="percent" data-after="%"></span>
                  </div>
                  <?php } ?>
                </div>
              </div>
            </div>
          </div>
        </div>
    </div>
</div>
 </main> 

<div class="scroll" id="btm">
<button><i class="fa fa-chevron-circle-down fa-3x" aria-hidden="true"></i></button>
</div>

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

    <!--=============== Scroll ===============-->  
  <script>
    document.querySelector("#btm").addEventListener("click", () => {
      window.scrollTo(0,document.body.scrollHeight);
    })
  </script>

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

  <script>
    /* ===================== drag ============================= */
    const wrapper = document.querySelector(".wrappera"),
    header = wrapper.querySelector("header");
    function onDrag({movementX, movementY}){
      let getStyle = window.getComputedStyle(wrapper);
      let leftVal = parseInt(getStyle.left);
      let topVal = parseInt(getStyle.top);
      wrapper.style.left = `${leftVal + movementX}px`;
      wrapper.style.top = `${topVal + movementY}px`;
    }
    header.addEventListener("mousedown", ()=>{
      header.classList.add("active");
      header.addEventListener("mousemove", onDrag);
    });
    document.addEventListener("mouseup", ()=>{
      header.classList.remove("active");
      header.removeEventListener("mousemove", onDrag);
    });
  </script>

  <script>
    const wrappera = document.querySelector(".wrapper"),
    headera = wrappera.querySelector(".modal-header");
    function onDraga({movementX, movementY}){
      let getStyle = window.getComputedStyle(wrappera);
      let leftVal = parseInt(getStyle.left);
      let topVal = parseInt(getStyle.top);
      wrappera.style.left = `${leftVal + movementX}px`;
      wrappera.style.top = `${topVal + movementY}px`;
    }
    headera.addEventListener("mousedown", ()=>{
      headera.classList.add("active");
      headera.addEventListener("mousemove", onDraga);
    });
    document.addEventListener("mouseup", ()=>{
      headera.classList.remove("active");
      headera.removeEventListener("mousemove", onDraga);
    });
  </script>

<script>
      /* ===================== modal ============================= */
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
</script>
  </body>
</html>
