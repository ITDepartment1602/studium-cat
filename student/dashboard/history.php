<?php

include '../../config.php';
session_start();
$user_id = $_SESSION['user_id'];
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
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
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




    <br>


    <!--==================== MAIN ====================-->
    <div class="container mt-5">
        <div class="row">
            <div class="col-12 border p-3 rounded">
                <?php
                // Query to get the exams taken by the user along with scores
                $query = "SELECT examTaken, 
                             MAX(CASE WHEN questionNumber = 150 THEN totalTime ELSE NULL END) AS totalTime, 
                             MAX(CASE WHEN questionNumber = 150 THEN timestamp ELSE NULL END) AS timestamp, 
                             topics1,
                             SUM(CASE WHEN isCorrect = 1 THEN 1 ELSE 0 END) AS correctAnswers, 
                             COUNT(*) AS totalQuestions 
                      FROM review 
                      WHERE studentId = '$user_id' 
                      GROUP BY examTaken, topics1 
                      HAVING COUNT(*) >= 145
                      ORDER BY examTaken ASC";

                $result = mysqli_query($con, $query) or die('query failed');

                $totalExams = 0;
                $totalTime = 0;
                $topicCounts = [];
                $correctAnswersByTopic = [];

                if (mysqli_num_rows($result) > 0) {
                    // Calculate totals and topic counts
                    while ($row = mysqli_fetch_assoc($result)) {
                        $totalExams++;
                        $totalTime += $row['totalTime'];
                        $topic = $row['topics1'];
                        $correctAnswers = $row['correctAnswers'];

                        // Count occurrences of topics
                        if (isset($topicCounts[$topic])) {
                            $topicCounts[$topic]++;
                        } else {
                            $topicCounts[$topic] = 1;
                        }

                        // Sum correct answers for each topic
                        if (isset($correctAnswersByTopic[$topic])) {
                            $correctAnswersByTopic[$topic] += $correctAnswers;
                        } else {
                            $correctAnswersByTopic[$topic] = $correctAnswers;
                        }
                    }

                    // Determine the best topic based on highest correct answers
                    $bestTopic = array_keys($correctAnswersByTopic, max($correctAnswersByTopic))[0];
                } else {
                    $bestTopic = 'N/A'; // No exams taken
                }

                // Display summary boxes
                echo '<div class="row text-center mb-4">';
                echo '<div class="col-md-4">';
                echo '<div class="score-box "><h4>' . $totalExams . '</h4><p>Total Exam Taken</p></div>';
                echo '</div>';
                echo '<div class="col-md-4">';
                $hours = floor($totalTime / 3600);
                $minutes = floor(($totalTime % 3600) / 60);
                $seconds = $totalTime % 60;

                $formattedTime = '';
                if ($hours > 0) {
                    $formattedTime .= $hours . ' hr ';
                }
                if ($minutes > 0) {
                    $formattedTime .= $minutes . ' min ';
                }
                $formattedTime .= $seconds . ' sec';

                echo '<div class="score-box "><h4>' . $formattedTime . '</h4><p>Total Time Spent</p></div>';
                echo '</div>';
                echo '<div class="col-md-4">';
                echo '<div class="score-box "><h4>' . htmlspecialchars($bestTopic) . '</h4><p>Your Best Topic</p></div>';
                echo '</div>';
                echo '</div>';

                // Reset the result pointer to fetch data again for displaying the table
                mysqli_data_seek($result, 0);

                // Table to display exam details
                echo '<table id="reviewTable" class="table table-striped table-bordered">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>Exam Number</th>';
                echo '<th>Date and Time Taken</th>';
                echo '<th>Topic</th>';
                echo '<th>Score</th>';
                echo '<th>Total Time</th>';
                echo '<th>Action</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';

                while ($row = mysqli_fetch_assoc($result)) {
                    $examTaken = $row['examTaken'];
                    $timestamp = $row['timestamp'];
                    $correctAnswers = $row['correctAnswers'];
                    $totalTime = $row['totalTime'];
                    $topic = $row['topics1'];
                    echo '<tr>';
                    echo '<td>Exam #' . ($examTaken + 1) . '</td>';
                    echo '<td>' . date('F d, Y h:i A', strtotime($timestamp)) . '</td>';
                    echo '<td>' . htmlspecialchars($topic) . '</td>';
                    echo '<td>' . $correctAnswers . '/150' . '</td>';
                    $hours = floor($totalTime / 3600);
                    $minutes = floor(($totalTime % 3600) / 60);
                    $seconds = $totalTime % 60;

                    $formattedTime = '';
                    if ($hours > 0) {
                        $formattedTime .= $hours . ' hr ';
                    }
                    if ($minutes > 0) {
                        $formattedTime .= $minutes . ' min ';
                    }
                    $formattedTime .= $seconds . ' sec';

                    echo '<td>' . $formattedTime . '</td>';
                    echo '<td style="text-align: center; "><a style="color: #1B4965;" href="history_details.php?examTaken=' . $examTaken . '&userId=' . $user_id . '" >Open</a></td>';
                    echo '</tr>';
                }

                echo '</tbody>';
                echo '</table>';
                ?>
            </div>
        </div>
    </div>



    <script>
        function showExamDetails(examTaken) {
            $.ajax({
                url: 'get_exam_details.php',
                type: 'POST',
                data: { examTaken: examTaken, userId: <?php echo $user_id; ?> },
                success: function (response) {
                    $('#exam-content').html(response);
                    $('#exam-details').show();
                }
            });
        }
    </script>

    </div>




    <div class="copy"
        style="background-color: #1B4965; height: 30px; position: fixed; bottom: 0; left: 0; right: 0; text-align: center;">
        <center><span style="color:white;">© Studium 2025, All Right Reserved.</span></center>
    </div>







    <div class="scroll" id="btm">
        <button><i class="fa fa-chevron-circle-down fa-3x" aria-hidden="true"></i></button>
    </div>

    <br><br><br>




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