<?php
include '../../config.php';

session_start();
$user_id = $_SESSION['user_id'];

// Fetch user data
$select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'") or die('query failed');
if (mysqli_num_rows($select) > 0) {
    $fetch = mysqli_fetch_assoc($select);
}

// Check if the user has a review for discount
$hasReview = mysqli_num_rows(mysqli_query($con, "SELECT * FROM studentReviews WHERE studentId = '$user_id'")) > 0;

// Define original prices
$basicPlanPrice = 2999.00;
$proPlanPrice = 3499.00;
$enterprisePlanPrice = 4499.00;
$ultimatePlanPrice = 5999.00;

// Calculate discounted prices if eligible
if ($hasReview) {
    $basicPlanPrice *= 0.80; // 20% off
    $proPlanPrice *= 0.80; // 20% off
    $enterprisePlanPrice *= 0.80; // 20% off
    $ultimatePlanPrice *= 0.80; // 20% off
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
    <link rel="stylesheet" href="css/style1.css">
    <link rel="stylesheet" href="../pchart/pchart.css">
    <link rel="stylesheet" href="../pricing/moda.css">
    <link rel="stylesheet" href="../pricing/exam.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Studium</title>
    <link rel="shortcut icon" type="text/css" href="../../img/logo1.svg">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;

        }

        body {


            background-image: url('../../img/subsriptionbg.png');
            /* Replace with your image path */
            background-size: cover;
            /* Cover the entire viewport */
            background-position: center;
            /* Center the image */
            background-repeat: no-repeat;
            /* Prevent image repetition */
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        .navbar {
            background: white;
            padding: 1rem 3rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .navbar-brand {
            color: black;
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
            margin-left: 80px;
        }

        .home-btn {
            position: absolute;
            right: 40px;
            top: 50%;
            transform: translateY(-50%);
            background: #1B4965;
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .home-btn:hover {
            background: #62B6CB;
            transform: translateY(calc(-50% - 2px));
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.2);
        }

        .home-btn i {
            margin-right: 8px;
        }

        .logo-container {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
        }

        .logo-container img {
            margin-top: 15px;
            margin-left: -10px;
            max-width: 13%;
        }

        .main-content {
            flex: 1;
            padding: 0.5rem;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: auto;
        }

        .pricing-container {
            width: 100%;
            max-width: 1800px;
            margin: auto;
            text-align: center;
            padding: 0.5rem;
            margin-top: 80px;
        }

        h1 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            font-size: 2.5em;
            font-weight: bold;
            text-align: center;
            padding: 0 20px;
            position: relative;
            display: inline-block;
            background: linear-gradient(120deg, rgb(193, 228, 250) 0%, rgb(204, 240, 250) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1::after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, #1B4965, #62B6CB);
            border-radius: 2px;
        }

        @media screen and (max-width: 480px) {
            h1 {
                font-size: 1.8rem;
                margin-bottom: 1.2rem;
            }

            h1::after {
                width: 80px;
                height: 2px;
                bottom: -8px;
            }
        }

        @media screen and (max-width: 375px) {
            h1 {
                font-size: 1.6rem;
                margin-bottom: 1rem;
            }

            h1::after {
                width: 60px;
            }

            .logo-container {
                position: absolute;
                left: 20px;
                top: 50%;
                transform: translateY(-50%);
            }

            .logo-container img {
                margin-top: 15px;
                margin-left: -10px;
                max-width: 13%;
            }
        }

        .toggle-container {
            margin-bottom: 1rem;
            color: #2c3e50;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        main {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Main grid layout for pricing cards */
        .pricing-grid {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-direction: row;
            flex-wrap: nowrap;
            margin: 0 -10px;
        }

        /* Individual pricing card dimensions and styling */
        .pricing-card {
            flex: 1;
            min-width: 250px;
            max-width: 250px;
            min-height: 250px;
            background: white;
            border-radius: 15px;
            padding: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .pricing-card:hover {
            transform: translateY(-10px);
        }

        .popular {
            border: 3px solid #1B4965;
        }

        .popular-badge {
            position: absolute;
            top: 15px;
            right: -30px;
            background: #1B4965;
            color: white;
            padding: 6px 30px;
            transform: rotate(45deg);
            font-size: 0.75em;
        }

        .card-header {
            margin-bottom: 0.5rem;
        }

        .card-header h3 {
            font-size: 1.4rem;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .price {
            margin-bottom: 0.5rem;
        }

        .price h2 {
            font-size: 2.2em;
            color: #2c3e50;
            display: inline-block;
        }

        .features {
            flex: 1;
            overflow-y: auto;
            list-style: none;
            padding: 0 10px;
            margin-bottom: 1rem;
        }

        .pricing-card li {
            margin: 8px 0;
            font-size: 1rem;
            line-height: 1.4;
            text-align: left;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .feature-text {
            flex: 1;
            margin-right: 10px;
            user-select: none;
            filter: blur(5px);
            color: #2c3e50;
        }

        .feature-text b {
            color: #1B4965;
            font-weight: 600;
        }

        .feature-switch {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .feature-switch::before {
            content: "✓";
            color: #1B4965;
            font-weight: bold;
        }

        .btn {
            background: #1B4965;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 30px;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 1px;
            margin-top: 1rem;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background: #62B6CB;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(33, 150, 243, 0.3);
        }

        .footer {
            background: white;
            padding: 0.8rem;
            text-align: center;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            position: relative;
            margin-top: 1rem;
        }

        .footer-links {
            margin: 0.5rem 0;
        }

        .footer-links a {
            color: #1B4965;
            text-decoration: none;
            margin: 0 15px;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #62B6CB;
        }

        .footer p {
            color: #666;
        }

        /* Switch styles */
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
            margin: 0 10px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(145deg, #62B6CB, #1B4965);
            transition: .4s;
            border-radius: 30px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1),
                0 2px 4px rgba(33, 150, 243, 0.2);
        }

        .slider:before {
            position: absolute;
            content: "$";
            display: flex;
            align-items: center;
            justify-content: center;
            height: 24px;
            width: 24px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
            font-size: 14px;
            color: #1B4965;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        input:checked+.slider {
            background: linear-gradient(145deg, #62B6CB, #1B4965);
        }

        input:checked+.slider:before {
            transform: translateX(30px);
            content: "₱";
        }

        .slider:hover {
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1),
                0 4px 8px rgba(33, 150, 243, 0.3);
        }

        .save-text {
            background: linear-gradient(145deg, #43A047, #4CAF50);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(76, 175, 80, 0.2);
        }

        /* Media Queries */
        @media screen and (max-width: 1200px) {
            body {
                height: auto;
                overflow-y: auto;
            }

            .main-content {
                padding: 1rem;
                min-height: auto;

            }

            .pricing-grid {
                flex-direction: column;
                align-items: center;
                gap: 20px;
                margin: 0;
                padding: 10px;
            }

            .pricing-card {
                width: 90%;
                max-width: 400px;
                margin: 0 auto 20px;
                min-height: auto;
                padding: 20px;
            }

            .features {
                height: auto;
                overflow: visible;
            }

            .toggle-container {
                margin: 1rem 0;
            }

            .switch {
                transform: scale(0.9);
            }
        }

        @media screen and (max-width: 768px) {
            .navbar {
                padding: 1rem 2rem;
            }

            .home-btn {
                right: 20px;
                padding: 6px 15px;
                font-size: 0.9rem;
            }

            .logo-container {
                left: 15px;
            }

            .logo-container img {
                margin-top: 10px;
                margin-left: -5px;
                max-width: 15%;
            }

            .navbar-brand {
                margin-left: 70px;
                font-size: 1.3rem;
            }
        }

        @media screen and (max-width: 480px) {
            .navbar {
                padding: 0.8rem 1rem;
            }

            .home-btn {
                right: 15px;
                padding: 5px 12px;
                font-size: 0.60rem;
            }

            .home-btn i {
                margin-right: 5px;
            }

            .logo-container {
                left: 10px;
            }

            .logo-container img {
                margin-top: 8px;
                margin-left: -3px;
                max-width: 18%;
            }

            .navbar-brand {
                margin-left: 60px;
                font-size: 1.2rem;
            }
        }

        @media screen and (max-width: 375px) {
            .navbar {
                padding: 0.8rem;
            }

            .home-btn {
                right: 8px;
                padding: 4px 10px;
                font-size: 0.8rem;
            }

            .logo-container {
                left: 8px;
            }

            .logo-container img {
                margin-top: 6px;
                margin-left: -2px;
                max-width: 20%;
            }

            .navbar-brand {
                margin-left: 55px;
                font-size: 1.1rem;
            }
        }

        /* Ensure proper display on iOS devices */
        @supports (-webkit-touch-callout: none) {
            body {
                min-height: -webkit-fill-available;
            }
        }


        .nurse a:hover,
        .product a:hover {
            color: black;
            text-decoration: none;
        }

        label {
            display: inline-block;
            padding: 5px 10px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <!-- Top Navigation Bar -->
    <!-- top navigation bar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top"
        style="background-color:#1B4965; position: fixed; height: 67px;">
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
                                <img src="../../img/logo2.svg"
                                    style="width:100px; margin-left: 50px; margin-bottom: 50px;">

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
                                    <a href="index.php?bundle_name=<?php echo $fetch['bundle_name']; ?>" id="myVideo"
                                        class="nav-link">
                                        <p style="font-size: 14px;"> <i class="bi bi-house"
                                                style="font-size: 17px;"></i>
                                            Home ></p>
                                    </a>
                                </td>
                            </tr>

                            <tr>
                                <td class="nav-link px-1">
                                </td>
                                <td>
                                    <a href="profile.php" id="myVideo" class="nav-link">
                                        <p style="font-size: 14px;"><i class="bi bi-person-square"
                                                style="font-size: 17px;"></i> View
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
                                        <p style="font-size: 14px;"><i class="bi bi-journal"
                                                style="font-size: 17px;"></i>
                                            My Notes ></p>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td class="nav-link px-1">
                                </td>
                                <td>
                                    <a href="" id="myVideo" class="nav-link">
                                        <p style="font-size: 14px;"><i class="bi bi-question-circle"
                                                style="font-size: 17px;"></i></i> User
                                            Guide ></p>
                                    </a>
                                </td>
                            </tr>


                            <tr>
                                <td class="nav-link px-1">
                                </td>
                                <td>
                                    <a href="subscription.php" id="myVideo" class="nav-link">
                                        <p style="font-size: 14px;"><i class="bi bi-calendar-check"
                                                style="font-size: 17px;"></i>
                                            Subscription ></p>
                                    </a>
                                </td>
                            </tr>

                            <tr>
                                <td class="nav-link px-1">
                                </td>
                                <td>
                                    <a href="package.php" id="myVideo" class="nav-link">
                                        <p style="font-size: 14px;"><i class="bi bi-box-seam"
                                                style="font-size: 17px;"></i>
                                            Package >
                                        </p>
                                    </a>
                                </td>
                            </tr>




                            <tr>
                                <td class="nav-link px-1">
                                </td>
                                <td>
                                    <a href="https://www.facebook.com/NCLEX.Amplified.Technical" target="_blank"
                                        id="myVideo" class="nav-link">
                                        <p style="font-size: 14px;"><i class="bi bi-telephone"
                                                style="font-size: 17px;"></i>
                                            Contact Us
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
                        <a href="https://www.facebook.com/NCLEXAmplifiedReviewCenter" target="_blank">
                            Upgrade Now!
                        </a>
                    </div>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <main style="margin-top: 4%; margin-bottom: 4%;">
        <div class="main-content">
            <div class="pricing-container">
                <h1>Select Your Studium Success Path</h1>

                <div class="pricing-grid" style="margin-top: 40px;">
            <!-- BASIC PLAN CARD -->
<div class="pricing-card shadow-sm rounded-4 p-4 text-center border">
    <div class="card-header border-0 bg-transparent mb-3">
        <h4 class="fw-bold text-primary mb-3">Basic Plan</h4>

        <div class="price">
            <?php if ($hasReview): ?>
                <!-- Original Price -->
                <h5 style="font-size:1.1em; color:gray; text-decoration: line-through;">
                    ₱<?php echo number_format($basicPlanPrice / 0.8, 2); ?>
                </h5>

                <!-- Discounted Price -->
                <h2 style="color:#1B4965; font-size:2.2em; font-weight:bold;">
                    ₱<?php echo number_format($basicPlanPrice, 2); ?>
                </h2>

                <!-- Discount Badge -->
                <span class="badge bg-success" style="font-size:0.9em;">20% OFF</span>
            <?php else: ?>
                <!-- Regular Price -->
                <h2 style="color:#2c3e50; font-weight:bold;">
                    ₱<?php echo number_format($basicPlanPrice, 2); ?>
                </h2>
            <?php endif; ?>
        </div>

        <p class="text-muted mb-2">/ 1 month</p>
    </div>

   
       
        <a href="https://www.facebook.com/NCLEXAmplifiedReviewCenter"
           target="_blank"
           class="btn btn-primary rounded-pill w-100 fw-semibold">
           ENROLL NOW
        </a>
   
</div>
<!-- PRO PLAN -->
<div class="pricing-card shadow-sm rounded-4 p-4 text-center border">
  <div class="card-header border-0 bg-transparent mb-3">
    <h4 class="fw-bold text-primary mb-3">Pro Plan</h4>

    <div class="price">
      <?php if ($hasReview): ?>
        <h5 style="font-size:1.1em; color:gray; text-decoration: line-through;">
          ₱<?php echo number_format($proPlanPrice / 0.8, 2); ?>
        </h5>
        <h2 style="color:#1B4965; font-size:2.2em; font-weight:bold;">
          ₱<?php echo number_format($proPlanPrice, 2); ?>
        </h2>
        <span class="badge bg-success" style="font-size:0.9em;">20% OFF</span>
      <?php else: ?>
        <h2 style="color:#2c3e50; font-weight:bold;">
          ₱<?php echo number_format($proPlanPrice, 2); ?>
        </h2>
      <?php endif; ?>
    </div>

    <p class="text-muted mb-2">/ 3 months</p>
  </div>


    
    <a href="https://www.facebook.com/NCLEXAmplifiedReviewCenter"
       target="_blank"
       class="btn btn-primary rounded-pill w-100 fw-semibold">
       ENROLL NOW
    </a>

</div>


<!-- ENTERPRISE PLAN -->
<div class="pricing-card shadow-sm rounded-4 p-4 text-center border">
  <div class="card-header border-0 bg-transparent mb-3">
    <h5 class="fw-bold text-primary mb-3">Enterprise Plan</h5>

    <div class="price">
      <?php if ($hasReview): ?>
        <h5 style="font-size:1.1em; color:gray; text-decoration: line-through;">
          ₱<?php echo number_format($enterprisePlanPrice / 0.8, 2); ?>
        </h5>
        <h2 style="color:#1B4965; font-size:2.2em; font-weight:bold;">
          ₱<?php echo number_format($enterprisePlanPrice, 2); ?>
        </h2>
        <span class="badge bg-success" style="font-size:0.9em;">20% OFF</span>
      <?php else: ?>
        <h2 style="color:#2c3e50; font-weight:bold;">
          ₱<?php echo number_format($enterprisePlanPrice, 2); ?>
        </h2>
      <?php endif; ?>
    </div>

    <p class="text-muted mb-2">/ 6 months</p>
  </div>

  
    <a href="https://www.facebook.com/NCLEXAmplifiedReviewCenter"
       target="_blank"
       class="btn btn-primary rounded-pill w-100 fw-semibold">
       ENROLL NOW
    </a>

</div>


<!-- ULTIMATE PLAN -->
<div class="pricing-card shadow-sm rounded-4 p-4 text-center border">
  <div class="card-header border-0 bg-transparent mb-3">
    <h4 class="fw-bold text-primary mb-3">Ultimate Plan</h4>

    <div class="price">
      <?php if ($hasReview): ?>
        <h5 style="font-size:1.1em; color:gray; text-decoration: line-through;">
          ₱<?php echo number_format($ultimatePlanPrice / 0.8, 2); ?>
        </h5>
        <h2 style="color:#1B4965; font-size:2.2em; font-weight:bold;">
          ₱<?php echo number_format($ultimatePlanPrice, 2); ?>
        </h2>
        <span class="badge bg-success" style="font-size:0.9em;">20% OFF</span>
      <?php else: ?>
        <h2 style="color:#2c3e50; font-weight:bold;">
          ₱<?php echo number_format($ultimatePlanPrice, 2); ?>
        </h2>
      <?php endif; ?>
    </div>

    <p class="text-muted mb-2">/ 12 months</p>
  </div>


   
    <a href="https://www.facebook.com/NCLEXAmplifiedReviewCenter"
       target="_blank"
       class="btn btn-primary rounded-pill w-100 fw-semibold">
       ENROLL NOW
    </a>

</div>
                </div>
            </div>
        </div>

        <!-- Footer -->


    </main>

    <div class="copy"
        style="background-color: #1B4965; height: 30px; position: fixed; bottom: 0; left: 0; right: 0; text-align: center;">
        <center><span style="color:white;">© Studium 2025, All Right Reserved.</span></center>
    </div>

    <script src="../ty/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.0.2/dist/chart.min.js"></script>
    <script src="../ty/js/jquery-3.5.1.js"></script>
    <script src="../ty/js/jquery.dataTables.min.js"></script>
    <script src="../ty/js/dataTables.bootstrap5.min.js"></script>
    <script src="../ty/js/script.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js" charset="utf-8"></script>
    <script src="assets/js/main.js"></script>



</body>

</html>