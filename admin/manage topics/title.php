<!DOCTYPE html>
<!-- Website - www.codingnepalweb.com -->
<html lang="en" dir="ltr">

<head>
  <meta charset="UTF-8" />
 <title>Studium Admin</title>
  <link rel="shortcut icon" type="text/css" href="../../img/logo1.svg">
  <link rel="stylesheet" href="../adminstyles.css" />
  <link rel="stylesheet" href="topics.css">
  <!-- Boxicons CDN Link -->
  <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head>

<body>
  <div class="sidebar">
    <div class="logo-details">
      <center><img src="../../img/logo1.svg" width="30%"></center>
    </div>
    <ul class="nav-links">
      <li>
        <a href="../">
          <i class="bx bx-grid-alt"></i>
          <span class="links_name">Dashboard</span>
        </a>
      </li>
      <li>
        <a href="#" class="active">
          <i class="bx bx-box"></i>
          <span class="links_name">Manage Topics</span>
        </a>
      </li>
      <li>
        <a href="../manage question">
          <i class="bx bx-list-ul"></i>
          <span class="links_name">Manage Question</span>
        </a>
      </li>
      <li>
        <a href="../manage bundle">
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
        <a href="../manage feedback">
          <i class="bx bx-heart"></i>
          <span class="links_name">Feedback</span>
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
      <div class="sales-boxes">
        <div class="recent-sales box">
          <div class="title">Manage Concept</div>


          <br>
          <div class="container">
            <div class="box-container">
              <?php
              include("../../config.php");
              $title = $_GET['title'];
              $q = "SELECT * from `topics1` where kilanlan = '$title'";
              //echo $course_name;
              $query = mysqli_query($con, $q);
              while ($row = mysqli_fetch_array($query)) {

                ?>
                <div class="box">
                  <img src="<?php echo $row['img']; ?>" alt="" style="width: 260px; height: 300px;">
                  <h3><?php echo $row['title'] ?></h3>
                  <a href="questionlist.php?title=<?php echo $_GET['title']; ?>&concept=<?php echo $row['title']; ?>"
                    class="btn">Open</a>
                  <a href="index.php" class="btn1" name="btn-delete">Back</a>
                </div>
              <?php } ?>
            </div>
          </div>

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
</body>

</html>