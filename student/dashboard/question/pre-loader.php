<?php
// pre-loader.php

// Include the config file for database connection
include '../../../config.php';

// Parse the URL parameters safely
$topics1 = isset($_GET['topics1']) ? $_GET['topics1'] : '';
$topics2 = isset($_GET['topics2']) ? $_GET['topics2'] : '';
$kilanlan = isset($_GET['kilanlan']) ? $_GET['kilanlan'] : '';
$id = isset($_GET['id']) ? $_GET['id'] : '';

// Fetch examTaken from the login table
$stmt = $con->prepare("SELECT examTaken FROM login WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$examTaken = $result->fetch_assoc()['examTaken'] ?? '';

if ($examTaken === null) {
    echo "No examTaken value found for user ID: $id";
    exit; // Stop further execution
}
// Check for existing records in the review table
$stmt = $con->prepare("SELECT COUNT(*) as count FROM review WHERE examTaken = ? AND studentId = ?");
$stmt->bind_param("si", $examTaken, $id);
$stmt->execute();
$result = $stmt->get_result();
$count = $result->fetch_assoc()['count'] ?? 0;

if ($count == 0) {
   
} else {
    // Delete records from the review table where examTaken matches and studentId matches
    $stmt = $con->prepare("DELETE FROM review WHERE examTaken = ? AND studentId = ?");
    $stmt->bind_param("si", $examTaken, $id);
    $stmt->execute();

}

// Close the statement and connection
$stmt->close();
$con->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<style>
body, html {
    height: 100%;
    margin: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
}

#pre-loader {
    background: #fff url(../../../img/NARC.gif) no-repeat center center;
    background-size: 10%;
    height: 100vh;
    width: 100%;
    position: fixed;
    z-index: 100;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
}

#start {
    position: relative;
    z-index: 101;
    margin-top: 20px;
    cursor: pointer;
}
.note {
  font-family: "Dangrek", sans-serif;
  font-weight: 400;
  font-style: normal;
}

</style>
<title>Loading...</title>   
<script>
        localStorage.removeItem('count');
    </script>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Dangrek&family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
</head>

<body>
    <div id="pre-loader">
        <p class="mt-56 note">Note: You will receive your results and score only upon completing 150 questions.
</p>
    </div>
    
    <button id="start" style="display: none;" onclick="startQuiz()">Start</button>

    <script>
        function startQuiz() {
            const topics1 = "<?php echo addslashes($topics1); ?>";
            const topics2 = "<?php echo addslashes($topics2); ?>";
            const kilanlan = "<?php echo addslashes($kilanlan); ?>";
            const id = "<?php echo addslashes($id); ?>";
            window.location.href = `question1.php?topics1=${topics1}&topics2=${topics2}&kilanlan=${kilanlan}&id=${id}`;
        }

        // Automatically redirect after 3 seconds
        setTimeout(startQuiz, 3000);
    </script>
</body>
</html>