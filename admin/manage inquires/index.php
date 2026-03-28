<?php 
include("count.php");
$count=new count;
$userd=$count->show_users();

?>

 <?php  
 //Database connectivity  
  include('../../config.php');
 $sql=mysqli_query($con,"select * from signup");  
 //Get Update id and status  
 if (isset($_GET['id']) && isset($_GET['status'])) {  
      $id=$_GET['id'];  
      $status=$_GET['status'];  
      mysqli_query($con,"update signup set status='$status' where id='$id'");  
      header("location:index.php");  
      die();  
 }  
 ?>  
<!DOCTYPE html>
<!-- Website - www.codingnepalweb.com -->
<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8" />
    <title>NCLEX Amplified</title>
    <link rel="shortcut icon" type="text/css" href="../img/logo.png">
    <link rel="stylesheet" href="../style.css" />
    <!-- Boxicons CDN Link -->
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../../table css/dataTables.bootstrap5.min.css" />
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  </head>
  <style type="text/css">
    /* Full-width input fields */
input[type=text], input[type=number], input[type=email]{
  width: 100%;
  padding: 12px 20px;
  margin: 8px 0;
  display: inline-block;
  border: 1px solid #ccc;
  box-sizing: border-box;
}

/* Set a style for all buttons */
button {
  background-color: #04AA6D;
  color: white;
  padding: 14px 20px;
  margin: 8px 0;
  border: none;
  cursor: pointer;
  width: 100%;
}

button:hover {
  opacity: 0.8;
}

/* Extra styles for the cancel button */
.cancelbtn {
  width: auto;
  padding: 10px 18px;
  background-color: #f44336;
}

/* Center the image and position the close button */
.imgcontainer {
  text-align: center;
  margin: 24px 0 12px 0;
  position: relative;
}

.container {
  padding: 16px;
}

  </style>
  <body>
    <div class="sidebar">
      <div class="logo-details">
        <span class="logo_name">Online Qbanks</span>
      </div>
      <ul class="nav-links">
        <li>
          <a href="#">
            <i class="bx bx-grid-alt"></i>
            <span class="links_name">Dashboard</span>
          </a>
        </li>
        <li>
          <a href="manage topics/">
            <i class="bx bx-box"></i>
            <span class="links_name">Manage Topics</span>
          </a>
        </li>
        <li>
          <a href="manage question/">
            <i class="bx bx-list-ul"></i>
            <span class="links_name">Manage Question</span>
          </a>
        </li>
        <li>
          <a href="manage bundle/">
            <i class="bx bx-pie-chart-alt-2"></i>
            <span class="links_name">Manage Bundle</span>
          </a>
        </li>
        <li>
          <a href="manage group">
            <i class="bx bx-user"></i>
            <span class="links_name">Manage Group</span>
          </a>
        </li>
        <li>
          <a href="manage result">
            <i class="bx bx-coin-stack"></i>
            <span class="links_name">Manage Result</span>
          </a>
        </li>
        <li>
          <a href="#">
            <i class="bx bx-book-alt"></i>
            <span class="links_name">N/A</span>
          </a>
        </li>
        <li>
          <a href="#" class="active">
            <i class="bx bx-message"></i>
            <span class="links_name">Inquires</span>
          </a>
        </li>
        <li>
          <a href="#">
            <i class="bx bx-heart"></i>
            <span class="links_name">Feedback</span>
          </a>
        </li>
        <li>
          <a href="#">
            <i class="bx bx-cog"></i>
            <span class="links_name">Setting</span>
          </a>
        </li>
        <li class="log_out">
          <a href="../index.php">
            <i class="bx bx-log-out"></i>
            <span class="links_name">Log out</span>
          </a>
        </li>
      </ul>
    </div>
    <section class="home-section">
      <nav>
        <div class="sidebar-button">
          <i class="bx bx-menu sidebarBtn"></i>
          <span class="dashboard">Dashboard</span>
        </div>
      </nav>

      <div class="home-content">
        <div class="overview-boxes">
          <div class="box">
            <div class="right-side">
              <div class="box-topic">Number of Inquires</div>
              <div class="number"><?php $count->user(); ?></div>
            </div>
          </div>

          <div class="box">
            <div class="right-side">
              <div class="box-topic">Total of Pending</div>
              <div class="number"><?php $count->bundles(); ?></div>
            </div>
          </div>

          <div class="box">
            <div class="right-side">
              <div class="box-topic">Total of Not Proceed</div>
              <div class="number"><?php $count->questions(); ?></div>
            </div>
          </div>

          <div class="box">
            <div class="right-side">
              <div class="box-topic">Total of Proceed</div>
              <div class="number"><?php $count->concept(); ?></div>
            </div>
          </div>
        </div>

        <div class="sales-boxes">
          <div class="recent-sales box">
            <div class="title">Sign Up Student</div>

              <table class="table table-striped data-table">
                <thead>
                      <tr>
                        <th>Email</th>
                        <th>Full Name</th>
                        <th>Facebook Name</th>
                        <th>Contact Number</th>
                        <th>Address</th>
                        <th>Review Center</th>
                        <th>Status</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      include('../../config.php');
                      
                      $query = "SELECT * FROM `signup`"; 
                      $data = mysqli_query($con, $query);

                      while ($rows = mysqli_fetch_array($data)) { 
                      ?>
                      <tr>
                          <td><?php echo $rows['email']; ?></td> 
                          <td><?php echo $rows['fullname']; ?></td> 
                          <td><?php echo $rows['facebookname']; ?></td> 
                          <td><?php echo $rows['contactnumber']; ?></td> 
                          <td><?php echo $rows['address']; ?></td> 
                          <td><?php echo $rows['rcenter']; ?></td>
                          <td>
                              <?php
                              if($rows['status']== 1){
                              echo '<p style="color:red">Pending</p>';
                              }elseif($rows['status']== 2){
                              echo '<p style="color:orange">Not Proceed</p>';
                              }
                              else{
                              echo '<p style="color:green">Proceed</p>';
                              }
                              ?>
                          </td> 
                          <td>  
                            <select class="form-control form-control-sm" style="width: 60%" onchange="status_update(this.options[this.selectedIndex].value,'<?php echo $rows['id'] ?>')">  
                                <option value="" disabled selected>Status</option>  
                                <option value="1">Pending</option>  
                                <option value="2">Not Proceed</option>
                                <option value="3">Proceed</option>
                            </select>  
                            </td>  
                      <?php } ?>
                      </tr>
                    </tbody>
              </table>
          </div>
        </div>
      </div>
    </section>

    <script>
      let sidebar = document.querySelector(".sidebar");
      let sidebarBtn = document.querySelector(".sidebarBtn");
      sidebarBtn.onclick = function () {
        sidebar.classList.toggle("active");
        if (sidebar.classList.contains("active")) {
          sidebarBtn.classList.replace("bx-menu", "bx-menu-alt-right");
        } else sidebarBtn.classList.replace("bx-menu-alt-right", "bx-menu");
      };
    </script>

     <script type="text/javascript">  
      function status_update(value,id){  
           //alert(id);  
           let url = "index.php";  
           window.location.href= url+"?id="+id+"&status="+value;  
      }  
     </script>

    <script src="../.././table js/jquery-3.5.1.js"></script>
    <script src="../.././table js/jquery.dataTables.min.js"></script>
    <script src="../.././table js/dataTables.bootstrap5.min.js"></script>
    <script src="../.././table js/script.js"></script>
  </body>
</html>
