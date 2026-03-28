<?php
include '../../config.php'; // Your database connection details

// Function to safely fetch and display topics
function getTopics($con, $selectedConcept) {
    //Check for valid database connection
    if (!$con) {
        error_log("Database connection error: " . mysqli_connect_error());
        return "Database connection failed.";
    }

    $topicsPerColumn = 3;
    $topics = [];

    // Prepare the statement to fetch distinct topics.  This prevents SQL injection.
    $stmt = $con->prepare("SELECT DISTINCT id, system, topics1 FROM question");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            while ($system = $result->fetch_assoc()) {
                $normalizedSystemName = strtolower(trim($system['system']));
                if (!in_array($normalizedSystemName, $topics)) {
                    $topics[] = $normalizedSystemName;
                    $value = $system['id'] . '|' . urlencode($system['system']);

                    // Prepare the statement to count questions for each topic and concept. This prevents SQL injection.
                    $countStmt = $con->prepare("SELECT COUNT(*) AS count FROM question WHERE system = ? AND topics1 = ?");
                    if ($countStmt) {
                        $countStmt->bind_param("ss", $system['system'], $selectedConcept);
                        $countStmt->execute();
                        $countResult = $countStmt->get_result();
                        $countRow = $countResult->fetch_assoc();
                        $count = $countRow['count'];
                        $countStmt->close();
                    } else {
                        error_log("Prepare failed: (" . $con->errno . ") " . $con->error);
                        return "Error preparing count statement.";
                    }


                    // Output the checkbox
                    static $columnCount = 0;
                    if ($columnCount % $topicsPerColumn == 0) {
                        echo '<div class="col-md-3">';
                    }
                    $disabled = empty($selectedConcept) ? 'disabled' : ($count > 0 ? '' : 'disabled');
                    echo "<label>
                            <input type='checkbox' class='topicCheckbox' value='{$value}' data-count='{$count}' $disabled> 
                            {$system['system']} ({$count})
                          </label><br>";
                    $columnCount++;
                    if ($columnCount % $topicsPerColumn == 0) {
                        echo '</div>';
                    }
                }
            }
            if ($columnCount % $topicsPerColumn != 0) {
                echo '</div>';
            }
            echo "<span id='totalCount'></span>";
            $stmt->close();
        } else {
            error_log("Get result failed: (" . $con->errno . ") " . $con->error);
            return "Error fetching topics.";
        }
    } else {
        error_log("Prepare failed: (" . $con->errno . ") " . $con->error);
        return "Error preparing statement.";
    }
    return null; //Indicates successful execution
}

if (isset($_GET['topics1'])) {
    $selectedConcept = mysqli_real_escape_string($con, $_GET['topics1']); //Sanitize input
    $error = getTopics($con, $selectedConcept);
    if ($error) {
        echo $error;
    }
} else {
    echo "Please select a concept.";
}

?>