<?php
// Clock.php
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counting Clock</title>
</head>

<body>

    <div id="counter">00:00:00</div>

    <script>
        // Retrieve the total seconds from local storage or start from 0
        let totalSeconds = parseInt(localStorage.getItem('count')) || 0;

        function updateCounter() {
            totalSeconds++;
            localStorage.setItem('count', totalSeconds); // Store the total seconds in local storage

            // Calculate hours, minutes, and seconds
            const hours = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
            const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
            const seconds = String(totalSeconds % 60).padStart(2, '0');

            // Update the counter display
            document.getElementById('counter').textContent = `${hours}:${minutes}:${seconds}`;
        }

        // Display the initial time

        setInterval(updateCounter, 1000); // Update every second

        // Function to get total time in seconds
        function getTotalTime() {
            return totalSeconds;
        }

        // Exposing the function to the global scope
        window.getTotalTime = getTotalTime;
    </script>

</body>

</html>