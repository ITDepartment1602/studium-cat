<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../'); // Redirect to login page
    exit();
}

include('../../config.php');

// Check if student_id is set in the URL
if (isset($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']); // Convert to integer for security

    // Modify the query to filter by student_id
    $query = "SELECT * FROM `history` LEFT JOIN login ON history.email = login.id WHERE login.id = $student_id";
} else {
    header('Location: ../manage_result.php'); // Adjust as necessary
    exit();
}

// Define categories
$categories = [
    'Adult Health',
    'Child Health',
    'Critical Care',
    'Fundamentals',
    'Leadership and Management',
    'Maternal and Newborn Health',
    'Mental Health',
    'Pharmacology'
];

// Function to get average score for each category
function getAverageScore($con, $eid, $student_id)
{
    $query = "SELECT AVG(score) AS average FROM history WHERE eid = '$eid' AND email = '$student_id'";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);
    return round($row['average'], 2); // Round to 2 decimal places
}
?>

<!DOCTYPE html>
<!-- Website - www.codingnepalweb.com -->
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8" />
    <title>Studium Admin</title>
    <link rel="shortcut icon" type="text/css" href="../../img/logo1.svg">
    <link rel="stylesheet" href="../adminstyles.css" />
    <!-- Boxicons CDN Link -->
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../../table css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"
        integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head>
<style type="text/css">
    /* Full-width input fields */
    input[type=text],
    input[type=number],
    input[type=email] {
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
        display: none;
        /* Hidden by default */
        position: fixed;
        /* Stay in place */
        z-index: 1;
        /* Sit on top */
        left: 0;
        top: 0;
        width: 100%;
        /* Full width */
        height: 100%;
        /* Full height */
        overflow: auto;
        /* Enable scroll if needed */
        background-color: rgb(0, 0, 0);
        /* Fallback color */
        background-color: rgba(0, 0, 0, 0.4);
        /* Black w/ opacity */
        padding-top: 60px;
    }

    /* Modal Content/Box */
    .modal-content {
        background-color: #fefefe;
        margin: 5% auto 15% auto;
        /* 5% from the top, 15% from the bottom and centered */
        border: 1px solid #888;
        width: 30%;
        /* Could be more or less, depending on screen size */
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
        from {
            -webkit-transform: scale(0)
        }

        to {
            -webkit-transform: scale(1)
        }
    }

    @keyframes animatezoom {
        from {
            transform: scale(0)
        }

        to {
            transform: scale(1)
        }
    }

    /* Change styles for span and cancel button on extra small screens */
    @media screen and (max-width: 300px) {
        .cancelbtn {
            width: 100%;
        }
    }


    .navbar,
    .card-header {
        background-color: #1B4965;
        color: white;
        /* Change text color to white for better contrast */
    }
</style>

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
                <a href="../manage topics">
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
                    <?php
                    $query = "SELECT history.*, login.fullname FROM `history` LEFT JOIN login ON history.email = login.id WHERE login.id = $student_id";

                    $data = mysqli_query($con, $query);
                    $student_name = 'fullname';
                    if ($rows = mysqli_fetch_array($data)) {
                        $student_name = $rows['fullname']; // Get the first word of fullname
                        mysqli_data_seek($data, 0); // Reset pointer for further use
                    }
                    ?>

                    <div class="title"><?php echo $student_name; ?>'s Results</div>


                    <div class="row">
                        <?php foreach ($categories as $category): ?>
                            <div class="col-md-3">
                                <div class="card text-center mb-4">
                                    <div class="card-header">
                                        <strong><?php echo $category; ?></strong>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title" style="font-size: 2rem;">
                                         <?php $averageScore = getAverageScore($con, $category, $student_id); ?>
                                            <?php echo $averageScore > 0 ? $averageScore : '0'; ?>%
                                        </h5>
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar"
                                                style="width: <?php echo $averageScore; ?>%; background-color: #1B4965;"
                                                aria-valuenow="<?php echo $averageScore; ?>" aria-valuemin="0"
                                                aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <table class="table table-striped data-table">
                        <thead>
                            <tr>
                                <th>Score</th>
                                <th>Correct Answer</th>
                                <th>Wrong Answer</th>
                                <th>Date Test</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $data = mysqli_query($con, $query);
                            while ($rows = mysqli_fetch_array($data)) {
                                ?>
                                <tr>
                                    <td><?php echo $rows['score']; ?>%</td>
                                    <td><?php echo $rows['sahi']; ?></td>
                                    <td><?php echo $rows['wrong']; ?></td>
                                    <td>
                                        <?php
                                        $date = strtotime($rows['date']); // Convert date string to timestamp
                                        echo date('F j, Y - h:i A', $date); // Format the date
                                        ?>
                                    </td>
                                </tr>
                            <?php } ?>
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