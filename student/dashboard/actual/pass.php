
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/pass.css">
    <title>Examination Results</title>
</head>
<body>
    <div class="container">
      <main>
        <div class="exam-results">
            <div class="exam-summary">
                <div class="checkmark" id="checkmark">&#10004;</div>
                <h1><span class="passed">Passed</span></h1>
            </div>

            <div class="progress-circle" data-score="85">
                <div class="progress-circle-text">0%</div>
                <svg class="circle-svg" width="200" height="200" viewBox="0 0 200 200">
                    <circle class="circle-background" cx="100" cy="100" r="90" />
                    <circle class="circle-progress" cx="100" cy="100" r="90" />
                </svg>
            </div>

            <p class="cngs">
                <i><center>Congratulations! You have successfully passed the exam with a great performance. Keep up the good work!</i></center>
            </p>
        </div>
      </main>
    </div>

    <script>
        const progressCircle = document.querySelector('.progress-circle');
        const percentageText = document.querySelector('.progress-circle-text');
        const score = progressCircle.getAttribute('data-score');
        const circleProgress = document.querySelector('.circle-progress');
        const circleLength = circleProgress.getTotalLength();
        const checkmark = document.getElementById('checkmark');
        circleProgress.style.strokeDasharray = circleLength;

        function animateProgressBar() {
            let currentPercentage = 0;
            const interval = setInterval(() => {
                currentPercentage++;
                const offset = circleLength - (circleLength * currentPercentage) / 100;
                circleProgress.style.strokeDashoffset = offset;
                percentageText.textContent = `${currentPercentage}%`;

                if (currentPercentage === 85) {
                    checkmark.style.display = 'block';
                }

                if (currentPercentage >= score) {
                    clearInterval(interval);
                }
            }, 30);
        }

        window.addEventListener('load', animateProgressBar);
    </script>
</body>
</html>
