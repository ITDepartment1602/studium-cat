<!--==================================================== COPY 1 STARTS HERE ====================================================-->

<?php


include '../../../config.php'; // Include your database configuration

// Initialize variables
$fetch = [];
$eid = $_GET['eid'] ?? '';
$kilanlan = $_GET['kilanlan'] ?? '';
$qq = $_GET['qq'] ?? '';
$kilanlanhistory = isset($_GET['kilanlanhistory']) ? mysqli_real_escape_string($con, $_GET['kilanlanhistory']) : '';
$cc = isset($_GET['cc']) ? mysqli_real_escape_string($con, $_GET['cc']) : '';
$wc = isset($_GET['wc']) ? mysqli_real_escape_string($con, $_GET['wc']) : '';

// Validate required parameters
if (!empty($eid) && !empty($kilanlan) && !empty($qq)) {
  // Use prepared statements for security
  $stmt = $con->prepare("SELECT * FROM `question` WHERE topics1 = ? AND system = ? AND id = ?");
  $stmt->bind_param("ssi", $eid, $kilanlan, $qq);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $fetch = $result->fetch_assoc();
  } else {
    die('No question found');
  }
}

// Handle quiz submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $ans = $_POST['ans'] ?? null; // Get the answer from the form
  $timeTaken = $_POST['time_taken'] ?? 0; // Get the time taken
  $user_id = $_GET['id'];

  // Fetch correct answer
  $stmt = $con->prepare("SELECT correctans FROM question WHERE id = ? AND system = ?");
  $stmt->bind_param("is", $qq, $kilanlan);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($row = $result->fetch_assoc()) {
    $correctAns = $row['correctans'];

    // Initialize counts
    $correctCount = 0;
    $wrongCount = 0;
    // Check if the answer is correct
    if ($ans == $correctAns) {
      $correctCount++; // Increment correct count
      $isCorrect = 1;
    } else {
      $wrongCount++; // Increment wrong count
      $isCorrect = 0;
    }

    // Fetch examTaken from login table
    $stmt = $con->prepare("SELECT examTaken FROM login WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $examTaken = $result->fetch_assoc()['examTaken'] ?? '';

    // Check if the question already exists in the review table for the same examTaken and studentId
    $stmt = $con->prepare("SELECT COUNT(*) as count FROM review WHERE questionId = ? AND examTaken = ? AND studentId = ?");
    $stmt->bind_param("isi", $qq, $examTaken, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'] ?? 0;

    if ($count == 0) {
      // Insert data into review table
      $stmt = $con->prepare("INSERT INTO review (questionId, isCorrect, topics1, system, cnc, timeTaken, studentId, examTaken, ans, correctAns, questionNumber) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $questionNumber = 115; // KASUKASUAN
      $stmt->bind_param("iissssisssi", $qq, $isCorrect, $fetch['topics1'], $fetch['system'], $fetch['cnc'], $timeTaken, $user_id, $examTaken, $ans, $correctAns, $questionNumber);
      $stmt->execute();

    } else {
      // Update isCorrect in review table
      $stmt = $con->prepare("UPDATE review SET isCorrect = ?, timeTaken = ?, ans = ? WHERE questionId = ? AND examTaken = ? AND studentId = ?");
      $stmt->bind_param("iissis", $isCorrect, $timeTaken, $ans, $qq, $examTaken, $user_id);
      $stmt->execute();
    }

  }
}
?>

<!-- MARCH 06 2025 -->
<!-- QUESTION ID -->
<?php
// Assuming $qq holds the current question ID
$qq = $_GET['qq'];

// Retrieve existing qnums from URL, if any
$qnumsString = $_GET['qnums'] ?? '';

// Append the current question ID to the existing qnums
if (!empty($qnumsString)) {
  $qnumsString .= '|' . $qq; // Append with pipe
} else {
  $qnumsString = $qq; // Just set it if empty
}
?>
<!-- QUESTION ID -->
<!-- MARCH 06 2025 -->



<!--==================================================== COPY 1 ENDS HERE ====================================================-->
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

  <script>
    function storeAnswerInLocalStorage(answerData) {
      // Retrieve existing answers from local storage
      let answers = JSON.parse(localStorage.getItem('quizAnswers')) || [];

      // Check if the question already exists
      const existingIndex = answers.findIndex(item => item.questionId === answerData.questionId);
      if (existingIndex > -1) {
        // Update existing answer
        answers[existingIndex] = answerData;

        // Log the entire answers array after the update
        console.log("Updated answers array:", answers);
      } else {
        // Add new answer
        answers.push(answerData);
        console.log("Added new answer:", answerData);
      }

      // Store updated answers back in local storage
      localStorage.setItem('quizAnswers', JSON.stringify(answers));
    }
  </script>
  <title>studium</title>
  <link rel="shortcut icon" type="text/css" href="../../../img/logo1.svg">
</head>

<body>

  <div class="qn">Question: 115 out of 150</div>
  <!--==================================================== COPY 2 STARTS HERE ====================================================-->

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
      include '../../../config.php';
      $topic = $_GET['eid'] ?? ''; //CHANGE it FROM 'topic' to 'eid'
      $kilanlan = $_GET['kilanlan'];

      $sql = "select * from question where topics1='$topic' and system='$kilanlan'";
      $result = mysqli_query($con, $sql);

      while ($row = mysqli_fetch_array($result)) {

        ?>
        <p style="font-size: 17px; width: 100%; margin-top: 15px"></p>
      <?php } ?>

      <!-- malupiton -->
      <i class="fa-solid fa-circle-stop" style="position: absolute; right: 45px; color: #B10000; "></i>&nbsp;<div
        style="font-size: 14px; padding-right: 9px; color: #B10000; ">
        <?php echo sprintf('%d:%02d', (int) ($timeTaken / 60), $timeTaken % 60) ?>
      </div>
      <!-- malupiton end-->

      &nbsp;
      &nbsp;
    </div>
  </div>
  <!--==================================================== COPY 2 ENDS HERE ====================================================-->


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
            <?php
            $eid = $_GET['eid'];
            $kilanlan = $_GET['kilanlan'];
            $qq = $_GET['qq'];

            $select = mysqli_query($con, "SELECT * FROM `question` WHERE topics1 = '$eid' AND topic = '$kilanlan' AND id = '$qq'") or die('query failed');
            if (mysqli_num_rows($select) > 0) {
              $fetch = mysqli_fetch_assoc($select);
            }
            ?>
            <!-- COPY MO SI QUERY -->
            <?php
            $sql = "SELECT id FROM question WHERE topics1 = '$topic' AND system = '$kilanlan' AND id = '$qq'";
            $result = mysqli_query($con, $sql);
            $row = mysqli_fetch_assoc($result);
            ?>
            <!-- END COPY MO SI QUERY -->
            <br>
            <form>
              <br>
              <b style="font-size: 20px; ">Question 115</b><br><br>
              <?= $fetch['question'] ?? 'Question not found' ?><br><br>
              <!-- QUESTION Choices UPDATED COPY STARTS HERE -->
              <?php
              $correctAnswer = $fetch['correctans'] ?? '';
              $selectedAnswer = $_POST['ans'] ?? '';
              ?>
              <!-- QUESTION Choices -->

              <div
                style="margin-bottom: 10px; position: relative; padding: 10px; padding-left: 10px; background-color: <?= (strpos($correctAnswer, '1') !== false) ? '#00B11B' : '#F2F2F2' ?>; border-radius: 4px;">
                <i class="fa fa-lg <?= (strpos($correctAnswer, '1') !== false) ? 'fa-solid fa-check' : 'fa-solid fa-xmark' ?>"
                  style="color: <?= (strpos($correctAnswer, '1') === false) ? 'red' : 'white' ?>"></i>
                <input type="radio" name="selected_ans" value="1" <?= (strpos($selectedAnswer, '1') !== false) ? 'checked' : '' ?>
                  style="position: absolute; left: 30px; top: 50%; transform: translateY(-50%); cursor: pointer;"
                  onclick="return false;">

                <span
                  style="margin-left: 20px; font-size: 16px; color: <?= (strpos($correctAnswer, '1') !== false) ? 'white' : 'black' ?>;"><?= $fetch['choiceA'] ?? '' ?></span>
              </div>

              <div
                style="margin-bottom: 10px; position: relative; padding: 10px; padding-left: 10px; background-color: <?= (strpos($correctAnswer, '2') !== false) ? '#00B11B' : '#F2F2F2' ?>; border-radius: 4px;">
                <input type="radio" name="selected_ans" value="2" <?= (strpos($selectedAnswer, '2') !== false) ? 'checked' : '' ?>
                  style="position: absolute; left: 30px; top: 50%; transform: translateY(-50%); cursor: pointer;"
                  onclick="return false;">
                <i class="fa fa-lg <?= (strpos($correctAnswer, '2') !== false) ? 'fa-solid fa-check' : 'fa-solid fa-xmark' ?>"
                  style="color: <?= (strpos($correctAnswer, '2') === false) ? 'red' : 'white' ?>"></i>
                <span
                  style="margin-left: 20px; font-size: 16px; color: <?= (strpos($correctAnswer, '2') !== false) ? 'white' : 'black' ?>;"><?= $fetch['choiceB'] ?? '' ?></span>
              </div>

              <div
                style="margin-bottom: 10px; position: relative; padding: 10px; padding-left: 10px; background-color: <?= (strpos($correctAnswer, '3') !== false) ? '#00B11B' : '#F2F2F2' ?>; border-radius: 4px;">
                <input type="radio" name="selected_ans" value="3" <?= (strpos($selectedAnswer, '3') !== false) ? 'checked' : '' ?>
                  style="position: absolute; left: 30px; top: 50%; transform: translateY(-50%); cursor: pointer;"
                  onclick="return false;">
                <i class="fa fa-lg <?= (strpos($correctAnswer, '3') !== false) ? 'fa-solid fa-check' : 'fa-solid fa-xmark' ?>"
                  style="color: <?= (strpos($correctAnswer, '3') === false) ? 'red' : 'white' ?>"></i>
                <span
                  style="margin-left: 20px; font-size: 16px; color: <?= (strpos($correctAnswer, '3') !== false) ? 'white' : 'black' ?>;"><?= $fetch['choiceC'] ?? '' ?></span>
              </div>

              <div
                style="margin-bottom: 10px; position: relative; padding: 10px; padding-left: 10px; background-color: <?= (strpos($correctAnswer, '4') !== false) ? '#00B11B' : '#F2F2F2' ?>; border-radius: 4px;">
                <input type="radio" name="selected_ans" value="4" <?= (strpos($selectedAnswer, '4') !== false) ? 'checked' : '' ?>
                  style="position: absolute; left: 30px; top: 50%; transform: translateY(-50%); cursor: pointer;"
                  onclick="return false;">
                <i class="fa fa-lg <?= (strpos($correctAnswer, '4') !== false) ? 'fa-solid fa-check' : 'fa-solid fa-xmark' ?>"
                  style="color: <?= (strpos($correctAnswer, '4') === false) ? 'red' : 'white' ?>"></i>
                <span
                  style="margin-left: 20px; font-size: 16px; color: <?= (strpos($correctAnswer, '4') !== false) ? 'white' : 'black' ?>;"><?= $fetch['choiceD'] ?? '' ?></span>
              </div>

              <!-- ENDS HERE -->
              <div class="container">
                <div class="box-container">
                  <div class="box">
                    <b style="color: #0B2557; font-size: 20px; ">Tags:</b><br><br>
                    <div class="" style="column-count: 2; column-gap: 40px;">
                      <p>Subject: <b><?php echo $fetch['topics1'] ?></b></p>
                      <p>Difficulty level: <b><?php echo $fetch['dlevel'] ?></b></p>
                      <p>NCLEX client needs category: <b><?php echo $fetch['cnc'] ?></b></p>
                      <!-- TIMER START HERE -->
                      <p>Time taken: <b>
                          <?php
                          $minutes = floor($timeTaken / 60);
                          $seconds = $timeTaken % 60;
                          if ($minutes > 0) {
                            echo $minutes . ' min ' . $seconds . ' sec';
                          } else {
                            echo $seconds . ' sec';
                          }
                          ?>
                        </b></p>
                      <!-- TIMER END HERE -->
                      <p>System: <b><?php echo $fetch['system'] ?></b></p>
                      <p>Question ID:
                        <b><?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?></b>
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </form>
          </div>
          <!--==================== Content Right ====================-->
          <div class="content2ab">
            <form>
              <br>
              <?= nl2br(str_replace('\n', "\n", $fetch['rationale'] ?? 'No rationale available')) ?>


              <br><br>

              <div class="container">
                <div class="box-container">

                  <div class="box">
                    <b style="color: #0B2557; font-size: 20px; ">NARC Additional notes:</b><br><br>
                    <p><?= nl2br(str_replace('\n', "\n", $fetch['narcan'])) ?></p>

                  </div>


                </div>
              </div>


            </form>
          </div>

          <!--==================== Content Footer ====================-->
          <div class="footerab">
            <div class="footerac">
              <?php
              $select = mysqli_query($con, "SELECT bundle_name FROM `login` WHERE id = '$id'") or die('query failed');
              if (mysqli_num_rows($select) > 0) {
                $endd = mysqli_fetch_assoc($select);
              }
              ?>
              <form method="POST" action="../index.php?bundle_name=<?php echo $endd['bundle_name']; ?>"
                onsubmit="return submitForm(this);">
                <input class="question button3" type="submit" value="End" />
              </form>
            </div>


            <a data-toggle="modal" data-target="#qp">
              <div class="question button2">Question Pages</div>
            </a>
            <div class="modal fade" id="qp" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
              aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:200%; margin-top: -5%;">
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
                                <ul class="list-group" style="color:black;">
                                  <table class="table table-striped data-table">
                                    <thead style="background-color: #5598C6; color: white;">
                                      <tr>
                                        <th>Question Number</th>
                                        <th>Status</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <tr style="background-color: #AAD8F8;">
                                        <td>Question Number: 1</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=1&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 2</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=2&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 3</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=3&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 4</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=4&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 5</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=5&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 6</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=6&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 7</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=7&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 8</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=8&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 9</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=9&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 10</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=10&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 11</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=11&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 12</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=12&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 13</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=13&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 14</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=14&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 15</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=15&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 16</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=16&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 17</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=17&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 18</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=18&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 19</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=19&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 20</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=20&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 21</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=21&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 22</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=22&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 23</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=23&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 24</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=24&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 25</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=25&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 26</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=26&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 27</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=27&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 28</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=28&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 29</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=29&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 30</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=30&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 31</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=31&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 32</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=32&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 33</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=33&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 34</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=34&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 35</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=35&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 36</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=36&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 37</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=37&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 38</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=38&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 39</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=39&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 40</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=40&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>

                                      <tr>
                                        <td>Question Number: 41</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=41&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>

                                      <tr>
                                        <td>Question Number: 42</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=42&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 43</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=43&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 44</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=44&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 45</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=45&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 46</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=46&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 47</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=47&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 48</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=48&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 49</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=49&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 50</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=50&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 51</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=51&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 52</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=52&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 53</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=53&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 54</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=54&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 55</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=55&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 56</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=56&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 57</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=57&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 58</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=58&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 59</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=59&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 60</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=60&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 61</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=61&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 62</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=62&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 63</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=63&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 64</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=64&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 65</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=65&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 66</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=66&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 67</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=67&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 68</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=68&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 69</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=69&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 70</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=70&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 71</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=71&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 72</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=72&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 73</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=73&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 74</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=74&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 75</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=75&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 76</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=76&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 77</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=77&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 78</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=78&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 79</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=79&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 80</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=80&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 81</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=81&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 82</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=82&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 83</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=83&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 84</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=84&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 85</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=85&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 86</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=86&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 87</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=87&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 88</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=88&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 89</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=89&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 90</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=90&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 91</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=91&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 92</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=92&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 93</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=93&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 94</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=94&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 95</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=95&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 96</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=96&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 97</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=97&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 98</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=98&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 99</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=99&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 100</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=100&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 101</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=101&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 102</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=102&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 103</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=103&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 104</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=104&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 105</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=105&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 106</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=106&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 107</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=107&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 108</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=108&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 109</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=109&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 110</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=110&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 111</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=111&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 112</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=112&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 113</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=113&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Question Number: 114</td>
                                        <td style="text-align: start;">
                                          <a href="../rationale/questionpages.php?questionNumber=114&examTaken=<?= $examTaken ?>&userId=<?= $id ?>"
                                            class="" target="_blank">Complete</a>

                                        </td>
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
            <?php
            // Debugging: Check values being passed
            
            // Ensure these variables are defined before use
            $correctCount = isset($correctCount) ? $correctCount : 0; // Default to 0 if not set
            $wrongCount = isset($wrongCount) ? $wrongCount : 0; // Default to 0 if not set
            
            // Retrieve GET parameters safely
            $topics1 = isset($_GET['eid']) ? $_GET['eid'] : '';
            $topics2 = isset($_GET['topics2']) ? $_GET['topics2'] : '';
            $kilanlan = isset($_GET['kilanlan']) ? $_GET['kilanlan'] : '';
            $id = isset($_GET['id']) ? $_GET['id'] : '';
            $kilanlanhistory = isset($_GET['kilanlanhistory']) ? $_GET['kilanlanhistory'] : '';
            $qnum1 = isset($_GET['qnum1']) ? $_GET['qnum1'] : '';
            $qnum2 = isset($qq) ? $qq : ''; // Assuming $qq is set
            
            ?>
            <form method="POST"
              action="../question/question116.php?topics1=<?= $_GET['eid'] ?>&topics2=<?= $_GET['topics2'] ?>&kilanlan=<?= $_GET['kilanlan'] ?>&id=<?= $_GET['id'] ?>&kilanlanhistory=<?= $_GET['kilanlanhistory'] ?>&cc=<?= $correctCount ?>&wc=<?= $wrongCount ?>&qnums=<?= $qnumsString ?>">
              <button class="question button2">Next</button>
            </form>


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
  <script src="../assets/js/disable.js"></script>

</body>

</html>