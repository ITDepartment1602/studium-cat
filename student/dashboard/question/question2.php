<!--=============== COPY 1 START ===============-->
<?php include '../../../config.php';

// qnums
$qnums = $_GET['qnums'] ?? [];
// qnums

$concept = isset($_GET['topics1']) ? mysqli_real_escape_string($con, $_GET['topics1']) : '';
$kilanlanhistory = isset($_GET['kilanlanhistory']) ? mysqli_real_escape_string($con, $_GET['kilanlanhistory']) : '';
$selectedTopics = isset($_GET['topics2']) ? explode(',', $_GET['topics2']) : [];
$cc = isset($_GET['cc']) ? mysqli_real_escape_string($con, $_GET['cc']) : '';
$wc = isset($_GET['wc']) ? mysqli_real_escape_string($con, $_GET['wc']) : '';


$systems = [];
foreach ($selectedTopics as $topic) {
    $decodedTopic = urldecode($topic);
    list($id, $systemName) = explode('|', $decodedTopic);
    $systems[] = mysqli_real_escape_string($con, $systemName);
}

// Debugging: Check extracted systems

?>

<!--=============== COPY 1 END ===============-->

<!DOCTYPE html>
<html oncontextmenu="return false" onselectstart="return false" ondragstart="return false">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--=============== REMIXICONS ===============-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.min.css">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet" />
    <!--=============== CSS ===============-->
    <link rel="stylesheet" href="../css/starts.css">
    <link rel="stylesheet" href="../css/feedback.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <!-- TIMER FORMULA -->
    <script type="text/javascript">
        var seconds = 1; // Start from 0

        function secondPassed() {
            var minutes = Math.floor(seconds / 60);
            var remainingSeconds = seconds % 60;

            // Format the remaining seconds
            if (remainingSeconds < 10) {
                remainingSeconds = "0" + remainingSeconds;
            }

            // Update the display
            document.getElementById('countup').innerHTML = minutes + ":" + remainingSeconds;
            seconds++;
        }

        // Start the timer
        var countupTimer = setInterval(secondPassed, 1000); // Update every second
    </script>
    <!-- TIMER FORMULA END HERE -->


    <script type="text/javascript">
        document.onmousedown = disableRightclick;
        var message = "<i class='fa fa-exclamation-circle' style='font-size: 20px'></i>&nbsp;<a style='top: 10%'>Warning: Right Click is Disabled</a>";
        function disableRightclick(evt) {
            if (evt.button == 2) {
                alert(message);
                return false;
            }
        }
    </script>
    <!--=============== CSS ===============-->

    <title>studium</title>
    <link rel="shortcut icon" type="text/css" href="../../../img/logo1.svg">
</head>

<body>
    <!--<div id="pre-loader"></div> -->
    <div class="qn">Question: 2 out of 150</div>

    <!--=============== COPY 2 START ===============-->

    <div class="containerab">
        <div class="navbaraba">

            <!-- OVERALL TIMER START-->
            <div
                style="position: absolute; display: flex; justify-content: center; flex-direction: column; align-items: center; left: 65px; right: 0; margin: 0 auto; text-align: center; font-size: 14px; ">
                <?php include '../clock.php'; ?> Total Time
            </div>
            <!-- OVERALL TIMER END-->
            &nbsp;&nbsp;&nbsp;&nbsp;
            <?php
            $systemList = "'" . implode("','", $systems) . "'";
            $sql = "SELECT * FROM question WHERE topics1='$concept' AND system IN ($systemList)";

            $result = mysqli_query($con, $sql);

            if (!$result) {
                die("Query failed: " . mysqli_error($con)); // Debugging line
            }

            while ($row = mysqli_fetch_array($result)) {
                echo "<p style='font-size: 17px; width: 100%; margin-top: 15px'></p>";
            }
            ?>
            <!--==================== Timer ====================-->
            <i class="fa-regular fa-clock" style="position: absolute; right: 45px;  "></i>&nbsp;<span id="countdown"
                style="font-size:14px;"></span>&nbsp;&nbsp;&nbsp;<span id="countup"
                style="font-size:14px;">0:00</span>&nbsp;&nbsp;&nbsp;
            <script>var seconds = 1; // Start from 0function secondPassed() {    var minutes = Math.floor(seconds / 60);    var remainingSeconds = seconds % 60;    if (remainingSeconds < 10) {      remainingSeconds = "0" + remainingSeconds;   }    document.getElementById('countup').innerHTML = minutes + ":" + remainingSeconds;    seconds++;}var countupTimer = setInterval(secondPassed, 1000); // Use function reference</script>
            &nbsp;
            <!-- END TIMER HERE -->
        </div>
    </div>



    <!--==================== HEADER ====================-->
    <div class="containerab">
        <!--==================== Content ====================-->
        <div class="subContainerab">
            <div class="main_containerab">
                <div class="contentab">
                    <div class="content3ab">
                        <div class="sidebar-link">
                            <?php include '../feedback.php'; ?>
                            <?php include '../calculator.php'; ?>
                            <a style="cursor: pointer;">
                                <i class="fa fa-arrows-alt" onclick="openFullscreen();" title="Enter Fullscreen"></i>
                            </a>
                            <a class="sidebar-link" style="cursor: pointer;">
                                <i class="fa fa-times-circle" onclick="closeFullscreen();" title="Exit Fullscreen"></i>
                            </a>
                            <?php include '../mynotes.php'; ?>
                            <a href="../../../img/userguide.mp4" target="_blank" class="sidebar-link"
                                style="cursor: pointer; text-decoration:none; color: white;">
                                <i class="fa fa-question-circle" title="User Guide"></i>
                            </a>
                        </div>
                    </div>
                    <!--==================== Content Left ====================-->
                    <div class="content1ab">

                        <!-- COPY DIZ WAN -->
                        <?php
                        // Retrieve qnums from URL
                        $qnumsString = $_GET['qnums'] ?? '';

                        // Convert the pipe-separated string into an array
                        $qnums = !empty($qnumsString) ? explode('|', $qnumsString) : [];

                        // Prepare the SQL query
                        $sql = "SELECT * FROM question WHERE topics1='$concept' AND system IN ($systemList)";

                        // If there are IDs in the qnums array, join them with commas for the NOT IN clause
                        if (!empty($qnums)) {
                            $qnumsList = implode(',', array_map('intval', $qnums)); // Ensure safe integer conversion
                            $sql .= " AND id NOT IN ($qnumsList)";
                        }

                        $sql .= " AND (type IS NULL OR type != 'SATA')";
                        $sql .= " ORDER BY RAND() LIMIT 1";

                        // Execute the query
                        $result = mysqli_query($con, $sql);

                        // Check if any questions were returned
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_array($result)) {
                                ?>
                                <!-- COPY DIZ WAN -->
                                <form
                                    action="../rationale/ans2.php?q=quiz&step=2&eid=<?= $concept ?>&n=1&id=<?= $_GET['id'] ?>&qid=<?= $row['correctans'] ?>&topics1=<?= $_GET['topics1'] ?>&topics2=<?= $_GET['topics2'] ?>&qq=<?= $row['id'] ?>&kilanlan=<?= $row['system'] ?>&kilanlanhistory=<?= $_GET['kilanlan'] ?>&cc=<?= $_GET['cc'] ?>&wc=<?= $_GET['wc'] ?>&qnums=<?= $qnumsString ?>"
                                    method='POST'>
                                    <!-- idagdagto -->
                                    <input type="hidden" name="time_taken" id="time_taken" value="0">
                                    <!-- end idagdagto -->
                                    <br>
                                    <b>Question 2</b><br><br>
                                    <!-- baguhinto -->
                                    <?= $row['question'] ?><br><br>
                                    <input type="radio" name="ans" value="1" required> <?= $row['choiceA'] ?><br><br>
                                    <input type="radio" name="ans" value="2" required> <?= $row['choiceB'] ?><br><br>
                                    <input type="radio" name="ans" value="3" required> <?= $row['choiceC'] ?><br><br>
                                    <input type="radio" name="ans" value="4" required> <?= $row['choiceD'] ?><br><br><br><br>
                                    <button class="question button1" type="submit" name="question"
                                        onclick="document.getElementById('time_taken').value = seconds;">Submit</button>
                                </form>
                                <!-- baguhinto -->
                                <?php
                                // ALSO DIZ WAN
                            } // End of while loop
                        } else {
                            echo "<p>No more questions available.</p>"; // Handle case when no questions are left
                        }
                        ?>
                        <!-- ALSO DIZ WAN -->
                    </div>


                    <!--=============== COPY 2 END ===============-->

                    <!--==================== Content Right ====================-->
                    <div class="content2ab">

                    </div>

                    <!--==================== Content Footer ====================-->
                    <div class="footerab">
                        <div class="footerac">
                            <?php
                            $select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$id'") or die('query failed');
                            if (mysqli_num_rows($select) > 0) {
                                $fetch = mysqli_fetch_assoc($select);
                            }
                            ?>
                            <form method="POST" action="../index.php?bundle_name=<?= $fetch['bundle_name'] ?>"
                                onsubmit="return submitForm(this);">
                                <input class="question button3" type="submit" value="End" />
                            </form>
                        </div>

                        <input type="button" value="Back" onclick="goBack()" class="question button2">


                        <a data-toggle="modal" data-target="#qp">
                            <div class="question button2">Question Pages</div>
                        </a>
                        <div class="modal fade" id="qp" tabindex="-1" role="dialog"
                            aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document"
                                style="max-width:200%; margin-top: -5%;">
                                <div class="qpages">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalLongTitle" style="color: black;">
                                                <b>Question Pages</b>
                                            </h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">

                                                <!-- Add Note -->
                                                <div class="col-md-12">
                                                    <div class="card" style="height: 490px;">
                                                        <div class="card-body">
                                                            <div class="data-item">
                                                                <ul class="list-group" style="color: black;">
                                                                    <table class="table table-striped data-table">
                                                                        <thead
                                                                            style="background-color: #5598C6; color: white;">
                                                                            <tr>
                                                                                <th>Question Number</th>
                                                                                <th>Status</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <!-- PORTINAYTI -->
                                                                            <tr style="background-color: #AAD8F8;">
                                                                                <td>Question Number: 1</td>
                                                                                <td style="text-align: start;">
                                                                                    <a href="../rationale/questionpages.php?questionNumber=1&examTaken=<?= $fetch['examTaken'] ?>&userId=<?= $fetch['id'] ?>"
                                                                                        class=""
                                                                                        target="_blank">Complete</a>
                                                                                </td>
                                                                            </tr>

                                                                            </tr>
                                                                            <tr></tr>
                                                                            <td>Question Number: 2</td>
                                                                            <td style="color: red;">Unseen</td>
                                                                            </script>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 3</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 4</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 5</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 6</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 7</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 8</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 9</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 10</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 11</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 12</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 13</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 14</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 15</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 16</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 17</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 18</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 19</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 20</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 21</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 22</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 23</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 24</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 25</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 26</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 27</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 28</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 29</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 30</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 31</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 32</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 33</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 34</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 35</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 36</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 37</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 38</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 39</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 40</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>

                                                                            <tr>
                                                                                <td>Question Number: 41</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 42</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 43</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 44</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 45</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 46</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 47</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 48</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 49</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 50</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 51</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 52</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 53</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 54</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 55</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 56</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 57</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 58</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 59</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 60</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 61</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 62</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 63</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 64</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 65</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 66</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 67</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 68</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 69</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 70</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 71</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 72</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 73</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 74</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 75</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 76</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 77</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 78</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 79</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 80</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 81</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 82</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 83</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 84</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 85</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 86</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 87</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 88</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 89</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 90</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 91</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 92</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 93</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 94</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 95</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 96</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 97</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 98</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 99</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 100</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 101</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 102</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 103</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 104</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 105</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 106</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 107</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 108</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 109</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 110</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 111</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 112</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 113</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 114</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 115</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 116</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 117</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 118</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 119</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 120</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 121</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 122</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 123</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 124</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 125</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 126</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 127</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 128</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 129</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 130</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 131</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 132</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 133</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 134</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 135</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 136</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 137</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 138</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 139</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 140</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 141</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 142</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 143</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 144</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 145</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 146</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 147</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 148</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 149</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Question Number: 150</td>
                                                                                <td style="color: red;">Unseen</td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>




                    </div>
                </div>
            </div>
        </div>
    </div>




    <!--==================== Java Script ====================-->
    <script src="../assets/js/loading.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/sweet.min.js"></script>
    <!--==================== Java Script Calculator====================-->
    <script type="text/javascript">
        var calculator_btn = document.querySelector(".calculator_btn");
        var wrapper = document.querySelector(".wrapper");
        var close_btns = document.querySelectorAll(".close_btn");

        calculator_btn.addEventListener("click", function () {
            wrapper.classList.add("active");
        });

        close_btns.forEach(function (btn) {
            btn.addEventListener("click", function () {
                wrapper.classList.remove("active");
            });
        });

    </script>
    <!--==================== Java Script Note====================-->

    <script>
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

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function (event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
    <!--==================== Java Script Back====================-->

    <script>
        function submitForm(form) {
            swal({
                title: "Are you sure?",
                text: "Your data will be lost",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
                .then(function (isOkay) {
                    if (isOkay) {
                        form.submit();
                    }
                });
            return false;
        }
    </script>

    <!--==================== Java Script Fullscreen====================-->

    <script>
        var elem = document.documentElement;
        function openFullscreen() {
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
            } else if (elem.webkitRequestFullscreen) { /* Safari */
                elem.webkitRequestFullscreen();
            } else if (elem.msRequestFullscreen) { /* IE11 */
                elem.msRequestFullscreen();
            }
        }

        function closeFullscreen() {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) { /* Safari */
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) { /* IE11 */
                document.msExitFullscreen();
            }
        }
    </script>
    <script>
        function goBack() {
            window.history.back();
        }
    </script>

    <script src="../assets/js/disable.js"></script>
</body>

</html>