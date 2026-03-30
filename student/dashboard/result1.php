<?php

include '../../config.php';
session_start();
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
   <html lang="en">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">

      <!--=============== REMIXICONS ===============-->
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.min.css">
      <!--=============== CSS ===============-->
      <link rel="stylesheet" href="assets/css/styles.css">
      <link rel="stylesheet" href="css/style2.css">

    <title>NCLEX Amplified</title>
    <link rel="shortcut icon" type="text/css" href="../../img/logo.png">
   </head>
<style type="text/css">
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700&display=swap');

*{
    font-family: 'Poppins', sans-serif;
    margin:0; padding:0;
    box-sizing: border-box;
    text-decoration: none;
    text-transform: capitalize;
    transition: .2s linear;
}

.welcome p{
   background-color: #CAE3FF;
   padding: 5px;
   font-size: 20px;
   color: black;
}
.active{
   color: red;
   text-decoration: underline;
}
input[type=text], input[type=number], input[type=email]{
  width: 100%;
  padding: 12px 20px;
  margin: 8px 0;
  display: inline-block;
  border: 1px solid #ccc;
  box-sizing: border-box;
}
#customers {
  font-family: Arial, Helvetica, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

#customers td, #customers th {
  border: 1px solid #ddd;
  padding: 8px;
}

#customers tr:nth-child(even){background-color: #f2f2f2;}

#customers tr:hover {background-color: #ddd;}

#customers th {
  padding-top: 12px;
  padding-bottom: 12px;
  text-align: left;
  background-color: #3D52A0;
  color: white;
}

* {
  box-sizing: border-box;
}
/* Create three equal columns that floats next to each other */
.column {
  position: absolute;
  float: left;
  margin-top: -100px;
  width: 33%;
  padding: 30px;
  height: 100px; /* Should be removed. Only for demonstration */
  text-align: center;
  color: #0A2558;
  width: 30%;
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
  width: 40px;
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
  margin-top: -50px;
  box-shadow: 0 0 10px white;
}
@media (max-width:768px){
    .progress-bar{
        display: none;
    }
    .column{
        display: none;
    }
}

</style>
   <body>
      <!--==================== HEADER ====================-->
      <header class="header" id="header">
         <nav class="nav container">
            <img src="img/logo.png" style="width:50px">

            <div class="nav__menu" id="nav-menu">
               <ul class="nav__list">
                  <li class="nav__item">
                     <a href="" class="nav__link">
                        <i class="ri-arrow-right-up-line"></i>
                        <span>Dashboard</span>
                     </a>
                  </li>

                  <li class="nav__item">
                     <a href="#" class="nav__link">
                        <i class="ri-arrow-right-up-line"></i>
                        <span>Contact Us</span>
                     </a>
                  </li>

                  <li class="nav__item">
                     <a href="#" class="nav__link">
                        <i class="ri-arrow-right-up-line"></i>
                        <span>User Guide</span>
                     </a>
                  </li>

                  <li class="nav__item">
                     <a href="../../index.php" class="nav__link">
                        <i class="ri-arrow-right-up-line"></i>
                        <span>Logout</span>
                     </a>
                  </li>
               </ul>

               <!-- Close button -->
               <div class="nav__close" id="nav-close">
                  <i class="ri-close-large-line"></i>
               </div>
            </div>

            <!-- Toggle button -->
            <div class="nav__toggle" id="nav-toggle">
               <i class="ri-menu-line"></i>
            </div>
         </nav>
      </header>
<br><br><br><br><br>
    <?php
         $select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'") or die('query failed');
         if(mysqli_num_rows($select) > 0){
            $fetch = mysqli_fetch_assoc($select);
         }
      ?>

<div class="welcome">
<center><p><a href="index.php"> Qbanks </a>&nbsp; <a href="profile.php"> Profile </a>&nbsp; <a href="" class="active">Result </a><p></center>
</div>
<br>

      <!--==================== MAIN ====================-->
<div class="container1">
    <div class="box-container">
        <div class="box">

<h2>Chance of passing</h2>

      <?php
         $con = getQuizConnection();
          $select = mysqli_query($con, "SELECT sum(sahi) FROM `history` WHERE email = '$user_id'") or die('query failed');
          while ($rows = mysqli_fetch_array($select)) {
      ?>
    <div class="row1">
        <div class="progress-bar" role="progressbar" aria-valuenow="1000" aria-valuemin="0" aria-valuemax="1870">   
        <span class="popOver" data-toggle="tooltip" data-placement="top" title="<?php echo $rows['sum(sahi)']; ?>"></span>  
        </div>
    </div>
  <?php } ?>
    <div class="row2">
      <div class="column" style="background-color:#FFA9AB; margin-left: 5%;">
        <h2>Low</h2>
      </div>
      <div class="column" style="background-color:#A1B6FF; margin-left: 33%;">
        <h2>Mid</h2>
      </div>
      <div class="column" style="background-color:#BCFFDF; margin-left: 63%;">
        <h2>High</h2>
      </div>
    </div>

        </div>
    </div>
</div>

<br><br><br><br><br><br><br>

<div class="container1">
    <h2 style="color:#DA3134; margin-left: 20px;">NARC Intermediate QBanks</h2>
    <div class="box-container">
        <div class="box">
            <table id="customers">
              <tr>
                <th>Concept</th>
                <th>Score</th>
                <th>Correct</th>
                <th>Wrong</th>
                <th>Date</th>
              </tr>
            <?php
            include('../../config.php');

            $query = "SELECT * FROM `history` where email = '$user_id' AND kilanlan = 'NARC Intermediate QBanks' "; 
            $data = mysqli_query($con, $query);

            while ($rows = mysqli_fetch_array($data)) { 
            ?>
              <tr>
                <td><?php echo $rows['eid']; ?></td>
                <td><?php echo $rows['score']; ?></td>
                <td><?php echo $rows['sahi']; ?></td>
                <td><?php echo $rows['wrong']; ?></td>
                <td><?php echo $rows['date']; ?></td>
              </tr>
            <?php } ?>
            </table>
        </div>

        <div class="chart">
              <ul class="numbers">
                <li><span>100%</span></li>
                <li><span>50%</span></li>
                <li><span>0%</span></li>
              </ul>
              <ul class="bars">
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Adult Health' AND kilanlan = 'NARC Intermediate QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>
                <li><div class="bar" data-percentage="<?php echo $rows['sahi'] ?>"></div><p>Adult Health</p></li>
                <?php } ?>
                
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Child Health' AND kilanlan = 'NARC Intermediate QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>
                <li><div class="bar" data-percentage="<?php echo $rows['sahi'] ?>"></div><p>Child Health</p></li>
                <?php } ?>
                
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Fundamentals' AND kilanlan = 'NARC Intermediate QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>
                <li><div class="bar" data-percentage="<?php echo $rows['sahi'] ?>"></div><p>Fundamentals</p></li>
                <?php } ?>
                
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Leadership and Management' AND kilanlan = 'NARC Intermediate QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>
                <li><div class="bar" data-percentage="<?php echo $rows['sahi'] ?>"></div><p>Leadership and Management</p></li>
                <?php } ?>
                
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Maternal and Newborn Health' AND kilanlan = 'NARC Intermediate QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>
                <li><div class="bar" data-percentage="<?php echo $rows['sahi'] ?>"></div><p>Maternal and Newborn Health</p></li>
                <?php } ?>
                
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Mental Health' AND kilanlan = 'NARC Intermediate QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>
                <li><div class="bar" data-percentage="<?php echo $rows['sahi'] ?>"></div><p>Mental Health</p></li>
                <?php } ?>
                
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Pharmacology' AND kilanlan = 'NARC Intermediate QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>
                <li><div class="bar" data-percentage="<?php echo $rows['sahi'] ?>"></div><p>Pharmacology</p></li>
                <?php } ?>
              </ul>
               
        </div>
    </div>
</div>

<br><br><br><hr><br>

<div class="container1">
    <h2 style="color:#DA3134; margin-left: 20px;">NARC Basic QBanks</h2>
    <div class="box-container">
        <div class="box">
              <table id="customers">
              <tr>
                <th>Concept</th>
                <th>Score</th>
                <th>Correct</th>
                <th>Wrong</th>
                <th>Date</th>
              </tr>
            <?php
            include('../../config.php');

            $query = "SELECT * FROM `history` where email = '$user_id' AND kilanlan = 'NARC Advance QBanks' "; 
            $data = mysqli_query($con, $query);

            while ($rows = mysqli_fetch_array($data)) { 
            ?>
              <tr>
                <td><?php echo $rows['eid']; ?></td>
                <td><?php echo $rows['score']; ?></td>
                <td><?php echo $rows['sahi']; ?></td>
                <td><?php echo $rows['wrong']; ?></td>
                <td><?php echo $rows['date']; ?></td>
              </tr>
            <?php } ?>
            </table>
        </div>

        <div class="chart">
              <ul class="numbers">
                <li><span>100%</span></li>
                <li><span>50%</span></li>
                <li><span>0%</span></li>
              </ul>
              <ul class="bars">
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Adult Health' AND kilanlan = 'NARC Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>
                <li><div class="bar" data-percentage="<?php echo $rows['sahi'] ?>"></div><p>Adult Health</p></li>
                <?php } ?>
                
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Child Health' AND kilanlan = 'NARC Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>
                <li><div class="bar" data-percentage="<?php echo $rows['sahi'] ?>"></div><p>Child Health</p></li>
                <?php } ?>
                
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Fundamentals' AND kilanlan = 'NARC Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>
                <li><div class="bar" data-percentage="<?php echo $rows['sahi'] ?>"></div><p>Fundamentals</p></li>
                <?php } ?>
                
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Leadership and Management' AND kilanlan = 'NARC Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>
                <li><div class="bar" data-percentage="<?php echo $rows['sahi'] ?>"></div><p>Leadership and Management</p></li>
                <?php } ?>
                
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Maternal and Newborn Health' AND kilanlan = 'NARC Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>
                <li><div class="bar" data-percentage="<?php echo $rows['sahi'] ?>"></div><p>Maternal and Newborn Health</p></li>
                <?php } ?>
                
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Mental Health' AND kilanlan = 'NARC Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>
                <li><div class="bar" data-percentage="<?php echo $rows['sahi'] ?>"></div><p>Mental Health</p></li>
                <?php } ?>
                
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Pharmacology' AND kilanlan = 'NARC Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>
                <li><div class="bar" data-percentage="<?php echo $rows['sahi'] ?>"></div><p>Pharmacology</p></li>
                <?php } ?>
              </ul>
        </div>
    </div>
</div>

<br><br><br><hr><br>

<div class="container1">
    <h2 style="color:#DA3134; margin-left: 20px;">NARC Advance QBanks</h2>
    <div class="box-container">
        <div class="box">
            <table id="customers">
              <tr>
                <th>Concept</th>
                <th>Score</th>
                <th>Correct</th>
                <th>Wrong</th>
                <th>Date</th>
              </tr>
            <?php
            include('../../config.php');

            $query = "SELECT * FROM `history` where email = '$user_id' AND kilanlan = 'NARC Intermediate and Advance QBanks' "; 
            $data = mysqli_query($con, $query);

            while ($rows = mysqli_fetch_array($data)) { 
            ?>
              <tr>
                <td><?php echo $rows['eid']; ?></td>
                <td><?php echo $rows['score']; ?></td>
                <td><?php echo $rows['sahi']; ?></td>
                <td><?php echo $rows['wrong']; ?></td>
                <td><?php echo $rows['date']; ?></td>
              </tr>
            <?php } ?>
            </table>    
        </div>

        <div class="chart">
              <ul class="numbers">
                <li><span>100%</span></li>
                <li><span>50%</span></li>
                <li><span>0%</span></li>
              </ul>
              <ul class="bars">
                  
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Adult Health' AND kilanlan = 'NARC Intermediate and Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>  
                <li><div class="bar" data-percentage="<?php echo $rows['sahi'] ?>"></div><p>Adult Health</p></li>
                <?php } ?>
                
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Child Health' AND kilanlan = 'NARC Intermediate and Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?>  
                <li><div class="bar" data-percentage="<?php echo $rows['sahi'] ?>"></div><p>Child Health</p></li>
                <?php } ?>
                
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Critical Care' AND kilanlan = 'NARC Intermediate and Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?> 
                <li><div class="bar" data-percentage="<?php echo $rows['sahi'] ?>"></div><p>Critical Care</p></li>
                <?php } ?>
                
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Fundamentals' AND kilanlan = 'NARC Intermediate and Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?> 
                <li><div class="bar" data-percentage="<?php echo $rows['sahi'] ?>"></div><p>Fundamentals</p></li>
                <?php } ?>
                
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Leadership and Management' AND kilanlan = 'NARC Intermediate and Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?> 
                <li><div class="bar" data-percentage="<?php echo $rows['sahi'] ?>"></div><p>Leadership and Management</p></li>
                <?php } ?>
                
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Maternal and Newborn Health' AND kilanlan = 'NARC Intermediate and Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?> 
                <li><div class="bar" data-percentage="<?php echo $rows['sahi'] ?>"></div><p>Maternal and Newborn Health</p></li>
                <?php } ?>
                
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Mental Health' AND kilanlan = 'NARC Intermediate and Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?> 
                <li><div class="bar" data-percentage="<?php echo $rows['sahi'] ?>"></div><p>Mental Health</p></li>
                <?php } ?>
                
                <?php
                include('../../config.php');
    
                $query = "SELECT * FROM `history` where email = '$user_id' AND eid = 'Pharmacology' AND kilanlan = 'NARC Intermediate and Advance QBanks' "; 
                $data = mysqli_query($con, $query);
    
                while ($rows = mysqli_fetch_array($data)) { 
                ?> 
                <li><div class="bar" data-percentage="<?php echo $rows['sahi'] ?>"></div><p>Pharmacology</p></li>
                <?php } ?>
              </ul>
        </div>
    </div>
</div>

<?php include "footer.php";?>


<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js" charset="utf-8"></script>
      <!--=============== MAIN JS ===============-->
      <script src="assets/js/main.js"></script>

    <!--=============== GRAPH ===============-->  
    <script type="text/javascript">
    $(function(){
      $('.bars li .bar').each(function(key, bar){
        var percentage = $(this).data('percentage');
        $(this).animate({
          'height' : percentage + '%'
        },1000);
      });
    });
    </script>



  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js" charset="utf-8"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js" charset="utf-8"></script>

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
   </body>
</html>