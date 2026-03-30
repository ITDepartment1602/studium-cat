<?php

include '../../config.php';
session_start([
  'cookie_lifetime' => 0, // Session lasts until the browser is closed
]);
$user_id = $_SESSION['user_id'];

// Clear stale NGN exam sessions when returning to dashboard
if (isset($_SESSION['current_ngn_examTaken'])) {
    unset($_SESSION['current_ngn_examTaken']);
}
if (isset($_SESSION['ngn_exam_set'])) {
    unset($_SESSION['ngn_exam_set']);
}

// Set timezone to Asia/Manila in MySQL
$quizCon = getQuizConnection();
mysqli_query($quizCon, "SET time_zone = '+08:00'"); // Adjust to your timezone if necessary

$totalQ = mysqli_fetch_assoc(mysqli_query($quizCon, "SELECT COUNT(*) as total FROM question"))['total'];

$usedQ = mysqli_fetch_assoc(mysqli_query($quizCon, "
    SELECT COUNT(DISTINCT questionId) as used 
    FROM review 
    WHERE studentId = '$user_id'
"))['used'];

$unusedQ = $totalQ - $usedQ;

$correct = mysqli_fetch_assoc(mysqli_query($quizCon, "
    SELECT COUNT(*) as correct 
    FROM review 
    WHERE studentId = '$user_id' AND ans = correctAns
"))['correct'];

$wrong = mysqli_fetch_assoc(mysqli_query($quizCon, "
    SELECT COUNT(*) as wrong 
    FROM review 
    WHERE studentId = '$user_id' AND ans != correctAns
"))['wrong'];

// Percentages
$usedPercent = ($totalQ > 0) ? round(($usedQ / $totalQ) * 100) : 0;
$correctPercent = ($correct + $wrong > 0) ? round(($correct / ($correct + $wrong)) * 100) : 0;

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
  
  .badge {
  font-size: 0.75rem;
  font-weight: 600;
}
.card {
  transition: all 0.2s ease-in-out;
}
.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(0,0,0,0.08) !important;
}

/* Dashboard Card Styling */
.dashboard-card {
  background: #fff;
  border: none;
  border-radius: 1rem;
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
  transition: all 0.25s ease;
  height: 100%;
}

.dashboard-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 18px rgba(0,0,0,0.1);
}

.dashboard-card h6 {
  font-weight: 700;
  color: #495057;
}

.dashboard-card select {
  border-radius: 8px;
  font-size: 0.85rem;
  padding: 6px 8px;
}

/* Chart Containers inside Cards */
.chart-wrapper {
  position: relative;
  width: 140px;
  height: 140px;
  margin-right: 1.5rem;
}

.chart-wrapper div {
  font-size: 0.85rem;
  color: #555;
}

.stat-details p {
  margin-bottom: 0.4rem;
  font-size: 0.9rem;
  color: #444;
}

/* Responsive Fix */
@media (max-width: 768px) {
  .chart-wrapper {
    margin: 0 auto 1rem auto;
  }
  .d-flex.flex-row.align-items-center {
    flex-direction: column;
    text-align: center;
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
  <!-- Modern Dashboard -->
<main class="mt-5 pt-5">
  <div class="container">



    <!-- Dashboard Header -->
    <div class="text-center mb-5">
      <h2 style="font-weight:800; color:#1B4965;">
        Performance Dashboard
      </h2>
      <p style="color:#6c757d;">Track your progress and start practicing smarter</p>
    </div>

    <div class="row g-4">
        
  

      <!-- Passing Rate -->
      <div class="col-lg-4 col-md-6 col-12">
        <div class="card rounded-8 h-100" style="background-color: #F9FDFF; border: 2px solid #E4F8FF;">
          <div class="card-body text-center position-relative">
            <h6 class="fw-bold text-secondary mb-3">
              <i class="fas fa-check-circle me-2 " style="color: #02968A;"></i>Chance of Passing
            </h6>
            <div class="position-relative d-inline-block">
              <canvas id="passingChart" style="max-height:220px;"></canvas>
              <!-- Percent in the center -->
              <div id="passingValue" 
                   class="position-absolute top-50 start-50 translate-middle fw-bold"
                   style="font-size:1.8rem; color:#02968A;">
              </div>
            </div>
          </div>
        </div>
      </div>


      <!-- Practice Mode -->
      <div class="col-lg-8 col-md-6 col-12">
        <div class="card rounded-4 h-100" style="background-color: #F9FDFF; border: 2px solid #E4F8FF;">
          <div class="card-body">
            <h6 class="fw-bold text-secondary mb-4">
             Choose Your Practice Mode
            </h6>
            <div class="d-flex flex-column flex-md-row flex-wrap justify-content-start gap-3">
              <?php
              // Check for active paused NGN exam
              $ngnStateCheck = mysqli_query($quizCon, "SELECT * FROM temporary_exam_state WHERE student_id = '$user_id'");
              if (mysqli_num_rows($ngnStateCheck) > 0) {
                  $stRow = mysqli_fetch_assoc($ngnStateCheck);
                  $savedTimeStr = date('M d, Y h:i A', strtotime($stRow['updated_at']));
              ?>
                <div class="d-flex align-items-center p-3 rounded-4 hover-shadow w-100"
                     style="min-width:250px; max-width:100%; height: 100%; background:#fffbeb; border: 2px solid #fde047;">
                  <div class="me-3 rounded-3" style="width:100px; height:100px; background:#fef08a; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-pause-circle" style="font-size:40px; color:#ca8a04;"></i>
                  </div>
                  <div class="flex-grow-1">
                    <h6 class="fw-bold mb-1" style="color:#854d0e;">Paused NGN Exam Active</h6>
                    <p class="text-muted small mb-2" style="color:#a16207 !important;">You have an ongoing exam. Resume where you left off. <br><span style="font-size:10px; font-weight:bold; color:#854d0e;">Saved: <?php echo $savedTimeStr; ?></span></p>
                    <div class="d-flex gap-2 mt-2">
                       <a href="ngn/index.php" class="btn btn-sm text-white px-3 shadow" style="background: linear-gradient(135deg,#ca8a04,#eab308);">
                         <i class="fas fa-play me-1"></i> Resume
                       </a>
                       <button onclick="discardExam(<?php echo $stRow['examTaken']; ?>)" class="btn btn-sm btn-outline-danger px-3 shadow-sm bg-white">
                         <i class="fas fa-trash-alt me-1"></i> Discard
                       </button>
                    </div>
                  </div>
                </div>
              <?php
              }
              error_reporting(E_ALL ^ E_WARNING); // suppress warning if missing
              $bundle_name = @$_GET['bundle_name'];
              $q = "select * from topics LEFT JOIN bundlelist on topics.title=bundlelist.bundlelist_name where bundle_name='$bundle_name'";
              $query = mysqli_query($con, $q);
              while ($row = mysqli_fetch_array($query)) {
              ?>
                <div class="d-flex align-items-center p-3 rounded-4 hover-shadow w-100"
                     style="min-width:250px; max-width:100%; height: 100%; background:#F9FDFF;">
                  <img src="../../admin/manage topics/<?php echo $row['image']; ?>"
                       class="me-3 rounded-3"
                       style="height:100px; object-fit:contain;">
                  <div class="flex-grow-1">
                    <h6 class="fw-bold mb-1"><?php echo $row['name'] ?></h6>
                    <p class="text-muted small mb-2"><?php echo $row['description'] ?></p>
                    <?php if ($row['name'] == "NARC NGN QBanks (Soon)") { ?>
                      <span class="badge bg-warning text-dark">Coming Soon</span>
                    <?php } else { ?>
                      <a href="topic.php?kilanlan=<?php echo $row['title'] ?>"
                         class="btn btn-sm text-white px-3"
                         style="background: linear-gradient(135deg,#004AAD,#02968A);">
                        Open
                      </a>
                    <?php } ?>
                  </div>
                </div>
              <?php } ?>
            </div>
          </div>
        </div>
      </div>
      
      
<!-- === STATISTICS CARDS === -->
<div class="row g-4 mb-4">
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0 fw-bold">Statistics</h4>
  
  <!-- Custom Toggle: hidden on small/mobile screens -->
  <div class="custom-toggle d-none d-md-flex" id="modeToggle" style="background: #ddd;">
    <span class="toggle-option active" data-mode="traditional">Traditional</span>
    <span class="toggle-option disabled" data-mode="ngn" style="text-decoration: line-through;">NGN</span>
  </div>
</div>


<style>
/* Container */
.custom-toggle {
  display: flex;
  border-radius: 30px;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  border: 2px solid rgba(255, 255, 255, 0.5); /* Border around toggle */
  cursor: pointer;
  user-select: none;
  font-weight: 500;
  font-size: 1rem;
}

/* Options */
.toggle-option {
  padding: 8px 25px;
  text-align: center;
  flex: 1;
  transition: all 0.3s ease;
}

/* Active Option - Traditional */
.toggle-option.active {
  background: linear-gradient(135deg,#004AAD,#02968A); /* Gradient */
  color: white;
  border-radius: 30px;
}

/* Disabled / NGN style */
.toggle-option.disabled {
  background: #ddd; /* Grey background */
  color: #888; /* Grey text */
  cursor: not-allowed;
}
</style>

<script>
const toggle = document.getElementById('modeToggle');
const options = toggle.querySelectorAll('.toggle-option');

options.forEach(option => {
  option.addEventListener('click', () => {
    if(option.dataset.mode === 'ngn') {
      // NGN is disabled → do nothing
      return;
    } else {
      // Traditional clicked → set active
      options.forEach(o => o.classList.toggle('active', o.dataset.mode === 'traditional'));
    }
  });
});
</script>

  <!-- Questions Card -->
  <div class="col-lg-6 col-12">
    <div class="card rounded-4 h-100 p-3 d-flex flex-column flex-lg-row align-items-center justify-content-around" style="background-color: #F9FDFF; border: 2px solid #E4F8FF;">

      <!-- Circle -->
      <div class="position-relative mb-3 mb-lg-0" style="width:130px; height:130px;">
        <canvas id="questionsCircle"></canvas>
        <div class="position-absolute top-50 start-50 translate-middle fw-semibold" style="font-size:0.9rem; color:#444;">
          Usage
        </div>
      </div>

      <!-- Text -->
      <div class="text-center text-lg-start">
        <p class="mb-2"><b>Total Questions :</b> <?= $totalQ ?></p>
        <p class="mb-2">
          <b>Used Questions :</b> <?= $usedQ ?>
          <span class="badge rounded-pill px-2 py-1 ms-2 text-white" style="background:#02968A;">
            <?= $usedPercent ?>%
          </span>
        </p>
        <p class="mb-0">
          <b>Unused Questions :</b> <?= $unusedQ ?>
          <span class="badge rounded-pill px-2 py-1 ms-2 text-dark" style="background:#ddd;">
            <?= 100 - $usedPercent ?>%
          </span>
        </p>
      </div>
    </div>
  </div>

  <!-- Performance Card -->
  <div class="col-lg-6 col-12">
    <div class="card rounded-4 h-100 p-3 d-flex flex-column flex-lg-row align-items-center justify-content-around" style="background-color: #F9FDFF; border: 2px solid #E4F8FF;">

      <!-- Circle -->
      <div class="position-relative mb-3 mb-lg-0" style="width:130px; height:130px;">
        <canvas id="performanceCircle"></canvas>
        <div class="position-absolute top-50 start-50 translate-middle fw-semibold" style="font-size:0.9rem; color:#444;">
          Questions
        </div>
      </div>

      <!-- Text -->
      <div class="text-center text-lg-start">
        <p class="mb-2">
          <b>Total Correct :</b> <?= $correct ?>
          <span class="badge rounded-pill px-2 py-1 ms-2 text-white" style="background:#02968A;">
            <?= $correctPercent ?>%
          </span>
        </p>
        <p class="mb-0">
          <b>Total Incorrect :</b> <?= $wrong ?>
          <span class="badge rounded-pill px-2 py-1 ms-2 text-white" style="background:#d72638;">
            <?= ($correct + $wrong > 0) ? 100 - $correctPercent : 0 ?>%
          </span>
        </p>
      </div>

    </div>
  </div>

</div>


<div class="row g-4 mb-4">

<h4 class="mb-0" style="font-weight: bold;">Topics and Concepts Statistics</h4>
 <!-- Concepts Statistics -->
  <div class="col-lg-6 col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0">Concepts Statistics</h6>
        <select id="topicSelect" class="form-select form-select-sm" style="max-width:220px;">
          <option value="Adult Health">Adult Health</option>
          <option value="Child Health">Child Health</option>
          <option value="Critical Care">Critical Care</option>
          <option value="Fundamentals">Fundamentals</option>
          <option value="Leadership And Management">Leadership And Management</option>
          <option value="Mental Health">Mental Health</option>
          <option value="Pharmacology">Pharmacology</option>
          <option value="Maternal And Newborn Health">Maternal And Newborn Health</option>
        </select>
      </div>
    <div class="  p-3" style="background-color: #F9FDFF; border: 2px solid #E4F8FF;">
    

 <div class=" rounded-4 h-100 d-flex flex-column flex-lg-row align-items-center justify-content-around">
        <!-- Circle -->
        <div class="chart-wrapper">
          <canvas id="topicChart"></canvas>
          <div class="position-absolute top-50 start-50 translate-middle fw-semibold">Score</div>
        </div>

        <!-- Stats -->
        <div class="stat-details">
          <p><b>Total Questions:</b> <span id="topicTotal">0</span></p>
          <p><b>Used Questions:</b> <span id="topicUsed">0</span></p>
          <p><b>Correct Questions:</b> <span id="topicCorrect">0</span>
            <span id="topicCorrectPercent" class="badge ms-2" style="background-color: #02968A;">0%</span>
          </p>
          <p><b>Incorrect Questions:</b> <span id="topicWrong">0</span>
            <span id="topicWrongPercent" class="badge ms-2" style="background-color: #D72638;">0%</span>
          </p>
        </div>
      </div>
    </div>
  </div>
  
    <!-- Topics Statistics -->
   <div class="col-lg-6 col-12">
       <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0">Topics Statistics</h6>
        <select id="conceptSelect" class="form-select form-select-sm" style="max-width:220px;">
          <option value="Pain Meds">Pain Meds</option>
          <option value="Antepartum">Antepartum</option>
          <option value="Assignment/Delegation">Assignment/Delegation</option>
          <option value="Cardiovascular">Cardiovascular</option>
          <option value="Oncology">Oncology</option>
          <option value="Emergency Care">Emergency Care</option>
          <option value="Endocrine">Endocrine</option>
          <option value="Nursing Legalities">Nursing Legalities</option>
          <option value="Fluid and Electrolyte">Fluid and Electrolyte</option>
          <option value="Gastrointestinal/Nutrition">Gastrointestinal/Nutrition</option>
          <option value="Growth and Development">Growth and Development</option>
          <option value="Hematology">Hematology</option>
          <option value="Immunology">Immunology</option>
          <option value="Communicable Disease">Communicable Disease</option>
          <option value="Integumentary">Integumentary</option>
          <option value="Management Concepts">Management Concepts</option>
          <option value="Psychiatry">Psychiatry</option>
          <option value="Musculoskeletal">Musculoskeletal</option>
          <option value="Neurology">Neurology</option>
          <option value="Prioritization">Prioritization</option>
          <option value="Psych Meds">Psych Meds</option>
          <option value="Respiratory">Respiratory</option>
          <option value="Skills/Procedures">Skills/Procedures</option>
          <option value="Genitourinary">Genitourinary</option>
          <option value="Eyes/Ears/Nose/Throat">Eyes/Ears/Nose/Throat</option>
          <option value="Intrapartum">Intrapartum</option>
          <option value="Postpartum">Postpartum</option>
          <option value="Labor and Delivery">Labor and Delivery</option>
          <option value="Drug Computations">Drug Computations</option>
          <option value="Culture and Religion">Culture and Religion</option>
          <option value="Neonatology">Neonatology</option>
          <option value="End of Life Care">End of Life Care</option>
          <option value="Communication">Communication</option>
        </select>
      </div>
     <div class="  p-3" style="background-color: #F9FDFF; border: 2px solid #E4F8FF;">
      

      <div class=" rounded-4 h-100 d-flex flex-column flex-lg-row align-items-center justify-content-around">
        <!-- Circle -->
          <div class="chart-wrapper">
          <canvas id="conceptChart"></canvas>
          <div class="position-absolute top-50 start-50 translate-middle fw-semibold">Score</div>
        </div>

        <!-- Text -->
         <div class="stat-details">
          <p><b>Total Questions:</b> <span id="conceptTotal">0</span></p>
          <p><b>Used Questions:</b> <span id="conceptUsed">0</span></p>
          <p><b>Correct Questions:</b> <span id="conceptCorrect">0</span>
            <span id="conceptCorrectPercent" class="badge ms-2" style="background-color: #02968A;">0%</span>
          </p>
          <p><b>Incorrect Questions:</b> <span id="conceptWrong">0</span>
            <span id="conceptWrongPercent" class="badge ms-2" style="background-color: #D72638;" >0%</span>
          </p>
        </div>
      </div>
    </div>
  </div>


</div>


      <!-- Average Scores -->
      <div class="col-12">
        <div class="card shadow-lg border-0 rounded-4 h-100 mt-3">
          <div class="card-body">
            <h6 class="fw-bold text-secondary">
             Average Scores per Concept
            </h6>
            <canvas id="scoresChart" style="max-height:260px;"></canvas>
          </div>
        </div>
      </div>

      <!-- Concept Score Cards -->
      <div class="col-12 mt-4">
        <div class="row g-3">
          <?php
          $conceptIcons = [
            'Adult Health' => 'fas fa-user-md',
            'Child Health' => 'fas fa-baby',
            'Critical Care' => 'fas fa-heartbeat',
            'Fundamentals' => 'fas fa-cogs',
            'Leadership And Management' => 'fas fa-users-cog',
            'Mental Health' => 'fas fa-brain',
            'Pharmacology' => 'fas fa-pills',
            'Maternal And Newborn Health' => 'fas fa-female'
          ];

          $concepts = array_keys($conceptIcons);

          foreach ($concepts as $concept) {
            $query = "SELECT * FROM `history` WHERE email = '$user_id' AND eid = '$concept' AND kilanlan = 'NARC Intermediate and Advance QBanks'";
            $data = mysqli_query($quizCon, $query);

            $totalScore = 0; $count = 0;
            while ($rows = mysqli_fetch_array($data)) {
              $totalScore += $rows['score'];
              $count++;
            }
            $scoreDisplay = ($count > 0) ? round($totalScore / $count) : 0;
          ?>
         <div class="col-xl-3 col-md-6 col-12">
  <div class="card border-0 shadow-sm rounded-4 h-100 text-center p-3 hover-shadow concept-card"
       data-score="<?php echo $scoreDisplay; ?>">

    <div class="card-body">
      <div class="mb-2" style="font-size:2rem; color:#02968A;">
        <i class="<?php echo $conceptIcons[$concept]; ?>"></i>
      </div>
      <p class="fw-semibold mb-1 text-dark"><?php echo $concept; ?></p>
      <h4 class="fw-bold" style="color:#1B4965;"><?php echo $scoreDisplay; ?>%</h4>

     <!-- Progress Bar -->
<div class="concept-progress">
  <div class="concept-progress-bar"
       style="width: <?php echo $scoreDisplay; ?>%;"
       aria-valuenow="<?php echo $scoreDisplay; ?>"
       aria-valuemin="0" aria-valuemax="100">
  </div>
</div>

    </div>
  </div>
</div>

          <?php } ?>
        </div>
      </div>

    </div>
  </div>
</main>

<!-- ChartJS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Passing Donut Chart
  <?php
  $select = mysqli_query($quizCon, "SELECT sum(score) FROM `history` WHERE email = '$user_id' ") or die('query failed');
  $passing = 0;
  while ($rows = mysqli_fetch_array($select)) {
    $total = mysqli_num_rows(mysqli_query($quizCon, "SELECT * FROM `history` WHERE email = '$user_id' "));
    $passing = ($total == 0) ? 0 : ($rows['sum(score)'] / $total);
  }
  $passingRounded = round($passing);
  ?>
  const passingValue = <?php echo $passingRounded; ?>;
  document.getElementById("passingValue").innerText = passingValue + "%";

  new Chart(document.getElementById('passingChart'), {
    type: 'doughnut',
    data: {
      labels: ['Achieved', 'Remaining'],
      datasets: [{
        data: [passingValue, 100 - passingValue],
        backgroundColor: ['#02968A','#e9ecef'],
        borderWidth: 0
      }]
    },
    options: {
      cutout: '75%',
      plugins: {
        legend: { display: false },
        tooltip: { enabled: true },
      }
    }
  });

  // Average Scores Line Chart
  const labels = [];
  const dataScores = [];
  <?php
  foreach ($concepts as $concept) {
    $query = "SELECT * FROM `history` WHERE email = '$user_id' AND eid = '$concept' AND kilanlan = 'NARC Intermediate and Advance QBanks'";
    $data = mysqli_query($quizCon, $query);
    $totalScore = 0; $count = 0;
    while ($rows = mysqli_fetch_array($data)) {
      $totalScore += $rows['score'];
      $count++;
    }
    $avg = ($count > 0) ? $totalScore / $count : 0;
    echo "labels.push('$concept');";
    echo "dataScores.push(Math.round($avg));";
  }
  ?>
  new Chart(document.getElementById('scoresChart'), {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: 'Average %',
        data: dataScores,
        fill: true,
        tension: 0.4,
        borderColor: '#004AAD',
        backgroundColor: 'rgba(2,150,138,0.2)',
        pointBackgroundColor: '#02968A'
      }]
    },
    options: {
      responsive: true,
      scales: { y: { beginAtZero: true, max: 100 } }
    }
  });
</script>

<style>
  .hover-shadow:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.1) !important;
  }
  
/* Default state - hidden / collapsed */
.concept-progress {
  height: 10px;
  border-radius: 6px;
  background: #e9ecef;
  overflow: hidden;
  margin-top: 8px;
}

.concept-progress-bar {
  height: 100%;
  border-radius: 6px;
  background: linear-gradient(135deg, #004AAD, #02968A);
  width: 0; /* Start collapsed */
  transition: width 1.5s ease-in-out;
}

/* Card fade-in animation */
.concept-card {
  opacity: 0;
  transform: translateY(30px);
  transition: all 0.8s ease-out;
}

.concept-card.visible {
  opacity: 1;
  transform: translateY(0);
}

</style>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const cards = document.querySelectorAll(".concept-card");

    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const card = entry.target;
          card.classList.add("visible");

          // Animate progress bar
          const progressBar = card.querySelector(".concept-progress-bar");
          const score = progressBar.getAttribute("aria-valuenow");
          progressBar.style.width = score + "%";

          observer.unobserve(card); // Trigger once only
        }
      });
    }, { threshold: 0.2 });

    cards.forEach(card => {
      observer.observe(card);
    });
  });
</script>


<!-- FontAwesome -->
<script src="https://kit.fontawesome.com/8cebfeba05.js" crossorigin="anonymous"></script>


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

<script>
  // Circle 1 (Questions)
  new Chart(document.getElementById('questionsCircle'), {
    type: 'doughnut',
    data: {
      labels: ['Used', 'Unused'],
      datasets: [{
        data: [<?= $usedQ ?>, <?= $unusedQ ?>],
        backgroundColor: ['#02968A', '#ddd'],
        borderWidth: 0
      }]
    },
  options: {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '80%',
  plugins: { legend: { display: false } }
}

  });

  // Circle 2 (Performance)
  new Chart(document.getElementById('performanceCircle'), {
    type: 'doughnut',
    data: {
      labels: ['Correct', 'Wrong'],
      datasets: [{
        data: [<?= $correct ?>, <?= $wrong ?>],
        backgroundColor: ['#02968A', '#d72638'],
        borderWidth: 0
      }]
    },
    options: {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '80%',
  plugins: { legend: { display: false } }
}

  });
</script>

<script>
let conceptChart, topicChart;

function updateConceptChart(correct, wrong) {
  if (conceptChart) conceptChart.destroy();

  let data, colors;

  if (correct + wrong === 0) {
    // No data → show full grey circle
    data = [100];
    colors = ['#ddd'];
  } else {
    // Normal data
    data = [correct, wrong];
    colors = ['#02968A', '#d72638'];
  }

  conceptChart = new Chart(document.getElementById('conceptChart'), {
    type: 'doughnut',
    data: {
      datasets: [{
        data: data,
        backgroundColor: colors,
        borderWidth: 0
      }]
    },
    options: {
      cutout: '80%',
      plugins: { legend: { display: false } },
      responsive: true,
      maintainAspectRatio: false
    }
  });
}


function updateTopicChart(correct, wrong) {
  if (topicChart) topicChart.destroy();

  let data, colors;

  if (correct + wrong === 0) {
    // No data → show full grey circle
    data = [100];
    colors = ['#ddd'];
  } else {
    // Normal data
    data = [correct, wrong];
    colors = ['#02968A', '#d72638'];
  }

  topicChart = new Chart(document.getElementById('topicChart'), {
    type: 'doughnut',
    data: {
      datasets: [{
        data: data,
        backgroundColor: colors,
        borderWidth: 0
      }]
    },
    options: {
      cutout: '80%',
      plugins: {
        legend: { display: false }
      },
      responsive: true,
      maintainAspectRatio: false
    }
  });
}


function fetchStats(type, value) {
  fetch(`get_stats.php?type=${type}&value=${encodeURIComponent(value)}`)
    .then(res => res.json())
    .then(data => {
      if (type === "concept") {
        document.getElementById("conceptTotal").innerText = data.total;
        document.getElementById("conceptUsed").innerText = data.used;
        document.getElementById("conceptCorrect").innerText = data.correct;
        document.getElementById("conceptWrong").innerText = data.wrong; 

        let correctPercent = (data.correct + data.wrong > 0) ? Math.round((data.correct / (data.correct + data.wrong)) * 100) : 0;
        let wrongPercent = (data.correct + data.wrong > 0) ? 100 - correctPercent : 0;

        document.getElementById("conceptCorrectPercent").innerText = correctPercent + "%";
        document.getElementById("conceptWrongPercent").innerText = wrongPercent + "%";

        updateConceptChart(data.correct, data.wrong);
      } else {
        document.getElementById("topicTotal").innerText = data.total;
        document.getElementById("topicUsed").innerText = data.used;
        document.getElementById("topicCorrect").innerText = data.correct;
        document.getElementById("topicWrong").innerText = data.wrong;

        let correctPercent = (data.correct + data.wrong > 0) ? Math.round((data.correct / (data.correct + data.wrong)) * 100) : 0;
        let wrongPercent = (data.correct + data.wrong > 0) ? 100 - correctPercent : 0;

        document.getElementById("topicCorrectPercent").innerText = correctPercent + "%";
        document.getElementById("topicWrongPercent").innerText = wrongPercent + "%";

        updateTopicChart(data.correct, data.wrong);
      }
    });
}

// Attach dropdown listeners
document.getElementById("conceptSelect").addEventListener("change", function() {
  fetchStats("concept", this.value);
});

document.getElementById("topicSelect").addEventListener("change", function() {
  fetchStats("topic", this.value);
});

fetchStats("concept", document.getElementById("conceptSelect").value);
fetchStats("topic", document.getElementById("topicSelect").value);

function discardExam(examTaken) {
  Swal.fire({
    title: 'Discard Progress?',
    text: "This will PERMANENTLY DELETE all answers and progress for this paused attempt. You will need to start a new exam from scratch.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, Discard Progress',
    cancelButtonText: 'Keep it'
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        const response = await fetch('ngn/cancel_exam.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ examTaken: examTaken })
        });
        const res = await response.json();
        if (res.ok) {
          Swal.fire({ title: 'Discarded', text: 'Temporary data cleared.', icon: 'success', timer: 1500, showConfirmButton: false });
          setTimeout(() => location.reload(), 1500);
        } else {
          throw new Error(res.error || 'Server Error');
        }
      } catch (err) {
        Swal.fire('Error', 'Failed to clear data.', 'error');
      }
    }
  });
}
</script>



</body>

</html>