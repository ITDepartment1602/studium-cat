<?php

include '../../config.php';
session_start();
$user_id = $_SESSION['user_id'];

$email = isset($_GET['email']) ? mysqli_real_escape_string($con, $_GET['email']) : '';
$eid = isset($_GET['eid']) ? mysqli_real_escape_string($con, $_GET['eid']) : '';
$topics1 = isset($_GET['topics1']) ? mysqli_real_escape_string($con, $_GET['topics1']) : '';
$kilanlan = isset($_GET['kilanlan']) ? mysqli_real_escape_string($con, $_GET['kilanlan']) : '';

$level = isset($_GET['level']) ? mysqli_real_escape_string($con, $_GET['level']) : '';
$sahi = isset($_GET['sahi']) ? mysqli_real_escape_string($con, $_GET['sahi']) : '';
$wrong = isset($_GET['wrong']) ? mysqli_real_escape_string($con, $_GET['wrong']) : '';


// Fetch user information
$select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'") or die('query failed');
if (mysqli_num_rows($select) > 0) {
  $fetch = mysqli_fetch_assoc($select);
}

$examTakenQuery = mysqli_query($con, "SELECT examTaken FROM `login` WHERE id = '$user_id'") or die('query failed');
$examTaken = mysqli_fetch_assoc($examTakenQuery)['examTaken'] ?? 0;
$examTakenMinus = $examTaken - 1;

// Count the number of correct answers
$correctAnswersQuery = mysqli_query($con, "SELECT COUNT(*) as correctCount FROM `review` WHERE studentId = '$user_id' AND isCorrect = 1 AND examTaken = '$examTakenMinus'") or die('query failed');
$correctAnswersCount = mysqli_fetch_assoc($correctAnswersQuery)['correctCount'] ?? 0;

// Count the number of wrong answers
$wrongAnswersQuery = mysqli_query($con, "SELECT COUNT(*) as wrongCount FROM `review` WHERE studentId = '$user_id' AND isCorrect = 0 AND examTaken = '$examTakenMinus'") or die('query failed');
$wrongAnswersCount = mysqli_fetch_assoc($wrongAnswersQuery)['wrongCount'] ?? 0;

$totalTimeQuery = mysqli_query($con, "SELECT totalTime FROM `review` WHERE studentId = '$user_id' AND examTaken = '$examTakenMinus' AND questionNumber = 150") or die('query failed');
$totalTime = mysqli_fetch_assoc($totalTimeQuery)['totalTime'] ?? 0;

$score = ($correctAnswersCount / 150) * 100;

date_default_timezone_set('Asia/Manila');
$date = date('Y-m-d H:i:s');

$insertHistoryQuery = "INSERT INTO `history` ( email, eid, kilanlan, score, level, sahi, wrong, date) VALUES ( '$user_id', '$topics1', 'NARC Intermediate and Advance QBanks', '$score', '150', '$correctAnswersCount', '$wrongAnswersCount', NOW())";
mysqli_query($con, $insertHistoryQuery) or die('query failed');

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
  <link rel="stylesheet" href="../pricing/exam.css">
  <link rel="stylesheet" href="../pchart/pchart.css">
  <link rel="stylesheet" href="../pricing/moda.css">
  <title>studium</title>
  <link rel="shortcut icon" type="text/css" href="../../img/logo1.svg">
</head>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700&display=swap');

  * {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    outline: none;
    border: none;
    text-decoration: none;
    text-transform: capitalize;
  }

  .container .box-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 10px;
  }

  @media (max-width:768px) {
    .container {
      padding: 10px;
      margin-left: -15px;
    }
  }
  
  .card h5 {
  margin-top: 0;
  margin-bottom: 0.5rem; /* small space */
}

.chart-container {
  margin: 0 auto; /* center horizontally */
  padding: 0;      /* remove extra padding */
  margin-top: 0;   /* remove top margin */
  width: 120px;
  height: 120px;   /* fix height so chart fits nicely */
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
      $dateExpired = date('Y-m-d H:i:s', strtotime($fetch['dateexpired']));
      $today = date('Y-m-d H:i:s');
      $diff = date_diff(date_create($today), date_create($dateExpired));
      $daysLeft = (int) $diff->format('%d');
      $interval = '';
      if ($daysLeft == 7) {
        $interval = '1 week';
      } else if ($daysLeft > 1) {
        $interval = $daysLeft . ' days';
      } else if ($daysLeft == 1) {
        $interval = '1 day';
      } else if ($daysLeft < 0) {
        header('Location: ../../index.php');
        exit;
      }

      if ($daysLeft == 0) {
        echo '<span class="text-white notif1" style="color: #fff;"> <i class="fa fa-bell"></i> Your account was expiring today.</span>';
      } else if ($daysLeft <= 7) {
        echo '<span class="text-white notif2" style="color: #fff;"> <i class="fa fa-bell"> </i> ' . $interval . ' remaining until expiration.</span>';
      }
      ?>
      <a class="navbar-brand me-auto ms-lg-0 ms-3 text-uppercase fw-bold" style="font-size: 20px"></a>
      <a class="nav-link text-white" href="../../index.php">
        <i class="fa fa-sign-out logouts" style="color: #fff; " aria-hidden="true"></i>
      </a>
      <style>
        .logouts { font-size: 13px; }
        .notif1 { font-size: 10px; }
        .notif2 { font-size: 11px; }
        @media (min-width: 768px) {
          .logouts { font-size: 32px; }
          .notif1 { font-size: 14px; }
          .notif2 { font-size: 14px; }
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
          <?php
          $select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'") or die('query failed');
          if (mysqli_num_rows($select) > 0) {
            $fetch = mysqli_fetch_assoc($select);
          }
          ?>
          <li style="width: 100%; background-color: #62B6CB;">
            <table id="table" style="margin-top: 20px; width: 100%; background-color: #1B4965;">
              <tr>
                <td class="nav-link px-1"></td>
                <img src="../../img/logo2.svg" style="width:100px; margin-left: 50px; margin-bottom: 50px;">
              </tr>
              <tr>
                <td class="nav-link px-1"></td>
                <div>
                  <p style="margin-top: -10%; color: black; font-weight: normal; text-align: center;">
                    Hello <span style="font-weight: bold; ">
                      <?php echo explode(' ', trim($fetch['fullname']))[0]; ?>!
                    </span>
                  </p>
                </div>
              </tr>
              <tr>
                <td class="nav-link px-1"></td>
                <td>
                  <a href="index.php?bundle_name=<?php echo $fetch['bundle_name']; ?>" id="myVideo" class="nav-link">
                    <p style="font-size: 14px;"> <i class="bi bi-house" style="font-size: 17px;"></i> Home ></p>
                  </a>
                </td>
              </tr>
              <tr>
                <td class="nav-link px-1"></td>
                <td>
                  <a href="profile.php" id="myVideo" class="nav-link">
                    <p style="font-size: 14px;"><i class="bi bi-person-square" style="font-size: 17px;"></i> View Profile ></p>
                  </a>
                </td>
              </tr>
              <tr>
                <td class="nav-link px-1"></td>
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
                <td class="nav-link px-1"></td>
                <td>
                  <a href="" id="myVideo" class="nav-link">
                    <p style="font-size: 14px;"><i class="bi bi-question-circle" style="font-size: 17px;"></i> User Guide ></p>
                  </a>
                </td>
              </tr>
              <tr>
                <td class="nav-link px-1"></td>
                <td>
                  <a href="subscription.php" id="myVideo" class="nav-link">
                    <p style="font-size: 14px;"><i class="bi bi-calendar-check" style="font-size: 17px;"></i> Subscription ></p>
                  </a>
                </td>
              </tr>
              <tr>
                <td class="nav-link px-1"></td>
                <td>
                  <a href="package.php" id="myVideo" class="nav-link">
                    <p style="font-size: 14px;"><i class="bi bi-box-seam" style="font-size: 17px;"></i> Package ></p>
                  </a>
                </td>
              </tr>
              <tr>
                <td class="nav-link px-1"></td>
                <td>
                  <a href="https://www.facebook.com/NCLEX.Amplified.Technical" target="_blank" id="myVideo" class="nav-link">
                    <p style="font-size: 14px;"><i class="bi bi-telephone" style="font-size: 17px;"></i> Contact Us ></p>
                  </a>
                </td>
              </tr>
            </table>
          </li>
          <tr><td class="nav-link px-1"></td></tr>
          <div style="position: absolute; bottom: 0; left: 0; right: 0; text-align: center; padding-bottom: 20px; background-color: #62B6CB;">
            <li style="background-color: #62B6CB; margin-top: -8px;">
              <hr class="dropdown-divider bg-dark" />
            </li>
            <?php
            $select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'") or die('query failed');
            if (mysqli_num_rows($select) > 0) {
              $fetch = mysqli_fetch_assoc($select);
            }
            ?>
            <p style="color: black; font-weight: bold; font-size: 14px; text-align: center; ">Expiration Date</p>
            <p style="color: black; font-size: 14px; text-align: center; margin-top: -20px;">
              <?php echo date('F d, Y', strtotime($fetch['dateexpired'])); ?><br>
              <?php echo date('h:i A', strtotime($fetch['dateexpired'])); ?>
            </p>
            <a href="https://www.facebook.com/NCLEX.Amplified.Official" target="_blank">Upgrade Now!</a>
          </div>
        </ul>
      </nav>
    </div>
  </div>
  <!-- offcanvas -->

<main class="mt-5 pt-4">
  <div class="container-fluid">
    <h2 class="text-black my-4 font-bold">Test Results</h2>

    <div class="row g-4">
      <!-- Score Card -->
      <div class="col-md-4">
        <div class="card text-center p-4">
          <h5>Points Scored</h5>
          <div class="chart-container mb-2">
            <canvas id="scoreChart"></canvas>
          </div>
          <h4><?= number_format($score,2); ?>%</h4>
        </div>
      </div>

      <!-- Result Details -->
      <div class="col-md-8">
        <div class="card p-4">
          <h5>Result</h5>
          <table class="table">
            <tr><td>Total Questions</td><td>150</td></tr>
            <tr><td>Right Answers</td><td class="text-success"><?= $correctAnswersCount; ?></td></tr>
            <tr><td>Wrong Answers</td><td class="text-danger"><?= $wrongAnswersCount; ?></td></tr>
            <tr><td>Total Time Taken</td><td>
              <?php
                $h = floor($totalTime/3600); $m=floor(($totalTime%3600)/60); $s=$totalTime%60;
                echo ($h>0?"$h hr ":"") . ($m>0?"$m min ":"") . "$s sec";
              ?>
            </td></tr>
          </table>
          <a href="index.php?bundle_name=<?= $fetch['bundle_name']; ?>" class="btn float-end" style="background: #1B4965; color: white; ">Go to Dashboard</a>
        </div>
      </div>
    </div>

     <!-- Review Table -->
    <div class="card mt-4 p-3">
      <h5>Review</h5>
      <div class="table-responsive">
        <table id="reviewTable" class="table table-striped table-hover">
          <thead>
            <tr>
              <th>#</th><th>ID</th><th>Concepts</th><th>Topics</th><th>Client Needs</th><th>Time Taken</th><th>Status</th><th>Action</th>
            </tr>
          </thead>
           <tbody>
          <?php
          // Update the query to include 'timeTaken' instead of 'time'
          $examTakenQuery = mysqli_query($con, "SELECT examTaken FROM `login` WHERE id = '$user_id'") or die('query failed');
          $examTaken = mysqli_fetch_assoc($examTakenQuery)['examTaken'] ?? 0;
          $examTakenMinus = $examTaken - 1;

          $reviewQuery = mysqli_query($con, "SELECT isCorrect, questionNumber, questionId, topics1, system, cnc, timeTaken, ans, correctAns FROM `review` WHERE studentId = '$user_id' AND examTaken = '$examTakenMinus' ") or die('query failed');
          while ($row = mysqli_fetch_assoc($reviewQuery)) {
            echo "<tr>";

            echo "<td>" . $row["questionNumber"] . "</td>";

            echo "<td>" . str_pad($row['questionId'], 5, '0', STR_PAD_LEFT) . "</td>";
            echo "<td>" . $row['topics1'] . "</td>";
            echo "<td>" . $row['system'] . "</td>";
            echo "<td>" . $row['cnc'] . "</td>";
            // Convert timeTaken to a more readable format
            $timeTaken = $row['timeTaken'];
            $minutes = floor($timeTaken / 60);
            $seconds = $timeTaken % 60;
            $formattedTime = ($minutes > 0 ? $minutes . ' min ' : '') . $seconds . ' sec';
            echo "<td>" . $formattedTime . "</td>";
            echo "<td style='color:" . ($row['isCorrect'] ? 'green' : 'red') . ";'>" . ($row['isCorrect'] ? '✔' : '✗') . "</td>";
            echo "<td><a href='rationale/qpages.php?isCorrect=" . $row['isCorrect'] .
              "&questionId=" . $row['questionId'] .
              "&questionNumber=" . $row['questionNumber'] .  // <-- Added this line
              "&topics1=" . urlencode($row['topics1']) .
              "&system=" . urlencode($row['system']) .
              "&cnc=" . urlencode($row['cnc']) .
              "&timeTaken=" . $row['timeTaken'] .
              "&ans=" . urlencode($row['ans']) .
              "&correctAns=" . urlencode($row['correctAns']) .
              "' target='_blank' style='color: #1B4965; text-decoration: underline;'>View</a></td>";


            echo "</tr>";
          }
          ?>
        </tbody>
        </table>
      </div>
    </div>

    <!-- ✅ System Breakdown -->
    <?php
   $systemCounts = [];
$q = mysqli_query($con, "
  SELECT system, COUNT(*) as cnt 
  FROM review 
  WHERE studentId='$user_id' 
    AND examTaken='$examTakenMinus' 
  GROUP BY system
");

while($row = mysqli_fetch_assoc($q)){
  $systemCounts[$row['system']] = $row['cnt'];
}

$orderedSystemCounts = $systemCounts; // lahat ng meron sa DB, counted agad

    ?>

    <div class="card mt-4 p-3">
      <h5>Topics Breakdown</h5>

      <?php $hasSystem = array_sum($orderedSystemCounts) > 0; ?>

      <?php if($hasSystem): ?>
        <!-- Graph -->
        <div class="mb-4" style="height:400px;">
          <canvas id="systemChart"></canvas>
        </div>
      <?php endif; ?>

      <!-- Table -->
      <div class="table-responsive">
        <table class="table table-bordered table-striped">
          <thead class="table-dark">
            <tr><th>Topics</th><th>Count</th></tr>
          </thead>
          <tbody>
            <?php foreach($orderedSystemCounts as $system => $count): ?>
              <tr><td><?= htmlspecialchars($system); ?></td><td><?= $count; ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
  $(document).ready(()=> $('#reviewTable').DataTable({ pageLength: 10 }));

  // ✅ Score chart
  new Chart(document.getElementById('scoreChart'), {
    type:'doughnut',
    data:{
      labels:['Correct','Wrong'],
      datasets:[{
        data:[<?= $correctAnswersCount; ?>,<?= $wrongAnswersCount; ?>],
        backgroundColor:['#2E8B57','#DC143C']
      }]
    },
    options:{cutout:'70%',plugins:{legend:{display:false}}}
  });

  <?php if($hasSystem): ?>
  // ✅ System chart
  new Chart(document.getElementById('systemChart').getContext('2d'), {
    type: 'pie',
    data: {
      labels: <?= json_encode(array_keys($orderedSystemCounts)); ?>,
      datasets: [{
        label: 'Questions per System',
        data: <?= json_encode(array_values($orderedSystemCounts)); ?>,
        backgroundColor: [
  '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
  '#9966FF', '#FF9F40', '#C9CBCF', '#E7E9ED',
  '#2ecc71', '#e74c3c', '#3498db', '#f1c40f',
  '#9b59b6', '#1abc9c', '#34495e', '#d35400',
  '#7f8c8d', '#27ae60', '#c0392b', '#2980b9'
]

      }]
    },
    options: {
      responsive:true,
      maintainAspectRatio:false,
      plugins:{legend:{display:false}},
      scales:{
        x:{ticks:{autoSkip:false,maxRotation:90,minRotation:45}},
        y:{beginAtZero:true}
      }
    }
  });
  <?php endif; ?>
</script>
</body>
</html>
