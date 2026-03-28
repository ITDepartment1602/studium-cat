<?php

include '../../config.php';
session_start([
  'cookie_lifetime' => 0, // Session lasts until the browser is closed
]);
$user_id = $_SESSION['user_id'];

// Set timezone to Asia/Manila in MySQL
mysqli_query($con, "SET time_zone = '+08:00'"); // Adjust to your timezone if necessary

// Update last login and login status
if (isset($user_id)) {
  // Update lastlogin and loginstatus in a single query
  $updateQuery = "UPDATE login SET lastlogin = NOW(), loginstatus = 'Active now' WHERE id = $user_id";
  $result = mysqli_query($con, $updateQuery);

  if (!$result) {
    // Handle error if the update fails
    echo "Error updating last login and status: " . mysqli_error($con);
  }
} else {
  // Handle case where user_id is not set
  echo "User not logged in.";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../ty/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../ty/css/dataTables.bootstrap5.min.css" />
  <link rel="stylesheet" href="../ty/css/style.css" />
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="../pchart/pchart.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/progressbar.js/1.1.0/progressbar.min.js"></script>
  <link rel="stylesheet" href="../pricing/moda.css">
  <link rel="stylesheet" href="../pricing/exam.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <title>studium</title>
  <link rel="shortcut icon" type="text/css" href="../../img/logo1.svg">
</head>
<style>
  /* Create three equal columns that floats next to each other */
  .column,
  .column1,
  .column2 {
    position: absolute;
    float: left;
    margin-top: -100px;
    /* Should be removed. Only for demonstration */
    text-align: center;
    color: #FFF;
    width: 23%;
  }

  .column {
    margin-left: 9%;
    border-radius: 50px;
  }

  .column1 {
    margin-left: 31%;
  }

  .column2 {
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

  .tooltip {
    position: relative;
    float: right;
    margin-top: -10px;
  }

  .tooltip>.tooltip-inner {
    background-color: #0A2558;
    color: white;
    border-radius: 5px;
    width: 55px;
  }

  .popOver+.tooltip>.tooltip-arrow {
    border-top: 10px solid #0A2558;
    border-left: 10px solid transparent;
    border-right: 10px solid transparent;
    margin-bottom: -80%;
    margin-left: 9px;
    width: 10px;
    border-radius: 0 0 10px 10px;
  }

  .progress-bar {
    display: block;
    background: white;
    padding: 100px;
    margin-left: 70px;
    margin-top: -50px;
    box-shadow: 0 0 10px white;
  }


  .score-box {
    background-color: #1B4965;
    /* Box color */
    color: white;
    /* Text color */
    padding: 20px;
    /* Padding inside the box */
    border-radius: 8px;
    /* Rounded corners */
    text-align: center;
    /* Center text */
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    /* Shadow effect */
    transition: transform 0.2s;
    /* Smooth effect on hover */
  }

  .score-box:hover {
    transform: scale(1.05);
    /* Scale up on hover */
  }




  .canvascsss {

    width: 80%;
    margin: auto;
  }

  @media (max-width:768px) {

    .row1 {
      display: none;
    }

    .canvascsss {
      max-width: 400px;
    }



  }

  @media (max-width:1440px) {

    .canvascsss {
      max-width: 1100px;
    }

  }


  @media (max-width: 1024px) {
    #scoresChart {
      display: none;
      /* Hide the chart on screens smaller than 1024px */
    }
  }
</style>

<body>

  <!-- top navigation bar -->
  <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background-color:#1B4965;">
    <div class="container-fluid">
      <button class="navbar-toggler" style="color: #fff; font-size: 10px" type="button" data-bs-toggle="offcanvas"
        data-bs-target="#sidebar" aria-controls="offcanvasExample">
        <span class="navbar-toggler-icon" data-bs-target="#sidebar"></span>
      </button>
      <?php
      $select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'") or die('query failed');
      if (mysqli_num_rows($select) > 0) {
        $fetch = mysqli_fetch_assoc($select);
      }
      date_default_timezone_set('Asia/Manila');
      $dateExpired = strtotime($fetch['dateexpired']);
      $today = strtotime(date('Y-m-d H:i:s'));
      $diff = $dateExpired - $today;
      $daysLeft = floor($diff / (60 * 60 * 24));

      $interval = '';
      if ($daysLeft == 7) {
        $interval = '1 week';
      } else if ($daysLeft > 1) {
        $interval = $daysLeft . ' days';
      } else if ($daysLeft == 1) {
        $interval = '1 day';
      } else if ($daysLeft < 0) {
        header('Location: ../../logout.php');
        exit;
      }

      if ($daysLeft == 0 && $diff > 0) {
        echo '<span class="text-white notif1" style="color: #fff;"> <i class="fa fa-bell"></i> Your account is expiring today.</span>';
      } else if ($daysLeft <= 7 && $daysLeft > 0) {
        echo '<span class="text-white notif2" style="color: #fff;"> <i class="fa fa-bell"> </i> ' . $interval . ' remaining until expiration.</span>';
      }
      ?>
      <a class="navbar-brand me-auto ms-lg-0 ms-3 text-uppercase fw-bold" style="font-size: 20px"></a>
      <a class="nav-link text-white" href="../../logout.php">

        <i class="fa fa-sign-out logouts" style="color: #fff; " aria-hidden="true"></i>
      </a>

      <style>
        .logouts {

          font-size: 13px;
        }

        .notif1 {

          font-size: 10px;
        }

        .notif2 {

          font-size: 11px;
        }

        @media (min-width: 768px) {
          .logouts {
            font-size: 32px;
          }

          .notif1 {

            font-size: 14px;
          }

          .notif2 {

            font-size: 14px;
          }
        }
      </style>
    </div>
  </nav>
  <!-- top navigation bar -->

  <!-- offcanvas -->
  <div class="offcanvas offcanvas-start sidebar-nav ml-6" tabindex="-1" id="sidebar">
    <div class="offcanvas-body p-0" style=" background-color: #62B6CB;">
      <nav class="navbar-dark" style="width: 100%; ">
        <ul class="navbar-nav" style=" background-color: #62B6CB; padding-bottom: 80%;">

          <!---<form action="" method=" POST">
          <div class="col-md-auto">
            <input type="text" name="search" class='form-control' placeholder="Search By Name" value="">


            </form>--->
          <?php
          $select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'") or die('query failed');
          if (mysqli_num_rows($select) > 0) {
            $fetch = mysqli_fetch_assoc($select);
          }
          ?>



          <li style="width: 100%; background-color: #62B6CB;">
            <table id="table" style="margin-top: 20px; width: 100%; background-color: #1B4965;">
              <tr>
                <td class="nav-link px-1">
                </td>
                <img src="../../img/logo2.svg" style="width:100px; margin-left: 50px; margin-bottom: 50px;">

              </tr>
              <tr>
                <td class="nav-link px-1">
                </td>
                <div>
                  <p style="margin-top: -10%; color: black; font-weight: normal; text-align: center;">
                    Hello
                    <span style="font-weight: bold; ">
                      <?php echo explode(' ', trim($fetch['fullname']))[0]; ?>!
                    </span>

                  </p>

                </div>
              </tr>

              <tr>
                <td class="nav-link px-1">
                </td>
                <td>
                  <a href="index.php?bundle_name=<?php echo $fetch['bundle_name']; ?>" id="myVideo" class="nav-link">
                    <p style="font-size: 14px;"> <i class="bi bi-house" style="font-size: 17px;"></i> Home ></p>
                  </a>
                </td>
              </tr>

              <tr>
                <td class="nav-link px-1">
                </td>
                <td>
                  <a href="profile.php" id="myVideo" class="nav-link">
                    <p style="font-size: 14px;"><i class="bi bi-person-square" style="font-size: 17px;"></i> View
                      Profile ></p>
                  </a>
                </td>
              </tr>
              <tr>
                <td class="nav-link px-1">
                </td>
                <?php
                $select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'") or die('query failed');
                if (mysqli_num_rows($select) > 0) {
                  $fetch = mysqli_fetch_assoc($select);
                }
                ?>
                <td>
                  <a href="note.php?id=<?php echo $fetch['id'] ?>" id="myVideo" class="nav-link">
                    <p style="font-size: 14px;"><i class="bi bi-journal" style="font-size: 17px;"></i> My Notes ></p>
                  </a>
                </td>
              </tr>
              <tr>
                <td class="nav-link px-1">
                </td>
                <td>
                  <a href="../../img/userguide.mp4" target="_blank" rel="noopener noreferrer" id="myVideo"
                    class="nav-link">
                    <p style="font-size: 14px;"><i class="bi bi-question-circle" style="font-size: 17px;"></i></i> User
                      Guide ></p>
                  </a>
                </td>
              </tr>


              <tr>
                <td class="nav-link px-1">
                </td>
                <td>
                  <a href="subscription.php" id="myVideo" class="nav-link">
                    <p style="font-size: 14px;"><i class="bi bi-calendar-check" style="font-size: 17px;"></i>
                      Subscription ></p>
                  </a>
                </td>
              </tr>

              <tr>
                <td class="nav-link px-1">
                </td>
                <td>
                  <a href="package.php" id="myVideo" class="nav-link">
                    <p style="font-size: 14px;"><i class="bi bi-box-seam" style="font-size: 17px;"></i> Package >
                    </p>
                  </a>
                </td>
              </tr>




              <tr>
                <td class="nav-link px-1">
                </td>
                <td>
                  <a href="https://www.facebook.com/NCLEX.Amplified.Official" target="_blank" id="myVideo"
                    class="nav-link">
                    <p style="font-size: 14px;"><i class="bi bi-telephone" style="font-size: 17px;"></i> Contact Us
                      >
                    </p>
                  </a>
                </td>
              </tr>
            </table>

          </li>
          <tr>
            <td class="nav-link px-1">
            </td>

          </tr>

          <div
            style="position: absolute; bottom: 0; left: 0; right: 0; text-align: center; padding-bottom: 20px; background-color: #62B6CB;">
            <li style="background-color: #62B6CB; margin-top: -8px;">
              <hr class="dropdown-divider bg-dark" style="" />
            </li>
            <?php
            $select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'") or die('query failed');
            if (mysqli_num_rows($select) > 0) {
              $fetch = mysqli_fetch_assoc($select);
            }
            ?>
            <p style="color: black; font-weight: bold; font-size: 14px; text-align: center; ">
              Expiration
              Date</p>
            <p style="color: black; font-size: 14px; text-align: center; margin-top: -20px;">
              <?php echo date('F d, Y', strtotime($fetch['dateexpired'])); ?><br>
              <?php echo date('h:i A', strtotime($fetch['dateexpired'])); ?>
            </p>
            <a href="https://www.facebook.com/NCLEX.Amplified.Official" target="_blank">
              Upgrade Now!
            </a>
          </div>
        </ul>
      </nav>
    </div>
  </div>
  <!-- offcanvas -->
  <main class="mt-5 pt-5">
    <div class="container1" style="margin-top: -10px">
      <div class="box-container">
        <div class="box">

          <center>
            <h2>Chance of passing</h2>
          </center>

          <?php
          $select = mysqli_query($con, "SELECT sum(score) FROM `history` WHERE email = '$user_id' ") or die('query failed');
          while ($rows = mysqli_fetch_array($select)) {
            $total = mysqli_num_rows(mysqli_query($con, "SELECT * FROM `history` WHERE email = '$user_id' "));
            $totalPercentage = ($total == 0) ? 0 : ($rows['sum(score)'] / $total); ?>



            <div
              style="display: flex; align-items: center; width: 80%; margin: auto; margin-top: 25px; height: 40px; background: linear-gradient(to right, #DA3134, #004AAD, #02968A); border-radius: 20px; position: relative;">
              <div style="position: absolute; left: 10%; font-weight: bold; color: white;">Low</div>
              <div style="position: absolute; left: 50%; transform: translateX(-50%); font-weight: bold; color: white;">
                Mid</div>
              <div style="position: absolute; right: 10%; font-weight: bold; color: white;">High</div>
              <div
                style="position: absolute; left: <?php echo round($totalPercentage); ?>%; transform: translateX(-50%); top: -50%; background: navy; color: white; padding: 5px 10px; border-radius: 5px;">
                <?php echo round($totalPercentage); ?>%
              </div>
            </div>
          </div>

        <?php } ?>

      </div>
    </div>
    </div>
  </main>

  <main class="mt-1 pt-1">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12 mb-3">
          <div class="card-body">
            <div class="container">
              <div class="box-container">
                <?php

                $bundle_name = $_GET['bundle_name'];

                $q = "select * from topics LEFT JOIN bundlelist on topics.title=bundlelist.bundlelist_name where bundle_name='$bundle_name'";
                //echo $course_name;
                $query = mysqli_query($con, $q);
                while ($row = mysqli_fetch_array($query)) {

                  ?>
                                  <div class="box">
                  <img src="../../admin/manage topics/<?php echo $row['image']; ?>"
                       style="width: 240px; height: 300px; border: 2px solid #1B4965;">
                  <h3><?php echo $row['name'] ?></h3>
                  <p><?php echo $row['description'] ?></p>
                
                  <?php if ($row['name'] == "NARC NGN QBanks (Soon)") { ?>
                    <button class="btn ngn-announcement"
                            style="background: #1B4965; width: 200px; cursor: pointer;">
                      Coming Soon
                    </button>
                  <?php } else { ?>
                    <a href="topic.php?kilanlan=<?php echo $row['title'] ?>" class="btn"
                       style="background: #1B4965; width: 200px;">Open</a>
                  <?php } ?>
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

    <div class="container">
      <h3 style="color:#1B4965;">Your Average Scores</h3>
      <canvas id="scoresChart" class="canvascsss"></canvas>

      <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
      <script>
        const labels = [];
        const dataScores = [];

        <?php
        include('../../config.php');
        // Array of concepts to process
        $concepts = [
          'Adult Health',
          'Child Health',
          'Critical Care',
          'Fundamentals',
          'Leadership And Management',
          'Mental Health',
          'Pharmacology',
          'Maternal And Newborn Health'
        ];

        foreach ($concepts as $concept) {
          // Prepare the query for each concept
          $query = "SELECT * FROM `history` WHERE email = '$user_id' AND eid = '$concept' AND kilanlan = 'NARC Intermediate and Advance QBanks'";
          $data = mysqli_query($con, $query);

          $totalScore = 0; // Initialize total score to 0
          $count = 0; // Initialize count to 0
        
          while ($rows = mysqli_fetch_array($data)) {
            $score = $rows['score'];
            $totalScore += $score; // Accumulate the score
            $count++; // Increment the count
          }

          // Calculate average score
          if ($count > 0) {
            $averageScore = $totalScore / $count; // Calculate average
            echo "labels.push('$concept');";
            echo "dataScores.push($averageScore);";
          } else {
            echo "labels.push('$concept');";
            echo "dataScores.push(0);"; // Display 0 if no entries found
          }
        }
        ?>

        // Function to create the chart
        function createChart() {
          const ctx = document.getElementById('scoresChart').getContext('2d');
          new Chart(ctx, {
            type: 'bar',
            data: {
              labels: labels,
              datasets: [{
                label: 'Average Percentage',
                data: dataScores.map(score => Math.round(score)),
                backgroundColor: '#1B4965',
                fill: true,
                tension: 0.3
              }]
            },
            options: {
              responsive: true,
              scales: {
                x: {
                  ticks: {
                    maxRotation: 0,
                    minRotation: 0,
                    autoSkip: false,
                    font: {
                      size: 10
                    }
                  }
                },
                y: {
                  beginAtZero: true,
                  suggestedMax: 100,
                  ticks: {
                    font: {
                      size: 10
                    }
                  }
                }
              }
            }
          });
        }

        // Check if the screen width is 1024px or wider
        if (window.innerWidth > 1024) {
          createChart(); // Create the chart if the condition is met
        }
      </script>
    </div>
    <div class="container" style="margin-top: 50px">

      <div class="row">
        <?php
        include('../../config.php');

        // Array of concepts to process
        $concepts = [
          'Adult Health',
          'Child Health',
          'Critical Care',
          'Fundamentals',
          'Leadership And Management',
          'Mental Health',
          'Pharmacology',
          'Maternal And Newborn Health'
        ];

        foreach ($concepts as $concept) {
          // Prepare the query for each concept
          $query = "SELECT * FROM history WHERE email = '$user_id' AND eid = '$concept' AND kilanlan = 'NARC Intermediate and Advance QBanks'";
          $data = mysqli_query($con, $query);

          $totalScore = 0; // Initialize total score to 0
          $count = 0; // Initialize count to 0
        
          while ($rows = mysqli_fetch_array($data)) {
            $score = $rows['score'];
            $totalScore += $score; // Accumulate the score
            $count++; // Increment the count
          }

          // Calculate average score
          if ($count > 0) {
            $averageScore = $totalScore / $count; // Calculate average
            $scoreDisplay = round($averageScore) . '%';
          } else {
            $scoreDisplay = '0%'; // Display 0 if no entries found
          }
          ?>

          <div class="col-xl-3 col-md-6 col-12 mb-3">
            <div class="score-box">
              <p><?php echo $concept; ?></p>
              <h4><?php echo $scoreDisplay; ?></h4>
            </div>
          </div>

        <?php } ?>
      </div>
    </div>
  </main>

  <div class="scroll" id="btm">
    <button><i class="fa fa-chevron-circle-down fa-3x" aria-hidden="true"></i></button>
  </div>

  <br><br><br>

  <div class="copy"
    style="background-color: #1B4965; height: 30px; position: fixed; bottom: 0; left: 0; right: 0; text-align: center;">
    <center><span style="color:white;">© Studium 2025, All Right Reserved.</span></center>
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
      window.scrollTo(0, document.body.scrollHeight);
    })
  </script>

  <!--=============== GRAPH ===============-->
  <script>
    $(function () {
      $('[data-toggle="tooltip"]').tooltip({ trigger: 'manual' }).tooltip('show');
    });

    // $( window ).scroll(function() {   
    // if($( window ).scrollTop() > 10){  // scroll down abit and get the action   
    $(".progress-bar").each(function () {
      each_bar_width = Math.min(parseInt($(this).attr('aria-valuenow')), 100);
      $(this).width(each_bar_width + '%');
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

    function run() {
      var fName = arguments[0],
        aArgs = Array.prototype.slice.call(arguments, 1);
      try {
        fName.apply(window, aArgs);
      } catch (err) {

      }
    };

    /* ===================== chart ============================= */
    function _chart() {
      $('.b-skills').appear(function () {
        setTimeout(function () {
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
            onStep: function (from, to, percent) {
              this.el.children[0].innerHTML = Math.round(percent);
            }
          });
        }, 150);
      });
    };


    $(document).ready(function () {
      run(_chart);
    });
  </script>

  <script>
    /* ===================== drag ============================= */
    const wrapper = document.querySelector(".wrappera"),
      header = wrapper.querySelector("header");
    function onDrag({ movementX, movementY }) {
      let getStyle = window.getComputedStyle(wrapper);
      let leftVal = parseInt(getStyle.left);
      let topVal = parseInt(getStyle.top);
      wrapper.style.left = `${leftVal + movementX}px`;
      wrapper.style.top = `${topVal + movementY}px`;
    }
    header.addEventListener("mousedown", () => {
      header.classList.add("active");
      header.addEventListener("mousemove", onDrag);
    });
    document.addEventListener("mouseup", () => {
      header.classList.remove("active");
      header.removeEventListener("mousemove", onDrag);
    });
  </script>

  <script>
    const wrappera = document.querySelector(".wrapper"),
      headera = wrappera.querySelector(".modal-header");
    function onDraga({ movementX, movementY }) {
      let getStyle = window.getComputedStyle(wrappera);
      let leftVal = parseInt(getStyle.left);
      let topVal = parseInt(getStyle.top);
      wrappera.style.left = `${leftVal + movementX}px`;
      wrappera.style.top = `${topVal + movementY}px`;
    }
    headera.addEventListener("mousedown", () => {
      headera.classList.add("active");
      headera.addEventListener("mousemove", onDraga);
    });
    document.addEventListener("mouseup", () => {
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
    btn.onclick = function () {
      modal.style.display = "block";
    }

    // When the user clicks on <span> (x), close the modal
    span.onclick = function () {
      modal.style.display = "none";
    }
  </script>
  
  
  <script>
       /* ===================== COMING SOON ============================= */
  document.addEventListener("DOMContentLoaded", function () {
    const ngnButton = document.querySelector(".ngn-announcement");
    if (ngnButton) {
      ngnButton.addEventListener("click", function () {
        Swal.fire({
          title: "Coming Soon: NARC NGN QBanks Package",
          html: `
            
      <p>
        We’ve heard your excitement and questions about the upcoming <strong>NARC NGN Computer Adaptive Test (CAT)</strong> QBank package.
      </p>
      <p>
        Please be informed that this is a <strong>completely separate review experience</strong> designed specifically for NGN. As of now, there is 
        <strong>no official release date</strong>.
      </p>
      <p>
        We understand many students are eagerly waiting and sometimes confused about the status. We truly appreciate your patience and interest.
      </p>
      <p>
         Stay tuned official announcements will be made on our platform. We're working hard to provide the highest quality preparation tools tailored for NGN.
      </p>
      <p style="margin-top: 10px;">
        The NARC Team
      </p>
          `,
          icon: "",
          confirmButtonText: "Got it!",
          confirmButtonColor: "#1B4965"
        });
      });
    }
  });
</script>

</body>

</html>