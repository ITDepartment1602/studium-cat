<?php
include '../../config.php';
session_start();
$user_id = $_SESSION['user_id']; // login.id

// Total Questions
$totalQ = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as total FROM question"))['total'];

// Used Questions (distinct)
$usedQ = mysqli_fetch_assoc(mysqli_query($con, "
    SELECT COUNT(DISTINCT questionId) as used 
    FROM review 
    WHERE studentId = '$user_id'
"))['used'];

// Unused Questions
$unusedQ = $totalQ - $usedQ;

// Correct Answers
$correct = mysqli_fetch_assoc(mysqli_query($con, "
    SELECT COUNT(*) as correct 
    FROM review 
    WHERE studentId = '$user_id' AND ans = correctAns
"))['correct'];

// Wrong Answers
$wrong = mysqli_fetch_assoc(mysqli_query($con, "
    SELECT COUNT(*) as wrong 
    FROM review 
    WHERE studentId = '$user_id' AND ans != correctAns
"))['wrong'];
?>
<!DOCTYPE html>
<html>
<head>
  <title>Dashboard</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 20px;
      background: #f4f6f9;
    }

    .dashboard {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      justify-content: center;
    }

    .card {
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      flex: 1 1 45%;
      min-width: 300px;
      max-width: 600px;
      padding: 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
    }

    .circle-wrapper {
      position: relative;
      width: 160px;
      aspect-ratio: 1;
      flex-shrink: 0;
    }

    canvas {
      width: 100% !important;
      height: 100% !important;
    }

    .circle-text {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: clamp(14px, 2.5vw, 18px);
      font-weight: bold;
      text-align: center;
      pointer-events: none;
    }

    .text-stats {
      flex: 1;
      font-size: clamp(14px, 2vw, 16px);
      line-height: 1.6;
    }

    /* Mobile: stack top-bottom */
    @media (max-width: 768px) {
      .card {
        flex-direction: column;
        text-align: center;
      }
      .text-stats {
        text-align: center;
      }
    }
  </style>
</head>
<body>

<div class="dashboard">
  <!-- Card 1: Questions -->
  <div class="card">
    <div class="circle-wrapper">
      <canvas id="questionsCircle"></canvas>
      <div class="circle-text">Questions</div>
    </div>
    <div class="text-stats">
      <p><b>Total Questions:</b> <?= $totalQ ?></p>
      <p><b>Used Questions:</b> <?= $usedQ ?></p>
      <p><b>Unused Questions:</b> <?= $unusedQ ?></p>
    </div>
  </div>

  <!-- Card 2: Performance -->
  <div class="card">
    <div class="circle-wrapper">
      <canvas id="performanceCircle"></canvas>
      <div class="circle-text">Performance</div>
    </div>
    <div class="text-stats">
      <p><b>Correct Answers:</b> <?= $correct ?></p>
      <p><b>Wrong Answers:</b> <?= $wrong ?></p>
    </div>
  </div>
</div>

<script>
  // Circle 1 (Questions)
  new Chart(document.getElementById('questionsCircle'), {
    type: 'doughnut',
    data: {
      labels: ['Used', 'Unused'],
      datasets: [{
        data: [<?= $usedQ ?>, <?= $unusedQ ?>],
        backgroundColor: ['#4caf50', '#ddd']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '70%',
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
        backgroundColor: ['#2196f3', '#f44336']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '70%',
      plugins: { legend: { display: false } }
    }
  });
</script>

</body>
</html>
