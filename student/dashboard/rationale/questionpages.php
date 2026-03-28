<?php
include '../../../config.php'; // Include your database configuration

$questionNumber = (int) ($_GET['questionNumber'] ?? 0);
$examTaken = (int) ($_GET['examTaken'] ?? 0);
$user_id = (int) ($_GET['userId'] ?? 0);

// Ensure that the parameters are set
if (isset($_GET['questionNumber'], $_GET['examTaken'], $_GET['userId'])) {
    // Prepare the query to fetch data from the review table
    $stmt = $con->prepare("SELECT * FROM review WHERE studentId = ? AND questionNumber = ? AND examTaken = ?");
    if (!$stmt) {
        die("Prepare failed: " . $con->error);
    }

    $stmt->bind_param("iii", $user_id, $questionNumber, $examTaken);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0 && $row = $result->fetch_assoc()) {
        // Assign values from the review table
        $isCorrect = $row['isCorrect'];
        $questionId = $row['questionId'];
        $topics1 = $row['topics1'];
        $system = $row['system'];
        $cnc = $row['cnc'];
        $timeTaken = $row['timeTaken'];
        $correctAns = $row['correctAns'] ?? '';
        $ans = $row['ans'] ?? '';

        // Fetch additional details from the question table
        if ($questionId) {
            $stmt2 = $con->prepare("SELECT question, rationale, options, narcan, dlevel, type, choiceA, choiceB, choiceC, choiceD FROM question WHERE id = ?");
            if (!$stmt2) {
                die("Prepare failed: " . $con->error);
            }

            $stmt2->bind_param("i", $questionId);
            $stmt2->execute();
            $result2 = $stmt2->get_result();

            if ($result2->num_rows > 0 && $row2 = $result2->fetch_assoc()) {
                $question = $row2['question'] ?? 'Question not found';
                $rationale = $row2['rationale'] ?? 'No rationale available';
                $options = json_decode($row2['options'], true) ?? []; // Decode options as an array
                $choices = [
                    'A' => $row2['choiceA'] ?? '',
                    'B' => $row2['choiceB'] ?? '',
                    'C' => $row2['choiceC'] ?? '',
                    'D' => $row2['choiceD'] ?? '',
                ];
                $narcan = $row2['narcan'] ?? 'No additional notes available';
                $dlevel = $row2['dlevel'] ?? 'Not specified';
                $questionType = $row2['type'] ?? ''; // Add this line to get the question type
            } else {
                $question = 'No question details found for this ID.';
            }
        } else {
            $question = 'Question ID is invalid.';
        }
    } else {
        $question = 'No question found in review.';
    }
} else {
    $question = 'Invalid parameters.';
}
?>

<!DOCTYPE html>
<html oncontextmenu="return false" onselectstart="return false" ondragstart="return false">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.min.css">
    <link rel="stylesheet" href="../css/starts.css">
    <link rel="stylesheet" href="../css/feedback.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <title>Question <?= htmlspecialchars($questionNumber) ?></title>
    <link rel="shortcut icon" type="text/css" href="../../../img/logo1.svg">
</head>

<body>
    <div class="qn"></div>

    <div class="containerab">
        <div class="navbaraba" style="background-color: #1B4965;">
            <!-- OVERALL TIMER START-->
            <!-- OVERALL TIMER END-->
            &nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;
            &nbsp;
        </div>
    </div>
    <div class="containerab">
        <div class="subContainerab">
            <div class="main_containerab">
                <div class="contentab">
                    <div class="content1ab">
                        <br>
                        <form>
                            <b style="font-size: 20px;">Question <?= htmlspecialchars($questionNumber) ?></b><br><br>
                            <p><?= nl2br(htmlspecialchars($question)) ?></p>
                            <br><br>

                            <!-- QUESTION Choices -->
                            <?php
                            $correctAnswer = json_decode($row['correctAns'], true) ?? []; // Decode correct answers
                            $selectedAnswer = isset($row['ans']) ? json_decode($row['ans'], true) : []; // Decode user's answers
                            $choices = [
                                htmlspecialchars($row2['choiceA']),
                                htmlspecialchars($row2['choiceB']),
                                htmlspecialchars($row2['choiceC']),
                                htmlspecialchars($row2['choiceD']),
                            ];




                            if ($questionType === 'SATA'): // Check if question type is SATA
                                foreach ($options as $index => $option): ?>
                                    <li style="list-style: none; display: flex; align-items: center; padding: 10px; border-radius: 5px; margin-bottom: 5px; 
                background-color: <?= in_array($index, $correctAnswer) ? '#00B11B' : '#F2F2F2' ?>;">

                                        <!-- Cross for wrong answers on the left side -->
                                        <?php if (!in_array($index, $correctAnswer) && in_array($index, $selectedAnswer)): ?>
                                            <span style="color: red; font-size: 20px; margin-right: 10px;">&#10006;</span>
                                        <?php endif; ?>

                                        <!-- Read-only Checkbox -->
                                        <input type="checkbox" name="ans[]" value="<?= $index ?>" <?= in_array($index, $selectedAnswer) ? 'checked' : 'disabled' ?>
                                            style="margin-right: 10px; cursor: pointer;" disabled>

                                        <!-- Option Text -->
                                        <span
                                            style="flex-grow: 1; color: <?= in_array($index, $correctAnswer) ? 'white' : 'black' ?>;">
                                            <?= htmlspecialchars($option) ?>
                                        </span>
                                    </li>
                                <?php endforeach;
                            elseif ($questionType === null): // Check if question type is NULL
                                foreach ($choices as $index => $choice): ?>
                                    <div
                                        style="margin-bottom: 10px; padding: 10px; background-color: <?= ($correctAnswer == $index + 1) ? '#00B11B' : '#F2F2F2' ?>; border-radius: 4px;">
                                        <input type="radio" name="selected_ans" value="<?= $index + 1 ?>"
                                            <?= ($selectedAnswer == $index + 1) ? 'checked' : 'disabled' ?> disabled>
                                        <span style="color: <?= ($correctAnswer == $index + 1) ? 'white' : 'black' ?>;">
                                            <?= $choice ?>
                                        </span>
                                    </div>
                                <?php endforeach;
                            else: // For traditional questions
                                $correctAns = $row['correctAns'] ?? '';
                                $ans = $row['ans'] ?? ''; ?>

                                <?php
                                // Ensure we have a valid correct answer and user answer
                                $correctAns = $row['correctAns'] ?? '';
                                $ans = $row['ans'] ?? '';

                                foreach ($choices as $key => $value): ?>
                                    <div
                                        style="margin-bottom: 10px; padding: 10px; background-color: <?= ($correctAns == $key + 1) ? '#00B11B' : '#F2F2F2' ?>; border-radius: 4px;">
                                        <input type="radio" name="selected_ans" value="<?= $key + 1 ?>" style="cursor: pointer;"
                                            <?= ($ans == $key + 1) ? 'checked' : '' ?> disabled>
                                        <span
                                            style="font-size: 16px; color: <?= ($correctAns == $key + 1) ? 'white' : 'black' ?>;">
                                            <?= htmlspecialchars($value) ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                                <?php
                            endif; ?>
                            <div class="container">
                                <div class="box-container">
                                    <div class="box">
                                        <b style="color: #0B2557; font-size: 20px;">Tags:</b><br><br>
                                        <div style="column-count: 2; column-gap: 40px;">
                                            <p>Subject: <b><?= htmlspecialchars($topics1) ?></b></p>

                                            <p>Difficulty level: <b><?= htmlspecialchars($dlevel) ?></b></p>
                                            <!-- Added Difficulty Level -->
                                            <p>NCLEX client needs category: <b><?= htmlspecialchars($cnc) ?></b></p>
                                            <!-- Added NCLEX Category -->

                                            <p>Time taken: <b><?= htmlspecialchars($timeTaken) ?> Sec</b></p>
                                            <p>System: <b><?= htmlspecialchars($system) ?></b></p>
                                            <p>Question ID:
                                                <b><?= str_pad(htmlspecialchars($questionId), 5, '0', STR_PAD_LEFT) ?></b>
                                            </p>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>


                    </div>




                    <div class="content2ab">
                        <form>
                            <p><?= nl2br(htmlspecialchars(str_replace('\\n', "\n", $rationale))) ?></p>
                            <div class="container">
                                <div class="box-container">
                                    <div class="box">
                                        <b style="color: #0B2557; font-size: 20px;">Narcan Additional Notes:</b><br><br>
                                        <p><?= nl2br(htmlspecialchars(str_replace('\\n', "\n", $narcan))) ?></p>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/loading.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/sweet.min.js"></script>
    <script src="../assets/js/disable.js"></script>
</body>

</html>