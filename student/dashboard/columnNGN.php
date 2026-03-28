<?php
include "../../config.php";

// fetch 1 random NGN Column question
$query = mysqli_query($con, "SELECT * FROM NGNcolumn ORDER BY RAND() LIMIT 1");
$data = mysqli_fetch_assoc($query);

// decode JSON fields
$columns = json_decode($data['columns'], true);
$rows = json_decode($data['rows'], true);
$correct = json_decode($data['correct'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>NGN Column Question</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f4f4f4;
        padding: 20px;
    }
    .container {
        background: white;
        padding: 20px;
        border-radius: 12px;
        max-width: 900px;
        margin: auto;
        box-shadow: 0 0 12px rgba(0,0,0,0.1);
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    th, td {
        padding: 12px;
        text-align: center;
        border-bottom: 1px solid #ddd;
    }
    th {
        background: #0066ff;
        color: white;
        border-radius: 5px;
    }
    .row-title {
        text-align: left;
        font-weight: bold;
        width: 35%;
    }
    .question-box {
        padding: 15px;
        background: #eef4ff;
        border-radius: 10px;
        margin-bottom: 20px;
        border-left: 5px solid #0066ff;
    }
    input[type="radio"] {
        transform: scale(1.3);
        cursor: pointer;
    }
    .submit-btn {
        background: #0066ff;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 10px;
        font-size: 18px;
        cursor: pointer;
        margin-top: 20px;
        width: 100%;
    }
</style>
</head>
<body>

<div class="container">

    <div class="question-box">
        <h3><?php echo $data['question']; ?></h3>
    </div>

    <form method="POST">

        <table>
            <tr>
                <th>Assessment Findings</th>
                <?php 
                    // create column headers
                    foreach ($columns as $col) {
                        echo "<th>$col</th>";
                    }
                ?>
            </tr>

            <?php
            // generate rows with radio buttons
            $rowNumber = 1;
            foreach ($rows as $row) {
                echo "<tr>";
                echo "<td class='row-title'>$row</td>";

                // generate radio buttons per column
                $colNumber = 1;
                foreach ($columns as $col) {
                    echo "
                    <td>
                        <input type='radio' name='row$rowNumber' value='{$rowNumber}-{$colNumber}'>
                    </td>";
                    $colNumber++;
                }

                echo "</tr>";
                $rowNumber++;
            }
            ?>
        </table>

        <button class="submit-btn" name="submit">Submit</button>

    </form>

</div>

<?php
// check answers
if (isset($_POST['submit'])) {

    echo "<div class='container' style='margin-top:20px;background:#e8f7e8;border-left:6px solid #28a745;'>";

    $score = 0;
    $total = count($rows);

    echo "<h3>Results:</h3>";

    foreach ($rows as $i => $row) {
        $index = $i + 1; 
        $answer = $_POST["row$index"] ?? "No Answer";

        if ($answer == $correct[$i]) {
            echo "<p><b>$row:</b> Correct ✔️</p>";
            $score++;
        } else {
            echo "<p><b>$row:</b> Incorrect Correct: ".$correct[$i].")</p>";
        }
    }

    echo "<h2>Score: $score / $total</h2>";
    echo "</div>";
}
?>

</body>
</html>