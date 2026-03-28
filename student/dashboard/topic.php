<?php
include '../../config.php';

session_start();
$user_id = $_SESSION['user_id'];

// Fetch user data
$select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'") or die('query failed');
if (mysqli_num_rows($select) > 0) {
    $fetch = mysqli_fetch_assoc($select);
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

    <!-- Main Content -->
    <main class="mt-5 pt-4">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="card-body">
                        <h4 style="color:#0A2558;">Question Type</h4>
                        <p style="font-size: 15px"><i>(Please select Concepts and Topics)</i></p>
                        <br>
                        <div class="container">
                            <div class="box">
                                <p style="color:#0A2558"><b>Test Mode</b></p>

                                <label>
                                    <input type="radio" name="test_mode" value="Study Mode" required>
                                    <span>Study Mode</span>
                                </label>
                                <a href="#"
                                    style="text-decoration: none; user-select: none; color: #767676; font-size: 15px; pointer-events: none;">
                                    <i class="fa fa-circle-thin" aria-hidden="true"></i>
                                    <span style="color: black; font-size: 16px "> <span
                                            style="text-decoration: line-through;">Exam Mode</span>(Soon)</span>
                                </a>
                            </div>
                            <hr>
                            <form action="question/question1.php" method='GET'>
                                <p style="color:#0A2558"><b>Concepts</b></p>
                                <div class="row">
                                    <?php
                                    $kilanlan = $_GET['kilanlan'];

                                    $q = "SELECT * FROM topics1 WHERE kilanlan = '$kilanlan'";
                                    $query = mysqli_query($con, $q);
                                    $concepts = [];

                                    while ($row = mysqli_fetch_array($query)) {
                                        $concepts[] = $row['title'];
                                    }

                                    $totalConcepts = count($concepts);
                                    $conceptsPerColumn = ceil($totalConcepts / 4);

                                    for ($i = 0; $i < 4; $i++) {
                                        echo '<div class="col-md-3">';
                                        for ($j = 0; $j < $conceptsPerColumn; $j++) {
                                            $index = $i * $conceptsPerColumn + $j;
                                            if ($index < $totalConcepts) {
                                                echo "<div class='box'>
                                                    <label>
                                                        <input type='radio' name='topics' value='{$concepts[$index]}' class='conceptCheckbox' required disabled>
                                                        <span>{$concepts[$index]}</span>
                                                    </label>
                                                </div>";
                                            }
                                        }
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                                <hr>
                                <p style="color:#0A2558"><b>Topics</b></p>
                                <div class="mb-2 " style="display: none;">
                                    <label>
                                        <input  type="checkbox" id="selectAllTopics" disabled> Select All Topics
                                        <i class="fa fa-info-circle"
                                            style="color: #5598C6; cursor: help; position: relative; top: 1px;"
                                            data-bs-toggle="tooltip" data-bs-placement="right"
                                            title="Select all options. The test will contain 150 random questions"></i>
                                    </label>
                                </div>
                                <p style="font-size: 12px; font-style: italic; color: #6B7280; margin: 0;">
  To prevent any errors, all topics have been automatically selected. Your test will include 150 random questions.
</p>


                                <div id="topicsContainer" class="row">
                                    <?php
                                    $allTopicsQuery = mysqli_query($con, "SELECT DISTINCT `system`, `topics1` FROM `question`");
                                    $topics = [];

                                    if (mysqli_num_rows($allTopicsQuery) > 0) {
                                        while ($system = mysqli_fetch_assoc($allTopicsQuery)) {
                                            $normalizedSystemName = strtolower(trim($system['system']));
                                            if (!in_array($normalizedSystemName, $topics)) {
                                                $topics[] = $normalizedSystemName;
                                            }
                                        }
                                    }

                                    if (!empty($topics)) {
                                        $topicsPerColumn = 3;
                                        $columnCount = 0;

                                        echo '<div class="col-md-3 d-flex flex-column">';

                                        foreach ($topics as $topic) {
                                            echo "<label class='flex-grow-1'>
                                                <input type='checkbox' class='topicCheckbox' value='{$topic}' data-count='0' disabled> 
                                                " . ucfirst($topic) . " 
                                              </label>";

                                            $columnCount++;

                                            if ($columnCount % $topicsPerColumn == 0) {
                                                echo '</div>';
                                                if ($columnCount < count($topics)) {
                                                    echo '<div class="col-md-3 d-flex flex-column">';
                                                }
                                            }
                                        }

                                        if ($columnCount % $topicsPerColumn != 0) {
                                            echo '</div>';
                                        }
                                    } else {
                                        echo "No topics found.";
                                    }
                                    ?>
                                </div>
                                <button class="btn" style="background: #1B4965; color: white; float: right;"
                                    id="startTestButton">Start Test</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const studyModeRadio = document.querySelector('input[name="test_mode"][value="Study Mode"]');
            const conceptRadios = document.querySelectorAll('.conceptCheckbox');
            const selectAllTopics = document.getElementById('selectAllTopics');

            studyModeRadio.addEventListener('change', function () {
                if (this.checked) {
                    conceptRadios.forEach(radio => radio.disabled = false);
                } else {
                    conceptRadios.forEach(radio => {
                        radio.disabled = true;
                        radio.checked = false;
                    });
                    selectAllTopics.disabled = true;
                    selectAllTopics.checked = false;
                }
            });

            conceptRadios.forEach(radio => {
                radio.addEventListener('change', function () {
                    const studyModeChecked = studyModeRadio.checked;

                    if (this.checked && !studyModeChecked) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Please select a Test Mode!',
                        });
                    }

                    selectAllTopics.checked = false;

                    if (this.checked) {
                        fetch(`get_topics.php?topics1=${this.value}`)
                            .then(response => response.text())
                            .then(html => {
                                document.getElementById('topicsContainer').innerHTML = html;
                                selectAllTopics.disabled = false;
                                initializeTopicCheckboxes();
                            });
                    } else {
                        selectAllTopics.disabled = true;
                        selectAllTopics.checked = false;
                    }
                });
            });

          function initializeTopicCheckboxes() {
    const checkboxes = document.querySelectorAll('.topicCheckbox');
    const selectAllCheckbox = document.getElementById('selectAllTopics');

    // ✅ Auto-check topics that have data-count > 0
    checkboxes.forEach(checkbox => {
        const count = parseInt(checkbox.getAttribute('data-count') || 0);
        if (count > 0) {
            checkbox.checked = true;
        }

        // 🔒 Prevent unchecking once checked
        checkbox.addEventListener('click', function (e) {
            if (this.checked) return; // allow default if it was unchecked
            e.preventDefault(); // block uncheck action
            this.checked = true; // keep it checked
        });
    });

    // Optional: disable "Select All" since all are already checked
    selectAllCheckbox.disabled = true;
    selectAllCheckbox.checked = true;

    updateTotalCount();
}



            function updateTotalCount() {
                let totalCount = 0;
                const checkedCheckboxes = document.querySelectorAll('.topicCheckbox:checked');
                checkedCheckboxes.forEach(checkbox => {
                    totalCount += parseInt(checkbox.getAttribute('data-count'));
                });
            }

            initializeTopicCheckboxes();
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Initialize all tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            document.getElementById('startTestButton').addEventListener('click', function (event) {
                event.preventDefault();
                const conceptSelected = document.querySelector('.conceptCheckbox:checked');
                const topicsSelected = document.querySelectorAll('#topicsContainer .topicCheckbox:checked');
                const studyModeChecked = document.querySelector('input[name="test_mode"]:checked');
                const sumOfTopicCounts = Array.from(topicsSelected).reduce((total, topic) => total + parseInt(topic.getAttribute('data-count')), 0);

                if (!studyModeChecked) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Please select a Test Mode!',
                    });
                } else if (!conceptSelected) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Please select a Concept!',
                    });
                } else if (topicsSelected.length === 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Please select at least one topic!',
                    });
                } else if (sumOfTopicCounts < 150) {
                    Swal.fire({
                        title: "Not Enough Topics Selected",
                        text: `You only selected a total of ${sumOfTopicCounts} questions for topics. Please select ${150 - sumOfTopicCounts} more to proceed.`,
                        icon: "warning",
                        button: "OK",
                    });
                } else {
                    // Show the instructions modal
                    Swal.fire({
                        title: '<strong>Exam Instructions</strong>',
                        html: `
        <ul style="list-style: none; padding: 0; text-align: left;">
            <li><strong>• Complete All Questions:</strong> Your results will only be displayed after you have finished all 150 questions. Make sure to answer all questions before submitting.</li> <br/>
            <li><strong>• Use One Device Only:</strong> Do not use more than one device at a time while taking the exam. This can lead to errors in your results. Each account should only be accessed from one device during the exam.</li> <br/>
            <li><strong>• Avoid Reloading or Navigating Back:</strong> Do not reload the page or use the back button on your browser or mobile device. This can disrupt the exam process and may affect your results.</li><br/>
            <li><strong>• Report Missing Exhibits:</strong> If you encounter a question that does not have any exhibits or attachments, please report it on the technical support page. Include a screenshot of the question for reference so that it can be addressed appropriately.</li>
        </ul>
    `,

                        showCloseButton: false,
                        confirmButtonText: 'I Understand',
                        cancelButtonText: 'Cancel',
                        showCancelButton: true,
                        width: '600px', // Set to a larger size
                        maxWidth: '600px', // Set a max width
                        allowOutsideClick: false,
                        customClass: {
                            confirmButton: 'custom-confirm-button', // Custom class for confirm button
                            cancelButton: 'custom-cancel-button' // Optional: custom class for cancel button
                        },

                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Redirect to the question page
                            const selectedTopics = Array.from(topicsSelected).map(topic => topic.value);
                            window.location.href = `question/pre-loader.php?topics1=${conceptSelected.value}&topics2=${selectedTopics.join(',')}&kilanlan=<?= $kilanlan ?>&id=<?= $fetch['id'] ?>`;
                        } else if (result.isDismissed) {
                            // Handle cancel action if needed
                            console.log('User canceled the action.');
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Redirect to the question page
                            const selectedTopics = Array.from(topicsSelected).map(topic => topic.value);
                            window.location.href = `question/pre-loader.php?topics1=${conceptSelected.value}&topics2=${selectedTopics.join(',')}&kilanlan=<?= $kilanlan ?>&id=<?= $fetch['id'] ?>`;
                        }
                    });
                }
            });
        });

        const style = document.createElement('style');
        style.innerHTML = `

        *{

text-transform: none;
    }
    .custom-confirm-button {
        background-color: #1B4965 !important;
        color: white !important;
    }
    .custom-cancel-button {
        background-color: #f00 !important; /* Optional: Red color for cancel button */
        color: white !important;
    }
`;
        document.head.appendChild(style);
    </script>
</body>

</html>

