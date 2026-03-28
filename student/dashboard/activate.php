<?php
session_start();
include '../../config.php';
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['pending_user_id'])) {
    header('location: ../');
    exit;
}

$fullname = $_SESSION['pending_fullname'];
$subMonth = $_SESSION['pending_subMonth'];
$userId   = $_SESSION['pending_user_id'];

// Handle activation
if (isset($_POST['activate'])) {
    $dateNow = new DateTime("now", new DateTimeZone('Asia/Manila'));
    $dateNow->modify("+{$subMonth} months");
    $dateExpired = $dateNow->format('Y-m-d H:i:s');

    // Update both dateexpired and set subMonth to NULL
    mysqli_query($con, "
        UPDATE login 
        SET dateexpired = '$dateExpired', subMonth = NULL 
        WHERE id = '$userId'
    ");

    $_SESSION['user_id'] = $userId; // Set as active user
    unset($_SESSION['pending_user_id'], $_SESSION['pending_fullname'], $_SESSION['pending_subMonth']);

    header("location: profile.php");
    exit;
}


// If user clicks Not Now
if (isset($_POST['not_now'])) {
    header("location: ../../");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Studium</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="shortcut icon" type="text/css" href="../../img/logo1.svg">
  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #f8f9fa;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      padding: 1rem;
      height: 100vh;
      width: 100vw;
    }

    /* Floating circles background */
    .bg-circle {
      position: absolute;
      border-radius: 50%;
      background: rgba(27,73,101,0.07);
      animation: float 8s infinite ease-in-out;
      z-index: 0;
    }
    .circle1 { width: 220px; height: 220px; top: -80px; left: -80px; animation-delay: 0s; }
    .circle2 { width: 150px; height: 150px; bottom: -60px; right: -60px; animation-delay: 2s; }
    .circle3 { width: 100px; height: 100px; top: 50%; left: -50px; animation-delay: 4s; }

    @keyframes float {
      0%, 100% { transform: translateY(0) rotate(0deg); }
      50% { transform: translateY(-25px) rotate(180deg); }
    }

    /* Rotating outline ring */
    .rotating-ring {
      position: absolute;
      width: 400px;
      height: 400px;
      border: 2px dashed rgba(27,73,101,0.15);
      border-radius: 50%;
      animation: spin 20s linear infinite;
      z-index: 0;
    }
    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    .activation-card {
      position: relative;
      background: #fff;
      border: 1px solid #e5e5e5;
      border-radius: 1.25rem;
      padding: 2rem;
      max-width: 480px;
      width: 100%;
      box-shadow: 0 8px 25px rgba(27,73,101,0.15);
      animation: fadeInUp 0.6s ease-out;
      z-index: 1;
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .activation-header {
      font-size: 1.8rem;
      font-weight: 700;
      color: #1B4965;
      text-align: center;
      margin-bottom: 1rem;
    }
    .activation-body {
      font-size: 1rem;
      color: #333;
      text-align: center;
      margin-bottom: 1.5rem;
    }
    .activation-body b {
      color: #1B4965;
    }
    .btn-activate {
      background: #1B4965;
      border: none;
      color: #fff;
      font-weight: 600;
      padding: 0.85rem 1.2rem;
      border-radius: 0.75rem;
      transition: 0.3s ease;
    }
    .btn-activate:hover {
      background: #16374d;
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(27,73,101,0.3);
    }
    .btn-notnow {
      background: #fff;
      border: 2px solid #1B4965;
      color: #1B4965;
      font-weight: 600;
      padding: 0.85rem 1.2rem;
      border-radius: 0.75rem;
      transition: 0.3s ease;
    }
    .btn-notnow:hover {
      background: #f0f4f8;
      border: 2px solid #1B4965;
      transform: translateY(-2px);
    }
    .button-group {
      display: flex;
      justify-content: space-between;
      gap: 1rem;
    }
    @media (max-width: 576px) {
      .activation-card {
        padding: 1.5rem;
      }
      .button-group {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
  <!-- Background animations -->
  <div class="bg-circle circle1"></div>
  <div class="bg-circle circle2"></div>
  <div class="bg-circle circle3"></div>
  <div class="rotating-ring"></div>

  <div class="activation-card">
    <div class="activation-header">Activate Subscription</div>
    <div class="activation-body">
      <p>Hello <b><?= htmlspecialchars($fullname) ?></b>!</p>
      <?php $monthLabel = ($subMonth == 1) ? "Month" : "Months"; ?>
      <p>Do you want to activate your <b><?= $subMonth . " " . $monthLabel ?></b> Studium Subscription now?</p>
    </div>

    <form method="post" id="activationForm" class="button-group">
      <button type="button" id="activateBtn" class="btn btn-activate w-100">Activate Now</button>
      <button type="submit" name="not_now" class="btn btn-notnow w-100">❌ Not Now</button>
    </form>
  </div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const activateBtn = document.getElementById('activateBtn');
  const form = document.getElementById('activationForm');

  activateBtn.addEventListener('click', function() {
    Swal.fire({
      title: 'Are you sure?',
      text: "Once we activate your account, your subscription will start today!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#1B4965',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, activate it!'
    }).then((result) => {
      if (result.isConfirmed) {
        // Second confirmation
        Swal.fire({
          title: 'Final Confirmation',
          text: "This action cannot be undone. Do you really want to activate your subscription now?",
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#1B4965',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Yes, I am sure!'
        }).then((finalResult) => {
          if (finalResult.isConfirmed) {
            // Dynamically create hidden input so PHP sees $_POST['activate']
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'activate';
            hiddenInput.value = '1';
            form.appendChild(hiddenInput);

            form.submit();
          }
        });
      }
    });
  });
});
</script>


</body>
</html>
