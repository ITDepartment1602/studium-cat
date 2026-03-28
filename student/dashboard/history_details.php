<?php

include '../../config.php';
session_start();
$user_id = $_SESSION['user_id'];

// Fetch user information
$select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'") or die('query failed');
if (mysqli_num_rows($select) > 0) {
    $fetch = mysqli_fetch_assoc($select);
}

// Count the number of correct answers
$examTaken = $_GET['examTaken'];
$correctAnswersQuery = mysqli_query($con, "SELECT COUNT(*) as correctCount FROM `review` WHERE studentId = '$user_id' AND isCorrect = 1 AND examTaken = '$examTaken'") or die('query failed');
$correctAnswersCount = mysqli_fetch_assoc($correctAnswersQuery)['correctCount'] ?? 0;

// Count the number of wrong answers
$examTaken = $_GET['examTaken'];
$wrongAnswersQuery = mysqli_query($con, "SELECT COUNT(*) as wrongCount FROM `review` WHERE studentId = '$user_id' AND isCorrect = 0 AND examTaken = '$examTaken'") or die('query failed');
$wrongAnswersCount = mysqli_fetch_assoc($wrongAnswersQuery)['wrongCount'] ?? 0;

$totalTimeQuery = mysqli_query($con, "SELECT totalTime FROM `review` WHERE studentId = '$user_id' AND examTaken = '$examTaken' AND questionNumber = 150") or die('query failed');
$totalTime = mysqli_fetch_assoc($totalTimeQuery)['totalTime'] ?? 0;
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
</style>

<body>

    <!-- top navigation bar -->
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
                                    <a href="../../img/userguide.mp4" target="_blank" rel="noopener noreferrer"
                                        id="myVideo" class="nav-link">
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
                                    <a href="https://www.facebook.com/NCLEX.Amplified.Official" target="_blank"
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
                        <a href="https://www.facebook.com/NCLEX.Amplified.Official" target="_blank">
                            Upgrade Now!
                        </a>
                    </div>
                </ul>
            </nav>
        </div>
    </div>
    <!-- offcanvas -->

    <main class="mt-5 pt-4">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="card-body">


                        <br>
                        <h4 style="color:#0A2558">Test Results</h4>
                        <br>
                        <?php
                        include '../../config.php';
                        if (!(isset($_SESSION['user_id']))) {
                            header("location:index.php");

                        } else {
                            $email = $_SESSION['user_id'];
                        } ?>


                        <div class="container">
                            <div class="box-container">
                                <div class="box">
                                    <br>
                                    <table class="table table-striped title1"
                                        style="font-size:25px;font-weight:500; margin-top: -30px">

                                    </table>
                                    <h1 class="title" style="color: #1B4965; font-size: 20px; font-weight:500;">Points
                                        scored</h1>
                                    </center>
                                    <?php
                                    $averageScore = ($correctAnswersCount / 150) * 100;
                                    ?>


                                    <div class="titi" style="width:85%; background-color:#ddd; border-radius: 50px">
                                        <div class="skills html"
                                            style="width: <?php echo round($averageScore, 2); ?>%; background-color: #38B6FF; border-radius: 50px 50px 50px 50px; text-align: center">
                                            <?php echo round($averageScore, 2); ?>%
                                        </div>
                                    </div>



                                </div>
                                <div class="box">

                                    <br>
                                    <h1 class="title" style="color:#0A2558; font-size: 20px; margin-top: -20px">Result
                                    </h1><br />
                                    <table class="table" style="font-size:15px; margin-top: -30px;">


                                        <tr style="color:#000">
                                            <td>Total Questions:</td>
                                            <td>150</td>
                                        </tr>

                                        <tr style="color:#000">
                                            <td>Right Answer:&nbsp;<span class="glyphicon glyphicon-ok-circle"
                                                    aria-hidden="true"></span></td>
                                            <td><?php echo $correctAnswersCount; ?></td>
                                        </tr>
                                        <tr style="color:#000">
                                            <td>Wrong Answer:&nbsp;<span class="glyphicon glyphicon-remove-circle"
                                                    aria-hidden="true"></span>
                                            </td>
                                            <td><?php echo $wrongAnswersCount; ?></td>
                                        </tr>
                                        <tr style="color:#000">
                                            <td>Total Time Taken:&nbsp;<span class="glyphicon glyphicon-remove-circle"
                                                    aria-hidden="true"></span>
                                            </td>
                                            <td>
                                                <?php
                                                $hours = floor($totalTime / 3600);
                                                $minutes = floor(($totalTime % 3600) / 60);
                                                $seconds = $totalTime % 60;

                                                $timeString = '';
                                                if ($hours > 0) {
                                                    $timeString .= sprintf('%02d hr ', $hours);
                                                }
                                                if ($minutes > 0) {
                                                    $timeString .= sprintf('%02d min ', $minutes);
                                                }
                                                $timeString .= sprintf('%02d sec', $seconds);

                                                echo $timeString;
                                                ?>
                                            </td>
                                        </tr>
                                    </table>

                                    <br>


                                    </table>

                                </div>

                            </div>
                        </div>


                    </div>
                </div>
            </div>
        </div>
        <div style="margin-right: 100px; overflow-x: auto;  ">
            <style>
                @media (max-width: 992px) {
                    div[style*="margin-right: 100px;"] {
                        margin-right: 0 !important;

                    }
                }
            </style>
            <table id="reviewTable" class="table " style="overflow: hidden;">
                <thead>

                    <tr>
                        <th>Question #</th>
                        <th>ID</th>
                        <th>Topics</th>
                        <th>System</th>
                        <th>Client Needs</th>
                        <th>Time Taken</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Update the query to include 'timeTaken' instead of 'time'
                    $examTaken = $_GET['examTaken'];
                    $reviewQuery = mysqli_query($con, "SELECT isCorrect, ans, questionNumber,   questionId, topics1, system, cnc, timeTaken, ans, correctAns FROM `review` WHERE studentId = '$user_id' AND examTaken = '$examTaken'") or die('query failed');
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
                        echo "<td>" . ($row['isCorrect'] ? '<span style="color: green;">✔</span>' : '<span style="color: red;">✗</span>') . "</td>";
                        echo "<td><a href='rationale/qpages.php?isCorrect=" . $row['isCorrect'] . "&questionId=" . $row['questionId'] . "&topics1=" . $row['topics1'] . "&system=" . $row['system'] . "&cnc=" . $row['cnc'] . "&timeTaken=" . $row['timeTaken'] . "&ans=" . $row['ans'] . "&correctAns=" . $row['correctAns'] . "&questionNumber=" . $row['questionNumber'] .  "' target='_blank' class='' style='color: #1B4965; text-decoration: underline;'>View</a></td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="copy"
            style="background-color: #1B4965; height: 30px; position: fixed; bottom: 0; left: 0; right: 0; text-align: center;">
            <center><span style="color:white;">© Studium 2025, All Right Reserved.</span></center>
        </div>

    </main>



    <br><br><br><br><br><br><br><br><br><br><br><br><br>



    <script src="../ty/./js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.0.2/dist/chart.min.js"></script>


    <script src="../ty/./js/script.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js" charset="utf-8"></script>

    <!--=============== MAIN JS ===============-->
    <script src="assets/js/main.js"></script>

    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#reviewTable').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true
            });
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
</body>

</html>