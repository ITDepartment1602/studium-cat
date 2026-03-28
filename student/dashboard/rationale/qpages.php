    <?php
    include '../../../config.php'; // Include your database configuration

    // Retrieve GET parameters
    $isCorrect = $_GET['isCorrect'] ?? null;
    $questionId = $_GET['questionId'] ?? null;
    $topics1 = $_GET['topics1'] ?? '';
    $system = $_GET['system'] ?? '';
    $cnc = $_GET['cnc'] ?? '';
    $timeTaken = $_GET['timeTaken'] ?? 0;
    $correctAns = $_GET['correctAns'] ?? '';
    $questionNumber = $_GET['questionNumber'] ?? '';

    // Initialize variables
    $rationale = '';
    $question = '';
    $choices = [];
    $narcan = '';
    $dlevel = ''; // Difficulty level variable

    // Fetch question and choices from the review table
    // Fetch question and choices from the review table
    if (!empty($questionId)) {
        // Fetch question details
        $stmt = $con->prepare("SELECT * FROM review WHERE questionId = ?");
        $stmt->bind_param("i", $questionId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
        

            // Fetch related question details
            $stmt = $con->prepare("SELECT question, rationale, options, choiceA, choiceB, choiceC, choiceD, narcan, dlevel, type FROM question WHERE id = ?");
            $stmt->bind_param("i", $questionId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $question = $row['question'] ?? 'Question not found';
                $rationale = $row['rationale'] ?? 'No rationale available';
                $options = json_decode($row['options'], true) ?? []; // Ensure this is correctly decoded
                
                // Fetch choices
                $choices = [
                    'A' => $row['choiceA'] ?? '',
                    'B' => $row['choiceB'] ?? '',
                    'C' => $row['choiceC'] ?? '',
                    'D' => $row['choiceD'] ?? '',
                ];
                $narcan = $row['narcan'] ?? 'No additional notes available';
                $dlevel = $row['dlevel'] ?? 'Not specified';
                $questionType = $row['type'] ?? ''; // Get question type
            }
        } else {
            $question = 'No question found in review';
        }
    }

    // Display the question and options
    ?>

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

        <!--==================== HEADER ====================-->
        <div class="containerab">
            <!--==================== Content ====================-->
            <div class="subContainerab">
                <div class="main_containerab">
                    <div class="contentab">
                        <!--==================== Content Left ====================-->
                        <div class="content1ab">
        <br>
        <form>
            <b style="font-size: 20px;">Question <?= htmlspecialchars($questionNumber) ?></b><br><br>
            <p><?= nl2br(htmlspecialchars($question)) ?></p>
            <br><br>

            <!-- QUESTION Choices -->
            <?php
            $ans = $_GET['ans'] ?? '';
            $correctAnswer = json_decode($correctAns, true) ?? [];
            // Retrieve user's answers from URL
            $selectedAnswer = isset($_GET['ans']) ? (array) json_decode($_GET['ans'], true) : [];
            $choices = [
                htmlspecialchars($row['choiceA']),
                htmlspecialchars($row['choiceB']),
                htmlspecialchars($row['choiceC']),
                htmlspecialchars($row['choiceD']),
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
                        <input type="checkbox" name="ans[]" value="<?= $index ?>" 
                            <?= in_array($index, $selectedAnswer) ? 'checked' : 'disabled' ?> 
                            style="margin-right: 10px; cursor: pointer;" disabled>
                        
                        <!-- Option Text -->
                        <span style="flex-grow: 1; color: <?= in_array($index, $correctAnswer) ? 'white' : 'black' ?>;">
                            <?= htmlspecialchars($option) ?>
                        </span>
                    </li>
                <?php endforeach;
        
            else: // For traditional questions
                $correctAns = $_GET['correctAns'] ?? '';
                $ans = $_GET['ans'] ?? '';
            

                foreach ($choices as $key => $value): ?>
                    <div style="margin-bottom: 10px; padding: 10px; background-color: <?= ($correctAns == $key + 1) ? '#00B11B' : '#F2F2F2' ?>; border-radius: 4px;">
                        <input type="radio" name="selected_ans" value="<?= $key + 1 ?>" style="cursor: pointer;"
                            <?= ($ans == $key + 1) ? 'checked' : '' ?> disabled>
                        <span style="font-size: 16px; color: <?= ($correctAns == $key + 1) ? 'white' : 'black' ?>;">
                            <?= htmlspecialchars($value) ?>
                        </span>
                    </div>
                <?php endforeach;
            endif; ?>

            <div class="container">
                <div class="box-container">
                    <div class="box">
                        <b style="color: #0B2557; font-size: 20px;">Tags:</b><br><br>
                        <div style="column-count: 2; column-gap: 40px;">
                            <p>Subject: <b><?= htmlspecialchars($topics1) ?></b></p>
                            <p>Difficulty level: <b><?= htmlspecialchars($dlevel) ?></b></p>
                            <p>NCLEX client needs category: <b><?= htmlspecialchars($cnc) ?></b></p>
                            <p>Time taken: <b><?= htmlspecialchars($timeTaken) ?> Sec</b></p>
                            <p>System: <b><?= htmlspecialchars($system) ?></b></p>
                            <p>Question ID: <b><?= str_pad(htmlspecialchars($questionId), 5, '0', STR_PAD_LEFT) ?></b></p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

                        <!--==================== Content Right ====================-->
                        <div class="content2ab">
                            <form>

                            <p><?= nl2br(htmlspecialchars(str_replace('\n', "\n", $rationale))) ?></p>



                                <div class="container">
                                    <div class="box-container">
                                        <div class="box">
                                            <b style="color: #0B2557; font-size: 20px;">NARC Additional Notes:</b><br><br>

                                            <p><?= nl2br(htmlspecialchars(str_replace('\n', "\n", $narcan))) ?></p>

                                        </div>
                                    </div>
                                </div>


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
        <script src="../assets/js/disable.js"></script>

    </body>

    </html>