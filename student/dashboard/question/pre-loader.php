<?php
// pre-loader.php

include '../../../config.php';

$topics1  = isset($_GET['topics1'])  ? $_GET['topics1']  : '';
$topics2  = isset($_GET['topics2'])  ? $_GET['topics2']  : '';
$kilanlan = isset($_GET['kilanlan']) ? $_GET['kilanlan'] : '';
$id       = isset($_GET['id'])       ? $_GET['id']       : '';

$stmt = $con->prepare("SELECT examTaken FROM login WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result   = $stmt->get_result();
$examTaken = $result->fetch_assoc()['examTaken'] ?? '';

if ($examTaken === null) {
    echo "No examTaken value found for user ID: $id";
    exit;
}

$stmt = $con->prepare("SELECT COUNT(*) as count FROM review WHERE examTaken = ? AND studentId = ?");
$stmt->bind_param("si", $examTaken, $id);
$stmt->execute();
$count = $stmt->get_result()->fetch_assoc()['count'] ?? 0;

if ($count > 0) {
    $stmt = $con->prepare("DELETE FROM review WHERE examTaken = ? AND studentId = ?");
    $stmt->bind_param("si", $examTaken, $id);
    $stmt->execute();
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Loading…</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script>localStorage.removeItem('count'); localStorage.removeItem('timer_paused');</script>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
  height: 100%;
  font-family: 'Inter', sans-serif;
  background: linear-gradient(135deg, #0b1929 0%, #0f172a 55%, #0c2340 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.pl-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0;
  text-align: center;
  padding: 0 24px;
}

.pl-logo {
  width: 140px;
  height: auto;
  filter: brightness(0) invert(1);
  opacity: 0;
  transform: scale(0.85) translateY(12px);
  animation: logoReveal 1s cubic-bezier(0.4, 0, 0.2, 1) 0.2s forwards;
  margin-bottom: 32px;
}

@keyframes logoReveal {
  to { opacity: 1; transform: scale(1) translateY(0); }
}

.pl-bar-wrap {
  width: 200px;
  height: 3px;
  background: rgba(255,255,255,0.1);
  border-radius: 10px;
  overflow: hidden;
  margin-bottom: 20px;
}

.pl-bar {
  height: 100%;
  width: 0%;
  background: linear-gradient(90deg, #007CBF, #0d9488);
  border-radius: 10px;
  animation: barFill 2.6s ease-in-out 0.5s forwards;
}

@keyframes barFill {
  0%   { width: 0%; }
  40%  { width: 65%; }
  80%  { width: 92%; }
  100% { width: 100%; }
}

.pl-label {
  font-size: 0.82rem;
  color: rgba(255,255,255,0.45);
  font-weight: 500;
  letter-spacing: 0.2px;
  margin-bottom: 32px;
  opacity: 0;
  animation: fadeUp 0.7s ease 0.8s forwards;
}

.pl-tip {
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 12px;
  padding: 14px 20px;
  max-width: 320px;
  opacity: 0;
  animation: fadeUp 0.7s ease 1.2s forwards;
}

.pl-tip-label {
  font-size: 0.65rem;
  font-weight: 700;
  color: #0d9488;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  margin-bottom: 6px;
}

.pl-tip-text {
  font-size: 0.78rem;
  color: rgba(255,255,255,0.55);
  line-height: 1.55;
}

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}
</style>
</head>
<body>
  <div class="pl-wrap">
    <img class="pl-logo" src="../../../assets/STUDIUM.svg" alt="Studium">
    <div class="pl-bar-wrap">
      <div class="pl-bar"></div>
    </div>
    <div class="pl-label">Setting up your study session…</div>
    <div class="pl-tip">
      <div class="pl-tip-label">Quick Tip</div>
      <div class="pl-tip-text">Read each question carefully. You will receive your results and score after completing all 150 questions.</div>
    </div>
  </div>

  <script>
    function startQuiz() {
      const topics1  = "<?php echo addslashes($topics1); ?>";
      const topics2  = "<?php echo addslashes($topics2); ?>";
      const kilanlan = "<?php echo addslashes($kilanlan); ?>";
      const id       = "<?php echo addslashes($id); ?>";
      window.location.href = `index.php?step=1&topics1=${topics1}&topics2=${topics2}&kilanlan=${kilanlan}&id=${id}`;
    }
    setTimeout(startQuiz, 3000);
  </script>
</body>
</html>
