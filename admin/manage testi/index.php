<!DOCTYPE html>
<!-- Website - www.codingnepalweb.com -->
<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8" />
    <title>NCLEX Amplified</title>
    <link rel="shortcut icon" type="text/css" href="../../img/logo.png">
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


/* The Modal (background) */
.modal {
  display: none; /* Hidden by default */
  position: fixed; /* Stay in place */
  z-index: 1; /* Sit on top */
  left: 0;
  top: 0;
  width: 100%; /* Full width */
  height: 100%; /* Full height */
  overflow: auto; /* Enable scroll if needed */
  background-color: rgb(0,0,0); /* Fallback color */
  background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
  padding-top: 60px;
}

/* Modal Content/Box */
.modal-content {
  background-color: #fefefe;
  margin: 5% auto 15% auto; /* 5% from the top, 15% from the bottom and centered */
  border: 1px solid #888;
  width: 30%; /* Could be more or less, depending on screen size */
}

/* The Close Button (x) */
.close {
  position: absolute;
  right: 25px;
  top: 0;
  color: #000;
  font-size: 35px;
  font-weight: bold;
}

.close:hover,
.close:focus {
  color: red;
  cursor: pointer;
}

/* Add Zoom Animation */
.animate {
  -webkit-animation: animatezoom 0.6s;
  animation: animatezoom 0.6s
}

@-webkit-keyframes animatezoom {
  from {-webkit-transform: scale(0)} 
  to {-webkit-transform: scale(1)}
}
  
@keyframes animatezoom {
  from {transform: scale(0)} 
  to {transform: scale(1)}
}

/* Change styles for span and cancel button on extra small screens */
@media screen and (max-width: 300px) {
  .cancelbtn {
     width: 100%;
  }
}
  </style>
  <body>
    <div class="sidebar">
      <div class="logo-details">
        <span class="logo_name">Online Qbanks</span>
      </div>
      <ul class="nav-links">
        <li>
          <a href="../">
            <i class="bx bx-grid-alt"></i>
            <span class="links_name">Dashboard</span>
          </a>
        </li>
        <li>
          <a href="../manage topics/">
            <i class="bx bx-box"></i>
            <span class="links_name">Manage Topics</span>
          </a>
        </li>
        <li>
          <a href="../manage question/">
            <i class="bx bx-list-ul"></i>
            <span class="links_name">Manage Question</span>
          </a>
        </li>
        <li>
          <a href="../manage bundle/">
            <i class="bx bx-pie-chart-alt-2"></i>
            <span class="links_name">Manage Bundle</span>
          </a>
        </li>
        <li>
          <a href="../manage group">
            <i class="bx bx-user"></i>
            <span class="links_name">Manage Group</span>
          </a>
        </li>
        <li>
          <a href="../manage result">
            <i class="bx bx-coin-stack"></i>
            <span class="links_name">Manage Result</span>
          </a>
        </li>
        <li>
          <a href="#" class="active">
            <i class="bx bx-book-alt"></i>
            <span class="links_name">Testimonial</span>
          </a>
        </li>
        <li>
          <a href="../manage inquires">
            <i class="bx bx-message"></i>
            <span class="links_name">Inquires</span>
          </a>
        </li>
        <li>
          <a href="../">
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
          <a href="../../index.php">
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

        </div>

        <div class="sales-boxes">
          <div class="recent-sales box">
            <div class="title">Testimonial</div>
            <div class="title" style="float:right; margin-top: -50px;" onclick="document.getElementById('id01').style.display='block'"><i class='bx bx-user-plus' style="font-size: 2.5rem;"></i></div>

            <div id="id01" class="modal">
            <form class="modal-content animate" action="action.php" method="post" enctype="multipart/form-data">
              <div class="imgcontainer">
                <span onclick="document.getElementById('id01').style.display='none'" class="close" title="Close Modal">&times;</span>
              </div>

              <div class="container">
                <label><b>Message:</b></label>
                <textarea  class="form-control" placeholder="Testimonial Message" name="message"></textarea>

                <label><b>Name:</b></label>
                <input type="text" class="form-control" placeholder="Input Name(e.g RN, USRN)" name="name">

                <label><b>Credential:</b></label>
                <input type="text" class="form-control" placeholder="Input Credential" name="credinial">

                <button type="submit" name="submit">Submit</button>
              </div>

              <div class="container" style="background-color:#f1f1f1">
                <button type="button" onclick="document.getElementById('id01').style.display='none'" class="cancelbtn">Cancel</button>
              </div>
            </form>
          </div>

              <table class="table table-striped data-table">
                <thead>
                      <tr>
                        <th>id</th>
                        <th>Message</th>
                        <th>Name</th>
                        <th>Credential</th>                        
                        <th>Delete</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      include('../../config.php');
                      
                      $query = "SELECT * FROM `testimonial`"; 
                      $data = mysqli_query($con, $query);

                      while ($rows = mysqli_fetch_array($data)) { 
                      ?>
                      <tr>
                          <td><?php echo $rows['id']; ?></td> 
                          <td><?php echo $rows['message']; ?></td> 
                          <td><?php echo $rows['name']; ?></td> 
                          <td><?php echo $rows['credinial']; ?></td>
                          <td><a href="action.php?id=<?php echo $rows['id'] ?>" style="color: red; text-decoration: none;">Delete</td>
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
    <script src="../.././table js/jquery-3.5.1.js"></script>
    <script src="../.././table js/jquery.dataTables.min.js"></script>
    <script src="../.././table js/dataTables.bootstrap5.min.js"></script>
    <script src="../.././table js/script.js"></script>
  </body>
</html>
